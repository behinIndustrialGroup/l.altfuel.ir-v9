@extends('layouts.welcome')

@section('title')
    نتیجه پرداخت کارگاه آموزشی
@endsection

@section('style')
    <style>
        body {
            background: #f1f5f9 !important;
        }
    </style>
@endsection

@section('content')
    @if ($refId === 0 || $refId === 1)
        <div class="container pt-5 text-center">
            <div class="alert alert-danger">
                <h4 class="alert-heading">پرداخت ناموفق بود</h4>
                <p>متاسفانه پرداخت شما انجام نشد. لطفاً دوباره تلاش کنید.</p>
                <hr>
                <p class="mb-0">در صورت بروز مشکل، با پشتیبانی تماس بگیرید.</p>
            </div>
            <a href="{{ route('workshop-registration.form') }}" class="btn btn-primary mt-3">بازگشت به فرم ثبت‌نام</a>
        </div>
    @else
        <div class="container pt-5 text-center">
            <div class="alert alert-success">
                <h4 class="alert-heading">پرداخت با موفقیت انجام شد!</h4>
                <p>کد رهگیری شما:</p>
                <h5><strong>{{ $refId }}</strong></h5>
                <hr>
                <p class="mb-0">از ثبت‌نام شما سپاسگزاریم.</p>
            </div>
        </div>
    @endif
@endsection
