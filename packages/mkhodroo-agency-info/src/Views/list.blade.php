@extends('layouts.app')

@section('content')
    <div class="box">
        <div class="card p-4">
            <button onclick="new_agency()" class="btn btn-primary col-sm-2">{{ __('Add New Agency') }}</button>
        </div>

        <div class="card p-4 ">
            <form action="javascript:void(0)" id="filter-form1" class="col-sm-12 table-responsive">
                <div class="row col-sm-12">
                    <div class="col-sm-3 row">
                        <div class="col-sm-12">
                            {{ __(config('agency_info.main_field_name')) }}
                            <select name="{{ config('agency_info.main_field_name') }}_search" id="" class="form-control">
                                <option value="">{{ __('All') }}</option>
                                @foreach (config('agency_info.customer_type') as $catagory => $catagory_detail)
                                    <option value="{{ $catagory }}">{{ __($catagory_detail['name']) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-sm-3 row">
                        <div class="col-sm-12">
                            {{__('province')}}
                            <select name="province_search" id="" class="form-control col-sm-12">
                                <option value="">{{ __('All') }}</option>
                                @foreach ($provinces as $province)
                                    <option value="{{ $province->id }}">{{ $province->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-sm-3 row">
                        <div class="col-sm-12">
                            {{__('last referal')}}
                            <select name="last_referral_search" id="" class="form-control col-sm-12">
                                <option value="">{{ __('All') }}</option>
                                @foreach ($last_referrals as $last_refferal)
                                    <option value="{{ $last_refferal->value }}">{{ $last_refferal->value }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-sm-3 row">
                        <div class="col-sm-12">
                            {{__('new status')}}
                            <select name="new_status_search" id="" class="form-control col-sm-12">
                                <option value="">{{ __('All') }}</option>
                                @foreach ($new_statuses as $new_status)
                                    <option value="{{ $new_status->value }}">{{ $new_status->value }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-sm-12 row p-1"></div>
                    <div class="col-sm-3 row">
                        <div class="col-sm-12">
                            <input type="text" name="field_value" id="" class="form-control col-sm-12"
                            placeholder="{{ __('Everything') }}">
                        </div>
                    </div>
                    <div class="col-sm-3 row">
                        <div class="col-sm-6">
                            <button onclick="filter()" class="btn btn-primary">{{ __('Filter') }}</button>
                        </div>
                        <div class="col-sm-6">
                            <button class="btn btn-default" onclick="show_columns()">{{ __('Columns') }}</button>
                        </div>
                    </div>
                    <div class="col-sm-3 row mt-2">
                        <div class="col-sm-12">
                            <button type="button" onclick="toggleAdvancedFilter()" class="btn btn-dark col-sm-12">
                                {{ __('Advanced Filter') }}
                            </button>
                        </div>
                    </div>
                    <div class="col-sm-12 mt-3" id="advanced-filter-wrapper" style="display: none;">
                        <div id="advanced-filter-rows"></div>
                        <button type="button" class="btn btn-outline-primary mt-3" onclick="addAdvancedFilterRow()">
                            {{ __('Add Condition') }}
                        </button>
                    </div>
                </div>
                <div id="advanced-filter-template" style="display: none;">
                    <div class="row advanced-filter-row align-items-end mb-3" data-index="__index__">
                        <div class="col-sm-3">
                            <label class="form-label">{{ __('Field') }}</label>
                            <select name="advanced_filters[__index__][key]" class="form-control">
                                <option value="">{{ __('Select Field') }}</option>
                                @foreach ($cols as $col)
                                    <option value="{{ $col }}">{{ __($col) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-sm-2">
                            <label class="form-label">{{ __('Operator') }}</label>
                            <select name="advanced_filters[__index__][condition]" class="form-control">
                                <option value="equals" selected>{{ __('Equals') }}</option>
                                <option value="contains">{{ __('Contains') }}</option>
                            </select>
                        </div>
                        <div class="col-sm-3">
                            <label class="form-label">{{ __('Value') }}</label>
                            <input type="text" name="advanced_filters[__index__][value]" class="form-control" />
                        </div>
                        <div class="col-sm-2 boolean-operator-wrapper">
                            <label class="form-label">{{ __('Condition') }}</label>
                            <select name="advanced_filters[__index__][boolean]" class="form-control">
                                <option value="and" selected>{{ __('AND') }}</option>
                                <option value="or">{{ __('OR') }}</option>
                            </select>
                        </div>
                        <div class="col-sm-2 d-flex align-items-center mt-4 mt-sm-0">
                            <button type="button" class="btn btn-danger" onclick="removeAdvancedFilterRow(this)">
                                {{ __('Remove') }}
                            </button>
                        </div>
                    </div>
                </div>
            </form>

        </div>
        <div class="card p-4" style="display: none" id="columns_div">
            <div class="row">
                <div class="col-sm-12" style="float: right">
                    <select name="columns" id="columns" class="select2" multiple>
                        @for ($i = 0; $i < count($cols); $i++)
                            <option value="{{ $i }}">{{ __($cols[$i]) }}</option>
                        @endfor
                    </select>
                </div>
            </div>
        </div>

        <div class="card p-4">

            <table class="table table-stripped" id="infos">
                <thead>
                    <tr>
                        @for ($i = 0; $i < count($cols); $i++)
                            <th>{{ __($cols[$i]) }}</th>
                        @endfor
                    </tr>
                </thead>
            </table>
        </div>
    </div>
@endsection

@section('script')
    <script>
        initial_view()
        send_ajax_get_request(
            "{{ route('agencyInfo.list') }}",
            function(res) {
                console.log(res);
            }
        )
        $(document).ready(function() {
            var table = create_datatable(
                "infos",
                "{{ route('agencyInfo.list') }}",
                [
                    @for ($i = 0; $i < count($cols); $i++)
                        {
                            data: '{{ $cols[$i] }}',
                            render: function(data) {
                                return data ? data : '';
                            },
                            visible: <?php echo in_array($cols[$i], config('agency_info.default_fields')) ? true : 'false'; ?>
                        }
                        @if ($i != count($cols) - 1)
                            ,
                        @endif
                    @endfor
                ],
                function(row, data) {
                    // تغییر رنگ پس‌زمینه ردیف بر اساس مقدار فیلد enable
                    if (data.fin_green == 'ok') {
                        $(row).css('background-color', 'green');
                    }
                }
            )

            table.on('dblclick', 'tr', function() {
                var data = table.row(this).data();
                console.log(data);
                open_edit_form(data.parent_id, 'info')
                // show_edit_modal(data.id);
            })
        })


        function columnVisible(num) {
            var column = table.column(num);
            column.visible(1);
        }

        function columnHide(num) {
            var column = table.column(num);
            column.visible(0);
        }

        $('#columns').val([
            @for ($i = 0; $i < count($cols); $i++)
                @if (in_array($cols[$i], config('agency_info.default_fields')))
                    "{{ $i }}",
                @endif
            @endfor
        ]).trigger("change");

        function apply() {
            @for ($i = 0; $i < count($cols); $i++)
                columnHide({{ $i }})
            @endfor
            var columns = $('#columns').val();
            columns.forEach(function(column) {
                columnVisible(column)
            })
        }

        function show_columns() {
            var c = $('#columns_div')
            if (c.css('display') == 'none') {
                c.css('display', 'block')
            } else {
                c.css('display', 'none')
            }
        }

        function open_edit_form(parent_id, active_tab) {
            url = "{{ route('agencyInfo.editForm', ['parent_id' => 'parent_id']) }}";
            url = url.replace('parent_id', parent_id);
            open_admin_modal(
                url,
                '',
                function() {
                    var tab = $(`#${active_tab}-tab`).attr('class');
                    var tabBody = $(`#${active_tab}`).attr('class');
                    $(`#${active_tab}-tab`).click()
                }
            )

        }

        function new_agency() {
            open_admin_modal(
                "{{ route('agencyInfo.createForm') }}"
            )
        }

        let advancedFilterIndex = 0;

        function toggleAdvancedFilter() {
            var container = $('#advanced-filter-wrapper');
            if (container.is(':visible')) {
                container.slideUp();
            } else {
                container.slideDown();
                if (container.find('.advanced-filter-row').length === 0) {
                    addAdvancedFilterRow();
                }
            }
        }

        function addAdvancedFilterRow() {
            var template = $('#advanced-filter-template').html();
            template = template.replace(/__index__/g, advancedFilterIndex);
            $('#advanced-filter-rows').append(template);
            advancedFilterIndex++;
            refreshAdvancedFilterBoolean();
        }

        function removeAdvancedFilterRow(button) {
            $(button).closest('.advanced-filter-row').remove();
            refreshAdvancedFilterBoolean();
        }

        function refreshAdvancedFilterBoolean() {
            $('#advanced-filter-rows .advanced-filter-row').each(function(index) {
                if (index === 0) {
                    $(this).find('.boolean-operator-wrapper').hide();
                } else {
                    $(this).find('.boolean-operator-wrapper').show();
                }
            });
        }

        function filter() {
            apply()
            var fd = new FormData($('#filter-form1')[0]);
            fd.append('cols', $('#columns').val());
            send_ajax_formdata_request(
                "{{ route('agencyInfo.filterList') }}",
                fd,
                function(res) {
                    console.log(res);
                    update_datatable(res.data);
                }
            )
        }
    </script>
@endsection
