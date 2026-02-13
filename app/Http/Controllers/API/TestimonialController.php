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
use Spatie\Activitylog\Models\Activity;
use Illuminate\Support\Str;
use App\Traits\MessageTrait;


class TestimonialController extends Controller
{
    
    use MessageTrait;

    public function add_testimonial(Request $request){
        
        $validator = Validator::make($request->all(), [ 
             
            'name' => 'required|string',
            'position' => 'required|string',
            'company' => 'required|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        
        ],[
                
            'image.image' => 'The file must be an image.',
            'image.mimes' => 'The image must be a file of type: jpeg, png, jpg, gif, svg.',
            'image.max' => 'The image must not be greater than 2mb.',
        ]);


        if($validator->fails()) {
            
            $message = $validator->messages();
            
            return response()->json([
                
                'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'errors' => strval($validator->errors())
            
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }


        try{

            $check_if_already = Testimonial::where('name', $request->name)
            ->where('position', $request->position)
            ->where('company', $request->company)
            ->get();

            if(count($check_if_already) > 0){

                return response()->json([

                    'status_code' => Response::HTTP_CONFLICT,
                    'message' => $this->get_message('conflict'),

                ], Response::HTTP_CONFLICT);


            }else{

                $testimonial = $request->all();
                $testimonial['uuid'] = Str::uuid();
                $testimonial['auth_id'] = Auth::user()->uuid;

                if ($request->hasFile('image')) {

                    $file = $request->file('image');
                    $fileName = time() . '_' . $file->getClientOriginalName();
                    $folderName = '/upload_files/testimonials/';
                    $destinationPath = public_path() . $folderName;
            
                    if (!file_exists($destinationPath)) {
                        mkdir($destinationPath, 0755, true);
                    }
            
                    $file->move($destinationPath, $fileName);
                    $testimonial['image'] = $folderName . $fileName;

                }
            
                $save_testimonial = Testimonial::create($testimonial);

                if($save_testimonial) { 
                    
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

    
    
    public function edit_testimonial($uuid){

        try {
            
            $menu_id = request()->header('menu-uuid');
            $get_menu = Menu::where('uuid', $menu_id)->first();
            $edit_testimonial_by_id = Testimonial::where('uuid', $uuid)->first();

            if(!$edit_testimonial_by_id)
            {
                return response()->json([

                    'status_code' => Response::HTTP_NOT_FOUND,
                    'message' => $this->get_message('not_found'),

                ], Response::HTTP_NOT_FOUND);
            }


            if ($edit_testimonial_by_id) {

                return response()->json([

                    'status_code' => Response::HTTP_OK,
                    'data' => $edit_testimonial_by_id,

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


    
    public function update_testimonial(Request $request){

        
        $validator = Validator::make($request->all(), [
            
            'name' => 'required|string',
            'position' => 'required|string',
            'company' => 'required|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',

        ],[
                
            'image.image' => 'The file must be an image.',
            'image.mimes' => 'The image must be a file of type: jpeg, png, jpg, gif, svg.',
            'image.max' => 'The image must not be greater than 2mb.',
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
            $upd_testimonial = Testimonial::where('uuid', $uuid)->first();

            
            
            if (!$upd_testimonial) {

                return response()->json([
                    'status_code' => Response::HTTP_NOT_FOUND,
                    'message' => $this->get_message('not_found'),
                ], Response::HTTP_NOT_FOUND);

            }

            $upd_testimonial->fill($request->all());

            if ($request->hasFile('image')) {

                $file = $request->file('image');
                $fileName = time() . '_' . $file->getClientOriginalName();
                $folderName = '/upload_files/testimonials/';
                $destinationPath = public_path() . $folderName;
                
                if (!file_exists($destinationPath)) {
                    mkdir($destinationPath, 0755, true);
                }

                $file->move($destinationPath, $fileName);
                $upd_testimonial->image = $folderName . $fileName;
            }

            $updatee_testimonial = $upd_testimonial->save();
            
            if($updatee_testimonial){

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



    public function delete_testimonial($uuid){

        try{

            $del_testimonial = Testimonial::where('uuid', $uuid)->first();
            
            if(!$del_testimonial)
            {
                
                return response()->json([

                    'status_code' => Response::HTTP_NOT_FOUND,
                    'message' => $this->get_message('not_found'),

                ], Response::HTTP_NOT_FOUND);


            }else{

                $delete_testimonial = Testimonial::destroy($del_testimonial->id);

                if($delete_testimonial){
                    
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



    public function get_testimonial(){

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

            $get_testimonial = Testimonial::all();

            if($get_testimonial){
                
                return response()->json([
                        
                    'status_code' => Response::HTTP_OK,
                    'new' => $add,
                    'edit' => $edit,
                    'update' => $update,
                    'delete' => $delete,
                    'view' => $view,
                    'view_global' => $view_global,
                    'data' => $get_testimonial,

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
