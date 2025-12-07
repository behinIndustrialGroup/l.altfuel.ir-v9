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
    <div class="direct-chat-messages overflow-auto border rounded p-3 bg-light" style="height: 400px" id="comments-container">
        @if(count($comments) > 0)
            @foreach ($comments as $comment)
                @php
                    // نام کاربر از contact_name که در کنترلر ست شده
                    $userName = $comment['contact_name'] ?? 'کاربر';
                    
                    // تعیین رنگ و نقش بر اساس is_owner
                    if ($comment['new_is_owner'] ?? false) {
                        $bgClass = 'bg-success text-white';
                        $textClass = 'text-white-50';
                        $userRole = '(کاربر)';
                    } else {
                        $bgClass = 'bg-info text-white';
                        $textClass = 'text-white-50';
                        $userRole = '(کارشناس)';
                    }
                @endphp
                <div class="direct-chat-msg mb-3">
                    <div class="p-3 rounded shadow-sm {{ $bgClass }}">
                        <div class="d-flex justify-content-between mb-2">
                            <small>
                                <strong>{{ $userName }}</strong>
                                <span class="{{ $textClass }}">{{ $userRole }}</span>
                            </small>
                            <small class="{{ $textClass }}">
                                {{ $comment['new_created_at'] ? verta($comment['new_created_at'])->format('Y/m/d H:i') : '-' }}
                            </small>
                        </div>
                        <hr class="border-white">
                        <div style="white-space: pre-line">{{ $comment['new_text'] ?? 'بدون متن' }}</div>
                    </div>
                </div>
            @endforeach
        @else
            <div class="alert alert-info">
                هیچ پیامی برای این تیکت ثبت نشده است.
            </div>
        @endif
    </div>

    <!-- فرم افزودن کامنت -->
    <div class="mt-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <form id="crm-comment-form">
                    @csrf
                    <input type="hidden" name="ticket_id" value="{{ $ticket['new_ticketid'] }}">
                    <div class="form-group">
                        <label for="comment_text">پیام جدید</label>
                        <textarea name="text" id="comment_text" class="form-control" rows="4" placeholder="متن پیام خود را وارد کنید..." required></textarea>
                    </div>
                    <button type="button" class="btn btn-success mt-2" onclick="submitCrmComment()">
                        <i class="fa fa-paper-plane"></i> ارسال پیام
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    function closeModal() {
        $('#ticket-modal').modal('hide');
    }

    function submitCrmComment() {
        const form = document.getElementById('crm-comment-form');
        const formData = new FormData(form);

        // بررسی اینکه متن خالی نباشد
        const text = formData.get('text');
        if (!text || text.trim() === '') {
            alert('لطفاً متن پیام را وارد کنید');
            return;
        }

        // غیرفعال کردن دکمه تا زمان اتمام درخواست
        const submitBtn = event.target;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> در حال ارسال...';

        $.ajax({
            url: "{{ route('ATRoutes.crm.addComment') }}",
            method: 'POST',
            data: {
                ticket_id: formData.get('ticket_id'),
                text: text,
                _token: formData.get('_token')
            },
            success: function(response) {
                if (response.success) {
                    // پاک کردن فرم
                    form.reset();
                    
                    // نمایش پیام موفقیت
                    if (typeof show_message === 'function') {
                        show_message(response.message);
                    } else {
                        alert(response.message);
                    }

                    // افزودن کامنت جدید به لیست
                    const now = new Date();
                    const persianDate = now.toLocaleDateString('fa-IR');
                    const time = now.toLocaleTimeString('fa-IR', { hour: '2-digit', minute: '2-digit' });
                    
                    const newComment = `
                        <div class="direct-chat-msg mb-3">
                            <div class="p-3 rounded shadow-sm bg-white">
                                <div class="d-flex justify-content-between mb-2">
                                    <small><strong>{{ auth()->user()->display_name ?? auth()->user()->name }}</strong></small>
                                    <small class="text-muted">${persianDate} ${time}</small>
                                </div>
                                <hr>
                                <div style="white-space: pre-line">${text}</div>
                            </div>
                        </div>
                    `;
                    
                    $('#comments-container').append(newComment);
                    
                    // اسکرول به پایین
                    $('#comments-container').scrollTop($('#comments-container')[0].scrollHeight);
                }
            },
            error: function(xhr) {
                const errorMsg = xhr.responseJSON?.error || 'خطا در ارسال پیام';
                if (typeof show_error === 'function') {
                    show_error(errorMsg);
                } else {
                    alert(errorMsg);
                }
                console.error(xhr);
            },
            complete: function() {
                // فعال کردن دوباره دکمه
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fa fa-paper-plane"></i> ارسال پیام';
            }
        });
    }
</script>
