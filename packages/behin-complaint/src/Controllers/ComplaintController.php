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

use function Symfony\Component\String\s;

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

        if ($request->file('file')) {
            $filePath = FileController::store($request->file('file'), 'complaint');
            if ($filePath['status'] == 200) {
                $data['file'] = $filePath['dir'];
                $attachment = public_path($filePath['dir']);
            } else {
                return redirect()->back()->withErrors(['file' => trans($filePath['message'])]);
            }
        }



        Complaint::create([
            'content' => json_encode($data)
        ]);
        $rhs_thesubjectcomplaint = match ($data['complaint_subject']) {
            'ارجاع از معاینه فنی' => 130770000,
            'تبدیل یا تعویض مخزن دولتی' => 130770001,
            'تبدیل یا درخواست گواهی سلامت آزاد' => 130770002,
            'تعمیر سیستم گازسوز' => 130770003,
        };

        $rhs_centertype = match ($data['center_type']) {
            'مرکز خدمات فنی' => 130770000,
            'آزمایشگاه هیدرواستاتیک' => 130770001,
            'مرکز کم فشار' => 130770002,
            'نمیدانم' => 130770003,
        };

        $visit_date = $data['visit_date_alt'];
        $visit_date = Carbon::parse((int)$visit_date / 1000);

        $mobile = $this->convertPersianToEnglish($data['mobile']);

        $response = $crmClient->request("contacts", "GET", [
            '$select' => 'contactid,fullname,mobilephone',
            '$filter' => "mobilephone eq '$mobile'"
        ]);

        if ($response->successful()) {
            $body = $response->json();
            if (!empty($body['value'])) {
                // مخاطب موجود است
                $contactId = $body['value'][0]['contactid'];
            } else {
                // مخاطب وجود ندارد → ایجاد جدید
                $response = $crmClient->save('contacts', [
                    "rhs_nationalcode" => $data['national_code'],
                    "createdon" => now(),
                    "telephone1" => $mobile,
                    "mobilephone" => $mobile,
                    "firstname" => $data['first_name_last_name'],
                    "rhs_address" => $data['address'],
                ]);

                $entityIdHeader = $response->header('OData-EntityId');

                if ($entityIdHeader) {
                    // استخراج GUID از داخل پرانتز
                    preg_match('/\(([^)]+)\)/', $entityIdHeader, $matches);
                    $contactId = $matches[1] ?? null;
                }
            }
        } else {
            // echo "CRM query failed: " . $response->body();
        }

        if ($contactId) {
            $response = $crmClient->save('annotations', [
                "subject" => "شکایت ثبت کردن در تاریخ " . verta()->today(),
                "notetext" => "شماره وین: ". $data['vin'],
                "objectid_contact@odata.bind" => "/contacts($contactId)",
            ]);
        }

        $response = $crmClient->save('rhs_complaintsprocesses', [
            "statecode" => 0,
            "statuscode" => 1,
            "rhs_nationalcode" => $data['national_code'],
            "createdon" => now(),
            "rhs_mobile" => $mobile,
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
            "objectid_contact@odata.bind" => "/contacts($contactId)",
        ]);

        $entityIdHeader = $response->header('OData-EntityId');
        preg_match('/\(([^)]+)\)/', $entityIdHeader, $matches);
        $complaintId = $matches[1] ?? null;

        if ($attachment && file_exists($attachment)) {
            $fileContent = base64_encode(file_get_contents($attachment));
            $response = $crmClient->save('annotations', [
                "subject" => "پیوست شکایت",
                "notetext" => "فایل پیوست شکایت ثبت شده است.",
                "filename" => basename($attachment),
                "mimetype" => mime_content_type($attachment),
                "documentbody" => $fileContent,
                "objectid_rhs_complaintsprocess@odata.bind" => "/rhs_complaintsprocesses($complaintId)",
            ]);
        }


        if ($response->failed()) {
            // return response()->json([
            //     'message' => 'CRM request failed.',
            //     'status' => $response->status(),
            //     'errors' => $response->json(),
            // ], $response->status() ?: 500);
        }

        Mail::to('info@altfuel.ir')->send(new ComplaintSubmitted($data, $attachment));

        return redirect()->route('complaint.create')->with('success', 'شکایت با موفقیت ثبت شد');
    }
    public function convertPersianToEnglish($string) {
        static $map = [
            '۰' => '0',
            '۱' => '1',
            '۲' => '2',
            '۳' => '3',
            '۴' => '4',
            '۵' => '5',
            '۶' => '6',
            '۷' => '7',
            '۸' => '8',
            '۹' => '9',
        ];

        return strtr($string, $map);
    }

    public function list()
    {
        $complaints = Complaint::get();
        return view('ComplaintViews::list')->with([
            'complaints' => $complaints
        ]);
    }
}
