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
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use App\Models\GalleryCategory;
use Illuminate\Support\Str;


class GalleryCategoryController extends Controller
{
    
    public function add_gallery_category(Request $request){ 
        
        $validator = Validator::make($request->all(), [ 
             
            'category_name' => 'required|string|regex:/^[a-zA-Z0-9\s\-]+$/',
            'slug' => 'required|string|regex:/^[a-z0-9\-]+$/',
            'auth_id' => 'required',
        
        ]);


        if($validator->fails()) {
            
            $message = $validator->messages();
            
            return response()->json([
                
                'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'errors' => strval($validator->errors())
            
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try{

            $check_if_already = GalleryCategory::where('category_name', $request->category_name)->where('slug', $request->slug)->get();

            if(count($check_if_already) > 0){ 

                return response()->json([

                    'status_code' => Response::HTTP_CONFLICT,
                    'message' => 'This Gallery Category has already been taken.',

                ], Response::HTTP_CONFLICT); // 409 Conflict


            }else{

                $gallery_category = $request->all();
                $gallery_category['uuid'] = Str::uuid();
                
                $save_gallery_category = GalleryCategory::create($gallery_category);

                if($save_gallery_category) {
                    
                    return response()->json([
                            
                        'status_code' => Response::HTTP_CREATED,
                        'message' => 'Gallery Category add successfully',

                    ], Response::HTTP_CREATED);

                }

            }
            
        
        }catch (QueryException $e) {
            
            if ($e->getCode() === '23000') { // SQLSTATE code for integrity constraint violation
                
                return response()->json([

                    'status_code' => Response::HTTP_CONFLICT,
                    'message' => 'Duplicate entry detected',
                    'error' => 'This Gallery Category has already been taken.',

                ], Response::HTTP_CONFLICT); // 409 Conflict
            }

            // For other SQL errors
            return response()->json([

                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'Database error',
                'error' => $e->getMessage(), 

            ], Response::HTTP_INTERNAL_SERVER_ERROR); 
        

        }catch (\Exception $e) { 
            // Handle general exceptions
            return response()->json([

                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'Server error',
                'error' => $e->getMessage(),

            ], Response::HTTP_INTERNAL_SERVER_ERROR); 
        }

    }


    public function edit_gallery_category($uuid){

        $get_gallery_category = GalleryCategory::where('uuid', $uuid)->first();

        if($get_gallery_category)
        {
            
            return response()->json([

                'status_code' => Response::HTTP_OK,
                'get_gallery_category' => $get_gallery_category,

            ], Response::HTTP_OK);


        }else{

            return response()->json([

                'status_code' => Response::HTTP_NOT_FOUND,
                'message' => 'Record Not Found',

            ], Response::HTTP_NOT_FOUND);

        }

    }



    public function update_gallery_category(Request $request, $uuid){
        

        $validator = Validator::make($request->all(), [ 
             
            'category_name' => 'required|string|regex:/^[a-zA-Z0-9\s\-]+$/',
            'slug' => 'required|string|regex:/^[a-z0-9\-]+$/',
            'auth_id' => 'required',
        
        ]);
        
        
        if($validator->fails()) {
            
            $message = $validator->messages();
            
            return response()->json([
                
                'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'errors' => strval($validator->errors())
            
            ], Response::HTTP_UNPROCESSABLE_ENTITY);

        }

        try {
            
            $upd_gallery_category = GalleryCategory::where('uuid', $uuid)->first();

            if (!$upd_gallery_category) {
                return response()->json([

                    'status_code' => Response::HTTP_NOT_FOUND,
                    'message' => 'Record not found'

                ], Response::HTTP_NOT_FOUND);
            }

            $update_gallery_category = $upd_gallery_category->update($request->all());

            if($update_gallery_category){
                
                return response()->json([
                    
                    'status_code' => Response::HTTP_OK,
                    'message' => 'Gallery Category has been updated',
                
                ], Response::HTTP_OK);

            }


        }catch (QueryException $e) {
            
            if ($e->getCode() === '23000') { // SQLSTATE code for integrity constraint violation
                // Handle unique constraint violation
                return response()->json([

                    'status_code' => Response::HTTP_CONFLICT,
                    'message' => 'Duplicate entry detected',
                    'error' => 'This Gallery Category has already been taken.',

                ], Response::HTTP_CONFLICT); // 409 Conflict
            }

            // For other SQL errors
            return response()->json([

                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'Database error',
                'error' => $e->getMessage(), 

            ], Response::HTTP_INTERNAL_SERVER_ERROR); // 500 Internal Server Error
        
        }catch (\Exception $e) { 
            // Handle general exceptions
            return response()->json([

                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'Server error',
                'error' => $e->getMessage(),

            ], Response::HTTP_INTERNAL_SERVER_ERROR); // 500 Internal Server Error
        }
        
    }



    public function delete_gallery_category($uuid){

        try{

            $del_gallery_category = GalleryCategory::where('uuid', $uuid)->first();
            
            if(!$del_gallery_category)
            {
                
                return response()->json([

                    'status_code' => Response::HTTP_NOT_FOUND,
                    'message' => 'Record not found'

                ], Response::HTTP_NOT_FOUND);


            }else{

                $delete_attribute = GalleryCategory::destroy($del_gallery_category->id);

                if($delete_attribute){
                
                    return response()->json([
                        
                        'status_code' => Response::HTTP_OK,
                        'message' => 'Gallery Category has been deleted',
                    
                    ], Response::HTTP_OK);
    
                }

            }


        }catch (\Exception $e) { 
            // Handle general exceptions
            return response()->json([

                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'Server error',
                'error' => $e->getMessage(),

            ], Response::HTTP_INTERNAL_SERVER_ERROR); // 500 Internal Server Error
        } 
        
    }


    public function get_own_gallery_category($authid){

        $get_own_gallery_category = GalleryCategory::where('auth_id', $authid)->get();

        if(count($get_own_gallery_category) > 0)
        {
            $get_own_gallery_category->base_url = config('app.base_url');
            return response()->json([

                'status_code' => Response::HTTP_OK,
                'get_own_gallery_category' => $get_own_gallery_category,

            ], Response::HTTP_OK);


        }else{

            return response()->json([

                'status_code' => Response::HTTP_NOT_FOUND,
                'message' => 'Record Not Found',

            ], Response::HTTP_NOT_FOUND);

        }

    }



    public function get_all_gallery_category(){

        try{

            $get_all_gallery_category = GalleryCategory::all();

            if($get_all_gallery_category){

                return response()->json([
                        
                    'status_code' => Response::HTTP_OK,
                    'get_all_gallery_category' => $get_all_gallery_category,

                ], Response::HTTP_OK);
    
            }

        }catch (\Exception $e) { 
            // Handle general exceptions
            return response()->json([

                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'Server error',
                'error' => $e->getMessage(),

            ], Response::HTTP_INTERNAL_SERVER_ERROR); // 500 Internal Server Error
        } 

    }

}
