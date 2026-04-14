(function ($) {
  "use strict";

  var auroraDictationCounter = 0;
  var auroraDictationState = {
    activeControl: null,
    activeStatus: null,
    activeTarget: null,
    activeEditorId: null,
    isRecording: false,
    mode: null,
    recognition: null,
    mediaRecorder: null,
    mediaStream: null,
    mediaChunks: [],
  };

  var SpeechRecognition =
    window.SpeechRecognition || window.webkitSpeechRecognition;

  function ensureAuroraDictationStyles() {
    if ($("#aurora-dictation-styles").length) {
      return;
    }

    $("head").append(
      '<style id="aurora-dictation-styles">' +
        ".aurora-dictation-control{display:flex;align-items:center;gap:10px;margin-top:8px;flex-wrap:wrap;}" +
        ".aurora-dictation-control--editor{margin-bottom:8px;}" +
        ".aurora-dictation-btn{display:inline-flex;align-items:center;gap:8px;border:1px solid #d0d7e2;background:#fff;color:#1f2937;border-radius:8px;padding:6px 12px;font-size:12px;font-weight:600;line-height:1.2;cursor:pointer;transition:all .2s ease;}" +
        ".aurora-dictation-btn:hover{background:#f8fafc;border-color:#b8c4d6;}" +
        ".aurora-dictation-btn.is-recording{background:#1f2937;color:#fff;border-color:#1f2937;}" +
        ".aurora-dictation-status{font-size:12px;color:#6b7280;}" +
      "</style>"
    );
  }

  function isDictationCompatibleInput($field) {
    if (!$field.length) {
      return false;
    }

    if ($field.data("auroraDictationBound")) {
      return false;
    }

    if ($field.is("[disabled],[readonly]")) {
      return false;
    }

    if (!$field.is(":visible")) {
      return false;
    }

    if ($field.hasClass("tinymce") || $field.hasClass("tinymce-manual")) {
      return false;
    }

    if ($field.closest(".aurora-dictation-control").length) {
      return false;
    }

    return true;
  }

  function isSupportedInputType(type) {
    var normalized = (type || "text").toLowerCase();
    return (
      normalized === "text" ||
      normalized === "search" ||
      normalized === "email" ||
      normalized === "url" ||
      normalized === "tel"
    );
  }

  function getTargetId($field) {
    var currentId = $field.attr("id");

    if (!currentId) {
      auroraDictationCounter++;
      currentId = "aurora-dictation-target-" + auroraDictationCounter;
      $field.attr("id", currentId);
    }

    return currentId;
  }

  function renderDictationControl($anchor, dataAttrs, extraClass) {
    var attrs = "";
    var key;

    for (key in dataAttrs) {
      if (Object.prototype.hasOwnProperty.call(dataAttrs, key)) {
        attrs += " " + key + '="' + dataAttrs[key] + '"';
      }
    }

    var html =
      '<div class="aurora-dictation-control ' +
      (extraClass || "") +
      '">' +
      '<button type="button" class="aurora-dictation-btn"' +
      attrs +
      '>' +
      '<i class="fa-solid fa-microphone"></i>' +
      "<span>Dictation Mic</span>" +
      "</button>" +
      '<span class="aurora-dictation-status"></span>' +
      "</div>";

    $anchor.after(html);
  }

  function attachDictationToField(field) {
    var $field = $(field);
    var tagName = ($field.prop("tagName") || "").toLowerCase();

    if (!isDictationCompatibleInput($field)) {
      return;
    }

    if (
      tagName === "input" &&
      !isSupportedInputType($field.attr("type"))
    ) {
      return;
    }

    var targetId = getTargetId($field);
    var $anchor = $field.closest(".input-group");

    if (!$anchor.length) {
      $anchor = $field;
    }

    renderDictationControl($anchor, { "data-target-id": targetId }, "");
    $field.data("auroraDictationBound", true);
  }

  function attachDictationToEditor(editor) {
    if (!editor || !editor.id || editor.removed) {
      return;
    }

    var $textarea = $("#" + editor.id);

    if (!$textarea.length || $textarea.data("auroraEditorDictationBound")) {
      return;
    }

    var container = editor.getContainer ? editor.getContainer() : null;

    if (!container || !$(container).is(":visible")) {
      return;
    }

    renderDictationControl($(container), { "data-editor-id": editor.id }, "aurora-dictation-control--editor");
    $textarea.data("auroraEditorDictationBound", true);
  }

  function scanAuroraDictationTargets(context) {
    var $context = context ? $(context) : $(document.body);

    $context
      .find(
        'textarea, input[type="text"], input[type="search"], input[type="email"], input[type="url"], input[type="tel"]'
      )
      .each(function () {
        attachDictationToField(this);
      });

    $context.find('[contenteditable="true"]').each(function () {
      var $field = $(this);
      if ($field.data("auroraDictationBound") || !$field.is(":visible")) {
        return;
      }

      auroraDictationCounter++;
      var contentId =
        $field.attr("id") ||
        "aurora-dictation-contenteditable-" + auroraDictationCounter;
      $field.attr("id", contentId);
      renderDictationControl($field, { "data-target-id": contentId }, "");
      $field.data("auroraDictationBound", true);
    });

    if (typeof tinymce !== "undefined" && tinymce.editors) {
      $.each(tinymce.editors, function (index, editor) {
        attachDictationToEditor(editor);
      });
    }
  }

  function setControlState($control, isRecording, message) {
    if (!$control || !$control.length) {
      return;
    }

    var $icon = $control.find("i");
    var $label = $control.find("span");
    var $status = $control
      .closest(".aurora-dictation-control")
      .find(".aurora-dictation-status");

    $control.toggleClass("is-recording", isRecording);
    $icon
      .toggleClass("fa-microphone", !isRecording)
      .toggleClass("fa-stop", isRecording);
    $label.text(isRecording ? "Stop Dictation" : "Dictation Mic");
    $status.text(message || "");
  }

  function clearActiveState() {
    if (auroraDictationState.activeControl) {
      setControlState(auroraDictationState.activeControl, false, "");
    }

    auroraDictationState.activeControl = null;
    auroraDictationState.activeStatus = null;
    auroraDictationState.activeTarget = null;
    auroraDictationState.activeEditorId = null;
    auroraDictationState.isRecording = false;
    auroraDictationState.mode = null;
  }

  function insertIntoInput(element, text) {
    var normalized = $.trim(text);
    if (!normalized) {
      return;
    }

    var currentValue = element.value || "";
    var start =
      typeof element.selectionStart === "number"
        ? element.selectionStart
        : currentValue.length;
    var end =
      typeof element.selectionEnd === "number"
        ? element.selectionEnd
        : currentValue.length;
    var prefix = currentValue.substring(0, start);
    var suffix = currentValue.substring(end);
    var spacer = prefix.length > 0 && !/\s$/.test(prefix) ? " " : "";
    var addition = spacer + normalized + " ";

    element.value = prefix + addition + suffix;
    element.focus();

    if (typeof element.setSelectionRange === "function") {
      var caret = (prefix + addition).length;
      element.setSelectionRange(caret, caret);
    }

    $(element).trigger("input").trigger("change");
  }

  function insertIntoContentEditable(element, text) {
    var normalized = $.trim(text);
    if (!normalized) {
      return;
    }

    element.focus();
    document.execCommand("insertText", false, normalized + " ");
  }

  function insertTranscript(text) {
    var normalized = $.trim(text);

    if (!normalized) {
      return;
    }

    if (
      auroraDictationState.activeEditorId &&
      typeof tinymce !== "undefined"
    ) {
      var editor = tinymce.get(auroraDictationState.activeEditorId);
      if (editor) {
        editor.focus();
        editor.insertContent($("<div>").text(normalized + " ").html());
        return;
      }
    }

    if (!auroraDictationState.activeTarget) {
      return;
    }

    var target = auroraDictationState.activeTarget;
    var tagName = (target.tagName || "").toLowerCase();

    if (tagName === "textarea" || tagName === "input") {
      insertIntoInput(target, normalized);
      return;
    }

    if ($(target).attr("contenteditable") === "true") {
      insertIntoContentEditable(target, normalized);
    }
  }

  function stopMediaStream() {
    if (!auroraDictationState.mediaStream) {
      return;
    }

    $.each(auroraDictationState.mediaStream.getTracks(), function (
      index,
      track
    ) {
      track.stop();
    });

    auroraDictationState.mediaStream = null;
  }

  function uploadRecordedAudio(blob) {
    var formData = new FormData();
    formData.append("audio", blob, "aurora-dictation.webm");

    if (auroraDictationState.activeControl) {
      setControlState(
        auroraDictationState.activeControl,
        false,
        "Transcribing dictation..."
      );
    }

    $.ajax({
      url: admin_url + "ai/transcribe_audio",
      method: "POST",
      data: formData,
      processData: false,
      contentType: false,
    })
      .done(function (response) {
        var result = typeof response === "string" ? JSON.parse(response) : response;

        if (result.success && result.transcript) {
          insertTranscript(result.transcript);
        }

        clearActiveState();
      })
      .fail(function (error) {
        var result = {};

        try {
          result = JSON.parse(error.responseText);
        } catch (err) {
          result = {};
        }

        alert_float(
          "warning",
          result.error
            ? result.error
            : "The dictation recording could not be transcribed."
        );
        clearActiveState();
      });
  }

  function stopActiveDictation() {
    if (!auroraDictationState.isRecording) {
      clearActiveState();
      return;
    }

    if (
      auroraDictationState.mode === "browser" &&
      auroraDictationState.recognition
    ) {
      auroraDictationState.recognition.stop();
      return;
    }

    if (
      auroraDictationState.mode === "server" &&
      auroraDictationState.mediaRecorder
    ) {
      auroraDictationState.mediaRecorder.stop();
      return;
    }

    clearActiveState();
  }

  function startBrowserDictation() {
    var recognition = new SpeechRecognition();

    auroraDictationState.recognition = recognition;
    auroraDictationState.mode = "browser";
    auroraDictationState.isRecording = true;

    recognition.continuous = true;
    recognition.interimResults = true;
    recognition.lang = document.documentElement.lang || "en-US";

    recognition.onstart = function () {
      setControlState(
        auroraDictationState.activeControl,
        true,
        "Dictation is listening..."
      );
    };

    recognition.onresult = function (event) {
      var interimTranscript = "";
      var i;

      for (i = event.resultIndex; i < event.results.length; i++) {
        var transcript = event.results[i][0].transcript;
        if (event.results[i].isFinal) {
          insertTranscript(transcript);
        } else {
          interimTranscript += transcript;
        }
      }

      setControlState(
        auroraDictationState.activeControl,
        true,
        interimTranscript
          ? "Listening: " + interimTranscript
          : "Dictation is listening..."
      );
    };

    recognition.onerror = function (event) {
      alert_float(
        "warning",
        event.error === "not-allowed"
          ? "Microphone access was blocked."
          : "Dictation stopped unexpectedly."
      );
      clearActiveState();
    };

    recognition.onend = function () {
      clearActiveState();
    };

    recognition.start();
  }

  function startServerDictation() {
    navigator.mediaDevices
      .getUserMedia({ audio: true })
      .then(function (stream) {
        var mediaRecorder = new MediaRecorder(stream);

        auroraDictationState.mediaStream = stream;
        auroraDictationState.mediaRecorder = mediaRecorder;
        auroraDictationState.mediaChunks = [];
        auroraDictationState.mode = "server";
        auroraDictationState.isRecording = true;

        mediaRecorder.ondataavailable = function (event) {
          if (event.data && event.data.size > 0) {
            auroraDictationState.mediaChunks.push(event.data);
          }
        };

        mediaRecorder.onstop = function () {
          var mimeType = mediaRecorder.mimeType || "audio/webm";
          var audioBlob = new Blob(auroraDictationState.mediaChunks, {
            type: mimeType,
          });

          stopMediaStream();
          auroraDictationState.mediaRecorder = null;
          auroraDictationState.mediaChunks = [];
          auroraDictationState.mode = null;
          auroraDictationState.isRecording = false;
          uploadRecordedAudio(audioBlob);
        };

        mediaRecorder.start();
        setControlState(
          auroraDictationState.activeControl,
          true,
          "Dictation is recording..."
        );
      })
      .catch(function () {
        alert_float("warning", "Microphone access was blocked.");
        clearActiveState();
      });
  }

  function beginDictationForControl($button) {
    var targetId = $button.attr("data-target-id");
    var editorId = $button.attr("data-editor-id");
    var target = null;

    if (auroraDictationState.isRecording) {
      stopActiveDictation();
    }

    if (editorId) {
      auroraDictationState.activeEditorId = editorId;
    } else if (targetId) {
      target = document.getElementById(targetId);
      auroraDictationState.activeTarget = target;
    }

    auroraDictationState.activeControl = $button;

    if (SpeechRecognition) {
      startBrowserDictation();
      return;
    }

    if (
      navigator.mediaDevices &&
      typeof navigator.mediaDevices.getUserMedia === "function" &&
      typeof window.MediaRecorder !== "undefined"
    ) {
      startServerDictation();
      return;
    }

    alert_float(
      "warning",
      "Speech recognition is not supported in this browser."
    );
    clearActiveState();
  }

  function initAuroraDictation() {
    ensureAuroraDictationStyles();
    scanAuroraDictationTargets(document.body);

    $("body")
      .off("click.auroraDictation", ".aurora-dictation-btn")
      .on("click.auroraDictation", ".aurora-dictation-btn", function () {
        var $button = $(this);

        if (
          auroraDictationState.isRecording &&
          auroraDictationState.activeControl &&
          auroraDictationState.activeControl.is($button)
        ) {
          stopActiveDictation();
          return;
        }

        beginDictationForControl($button);
      });

    $(document)
      .off("focusin.auroraDictation")
      .on(
        "focusin.auroraDictation",
        'textarea, input[type="text"], input[type="search"], input[type="email"], input[type="url"], input[type="tel"], [contenteditable="true"]',
        function () {
          scanAuroraDictationTargets($(this).closest("form, .modal, .panel, body"));
        }
      );

    $(document)
      .off("shown.bs.modal.auroraDictation shown.bs.tab.auroraDictation")
      .on("shown.bs.modal.auroraDictation shown.bs.tab.auroraDictation", function (event) {
        scanAuroraDictationTargets(event.target);
      });

    if (window.MutationObserver) {
      var observer = new MutationObserver(function (mutations) {
        $.each(mutations, function (index, mutation) {
          if (mutation.addedNodes && mutation.addedNodes.length) {
            scanAuroraDictationTargets(mutation.target);
          }
        });
      });

      observer.observe(document.body, {
        childList: true,
        subtree: true,
      });
    }

    setInterval(function () {
      scanAuroraDictationTargets(document.body);
    }, 2500);

    $(window).on("beforeunload", function () {
      stopActiveDictation();
    });
  }

  $(function () {
    initAuroraDictation();
  });
})(jQuery);
