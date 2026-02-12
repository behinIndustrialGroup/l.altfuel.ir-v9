@extends('layouts.app')

@section('title', 'مراکز من')

@section('style')
    <style>
        .uc-container {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .uc-card {
            border-radius: 18px;
            border: none;
            box-shadow: 0 10px 30px rgba(31, 38, 135, 0.1);
            background: #ffffff;
            overflow: hidden;
        }

        .uc-card__header {
            background: linear-gradient(135deg, #1976d2, #42a5f5);
            color: #fff;
            padding: 1.5rem 2rem;
        }

        .uc-card__title {
            font-size: 1.5rem;
            font-weight: 600;
            margin: 0;
        }

        .uc-card__subtitle {
            margin-top: .35rem;
            opacity: .9;
        }

        .uc-badge {
            display: inline-flex;
            align-items: center;
            padding: .25rem .6rem;
            border-radius: 999px;
            font-size: .75rem;
            background: rgba(255, 255, 255, 0.15);
        }

        .uc-table-wrapper {
            padding: 1.5rem 2rem 2rem;
        }

        .uc-table-wrapper table {
            margin-bottom: 0;
        }

        .uc-table-wrapper thead {
            background-color: #f1f5f9;
        }

        .uc-table-wrapper thead th {
            border-bottom: none;
            font-weight: 600;
            color: #37474f;
            vertical-align: middle;
        }

        .uc-table-wrapper tbody tr:hover {
            box-shadow: inset 0 0 0 9999px rgba(33, 150, 243, 0.03);
        }

        .uc-centers-list {
            max-width: 500px;
            white-space: normal;
            word-break: break-word;
        }

        @media (max-width: 767.98px) {
            .uc-table-wrapper {
                padding: 1.25rem;
            }
        }
    </style>
@endsection

@section('content')
    <div class="uc-container">
        <div class="uc-card">
            <div class="uc-card__header d-flex justify-content-between align-items-start flex-column flex-lg-row">
                <div>
                    <h1 class="uc-card__title mb-2">مراکز متصل به شما</h1>
                    <p class="uc-card__subtitle">
                        در این صفحه بر اساس شماره تلفنی که در ستون
                        <strong>email</strong>
                        حساب کاربری شما ذخیره شده، مراکزی که در CRM ثبت شده‌اند نمایش داده می‌شوند.
                    </p>
                    <p class="uc-card__subtitle mb-0">
                        <strong>کاربر لاگین‌شده:</strong> {{ $user->name ?? '-' }} |
                        <strong>شماره پیش‌فرض کاربر (email):</strong>
                        <span dir="ltr">{{ $user->email ?? '-' }}</span>
                    </p>
                </div>
                <div class="uc-badge mt-3 mt-lg-0">
                    <i class="fa fa-building ms-1"></i>
                    <span>تعداد مراکز: {{ is_countable($centers) ? count($centers) : 0 }}</span>
                </div>
            </div>
            <div class="uc-table-wrapper table-responsive">
                <form class="mb-4" method="GET" action="{{ route('agencyInfo.userCenters') }}">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label">شماره تلفن (email) برای جستجو</label>
                            <input type="text"
                                   name="mobile"
                                   class="form-control"
                                   dir="ltr"
                                   value="{{ request('mobile', $user->email) }}"
                                   placeholder="مثال: 0912... یا ایمیل ذخیره شده">
                        </div>
                        <div class="col-md-2 mt-2 mt-md-0">
                            <button type="submit" class="btn btn-primary w-100">
                                جستجو
                            </button>
                        </div>
                        <div class="col-md-6 mt-2 mt-md-0">
                            <span class="text-muted small">
                                شماره‌ای که جستجو می‌شود:
                                <strong dir="ltr">{{ request('mobile', $user->email) }}</strong>
                            </span>
                        </div>
                    </div>
                </form>
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="width: 70px;">#</th>
                            <th>نام مرکز</th>
                            <th>کد مرکز</th>
                            <th>موبایل ثبت شده در CRM</th>
                            <th>تلفن</th>
                            <th style="width: 150px;">بدهی‌ها</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($centers as $index => $center)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $center['rhs_name'] ?? '-' }}</td>
                                <td dir="ltr">{{ $center['rhs_centercode'] ?? '-' }}</td>
                                <td dir="ltr">{{ $center['rhs_mobile'] ?? '-' }}</td>
                                <td dir="ltr">{{ $center['rhs_phone'] ?? '-' }}</td>
                                <td>
                                    @if(!empty($center['rhs_servicecenterid'] ?? null))
                                        <a href="{{ route('agencyInfo.userCenters.debts', [
                                                'serviceCenterId' => $center['rhs_servicecenterid'],
                                                'name' => $center['rhs_name'] ?? null,
                                                'code' => $center['rhs_centercode'] ?? null,
                                            ]) }}"
                                           class="btn btn-sm btn-outline-primary">
                                            مشاهده بدهی‌ها
                                        </a>
                                    @else
                                        <span class="text-muted small">شناسه CRM موجود نیست</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted">
                                    هیچ مرکزی برای شما پیدا نشد.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

