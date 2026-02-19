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
            return response()->json([
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $this->get_message('server_error'),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
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
            $lang = $request->language ?? getConfigValue('default_lang');
            $langId = getLanguage($lang);
            $all_languages = all_languages();

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
            // Create translations for all languages
            $this->updateBrandTranslation($brand, $lang, $langId->uuid, $request, $all_languages);
        
            return response()->json([
                'status_code' => 200,
                'message' => $this->get_message('add'),
            ], 200);
        
        }catch (\Illuminate\Database\QueryException $e) {
            
            if ($e->errorInfo[1] == 1062) {
                return response()->json([
                    'status_code' => 409,
                    'message' => 'Duplicate entry: The brand already exists.',
                ], 409);
            }
            dd($e);
            return response()->json([
                'status_code' => 500,
                'message' => $this->get_message('server_error'),
            ], 500);

        } catch (\Throwable $th) {
            dd($th);
            return response()->json([
                'status_code' => 500,
                'message' => $this->get_message('server_error'),
            ], 500);

        }
        
    }


    public function edit_brand($uuid, Request $request){

        try {
            $lang = $request->get('language') ?? getConfigValue('default_lang');
            
            $edit_brand_by_id = Brand::with(['brand_translations' => function ($query) use ($lang) {
                $query->where('lang', $lang);
            }])->where('uuid', $uuid)->first();

            if(!$edit_brand_by_id)
            {
                return response()->json([
                    'status_code' => Response::HTTP_NOT_FOUND,
                    'message' => $this->get_message('not_found'),
                ], Response::HTTP_NOT_FOUND);
            }

            // Get all translations for tab creation
            $all_translations = Brand_translation::where('brand_id', $uuid)
                ->join('languages', 'brand_translations.language_id', '=', 'languages.uuid')
                ->get();

            $brand_data = [
                'uuid' => $edit_brand_by_id->uuid,
                'brand' => $edit_brand_by_id->getTranslation('brand', $lang),
                'slug' => $edit_brand_by_id->slug,
                'logo' => $edit_brand_by_id->logo,
                'order_level' => $edit_brand_by_id->order_level,
                'description' => $edit_brand_by_id->getTranslation('description', $lang),
                'meta_title' => $edit_brand_by_id->meta_title,
                'meta_description' => $edit_brand_by_id->meta_description,
                'og_title' => $edit_brand_by_id->og_title,
                'og_description' => $edit_brand_by_id->og_description,
                'og_image' => $edit_brand_by_id->og_image,
                'x_title' => $edit_brand_by_id->x_title,
                'x_description' => $edit_brand_by_id->x_description,
                'x_image' => $edit_brand_by_id->x_image,
                'translations' => $all_translations,
            ];

            return response()->json([
                'status_code' => Response::HTTP_OK,
                'data' => $brand_data,
            ], Response::HTTP_OK);


        }catch(\Exception $e) { 
            return response()->json([
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

    }


    public function update_brand(Request $request)
    {
        $uuid = request()->header('uuid');
        $lang = $request->language ?? getConfigValue('default_lang');
        $langId = getLanguage($lang);

        try {
            // Find the brand
            $brand = Brand::where('uuid', $uuid)->first();

            if (!$brand) {
                return response()->json([
                    'status_code' => Response::HTTP_NOT_FOUND,
                    'message' => $this->get_message('not_found'),
                ], Response::HTTP_NOT_FOUND);
            }

            // Update brand fields (only update base fields if default language)
            if ($lang == getConfigValue('default_lang')) {
                $brand->brand = $request->brand;
                $brand->logo = $request->logo;
                $brand->order_level = $request->order_level;
                $brand->description = $request->description;
                // dd($brand);
            }

            $brand->meta_title = $request->meta_title;
            $brand->meta_description = $request->meta_description;
            $brand->og_title = $request->og_title;
            $brand->og_description = $request->og_description;
            $brand->og_image = $request->og_image;
            $brand->x_title = $request->x_title;
            $brand->x_description = $request->x_description;
            $brand->x_image = $request->x_image;
            // dd($brand);

            if ($request->slug) {
                $brand->slug = preg_replace('/[^A-Za-z0-9\-]/', '', str_replace(' ', '-', $request->slug));
            } else {
                $brand->slug = preg_replace('/[^A-Za-z0-9\-]/', '', str_replace(' ', '-', $request->brand)) . '-' . Str::random(5);
            }

            $brand->save();

            // Update translation
            $this->updateBrandTranslation($brand, $lang, $langId->uuid, $request);

            return response()->json([
                'status_code' => 200,
                'message' => $this->get_message('update'),
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $th->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Update brand translations.
     */
    private function updateBrandTranslation(Brand $brand, string $lang, string $langUuid, Request $request, $languages = []): void
    {
        if ($languages) {
            foreach ($languages as $language) {
                $translation = Brand_translation::firstOrNew([
                    'lang' => $language->app_language_code,
                    'language_id' => $language->uuid,
                    'brand_id' => $brand->uuid
                ]);
                $translation->brand = $request->brand;
                $translation->description = $request->description;
                $translation->logo = $request->logo;
                $translation->meta_title = $request->meta_title;
                $translation->meta_description = $request->meta_description;
                $translation->auth_id = Auth::user()->uuid;
                $translation->save();
            }
        } else {
            $translation = Brand_translation::firstOrNew([
                'lang' => $lang,
                'language_id' => $langUuid,
                'brand_id' => $brand->uuid
            ]);
            $translation->brand = $request->brand;
            $translation->description = $request->description;
            $translation->logo = $request->logo;
            $translation->meta_title = $request->meta_title;
            $translation->meta_description = $request->meta_description;
            $translation->auth_id = Auth::user()->uuid;
            $translation->save();
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

                // Delete translations first
                $del_brand->brand_translations()->delete();
                
                // Delete brand
                $delete_brand = Brand::destroy($del_brand->id);

                if($delete_brand){
                    
                    return response()->json([
                        
                        'status_code' => Response::HTTP_OK,
                        'message' => $this->get_message('delete'),
                    
                    ], Response::HTTP_OK);
    
                }

            }


        }catch (\Exception $e) { 
            return response()->json([
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $this->get_message('server_error'),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        } 
        
    }

    
    public function updateCategoryStatus(Request $request, string $id)
    {
        $request->validate([
            'status' => 'required|in:0,1',
        ]);

        try {
            $brand = Brand::where('uuid', $id)->first();

            if ($brand) {
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
            $brand = Brand::where('uuid', $id)->first();

            if ($brand) {
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