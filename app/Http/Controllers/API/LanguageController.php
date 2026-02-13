<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
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
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use App\Models\Language;
use App\Models\Menu;
use App\Models\Page;
use App\Models\PageTranslation;
use App\Models\Permission_assign;
use App\Services\PermissionService;
use Spatie\Activitylog\Models\Activity;
use Illuminate\Support\Str;
use App\Traits\MessageTrait;
use App\Services\ModuleTranslationService;
use App\Services\WidgetTranslationService;
use App\Services\PageTranslationService;


class LanguageController extends Controller
{

    use MessageTrait;
    protected $permissionService;
    protected $moduleTranslationService;
    protected $widgetTranslationService;
    protected $pageTranslationService;
    
    public function __construct(PermissionService $permissionService, WidgetTranslationService $widgetTranslationService, ModuleTranslationService $moduleTranslationService, PageTranslationService $pageTranslationService,)
    {
        $this->permissionService = $permissionService;
        $this->moduleTranslationService = $moduleTranslationService;
        $this->widgetTranslationService = $widgetTranslationService;
        $this->pageTranslationService = $pageTranslationService;
        
    }

    public function add_language(Request $request)
    {

        $validator = Validator::make($request->all(), [

            'name' => 'required|regex:/^[a-zA-Z0-9\s\-]+$/',
            'code' => 'required|regex:/^[a-zA-Z0-9\s\-]+$/',
            'rtl' => 'required|numeric|in:0,1',
            'flag' => '',

        ]);

        if ($validator->fails()) {

            $message = $validator->messages();

            return response()->json([

                'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'errors' => strval($validator->errors())

            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {

            $check_if_already = Language::where('name', $request->name)->where('code', $request->code)->get();

            if (count($check_if_already) > 0) {

                return response()->json([

                    'status_code' => Response::HTTP_CONFLICT,
                    'message' => $this->get_message('conflict'),

                ], Response::HTTP_CONFLICT); // 409 Conflict 


            } else {

                $language = $request->all();
                $language['uuid'] = Str::uuid();
                $language['auth_id'] = Auth::user()->uuid;
                $language['app_language_code'] = $request->app_language_code;
                if ($request->hasFile('flag')) {
                    $file = $request->file('flag');
                    $fileName = time() . '_' . $file->getClientOriginalName(); // Prepend timestamp for unique filename
                    $folderName = '/upload_files/flag/';
                    $destinationPath = public_path() . $folderName;

                    // Ensure the directory exists, if not create it
                    if (!file_exists($destinationPath)) {
                        mkdir($destinationPath, 0755, true);
                    }

                    // Move the file to the destination path
                    $file->move($destinationPath, $fileName);

                    // Update the menu's icon path
                    $language['flag'] = $folderName . $fileName;
                }

                $save_language = Language::create($language);

                if ($save_language) {
                    if ($request->is_default == 1) {
                        Language::query()->where('id', '!=', $save_language->id)->update(['is_default' => 0]);
                    }
                    if ($request->is_admin_default == 1) {
                        Language::query()->where('id', '!=', $save_language->id)->update(['is_admin_default' => 0]);
                    }

                    // dd($save_language);
                    if ($save_language && $save_language->app_language_code && $save_language->uuid) {
                        $this->moduleTranslationService->updateModuleTranslations($save_language->app_language_code, $save_language->uuid);
                        $this->widgetTranslationService->updateWidgetTranslations($save_language->app_language_code, $save_language->uuid);
                        $this->pageTranslationService->updatePageTranslations($save_language->app_language_code, $save_language->uuid);
                    }

                    return response()->json([

                        'status_code' => Response::HTTP_CREATED,
                        'message' => $this->get_message('add'),

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
            dd($e);
            return response()->json([

                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $this->get_message('server_error'),

            ], Response::HTTP_INTERNAL_SERVER_ERROR); // 500 Internal Server Error
        }
    }


    public function edit_language($uuid)
    {


        try {

            $edit_language = Language::where('uuid', $uuid)->first();
            $edit_language_translation = Language::where('uuid', $uuid)->first();

            if ($edit_language) {

                return response()->json([

                    'status_code' => Response::HTTP_OK,
                    'data' => $edit_language,

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


    public function update_language(Request $request)
    {

        $validator = Validator::make($request->all(), [

            'name' => 'required|regex:/^[a-zA-Z0-9\s\-]+$/',
            'code' => 'required|regex:/^[a-zA-Z0-9\s\-]+$/',
            'rtl' => 'required|numeric|in:0,1',
            'flag' => '',
            'is_default' => 'required|numeric|in:0,1',
            'status' => 'required|numeric|in:0,1',

        ]);

        if ($validator->fails()) {

            $message = $validator->messages();

            return response()->json([

                'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'errors' => strval($validator->errors())

            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }


        try {

            $uuid = $request->header('uuid');
            $upd_language = Language::where('uuid', $uuid)->first();

            if (!$upd_language) {
                return response()->json([
                    'status_code' => Response::HTTP_NOT_FOUND,
                    'message' => $this->get_message('not_found'),
                ], Response::HTTP_NOT_FOUND);
            }

            if ($request->hasFile('flag')) {
                $file = $request->file('flag');
                $fileName = time() . '_' . $file->getClientOriginalName(); // Prepend timestamp for unique filename
                $folderName = '/upload_files/flag/';
                $destinationPath = public_path() . $folderName;
                // Ensure the directory exists, if not create it
                if (!file_exists($destinationPath)) {
                    mkdir($destinationPath, 0755, true);
                }
                $file->move($destinationPath, $fileName);
                $upd_language->flag = $folderName . $fileName;
            }

            $upd_language->name = $request->name;
            $upd_language->code = $request->code;
            $upd_language->app_language_code = $request->app_language_code;
            $upd_language->rtl = $request->rtl;
            $upd_language->status = $request->status;

            // if ($request->is_default == 1) {
            $upd_language->is_default = $request->is_default;
            // }

            // if ($request->is_admin_default == 1) {
            $upd_language->is_admin_default = $request->is_admin_default;
            // }

            $update_language = $upd_language->save();
            if ($update_language) {
                if ($request->is_default == 1) {
                    Language::query()->where('id', '!=', $upd_language->id)->update(['is_default' => 0]);
                }
                if ($request->is_admin_default == 1) {
                    Language::query()->where('id', '!=', $upd_language->id)->update(['is_admin_default' => 0]);
                }

                return response()->json([
                    'status_code' => Response::HTTP_OK,
                    'message' => $this->get_message('update'),
                ], Response::HTTP_OK);
            } else {

                Language::where('code', 'us')->update(['is_default' => 1]);
                return response()->json([
                    'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                    'message' => $this->get_message('server_error'),
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        } catch (\Exception $e) {
            // Handle general exceptions
            return response()->json([
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $this->get_message('server_error'),
            ], Response::HTTP_INTERNAL_SERVER_ERROR); // 500 Internal Server Error
        }
    }

    public function set_default_language($uuid)
    {
        // First try to find the language
        $language = Language::where('uuid', $uuid)->first();
        if (!$language) {
            return response()->json([
                'status_code' => 404,
                'message' => 'Language not found'
            ], 404);
        }

        // First reset all languages to not default
        Language::where('is_default', true)->update(['is_default' => false]);

        // Set the selected language as default
        $language->is_default = true;
        $language->save();

        return response()->json([
            'status_code' => 200,
            'message' => 'Default language updated successfully'
        ]);
    }



    public function delete_language($uuid)
    {

        try {

            $del_language = Language::where('uuid', $uuid)->first();

            if (!$del_language) {

                return response()->json([

                    'status_code' => Response::HTTP_NOT_FOUND,
                    'message' => $this->get_message('not_found'),

                ], Response::HTTP_NOT_FOUND);
            } else {

                $check_if_is_default = Language::where('uuid', $uuid)->where('is_default', '1')->first();

                if ($check_if_is_default) {

                    return response()->json([

                        'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                        'message' => $this->get_message('can_not_delete'),

                    ], Response::HTTP_INTERNAL_SERVER_ERROR);
                } else {

                   

                    if ($uuid) {
                        $page_delete=$this->pageTranslationService->page_delete($uuid);
                         //dd($page_delete);
                        $delete_language = Language::destroy($del_language->id);
                        return response()->json([ 
                            'status_code' => Response::HTTP_OK,
                            'message' => $this->get_message('delete'),

                        ], Response::HTTP_OK);
                    }
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


    public function get_language()
    {


        try {
            $menuUuid = request()->header('menu-uuid');
            $permissions = $this->permissionService->checkPermissions($menuUuid);
            $get_all_languages = Language::orderBy('id', 'desc');
            if ($permissions['view']) {
                if (!$permissions['viewglobal']) {
                    $get_all_languages = $get_all_languages->where('auth_id', Auth::user()->uuid);
                }
            } else {
                if (Auth::user()->hasPermission('viewglobal')) {
                    $get_all_languages = $get_all_languages;
                } else {
                    return response()->json(['message' => 'You do not have permission to view this menu'], Response::HTTP_FORBIDDEN);
                }
            }

            $get_all_languages = $get_all_languages->get();

            if ($get_all_languages) {

                return response()->json([

                    'status_code' => Response::HTTP_OK,
                    'data' => $get_all_languages,
                    'permissions' => $permissions,


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



    public function get_active_languages()
    {
        try {
            $get_all_languages = Language::where('status', '1')->orderBy('name', 'asc');
            $get_all_languages = $get_all_languages->get();
            if ($get_all_languages) {
                return response()->json([
                    'status_code' => Response::HTTP_OK,
                    'data' => $get_all_languages,
                ], Response::HTTP_OK);
            }
        } catch (\Exception $e) {
            return response()->json([

                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $this->get_message('server_error'),

            ], Response::HTTP_INTERNAL_SERVER_ERROR); // 500 Internal Server Error

        }
    }


    // get Menu by parent

    public function get_sort_menu()
    {
        // Fetch all parent menus with parent_id = 0
        $menus = Menu::where('parent_id', 0)->where('status', '1')->orderBy('sort_id')->get();

        // Add children to each parent menu
        $menus = $this->addChildren($menus);

        // return response()->json($menus);

        return response()->json([

            'status_code' => Response::HTTP_OK,
            'data' => $menus,

        ], Response::HTTP_OK);
    }

    private function addChildren($menus)
    {
        foreach ($menus as $menu) {
            // Fetch children menus
            $children = Menu::where('parent_id', $menu->id)->where('status', '1')->orderBy('sort_id')->get();

            // Recursively add children
            if ($children->isNotEmpty()) {
                $menu->is_child = 1;
                $menu->children = $this->addChildren($children);
            } else {
                $menu->is_child = 0;
                unset($menu->children);  // Remove the 'children' key if no children exist
            }
        }

        return $menus;
    }


    public function updateLanguageStatus(Request $request, string $id)
    {

        $request->validate([
            'status' => 'required|in:0,1', // Ensure status is either 0 or 1
        ]);

        try {
            // Find the Language by UUID and active status
            $langage = Language::where('uuid', $id)->first();
            //dd($langage);
            if ($langage) {
                // Update the status
                $langage->status = $request->status;
                $langage->save();

                return response()->json([
                    'status_code' => 200,
                    'message' => $this->get_message('update'), // Ensure `get_message` is properly defined
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
}
