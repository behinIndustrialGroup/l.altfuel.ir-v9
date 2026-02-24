@extends('layouts.app')

@php
    $title = 'ایجاد تیکت پشتیبانی';
@endphp

@section('content')
    <div class="card card-info">
        <div class="card-header">
            ایجاد تیکت پشتیبانی
        </div>
        <div class="card-body">
            <div class="alert alert-danger" role="alert">
                <strong>توجه:</strong> لازم است در ابتدای تیکت شماره وین یا شاسی خودرو مربوطه را وارد کنید
            </div>
            <form action="javascript:void(0)" id="ticket-form" enctype="multipart/form-data">
                @csrf
                <div class="form-group">
                    <label for="">دسته بندی</label>
                    @include('ATView::partial-view.catagory')
                </div>
                <div class="form-group">
                    <label for="">عنوان</label>
                    <input type="text" name="title" id="" class="form-control">
                </div>
                <div class="form-group" id="conversion-type-wrapper" style="display: none">
                    <label for="conversion_type">نوع تبدیل</label>
                    <select name="conversion_type" id="conversion_type" class="form-control">
                        <option value="">لطفا نوع تبدیل را انتخاب کنید</option>
                        @foreach (config('ATConfig.conversion_types', []) as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <small class="text-danger d-none" id="conversion-required-hint">تکمیل این فیلد الزامی است.</small>
                </div>
                <p>
                    لطفاً در صورتی که تیکت شما مربوط به مشکلات خودرو است، شماره VIN خودرو را در متن پیام خود درج فرمایید.
                </p>
                @include('ATView::partial-view.create-comment-form', ['form_id' => 'ticket-form'])
            </form>
        </div>
    </div>
@endsection

@section('script')
    <script>
        $(document).on('ticket-category-changed', function(event, meta, catId) {
            const wrapper = $('#conversion-type-wrapper');
            const select = $('#conversion_type');
            const hint = $('#conversion-required-hint');

            if (!wrapper.length) {
                return;
            }

            const enabled = meta && Boolean(meta.enabled);
            const required = meta && Boolean(meta.required);

            if (!enabled || !catId || catId === 'notSelected') {
                select.val('');
                select.prop('required', false);
                hint.addClass('d-none');
                wrapper.hide();
                return;
            }

            wrapper.show();
            select.prop('required', required);
            if (required) {
                hint.removeClass('d-none');
            } else {
                hint.addClass('d-none');
            }
        });
    </script>
@endsection
