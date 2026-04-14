(function () {
  "use strict";

  var state = {
    activeButton: null,
    activeTarget: null,
    recognition: null,
    isListening: false,
  };

  var SpeechRecognition =
    window.SpeechRecognition || window.webkitSpeechRecognition;

  function ensureStyles() {
    if (document.getElementById("dictation-mic-styles")) {
      return;
    }

    var style = document.createElement("style");
    style.id = "dictation-mic-styles";
    style.textContent =
      ".dictation-mic-wrap{display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-top:8px;}" +
      ".dictation-mic-btn{display:inline-flex;align-items:center;gap:8px;padding:8px 12px;border-radius:999px;border:1px solid rgba(15,23,42,.12);background:#fff;color:#0f172a;font-size:12px;font-weight:600;line-height:1;cursor:pointer;transition:all .2s ease;}" +
      ".dictation-mic-btn:hover{background:#f8fafc;border-color:rgba(15,23,42,.24);}" +
      ".dictation-mic-btn.is-listening{background:#0f172a;color:#fff;border-color:#0f172a;}" +
      ".dictation-mic-status{font-size:12px;color:#64748b;}";
    document.head.appendChild(style);
  }

  function isVisible(element) {
    return !!(element.offsetWidth || element.offsetHeight || element.getClientRects().length);
  }

  function isSupportedInput(element) {
    var tag = (element.tagName || "").toLowerCase();
    var type = (element.getAttribute("type") || "text").toLowerCase();

    if (element.disabled || element.readOnly || !isVisible(element)) {
      return false;
    }

    if (element.dataset.dictationMicBound === "1") {
      return false;
    }

    if (tag === "textarea") {
      return true;
    }

    if (tag === "input") {
      return ["text", "search", "email", "tel", "url", "password", "number"].indexOf(type) !== -1;
    }

    return element.getAttribute("contenteditable") === "true";
  }

  function ensureTargetId(element) {
    if (!element.id) {
      element.id = "dictation-mic-target-" + Math.random().toString(36).slice(2, 10);
    }

    return element.id;
  }

  function buildControl(targetId) {
    var wrap = document.createElement("div");
    wrap.className = "dictation-mic-wrap";
    wrap.innerHTML =
      '<button type="button" class="dictation-mic-btn" data-target-id="' +
      targetId +
      '">' +
      '<i class="fa-solid fa-microphone"></i>' +
      "<span>Dictation Mic</span>" +
      "</button>" +
      '<span class="dictation-mic-status"></span>';
    return wrap;
  }

  function placeControl(element) {
    var targetId = ensureTargetId(element);
    var anchor = element.closest(".input-group") || element;
    var control = buildControl(targetId);

    if (anchor.nextSibling) {
      anchor.parentNode.insertBefore(control, anchor.nextSibling);
    } else {
      anchor.parentNode.appendChild(control);
    }

    element.dataset.dictationMicBound = "1";
  }

  function scan(scope) {
    var root = scope || document;
    var fields = root.querySelectorAll(
      'textarea, input[type="text"], input[type="search"], input[type="email"], input[type="tel"], input[type="url"], input[type="password"], input[type="number"], [contenteditable="true"]'
    );

    fields.forEach(function (field) {
      if (isSupportedInput(field)) {
        placeControl(field);
      }
    });
  }

  function setButtonState(button, listening, message) {
    if (!button) {
      return;
    }

    var status = button.parentElement.querySelector(".dictation-mic-status");
    var icon = button.querySelector("i");
    var label = button.querySelector("span");

    button.classList.toggle("is-listening", listening);
    if (icon) {
      icon.className = listening ? "fa-solid fa-stop" : "fa-solid fa-microphone";
    }

    if (label) {
      label.textContent = listening ? "Stop Dictation" : "Dictation Mic";
    }

    if (status) {
      status.textContent = message || "";
    }
  }

  function insertText(target, text) {
    var clean = (text || "").trim();
    if (!clean || !target) {
      return;
    }

    var tag = (target.tagName || "").toLowerCase();

    if (tag === "textarea" || tag === "input") {
      var current = target.value || "";
      var start = typeof target.selectionStart === "number" ? target.selectionStart : current.length;
      var end = typeof target.selectionEnd === "number" ? target.selectionEnd : current.length;
      var prefix = current.substring(0, start);
      var suffix = current.substring(end);
      var spacer = prefix && !/\s$/.test(prefix) ? " " : "";
      var nextValue = prefix + spacer + clean + " " + suffix;

      target.value = nextValue;
      target.focus();

      if (typeof target.setSelectionRange === "function") {
        var caret = (prefix + spacer + clean + " ").length;
        target.setSelectionRange(caret, caret);
      }

      target.dispatchEvent(new Event("input", { bubbles: true }));
      target.dispatchEvent(new Event("change", { bubbles: true }));
      return;
    }

    if (target.getAttribute("contenteditable") === "true") {
      target.focus();
      document.execCommand("insertText", false, clean + " ");
    }
  }

  function stopListening() {
    if (state.recognition) {
      state.recognition.onend = null;
      state.recognition.stop();
    }

    setButtonState(state.activeButton, false, "");
    state.activeButton = null;
    state.activeTarget = null;
    state.recognition = null;
    state.isListening = false;
  }

  function startListening(button) {
    if (!SpeechRecognition) {
      window.alert("This browser does not support speech dictation.");
      return;
    }

    var targetId = button.getAttribute("data-target-id");
    var target = document.getElementById(targetId);

    if (!target) {
      return;
    }

    if (state.isListening) {
      stopListening();
    }

    var recognition = new SpeechRecognition();
    recognition.continuous = true;
    recognition.interimResults = true;
    recognition.lang = document.documentElement.lang || "en-US";

    state.activeButton = button;
    state.activeTarget = target;
    state.recognition = recognition;
    state.isListening = true;

    recognition.onstart = function () {
      setButtonState(button, true, "Dictation is listening...");
    };

    recognition.onresult = function (event) {
      var interim = "";
      for (var i = event.resultIndex; i < event.results.length; i++) {
        var transcript = event.results[i][0].transcript;
        if (event.results[i].isFinal) {
          insertText(target, transcript);
        } else {
          interim += transcript;
        }
      }

      setButtonState(
        button,
        true,
        interim ? "Listening: " + interim : "Dictation is listening..."
      );
    };

    recognition.onerror = function (event) {
      setButtonState(button, false, "");
      state.activeButton = null;
      state.activeTarget = null;
      state.recognition = null;
      state.isListening = false;

      if (event.error !== "no-speech") {
        window.alert(
          event.error === "not-allowed"
            ? "Microphone access was blocked."
            : "Dictation stopped unexpectedly."
        );
      }
    };

    recognition.onend = function () {
      setButtonState(button, false, "");
      state.activeButton = null;
      state.activeTarget = null;
      state.recognition = null;
      state.isListening = false;
    };

    recognition.start();
  }

  function bindEvents() {
    document.addEventListener("click", function (event) {
      var button = event.target.closest(".dictation-mic-btn");
      if (!button) {
        return;
      }

      if (state.isListening && state.activeButton === button) {
        stopListening();
        return;
      }

      startListening(button);
    });
  }

  function observeMutations() {
    if (!window.MutationObserver) {
      return;
    }

    var observer = new MutationObserver(function (mutations) {
      mutations.forEach(function (mutation) {
        mutation.addedNodes.forEach(function (node) {
          if (node.nodeType === 1) {
            scan(node);
          }
        });
      });
    });

    observer.observe(document.body, { childList: true, subtree: true });
  }

  document.addEventListener("DOMContentLoaded", function () {
    ensureStyles();
    scan(document);
    bindEvents();
    observeMutations();
  });
})();
