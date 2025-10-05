@extends('layouts.app')

@php
    use Illuminate\Support\Str;
    $statusLabels = [
        'open' => ['label' => 'باز', 'class' => 'badge-success'],
        'answered' => ['label' => 'پاسخ داده شده', 'class' => 'badge-info'],
        'closed' => ['label' => 'بسته شده', 'class' => 'badge-danger'],
    ];

    $senderLabels = [
        'agent' => 'پشتیبان',
        'bot' => 'ربات',
        'user' => 'کاربر',
    ];
@endphp

@section('title', 'جزئیات تیکت تلگرام #' . $ticket->id)

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
                <h4 class="mb-0">تیکت شماره {{ $ticket->id }}</h4>
                <a href="{{ route('telegram-tickets.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="fa fa-arrow-right ml-1"></i>
                    بازگشت به لیست
                </a>
            </div>
        </div>

        <div class="col-12">
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <ul class="mb-0 pr-3">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            @endif
        </div>

        <div class="col-lg-4 mb-3">
            <div class="card card-outline card-primary h-100">
                <div class="card-header">
                    <h3 class="card-title">اطلاعات تیکت</h3>
                </div>
                <div class="card-body">
                    <dl class="ticket-meta mb-0">
                        <dt>شناسه تیکت</dt>
                        <dd class="mb-3">{{ $ticket->id }}</dd>

                        <dt>کد کاربر</dt>
                        <dd class="mb-3">{{ $ticket->user_id ?? '—' }}</dd>

                        <dt>وضعیت</dt>
                        <dd class="mb-3">
                            @php
                                $status = $statusLabels[$ticket->status] ?? ['label' => 'نامشخص', 'class' => 'badge-secondary'];
                            @endphp
                            <span class="badge badge-pill {{ $status['class'] }} px-3 py-2">
                                {{ $status['label'] }}
                            </span>
                        </dd>

                        <dt>ثبت شده در</dt>
                        <dd class="mb-0">{{ optional($ticket->created_at)->format('Y-m-d H:i') ?? '—' }}</dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-lg-8 mb-3">
            <div class="card card-outline card-primary direct-chat direct-chat-primary">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0">گفتگوی تیکت</h3>
                    <span class="badge badge-light">{{ $ticket->messages->count() }} پیام</span>
                </div>
                <div class="card-body">
                    <div class="direct-chat-messages" style="height: 420px;">
                        @forelse ($ticket->messages as $message)
                            @php
                                $senderLabel = $senderLabels[$message->sender_type] ?? 'کاربر';
                                $replyPreview = $message->replyTo ? Str::limit($message->replyTo->message, 120) : null;
                                $isAgentMessage = $message->sender_type === 'agent';
                                $messageClasses = $isAgentMessage ? 'direct-chat-msg right agent-message' : 'direct-chat-msg participant-message';
                            @endphp
                            <div class="{{ $messageClasses }}">
                                <div class="direct-chat-infos message-meta">
                                    <span class="direct-chat-name">{{ $senderLabel }}</span>
                                    <span class="direct-chat-timestamp">{{ optional($message->created_at)->format('Y-m-d H:i') }}</span>
                                </div>
                                <div class="direct-chat-text message-bubble"
                                    data-message-id="{{ $message->id }}"
                                    data-message-preview="{{ e(Str::limit($message->message, 140)) }}">
                                    @if ($replyPreview)
                                        <div class="quoted-message mb-2">
                                            <i class="fa fa-reply ml-1"></i>
                                            <span class="text-muted">{{ $replyPreview }}</span>
                                        </div>
                                    @endif
                                    <div class="message-content">{!! nl2br(e($message->message)) !!}</div>
                                </div>
                                <div class="message-actions mt-1">
                                    <button type="button" class="btn btn-link btn-sm p-0 reply-button"
                                        data-message-id="{{ $message->id }}"
                                        data-message-preview="{{ e(Str::limit($message->message, 140)) }}">
                                        <i class="fa fa-reply ml-1"></i>
                                        پاسخ
                                    </button>
                                </div>
                            </div>
                        @empty
                            <div class="text-center text-muted py-5">پیامی برای این تیکت ثبت نشده است.</div>
                        @endforelse
                    </div>
                </div>

                <div class="card-footer">
                    @if ($ticket->status !== 'closed')
                        <form action="{{ route('telegram-tickets.reply', $ticket->id) }}" method="POST" class="mb-3">
                            @csrf
                            <input type="hidden" name="reply_to_message_id" id="reply_to_message_id"
                                value="{{ old('reply_to_message_id') }}">

                            <div id="reply-preview" class="reply-preview-card d-none mb-3">
                                <div class="d-flex align-items-center">
                                    <i class="fa fa-reply ml-2 text-primary"></i>
                                    <div>
                                        <small class="text-muted d-block">در حال پاسخ به</small>
                                        <span id="reply-preview-text" class="font-weight-bold"></span>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-light btn-sm" id="reply-preview-clear">
                                    <i class="fa fa-times"></i>
                                </button>
                            </div>

                            <div class="form-group">
                                <label for="reply">پاسخ شما</label>
                                <textarea name="reply" id="reply" class="form-control" rows="4" required>{{ old('reply') }}</textarea>
                            </div>

                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-paper-plane ml-1"></i>
                                ارسال پاسخ
                            </button>
                        </form>

                        <form action="{{ route('telegram-tickets.close', $ticket->id) }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-outline-danger btn-sm"
                                onclick="return confirm('آیا از بستن این تیکت مطمئن هستید؟')">
                                <i class="fa fa-lock ml-1"></i>
                                بستن تیکت
                            </button>
                        </form>
                    @else
                        <div class="alert alert-secondary mb-0">این تیکت بسته شده است.</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@section('style')
    <style>
        .ticket-meta dt {
            font-size: 0.85rem;
            color: #6c757d;
            font-weight: 600;
        }

        .ticket-meta dd {
            font-size: 0.95rem;
        }

        .direct-chat-messages {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .direct-chat-msg {
            display: flex;
            flex-direction: column;
            gap: 0.35rem;
            max-width: 85%;
        }

        .direct-chat-msg.participant-message {
            align-self: flex-start;
            align-items: flex-start;
        }

        .direct-chat-msg.agent-message {
            align-self: flex-end;
            align-items: flex-end;
        }

        .direct-chat-infos.message-meta {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.85rem;
            color: #6c757d;
            width: 100%;
        }

        .direct-chat-msg.participant-message .message-meta {
            justify-content: flex-start;
            text-align: left;
        }

        .direct-chat-msg.agent-message .message-meta {
            justify-content: flex-end;
            text-align: right;
        }

        .direct-chat-infos.message-meta .direct-chat-name,
        .direct-chat-infos.message-meta .direct-chat-timestamp {
            float: none !important;
        }

        .direct-chat-text.message-bubble {
            position: relative;
            border-radius: 1rem;
            border: 1px solid #e1e5eb;
            background-color: #f8f9fa;
            padding: 0.75rem 1rem;
            line-height: 1.8;
        }

        .direct-chat-msg.agent-message .direct-chat-text.message-bubble {
            background-color: #dcf8c6;
            border-color: rgba(37, 211, 102, 0.35);
            color: #0f5132;
        }

        .direct-chat-msg.participant-message .direct-chat-text.message-bubble {
            color: #1f2d3d;
        }

        .direct-chat-msg.agent-message .direct-chat-text.message-bubble .message-content {
            color: inherit;
        }

        .direct-chat-msg.participant-message .direct-chat-text.message-bubble::before,
        .direct-chat-msg.participant-message .direct-chat-text.message-bubble::after,
        .direct-chat-msg.agent-message .direct-chat-text.message-bubble::before,
        .direct-chat-msg.agent-message .direct-chat-text.message-bubble::after {
            content: '';
            position: absolute;
            top: 14px;
            width: 0;
            height: 0;
            border-style: solid;
        }

        .direct-chat-msg.participant-message .direct-chat-text.message-bubble::before {
            left: -10px;
            border-width: 8px 10px 8px 0;
            border-color: transparent #e1e5eb transparent transparent;
        }

        .direct-chat-msg.participant-message .direct-chat-text.message-bubble::after {
            left: -8px;
            border-width: 8px 10px 8px 0;
            border-color: transparent #f8f9fa transparent transparent;
        }

        .direct-chat-msg.agent-message .direct-chat-text.message-bubble::before {
            right: -10px;
            border-width: 8px 0 8px 10px;
            border-color: transparent transparent transparent rgba(37, 211, 102, 0.35);
        }

        .direct-chat-msg.agent-message .direct-chat-text.message-bubble::after {
            right: -8px;
            border-width: 8px 0 8px 10px;
            border-color: transparent transparent transparent #dcf8c6;
        }

        .message-bubble.selected-reply {
            border-color: rgba(60, 141, 188, 0.6);
            box-shadow: 0 0 0 2px rgba(60, 141, 188, 0.1);
        }

        .quoted-message {
            border-right: 3px solid #3c8dbc;
            padding-right: 0.75rem;
            color: #6c757d;
            font-size: 0.85rem;
        }

        .direct-chat-msg.agent-message .quoted-message {
            border-right: 0;
            border-left: 3px solid rgba(37, 211, 102, 0.35);
            padding-right: 0;
            padding-left: 0.75rem;
        }

        .direct-chat-msg .message-actions {
            margin-top: 0.25rem;
            width: 100%;
        }

        .direct-chat-msg.participant-message .message-actions {
            text-align: left;
        }

        .direct-chat-msg.agent-message .message-actions {
            text-align: right;
        }

        .reply-preview-card {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background-color: #f0f7ff;
            border-radius: 0.75rem;
            border-right: 3px solid #3c8dbc;
            padding: 0.75rem 1rem;
        }

        .reply-preview-card button {
            border: none;
        }

        .message-actions .btn-link {
            color: #3c8dbc;
        }

        .message-actions .btn-link:hover {
            text-decoration: none;
            color: #367fa9;
        }
    </style>
@endsection

@section('script')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const replyInput = document.getElementById('reply_to_message_id');
            const replyPreview = document.getElementById('reply-preview');
            const replyPreviewText = document.getElementById('reply-preview-text');
            const replyPreviewClear = document.getElementById('reply-preview-clear');
            const messageBubbles = document.querySelectorAll('.message-bubble');

            function showPreview(messageId, messageText) {
                if (!replyPreview || !replyPreviewText || !replyInput) {
                    return;
                }

                replyInput.value = messageId || '';

                if (messageId) {
                    replyPreviewText.textContent = messageText || '';
                    replyPreview.classList.remove('d-none');

                    messageBubbles.forEach((bubble) => {
                        bubble.classList.toggle('selected-reply', bubble.dataset.messageId === messageId);
                    });
                } else {
                    replyPreviewText.textContent = '';
                    replyPreview.classList.add('d-none');

                    messageBubbles.forEach((bubble) => {
                        bubble.classList.remove('selected-reply');
                    });
                }
            }

            document.querySelectorAll('.reply-button').forEach(function (button) {
                button.addEventListener('click', function () {
                    const messageId = this.dataset.messageId;
                    const messageText = this.dataset.messagePreview || '';

                    if (replyInput && replyInput.value === messageId) {
                        showPreview('', '');
                        return;
                    }

                    showPreview(messageId, messageText);
                });
            });

            if (replyPreviewClear) {
                replyPreviewClear.addEventListener('click', function () {
                    showPreview('', '');
                });
            }

            if (replyInput && replyInput.value) {
                const selectedMessage = document.querySelector('.reply-button[data-message-id="' + replyInput.value + '"]');
                if (selectedMessage) {
                    showPreview(replyInput.value, selectedMessage.dataset.messagePreview || '');
                }
            }
        });
    </script>
@endsection
