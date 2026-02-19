<?php

namespace App\Http\Controllers\API\Ecommerce;

use App\Models\Ecommerce\Customer;
use Illuminate\Support\Str;
use App\Traits\MessageTrait;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\Ecommerce\AddressCustomer;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\CMS\Theme;
use App\Models\Page;
use App\Models\Password_reset_token;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class CustomerAuthController extends Controller
{
     use MessageTrait;
    public function register(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => ['required', 'string', 'email', 'max:255', 'unique:customers'],
                'password' => ['required', 'string', Password::min(8)->mixedCase()->numbers()],
                'password_confirmation' => $request->has('password_confirmation') ? ['required', 'string', 'same:password'] : ['nullable'],
                'first_name' => ['string', 'max:100', 'regex:/^[a-zA-Z0-9\s\-]+$/'],
                'last_name' => ['string', 'max:100', 'regex:/^[a-zA-Z0-9\s\-]+$/'],
                'phone' => ['nullable', 'string'],
                'language' => ['nullable', 'string', 'max:255'],
                'tax_setting' => ['nullable', 'string'],
                'notes' => ['nullable', 'string', 'max:5000'],
                'tags' => ['nullable', 'string'],
                'market_emails' => ['nullable', 'in:0,1'],
                'market_sms' => ['nullable', 'in:0,1'],
                'country' => ['nullable', 'string', 'max:255'],
                'address_first_name' => ['nullable', 'string', 'max:255'],
                'address_last_name' => ['nullable', 'string', 'max:255'],
                'company' => ['nullable', 'string', 'max:255'],
                'address' => ['nullable', 'string', 'max:255'],
                'apartment' => ['nullable', 'string', 'max:255'],
                'city' => ['nullable', 'string', 'max:255'],
                'address_phone' => ['nullable', 'string'],
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                    'errors' => $validator->messages()
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $customer = new Customer([
                'uuid' => Str::uuid(),
                'name' => $request->first_name . ' ' . $request->last_name,
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'phone' => $request->phone,
                'language' => $request->language,
                'tax_setting' => $request->tax_setting,
                'market_emails' => $request->market_emails ?? 0,
                'market_sms' => $request->market_sms ?? 0,
                'notes' => $request->notes,
                'tags' => $request->tags
            ]);
            $customer->save();

            $addressFields = [
            'country',
            'address_first_name',
            'address_last_name',
            'company',
            'address',
            'apartment',
            'city',
            'address_phone'
        ];

        $hasAddressData = false;
        foreach ($addressFields as $field) {
            if ($request->filled($field)) {
                $hasAddressData = true;
                break;
            }
        }

        if ($hasAddressData) {
            $customer->address()->create([
                'uuid' => Str::uuid(),
                'type' => 'billing_address',
                'country' => $request->country,
                'address_first_name' => $request->address_first_name,
                'address_last_name' => $request->address_last_name,
                'company' => $request->company,
                'address' => $request->address,
                'apartment' => $request->apartment,
                'city' => $request->city,
                'address_phone' => $request->address_phone,
            ]);
        }

            $token = $customer->createToken('customer_auth_token', ['customer'])->accessToken;

            return response()->json([
                'status_code' => Response::HTTP_CREATED,
                'message' => $this->get_message('registration_success'),
                'token' => $token,
                'customer' => $customer
            ], Response::HTTP_CREATED);

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
     * Login customer
     */
    public function login(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => ['required', 'string', 'email'],
                'password' => ['required', 'string'],
            ]);

            if($validator->fails()) {            
                return response()->json([
                    'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                    'errors' => $validator->messages()
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            // Find customer by email
            $customer = Customer::where('email', $request->email)->first();
            
            // Check if customer exists and password is correct
            if (!$customer || !Hash::check($request->password, $customer->password)) {
                return response()->json([
                    'status_code' => Response::HTTP_UNAUTHORIZED,
                    'message' => 'Invalid credentials'
                ], Response::HTTP_UNAUTHORIZED);
            }

            // Create token
            $token = $customer->createToken('customer_auth_token', ['customer'])->accessToken;
            // The third parameter 'customer' specifies the guard name
            return response()->json([
                'status_code' => Response::HTTP_OK,
                'message' => 'Login successful',
                'token' => $token,
                'customer' => $customer
            ], Response::HTTP_OK);

        } catch (\Throwable $th) {
            Log::error($th->getMessage());
            return response()->json([
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'Server Error',
                'error' => $th->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function forgot_password(Request $request)
    {

        try {

            $validator = Validator::make($request->all(), [
                'email' => 'required|email|exists:users,email',            
            ],[
                'email.exists' => 'User not exist',
            ]); 
            
            
            if($validator->fails()) {
                
                $message = $validator->messages();

                return response()->json([

                    'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                    'errors' => strval($validator->errors())

                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $delete_old_token = Password_reset_token::where('email', $request->email)->delete();
            $get_user_by_email = Customer::where('email', $request->email)->first();

            if ($get_user_by_email) {
                $first_name = $get_user_by_email->first_name;
                $last_name =  $get_user_by_email->last_name;
            }

            $token = Str::random(64);
            // $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

            $save_token = Password_reset_token::insert([
                'email' => $request->email,
                'token' => $token,
                'created_at' => Carbon::now()
            ]);

            if ($save_token) {

                // Email Send To Admin Start: 
                $data = [

                    'details' => [
                        'heading' => "Forgot Password",
                        'FromEmail' => config('app.from_email'),
                        'FName'   => $first_name,
                        'LName'   => $last_name,
                        'Email'   => $request->email,
                        'WebsiteName' => config('app.name'),
                        'currentDate' => Carbon::now()->format('d-M-Y'),
                        'token'       => $token
                    ]

                ];

                $sendMail = Mail::send('emailtemplate/forgot_password', $data, function ($message) use ($data) {

                    $message->from($data['details']['FromEmail'], $data['details']['WebsiteName']);
                    $message->to($data['details']['Email'])->subject($data['details']['heading']);
                });


                if ($sendMail) {

                    try {

                        return response()->json([

                            'status_code' => Response::HTTP_OK,
                            'message' => "Reset Link has been sent",

                        ], Response::HTTP_OK);
                    } catch (\Exception $e) {
                        // Log the error
                        Log::error('Failed to send Password reset link: ' . $e->getMessage());

                        // Handle server error
                        return response()->json([

                            'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                            'message' => 'Failed to send Password reset link. Please try again later.',

                        ], Response::HTTP_INTERNAL_SERVER_ERROR);
                    }
                }
            }
        } catch (\Exception $e) {
            // Handle general exceptions
            return response()->json([
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $this->get_message('server_error'),
                // 'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR); // 500 Internal Server Error
        }
    }

    /**
     * Logout customer
     */
    public function logout(Request $request)
    {
        try {
            // Revoke all tokens
            $request->user()->tokens()->delete();

            return response()->json([
                'status_code' => Response::HTTP_OK,
                'message' => 'Logged out successfully'
            ], Response::HTTP_OK);
            
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
     * Get authenticated customer profile
     */
    public function profile(Request $request)
    {
        try {
            $customer = $request->user();
            
            // Load address if exists
            if ($customer->address_id) {
                $customer->load('address');
            }
            
            $customer->load('orders');
            $customer->paid_order_count = $customer->orders->where('mark_as_paid', 1)->count();
            $customer->unpaid_order_count = $customer->orders->where('mark_as_paid', 0)->count();

            return response()->json([
                'status_code' => Response::HTTP_OK,
                'customer' => $customer
            ], Response::HTTP_OK);
            
        } catch (\Throwable $th) {
            Log::error($th->getMessage());
            return response()->json([
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'Server Error',
                'error' => $th->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    
    public function update_profile(Request $request)
    {
        try {
            $customer = $request->user();
            $auth = Auth::guard('customer')->user();
            $validator = Validator::make($request->all(), [
                'first_name' => ['required', 'string', 'max:255'],
                'last_name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'email', 'max:255', Rule::unique('customers', 'email')->ignore($auth->id)],
                'password' => ['nullable', 'string', Password::min(8)->mixedCase()->numbers()],
                'language' => ['nullable', 'string', 'max:255'],
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'status_code' => Response::HTTP_BAD_REQUEST,
                    'message' => 'Validation Error',
                    'errors' => $validator->messages()
                ], Response::HTTP_BAD_REQUEST);
            }

            if ($request->hasFile('image')) {
                $file = $request->file('image');
                $fileName = time() . '_' . $file->getClientOriginalName();
                $folderName = '/upload_files/customers/';
                $destinationPath = public_path() . $folderName;

                if (!file_exists($destinationPath)) {
                    mkdir($destinationPath, 0755, true);
                }

                $file->move($destinationPath, $fileName);
                $customer->image = $folderName . $fileName;
            }

            $customer->update([
                'first_name' => $request->input('first_name'),
                'last_name' => $request->input('last_name'),
                'email' => $request->input('email'),
                'phone' => $request->input('phone'),
            ]);
            return response()->json([
                'status_code' => Response::HTTP_OK,
                'message' => 'Profile updated successfully'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Log::error($th->getMessage());
            return response()->json([
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'Server Error',
                'error' => $th->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function change_password(Request $request)
    {
        try {
            $customer = $request->user();
            $auth = Auth::guard('customer')->user();

            $validator = Validator::make($request->all(), [
                'current_password' => ['required', 'string'],
                'new_password' => ['required', 'string', Password::min(8)->mixedCase()->numbers()],
                'password_confirmation' => ['required', 'string', 'same:new_password'],
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status_code' => Response::HTTP_BAD_REQUEST,
                    'message' => 'Validation Error',
                    'errors' => $validator->messages()
                ], Response::HTTP_BAD_REQUEST);
            }

            // Verify current password
            if (!Hash::check($request->input('current_password'), $auth->password)) {
                return response()->json([
                    'status_code' => Response::HTTP_BAD_REQUEST,
                    'message' => 'Validation Error',
                    'errors' => ['current_password' => ['The current password is incorrect.']]
                ], Response::HTTP_BAD_REQUEST);
            }

            // Update password
            $customer->update([
                'password' => Hash::make($request->input('new_password')),
            ]);

            return response()->json([
                'status_code' => Response::HTTP_OK,
                'message' => 'Password changed successfully'
            ], Response::HTTP_OK);

        } catch (\Throwable $th) {
            Log::error($th->getMessage());
            return response()->json([
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'Server Error',
                'error' => $th->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function get_columns()
    {
        $columns = [
            'email',
            'password',
        ];
        return response()->json([
            'status_code' => Response::HTTP_OK,
            'message' => 'Login Columns',
            'data' => $columns,
        ], Response::HTTP_OK);
    }

    public function signup_get_columns() {
        $columns = (new Customer())->getConnection()->getSchemaBuilder()->getColumnListing((new Customer())->getTable());
        array_push($columns, 'password_confirmation');
        return response()->json([
            'status_code' => Response::HTTP_OK,
            'message' => 'Login Columns',
            'data' => $columns,
        ], Response::HTTP_OK);
    }

    public function forgetpassword_get_columns() {
        $columns = ['email'];
        return response()->json([
            'status_code' => Response::HTTP_OK,
            'message' => 'Forget Password Columns',
            'data' => $columns,
        ], Response::HTTP_OK);
    }
    public function resetpassword_get_columns() {
        $columns = ['email','password','password_confirmation'];
        return response()->json([
            'status_code' => Response::HTTP_OK,
            'message' => 'Reset Password Columns',
            'data' => $columns,
        ], Response::HTTP_OK);
    }

    public function customerprofile_get_columns(){
        $columns = (new Customer())->getConnection()->getSchemaBuilder()->getColumnListing((new Customer())->getTable());
        $address = (new AddressCustomer())->getConnection()->getSchemaBuilder()->getColumnListing((new AddressCustomer())->getTable());
        $columns = array_merge($columns,$address);
        return response()->json([
            'status_code' => Response::HTTP_OK,
            'message' => 'Customer Profile Columns',
            'data' => $columns,
        ], Response::HTTP_OK);
    }

    public function forgot_password_customer(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email|exists:customers,email',
            ], [
                'email.exists' => 'Customer does not exist',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                    'message' => $validator->errors()->first()
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            Password_reset_token::where('email', $request->email)->delete();
            $customer = Customer::where('email', $request->email)->first();

            $first_name = $customer->first_name ?? '';
            $last_name = $customer->last_name ?? '';
            $token = Str::random(64);

            Password_reset_token::insert([
                'email' => $request->email,
                'token' => $token,
                'created_at' => Carbon::now()
            ]);
            
            $theme = Theme::where('status', 1)->first();
            $page = Page::where('theme_id', $theme->uuid)
            ->where('page_type', 'reset_password')
            ->first();
            $data = [
                'details' => [
                    'heading' => "Forgot Password",
                    'FromEmail' => config('app.from_email'),
                    'FName' => $first_name,
                    'LName' => $last_name,
                    'Email' => $request->email,
                    'WebsiteName' => config('app.name'),
                    'currentDate' => Carbon::now()->format('d-M-Y'),
                    'token' => $token,
                    'reset_link' => getConfigValue('WEB_URL') . '/'.$page->slug.'?token=' . $token.'&email='.$request->email
                ]
            ];
            $sendMail = Mail::send('emailtemplate/customer_forgot_password', $data, function ($message) use ($data) {
                $message->from($data['details']['FromEmail'], $data['details']['WebsiteName']);
                $message->to($data['details']['Email'])->subject($data['details']['heading']);
            });

            if ($sendMail) {
                return response()->json([
                    'status_code' => Response::HTTP_OK,
                    'message' => "Reset Link has been sent",
                ], Response::HTTP_OK);
            }

            return response()->json([
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'Failed to send Password reset link. Please try again later.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);

        } catch (\Exception $e) {
            Log::error('Customer forgot password error: ' . $e->getMessage());

            return response()->json([
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'Server error occurred. Please try again later.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function reset_password_customer(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:customers,email',
            'token' => 'required',
            'password' => [
                'required',
                'string',
                'regex:/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[@#$!%*?&]).{8,}$/',
            ],
            'password_confirmation' => ['required', 'string','regex:/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[@#$!%*?&]).{8,}$/', 'same:password'],

        ], [
            'password.regex' => 'The password must be between 8 and 16 characters and contain at least one uppercase letter, one lowercase letter, one number, and one special character.',
            'password_confirmation.regex' => 'The password must be between 8 and 16 characters and contain at least one uppercase letter, one lowercase letter, one number, and one special character.'
        ]);


        if ($validator->fails()) {

            $message = $validator->messages();

            return response()->json([

                'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'errors' => $validator->errors()

            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $updatePassword = Password_reset_token::where(['email' => $request->email, 'token' => $request->token])->first();

        if ($updatePassword) {

            // Check if token has expired (more than 24 hours)
            $expiresAt = Carbon::parse($updatePassword->expires_at); // Convert expires_at to Carbon instance
            $now = Carbon::now();
            $timeDifference = $now->diffInMinutes($expiresAt);
            if ($timeDifference > 1440) { // Check if the difference is more than 24 hours (1440 minutes)
                // Token has expired
                Password_reset_token::where(['email' => $request->email, 'token' => $request->token])->delete();
                return response()->json([
                    'status_code' => Response::HTTP_UNAUTHORIZED,
                    'message' => 'Token has been expired',
                ], Response::HTTP_UNAUTHORIZED);
            }

            try {

                $user = Customer::where('email', $request->email)->update(['password' => bcrypt($request->password)]);
                $del_token = Password_reset_token::where(['email' => $request->email, 'token' => $request->token])->delete();

                $data = [

                    'details' => [

                        'heading' => "RESET PASSWORD",
                        'FromEmail' => config('app.from_email'),
                        'SignupEmail'   => $request->email,
                        'WebsiteName'   => config('app.name'),
                        'currentDate'   => Carbon::now()->format('d-M-Y'),

                    ]

                ];

                $sendMail = Mail::send('emailtemplate/reset_password_email_template', $data, function ($message) use ($data) {
                    $message->from($data['details']['FromEmail'], $data['details']['WebsiteName']);
                    $message->to($data['details']['SignupEmail'])->subject($data['details']['heading']);
                });

                if ($sendMail) {
                    return response()->json([
                        'status_code' => Response::HTTP_OK,
                        'message' => "Password has been reset",
                    ], Response::HTTP_OK);
                }
            } catch (\Exception $e) {

                // Log the error
                Log::error('Failed to send reset password link: ' . $e->getMessage());

                // Handle server error
                return response()->json([

                    'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                    'message' => $this->get_message('server_error'),

                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        } else {

            return response()->json([
                'status_code' => Response::HTTP_UNAUTHORIZED,
                'message' => 'Token has been expired',
            ], Response::HTTP_UNAUTHORIZED);
        }
    }


}
