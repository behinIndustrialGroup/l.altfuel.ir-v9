<?php

namespace Mkhodroo\AgencyInfo\Controllers;

use App\Http\Controllers\Controller;
use Behin\CrmClient\CrmClient;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Mkhodroo\AgencyInfo\Models\AgencyInfo;

class SendFinancialToCrmController extends Controller
{
    protected $crmClient;

    public function __construct(CrmClient $crmClient)
    {
        $this->crmClient = $crmClient;
    }

    /**
     * ارسال اطلاعات مالی مراکز به CRM
     */
    public function sendFinancialDataToCrm()
    {
        set_time_limit(0);

        echo "<h2>شروع بررسی اطلاعات مالی مراکز</h2><hr>";

        $totalCenters = 0;
        $totalFinancialRecords = 0;
        $columns = [
            'crm_service_center_id',
            'membership_96',
            'membership_97',
            'membership_98',
            'irngv',
            'plate_reader',
        ];

        $selects = ['parent_id'];

        foreach ($columns as $col) {
            $selects[] = DB::raw("MAX(CASE WHEN `key` = '$col' THEN value END) AS `$col`");
        }

        $data = DB::table('agency_info')
            ->select($selects)
            ->havingNotNull('crm_service_center_id')
            ->groupBy('parent_id')
            ->chunk(20, function ($agencies) use (&$totalCenters) {

                foreach ($agencies as $agency) {
                    $totalCenters++;
                    echo "<p>🏢 مرکز #{$agency->membership_96}</p>";
                    flush();
                }
            });
  


        echo "<h2>پایان پردازش</h2>";
        echo "<p>مراکز: $totalCenters</p>";
    }

    // public function sendFinancialDataToCrm()
    // {
    //     @ini_set('output_buffering', 'off');
    //     @ini_set('zlib.output_compression', false);
    //     while (ob_get_level()) {
    //         ob_end_flush();
    //     }
    //     ob_implicit_flush(true);
    //     try {
    //         set_time_limit(0);

    //         $agencies = $this->getAgenciesWithCrmId();
    //         $totalCount = count($agencies);

    //         if ($totalCount == 0) {
    //             echo "<p style='color: red;'>هیچ مرکزی با CRM ID یافت نشد</p>";
    //             return;
    //         }

    //         echo "<h2>شروع ارسال اطلاعات مالی $totalCount مرکز به CRM</h2>";
    //         echo "<hr>";

    //         $successCount = 0;
    //         $errorCount = 0;
    //         $skippedCount = 0;
    //         $totalFinancialRecords = 0;
    //         foreach ($agencies as $agency) {

    //             echo "<h3>🏢 {$agency['name']}</h3>";
    //             flush();

    //             $financialData = $this->getFinancialDataForAgency($agency['parent_id']);

    //             if (empty($financialData)) {
    //                 echo "<p style='color: orange;'>⚠ هیچ اطلاعات مالی ندارد</p>";
    //             } else {
    //                 $count = count($financialData);
    //                 echo "<p style='color: blue;'>📊 تعداد رکوردهای مالی: <strong>$count</strong></p>";
    //                 $totalFinancialRecords += $count;
    //             }

    //             echo "<hr>";
    //             flush();

    //             usleep(300000); // مکث کوتاه برای UI
    //         }


    //         // foreach ($agencies as $agency) {
    //         //     echo "<h3>پردازش اطلاعات مالی: {$agency['name']}</h3>";
    //         //     echo "<ul>";

    //         //     // دریافت اطلاعات مالی این مرکز
    //         //     $financialData = $this->getFinancialDataForAgency($agency['parent_id']);

    //         //     if (empty($financialData)) {
    //         //         echo "<li style='color: orange;'>⚠ هیچ اطلاعات مالی یافت نشد</li>";
    //         //         $skippedCount++;
    //         //     } else {
    //         //         // پردازش اطلاعات مالی
    //         //         foreach ($financialData as $financial) {
    //         //             // چک کردن وجود رکورد قبلی
    //         //             $existingRecord = $this->checkExistingFinancialRecord($financial, $agency['crm_service_center_id']);
    //         //             $totalFinancialRecords++;

    //         //             if ($existingRecord) {
    //         //                 $skippedCount++;
    //         //                 echo "<li style='color: orange;'>⚠ {$financial['name']} - قبلاً وجود دارد (رد شد)</li>";
    //         //             } else {
    //         //                 $result = $this->createFinancialRecord($financial, $agency['crm_service_center_id']);

    //         //                 if ($result['success']) {
    //         //                     $successCount++;
    //         //                     echo "<li style='color: green;'>✓ {$financial['name']} - موفق</li>";
    //         //                 } else {
    //         //                     $errorCount++;
    //         //                     echo "<li style='color: red;'>✗ {$financial['name']} - خطا: {$result['message']}</li>";
    //         //                 }
    //         //             }
    //         //         }
    //         //     }

    //         //     echo "</ul>";
    //         //     echo "<hr>";

    //         //     // استراحت کوتاه
    //         //     usleep(500000); // 0.5 ثانیه
    //         // }

    //         echo "<h2>نتیجه نهایی</h2>";
    //         echo "<p><strong>مراکز پردازش شده: $totalCount</strong></p>";
    //         echo "<p><strong>رکوردهای مالی: $totalFinancialRecords</strong></p>";
    //         echo "<p><strong>موفق: $successCount - خطا: $errorCount - رد شده: $skippedCount</strong></p>";
    //     } catch (\Exception $e) {
    //         echo "<h2 style='color: red;'>خطا در پردازش:</h2>";
    //         echo "<p>" . $e->getMessage() . "</p>";
    //     }
    // }

    /**
     * تست ارسال اطلاعات مالی
     */
    public function testFinancialData()
    {
        try {
            echo "<h2>تست ارسال اطلاعات مالی</h2>";

            // تست اتصال به CRM
            echo "<h3>1. تست اتصال به CRM:</h3>";
            try {
                $testResponse = $this->crmClient->request("rhs_servicecenters", "GET", [
                    '$select' => 'rhs_servicecenterid,rhs_name',
                    '$top' => 1
                ]);

                if ($testResponse->successful()) {
                    echo "<p style='color: green;'>✓ اتصال به CRM موفق</p>";
                } else {
                    echo "<p style='color: red;'>✗ خطا در اتصال به CRM: " . $testResponse->status() . "</p>";
                    echo "<pre>" . $testResponse->body() . "</pre>";
                    return;
                }
            } catch (\Exception $e) {
                echo "<p style='color: red;'>✗ Exception در اتصال به CRM: " . $e->getMessage() . "</p>";
                return;
            }

            // تست دسترسی به جدول مالی
            echo "<h3>2. تست دسترسی به جدول rhs_financialinformationcenters:</h3>";
            try {
                $financialTestResponse = $this->crmClient->request("rhs_financialinformationcenters", "GET", [
                    '$select' => 'rhs_financialinformationcenterid,rhs_name',
                    '$top' => 1
                ]);

                if ($financialTestResponse->successful()) {
                    echo "<p style='color: green;'>✓ دسترسی به جدول مالی موفق</p>";
                    $financialData = $financialTestResponse->json();
                    echo "<p>تعداد رکوردهای موجود: " . count($financialData['value'] ?? []) . "</p>";
                } else {
                    echo "<p style='color: red;'>✗ خطا در دسترسی به جدول مالی: " . $financialTestResponse->status() . "</p>";
                    echo "<pre>" . $financialTestResponse->body() . "</pre>";

                    if ($financialTestResponse->status() == 404) {
                        echo "<p style='color: orange;'>⚠ احتمالاً جدول rhs_financialinformationcenters در CRM وجود ندارد یا نام آن متفاوت است</p>";
                    }
                    return;
                }
            } catch (\Exception $e) {
                echo "<p style='color: red;'>✗ Exception در دسترسی به جدول مالی: " . $e->getMessage() . "</p>";
                return;
            }

            // بررسی مراکز با CRM ID
            echo "<h3>3. بررسی مراکز با CRM ID:</h3>";
            $agencies = $this->getAgenciesWithCrmId();
            echo "<p>تعداد مراکز با CRM ID: " . count($agencies) . "</p>";

            if (empty($agencies)) {
                echo "<p style='color: red;'>هیچ مرکزی با CRM ID یافت نشد</p>";

                // بررسی اینکه آیا اصلاً رکوردی با key 'crm_service_center_id' وجود دارد
                $crmIdCount = DB::table('agency_info')
                    ->where('key', 'crm_service_center_id')
                    ->count();
                echo "<p>تعداد رکوردهای crm_service_center_id در دیتابیس: $crmIdCount</p>";

                if ($crmIdCount == 0) {
                    echo "<p style='color: orange;'>⚠ هیچ مرکزی در CRM ایجاد نشده است. ابتدا از روت /agency-info/send-to-crm استفاده کنید.</p>";
                }
                return;
            }

            $firstAgency = $agencies[0];

            echo "<h3>4. اطلاعات اولین مرکز:</h3>";
            echo "<pre>";
            print_r([
                'parent_id' => $firstAgency['parent_id'],
                'name' => $firstAgency['name'],
                'crm_service_center_id' => $firstAgency['crm_service_center_id']
            ]);
            echo "</pre>";

            // بررسی اطلاعات مالی
            echo "<h3>3. بررسی اطلاعات مالی:</h3>";
            $financialData = $this->getFinancialDataForAgency($firstAgency['parent_id']);
            echo "<p>تعداد اطلاعات مالی یافت شده: " . count($financialData) . "</p>";

            if (empty($financialData)) {
                echo "<p style='color: orange;'>این مرکز اطلاعات مالی ندارد</p>";

                // بررسی اینکه آیا اصلاً رکوردهای مالی وجود دارند
                $financialCount = DB::table('agency_info')
                    ->where('parent_id', $firstAgency['parent_id'])
                    ->whereIn('key', ['membership_96', 'irngv'])
                    ->count();
                echo "<p>تعداد رکوردهای مالی در دیتابیس برای این مرکز: $financialCount</p>";

                // نمایش تمام کلیدهای موجود برای این مرکز
                $allKeys = DB::table('agency_info')
                    ->where('parent_id', $firstAgency['parent_id'])
                    ->pluck('key')
                    ->toArray();
                echo "<p>کلیدهای موجود برای این مرکز:</p>";
                echo "<pre>" . implode(', ', $allKeys) . "</pre>";

                return;
            }

            echo "<h3>4. اطلاعات مالی یافت شده:</h3>";
            echo "<pre>";
            print_r($financialData);
            echo "</pre>";

            // تست ایجاد اولین رکورد مالی
            $firstFinancial = $financialData[0];
            echo "<h3>5. تست چک کردن رکورد تکراری: {$firstFinancial['name']}</h3>";

            $existingRecord = $this->checkExistingFinancialRecord($firstFinancial, $firstAgency['crm_service_center_id']);

            if ($existingRecord) {
                echo "<p style='color: orange;'>⚠ این رکورد مالی قبلاً در CRM وجود دارد</p>";
            } else {
                echo "<p style='color: green;'>✓ این رکورد مالی در CRM وجود ندارد، می‌توان ایجاد کرد</p>";

                echo "<h3>6. تست ایجاد رکورد مالی: {$firstFinancial['name']}</h3>";

                $result = $this->createFinancialRecord($firstFinancial, $firstAgency['crm_service_center_id']);

                if ($result['success']) {
                    echo "<p style='color: green;'>✓ رکورد مالی با موفقیت ایجاد شد</p>";
                } else {
                    echo "<p style='color: red;'>✗ خطا در ایجاد رکورد مالی: {$result['message']}</p>";
                }
            }
        } catch (\Exception $e) {
            echo "<h2 style='color: red;'>خطا در تست:</h2>";
            echo "<p>" . $e->getMessage() . "</p>";
            echo "<pre>" . $e->getTraceAsString() . "</pre>";
        }
    }

    /**
     * پاک کردن رکوردهای مالی تکراری از CRM
     */
    public function cleanDuplicateFinancialRecords()
    {
        try {
            set_time_limit(0);

            echo "<h2>شروع پاک کردن رکوردهای مالی تکراری</h2>";
            echo "<hr>";

            $agencies = $this->getAgenciesWithCrmId();
            $totalDeleted = 0;

            foreach ($agencies as $agency) {
                echo "<h3>بررسی رکوردهای تکراری: {$agency['name']}</h3>";
                echo "<ul>";

                // دریافت تمام رکوردهای مالی این مرکز از CRM
                $crmRecords = $this->getCrmFinancialRecords($agency['crm_service_center_id']);

                if (empty($crmRecords)) {
                    echo "<li style='color: orange;'>⚠ هیچ رکورد مالی در CRM یافت نشد</li>";
                    continue;
                }

                // گروه‌بندی رکوردها بر اساس نام و مبلغ
                $groupedRecords = [];
                foreach ($crmRecords as $record) {
                    $key = $record['rhs_name'] . '_' . $record['rhs_amount'];
                    if (!isset($groupedRecords[$key])) {
                        $groupedRecords[$key] = [];
                    }
                    $groupedRecords[$key][] = $record;
                }

                // حذف رکوردهای اضافی
                foreach ($groupedRecords as $key => $records) {
                    if (count($records) > 1) {
                        // نگه داشتن اولین رکورد و حذف بقیه
                        for ($i = 1; $i < count($records); $i++) {
                            $recordId = $records[$i]['rhs_financialinformationcenterid'];
                            $result = $this->deleteCrmFinancialRecord($recordId);

                            if ($result['success']) {
                                $totalDeleted++;
                                echo "<li style='color: green;'>✓ رکورد تکراری حذف شد: {$records[$i]['rhs_name']}</li>";
                            } else {
                                echo "<li style='color: red;'>✗ خطا در حذف: {$records[$i]['rhs_name']} - {$result['message']}</li>";
                            }
                        }
                    }
                }

                echo "</ul>";
                echo "<hr>";

                // استراحت کوتاه
                usleep(500000); // 0.5 ثانیه
            }

            echo "<h2>نتیجه نهایی</h2>";
            echo "<p><strong>تعداد رکوردهای حذف شده: $totalDeleted</strong></p>";
        } catch (\Exception $e) {
            echo "<h2 style='color: red;'>خطا در پاک کردن رکوردهای تکراری:</h2>";
            echo "<p>" . $e->getMessage() . "</p>";
        }
    }

    /**
     * دریافت رکوردهای مالی یک مرکز از CRM
     */
    private function getCrmFinancialRecords($serviceCenterId)
    {
        try {
            $response = $this->crmClient->request("rhs_financialinformationcenters", "GET", [
                '$filter' => "_rhs_servicecenter_value eq '$serviceCenterId'",
                '$select' => 'rhs_financialinformationcenterid,rhs_name,rhs_amount,rhs_paymentdate,rhs_trackingcode'
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['value'] ?? [];
            }

            return [];
        } catch (\Exception $e) {
            Log::error("Error getting CRM financial records", [
                'service_center_id' => $serviceCenterId,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * حذف رکورد مالی از CRM
     */
    private function deleteCrmFinancialRecord($recordId)
    {
        try {
            $response = $this->crmClient->request("rhs_financialinformationcenters($recordId)", "DELETE");

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => 'رکورد حذف شد'
                ];
            }

            return [
                'success' => false,
                'message' => 'خطا در حذف - Status: ' . $response->status()
            ];
        } catch (\Exception $e) {
            Log::error("Error deleting CRM financial record", [
                'record_id' => $recordId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Exception در حذف: ' . $e->getMessage()
            ];
        }
    }

    /**
     * چک کردن وجود رکورد مالی قبلی در CRM
     */
    private function checkExistingFinancialRecord($financial, $serviceCenterId)
    {
        try {
            $persianName = $this->convertToPersianName($financial['name']);

            // جستجو بر اساس نام، مبلغ و service center
            $filter = "rhs_name eq '$persianName' and rhs_amount eq " . floatval($financial['amount']) . " and _rhs_servicecenter_value eq '$serviceCenterId'";

            $response = $this->crmClient->request("rhs_financialinformationcenters", "GET", [
                '$filter' => $filter,
                '$select' => 'rhs_financialinformationcenterid,rhs_name,rhs_amount',
                '$top' => 1
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $records = $data['value'] ?? [];

                return count($records) > 0;
            }

            // اگر خطا در جستجو رخ داد، فرض می‌کنیم رکورد وجود ندارد
            return false;
        } catch (\Exception $e) {
            Log::error("Error checking existing financial record", [
                'financial' => $financial,
                'service_center_id' => $serviceCenterId,
                'error' => $e->getMessage()
            ]);

            // در صورت خطا، فرض می‌کنیم رکورد وجود ندارد تا از دست رفتن داده جلوگیری شود
            return false;
        }
    }

    /**
     * دریافت مراکزی که CRM ID دارند
     */
    private function getAgenciesWithCrmId()
    {
        try {
            $agencies = [];

            // دریافت مراکزی که CRM ID دارند
            $crmIds = DB::table('agency_info')
                ->where('key', 'crm_service_center_id')
                ->get();

            foreach ($crmIds as $crmRecord) {
                // دریافت نام مرکز
                $nameRecords = DB::table('agency_info')
                    ->where('parent_id', $crmRecord->parent_id)
                    ->whereIn('key', ['firstname', 'lastname'])
                    ->get();

                $firstname = '';
                $lastname = '';

                foreach ($nameRecords as $record) {
                    if ($record->key === 'firstname') {
                        $firstname = $record->value ?? '';
                    } elseif ($record->key === 'lastname') {
                        $lastname = $record->value ?? '';
                    }
                }

                $name = trim($firstname . ' ' . $lastname);

                $agencies[] = [
                    'parent_id' => $crmRecord->parent_id,
                    'name' => $name ?: 'نامشخص',
                    'crm_service_center_id' => $crmRecord->value
                ];
            }

            return $agencies;
        } catch (\Exception $e) {
            Log::error("Error getting agencies with CRM ID", [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * دریافت اطلاعات مالی یک مرکز (بدون debt1 و debt2)
     */
    private function getFinancialDataForAgency($parentId)
    {
        try {
            $financialKeys = [
                'membership_96' => ['membership_96', 'membership_96_pay_date', 'membership_96_ref_id'],
                'membership_97' => ['membership_97', 'membership_97_pay_date', 'membership_97_ref_id'],
                'membership_98' => ['membership_98', 'membership_98_pay_date', 'membership_98_ref_id'],
                'membership_99' => ['membership_99', 'membership_99_pay_date', 'membership_99_ref_id'],
                'membership_00' => ['membership_00', 'membership_00_pay_date', 'membership_00_ref_id'],
                'membership_01' => ['membership_01', 'membership_01_pay_date', 'membership_01_ref_id'],
                'membership_02' => ['membership_02', 'membership_02_pay_date', 'membership_02_ref_id'],
                'membership_03' => ['membership_03', 'membership_03_pay_date', 'membership_03_ref_id'],
                'membership_04' => ['membership_04', 'membership_04_pay_date', 'membership_04_ref_id'],
                'irngv' => ['irngv', 'irngv_pay_date', 'irngv_ref_id'],
                'irngv_fee' => ['irngv_fee', 'irngv_fee_pay_date', 'irngv_fee_ref_id'],
                'lock_fee' => ['lock_fee', 'lock_fee_pay_date', 'lock_fee_ref_id'],
                'plate_reader' => ['plate_reader', 'plate_reader_pay_date', 'plate_reader_ref_id']
            ];

            $financialRecords = [];

            foreach ($financialKeys as $name => $keys) {
                $amountKey = $keys[0];
                $dateKey = $keys[1];
                $refKey = $keys[2];

                // دریافت مقادیر از دیتابیس
                $records = DB::table('agency_info')
                    ->where('parent_id', $parentId)
                    ->whereIn('key', $keys)
                    ->get()
                    ->keyBy('key');

                $amount = isset($records[$amountKey]) ? $records[$amountKey]->value : null;
                $payDate = isset($records[$dateKey]) ? $records[$dateKey]->value : null;
                $refId = isset($records[$refKey]) ? $records[$refKey]->value : null;

                // اگر حداقل مبلغ وجود داشت، رکورد را اضافه کن
                if ($amount && $amount !== '' && $amount !== '0') {
                    $financialRecords[] = [
                        'name' => $name,
                        'amount' => $amount,
                        'pay_date' => $payDate,
                        'ref_id' => $refId,
                        'year' => $this->extractYearFromName($name)
                    ];
                }
            }

            return $financialRecords;
        } catch (\Exception $e) {
            Log::error("Error getting financial data for agency", [
                'parent_id' => $parentId,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * تبدیل نام انگلیسی به نام فارسی برای نمایش در CRM
     */
    private function convertToPersianName($englishName)
    {
        // تبدیل نام‌های مختلف به فارسی
        if ($englishName === 'irngv') {
            return 'irngv';
        } elseif ($englishName === 'plate_reader') {
            return 'پلاک خوان';
        } elseif ($englishName === 'lock_fee') {
            return 'هزینه قفل';
        } elseif ($englishName === 'irngv_fee') {
            return 'هزینه irngv';
        } elseif (preg_match('/membership_(\d{2})/', $englishName, $matches)) {
            $year = $matches[1];
            return "حق عضویت سال $year";
        }

        // اگر نام شناخته شده نبود، همان نام انگلیسی را برگردان
        return $englishName;
    }

    /**
     * استخراج سال از نام پرداخت
     */
    private function extractYearFromName($name)
    {
        if (preg_match('/membership_(\d{2})/', $name, $matches)) {
            $year = intval($matches[1]);
            return $year >= 96 ? 1300 + $year : 1400 + $year; // تبدیل به سال شمسی کامل
        }

        return null; // برای سایر موارد مثل irngv، debt
    }

    /**
     * ایجاد رکورد مالی در CRM
     */
    private function createFinancialRecord($financial, $serviceCenterId)
    {
        try {
            $persianName = $this->convertToPersianName($financial['name']);

            $financialData = [
                'rhs_name' => $persianName,
                'rhs_amount' => floatval($financial['amount']),
                'rhs_Servicecenter@odata.bind' => "/rhs_servicecenters($serviceCenterId)"
            ];

            // اضافه کردن تاریخ پرداخت اگر وجود داشت
            if ($financial['pay_date']) {
                $financialData['rhs_paymentdate'] = $this->formatDate($financial['pay_date']);
            }

            // اضافه کردن کد پیگیری اگر وجود داشت
            if ($financial['ref_id']) {
                $financialData['rhs_trackingcode'] = $financial['ref_id'];
            }

            // اضافه کردن سال اگر وجود داشت
            if ($financial['year']) {
                $financialData['rhs_year'] = (string)$financial['year'];
            }

            // حذف فیلدهای خالی
            $financialData = array_filter($financialData, function ($value) {
                return $value !== '' && $value !== null;
            });

            echo "<h4>درحال ارسال به CRM:</h4>";
            echo "<p><strong>نام انگلیسی:</strong> {$financial['name']}</p>";
            echo "<p><strong>نام فارسی:</strong> $persianName</p>";
            echo "<pre>";
            print_r($financialData);
            echo "</pre>";

            $response = $this->crmClient->request("rhs_financialinformationcenters", "POST", $financialData);

            echo "<h4>پاسخ CRM:</h4>";
            echo "<p><strong>Status Code:</strong> " . $response->status() . "</p>";
            echo "<p><strong>Response Body:</strong></p>";
            echo "<pre>" . $response->body() . "</pre>";

            if ($response->successful()) {
                echo "<p style='color: green;'>✓ درخواست موفق بود</p>";
                return [
                    'success' => true,
                    'message' => 'رکورد مالی ایجاد شد'
                ];
            }

            // تحلیل خطاهای رایج
            $errorBody = $response->body();
            $statusCode = $response->status();

            $errorMessage = "خطا در ایجاد رکورد مالی - Status: $statusCode";

            if ($statusCode == 400) {
                $errorMessage .= " (Bad Request - احتمالاً فیلد اجباری خالی است یا فرمت داده اشتباه است)";
            } elseif ($statusCode == 404) {
                $errorMessage .= " (Not Found - احتمالاً Service Center ID اشتباه است)";
            } elseif ($statusCode == 401) {
                $errorMessage .= " (Unauthorized - مشکل احراز هویت)";
            } elseif ($statusCode == 403) {
                $errorMessage .= " (Forbidden - عدم دسترسی)";
            }

            Log::error("Failed to create financial record", [
                'financial_data' => $financialData,
                'response_status' => $response->status(),
                'response_body' => $response->body()
            ]);

            return [
                'success' => false,
                'message' => $errorMessage
            ];
        } catch (\Exception $e) {
            echo "<h4 style='color: red;'>Exception رخ داد:</h4>";
            echo "<p><strong>پیام خطا:</strong> " . $e->getMessage() . "</p>";
            echo "<p><strong>فایل:</strong> " . $e->getFile() . "</p>";
            echo "<p><strong>خط:</strong> " . $e->getLine() . "</p>";
            echo "<pre>" . $e->getTraceAsString() . "</pre>";

            Log::error("Exception creating financial record", [
                'financial' => $financial,
                'service_center_id' => $serviceCenterId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Exception در ایجاد رکورد مالی: ' . $e->getMessage()
            ];
        }
    }

    /**
     * فرمت کردن تاریخ برای CRM
     */
    private function formatDate($date)
    {
        if (!$date) return null;

        try {
            return \Carbon\Carbon::parse($date)->toIso8601String();
        } catch (\Exception $e) {
            return null;
        }
    }
}
