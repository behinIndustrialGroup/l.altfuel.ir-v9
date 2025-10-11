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
                <p>
                    لطفاً در صورتی که تیکت شما مربوط به مشکلات خودرو است، شماره VIN خودرو را در متن پیام خود درج فرمایید.
                </p>
                @include('ATView::partial-view.create-comment-form', ['form_id' => 'ticket-form'])
            </form>
        </div>
    </div>
@endsection

@section('script')
@endsection
