@extends('layouts.welcome')

@section('content')
    <div class="complaint-wrapper">
        <div class="complaint-card">
            <div class="complaint-card__header">
                <h2>فرم ثبت شکایت</h2>
                <p>لطفاً اطلاعات خود را با دقت تکمیل کنید تا همکاران ما در اسرع وقت پیگیری کنند.</p>
            </div>
            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            <form action="{{ route('complaint.store') }}" method="POST" enctype="multipart/form-data" class="complaint-form">
                @csrf
                <div class="form-grid">
                    <div class="form-field">
                        <label for="first_name_last_name">نام و نام خانوادگی</label>
                        <input id="first_name_last_name" type="text" name="first_name_last_name" class="input-control"
                            value="{{ old('first_name_last_name') }}" required>
                    </div>
                    <div class="form-field">
                        <label for="national_code">کد ملی</label>
                        <input id="national_code" type="text" name="national_code" class="input-control"
                            value="{{ old('national_code') }}" required>
                    </div>
                    <div class="form-field">
                        <label for="mobile">موبایل</label>
                        <input id="mobile" type="text" name="mobile" class="input-control"
                            value="{{ old('mobile') }}" required>
                    </div>
                    <div class="form-field">
                        <div class="form-field__label">
                            <label for="vin">شماره VIN خودرو</label>
                            <button class="hint-toggle" type="button" aria-label="نمایش راهنمای شماره VIN خودرو"
                                aria-expanded="false" aria-controls="hint-vin" data-target="hint-vin">
                                <span class="sr-only">نمایش یا پنهان کردن راهنمای شماره VIN خودرو</span>
                                ?
                            </button>
                        </div>
                        <span class="field-hint" id="hint-vin">شماره وین در برگ سبز خودرو و در کارت خودرو ثبت است. در کارت
                            خودروهای قدیمی پشت کارت به صورت عمودی و در کارت خودروهای جدید جلوی کارت ثبت و جلوی آن نوشته شده
                            vin</span>
                        <input id="vin" type="text" name="vin" class="input-control"
                            value="{{ old('vin') }}" required>
                    </div>
                    <div class="form-field">
                        <label for="business_name">نام واحد صنفی</label>
                        <input id="business_name" type="text" name="business_name" class="input-control"
                            value="{{ old('business_name') }}" required>
                    </div>
                    <div class="form-field">
                        <label for="manager_name">نام مدیر واحد صنفی</label>
                        <input id="manager_name" type="text" name="manager_name" class="input-control"
                            value="{{ old('manager_name') }}" required>
                    </div>
                    <div class="form-field">
                        <label for="state">استان</label>
                        <input id="state" type="text" name="state" class="input-control"
                            value="{{ old('state') }}" required>
                    </div>
                    <div class="form-field">
                        <label for="city">شهر</label>
                        <input id="city" type="text" name="city" class="input-control"
                            value="{{ old('city') }}" required>
                    </div>
                    <div class="form-field form-field--full">
                        <label for="address">آدرس</label>
                        <textarea id="address" name="address" class="input-control input-control--textarea" rows="3" required>{{ old('address') }}</textarea>
                    </div>
                    <div class="form-field">
                        <label for="center_type">نوع مرکز</label>
                        <select name="center_type" id="center_type" class="input-control">
                            <option value="مرکز خدمات فنی" {{ old('center_type') == 'مرکز خدمات فنی' ? 'selected' : '' }}>
                                خدمات فنی (پرفشار)</option>
                            <option value="آزمایشگاه هیدرواستاتیک"
                                {{ old('center_type') == 'آزمایشگاه هیدرواستاتیک' ? 'selected' : '' }}>آزمایشگاه
                                هیدرواستاتیک</option>
                            <option value="مرکز کم فشار" {{ old('center_type') == 'مرکز کم فشار' ? 'selected' : '' }}>کم
                                فشار</option>
                            <option value="نمیدانم" {{ old('center_type') == 'نمیدانم' ? 'selected' : '' }}>نمیدانم
                            </option>
                        </select>
                    </div>
                    <div class="form-field">
                        <label for="complaint_subject">موضوع شکایت</label>
                        <select name="complaint_subject" id="complaint_subject" class="input-control">
                            <option value="ارجاع از معاینه فنی"
                                {{ old('complaint_subject') == 'ارجاع از معاینه فنی' ? 'selected' : '' }}>ارجاع از معاینه
                                فنی</option>
                            <option value="تبدیل یا تعویض مخزن دولتی"
                                {{ old('complaint_subject') == 'تبدیل یا تعویض مخزن دولتی' ? 'selected' : '' }}>تبدیل یا
                                تعویض مخزن دولتی</option>
                            <option value="تبدیل یا درخواست گواهی سلامت آزاد"
                                {{ old('complaint_subject') == 'تبدیل یا درخواست گواهی سلامت آزاد' ? 'selected' : '' }}>
                                تبدیل یا درخواست گواهی سلامت آزاد</option>
                            <option value="تعمیر سیستم گازسوز"
                                {{ old('complaint_subject') == 'تعمیر سیستم گازسوز' ? 'selected' : '' }}>تعمیر سیستم گازسوز
                            </option>
                        </select>
                    </div>
                    <div class="form-field">
                        <label for="visit_date">تاریخ مراجعه</label>
                        <input id="visit_date" type="text" name="visit_date" id="visit_date" class="input-control"
                            value="{{ old('visit_date') }}" required>
                        <input type="hidden" name="visit_date_alt" id="visit_date_alt">
                    </div>
                    <div class="form-field form-field--full">
                        <div class="form-field__label">
                            <label for="description">توضیحات</label>
                            <button class="hint-toggle" type="button" aria-label="نمایش راهنمای توضیحات"
                                aria-expanded="false" aria-controls="hint-description" data-target="hint-description">
                                <span class="sr-only">نمایش یا پنهان کردن راهنمای توضیحات</span>
                                ?
                            </button>
                        </div>
                        <span class="field-hint" id="hint-description">لطفا توضیح کامل درج بفرمایید فرمهای ناقص رسیدگی
                            نخواهند شد</span>
                        <textarea id="description" name="description" class="input-control input-control--textarea" rows="4">{{ old('description') }}</textarea>
                    </div>
                    <div class="form-field form-field--full">
                        <div class="form-field__label">
                            <label for="file">پیوست</label>
                            <button class="hint-toggle" type="button" aria-label="نمایش راهنمای پیوست"
                                aria-expanded="false" aria-controls="hint-file" data-target="hint-file">
                                <span class="sr-only">نمایش یا پنهان کردن راهنمای پیوست</span>
                                ?
                            </button>
                        </div>
                        <span class="field-hint" id="hint-file">فاکتور یا مدارک مرتبط را ارسال کنید.</span>
                        <label class="file-upload">
                            <input id="file" type="file" name="file">
                            <span class="file-upload__label">انتخاب فایل</span>
                        </label>
                    </div>
                </div>
                <button type="submit" class="submit-btn">ثبت شکایت</button>
            </form>
        </div>
    </div>
@endsection
@section('script')
    <script>
        initial_view()
        $('#visit_date').persianDatepicker({
            autoClose: true,
            altField: '#visit_date_alt',
            initialValue: false,
            viewMode: 'day',
            format: 'YYYY-MM-DD',
            initialValueType: 'persian',
            calendar: {
                persian: {
                    leapYearMode: 'astronomical',
                    locale: 'fa'
                }
            }
        });
        document.querySelectorAll('.hint-toggle').forEach((button) => {
            const targetId = button.getAttribute('data-target');
            const hint = document.getElementById(targetId);

            if (!hint) {
                return;
            }

            hint.classList.add('field-hint--hidden');

            button.addEventListener('click', () => {
                const isVisible = hint.classList.toggle('field-hint--visible');
                button.setAttribute('aria-expanded', isVisible);
            });
        });
    </script>
@endsection
@section('style')
    <style>
        .complaint-wrapper {
            width: min(880px, 100%);
            margin: 0 auto;
            padding: 2.5rem 1.5rem;
        }

        .complaint-card {
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(14px);
            border-radius: 24px;
            padding: clamp(2rem, 5vw, 3rem);
            box-shadow: 0 25px 60px rgba(15, 37, 64, 0.18);
            border: 1px solid rgba(255, 255, 255, 0.6);
        }

        .complaint-card__header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .complaint-card__header h2 {
            font-size: clamp(1.4rem, 2.2vw, 1.9rem);
            font-weight: 700;
            color: #0d2136;
            margin-bottom: 0.75rem;
        }

        .complaint-card__header p {
            color: #5f6c7b;
            font-size: 0.95rem;
            line-height: 1.8;
        }

        .complaint-form {
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }

        .form-grid {
            display: grid;
            gap: 1.5rem;
            grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
        }

        .form-field {
            display: flex;
            flex-direction: column;
            gap: 0.6rem;
        }

        .form-field--full {
            grid-column: 1 / -1;
        }

        .form-field__label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #14283c;
            font-size: 0.95rem;
            font-weight: 600;
        }

        .form-field label {
            font-weight: 600;
            color: #14283c;
            font-size: 0.95rem;
        }

        .hint-toggle {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 1.75rem;
            height: 1.75rem;
            border-radius: 50%;
            border: 1px solid #1d72f2;
            background-color: #f0f6ff;
            color: #1d72f2;
            font-size: 1rem;
            line-height: 1;
            cursor: pointer;
            transition: background-color 0.2s ease, color 0.2s ease, border-color 0.2s ease;
        }

        .hint-toggle:hover,
        .hint-toggle:focus {
            background-color: #1d72f2;
            color: #ffffff;
            border-color: #1357b8;
            outline: none;
        }

        .sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            border: 0;
        }

        .field-hint {
            display: block;
            font-size: 0.85rem;
            color: #1d72f2;
            background-color: #eaf3ff;
            padding: 0.6rem 0.9rem;
            border-radius: 12px;
            line-height: 1.7;
        }

        .field-hint--hidden {
            display: none;
        }

        .field-hint--visible {
            display: block;
        }

        .input-control {
            border-radius: 14px;
            border: 1px solid rgba(13, 33, 54, 0.1);
            padding: 0.75rem 1rem;
            background: rgba(255, 255, 255, 0.9);
            box-shadow: inset 0 1px 3px rgba(13, 33, 54, 0.05);
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .input-control:focus {
            outline: none;
            border-color: #1d72f2;
            box-shadow: 0 0 0 4px rgba(29, 114, 242, 0.15);
        }

        .input-control--textarea {
            resize: vertical;
            min-height: 120px;
        }

        .file-upload {
            position: relative;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.7rem;
            padding: 0.75rem 1.5rem;
            border: 1px dashed rgba(29, 114, 242, 0.5);
            border-radius: 14px;
            color: #1d72f2;
            font-weight: 600;
            cursor: pointer;
            background: rgba(29, 114, 242, 0.08);
            transition: border-color 0.2s ease, background 0.2s ease;
        }

        .file-upload:hover {
            border-color: #1d72f2;
            background: rgba(29, 114, 242, 0.12);
        }

        .file-upload input[type="file"] {
            position: absolute;
            inset: 0;
            opacity: 0;
            cursor: pointer;
        }

        .submit-btn {
            align-self: flex-end;
            padding: 0.9rem 2.6rem;
            border: none;
            border-radius: 999px;
            background: linear-gradient(135deg, #1d72f2, #16c7c0);
            color: #fff;
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .submit-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 15px 35px rgba(29, 114, 242, 0.25);
        }

        .submit-btn:focus {
            outline: none;
            box-shadow: 0 0 0 4px rgba(29, 114, 242, 0.25);
        }

        @media (max-width: 576px) {
            .complaint-wrapper {
                padding: 1.5rem 1rem;
            }

            .complaint-card {
                padding: 1.8rem 1.4rem;
            }
        }
    </style>
@endsection
