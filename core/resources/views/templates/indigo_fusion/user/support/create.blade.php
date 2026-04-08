@extends($activeTemplate . 'layouts.master')
@section('content')
    <div class="row justify-content-center">
        <div class="col-xl-8 col-lg-10">
            <div class="text-end mb-3">
                <a href="{{ route('ticket.index') }}" class="btn btn-sm btn--base mb-2"><i class="la la-list"></i> @lang('All Tickets')</a>
            </div>
            <div class="card custom--card">
                <div class="card-body">
                    <form action="{{ route('ticket.store') }}" method="post" enctype="multipart/form-data">
                        @csrf
                        <div class="row">
                            <div class="form-group col-md-6">
                                <label class="form-label">@lang('Subject')</label>
                                <input type="text" name="subject" value="{{ old('subject') }}" class="form--control" required>
                            </div>
                            <div class="form-group col-md-6">
                                <label class="form-label">@lang('Priority')</label>
                                <select name="priority" class="form--control" required>
                                    <option value="3">@lang('High')</option>
                                    <option value="2">@lang('Medium')</option>
                                    <option value="1">@lang('Low')</option>
                                </select>
                            </div>
                            <div class="col-12 form-group">
                                <label class="form-label">@lang('Message')</label>
                                <div class="ticket-tools">
                                    <div class="ticket-tools__group">
                                        <button type="button" class="ticket-tool insert-template" data-template="Issue Summary:&#10;">@lang('Issue Summary')</button>
                                        <button type="button" class="ticket-tool insert-template" data-template="Account Number:&#10;">@lang('Account Number')</button>
                                        <button type="button" class="ticket-tool insert-template" data-template="Transaction Reference:&#10;">@lang('Transaction Ref')</button>
                                        <button type="button" class="ticket-tool insert-template" data-template="1. &#10;2. &#10;3. ">@lang('Numbered List')</button>
                                        <button type="button" class="ticket-tool insert-template" data-template="- &#10;- &#10;- ">@lang('Bullet List')</button>
                                    </div>
                                    <button type="button" class="ticket-tool ticket-tool--mic" id="speechToTextBtn" aria-pressed="false">
                                        <i class="las la-microphone"></i>
                                        <span>@lang('Dictate Message')</span>
                                    </button>
                                </div>
                                <textarea name="message" id="inputMessage" rows="6" class="form--control" required>{{ old('message') }}</textarea>
                                <small class="text--info d-block mt-2" id="speechToTextStatus">@lang('Use the microphone to speak your message, or use the quick formatting buttons to structure your request.')</small>
                            </div>

                            <div class="col-md-9">
                                <button type="button" class="btn btn--base btn-sm addAttachment my-2"> <i class="fas fa-plus"></i> @lang('Add Attachment') </button>
                                <small class="mb-2"><span class="text--info">@lang('Max 5 files can be uploaded | Maximum upload size is ' . convertToReadableSize(ini_get('upload_max_filesize')) . ' | Allowed File Extensions: .jpg, .jpeg, .png, .pdf, .doc, .docx')</span></small>
                                <div class="row fileUploadsContainer">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <button class="btn btn--base w-100 my-2" type="submit"><i class="las la-paper-plane"></i> @lang('Submit')</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('style')
    <style>
        .input-group-text:focus {
            box-shadow: none !important;
        }

        .ticket-tools {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 12px;
        }

        .ticket-tools__group {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .ticket-tool {
            border: 1px solid rgba(9, 159, 239, 0.25);
            background: rgba(9, 159, 239, 0.08);
            color: hsl(var(--base));
            border-radius: 999px;
            font-size: 13px;
            line-height: 1.1;
            padding: 10px 14px;
            transition: 0.2s ease-in-out;
        }

        .ticket-tool:hover,
        .ticket-tool:focus {
            background: hsl(var(--base) / 0.16);
            border-color: hsl(var(--base));
            color: hsl(var(--white));
        }

        .ticket-tool--mic {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: linear-gradient(135deg, hsl(var(--base)), hsl(var(--base-two)));
            border-color: transparent;
            color: hsl(var(--white));
        }

        .ticket-tool--mic.is-listening {
            box-shadow: 0 0 0 4px rgba(9, 159, 239, 0.18);
        }
    </style>
@endpush

@push('script')
    <script>
        (function($) {
            "use strict";
            var fileAdded = 0;
            var messageField = $('#inputMessage');
            var statusField = $('#speechToTextStatus');
            var micButton = $('#speechToTextBtn');

            function insertAtCursor(text) {
                var field = messageField.get(0);
                if (!field) return;

                var start = field.selectionStart || 0;
                var end = field.selectionEnd || 0;
                var current = field.value || '';
                var prefix = current.slice(0, start);
                var suffix = current.slice(end);
                var needsBreak = prefix.length && !prefix.endsWith("\n") ? "\n" : "";
                var insertion = needsBreak + text;
                field.value = prefix + insertion + suffix;
                field.focus();
                var caret = (prefix + insertion).length;
                field.setSelectionRange(caret, caret);
                messageField.trigger('input');
            }

            $('.addAttachment').on('click', function() {
                fileAdded++;
                if (fileAdded == 5) {
                    $(this).attr('disabled', true)
                }
                $(".fileUploadsContainer").append(`
                    <div class="col-lg-6 col-md-12 removeFileInput">
                        <div class="form-group">
                            <div class="input-group">
                                <input type="file" name="attachments[]" class="form--control" accept=".jpeg,.jpg,.png,.pdf,.doc,.docx" required>
                                <button type="button" class="input-group-text removeFile bg--danger text-white border-0"><i class="fas fa-times"></i></button>
                            </div>
                        </div>
                    </div>
                `)
            });
            $(document).on('click', '.removeFile', function() {
                $('.addAttachment').removeAttr('disabled', true)
                fileAdded--;
                $(this).closest('.removeFileInput').remove();
            });

            $('.insert-template').on('click', function() {
                insertAtCursor($(this).data('template'));
            });

            var SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;

            if (SpeechRecognition) {
                var recognition = new SpeechRecognition();
                recognition.continuous = true;
                recognition.interimResults = true;
                recognition.lang = document.documentElement.lang || 'en-US';
                var finalTranscript = '';

                recognition.onstart = function() {
                    micButton.addClass('is-listening').attr('aria-pressed', 'true');
                    statusField.text("@lang('Listening now. Speak clearly and click the microphone again when you are finished.')");
                };

                recognition.onresult = function(event) {
                    var interimTranscript = '';
                    for (var i = event.resultIndex; i < event.results.length; ++i) {
                        var transcript = event.results[i][0].transcript;
                        if (event.results[i].isFinal) {
                            finalTranscript += transcript + ' ';
                        } else {
                            interimTranscript += transcript;
                        }
                    }
                    messageField.val((finalTranscript + interimTranscript).trim());
                };

                recognition.onerror = function() {
                    micButton.removeClass('is-listening').attr('aria-pressed', 'false');
                    statusField.text("@lang('The microphone could not start in this browser. You can still type your message normally.')");
                };

                recognition.onend = function() {
                    micButton.removeClass('is-listening').attr('aria-pressed', 'false');
                    statusField.text("@lang('Dictation stopped. Review the message and submit when ready.')");
                };

                micButton.on('click', function() {
                    if (micButton.hasClass('is-listening')) {
                        recognition.stop();
                    } else {
                        finalTranscript = messageField.val().trim();
                        if (finalTranscript.length) {
                            finalTranscript += ' ';
                        }
                        recognition.start();
                    }
                });
            } else {
                micButton.prop('disabled', true);
                statusField.text("@lang('Voice dictation is not available in this browser. You can still type your message and use the formatting buttons above.')");
            }
        })(jQuery);
    </script>
@endpush

@push('bottom-menu')
    <li>
        <a href="{{ route('user.profile.setting') }}">@lang('Profile')</a>
    </li>

    @if (gs()->modules->referral_system)
        <li><a href="{{ route('user.referral.users') }}">@lang('Referral')</a></li>
    @endif

     @if (gs()->modules->virtual_card)
        <li><a href="{{ route('user.vcard.index') }}">@lang('Virtual Cards')</a></li>
    @endif

    <li><a href="{{ route('user.twofactor') }}">@lang('2FA Security')</a></li>
    <li><a href="{{ route('user.change.password') }}">@lang('Change Password')</a></li>
    <li><a href="{{ route('user.transaction.history') }}">@lang('Transactions')</a></li>
    <li><a  href="{{ route('user.statement') }}">@lang('Statement')</a></li>
    <li><a class="active" href="{{ route('ticket.index') }}">@lang('Support Tickets')</a></li>
@endpush
