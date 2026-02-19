<?php

namespace App\Http\Controllers\API\Ecommerce;

use App\Http\Controllers\Controller;
use App\Models\Ecommerce\Supplier;
use App\Services\PermissionService;
use App\Traits\MessageTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class SupplierController extends Controller
{
    protected $permissionService;
    use MessageTrait;
    public function __construct(PermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try{
            $menuUuid = request()->header('menu-uuid');
            $permissions = $this->permissionService->checkPermissions($menuUuid);
            $suppliers = Supplier::with('country:uuid,name,code')->orderBy('id', 'desc');
            if ($permissions['view']) {
                if (!$permissions['viewglobal']) {
                    $suppliers = $suppliers->where('auth_id', Auth::user()->uuid);
                }
            }else{
                if (Auth::user()->hasPermission('viewglobal')) {
                    $suppliers = $suppliers;
                } else {
                    return response()->json([
                        'message' => 'You do not have permission to view this menu'
                    ], Response::HTTP_FORBIDDEN);
                }
            }
            $suppliers = $suppliers->get();

            return response()->json([
                'status_code'=>200,
                'permissions' => $permissions,
                'data'=>$suppliers
            ],200);

        }catch (\Exception $e) { 
            // Handle general exceptions
            return response()->json([
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $this->get_message('server_error'),
            ], Response::HTTP_INTERNAL_SERVER_ERROR); // 500 Internal Server Error
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'company' => [
                'required',
                'string',
                'max:255',
                'regex:/^[^<>]+$/',
                'unique:carriers,name',
            ],
            'country_id' => [
                'required',
                'max:255',
                'regex:/^[^<>]+$/',
            ],
            'email' => [
                'nullable',
                'email',
                'max:150',
            ],
        ], [
            'company.required' => 'The company field is required.',
            'company.string' => 'The company must be a string.',
            'company.max' => 'The company may not be greater than 255 characters.',
            'company.regex' => 'The company contains invalid characters.',
            'country_id.required' => 'The country field is required.',
            'country_id.regex' => 'The country contains invalid characters.',
            'email.email' => 'Email is not valid',
            'email.max' => 'Email must be at most 150 characters',
        ]);

        if($validator->fails()) {            
            $message = $validator->messages();
            return response()->json([
                'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'errors' => strval($message)
            
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $data = [
                'company'=> $request->company,
                'country_id' => $request->country_id,
                'address' => $request->address,
                'apart_suite' => $request->apart_suite,
                'city' => $request->city,
                'postal_code' => $request->postal_code,
                'contact_name' => $request->contact_name,
                'email' => $request->email,
                'phone_number' => $request->phone_number,
                'status' => 1,
            ];
            $supplier = Supplier::create($data);
            if ($supplier) {
                return response()->json([
                    'status_code'=>200,
                    'message'=>"Supplier added successfully",
                ], 200);
            } else {
                return response()->json([
                    'status_code'=>500,
                    'message'=>$this->get_message('server_error'),
                ], 500);
            }
        }catch(\Exception $e) {
            Log::error(['Supplier Store Error'=>$e->getMessage()]);
            return response()->json([
                'status_code'=>500,
                'message'=>$this->get_message('server_error'),
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
            $supplier = Supplier::findByUuid($id);
            if ($supplier) {
                return response()->json([
                    'status_code'=>200,
                    'data'=>$supplier
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
        //
        $validator = Validator::make($request->all(),[
            'company' => [
                'required',
                'string',
                'max:255',
                'regex:/^[^<>]+$/',
                'unique:carriers,name',
            ],
            'country_id' => [
                'required',
                'max:255',
                'regex:/^[^<>]+$/',
            ],
            'email' => [
                'nullable',
                'email',
                'max:150',
            ],
        ], [
            'company.required' => 'The company field is required.',
            'company.string' => 'The company must be a string.',
            'company.max' => 'The company may not be greater than 255 characters.',
            'company.regex' => 'The company contains invalid characters.',
            'country_id.required' => 'The country field is required.',
            'country_id.regex' => 'The country contains invalid characters.',
            'email.email' => 'Email is not valid',
            'email.max' => 'Email must be at most 150 characters',
        ]);

        if($validator->fails()) {            
            $message = $validator->messages();
            return response()->json([
                'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'errors' => strval($message)
            
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $data = [
                'company'=> $request->company,
                'country_id' => $request->country_id,
                'address' => $request->address,
                'apart_suite' => $request->apart_suite,
                'city' => $request->city,
                'postal_code' => $request->postal_code,
                'contact_name' => $request->contact_name,
                'email' => $request->email,
                'phone_number' => $request->phone_number,
                'status' => 1,
            ];
            $supplier = Supplier::findByUuid($id);
            if ($supplier) {
                $supplier->update($data);
                return response()->json([
                    'status_code'=>200,
                    'message'=>"Supplier updated successfully",
                ], 200);
            }else{
                return response()->json([
                    'status_code'=>404,
                    'message'=>$this->get_message('not_found'),
                ], 404);
            } 
        }catch(\Exception $e) {
            Log::error(['Supplier Update Error'=>$e->getMessage()]);
            return response()->json([
                'status_code'=>500,
                'message'=>$this->get_message('server_error'),
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
            $supplier = Supplier::findByUuid($id);
            if ($supplier) {
                $supplier->delete();
                return response()->json([
                    'status_code'=>200,
                    'message' => "Supplier delete successfully",
                ],200);
            } else {
                return response()->json([
                    'status_code'=>404,
                    'message'=>$this->get_message('not_found'),
                ], 404);
            }
        } catch (\Throwable $e) {
            Log::error($e->getMessage());
            return response()->json([
                'status_code'=>500,
                'message'=>$this->get_message('server_error'),
            ], 500);
        }
    }

    public function updateStatus(Request $request, string $id)
    {
        try {
            $supplier = Supplier::findByUuid($id);
            if ($supplier) {
                $supplier->status = $request->status;
                if ($supplier->save()) {
                    return response()->json([
                        'status_code'=>200,
                        'message' => "Supplier status updated successfully",
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
            Log::error($e->getMessage());
            return response()->json([
                'status_code'=>500,
                'message'=>$this->get_message('server_error'),
            ], 500);
        }
    }

    public function get_supplier() {
        $suppliers = Supplier::where('status', 1)->get();
        return response()->json([
            'status_code'=>200,
            'data'=>$suppliers
        ], 200);
    }
}
