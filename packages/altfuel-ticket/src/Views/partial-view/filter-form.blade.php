@php
    $title = '';
    $agents = config('ATConfig.filter_agents', []);
    $conversionTypes = config('ATConfig.conversion_types', []);
@endphp

<!-- دکمه نمایش فیلتر پیشرفته -->
<button class="btn btn-dark d-flex align-items-center gap-2 shadow-lg rounded-pill px-4 py-2 fs-5" type="button"
    onclick="toggleAdvancedFilter()" style="transition: all 0.3s ease-in-out; font-weight: 500;">
    <i class="fa fa-filter fa-lg"></i>
    <span>فیلتر پیشرفته</span>
</button>



<!-- باکس فیلتر پیشرفته -->
<div id="advanced-filter-box"
    style="display: none; border: 1px solid #ccc; padding: 15px; border-radius: 10px; margin-top: 10px;">
    <div class="form-group mb-2">
        <label for="ticket_number">شماره تیکت</label>
        <input type="text" name="ticket_number" class="form-control" placeholder="مثلاً 1234">
    </div>

    <div class="row">
        <div class="col-md-6">
            <label>از تاریخ:</label>
            <input type="text" id="date_from" name="date_from" class="form-control" placeholder="تاریخ شروع">
            <input type="hidden" id="date_from_alt" name="date_from_alt" class="form-control" placeholder="تاریخ شروع">
        </div>
        <div class="col-md-6">
            <label>تا تاریخ:</label>
            <input type="text" id="date_to" name="date_to" class="form-control" placeholder="تاریخ پایان">
            <input type="hidden" id="date_to_alt" name="date_to_alt" class="form-control" placeholder="تاریخ پایان">
        </div>
    </div>

    <div class="form-group mb-2">
        <label for="agent_id">کارشناس پاسخ‌دهنده</label>
        <select name="agent_id" class="form-control">
            <option value="">انتخاب کارشناس</option>
            <option value="none">بدون کارشناس</option>
            @foreach ($agents as $agent)
                <option value="{{ $agent['id'] }}">{{ $agent['name'] }}</option>
            @endforeach
        </select>
    </div>

    <div class="form-group mb-2">
        <label for="conversion_type">نوع تبدیل</label>
        <select name="conversion_type" id="conversion_type" class="form-control">
            <option value="">انتخاب نوع تبدیل</option>
            @foreach ($conversionTypes as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <div class="form-group mb-2">
        <label for="filter_child_cat">دسته بندی</label>
        <div class="row">
            <div class="col-md-6 mb-2 mb-md-0">
                <select id="filter_parent_cat" name="filter_parent_cat" class="form-control"></select>
            </div>
            <div class="col-md-6">
                <select name="filter_catagory" id="filter_child_cat" class="form-control"></select>
            </div>
        </div>
    </div>

    <button class="btn btn-warning mt-2" onclick="filterWithAgent()">جستجو</button>
</div>
<script>
    $(document).ready(function() {
        $('#date_from').persianDatepicker({
            format: 'YYYY/MM/DD',
            observer: true,
            initialValue: false,
            autoClose: true,
            altField: "#date_from_alt",
            calendar: {
                persian: {
                    leapYearMode: 'astronomical',
                    locale: 'fa'
                }
            }
        });
    });

    $(document).ready(function() {
        $('#date_to').persianDatepicker({
            format: 'YYYY/MM/DD',
            observer: true,
            initialValue: false,
            autoClose: true,
            altField: "#date_to_alt",
            calendar: {
                persian: {
                    leapYearMode: 'astronomical',
                    locale: 'fa'
                }
            }
        });
    });

    $(document).ready(function() {
        var parentCat = $('#filter_parent_cat');
        var childCat = $('#filter_child_cat');

        function setDefaultOptions() {
            parentCat.html('');
            parentCat.append(new Option('انتخاب دسته بندی', ''));

            childCat.html('');
            childCat.append(new Option('انتخاب زیر دسته', ''));
        }

        setDefaultOptions();

        function loadChildren(parentId) {
            if (!parentId) {
                childCat.html('');
                childCat.append(new Option('انتخاب زیر دسته', ''));
                childCat.val('');
                return;
            }

            @if (auth()->user()->access('new-tickets-counter'))
                var url =
                    "{{ route('ATRoutes.catagory.getChildrenByParentId', ['parent_id' => 'parent_id', 'count' => 'count']) }}";
            @else
                var url =
                    "{{ route('ATRoutes.catagory.getChildrenByParentId', ['parent_id' => 'parent_id']) }}";
            @endif

            url = url.replace('parent_id', parentId);
            childCat.html('');
            childCat.append(new Option('انتخاب زیر دسته', ''));
            childCat.val('');

            send_ajax_get_request(
                url,
                function(data) {
                    data.forEach(element => {
                        if (element.count) {
                            childCat.append(new Option(element.name + '(' + element.count + ')',
                                element.id));
                        } else {
                            childCat.append(new Option(element.name, element.id));
                        }
                    });
                }
            );
        }

        send_ajax_get_request(
            "{{ route('ATRoutes.catagory.getAllParent') }}",
            function(data) {
                data.forEach(element => {
                    parentCat.append(new Option(element.name, element.id));
                });

                parentCat.trigger('change');
            }
        );

        parentCat.on('change', function() {
            loadChildren($(this).val());
        });
    });

    function toggleAdvancedFilter() {
        $('#advanced-filter-box').slideToggle();
    }
</script>
