<div class="container py-4">
    <div class="d-flex justify-content-between align-items-start mb-3">
        <h5 class="mb-0">شناسه تیکت: <strong>{{ $ticket['new_ticket_id'] ?? $ticket['new_ticketid'] }}</strong></h5>
        <button type="button" class="btn btn-sm" onclick="closeModal()" data-bs-dismiss="modal"
            aria-label="Close"><i class="fa fa-times"></i></button>
    </div>

    <div class="row g-4">
        <!-- اطلاعات کاربر -->
        <div class="col-md-4">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h6 class="mb-3">اطلاعات مخاطب</h6>
                    @if(isset($ticket['new_contact']))
                        <p class="mb-2">
                            <strong>نام:</strong> {{ $ticket['new_contact']['fullname'] ?? 'نامشخص' }}
                        </p>
                        <p class="mb-2">
                            <strong>شماره همراه:</strong> {{ $ticket['new_contact']['mobilephone'] ?? '-' }}
                        </p>
                        <p class="mb-2">
                            <strong>ایمیل:</strong> {{ $ticket['new_contact']['emailaddress1'] ?? '-' }}
                        </p>
                    @else
                        <p class="text-muted">اطلاعات مخاطب موجود نیست</p>
                    @endif
                    
                    <hr>
                    
                    <h6 class="mb-3">اطلاعات تیکت</h6>
                    <p class="mb-2">
                        <strong>دسته‌بندی:</strong> 
                        {{ $ticket['new_cat_id']['new_name'] ?? 'نامشخص' }}
                    </p>
                    <p class="mb-2">
                        <strong>وضعیت:</strong> 
                        <span class="badge badge-info">{{ $ticket['new_status'] ?? 'نامشخص' }}</span>
                    </p>
                    @if(isset($ticket['new_conversion_type']))
                        <p class="mb-2">
                            <strong>نوع تبدیل:</strong> {{ $ticket['new_conversion_type'] }}
                        </p>
                    @endif
                    @if(isset($ticket['new_score']))
                        <p class="mb-2">
                            <strong>امتیاز:</strong> {{ $ticket['new_score'] }}
                        </p>
                    @endif
                </div>
            </div>
        </div>

        <!-- عنوان و تاریخ‌ها -->
        <div class="col-md-8">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h5 class="mb-3">{{ $ticket['new_title'] ?? 'بدون عنوان' }}</h5>
                    <div class="row">
                        <div class="col-md-6">
                            <p class="mb-2">
                                <strong>تاریخ ایجاد:</strong> 
                                {{ $ticket['new_created_at'] ? verta($ticket['new_created_at'])->format('Y/m/d H:i:s') : '-' }}
                            </p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-2">
                                <strong>آخرین بروزرسانی:</strong> 
                                {{ $ticket['new_updated_at'] ? verta($ticket['new_updated_at'])->format('Y/m/d H:i:s') : '-' }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- بخش نظرات -->
    <hr class="my-4">
    <h6>پیام‌ها</h6>
    <div class="direct-chat-messages overflow-auto border rounded p-3 bg-light" style="height: 500px">
        @if(count($comments) > 0)
            @foreach ($comments as $comment)
                <div class="direct-chat-msg mb-3">
                    <div class="p-3 rounded shadow-sm bg-white">
                        <div class="d-flex justify-content-between mb-2">
                            <small><strong>{{ $comment['new_user_name'] ?? 'کاربر' }}</strong></small>
                            <small class="text-muted">
                                {{ $comment['new_created_at'] ? verta($comment['new_created_at'])->format('Y/m/d H:i') : '-' }}
                            </small>
                        </div>
                        <hr>
                        <div style="white-space: pre-line">{{ $comment['new_text'] ?? '' }}</div>
                    </div>
                </div>
            @endforeach
        @else
            <div class="alert alert-info">
                هیچ پیامی برای این تیکت ثبت نشده است.
            </div>
        @endif
    </div>
</div>

<script>
    function closeModal() {
        $('#ticket-modal').modal('hide');
    }
</script>
