<!-- فرم تغییر دسته‌بندی CRM -->
<div class="card border-0 shadow-sm">
    <div class="card-body">
        <h6 class="mb-3">تغییر دسته‌بندی تیکت</h6>
        <form id="crm-category-form">
            @csrf
            <input type="hidden" name="ticket_id" value="{{ $ticket['new_ticketid'] }}">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="parent_category">دسته‌بندی اصلی</label>
                        <select name="parent_category" id="parent_category" class="form-control" required>
                            @if(count($parentCategories) > 0)
                                <option value="">انتخاب کنید...</option>
                                @foreach($parentCategories as $category)
                                    <option value="{{ $category['new_ticketcategoryid'] }}">
                                        {{ $category['new_name'] }}
                                    </option>
                                @endforeach
                            @else
                                <option value="">دسته‌بندی‌ای در CRM موجود نیست</option>
                            @endif
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="child_category">زیردسته</label>
                        <select name="category_id" id="child_category" class="form-control" required>
                            <option value="">ابتدا دسته‌بندی اصلی را انتخاب کنید</option>
                        </select>
                    </div>
                </div>
            </div>
            <button type="button" class="btn btn-warning" onclick="changeCrmCategory()" 
                    @if(count($parentCategories) == 0) disabled @endif>
                <i class="fa fa-exchange"></i> تغییر دسته‌بندی
            </button>
            @if(count($parentCategories) == 0)
                <small class="text-muted d-block mt-2">
                    <i class="fa fa-info-circle"></i> 
                    هیچ دسته‌بندی‌ای در سیستم CRM تعریف نشده است.
                </small>
            @endif
        </form>
    </div>
</div>

<script>
    $(document).ready(function() {
        // رویداد تغییر دسته‌بندی اصلی
        $('#parent_category').change(function() {
            const parentId = $(this).val();
            const childSelect = $('#child_category');
            
            childSelect.html('<option value="">در حال بارگذاری...</option>');
            
            if (parentId) {
                $.ajax({
                    url: "{{ route('ATRoutes.crm.getChildCategories') }}",
                    method: 'GET',
                    data: { parent_id: parentId },
                    success: function(response) {
                        childSelect.html('<option value="">انتخاب کنید...</option>');
                        
                        if (response && response.length > 0) {
                            response.forEach(function(category) {
                                childSelect.append(
                                    `<option value="${category.new_ticketcategoryid}">${category.new_name}</option>`
                                );
                            });
                        } else {
                            childSelect.html('<option value="">زیردسته‌ای موجود نیست</option>');
                        }
                    },
                    error: function() {
                        childSelect.html('<option value="">خطا در بارگذاری</option>');
                    }
                });
            } else {
                childSelect.html('<option value="">ابتدا دسته‌بندی اصلی را انتخاب کنید</option>');
            }
        });
    });

    function changeCrmCategory() {
        const form = document.getElementById('crm-category-form');
        const formData = new FormData(form);

        // بررسی انتخاب دسته‌بندی
        const categoryId = formData.get('category_id');
        if (!categoryId) {
            alert('لطفاً دسته‌بندی را انتخاب کنید');
            return;
        }

        // غیرفعال کردن دکمه
        const submitBtn = event.target;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> در حال تغییر...';

        $.ajax({
            url: "{{ route('ATRoutes.crm.changeCategory') }}",
            method: 'POST',
            data: {
                ticket_id: formData.get('ticket_id'),
                category_id: categoryId,
                _token: formData.get('_token')
            },
            success: function(response) {
                if (response.success) {
                    if (typeof show_message === 'function') {
                        show_message(response.message);
                    } else {
                        alert(response.message);
                    }

                    // بروزرسانی نمایش دسته‌بندی در صفحه
                    if (response.category_name) {
                        const categoryDisplay = document.querySelector('.ticket-category-display');
                        if (categoryDisplay) {
                            categoryDisplay.textContent = response.category_name;
                        }
                    }

                    // ریست کردن فرم
                    form.reset();
                }
            },
            error: function(xhr) {
                const errorMsg = xhr.responseJSON?.error || 'خطا در تغییر دسته‌بندی';
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
                submitBtn.innerHTML = '<i class="fa fa-exchange"></i> تغییر دسته‌بندی';
            }
        });
    }
</script>