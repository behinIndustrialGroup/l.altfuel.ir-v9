<?php

namespace Mkhodroo\AltfuelTicket\Controllers;

use App\Http\Controllers\Controller;
use Hekmatinasser\Verta\Verta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use IntlDateFormatter;
use Mkhodroo\AltfuelTicket\Models\Ticket;
use Mkhodroo\AltfuelTicket\Models\TicketCatagory;
use Mkhodroo\AltfuelTicket\Models\TicketComment;
use Morilog\Jalali\Jalalian;
use Carbon\Carbon;


class TicketFilterController extends Controller
{
    public function filterByAgent(Request $request)
    {
        $query = Ticket::query();

        if ($request->filled('ticket_number')) {
            $query->where('id', $request->ticket_number);
        }

        if ($request->filled('date_from')) {
            $from = Carbon::createFromTimestamp($request->date_from_alt / 1000)->startOfDay();
            $query->where('created_at', '>=', $from);
        }

        if ($request->filled('date_to')) {
            $to = Carbon::createFromTimestamp($request->date_to_alt / 1000)->endOfDay();
            $query->where('created_at', '<=', $to);
        }

        if ($request->filled('agent_id')) {
            $agentId = $request->agent_id;

            if ($agentId === 'none') {
                $agentIds = collect(config('ATConfig.filter_agents', []))
                    ->pluck('id')
                    ->filter()
                    ->map(fn($id) => (int) $id)
                    ->unique()
                    ->values();

                if ($agentIds->isNotEmpty()) {
                    $ticketIds = TicketComment::whereIn('user_id', $agentIds)->pluck('ticket_id')->unique();
                    if ($ticketIds->isNotEmpty()) {
                        $query->whereNotIn('id', $ticketIds);
                    }
                }
            } else {
                $ticketIds = TicketComment::where('user_id', $agentId)->pluck('ticket_id')->unique();
                $query->whereIn('id', $ticketIds);
            }
        }

        if ($request->filled('conversion_type')) {
            $query->where('conversion_type', $request->conversion_type);
        }

        if ($request->filled('status')) {
            $statusKey = $request->status;
            $statusValue = config("ATConfig.status.$statusKey");

            if (!empty($statusValue)) {
                $query->where('status', $statusValue);
            }
        }

        if ($request->filled('filter_catagory')) {
            $query->where('cat_id', $request->filter_catagory);
        } elseif ($request->filled('filter_parent_cat')) {
            $categoryIds = $this->getDescendantCategoryIds($request->filter_parent_cat);
            $query->whereIn('cat_id', $categoryIds);
        }

        $result = $query->get()->map(function ($row) {
            return [
                'id' => $row->id,
                'title' => $row->title,
                'user' => $row->user()?->display_name,
                'catagory' => $row->catagory()['name'] ?? '-',
                'status' => $row->status,
                'conversion_type_label' => $row->conversion_type_label,
                'updated_at' => verta($row->updated_at)->format('Y-m-d H:i'),
            ];
        });

        return response()->json($result);
    }

    function jalaliToGregorian($jalaliDate)
    {
        [$jy, $jm, $jd] = explode('/', $this->convertPersianNumbers($jalaliDate));
        $jy = (int)$jy;
        $jm = (int)$jm;
        $jd = (int)$jd;

        $gDate = Verta::jalaliToGregorian($jy, $jm, $jd);
        $gDate = $gDate[0] . '-' . $gDate[1] . '-' . $gDate[2];
        Log::info($gDate);

        return date('Y-m-d', $gDate);
    }

    function jalaliDayOfYear($month, $day, $isLeap)
    {
        $daysInMonth = [0, 31, 62, 93, 124, 155, 186, 216, 246, 276, 306, 336];
        return $daysInMonth[$month - 1] + $day;
    }

    function isJalaliLeap($year)
    {
        $mod = $year % 33;
        return in_array($mod, [1, 5, 9, 13, 17, 22, 26, 30]);
    }

    function convertPersianNumbers($string)
    {
        $persian = ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'];
        $english = ['0','1','2','3','4','5','6','7','8','9'];
        return str_replace($persian, $english, $string);
    }

    private function getDescendantCategoryIds($categoryId)
    {
        if (is_null($categoryId)) {
            return [];
        }

        $categories = TicketCatagory::all(['id', 'parent_id'])->groupBy('parent_id');

        $pending = [(int) $categoryId];
        $visited = [];

        while (!empty($pending)) {
            $currentId = array_pop($pending);

            if (isset($visited[$currentId])) {
                continue;
            }

            $visited[$currentId] = true;

            foreach ($categories->get($currentId, collect()) as $category) {
                $pending[] = (int) $category->id;
            }
        }

        return array_keys($visited);
    }

}
