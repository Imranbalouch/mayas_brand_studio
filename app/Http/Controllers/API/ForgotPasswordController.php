<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Symfony\Component\HttpFoundation\Response;
use Exception;
use Mail;
use Auth;
use Session;
use Hash;
use DB;
use App\Models\User;
use App\Models\Password_reset_token;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Str;
use App\Traits\MessageTrait;
use Illuminate\Http\Exceptions\ThrottleRequestsException;

class ForgotPasswordController extends Controller
{

    use MessageTrait;

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
            $get_user_by_email = User::where('email', $request->email)->first();

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


    public function reset_password(Request $request)
    {

        // dd($request->all());

        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'token' => 'required',
            'password' => [
                'required',
                'string',
                'regex:/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[@#$!%*?&]).{8,}$/',
            ],

        ], [
            'password.regex' => 'The password must be between 8 and 16 characters and contain at least one uppercase letter, one lowercase letter, one number, and one special character.'
        ]);


        if ($validator->fails()) {

            $message = $validator->messages();

            return response()->json([

                'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'errors' => strval($validator->errors())

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
            // if ($expiresAt->isPast()) { // Check if the expiration date is in the past
            //     // Token has expired
            //     Password_reset_token::where(['email' => $request->email, 'token' => $request->token])->delete();
            //     return response()->json([
            //         'status_code' => Response::HTTP_UNAUTHORIZED,
            //         'message' => 'Token has been expired',
            //     ], Response::HTTP_UNAUTHORIZED);
            // }

            try {

                $user = User::where('email', $request->email)->update(['password' => bcrypt($request->password)]);
                $del_token = Password_reset_token::where(['email' => $request->email, 'token' => $request->token])->delete();


                // Email Send To Admin Start : 

                $data = [

                    'details' => [

                        'heading' => "RESET PASSWORD",
                        'FromEmail' => config('app.from_email'),
                        'SignupEmail'   => $request->email,
                        'WebsiteName'   => config('app.name'),
                        'currentDate'   => Carbon::now()->format('d-M-Y'),

                    ]

                ];

                //  dd($data);

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
