<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Symfony\Component\HttpFoundation\Response;
use Mail;
use Auth;
use Session;
use Hash;
use DB;
use App\Models\Brand;
use App\Models\Brand_translation;
use App\Models\Menu;
use App\Models\Permission_assign;
use App\Models\Language;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon; 
use Illuminate\Support\Str;
use App\Services\PermissionService;
use App\Traits\MessageTrait;
use DeepCopy\f001\B;

class BrandController extends Controller
{
    
    use MessageTrait;
    protected $permissionService;

    public function __construct(PermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }


    public function get_brand(){

        try{

            $menuUuid = request()->header('menu-uuid');
            $permissions = $this->permissionService->checkPermissions($menuUuid);
            $get_all_brand = Brand::orderBy('id', 'desc');
            if ($permissions['view']) {
                if (!$permissions['viewglobal']) {
                    $get_all_brand = $get_all_brand->where('auth_id', Auth::user()->uuid);
                }
            }else{
                if (Auth::user()->hasPermission('viewglobal')) {
                    $get_all_brand = $get_all_brand;
                } else {
                    return response()->json([
                        'message' => 'You do not have permission to view this menu'
                    ], Response::HTTP_FORBIDDEN);
                }
            }
            $get_all_brand = $get_all_brand->get();

            return response()->json([
                'status_code'=>200,
                'permissions' => $permissions,
                'data'=>$get_all_brand
            ],200);

        }catch (\Exception $e) { 
            // Handle general exceptions
            return response()->json([

                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $this->get_message('server_error'),
                

            ], Response::HTTP_INTERNAL_SERVER_ERROR); // 500 Internal Server Error
        } 

    }


    public function add_brand(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'brand' => 'required|max:255',
            'slug' => '',
            'logo' => '',
            'order_level' => '',
            'description' => '',
            'meta_title' => '',
            'meta_description' => '',
            'og_title'=>'',
            'og_description'=>'',
            'og_image'=>'',
            'x_title'=>'',
            'x_description'=>'',
            'x_image'=>'',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'errors' => strval($validator->errors())
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            // Create Brand
            $brand = new Brand();
            $brand->uuid = Str::uuid();
            $brand->auth_id = Auth::user()->uuid;
            $brand->brand = $request->brand;
            $brand->logo = $request->logo;
            $brand->order_level = $request->order_level ?: 0;
            $brand->description = $request->description;
            $brand->meta_title = $request->meta_title;
            $brand->meta_description = $request->meta_description;
            $brand->og_title = $request->og_title;
            $brand->og_description = $request->og_description;
            $brand->og_image = $request->og_image;
            $brand->x_title = $request->x_title;
            $brand->x_description = $request->x_description;
            $brand->x_image = $request->x_image;
        
            if ($request->slug) {
                $brand->slug = preg_replace('/[^A-Za-z0-9\-]/', '', str_replace(' ', '-', $request->slug));
            } else {
                $brand->slug = preg_replace('/[^A-Za-z0-9\-]/', '', str_replace(' ', '-', $request->brand)) . '-' . Str::random(5);
            }
        
            $brand->save();
        
            return response()->json([
                'status_code' => 200,
                'message' => $this->get_message('update'),
            ], 200);
        
        }catch (\Illuminate\Database\QueryException $e) {
            
            if ($e->errorInfo[1] == 1062) { // Error code for duplicate entry
                return response()->json([
                    'status_code' => 409,
                    'message' => 'Duplicate entry: The brand already exists.',
                ], 409);
            }

            return response()->json([
                'status_code' => 500,
                // 'message' => $e->getMessage(),
                'message' => $this->get_message('server_error'),
            ], 500);

        } catch (\Throwable $th) {

            return response()->json([
                'status_code' => 500,
                // 'message' => $th->getMessage(),
                'message' => $this->get_message('server_error'),
            ], 500);

        }
        
    }


    public function edit_brand($uuid){

        try {
            
            $edit_brand_by_id = Brand::where('uuid', $uuid)->first();

            if(!$edit_brand_by_id)
            {
                return response()->json([
                    'status_code' => Response::HTTP_NOT_FOUND,
                    'message' => $this->get_message('not_found'),
                ], Response::HTTP_NOT_FOUND);
            }
            
            $get_active_language = Language::where('status', '1')->get();

            $now = Carbon::now();
            $auth_id = Auth::user()->uuid;

            if(count($get_active_language) > 0){

                foreach($get_active_language as $key => $language){
                    
                    $check_brand_translation = Brand_translation::where('brand_id', $edit_brand_by_id->id)
                    ->where('language_id', $language->id)
                    ->where('status', '1')->first();

                    if($check_brand_translation)
                    {
                        
                       

                    }
                    else{

                        $save_brand_translation = Brand_translation::insert([
                            ['uuid' => Str::uuid(), 'brand_id' => $edit_brand_by_id->id, 'brand' => $edit_brand_by_id->brand , 'language_id' => $language->id , 'lang' => $language->app_language_code , 'auth_id' => $auth_id , 'created_at' => $now, 'updated_at' => $now],
                        ]);

                    }


                }


            }

            $brand_translations = Brand_translation::where('brand_id', $edit_brand_by_id->id)
            ->where('brand_translations.status', '1')
            ->join('languages', 'brand_translations.language_id', '=', 'languages.id')
            ->select('languages.code as language_code', 'languages.name as language_name' , 'languages.flag as flag' , 'languages.rtl as dir', 'brand_translations.*')
            ->get();

            
            if ($edit_brand_by_id) {

                $edit_brand_by_id->translations = $brand_translations;
       
                return response()->json([

                    'status_code' => Response::HTTP_OK,
                    'data' => $edit_brand_by_id,

                ], Response::HTTP_OK);


            }else{

                return response()->json([

                    'status_code' => Response::HTTP_NOT_FOUND,
                    'message' => $this->get_message('not_found'),

                ], Response::HTTP_NOT_FOUND);

            }


        }catch(\Exception $e) { 
            // Handle general exceptions
            return response()->json([

                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                // 'message' => $this->get_message('server_error'),
                'message' => $e->getMessage(),

            ], Response::HTTP_INTERNAL_SERVER_ERROR); // 500 Internal Server Error
        }

    }


    public function update_brand(Request $request)
    {

        $uuid = request()->header('uuid');

        try {
            // Find the brand
            $brand = Brand::where('uuid', $uuid)->first();
            // Update brand fields
            $brand->brand = $request->brand;
            $brand->logo = $request->logo;
            $brand->order_level = $request->order_level;
            $brand->description = $request->description;
            $brand->meta_title = $request->meta_title;
            $brand->meta_description = $request->meta_description;
            $brand->og_title = $request->og_title;
            $brand->og_description = $request->og_description;
            $brand->og_image = $request->og_image;
            $brand->x_title = $request->x_title;
            $brand->x_description = $request->x_description;
            $brand->x_image = $request->x_image;
        

            if ($request->slug) {
                $brand->slug = preg_replace('/[^A-Za-z0-9\-]/', '', str_replace(' ', '-', $request->slug));
            } else {
                $brand->slug = preg_replace('/[^A-Za-z0-9\-]/', '', str_replace(' ', '-', $request->brand)) . '-' . Str::random(5);
            }

            $brand->save();

            $updatedTranslations = false;

            foreach ($request->all() as $key => $value) {
                
                if (strpos($key, 'name_') === 0) {
                    
                    $languageCode = substr($key, 5);
            
                    $languageId = DB::table('languages')->where('code', $languageCode)->value('id');
            
                    if($languageId){
                        
                        Brand_translation::where('language_id', $languageId)
                        ->where('brand_id', $brand->id)
                        ->update(['brand' => $value]);

                        $updatedTranslations = true;
                    }

                }

            }


            foreach ($request->all() as $key => $value) {
                
                if ($request->hasFile($key)) {

                    if (strpos($key, 'logo_') === 0) {
                        $languageCode = substr($key, 5);
                        $languageId = DB::table('languages')->where('code', $languageCode)->value('id');
            
                        if ($languageId) {
                            $file = $request->file($key);
                            $fileName = time() . '_' . $file->getClientOriginalName();
                            $folderName = '/upload_files/logo/';
                            $destinationPath = public_path() . $folderName;
            
                            if (!file_exists($destinationPath)) {
                                mkdir($destinationPath, 0755, true);
                            }
            
                            $file->move($destinationPath, $fileName);
            
                            Brand_translation::where('language_id', $languageId)
                            ->where('brand_id', $brand->id)
                            ->update(['logo' => $folderName . $fileName]);

                            $updatedTranslations = true;
                        }
                    }

                }

            }


            foreach ($request->all() as $key => $value) {
                
                if (strpos($key, 'description_') === 0) {
                    
                    $languageCode = substr($key, 12);
            
                    $languageId = DB::table('languages')->where('code', $languageCode)->value('id');
            
                    if($languageId){
                        
                        Brand_translation::where('language_id', $languageId)
                        ->where('brand_id', $brand->id)
                        ->update(['description' => $value]);

                        $updatedTranslations = true;
                    }

                }

            }


            if ($updatedTranslations) {
               
                $get_active_language = Language::where('status', '1')->where('is_default', '1')->first();
                $get_role_trans_by_def_lang = Brand_translation::where('brand_id', $brand->id)
                ->where('language_id', $get_active_language->id)
                ->first();
    
                $upd_brand2 = DB::table('brands')
                ->where('id', $brand->id)
                ->update([
                    'brand' => $get_role_trans_by_def_lang->brand,
                ]);

            }

            return response()->json([
                'status_code' => 200,
                'message' => 'Brand has been updated',
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $th->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    public function delete_brand($uuid){

        try{

            $del_brand = Brand::where('uuid', $uuid)->first();
            
            if(!$del_brand)
            {
                
                return response()->json([

                    'status_code' => Response::HTTP_NOT_FOUND,
                    'message' => $this->get_message('not_found'),

                ], Response::HTTP_NOT_FOUND);


            }else{

                $delete_brand = Brand::destroy($del_brand->id);

                if($delete_brand){
                    
                    $del_brand_translation = Brand_translation::where('brand_id', $del_brand->id)->delete();

                    return response()->json([
                        
                        'status_code' => Response::HTTP_OK,
                        'message' => $this->get_message('delete'),
                    
                    ], Response::HTTP_OK);
    
                }

            }


        }catch (\Exception $e) { 
            // Handle general exceptions
            return response()->json([

                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $this->get_message('server_error'),
                

            ], Response::HTTP_INTERNAL_SERVER_ERROR); // 500 Internal Server Error
        } 
        
    }

    
    public function updateCategoryStatus(Request $request, string $id)
    {
        $request->validate([
            'status' => 'required|in:0,1', // Ensure status is either 0 or 1
        ]);

        try {
            // Find the category by UUID and active status
            $brand = Brand::where('uuid', $id)->first();

            if ($brand) {
                // Update the status
                $brand->status = $request->status;
                $brand->save();

                return response()->json([
                    'status_code' => 200,
                    'message' => $this->get_message('update'),
                ], 200);
            } else {
                return response()->json([
                    'status_code' => 404,
                    'message' => $this->get_message('not_found'),
                ], 404);
            }
        } catch (\Throwable $e) {
            return response()->json([
                'status_code' => 500,
                'message' => $this->get_message('server_error'),
            ], 500);
        }

    }


    public function updateCategoryFeatured(Request $request, string $id)
    {
        $request->validate([
            'featured' => 'required|in:0,1',
        ]);

        try {
            // Find the category by UUID and active featured
            $brand = Brand::where('uuid', $id)->first();

            if ($brand) {
                // Update the featured
                $brand->featured = $request->featured;
                $brand->save();

                return response()->json([
                    'featured_code' => 200,
                    'message' => $this->get_message('update'),
                ], 200);
            } else {
                return response()->json([
                    'featured_code' => 404,
                    'message' => $this->get_message('not_found'),
                ], 404);
            }
        } catch (\Throwable $e) {
            return response()->json([
                'featured_code' => 500,
                'message' => $this->get_message('server_error'),
            ], 500);
        }

    }


    public function get_active_brands(){

        try{

            $get_all_active_brand = Brand::where('status', '1')->get();

            if($get_all_active_brand){
                
                return response()->json([
                    'status_code' => Response::HTTP_OK,
                    'data' => $get_all_active_brand,
                ], Response::HTTP_OK);
    
            }

        }catch (\Exception $e) { 

            return response()->json([
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $this->get_message('server_error'),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);

        } 
        
    } 
    

}
