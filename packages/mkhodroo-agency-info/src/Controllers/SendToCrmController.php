<?php

namespace Mkhodroo\AgencyInfo\Controllers;

use App\Http\Controllers\Controller;
use Behin\CrmClient\CrmClient;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SendToCrmController extends Controller
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
        try {
            set_time_limit(0);
            
            // تعریف آرایه کدهای مراکز مجاز - می‌توانید این آرایه را تغییر دهید
            $allowedAgencyCodes = [
        // کدهای مراکز مجاز برای ارسال به CRM
        '10013', '10019', '10026', '10031', '10041', '10045', '10056', 
        '11004', '11013', '11044', '11045', '11048', '11050', '11061', 
        '12002', '12006', '12010', 
        '13005', '13030', '13033', '13045', '13047', '13054', '13057', '13072', '13073', '13085', '13111', 
        '14004', '14011', '14013', '14016', 
        '15007', 
        '16007', 
        '17011', '17034', '17040', '17042', '17048', '17054', '17056', '17062', '17065', '17067', '17071', '17075', '17079', '17084', '17091', '17099', '17101', 
        '18012', '18018', 
        '19005', '19014', 
        '20011', '20019', '20023', '20039', '20046', '20051', '20053', '20057', '20061', '20067', '20069', '20079', '20086', '20093', 
        '21007', '21016', '21017', 
        '22002', '22015', '22030', 
        '23004', '23006', 
        '24008', 
        '25004', 
        '26005', '26023', '26025', 
        '27006', '27012', 
        '28008', 
        '29024', '29027', 
        '30007', '30017', 
        '31017', '31018', '31029', '31037', '31039', '31043', 
        '33005', '33006', '33010', '33011', '33016', '33028', '33029', 
        '34025', '34028', '34029', 
        '36014', '36025', '36026', '36034', '36041', 
        '37005', 
        '39015', 
        '40020', 
        'H100010', 'H100215', 'H100216', 'H100218', 'H100222', 'H100426', 
        'H109904', 'H109907', 
        'H110011', 'H110215', 'H110216', 'H110218', 'H110226', 'H110228', 'H110229', 
        'H119801', 'H119805', 'H119908', 
        'H120204', 'H120205', 'H120210', 'H120212', 'H120215', 
        'H129801', 
        'H130111', 'H130218', 
        'H170209', 'H170212', 'H170220', 'H170324', 
        'H179701', 
        'H180101', 'H180204', 
        'H200107', 'H200215', 'H200216', 'H200217', 'H200218', 'H200230', 'H200233', 'H200234', 'H200235', 
        'H209803', 'H209804', 
        'H219902', 
        'H220006', 'H220210', 'H220211', 
        'H230202', 'H230203', 
        'H249803', 
        'H260103', 'H260204', 'H260205', 'H260206', 'H260209', 'H260215', 'H260317', 'H260318', 'H260319', 
        'H290206', 'H290210', 
        'H299902', 
        'H300214', 
        'H310210', 
        'H330204', 'H330205', 'H330207', 
        'H340207', 
        'H349801', 
        'H369903', 
        'H370204', 'H370205', 'H370206', 'H370207', 
        'H379801', 
        'K110112', 'K110325', 
        'K120101', 'K120204', 
        'K130103', 'K130124', 'K130128', 'K130360', 'K130467', 
        'K140202', 
        'K180212', 
        'K190407', 
        'K200104', 'K200333', 'K200338', 
        'K220105', 
        'K240104', 
        'K250305', 
        'K270416', 
        'K300214', 
        'K310101', 
        'K330318', 
        'K340101', 'K340109', 'K340225', 
        'K350303', 
        'K370210', 
        'K380101', 
        'K400104', 'K400307'
    ];
            
            // اگر می‌خواهید همه مراکز را پردازش کنید، این خط را uncomment کنید:
            // $allowedAgencyCodes = null;
            
            $agencies = $this->getAgencyData($allowedAgencyCodes);
            $totalCount = count($agencies);
            
            if ($totalCount == 0) {
                echo "<p style='color: red;'>هیچ مرکزی برای ارسال یافت نشد</p>";
                return;
            }

            echo "<h2>شروع ارسال اطلاعات مالی $totalCount مرکز به CRM</h2>";
            if ($allowedAgencyCodes !== null) {
                echo "<p style='color: blue;'>فقط مراکز با کدهای مجاز پردازش می‌شوند: " . implode(', ', $allowedAgencyCodes) . "</p>";
            }
            echo "<hr>";
            
            $successCount = 0;
            $errorCount = 0;
            $skippedCount = 0;
            $totalFinancialRecords = 0;

            foreach ($agencies as $agency) {
                // بررسی وجود CRM Service Center ID
                $serviceCenterId = $this->getCrmServiceCenterId($agency['parent_id']);
                
                if (!$serviceCenterId) {
                    echo "<p style='color: orange;'>⚠ {$agency['name']} (کد: {$agency['agency_code']}) - مرکز در CRM وجود ندارد، رد شد</p>";
                    $skippedCount++;
                    continue;
                }

                echo "<h3>پردازش اطلاعات مالی: {$agency['name']} (کد: {$agency['agency_code']})</h3>";
                echo "<ul>";

                // دریافت اطلاعات مالی این مرکز
                $financialData = $this->getFinancialDataForAgency($agency['parent_id']);
                
                foreach ($financialData as $financial) {
                    $result = $this->createFinancialRecord($financial, $serviceCenterId);
                    $totalFinancialRecords++;
                    
                    if ($result['success']) {
                        $successCount++;
                        echo "<li style='color: green;'>✓ {$financial['name']} - موفق</li>";
                    } else {
                        $errorCount++;
                        echo "<li style='color: red;'>✗ {$financial['name']} - خطا: {$result['message']}</li>";
                    }
                }

                echo "</ul>";
                echo "<hr>";
                
                // استراحت کوتاه
                usleep(500000); // 0.5 ثانیه
            }

            echo "<h2>نتیجه نهایی</h2>";
            echo "<p><strong>مراکز پردازش شده: $totalCount</strong></p>";
            echo "<p><strong>رکوردهای مالی: $totalFinancialRecords</strong></p>";
            echo "<p><strong>موفق: $successCount - خطا: $errorCount - رد شده: $skippedCount</strong></p>";

        } catch (\Exception $e) {
            echo "<h2 style='color: red;'>خطا در پردازش:</h2>";
            echo "<p>" . $e->getMessage() . "</p>";
        }
    }

    /**
     * دریافت اطلاعات مالی یک مرکز
     */
    private function getFinancialDataForAgency($parentId)
    {
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
            'debt1' => ['debt1', 'debt1_pay_date', 'debt1_ref_id'],
            'debt2' => ['debt2', 'debt2_pay_date', 'debt2_ref_id'],
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

            $amount = $records[$amountKey]->value ?? null;
            $payDate = $records[$dateKey]->value ?? null;
            $refId = $records[$refKey]->value ?? null;

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
            $financialData = [
                'rhs_name' => $financial['name'],
                'rhs_amount' => floatval($financial['amount']),
                'rhs_servicecenter@odata.bind' => "/rhs_servicecenters($serviceCenterId)"
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
                $financialData['rhs_year'] = $financial['year'];
            }

            // حذف فیلدهای خالی
            $financialData = array_filter($financialData, function($value) {
                return $value !== '' && $value !== null;
            });

            $response = $this->crmClient->request("rhs_financialinformationcenters", "POST", $financialData);
            
            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => 'رکورد مالی ایجاد شد'
                ];
            }

            Log::error("Failed to create financial record", [
                'financial_data' => $financialData,
                'response_status' => $response->status(),
                'response_body' => $response->body()
            ]);

            return [
                'success' => false,
                'message' => 'خطا در ایجاد رکورد مالی: ' . $response->status()
            ];

        } catch (\Exception $e) {
            Log::error("Exception creating financial record", [
                'financial' => $financial,
                'service_center_id' => $serviceCenterId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Exception در ایجاد رکورد مالی: ' . $e->getMessage()
            ];
        }
    }

    /**
     * دریافت آمار مراکز موجود در CRM
     */
    public function getCrmStats()
    {
        try {
            echo "<h2>آمار مراکز در CRM</h2>";
            
            // تعریف آرایه کدهای مراکز مجاز - می‌توانید این آرایه را تغییر دهید
            $allowedAgencyCodes = [
                '12001', '12002', '12003', '12004', '12005',
                '13001', '13002', '13003',
                '14001', '14002'
                // کدهای مرکز مورد نظر خود را اینجا اضافه کنید
            ];
            
            // اگر می‌خواهید همه مراکز را بررسی کنید، این خط را uncomment کنید:
            // $allowedAgencyCodes = null;
            
            $agencies = $this->getAgencyData($allowedAgencyCodes);
            $totalCount = count($agencies);
            
            if ($totalCount == 0) {
                echo "<p style='color: red;'>هیچ مرکزی یافت نشد</p>";
                return;
            }
            
            if ($allowedAgencyCodes !== null) {
                echo "<p style='color: blue;'>فقط مراکز با کدهای مجاز بررسی می‌شوند: " . implode(', ', $allowedAgencyCodes) . "</p>";
            }
            
            $existingInCrm = 0;
            $notInCrm = 0;
            $existingIds = [];
            
            echo "<h3>بررسی وضعیت مراکز:</h3>";
            echo "<ul>";
            
            foreach ($agencies as $agency) {
                $crmId = $this->getCrmServiceCenterId($agency['parent_id']);
                
                if ($crmId) {
                    $existingInCrm++;
                    $existingIds[] = [
                        'parent_id' => $agency['parent_id'],
                        'name' => $agency['name'],
                        'agency_code' => $agency['agency_code'],
                        'crm_id' => $crmId
                    ];
                    echo "<li style='color: blue;'>✓ {$agency['name']} (کد: {$agency['agency_code']}) - موجود در CRM (ID: $crmId)</li>";
                } else {
                    $notInCrm++;
                    echo "<li style='color: orange;'>⚠ {$agency['name']} (کد: {$agency['agency_code']}) - موجود نیست در CRM</li>";
                }
            }
            
            echo "</ul>";
            
            echo "<div style='margin: 20px 0; padding: 15px; border: 1px solid #ddd; background-color: #f9f9f9;'>";
            echo "<h3>خلاصه آمار:</h3>";
            echo "<ul>";
            echo "<li><strong>کل مراکز:</strong> $totalCount</li>";
            echo "<li><strong>موجود در CRM:</strong> <span style='color: blue;'>$existingInCrm</span></li>";
            echo "<li><strong>موجود نیست در CRM:</strong> <span style='color: orange;'>$notInCrm</span></li>";
            echo "<li><strong>درصد پوشش CRM:</strong> <span style='color: " . ($existingInCrm > $notInCrm ? 'green' : 'red') . ";'>" . round(($existingInCrm / $totalCount) * 100, 2) . "%</span></li>";
            echo "</ul>";
            echo "</div>";
            
            if ($notInCrm > 0) {
                echo "<div style='margin: 10px 0; padding: 10px; border: 1px solid orange; background-color: #fff3cd;'>";
                echo "<h4>⚠ توجه:</h4>";
                echo "<p>$notInCrm مرکز هنوز در CRM ایجاد نشده‌اند. می‌توانید از روت <code>/agency-info/send-to-crm</code> برای ارسال آن‌ها استفاده کنید.</p>";
                echo "</div>";
            }
            
            if ($existingInCrm > 0) {
                echo "<div style='margin: 10px 0; padding: 10px; border: 1px solid blue; background-color: #d1ecf1;'>";
                echo "<h4>ℹ اطلاعات:</h4>";
                echo "<p>$existingInCrm مرکز قبلاً در CRM ایجاد شده‌اند و در ارسال بعدی رد خواهند شد.</p>";
                echo "</div>";
            }
            
        } catch (\Exception $e) {
            echo "<h2 style='color: red;'>خطا در دریافت آمار:</h2>";
            echo "<p>" . $e->getMessage() . "</p>";
        }
    }

    /**
     * ارسال اطلاعات مراکز به CRM به صورت chunk
     */
    public function sendToCrm()
    {
        try {
            set_time_limit(0); // بدون محدودیت زمان
            
            // تعریف آرایه کدهای مراکز مجاز - می‌توانید این آرایه را تغییر دهید
            $allowedAgencyCodes = 
//             ['10013', '10019', '10026', '10031', '10041', '10045', '10056', '11004', '11013', '11062', '12002', '12003', '13010', '13054', '13062', '13126', '13127', '17102',
// 'K170413', 'K350303', 'K370210', 'K380101', 'K400104', 'K400307'];

         
        
        ['10013', '10019', '10026', '10031', '10041', '10045', '10056', 
        '11004', '11013', '11044', '11045', '11048', '11050', '11061', 
        '12002', '12006', '12010', 
        '13005', '13030', '13033', '13045', '13047', '13054', '13057', '13072', '13073', '13085', '13111', 
        '14004', '14011', '14013', '14016', 
        '15007', 
        '16007', 
        '17011', '17034', '17040', '17042', '17048', '17054', '17056', '17062', '17065', '17067', '17071', '17075', '17079', '17084', '17091', '17099', '17101', 
        '18012', '18018', 
        '19005', '19014', 
        '20011', '20019', '20023', '20039', '20046', '20051', '20053', '20057', '20061', '20067', '20069', '20079', '20086', '20093', 
        '21007', '21016', '21017', 
        '22002', '22015', '22030', 
        '23004', '23006', 
        '24008', 
        '25004', 
        '26005', '26023', '26025', 
        '27006', '27012', 
        '28008', 
        '29024', '29027', 
        '30007', '30017', 
        '31017', '31018', '31029', '31037', '31039', '31043', 
        '33005', '33006', '33010', '33011', '33016', '33028', '33029', 
        '34025', '34028', '34029', 
        '36014', '36025', '36026', '36034', '36041', 
        '37005', 
        '39015', 
        '40020', 
        'H100010', 'H100215', 'H100216', 'H100218', 'H100222', 'H100426', 
        'H109904', 'H109907', 
        'H110011', 'H110215', 'H110216', 'H110218', 'H110226', 'H110228', 'H110229', 
        'H119801', 'H119805', 'H119908', 
        'H120204', 'H120205', 'H120210', 'H120212', 'H120215', 
        'H129801', 
        'H130111', 'H130218', 
        'H170209', 'H170212', 'H170220', 'H170324', 
        'H179701', 
        'H180101', 'H180204', 
        'H200107', 'H200215', 'H200216', 'H200217', 'H200218', 'H200230', 'H200233', 'H200234', 'H200235', 
        'H209803', 'H209804', 
        'H219902', 
        'H220006', 'H220210', 'H220211', 
        'H230202', 'H230203', 
        'H249803', 
        'H260103', 'H260204', 'H260205', 'H260206', 'H260209', 'H260215', 'H260317', 'H260318', 'H260319', 
        'H290206', 'H290210', 
        'H299902', 
        'H300214', 
        'H310210', 
        'H330204', 'H330205', 'H330207', 
        'H340207', 
        'H349801', 
        'H369903', 
        'H370204', 'H370205', 'H370206', 'H370207', 
        'H379801', 
        'K110112', 'K110325', 
        'K120101', 'K120204', 
        'K130103', 'K130124', 'K130128', 'K130360', 'K130467', 
        'K140202', 
        'K180212', 
        'K190407', 
        'K200104', 'K200333', 'K200338', 
        'K220105', 
        'K240104', 
        'K250305', 
        'K270416', 
        'K300214', 
        'K310101', 
        'K330318', 
        'K340101', 'K340109', 'K340225', 
        'K350303', 
        'K370210', 
        'K380101', 
        'K400104', 'K400307'
   
                // کدهای مرکز مورد نظر خود را اینجا اضافه کنید
            ];
            
            // اگر می‌خواهید همه مراکز را پردازش کنید، این خط را uncomment کنید:
            // $allowedAgencyCodes = null;
            
            $agencies = $this->getAgencyData($allowedAgencyCodes);
            $totalCount = count($agencies);
            
            if ($totalCount == 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'هیچ مرکزی برای ارسال یافت نشد'
                ]);
            }

            $chunkSize = 10; // تعداد رکورد در هر chunk
            $chunks = array_chunk($agencies, $chunkSize);
            $totalChunks = count($chunks);
            
            $successCount = 0;
            $errorCount = 0;
            $skippedCount = 0;
            $processedCount = 0;
            $allResults = [];

            echo "<h2>شروع ارسال $totalCount مرکز به CRM</h2>";
            if ($allowedAgencyCodes !== null) {
                echo "<p style='color: blue;'>فقط مراکز با کدهای مجاز پردازش می‌شوند: " . implode(', ', $allowedAgencyCodes) . "</p>";
            }
            echo "<p>تعداد chunk ها: $totalChunks</p>";
            echo "<hr>";
            
            // فلاش کردن خروجی
            if (ob_get_level()) {
                ob_end_flush();
            }
            ob_start();

            foreach ($chunks as $chunkIndex => $chunk) {
                $chunkNumber = $chunkIndex + 1;
                $chunkResults = [];
                
                echo "<h3>پردازش Chunk $chunkNumber از $totalChunks</h3>";
                echo "<ul>";
                
                foreach ($chunk as $agency) {
                    $processedCount++;
                    $result = $this->processAgency($agency);
                    $chunkResults[] = $result;
                    $allResults[] = $result;

                    if ($result['success']) {
                        if (isset($result['skipped']) && $result['skipped']) {
                            $skippedCount++;
                            echo "<li style='color: blue;'>⏭ {$result['name']} (کد: {$agency['agency_code']}) - رد شد: {$result['message']}</li>";
                        } else {
                            $successCount++;
                            echo "<li style='color: green;'>✓ {$result['name']} (کد: {$agency['agency_code']}) - موفق</li>";
                        }
                    } else {
                        $errorCount++;
                        echo "<li style='color: red;'>✗ {$result['name']} (کد: {$agency['agency_code']}) - خطا: {$result['message']}</li>";
                    }
                    
                    // فلاش کردن خروجی برای نمایش فوری
                    ob_flush();
                    flush();
                    
                    // کمی استراحت
                    usleep(200000); // 0.2 ثانیه
                }
                
                echo "</ul>";
                $chunkSuccess = count(array_filter($chunkResults, fn($r) => $r['success'] && !isset($r['skipped'])));
                $chunkSkipped = count(array_filter($chunkResults, fn($r) => isset($r['skipped']) && $r['skipped']));
                $chunkError = count(array_filter($chunkResults, fn($r) => !$r['success']));
                
                echo "<p><strong>Chunk $chunkNumber کامل شد. موفق: $chunkSuccess - رد شده: $chunkSkipped - خطا: $chunkError</strong></p>";
                echo "<hr>";
                
                ob_flush();
                flush();
                
                // استراحت بین chunk ها
                sleep(1);
            }

            echo "<h2>نتیجه نهایی</h2>";
            echo "<p><strong>کل: $totalCount - موفق: $successCount - رد شده: $skippedCount - خطا: $errorCount</strong></p>";
            
            // لاگ نهایی
            Log::info("Agency CRM sync completed", [
                'total' => $totalCount,
                'success' => $successCount,
                'skipped' => $skippedCount,
                'error' => $errorCount,
                'allowed_codes' => $allowedAgencyCodes
            ]);

        } catch (\Exception $e) {
            Log::error("Exception while sending agencies to CRM", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            echo "<h2 style='color: red;'>خطا در پردازش</h2>";
            echo "<p>{$e->getMessage()}</p>";
        }
    }

    /**
     * تست و debug اطلاعات
     */
    public function debugData()
    {
        try {
            $agencies = $this->getAgencyData();
            
            echo "<h2>Debug اطلاعات مراکز</h2>";
            echo "<p>تعداد کل مراکز: " . count($agencies) . "</p>";
            
            if (count($agencies) > 0) {
                echo "<h3>نمونه اولین مرکز:</h3>";
                echo "<pre>";
                print_r($agencies[0]);
                echo "</pre>";
                
                // تست اتصال به CRM
                echo "<h3>تست اتصال به CRM:</h3>";
                try {
                    $testResponse = $this->crmClient->request("contacts", "GET", [
                        '$select' => 'contactid,fullname',
                        '$top' => 1
                    ]);
                    
                    if ($testResponse->successful()) {
                        echo "<p style='color: green;'>✓ اتصال به CRM موفق</p>";
                        echo "<pre>";
                        print_r($testResponse->json());
                        echo "</pre>";
                    } else {
                        echo "<p style='color: red;'>✗ خطا در اتصال به CRM</p>";
                        echo "<pre>";
                        echo $testResponse->body();
                        echo "</pre>";
                    }
                } catch (\Exception $e) {
                    echo "<p style='color: red;'>✗ Exception در اتصال به CRM: " . $e->getMessage() . "</p>";
                }
                
                // تست پردازش یک مرکز
                echo "<h3>تست پردازش اولین مرکز:</h3>";
                $result = $this->processAgency($agencies[0]);
                echo "<pre>";
                print_r($result);
                echo "</pre>";
            } else {
                echo "<p style='color: red;'>هیچ مرکزی یافت نشد!</p>";
            }
            
        } catch (\Exception $e) {
            echo "<h2 style='color: red;'>خطا در debug:</h2>";
            echo "<p>" . $e->getMessage() . "</p>";
            echo "<pre>" . $e->getTraceAsString() . "</pre>";
        }
    }
    /**
     * تست ایجاد Service Center بدون lookup
     */
    public function testServiceCenterWithoutLookup()
    {
        try {
            echo "<h2>تست ایجاد Service Center بدون lookup</h2>";
            
            // تست با داده‌های واقعی بدون lookup
            $agencies = $this->getAgencyData();
            if (empty($agencies)) {
                echo "<p style='color: red;'>هیچ مرکزی یافت نشد</p>";
                return;
            }
            
            $firstAgency = $agencies[0];
            
            $serviceCenterData = [
                'rhs_name' => $firstAgency['name'] ?? 'تست نام',
                'rhs_fullname' => $firstAgency['firstname'] ?? 'تست',
                'rhs_lastname' => $firstAgency['lastname'] ?? 'نام خانوادگی',
                'rhs_mobile' => $this->cleanMobile($firstAgency['mobile'] ?? '09123456789'),
                'rhs_centercode' => $firstAgency['agency_code'] ?? '12003',
                'rhs_address' => $firstAgency['address'] ?? '',
                'rhs_nationalcode' => $this->cleanMobile($firstAgency['national_id'] ?? ''),
                'rhs_province' => $firstAgency['province'] ?? '',
                'rhs_city' => $firstAgency['city'] ?? ''
                // بدون lookup
            ];

            echo "<h3>ارسال داده‌های بدون lookup:</h3>";
            echo "<pre>";
            print_r($serviceCenterData);
            echo "</pre>";

            $response = $this->crmClient->request("rhs_servicecenters", "POST", $serviceCenterData);
            
            if ($response->successful()) {
                echo "<p style='color: green;'>✓ Service Center بدون lookup با موفقیت ایجاد شد</p>";
                $serviceCenterId = $this->extractEntityId($response);
                echo "<p>Service Center ID: $serviceCenterId</p>";
                
                // حالا تست ایجاد Contact و اتصال
                echo "<hr>";
                echo "<h3>حالا تست ایجاد Contact:</h3>";
                $this->testCreateContactAndLink($serviceCenterId, $firstAgency);
                
            } else {
                echo "<p style='color: red;'>✗ خطا در ایجاد Service Center: " . $response->status() . "</p>";
                echo "<pre>" . $response->body() . "</pre>";
            }
            
        } catch (\Exception $e) {
            echo "<h2 style='color: red;'>خطا در تست:</h2>";
            echo "<p>" . $e->getMessage() . "</p>";
        }
    }

    /**
     * مقایسه داده‌های ارسالی با داده‌های ذخیره شده در CRM
     */
    private function compareDataWithCrm($serviceCenterId, $sentData)
    {
        try {
            // لیست تمام فیلدهای قابل بررسی
            $allFields = [
                'rhs_name', 'rhs_fullname', 'rhs_lastname', 'rhs_mobile', 'rhs_phone', 
                'rhs_centercode', 'rhs_address', 'rhs_nationalcode', 'rhs_province', 
                'rhs_city', 'rhs_description', 'rhs_row', 'rhs_yearofreceivingthecode', 
                'rhs_guildnumber', 'rhs_postalcode', 'rhs_location', 'rhs_dateofissue', 
                'rhs_expirydate', 'rhs_debtdescription', 'statecode', 'statuscode'
            ];
            
            // دریافت داده‌های ذخیره شده از CRM
            $response = $this->crmClient->request("rhs_servicecenters($serviceCenterId)", "GET", [
                '$select' => implode(',', $allFields)
            ]);
            
            if (!$response->successful()) {
                echo "<p style='color: red;'>خطا در دریافت داده‌های CRM: " . $response->status() . "</p>";
                echo "<pre>" . $response->body() . "</pre>";
                return;
            }
            
            $crmData = $response->json();
            
            echo "<table border='1' style='border-collapse: collapse; width: 100%; font-family: Arial;'>";
            echo "<tr style='background-color: #f0f0f0;'>";
            echo "<th style='padding: 8px;'>فیلد</th><th style='padding: 8px;'>داده ارسالی</th><th style='padding: 8px;'>داده CRM</th><th style='padding: 8px;'>وضعیت</th>";
            echo "</tr>";
            
            $matchCount = 0;
            $totalCount = 0;
            $emptyInCrm = 0;
            $notSent = 0;
            
            // بررسی فیلدهای ارسالی
            foreach ($sentData as $field => $sentValue) {
                $crmValue = $crmData[$field] ?? null;
                $totalCount++;
                
                // تبدیل مقادیر null به رشته خالی برای مقایسه بهتر
                $sentValueDisplay = $sentValue === null ? 'NULL' : (string)$sentValue;
                $crmValueDisplay = $crmValue === null ? 'NULL' : (string)$crmValue;
                
                $isMatch = $sentValue == $crmValue;
                if ($isMatch) {
                    $matchCount++;
                } elseif ($crmValue === null || $crmValue === '') {
                    $emptyInCrm++;
                }
                
                $statusColor = $isMatch ? 'green' : ($crmValue === null || $crmValue === '' ? 'orange' : 'red');
                $statusText = $isMatch ? '✓ مطابق' : ($crmValue === null || $crmValue === '' ? '⚠ خالی در CRM' : '✗ نامطابق');
                
                echo "<tr>";
                echo "<td style='padding: 8px;'><strong>$field</strong></td>";
                echo "<td style='padding: 8px;'>" . htmlspecialchars($sentValueDisplay) . "</td>";
                echo "<td style='padding: 8px;'>" . htmlspecialchars($crmValueDisplay) . "</td>";
                echo "<td style='padding: 8px; color: $statusColor;'>$statusText</td>";
                echo "</tr>";
            }
            
            // بررسی فیلدهایی که ارسال نشده‌اند ولی در CRM مقدار دارند
            foreach ($allFields as $field) {
                if (!array_key_exists($field, $sentData)) {
                    $crmValue = $crmData[$field] ?? null;
                    if ($crmValue !== null && $crmValue !== '') {
                        $notSent++;
                        echo "<tr style='background-color: #f9f9f9;'>";
                        echo "<td style='padding: 8px;'><strong>$field</strong></td>";
                        echo "<td style='padding: 8px; color: gray;'>ارسال نشده</td>";
                        echo "<td style='padding: 8px;'>" . htmlspecialchars((string)$crmValue) . "</td>";
                        echo "<td style='padding: 8px; color: blue;'>ℹ مقدار پیش‌فرض CRM</td>";
                        echo "</tr>";
                    }
                }
            }
            
            echo "</table>";
            
            $percentage = $totalCount > 0 ? round(($matchCount / $totalCount) * 100, 2) : 0;
            echo "<div style='margin: 15px 0; padding: 10px; border: 1px solid #ddd; background-color: #f9f9f9;'>";
            echo "<h4>خلاصه نتایج:</h4>";
            echo "<ul>";
            echo "<li><strong>کل فیلدهای ارسالی:</strong> $totalCount</li>";
            echo "<li><strong>فیلدهای مطابق:</strong> <span style='color: green;'>$matchCount</span></li>";
            echo "<li><strong>فیلدهای خالی در CRM:</strong> <span style='color: orange;'>$emptyInCrm</span></li>";
            echo "<li><strong>فیلدهای نامطابق:</strong> <span style='color: red;'>" . ($totalCount - $matchCount) . "</span></li>";
            echo "<li><strong>درصد تطابق:</strong> <span style='color: " . ($percentage > 80 ? 'green' : ($percentage > 50 ? 'orange' : 'red')) . ";'>$percentage%</span></li>";
            if ($notSent > 0) {
                echo "<li><strong>فیلدهای اضافی در CRM:</strong> <span style='color: blue;'>$notSent</span></li>";
            }
            echo "</ul>";
            echo "</div>";
            
            // نمایش فیلدهای نامطابق
            if ($matchCount < $totalCount) {
                echo "<h4>جزئیات فیلدهای نامطابق:</h4>";
                echo "<ul>";
                foreach ($sentData as $field => $sentValue) {
                    $crmValue = $crmData[$field] ?? null;
                    if ($sentValue != $crmValue) {
                        $sentDisplay = $sentValue === null ? 'NULL' : $sentValue;
                        $crmDisplay = $crmValue === null ? 'NULL' : $crmValue;
                        echo "<li><strong>$field:</strong> ارسالی='$sentDisplay' ← CRM='$crmDisplay'</li>";
                    }
                }
                echo "</ul>";
            }
            
            // توصیه‌هایی برای بهبود
            if ($emptyInCrm > 0) {
                echo "<div style='margin: 10px 0; padding: 10px; border: 1px solid orange; background-color: #fff3cd;'>";
                echo "<h4>⚠ توجه:</h4>";
                echo "<p>$emptyInCrm فیلد در CRM خالی ذخیره شده‌اند. این ممکن است به دلایل زیر باشد:</p>";
                echo "<ul>";
                echo "<li>نوع داده فیلد در CRM با داده ارسالی مطابقت ندارد</li>";
                echo "<li>فیلد در CRM وجود ندارد یا نام آن متفاوت است</li>";
                echo "<li>محدودیت‌های validation در CRM</li>";
                echo "<li>مشکل در encoding یا فرمت داده</li>";
                echo "</ul>";
                echo "</div>";
            }
            
        } catch (\Exception $e) {
            echo "<p style='color: red;'>خطا در مقایسه داده‌ها: " . $e->getMessage() . "</p>";
            echo "<pre>" . $e->getTraceAsString() . "</pre>";
        }
    }

    /**
     * تست ایجاد Contact و اتصال به Service Center
     */
    private function testCreateContactAndLink($serviceCenterId, $agencyData)
    {
        try {
            $mobile = $this->cleanMobile($agencyData['mobile'] ?? '');
            
            // ایجاد Contact جدید
            $contactData = [
                'firstname' => $agencyData['firstname'] ?? '',
                'lastname' => $agencyData['lastname'] ?? '',
                'fullname' => $agencyData['name'] ?? '',
                'mobilephone' => $mobile,
                'telephone1' => $this->cleanMobile($agencyData['phone'] ?? ''),
                'address1_line1' => $agencyData['address'] ?? ''
            ];

            // حذف فیلدهای خالی
            $contactData = array_filter($contactData, function($value) {
                return $value !== '' && $value !== null;
            });

            echo "<p>ایجاد Contact جدید:</p>";
            echo "<pre>";
            print_r($contactData);
            echo "</pre>";

            $contactResponse = $this->crmClient->request("contacts", "POST", $contactData);
            
            if ($contactResponse->successful()) {
                $contactId = $this->extractEntityId($contactResponse);
                echo "<p style='color: green;'>✓ Contact جدید ایجاد شد: $contactId</p>";
                
                // حالا تست به‌روزرسانی Service Center با lookup
                echo "<p>به‌روزرسانی Service Center با lookup:</p>";
                $updateData = [
                    'new_contact@odata.bind' => "/contacts($contactId)"
                ];
                
                $updateResponse = $this->crmClient->request("rhs_servicecenters($serviceCenterId)", "PATCH", $updateData);
                
                if ($updateResponse->successful()) {
                    echo "<p style='color: green;'>✓ Service Center با موفقیت به Contact متصل شد</p>";
                } else {
                    echo "<p style='color: red;'>✗ خطا در اتصال: " . $updateResponse->status() . "</p>";
                    echo "<pre>" . $updateResponse->body() . "</pre>";
                }
                
            } else {
                echo "<p style='color: red;'>✗ خطا در ایجاد Contact: " . $contactResponse->status() . "</p>";
                echo "<pre>" . $contactResponse->body() . "</pre>";
            }
            
        } catch (\Exception $e) {
            echo "<p style='color: red;'>خطا در ایجاد Contact: " . $e->getMessage() . "</p>";
        }
    }
    public function testMinimalServiceCenter()
    {
        try {
            echo "<h2>تست جامع تمام فیلدهای Service Center</h2>";
            
            // دریافت داده‌های واقعی
            $agencies = $this->getAgencyData();
            if (empty($agencies)) {
                echo "<p style='color: red;'>هیچ مرکزی یافت نشد</p>";
                return;
            }
            
            $firstAgency = $agencies[0];
            
            echo "<h3>داده‌های اصلی از دیتابیس:</h3>";
            echo "<pre>";
            print_r($firstAgency);
            echo "</pre>";
            
            // آماده‌سازی تمام فیلدهای مورد نظر برای ارسال
            $statecode = $this->formatEnable($firstAgency['enable'] ?? 1);
            $statuscode = $firstAgency['fin_green'] === 'ok' ? 0 : 1;
            $allServiceCenterData = [
                'rhs_name' => $firstAgency['name'] ?? '',
                'rhs_fullname' => $firstAgency['firstname'] ?? '',
                'rhs_lastname' => $firstAgency['lastname'] ?? '',
                'rhs_mobile' => $this->cleanMobile($firstAgency['mobile'] ?? ''),
                'rhs_phone' => $this->cleanMobile($firstAgency['phone'] ?? ''),
                'rhs_centercode' => $firstAgency['agency_code'] ?? '',
                'rhs_address' => $firstAgency['address'] ?? '',
                'rhs_nationalcode' => $this->cleanMobile($firstAgency['national_id'] ?? ''),
                'rhs_province' => $firstAgency['province'] ?? '',
                'rhs_city' => $firstAgency['city'] ?? '',
                'rhs_description' => $firstAgency['description'] ?? '',
                'rhs_row' => $firstAgency['customer_type'] ?? '',
                'rhs_yearofreceivingthecode' => $firstAgency['receiving_code_year'] ?? '',
                'rhs_guildnumber' => $firstAgency['guild_number'] ?? '',
                'rhs_postalcode' => $firstAgency['postal_code'] ?? '',
                'rhs_location' => $firstAgency['location'] ?? '',
                'rhs_dateofissue' => $this->formatDate($firstAgency['issued_date'] ?? ''),
                'rhs_expirydate' => $this->formatDate($firstAgency['exp_date'] ?? ''),
                'rhs_debtdescription' => $firstAgency['fin_details'] ?? '',
                'statecode' => $statecode,
                'statuscode' => $statuscode
            ];
            
            echo "<h3>تست 1: ارسال تمام فیلدها (بدون حذف خالی‌ها)</h3>";
            echo "<h4>داده‌های آماده برای ارسال:</h4>";
            echo "<pre>";
            print_r($allServiceCenterData);
            echo "</pre>";
            
            // ارسال به CRM با تمام فیلدها
            $response = $this->crmClient->request("rhs_servicecenters", "POST", $allServiceCenterData);
            
            if ($response->successful()) {
                $serviceCenterId = $this->extractEntityId($response);
                echo "<p style='color: green;'>✓ Service Center با تمام فیلدها با موفقیت ایجاد شد</p>";
                echo "<p><strong>Service Center ID:</strong> $serviceCenterId</p>";
                
                // ذخیره CRM Service Center ID در دیتابیس
                $saveResult = $this->saveCrmServiceCenterId($firstAgency['parent_id'], $serviceCenterId);
                if ($saveResult) {
                    echo "<p style='color: green;'>✓ CRM Service Center ID در دیتابیس ذخیره شد</p>";
                } else {
                    echo "<p style='color: orange;'>⚠ خطا در ذخیره CRM Service Center ID در دیتابیس</p>";
                }
                
                // مقایسه داده‌ها
                echo "<hr>";
                echo "<h3>مقایسه داده‌های ارسالی با CRM:</h3>";
                $this->compareDataWithCrm($serviceCenterId, $allServiceCenterData);
                
                // تست ایجاد Contact و اتصال
                echo "<hr>";
                echo "<h3>تست 2: ایجاد Contact و اتصال</h3>";
                $this->testCreateContactAndLink($serviceCenterId, $firstAgency);
                
            } else {
                echo "<p style='color: red;'>✗ خطا در ایجاد Service Center با تمام فیلدها: " . $response->status() . "</p>";
                echo "<pre>" . $response->body() . "</pre>";
                
                // اگر تست کامل ناموفق بود، تست با فیلدهای اساسی
                echo "<hr>";
                echo "<h3>تست جایگزین: فقط فیلدهای اساسی</h3>";
                $this->testBasicFields($firstAgency);
            }
            
        } catch (\Exception $e) {
            echo "<h2 style='color: red;'>خطا در تست جامع:</h2>";
            echo "<p>" . $e->getMessage() . "</p>";
            
            // در صورت خطا، تست با فیلدهای اساسی
            echo "<hr>";
            echo "<h3>تست جایگزین: فقط فیلدهای اساسی</h3>";
            try {
                $agencies = $this->getAgencyData();
                if (!empty($agencies)) {
                    $this->testBasicFields($agencies[0]);
                }
            } catch (\Exception $e2) {
                echo "<p style='color: red;'>خطا در تست جایگزین: " . $e2->getMessage() . "</p>";
            }
        }
    }

    /**
     * تست با فیلدهای اساسی در صورت ناموفق بودن تست کامل
     */
    private function testBasicFields($agency)
    {
        try {
            $basicServiceCenterData = [
                'rhs_name' => $agency['name'] ?? 'تست نام',
                'rhs_fullname' => $agency['firstname'] ?? '',
                'rhs_lastname' => $agency['lastname'] ?? '',
                'rhs_mobile' => $this->cleanMobile($agency['mobile'] ?? ''),
                'rhs_centercode' => $agency['agency_code'] ?? ''
            ];
            
            // حذف فیلدهای خالی
            $basicServiceCenterData = array_filter($basicServiceCenterData, function($value) {
                return $value !== '' && $value !== null;
            });
            
            echo "<h4>داده‌های اساسی برای ارسال:</h4>";
            echo "<pre>";
            print_r($basicServiceCenterData);
            echo "</pre>";
            
            $response = $this->crmClient->request("rhs_servicecenters", "POST", $basicServiceCenterData);
            
            if ($response->successful()) {
                $serviceCenterId = $this->extractEntityId($response);
                echo "<p style='color: green;'>✓ Service Center با فیلدهای اساسی با موفقیت ایجاد شد</p>";
                echo "<p><strong>Service Center ID:</strong> $serviceCenterId</p>";
                
                // ذخیره CRM Service Center ID در دیتابیس
                $saveResult = $this->saveCrmServiceCenterId($agency['parent_id'], $serviceCenterId);
                if ($saveResult) {
                    echo "<p style='color: green;'>✓ CRM Service Center ID در دیتابیس ذخیره شد</p>";
                } else {
                    echo "<p style='color: orange;'>⚠ خطا در ذخیره CRM Service Center ID در دیتابیس</p>";
                }
                
                // مقایسه داده‌ها
                echo "<h4>مقایسه داده‌های اساسی با CRM:</h4>";
                $this->compareDataWithCrm($serviceCenterId, $basicServiceCenterData);
                
                // تست ایجاد Contact و اتصال
                echo "<hr>";
                echo "<h3>ایجاد Contact و اتصال:</h3>";
                $this->testCreateContactAndLink($serviceCenterId, $agency);
                
            } else {
                echo "<p style='color: red;'>✗ خطا در ایجاد Service Center با فیلدهای اساسی: " . $response->status() . "</p>";
                echo "<pre>" . $response->body() . "</pre>";
            }
            
        } catch (\Exception $e) {
            echo "<p style='color: red;'>خطا در تست فیلدهای اساسی: " . $e->getMessage() . "</p>";
        }
    }
    public function testCreateServiceCenter()
    {
        try {
            // تست با یک contact موجود
            $testResponse = $this->crmClient->request("contacts", "GET", [
                '$select' => 'contactid,fullname',
                '$top' => 1
            ]);
            
            if (!$testResponse->successful()) {
                echo "<p style='color: red;'>خطا در دریافت contact برای تست</p>";
                return;
            }
            
            $contacts = $testResponse->json()['value'];
            if (empty($contacts)) {
                echo "<p style='color: red;'>هیچ contact برای تست یافت نشد</p>";
                return;
            }
            
            $contactId = $contacts[0]['contactid'];
            echo "<p>استفاده از Contact ID: $contactId</p>";
            
            // تست با داده‌های واقعی از اولین مرکز
            $agencies = $this->getAgencyData();
            if (empty($agencies)) {
                echo "<p style='color: red;'>هیچ مرکزی یافت نشد</p>";
                return;
            }
            
            $firstAgency = $agencies[0];
            
            // تست با فیلدهای محدود ابتدا
            $serviceCenterData = [
                'rhs_name' => $firstAgency['name'] ?? 'تست نام',
                'rhs_fullname' => $firstAgency['firstname'] ?? 'تست',
                'rhs_lastname' => $firstAgency['lastname'] ?? 'نام خانوادگی',
                'rhs_mobile' => $this->cleanMobile($firstAgency['mobile'] ?? '09123456789'),
                'rhs_centercode' => $firstAgency['agency_code'] ?? '12003',
                'new_contact@odata.bind' => "/contacts($contactId)"
            ];

            echo "<h3>ارسال داده‌های تست:</h3>";
            echo "<pre>";
            print_r($serviceCenterData);
            echo "</pre>";

            $response = $this->crmClient->request("rhs_servicecenters", "POST", $serviceCenterData);
            
            if ($response->successful()) {
                echo "<p style='color: green;'>✓ Service Center تست با موفقیت ایجاد شد</p>";
                $serviceCenterId = $this->extractEntityId($response);
                echo "<p>Service Center ID: $serviceCenterId</p>";
            } else {
                echo "<p style='color: red;'>✗ خطا در ایجاد Service Center تست</p>";
                echo "<p>Status: " . $response->status() . "</p>";
                echo "<pre>";
                echo $response->body();
                echo "</pre>";
            }
            
        } catch (\Exception $e) {
            echo "<h2 style='color: red;'>خطا در تست:</h2>";
            echo "<p>" . $e->getMessage() . "</p>";
        }
    }

    /**
     * تست کامل ایجاد و بررسی یک Service Center
     */
    public function testCompleteServiceCenter()
    {
        try {
            echo "<h2>تست کامل ایجاد و بررسی Service Center</h2>";
            
            // دریافت داده‌های واقعی
            $agencies = $this->getAgencyData();
            if (empty($agencies)) {
                echo "<p style='color: red;'>هیچ مرکزی یافت نشد</p>";
                return;
            }
            
            $firstAgency = $agencies[0];
            
            echo "<h3>داده‌های اصلی از دیتابیس:</h3>";
            echo "<pre>";
            print_r($firstAgency);
            echo "</pre>";
            
            // آماده‌سازی داده‌ها برای ارسال
            $serviceCenterData = [
                'rhs_name' => $firstAgency['name'] ?? '',
                'rhs_fullname' => $firstAgency['firstname'] ?? '',
                'rhs_lastname' => $firstAgency['lastname'] ?? '',
                'rhs_mobile' => $this->cleanMobile($firstAgency['mobile'] ?? ''),
                'rhs_phone' => $this->cleanMobile($firstAgency['phone'] ?? ''),
                'rhs_centercode' => $firstAgency['agency_code'] ?? '',
                'rhs_address' => $firstAgency['address'] ?? '',
                'rhs_nationalcode' => $this->cleanMobile($firstAgency['national_id'] ?? ''),
                'rhs_province' => $firstAgency['province'] ?? '',
                'rhs_city' => $firstAgency['city'] ?? '',
                'rhs_description' => $firstAgency['description'] ?? '',
                'rhs_debtdescription' => $firstAgency['fin_details'] ?? ''
            ];
            
            // حذف فیلدهای خالی
            $serviceCenterData = array_filter($serviceCenterData, function($value) {
                return $value !== '' && $value !== null;
            });
            
            echo "<h3>داده‌های آماده برای ارسال:</h3>";
            echo "<pre>";
            print_r($serviceCenterData);
            echo "</pre>";
            
            // ارسال به CRM
            $response = $this->crmClient->request("rhs_servicecenters", "POST", $serviceCenterData);
            
            if ($response->successful()) {
                $serviceCenterId = $this->extractEntityId($response);
                echo "<p style='color: green;'>✓ Service Center با موفقیت ایجاد شد</p>";
                echo "<p><strong>Service Center ID:</strong> $serviceCenterId</p>";
                
                // مقایسه داده‌ها
                echo "<hr>";
                echo "<h3>مقایسه داده‌های ارسالی با CRM:</h3>";
                $this->compareDataWithCrm($serviceCenterId, $serviceCenterData);
                
            } else {
                echo "<p style='color: red;'>✗ خطا در ایجاد Service Center: " . $response->status() . "</p>";
                echo "<pre>" . $response->body() . "</pre>";
            }
            
        } catch (\Exception $e) {
            echo "<h2 style='color: red;'>خطا در تست کامل:</h2>";
            echo "<p>" . $e->getMessage() . "</p>";
        }
    }
    private function processAgency($agency)
    {
        try {
            // بررسی اینکه آیا این مرکز قبلاً در CRM ایجاد شده است
            $existingCrmId = $this->getCrmServiceCenterId($agency['parent_id']);
            
            if ($existingCrmId) {
                return [
                    'agency_id' => $agency['parent_id'],
                    'name' => $agency['name'] ?? 'نامشخص',
                    'success' => true,
                    'message' => 'مرکز قبلاً در CRM موجود است',
                    'service_center_id' => $existingCrmId,
                    'skipped' => true
                ];
            }
            
            $mobile = $this->cleanMobile($agency['mobile'] ?? '');
            
            if (!$mobile) {
                return [
                    'agency_id' => $agency['parent_id'],
                    'name' => $agency['name'] ?? 'نامشخص',
                    'success' => false,
                    'message' => 'شماره موبایل وجود ندارد'
                ];
            }

            // پیدا کردن یا ایجاد Contact
            $contactResult = $this->getOrCreateContact($agency, $mobile);
            
            if (!$contactResult['success']) {
                return [
                    'agency_id' => $agency['parent_id'],
                    'name' => $agency['name'] ?? 'نامشخص',
                    'success' => false,
                    'message' => 'خطا در Contact: ' . $contactResult['message']
                ];
            }

            // ایجاد Service Center
            $serviceCenterResult = $this->createServiceCenter($agency, $contactResult['contact_id']);
            
            if ($serviceCenterResult['success']) {
                // ذخیره CRM Service Center ID در دیتابیس
                $this->saveCrmServiceCenterId($agency['parent_id'], $serviceCenterResult['service_center_id']);
                
                return [
                    'agency_id' => $agency['parent_id'],
                    'name' => $agency['name'] ?? 'نامشخص',
                    'success' => true,
                    'message' => 'مرکز با موفقیت ایجاد شد',
                    'contact_id' => $contactResult['contact_id'],
                    'service_center_id' => $serviceCenterResult['service_center_id']
                ];
            } else {
                return [
                    'agency_id' => $agency['parent_id'],
                    'name' => $agency['name'] ?? 'نامشخص',
                    'success' => false,
                    'message' => 'خطا در Service Center: ' . $serviceCenterResult['message']
                ];
            }

        } catch (\Exception $e) {
            Log::error("Exception processing agency", [
                'agency_id' => $agency['parent_id'] ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'agency_id' => $agency['parent_id'] ?? 'unknown',
                'name' => $agency['name'] ?? 'نامشخص',
                'success' => false,
                'message' => 'خطا در پردازش: ' . $e->getMessage()
            ];
        }
    }

    /**
     * پیدا کردن یا ایجاد Contact
     */
    private function getOrCreateContact($agency, $mobile)
    {
        try {
            // جستجو برای Contact موجود
            $contactId = $this->findContactByMobile($mobile);
            
            if ($contactId) {
                return [
                    'success' => true,
                    'contact_id' => $contactId,
                    'message' => 'Contact موجود یافت شد'
                ];
            }

            // ایجاد Contact جدید
            return $this->createContact($agency, $mobile);
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'خطا در getOrCreateContact: ' . $e->getMessage()
            ];
        }
    }

    /**
     * جستجو برای Contact بر اساس موبایل
     */
    private function findContactByMobile($mobile)
    {
        try {
            $response = $this->crmClient->request("contacts", "GET", [
                '$select' => 'contactid',
                '$filter' => "mobilephone eq '$mobile'"
            ]);

            if ($response->successful()) {
                $body = $response->json();
                if (!empty($body['value'])) {
                    return $body['value'][0]['contactid'];
                }
            }

            return null;
        } catch (\Exception $e) {
            Log::error("Error finding contact", ['mobile' => $mobile, 'error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * ایجاد Contact جدید
     */
    private function createContact($agency, $mobile)
    {
        try {
            $contactData = [
                'firstname' => $agency['firstname'] ?? '',
                'lastname' => $agency['lastname'] ?? '',
                'fullname' => $agency['name'] ?? '',
                'mobilephone' => $mobile,
                'telephone1' => $this->cleanMobile($agency['phone'] ?? ''),
                'address1_line1' => $agency['address'] ?? '',
                'createdon' => now()->toIso8601String()
            ];

            // حذف فیلدهای خالی
            $contactData = array_filter($contactData, function($value) {
                return $value !== '' && $value !== null;
            });

            $response = $this->crmClient->request("contacts", "POST", $contactData);
            
            if ($response->successful()) {
                $contactId = $this->extractEntityId($response);
                return [
                    'success' => true,
                    'contact_id' => $contactId,
                    'message' => 'Contact جدید ایجاد شد'
                ];
            }

            Log::error("Failed to create contact", [
                'agency_id' => $agency['parent_id'],
                'response_status' => $response->status(),
                'response_body' => $response->body()
            ]);

            return [
                'success' => false,
                'message' => 'خطا در ایجاد Contact: ' . $response->status() . ' - ' . $response->body()
            ];
        } catch (\Exception $e) {
            Log::error("Exception creating contact", [
                'agency_id' => $agency['parent_id'],
                'error' => $e->getMessage()
            ]);
            return [
                'success' => false,
                'message' => 'Exception در ایجاد Contact: ' . $e->getMessage()
            ];
        }
    }

    /**
     * ایجاد Service Center
     */
    private function createServiceCenter($agency, $contactId)
    {
        try {
            $statecode = $this->formatEnable($agency['enable'] ?? 1);
            $statuscode = $agency['fin_green'] === 'ok' ? 0 : 1;
            $serviceCenterData = [
                'rhs_name' => $agency['name'] ?? '',
                'rhs_fullname' => $agency['firstname'] ?? '',
                'rhs_lastname' => $agency['lastname'] ?? '',
                'rhs_mobile' => $this->cleanMobile($agency['mobile'] ?? ''),
                'rhs_phone' => $this->cleanMobile($agency['phone'] ?? ''),
                'rhs_centercode' => $agency['agency_code'] ?? '',
                'rhs_address' => $agency['address'] ?? '',
                'rhs_nationalcode' => $this->cleanMobile($agency['national_id'] ?? ''),
                'rhs_province' => $agency['province'] ?? '',
                'rhs_city' => $agency['city'] ?? '',
                'rhs_description' => $agency['description'] ?? '',
                // فیلدهای اضافی که کاربر درخواست کرده
                'rhs_row' => $agency['customer_type'] ?? '',
                'rhs_yearofreceivingthecode' => $agency['receiving_code_year'] ?? '',
                'rhs_guildnumber' => $agency['guild_number'] ?? '',
                'rhs_dateofissue' => $this->formatDate($agency['issued_date'] ?? ''),
                'rhs_expirydate' => $this->formatDate($agency['exp_date'] ?? ''),
                'rhs_postalcode' => $agency['postal_code'] ?? '',
                'rhs_location' => $agency['location'] ?? '',
                'rhs_debtdescription' => $agency['fin_details'] ?? '',
                'statecode' => $statecode,
                'statuscode' => $statuscode
            ];
            
            // اضافه کردن lookup فقط اگر contactId وجود داشت
            if ($contactId) {
                $serviceCenterData['new_contact@odata.bind'] = "/contacts($contactId)";
            }

            // حذف فیلدهای خالی
            $serviceCenterData = array_filter($serviceCenterData, function($value) {
                return $value !== '' && $value !== null;
            });

            $response = $this->crmClient->request("rhs_servicecenters", "POST", $serviceCenterData);
            
            if ($response->successful()) {
                $serviceCenterId = $this->extractEntityId($response);
                return [
                    'success' => true,
                    'service_center_id' => $serviceCenterId,
                    'message' => 'Service Center ایجاد شد'
                ];
            }

            Log::error("Failed to create service center", [
                'agency_id' => $agency['parent_id'],
                'contact_id' => $contactId,
                'response_status' => $response->status(),
                'response_body' => $response->body()
            ]);

            return [
                'success' => false,
                'message' => 'خطا در ایجاد Service Center: ' . $response->status() . ' - ' . $response->body()
            ];
        } catch (\Exception $e) {
            Log::error("Exception creating service center", [
                'agency_id' => $agency['parent_id'],
                'contact_id' => $contactId,
                'error' => $e->getMessage()
            ]);
            return [
                'success' => false,
                'message' => 'Exception در ایجاد Service Center: ' . $e->getMessage()
            ];
        }
    }

    /**
     * ذخیره CRM Service Center ID در دیتابیس
     */
    private function saveCrmServiceCenterId($parentId, $serviceCenterId)
    {
        try {
            // بررسی اینکه آیا رکورد با key 'crm_service_center_id' وجود دارد یا نه
            $existingRecord = DB::table('agency_info')
                ->where('parent_id', $parentId)
                ->where('key', 'crm_service_center_id')
                ->first();

            if ($existingRecord) {
                // به‌روزرسانی رکورد موجود
                DB::table('agency_info')
                    ->where('parent_id', $parentId)
                    ->where('key', 'crm_service_center_id')
                    ->update([
                        'value' => $serviceCenterId,
                        'updated_at' => now()
                    ]);
                
                Log::info("Updated CRM Service Center ID", [
                    'parent_id' => $parentId,
                    'service_center_id' => $serviceCenterId
                ]);
            } else {
                // ایجاد رکورد جدید
                DB::table('agency_info')->insert([
                    'parent_id' => $parentId,
                    'key' => 'crm_service_center_id',
                    'value' => $serviceCenterId,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
                
                Log::info("Created new CRM Service Center ID record", [
                    'parent_id' => $parentId,
                    'service_center_id' => $serviceCenterId
                ]);
            }

            return true;
        } catch (\Exception $e) {
            Log::error("Failed to save CRM Service Center ID", [
                'parent_id' => $parentId,
                'service_center_id' => $serviceCenterId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * دریافت CRM Service Center ID از دیتابیس
     */
    private function getCrmServiceCenterId($parentId)
    {
        try {
            $record = DB::table('agency_info')
                ->where('parent_id', $parentId)
                ->where('key', 'crm_service_center_id')
                ->first();

            return $record ? $record->value : null;
        } catch (\Exception $e) {
            Log::error("Failed to get CRM Service Center ID", [
                'parent_id' => $parentId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * دریافت اطلاعات مراکز
     */
    private function getAgencyData($allowedAgencyCodes = null)
    {
        // اگر پارامتر ارسال نشده، از کانفیگ استفاده کن
        if ($allowedAgencyCodes === null) {
            $filterEnabled = config('agency_crm_filter.filter_enabled', false);
            if ($filterEnabled) {
                $allowedAgencyCodes = config('agency_crm_filter.allowed_agency_codes', []);
            }
        }

        $desiredKeys = [
            'customer_type',
            'firstname',
            'lastname',
            'receiving_code_year',
            'national_id',
            'agency_code',
            'address',
            'guild_number',
            'mobile',
            'phone',
            'issued_date',
            'exp_date',
            'description',
            'province',
            'city',
            'postal_code',
            'enable',
            'location',
            'fin_green',
            'fin_details'
        ];

        // گرفتن اطلاعات شهرها و استان‌ها
        $cities = DB::table('cities')->get()->keyBy('id');
        $provinces = DB::table('new_provinces')->get()->keyBy('id');

        // گرفتن اطلاعات key-value
        $rawData = DB::table('agency_info')
            ->whereIn('key', $desiredKeys)
            ->get();

        // گروه‌بندی بر اساس parent_id
        $grouped = $rawData->groupBy('parent_id');

        // ساختن خروجی نهایی
        $structured = $grouped->map(function ($items, $parentId) use ($desiredKeys, $cities, $provinces) {
            $row = ['parent_id' => $parentId];

            foreach ($desiredKeys as $key) {
                $value = $items->firstWhere('key', $key)->value ?? null;

                if ($key === 'city') {
                    $cityId = intval($value);
                    $value = $cities[$cityId]->city ?? 'نامشخص';
                }

                if ($key === 'province') {
                    $provinceId = intval($value);
                    $value = $provinces[$provinceId]->name ?? 'نامشخص';
                }

                $row[$key] = $value;
            }

            // ایجاد نام کامل
            $row['name'] = trim(($row['firstname'] ?? '') . ' ' . ($row['lastname'] ?? ''));

            return $row;
        })->values();

        // فیلتر کردن بر اساس کدهای مرکز مجاز (اگر تعریف شده باشد)
        if ($allowedAgencyCodes !== null && is_array($allowedAgencyCodes) && !empty($allowedAgencyCodes)) {
            $structured = $structured->filter(function ($agency) use ($allowedAgencyCodes) {
                return in_array($agency['agency_code'], $allowedAgencyCodes);
            })->values();
        }

        return $structured->toArray();
    }

    /**
     * تمیز کردن شماره موبایل
     */
    private function cleanMobile($mobile)
    {
        if (!$mobile) return '';
        
        // تبدیل اعداد فارسی به انگلیسی
        $persianNumbers = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        $englishNumbers = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        
        return str_replace($persianNumbers, $englishNumbers, $mobile);
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

    /**
     * فرمت کردن وضعیت enable برای statecode
     */
    private function formatEnable($enable)
    {
        return match ($enable) {
            '1', 1, 'true', true => 0, // فعال = 0 در CRM
            '0', 0, 'false', false => 1, // غیرفعال = 1 در CRM
            default => 0
        };
    }

    /**
     * فرمت کردن fin_green برای statuscode
     */
    private function formatFinGreen($finGreen)
    {
        return match (strtolower(trim($finGreen ?? ''))) {
            'ok' => 1, // فعال = 1 در CRM
            'not ok', 'notok', 'not_ok' => 2, // غیرفعال = 2 در CRM
            default => 1 // پیش‌فرض فعال
        };
    }

    /**
     * نمایش پیش‌نمایش مراکز فیلتر شده
     */
    public function previewFilteredAgencies()
    {
        try {
            echo "<h2>پیش‌نمایش مراکز فیلتر شده</h2>";
            
            // دریافت تنظیمات از کانفیگ
            $filterEnabled = config('agency_crm_filter.filter_enabled', false);
            $allowedAgencyCodes = $filterEnabled ? config('agency_crm_filter.allowed_agency_codes', []) : null;
            
            if (!$filterEnabled) {
                echo "<p style='color: orange;'>فیلتر غیرفعال است - همه مراکز نمایش داده می‌شوند</p>";
            } else {
                echo "<p style='color: blue;'>فیلتر فعال است - تعداد کدهای مجاز: " . count($allowedAgencyCodes) . "</p>";
            }
            
            $agencies = $this->getAgencyData($allowedAgencyCodes);
            $totalCount = count($agencies);
            
            if ($totalCount == 0) {
                echo "<p style='color: red;'>هیچ مرکزی با کدهای تعریف شده یافت نشد</p>";
                return;
            }
            
            echo "<h3>مراکز یافت شده ($totalCount مرکز):</h3>";
            echo "<table border='1' style='border-collapse: collapse; width: 100%; font-family: Arial;'>";
            echo "<tr style='background-color: #f0f0f0;'>";
            echo "<th style='padding: 8px;'>ردیف</th>";
            echo "<th style='padding: 8px;'>کد مرکز</th>";
            echo "<th style='padding: 8px;'>نام</th>";
            echo "<th style='padding: 8px;'>موبایل</th>";
            echo "<th style='padding: 8px;'>استان</th>";
            echo "<th style='padding: 8px;'>شهر</th>";
            echo "<th style='padding: 8px;'>وضعیت در CRM</th>";
            echo "</tr>";
            
            $foundCodes = [];
            foreach ($agencies as $index => $agency) {
                $foundCodes[] = $agency['agency_code'];
                $crmId = $this->getCrmServiceCenterId($agency['parent_id']);
                $crmStatus = $crmId ? "موجود (ID: $crmId)" : "موجود نیست";
                $crmColor = $crmId ? 'green' : 'red';
                
                echo "<tr>";
                echo "<td style='padding: 8px;'>" . ($index + 1) . "</td>";
                echo "<td style='padding: 8px;'><strong>{$agency['agency_code']}</strong></td>";
                echo "<td style='padding: 8px;'>{$agency['name']}</td>";
                echo "<td style='padding: 8px;'>{$agency['mobile']}</td>";
                echo "<td style='padding: 8px;'>{$agency['province']}</td>";
                echo "<td style='padding: 8px;'>{$agency['city']}</td>";
                echo "<td style='padding: 8px; color: $crmColor;'>$crmStatus</td>";
                echo "</tr>";
            }
            echo "</table>";
            
            // نمایش کدهای پیدا نشده
            if ($filterEnabled && $allowedAgencyCodes) {
                $notFoundCodes = array_diff($allowedAgencyCodes, $foundCodes);
                if (!empty($notFoundCodes)) {
                    echo "<div style='margin: 20px 0; padding: 15px; border: 1px solid orange; background-color: #fff3cd;'>";
                    echo "<h4>⚠ کدهای پیدا نشده (" . count($notFoundCodes) . " کد):</h4>";
                    echo "<p style='color: red;'>" . implode(', ', $notFoundCodes) . "</p>";
                    echo "<p><small>این کدها در دیتابیس وجود ندارند یا اطلاعات کاملی ندارند</small></p>";
                    echo "</div>";
                }
                
                echo "<div style='margin: 20px 0; padding: 15px; border: 1px solid #ddd; background-color: #f9f9f9;'>";
                echo "<h4>خلاصه:</h4>";
                echo "<ul>";
                echo "<li><strong>کل کدهای تعریف شده:</strong> " . count($allowedAgencyCodes) . "</li>";
                echo "<li><strong>کدهای پیدا شده:</strong> <span style='color: green;'>" . count($foundCodes) . "</span></li>";
                echo "<li><strong>کدهای پیدا نشده:</strong> <span style='color: red;'>" . count($notFoundCodes) . "</span></li>";
                echo "<li><strong>درصد موفقیت:</strong> " . round((count($foundCodes) / count($allowedAgencyCodes)) * 100, 2) . "%</li>";
                echo "</ul>";
                echo "</div>";
            }
            
        } catch (\Exception $e) {
            echo "<h2 style='color: red;'>خطا در نمایش پیش‌نمایش:</h2>";
            echo "<p>" . $e->getMessage() . "</p>";
        }
    }

    /**
     * استخراج Entity ID از response header
     */
    private function extractEntityId($response)
    {
        $entityIdHeader = $response->header('OData-EntityId');
        if ($entityIdHeader) {
            preg_match('/\(([^)]+)\)/', $entityIdHeader, $matches);
            return $matches[1] ?? null;
        }
        return null;
    }
}