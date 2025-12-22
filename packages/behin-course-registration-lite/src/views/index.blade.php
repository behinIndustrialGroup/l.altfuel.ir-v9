@extends('layouts.welcome')

@section('title')
    ثبت‌نام کارگاه آموزشی
@endsection

@section('style')
    <style>
        body {
            background: linear-gradient(135deg, #1f3b73, #12806c) !important;
        }
    </style>
@endsection

@section('content')
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="container d-flex justify-content-center align-items-center min-vh-100">
        <div class="card shadow-sm p-4" style="max-width: 420px; width: 100%;">
            <h2 class="text-center mb-4">فرم ثبت‌نام کارگاه</h2>
            <form action="{{ route('workshop-registration.submit') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label for="name" class="form-label">نام و نام خانوادگی:</label>
                    <input type="text" class="form-control" name="name" id="name" value="{{ old('name') }}" required>
                </div>

                <div class="mb-3">
                    <label for="national_id" class="form-label">کد ملی:</label>
                    <input type="text" class="form-control" name="national_id" id="national_id" value="{{ old('national_id') }}" required>
                </div>

                <div class="mb-3">
                    <label for="birth_certificate_number" class="form-label">شماره شناسنامه:</label>
                    <input type="text" class="form-control" name="birth_certificate_number" id="birth_certificate_number" value="{{ old('birth_certificate_number') }}" required>
                </div>

                <div class="mb-3">
                    <label for="birth_date" class="form-label">تاریخ تولد:</label>
                    <input type="text" data-jdp class="form-control" name="birth_date" id="birth_date" value="{{ old('birth_date') }}" required>
                </div>

                <div class="mb-3">
                    <label for="mobile" class="form-label">شماره موبایل:</label>
                    <input type="text" class="form-control" name="mobile" id="mobile" value="{{ old('mobile') }}" required>
                </div>

                <div class="mb-3">
                    <label for="phone" class="form-label">تلفن:</label>
                    <input type="text" class="form-control" name="phone" id="phone" value="{{ old('phone') }}" required>
                </div>

                <div class="mb-4">
                    <label for="course" class="form-label">انتخاب دوره:</label>
                    <select name="course" id="course" class="form-select select2" required>
                        <option value="" disabled {{ old('course') ? '' : 'selected' }}>لطفاً یک دوره را انتخاب کنید</option>
                        @foreach ($courses as $key => $course)
                            <option value="{{ $key }}" {{ old('course') === $key ? 'selected' : '' }}>
                                {{ $course['title'] }} - {{ number_format($course['price']) }} ریال
                            </option>
                        @endforeach
                    </select>
                </div>

                <button type="submit" class="btn btn-primary w-100">ادامه و پرداخت</button>
            </form>
        </div>
    </div>
@endsection

@section('script')
    <script>
        initial_view()
        jalaliDatepicker.startWatch();
    </script>
@endsection
