<?php

namespace App\Http\Controllers\API\Ecommerce;

use Illuminate\Support\Str;
use App\Traits\MessageTrait;
use Illuminate\Http\Request;
use App\Models\Ecommerce\AddressCustomer;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\Response;

class AddressController extends Controller
{
    use MessageTrait;
    public function get_address_default(Request $request){
        try {
            // Retrieve address data
            $authId = $request->header('authid'); // Get the authenticated user's auth_id();
            $address = AddressCustomer::with('customer')->where('customer_id', $authId)->where('is_default', 1)->orderByDesc('id')->get();
            return response()->json(['data' => $address], Response::HTTP_OK);
        } catch (\Exception $e) {
            Log::error("Error fetching address: " . $e->getMessage());
            return response()->json(['error' => 'Unable to fetch address'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function get_address(Request $request){
        try {
            // Retrieve address data
            $authId = $request->header('authid'); // Get the authenticated user's auth_id();
            $address = AddressCustomer::where('customer_id', $authId)->orderByDesc('id')->get();
            return response()->json(['data' => $address], Response::HTTP_OK);
        } catch (\Exception $e) {
            Log::error("Error fetching address: " . $e->getMessage());
            return response()->json(['error' => 'Unable to fetch address'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function add_address(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'customer_id' => 'required',
                'company_id' => 'nullable|integer',
                'type' => 'nullable|string',
                'country' => 'nullable|string',
                'country_uuid' => 'nullable|string',
                'city_uuid' => 'nullable|string',
                'address_first_name' => 'nullable|string',
                'address_last_name' => 'nullable|string',
                'address_email' => 'nullable|email',
                'company' => 'nullable|string',
                'address' => 'nullable|string',
                'apartment' => 'nullable|string',
                'postal_code' => 'nullable|string',
                'city' => 'nullable|string',
                'state' => 'nullable|string',
                'address_phone' => 'required|string',
                'is_default' => 'nullable|in:0,1'
            ]);

            if (isset($validatedData['is_default']) && $validatedData['is_default'] == 1) {
                $existingDefault = AddressCustomer::where('customer_id', $validatedData['customer_id'])
                    ->where('is_default', 1)
                    ->first();

                if ($existingDefault) {
                    return response()->json([
                            'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                            'message' => 'Another address is already set as default. Only one address can be default.'
                        ], Response::HTTP_UNPROCESSABLE_ENTITY);

                }
            }
    
            $validatedData['uuid'] = Str::uuid();
    
            $address = AddressCustomer::create($validatedData);
    
            return response()->json(['status_code' => Response::HTTP_CREATED,'message' => 'Address added successfully', 'data' => $address], Response::HTTP_CREATED);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['error' => $e->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Exception $e) {
            Log::error("Error adding address: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update_address(Request $request, $uuid)
    {
        try {
            $address = AddressCustomer::where('uuid', $uuid)->firstOrFail();

            $validatedData = $request->validate([
                'customer_id' => 'required',
                'company_id' => 'nullable|integer',
                'type' => 'nullable|string',
                'country' => 'nullable|string',
                'country_uuid' => 'nullable|string',
                'city_uuid' => 'nullable|string',
                'address_first_name' => 'nullable|string',
                'address_last_name' => 'nullable|string',
                'address_email' => 'nullable|email',
                'company' => 'nullable|string',
                'address' => 'nullable|string',
                'apartment' => 'nullable|string',
                'postal_code' => 'nullable|string',
                'city' => 'nullable|string',
                'state' => 'nullable|string',
                'address_phone' => 'required|string',
                'is_default' => 'nullable|in:0,1'
            ]);

            if (isset($validatedData['is_default']) && $validatedData['is_default'] == 1) {
                $existingDefault = AddressCustomer::where('customer_id', $validatedData['customer_id'])
                    ->where('is_default', 1)
                    ->where('uuid', '!=', $uuid) 
                    ->first();

                if ($existingDefault) {
                    return response()->json([
                            'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                            'message' => 'Another address is already set as default. Only one address can be default.'
                        ], Response::HTTP_UNPROCESSABLE_ENTITY);

                }
            }

            $address->update($validatedData);

            return response()->json([
                'status_code' => Response::HTTP_OK,
                'message' => 'Address updated successfully',
                'data' => $address
            ], Response::HTTP_OK);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['error' => $e->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Exception $e) {
            Log::error("Error updating address: " . $e->getMessage());
            return response()->json(['error' => 'Unable to update address'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function get_columns()
    {
        $columns = (new AddressCustomer())->getConnection()->getSchemaBuilder()->getColumnListing((new AddressCustomer())->getTable());
        
        return response()->json([
            'status_code' => Response::HTTP_OK,
            'message' => $this->get_message('add'),
            'data' => $columns,
        ], Response::HTTP_OK);
    }
    
    }
    