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
                        <span class="ticket-category-display">{{ $ticket['new_cat_id']['new_name'] ?? 'نامشخص' }}</span>
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
                    <p class="mb-2">
                        <strong>امتیاز:</strong>
                        <span id="ticket-score-display">
                            @if(isset($ticket['new_score']) && $ticket['new_score'] > 0)
                                @for($i = 1; $i <= 5; $i++)
                                    <i class="fa fa-star {{ $i <= $ticket['new_score'] ? 'text-warning' : 'text-muted' }}"></i>
                                @endfor
                                ({{ $ticket['new_score'] }}/5)
                            @else
                                <span class="text-muted">امتیازی ثبت نشده</span>
                            @endif
                        </span>
                    </p>
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
                        
                        {{-- نمایش پیوست‌ها --}}
                        @if(isset($attachments) && count($attachments) > 0)
                            @php
                                $commentAttachments = array_filter($attachments, function($att) use ($comment) {
                                    // فیلتر attachments مربوط به این کامنت بر اساس زمان
                                    $commentTime = strtotime($comment['new_created_at'] ?? '');
                                    $attachTime = strtotime($att['createdon'] ?? '');
                                    return abs($commentTime - $attachTime) < 60; // در بازه 60 ثانیه
                                });
                            @endphp
                            
                            @if(count($commentAttachments) > 0)
                                <div class="mt-2 pt-2 border-top {{ ($comment['new_is_owner'] ?? false) ? 'border-white' : '' }}">
                                    <small class="{{ ($comment['new_is_owner'] ?? false) ? 'text-white-50' : 'text-muted' }}">
                                        <i class="fa fa-paperclip"></i> پیوست‌ها:
                                    </small>
                                    @foreach($commentAttachments as $index => $attach)
                                        <a href="{{ $attach['notetext'] ?? '#' }}" target="_blank" 
                                           class="d-block mt-1 {{ ($comment['new_is_owner'] ?? false) ? 'text-white' : '' }}">
                                            <i class="fa fa-file"></i> {{ $attach['filename'] ?? 'پیوست ' . ($index + 1) }}
                                        </a>
                                    @endforeach
                                </div>
                            @endif
                        @endif
                    </div>
                </div>
            @endforeach
        @else
            <div class="alert alert-info">
                هیچ پیامی برای این تیکت ثبت نشده است.
            </div>
        @endif
    </div>

    <!-- فرم افزودن کامنت - فقط برای تیکت‌های باز -->
    @php
        $isClosed = ($ticket['new_status'] ?? '') === 'بسته شده' || ($ticket['new_status_option'] ?? 0) == 100000003;
    @endphp

    @if(!$isClosed)
        <div class="mt-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <form id="crm-comment-form" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="ticket_id" value="{{ $ticket['new_ticketid'] }}">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label for="comment_text">پیام جدید</label>
                                    <textarea name="text" id="comment_text" class="form-control" rows="4" placeholder="متن پیام خود را وارد کنید..." required></textarea>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>پیوست</label>
                                    <small class="d-block text-muted mb-2">
                                        فایل های مجاز: {{ implode(', ', config('ATConfig.attachment-file-types-translate')) }}
                                    </small>
                                    <input type="file" name="files[]" class="form-control-file file-input mb-2">
                                    <div id="crm-inputFields"></div>
                                    <button type="button" class="btn btn-sm btn-info" onclick="addCrmFile()">
                                        <i class="fa fa-plus"></i> افزودن فایل دیگر
                                    </button>
                                </div>
                            </div>
                        </div>
                        <button type="button" class="btn btn-success mt-2" onclick="submitCrmComment()">
                            <i class="fa fa-paper-plane"></i> ارسال پیام
                        </button>
                    </form>
                </div>
            </div>
        </div>
    @else
        <div class="mt-3">
            <div class="alert alert-warning text-center">
                <i class="fa fa-lock"></i> این تیکت بسته شده است و امکان ارسال پیام جدید وجود ندارد.
            </div>
        </div>
    @endif

    <!-- فرم تغییر دسته‌بندی - فقط برای کارشناسان -->
    @if(auth()->user()->access('change-catagory') && !$isClosed)
        <div class="mt-3">
            @include('ATView::partial-view.crm-change-category-form', [
                'ticket' => $ticket,
                'parentCategories' => $parentCategories
            ])
        </div>
    @endif

    <!-- فرم امتیازدهی - فقط برای کاربر -->
    @php
        $isOwner = false;
        if (auth()->check() && isset($ticket['new_contact']['contactid'])) {
            $userContactId = auth()->user()->crm_contact_id;
            $ticketContactId = $ticket['new_contact']['contactid'];
            $isOwner = ($userContactId && $ticketContactId && $userContactId === $ticketContactId);
        }
        
        $hasScore = isset($ticket['new_score']) && $ticket['new_score'] > 0;
    @endphp

    @if($isOwner && !$hasScore)
        <div class="mt-3">
            <div class="card border-0 shadow-sm" id="rating-card">
                <div class="card-body">
                    <h6 class="mb-3">امتیازدهی به خدمات</h6>
                    <p class="text-muted small">لطفاً کیفیت خدمات دریافتی را امتیاز دهید:</p>
                    <div class="rating-stars mb-3" id="rating-stars">
                        @for($i = 1; $i <= 5; $i++)
                            <i class="fa fa-star star-rating" data-score="{{ $i }}" 
                               style="font-size: 2rem; cursor: pointer; color: #ddd;"></i>
                        @endfor
                    </div>
                    <button type="button" class="btn btn-primary" id="submit-score-btn" onclick="submitScore()" style="display: none;">
                        <i class="fa fa-check"></i> ثبت امتیاز
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>

<script>
    function closeModal() {
        $('#ticket-modal').modal('hide');
    }

    // افزودن فایل جدید
    function addCrmFile() {
        const divEle = document.getElementById("crm-inputFields");
        const wrapper = document.createElement("div");
        wrapper.className = "mb-2";
        const iField = document.createElement("input");
        iField.setAttribute("type", "file");
        iField.setAttribute("name", "files[]");
        iField.classList.add("form-control-file", "file-input");
        wrapper.appendChild(iField);
        divEle.appendChild(wrapper);
    }

    // بررسی حجم فایل
    var maxFileSizeInMB = parseInt('{{ config('ATConfig.max-attach-file-size') }}') / 1024;

    function checkFileSize(file) {
        var maxSizeInBytes = maxFileSizeInMB * 1024 * 1024;
        return file.size <= maxSizeInBytes;
    }

    // فشرده‌سازی تصویر
    async function compressImage(file) {
        return new Promise(function(resolve, reject) {
            const reader = new FileReader();
            reader.readAsDataURL(file);

            reader.onload = function(event) {
                const img = new Image();
                img.src = event.target.result;

                img.onload = function() {
                    const canvas = document.createElement('canvas');
                    const ctx = canvas.getContext('2d');

                    const maxWidth = 700;
                    const scaleSize = maxWidth / img.width;
                    canvas.width = maxWidth;
                    canvas.height = img.height * scaleSize;

                    ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
                    canvas.toBlob(function(blob) {
                        resolve(blob)
                    }, 'image/jpeg', 1);
                };
            };
        })
    }

    async function submitCrmComment() {
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

        // پردازش فایل‌ها (فشرده‌سازی تصاویر بزرگ)
        const fileInputs = $('.file-input');
        const finalFormData = new FormData();
        finalFormData.append('ticket_id', formData.get('ticket_id'));
        finalFormData.append('text', text);
        finalFormData.append('_token', formData.get('_token'));

        for (let i = 0; i < fileInputs.length; i++) {
            const inputElement = fileInputs[i];
            const file = inputElement.files[0];

            if (!file) continue;

            if (!checkFileSize(file)) {
                if (file.type.startsWith('image/')) {
                    try {
                        const compressedBlob = await compressImage(file);
                        finalFormData.append(`files[${i}]`, compressedBlob, file.name);
                    } catch (error) {
                        console.error('Error compressing image:', error);
                        alert('خطا در فشرده‌سازی تصویر!');
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = '<i class="fa fa-paper-plane"></i> ارسال پیام';
                        return;
                    }
                } else {
                    alert(`فایل ${file.name} بیش از حد مجاز است و نمی‌تواند آپلود شود.`);
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fa fa-paper-plane"></i> ارسال پیام';
                    return;
                }
            } else {
                finalFormData.append(`files[${i}]`, file);
            }
        }

        $.ajax({
            url: "{{ route('ATRoutes.crm.addComment') }}",
            method: 'POST',
            data: finalFormData,
            processData: false,
            contentType: false,
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

    // امتیازدهی
    let selectedScore = 0;

    $(document).ready(function() {
        // رویداد hover برای ستاره‌ها
        $('.star-rating').hover(
            function() {
                const score = $(this).data('score');
                highlightStars(score);
            },
            function() {
                highlightStars(selectedScore);
            }
        );

        // رویداد کلیک برای انتخاب امتیاز
        $('.star-rating').click(function() {
            selectedScore = $(this).data('score');
            highlightStars(selectedScore);
            $('#submit-score-btn').show();
        });
    });

    function highlightStars(score) {
        $('.star-rating').each(function() {
            const starScore = $(this).data('score');
            if (starScore <= score) {
                $(this).css('color', '#ffc107'); // طلایی
            } else {
                $(this).css('color', '#ddd'); // خاکستری
            }
        });
    }

    function submitScore() {
        if (selectedScore === 0) {
            alert('لطفاً امتیاز خود را انتخاب کنید');
            return;
        }

        const submitBtn = $('#submit-score-btn');
        submitBtn.prop('disabled', true);
        submitBtn.html('<i class="fa fa-spinner fa-spin"></i> در حال ثبت...');

        $.ajax({
            url: "{{ route('ATRoutes.crm.setScore') }}",
            method: 'POST',
            data: {
                ticket_id: '{{ $ticket['new_ticketid'] }}',
                score: selectedScore,
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    if (typeof show_message === 'function') {
                        show_message(response.message);
                    } else {
                        alert(response.message);
                    }

                    // بروزرسانی نمایش امتیاز
                    let starsHtml = '';
                    for (let i = 1; i <= 5; i++) {
                        starsHtml += `<i class="fa fa-star ${i <= selectedScore ? 'text-warning' : 'text-muted'}"></i> `;
                    }
                    starsHtml += `(${selectedScore}/5)`;
                    $('#ticket-score-display').html(starsHtml);

                    // مخفی کردن فرم امتیازدهی
                    $('#rating-card').fadeOut(function() {
                        $(this).remove();
                    });
                }
            },
            error: function(xhr) {
                const errorMsg = xhr.responseJSON?.error || 'خطا در ثبت امتیاز';
                if (typeof show_error === 'function') {
                    show_error(errorMsg);
                } else {
                    alert(errorMsg);
                }
                console.error(xhr);
            },
            complete: function() {
                submitBtn.prop('disabled', false);
                submitBtn.html('<i class="fa fa-check"></i> ثبت امتیاز');
            }
        });
    }
</script>
