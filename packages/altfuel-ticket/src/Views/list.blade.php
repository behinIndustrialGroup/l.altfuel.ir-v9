@extends('layouts.app')


@php
    $title = '';
@endphp

@section('content')
    <div class="card">
        <div class="card-header">
            <form action="javascript:void(0)" id="cat-form">
                <div class="form-group">
                    <label for="">دسته بندی</label>

                    @if (auth()->user()->access('Ticket-Actors'))
                        @include('ATView::partial-view.category-for-actor')
                        <button class="btn btn-primary mt-2" onclick="filterAll()">فیلتر تمام تیکت ها</button>
                        <button class="btn btn-info mt-2" onclick="filter()">فیلتر تیکت های جدید و درحال بررسی</button>
                        <button class="btn btn-secondary mt-2" onclick="oldTicket()">فیلتر تیکت های پاسخ داده شده و بسته
                            شده</button>
                        <br>
                        <br>
                        @if (auth()->user()->access('جستجو پیشرفته'))
                            @include('ATView::partial-view.filter-form')
                        @endif
                        <div id="conversion-settings" class="border rounded p-3 mt-3" style="display: none">
                            <h6 class="mb-2">تنظیم نوع تبدیل</h6>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="conversion-enabled-toggle">
                                <label class="form-check-label" for="conversion-enabled-toggle">فعال برای این دسته بندی</label>
                            </div>
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" id="conversion-required-toggle" disabled>
                                <label class="form-check-label" for="conversion-required-toggle">الزامی بودن هنگام ثبت تیکت</label>
                            </div>
                            <button type="button" class="btn btn-primary btn-sm mt-3" onclick="saveConversionSettings()">ذخیره</button>
                        </div>
                    @else
                        @include('ATView::partial-view.catagory')
                        <button class="btn btn-info" onclick="filter()">فیلتر</button>
                    @endif
                </div>
            </form>
        </div>
        <div class="table-responsive mt-2">
            <table class="table table-stripped" id="tickets-table">
                <thead>
                    <tr>
                        <th>شناسه</th>
                        <th>عنوان</th>
                        <th>ثبت کننده</th>
                        <th>دسته بندی</th>
                        <th>وضعیت</th>
                        <th>آخرین تغییرات</th>
                        {{-- <th>امتیاز</th> --}}
                    </tr>
                </thead>
                <tbody>
                    @foreach ($myTickets as $ticket)
                        <tr>
                            <td>{{ $ticket->id }}</td>
                            <td>{{ $ticket->title }}</td>
                            <td>{{ $ticket->user }}</td>
                            <td>{{ $ticket->catagory }}</td>
                            <td>{{ $ticket->status }}</td>
                            <td dir="ltr">{{ verta($ticket->updated_at)->format('Y-m-d H:i') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

    </div>
@endsection

@section('script')
    <script>
        var table = $('#tickets-table').DataTable({
            "order": [
                [5, "desc"]
            ],
            "language": {
                "url": "{{ url('public/js/fa.json') }}"
            },
            columns: [{
                    data: 'id'
                },
                {
                    data: 'title'
                },
                {
                    data: 'user'
                },
                {
                    data: 'catagory'
                },
                {
                    data: 'status'
                },
                {
                    data: 'updated_at'
                }
            ]
        })

        const conversionSettingsContainer = $('#conversion-settings');
        const conversionEnabledToggle = $('#conversion-enabled-toggle');
        const conversionRequiredToggle = $('#conversion-required-toggle');
        let currentConversionCategoryId = null;

        function updateConversionSettingsUi(meta, catId) {
            if (!conversionSettingsContainer.length) {
                return;
            }

            currentConversionCategoryId = catId && catId !== 'notSelected' ? catId : null;

            if (!currentConversionCategoryId) {
                conversionSettingsContainer.hide();
                conversionEnabledToggle.prop('checked', false);
                conversionRequiredToggle.prop('checked', false).prop('disabled', true);
                return;
            }

            const enabled = meta && Boolean(meta.enabled);
            const required = meta && Boolean(meta.required);

            conversionSettingsContainer.show();
            conversionEnabledToggle.prop('checked', enabled);
            conversionRequiredToggle.prop('disabled', !enabled);
            conversionRequiredToggle.prop('checked', enabled && required);
        }

        $(document).on('ticket-category-changed', function(event, meta, catId) {
            updateConversionSettingsUi(meta, catId);
        });

        conversionEnabledToggle.on('change', function() {
            const isEnabled = $(this).is(':checked');
            conversionRequiredToggle.prop('disabled', !isEnabled);
            if (!isEnabled) {
                conversionRequiredToggle.prop('checked', false);
            }
        });

        window.saveConversionSettings = function() {
            if (!conversionSettingsContainer.length) {
                return;
            }

            if (!currentConversionCategoryId) {
                show_error('لطفا ابتدا دسته بندی مورد نظر را انتخاب کنید');
                return;
            }

            const payload = {
                catagory_id: currentConversionCategoryId,
                conversion_type_enabled: conversionEnabledToggle.is(':checked') ? 1 : 0,
                conversion_type_required: conversionRequiredToggle.is(':checked') ? 1 : 0,
            };

            send_ajax_request(
                "{{ route('ATRoutes.catagory.updateConversionSettings') }}",
                $.param(payload),
                function(res) {
                    show_message(res.message);
                    const selectedOption = $('#child_cat_for_user option:selected').get(0) || $('#child_cat option:selected').get(0);
                    if (selectedOption) {
                        selectedOption.dataset.conversionEnabled = res.conversion_type_enabled ? 1 : 0;
                        selectedOption.dataset.conversionRequired = res.conversion_type_required ? 1 : 0;
                        $(document).trigger('ticket-category-changed', [{
                            enabled: !!res.conversion_type_enabled,
                            required: !!res.conversion_type_required,
                        }, selectedOption.value]);
                    }
                },
                function(error) {
                    show_error(error);
                }
            )
        }

        function filter() {
            data = $('#cat-form').serialize();
            send_ajax_request(
                "{{ route('ATRoutes.get.getByCatagory') }}",
                data,
                function(data) {
                    console.log(data);
                    update_datatable(data);
                }
            )
        }

        table.on('click', 'tr', function() {
            data = table.row(this).data();
            show_comment_modal(data.id, data.title, data.user);
        })

        function filterAll() {
            data = $('#cat-form').serialize();
            send_ajax_request(
                "{{ route('ATRoutes.get.getAllByCatagory') }}",
                data,
                function(data) {
                    console.log(data);
                    update_datatable(data);
                }
            )
        }



        function oldTicket() {
            data = $('#cat-form').serialize();
            send_ajax_request(
                "{{ route('ATRoutes.get.oldGetByCatagory') }}",
                data,
                function(data) {
                    console.log(data);
                    update_datatable(data);
                }
            )
        }

        function filterWithAgent() {
            let data = $('#cat-form').serialize(); // اگر فیلترها داخل همون فرم هستن

            send_ajax_request(
                "{{ route('ATRoutes.filterByAgent') }}", // این رو باید توی Route تعریف کنی
                data,
                function(data) {
                    update_datatable(data);
                }
            )
        }


        function show_comment_modal(ticket_id, title, user) {
            var fd = new FormData();
            fd.append('ticket_id', ticket_id);
            send_ajax_formdata_request(
                "{{ route('ATRoutes.show.ticket') }}",
                fd,
                function(body) {
                    open_admin_modal_with_data(body, title, function() {
                        $(".direct-chat-messages").animate({
                            scrollTop: $('.direct-chat-messages').prop("scrollHeight")
                        }, 1);
                    });
                },
                function(data) {
                    show_error(data);
                }
            )
        }
    </script>
@endsection
