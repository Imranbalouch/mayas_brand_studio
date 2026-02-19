<?php

namespace App\Http\Controllers\API\Ecommerce;

use Exception;
use App\Models\Ecommerce\City;
use App\Traits\MessageTrait;
use Illuminate\Http\Request;
use App\Services\PermissionService;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class CityController extends Controller
{
   protected $permissionService;
    use MessageTrait;
    public function __construct(PermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    public function index()
    {
        //
        try{
            $menuUuid = request()->header('menu-uuid');
            $permissions = $this->permissionService->checkPermissions($menuUuid);
            $cities = City::with('country')->orderBy('id', 'desc');
            if ($permissions['view']) {
                if (!$permissions['viewglobal']) {
                    $cities = $cities->where('auth_id', Auth::user()->uuid);
                }
            }else{
                if (Auth::user()->hasPermission('viewglobal')) {
                    $cities = $cities;
                } else {
                    return response()->json([
                        'message' => 'You do not have permission to view this menu'
                    ], Response::HTTP_FORBIDDEN);
                }
            }
            $cities = $cities->get();

            return response()->json([
                'status_code'=>200,
                'permissions' => $permissions,
                'data'=>$cities
            ],200);

        }catch (\Exception $e) { 
            // Handle general exceptions
            return response()->json([
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $this->get_message('server_error'),
            ], Response::HTTP_INTERNAL_SERVER_ERROR); // 500 Internal Server Error
        } 
    }

    public function store(Request $request)
    {
        //
        $validator = Validator::make($request->all(),[
            'name' => 'required',
            'price' => 'required',
            'min_price' => 'required',
        ]);

        if($validator->fails()) {            
            $message = $validator->messages();
            return response()->json([
                'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'errors' => strval($message)
            
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        try {
            $data = $request->all();
            $data = [
                'country_uuid'=> $request->country_uuid,
                'name'=> $request->name,
                'price' => $request->price,
                'min_price' => $request->min_price,
                'vat_percent' => $request->vat_percent,
                'status' => 1
            ];
            $cities = City::create($data);
            if ($cities) {
                return response()->json([
                    'status_code'=>200,
                    'message'=>"City added successfully",
                ], 200);
            } else {
                return response()->json([
                    'status_code'=>500,
                    'message'=>$this->get_message('server_error'),
                ], 500);
            }
        }catch (\Exception $e) {
            Log::error($e->getMessage());
            return response()->json([
                'status_code'=>500,
                'message'=>$this->get_message('server_error'),
            ], 500);
        }
    }

    public function edit(string $id)
    {
        //
        try {
            $cities = City::findByUuid($id);
            if ($cities) {
                return response()->json([
                    'status_code'=>200,
                    'data'=>$cities
                ], 200);
            } else {
                return response()->json([
                    'status_code'=>404,
                    'message'=>$this->get_message('not_found'),
                ], 404);
            }
        }catch (\Exception $e) {
            Log::error($e->getMessage());
            return response()->json([
                'status_code'=>500,
                'message'=>$this->get_message('server_error'),
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $validator = Validator::make($request->all(),[
            'name' => 'required',
            'price' => 'required',
            'min_price' => 'required',
        ]);

        if ($validator->fails()) {
            $message = $validator->messages();
            return response()->json([
                'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'errors' => strval($message)
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $cities = City::findByUuid($id);
            $cities->update($request->only(['name', 'price', 'min_price', 'vat_percent', 'country_uuid']));
            return response()->json([
                'status_code' => 200,
                'message' => "City updated successfully",
            ], 200);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return response()->json([
                'status_code' => 500,
                'message' => $this->get_message('server_error'),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
        try {
            $cities = City::findByUuid($id);
            if ($cities) {
                $cities->delete();
                return response()->json([
                    'status_code'=>200,
                    'message'=>$this->get_message('delete'),
                ],200);
            } else {
                return response()->json([
                    'status_code'=>404,
                    'message'=>$this->get_message('not_found'),
                ], 404);
            }
        } catch (\Throwable $e) {
            return response()->json([
                'status_code'=>500,
                'message'=>$this->get_message('server_error'),
            ], 500);
        }
    }

    public function updateStatus(Request $request, string $id)
    {
        try {
            $cities = City::findByUuid($id);
            if ($cities) {
                $cities->status = $request->status;
                if ($cities->save()) {
                    return response()->json([
                        'status_code'=>200,
                        'message' => "City status updated successfully",
                    ], 200);
                } else {
                    return response()->json([
                        'status_code'=>500,
                        'message'=>$this->get_message('server_error'),
                    ], 500);
                }
            } else {
                return response()->json([
                    'status_code'=>404,
                    'message'=>$this->get_message('not_found'),
                ], 404);
            }
        } catch (\Throwable $e) {
            return response()->json([
                'status_code'=>500,
                'message'=>$this->get_message('server_error'),
            ], 500);
        }
    }
    
    public function get_city_by_country(Request $request)
    {
        try {
         $countryId = $request->query('country_id');

        if (!$countryId) {
            return response()->json([
                'status' => false,
                'message' => 'country_id is required',
                'data' => []
            ], 400);
        }

        $cities = City::where('country_uuid', $countryId)
            ->where('status', 1) 
            ->get();

        return response()->json([
                        'status_code'=>200,
                        'data' => $cities,
        ], 200);
    } catch(Exception $e){
        return response()->json([
            'status_code'=>500,
            'message'=>$e,
        ], 500);
        }
    }

}
