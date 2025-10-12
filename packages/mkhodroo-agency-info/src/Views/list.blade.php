@extends('layouts.app')

@section('title', __('Agency Info'))

@section('style')
    <style>
        .md-container {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .md-card {
            border-radius: 18px;
            border: none;
            box-shadow: 0 10px 30px rgba(31, 38, 135, 0.1);
            background: #ffffff;
            overflow: hidden;
        }

        .md-card__header {
            background: linear-gradient(135deg, #1976d2, #42a5f5);
            color: #fff;
            padding: 1.5rem 2rem;
        }

        .md-card__title {
            font-size: 1.5rem;
            font-weight: 600;
            margin: 0;
        }

        .md-toolbar {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            align-items: center;
        }

        .md-toolbar .btn {
            border-radius: 999px;
            padding: 0.6rem 1.5rem;
            text-transform: none;
            font-weight: 500;
            letter-spacing: .4px;
            transition: all .2s ease-in-out;
        }

        .md-toolbar .btn-primary {
            background: #1976d2;
            border: none;
        }

        .md-toolbar .btn-primary:hover {
            background: #115293;
        }

        .md-toolbar .btn-outline-secondary {
            border-radius: 999px;
        }

        .md-filter-chip {
            border-radius: 16px;
            background: rgba(25, 118, 210, 0.08);
            color: #0d47a1;
            padding: 0.35rem 0.9rem;
            font-size: 0.85rem;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
        }

        .md-form-section {
            padding: 1.5rem 2rem 2rem;
        }

        .md-form-section .form-group label {
            font-weight: 500;
            color: #37474f;
        }

        .md-advanced-filter {
            background: rgba(25, 118, 210, 0.05);
            border-radius: 14px;
            padding: 1.5rem;
            width: 100%;
        }

        .md-advanced-filter .btn-outline-primary {
            border-radius: 12px;
        }

        #columns_div {
            border-radius: 18px;
            border: none;
            box-shadow: 0 12px 26px rgba(0, 0, 0, 0.08);
        }

        .md-table-wrapper {
            padding: 0;
        }

        .md-table-wrapper table {
            margin-bottom: 0;
        }

        .md-table-wrapper thead {
            background-color: #f1f5f9;
        }

        .md-table-wrapper thead th {
            border-bottom: none;
            font-weight: 600;
            color: #37474f;
        }

        .md-table-wrapper tbody tr {
            transition: box-shadow 0.2s ease, transform 0.2s ease;
        }

        .md-table-wrapper tbody tr:hover {
            box-shadow: inset 0 0 0 9999px rgba(33, 150, 243, 0.05);
            transform: translateY(-1px);
        }

        .md-section-title {
            font-size: 1.15rem;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .md-section-title i {
            color: #1e88e5;
        }

        .md-advanced-filter .advanced-filter-row {
            background: #fff;
            border-radius: 12px;
            padding: 1rem;
            margin: 0;
            box-shadow: inset 0 0 0 1px rgba(33, 150, 243, 0.1);
        }

        .md-advanced-filter .btn-danger {
            border-radius: 12px;
        }

        @media (max-width: 767.98px) {
            .md-form-section {
                padding: 1.5rem 1.25rem 2rem;
            }

            .md-toolbar {
                flex-direction: column;
                align-items: stretch;
            }

            .md-toolbar .btn {
                width: 100%;
            }

            .md-advanced-filter .advanced-filter-row {
                padding: 1.25rem;
            }
        }
    </style>
@endsection

@section('content')
    <div class="md-container">
        <div class="md-card">
            <div class="md-card__header d-flex justify-content-between align-items-start flex-column flex-lg-row">
                <div>
                    <h1 class="md-card__title mb-2">{{ __('Agency Information Dashboard') }}</h1>
                    <p class="mb-0 text-white-50">{{ __('Manage, filter and explore agency profiles with ease.') }}</p>
                </div>
                <div class="md-filter-chip mt-3 mt-lg-0">
                    <i class="fa fa-database"></i>
                    <span>{{ __('Total Columns') }}: {{ count($cols) }}</span>
                </div>
            </div>
            <div class="md-form-section">
                <div class="md-toolbar">
                    <button onclick="new_agency()" class="btn btn-primary d-flex align-items-center gap-2">
                        <i class="fa fa-plus-circle"></i>
                        {{ __('Add New Agency') }}
                    </button>
                    <button class="btn btn-outline-secondary d-flex align-items-center gap-2" onclick="show_columns()">
                        <i class="fa fa-columns"></i>
                        {{ __('Columns') }}
                    </button>
                    <button type="button" onclick="toggleAdvancedFilter()" class="btn btn-outline-secondary d-flex align-items-center gap-2">
                        <i class="fa fa-filter"></i>
                        {{ __('Advanced Filter') }}
                    </button>
                </div>
            </div>
        </div>

        <div class="md-card">
            <div class="md-form-section">
                <h2 class="md-section-title">
                    <i class="fa fa-tune"></i>
                    {{ __('Quick Filters') }}
                </h2>
                <form action="javascript:void(0)" id="filter-form1" class="row g-4">
                    <div class="col-12 col-lg-8">
                        <div class="form-group">
                            <label class="form-label">{{ __('Search Everything') }}</label>
                            <input type="text" name="field_value" class="form-control" placeholder="{{ __('Everything') }}">
                        </div>
                    </div>
                    <div class="col-12 col-lg-4 d-flex align-items-end">
                        <div class="d-flex flex-wrap gap-2 w-100">
                            <button onclick="filter()" class="btn btn-primary flex-grow-1">
                                <i class="fa fa-search me-2"></i>{{ __('Filter') }}
                            </button>
                            <button type="button" class="btn btn-light flex-grow-1" onclick="initial_view()">
                                <i class="fa fa-undo me-2"></i>{{ __('Reset') }}
                            </button>
                        </div>
                    </div>
                    <div class="col-12">
                        <div id="advanced-filter-wrapper" class="md-advanced-filter" style="display: none;">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="fw-semibold text-primary">{{ __('Advanced Conditions') }}</span>
                                <button type="button" class="btn btn-sm btn-light" onclick="toggleAdvancedFilter()">
                                    <i class="fa fa-times"></i>
                                </button>
                            </div>
                            <div id="advanced-filter-rows" class="d-flex flex-column gap-3"></div>
                            <button type="button" class="btn btn-outline-primary mt-3" onclick="addAdvancedFilterRow()">
                                <i class="fa fa-plus me-2"></i>{{ __('Add Condition') }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="md-card p-4" style="display: none" id="columns_div">
            <div class="row">
                <div class="col-sm-12" style="float: right">
                    <label class="form-label d-block mb-2 text-muted">{{ __('Visible Columns') }}</label>
                    <select name="columns" id="columns" class="select2" multiple>
                        @for ($i = 0; $i < count($cols); $i++)
                            <option value="{{ $i }}">{{ __($cols[$i]) }}</option>
                        @endfor
                    </select>
                </div>
            </div>
        </div>

        <div class="md-card md-table-wrapper">
            <div class="p-3 border-bottom bg-white d-flex align-items-center justify-content-between">
                <h2 class="md-section-title mb-0">
                    <i class="fa fa-database"></i>
                    {{ __('Agency Records') }}
                </h2>
                <span class="text-muted small">{{ __('Double click on a row to open details.') }}</span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle" id="infos">
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

        <div id="advanced-filter-template" style="display: none;">
            <div class="row advanced-filter-row align-items-end mb-3" data-index="__index__">
                <div class="col-sm-12 col-lg-3">
                    <label class="form-label">{{ __('Field') }}</label>
                    <select name="advanced_filters[__index__][key]" class="form-control">
                        <option value="">{{ __('Select Field') }}</option>
                        @foreach ($cols as $col)
                            <option value="{{ $col }}">{{ __($col) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-12 col-lg-2">
                    <label class="form-label">{{ __('Operator') }}</label>
                    <select name="advanced_filters[__index__][condition]" class="form-control">
                        <option value="equals" selected>{{ __('Equals') }}</option>
                        <option value="contains">{{ __('Contains') }}</option>
                    </select>
                </div>
                <div class="col-sm-12 col-lg-3">
                    <label class="form-label">{{ __('Value') }}</label>
                    <input type="text" name="advanced_filters[__index__][value]" class="form-control" />
                </div>
                <div class="col-sm-12 col-lg-2 boolean-operator-wrapper">
                    <label class="form-label">{{ __('Condition') }}</label>
                    <select name="advanced_filters[__index__][boolean]" class="form-control">
                        <option value="and" selected>{{ __('AND') }}</option>
                        <option value="or">{{ __('OR') }}</option>
                    </select>
                </div>
                <div class="col-sm-12 col-lg-2 d-flex align-items-center mt-3 mt-lg-4">
                    <button type="button" class="btn btn-danger w-100" onclick="removeAdvancedFilterRow(this)">
                        <i class="fa fa-trash"></i> {{ __('Remove') }}
                    </button>
                </div>
            </div>
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
