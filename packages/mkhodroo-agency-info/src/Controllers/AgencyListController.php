<?php

namespace Mkhodroo\AgencyInfo\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Hekmatinasser\Verta\Verta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Mkhodroo\AgencyInfo\Models\AgencyInfo;
use Mkhodroo\Cities\Controllers\CityController;
use Mkhodroo\Cities\Controllers\ProvinceController;
use Mkhodroo\Cities\Models\NewProvince;
use Mkhodroo\DateConvertor\Controllers\SDate;

class AgencyListController extends Controller
{
    public static function view()
    {
        // return AgencyInfo::groupBy('key')->pluck('key')->toArray();
        // return self::getKeys();
        $provinces = NewProvince::all();
        $last_referrals = GetAgencyController::getUniqueValueOfKey('last_referral');
        $new_statuses = GetAgencyController::getUniqueValueOfKey('new_status');
        return view('AgencyView::list')->with([
            'cols' => self::getKeys(),
            'provinces' => $provinces,
            'last_referrals' => $last_referrals,
            'new_statuses' => $new_statuses,
        ]);
    }

    public static function getKeys(){
        $keys = AgencyInfo::groupBy('key')->pluck('key');
        // $keys[] = 'province';
        // $keys[] = 'city';
        return $keys;
    }

    public static function list()
    {
        return [
            'data' => []
        ];
    }
    public static function filterList(Request $r)
    {

        $main_field_search = config('agency_info.main_field_name'). "_search";
        $main_field = config('agency_info.main_field_name');
        $parent_ids = [];
        if($r->$main_field_search){
            $parent_ids[] = AgencyInfo::where('key', $main_field)->where('value', $r->$main_field_search)->pluck('parent_id')->toArray();
        }
        if($r->province_search){
            $parent_ids[] = AgencyInfo::where('key', 'province')->where('value', $r->province_search)->pluck('parent_id')->toArray();
        }
        if($r->last_referral_search){
            $parent_ids[] = AgencyInfo::where('key', 'last_referral')->where('value', $r->last_referral_search)->pluck('parent_id')->toArray();
        }
        if($r->new_status_search){
            $parent_ids[] = AgencyInfo::where('key', 'new_status')->where('value', $r->new_status_search)->pluck('parent_id')->toArray();
        }
        if($r->field_value){
            $parent_ids[] = AgencyInfo::where('value' , 'like', "%". $r->field_value. "%")->pluck('parent_id')->toArray();

        }

        $advanced_filters = collect($r->get('advanced_filters', []))
            ->map(function ($filter) {
                $filter['key'] = isset($filter['key']) ? trim($filter['key']) : '';
                $filter['value'] = isset($filter['value']) ? trim($filter['value']) : '';
                $filter['condition'] = isset($filter['condition']) ? trim($filter['condition']) : 'equals';
                $filter['boolean'] = isset($filter['boolean']) ? strtolower(trim($filter['boolean'])) : 'and';
                return $filter;
            })
            ->filter(function ($filter) {
                return $filter['key'] !== '' && $filter['value'] !== '';
            })
            ->values();

        if ($advanced_filters->isNotEmpty()) {
            $advanced_parent_ids = [];
            $advanced_filters->each(function ($filter, $index) use (&$advanced_parent_ids) {
                $condition = $filter['condition'] === 'contains' ? 'contains' : 'equals';
                $boolean = $filter['boolean'] === 'or' ? 'or' : 'and';

                $query = AgencyInfo::where('key', $filter['key']);

                if ($condition === 'contains') {
                    $query->where('value', 'like', "%" . $filter['value'] . "%");
                } else {
                    $query->where('value', $filter['value']);
                }

                $current_parent_ids = $query->pluck('parent_id')->toArray();

                if ($index === 0) {
                    $advanced_parent_ids = $current_parent_ids;
                    return;
                }

                if ($boolean === 'or') {
                    $advanced_parent_ids = array_values(array_unique(array_merge($advanced_parent_ids, $current_parent_ids)));
                } else {
                    $advanced_parent_ids = array_values(array_intersect($advanced_parent_ids, $current_parent_ids));
                }
            });

            $parent_ids[] = $advanced_parent_ids;
        }

        $count = count($parent_ids);
        if($count > 1){
            $intersects = $parent_ids[0];
            foreach($parent_ids as $parent_id){
                $intersects = array_intersect($intersects, $parent_id);
            }
            $parent_ids = $intersects;
        }
        if($count === 1){
            $parent_ids = $parent_ids[0];
        }
        if($count === 0){
            return ['data' => []];
        }
        $parent_ids = array_values(array_unique($parent_ids));
        if(empty($parent_ids)){
            return ['data' => []];
        }
        $agencies = AgencyInfo::whereIn('id', $parent_ids)->groupBy('parent_id')->get();

        $key_indexes = explode(',', $r->cols);
        $agencies =  $agencies->each(function ($agency) use($key_indexes) {
            $agency = self::makeCustomFields($agency, $key_indexes);
        });
        return ['data' => $agencies];
    }

    public static function makeCustomFields(object $agency, array $cols) {
        $keys = self::getKeys();
        foreach($cols as $key_index){
            $key = $keys[$key_index];
            if($key === 'province'){
                $agency->$key = ProvinceController::getById(GetAgencyController::getByKey($agency->parent_id, $key)?->value)?->name;
            }elseif($key === 'city'){
                $agency->$key = CityController::getById(GetAgencyController::getByKey($agency->parent_id, 'city')?->value)?->city;
            }else{
                $agency->$key = __(GetAgencyController::getByKey($agency->parent_id, $key)?->value);
            }
        }
        $agency->fin_green = __(GetAgencyController::getByKey($agency->parent_id, 'fin_green')?->value);
        return $agency;
    }


    public static function getValidAgencies($type = 'agency')
    {
        //  -----  jalali date to timestamp
        // $expDateFix = self::expDateFix();

        $parent_ids = DB::table('agency_info as a1')
        ->join('agency_info as a2', function ($join) {
            $join->on('a1.parent_id', '=', 'a2.parent_id')
                ->where('a1.key', config('agency_info.main_field_name'))
                ->where('a2.key', 'exp_date');
        })
        ->where('a1.value', $type)
        ->where('a2.value', '>', Carbon::now()->timestamp)
        ->pluck('a1.parent_id');

        // dd($parent_ids);
        // $parent_ids = AgencyInfo::where('key', config('agency_info.main_field_name'))->where('value', $type)->pluck('id');


        // $parent_ids = AgencyInfo::whereIn('parent_id', $parent_ids)->where('key', 'enable')->where('value', '1')->pluck('parent_id');
        // $exp_dates = AgencyInfo::whereIn('parent_id', $parent_ids)->where('key', 'exp_date')->whereNotNull('value')->where('value', '!=', '')->get();
        // $parent_ids = [];
        // $sDate = new SDate();
        $agencies = AgencyInfo::whereIn('id', $parent_ids)->groupBy('parent_id')->get();
        // return $agencies;
        $key_indexes = explode(',', '0,1,2');
        $agencies =  $agencies->each(function ($agency) use($key_indexes) {
            $agency = self::makeCustomFields($agency, $key_indexes);
        });
        return ['data' => $agencies];
        $agencies = [];
        foreach($parent_ids as $parent_id){
            // $exp = $sDate->toGrDate($exp_date->value);
            // $GregorianExpDate = $sDate->gregorianToCarbon($exp);
            // $now_carbon = Carbon::now();
            // $diff = $now_carbon->diffInDays($exp, false);
            // echo $diff;
            // if($diff >= 0){
                $parent_ids[] = $parent_id;
                $agencies[] = [
                    'agency_code' => GetAgencyController::getByKey($parent_id, 'agency_code')?->value,
                    'name' => GetAgencyController::getByKey($parent_id, 'firstname')?->value,
                    'province' => CityController::getById(GetAgencyController::getByKey($parent_id, 'province')?->value)->province,
                    'city' => CityController::getById(GetAgencyController::getByKey($parent_id, 'province')?->value)->city,
                    'address' => GetAgencyController::getByKey($parent_id, 'address')?->value,
                    'phone' => GetAgencyController::getByKey($parent_id, 'phone')?->value,
                    'mobile' => GetAgencyController::getByKey($parent_id, 'mobile')?->value,
                    'exp_date' => GetAgencyController::getByKey($parent_id, 'exp_date')?->value,
                ];
            // }
        }
        return json_encode($agencies);

    }

}
