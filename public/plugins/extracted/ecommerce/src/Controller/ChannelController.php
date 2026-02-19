<?php

namespace App\Http\Controllers\API\Ecommerce;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Symfony\Component\HttpFoundation\Response;
use Mail;
use Auth;
use Session;
use Hash;
use DB;
use App\Models\Ecommerce\Channel;
use App\Models\Ecommerce\Channel_translation;
use App\Models\Menu;
use App\Models\Permission_assign;
use App\Models\Language;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon; 
use Illuminate\Support\Str;
use App\Services\PermissionService;
use App\Traits\MessageTrait;
use DeepCopy\f001\B;

class ChannelController extends Controller
{
    
    use MessageTrait;
    protected $permissionService;

    public function __construct(PermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }


    public function get_channel(){

        try{

            $menuUuid = request()->header('menu-uuid');
            $permissions = $this->permissionService->checkPermissions($menuUuid);
            $get_all_channel = Channel::orderBy('id', 'desc');
            if ($permissions['view']) {
                if (!$permissions['viewglobal']) {
                    $get_all_channel = $get_all_channel->where('auth_id', Auth::user()->uuid);
                }
            }else{
                if (Auth::user()->hasPermission('viewglobal')) {
                    $get_all_channel = $get_all_channel;
                } else {
                    return response()->json([
                        'message' => 'You do not have permission to view this menu'
                    ], Response::HTTP_FORBIDDEN);
                }
            }
            $get_all_channel = $get_all_channel->get();

            return response()->json([
                'status_code'=>200,
                'permissions' => $permissions,
                'data'=>$get_all_channel
            ],200);

        }catch (\Exception $e) { 
            // Handle general exceptions
            return response()->json([ 
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $this->get_message('server_error'), 
            ], Response::HTTP_INTERNAL_SERVER_ERROR); // 500 Internal Server Error
        } 

    }


    public function add_channel(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'channel' => 'required|max:255',  
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'errors' => strval($validator->errors())
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            // Create Channel
            $channel = new Channel();
            $channel->uuid = Str::uuid();
            $channel->auth_id = Auth::user()->uuid;
            $channel->channel = $request->channel; 
            $channel->order_level = $request->order_level ?: 0; 
            $channel->status = $request->status;
            $channel->save();
        
            return response()->json([
                'status_code' => 200,
                'message' => $this->get_message('update'),
            ], 200);
        
        }catch (\Illuminate\Database\QueryException $e) {
            
            if ($e->errorInfo[1] == 1062) { // Error code for duplicate entry
                return response()->json([
                    'status_code' => 409,
                    'message' => 'Duplicate entry: The channel already exists.',
                ], 409);
            }

            return response()->json([
                'status_code' => 500,
                // 'message' => $e->getMessage(),
                'message' => $this->get_message('server_error'),
            ], 500);

        } catch (\Throwable $th) {

            return response()->json([
                'status_code' => 500,
                // 'message' => $th->getMessage(),
                'message' => $this->get_message('server_error'),
            ], 500);

        }
        
    }


    public function edit_channel($uuid){

        try {
            
            $edit_channel_by_id = Channel::where('uuid', $uuid)->first();

            if(!$edit_channel_by_id)
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
                    
                    $check_channel_translation = Channel_translation::where('channel_id', $edit_channel_by_id->id)
                    ->where('language_id', $language->id)
                    ->where('status', '1')->first();

                    if($check_channel_translation)
                    {
                        
                       

                    }
                    else{

                        $save_channel_translation = Channel_translation::insert([
                            ['uuid' => Str::uuid(), 'channel_id' => $edit_channel_by_id->id, 'channel' => $edit_channel_by_id->channel , 'language_id' => $language->id , 'lang' => $language->app_language_code , 'auth_id' => $auth_id , 'created_at' => $now, 'updated_at' => $now],
                        ]);

                    }


                }


            }

            $channel_translations = Channel_translation::where('channel_id', $edit_channel_by_id->id)
            ->where('channel_translations.status', '1')
            ->join('languages', 'channel_translations.language_id', '=', 'languages.id')
            ->select('languages.code as language_code', 'languages.name as language_name' , 'languages.flag as flag' , 'languages.rtl as dir', 'channel_translations.*')
            ->get();

            
            if ($edit_channel_by_id) {

                $edit_channel_by_id->translations = $channel_translations;
       
                return response()->json([

                    'status_code' => Response::HTTP_OK,
                    'data' => $edit_channel_by_id,

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


    public function update_channel(Request $request)
    {

        $uuid = request()->header('uuid');

        try {
            // Find the channel
            $channel = Channel::where('uuid', $uuid)->first();
            // Update channel fields
            $channel->channel = $request->channel; 
            $channel->order_level = $request->order_level ?: 0; 
            $channel->status = $request->status;
        
 
            $channel->save();

            $updatedTranslations = false;

            foreach ($request->all() as $key => $value) {
                
                if (strpos($key, 'name_') === 0) {
                    
                    $languageCode = substr($key, 5);
            
                    $languageId = DB::table('languages')->where('code', $languageCode)->value('id');
            
                    if($languageId){
                        
                        Channel_translation::where('language_id', $languageId)
                        ->where('channel_id', $channel->id)
                        ->update(['channel' => $value]);

                        $updatedTranslations = true;
                    }

                }

            }


             

             

            if ($updatedTranslations) {
               
                $get_active_language = Language::where('status', '1')->where('is_default', '1')->first();
                $get_role_trans_by_def_lang = Channel_translation::where('channel_id', $channel->id)
                ->where('language_id', $get_active_language->id)
                ->first();
    
                $upd_channel2 = DB::table('channels')
                ->where('id', $channel->id)
                ->update([
                    'channel' => $get_role_trans_by_def_lang->channel,
                ]);

            }

            return response()->json([
                'status_code' => 200,
                'message' => 'Channel has been updated',
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $th->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    public function delete_channel($uuid){

        try{

            $del_channel = Channel::where('uuid', $uuid)->first();
            
            if(!$del_channel)
            {
                
                return response()->json([

                    'status_code' => Response::HTTP_NOT_FOUND,
                    'message' => $this->get_message('not_found'),

                ], Response::HTTP_NOT_FOUND);


            }else{

                $delete_channel = Channel::destroy($del_channel->id);

                if($delete_channel){
                    
                    $del_channel_translation = Channel_translation::where('channel_id', $del_channel->id)->delete();

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

    
    public function updateChannelStatus(Request $request, string $id)
    {
        $request->validate([
            'status' => 'required|in:0,1', // Ensure status is either 0 or 1
        ]);

        try {
            // Find the category by UUID and active status
            $channel = Channel::where('uuid', $id)->first();

            if ($channel) {
                // Update the status
                $channel->status = $request->status;
                $channel->save();

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


    public function updateChannelFeatured(Request $request, string $id)
    {
        $request->validate([
            'featured' => 'required|in:0,1',
        ]);

        try {
            // Find the category by UUID and active featured
            $channel = Channel::where('uuid', $id)->first();

            if ($channel) {
                // Update the featured
                $channel->featured = $request->featured;
                $channel->save();

                return response()->json([
                    'featured_code' => 200,
                    'message' => $this->get_message('update'),
                ], 200);
            } else {
                return response()->json([
                    'featured_code' => 404,
                    'message' => $this->get_message('not_found'),
                ], 404);
            }
        } catch (\Throwable $e) {
            return response()->json([
                'featured_code' => 500,
                'message' => $this->get_message('server_error'),
            ], 500);
        }

    }


    public function get_active_channels(){

        try{

            $get_all_active_channel = Channel::where('status', '1')->get();

            if($get_all_active_channel){
                
                return response()->json([
                    'status_code' => Response::HTTP_OK,
                    'data' => $get_all_active_channel,
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
