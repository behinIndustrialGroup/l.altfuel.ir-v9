@extends('layouts.app')

@php
    use Illuminate\Support\Str;
@endphp

@section('content')
    <div class="container">
        <h3>لیست تیکت‌ها</h3>

        <div class="mb-3">
            <a href="{{ route('telegram-tickets.index', ['status' => 'open']) }}"
                class="btn btn-sm {{ ($status ?? 'open') === 'open' ? 'btn-primary' : 'btn-outline-primary' }}">تیکت‌های باز</a>
            <a href="{{ route('telegram-tickets.index', ['status' => 'answered']) }}"
                class="btn btn-sm {{ ($status ?? 'open') === 'answered' ? 'btn-primary' : 'btn-outline-primary' }}">تیکت‌های پاسخ داده‌شده</a>
            <a href="{{ route('telegram-tickets.index', ['status' => 'closed']) }}"
                class="btn btn-sm {{ ($status ?? 'open') === 'closed' ? 'btn-primary' : 'btn-outline-primary' }}">تیکت‌های بسته شده</a>
            <a href="{{ route('telegram-tickets.index', ['status' => 'all']) }}"
                class="btn btn-sm {{ ($status ?? 'open') === 'all' ? 'btn-secondary' : 'btn-outline-secondary' }}">همه تیکت‌ها</a>
        </div>

        @if ($tickets->isEmpty())
            <p>هیچ تیکتی برای نمایش وجود ندارد.</p>
        @else
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>نام کاربر</th>
                        <th>آخرین پیام</th>
                        <th>وضعیت</th>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($tickets as $ticket)
                        <tr>
                            <td>{{ $ticket->id }}</td>
                            <td>{{ $ticket->user_id }}</td>
                            @php
                                $latestMessage = $ticket->latestMessage;
                                $latestPreview = optional($latestMessage)->message;
                                if (!$latestPreview && $latestMessage && $latestMessage->attachment_name) {
                                    $latestPreview = '📎 ' . $latestMessage->attachment_name;
                                }
                            @endphp
                            <td>{{ Str::limit($latestPreview, 50) }}</td>
                            <td>
                                @switch($ticket->status)
                                    @case('open')
                                        باز
                                    @break

                                    @case('answered')
                                        پاسخ داده‌شده
                                    @break

                                    @case('closed')
                                        بسته شده
                                    @break

                                    @default
                                        -
                                @endswitch
                            </td>
                            <td>
                                <a href="{{ route('telegram-tickets.show', $ticket->id) }}" class="btn btn-info btn-sm">مشاهده</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
@endsection
