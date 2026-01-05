<div class="row">
    <div class="col-md-6">
        <select name="parent_category" id="parent_category" class="form-control" required>
            @if(count($parentCategories) > 0)
                <option value="">انتخاب دسته‌بندی اصلی...</option>
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
    <div class="col-md-6">
        <select name="category_id" id="child_category" class="form-control" required>
            <option value="">ابتدا دسته‌بندی اصلی را انتخاب کنید</option>
        </select>
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
                    url: "{{ route('ATRoutes.crm.create.getChildCategories') }}",
                    method: 'GET',
                    data: { parent_id: parentId },
                    success: function(response) {
                        console.log('Child categories response:', response);
                        
                        childSelect.html('<option value="">انتخاب کنید...</option>');
                        
                        if (response && response.length > 0) {
                            response.forEach(function(category) {
                                childSelect.append(
                                    `<option value="${category.new_ticketcategoryid}" data-conversion-enabled="${category.new_conversion_type_enabled || false}">${category.new_name}</option>`
                                );
                            });
                        } else {
                            childSelect.html('<option value="">زیردسته‌ای موجود نیست</option>');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error loading child categories:', {
                            status: status,
                            error: error,
                            response: xhr.responseText,
                            parentId: parentId
                        });
                        
                        let errorMsg = 'خطا در بارگذاری';
                        if (xhr.responseJSON && xhr.responseJSON.error) {
                            errorMsg = xhr.responseJSON.error;
                        }
                        
                        childSelect.html(`<option value="">خطا: ${errorMsg}</option>`);
                        
                        // نمایش خطا به کاربر
                        if (typeof show_error === 'function') {
                            show_error('خطا در دریافت زیردسته‌ها: ' + errorMsg);
                        } else {
                            alert('خطا در دریافت زیردسته‌ها: ' + errorMsg);
                        }
                    }
                });
            } else {
                childSelect.html('<option value="">ابتدا دسته‌بندی اصلی را انتخاب کنید</option>');
            }
        });

        // رویداد تغییر دسته‌بندی فرعی برای نمایش/مخفی کردن فیلد نوع تبدیل
        $('#child_category').change(function() {
            const selectedOption = $(this).find('option:selected');
            const conversionEnabled = selectedOption.data('conversion-enabled');
            const conversionField = $('#conversion_type_field');
            
            if (conversionEnabled === true) {
                conversionField.show();
                $('#conversion_type').prop('required', true);
            } else {
                conversionField.hide();
                $('#conversion_type').prop('required', false).val('');
            }
        });
    });
</script>