<?php

namespace Mkhodroo\AltfuelTicket\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Mkhodroo\AltfuelTicket\Models\TicketCatagory;
use Behin\CrmClient\CrmClient;

class TicketConversionController extends Controller
{
    public function update(Request $request, CrmClient $crmClient)
    {
        if (!auth()->user()->access('Ticket-Actors')) {
            return response(trans('auth.failed'), 403);
        }

        $ticket = GetTicketController::findByTicketId($request->ticket_id);
        if (!$ticket) {
            abort(404);
        }

        $category = $ticket->cat_id ? TicketCatagory::find($ticket->cat_id) : null;
        if (!$category || !$category->conversion_type_enabled) {
            return response()->json([
                'message' => trans('ATTrans.conversion-type-disabled'),
            ], 422);
        }

        $types = array_keys(config('ATConfig.conversion_types', []));
        $rules = [$category->conversion_type_required ? 'required' : 'nullable'];
        $rules[] = Rule::in($types);

        $validator = Validator::make($request->all(), [
            'conversion_type' => $rules,
        ], [
            'conversion_type.required' => trans('ATTrans.conversion-type-required'),
            'conversion_type.in' => trans('ATTrans.conversion-type-invalid'),
        ]);

        $validator->validate();

        $newValue = $request->input('conversion_type');
        $newValue = $newValue === '' ? null : $newValue;

        $oldValue = $ticket->conversion_type;
        $oldLabel = $ticket->conversion_type_label ?? trans('ATTrans.conversion-type-not-set');

        $ticket->conversion_type = $newValue;
        $ticket->save();
        $ticket->refresh();

        $newLabel = $ticket->conversion_type_label ?? trans('ATTrans.conversion-type-not-set');

        if ($oldValue !== $ticket->conversion_type) {
            AddTicketCommentController::add(
                $crmClient,
                $ticket->id,
                trans('ATTrans.conversion-type-changed', [
                    'old' => $oldLabel,
                    'new' => $newLabel,
                ])
            );
        }

        return response()->json([
            'message' => trans('ATTrans.conversion-type-update-success'),
            'label' => $newLabel,
        ]);
    }
}
