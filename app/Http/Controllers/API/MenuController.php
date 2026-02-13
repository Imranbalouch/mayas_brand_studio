<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\BussinessSetting;
use App\Models\CMS\Theme;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Symfony\Component\HttpFoundation\Response;
use Exception;
use Mail;
use Auth;
use Session;
use Hash;
use DB;
use App\Models\Menu;
use App\Models\Language;
use App\Models\Menu_translation;
use App\Models\Permission_assign;
use App\Models\User_special_permission;
use App\Models\User;
use App\Services\PermissionService;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Str;
use App\Traits\MessageTrait;
use Illuminate\Support\Facades\Route;

class MenuController extends Controller
{
    use MessageTrait;
    protected $permissionService;
    public function __construct(PermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    public function add_menu(Request $request)
    {

        $validator = Validator::make($request->all(), [

            'name' => 'required|max:50|regex:/^[a-zA-Z0-9\s\-\(\)]+$/',
            'sort_id' => 'required|numeric',
            'icon' => config('app.image_validation'),
            'parent_id' => '',
            'url' => '',
            'status' => ''

        ],[
                
            'icon.icon' => 'The file must be an image.',
            'icon.mimes' => 'The image must be a file of type: jpeg, png, jpg, gif, svg.',
            'icon.max' => 'The image must not be greater than 2mb.',
        ]);


        if ($validator->fails()) {

            $message = $validator->messages();

            return response()->json([

                'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'errors' => strval($validator->errors())

            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }


        try {

            $check_if_already = Menu::where('name', $request->name)->where('sort_id', $request->sort_id)->get();

            if (count($check_if_already) > 0) {

                return response()->json([

                    'status_code' => Response::HTTP_CONFLICT,
                    // 'message' => 'This Record has already been taken.',
                    'message' => $this->get_message('conflict'),

                ], Response::HTTP_CONFLICT); // 409 Conflict


            } else {

                $menu = $request->all();
                $menu['uuid'] = Str::uuid();
                $menu['auth_id'] = Auth::user()->uuid;


                if ($request->hasFile('icon')) {
                    $file = $request->file('icon');
                    $fileName = time() . '_' . $file->getClientOriginalName(); // Prepend timestamp for unique filename
                    $folderName = '/upload_files/menu/';
                    $destinationPath = public_path() . $folderName;

                    // Ensure the directory exists, if not create it
                    if (!file_exists($destinationPath)) {
                        mkdir($destinationPath, 0755, true);
                    }

                    // Move the file to the destination path
                    $file->move($destinationPath, $fileName);

                    // Update the menu's icon path
                    $menu['icon'] = $folderName . $fileName;
                }

                // Create the menu item
                $save_menu = Menu::create($menu);


                if ($save_menu) {

                    return response()->json([

                        'status_code' => Response::HTTP_CREATED,
                        'message' => 'Menu added successfully',

                    ], Response::HTTP_CREATED);
                }
            }
        } catch (QueryException $e) {

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


        } catch (\Exception $e) {
            // Handle general exceptions
            return response()->json([

                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $this->get_message('server_error'),

            ], Response::HTTP_INTERNAL_SERVER_ERROR); // 500 Internal Server Error
        }
    }


    public function edit_menu($uuid)
    {

        try {

            $menu_id = request()->header('menu-uuid');
            $get_menu = Menu::where('uuid', $menu_id)->first();
            $edit_menu_by_id = Menu::where('uuid', $uuid)->first();

            $menu_translation_get = Menu_translation::where('menu_id', $edit_menu_by_id->id)
                ->where('status', '1')->get();

            $get_active_language = Language::where('status', '1')->get();

            $now = Carbon::now();
            $auth_id = Auth::user()->uuid;

            if (count($get_active_language) > 0) {

                foreach ($get_active_language as $key => $language) {

                    $check_menu_translation = Menu_translation::where('menu_id', $edit_menu_by_id->id)
                        ->where('language_id', $language->id)
                        ->where('status', '1')->first();

                    if ($check_menu_translation) {
                    } else {

                        $save_menu_translation = Menu_translation::insert([
                            ['uuid' => Str::uuid(), 'menu_id' => $edit_menu_by_id->id, 'name' => $edit_menu_by_id->name, 'language_id' => $language->id, 'auth_id' => $auth_id, 'created_at' => $now, 'updated_at' => $now],
                        ]);
                    }
                }
            }

            $menu_translations = Menu_translation::where('menu_id', $edit_menu_by_id->id)
                ->where('menu_translations.status', '1')
                ->where('languages.status', '1')
                ->join('languages', 'menu_translations.language_id', '=', 'languages.id')
                ->select('languages.code as language_code', 'languages.name as language_name', 'languages.flag as flag', 'languages.rtl as dir', 'menu_translations.*')
                ->get();


            if ($edit_menu_by_id) {

                $edit_menu_by_id->translations = $menu_translations;

                return response()->json([

                    'status_code' => Response::HTTP_OK,
                    'data' => $edit_menu_by_id,

                ], Response::HTTP_OK);
            } else {

                return response()->json([
                    'status_code' => Response::HTTP_NOT_FOUND,
                    'message' => $this->get_message('not_found'),
                ], Response::HTTP_NOT_FOUND);
            }
        } catch (\Exception $e) {
            // Handle general exceptions
            return response()->json([

                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $this->get_message('server_error'),

            ], Response::HTTP_INTERNAL_SERVER_ERROR); // 500 Internal Server Error
        }
    }


    public function update_menu(Request $request,string $uuid)
    {
        $validator = Validator::make($request->all(), [
            'icon' => config('app.image_validation'),
            'parent_id' => '',
            'url' => '',
            'status' => ''
        ]);

        if ($validator->fails()) {

            $message = $validator->messages();

            return response()->json([

                'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'errors' => strval($validator->errors())

            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }


        try {
            $upd_menu = Menu::where('uuid', $uuid)->first();

            if (!$upd_menu) {
                return response()->json([
                    'status_code' => Response::HTTP_NOT_FOUND,
                    'message' => $this->get_message('not_found'),
                ], Response::HTTP_NOT_FOUND);
            }

            if ($request->hasFile('icon')) {
                $file = $request->file('icon');
                $fileName = time() . '_' . $file->getClientOriginalName();
                $folderName = '/upload_files/menu/';
                $destinationPath = public_path() . $folderName;

                if (!file_exists($destinationPath)) {
                    mkdir($destinationPath, 0755, true);
                }

                $file->move($destinationPath, $fileName);
                $upd_menu->icon = $folderName . $fileName;
            }

            // Update menu fields
            $upd_menu->sort_id = $request->sort_id;
            $upd_menu->parent_id = $request->parent_id;
            $upd_menu->url = $request->url;
            $upd_menu->status = $request->status;

            // Save the updated menu
            $upd_menu->save();


            foreach ($request->all() as $key => $value) {

                if (strpos($key, 'name_') === 0) {

                    $languageCode = substr($key, 5);

                    $languageId = DB::table('languages')->where('code', $languageCode)->value('id');

                    if ($languageId) {

                        Menu_translation::where('language_id', $languageId)
                            ->where('menu_id', $upd_menu->id)
                            ->update(['name' => $value]);
                    }
                }
            }


            $get_active_language = Language::where('status', '1')->where('is_default', '1')->first();
            $get_menu_trans_by_def_lang = Menu_translation::where('menu_id', $upd_menu->id)
                ->where('language_id', $get_active_language->id)
                ->first();

            $update_menu2 = DB::table('menus')->where('id', $upd_menu->id)->update(['name' => $get_menu_trans_by_def_lang->name]);

            return response()->json([
                'status_code' => Response::HTTP_OK,
                'message' => 'Menu updated successfully',
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            // Handle general exceptions
            return response()->json([
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $this->get_message('conflict'),
            ], Response::HTTP_INTERNAL_SERVER_ERROR); // 500 Internal Server Error
        }
    }


    public function delete_menu($uuid)
    {

        try {

            $del_menu = Menu::where('uuid', $uuid)->first();
            if (Auth::user()->hasPermission('ViewGlobal') || $del_menu->auth_id == Auth::user()->uuid) {
                // allowed to edit
            } else {
                return response()->json([
                    'status_code' => Response::HTTP_FORBIDDEN,
                    'message' => 'Invalid permission for this action.',
                ], Response::HTTP_FORBIDDEN);
            }
            
            if (!$del_menu) {

                return response()->json([

                    'status_code' => Response::HTTP_NOT_FOUND,
                    'message' => $this->get_message('not_found'),

                ], Response::HTTP_NOT_FOUND);
            } else {

                $delete_menu = Menu::destroy($del_menu->id);

                if ($delete_menu) {

                    $del_parent_menu = Menu::where('parent_id', $del_menu->id)->delete();
                    $del_menu_translation = Menu_translation::where('menu_id', $del_menu->id)->delete();
                    $del_menu_special_permission = User_special_permission::where('menu_id', $del_menu->id)->delete();
                    $del_permission_assign = Permission_assign::where('menu_id', $del_menu->id)->delete();

                    return response()->json([

                        'status_code' => Response::HTTP_OK,
                        'message' => 'Menu deleted successfully',

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

    public function get_menu(Request $request)
    {
        try {
            $menuUuid = $request->header('menu-uuid');
            $permissions = $this->permissionService->checkPermissions($menuUuid);
           
            // Get the specified menu
            $get_all_menu = Menu::orderBy('created_at', 'desc');
            if ($permissions['view']) {
                if (!$permissions['viewglobal']) {
                    $get_all_menu = $get_all_menu->where('auth_id', Auth::user()->uuid);
                }
                $get_all_menu = $get_all_menu->get();
            } else {
                if (Auth::user()->hasPermission('viewglobal')) {
                    $get_all_menu = $get_all_menu->get();
                } else {
                    return response()->json(['message' => 'Invalid permission for this action'], Response::HTTP_FORBIDDEN);
                }
            }
            return response()->json([
                'status_code' => Response::HTTP_OK,
                'permissions' => $permissions,
                'data' => $get_all_menu,
                'role_id' => Auth::user()->role_id,
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            return response()->json([
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR); // 500 Internal Server Error

        }
    }

    // public function get_ready()
    // {
    //     define('UPDATE_INFO_URL', 'https://crm.digitalgraphiks.ae/site/get_access');
    //     $dn = request()->getHost();

    //     $curl = curl_init();
    //     curl_setopt_array($curl, [
    //         CURLOPT_RETURNTRANSFER => 1,
    //         CURLOPT_SSL_VERIFYHOST => 0,
    //         // CURLOPT_USERAGENT      => $this->ci->agent->agent_string(),
    //         CURLOPT_SSL_VERIFYPEER => 0,
    //         CURLOPT_TIMEOUT        => 30,
    //         //CURLOPT_URL            => UPDATE_INFO_URL."?key=123&name=".env('THEME_CMS'),
    //         CURLOPT_URL            => UPDATE_INFO_URL . "?key=" . env('THEME_KEY') . "&name=" . $dn,
    //         CURLOPT_POST           => 0,

    //     ]);
    //     $result = curl_exec($curl);
    //     curl_close($curl);
    //     return $result;
    // }


    public function get_sort_menu()
    {
        // $result = $this->get_ready();
        // if (!empty($result)) {
        //     return response()->json([
        //         'status_code' => $result,
        //     ], Response::HTTP_INTERNAL_SERVER_ERROR);
        // }

        $rols_id = Auth::user()->role_id;
        $menuIds = [];
        $fileManagerUrls = [];
        $widgets = [];
        $modules = [];
        $formbuilder = [];
        if ($rols_id == "1") {
            // Admin has access to all menus
            $menus = Menu::where('parent_id', 0)
                ->where('status', '1')
                ->where('is_plugin_active', '1');

            $fileManagerUrls = $menus->get()->filter(function ($menu) {
                return strpos($menu->url, 'filemanager') !== false; // Adjust the condition based on your URL structure
            })->pluck('uuid');

            $widgets = $menus->get()->filter(function ($menu) {
                return strpos($menu->url, 'widgets') !== false; // Adjust the condition based on your URL structure
            })->pluck('uuid');

            $modules = $menus->get()->filter(function ($menu) {
                return strpos($menu->url, 'modules') !== false; // Adjust the condition based on your URL structure
            })->pluck('uuid');

            $formbuilder = $menus->get()->filter(function ($menu) {
                return strpos($menu->url, 'formbuilder') !== false; // Adjust the condition based on your URL structure
            })->pluck('uuid');

            $menus = $menus->where('is_visible_menu', '1')->whereNotIn('url', ['widgets', 'modules', 'formbuilder'])
            ->orderBy('sort_id')
            ->get();

            $menus = $this->addChildren($menus); // Pass menuIds for permission checking
        } else {
            // Get role and special permissions
            $roleMenuIds = Permission_assign::where('role_id', $rols_id)
            ->where('status', '1')
            ->pluck('menu_id')
            ->toArray();
            
            $specialMenuIds = User_special_permission::where('user_id', Auth::id())
                ->where('status', '1')
                ->pluck('menu_id')
                ->toArray();
            // Merge and remove duplicate menu IDs
            $menuIds = array_unique(array_merge($roleMenuIds, $specialMenuIds));
            
            // Fetch top-level menus the user has permission to access
            $menus = Menu::whereIn('id', $menuIds)
                ->where('parent_id', 0)
                ->where('status', '1');
               
            $permissionMenu = $menus->get();

            $fileManagerUrls = $permissionMenu->filter(function ($menu) {
                return strpos($menu->url, 'filemanager') !== false; // Adjust the condition based on your URL structure
            })->pluck('uuid');
            
            $widgets = $permissionMenu->filter(function ($menu) {
                return strpos($menu->url, 'widgets') !== false; // Adjust the condition based on your URL structure
            })->pluck('uuid');

            $modules = $permissionMenu->filter(function ($menu) {
                return strpos($menu->url, 'modules') !== false; // Adjust the condition based on your URL structure
            })->pluck('uuid');
           
            $formbuilder = $permissionMenu->filter(function ($menu) {
                return strpos($menu->url, 'formbuilder') !== false; // Adjust the condition based on your URL structure
            })->pluck('uuid');


            $menus = $menus->where('is_visible_menu', '1')->whereNotIn('url', ['widgets', 'modules', 'formbuilder'])
            ->orderBy('sort_id')
            ->get();

            $menus = $this->addChildren($menus, $menuIds); // Pass menuIds for permission checking
        }
        
        $theme = Theme::select('uuid', 'status', 'theme_path')->where('status', 1)->first();
        $baseurl = BussinessSetting::where('type','api_base_url')->first();
        $productbaseurl = BussinessSetting::where('type','api_base_product_url')->first();
        $baseurl = $baseurl ? $baseurl->value : '';
        $productbaseurl = $productbaseurl ? $productbaseurl->value : '';
        return response()->json([
            'status_code' => Response::HTTP_OK,
            'data' => $menus,
            'role_id' => $rols_id,
            'menuIds' => $menuIds,
            'theme_id' => $theme ? $theme->uuid : '',
            'fileManagerUrls' => isset($fileManagerUrls[0]) ? $fileManagerUrls[0] : '',
            'widgetPermission' => isset($widgets[0]) ? $widgets[0] : '',
            'modulesPermission' => isset($modules[0]) ? $modules[0] : '',
            'formbuilderPermission' => isset($formbuilder[0]) ? $formbuilder[0] : '',
            'baseurl' => $baseurl,
            'productbaseurl' => $productbaseurl,
        ], Response::HTTP_OK);
    }

    private function addChildren($menus, $menuIds = null)
    {
        foreach ($menus as $menu) {
            // Check if user is admin or apply permission filtering for child menus
            $query = Menu::where('parent_id', $menu->id)
                ->where('status', '1')
                ->where('is_visible_menu', '1')
                ->orderBy('sort_id');

            // Apply permission filter only if $menuIds is provided (i.e., for non-admins)
            if ($menuIds !== null) {
                $query->whereIn('id', $menuIds);
            }

            $children = $query->get();

            // Recursively add children
            if ($children->isNotEmpty()) {
                $menu->is_child = 1;
                $menu->children = $this->addChildren($children, $menuIds); // Recursive call with permission filter
            } else {
                $menu->is_child = 0;
                unset($menu->children);  // Remove the 'children' key if no children exist
            }
        }

        return $menus;
    }
}
