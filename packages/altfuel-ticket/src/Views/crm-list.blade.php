@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4>تیکت‌های CRM</h4>
            <a href="{{ route('ATRoutes.crm.create.index') }}" class="btn btn-success">
                <i class="fa fa-plus"></i> ایجاد تیکت جدید
            </a>
        </div>
        <div class="card-body">
            <!-- فرم جستجو بر اساس دسته‌بندی -->
            <form method="GET" action="{{ route('ATRoutes.crm.list') }}" class="mb-4">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="category">دسته‌بندی</label>
                            <select name="category" id="category" class="form-control">
                                <option value="">همه دسته‌بندی‌ها</option>
                                @if(isset($categories))
                                    @foreach($categories as $category)
                                        <option value="{{ $category['value'] }}" 
                                            {{ (request('category') == $category['value']) ? 'selected' : '' }}>
                                            {{ $category['label'] }}
                                        </option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary">فیلتر</button>
                        <a href="{{ route('ATRoutes.crm.list') }}" class="btn btn-secondary mr-2">پاک کردن فیلتر</a>
                    </div>
                </div>
            </form>

            @if($contactId)
                <div class="alert alert-info mb-3">
                    در حال نمایش تیکت‌های شما
                </div>
            @else
                <div class="alert alert-warning mb-3">
                    شناسه مخاطب CRM برای حساب کاربری شما ثبت نشده است.
                </div>
            @endif

            @if(count($tickets) > 0)
                <!-- جدول تیکت‌ها -->
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>شناسه</th>
                                <th>عنوان</th>
                                <th>دسته‌بندی</th>
                                <th>وضعیت</th>
                                <th>تاریخ ایجاد</th>
                                <th>آخرین بروزرسانی</th>
                                <th>عملیات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($tickets as $ticket)
                                <tr>
                                    <td>{{ $ticket['new_ticket_id'] ?? $ticket['new_ticketid'] }}</td>
                                    <td>{{ $ticket['new_title'] ?? 'بدون عنوان' }}</td>
                                    <td>
                                        <span class="badge badge-secondary">
                                            {{ $ticket['new_category'] ?? 'بدون دسته‌بندی' }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-info">
                                            {{ $ticket['new_status'] ?? 'نامشخص' }}
                                        </span>
                                    </td>
                                    <td>{{ $ticket['new_created_at'] ? verta($ticket['new_created_at'])->format('Y/m/d H:i') : '-' }}</td>
                                    <td>{{ $ticket['new_updated_at'] ? verta($ticket['new_updated_at'])->format('Y/m/d H:i') : '-' }}</td>
                                    <td>
                                        <button class="btn btn-sm btn-info" onclick="showTicket('{{ $ticket['new_ticketid'] }}')">
                                            مشاهده
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="alert alert-warning">
                    هیچ تیکتی یافت نشد.
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Modal برای نمایش جزئیات تیکت -->
<div class="modal fade" id="ticket-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-body" id="ticket-content">
                <!-- محتوای تیکت اینجا لود می‌شود -->
            </div>
        </div>
    </div>
</div>

<script>
    function showTicket(ticketId) {
        const fd = new FormData();
        fd.append('ticket_id', ticketId);
        
        send_ajax_formdata_request(
            "{{ route('ATRoutes.crm.show') }}",
            fd,
            function(response) {
                $('#ticket-content').html(response);
                $('#ticket-modal').modal('show');
            }
        );
    }
</script>
@endsection
