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
use App\Models\NewsletterSubscription;
use Illuminate\Support\Str;
use App\Traits\MessageTrait;


class NewsletterController extends Controller
{
    
    use MessageTrait;
    
    public function add_newsletter(Request $request){
        
        $validator = Validator::make($request->all(), [
             
            'email' => 'required|email|max:255',

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

            $save_newsletter = NewsletterSubscription::create($inquiry);
            
            if($save_newsletter) {

                $data = [
                    
                    'details'=>[
                    
                        'WebsiteName' => config('app.name'),
                        'heading' => "SUBSCRIBER",
                        'FromEmail' => config('app.from_email'),
                        'SignupEmail' => $request->email,
                        'hi_message' => $request->first_name.' '.$request->last_name,
                        'currentDate'  => Carbon::now()->format('d-M-Y'),
                        'newsletter' => $save_newsletter,
                        
                    ]
                
                ];
                
                // USER
                $sendMailUser = Mail::send('emailtemplate/thanks_subscriber', $data, function($message) use ($data){
                
                    $message->from($data['details']['FromEmail'], $data['details']['WebsiteName']); 
                    
                    $message->to($data['details']['SignupEmail'])->subject($data['details']['heading']);
                
                });

                // ADMIN
                $sendMailAdmin = Mail::send('emailtemplate/newsletter', $data, function($message) use ($data){
                
                    $message->from($data['details']['FromEmail'], $data['details']['WebsiteName']); 
                    
                    $message->to('custombackend@gmail.com')->subject($data['details']['heading']);
                
                });
                
                return response()->json([
                        
                    'status_code' => Response::HTTP_CREATED,
                    'message' => $this->get_message('submit_newsletter'),

                ], Response::HTTP_CREATED);

            }

        
        }catch (QueryException $e) {
            
            if ($e->getCode() === '23000') {
                
                return response()->json([

                    'status_code' => Response::HTTP_CONFLICT,
                    'message' => $this->get_message('already_subscribe'),

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



    public function edit_newsletter($uuid){

        try {
            
            $menu_id = request()->header('menu-uuid');
            $get_menu = Menu::where('uuid', $menu_id)->first();
            $edit_subscriber_by_id = NewsletterSubscription::where('uuid', $uuid)->first();

            if(!$edit_subscriber_by_id)
            {
                
                return response()->json([

                    'status_code' => Response::HTTP_NOT_FOUND,
                    'message' => $this->get_message('not_found'),

                ], Response::HTTP_NOT_FOUND);

            }
            else{

                return response()->json([

                    'status_code' => Response::HTTP_OK,
                    'data' => $edit_subscriber_by_id,

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


    public function delete_newsletter($uuid){

        try{

            $del_newsletter = NewsletterSubscription::where('uuid', $uuid)->first();
            
            if(!$del_newsletter)
            {
                
                return response()->json([

                    'status_code' => Response::HTTP_NOT_FOUND,
                    'message' => $this->get_message('not_found'),

                ], Response::HTTP_NOT_FOUND);


            }else{

                $delete_newsletter = NewsletterSubscription::destroy($del_newsletter->id);

                if($delete_newsletter){

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


    public function get_all_newsletter(){

        try{

            $menu_id = request()->header('menu-uuid');
            $role_id = Auth::user()->role_id; 
            $get_menu = Menu::where('uuid', $menu_id)->first();
            
            $get_newsletter = NewsletterSubscription::all();

            if($get_newsletter){
                
                return response()->json([
                        
                    'status_code' => Response::HTTP_OK,
                    'data' => $get_newsletter,

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
