<?php

namespace App\Http\Controllers\API\Ecommerce;

use App\Models\Ecommerce\Customer;
use Illuminate\Support\Str;
use App\Traits\MessageTrait;
use Illuminate\Http\Request;
use App\Models\Ecommerce\AddressCustomer;
use App\Services\PermissionService;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class CustomerController extends Controller
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
        try {
            $menuUuid = request()->header('menu-uuid');
            $permissions = $this->permissionService->checkPermissions($menuUuid);
    
            $customers = Customer::orderBy('id', 'desc');
    
            if ($permissions['view']) {
                if (!$permissions['viewglobal']) {
                    $customers = $customers->where('auth_id', Auth::user()->uuid);
                }
            } else {
                if (Auth::user()->hasPermission('viewglobal')) {
                    $customers = $customers;
                } else {
                    return response()->json([
                        'message' => 'You do not have permission to view this menu'
                    ], Response::HTTP_FORBIDDEN);
                }
            }
    
            $customers = $customers->with('address')->get();       
    
            return response()->json([
                'status_code' => 200,
                'permissions' => $permissions,
                'data' => $customers, 
            ], 200);
        } catch (\Exception $e) {
            Log::error('Customer List Error: ' . $e->getMessage());
            return response()->json([
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $this->get_message('server_error'),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
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
        try {
            $validator = Validator::make($request->all(), [
                'email' => ['required', 'string', 'email', 'max:255', 'unique:customers'],
                'first_name' => ['required', 'string', 'max:255'],
                'last_name' => ['required', 'string', 'max:255'],
                'phone' => ['nullable', 'string'],
                'language' => ['nullable', 'string', 'max:255'],
                'tax_setting' => ['nullable', 'string'],
                'notes' => ['nullable', 'string', 'max:5000'],
                'tags' => ['nullable', 'string'],
                'market_emails' => ['nullable', 'in:0,1'],
                'market_sms' => ['nullable', 'in:0,1'],
                'billing_address.address_first_name' => ['nullable', 'string', 'max:255'],
                'billing_address.address_last_name' => ['nullable', 'string', 'max:255'],
                'billing_address.company' => ['nullable', 'string', 'max:255'],
                'billing_address.address' => ['nullable', 'string', 'max:255'],
                'billing_address.apartment' => ['nullable', 'string', 'max:255'],
                'billing_address.city' => ['nullable', 'string', 'max:255'],
                'billing_address.address_phone' => ['nullable', 'string'],
                'billing_address.country' => ['nullable', 'string', 'max:255'],
                'shipping_address.*.address_first_name' => ['nullable', 'string', 'max:255'],
                'shipping_address.*.address_last_name' => ['nullable', 'string', 'max:255'],
                'shipping_address.*.company' => ['nullable', 'string', 'max:255'],
                'shipping_address.*.address' => ['nullable', 'string', 'max:255'],
                'shipping_address.*.apartment' => ['nullable', 'string', 'max:255'],
                'shipping_address.*.city' => ['nullable', 'string', 'max:255'],
                'shipping_address.*.address_phone' => ['nullable', 'string'],
                'shipping_address.*.country' => ['nullable', 'string', 'max:255'],
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                    'errors' => $validator->errors()
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            // Create customer
            $customer = new Customer;
            $customer->uuid = Str::uuid();
            $customer->auth_id = Auth::user()->uuid;
            $customer->name = $request->first_name . ' ' . $request->last_name;
            $customer->first_name = $request->first_name;
            $customer->last_name = $request->last_name;
            $customer->email = $request->email;
            $customer->phone = $request->phone;
            $customer->language = $request->language;
            $customer->tax_setting = $request->tax_setting;
            $customer->market_emails = $request->market_emails ?? 0;
            $customer->market_sms = $request->market_sms ?? 0;
            $customer->notes = $request->notes;
            $customer->tags = $request->tags;
            $customer->save();

            // Create billing address
            if ($request->has('billing_address')) {
                $billingAddress = new AddressCustomer;
                $billingAddress->uuid = Str::uuid();
                $billingAddress->auth_id = Auth::user()->uuid;
                $billingAddress->type = "billing_address";
                $billingAddress->customer_id = $customer->uuid;
                $billingAddress->address_first_name = $request->billing_address['address_first_name'] ?? null;
                $billingAddress->address_last_name = $request->billing_address['address_last_name'] ?? null;
                $billingAddress->company = $request->billing_address['company'] ?? null;
                $billingAddress->address = $request->billing_address['address'] ?? null;
                $billingAddress->apartment = $request->billing_address['apartment'] ?? null;
                $billingAddress->city = $request->billing_address['city'] ?? null;
                $billingAddress->state = $request->billing_address['state'] ?? null;
                $billingAddress->address_phone = $request->billing_address['address_phone'] ?? null;
                $billingAddress->country = $request->billing_address['country'] ?? null;
                $billingAddress->save();
            }

            // Create shipping addresses
            if ($request->has('shipping_address')) {
                foreach ($request->shipping_address as $shippingAddressData) {
                    $shippingAddress = new AddressCustomer;
                    $shippingAddress->uuid = Str::uuid();
                    $shippingAddress->auth_id = Auth::user()->uuid;
                    $shippingAddress->type = "shipping_address";
                    $shippingAddress->customer_id = $customer->uuid;
                    $shippingAddress->address_first_name = $shippingAddressData['address_first_name'] ?? null;
                    $shippingAddress->address_last_name = $shippingAddressData['address_last_name'] ?? null;
                    $shippingAddress->address_email = $shippingAddressData['address_email'] ?? null;
                    $shippingAddress->company = $shippingAddressData['company'] ?? null;
                    $shippingAddress->address = $shippingAddressData['address'] ?? null;
                    $shippingAddress->apartment = $shippingAddressData['apartment'] ?? null;
                    $shippingAddress->city = $shippingAddressData['city'] ?? null;
                    $shippingAddress->state = $shippingAddressData['state'] ?? null;
                    $shippingAddress->address_phone = $shippingAddressData['address_phone'] ?? null;
                    $shippingAddress->country = $shippingAddressData['country'] ?? null;
                    $shippingAddress->save();
                }
            }

            return response()->json([
                'status_code' => 200,
                'message' => 'Customer has been created',
            ], 200);

        } catch (\Throwable $th) {
            Log::error($th->getMessage());
            return response()->json([
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'Server Error',
                'error' => $th->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    

    /**
     * Display the specified resource.
     */
    public function show(string $uuid)
    {
       try {
        $edit_customer = Customer::where('uuid', $uuid)->first();

        if ($edit_customer) {
            // Fetch the associated address manually
            $address_customer = AddressCustomer::where('uuid', $edit_customer->address_id)->first();

            // Include the address in the response
            return response()->json([
                'status_code' => Response::HTTP_OK,
                'data' => [
                    'customer' => $edit_customer,
                    'address' => $address_customer,
                ],
            ], Response::HTTP_OK);
        } else {
            return response()->json([
                'status_code' => Response::HTTP_NOT_FOUND,
                'message' => $this->get_message('not_found'),
            ], Response::HTTP_NOT_FOUND);
        }
    } catch (\Exception $e) {
        return response()->json([
            'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
            'message' => $this->get_message('server_error'),
        ], Response::HTTP_INTERNAL_SERVER_ERROR); // 500 Internal Server Error
    }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($uuid)
{
    try {
        $edit_customer = Customer::with('address')->where('uuid', $uuid)->get();

        if ($edit_customer) {
            return response()->json([
                'status_code' => \Illuminate\Http\Response::HTTP_OK,
                'data' => $edit_customer,
            ], \Illuminate\Http\Response::HTTP_OK);
        } else {
            return response()->json([
                'status_code' => \Illuminate\Http\Response::HTTP_NOT_FOUND,
                'message' => $this->get_message('not_found'),
            ], \Illuminate\Http\Response::HTTP_NOT_FOUND);
        }
    } catch (\Exception $e) {
        \Log::error('Edit Customer Error: ' . $e->getMessage());

        return response()->json([
            'status_code' => \Illuminate\Http\Response::HTTP_INTERNAL_SERVER_ERROR,
            'message' => $this->get_message('server_error'),
        ], \Illuminate\Http\Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $uuid)
    {
    try {
        $customer = Customer::where('uuid', $uuid)->first();
            if (!$customer) {
                return response()->json([
                    'status_code' => 404,
                    'message' => 'Record not found',
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'email' => ['required', 'string', 'email', 'max:255', 'unique:customers,email,'.$customer->id],
                'first_name' => ['required', 'string', 'max:255'],
                'last_name' => ['required', 'string', 'max:255'],
                'phone' => ['nullable', 'string'],
                'language' => ['nullable', 'string', 'max:255'],
                'tax_setting' => ['nullable', 'string'],
                'notes' => ['nullable', 'string', 'max:5000'],
                'tags' => ['nullable', 'string'],
                'market_emails' => ['nullable', 'in:0,1'],
                'market_sms' => ['nullable', 'in:0,1'],
                'billing_address.address_first_name' => ['nullable', 'string', 'max:255'],
                'billing_address.address_last_name' => ['nullable', 'string', 'max:255'],
                'billing_address.company' => ['nullable', 'string', 'max:255'],
                'billing_address.address' => ['nullable', 'string', 'max:255'],
                'billing_address.apartment' => ['nullable', 'string', 'max:255'],
                'billing_address.city' => ['nullable', 'string', 'max:255'],
                'billing_address.address_phone' => ['nullable', 'string'],
                'billing_address.country' => ['nullable', 'string', 'max:255'],
                'shipping_address.*.address_first_name' => ['nullable', 'string', 'max:255'],
                'shipping_address.*.address_last_name' => ['nullable', 'string', 'max:255'],
                'shipping_address.*.company' => ['nullable', 'string', 'max:255'],
                'shipping_address.*.address' => ['nullable', 'string', 'max:255'],
                'shipping_address.*.apartment' => ['nullable', 'string', 'max:255'],
                'shipping_address.*.city' => ['nullable', 'string', 'max:255'],
                'shipping_address.*.address_phone' => ['nullable', 'string'],
                'shipping_address.*.country' => ['nullable', 'string', 'max:255'],
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                    'errors' => $validator->errors()
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            // Update customer
            $customer->name = $request->input('first_name') . ' ' . $request->input('last_name');
            $customer->first_name = $request->input('first_name');
            $customer->last_name = $request->input('last_name');
            $customer->email = $request->input('email');
            $customer->phone = $request->input('phone');
            $customer->language = $request->input('language');
            $customer->tax_setting = $request->input('tax_setting');
            $customer->notes = $request->input('notes');
            $customer->market_emails = $request->input('market_emails', $customer->market_emails ?? 0);
            $customer->market_sms = $request->input('market_sms', $customer->market_sms ?? 0);
            $customer->tags = $request->input('tags');
            $customer->save();

            // Update or create billing address
            if ($request->has('billing_address')) {
                $billingAddress = AddressCustomer::where('customer_id', $customer->uuid)->where('type', 'billing_address')->first();
                if (!$billingAddress) {
                    $billingAddress = new AddressCustomer;
                    $billingAddress->uuid = Str::uuid();
                    $billingAddress->auth_id = Auth::user()->uuid;
                    $billingAddress->type = "billing_address";
                    $billingAddress->customer_id = $customer->uuid;
                }
                $billingAddress->address_first_name = $request->billing_address['address_first_name'] ?? null;
                $billingAddress->address_last_name = $request->billing_address['address_last_name'] ?? null;
                $billingAddress->company = $request->billing_address['company'] ?? null;
                $billingAddress->address = $request->billing_address['address'] ?? null;
                $billingAddress->apartment = $request->billing_address['apartment'] ?? null;
                $billingAddress->city = $request->billing_address['city'] ?? null;
                $billingAddress->state = $request->billing_address['state'] ?? null;
                $billingAddress->address_phone = $request->billing_address['address_phone'] ?? null;
                $billingAddress->country = $request->billing_address['country'] ?? null;
                $billingAddress->save();
            }

            // Delete existing shipping addresses and create new ones
            if ($request->has('shipping_address')) {
                AddressCustomer::where('customer_id', $customer->uuid)->where('type', 'shipping_address')->delete();
                foreach ($request->shipping_address as $shippingAddressData) {
                    $shippingAddress = new AddressCustomer;
                    $shippingAddress->uuid = Str::uuid();
                    $shippingAddress->auth_id = Auth::user()->uuid;
                    $shippingAddress->type = "shipping_address";
                    $shippingAddress->customer_id = $customer->uuid;
                    $shippingAddress->address_first_name = $shippingAddressData['address_first_name'] ?? null;
                    $shippingAddress->address_last_name = $shippingAddressData['address_last_name'] ?? null;
                    $shippingAddress->address_email = $shippingAddressData['address_email'] ?? null;
                    $shippingAddress->company = $shippingAddressData['company'] ?? null;
                    $shippingAddress->address = $shippingAddressData['address'] ?? null;
                    $shippingAddress->apartment = $shippingAddressData['apartment'] ?? null;
                    $shippingAddress->city = $shippingAddressData['city'] ?? null;
                    $shippingAddress->state = $shippingAddressData['state'] ?? null;
                    $shippingAddress->address_phone = $shippingAddressData['address_phone'] ?? null;
                    $shippingAddress->country = $shippingAddressData['country'] ?? null;
                    $shippingAddress->save();
                }
            }

            return response()->json([
                'status_code' => 200,
                'message' => 'Customer has been updated successfully',
            ], 200);

        } catch (\Throwable $th) {
            Log::error('Customer update error: ' . $th->getMessage());
            return response()->json([
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'Server error: ' . $th->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function deleteShippingAddress($customerUuid, $addressUuid)
{
    try {
        $address = AddressCustomer::where('customer_id', $customerUuid)
            ->where('uuid', $addressUuid)
            ->where('type', 'shipping_address')
            ->first();
            
        if (!$address) {
            return response()->json([
                'status_code' => 404,
                'message' => 'Shipping address not found',
            ], 404);
        }
        
        $address->delete();
        
        return response()->json([
            'status_code' => 200,
            'message' => 'Shipping address deleted successfully',
        ], 200);
        
    } catch (\Throwable $th) {
        Log::error('Delete shipping address error: ' . $th->getMessage());
        return response()->json([
            'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
            'message' => 'Server error: ' . $th->getMessage(),
        ], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}
    


    /**
     * Remove the specified resource from storage.
     */
    public function destroy($uuid)
    {
        try {
            $del_customer = Customer::where('uuid', $uuid)->first();

            if (!$del_customer) {
                return response()->json([
                    'status_code' => Response::HTTP_NOT_FOUND,
                    'message' => $this->get_message('not_found'),
                ], Response::HTTP_NOT_FOUND);
            }

            // Delete associated address via customer_id
            $address_customer = AddressCustomer::where('customer_id', $del_customer->uuid)->first();
            if ($address_customer) {
                $address_customer->delete();
            }

            // Delete customer
            $delete_customer = $del_customer->delete();

            if ($delete_customer) {
                return response()->json([
                    'status_code' => Response::HTTP_OK,
                    'message' => 'Customer has been deleted successfully',
                ], Response::HTTP_OK);
            }

        } catch (\Exception $e) {
            return response()->json([
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $this->get_message('server_error'),
            ], Response::HTTP_INTERNAL_SERVER_ERROR); 
        }
    }

    public function active_customers()
    {
        try {
            $customers = Customer::with('address')->orderBy('id', 'desc')->get();

            return response()->json([
                'status_code' => 200,
                'data' => $customers, 
            ], 200);
        } catch (\Exception $e) {
            \Log::error('Customer List Error: ' . $e->getMessage());

            return response()->json([
                'status_code' => \Illuminate\Http\Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $this->get_message('server_error'),
            ], \Illuminate\Http\Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update_status(Request $request, string $id)
    {
        try {
            $customer = Customer::where('uuid', $id)->first();
            if ($customer) {
                $customer->status = $request->status;
                if ($customer->save()) {
                    return response()->json([
                        'status_code'=>200,
                        'message'=>'Customer status updated successfully'
                    ], 200);
                } else {
                    return response()->json([
                        'status_code'=>500,
                        'message'=>'Internal Server Error'
                    ], 500);
                }
            } else {
                return response()->json([
                    'status_code'=>404,
                    'message'=>'Page not found'
                ], 404);
            }
        } catch (\Throwable $e) {
            return response()->json([
                'status_code'=>500,
                'message'=>$e->getMessage()
            ], 500);
        }
    }
    
}
