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
use App\Models\Category;
use App\Models\CategoryTranslation;
use App\Models\Language;
use App\Models\Role;
use App\Services\PermissionService;
use App\Traits\MessageTrait;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon; 
use Illuminate\Support\Str;


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

    public function edit_category($uuid){

        try {
            
            $edit_category_by_id = Category::where('uuid', $uuid)->first();

            if(!$edit_category_by_id)
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
                    
                    $check_category_translation = CategoryTranslation::where('category_id', $edit_category_by_id->id)
                    ->where('language_id', $language->id)
                    ->where('status', '1')->first();

                    if($check_category_translation)
                    {
                        
                        

                    }
                    else{

                        $save_category_translation = CategoryTranslation::insert([
                            ['uuid' => Str::uuid(), 'category_id' => $edit_category_by_id->id, 'name' => $edit_category_by_id->name , 'language_id' => $language->id , 'lang' => $language->app_language_code , 'auth_id' => $auth_id , 'created_at' => $now, 'updated_at' => $now],
                        ]);

                    }


                }

 
            } 

            $category_translations = CategoryTranslation::where('category_id', $edit_category_by_id->id)
            ->where('category_translations.status', '1')
            ->join('languages', 'category_translations.language_id', '=', 'languages.id')
            ->select('languages.code as language_code', 'languages.name as language_name' , 'languages.flag as flag' , 'languages.rtl as dir', 'category_translations.*')
            ->get();

            
            if ($edit_category_by_id) {

                $edit_category_by_id->translations = $category_translations;
       
                return response()->json([

                    'status_code' => Response::HTTP_OK,
                    'data' => $edit_category_by_id,

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

            $category->save();


            $updatedTranslations = false;

            foreach ($request->all() as $key => $value) {
                
                if (strpos($key, 'name_') === 0) {
                    
                    $languageCode = substr($key, 5);

                    $languageId = DB::table('languages')->where('code', $languageCode)->value('id');

                    if($languageId){
                        
                        CategoryTranslation::where('language_id', $languageId)
                        ->where('category_id', $category->id)
                        ->update(['name' => $value]);

                        $updatedTranslations = true;
                    }

                }

            }


            if ($updatedTranslations) {
                
                $get_active_language = Language::where('status', '1')->where('is_default', '1')->first();
                $get_role_trans_by_def_lang = CategoryTranslation::where('category_id', $category->id)
                ->where('language_id', $get_active_language->id)
                ->first();

                $upd_brand2 = DB::table('categories')
                ->where('id', $category->id)
                ->update([
                    'name' => $get_role_trans_by_def_lang->name,
                ]);

            }

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
        CategoryTranslation::where('category_id', $category->id)->delete();

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



}
