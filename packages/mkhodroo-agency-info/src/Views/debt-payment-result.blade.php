@extends('layouts.app')

@section('title', 'نتیجه پرداخت بدهی')

@section('style')
    <style>
        .payment-result-container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 60vh;
            padding: 2rem 1rem;
        }

        .payment-result-card {
            max-width: 600px;
            width: 100%;
            border-radius: 20px;
            border: none;
            box-shadow: 0 15px 40px rgba(31, 38, 135, 0.15);
            overflow: hidden;
        }

        .payment-result-header {
            padding: 2.5rem 2rem;
            text-align: center;
            color: #fff;
        }

        .payment-result-header.success {
            background: linear-gradient(135deg, #4caf50, #66bb6a);
        }

        .payment-result-header.warning {
            background: linear-gradient(135deg, #ff9800, #ffa726);
        }

        .payment-result-header.error {
            background: linear-gradient(135deg, #f44336, #ef5350);
        }

        .payment-result-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
        }

        .payment-result-title {
            font-size: 1.75rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .payment-result-body {
            padding: 2rem;
        }

        .payment-info-item {
            display: flex;
            justify-content: space-between;
            padding: 1rem 0;
            border-bottom: 1px solid #e0e0e0;
        }

        .payment-info-item:last-child {
            border-bottom: none;
        }

        .payment-info-label {
            font-weight: 600;
            color: #37474f;
        }

        .payment-info-value {
            color: #546e7a;
        }

        .payment-actions {
            padding: 1.5rem 2rem;
            background-color: #f5f5f5;
            text-align: center;
        }
    </style>
@endsection

@section('content')
    <div class="payment-result-container">
        <div class="payment-result-card">
            <div class="payment-result-header {{ $success ? ($warning ?? false ? 'warning' : 'success') : 'error' }}">
                <div class="payment-result-icon">
                    @if($success)
                        @if($warning ?? false)
                            <i class="fa fa-exclamation-triangle"></i>
                        @else
                            <i class="fa fa-check-circle"></i>
                        @endif
                    @else
                        <i class="fa fa-times-circle"></i>
                    @endif
                </div>
                <h1 class="payment-result-title">
                    @if($success)
                        @if($warning ?? false)
                            پرداخت موفق با هشدار
                        @else
                            پرداخت موفق
                        @endif
                    @else
                        پرداخت ناموفق
                    @endif
                </h1>
                <p class="mb-0">{{ $message }}</p>
            </div>

            @if($success && isset($ref_id))
                <div class="payment-result-body">
                    <div class="payment-info-item">
                        <span class="payment-info-label">کد رهگیری:</span>
                        <span class="payment-info-value" dir="ltr">{{ $ref_id }}</span>
                    </div>
                    @if(isset($debt_name))
                        <div class="payment-info-item">
                            <span class="payment-info-label">عنوان بدهی:</span>
                            <span class="payment-info-value">{{ $debt_name }}</span>
                        </div>
                    @endif
                    @if(isset($amount))
                        <div class="payment-info-item">
                            <span class="payment-info-label">مبلغ پرداختی:</span>
                            <span class="payment-info-value">{{ number_format($amount) }} ریال</span>
                        </div>
                    @endif
                    <div class="payment-info-item">
                        <span class="payment-info-label">تاریخ پرداخت:</span>
                        <span class="payment-info-value">{{ now()->format('Y-m-d H:i:s') }}</span>
                    </div>
                </div>
            @endif

            <div class="payment-actions">
                <a href="{{ route('agencyInfo.userCenters') }}" class="btn btn-primary">
                    <i class="fa fa-home ms-1"></i>
                    بازگشت به لیست مراکز
                </a>
            </div>
        </div>
    </div>
@endsection
