@extends('layouts.app')

@section('title', 'بدهی‌های مرکز')

@section('style')
    <style>
        .ud-container {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .ud-card {
            border-radius: 18px;
            border: none;
            box-shadow: 0 10px 30px rgba(31, 38, 135, 0.1);
            background: #ffffff;
            overflow: hidden;
        }

        .ud-card__header {
            background: linear-gradient(135deg, #1976d2, #42a5f5);
            color: #fff;
            padding: 1.5rem 2rem;
        }

        .ud-card__title {
            font-size: 1.5rem;
            font-weight: 600;
            margin: 0;
        }

        .ud-card__subtitle {
            margin-top: .35rem;
            opacity: .9;
        }

        .ud-badge {
            display: inline-flex;
            align-items: center;
            padding: .25rem .6rem;
            border-radius: 999px;
            font-size: .75rem;
            background: rgba(255, 255, 255, 0.15);
        }

        .ud-table-wrapper {
            padding: 1.5rem 2rem 2rem;
        }

        .ud-table-wrapper table {
            margin-bottom: 0;
        }

        .ud-table-wrapper thead {
            background-color: #f1f5f9;
        }

        .ud-table-wrapper thead th {
            border-bottom: none;
            font-weight: 600;
            color: #37474f;
            vertical-align: middle;
        }

        .ud-status-badge {
            border-radius: 999px;
            padding: .2rem .6rem;
            font-size: .75rem;
        }
    </style>
@endsection

@section('content')
    <div class="ud-container">
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fa fa-exclamation-circle ms-1"></i>
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fa fa-check-circle ms-1"></i>
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="ud-card">
            <div class="ud-card__header d-flex justify-content-between align-items-start flex-column flex-lg-row">
                <div>
                    <h1 class="ud-card__title mb-2">بدهی‌های مرکز</h1>
                    <p class="ud-card__subtitle mb-1">
                        <strong>نام مرکز:</strong> {{ $centerName ?? '-' }}
                        @if($centerCode)
                            | <strong>کد مرکز:</strong> <span dir="ltr">{{ $centerCode }}</span>
                        @endif
                    </p>
                    <p class="ud-card__subtitle mb-0">
                        <strong>کاربر:</strong> {{ $user->name ?? '-' }}
                    </p>
                </div>
                <div class="ud-badge mt-3 mt-lg-0">
                    <i class="fa fa-file-invoice-dollar ms-1"></i>
                    <span>تعداد بدهی‌ها: {{ is_countable($debts) ? count($debts) : 0 }}</span>
                </div>
            </div>
            <div class="ud-table-wrapper table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="width: 60px;">#</th>
                            <th>عنوان بدهی</th>
                            <th>مبلغ</th>
                            <th>تاریخ پرداخت</th>
                            <th>کد رهگیری</th>
                            <th>وضعیت</th>
                            <th style="width: 150px;">اقدام</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($debts as $index => $debt)
                            @php
                                $isPaid = $debt['is_paid'] ?? false;
                            @endphp
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $debt['rhs_name'] ?? '-' }}</td>
                                <td>
                                    @if(isset($debt['rhs_amountowed']))
                                        {{ number_format($debt['rhs_amountowed']) }} ریال
                                    @else
                                        -
                                    @endif
                                </td>
                                <td dir="ltr">
                                    {{ $debt['rhs_debtpaymentdate'] ?? '-' }}
                                </td>
                                <td dir="ltr">
                                    {{ $debt['rhs_paymentid'] ?? '-' }}
                                </td>
                                <td>
                                    @if($isPaid)
                                        <span class="badge bg-success ud-status-badge">پرداخت شده</span>
                                    @else
                                        <span class="badge bg-warning text-dark ud-status-badge">پرداخت نشده</span>
                                    @endif
                                </td>
                                <td>
                                    @if(! $isPaid)
                                        <form action="{{ route('agencyInfo.userCenters.payDebt', ['debtId' => $debt['rhs_debtinformationid']]) }}" method="POST" style="display: inline;">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-success">
                                                <i class="fa fa-credit-card ms-1"></i>
                                                پرداخت بدهی
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-muted small">پرداخت شده</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted">
                                    هیچ بدهی‌ای برای این مرکز در CRM یافت نشد.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

