<?php

namespace App\Http\Controllers\API\Ecommerce;

use App\Http\Controllers\Controller;
use App\Models\Ecommerce\PaymentTerms;
use App\Services\PermissionService;
use App\Traits\MessageTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class PaymentTermsController extends Controller
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
        //
        try{
            $menuUuid = request()->header('menu-uuid');
            $permissions = $this->permissionService->checkPermissions($menuUuid);
            $paymentTerm = PaymentTerms::orderBy('id', 'desc');
            if ($permissions['view']) {
                if (!$permissions['viewglobal']) {
                    $paymentTerm = $paymentTerm->where('auth_id', Auth::user()->uuid);
                }
            }else{
                if (Auth::user()->hasPermission('viewglobal')) {
                    $paymentTerm = $paymentTerm;
                } else {
                    return response()->json([
                        'message' => 'You do not have permission to view this menu'
                    ], Response::HTTP_FORBIDDEN);
                }
            }
            $paymentTerm = $paymentTerm->get();

            return response()->json([
                'status_code'=>200,
                'permissions' => $permissions,
                'data'=>$paymentTerm
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
        //
        $validator = Validator::make($request->all(),[
            'name' => [
                'required',
                'string',
                'max:255',
                'regex:/^[^<>]+$/',
                'unique:paymentterms,name',
            ],
            'description' => [
                'nullable',
                'string',
                'max:255',
                'regex:/^[^<>]+$/',
            ],
            'url' => [
                'nullable',
                'string',
                'max:255',
                'regex:/^[^<>]+$/',
            ],
        ], [
            'name.required' => 'The name field is required.',
            'name.string' => 'The name must be a string.',
            'name.max' => 'The name may not be greater than 255 characters.',
            'name.regex' => 'The name contains invalid characters.',
            'name.unique' => 'The name has already been taken.',
            'description.string' => 'The description must be a string.',
            'description.max' => 'The description may not be greater than 255 characters.',
            'description.regex' => 'The description contains invalid characters.',
            'url.regex' => 'The description contains invalid characters.',
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
                'name'=> $request->name,
                'description' => $request->description,
                'url' => $request->url,
                'status' => $request->status
            ];
            $paymentTerm = PaymentTerms::create($data);
            if ($paymentTerm) {
                return response()->json([
                    'status_code'=>200,
                    'message'=>"Payment Term added successfully",
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
            $paymentTerm = PaymentTerms::findByUuid($id);
            if ($paymentTerm) {
                return response()->json([
                    'status_code'=>200,
                    'data'=>$paymentTerm
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
        $validator = Validator::make($request->all(), [
            'name' => [
                'required',
                'string',
                'max:255',
                'regex:/^[^<>]+$/',
                'unique:paymentterms,name,' . $id . ',uuid',
            ],
            'description' => [
                'nullable',
                'string',
                'max:255',
                'regex:/^[^<>]+$/',
            ],
            'url' => [
                'nullable',
                'string',
                'max:255',
                'regex:/^[^<>]+$/',
            ],
        ], [
            'name.required' => 'The name field is required.',
            'name.string' => 'The name must be a string.',
            'name.max' => 'The name may not be greater than 255 characters.',
            'name.regex' => 'The name contains invalid characters.',
            'name.unique' => 'The name has already been taken.',
            'description.string' => 'The description must be a string.',
            'description.max' => 'The description may not be greater than 255 characters.',
            'description.regex' => 'The description contains invalid characters.',
            'url.regex' => 'The url contains invalid characters.',
        ]);

        if ($validator->fails()) {
            $message = $validator->messages();
            return response()->json([
                'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'errors' => strval($message)
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $paymentTerm = PaymentTerms::findByUuid($id);
            $paymentTerm->update($request->only(['name', 'description', 'url']));
            return response()->json([
                'status_code' => 200,
                'message' => "Payment Term updated successfully",
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
            $paymentTerm = PaymentTerms::findByUuid($id);
            if ($paymentTerm) {
                $paymentTerm->delete();
                return response()->json([
                    'status_code'=>200,
                    'message' => "Payment Term delete successfully",
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
            $paymentTerm = PaymentTerms::findByUuid($id);
            if ($paymentTerm) {
                $paymentTerm->status = $request->status;
                if ($paymentTerm->save()) {
                    return response()->json([
                        'status_code'=>200,
                        'message' => "Payment Term status updated successfully",
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
    
    public function get_paymentterm() {
        $paymentTerms = PaymentTerms::where('status', 1)->get();
        return response()->json([
            'status_code'=>200,
            'data'=>$paymentTerms
        ], 200);
    }
}
