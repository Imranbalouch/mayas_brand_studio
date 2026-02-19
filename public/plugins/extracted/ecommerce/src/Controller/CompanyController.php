<?php

namespace App\Http\Controllers\API\Ecommerce;

use App\Models\Ecommerce\Company;
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


class CompanyController extends Controller
{
    protected $permissionService;
    use MessageTrait;
    public function __construct(PermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }
    
    public function index()
{
    try {
        $menuUuid = request()->header('menu-uuid');
        $permissions = $this->permissionService->checkPermissions($menuUuid);
        $companies = Company::with('addresses')
        ->orderBy('id', 'desc');

        if ($permissions['view']) {
            if (!$permissions['viewglobal']) {
                $companies = $companies->where('auth_id', Auth::user()->uuid);
            }
        } else {
            if (Auth::user()->hasPermission('viewglobal')) {
                $companies = $companies;
            } else {
                return response()->json([
                    'message' => 'You do not have permission to view this menu'
                ], Response::HTTP_FORBIDDEN);
            }
        }

        $companies = $companies->get();

        return response()->json([
            'status_code' => 200,
            'permissions' => $permissions,
            'data' => $companies
        ], 200);
    } catch (\Exception $e) {
        Log::error('Company List Error: ' . $e->getMessage());
        return response()->json([
            'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
            'message' => 'Server error occurred while fetching companies.',
        ], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}


    public function store(Request $request)
{
    try {
        // Validate request
        $request->validate([
            'company_name' => 'required|string|max:255',
            'company_id' => 'nullable|string|max:64|unique:companies',
            'main_contact_id' => 'nullable|array',
            'location_id' => 'nullable|string|max:64|unique:companies',
            'catalogs_id' => 'nullable|array',
            'payment_terms_id' => 'nullable|string',
            'deposit' => 'nullable|numeric|min:0|max:99',
            // 'ship_to_address' => 'nullable|in:0,1',
            'order_submission' => 'nullable|string',
            'tax_id' => 'nullable|string|max:256',
            'tax_setting' => 'nullable|string',

            // Billing address
            // 'billing_address.address_first_name' => 'nullable|string|max:255',
            // 'billing_address.address_last_name' => 'nullable|string|max:255',
            // 'billing_address.company' => 'nullable|string|max:255',
            // 'billing_address.address' => 'nullable|string|max:255',
            // 'billing_address.apartment' => 'nullable|string|max:255',
            // 'billing_address.city' => 'nullable|string|max:255',
            // 'billing_address.state' => 'nullable|string|max:255',
            // 'billing_address.address_phone' => 'nullable|string',
            // 'billing_address.country' => 'nullable|string|max:255',

            // Shipping address (array of addresses)
            // 'shipping_address.*.address_first_name' => 'nullable|string|max:255',
            // 'shipping_address.*.address_last_name' => 'nullable|string|max:255',
            // 'shipping_address.*.address_email' => 'nullable|string|email|max:255',
            // 'shipping_address.*.company' => 'nullable|string|max:255',
            // 'shipping_address.*.address' => 'nullable|string|max:255',
            // 'shipping_address.*.apartment' => 'nullable|string|max:255',
            // 'shipping_address.*.city' => 'nullable|string|max:255',
            // 'shipping_address.*.state' => 'nullable|string|max:255',
            // 'shipping_address.*.address_phone' => 'nullable|string',
            // 'shipping_address.*.country' => 'nullable|string|max:255',
        ]);

        $authId = Auth::user()->uuid;

        // Create company
        $company = new Company();
        $company->uuid = Str::uuid();
        $company->auth_id = $authId;
        $company->company_name = $request->company_name;
        $company->company_id = $request->company_id;
        $company->main_contact_id = json_encode($request->main_contact_id); 
        $company->location_id = $request->location_id;
        $company->catalogs_id = json_encode($request->catalogs_id);
        $company->payment_terms_id = $request->payment_terms_id;
        $company->deposit = $request->deposit;
        // $company->ship_to_address = $request->ship_to_address;
        $company->order_submission = $request->order_submission;
        $company->tax_id = $request->tax_id;
        $company->tax_setting = $request->tax_setting;
        $company->approved = $request->approved;
        $company->save();

        // Create billing address
        // if ($request->has('billing_address')) {
        //     $billing = new AddressCustomer();
        //     $billing->uuid = Str::uuid();
        //     $billing->auth_id = $authId;
        //     $billing->company_id = $company->uuid;
        //     $billing->type = 'billing_address';
        //     $billing->address_first_name = $request->billing_address['address_first_name'] ?? null;
        //     $billing->address_last_name = $request->billing_address['address_last_name'] ?? null;
        //     $billing->company = $request->billing_address['company'] ?? null;
        //     $billing->address = $request->billing_address['address'] ?? null;
        //     $billing->apartment = $request->billing_address['apartment'] ?? null;
        //     $billing->city = $request->billing_address['city'] ?? null;
        //     $billing->state = $request->billing_address['state'] ?? null;
        //     $billing->address_phone = $request->billing_address['address_phone'] ?? null;
        //     $billing->country = $request->billing_address['country'] ?? null;
        //     $billing->save();
        // }

        // Create shipping addresses
        // if ($request->has('shipping_address') && is_array($request->shipping_address)) {
        //     foreach ($request->shipping_address as $shippingData) {
        //         $shipping = new AddressCustomer();
        //         $shipping->uuid = Str::uuid();
        //         $shipping->auth_id = $authId;
        //         $shipping->company_id = $company->uuid;
        //         $shipping->type = 'shipping_address';
        //         $shipping->address_first_name = $shippingData['address_first_name'] ?? null;
        //         $shipping->address_last_name = $shippingData['address_last_name'] ?? null;
        //         $shipping->address_email = $shippingData['address_email'] ?? null;
        //         $shipping->company = $shippingData['company'] ?? null;
        //         $shipping->address = $shippingData['address'] ?? null;
        //         $shipping->apartment = $shippingData['apartment'] ?? null;
        //         $shipping->city = $shippingData['city'] ?? null;
        //         $shipping->state = $shippingData['state'] ?? null;
        //         $shipping->address_phone = $shippingData['address_phone'] ?? null;
        //         $shipping->country = $shippingData['country'] ?? null;
        //         $shipping->save();
        //     }
        // }

        return response()->json([
            'status_code' => 200,
            'message' => 'Company has been created successfully',
        ], 200);
    } catch (\Throwable $th) {
        Log::error('Company Store Error: ' . $th->getMessage());
        return response()->json([
            'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
            'message' => 'Error while creating company.',
            'error' => $th->getMessage(),
        ], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}

public function edit($uuid)
{
    try {
        $company = Company::
        // with(['addresses' => function ($query) {
        //     $query->whereIn('type', ['billing_address', 'shipping_address']);
        // }])->
        where('uuid', $uuid)->first();

        if (!$company) {
            return response()->json([
                'status_code' => Response::HTTP_NOT_FOUND,
                'message' => 'Company not found.',
            ], Response::HTTP_NOT_FOUND);
        }

        // Structure the response to include billing and shipping addresses
        $responseData = [
            'company_name' => $company->company_name,
            'company_id' => $company->company_id,
            'main_contact_id' => json_decode($company->main_contact_id, true),
            'location_id' => $company->location_id,
            'catalogs_id' => json_decode($company->catalogs_id, true),
            'payment_terms_id' => $company->payment_terms_id,
            'deposit' => $company->deposit,
            // 'ship_to_address' => $company->ship_to_address,
            'order_submission' => $company->order_submission,
            'tax_id' => $company->tax_id,
            'tax_setting' => $company->tax_setting,
            // 'billingAddress' => $company->addresses->where('type', 'billing_address')->first(),
            // 'shippingAddress' => $company->addresses->where('type', 'shipping_address')->values()->toArray(),
        ];

        return response()->json([
            'status_code' => Response::HTTP_OK,
            'data' => [$responseData],
        ], Response::HTTP_OK);
    } catch (\Exception $e) {
        Log::error('Company Edit Error: ' . $e->getMessage());
        return response()->json([
            'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
            'message' => 'Error while fetching company data.',
        ], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}

    public function update(Request $request, $uuid)
    {
        try {
            $company = Company::where('uuid', $uuid)->first();

            if (!$company) {
                return response()->json([
                    'status_code' => 404,
                    'message' => 'Company not found',
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'company_name' => 'required|string|max:255',
                'company_id' => 'nullable|string|max:64|unique:companies,company_id,' . $company->id,
                'main_contact_id' => 'nullable|array',
                'location_id' => 'nullable|string|max:64|unique:companies,location_id,' . $company->id,
                'catalogs_id' => 'nullable|array',
                'payment_terms_id' => 'nullable|string',
                'deposit' => 'nullable|numeric|min:0|max:99',
                // 'ship_to_address' => 'nullable|in:0,1',
                'order_submission' => 'nullable|string',
                'tax_id' => 'nullable|string|max:256',
                'tax_setting' => 'nullable|string',

                // Billing address
                // 'billing_address.address_first_name' => 'nullable|string|max:255',
                // 'billing_address.address_last_name' => 'nullable|string|max:255',
                // 'billing_address.company' => 'nullable|string|max:255',
                // 'billing_address.address' => 'nullable|string|max:255',
                // 'billing_address.apartment' => 'nullable|string|max:255',
                // 'billing_address.city' => 'nullable|string|max:255',
                // 'billing_address.state' => 'nullable|string|max:255',
                // 'billing_address.address_phone' => 'nullable|string',
                // 'billing_address.country' => 'nullable|string|max:255',

                // Shipping address (array of addresses)
                // 'shipping_address.*.address_first_name' => 'nullable|string|max:255',
                // 'shipping_address.*.address_last_name' => 'nullable|string|max:255',
                // 'shipping_address.*.address_email' => 'nullable|string|email|max:255',
                // 'shipping_address.*.company' => 'nullable|string|max:255',
                // 'shipping_address.*.address' => 'nullable|string|max:255',
                // 'shipping_address.*.apartment' => 'nullable|string|max:255',
                // 'shipping_address.*.city' => 'nullable|string|max:255',
                // 'shipping_address.*.state' => 'nullable|string|max:255',
                // 'shipping_address.*.address_phone' => 'nullable|string',
                // 'shipping_address.*.country' => 'nullable|string|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                    'errors' => $validator->errors()
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            // Update company fields
            $company->update([
                'company_name' => $request->company_name,
                'company_id' => $request->company_id,
                'main_contact_id' => json_encode($request->main_contact_id),
                'location_id' => $request->location_id,
                'catalogs_id' => json_encode($request->catalogs_id),
                'payment_terms_id' => $request->payment_terms_id,
                'deposit' => $request->deposit,
                // 'ship_to_address' => $request->ship_to_address,
                'order_submission' => $request->order_submission,
                'tax_id' => $request->tax_id,
                'tax_setting' => $request->tax_setting,
            ]);

            // Update or create billing address
            // if ($request->has('billing_address')) {
            //     $billingData = $request->billing_address;
            //     $billingAddress = AddressCustomer::where('company_id', $company->uuid)
            //         ->where('type', 'billing_address')
            //         ->first();

            //     if ($billingAddress) {
            //         $billingAddress->update([
            //             'address_first_name' => $billingData['address_first_name'] ?? null,
            //             'address_last_name' => $billingData['address_last_name'] ?? null,
            //             'company' => $billingData['company'] ?? null,
            //             'address' => $billingData['address'] ?? null,
            //             'apartment' => $billingData['apartment'] ?? null,
            //             'city' => $billingData['city'] ?? null,
            //             'state' => $billingData['state'] ?? null,
            //             'address_phone' => $billingData['address_phone'] ?? null,
            //             'country' => $billingData['country'] ?? null,
            //         ]);
            //     } else {
            //         $billingAddress = new AddressCustomer();
            //         $billingAddress->uuid = Str::uuid();
            //         $billingAddress->auth_id = Auth::user()->uuid;
            //         $billingAddress->company_id = $company->uuid;
            //         $billingAddress->type = 'billing_address';
            //         $billingAddress->address_first_name = $billingData['address_first_name'] ?? null;
            //         $billingAddress->address_last_name = $billingData['address_last_name'] ?? null;
            //         $billingAddress->company = $billingData['company'] ?? null;
            //         $billingAddress->address = $billingData['address'] ?? null;
            //         $billingAddress->apartment = $billingData['apartment'] ?? null;
            //         $billingAddress->city = $billingData['city'] ?? null;
            //         $billingAddress->state = $billingData['state'] ?? null;
            //         $billingAddress->address_phone = $billingData['address_phone'] ?? null;
            //         $billingAddress->country = $billingData['country'] ?? null;
            //         $billingAddress->save();
            //     }
            // }

            // Update or create shipping addresses
            // if ($request->has('shipping_address') && is_array($request->shipping_address)) {
                // Delete existing shipping addresses that are not in the request (optional, depending on requirements)
                // AddressCustomer::where('company_id', $company->uuid)->where('type', 'shipping_address')->delete();

                // foreach ($request->shipping_address as $index => $shippingData) {
                    // Check if the shipping address has a UUID (indicating an existing address)
            //         $shippingAddress = isset($shippingData['uuid'])
            //             ? AddressCustomer::where('uuid', $shippingData['uuid'])->where('type', 'shipping_address')->first()
            //             : null;

            //         if ($shippingAddress) {
            //             $shippingAddress->update([
            //                 'address_first_name' => $shippingData['address_first_name'] ?? null,
            //                 'address_last_name' => $shippingData['address_last_name'] ?? null,
            //                 'address_email' => $shippingData['address_email'] ?? null,
            //                 'company' => $shippingData['company'] ?? null,
            //                 'address' => $shippingData['address'] ?? null,
            //                 'apartment' => $shippingData['apartment'] ?? null,
            //                 'city' => $shippingData['city'] ?? null,
            //                 'state' => $shippingData['state'] ?? null,
            //                 'address_phone' => $shippingData['address_phone'] ?? null,
            //                 'country' => $shippingData['country'] ?? null,
            //             ]);
            //         } else {
            //             $shippingAddress = new AddressCustomer();
            //             $shippingAddress->uuid = Str::uuid();
            //             $shippingAddress->auth_id = Auth::user()->uuid;
            //             $shippingAddress->company_id = $company->uuid;
            //             $shippingAddress->type = 'shipping_address';
            //             $shippingAddress->address_first_name = $shippingData['address_first_name'] ?? null;
            //             $shippingAddress->address_last_name = $shippingData['address_last_name'] ?? null;
            //             $shippingAddress->address_email = $shippingData['address_email'] ?? null;
            //             $shippingAddress->company = $shippingData['company'] ?? null;
            //             $shippingAddress->address = $shippingData['address'] ?? null;
            //             $shippingAddress->apartment = $shippingData['apartment'] ?? null;
            //             $shippingAddress->city = $shippingData['city'] ?? null;
            //             $shippingAddress->state = $shippingData['state'] ?? null;
            //             $shippingAddress->address_phone = $shippingData['address_phone'] ?? null;
            //             $shippingAddress->country = $shippingData['country'] ?? null;
            //             $shippingAddress->save();
            //         }
            //     }
            // }

            return response()->json([
                'status_code' => 200,
                'message' => 'Company has been updated successfully',
            ], 200);
        } catch (\Throwable $th) {
            Log::error('Company Update Error: ' . $th->getMessage());
            return response()->json([
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'Error while updating company.',
                'error' => $th->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function deleteShippingAddress($companyUuid, $addressUuid)
{
    try {
        $address = AddressCustomer::where('company_id', $companyUuid)
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

    
public function destroy($uuid)
{
    try {
        $del_company = Company::where('uuid', $uuid)->first();

        if (!$del_company) {
            return response()->json([
                'status_code' => Response::HTTP_NOT_FOUND,
                'message' => 'Company not found.',
            ], Response::HTTP_NOT_FOUND);
        } else {
            // Delete billing address if exists
            if ($del_company->billing_address_id) {
                $billing_address = AddressCustomer::where('uuid', $del_company->billing_address_id)
                                  ->where('type', 'billing_address')
                                  ->first();
                if ($billing_address) {
                    $billing_address->delete(); 
                }
            }
            
            // Delete shipping address if exists
            if ($del_company->shipping_address_id) {
                $shipping_address = AddressCustomer::where('uuid', $del_company->shipping_address_id)
                                   ->where('type', 'shipping_address')
                                   ->first();
                if ($shipping_address) {
                    $shipping_address->delete(); 
                }
            }

            $delete_company = Company::destroy($del_company->id);

            if ($delete_company) {
                return response()->json([
                    'status_code' => Response::HTTP_OK,
                    'message' => 'Company has been deleted successfully',
                ], Response::HTTP_OK);
            }
        }
    } catch (\Exception $e) {
        Log::error('Company Delete Error: ' . $e->getMessage());
        return response()->json([
            'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
            'message' => 'Error while deleting company.',
        ], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}

public function ApprovedStatus(Request $request, string $id)
    {
        
        $request->validate([
            'approved' => 'required|in:0,1', // Ensure status is either 0 or 1
        ]);

        try {
            // Find the Currency by UUID and active status
            $langage = Company::where('uuid', $id)->first();
            //dd($langage);
            if ($langage) {
                // Update the status
                $langage->approved = $request->approved;
                $langage->save();

                return response()->json([
                    'status_code' => 200,
                    'message' => 'Status updated successfully',
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

    public function active_companies()
    {
        try {
            $companies = Company::with('addresses')
                ->orderBy('id', 'desc')
                ->get();
    
             return response()->json([
                'status_code' => 200,
                'data' => $companies,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Company List Error: ' . $e->getMessage());
            return response()->json([
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'Server error occurred while fetching companies.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
