<div class="container py-4">
    <div class="d-flex justify-content-between align-items-start mb-3">
        <h5 class="mb-0">شناسه تیکت: <strong>{{ $ticket->id }}</strong></h5>
        <button type="button" class="btn btn-sm" onclick="closeModal()" data-bs-dismiss="modal"
            aria-label="Close"><i class="fa fa-times"></i></button>
    </div>

    <div class="row g-4">
        <!-- اطلاعات کاربر -->
        <div class="col-md-4">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <p class="mb-2">
                        <strong>کاربر:</strong> {{ $ticket->user()?->display_name }}
                        @if ($ticket->user()->role_id == config('user_profile.agency_role'))
                            <i class="fa fa-check-circle text-primary"></i>
                        @endif
                    </p>
                    <p class="mb-2"><strong>شماره همراه:</strong> {{ $ticket->user()->email ?? '' }}</p>
                    <p class="mb-0">
                        <strong>کارشناس:</strong>
                        {{ $ticket->actor_id ? $ticket->actor()?->display_name : 'تخصیص داده نشده' }}
                    </p>
                    <p class="mb-0 mt-2">
                        <strong>نوع تبدیل:</strong>
                        {{ $ticket->conversion_type_label ?? 'ثبت نشده' }}
                    </p>
                </div>
            </div>
        </div>

        <!-- وضعیت‌ها -->
        <div class="col-md-8">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="row g-2">
                        @foreach (config('ATConfig.status') as $key => $status)
                            <div class="col-6 col-md-3">
                                <button class="btn btn-outline-secondary w-100 status-btn" id="{{ $key }}"
                                    onclick="change_status('{{ $key }}')">{{ __($status) }}</button>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- بخش نظرات -->
    <hr class="my-4">
    <p>پیام ها</p>
    <div class="direct-chat-messages overflow-auto border rounded p-3 bg-light" style="height: 500px">
        @foreach ($ticket->comments() as $comment)
            <div class="direct-chat-msg {{ $comment->user()->id === auth()->user()->id ? 'text-end' : '' }}">
                <div class="d-flex align-items-start mb-2">
                    <div class="p-3 rounded shadow-sm w-100 {{ $comment->user()->id === auth()->user()->id ? 'bg-success' : 'bg-white' }}">
                        <div class="d-flex justify-content-between">
                            <small>{{ $comment->user()?->display_name ?? '' }}</small>
                            <small class="text-muted">{{ verta($comment->created_at) }}</small>
                        </div>
                        <hr>
                        <div style="white-space: pre-line">{{ $comment->text ?? '' }}</div>

                        @foreach ($comment->attachments() as $index => $attach)
                            <a class="d-block mt-2" href="{{ url($attach->file) }}" target="_blank">پیوست
                                {{ $index + 1 }}</a>
                        @endforeach

                        @if ($comment->attachments()->count() > 1)
                            <a href="{{ route('ATRoutes.download.zip', ['id' => $comment->id]) }}"
                                class="btn btn-sm btn-outline-primary mt-2">دانلود همه به صورت یکجا</a>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach

        <div class="mt-3">
            <a href="{{ route('ATRoutes.ticket.download.zip', ['id' => $ticket->id]) }}"
                class="btn btn-outline-primary">دانلود همه پیوست‌ها</a>
        </div>
    </div>

    <!-- فرم کامنت یا فرم بسته بودن تیکت -->
    <div class="mt-4">
        @if ($ticket->status === config('ATConfig.status.closed'))
            <div class="alert alert-danger text-center">
                {{ __('This ticket is closed') }}
            </div>
        @else
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body">
                    @include('ATView::partial-view.add-comment-form', [
                        'form_id' => 'comment-form',
                        'ticket_id' => $ticket->ticket_id,
                    ])
                </div>
            </div>

            @if (auth()->user()->access('change-catagory'))
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        @include('ATView::partial-view.change-catagory-form', [
                            'form_id' => 'chage-cat-form',
                            'ticket_id' => $ticket->ticket_id,
                        ])
                    </div>
                </div>
            @endif
        @endif
    </div>
</div>

<script>
    change_status_btn_color();

    function change_status_btn_color() {
        let url = "{{ route('ATRoutes.get.status', ['id' => 'id']) }}".replace('id', '{{ $ticket->ticket_id }}');
        send_ajax_get_request(url, function(res) {
            $('.status-btn').css('background', '#f8f9fa');
            $('#' + res).css('background', '#fa7f69');
        });
    }

    window.change_status = function(status_key) {
        const fd = new FormData();
        fd.append('ticket_id', '{{ $ticket->ticket_id }}');
        fd.append('status_key', status_key);
        send_ajax_formdata_request("{{ route('ATRoutes.changeStatus') }}", fd, function(res) {
            console.log(res);
            change_status_btn_color();
            filter();
        });
    }

    function closeModal() {
        $('#admin-modal').modal('hide');
    }
</script>
