<div class="card">
    <div class="row">
        <div class="col-sm-12 p-2">
            <div class="input-group mb-3 col-sm-8 float-right">
                <textarea name="text" id="crm_ticket_text" class="form-control" style="border: none" rows="4" placeholder="متن پیام" required></textarea>
            </div>
            <div class="col-sm-4 float-left" style="font-size: 15px">
                پیوست: فایل های مجاز {{ json_encode(config('ATConfig.attachment-file-types-translate')) }}
                <input type="file" id="crm-file-input" name="files[]" class="crm-file-input">
                <div id="crm-inputFields"></div>
                <button type="button" class="btn btn-info" onclick="addCrmFile()">افزودن فایل دیگر &plus;</button>
            </div>
        </div>
    </div>
</div>

<div class="btn btn-success" id="crm-submit-btn" onclick="submitCrmTicket()">
    ثبت تیکت
</div>

<script type="text/javascript">
    var maxFileSizeInMB = parseInt('{{ config('ATConfig.max-attach-file-size') }}') / 1024;

    function checkFileSize(file) {
        var maxSizeInBytes = maxFileSizeInMB * 1024 * 1024;
        return file.size <= maxSizeInBytes;
    }

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

    async function submitCrmTicket() {
        // بررسی validation
        if (!$('#crm-ticket-form')[0].checkValidity()) {
            $('#crm-ticket-form')[0].reportValidity();
            return;
        }

        const fileInput = $('.crm-file-input');
        let formData = new FormData($('#crm-ticket-form')[0]);

        // غیرفعال کردن دکمه تا زمان اتمام درخواست
        const submitBtn = $('#crm-submit-btn');
        submitBtn.prop('disabled', true);
        submitBtn.text('در حال ثبت...');

        try {
            // حلقه برای بررسی فایل‌ها و فشرده‌سازی تصاویر
            for (let i = 0; i < fileInput.length; i++) {
                const inputElement = fileInput[i];
                const file = inputElement.files[0];

                if (!file) continue;

                if (!checkFileSize(file)) {
                    if (file.type.startsWith('image/')) {
                        try {
                            const compressedBlob = await compressImage(file);
                            formData.set(`files[${i}]`, compressedBlob, file.name);
                        } catch (error) {
                            console.error('Error compressing image:', error);
                            alert('خطا در فشرده‌سازی تصویر!');
                            return;
                        }
                    } else {
                        alert(`فایل ${file.name} بیش از حد مجاز است و نمی‌تواند آپلود شود.`);
                        return;
                    }
                }
            }

            // ارسال درخواست
            $.ajax({
                url: "{{ route('ATRoutes.crm.create.store') }}",
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        if (typeof show_message === 'function') {
                            show_message(response.message);
                        } else {
                            alert(response.message);
                        }

                        // ریدایرکت به لیست تیکت‌های CRM یا صفحه مناسب
                        setTimeout(function() {
                            window.location.href = "{{ route('ATRoutes.crm.list') }}";
                        }, 1500);
                    }
                },
                error: function(xhr) {
                    let errorMsg = 'خطا در ایجاد تیکت';
                    
                    if (xhr.responseJSON) {
                        if (xhr.responseJSON.error) {
                            errorMsg = xhr.responseJSON.error;
                        } else if (xhr.responseJSON.errors) {
                            // مدیریت خطاهای validation
                            const errors = xhr.responseJSON.errors;
                            const errorMessages = [];
                            for (const field in errors) {
                                errorMessages.push(...errors[field]);
                            }
                            errorMsg = errorMessages.join('\n');
                        }
                    }
                    
                    if (typeof show_error === 'function') {
                        show_error(errorMsg);
                    } else {
                        alert(errorMsg);
                    }
                    console.error('CRM Ticket Creation Error:', xhr);
                },
                complete: function() {
                    // فعال کردن دوباره دکمه
                    submitBtn.prop('disabled', false);
                    submitBtn.text('ثبت تیکت');
                }
            });

        } catch (error) {
            console.error('Error in submitCrmTicket:', error);
            alert('خطا در ارسال فرم');
            
            // فعال کردن دوباره دکمه
            submitBtn.prop('disabled', false);
            submitBtn.text('ثبت تیکت');
        }
    }

    function addCrmFile() {
        const divEle = document.getElementById("crm-inputFields");
        const wrapper = document.createElement("div");
        wrapper.className = "mb-2";
        const iField = document.createElement("input");
        iField.setAttribute("type", "file");
        iField.setAttribute("name", "files[]");
        iField.classList.add("crm-file-input");
        wrapper.appendChild(iField);
        divEle.appendChild(wrapper);
    }
</script>