@extends('layouts.app')

@section('title')
    کاربران
@endsection

@section('content')
    <div class="row">
        <div class="box">
            <div class="box-header">
                <a href="{{ route('register') }}">
                    <button>
                        ایجاد کاربر
                    </button>
                </a>
            </div>

            <div class="box-body">
                <table class="table" id="table">
                    <thead>
                        <tr>
                            <th>شناسه</th>
                            <th>نام</th>
                            <th>نام کاربری</th>
                            <th>ویرایش</th>

                        </tr>
                    </thead>
                    @foreach ($users as $user)
                        <tr>
                            <td>{{ $user->id }}</td>
                            <td>{{ $user->display_name }}</td>
                            <td>{{ $user->name }}</td>
                            <td><a href="{{ $user->id }}"><i class="fa fa-edit"></i></a></td>
                        </tr>
                    @endforeach
                </table>

            </div>
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-3">
                <div class="text-muted small">
                    نمایش {{ $rows->firstItem() ?? 0 }} تا {{ $rows->lastItem() ?? 0 }} از
                    {{ number_format($rows->total()) }} رکورد
                </div>
                <div>
                    {{ $rows->onEachSide(1)->links('pagination::bootstrap-4') }}
                </div>
            </div>
        </div>
    </div>
@endsection
