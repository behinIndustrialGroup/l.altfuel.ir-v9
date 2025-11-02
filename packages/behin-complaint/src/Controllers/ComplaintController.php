<?php

namespace Behin\Complaint\Controllers;

use App\Http\Controllers\Controller;
use Behin\Complaint\Mail\ComplaintSubmitted;
use Behin\Complaint\Models\Complaint;
use FileService\Controllers\FileServiceController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Mkhodroo\AgencyInfo\Controllers\FileController;
use Behin\CrmClient\CrmClient;
use Carbon\Carbon;

class ComplaintController extends Controller
{
    public function create()
    {
        return view('ComplaintViews::create');
    }

    public function store(Request $request, CrmClient $crmClient)
    {
        $validated = $request->validate([
            'first_name_last_name' => 'required|string|max:255',
            'national_code' => 'required|string|size:10',
            'mobile' => 'required|string|size:11',
            'vin' => 'nullable|string|max:50',
            'business_name' => 'required|string|max:255',
            'manager_name' => 'required|string|max:255',
            'state' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'address' => 'required|string',
            'center_type' => 'required|string|max:255',
            'complaint_subject' => 'required|string|max:255',
            'visit_date' => 'required',
            'description' => 'nullable|string',
        ]);
        $data = $request->except('_token');
        $attachment = null;

        if($request->file('file'))
        {
            $filePath = FileController::store($request->file('file'), 'complaint');
            if($filePath['status'] == 200)
            {
                $data['file'] = $filePath['dir'];
                $attachment = public_path($filePath['dir']);
            }
            else
            {
                return redirect()->back()->withErrors(['file' => trans($filePath['message'])]);
            }
        }



        Complaint::create([
            'content' => json_encode($data)
        ]);
        $rhs_thesubjectcomplaint = match($data['complaint_subject']) {
            'ارجاع از معاینه فنی' => 130770000,
            'تبدیل یا تعویض مخزن دولتی' => 130770001,
            'تبدیل یا درخواست گواهی سلامت آزاد' => 130770002,
            'تعمیر سیستم گازسوز' => 130770003,
        };

        $rhs_centertype = match($data['center_type']) {
            'خدمات فنی (پرفشار)' => 130770000,
            'آزمایشگاه هیدرواستاتیک' => 130770001,
            'کم فشار' => 130770002,
            'نمیدانم' => 130770003,
        };

        $visit_date = $data['visit_date_alt'];
        $visit_date = Carbon::parse((int)$visit_date / 1000);

        $response = $crmClient->save('rhs_complaintsprocesses', [
            "statecode" => 0,
            "statuscode" => 1,
            "rhs_nationalcode" => $data['national_code'],
            "createdon" => now(),
            "rhs_mobile" => $data['mobile'],
            "modifiedon" => now(),
            "rhs_dateofreference" => $visit_date,
            "rhs_thesubjectcomplaint" => $rhs_thesubjectcomplaint,
            "rhs_name" => $data['first_name_last_name'],
            "rhs_centertype" => $rhs_centertype,
            "rhs_address" => $data['address'],
            "rhs_thenameoftheunionmanager" => $data['manager_name'],
            "rhs_province" => $data['state'],
            "rhs_city" => $data['city'],
            "rhs_tradeunitname" => $data['business_name'],
            "rhs_description" => 'VIN: ' . $data['vin'] . ' توضیحات: ' . $data['description'],
        ]);

        if ($response->failed()) {
            return response()->json([
                'message' => 'CRM request failed.',
                'status' => $response->status(),
                'errors' => $response->json(),
            ], $response->status() ?: 500);
        }

        Mail::to('info@altfuel.ir')->send(new ComplaintSubmitted($data, $attachment));

        return redirect()->route('complaint.create')->with('success', 'شکایت با موفقیت ثبت شد');
    }

    public function list()
    {
        $complaints = Complaint::get();
        return view('ComplaintViews::list')->with([
            'complaints' => $complaints
        ]);
    }
}
