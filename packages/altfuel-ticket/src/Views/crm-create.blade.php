@extends('layouts.app')

@php
    $title = 'ایجاد تیکت پشتیبانی CRM';
@endphp

@section('content')
    <div class="card card-info">
        <div class="card-header">
            ایجاد تیکت پشتیبانی CRM
        </div>
        <div class="card-body">
            <form action="javascript:void(0)" id="crm-ticket-form" enctype="multipart/form-data">
                @csrf
                <div class="form-group">
                    <label for="">دسته بندی</label>
                    @include('ATView::partial-view.crm-category-selector')
                </div>
                <div class="form-group">
                    <label for="ticket_title">عنوان</label>
                    <input type="text" name="title" id="ticket_title" class="form-control" required>
                </div>
                <p>
                    لطفاً در صورتی که تیکت شما مربوط به مشکلات خودرو است، شماره VIN خودرو را در متن پیام خود درج فرمایید.
                </p>
                @include('ATView::partial-view.crm-create-comment-form', ['form_id' => 'crm-ticket-form'])
            </form>
        </div>
    </div>
@endsection

@section('script')
    <script>
        // اضافه کردن validation برای فرم
        $(document).ready(function() {
            $('#crm-ticket-form').on('submit', function(e) {
                e.preventDefault();
                
                // بررسی انتخاب دسته‌بندی
                const categoryId = $('#child_category').val();
                if (!categoryId) {
                    alert('لطفاً دسته‌بندی را انتخاب کنید');
                    return false;
                }
                
                // بررسی عنوان
                const title = $('#ticket_title').val().trim();
                if (!title) {
                    alert('لطفاً عنوان تیکت را وارد کنید');
                    return false;
                }
                
                // بررسی متن پیام
                const text = $('textarea[name="text"]').val().trim();
                if (!text) {
                    alert('لطفاً متن پیام را وارد کنید');
                    return false;
                }
                
                return true;
            });
        });
    </script>
@endsection