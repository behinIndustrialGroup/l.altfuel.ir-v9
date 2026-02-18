@extends('layouts.app')

@section('style')
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
@endsection

@section('content')
    @if (auth()->user()->access('آیکون مشاهده مراکز من'))
        <div class="col-sm-3 ">
            <!-- small box -->
            <div class="small-box bg-primary">
                <div class="inner">
                    <h3>{{ trans('مراکز من') }}</h3>

                    <p>{{ trans('مشاهده اطلاعات مراکز مرتبط با شما و بدهی های مرکز') }}</p>
                </div>
                <div class="icon">
                    <i class="ion ion-bag"></i>
                </div>
                <a href="{{ route('agencyInfo.userCenters') }}" class="small-box-footer">{{ trans('مشاهده') }}
                    <i class="fa fa-arrow-circle-left"></i></a>
            </div>
        </div>
    @endif
@endsection
