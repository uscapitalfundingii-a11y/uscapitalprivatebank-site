@extends('admin.layouts.app')

@section('panel')
    <div class="row">
        <div class="col-lg-12">

            <div class="card">
                <div class="card-body ">

                    <h6 class="card-title  mb-4">
                        <div class="row align-items-center">
                            <div class="col-sm-8 col-md-6">
                                @php echo $ticket->statusBadge; @endphp
                                [@lang('Ticket#'){{ $ticket->ticket }}] {{ $ticket->subject }}
                            </div>
                            @can('admin.ticket.close')
                                <div class="col-sm-4  col-md-6 text-sm-end mt-sm-0 mt-3">
                                    @if ($ticket->status != Status::TICKET_CLOSE)
                                        <button class="btn btn--danger btn-sm" type="button" data-bs-toggle="modal" data-bs-target="#DelModal">
                                            <i class="la la-times"></i> @lang('Close Ticket')
                                        </button>
                                    @endif
                                </div>
                            @endcan
                        </div>
                    </h6>

                    @can('admin.ticket.reply')
                        <form action="{{ route('admin.ticket.reply', $ticket->id) }}" enctype="multipart/form-data" method="post" class="form-horizontal disableSubmission">
                            @csrf

                            <div class="row ">
                                <div class="col-md-12">
                                    <div class="d-flex justify-content-end flex-wrap gap-2 mb-2">
                                        <button class="btn btn--dark btn-sm dictateReplyBtn" type="button" id="dictateReplyBtn">
                                            <i class="las la-microphone"></i> @lang('Dictate')
                                        </button>
                                        <button class="btn btn--warning btn-sm polishAiDraftBtn" type="button" data-url="{{ route('admin.ticket.ai.polish', $ticket->id) }}">
                                            <i class="las la-magic"></i> @lang('Revise with AI')
                                        </button>
                                        <button class="btn btn--info btn-sm generateAiDraftBtn" type="button" data-url="{{ route('admin.ticket.ai.draft', $ticket->id) }}">
                                            <i class="las la-robot"></i> @lang('Reply with AI')
                                        </button>
                                    </div>
                                    <div class="form-group">
                                        <textarea class="form-control" name="message" rows="5" required id="inputMessage" placeholder="@lang('Enter reply here')"></textarea>
                                        <small class="text-muted d-none mt-2" id="aiDraftStatus"></small>
                                    </div>
                                </div>
                                <div class="col-md-9">
                                    <button type="button" class="btn btn--dark addAttachment my-2"> <i class="fas fa-plus"></i> @lang('Add Attachment') </button>
                                    <p class="mb-2"><span class="text--info">@lang('Max 5 files can be uploaded | Maximum upload size is ' . convertToReadableSize(ini_get('upload_max_filesize')) . ' | Allowed File Extensions: .jpg, .jpeg, .png, .pdf, .doc, .docx')</span></p>
                                    <div class="row fileUploadsContainer">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <button class="btn btn--primary w-100 my-2" type="submit" name="replayTicket" value="1"><i class="la la-fw la-lg la-reply"></i> @lang('Reply')
                                    </button>
                                </div>
                            </div>
                        </form>
                    @endcan

                    @foreach ($messages as $message)
                        @if ($message->is_ai_response)
                            <div class="row border border-info border-radius-3 my-3 mx-0 ai-bg-reply">

                                <div class="col-md-3 border-end text-md-end text-start">
                                    <h5 class="my-3">{{ $message->author_label }}</h5>
                                    <p class="lead text-muted">@lang('Automated Reply')</p>
                                    @can('admin.ticket.delete')
                                        <button class="btn btn--danger btn-sm my-3 confirmationBtn" data-question="@lang('Are you sure to delete this message?')" data-action="{{ route('admin.ticket.delete', $message->id) }}"><i class="la la-trash"></i> @lang('Delete')</button>
                                    @endcan
                                </div>
                                <div class="col-md-9">
                                    <p class="text-muted fw-bold my-3">
                                        @lang('Posted on') {{ showDateTime($message->created_at, 'l, dS F Y @ h:i a') }}</p>
                                    <p>{{ $message->message }}</p>
                                    @if ($message->ai_model)
                                        <p class="text-muted mb-0"><small>@lang('Model'): {{ $message->ai_model }}</small></p>
                                    @endif
                                </div>
                            </div>
                        @elseif ($message->admin_id == 0)
                            <div class="row border border--primary border-radius-3 my-3 mx-0">

                                <div class="col-md-3 border-end text-md-end text-start">
                                    <h5 class="my-3">{{ $ticket->name }}</h5>
                                    @if ($ticket->user_id != null && can('admin.users.detail') )
                                        <p><a href="{{ route('admin.users.detail', $ticket->user_id) }}">&#64;{{ $ticket->name }}</a></p>
                                    @else
                                        <p>@<span>{{ $ticket->name }}</span></p>
                                    @endif

                                    @can('admin.ticket.delete')
                                        <button class="btn btn--danger btn-sm my-3 confirmationBtn" data-question="@lang('Are you sure to delete this message?')" data-action="{{ route('admin.ticket.delete', $message->id) }}"><i class="la la-trash"></i> @lang('Delete')</button>
                                    @endcan
                                </div>

                                <div class="col-md-9">
                                    <p class="text-muted fw-bold my-3">
                                        @lang('Posted on') {{ showDateTime($message->created_at, 'l, dS F Y @ h:i a') }}</p>
                                    <p>{{ $message->message }}</p>

                                    @can('admin.ticket.download')
                                        @if ($message->attachments->count() > 0)
                                            <div class="my-3">
                                                @foreach ($message->attachments as $k => $image)
                                                    <a href="{{ route('admin.ticket.download', encrypt($image->id)) }}" class="me-2"><i class="fa-regular fa-file"></i> @lang('Attachment') {{ ++$k }}</a>
                                                @endforeach
                                            </div>
                                        @endif
                                    @endcan
                                </div>
                            </div>
                        @else
                            <div class="row border border-warning border-radius-3 my-3 mx-0 admin-bg-reply">

                                <div class="col-md-3 border-end text-md-end text-start">
                                    <h5 class="my-3">{{ @$message->admin->name }}</h5>
                                    <p class="lead text-muted">@lang('Staff')</p>
                                    @can('admin.ticket.delete')
                                    <button class="btn btn--danger btn-sm my-3 confirmationBtn" data-question="@lang('Are you sure to delete this message?')" data-action="{{ route('admin.ticket.delete', $message->id) }}"><i class="la la-trash"></i> @lang('Delete')</button>
                                    @endif
                                </div>
                                <div class="col-md-9">
                                    <p class="text-muted fw-bold my-3">
                                        @lang('Posted on') {{ showDateTime($message->created_at, 'l, dS F Y @ h:i a') }}</p>
                                    <p>{{ $message->message }}</p>

                                    @can('admin.ticket.download')
                                        @if ($message->attachments->count() > 0)
                                            <div class="my-3">
                                                @foreach ($message->attachments as $k => $image)
                                                    <a href="{{ route('admin.ticket.download', encrypt($image->id)) }}" class="me-2"><i class="fa-regular fa-file"></i> @lang('Attachment') {{ ++$k }} </a>
                                                @endforeach
                                            </div>
                                        @endif
                                    @endcan
                                </div>

                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    @can('admin.ticket.close')
        <div class="modal fade" id="DelModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"> @lang('Close Support Ticket!')</h5>
                        <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                            <i class="las la-times"></i>
                        </button>
                    </div>
                    <div class="modal-body">
                        <p>@lang('Are you want to close this support ticket?')</p>
                    </div>
                    <div class="modal-footer">
                        <form method="post" action="{{ route('admin.ticket.close', $ticket->id) }}">
                            @csrf
                            <input type="hidden" name="replayTicket" value="2">
                            <button type="button" class="btn btn--dark" data-bs-dismiss="modal"> @lang('No') </button>
                            <button type="submit" class="btn btn--primary"> @lang('Yes') </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endcan

    @can('admin.ticket.delete')
        <x-confirmation-modal />
    @endcan
@endsection

@can('admin.ticket.index')
@push('breadcrumb-plugins')
    <x-back route="{{ route('admin.ticket.index') }}" />
@endpush
@endcan

@push('style')
    <style>
        .ai-bg-reply {
            background-color: rgba(13, 202, 240, 0.08);
        }
    </style>
@endpush

@push('script')
    <script>
        "use strict";
        (function($) {
            const $draftButton = $('.generateAiDraftBtn');
            const $polishButton = $('.polishAiDraftBtn');
            const $dictateButton = $('#dictateReplyBtn');
            const $messageField = $('#inputMessage');
            const $draftStatus = $('#aiDraftStatus');
            const SpeechRecognitionApi = window.SpeechRecognition || window.webkitSpeechRecognition;
            let recognition = null;
            let isListening = false;

            $('.delete-message').on('click', function(e) {
                $('.message_id').val($(this).data('id'));
            })
            var fileAdded = 0;
            $('.addAttachment').on('click', function() {
                fileAdded++;
                if (fileAdded == 5) {
                    $(this).attr('disabled', true)
                }
                $(".fileUploadsContainer").append(`
                <div class="col-xl-4 col-lg-6 col-md-6 col-sm-6 removeFileInput">
                    <div class="form-group">
                        <div class="input-group">
                            <input type="file" name="attachments[]" class="form-control" accept=".jpeg,.jpg,.png,.pdf,.doc,.docx" required>
                            <button type="button" class="input-group-text removeFile bg--danger border--danger"><i class="fas fa-times"></i></button>
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

            $draftButton.on('click', function() {
                const $button = $(this);
                const originalHtml = $button.html();

                $button.prop('disabled', true).html('<i class="las la-spinner la-spin"></i> @lang("Generating...")');
                $draftStatus.removeClass('d-none text-danger text-success').text('@lang("Generating AI draft...")');

                $.ajax({
                    url: $button.data('url'),
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.draft) {
                            $messageField.val(response.draft).trigger('focus');
                            $draftStatus.addClass('text-success').text('@lang("AI draft added to the reply box. You can edit it before sending.")');
                            return;
                        }

                        $draftStatus.addClass('text-danger').text('@lang("AI draft could not be generated right now.")');
                    },
                    error: function(xhr) {
                        const message = xhr.responseJSON?.message || '@lang("AI draft could not be generated right now.")';
                        $draftStatus.addClass('text-danger').text(message);
                    },
                    complete: function() {
                        $button.prop('disabled', false).html(originalHtml);
                        $draftStatus.removeClass('d-none');
                    }
                });
            });

            $polishButton.on('click', function() {
                const $button = $(this);
                const originalHtml = $button.html();
                const currentDraft = $messageField.val().trim();

                if (!currentDraft) {
                    $draftStatus.removeClass('d-none text-success').addClass('text-danger').text('@lang("Please enter or dictate a reply before asking AI to revise it.")');
                    return;
                }

                $button.prop('disabled', true).html('<i class="las la-spinner la-spin"></i> @lang("Revising...")');
                $draftStatus.removeClass('d-none text-danger text-success').text('@lang("Revising your draft...")');

                $.ajax({
                    url: $button.data('url'),
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        message: currentDraft
                    },
                    success: function(response) {
                        if (response.draft) {
                            $messageField.val(response.draft).trigger('focus');
                            $draftStatus.addClass('text-success').text('@lang("Your reply was revised and updated in the reply box.")');
                            return;
                        }

                        $draftStatus.addClass('text-danger').text('@lang("AI revision could not be generated right now.")');
                    },
                    error: function(xhr) {
                        const message = xhr.responseJSON?.message || '@lang("AI revision could not be generated right now.")';
                        $draftStatus.addClass('text-danger').text(message);
                    },
                    complete: function() {
                        $button.prop('disabled', false).html(originalHtml);
                        $draftStatus.removeClass('d-none');
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
                    $dictateButton.html('<i class="las la-microphone-slash"></i> @lang("Stop Dictation")');
                    $draftStatus.removeClass('d-none text-danger text-success').text('@lang("Listening... speak to fill the reply box.")');
                };

                recognition.onresult = function(event) {
                    let transcript = '';
                    for (let i = event.resultIndex; i < event.results.length; i++) {
                        transcript += event.results[i][0].transcript;
                    }

                    const existing = $messageField.val().trim();
                    const spacer = existing && !existing.endsWith(' ') ? ' ' : '';
                    $messageField.val((existing + spacer + transcript).trimStart());
                };

                recognition.onerror = function(event) {
                    $draftStatus.removeClass('d-none text-success').addClass('text-danger').text(event.error === 'not-allowed'
                        ? '@lang("Microphone permission was denied.")'
                        : '@lang("Dictation stopped because the browser reported an error.")');
                };

                recognition.onend = function() {
                    isListening = false;
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
        })(jQuery);
    </script>
@endpush
