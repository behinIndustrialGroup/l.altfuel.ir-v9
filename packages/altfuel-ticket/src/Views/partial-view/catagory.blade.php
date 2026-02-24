<div class="row">
    <select name="" id="parent_cat" class="parent-cat form-control col-sm-4"></select>
    <select name="catagory" id="child_cat" class="child-cat form-control col-sm-4 "></select>
</div>
<script>
    $(document).ready(function() {
        var parent_cat = $('.parent-cat');
        var child_cat = $('.child-cat');

        function appendOptionWithMeta(element, text) {
            const option = new Option(text, element.id);
            option.dataset.conversionEnabled = element.conversion_type_enabled ? 1 : 0;
            option.dataset.conversionRequired = element.conversion_type_required ? 1 : 0;
            return option;
        }

        function dispatchCategoryChange(option) {
            if (!option) {
                $(document).trigger('ticket-category-changed', [{ enabled: false, required: false }, null]);
                return;
            }

            const meta = {
                enabled: option.dataset.conversionEnabled === '1' || option.dataset.conversionEnabled === 1,
                required: option.dataset.conversionRequired === '1' || option.dataset.conversionRequired === 1,
            };

            $(document).trigger('ticket-category-changed', [meta, option.value]);
        }

        send_ajax_get_request(
            "{{ route('ATRoutes.catagory.getAllParent') }}",
            function(data) {
                parent_cat.html('');
                parent_cat.append(new Option("لطفا دسته بندی را انتخاب کنید", "notSelected"))
                data.forEach(element => {
                    parent_cat.append(new Option(`${element.name}`, element.id));
                });
                getChildrenByParentId($('.parent-cat').val())
                $('.parent-cat').val($('.parent-cat').val())

            }
        );
        parent_cat.on('change', function() {
            getChildrenByParentId($(this).val())
            $('.parent-cat').val($(this).val())
        })

        function getChildrenByParentId(parentId) {
            @if (auth()->user()->access('new-tickets-counter'))
                var url =
                    "{{ route('ATRoutes.catagory.getChildrenByParentId', ['parent_id' => 'parent_id', 'count' => 'count']) }}";
                url = url.replace('parent_id', parentId)
            @else
                var url =
                    "{{ route('ATRoutes.catagory.getChildrenByParentId', ['parent_id' => 'parent_id']) }}";
                url = url.replace('parent_id', parentId)
            @endif

            child_cat.html('');
            send_ajax_get_request(
                url,
                function(data) {
                    child_cat.html('');
                    let firstOption = null;
                    data.forEach(element => {
                        // Skip irngv category (id: 1, parent_id: 1) and id: 11, parent_id: 11
                        if ((element.id == 1 && element.parent_id == 1) || (element.id == 11 && element.parent_id == 11)) {
                            return;
                        }
                        
                        if (element.count) {
                            const option = appendOptionWithMeta(element, element.name + '(' + element.count + ')');
                            if (!firstOption) {
                                firstOption = option;
                            }
                            child_cat.append(option)
                        } else {
                            const option = appendOptionWithMeta(element, element.name);
                            if (!firstOption) {
                                firstOption = option;
                            }
                            child_cat.append(option)
                        }


                    });
                    const selectedOption = child_cat[0]?.options[child_cat[0].selectedIndex] ?? firstOption;
                    if (selectedOption) {
                        child_cat.val(selectedOption.value);
                    }
                    dispatchCategoryChange(selectedOption ?? null);
                }
            )
        }

        child_cat.on('change', function() {
            const option = this.options[this.selectedIndex];
            dispatchCategoryChange(option ?? null);
        });

    })
</script>
