<?php

namespace Mkhodroo\AltfuelTicket\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Mkhodroo\AltfuelTicket\Models\CatagoryActor;
use Mkhodroo\AltfuelTicket\Models\Ticket;
use Mkhodroo\AltfuelTicket\Models\TicketComment;

class GetTicketController extends Controller
{

    function getAll()
    {
        return ['data' => []];
        $result = Ticket::get()->each(function ($row) {
            $row->catagory = $row->catagory();
            $row->user = $row->user()?->display_name;
            $row->actor = $row->actor()?->display_name;
            // $row->user_level = $row->user()->level();
        });
        return $result;
    }

    function getMyTickets()
    {
        $data = Ticket::where('user_id', Auth::id())->get()->each(function ($row) {
            $row->catagory = $row->catagory()['name'];
            $row->user = $row->user()?->display_name;
            $row->actor = $row->actor()?->display_name;
            $row->conversion_type_label = $row->conversion_type_label;
            // $row->user_level = $row->user()->level();
        });
        return $data;
    }

    function getMyTicketsByCatagory($catagory_id)
    {
        if (is_array($catagory_id)) {
            return Ticket::where('user_id', Auth::id())->WhereIn('cat_id', $catagory_id)->get()->each(function ($row) {
                $row->catagory = $row->catagory();
                $row->user = $row->user()?->display_name;
                $row->actor = $row->actor()?->display_name;
                $row->conversion_type_label = $row->conversion_type_label;
                // $row->user_level = $row->user()->level();
            });
        }
        $category = CatagoryController::get($catagory_id)->name;
        return Ticket::where('user_id', Auth::id())->where('cat_id', $catagory_id)->get()->each(function ($row) use ($category) {
            $row->catagory = $category;
            $row->user = $row->user()?->display_name;
            $row->actor = $row->actor()?->display_name;
            $row->conversion_type_label = $row->conversion_type_label;
            // $row->user_level = $row->user()->level();
        });
    }

    function getByCatagory(Request $r)
    {
        if (auth()->user()->access("Ticket-Actors")) {
            // $actors = CatagoryActor::where('user_id', Auth::id())->pluck('cat_id');
            $category = CatagoryController::get($r->catagory)->name;

            return Ticket::where('cat_id', $r->catagory)
                ->whereIn('status', [config('ATConfig.status.new'), config('ATConfig.status.in_progress')])
                ->select('id', 'title', 'status', 'user_id', 'updated_at')
                ->get()
                ->map(function ($row) use ($category) {
                    return [
                        'id' => $row->id,
                        'title' => $row->title,
                        'user' => $row->user()?->display_name,
                        'catagory' => $category,
                        'status' => $row->status,
                        'conversion_type_label' => $row->conversion_type_label,
                        'updated_at' => verta($row->updated_at)->format('Y-m-d H:i'),
                    ];
                });
        }
        return $this->getMyTicketsByCatagory($r->catagory);
    }

    function oldGetByCatagory(Request $r)
    {
        if (auth()->user()->access("Ticket-Actors")) {
            // $actors = CatagoryActor::where('user_id', Auth::id())->pluck('cat_id');
            $category = CatagoryController::get($r->catagory)->name;
            return Ticket::where('cat_id', $r->catagory)
                ->whereIn('status', [config('ATConfig.status.answered'), config('ATConfig.status.closed')])
                ->select('id', 'title', 'status', 'user_id', 'updated_at')
                ->get()
                ->map(function ($row) use ($category) {
                    return [
                        'id' => $row->id,
                        'title' => $row->title,
                        'user' => $row->user()?->display_name,
                        'catagory' => $category,
                        'status' => $row->status,
                        'conversion_type_label' => $row->conversion_type_label,
                        'updated_at' => verta($row->updated_at)->format('Y-m-d H:i'),
                    ];
                });
        }
        return $this->getMyTicketsByCatagory($r->catagory);
    }

    function getAllByCatagory(Request $r)
    {
        if (auth()->user()->access("Ticket-Actors")) {
            // $actors = CatagoryActor::where('user_id', Auth::id())->pluck('cat_id');
            $category = CatagoryController::get($r->catagory)->name;
            return Ticket::where('cat_id', $r->catagory)
                ->select('id', 'title', 'status', 'user_id', 'updated_at')
                ->get()
                ->map(function ($row) use ($category) {
                    return [
                        'id' => $row->id,
                        'title' => $row->title,
                        'user' => $row->user()?->display_name,
                        'catagory' => $category,
                        'status' => $row->status,
                        'conversion_type_label' => $row->conversion_type_label,
                        'updated_at' => verta($row->updated_at)->format('Y-m-d H:i'),
                    ];
                });
        }
        return $this->getMyTicketsByCatagory($r->catagory);
    }

    public static function get($id)
    {
        return Ticket::find($id);
        // $ticket = Cache::remember('ticket_' . $id, 60, function () use ($id) {

        // });
    }

    public static function findByTicketId($ticket_id)
    {
        return Ticket::where('ticket_id', $ticket_id)->first();
    }
}
