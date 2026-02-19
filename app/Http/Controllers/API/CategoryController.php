<?php

namespace App\Http\Controllers\API;

use DB;
use Auth;
use Hash;
use Mail;
use Session;
use Carbon\Carbon; 
use App\Models\Role;
use App\Models\Category;
use App\Models\Language;
use Illuminate\Support\Str;
use App\Traits\MessageTrait;
use Illuminate\Http\Request;
use App\Models\CategoryTranslation;
use App\Services\PermissionService;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;


class CategoryController extends Controller
{
    
    use MessageTrait;
    protected $permissionService;

    public function __construct(PermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    public function get_category()
    {

        try {
           
            $menuUuid = request()->header('menu-uuid');
            $permissions = $this->permissionService->checkPermissions($menuUuid);
            $get_all_categories = Category::orderBy('id', 'desc');
            if ($permissions['view']) {
                if (!$permissions['viewglobal']) {
                    $get_all_categories = $get_all_categories->where('auth_id', Auth::user()->uuid);
                }
            }else{
                if (Auth::user()->hasPermission('viewglobal')) {
                    $get_all_categories = $get_all_categories;
                } else {
                    return response()->json(['message' => 'You do not have permission to view this menu'], Response::HTTP_FORBIDDEN);
                }
            }
            $get_all_categories = $get_all_categories->get();

            return response()->json([
                'status_code'=>200,
                'permissions' => $permissions,
                'data'=>$get_all_categories
            ],200);

        } catch (\Throwable $th) {
           
            return response()->json([

                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $this->get_message('server_error'),
                

            ], Response::HTTP_INTERNAL_SERVER_ERROR); // 500 Internal Server Error

        }

    }


    public function add_category(Request $request)
    {
        $lang   = $request->language ?? defaultLanguages()->app_language_code;
        $langId = getLanguage($lang);
        $all_languages = all_languages();
        
        $validator = Validator::make($request->all(), [
            'name' => 'required|max:255|unique:categories,name',
            'slug' => 'nullable|unique:categories,slug',
            'parent_id'=>'required',
            'order_level'=>'required',
            'banner'=>'',
            'icon'=>'',
            'meta_title'=>'',
            'meta_description'=>'',
            'og_title'=>'',
            'og_description'=>'',
            'og_image'=>'',
            'x_title'=>'',
            'x_description'=>'',
            'x_image'=>'',
        ]);

        if($validator->fails()) {
            
            $message = $validator->messages();
            
            return response()->json([
                    
                'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'errors' => strval($validator->errors())
            
            ], Response::HTTP_UNPROCESSABLE_ENTITY);

        }

        
        try {
           
            $category = new Category;
            $category->uuid = Str::uuid();
            $category->auth_id = Auth::user()->uuid;
            $category->name = $request->name;
            $category->order_level = 0;
            if($request->order_level != null) {
                $category->order_level = $request->order_level;
            }

            if ($request->parent_id != "0") {
                $category->parent_id = $request->parent_id;
    
                $parent = Category::find($request->parent_id);
                $category->level = $parent->level + 1 ;
            }

            $category->banner = $request->banner;
            $category->icon = $request->icon;
            $category->meta_title = $request->meta_title;
            $category->meta_description = $request->meta_description;
            $category->og_title = $request->og_title;
            $category->og_description = $request->og_description;
            $category->og_image = $request->og_image;
            $category->x_title = $request->x_title;
            $category->x_description = $request->x_description;
            $category->x_image = $request->x_image;

            if ($request->slug != null) {
                $category->slug = preg_replace('/[^A-Za-z0-9\-]/', '', str_replace(' ', '-', $request->slug));
            }
            else {
                $category->slug = preg_replace('/[^A-Za-z0-9\-]/', '', str_replace(' ', '-', $request->name)).'-'.Str::random(5);
            }

            $category->save();
            $this->updateCategoryTranslation($category, $lang, $langId->uuid, $request, $all_languages);

            return response()->json([
                'status_code'=>200,
                'message'=> 'Category has been added',
            ],200);


        } catch (\Throwable $th) {
            
            return response()->json([

                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                // 'message' => $this->get_message('server_error'),
                'message' => $th->getMessage(),

            ], Response::HTTP_INTERNAL_SERVER_ERROR);

        }

    }

    public function edit_category($uuid, Request $request){

        try {
            
            // $edit_category_by_id = Category::where('uuid', $uuid)->first();

            // if(!$edit_category_by_id)
            // {
            //     return response()->json([
            //         'status_code' => Response::HTTP_NOT_FOUND,
            //         'message' => $this->get_message('not_found'),
            //     ], Response::HTTP_NOT_FOUND);
            // }

            $lang = getConfigValue('default_lang');
            if ($request->has('lang')) {
                $lang = $request->lang;
            }
            // dd($lang, $uuid);
             $category = Category::with(['category_translations' => function ($query) use ($lang) {
                $query->where('lang', $lang);
            }])->where('uuid', $uuid)->first();
            // dd($category);

             if(!$category)
            {
                return response()->json([
                    'status_code' => Response::HTTP_NOT_FOUND,
                    'message' => $this->get_message('not_found'),
                ], Response::HTTP_NOT_FOUND);
            }
            
            // $get_active_language = Language::where('status', '1')->get();

            // $now = Carbon::now();
            // $auth_id = Auth::user()->uuid;

            // if(count($get_active_language) > 0){

            //     foreach($get_active_language as $key => $language){
                    
            //         $check_category_translation = CategoryTranslation::where('category_id', $edit_category_by_id->id)
            //         ->where('language_id', $language->id)
            //         ->where('status', '1')->first();

            //         if($check_category_translation)
            //         {
                        
                        

            //         }
            //         else{

            //             $save_category_translation = CategoryTranslation::insert([
            //                 ['uuid' => Str::uuid(), 'category_id' => $edit_category_by_id->id, 'name' => $edit_category_by_id->name , 'language_id' => $language->id , 'lang' => $language->app_language_code , 'auth_id' => $auth_id , 'created_at' => $now, 'updated_at' => $now],
            //             ]);

            //         }


            //     }

 
            // } 

            // $category_translations = CategoryTranslation::where('category_id', $edit_category_by_id->id)
            // ->where('category_translations.status', '1')
            // ->join('languages', 'category_translations.language_id', '=', 'languages.id')
            // ->select('languages.code as language_code', 'languages.name as language_name' , 'languages.flag as flag' , 'languages.rtl as dir', 'category_translations.*')
            // ->get();

            
            if ($category) {

                $data = [
                'uuid' => $category->uuid,
                'parent_id' => $category->getTranslation('parent_id', $lang), 
                'level' => $category->getTranslation('level', $lang), 
                'name' => $category->getTranslation('name', $lang),
                'order_level' => $category->getTranslation('order_level', $lang), 
                'commision_rate' => $category->commision_rate,
                'banner' => $category->getTranslation('banner', $lang),
                'icon' => $category->getTranslation('icon', $lang), 
                'cover_image' => $category->getTranslation('cover_image', $lang), 
                'featured' => $category->getTranslation('featured', $lang),
                'top' => $category->top,
                'digital' => $category->digital,
                'slug' => $category->slug,
                'meta_title' => $category->getTranslation('meta_title', $lang),
                'meta_description' => $category->getTranslation('meta_description', $lang), 
                'og_title' => $category->getTranslation('og_title', $lang), 
                'og_description' => $category->getTranslation('og_description', $lang), 
                'og_image' => $category->getTranslation('og_image', $lang),
                'x_title' => $category->getTranslation('x_title', $lang), 
                'x_description' => $category->getTranslation('x_description', $lang), 
                'x_image' => $category->getTranslation('x_image', $lang), 
                'status' => $category->status,
            ];
                // $edit_category_by_id->translations = $category_translations;
       
                return response()->json([

                    'status_code' => Response::HTTP_OK,
                    'data' => $data,

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


    public function update_category(Request $request)
    {

        try {

            $uuid = request()->header('uuid');
            // Find the category by ID
            $category = Category::where('uuid', $uuid)->first();
            $lang   = $request->language ?? defaultLanguages()->app_language_code;
            $langId = getLanguage($lang);

            if (!$category) {
                return response()->json([
                    'status_code' => Response::HTTP_NOT_FOUND,
                    'message' => $this->get_message('not_found'),
                ], Response::HTTP_NOT_FOUND);
            }

            $validator = Validator::make($request->all(), [
            'name' => 'unique:categories,name,' . $category->id,
            'slug' => 'nullable|unique:categories,slug,' . $category->id,
            'parent_id' => '',
            'order_level' => '',
            'banner' => '',
            'icon' => '',
            'meta_title' => '',
            'meta_description' => '',
            'og_title' => '',
            'og_description' => '',
            'og_image' => '',
            'x_title' => '',
            'x_description' => '',
            'x_image' => '',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'errors' => strval($validator->errors())
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

            // Update category properties
            $category->name = $request->name;
            $category->order_level = $request->order_level ?? 0;

            if ($request->parent_id != "0") {
                $category->parent_id = $request->parent_id;

                $parent = Category::find($request->parent_id);
                if ($parent) {
                    $category->level = $parent->level + 1;
                } else {
                    return response()->json([
                        'status_code' => 404,
                        'message' => 'Parent category not found',
                    ], 404);
                }
            } else {
                $category->parent_id = 0;
                $category->level = 0;
            }

            $category->banner = $request->banner;
            $category->icon = $request->icon;
            $category->meta_title = $request->meta_title;
            $category->meta_description = $request->meta_description;
            $category->og_title = $request->og_title;
            $category->og_description = $request->og_description;
            $category->og_image = $request->og_image;
            $category->x_title = $request->x_title;
            $category->x_description = $request->x_description;
            $category->x_image = $request->x_image;

            // Update slug
            if ($request->slug != null) {
                $category->slug = preg_replace('/[^A-Za-z0-9\-]/', '', str_replace(' ', '-', $request->slug));
            } else {
                $category->slug = preg_replace('/[^A-Za-z0-9\-]/', '', str_replace(' ', '-', $request->name)) . '-' . Str::random(5);
            }
            if($lang == defaultLanguages()->app_language_code){
            $category->save();
            }
            $this->updateCategoryTranslation($category, $lang, $langId->uuid, $request);

            // $updatedTranslations = false;

            // foreach ($request->all() as $key => $value) {
                
            //     if (strpos($key, 'name_') === 0) {
                    
            //         $languageCode = substr($key, 5);

            //         $languageId = DB::table('languages')->where('code', $languageCode)->value('id');

            //         if($languageId){
                        
            //             CategoryTranslation::where('language_id', $languageId)
            //             ->where('category_id', $category->id)
            //             ->update(['name' => $value]);

            //             $updatedTranslations = true;
            //         }

            //     }

            // }


            // if ($updatedTranslations) {
                
            //     $get_active_language = Language::where('status', '1')->where('is_default', '1')->first();
            //     $get_role_trans_by_def_lang = CategoryTranslation::where('category_id', $category->id)
            //     ->where('language_id', $get_active_language->id)
            //     ->first();

            //     $upd_brand2 = DB::table('categories')
            //     ->where('id', $category->id)
            //     ->update([
            //         'name' => $get_role_trans_by_def_lang->name,
            //     ]);

            // }

            return response()->json([
                'status_code' => 200,
                'message' => 'Category has been updated',
            ], 200);
            

        } catch (\Throwable $th) {
           
            return response()->json([
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $this->get_message('server_error'),
                // 'message' => $th->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
            
        }
    }
    

    public function delete_category($uuid)
{
    try {
        // Find the category by UUID
        $category = Category::where('uuid', $uuid)->firstOrFail();

        // Check if this category is assigned to any product
        $isAssigned = DB::table('product_categories')
                        ->where('category_uuid', $uuid)
                        ->exists();

        if ($isAssigned) {
            return response()->json([
                'status_code' => 400,
                'message' => 'Cannot delete category. It is assigned to one or more products.',
            ], 400);
        }

        // Delete the category and its translations
        $category->delete();
        CategoryTranslation::where('category_uuid', $uuid)->delete();

        return response()->json([
            'status_code' => 200,
            'message' => 'Category has been deleted successfully',
        ], 200);

    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        return response()->json([
            'status_code' => 404,
            'message' => 'Category not found',
        ], 404);

    } catch (\Throwable $th) {
        return response()->json([
            'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
            'message' => $this->get_message('server_error'),
        ], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}



    public function updateCategoryStatus(Request $request, string $id)
    {
        $request->validate([
            'status' => 'required|in:0,1', // Ensure status is either 0 or 1
        ]);

        try {
            // Find the category by UUID and active status
            $category = Category::where('uuid', $id)->first();

            if ($category) {
                // Update the status
                $category->status = $request->status;
                $category->save();

                return response()->json([
                    'status_code' => 200,
                    'message' => $this->get_message('update'), // Ensure `get_message` is properly defined
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
            'featured' => 'required|in:0,1', // Ensure status is either 0 or 1
        ]);

        try {
            // Find the category by UUID and active status
            $category = Category::where('uuid', $id)->first();

            if ($category) {
                // Update the status
                $category->featured = $request->featured;
                $category->save();

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
    

    public function get_active_categories(){

        try{

            $get_all_active_roles = Category::where('status', '1')->orderBy('parent_id','ASC')->get();

            // $get_all_active_roles = Category::with('childrenCategories')->get();

            if($get_all_active_roles){
                
                return response()->json([
                    'status_code' => Response::HTTP_OK,
                    'data' => $get_all_active_roles,
                ], Response::HTTP_OK);
    
            }

        }catch (\Exception $e) { 

            return response()->json([
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $this->get_message('server_error'),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);

        } 
        
    } 

    private function updateCategoryTranslation(Category $category, string $lang, string $langUuid, Request $request, $languages = []): void
    {
        if ($languages) {
            foreach ($languages as $language) {
                $translation = CategoryTranslation::firstOrNew([
                    'lang' => $language->app_language_code,
                    'language_id' => $language->uuid,
                    'category_uuid' => $category->uuid
                ]);
                $translation->name = $request->name;
                $translation->parent_id = $request->parent_id;
                $translation->level = $request->level;
                $translation->order_level = $request->order_level;
                $translation->featured = $request->featured;
                $translation->banner = $request->banner;
                $translation->icon = $request->icon;
                $translation->cover_image = $request->cover_image;
                $translation->meta_title = $request->meta_title;
                $translation->meta_description = $request->meta_description;
                $translation->og_title = $request->og_title;
                $translation->og_description = $request->og_description;
                $translation->og_image = $request->og_image;
                $translation->x_title = $request->x_title;
                $translation->x_description = $request->x_description;
                $translation->x_image = $request->x_image;
                $translation->save();
            }
        } else {
            $translation = CategoryTranslation::firstOrNew([
                'lang' => $lang,
                'language_id' => $langUuid,
                'category_uuid' => $category->uuid
            ]);
            $translation->name = $request->name;
            $translation->parent_id = $request->parent_id;
            $translation->level = $request->level;
            $translation->order_level = $request->order_level;
            $translation->featured = $request->featured;
            $translation->banner = $request->banner;
            $translation->icon = $request->icon;
            $translation->cover_image = $request->cover_image;
            $translation->meta_title = $request->meta_title;
            $translation->meta_description = $request->meta_description;
            $translation->og_title = $request->og_title;
            $translation->og_description = $request->og_description;
            $translation->og_image = $request->og_image;
            $translation->x_title = $request->x_title;
            $translation->x_description = $request->x_description;
            $translation->x_image = $request->x_image;
            $translation->save();
        }
    }



}
