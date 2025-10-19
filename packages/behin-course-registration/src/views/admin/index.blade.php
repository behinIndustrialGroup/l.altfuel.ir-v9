@extends('layouts.app')

@section('title', 'لیست ثبت نام دوره‌ها')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="box">
                <div class="box-header d-flex justify-content-between align-items-center flex-wrap">
                    <h3 class="box-title mb-0">لیست ثبت‌نامی‌ها</h3>
                    <a
                        href="{{ route('admin.course-registrations.export', array_merge(request()->except(['page']), ['sort' => $currentSort, 'direction' => $currentDirection])) }}"
                        class="btn btn-success mt-2 mt-md-0">
                        <i class="fa fa-file-excel-o"></i>
                        خروجی اکسل
                    </a>
                </div>
                <div class="box-body table-responsive p-0">
                    <table class="table table-striped table-hover mb-0">
                        <thead>
                            <tr>
                                @foreach ($sortableColumns as $column => $label)
                                    @php
                                        $isActive = $currentSort === $column;
                                        $nextDirection = $isActive && $currentDirection === 'asc' ? 'desc' : 'asc';
                                        $icon = 'fa-sort';
                                        if ($isActive) {
                                            $icon = $currentDirection === 'asc' ? 'fa-sort-asc' : 'fa-sort-desc';
                                        }
                                        $query = array_merge(request()->except(['page']), ['sort' => $column, 'direction' => $nextDirection]);
                                    @endphp
                                    <th>
                                        <a href="{{ route('admin.course-registrations.index', $query) }}"
                                            class="d-flex justify-content-between align-items-center text-dark">
                                            <span>{{ $label }}</span>
                                            <i class="fa {{ $icon }}"></i>
                                        </a>
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($registrations as $registration)
                                <tr>
                                    <td>{{ $registration->id }}</td>
                                    <td>{{ $registration->name }}</td>
                                    <td>{{ $registration->national_id }}</td>
                                    <td>{{ $registration->birth_certificate_number }}</td>
                                    <td>{{ optional($registration->birth_date)->format('Y-m-d') }}</td>
                                    <td>{{ $registration->mobile }}</td>
                                    <td>{{ $registration->phone }}</td>
                                    <td>{{ $registration->course_title }}</td>
                                    <td>{{ number_format($registration->price) }}</td>
                                    <td>{{ $registration->ref_id ?? '-' }}</td>
                                    <td>
                                        <span class="badge {{ $registration->status === 'success' ? 'badge-success' : ($registration->status === 'failed' ? 'badge-danger' : 'badge-secondary') }}">
                                            {{ $registration->status }}
                                        </span>
                                    </td>
                                    <td>{{ optional($registration->created_at)->format('Y-m-d H:i') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ count($sortableColumns) }}" class="text-center text-muted py-4">
                                        داده‌ای برای نمایش وجود ندارد.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="box-footer d-flex justify-content-center">
                    {{ $registrations->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection
