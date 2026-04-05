@extends('admin.layouts.app')

@section('panel')
    <div class="row mb-none-30">
        <div class="col-xl-12">
            <div class="card">
                <form action="{{ route('admin.users.notification.single', $user->id) }}" class="notificationForm" method="POST" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="via" value="email">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-12">
                                <div class="row">
                                    @if (gs('en'))
                                    <div class="col-xxl-2 col-xl-3 col-lg-4 col-md-3 col-sm-6">
                                        <div class="notification-via mb-4 active" data-method="email">
                                            <span class="active-badge"> <i class="las la-check"></i> </span>
                                            <div class="send-via-method">
                                                <i class="las la-envelope"></i>
                                                <h5>@lang('Send Via Email')</h5>
                                            </div>
                                        </div>
                                    </div>
                                    @endif
                                    @if (gs('sn'))
                                    <div class="col-xxl-2 col-xl-3 col-lg-4 col-md-3 col-sm-6">
                                        <div class="notification-via mb-4" data-method="sms">
                                            <span class="active-badge"> <i class="las la-check"></i> </span>
                                            <div class="send-via-method">
                                                <i class="las la-mobile-alt"></i>
                                                <h5>@lang('Send Via SMS')</h5>
                                            </div>
                                        </div>
                                    </div>
                                    @endif
                                    @if (gs('pn'))
                                    <div class="col-xxl-2 col-xl-3 col-lg-4 col-md-3 col-sm-12">
                                        <div class="notification-via mb-4" data-method="push">
                                            <span class="active-badge"> <i class="las la-check"></i> </span>
                                            <div class="send-via-method">
                                                <i class="las la-bell"></i>
                                                <h5>@lang('Send Via Firebase')</h5>
                                            </div>
                                        </div>
                                    </div>
                                    @endif
                                </div>
                            </div>
                            <div class="form-group col-md-12 subject-wrapper">
                                <label>@lang('Subject') </label>
                                <input type="text" class="form-control" placeholder="@lang('Subject / Title')" name="subject">
                            </div>
                            <div class="form-group col-md-12 push-notification-file d-none">
                                <label>@lang('Image (optional)') </label>
                                <input type="file" class="form-control" accept=".png,.jpg,.jpeg" name="image">
                                <small class="mt-3 text-muted"> @lang('Supported Files'):<b>@lang('.png, .jpg, .jpeg')</b> </small>
                            </div>
                            <div class="form-group col-md-12">
                                <div class="d-flex justify-content-end flex-wrap gap-2 mb-2">
                                    <button class="btn btn--dark btn-sm dictateNotificationBtn" type="button">
                                        <i class="las la-microphone"></i> @lang('Dictate')
                                    </button>
                                    <button class="btn btn--warning btn-sm polishNotificationBtn" type="button" data-url="{{ route('admin.users.notification.ai.revise') }}">
                                        <i class="las la-magic"></i> @lang('Revise with AI')
                                    </button>
                                </div>
                                <label>@lang('Message') </label>
                                <textarea name="message" rows="10" class="form-control nicEdit notificationMessageField"></textarea>
                                <small class="text-muted d-none mt-2 notificationDraftStatus"></small>
                            </div>
                        </div>
                    </div>

                    @can('admin.users.notification.single')
                        <div class="card-footer">
                            <button type="submit" class="btn w-100 h-45 btn--primary">@lang('Submit')</button>
                        </div>
                    @endif
                </form>
            </div>
        </div>
    </div>

@endsection
@push('script')
    <script>
        (function($) {
            "use strict"

            const $form = $('.notificationForm');
            const $messageField = $('.notificationMessageField');
            const $subjectField = $('[name=subject]');
            const $status = $('.notificationDraftStatus');
            const $dictateButton = $('.dictateNotificationBtn');
            const $polishButton = $('.polishNotificationBtn');
            const SpeechRecognitionApi = window.SpeechRecognition || window.webkitSpeechRecognition;
            let recognition = null;
            let isListening = false;
            let dictatedBaseText = '';
            let finalTranscript = '';

            function activeVia() {
                return $('[name=via]').val() || 'email';
            }

            function nicPanel() {
                return $messageField.prev('.nicEdit-main');
            }

            function editorIsRichText() {
                return activeVia() === 'email';
            }

            function getEditorValue() {
                if (editorIsRichText()) {
                    const html = nicPanel().html() || '';
                    return $('<div>').html(html.replace(/<br\s*\/?>/gi, "\n")).text().trim();
                }

                return ($messageField.val() || '').trim();
            }

            function setEditorValue(value) {
                if (editorIsRichText()) {
                    const normalized = (value || '').replace(/\r\n?/g, "\n");
                    nicPanel().html(normalized.split("\n").join('<br>'));
                }

                $messageField.val(value || '');
            }

            function syncEditorValue() {
                if (editorIsRichText()) {
                    $messageField.val(nicPanel().html() || '');
                }
            }

            $('.notification-via').on('click',function () {
                $('.notification-via').removeClass('active');
                $(this).addClass('active');
                $('[name=via]').val($(this).data('method'));
                if($(this).data('method') == 'email'){
                    var nicPrev = $('.nicEdit').prev('div');
                    nicPrev.prev('div').removeClass('d-none');
                    nicPrev.removeClass('d-none');
                    $('.nicEdit').css('display','none')

                }else{
                    var nicPrev = $('.nicEdit').prev('div');
                    nicPrev.prev('div').addClass('d-none');
                    nicPrev.addClass('d-none');
                    $('.nicEdit').css('display','block')
                    $('.nicEdit').val("")
                }

                if($(this).data('method') == 'push'){
                    $('.push-notification-file').removeClass('d-none');
                }else{
                    $('.push-notification-file').addClass('d-none');
                    $('.push-notification-file [type=file]').val('');
                }

                if($(this).data('method') == 'push' || $(this).data('method') == 'email'){
                    $('.subject-wrapper').removeClass('d-none');
                }else{
                    $('.subject-wrapper').addClass('d-none')
                }
                $('.subject-wrapper').find('input').val('');
            });

            $polishButton.on('click', function() {
                const $button = $(this);
                const originalHtml = $button.html();
                const currentDraft = getEditorValue();

                if (!currentDraft) {
                    $status.removeClass('d-none text-success').addClass('text-danger').text('@lang("Please enter or dictate a notification before asking AI to revise it.")');
                    return;
                }

                $button.prop('disabled', true).html('<i class="las la-spinner la-spin"></i> @lang("Revising...")');
                $status.removeClass('d-none text-danger text-success').text('@lang("Revising your notification draft...")');

                $.ajax({
                    url: $button.data('url'),
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        via: activeVia(),
                        subject: $subjectField.val(),
                        message: currentDraft
                    },
                    success: function(response) {
                        if (response.draft) {
                            setEditorValue(response.draft);
                            $status.addClass('text-success').text('@lang("Your notification was revised and updated in the message box.")');
                            return;
                        }

                        $status.addClass('text-danger').text('@lang("AI revision could not be generated right now.")');
                    },
                    error: function(xhr) {
                        const message = xhr.responseJSON?.message || '@lang("AI revision could not be generated right now.")';
                        $status.addClass('text-danger').text(message);
                    },
                    complete: function() {
                        $button.prop('disabled', false).html(originalHtml);
                        $status.removeClass('d-none');
                    }
                });
            });

            if (!SpeechRecognitionApi) {
                $dictateButton.prop('disabled', true).attr('title', '@lang("Dictation is not supported in this browser.")');
            } else {
                recognition = new SpeechRecognitionApi();
                recognition.lang = 'en-US';
                recognition.interimResults = true;
                recognition.continuous = true;

                recognition.onstart = function() {
                    isListening = true;
                    dictatedBaseText = getEditorValue();
                    finalTranscript = '';
                    $dictateButton.html('<i class="las la-microphone-slash"></i> @lang("Stop Dictation")');
                    $status.removeClass('d-none text-danger text-success').text('@lang("Listening... speak to fill the message box.")');
                };

                recognition.onresult = function(event) {
                    let interimTranscript = '';

                    for (let i = event.resultIndex; i < event.results.length; i++) {
                        const chunk = event.results[i][0].transcript.trim();

                        if (!chunk) {
                            continue;
                        }

                        if (event.results[i].isFinal) {
                            finalTranscript += (finalTranscript ? ' ' : '') + chunk;
                        } else {
                            interimTranscript += (interimTranscript ? ' ' : '') + chunk;
                        }
                    }

                    const spokenText = [finalTranscript.trim(), interimTranscript.trim()].filter(Boolean).join(' ').trim();
                    const composedText = [dictatedBaseText, spokenText].filter(Boolean).join(' ').trim();

                    setEditorValue(composedText);
                };

                recognition.onerror = function(event) {
                    $status.removeClass('d-none text-success').addClass('text-danger').text(event.error === 'not-allowed'
                        ? '@lang("Microphone permission was denied.")'
                        : '@lang("Dictation stopped because the browser reported an error.")');
                };

                recognition.onend = function() {
                    isListening = false;
                    dictatedBaseText = '';
                    finalTranscript = '';
                    $dictateButton.html('<i class="las la-microphone"></i> @lang("Dictate")');
                };

                $dictateButton.on('click', function() {
                    if (!recognition) {
                        return;
                    }

                    if (isListening) {
                        recognition.stop();
                        return;
                    }

                    recognition.start();
                });
            }


            $('.notificationForm').on('submit',function (e) {
                syncEditorValue();
                if ($('.notification-via.active').data('method') != 'email') {
                    e.preventDefault();
                    var val = $('.nicEdit').val();
                    setTimeout(() => {
                        $('.nicEdit').val(val);
                        document.getElementsByClassName('notificationForm')[0].submit();
                    }, 1);
                }

            });

        })(jQuery);
    </script>
@endpush
