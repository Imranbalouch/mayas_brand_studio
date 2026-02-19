<?php

namespace App\Http\Controllers\API\Ecommerce;

use Hash;
use Mail;
use Session;
use Carbon\Carbon; 
use App\Models\Menu;
use DeepCopy\f001\B;
use App\Models\Ecommerce\Order;
use App\Models\Ecommerce\Giftcard;
use App\Models\Language;
use Illuminate\Support\Str;
use App\Traits\MessageTrait;
use Illuminate\Http\Request;
use App\Models\Ecommerce\OrderTimeLine;
use App\Models\Ecommerce\GiftCardTimeLine;
use App\Models\Ecommerce\GiftcardReceiving;
use App\Models\Permission_assign;
use Illuminate\Support\Facades\DB;
use App\Services\PermissionService;
use App\Http\Controllers\Controller;
use App\Models\Ecommerce\Giftcard_translation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class GiftcardController extends Controller
{
    
    use MessageTrait;
    protected $permissionService;

    public function __construct(PermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }


    public function get_giftcard(){

        try{

            $menuUuid = request()->header('menu-uuid');
            $permissions = $this->permissionService->checkPermissions($menuUuid);
            $get_all_giftcard = Giftcard::with('customer')->orderBy('id', 'desc');
            if ($permissions['view']) {
                if (!$permissions['viewglobal']) {
                    $get_all_giftcard = $get_all_giftcard->where('auth_id', Auth::user()->uuid);
                }
            }else{
                if (Auth::user()->hasPermission('viewglobal')) {
                    $get_all_giftcard = $get_all_giftcard;
                } else {
                    return response()->json([
                        'message' => 'You do not have permission to view this menu'
                    ], Response::HTTP_FORBIDDEN);
                }
            }
            $get_all_giftcard = $get_all_giftcard->get();
 
            //dd($get_all_giftcard);
            return response()->json([
                'status_code'=>200,
                'permissions' => $permissions,
                'data'=>$get_all_giftcard
            ],200);

        }catch (\Exception $e) { 
            // Handle general exceptions
            return response()->json([

                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $this->get_message('server_error'),
                

            ], Response::HTTP_INTERNAL_SERVER_ERROR); // 500 Internal Server Error
        } 

    }


    public function add_giftcard(Request $request)
    {
        DB::beginTransaction();
        $validator = Validator::make($request->all(), [
            'giftcard' => 'required|max:255',
            'code' => 'required|min:8|max:20|unique:giftcards',
            'value' => '', 
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'errors' => strval($validator->errors())
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            // Create Giftcard
            $giftcard = new Giftcard();
            $giftcard->uuid = Str::uuid();
            $giftcard->auth_id = Auth::user()->uuid;
            $giftcard->giftcard = $request->giftcard;
            $giftcard->code = $request->code;
            $giftcard->value = $request->value ?? 0;
            $giftcard->balance = $giftcard->value ?? 0;
            $giftcard->customer_id = $request->customer_id;
            $giftcard->status = $request->status;
            $giftcard->note = $request->note;
            $giftcard->expiry_date = $request->expiry_date;
            
              
            $giftcard->save();
    
            GiftCardTimeLine::create([
            'uuid' => Str::uuid(),
            'auth_id' => Auth::user()->uuid,
            'giftcard_id' => $giftcard->uuid,
            'message' => 'Giftcard Created',
            'status' => true,
        ]);

        // Commit the transaction
        DB::commit();
        
            return response()->json([
                'status_code' => 200,
                'message' => $this->get_message('update'),
            ], 200);
        
        }catch (\Illuminate\Database\QueryException $e) {
            DB::rollBack();
           // dd($e);
            if ($e->errorInfo[1] == 1062) { // Error code for duplicate entry
                return response()->json([
                    'status_code' => 409,
                    'message' => 'Duplicate entry: The giftcard already exists.',
                ], 409);
            }

            return response()->json([
                'status_code' => 500,
                'error' => $e->getMessage(),
                'message' => $this->get_message('server_error'),
            ], 500);

        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'status_code' => 500,
                'error' => $th->getMessage(),
                'message' => $this->get_message('server_error'),
            ], 500);

        }
        
    }


    public function edit_giftcard($uuid){

        try {
            
            $edit_giftcard_by_id = Giftcard::with('user', 'giftcard_timeline')->where('uuid', $uuid)->first();

            if(!$edit_giftcard_by_id)
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
                    
                    $check_giftcard_translation = Giftcard_translation::where('giftcard_id', $edit_giftcard_by_id->id)
                    ->where('language_id', $language->id)
                    ->where('status', '1')->first();

                    if($check_giftcard_translation)
                    {
                        
                       

                    }
                    else{

                        $save_giftcard_translation = Giftcard_translation::insert([
                            ['uuid' => Str::uuid(), 'giftcard_id' => $edit_giftcard_by_id->id, 'giftcard' => $edit_giftcard_by_id->giftcard , 'language_id' => $language->id , 'lang' => $language->app_language_code , 'auth_id' => $auth_id , 'created_at' => $now, 'updated_at' => $now],
                        ]);

                    }


                }


            }

            $giftcard_translations = Giftcard_translation::where('giftcard_id', $edit_giftcard_by_id->id)
            ->where('giftcard_translations.status', '1')
            ->join('languages', 'giftcard_translations.language_id', '=', 'languages.id')
            ->select('languages.code as language_code', 'languages.name as language_name' , 'languages.flag as flag' , 'languages.rtl as dir', 'giftcard_translations.*')
            ->get();

            
            if ($edit_giftcard_by_id) {

                $edit_giftcard_by_id->translations = $giftcard_translations;
       
                return response()->json([

                    'status_code' => Response::HTTP_OK,
                    'data' => $edit_giftcard_by_id,

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


    public function update_giftcard(Request $request)
    {
         DB::beginTransaction();

        $uuid = request()->header('uuid');

        try {
            // Find the giftcard
            $giftcard = Giftcard::where('uuid', $uuid)->first();
            // Update giftcard fields
           // $giftcard->giftcard = $request->giftcard;
           // $giftcard->code = $request->code;
           // $giftcard->value = $request->value;
            $giftcard->status = $request->status;
            $giftcard->customer_id = $request->customer_id;
            $giftcard->note = $request->note;
            $giftcard->expiry_date = $request->expiry_date;

            $giftcard->save();

            $updatedTranslations = false;

            foreach ($request->all() as $key => $value) {
                
                if (strpos($key, 'name_') === 0) {
                    
                    $languageCode = substr($key, 5);
            
                    $languageId = DB::table('languages')->where('code', $languageCode)->value('id');
            
                    if($languageId){
                        
                        Giftcard_translation::where('language_id', $languageId)
                        ->where('giftcard_id', $giftcard->id)
                        ->update(['giftcard' => $value]);

                        $updatedTranslations = true;
                    }

                }

            }
 

            foreach ($request->all() as $key => $value) {
                
                if (strpos($key, 'note_') === 0) {
                    
                    $languageCode = substr($key, 12);
            
                    $languageId = DB::table('languages')->where('code', $languageCode)->value('id');
            
                    if($languageId){
                        
                        Giftcard_translation::where('language_id', $languageId)
                        ->where('giftcard_id', $giftcard->id)
                        ->update(['note' => $value]);

                        $updatedTranslations = true;
                    }

                }

            }


            if ($updatedTranslations) {
               
                $get_active_language = Language::where('status', '1')->where('is_default', '1')->first();
                $get_role_trans_by_def_lang = Giftcard_translation::where('giftcard_id', $giftcard->id)
                ->where('language_id', $get_active_language->id)
                ->first();
    
                $upd_giftcard2 = DB::table('giftcards')
                ->where('id', $giftcard->id)
                ->update([
                    'giftcard' => $get_role_trans_by_def_lang->giftcard,
                ]);

            }
             GiftCardTimeLine::create([
            'uuid' => Str::uuid(),
            'auth_id' => Auth::user()->uuid,
            'giftcard_id' => $giftcard->uuid,
            'message' => 'Giftcard Updated',
            'status' => true,
        ]);
         DB::commit();

            return response()->json([
                'status_code' => 200,
                'message' => 'Giftcard has been updated',
            ], 200);

        } catch (\Throwable $th) {
              DB::rollBack();
            return response()->json([
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $th->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


   public function delete_giftcard($uuid)
    {
        DB::beginTransaction();

        try {
            $del_giftcard = Giftcard::where('uuid', $uuid)->first();

            if (!$del_giftcard) {
                return response()->json([
                    'status_code' => Response::HTTP_NOT_FOUND,
                    'message' => $this->get_message('not_found'),
                ], Response::HTTP_NOT_FOUND);
            }

            // Delete the giftcard
            $delete_giftcard = Giftcard::destroy($del_giftcard->id);

            if ($delete_giftcard) {
                // Delete related translations
                Giftcard_translation::where('giftcard_id', $del_giftcard->id)->delete();

                // Record the deletion in the timeline
                GiftCardTimeLine::create([
                    'uuid' => Str::uuid(),
                    'auth_id' => Auth::user()->uuid,
                    'giftcard_id' => $del_giftcard->uuid,
                    'message' => 'Giftcard Deleted',
                    'status' => true,
                ]);

                DB::commit();

                return response()->json([
                    'status_code' => Response::HTTP_OK,
                    'message' => $this->get_message('delete'),
                ], Response::HTTP_OK);
            } else {
                DB::rollBack();

                return response()->json([
                    'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                    'message' => $this->get_message('server_error'),
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $this->get_message('server_error'),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    

    public function get_active_giftcards(){

        try{

            $get_all_active_giftcard = Giftcard::where('status', 'active')->get();

            if($get_all_active_giftcard){
                
                return response()->json([
                    'status_code' => Response::HTTP_OK,
                    'data' => $get_all_active_giftcard,
                ], Response::HTTP_OK);
    
            }

        }catch (\Exception $e) { 

            return response()->json([
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $this->get_message('server_error'),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);

        } 
        
    } 
    
    public function applyGiftcardToOrder(Request $request)
    {
        DB::beginTransaction();

        $validator = Validator::make($request->all(), [
            'order_id' => 'required|exists:orders,uuid',
            'giftcard_id' => 'required|exists:giftcards,uuid',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            // Get the order and giftcard
            $order = Order::where('uuid', $request->order_id)->first();
            $giftcard = Giftcard::where('uuid', $request->giftcard_id)->first();

            // Check if giftcard has sufficient value
            if ($giftcard->balance <= 0) {
                return response()->json([
                    'status_code' => Response::HTTP_BAD_REQUEST,
                    'message' => 'Giftcard has no remaining balance',
                ], Response::HTTP_BAD_REQUEST);
            }

            // Calculate how much of the giftcard to use
            $amountToUse = min($giftcard->balance, $order->grand_total);
            $remainingBalance = $giftcard->balance - $amountToUse;


            // Create giftcard receiving record
            $giftcardReceiving = GiftcardReceiving::create([
                'uuid' => Str::uuid(),
                'auth_id' => Auth::user()->uuid,
                'giftcard_id' => $giftcard->uuid,
                'order_id' => $order->uuid,
                'grand_total' => $amountToUse,
                'balance' => $remainingBalance,
            ]);

            $giftcard->balance = $remainingBalance;
            $giftcard->save();

            // Update order payment
            $order->grand_total -= $amountToUse;
            $order->save();

            // Create timeline entry
            OrderTimeLine::create([
                'uuid' => Str::uuid(),
                'auth_id' => Auth::user()->uuid,
                'order_id' => $order->uuid,
                'message' => 'Giftcard applied: $'.number_format($amountToUse, 2),
                'status' => $order->status,
            ]);

            // Giftcard timeline entry
            GiftCardTimeLine::create([
                'uuid' => Str::uuid(),
                'auth_id' => Auth::user()->uuid,
                'giftcard_id' => $giftcard->uuid,
                'message' => 'Used for Order #'.$order->code,
                'status' => true,
            ]);

            DB::commit();

            return response()->json([
                'status_code' => Response::HTTP_OK,
                'message' => 'Giftcard applied successfully',
                'data' => [
                    'amount_used' => $amountToUse,
                    'remaining_balance' => $remainingBalance,
                    'new_order_total' => $order->grand_total,
                ]
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $this->get_message('server_error'),
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

}
