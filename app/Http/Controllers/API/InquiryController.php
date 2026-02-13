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
use App\Models\Menu;
use App\Models\Language;
use App\Models\Inquiry;
use Illuminate\Support\Str;
use App\Traits\MessageTrait;



class InquiryController extends Controller
{
    
    use MessageTrait;
    
    public function add_inquiry(Request $request){
        
        $validator = Validator::make($request->all(), [
             
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'message' => 'required|string',
            'ip_address' => 'nullable|ip',

        ]);


        if($validator->fails()) {
            
            $message = $validator->messages();
            
            return response()->json([
                
                'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'errors' => strval($validator->errors())
            
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try{

            $inquiry = $request->all();
            $inquiry['uuid'] = Str::uuid();
            $inquiry['auth_id'] = Auth::user()->uuid;

            $save_inquiry = Inquiry::create($inquiry);
            
            if($save_inquiry) {

                $data = [
                    
                    'details'=>[
                    
                        'WebsiteName' => config('app.name'),
                        'heading' => "New Inquiry",
                        'FromEmail' => config('app.from_email'),
                        'SignupEmail' => $request->email,
                        'hi_message' => $request->first_name.' '.$request->last_name,
                        'currentDate'  => Carbon::now()->format('d-M-Y'),
                        'inquiry' => $save_inquiry,
                        
                    ]
                
                ];
                
                // USER
                $sendMailUser = Mail::send('emailtemplate/thanks_contact', $data, function($message) use ($data){
                
                    $message->from($data['details']['FromEmail'], $data['details']['WebsiteName']); 
                    
                    $message->to($data['details']['SignupEmail'])->subject($data['details']['heading']);
                
                });

                // ADMIN
                $sendMailAdmin = Mail::send('emailtemplate/inquiry', $data, function($message) use ($data){
                
                    $message->from($data['details']['FromEmail'], $data['details']['WebsiteName']); 
                    
                    $message->to('custombackend@gmail.com')->subject($data['details']['heading']);
                
                });
                
                return response()->json([
                        
                    'status_code' => Response::HTTP_CREATED,
                    'message' => $this->get_message('submit_inquiry'),

                ], Response::HTTP_CREATED);

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
            // Handle general exceptions
            return response()->json([

                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $this->get_message('server_error'),

            ], Response::HTTP_INTERNAL_SERVER_ERROR); 
        }

    }


    public function edit_inquiry($uuid){

        try {
            
            $menu_id = request()->header('menu-uuid');
            $get_menu = Menu::where('uuid', $menu_id)->first();
            $edit_blog_by_id = Inquiry::where('uuid', $uuid)->first();

            if(!$edit_blog_by_id)
            {
                
                return response()->json([

                    'status_code' => Response::HTTP_NOT_FOUND,
                    'message' => $this->get_message('not_found'),

                ], Response::HTTP_NOT_FOUND);

            }
            else{

                $inquiry_read = Inquiry::where('uuid', $uuid)->update(['is_read' => '1']);
                $edit_blog_by_id2 = Inquiry::where('uuid', $uuid)->first();

                return response()->json([

                    'status_code' => Response::HTTP_OK,
                    'data' => $edit_blog_by_id2,

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


    public function delete_inquiry($uuid){

        try{

            $del_inquiry = Inquiry::where('uuid', $uuid)->first();
            
            if(!$del_inquiry)
            {
                
                return response()->json([

                    'status_code' => Response::HTTP_NOT_FOUND,
                    'message' => $this->get_message('not_found'),

                ], Response::HTTP_NOT_FOUND);


            }else{

                $delete_inquiry = Inquiry::destroy($del_inquiry->id);

                if($delete_inquiry){

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
                // 'error' => $e->getMessage(),

            ], Response::HTTP_INTERNAL_SERVER_ERROR); // 500 Internal Server Error
        } 
        
    }


    public function get_all_inquiry(){

        try{

            $menu_id = request()->header('menu-uuid');
            $role_id = Auth::user()->role_id; 
            $get_menu = Menu::where('uuid', $menu_id)->first();
            
            $get_inquiry = Inquiry::all();

            if($get_inquiry){
                
                return response()->json([
                        
                    'status_code' => Response::HTTP_OK,
                    'data' => $get_inquiry,

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
