<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Symfony\Component\HttpFoundation\Response;
use Exception; 
use Mail;
use Auth;
use Session;
use Hash;
use DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use App\Models\Language;
use App\Models\Menu;
use App\Models\Permission_assign;
use App\Models\Testimonial;
use App\Models\WebsiteConfig;
use Spatie\Activitylog\Models\Activity;
use Illuminate\Support\Str;
use App\Traits\MessageTrait;



class WebsiteConfigController extends Controller
{
    
    use MessageTrait;

    public function add_websiteconfig(Request $request){
        
        $validator = Validator::make($request->all(), [ 
             
            'site_name' => 'nullable|required|string|max:255',
            'site_logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'site_favicon' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'meta_keywords' => 'nullable|string',
            'contact_email' => 'nullable|email|max:255',
            'contact_phone' => 'nullable|string|max:20',
            'contact_address' => 'nullable|string|max:255',
            'facebook_url' => 'nullable|url|max:255',
            'twitter_url' => 'nullable|url|max:255',
            'linkedin_url' => 'nullable|url|max:255',
            'instagram_url' => 'nullable|url|max:255',
            'youtube_url' => 'nullable|url|max:255',
            'footer_text' => 'nullable|string',
            'google_analytics_code' => 'nullable|string',
            'maintenance_mode' => 'nullable|boolean',
        
        ],[
                
            'site_logo.site_logo' => 'The file must be an image.',
            'site_logo.mimes' => 'The image must be a file of type: jpeg, png, jpg, gif, svg.',
            'site_logo.max' => 'The image must not be greater than 2mb.',
            'site_favicon.site_favicon' => 'The file must be an image.',
            'site_favicon.mimes' => 'The image must be a file of type: jpeg, png, jpg, gif, svg.',
            'site_favicon.max' => 'The image must not be greater than 2mb.',
        ]);


        if($validator->fails()) {
            
            $message = $validator->messages();
            
            return response()->json([
                
                'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'errors' => strval($validator->errors())
            
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }


        try{

            $check_if_already = WebsiteConfig::where('site_name', $request->site_name)->get();

            if(count($check_if_already) > 0){

                return response()->json([

                    'status_code' => Response::HTTP_CONFLICT,
                    'message' => $this->get_message('conflict'),

                ], Response::HTTP_CONFLICT);


            }else{

                $websiteconfig = $request->all();
                $websiteconfig['uuid'] = Str::uuid();
                $websiteconfig['auth_id'] = Auth::user()->uuid;

                if ($request->hasFile('site_logo')) {

                    $file = $request->file('site_logo');
                    $fileName = time() . '_' . $file->getClientOriginalName();
                    $folderName = '/upload_files/website_logo/';
                    $destinationPath = public_path() . $folderName;
            
                    if (!file_exists($destinationPath)) {
                        mkdir($destinationPath, 0755, true);
                    }
            
                    $file->move($destinationPath, $fileName);
                    $websiteconfig['site_logo'] = $folderName . $fileName;

                }


                if ($request->hasFile('site_favicon')) {

                    $file = $request->file('site_favicon');
                    $fileName = time() . '_' . $file->getClientOriginalName();
                    $folderName = '/upload_files/website_logo/';
                    $destinationPath = public_path() . $folderName;
            
                    if (!file_exists($destinationPath)) {
                        mkdir($destinationPath, 0755, true);
                    }
            
                    $file->move($destinationPath, $fileName);
                    $websiteconfig['site_favicon'] = $folderName . $fileName;

                }
            
                $save_websiteconfig = WebsiteConfig::create($websiteconfig);

                if($save_websiteconfig) { 
                    
                    return response()->json([
                            
                        'status_code' => Response::HTTP_CREATED,
                        'message' => $this->get_message('add'),

                    ], Response::HTTP_CREATED);

                }

            }
            
        
        }catch (QueryException $e) {
            
            if ($e->getCode() === '23000') {
                
                return response()->json([

                    'status_code' => Response::HTTP_CONFLICT,
                    'message' => $this->get_message('conflict'),

                ], Response::HTTP_CONFLICT);
            }

            
            return response()->json([

                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $this->get_message('server_error'),
                 

            ], Response::HTTP_INTERNAL_SERVER_ERROR); 
        

        }catch (\Exception $e) { 
            
            return response()->json([

                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $this->get_message('server_error'),
                

            ], Response::HTTP_INTERNAL_SERVER_ERROR); 
        }
        

    }

    
    
    public function edit_websiteconfig($uuid){

        try {
            
            $menu_id = request()->header('menu-uuid');
            $get_menu = Menu::where('uuid', $menu_id)->first();
            $edit_websiteconfig_by_id = WebsiteConfig::where('uuid', $uuid)->first();

            if(!$edit_websiteconfig_by_id)
            {
                return response()->json([

                    'status_code' => Response::HTTP_NOT_FOUND,
                    'message' => $this->get_message('not_found'),

                ], Response::HTTP_NOT_FOUND);
            }


            if ($edit_websiteconfig_by_id) {

                return response()->json([

                    'status_code' => Response::HTTP_OK,
                    'data' => $edit_websiteconfig_by_id,

                ], Response::HTTP_OK);

            }


        }catch(\Exception $e) { 
            // Handle general exceptions
            return response()->json([

                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $this->get_message('server_error'),

            ], Response::HTTP_INTERNAL_SERVER_ERROR); // 500 Internal Server Error
        }

    }


    
    public function update_websiteconfig(Request $request){

        
        $validator = Validator::make($request->all(), [
            
            'site_name' => 'nullable|required|string|max:255',
            'site_logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'site_favicon' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'meta_keywords' => 'nullable|string',
            'contact_email' => 'nullable|email|max:255',
            'contact_phone' => 'nullable|string|max:20',
            'contact_address' => 'nullable|string|max:255',
            'facebook_url' => 'nullable|url|max:255',
            'twitter_url' => 'nullable|url|max:255',
            'linkedin_url' => 'nullable|url|max:255',
            'instagram_url' => 'nullable|url|max:255',
            'youtube_url' => 'nullable|url|max:255',
            'footer_text' => 'nullable|string',
            'google_analytics_code' => 'nullable|string',
            'maintenance_mode' => 'nullable|boolean',

        ],[
                
            'site_logo.site_logo' => 'The file must be an image.',
            'site_logo.mimes' => 'The image must be a file of type: jpeg, png, jpg, gif, svg.',
            'site_logo.max' => 'The image must not be greater than 2mb.',
            'site_favicon.site_favicon' => 'The file must be an image.',
            'site_favicon.mimes' => 'The image must be a file of type: jpeg, png, jpg, gif, svg.',
            'site_favicon.max' => 'The image must not be greater than 2mb.',
        ]);
        
        
        if($validator->fails()) {
            
            $message = $validator->messages();
            
            return response()->json([
                
                'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'errors' => strval($validator->errors())
            
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        
        try{
            
            $menu_id = request()->header('menu-uuid'); 
            $uuid = request()->header('uuid');
            $upd_websiteconfig = WebsiteConfig::where('uuid', $uuid)->first();

    
            
            if (!$upd_websiteconfig) {

                return response()->json([

                    'status_code' => Response::HTTP_NOT_FOUND,
                    'message' => $this->get_message('not_found'),

                ], Response::HTTP_NOT_FOUND);

            }

            $upd_websiteconfig->fill($request->all());

            if ($request->hasFile('site_logo')) {

                $file = $request->file('site_logo');
                $fileName = time() . '_' . $file->getClientOriginalName();
                $folderName = '/upload_files/website_logo/';
                $destinationPath = public_path() . $folderName;
                
                if (!file_exists($destinationPath)) {
                    mkdir($destinationPath, 0755, true);
                }

                $file->move($destinationPath, $fileName);
                $upd_websiteconfig->site_logo = $folderName . $fileName;
            }

            if ($request->hasFile('site_favicon')) {

                $file = $request->file('site_favicon');
                $fileName = time() . '_' . $file->getClientOriginalName();
                $folderName = '/upload_files/website_logo/';
                $destinationPath = public_path() . $folderName;
                
                if (!file_exists($destinationPath)) {
                    mkdir($destinationPath, 0755, true);
                }

                $file->move($destinationPath, $fileName);
                $upd_websiteconfig->site_favicon = $folderName . $fileName;
            }

            $updatee_websiteconfig = $upd_websiteconfig->save();
            
            if($updatee_websiteconfig){

                return response()->json([
            
                    'status_code' => Response::HTTP_OK,
                    'message' => $this->get_message('update'),
    
                ], Response::HTTP_OK); 

            }
            

        }catch (\Exception $e) { 
            // Handle general exceptions
            return response()->json([

                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $this->get_message('server_error'),

            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
        
        
    }



    public function delete_websiteconfig($uuid){

        try{

            $del_websiteconfig = WebsiteConfig::where('uuid', $uuid)->first();
            
            if(!$del_websiteconfig)
            {
                
                return response()->json([

                    'status_code' => Response::HTTP_NOT_FOUND,
                    'message' => $this->get_message('not_found'),

                ], Response::HTTP_NOT_FOUND);


            }else{

                $delete_websiteconfig = WebsiteConfig::destroy($del_websiteconfig->id);

                if($delete_websiteconfig){
                    
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



    public function get_websiteconfig(){

        try{

            $menu_id = request()->header('menu-uuid');
            $role_id = Auth::user()->role_id; 
            $get_menu = Menu::where('uuid', $menu_id)->first();

            $add = 0;
            $edit = 0;
            $update = 0;
            $delete = 0;
            $view = 0;
            $view_global = 0;
            
            
            if($role_id != "1"){

                $check_permission = Permission_assign::where('role_id', $role_id)
                ->where('menu_id', $get_menu->id)
                ->get();

                foreach ($check_permission as $permission) {
                    
                    if ($permission->permission_id == '1') {
                        $add = 1;
                    }

                    if ($permission->permission_id == '2') {
                        $edit = 1;
                    }

                    if ($permission->permission_id == '3') {
                        $update = 1;
                    }

                    if ($permission->permission_id == '4') {
                        $delete = 1;
                    }

                    if ($permission->permission_id == '5') {
                        $view = 1;
                    }

                    if ($permission->permission_id == '6') {
                        $view_global = 1;
                    }

                }


            }else{

                $add = 1;
                $edit = 1;
                $update = 1;
                $delete = 1;
                $view = 1;
                $view_global = 1;

            }

            $get_websiteconfig = WebsiteConfig::all();

            if($get_websiteconfig){
                
                return response()->json([
                        
                    'status_code' => Response::HTTP_OK,
                    'new' => $add,
                    'edit' => $edit,
                    'update' => $update,
                    'delete' => $delete,
                    'view' => $view,
                    // 'view_global' => $view_global,
                    'data' => $get_websiteconfig,

                ], Response::HTTP_OK);
    
            }

        }catch (\Exception $e) { 
            // Handle general exceptions
            return response()->json([

                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $this->get_message('server_error'),
                

            ], Response::HTTP_INTERNAL_SERVER_ERROR); // 500 Internal Server Error
        } 

    }


}
