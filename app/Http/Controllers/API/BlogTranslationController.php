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
use App\Models\BlogTranslation;
use Illuminate\Support\Str;


class BlogTranslationController extends Controller
{
    
    public function add_blog_translation(Request $request){
        
        $validator = Validator::make($request->all(), [ 
             
            'blog_id' => 'required|integer',
            'language_id' => 'required|integer',
            'blog_name' => 'required|string',
            'short_description' => 'nullable|string',
            'description' => 'nullable|string', 
            'thumbnail_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'banner' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'meta_title' => 'nullable|string',
            'meta_img' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'meta_description' => 'nullable|string',
            'meta_keywords' => 'nullable|string',
            'author' => 'nullable|string|max:255',
            'auth_id' => 'nullable|string',
        
        ],[
                
            'thumbnail_image.thumbnail_image' => 'The file must be an image.',
            'thumbnail_image.mimes' => 'The image must be a file of type: jpeg, png, jpg, gif, svg.',
            'thumbnail_image.max' => 'The image must not be greater than 2mb.',
            'banner.banner' => 'The file must be an image.',
            'banner.mimes' => 'The image must be a file of type: jpeg, png, jpg, gif, svg.',
            'banner.max' => 'The image must not be greater than 2mb.',
            'meta_img.meta_img' => 'The file must be an image.',
            'meta_img.mimes' => 'The image must be a file of type: jpeg, png, jpg, gif, svg.',
            'meta_img.max' => 'The image must not be greater than 2mb.',
        ]);


        if($validator->fails()) {
            
            $message = $validator->messages();
            
            return response()->json([
                
                'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'errors' => strval($validator->errors())
            
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try{

            $check_if_already = BlogTranslation::where('blog_id', $request->blog_id)->where('language_id', $request->language_id)->where('blog_name', $request->blog_name)->get();

            if(count($check_if_already) > 0){

                return response()->json([

                    'status_code' => Response::HTTP_CONFLICT,
                    'message' => 'This Blog Translation has already been taken.',

                ], Response::HTTP_CONFLICT); // 409 Conflict 


            }else{

                $blog_translation = $request->all();
                $blog_translation['uuid'] = Str::uuid();

                if ($request->hasFile('thumbnail_image')) {
                    $file = $request->file('thumbnail_image');
                    $fileName = time() . '_' . $file->getClientOriginalName(); // Prepend timestamp for unique filename
                    $folderName = '/upload_files/thumbnail_image/';
                    $destinationPath = public_path() . $folderName;
            
                    
                    if (!file_exists($destinationPath)) {
                        mkdir($destinationPath, 0755, true);
                    }
            
                    
                    $file->move($destinationPath, $fileName);
            
                    
                    $blog_translation['thumbnail_image'] = $folderName . $fileName;
                }


                if ($request->hasFile('banner')) {
                    $file = $request->file('banner');
                    $fileName = time() . '_' . $file->getClientOriginalName(); // Prepend timestamp for unique filename
                    $folderName = '/upload_files/banner/';
                    $destinationPath = public_path() . $folderName;
            
                    
                    if (!file_exists($destinationPath)) {
                        mkdir($destinationPath, 0755, true);
                    }
            
                    
                    $file->move($destinationPath, $fileName);
            
                    
                    $blog_translation['banner'] = $folderName . $fileName;
                }


                if ($request->hasFile('meta_img')) {
                    $file = $request->file('meta_img');
                    $fileName = time() . '_' . $file->getClientOriginalName(); // Prepend timestamp for unique filename
                    $folderName = '/upload_files/meta_img/';
                    $destinationPath = public_path() . $folderName;
            
                    
                    if (!file_exists($destinationPath)) {
                        mkdir($destinationPath, 0755, true);
                    }
            
                    
                    $file->move($destinationPath, $fileName);
            
                    
                    $blog_translation['meta_img'] = $folderName . $fileName;
                }

                
                $save_blog_translation = BlogTranslation::create($blog_translation);

                if($save_blog_translation) {
                    
                    return response()->json([
                            
                        'status_code' => Response::HTTP_CREATED,
                        'message' => 'Blog Translation add successfully',

                    ], Response::HTTP_CREATED);

                }

            }
            
        
        }catch (QueryException $e) {
            
            if ($e->getCode() === '23000') { // SQLSTATE code for integrity constraint violation
                
                return response()->json([

                    'status_code' => Response::HTTP_CONFLICT,
                    'message' => 'Duplicate entry detected',
                    'error' => 'This Blog Translation has already been taken.',

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


    public function edit_blog_translation($uuid){

        $get_blog_translation = BlogTranslation::where('uuid', $uuid)->first();

        if($get_blog_translation)
        {
            
            return response()->json([

                'status_code' => Response::HTTP_OK,
                'get_blog_translation' => $get_blog_translation,

            ], Response::HTTP_OK);


        }else{

            return response()->json([

                'status_code' => Response::HTTP_NOT_FOUND,
                'message' => 'Record Not Found',

            ], Response::HTTP_NOT_FOUND);

        }

    }



    public function update_blog_translation(Request $request){

        $validator = Validator::make($request->all(), [ 
             
            'uuid' => 'required',
            'blog_id' => 'required|integer',
            'language_id' => 'required|integer',
            'blog_name' => 'required|string',
            'short_description' => 'nullable|string',
            'description' => 'nullable|string', 
            'thumbnail_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'banner' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'meta_title' => 'nullable|string',
            'meta_img' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'meta_description' => 'nullable|string',
            'meta_keywords' => 'nullable|string',
            'author' => 'nullable|string|max:255',
            'auth_id' => 'nullable|string',
        
        ],[
                
            'thumbnail_image.thumbnail_image' => 'The file must be an image.',
            'thumbnail_image.mimes' => 'The image must be a file of type: jpeg, png, jpg, gif, svg.',
            'thumbnail_image.max' => 'The image must not be greater than 2mb.',
            'banner.banner' => 'The file must be an image.',
            'banner.mimes' => 'The image must be a file of type: jpeg, png, jpg, gif, svg.',
            'banner.max' => 'The image must not be greater than 2mb.',
            'meta_img.meta_img' => 'The file must be an image.',
            'meta_img.mimes' => 'The image must be a file of type: jpeg, png, jpg, gif, svg.',
            'meta_img.max' => 'The image must not be greater than 2mb.',
        ]);
        
        
        if($validator->fails()) {
            
            $message = $validator->messages();
            
            return response()->json([
                
                'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'errors' => strval($validator->errors())
            
            ], Response::HTTP_UNPROCESSABLE_ENTITY);

        }

        try {
            
            $uuid = $request->uuid;
            $upd_blog_translation = BlogTranslation::where('uuid', $uuid)->first();

            if (!$upd_blog_translation) {
                return response()->json([
                    'status_code' => Response::HTTP_NOT_FOUND,
                    'message' => 'Record not found'
                ], Response::HTTP_NOT_FOUND);
            }

            if ($request->hasFile('thumbnail_image')) {
                $file = $request->file('thumbnail_image');
                $fileName = time() . '_' . $file->getClientOriginalName();
                $folderName = '/upload_files/thumbnail_image/';
                $destinationPath = public_path() . $folderName;
                
                if (!file_exists($destinationPath)) {
                    mkdir($destinationPath, 0755, true);
                }

                $file->move($destinationPath, $fileName);

                $upd_blog_translation->thumbnail_image = $folderName . $fileName;
            }


            if ($request->hasFile('banner')) {

                $file = $request->file('banner');
                $fileName = time() . '_' . $file->getClientOriginalName();
                $folderName = '/upload_files/banner/';
                $destinationPath = public_path() . $folderName;
                
                if (!file_exists($destinationPath)) {
                    mkdir($destinationPath, 0755, true);
                }

                $file->move($destinationPath, $fileName);

                $upd_blog_translation->banner = $folderName . $fileName;
            }


            if ($request->hasFile('meta_img')) {
                $file = $request->file('meta_img');
                $fileName = time() . '_' . $file->getClientOriginalName();
                $folderName = '/upload_files/meta_img/';
                $destinationPath = public_path() . $folderName;
                
                if (!file_exists($destinationPath)) {
                    mkdir($destinationPath, 0755, true);
                }

                $file->move($destinationPath, $fileName);

                $upd_blog_translation->meta_img = $folderName . $fileName;
            }
            
            
            $update_blog_translation = $upd_blog_translation->update($request->except(['thumbnail_image', 'banner', 'meta_img'])); 

            return response()->json([
                'status_code' => Response::HTTP_OK,
                'message' => 'Blog Translation updated successfully'
            ], Response::HTTP_OK);

        }catch (QueryException $e) {
            
            if ($e->getCode() === '23000') { // SQLSTATE code for integrity constraint violation
                // Handle unique constraint violation
                return response()->json([

                    'status_code' => Response::HTTP_CONFLICT,
                    'message' => 'Duplicate entry detected',
                    'error' => 'This Blog Translation has already been taken.',

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



    public function delete_blog_translation($uuid){

        try{

            $del_blog_translation = BlogTranslation::where('uuid', $uuid)->first();
            
            if(!$del_blog_translation)
            {
                
                return response()->json([

                    'status_code' => Response::HTTP_NOT_FOUND,
                    'message' => 'Record not found'

                ], Response::HTTP_NOT_FOUND);


            }else{

                $delete_blog_translation = BlogTranslation::destroy($del_blog_translation->id);

                if($delete_blog_translation){ 
                
                    return response()->json([
                        
                        'status_code' => Response::HTTP_OK,
                        'message' => 'Blog Translation has been deleted',
                    
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


    public function get_own_blog_translation($authid){

        $get_own_blog_translation = BlogTranslation::where('auth_id', $authid)->get();

        if(count($get_own_blog_translation) > 0)
        {
            $get_own_blog_translation->base_url = config('app.base_url');
            return response()->json([

                'status_code' => Response::HTTP_OK,
                'get_own_blog_translation' => $get_own_blog_translation,

            ], Response::HTTP_OK);

        }else{

            return response()->json([

                'status_code' => Response::HTTP_NOT_FOUND,
                'message' => 'Record Not Found',

            ], Response::HTTP_NOT_FOUND);

        }

    }



    public function get_all_blog_translation(){

        try{

            $get_all_blog_translation = BlogTranslation::all();

            if($get_all_blog_translation){

                return response()->json([
                        
                    'status_code' => Response::HTTP_OK,
                    'get_all_blog_translation' => $get_all_blog_translation,

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
