@extends('layouts.app')

@section('title')
    کاربران
@endsection

@section('content')
    <div class="container">

        <div class="card">
            <div
                class="card-header d-flex flex-column gap-3 gap-md-0 flex-md-row justify-content-between align-items-md-center">
                <div>
                    <a href="{{ route('register') }}">
                        <button class="btn btn-sm btn-primary">
                            ایجاد کاربر
                        </button>
                    </a>
                </div>
                <form class="d-flex gap-2" method="GET" action="{{ route('user.all', 'all') }}">
                    <input type="text" name="search" class="form-control" placeholder="جستجو بر اساس نام، نام کاربری یا نقش" value="{{ $search ?? '' }}">
                    <button class="btn btn-sm btn-secondary" type="submit">جستجو</button>
                </form>
            </div>

            <div class="card-body">
                <table class="table" id="table">
                    <thead>
                        <tr>
                            <th>شناسه</th>
                            <th>نام</th>
                            <th>نام کاربری</th>
                            <th>نقش</th>
                            <th>ویرایش</th>
                        </tr>
                    </thead>
                    @foreach ($users as $user)
                        <tr>
                            <td>{{ $user->id }}</td>
                            <td>{{ $user->display_name }}</td>
                            <td>{{ $user->email }}</td>
                            <td>{{ $user->role->name ?? '' }}</td>
                            <td><a href="{{ $user->id }}"><i class="fa fa-edit"></i></a></td>
                        </tr>
                    @endforeach
                </table>

            </div>
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-3">
                <div class="text-muted small">
                    نمایش {{ $users->firstItem() ?? 0 }} تا {{ $users->lastItem() ?? 0 }} از
                    {{ number_format($users->total()) }} رکورد
                </div>
                <div>
                    {{ $users->onEachSide(1)->links('pagination::bootstrap-4') }}
                </div>
            </div>
        </div>
    </div>
@endsection
