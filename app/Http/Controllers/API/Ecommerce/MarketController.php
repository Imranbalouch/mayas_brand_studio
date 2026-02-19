<?php

namespace App\Http\Controllers\API\Ecommerce;

use App\Http\Controllers\Controller;
use App\Models\Ecommerce\Currency;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Symfony\Component\HttpFoundation\Response;
use Mail;
use Auth;
use Session;
use Hash;
use DB;
use App\Models\Ecommerce\Market;
use App\Models\Ecommerce\Market_translation;
use App\Models\Menu;
use App\Models\Permission_assign;
use App\Models\Language;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon; 
use Illuminate\Support\Str;
use App\Services\PermissionService;
use App\Traits\MessageTrait;
use DeepCopy\f001\B;

class MarketController extends Controller
{
    
    use MessageTrait;
    protected $permissionService;

    public function __construct(PermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }


    public function get_market(){

        try{

            $menuUuid = request()->header('menu-uuid');
            $permissions = $this->permissionService->checkPermissions($menuUuid);
            $get_all_market = Market::orderBy('id', 'desc');
            if ($permissions['view']) {
                if (!$permissions['viewglobal']) {
                    $get_all_market = $get_all_market->where('auth_id', Auth::user()->uuid);
                }
            }else{
                if (Auth::user()->hasPermission('viewglobal')) {
                    $get_all_market = $get_all_market;
                } else {
                    return response()->json([
                        'message' => 'You do not have permission to view this menu'
                    ], Response::HTTP_FORBIDDEN);
                }
            }
            $get_all_market = $get_all_market->get();

            return response()->json([
                'status_code'=>200,
                'permissions' => $permissions,
                'data'=>$get_all_market
            ],200);

        }catch (\Exception $e) { 
            // Handle general exceptions
            return response()->json([

                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $this->get_message('server_error'),
                

            ], Response::HTTP_INTERNAL_SERVER_ERROR); // 500 Internal Server Error
        } 

    }


    public function add_market(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|max:255',
            'slug' => '',
            'country_id' => 'array',
            'language_id' => '',
            'currency_id' => '',
            'price_adjustment_value'=>'',
            'order_level' => '', 
            'meta_title' => '',
            'meta_description' => '',
            'og_title'=>'',
            'og_description'=>'',
            'og_image'=>'',
            'x_title'=>'',
            'x_description'=>'',
            'x_image'=>'',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'errors' => strval($validator->errors())
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {

            $check_if_already = Market::where('market_name', $request->name)->get(); 
          //  dd($check_if_already);
            if(count($check_if_already) > 0){ 
                return response()->json([ 
                    'status_code' => Response::HTTP_CONFLICT,
                    'message' => $this->get_message('conflict'), 
                ], Response::HTTP_CONFLICT); // 409 Conflict   
            }else{

            $default_language = Language::where('is_default', 1)->first();
            $default_currency = Currency::where('is_default', 1)->first();
            // Create Market
            $market = new Market();
            $market->uuid = Str::uuid();
            $market->auth_id = Auth::user()->uuid;
            $market->market_name = $request->name;
            $market->country_names = $request->country_names;
            $market->country_images = $request->country_images;
            $market->country_id = $request->country_id ? json_encode($request->country_id) : null;
            $market->language_id = $default_language ? $default_language->uuid : $request->language_id;
            $market->currency_id = $default_currency ? $default_currency->uuid : $request->currency_id;
            $market->price_adjustment=$request->price_adjustment ?? 0;
            $market->percentage=$request->price_adjustment_value ?? 0;
            $market->order_level = $request->order_level ?: 0; 
            $market->meta_title = $request->meta_title;
            $market->meta_description = $request->meta_description;
            $market->og_title = $request->og_title;
            $market->og_description = $request->og_description;
            $market->og_image = $request->og_image;
            $market->x_title = $request->x_title;
            $market->x_description = $request->x_description;
            $market->x_image = $request->x_image;
        
            if ($request->slug) {
                $market->slug = preg_replace('/[^A-Za-z0-9\-]/', '', str_replace(' ', '-', $request->slug));
            } else {
                $market->slug = preg_replace('/[^A-Za-z0-9\-]/', '', str_replace(' ', '-', $request->market)) . '-' . Str::random(5);
            }
           
            $market->save();
           // dd($market);
            return response()->json([
                'status_code' => 200,
                'message' => $this->get_message('update'),
            ], 200);
            }
        }catch (\Illuminate\Database\QueryException $e) {
            
           

            return response()->json([
                'status_code' => 500,
                'message' => $e->getMessage(),
                //  'message' => $this->get_message('server_error'),
            ], 500);

        } catch (\Throwable $th) {

            return response()->json([
                'status_code' => 500,
                'message' => $th->getMessage(),
                // 'message' => $this->get_message('server_error'),
            ], 500);

        }
        
    }


    public function edit_market($uuid){

        try {
            
            $edit_market_by_id = Market::where('uuid', $uuid)->first();
           
            if(!$edit_market_by_id)
            {
                return response()->json([
                    'status_code' => Response::HTTP_NOT_FOUND,
                    'message' => $this->get_message('not_found'),
                ], Response::HTTP_NOT_FOUND);
            }
            
           
            //dd($market_translations);
            if ($edit_market_by_id) {
                
                $default_currency = Currency::where('is_default', 1)->first();
                $edit_market_by_id->default_currency = $default_currency;
                // $edit_market_by_id->translations = $market_translations;
       
                return response()->json([

                    'status_code' => Response::HTTP_OK,
                    'data' => $edit_market_by_id,

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


    public function update_market(Request $request)
    {

        $uuid = request()->header('uuid');

        try {
            // Find the market
            $market = Market::where('uuid', $uuid)->first();
            // Update market fields
            //dd($request->all());
            $market->market_name = $request->market_name ?? $market->market_name;
            $market->slug = $request->slug;
            $market->country_names = $request->country_names ?? $market->country_names;
            $market->country_images = $request->country_images;
            $market->country_id = $request->country_id ? json_encode($request->country_id) : $market->country_id;
            $market->language_id = $request->language_id ?? $market->language_id;
            $market->currency_id = $request->currency_id ?? $market->currency_id;
            $market->tax_id = $request->tax_id;
            $market->price_adjustment=$request->price_adjustment ?? $market->price_adjustment;
            $market->percentage=$request->price_adjustment_value ??  $market->percentage;
            $market->meta_title = $request->meta_title;
            $market->meta_description = $request->meta_description;
            $market->og_title = $request->og_title;
            $market->og_description = $request->og_description;
            $market->og_image = $request->og_image;
            $market->x_title = $request->x_title;
            $market->x_description = $request->x_description;
            $market->x_image = $request->x_image;
        

            if ($request->slug) {
                $market->slug = preg_replace('/[^A-Za-z0-9\-]/', '', str_replace(' ', '-', $request->slug));
            } else {
                $market->slug = preg_replace('/[^A-Za-z0-9\-]/', '', str_replace(' ', '-', $request->market)) . '-' . Str::random(5);
            }
            
            $market->save();
           
           
           // dd($market);
            return response()->json([
                'status_code' => 200,
                'message' => 'Market has been updated',
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $th->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    public function delete_market($uuid){

        try{

            $del_market = Market::where('uuid', $uuid)->first();
            
            if(!$del_market)
            {
                
                return response()->json([

                    'status_code' => Response::HTTP_NOT_FOUND,
                    'message' => $this->get_message('not_found'),

                ], Response::HTTP_NOT_FOUND);


            }else{

                $delete_market = Market::destroy($del_market->id);

                if($delete_market){
                    
                    $del_market_translation = Market_translation::where('market_id', $del_market->id)->delete();

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

    
    public function updateMarketStatus(Request $request, string $id)
    {
        $request->validate([
            'status' => 'required|in:0,1', // Ensure status is either 0 or 1
        ]);

        try {
            // Find the category by UUID and active status
            $market = Market::where('uuid', $id)->first();

            if ($market) {
                // Update the status
                $market->status = $request->status;
                $market->save();

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


    public function updateMarketFeatured(Request $request, string $id)
    {
        $request->validate([
            'featured' => 'required|in:0,1',
        ]);

        try {
            // Find the category by UUID and active featured
            $market = Market::where('uuid', $id)->first();

            if ($market) {
                // Update the featured
                $market->featured = $request->featured;
                $market->save();

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


    public function get_active_markets(){

        try{

            $get_all_active_market = Market::orderBy('market_name', 'ASC')->get();

            if($get_all_active_market){
                
                return response()->json([
                    'status_code' => Response::HTTP_OK,
                    'data' => $get_all_active_market,
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
