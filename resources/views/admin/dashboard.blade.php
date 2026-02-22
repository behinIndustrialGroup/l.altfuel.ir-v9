@extends('layouts.app')

@section('style')
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
@endsection

@section('content')
    <div class="card p-2">
        @if (auth()->user()->access('آیکون مشاهده مراکز من') || auth()->user()->id == 803)
            <div class="col-sm-3 ">
                <!-- small box -->
                <div class="small-box bg-primary">
                    <div class="inner">
                        <h3>{{ trans('مراکز من') }}</h3>

                        <p>{{ trans('مشاهده اطلاعات مراکز مرتبط با حساب کاربری شما و بدهی های مرکز') }}</p>
                    </div>
                    <div class="icon">
                        <i class="ion ion-bag"></i>
                    </div>
                    <a href="{{ route('agencyInfo.userCenters') }}" class="small-box-footer">{{ trans('مشاهده') }}
                        <i class="fa fa-arrow-circle-left"></i></a>
                </div>
            </div>
        @endif
    </div>
@endsection
