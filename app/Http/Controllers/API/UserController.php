<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Symfony\Component\HttpFoundation\Response;
use Exception;
use Carbon\Carbon;
use Mail;
use Auth;
use Hash;
use DB;
use App\Models\User;
use App\Models\Role;
use App\Models\Menu;
use App\Models\OtpVerification;
use App\Models\Permission_assign;
use App\Services\PermissionService;
use Illuminate\Support\Facades\Validator;
use App\Traits\MessageTrait;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    use MessageTrait;
    protected $permissionService;

    public function __construct(PermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    public function get_users()
    {
        $menu_id = request()->header('menu-uuid');
        $permissions = $this->permissionService->checkPermissions($menu_id);

        $get_all_users = User::join('roles', 'users.role_id', '=', 'roles.id')
            ->select('users.*', 'roles.role as role_name')->where("users.uuid",'!=',Auth::user()->uuid)->orderBy('created_at', 'desc');
        
        if ($permissions['view']) {
            if (!$permissions['viewglobal']) {
                $get_all_users = $get_all_users->where('users.auth_id', Auth::user()->uuid);
            }
        }else{
            if (Auth::user()->hasPermission('viewglobal')) {
                $get_all_users = $get_all_users;
            } else {
                return response()->json(['message' => 'Invalid permission for this action'], Response::HTTP_FORBIDDEN);
            }
        }
        
        $get_all_users = $get_all_users->get();

        $get_all_users->transform(function ($user) {
            $user->name = $user->first_name . ' ' . $user->last_name; // Combine first name and last name
            return $user;
        });

        if ($get_all_users) {

            return response()->json([

                'status_code' => Response::HTTP_OK,
                'permissions' => $permissions,
                'data' => $get_all_users,

            ], Response::HTTP_OK);
        }
    }


    public function edit_user($uuid)
    {

        $edit_user = User::where('uuid', $uuid)->first();
        $role = Role::where('id', $edit_user->role_id)->first();

        $menu_id = request()->header('menu-uuid');

        $role_id = Auth::user()->role_id; // Get the authenticated user's auth_id
        $get_menu = Menu::where('uuid', $menu_id)->first();
        
        $add = 0;
        $update = 0;
        $delete = 0;

        if ($role_id != "1") {

            $check_permission = Permission_assign::where('role_id', $role_id)
                ->where('menu_id', $get_menu->id)
                ->get();

            foreach ($check_permission as $permission) {

                if ($permission->permission_id == '1') {
                    $add = 1;
                }

                if ($permission->permission_id == '3') {
                    $update = 1;
                }

                if ($permission->permission_id == '4') {
                    $delete = 1;
                }
            }
        } else {

            $add = 1;
            $update = 1;
            $delete = 1;
        }

        if ($edit_user) {


            $edit_user->name = $edit_user->first_name . ' ' . $edit_user->last_name;
            $edit_user->role_name = $role ? $role->role : null;

            return response()->json([

                'status_code' => Response::HTTP_OK,
                'new' => $add,
                'update' => $update,
                'delete' => $delete,
                'data' => $edit_user,

            ], Response::HTTP_OK);
        } else {

            return response()->json([

                'status_code' => Response::HTTP_NOT_FOUND,
                'message' => $this->get_message('not_found'),

            ], Response::HTTP_NOT_FOUND);
        }
    }

    public function profile_edit($uuid)
    {
        $edit_user = User::where('uuid', $uuid)->first();
        if ($edit_user) {
            $edit_user->name = $edit_user->first_name . ' ' . $edit_user->last_name;
            return response()->json([
                'status_code' => Response::HTTP_OK,
                'data' => $edit_user,
            ], Response::HTTP_OK);
        } else {
            return response()->json([
                'status_code' => Response::HTTP_NOT_FOUND,
                'message' => $this->get_message('not_found'),
            ], Response::HTTP_NOT_FOUND);
        }
    }


    public function profile_update(Request $request)
    {


        $validator = Validator::make($request->all(), [
            'first_name' => 'required|regex:/^[a-zA-Z0-9\s\-]+$/|min:3|max:20',
            'last_name' => 'nullable|regex:/^[a-zA-Z0-9\s\-]+$/|min:3|max:20',
            'email' => 'required|email|max:150',
            'role_id' => 'required|numeric',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,svg|max:5120',
            'status' => 'required|numeric',
            'organization' => 'nullable|min:3|max:50',
            'address' => 'nullable|min:3|max:60',
            'state' => 'nullable|min:3|max:50',
            'zipcode' => 'nullable|min:3|max:50',
            'country' => 'nullable|min:3|max:50',
            'language' => 'nullable|min:3|max:50',
            'phone' => 'required',
        ], [
            'first_name.required' => 'First name is required',
            'first_name.min' => 'First name must be at least 3 characters',
            'first_name.max' => 'First name must be at most 20 characters',
            'last_name.min' => 'Last name must be at least 3 characters',
            'last_name.max' => 'Last name must be at most 20 characters',
            'email.required' => 'Email is required',
            'role_id.required' => 'Role is required',
            'status.required' => 'Status is required',
            'image.max' => 'Image size should be less than 5MB',
        ]);

        if ($validator->fails()) {

            $message = $validator->messages();

            return response()->json([

                'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'errors' => strval($validator->errors())

            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }


        try {
            session()->forget('user');
            $profileNew = [];
            $uuid = request()->header('uuid');
            $upd_user = User::where('uuid', $uuid)->first();

            if (!$upd_user) {
                return response()->json([
                    'status_code' => Response::HTTP_NOT_FOUND,
                    'message' => $this->get_message('not_found'),
                ], Response::HTTP_NOT_FOUND);
            }

            // Check if the 'image' file is present in the request
            if ($request->hasFile('image')) {
                $file = $request->file('image');
                $fileName = time() . '_' . $file->getClientOriginalName(); // Prepend timestamp for unique filename
                $folderName = '/upload_files/users/';
                $destinationPath = public_path() . $folderName;

                // Ensure the directory exists, if not create it
                if (!file_exists($destinationPath)) {
                    mkdir($destinationPath, 0755, true);
                }

                $file->move($destinationPath, $fileName);
                $upd_user->image = $folderName . $fileName;
                $profileNew['image'] = $folderName . $fileName;
            }

            // Update other user fields except 'image'
            $upd_user->fill($request->except('image'));

            // Update IP address
            $upd_user->ip = $request->ip();

            $profileNew['uuid'] = $upd_user->uuid;
            $profileNew['ip'] = $request->ip();
            $profileNew['role_id'] = $request->role_id;
            $profileNew['status'] = $request->status;
            $profileNew['first_name'] = $request->first_name;
            $profileNew['last_name'] = $request->last_name;
            $profileNew['email'] = $request->email;
            $profileNew['organization'] = $request->organization;
            $profileNew['phone'] = $request->phone;
            $profileNew['address'] = $request->address;
            $profileNew['state'] = $request->state;
            $profileNew['zipcode'] = $request->zipcode;
            $profileNew['language'] = $request->language;

            $request->session()->put('user', $profileNew);

            // otp send
            $email = $request->email;
            if (isset($email)) {
                $user = User::where('id', '!=', $upd_user->id)->where('email', $email)->first();
                if ($user) {
                    return response()->json([
                        'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                        'errors' => strval(json_encode([
                            'email' => ['Email already exists']
                        ]))
                    ], Response::HTTP_UNPROCESSABLE_ENTITY);
                }
                $delete_otp = OtpVerification::where('user_id', $upd_user->id)->delete();

                if (env('DEMO_MODE') == 'On') {
                    $otp = '000000';
                } else {
                    $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                }
                $expiresAt = now()->addMinutes(config('app.expire_minute'));
                OtpVerification::create([
                    'user_id' => $upd_user->id,
                    'otp' => Hash::make($otp), // Store hashed OTP for security
                    'otp_show' => $otp,
                    'expires_at' => $expiresAt,
                ]);
                $data = [
                    'details' => [
                        'heading'   => 'Verify OTP',
                        'FromEmail' => config('app.from_email'),
                        'name' => "HI " . $request->first_name . ' ' . $request->last_name,
                        'email'   => $request->email,
                        'verification_code' => $otp,
                        'currentDate'   => Carbon::now()->format('d-M-Y'),
                        'website' => config('app.name'),
                    ]
                ];
                $sendMail = Mail::send('emailtemplate/profile_otp_verification', $data, function ($message) use ($data) {
                    $message->from($data['details']['FromEmail'], $data['details']['website']);
                    $message->to($data['details']['email'])->subject($data['details']['heading']);
                });
                if ($sendMail) {
                    try {
                        // Logic to send OTP
                        // For example, using a service or library to send the OTP

                        // If sending is successful
                        return response()->json([

                            'status_code' => Response::HTTP_OK,
                            'message' => $this->get_message('otp_success'),

                        ], Response::HTTP_OK);
                    } catch (\Exception $e) {
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
        } catch (\Exception $e) {
            // Handle general exceptions
            Log::error('Failed to update user: ' . $e->getMessage());
            return response()->json([
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $this->get_message('server_error'),
            ], Response::HTTP_INTERNAL_SERVER_ERROR); // 500 Internal Server Error
        }
    }

    public function update_user(Request $request)
    {


        $validator = Validator::make($request->all(), [
            'first_name' => 'required|min:3|max:30|regex:/^[a-zA-Z0-9\s\-]+$/',
            'last_name' => 'nullable|min:3|max:40|regex:/^[a-zA-Z0-9\s\-]+$/',
            'email' => 'required|max:254|email',
            'role_id' => 'required|numeric',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,svg|max:5120',
            'status' => 'required|numeric',
            'phone' => 'required',
        ],[
                
            'image.image' => 'The file must be an image.',
            'image.mimes' => 'The image must be a file of type: jpeg, png, jpg, svg.',
            'image.max' => 'The image must not be greater than 5mb.',
        ]);


        if ($validator->fails()) {

            $message = $validator->messages();

            return response()->json([

                'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'errors' => strval($validator->errors())

            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }


        try {
            $uuid = request()->header('uuid');
            $upd_user = User::where('uuid', $uuid)->first();

            if (!$upd_user) {
                return response()->json([
                    'status_code' => Response::HTTP_NOT_FOUND,
                    'message' => $this->get_message('not_found'),
                ], Response::HTTP_NOT_FOUND);
            }

            // Check if the 'image' file is present in the request
            if ($request->hasFile('image')) {
                $file = $request->file('image');
                $fileName = time() . '_' . $file->getClientOriginalName(); // Prepend timestamp for unique filename
                $folderName = '/upload_files/users/';
                $destinationPath = public_path() . $folderName;

                // Ensure the directory exists, if not create it
                if (!file_exists($destinationPath)) {
                    mkdir($destinationPath, 0755, true);
                }

                $file->move($destinationPath, $fileName);
                $upd_user->image = $folderName . $fileName;
            }

            // Update other user fields except 'image'
            $upd_user->fill($request->except('image'));

            // Update IP address
            $upd_user->ip = $request->ip();

            // Save updated user record
            $save_upd = $upd_user->save();

            if ($save_upd) {

                return response()->json([
                    'status_code' => Response::HTTP_OK,
                    'message' => $this->get_message('update'),
                ], Response::HTTP_OK);
            }
        
        }
         catch (\Exception $e) {
            if ($e->getCode() === '23000') { // SQLSTATE code for integrity constraint violation
                // Handle unique constraint violation
                return response()->json([

                    'status_code' => Response::HTTP_CONFLICT,
                    'message' => $this->get_message('conflict'),

                ], Response::HTTP_CONFLICT); // 409 Conflict
            }
            // Handle general exceptions
            return response()->json([
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $this->get_message('server_error'),
            ], Response::HTTP_INTERNAL_SERVER_ERROR); // 500 Internal Server Error
        }
    }

    public function verify_otp(Request $request)
    {
        try {
            //code...
            $inputOtp = $request->otp;
            $inputEmail = $request->email;
            $currentDateTime = Carbon::now();
            $userSession = $request->session()->get('user');
            $user = User::where('uuid', $userSession['uuid'])->first();
            $otpRecord = OtpVerification::where('user_id', $user->id)->latest()->first();

            if (!$otpRecord) {
                return response()->json([
                    'status_code' => Response::HTTP_BAD_REQUEST,
                    'message' => 'OTP Not Found',
                ], Response::HTTP_BAD_REQUEST);
            }

            if ($otpRecord->expires_at < $currentDateTime) {
                return response()->json([
                    'status_code' => Response::HTTP_BAD_REQUEST,
                    'message' => 'OTP Expired',
                ], Response::HTTP_BAD_REQUEST);
            }

            if (!Hash::check($inputOtp, $otpRecord->otp)) {
                return response()->json([
                    'status_code' => Response::HTTP_BAD_REQUEST,
                    'message' => 'Invalid OTP',
                ], Response::HTTP_BAD_REQUEST);
            }

            if (is_array($userSession)) {
                $update_user = User::where('uuid', $userSession['uuid'])->update($userSession);
            } else {
                Log::error('Failed to update user: User data is not an array');
                return response()->json([
                    'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                    'message' => 'Internal Server Error',
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
            if ($update_user) {
                return response()->json([
                    'status_code' => Response::HTTP_OK,
                    'message' => "Profile has been updated",
                ], Response::HTTP_OK);
            }
        } catch (\Throwable $th) {
            //throw $th;
            // Log the error
            Log::error('Failed to send OTP: ' . $th->getMessage());
            // Handle server error
            return response()->json([
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $this->get_message('otp_failed'),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function delete_user($uuid)
    {

        try {

            $del_user = User::where('uuid', $uuid)->first();

            if (!$del_user) {

                return response()->json([

                    'status_code' => Response::HTTP_NOT_FOUND,
                    'message' => $this->get_message('not_found'),

                ], Response::HTTP_NOT_FOUND);
            } else {

                $delete_user = User::destroy($del_user->id);

                if ($delete_user) {

                    return response()->json([

                        'status_code' => Response::HTTP_OK,
                        'message' => "Staff deleted successfully",

                    ], Response::HTTP_OK);
                }
            }
        } catch (\Exception $e) {
            // Handle general exceptions
            return response()->json([

                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $this->get_message('server_error'),


            ], Response::HTTP_INTERNAL_SERVER_ERROR); // 500 Internal Server Error
        }
    }


    public function change_password(Request $request)
    {


        $validator = Validator::make($request->all(), [

            'email' => 'required|email|exists:users,email',
            'password' => 'required|min:8'

        ]);

        if ($validator->fails()) {

            $message = $validator->messages();

            return response()->json([

                'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'errors' => strval($validator->errors())

            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }


        try {

            // Find the user by email
            $user = User::where('email', $request->email)->first();

            if (!$user) {
                return response()->json([
                    'status_code' => Response::HTTP_NOT_FOUND,
                    'message' => $this->get_message('not_found'),
                ], Response::HTTP_NOT_FOUND);
            }

            // Update the password
            $user->password = Hash::make($request->password);
            $user->save();

            return response()->json([
                'status_code' => Response::HTTP_OK,
                'message' => 'Password updated successfully',
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            // Handle general exceptions
            return response()->json([

                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $this->get_message('server_error'),


            ], Response::HTTP_INTERNAL_SERVER_ERROR); // 500 Internal Server Error
        }
    }


    public function get_active_users()
    {

        try {

            $get_all_active_users = User::select('id','uuid', 'first_name', 'last_name', 'email')
                ->where('status', '1')->orderBy('created_at', 'desc')
                ->get();

            $get_all_active_users->transform(function ($user) {
                $user->name = $user->first_name . ' ' . $user->last_name; // Combine first name and last name
                return $user;
            });

            if ($get_all_active_users) {

                return response()->json([

                    'status_code' => Response::HTTP_OK,
                    'data' => $get_all_active_users,

                ], Response::HTTP_OK);
            }
        } catch (\Exception $e) {
            // Handle general exceptions
            return response()->json([

                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $this->get_message('server_error'),

            ], Response::HTTP_INTERNAL_SERVER_ERROR); // 500 Internal Server Error
        }
    }

    public function profile(Request $request){
        try {
            $user = $request->user();
        
            return response()->json([
                'status_code' => Response::HTTP_OK,
                'user' => $user
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
}
