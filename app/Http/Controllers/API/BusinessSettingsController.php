<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use App\Models\BussinessSetting;
use App\Models\Page;
use App\Services\PageUpdateService;

class BusinessSettingsController extends Controller
{

    protected $pageUpdateService;
    public function __construct(PageUpdateService $pageUpdateService)
    {
        $this->pageUpdateService = $pageUpdateService;
    }
    public function general_setting(){
        $generalSetting  = BussinessSetting::where('value', '!=', null)->get();
        return response()->json(['status_code' => 200, 'message' => 'General Settings', 'data' => $generalSetting], 200);
    }
    public function update(Request $request)
    {
        try {
            foreach ($request->types as $key => $type) {
                if($type == 'site_name'){
                    $this->overWriteEnvFile('APP_NAME', $request[$type]);
                }
                if($type == 'timezone'){
                    $this->overWriteEnvFile('APP_TIMEZONE', $request[$type]);
                }
                else {
                    $lang = null;
                    if(gettype($type) == 'array'){
                        $lang = array_key_first($type);
                        $type = $type[$lang];
                        $business_settings = BussinessSetting::where('type', $type)->where('lang',$lang)->first();
                    }else{
                        $business_settings = BussinessSetting::where('type', $type)->first();
                    }

                    if($business_settings!=null){
                        if(gettype($request[$type]) == 'array'){
                            $business_settings->value = json_encode($request[$type]);
                        }
                        else {
                            $business_settings->value = $request[$type];
                        }
                        $business_settings->lang = $lang;
                        $business_settings->save();
                    }
                    else{
                        $business_settings = new BussinessSetting;
                        $business_settings->type = $type;
                        if(gettype($request[$type]) == 'array'){
                            $business_settings->value = json_encode($request[$type]);
                        }
                        else {
                            $business_settings->value = $request[$type];
                        }
                        $business_settings->lang = $lang;
                        $business_settings->save();
                    }
                }
            }

            $pages = Page::with(['theme' => function ($query) {
                $query->where('status', 1);
            }])->pluck('slug')->toArray();
            if (count($pages) > 0) {
                $languages = all_languages();
                foreach($languages as $lang){
                    foreach ($pages as $key => $slug) {
                    $this->pageUpdateService->updatePage($slug, $lang->app_language_code);
                    }
                }
                
            }
            Artisan::call('cache:clear');
            return response()->json(['status_code' => 200, 'message' => 'General Settings Updated Successfully'], 200);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json(['status_code' => 500, 'message' => $th->getMessage()], 500);
        }
    }
}
