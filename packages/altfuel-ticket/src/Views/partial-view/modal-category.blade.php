<div class="row">
    <select name="" id="parent_cat_on_modal" class="parent-cat form-control col-sm-4"></select>
    <select name="catagory" id="child_cat_on_modal" class="child-cat form-control col-sm-4 "></select>
    {{-- <select name="actor" id="actor_select_on_modal" class="actor-select form-control col-sm-4 "></select> --}}
</div>
<script>
    $(document).ready(function() {
        var parent_cat = $('#parent_cat_on_modal');
        var child_cat = $('#child_cat_on_modal');
        var actor_select = $('#actor_select_on_modal');
        send_ajax_get_request(
            "{{ route('ATRoutes.catagory.getAllParent') }}",
            function(data) {
                parent_cat.html('');
                data.forEach(element => {
                    parent_cat.append(new Option(`${element.name}`, element.id));
                });
                getChildrenByParentId($('#parent_cat_on_modal').val())
                $('#parent_cat_on_modal').val($('#parent_cat_on_modal').val())

            }
        );
        parent_cat.on('change', function() {
            getChildrenByParentId($(this).val())
            $('#parent_cat_on_modal').val($(this).val())
        })
        var count;

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
                    data.forEach(element => {
                        // Skip irngv category (id: 1, parent_id: 1)
                        if (element.id == 1 && element.parent_id == 1) {
                            return;
                        }
                        
                        if (element.count) {
                            child_cat.append(
                                new Option(
                                    element.name + '(' + element.count + ')',
                                    element.id
                                )
                            )
                        } else {
                            child_cat.append(
                                new Option(
                                    element.name,
                                    element.id
                                )
                            )
                        }


                    });
                    getActorsByCatId($('#child_cat_on_modal').val())
                    $('#actor_select_on_modal').val($('#actor_select_on_modal').val())
                }
            )
        }

        child_cat.on('change', function() {
            getActorsByCatId($(this).val())
            $('#child_cat_on_modal').val($(this).val())
        })

        function getActorsByCatId(catId) {
            var url = "{{ route('ATRoutes.catagory.getActorsByCatId', ['cat_id' => 'cat_id']) }}";
            url = url.replace('cat_id', catId)
            actor_select.html('');
            send_ajax_get_request(
                url,
                function(data) {
                    actor_select.html('');
                    actor_select.append(
                        new Option(
                            "سیستمی",
                            "random"
                        )
                    )
                    data.forEach(element => {
                        actor_select.append(
                            new Option(
                                element.display_name,
                                element.id
                            )
                        )
                    });
                }
            )
        }

    })
</script>
