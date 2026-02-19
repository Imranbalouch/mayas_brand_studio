<?php

namespace App\Http\Controllers\API\Ecommerce;

use App\Models\Ecommerce\TaxType;
use Illuminate\Support\Str;
use App\Traits\MessageTrait;
use Illuminate\Http\Request;
use App\Services\PermissionService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class TaxTypeController extends Controller
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
            $tax_type = TaxType::orderBy('id', 'desc');
            if ($permissions['view']) {
                if (!$permissions['viewglobal']) {
                    $tax_type = $tax_type->where('auth_id', Auth::user()->uuid);
                }
            }else{
                if (Auth::user()->hasPermission('viewglobal')) {
                    $tax_type = $tax_type;
                } else {
                    return response()->json([
                        'message' => 'You do not have permission to view this menu'
                    ], Response::HTTP_FORBIDDEN);
                }
            }
            $tax_type = $tax_type->get();

            return response()->json([
                'status_code'=>200,
                'permissions' => $permissions,
                'data'=>$tax_type
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
            'name' => 'required|string|max:255',
            'value' => 'nullable|string|max:255',
            'status' => 'required|numeric|in:0,1',
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
                'uuid' => Str::uuid(),
                'auth_id' => Auth::user()->uuid,
                'name'=> $request->name,
                'value' => $request->value,
                'status' => $request->status
            ];
            $tax_type = TaxType::create($data);
            if ($tax_type) {
                return response()->json([
                    'status_code'=>200,
                    'message'=>"Tax Type added successfully",
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
                'message'=>$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
        try {
            $tax_type = TaxType::findByUuid($id);
            if ($tax_type) {
                return response()->json([
                    'status_code'=>200,
                    'data'=>$tax_type
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
                'message'=>$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'value' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            $message = $validator->messages();
            return response()->json([
                'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'errors' => strval($message)
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $tax_type = TaxType::findByUuid($id);
            
            if (!$tax_type) {
                return response()->json([
                    'status_code' => 404,
                    'message' => "Tax Type not found",
                ], 404);
            }

            $tax_type->update($request->only(['name', 'value'])); // Added 'status' here since it's in validation
            return response()->json([
                'status_code' => 200,
                'message' => "Tax Type updated successfully",
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
            $tax_type = TaxType::findByUuid($id);
            if ($tax_type) {
                $tax_type->delete();
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
            $tax_type = TaxType::findByUuid($id);
            if ($tax_type) {
                $tax_type->status = $request->status;
                if ($tax_type->save()) {
                    return response()->json([
                        'status_code'=>200,
                        'message' => "Tax Type status updated successfully",
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
                'message'=>$e->getMessage(),
            ], 500);
        }
    }

    public function get_active_tax_type(){
        try{
        $tax_type = TaxType::where('status',1)->get();
        return response()->json([
            'status_code' => Response::HTTP_OK,
            'data' => $tax_type
        ]);
        }catch (\Exception $e) { 
            // Handle general exceptions
            return response()->json([
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $this->get_message('server_error'),
            ], Response::HTTP_INTERNAL_SERVER_ERROR); // 500 Internal Server Error
        } 
    }
}
