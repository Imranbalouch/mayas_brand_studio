<?php

namespace App\Http\Controllers\API;

use DB;
use Auth;
use Hash;
use Mail;
use Session;
use Exception; 
use Carbon\Carbon; 
use App\Models\User;
use Illuminate\Support\Str;
use App\Traits\MessageTrait;
use Illuminate\Http\Request;
use App\Models\OtpVerification;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;


class RegisterController extends Controller
{
    
    use MessageTrait;

    public function register(Request $request){ 

        try {

            $validator = Validator::make($request->all(), [
                'first_name' => 'required|min:3|max:30|regex:/^[a-zA-Z0-9\s\-]+$/',
                'last_name' => 'nullable|min:3|max:40|regex:/^[a-zA-Z0-9\s\-]+$/',
                'email' => 'required|max:254|email',
                'password' => 'required|min:6|max:16',
                'role_id' => 'required|numeric',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,svg|max:2048',
                'status' => 'required|numeric',
            ], [
                
                'image.image' => 'The file must be an image.',
                'image.mimes' => 'The image must be a file of type: jpeg, png, jpg, svg.',
                'image.max' => 'The image must not be greater than 2mb.',
            ]);
            
            
            if($validator->fails()){
                
                $message = $validator->messages();
                
                return response()->json([
                    
                    'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                    'errors' => strval($validator->errors())
                
                ], Response::HTTP_UNPROCESSABLE_ENTITY);

            }

            $user = $request->all();
            $user['uuid'] = Str::uuid();
            $user['password'] = bcrypt($user['password']);
            $user['ip'] = $request->ip();
            $user['auth_id'] = Auth::user()->uuid;


            if ($request->hasFile('image')) {
                $file = $request->file('image');
                $fileName = time() . '_' . $file->getClientOriginalName(); // Prepend timestamp for unique filename
                $folderName = '/upload_files/users/';
                $destinationPath = public_path() . $folderName;
        
                // Ensure the directory exists, if not create it
                if (!file_exists($destinationPath)) {
                    mkdir($destinationPath, 0755, true);
                }
        
                // Move the file to the destination path
                $file->move($destinationPath, $fileName);
        
                // Update the menu's icon path
                $user['image'] = $folderName . $fileName;
            }

            $save_user = User::create($user);

            if($save_user) { 
                
                // Email Send To Admin Start : 
                    
                $data = [
                    
                    'details'=>[
                    
                        'WebsiteName' => config('app.name'),
                        'heading' => "Welcome to the Team!",
                        'FromEmail' => config('app.from_email'),
                        'FName' => $save_user->first_name, 
                        'LName' => $save_user->last_name, 
                        'SignupEmail' => $request->email, 
                        'hi_message' => $request->first_name.' '.$request->last_name,
                        'currentDate'  => Carbon::now()->format('d-M-Y'),
                        
                    ]
                
                ];
                
                //  dd($data);
                
                // User Email
                Mail::send('emailtemplate/welcome_email', $data, function($message) use ($data){
                
                    $message->from($data['details']['FromEmail'], $data['details']['WebsiteName']); 
                    
                    $message->to($data['details']['SignupEmail'])->subject($data['details']['heading']);
                
                });

                
                return response()->json([
                    
                    'status_code' => 201,
                    'message' => $this->get_message('registration_success'),

                ], 201);

            }
        
        }catch (QueryException $e) {
            
            if ($e->getCode() === '23000') { // SQLSTATE code for integrity constraint violation
                // Handle unique constraint violation
                return response()->json([

                    'status_code' => Response::HTTP_CONFLICT,
                    'message' => $this->get_message('conflict'),

                ], Response::HTTP_CONFLICT); // 409 Conflict
            }

            // For other SQL errors
            return response()->json([

                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $this->get_message('server_error'),
                

            ], Response::HTTP_INTERNAL_SERVER_ERROR); // 500 Internal Server Error
        
        }catch (\Exception $e) { 
            // Handle general exceptions
            return response()->json([

                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $this->get_message('server_error'),
                

            ], Response::HTTP_INTERNAL_SERVER_ERROR); // 500 Internal Server Error
        }

            
    }


    // Login Via API
    public function login(Request $request){


        $validator = Validator::make($request->all(), [
             
            'email' => 'required|email',
            'password' => 'required',

        ]);
        
        if($validator->fails()){
            
            $message = $validator->messages();
            
            return response()->json([
                
                'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'errors' => strval($validator->errors())
            
            ], Response::HTTP_UNPROCESSABLE_ENTITY);

        } 
        
        $email = $request->email;
        $password = $request->password;

        if(Auth::attempt(['email' => $email, 'password' => $password])) {

            $user = Auth::user();

            if($user->status == 0){

                return response()->json([

                    'status_code' => Response::HTTP_UNAUTHORIZED,
                    'message' => 'Your account is deactive, please contact admin.',

                ], Response::HTTP_UNAUTHORIZED);

            }
            
            // OTP Process 
            if (getConfigValue('DEMO_MODE') == 'On') {
                $otp = '000000';
            }else{
                $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            }
            $expiresAt = now()->addMinutes(config('app.expire_minute')); 

            // Save OTP in DB
            OtpVerification::create([
                'user_id' => $user->id,
                'otp' => Hash::make($otp),
                'otp_show' => $otp,
                'expires_at' => $expiresAt,
            ]);

            $data = [
                
                'details'=>[              
                    'heading'   => 'Verify OTP',
                    'FromEmail' => config('app.from_email'),
                    'FName' => $user->first_name, 
                    'LName' => $user->last_name, 
                    'email'   => $user->email,
                    'verification_code' => $otp,
                    'currentDate'   => Carbon::now()->format('d-M-Y'),
                    'website' => config('app.name'),
                ]
            
            ];
            
            // User Email
            if (env('DEMO_MODE') != 'On') {
                $sendMail = Mail::send('emailtemplate/otp_verification', $data, function($message) use ($data){
                
                    $message->from($data['details']['FromEmail'], $data['details']['website']);
                    $message->to($data['details']['email'])->subject($data['details']['heading']);
                
                });
                if($sendMail){
                        
                    try {
    
                        return response()->json([
    
                            'status_code' => Response::HTTP_OK,
                            'message' => $this->get_message('otp_success'),
    
                        ], Response::HTTP_OK);
    
                    }catch (\Exception $e) {
    
                        // Log the error
                        Log::error('Failed to send OTP: ' . $e->getMessage());
            
                        // Handle server error
                        return response()->json([
    
                            'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                            'message' => $this->get_message('otp_failed'),
    
                        ], Response::HTTP_INTERNAL_SERVER_ERROR);
    
                    }
                    
                }
            }else{
                try {
    
                    return response()->json([

                        'status_code' => Response::HTTP_OK,
                        'message' => $this->get_message('otp_success'),

                    ], Response::HTTP_OK);

                }catch (\Exception $e) {

                    // Log the error
                    Log::error('Failed to send OTP: ' . $e->getMessage());
        
                    // Handle server error
                    return response()->json([

                        'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                        'message' => $this->get_message('otp_failed'),

                    ], Response::HTTP_INTERNAL_SERVER_ERROR);

                }
            }
          
            
        }else{

            return response()->json([

                'status_code' => Response::HTTP_UNAUTHORIZED,
                'message' => 'Invalid credentials',

            ], Response::HTTP_UNAUTHORIZED);

        }


    } 



    public function logout(Request $request)
    {
        
        $user = Auth::user(); 
        $user->token()->revoke();

        if($user){
            activity()->useLog('Logout')->causedBy($user)->withProperties($user)->log('You have Been Logout');
        }

        return response()->json([
            
            'status_code' => Response::HTTP_OK,
            'message' => $this->get_message('logout_success'),
        
        ], Response::HTTP_OK);

    }


    public function verify_otp(Request $request)
    {
        try {
            //code...
            $inputOtp = $request->otp;
            $inputEmail = $request->email;
            $currentDateTime = Carbon::now();
    
            $user = User::where('email', $inputEmail)->first();
            $otpRecord = OtpVerification::where('user_id', $user->id)->latest()->first();
    
            if(!$otpRecord){
                return response()->json([
                    'status_code' => Response::HTTP_BAD_REQUEST,
                    'message' => 'OTP Not Found',
                ], Response::HTTP_BAD_REQUEST);
            }

            if($otpRecord->expires_at < $currentDateTime) {
                return response()->json([
                    'status_code' => Response::HTTP_BAD_REQUEST,
                    'message' => 'OTP Expired',
                ], Response::HTTP_BAD_REQUEST);
            }

            if(!Hash::check($inputOtp, $otpRecord->otp)) {
                return response()->json([
                    'status_code' => Response::HTTP_BAD_REQUEST,
                    'message' => 'Invalid OTP',
                ], Response::HTTP_BAD_REQUEST);
            }
        
            $delete_otp = OtpVerification::where('user_id', $user->id)->delete();
            $accessToken = $user->createToken('MyToken', ['admin'])->accessToken;
    
            activity('Login')
            ->causedBy($user)
            ->event('Attempt Login')
            ->withProperties(['ip' => $request->ip()])
            ->log('Attempt Login');
            
            return response()->json([
                'status_code'=> Response::HTTP_OK,
                'message' => $this->get_message('login_success'),
                'accessToken'=> $accessToken,
                'user' => [
                    'uuid' => $user->uuid,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'email' => $user->email,
                    'role_id' => $user->role_id,
                    'photoUrl' => $user->image,
                ],
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            //throw $th;
            //dd($th);
             // Log the error
             Log::error('Failed to send OTP: ' . $th->getMessage());
             // Handle server error
             return response()->json([
                 'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                 'message' => $this->get_message('otp_failed'),
             ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

    }


    public function resend_otp(Request $request){

        $validator = Validator::make($request->all(), [
             
            'email' => 'required|email|exists:users,email',

        ]); 
        
        if($validator->fails()){
            
            $message = $validator->messages();
            
            return response()->json([
                
                'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'errors' => strval($validator->errors())
            
            ], Response::HTTP_UNPROCESSABLE_ENTITY);

        } 
        
        $email = $request->email;

        if(isset($email)) {

            $user = User::where('email', $email)->first();
            $delete_otp = OtpVerification::where('user_id', $user->id)->delete();

            // OTP Process 
            if (env('DEMO_MODE') == 'On') {
                $otp = '000000';
            }else{
                $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            }
            $expiresAt = now()->addMinutes(config('app.expire_minute')); 

            // Save OTP in DB
            OtpVerification::create([
                'user_id' => $user->id,
                'otp' => Hash::make($otp), // Store hashed OTP for security
                'otp_show' => $otp,
                'expires_at' => $expiresAt,
            ]);

            $data = [
                
                'details'=>[              
                    'heading'   => 'Verify OTP',
                    'FromEmail' => config('app.from_email'),
                    'name' => "HI ".$user->name.' '.$user->lname, 
                    'email'   => $user->email,
                    'verification_code' => $otp,
                    'currentDate'   => Carbon::now()->format('d-M-Y'),
                    'website' => config('app.name'),
                ]
            
            ];

            if (env('DEMO_MODE') != 'On') {
                // User Email
                $sendMail = Mail::send('emailtemplate/otp_verification', $data, function($message) use ($data){
                
                    $message->from($data['details']['FromEmail'], $data['details']['website']);
                    $message->to($data['details']['email'])->subject($data['details']['heading']);
                
                });
                if($sendMail){
                        
                    try {
    
                        // Logic to send OTP
                        // For example, using a service or library to send the OTP
            
                        // If sending is successful
                        return response()->json([
    
                            'status_code' => Response::HTTP_OK,
                            'message' => $this->get_message('otp_success'),
    
                        ], Response::HTTP_OK);
    
                    }catch (\Exception $e) {
    
                        // Log the error
                        Log::error('Failed to send OTP: ' . $e->getMessage());
            
                        // Handle server error
                        return response()->json([
    
                            'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                            'message' => $this->get_message('otp_failed'),
    
                        ], Response::HTTP_INTERNAL_SERVER_ERROR);
    
                    }
                    
                }
            }else{
                try {
    
                    // Logic to send OTP
                    // For example, using a service or library to send the OTP
        
                    // If sending is successful
                    return response()->json([

                        'status_code' => Response::HTTP_OK,
                        'message' => $this->get_message('otp_success'),

                    ], Response::HTTP_OK);

                }catch (\Exception $e) {

                    // Log the error
                    Log::error('Failed to send OTP: ' . $e->getMessage());
        
                    // Handle server error
                    return response()->json([

                        'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                        'message' => $this->get_message('otp_failed'),

                    ], Response::HTTP_INTERNAL_SERVER_ERROR);

                }
            }
            
        }

    }


}