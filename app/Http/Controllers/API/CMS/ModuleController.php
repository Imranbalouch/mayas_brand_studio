<?php

namespace App\Http\Controllers\API\CMS;

use App\Models\Menu;
use App\Models\Language;
use App\Models\CMS\Theme;
use App\Models\CMS\Module;
use Illuminate\Support\Str;
use App\Traits\MessageTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Models\CMS\ModuleField;
use Illuminate\Validation\Rule;
use App\Models\CMS\ModuleTranslation;
use App\Models\Permission_assign;
use App\Models\CMS\Module_details;
use Illuminate\Support\Facades\DB;
use App\Services\PageUpdateService;
use App\Services\PermissionService;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\CMS\Module_details_translation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class ModuleController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    use MessageTrait;

    protected $pageUpdateService;
    protected $permissionService;
    public function __construct(PageUpdateService $pageUpdateService, PermissionService $permissionService)
    {
        $this->pageUpdateService = $pageUpdateService;
        $this->permissionService = $permissionService;
    }
    public function index(Request $request)
    {
        $theme_id = $request->header('theme-id');
        $menuUuid = $request->header('menu-uuid');
        $permissions = $this->permissionService->checkPermissions($menuUuid);
        try {
            $modules = Module::where('theme_id', $theme_id);
            if ($permissions['view']) {
                if (!$permissions['viewglobal']) {
                    $modules = $modules->where('auth_id', Auth::user()->uuid);
                }
            } else {
                if (Auth::user()->hasPermission('viewglobal')) {
                    $modules = $modules;
                } else {
                    return response()->json(['message' => 'You do not have permission to view this menu'], Response::HTTP_FORBIDDEN);
                }
            }
            $modules = $modules->get();
            return response()->json([
                'status_code' => 200,
                'permissions' => $permissions,
                'data' => $modules
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status_code' => 500,
                'message' => $this->get_message('server_error'),
            ], 500);
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
        $theme = Theme::findByUuid($request->header('theme-id'));
        if (!$theme) {
            return $this->jsonResponse(404, null, $this->get_message('not_found'));
        }

        try {
            $all_languages = all_languages();
            $lang   = $request->language ?? defaultLanguages()->app_language_code;
            $langId = getLanguage($lang);
            $shortkey = strtolower(str_replace(' ', '_', $request->name));

            // 🔹 Ensure unique module
            if (Module::where('theme_id', $theme->uuid)->where('shortkey', $shortkey)->exists()) {
                $message = ['name' => ['The module name has already been taken.']];
                return $this->jsonResponse(Response::HTTP_UNPROCESSABLE_ENTITY, null, json_encode($message));
            }

            // 🔹 Validation
            $rules = [
                'name'      => 'required|regex:/^[a-zA-Z0-9_ ]+$/',
                'html_code' => 'required',
            ];
            if ($request->moduletype !== 'api') {
                $rules['field_name.*'] = 'required|regex:/^[a-zA-Z0-9_ ]+$/';
            } else {
                $rules['api_url'] = 'required';
            }

            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return $this->jsonResponse(Response::HTTP_UNPROCESSABLE_ENTITY, null, (string) $validator->messages());
            }

            // 🔹 Create module
            $module = Module::create([
                'uuid'       => Str::uuid(),
                'theme_id'   => $theme->uuid,
                'name'       => $request->name,
                'moduleclass' => $request->moduleclass,
                'shortkey'   => $shortkey,
                'html_code'  => $request->html_code,
                'moduletype' => $request->moduletype,
                'api_url'    => $request->api_url ?? null,
                'auth_id'    => Auth::user()->uuid,
            ]);

            // 🔹 Sync fields
            $this->syncModuleFields($module, $request);

            // 🔹 Add translation
            $this->updateModuleTranslation($module, $lang, $langId->uuid, $request, $all_languages);

            // 🔹 Create blade file
            $this->updateModuleBlade($module, $request->html_code, $shortkey, $shortkey, $lang, $all_languages);

            return $this->jsonResponse(201, null, $this->get_message('add'));
        } catch (\Throwable $e) {
            Log::error("Module Store Error: " . $e->getMessage());
            return $this->jsonResponse(500, null, $this->get_message('server_error'));
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

    public function edit(string $id, Request $request)
    {
        //
        try {
            $lang = getConfigValue('defaul_lang');
            if ($request->has('language')) {
                $lang = $request->language;
            }
            $module = Module::with(['module_translations' => function ($query) use ($lang) {
                $query->where('lang', $lang);
            }])->where('uuid', $id)->with('moduleFields')->first();

            if (!$module) {
                return response()->json([
                    'status_code' => Response::HTTP_NOT_FOUND,
                    'message' => $this->get_message('not_found'),
                ], Response::HTTP_NOT_FOUND);
            }

            if ($module) {
                $module = [
                    'uuid' => $module->uuid,
                    'name' => $module->getTranslation('name', $lang),
                    'html_code' => $module->getTranslation('html_code', $lang),
                    'api_url' => $module->api_url,
                    'module_fields' => $module->moduleFields,
                    'module_translations' => $module->module_translations,
                    'moduleclass' => $module->moduleclass,
                    'moduletype' => $module->moduletype,
                    'shortkey' => $module->shortkey,
                    'status' => $module->status,
                    'theme_id' => $module->theme_id,
                ];

                return response()->json([
                    'status_code' => Response::HTTP_OK,
                    'data' => $module,
                ], Response::HTTP_OK);
            } else {
                return response()->json([
                    'status_code' => Response::HTTP_NOT_FOUND,
                    'message' => $this->get_message('not_found'),
                ], Response::HTTP_NOT_FOUND);
            }
        } catch (\Exception $e) {
            Log::error("Module Edit Error: " . $e->getMessage());
            return response()->json([
                'status_code' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update the specified module in storage.
     */
    public function update(Request $request, string $id)
    {
        $lang = $request->language ?? defaultLanguages()->app_language_code;
        $langId = getLanguage($lang);

        $theme_id = $request->header('theme-id');
        $theme = Theme::findByUuid($theme_id);

        if (!$theme) {
            return $this->jsonResponse(404, null, $this->get_message('not_found'));
        }

        $module = Module::where('theme_id', $theme_id)->where('uuid', $id)->first();
        if (!$module) {
            return $this->jsonResponse(404, null, $this->get_message('not_found'));
        }

        $moduleOldName = $module->shortkey;

        try {
            // 🔹 Validation
            $validator = $this->validateUpdate($request, $theme_id, $id);
            if ($validator->fails()) {
                return $this->jsonResponse(Response::HTTP_UNPROCESSABLE_ENTITY, null, strval($validator->messages()));
            }

            // 🔹 Prepare update data
            $shortkey = strtolower(str_replace(' ', '_', $request->name));
            $data = $this->prepareUpdateData($request, $lang, $shortkey);

            // 🔹 Update module & translations
            $module->update($data);
            $this->updateModuleTranslation($module, $lang, $langId->uuid, $request);

            // 🔹 Rebuild module fields
            $this->syncModuleFields($module, $request);
            $all_languages = all_languages();
            // 🔹 Handle blade file updates
            if($moduleOldName != $shortkey){
                $this->updateModuleBlade($module, $request->html_code, $shortkey, $moduleOldName, $lang,$all_languages);
            }else{
                $this->updateModuleBlade($module, $request->html_code, $shortkey, $moduleOldName, $lang);
            }

            // 🔹 If non-API, update details view
            if ($module->moduletype !== 'api') {
                $this->refreshModuleDetailsView($module, $lang);
            }

            return $this->jsonResponse(200, null, $this->get_message('update'));
        } catch (\Throwable $e) {
            Log::error("Module Update Error: " . $e->getMessage());
            return $this->jsonResponse(500, null, $this->get_message('server_error'));
        }
    }

    /**
     * Validate module update request.
     */
    private function validateUpdate(Request $request, string $theme_id, string $id)
    {
        $shortkey = strtolower(str_replace(' ', '_', $request->name));

        // Ensure unique module name
        $exists = Module::where('theme_id', $theme_id)
            ->where('uuid', '!=', $id)
            ->where('shortkey', $shortkey)
            ->exists();

        if ($exists) {
            $validator = Validator::make([], []);
            $validator->errors()->add('name', 'The module name has already been taken.');
            return $validator;
        }

        $rules = [
            'name' => 'required|regex:/^[a-zA-Z0-9_ ]+$/',
            'html_code' => 'required',
        ];

        if ($request->moduletype !== 'api') {
            $rules['field_name.*'] = 'required|regex:/^[a-zA-Z0-9_ ]+$/';
        } else {
            $rules['api_url'] = 'required';
        }

        return Validator::make($request->all(), $rules);
    }

    /**
     * Prepare module update data.
     */
    private function prepareUpdateData(Request $request, string $lang, string $shortkey): array
    {
        if ($lang == getConfigValue('defaul_lang')) {
            return [
                'name' => $request->name,
                'html_code' => $request->html_code,
                'moduleclass' => $request->moduleclass,
                'moduletype' => $request->moduletype,
                'api_url' => $request->api_url ?? null,
                'shortkey' => $shortkey,
                'lang' => $request->language,
            ];
        }

        return [
            'moduletype' => $request->moduletype,
            'api_url' => $request->api_url ?? null,
            'shortkey' => $shortkey,
            'lang' => $request->language,
        ];
    }

    /**
     * Update module translations.
     */
    private function updateModuleTranslation(Module $module, string $lang, string $langUuid, Request $request, $languages = []): void
    {
        if ($languages) {
            foreach ($languages as $language) {
                $translation = ModuleTranslation::firstOrNew([
                    'lang' => $language->app_language_code,
                    'language_id' => $language->uuid,
                    'module_id' => $module->uuid
                ]);
                $translation->name = $request->name;
                $translation->html_code = $request->html_code;
                $translation->moduleclass = $request->moduleclass;
                $translation->save();
            }
        } else {
            $translation = ModuleTranslation::firstOrNew([
                'lang' => $lang,
                'language_id' => $langUuid,
                'module_id' => $module->uuid
            ]);
            $translation->name = $request->name;
            $translation->html_code = $request->html_code;
            $translation->moduleclass = $request->moduleclass;
            $translation->save();
        }
    }

    /**
     * Sync module fields.
     */
    private function syncModuleFields(Module $module, Request $request): void
    {
        $fields = [];
        if ($request->moduletype !== 'api' && $request->has('field_name') && is_array($request->field_name)) {
            foreach ($request->field_name as $key => $value) {
                $field_options = $request->field_type[$key] === 'image'
                    ? json_encode([
                        'height' => $request->field_height[$key] ?? 0,
                        'width' => $request->field_width[$key] ?? 0,
                    ])
                    : json_encode($request->field_option[$key] ?? []);

                $fields[] = [
                    'field_name' => $request->field_name[$key] ?? '',
                    'field_type' => $request->field_type[$key] ?? '',
                    'field_options' => $field_options,
                    'field_id' => $request->fieldnamelbl[$key] ?? json_encode([]),
                    'is_required' => $request->is_required[$key] ?? 0,
                ];
            }
        }

        $module->moduleFields()->delete();
        if ($fields) {
            $module->moduleFields()->createMany($fields);
        }
    }

    /**
     * Update module blade view.
     */
    private function updateModuleBlade(Module $module, string $html, string $newName, string $oldName, string $lang, $languages = []): void
    {
        if ($languages) {
            foreach ($languages as $language) {
                $path = $this->getModuleBladePath($module, $newName, $language->app_language_code);
                $this->putBladeFile($path, $html);
                if ($newName !== $oldName) {
                    $oldPath = $this->getModuleBladePath($module, $oldName, $language->app_language_code);
                    if (File::exists($oldPath)) {
                        File::delete($oldPath);
                    }
                }
            }
        } else {
            $path = $this->getModuleBladePath($module, $newName, $lang);
            $this->putBladeFile($path, $html);
            if ($newName !== $oldName) {
                $oldPath = $this->getModuleBladePath($module, $oldName, $lang);
                if (File::exists($oldPath)) {
                    File::delete($oldPath);
                }
            }
        }
    }

    /**
     * Refresh module details view if needed.
     */
    private function refreshModuleDetailsView(Module $module, string $lang): void
    {
        $details = Module_details::where('module_id', $module->uuid)->get();
        if ($details->count()) {
            $combined = $details->implode('');
            $path = $this->getModuleBladePath($module, $module->shortkey, $lang);
            $this->putBladeFile($path, $combined);
        }
    }

    /**
     * Get full blade path for a module.
     */
    private function getModuleBladePath(Module $module, string $fileName, string $lang): string
    {
        return base_path(
            'resources/views/components/' .
                str_replace(' ', '_', strtolower($module->theme->theme_path)) .
                '/modules/' . $lang . '/' . strtolower($fileName) . '.blade.php'
        );
    }

    /**
     * Write blade file to disk.
     */
    private function putBladeFile(string $path, string $content): void
    {
        if (!File::exists(dirname($path))) {
            File::makeDirectory(dirname($path), 0755, true, true);
        }
        File::put($path, $content);
    }


    /**
     * Remove the specified module from storage.
     */
    public function destroy(string $id)
    {
        try {
            $module = Module::findByUuid($id);

            if (!$module) {
                return $this->jsonResponse(404, null, $this->get_message('not_found'));
            }

            // Delete related fields & translations
            $module->moduleFields()->delete();
            // Delete module view file
            $this->deleteModuleViewFile($module);

             
            $module->module_translations()->delete();

            // Finally delete module
            $module->delete();

            return $this->jsonResponse(200, null, $this->get_message('delete'));
        } catch (\Throwable $e) {
            Log::error('Delete Module Error: ' . $e->getMessage());
            return $this->jsonResponse(500, null, $this->get_message('server_error'));
        }
    }

    /**
     * Delete the blade view file of the given module if exists.
     */
    private function deleteModuleViewFile(Module $module): void
    {
         $all_languages = all_languages(); 
        foreach ($all_languages as $language) {
            $langCode = $language->app_language_code;

            $fileName = strtolower($module->shortkey) . '.blade.php';
            $filePath = base_path(
                'resources/views/components/' .
                    str_replace(' ', '_', strtolower($module->theme->theme_path)) .
                    '/modules/' . $langCode . '/' . $fileName
            );

            if (File::exists($filePath)) {
                File::delete($filePath);
            }
        }
    }

    /**
     * Update the specified resource in storage.
     */

    public function updateStatus(Request $request, string $id)
    {
        try {
            $module = Module::findByUuid($id);
            if ($module) {
                $module->status = $request->status;
                if ($module->save()) {
                    return response()->json([
                        'status_code' => 200,
                        'message' => $this->get_message('update'),
                    ], 200);
                } else {
                    return response()->json([
                        'status_code' => 500,
                        'message' => $this->get_message('server_error'),
                    ], 500);
                }
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



    public function getModuleById(string $id)
    {
        try {
            $module = Module::where('uuid', $id)
                ->with([
                    'moduleFields:uuid,module_id,field_name,field_id,field_type',
                    'moduleDetails'
                ])
                ->first();

            if (!$module) {
                return $this->jsonResponse(404, null, $this->get_message('not_found'));
            }

            $menuUuid = request()->header('menu-uuid');
            $roleId   = Auth::user()->role_id;
            $menu     = Menu::where('uuid', $menuUuid)->first();

            // Permissions
            $permissions = $this->getPermissions($roleId, $menu?->id);

            return $this->jsonResponse(200, [
                'new'    => $permissions['add'],
                'edit'   => $permissions['edit'],
                'update' => $permissions['update'],
                'delete' => $permissions['delete'],
                'view'   => $permissions['view'],
                'module' => $module,
            ]);
        } catch (\Exception $e) {
            return $this->jsonResponse(500, null, $this->get_message('server_error'));
        }
    }

    public function getModulesfields(Request $request)
    {
        $module_id = $request->header('module-id');
        $module = Module::findByUuid($module_id);
        if ($module == null) {
            return response()->json([
                'status_code' => 404,
                'message' => $this->get_message('not_found'),
            ], 404);
        }
        try {
            $modulefields = ModuleField::where('module_id', $module_id)->get();
            return response()->json([
                'status_code' => 200,
                'data' => $modulefields
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status_code' => 500,
                'message' => $this->get_message('server_error'),
            ], 500);
        }
    }

    public function storeModulesfields(Request $request)
    {
        $module_id = $request->header('module-id');
        $module = Module::findByUuid($module_id);
        if ($module == null) {
            return response()->json([
                'status_code' => 404,
                'message' => $this->get_message('not_found'),
            ], 404);
        }
        try {
            $data = [];
            $uuid = Str::uuid();
            $data = [
                'uuid' => $uuid,
                'module_id' => $module_id,
                'field_name' => $request->field_name,
                'field_type' => $request->field_type,
                'field_options' => $request->field_options,
            ];
            $modulefields = ModuleField::create($data);

            return response()->json([
                'status_code' => 200,
                'data' => $this->get_message('add'),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status_code' => 500,
                'message' => $this->get_message('server_error'),
            ], 500);
        }
    }

    public function editModulesfields(string $id)
    {

        try {
            $moduleField = ModuleField::findByUuid($id);
            if ($moduleField != null) {
                return response()->json([
                    'status_code' => 200,
                    'data' => $moduleField
                ], 200);
            } else {
                return response()->json([
                    'status_code' => 404,
                    'message' => $this->get_message('not_found'),
                ], 404);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status_code' => 500,
                'message' => $this->get_message('server_error'),
            ], 500);
        }
    }

    public function updateModulesfields(Request $request, string $id)
    {
        //
        try {
            $moduleField = ModuleField::findByUuid($id);
            if ($moduleField != null) {
                $data = [
                    'field_name' => $request->field_name,
                    'field_type' => $request->field_type,
                    'field_options' => $request->field_options,
                ];
                $moduleField->update($data);
                return response()->json([
                    'status_code' => 200,
                    'data' => $this->get_message('update'),
                ], 200);
            } else {
                return response()->json([
                    'status_code' => 404,
                    'message' => $this->get_message('not_found'),
                ], 404);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status_code' => 500,
                'message' => $this->get_message('server_error'),
            ], 500);
        }
    }


    public function deleteModulesfields(Request $request, string $id)
    {
        //
        try {
            $moduleField = ModuleField::findByUuid($id);
            if ($moduleField) {
                $moduleField->delete();
                return response()->json([
                    'status_code' => 200,
                    'message' => $this->get_message('delete'),
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


    public function updateStatusModulesfields(Request $request, string $id)
    {
        try {
            $moduleField = ModuleField::findByUuid($id);
            if ($moduleField) {
                $moduleField->status = $request->status;
                if ($moduleField->save()) {
                    return response()->json([
                        'status_code' => 200,
                        'message' => $this->get_message('update'),
                    ], 200);
                } else {
                    return response()->json([
                        'status_code' => 500,
                        'message' => $this->get_message('server_error'),
                    ], 500);
                }
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


    public function addModulesdetails(Request $request)
    {
        try {
            $moduleUuid = $request->header('module-uuid');
            $module     = Module::findByUuid($moduleUuid);

            if (!$module) {
                return $this->jsonResponse(404, null, $this->get_message('not_found'));
            }

            // Validate input
            $validator = Validator::make($request->all(), [
                'details' => 'required',
                'view'    => 'required',
                'status'  => 'required',
            ]);

            if ($validator->fails()) {
                return $this->jsonResponse(Response::HTTP_UNPROCESSABLE_ENTITY, null, $validator->messages()->toJson());
            }

            // Prepare details
            $detailsArr   = json_decode($request->details, true);
            $lang         = $detailsArr['language'] ?? $request->language ?? 'en';
            unset($detailsArr['language']);

            $detailsJson  = json_encode($detailsArr);
            $langId       = getLanguage($lang);

            // Create module detail
            $moduleDetail = Module_details::create([
                'uuid'      => Str::uuid(),
                'module_id' => $moduleUuid,
                'details'   => $detailsJson,
                'view'      => $request->view,
                'status'    => $request->status,
                'auth_id'   => Auth::user()->uuid,
            ]);

            // Create translation
            Module_details_translation::updateOrCreate(
                [
                    'lang'             => $lang,
                    'language_id'      => $langId->uuid,
                    'module_detail_id' => $moduleDetail->uuid,
                ],
                [
                    'details' => $detailsJson,
                    'view'    => $request->view,
                ]
            );

            // Update module blade file
            $this->updateModuleViewFile($module, $moduleUuid, $lang);

            // Refresh pages
            $this->updateThemePages($lang);

            return $this->jsonResponse(200, null, $this->get_message('add'));
        } catch (\Exception $e) {
            Log::error('Add Module Details Error: ' . $e->getMessage());
            dd($e);
            return $this->jsonResponse(500, null, $this->get_message('server_error'));
        }
    }


    public function editModulesdetails(string $id, Request $request)
    {
        try {
            $lang        = $request->get('language') ?? getConfigValue('defaul_lang');
            $moduleUuid  = $request->header('module-uuid');

            $module      = Module::where('uuid', $moduleUuid)->first();
            $moduleDetail = Module_details::where('uuid', $id)->first();
            // $moduleDetailTranslation = $moduleDetail->moduleDetailsTranslation->first();

            if (!$moduleDetail) {
                return $this->jsonResponse(404, null, $this->get_message('not_found'));
            }

            $data = [
                'uuid'        => $moduleDetail->uuid,
                'module_id'   => $moduleDetail->module_id,
                'status'      => $moduleDetail->status,
                'created_at'  => $moduleDetail->created_at,
                'updated_at'  => $moduleDetail->updated_at,
                'view'        => $moduleDetail->view,
                'details'     => $moduleDetail->getTranslation("details", $lang),
                'module_html' => $module->getTranslation("html_code", $lang), // override with module html
                'lang'        => $lang
            ];

            return $this->jsonResponse(200, $data);
        } catch (\Exception $e) {
            return $this->jsonResponse(500, null, $this->get_message('server_error'));
        }
    }

    /**
     * Standardized JSON response.
     */
    private function jsonResponse(int $statusCode, $data = null, string $message = null)
    {
        $response = ['status_code' => $statusCode];

        if ($data !== null) {
            $response['data'] = $data;
        }
        if ($message !== null) {
            $response['message'] = $message;
        }

        return response()->json($response, $statusCode);
    }


    public function updateModulesdetails(Request $request)
    {
        try {
            $detailsData = json_decode($request->details, true) ?? [];
            $lang        = $detailsData['language'] ?? 'en';
            unset($detailsData['language']);

            $detailsJson = json_encode($detailsData);
            $langId      = getLanguage($lang);
            $uuid        = $request->header('uuid');
            $moduleUuid  = $request->header('module-uuid');

            $module      = Module::where('uuid', $moduleUuid)->first();
            $moduleDetail = Module_details::where('uuid', $uuid)->first();

            if (!$moduleDetail) {
                return response()->json([
                    'status_code' => 404,
                    'message'     => $this->get_message('not_found'),
                ], 404);
            }

            // Update base module details
            $this->updateModuleDetail($moduleDetail, $detailsJson, $request, $lang);

            // Update translations
            $this->updateModuleDetailsTranslation($moduleDetail, $detailsJson, $request, $lang, $langId);

            // Rebuild blade file for this module and language
            $this->updateModuleViewFile($module, $moduleUuid, $lang);

            // Update theme pages
            $this->updateThemePages($lang);

            return response()->json([
                'status_code' => 200,
                'message'     => $this->get_message('update'),
            ], 200);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return response()->json([
                'status_code' => 500,
                'message'     => $this->get_message('server_error'),
            ], 500);
        }
    }

    /**
     * Update base module detail.
     */
    private function updateModuleDetail(Module_details $moduleDetail, string $detailsJson, Request $request, string $lang): void
    {
        if ($lang === getConfigValue('defaul_lang')) {
            $moduleDetail->details = $detailsJson;
            $moduleDetail->view    = $request->view;
        }

        $moduleDetail->status = $request->status;
        $moduleDetail->save();
    }

    /**
     * Update or create module translation.
     */
    private function updateModuleDetailsTranslation(Module_details $moduleDetail, string $detailsJson, Request $request, string $lang, $langId): void
    {
        $translation = Module_details_translation::firstOrNew([
            'lang'            => $lang,
            'language_id'     => $langId->uuid,
            'module_detail_id' => $moduleDetail->uuid,
        ]);

        $translation->details = $detailsJson;
        $translation->view    = $request->view;
        $translation->save();
    }

    /**
     * Generate or update blade file for module view.
     */
    private function updateModuleViewFile(Module $module, string $moduleUuid, string $lang): void
    {
        $views = Module_details::where('module_id', $moduleUuid)
            ->with(['moduleDetailsTranslation' => fn($q) => $q->where('lang', $lang)])
            ->get()
            ->pluck('moduleDetailsTranslation.*.view')
            ->flatten()
            ->implode('');

        $fileName = $module->shortkey . '.blade.php';
        $filePath = base_path('resources/views/components/' .
            str_replace(' ', '_', strtolower($module->theme->theme_path)) .
            '/modules/' . $lang . '/' . $fileName);

        $this->createOrUpdateFile($filePath, $views);
    }

    public function deleteModulesdetails(string $id, Request $request)
    {
        try {
            $moduleDetails = Module_details::where('uuid', $id)->first();
            $moduleUuid    = request()->header('module-uuid');
            $module        = Module::where('uuid', $moduleUuid)->first();
            $lang = $request->get('language') ?? getConfigValue('defaul_lang');

            if (!$moduleDetails) {
                return response()->json([
                    'status_code' => 404,
                    'message'     => $this->get_message('not_found'),
                ], 404);
            }

            // Delete module detail and translations
             if ($lang !== getConfigValue('defaul_lang')) {
                $translation = $moduleDetails->moduleDetailsTranslation()
                    ->where('lang', $lang)
                    ->first();

                if ($translation) {
                    $translation->delete();
                }

            } else{
                $moduleDetails->delete();
                $moduleDetails->moduleDetailsTranslation()->delete();
            }
            $this->updateModuleViews($module, $moduleUuid);

            // Update theme pages
            Language::where('status', 1)->get()->pluck('app_language_code')->each(function ($lang) {
                $this->updateThemePages($lang);
            });

            return response()->json([
                'status_code' => 200,
                'message'     => $this->get_message('delete'),
            ], 200);
        } catch (\Throwable $e) {
            Log::error('Delete Module Details Error: ' . $e->getMessage());
            return response()->json([
                'status_code' => 500,
                'message'     => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update blade files for each language after module deletion.
     */
    private function updateModuleViews(Module $module, string $moduleUuid): void
    {
        foreach (languages() as $language) {
            $langCode = $language->app_language_code;

            $views = Module_details::where('module_id', $moduleUuid)
                ->with(['moduleDetailsTranslation' => function ($q) use ($langCode) {
                    $q->where('lang', $langCode);
                }])
                ->get()
                ->pluck('moduleDetailsTranslation.*.view')
                ->flatten()
                ->implode('');

            $fileName = $module->shortkey . '.blade.php';
            $filePath = base_path('resources/views/components/' .
                str_replace(' ', '_', strtolower($module->theme->theme_path)) .
                '/modules/' . $langCode . '/' . $fileName);

            $this->createOrUpdateFile($filePath, $views);
        }
    }

    /**
     * Create or update file with given content.
     */
    private function createOrUpdateFile(string $filePath, string $content): void
    {
        if (!File::exists(dirname($filePath))) {
            File::makeDirectory(dirname($filePath), 0755, true);
        }
        File::put($filePath, $content);
    }

    /**
     * Update all theme pages after module update.
     */
    private function updateThemePages($lang = null): void
    {
        try {
            $themeSlugs = Theme::where('status', 1)
                ->with('pages')
                ->first()
                ->pages
                ->pluck('slug')
                ->toArray();

            foreach ($themeSlugs as $slug) {
                $this->pageUpdateService->updatePage($slug, $lang);
            }
        } catch (\Exception $e) {
            dd($e);
            throw new \RuntimeException($e->getMessage());
        }
    }

    public function viewModulesdetails(string $uuid, Request $request)
    {
        try {
            $lang = $request->get('language') ?? getConfigValue('defaul_lang');
            $moduleUuid = request()->header('module-uuid');
            $menuUuid   = request()->header('menu-uuid');
            $roleId     = Auth::user()->role_id;

            $module     = Module::where('uuid', $moduleUuid)->first();
            $menu       = Menu::where('uuid', $menuUuid)->first();
            if ($module === null) {
                return $this->jsonResponse(404, null, $this->get_message('not_found'));
            }
            $moduleDetails = Module_details::where('module_id', $uuid)
                ->whereHas('moduleDetailsTranslation', function ($query) use ($lang) {
                    $query->where('lang', $lang);
                })
                ->select('id', 'uuid', 'details', 'module_id')
                ->orderByDesc('id')
                ->get()
                ->map(function ($item) use ($lang) {
                    $item->details = $item->getTranslation('details', $lang);
                    return $item;
                });
            
            if ($moduleDetails->isEmpty() && $lang !== getConfigValue('defaul_lang')) {
                $defaultLang = getConfigValue('defaul_lang');
                $moduleDetails = Module_details::where('module_id', $uuid)
                    ->whereHas('moduleDetailsTranslation', function ($query) use ($defaultLang) {
                        $query->where('lang', $defaultLang);
                    })
                    ->select('id', 'uuid', 'details', 'module_id')
                    ->orderByDesc('id')
                    ->get()
                    ->map(function ($item) use ($defaultLang) {
                        $item->details = $item->getTranslation('details', $defaultLang);
                        return $item;
                    });
            }

            // Permissions
            $permissions = $this->getPermissions($roleId, $menu?->id);

            return $this->jsonResponse(200, [
                'new'         => $permissions['add'],
                'edit'        => $permissions['edit'],
                'update'      => $permissions['update'],
                'delete'      => $permissions['delete'],
                'view'        => $permissions['view'],
                'modules'     => $moduleDetails,
                'module_name' => $module->getTranslation('name', $lang), // Use translation for module name
                'module_html' => $module->getTranslation('html_code', $lang),
            ]);
        } catch (\Exception $e) {
            Log::error("ModuleController::viewModulesdetails: " . $e->getMessage());
            return $this->jsonResponse(500, null, $this->get_message('server_error'));
        }
    }

    /**
     * Get permissions for given role and menu.
     */
    private function getPermissions(int|string $roleId, ?int $menuId): array
    {
        // Super admin gets full access
        if ($roleId == 1) {
            return ['add' => 1, 'edit' => 1, 'update' => 1, 'delete' => 1, 'view' => 1];
        }

        $permissions = ['add' => 0, 'edit' => 0, 'update' => 0, 'delete' => 0, 'view' => 0];

        if ($menuId) {
            $assigned = Permission_assign::where('role_id', $roleId)
                ->where('menu_id', $menuId)
                ->pluck('permission_id')
                ->toArray();

            foreach ($assigned as $permissionId) {
                switch ($permissionId) {
                    case 1:
                        $permissions['add']    = 1;
                        break;
                    case 2:
                        $permissions['edit']   = 1;
                        break;
                    case 3:
                        $permissions['update'] = 1;
                        break;
                    case 4:
                        $permissions['delete'] = 1;
                        break;
                    case 5:
                        $permissions['view']   = 1;
                        break;
                }
            }
        }

        return $permissions;
    }

    public function updateModulesdetailsStatus(Request $request, string $id)
    {
        try {
            $module = Module_details::findByUuid($id);
            if ($module) {
                $module->status = $request->status;
                if ($module->save()) {
                    return response()->json([
                        'status_code' => 200,
                        'message' => $this->get_message('update'),
                    ], 200);
                } else {
                    return response()->json([
                        'status_code' => 500,
                        'message' => $this->get_message('server_error'),
                    ], 500);
                }
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
