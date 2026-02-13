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
use App\Models\Menu;
use App\Models\Language;
use App\Models\Menu_translation;
use App\Models\Permission_assign;
use App\Models\User_special_permission;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon; 
use Illuminate\Support\Str;
use App\Traits\MessageTrait;


class PluginController extends Controller
{

    use MessageTrait;
    
    public function get_plugin()
    {
        
        try {
            
            // Fetch all parent menus with parent_id = 0
            $menus = Menu::where('status' , '1')->where('is_plugin' , '1')->orderBy('sort_id')->get();

            return response()->json([
                            
                'status_code' => Response::HTTP_OK,
                'data' => $menus,
            
            ], Response::HTTP_OK);


        }catch (\Exception $e) { 

            return response()->json([

                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $this->get_message('server_error'),

            ], Response::HTTP_INTERNAL_SERVER_ERROR);
            
        }
        
        

    }


    public function plugin_status(Request $request)
    {
        
        $validator = Validator::make($request->all(), [   
            'is_plugin_active' => 'required'
        ]);

        if($validator->fails()) {
            
            $message = $validator->messages();
            
            return response()->json([
                
                'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'errors' => strval($validator->errors())
            
            ], Response::HTTP_UNPROCESSABLE_ENTITY);

        }


        try {
            

            $uuid = request()->header('uuid');
            $upd_plugin_status = Menu::where('uuid', $uuid)->first();

            if (!$upd_plugin_status) {
                return response()->json([
                    'status_code' => Response::HTTP_NOT_FOUND,
                    'message' => $this->get_message('not_found'),
                ], Response::HTTP_NOT_FOUND);
            }
            
            $upd_plugin_status->is_plugin_active = $request->is_plugin_active;
            $change_plugin_status = $upd_plugin_status->save();

            if($change_plugin_status){

                return response()->json([
                        
                    'status_code' => Response::HTTP_OK,
                    'message' => $this->get_message('update'),
                
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
