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
use App\Models\BlogCategoryTranslation;
use Illuminate\Support\Str;



class BlogCategoryTranslationController extends Controller
{
    
    public function add_blog_category_translation(Request $request){ 
        
        $validator = Validator::make($request->all(), [ 
             
            'blog_category_id' => 'required|integer',
            'language_id' => 'required|integer',
            'category' => 'required|string',
            'description' => 'nullable|string',
            'meta_title' => 'nullable|string',
            'meta_description' => 'nullable|string',
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

            $check_if_already = BlogCategoryTranslation::where('blog_category_id', $request->blog_category_id)->where('language_id', $request->language_id)->get();

            if(count($check_if_already) > 0){ 

                return response()->json([

                    'status_code' => Response::HTTP_CONFLICT,
                    'message' => 'This Blog Category Translation has already been taken.',

                ], Response::HTTP_CONFLICT); // 409 Conflict 


            }else{

                $blogcategory_translation = $request->all();
                $blogcategory_translation['uuid'] = Str::uuid();
                
                $save_blog_category_translation = BlogCategoryTranslation::create($blogcategory_translation);

                if($save_blog_category_translation) {
                    
                    return response()->json([
                            
                        'status_code' => Response::HTTP_CREATED,
                        'message' => 'Blog Category Translation add successfully',

                    ], Response::HTTP_CREATED);

                }

            }
            
        
        }catch (QueryException $e) {
            
            if ($e->getCode() === '23000') { // SQLSTATE code for integrity constraint violation
                
                return response()->json([

                    'status_code' => Response::HTTP_CONFLICT,
                    'message' => 'Duplicate entry detected',
                    'error' => 'This Blog Category Translation has already been taken.',

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


    public function edit_blog_category_translation($uuid){

        $edit_blog_category_translation = BlogCategoryTranslation::where('uuid', $uuid)->first();

        if($edit_blog_category_translation)
        {
            
            return response()->json([

                'status_code' => Response::HTTP_OK,
                'edit_blog_category_translation' => $edit_blog_category_translation,

            ], Response::HTTP_OK);


        }else{

            return response()->json([

                'status_code' => Response::HTTP_NOT_FOUND,
                'message' => 'Record Not Found',

            ], Response::HTTP_NOT_FOUND);

        }

    }



    public function update_blog_category_translation(Request $request, $uuid){
        

        $validator = Validator::make($request->all(), [ 
             
            'blog_category_id' => 'required|integer',
            'language_id' => 'required|integer',
            'category' => 'required|string',
            'description' => 'nullable|string',
            'meta_title' => 'nullable|string',
            'meta_description' => 'nullable|string',
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
            
            $upd_blog_category_translation = BlogCategoryTranslation::where('uuid', $uuid)->first();

            if (!$upd_blog_category_translation) {
                return response()->json([

                    'status_code' => Response::HTTP_NOT_FOUND,
                    'message' => 'Record not found'

                ], Response::HTTP_NOT_FOUND);
            }

            $update_blog_category_translation = $upd_blog_category_translation->update($request->all());

            if($update_blog_category_translation){
                
                return response()->json([
                    
                    'status_code' => Response::HTTP_OK,
                    'message' => 'Blog Category Translation has been updated',
                
                ], Response::HTTP_OK);

            }


        }catch (QueryException $e) {
            
            if ($e->getCode() === '23000') { // SQLSTATE code for integrity constraint violation
                // Handle unique constraint violation
                return response()->json([

                    'status_code' => Response::HTTP_CONFLICT,
                    'message' => 'Duplicate entry detected',
                    'error' => 'This Blog Category Translation has already been taken.',

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



    public function delete_blog_category_translation($uuid){

        try{

            $del_blog_category_translation = BlogCategoryTranslation::where('uuid', $uuid)->first();
            
            if(!$del_blog_category_translation)
            {
                
                return response()->json([

                    'status_code' => Response::HTTP_NOT_FOUND,
                    'message' => 'Record not found'

                ], Response::HTTP_NOT_FOUND);


            }else{

                $delete_blog_category_translation = BlogCategoryTranslation::destroy($del_blog_category_translation->id);

                if($delete_blog_category_translation){
                
                    return response()->json([
                        
                        'status_code' => Response::HTTP_OK,
                        'message' => 'Blog Category Translation has been deleted',
                    
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


    public function get_own_blog_category_translation($authid){

        $get_own_blog_category_translation = BlogCategoryTranslation::where('auth_id', $authid)->get();

        if(count($get_own_blog_category_translation) > 0)
        {

            return response()->json([

                'status_code' => Response::HTTP_OK,
                'get_own_blog_category_translation' => $get_own_blog_category_translation,

            ], Response::HTTP_OK);


        }else{

            return response()->json([

                'status_code' => Response::HTTP_NOT_FOUND,
                'message' => 'Record Not Found',

            ], Response::HTTP_NOT_FOUND);

        }

    }



    public function get_blog_category_translation(){

        try{

            $get_blog_category_translation = BlogCategoryTranslation::all();

            if($get_blog_category_translation){
                
                return response()->json([
                        
                    'status_code' => Response::HTTP_OK,
                    'get_all_blog_category_translation' => $get_blog_category_translation,

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
