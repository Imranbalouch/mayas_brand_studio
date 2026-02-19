<?php

namespace App\Http\Controllers\API\Ecommerce;

use Auth;
use Hash;
use Mail;
use Session;
use Carbon\Carbon; 
use App\Models\Language;
use App\Models\Ecommerce\Attribute;
use Illuminate\Support\Str;
use App\Traits\MessageTrait;
use Illuminate\Http\Request;
use App\Models\Ecommerce\AttributeValue;
use Illuminate\Support\Facades\DB;
use App\Services\PermissionService;
use App\Http\Controllers\Controller;
use App\Models\Ecommerce\AttributeTranslation;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;



class AttributeController extends Controller
{
    
    use MessageTrait;
    protected $permissionService;

    public function __construct(PermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }


        public function get_attribute()
        {
            try {
                // Retrieve menu UUID from request headers
                $menuUuid = request()->header('menu-uuid');
                $permissions = $this->permissionService->checkPermissions($menuUuid);

                // Base query with relationships
                $query = Attribute::with('attribute_values');

                // Apply order
                $query->orderBy('id', 'desc');

                // Check permissions
                if ($permissions['view']) {
                    if (!$permissions['viewglobal']) {
                        $query->where('auth_id', Auth::user()->uuid);
                    }
                } else {
                    if (!Auth::user()->hasPermission('viewglobal')) {
                        return response()->json([
                            'message' => 'You do not have permission to view this menu',
                        ], Response::HTTP_FORBIDDEN);
                    }
                }

                // Execute query
                $attributes = $query->get();

                // Return response
                return response()->json([
                    'status_code' => 200,
                    'permissions' => $permissions,
                    'data' => $attributes,
                ], 200);

            } catch (\Exception $e) {
                // Return error response
                return response()->json([
                    'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                    'message' => $this->get_message('server_error'),
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        }


    
    public function add_attribute(Request $request)
    {
        // Validate input
        $validator = Validator::make($request->all(), [
            'attribute_name' => 'required|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'errors' => strval($validator->errors())
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            // Check if the attribute already exists for the authenticated user
            $existingAttribute = Attribute::where('attribute_name', $request->attribute_name)
                ->where('auth_id', Auth::user()->uuid)
                ->first();

            if ($existingAttribute) {
                return response()->json([
                    'status_code' => 409,
                    'message' => 'Duplicate entry: The attribute already exists.',
                ], 409);
            }

            // Create and save a new attribute
            $attribute = new Attribute();
            $attribute->uuid = Str::uuid();
            $attribute->auth_id = Auth::user()->uuid;
            $attribute->attribute_name = $request->attribute_name;
            $attribute->save();

            return response()->json([
                'status_code' => 200,
                'message' => $this->get_message('add'),
            ], 200);

        } catch (\Illuminate\Database\QueryException $e) {
            if ($e->errorInfo[1] == 1062) { // Error code for duplicate entry
                return response()->json([
                    'status_code' => 409,
                    'message' => 'Duplicate entry: The attribute already exists.',
                ], 409);
            }

            return response()->json([
                'status_code' => 500,
                'message' => $this->get_message('server_error'),
            ], 500);

        } catch (\Throwable $th) {
            return response()->json([
                'status_code' => 500,
                'message' => $this->get_message('server_error'),
            ], 500);
        }
    }


    public function edit_attribute($uuid){

        try {
                
                $edit_attribute_by_id = Attribute::where('uuid', $uuid)->first();

                // dd($edit_attribute_by_id);
            
                if(!$edit_attribute_by_id)
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
                        
                        $check_AttributeTranslation = AttributeTranslation::where('attribute_id', $edit_attribute_by_id->id)
                        ->where('language_id', $language->id)
                        ->where('status', '1')->first();
                        // dd($check_AttributeTranslation);
                        
                        if($check_AttributeTranslation)
                        {
                            
                        
            
                        }
                        else{
            // dd( $language->id);
                            $save_AttributeTranslation = AttributeTranslation::insert([
                                ['uuid' => Str::uuid(), 'attribute_id' => $edit_attribute_by_id->id, 'attribute_name' => $edit_attribute_by_id->attribute_name , 'language_id' => $language->id , 'lang' => $language->app_language_code , 'auth_id' => $auth_id , 'created_at' => $now, 'updated_at' => $now],
                            ]);
            
                        }
            
            
                    }
            
            
                }
            
                $AttributeTranslations = AttributeTranslation::where('attribute_id', $edit_attribute_by_id->id)
                ->where('attribute_translations.status', '1')
                ->join('languages', 'attribute_translations.language_id', '=', 'languages.id')
                ->select('languages.code as language_code', 'languages.name as language_name' , 'languages.flag as flag' , 'languages.rtl as dir', 'attribute_translations.*')
                ->get();
            
                
                if ($edit_attribute_by_id) {
            
                    $edit_attribute_by_id->translations = $AttributeTranslations;
            
                    return response()->json([
            
                        'status_code' => Response::HTTP_OK,
                        'data' => $edit_attribute_by_id,
            
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

    
    
    public function update_attribute(Request $request)
    {
        $uuid = request()->header('uuid');

        try {
            // Find the attribute by uuid
            $attribute = Attribute::where('uuid', $uuid)->first();

            // Return 404 if the attribute is not found
            if (!$attribute) {
                return response()->json([
                    'status_code' => 404,
                    'message' => $this->get_message('not_found'),
                ], 404);
            }
            //dd( $request->name);
            // Update Attribute fields
            $attribute->attribute_name = $request->attribute_name;
            $attribute->save();

            $updatedTranslations = false;

            foreach ($request->all() as $key => $value) {
                if (strpos($key, 'name_') === 0) {
                    $languageCode = substr($key, 5);

                    $languageId = DB::table('languages')->where('code', $languageCode)->value('id');

                    if ($languageId) {
                        AttributeTranslation::where('language_id', $languageId)
                        ->where('attribute_id', $attribute->id)
                        ->update(['attribute_name' => $value]);

                        $updatedTranslations = true;
                    }
                }
            }
                // dd( $updatedTranslations);
                    if ($updatedTranslations) {
                $get_active_language = Language::where('status', '1')->where('is_default', '1')->first();
                $get_role_trans_by_def_lang = AttributeTranslation::where('attribute_id', $attribute->id)
                    ->where('language_id', $get_active_language->id)
                    ->first();

                if ($get_role_trans_by_def_lang) {
                    DB::table('attributes')
                        ->where('id', $attribute->id)
                        ->update([
                            'attribute_name' => $get_role_trans_by_def_lang->attribute_name,
                        ]);
                }
            }
            


            return response()->json([
                'status_code' => 200,
                'message' => 'Attribute has been updated',
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $th->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    public function delete_attribute($uuid)
    {
        try {
            // Find the attribute by UUID
            $del_attribute = Attribute::where('uuid', $uuid)->first();

            // If attribute is not found, return 404
            if (!$del_attribute) {
                return response()->json([
                    'status_code' => Response::HTTP_NOT_FOUND,
                    'message' => $this->get_message('not_found'),
                ], Response::HTTP_NOT_FOUND);
            }

            // Delete the attribute, translations, and values
            $deleted = $del_attribute->delete();
            if ($deleted) {
                // Delete related translations and values
                AttributeTranslation::where('attribute_id', $del_attribute->id)->delete();
                AttributeValue::where('attribute_id', $del_attribute->id)->delete();

                return response()->json([
                    'status_code' => Response::HTTP_OK,
                    'message' => $this->get_message('delete'),
                ], Response::HTTP_OK);
            }

            // If deletion fails, return a 500 response
            return response()->json([
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $this->get_message('delete_failed'),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);

        } catch (\Exception $e) {
            // Handle exceptions
            return response()->json([
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $this->get_message('server_error'),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    public function updateAttributeStatus(Request $request, string $id)
    {
        $request->validate([
            'status' => 'required|in:0,1', // Ensure status is either 0 or 1
        ]);

        try {
            
            $attribute = Attribute::where('uuid', $id)->first();

            if ($attribute) {

                // Update the status
                $attribute->status = $request->status;
                $attribute->save();

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


    public function store_attribute_value(Request $request)
    {
        try {
            // Validate request input
            $validator = Validator::make($request->all(), [
                'value' => 'required|string|max:255',
                'color_code' => '',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                    'errors' => $validator->errors(),
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            // Fetch the attribute using UUID
            $attribute_uuid = request()->header('uuid');
            $get_attribute = Attribute::where('uuid', $attribute_uuid)->first();

            if (!$get_attribute) {
                return response()->json([
                    'status_code' => Response::HTTP_NOT_FOUND,
                    'message' => $this->get_message('not_found'),
                ], Response::HTTP_NOT_FOUND);
            }

            // Check for duplicate value and color_code for the same attribute_id
            $duplicate = AttributeValue::where('attribute_id', $get_attribute->id)
                ->where('value', ucfirst($request->value))
                ->where('color_code', $request->color_code)
                ->exists();

            if ($duplicate) {
                return response()->json([
                    'status_code' => Response::HTTP_CONFLICT,
                    'message' => $this->get_message('conflict'),
                ], Response::HTTP_CONFLICT);
            }

            // Create a new attribute value
            $attribute_value = new AttributeValue();
            $attribute_value->uuid = Str::uuid();
            $attribute_value->auth_id = Auth::user()->uuid;
            $attribute_value->attribute_id = $get_attribute->id;
            $attribute_value->language_id = 1; 
            $attribute_value->value = ucfirst($request->value);
            $attribute_value->color_code = $request->color_code; // Add color code
            $attribute_value->save();

            return response()->json([
                'status_code' => Response::HTTP_OK,
                'message' => $this->get_message('add'),
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            return response()->json([
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $this->get_message('server_error'),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    

    public function edit_attribute_value($id)
    {
        try {
            // Fetch the attribute using the UUID
            $get_attribute = Attribute::where('uuid', $id)->first();

            if (!$get_attribute) {
                return response()->json([
                    'status_code' => Response::HTTP_NOT_FOUND,
                    'message' => $this->get_message('not_found'),
                ], Response::HTTP_NOT_FOUND);
            }

            // Retrieve menu UUID from request headers
            $menuUuid = request()->header('menu-uuid');

            // Check user permissions
            $permissions = $this->permissionService->checkPermissions($menuUuid);

            // Base query to fetch attribute values with conditions
            $query = AttributeValue::where('attribute_id', $get_attribute->id)->orderBy('id', 'desc');

            if ($permissions['view']) {
                if (!$permissions['viewglobal']) {
                    // Limit results to current user's scope if not allowed global view
                    $query->where('auth_id', Auth::user()->uuid);
                }
            } else {
                // Deny access if user lacks both specific and global permissions
                if (!Auth::user()->hasPermission('viewglobal')) {
                    return response()->json([
                        'status_code' => Response::HTTP_FORBIDDEN,
                        'message' => 'You do not have permission to view this menu',
                    ], Response::HTTP_FORBIDDEN);
                }
            }

            // Execute query and fetch attribute values
            $attribute_values = $query->get();

            // Modify each attribute value to include the attribute name
            $data_with_attribute_name = $attribute_values->map(function($attribute_value) use ($get_attribute) {
                // Add attribute name to each attribute value
                $attribute_value->attribute_name = $get_attribute->attribute_name;
                return $attribute_value;
            });

            // Return response with attribute details, including the attribute name and permissions
            return response()->json([
                'status_code' => Response::HTTP_OK,
                'permissions' => $permissions, // Include the attribute name at the top level
                'data' => $data_with_attribute_name,  // Modified data with the attribute name
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            // Handle exceptions and return an internal server error response
            return response()->json([
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $this->get_message('server_error'),
                'error' => $e->getMessage(), // Optional: Include for debugging in dev environments
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    

    public function edit_specific_attribute_value($uuid)
    {
        
        try {
            // Fetch the attribute value using the ID
            $attribute_value = AttributeValue::where('uuid', $uuid)->first();

            if (!$attribute_value) {
                return response()->json([
                    'status_code' => Response::HTTP_NOT_FOUND,
                    'message' => $this->get_message('not_found'),
                ], Response::HTTP_NOT_FOUND);
            }

            // Return the fetched attribute value details
            return response()->json([
                'status_code' => Response::HTTP_OK,
                'data' => $attribute_value,
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            return response()->json([
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $this->get_message('server_error'),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    public function update_attribute_value(Request $request)
    {
        try {
            // Get UUID from request header
            $uuid = $request->header('uuid');

            // Find the attribute value linked to the attribute
            $attribute_value = AttributeValue::where('uuid', $uuid)->first();

            if (!$attribute_value) {
                return response()->json([
                    'status_code' => Response::HTTP_NOT_FOUND,
                    'message' => $this->get_message('not_found'),
                ], Response::HTTP_NOT_FOUND);
            }

            // Validate the request
            $validator = Validator::make($request->all(), [
                'value' => 'required|max:255',
                'color_code' => '',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                    'errors' => $validator->errors(),
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            // Update the attribute value
            $attribute_value->value = ucfirst($request->value);
            $attribute_value->color_code = $request->color_code ?? $attribute_value->color_code;
            $attribute_value->save();

            return response()->json([
                'status_code' => Response::HTTP_OK,
                'message' => $this->get_message('update'),
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            return response()->json([
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $this->get_message('server_error'),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    public function delete_attribute_value($uuid)
    {
        try {

            $attribute_value = AttributeValue::where('uuid', $uuid)->first();

            if (!$attribute_value) {
                return response()->json([
                    'status_code' => Response::HTTP_NOT_FOUND,
                    'message' => $this->get_message('not_found'),
                ], Response::HTTP_NOT_FOUND);
            }

            // Delete the attribute value
            $attribute_value->delete();

            return response()->json([
                'status_code' => Response::HTTP_OK,
                'message' => $this->get_message('delete'),
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            return response()->json([
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $this->get_message('server_error'),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    public function get_active_attributes(){

        try{

            $get_all_active_attributes = Attribute::where('status', '1')->get();

            if($get_all_active_attributes){
                
                return response()->json([
                    'status_code' => Response::HTTP_OK,
                    'data' => $get_all_active_attributes,
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
