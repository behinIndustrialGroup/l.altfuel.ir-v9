<?php

namespace CourseRegistration\Controllers;

use App\Http\Controllers\Controller;
use CourseRegistration\Models\CourseRegistration;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CourseRegistrationAdminController extends Controller
{
    /**
     * Columns that can be used for sorting the list of registrations.
     */
    private array $sortableColumns = [
        'id' => 'شناسه',
        'name' => 'نام و نام خانوادگی',
        'national_id' => 'کد ملی',
        'birth_certificate_number' => 'شماره شناسنامه',
        'birth_date' => 'تاریخ تولد',
        'mobile' => 'موبایل',
        'phone' => 'تلفن ثابت',
        'course_title' => 'عنوان دوره',
        'ref_id' => 'کد پیگیری پرداخت',
        'price' => 'مبلغ (تومان)',
        'status' => 'وضعیت',
        'created_at' => 'تاریخ ثبت',
    ];

    public function index(Request $request)
    {
        [$column, $direction] = $this->resolveSorting($request);
        $registrations = CourseRegistration::whereIn('status', ['pending', 'failed'])
        ->whereNotNull('ref_id')->get();
        foreach($registrations as $registration){
            $registration->status = 'success';
            $registration->save();
        }

        /** @var LengthAwarePaginator $registrations */
        $registrations = CourseRegistration::orderBy($column, $direction)
            ->paginate(25)
            ->appends($request->except('page'));

        return view('CourseRegistrationViews::admin.index', [
            'registrations' => $registrations,
            'currentSort' => $column,
            'currentDirection' => $direction,
            'sortableColumns' => $this->sortableColumns,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        [$column, $direction] = $this->resolveSorting($request);

        $registrations = CourseRegistration::orderBy($column, $direction)->get();

        $filename = 'course_registrations_' . now()->format('Y_m_d_H_i_s') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $columns = array_keys($this->sortableColumns);

        return response()->streamDownload(function () use ($registrations, $columns) {
            $handle = fopen('php://output', 'w');

            // Add UTF-8 BOM to make sure Excel opens the file correctly.
            fwrite($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            $headers = [];
            foreach ($columns as $column) {
                $headers[] = $this->sortableColumns[$column];
            }

            fputcsv($handle, $headers);

            foreach ($registrations as $registration) {
                $row = [];

                foreach ($columns as $column) {
                    $row[] = $this->formatColumn($registration, $column);
                }

                fputcsv($handle, $row);
            }

            fclose($handle);
        }, $filename, $headers);
    }

    private function resolveSorting(Request $request): array
    {
        $column = $request->get('sort', 'created_at');
        $direction = strtolower($request->get('direction', 'desc')) === 'asc' ? 'asc' : 'desc';

        if (! array_key_exists($column, $this->sortableColumns)) {
            $column = 'created_at';
        }

        return [$column, $direction];
    }

    private function formatColumn(CourseRegistration $registration, string $column): string
    {
        $value = $registration->getAttribute($column);

        return match ($column) {
            'price' => number_format((int) $value),
            'created_at' => optional($registration->created_at)->format('Y-m-d H:i') ?? '',
            'birth_date' => $value instanceof Carbon
                ? $value->format('Y-m-d')
                : ($value ? Carbon::parse($value)->format('Y-m-d') : ''),
            default => (string) ($value ?? ''),
        };
    }
}
