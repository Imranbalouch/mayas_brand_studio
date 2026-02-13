<?php

namespace App\Http\Controllers\API\CMS;

use App\Http\Controllers\Controller;
use App\Models\CMS\Theme;
use App\Models\CMS\Widget;
use App\Models\CMS\WidgetField;
use App\Models\CMS\WidgetTranslation;
use App\Models\Language;
use App\Models\Page;
use App\Services\PageUpdateService;
use App\Services\PermissionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use App\Traits\MessageTrait;
use Illuminate\Support\Facades\Log;

class WidgetController extends Controller
{

    use MessageTrait;
    /**
     * Display a listing of the resource.
     */
    protected $pageUpdateService;
    protected $permissionService;
    public function __construct(PageUpdateService $pageUpdateService, PermissionService $permissionService)
    {
        $this->pageUpdateService = $pageUpdateService;
        $this->permissionService = $permissionService;
    }

    public function index(Request $request)
    {

        try {
            $themeId  = $request->header('theme-id');
            $menuUuid = $request->header('widget-uuid');

            // Validate required headers
            if (!$themeId || !$menuUuid) {
                return response()->json([
                    'status_code' => Response::HTTP_BAD_REQUEST,
                    'message'     => 'Invalid theme ID or menu UUID',
                ], Response::HTTP_BAD_REQUEST);
            }

            // Check permissions
            $permissions = $this->permissionService->checkPermissions($menuUuid);

            if ($permissions instanceof \Illuminate\Http\JsonResponse) {
                return $permissions;
            }

            // Query widgets
            $widgetsQuery = Widget::where('theme_id', $themeId)->orderByDesc('created_at');

            // Apply permission restrictions
            if ($permissions['view']) {
                if (!$permissions['viewglobal']) {
                    $widgetsQuery->where('auth_id', Auth::id());
                }
            } elseif (!Auth::user()->hasPermission('viewglobal')) {
                return response()->json([
                    'status_code' => Response::HTTP_FORBIDDEN,
                    'message'     => 'Invalid permission for this action',
                ], Response::HTTP_FORBIDDEN);
            }

            $widgets = $widgetsQuery->get();

            return response()->json([
                'status_code' => Response::HTTP_OK,
                'permissions' => $permissions,
                'data'        => $widgets,
            ], Response::HTTP_OK);
        } catch (\Throwable $e) {
            Log::error("Widget Index Error: {$e->getMessage()}");

            return response()->json([
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message'     => $this->get_message('server_error'),
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
        $lang   = $request->language ?? defaultLanguages()->app_language_code;
        $langId = getLanguage($lang);
        $themeId = $request->header('theme-id');
        $theme   = Theme::findByUuid($themeId);

        if (!$theme) {
            return response()->json([
                'status_code' => Response::HTTP_NOT_FOUND,
                'message'     => $this->get_message('not_found'),
            ], Response::HTTP_NOT_FOUND);
        }

        try {
            $all_languages = all_languages();
            $shortkey = strtolower(str_replace(' ', '-', $request->name));

            // Check for duplicate name
            if (Widget::where('theme_id', $themeId)->where('shortkey', $shortkey)->exists()) {
                return response()->json([
                    'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                    'errors'      => json_encode(['name' => ['The widget name has already been taken.']]),
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            // Check for duplicate widget_type
            if ($request->filled('widget_type') && $request->widget_type !== 'Select Widget Type') {
                if (Widget::where('theme_id', $themeId)->where('widget_type', $request->widget_type)->exists()) {
                    return response()->json([
                        'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                        'errors'      => json_encode([
                            'widget_type' => ["Widget type \"{$request->widget_type}\" already exists."]
                        ]),
                    ], Response::HTTP_UNPROCESSABLE_ENTITY);
                }
            }

            // Validate request
            $validator = Validator::make($request->all(), [
                'name'           => ['required', 'regex:/^[a-zA-Z\s]+$/'],
                'field_name'     => ['required', 'array'],
                'field_name.*'   => ['required', 'regex:/^[a-zA-Z\s]+$/'],
                'html_code'      => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                    'errors'      => $validator->messages()->toJson(),
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            // Create widget
            $widget = Widget::create([
                'theme_id'    => $themeId,
                'name'        => $request->name,
                'widget_image' => $request->widget_image,
                'shortkey'    => $shortkey,
                'widget_type' => $request->widget_type,
                'html_code'   => $request->html_code,
            ]);

            // Save widget fields
            $this->storeWidgetFields($widget, $request);

            $this->updateWidgetTranslation($widget, $lang, $langId->uuid, $request, $all_languages);

            // Save Blade file
            $this->saveWidgetBladeFile($widget, $shortkey, $shortkey, $request->html_code, $lang, $all_languages);

            return response()->json([
                'status_code' => 200,
                'message'     => $this->get_message('add'),
            ], 200);
        } catch (\Throwable $e) {
            Log::error("Widget Store Error: {$e->getMessage()}");

            return response()->json([
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message'     => $this->get_message('server_error'),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
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
            $widget = Widget::with(['widget_translations' => function ($query) use ($lang) {
                $query->where('lang', $lang);
            }])->where('uuid', $id)->with('widgetFields')->first();

            if ($widget != null) {
                $widget = [
                    'uuid' => $widget->uuid,
                    'theme_id' => $widget->theme_id,
                    'name' => $widget->getTranslation('name', $lang),
                    'shortkey' => $widget->shortkey,
                    'html_code' => $widget->getTranslation('html_code', $lang),
                    'status' => $widget->status,
                    'default_data' => $widget->default_data,
                    'widget_fields' => $widget->widgetFields,
                    'widget_image' => $widget->widget_image,
                    'widget_type' => $widget->widget_type,
                ];
                return response()->json([
                    'status_code' => 200,
                    'data' => $widget
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

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $lang   = $request->language ?? defaultLanguages()->app_language_code;
        $langId = getLanguage($lang);
        $themeId = $request->header('theme-id');
        $theme   = Theme::findByUuid($themeId);

        if (!$theme) {
            return response()->json([
                'status_code' => Response::HTTP_NOT_FOUND,
                'message'     => $this->get_message('not_found'),
            ], Response::HTTP_NOT_FOUND);
        }

        $widget = Widget::where('theme_id', $themeId)->where('uuid', $id)->first();

        if (!$widget) {
            return response()->json([
                'status_code' => Response::HTTP_NOT_FOUND,
                'errors'      => $this->get_message('not_found'),
            ], Response::HTTP_NOT_FOUND);
        }

        try {
            $shortkey = strtolower(str_replace(' ', '-', $request->name));

            // Check duplicate shortkey (name)
            if (
                Widget::where('theme_id', $themeId)
                ->where('uuid', '!=', $id)
                ->where('shortkey', $shortkey)
                ->exists()
            ) {
                return response()->json([
                    'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                    'errors'      => json_encode(['name' => ['The widget name has already been taken.']]),
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            // Check duplicate widget_type
            if ($request->filled('widget_type')) {
                if (
                    Widget::where('theme_id', $themeId)
                    ->where('uuid', '!=', $id)
                    ->where('widget_type', $request->widget_type)
                    ->exists()
                ) {
                    return response()->json([
                        'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                        'errors'      => json_encode([
                            'widget_type' => ["Widget type \"{$request->widget_type}\" already exists."]
                        ]),
                    ], Response::HTTP_UNPROCESSABLE_ENTITY);
                }
            }

            // Validate input
            $validator = Validator::make($request->all(), [
                'name'           => ['required', 'regex:/^[a-zA-Z\s]+$/'],
                'field_name'     => ['required', 'array'],
                'field_name.*'   => ['required', 'regex:/^[a-zA-Z\s]+$/'],
                'html_code'      => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                    'errors'      => $validator->messages()->toJson(),
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
            $oldshortkey = $widget->shortkey;
            // Update widget data
            $data = $this->prepareUpdateData($request, $lang, $shortkey);
            $widget->update($data);

            // Refresh widget fields
            $widget->widgetFields()->delete();

            $this->storeWidgetFields($widget, $request);
            // Translation Add
            $this->updateWidgetTranslation($widget, $lang, $langId->uuid, $request);
            // Save Blade file
            $this->saveWidgetBladeFile($widget, $shortkey, $oldshortkey, $request->html_code, $lang);

            // Update related pages
            $pages = Page::where('theme_id', $widget->theme_id)->pluck('slug')->toArray();
            foreach ($pages as $slug) {
                $this->pageUpdateService->updatePage($slug, $lang);
            }

            return response()->json([
                'status_code' => Response::HTTP_OK,
                'message'     => $this->get_message('update'),
            ], Response::HTTP_OK);
        } catch (\Throwable $e) {
            Log::error("Widget Update Error: {$e->getMessage()}");

            return response()->json([
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message'     => $this->get_message('server_error'),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $widget = Widget::findByUuid($id);

            if (!$widget) {
                return response()->json([
                    'status_code' => Response::HTTP_NOT_FOUND,
                    'message'     => $this->get_message('not_found'),
                ], Response::HTTP_NOT_FOUND);
            }

            // Delete widget fields
            $widget->widgetFields()->delete();

            // Delete Blade file
            $this->deleteWidgetBladeFile($widget);
            $widget->widget_translations()->delete();
            // Delete widget
            $widget->delete();

            return response()->json([
                'status_code' => Response::HTTP_OK,
                'message'     => $this->get_message('delete'),
            ], Response::HTTP_OK);
        } catch (\Throwable $e) {
            Log::error("Widget Delete Error: {$e->getMessage()}");

            return response()->json([
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message'     => $this->get_message('server_error'),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function updateStatus(Request $request, string $id)
    {
        try {
            $widget = Widget::findByUuid($id);

            if (!$widget) {
                return $this->jsonResponse(Response::HTTP_NOT_FOUND, $this->get_message('not_found'));
            }

            $widget->status = $request->status;

            if (!$widget->save()) {
                return $this->jsonResponse(Response::HTTP_INTERNAL_SERVER_ERROR, $this->get_message('server_error'));
            }

            // Update related pages
            $pages = Page::where('theme_id', $widget->theme_id)->pluck('slug')->toArray();
            Language::where('status', 1)->get()->pluck('app_language_code')->each(function ($lang) use ($pages) {
                foreach ($pages as $slug) {
                    $this->pageUpdateService->updatePage($slug, $lang);
                }
            });

            return $this->jsonResponse(Response::HTTP_OK, $this->get_message('update'));
        } catch (\Throwable $e) {
            Log::error("Widget Status Update Error: {$e->getMessage()}");
            return $this->jsonResponse(Response::HTTP_INTERNAL_SERVER_ERROR, $this->get_message('server_error'));
        }
    }


    public function updateHeader(Request $request, string $id)
    {
        try {
            // Find widget by UUID (no need to check twice)
            $widget = Widget::findByUuid($id);

            if (!$widget) {
                return $this->jsonResponse(Response::HTTP_NOT_FOUND, $this->get_message('not_found'));
            }

            // Reset all other headers
            Widget::where('uuid', '!=', $id)->update(['default_header' => 0]);

            // Update selected widget
            $widget->default_header = $request->status;

            if (!$widget->save()) {
                return $this->jsonResponse(Response::HTTP_INTERNAL_SERVER_ERROR, $this->get_message('server_error'));
            }

            return $this->jsonResponse(Response::HTTP_OK, $this->get_message('update'));
        } catch (\Throwable $e) {
            Log::error("Widget Header Update Error: {$e->getMessage()}");
            return $this->jsonResponse(Response::HTTP_INTERNAL_SERVER_ERROR, $this->get_message('server_error'));
        }
    }

    public function getWidgetsfields(Request $request)
    {
        try {
            $widgetUuid = $request->header('widget-id');

            if (!$widgetUuid) {
                return $this->jsonResponse(Response::HTTP_BAD_REQUEST, 'Missing widget ID');
            }

            $widget = Widget::findByUuid($widgetUuid);

            if (!$widget) {
                return $this->jsonResponse(Response::HTTP_NOT_FOUND, $this->get_message('not_found'));
            }

            $widgetFields = $widget->widgetFields()->get();

            return response()->json([
                'status_code' => Response::HTTP_OK,
                'data'        => $widgetFields,
            ], Response::HTTP_OK);
        } catch (\Throwable $e) {
            Log::error("Get Widget Fields Error: {$e->getMessage()}");

            return $this->jsonResponse(Response::HTTP_INTERNAL_SERVER_ERROR, $this->get_message('server_error'));
        }
    }

    public function storeWidgetsfields(Request $request)
    {
        try {
            $widgetUuid = $request->header('widget-id');

            if (!$widgetUuid) {
                return $this->jsonResponse(Response::HTTP_BAD_REQUEST, 'Missing widget ID');
            }

            $widget = Widget::findByUuid($widgetUuid);

            if (!$widget) {
                return $this->jsonResponse(Response::HTTP_NOT_FOUND, $this->get_message('not_found'));
            }

            // Validate request
            $validator = Validator::make($request->all(), [
                'field_name'    => 'required|string|max:255',
                'field_type'    => 'required|string|max:100',
                'field_options' => 'nullable|json',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                    'errors'      => $validator->messages(),
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            // Create WidgetField
            $widgetField = WidgetField::create([
                'uuid'          => (string) Str::uuid(),
                'widget_id'     => $widget->id, // ✅ use PK instead of UUID
                'field_name'    => $request->field_name,
                'field_type'    => $request->field_type,
                'field_options' => $request->field_options,
            ]);

            return response()->json([
                'status_code' => Response::HTTP_CREATED,
                'message'     => $this->get_message('add'),
                'data'        => $widgetField,
            ], Response::HTTP_CREATED);
        } catch (\Throwable $e) {
            Log::error("Store Widget Field Error: {$e->getMessage()}");

            return $this->jsonResponse(Response::HTTP_INTERNAL_SERVER_ERROR, $this->get_message('server_error'));
        }
    }

    public function editWidgetsfields(string $id)
    {
        try {
            $widgetField = WidgetField::findByUuid($id);

            if (!$widgetField) {
                return response()->json([
                    'status_code' => Response::HTTP_NOT_FOUND,
                    'message'     => $this->get_message('not_found'),
                ], Response::HTTP_NOT_FOUND);
            }

            return response()->json([
                'status_code' => Response::HTTP_OK,
                'data'        => $widgetField,
            ], Response::HTTP_OK);
        } catch (\Throwable $e) {
            Log::error("Edit Widget Field Error: {$e->getMessage()}");

            return response()->json([
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message'     => $this->get_message('server_error'),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function updateWidgetsfields(Request $request, string $id)
    {
        try {
            $widgetField = WidgetField::findByUuid($id);

            if (!$widgetField) {
                return response()->json([
                    'status_code' => Response::HTTP_NOT_FOUND,
                    'message'     => $this->get_message('not_found'),
                ], Response::HTTP_NOT_FOUND);
            }

            $widgetField->update([
                'field_name'    => $request->field_name,
                'field_type'    => $request->field_type,
                'field_options' => $request->field_options,
            ]);

            return response()->json([
                'status_code' => Response::HTTP_OK,
                'message'     => $this->get_message('update'),
            ], Response::HTTP_OK);
        } catch (\Throwable $e) {
            Log::error("Update Widget Field Error: {$e->getMessage()}");

            return response()->json([
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message'     => $this->get_message('server_error'),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function deleteWidgetsfields(string $id)
    {
        try {
            $widgetField = WidgetField::findByUuid($id);

            if (!$widgetField) {
                return response()->json([
                    'status_code' => Response::HTTP_NOT_FOUND,
                    'message'     => $this->get_message('not_found'),
                ], Response::HTTP_NOT_FOUND);
            }

            $widgetField->delete();

            return response()->json([
                'status_code' => Response::HTTP_OK,
                'message'     => $this->get_message('delete'),
            ], Response::HTTP_OK);
        } catch (\Throwable $e) {
            Log::error("Delete Widget Field Error: {$e->getMessage()}");

            return response()->json([
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message'     => $this->get_message('server_error'),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function updateStatusWidgetsfields(Request $request, string $id)
    {
        try {
            $widgetField = WidgetField::findByUuid($id);

            if (!$widgetField) {
                return response()->json([
                    'status_code' => Response::HTTP_NOT_FOUND,
                    'message'     => $this->get_message('not_found'),
                ], Response::HTTP_NOT_FOUND);
            }

            $widgetField->status = $request->status;

            if (!$widgetField->save()) {
                return response()->json([
                    'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                    'message'     => $this->get_message('server_error'),
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            return response()->json([
                'status_code' => Response::HTTP_OK,
                'message'     => $this->get_message('update'),
            ], Response::HTTP_OK);
        } catch (\Throwable $e) {
            Log::error("Update WidgetField Status Error: {$e->getMessage()}");

            return response()->json([
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message'     => $this->get_message('server_error'),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function default_data(Request $request, string $id)
    {
        try {
            $themeId = $request->header('theme-id');
            $lang = $request->lang;
            $languuid = getAllLanguage($lang);
            $defaultLang = getConfigValue('defaul_lang');

            $widget = Widget::where('shortkey', $id)
                ->where('theme_id', $themeId)
                ->first();

            if (!$widget) {
                return response()->json([
                    'status_code' => Response::HTTP_NOT_FOUND,
                    'message'     => $this->get_message('not_found'),
                ], Response::HTTP_NOT_FOUND);
            }
            
            if ($lang === $defaultLang) {
                 $widget->default_data = $request->shortcode;
            } else{
                $translation = WidgetTranslation::firstOrNew([
                'lang' => $lang,
                'language_id' => $languuid->uuid,
                'widget_uuid' => $widget->uuid,
                ]);

                $translation->name = $widget->name;
                $translation->default_data = $request->shortcode;
                $translation->save();
            }

            if (!$widget->save()) {
                return response()->json([
                    'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                    'message'     => $this->get_message('server_error'),
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            // Update all related pages
            $pages = Page::where('theme_id', $widget->theme_id)->pluck('slug');
            foreach ($pages as $slug) {
                $this->pageUpdateService->updatePage($slug, $lang ?? $defaultLang);
            }

            return response()->json([
                'status_code' => Response::HTTP_OK,
                'message'     => $this->get_message('update'),
            ], Response::HTTP_OK);
        } catch (\Throwable $e) {
            Log::error("Default Data Update Error: {$e->getMessage()}");

            return response()->json([
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message'     => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Save widget fields from request
     */
    private function storeWidgetFields(Widget $widget, Request $request): void
    {
        if (!$request->has('field_name') || !is_array($request->field_name)) {
            return;
        }

        $fields = [];
        foreach ($request->field_name as $key => $name) {
            $fieldType = $request->field_type[$key] ?? '';

            $fieldOptions = ($fieldType === 'image')
                ? json_encode([
                    'height' => $request->field_height[$key] ?? 0,
                    'width'  => $request->field_width[$key] ?? 0,
                ])
                : json_encode($request->field_option[$key] ?? []);

            $fields[] = [
                'field_name'   => $name,
                'field_type'   => $fieldType,
                'field_options' => $fieldOptions,
                'field_id'     => $request->fieldnamelbl[$key] ?? json_encode([]),
                'is_required'  => $request->is_required[$key] ?? 0,
            ];
        }

        $widget->widgetFields()->createMany($fields);
    }

    /**
     * Save or update Blade file for widget
     */
    private function saveWidgetBladeFile(Widget $widget, string $newshortkey, string $oldshortkey, string $htmlCode, string $lang, $languages = []): void
    {

        if ($languages) {
            foreach ($languages as $language) {
                $path = $this->getModuleBladePath($widget, $newshortkey, $language->app_language_code);
                $this->putBladeFile($path, $htmlCode);
                if ($newshortkey !== $oldshortkey) {
                    $oldPath = $this->getModuleBladePath($widget, $oldshortkey, $language->app_language_code);
                    if (File::exists($oldPath)) {
                        File::delete($oldPath);
                    }
                }
            }
        } else {
            $path = $this->getModuleBladePath($widget, $newshortkey, $lang);
            $this->putBladeFile($path, $htmlCode);
            if ($newshortkey !== $oldshortkey) {
                $oldPath = $this->getModuleBladePath($widget, $oldshortkey, $lang);
                if (File::exists($oldPath)) {
                    File::delete($oldPath);
                }
            }
        }
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
     * Delete widget blade file if exists.
     */
    private function deleteWidgetBladeFile(Widget $widget): void
    {
        $all_languages = all_languages(); 
        $fileName = strtolower($widget->shortkey);
        foreach ($all_languages as $language) { 
                $filePath = base_path(
                    'resources/views/components/' .
                        str_replace(' ', '_', strtolower($widget->theme->theme_path)) .
                        '/'.$language->app_language_code.'/' . $fileName . '.blade.php'
                ); 
            if (File::exists($filePath)) {
                File::delete($filePath);
            }
        }
    }

    /**
     * Standard JSON response helper.
     */
    private function jsonResponse(int $status, string $message, array $extra = [])
    {
        return response()->json(array_merge([
            'status_code' => $status,
            'message'     => $message,
        ], $extra), $status);
    }

    /**
     * Get full blade path for a module.
     */
    private function getModuleBladePath(Widget $widget, string $fileName, string $lang): string
    {
        return base_path(
            'resources/views/components/' .
                str_replace(' ', '_', strtolower($widget->theme->theme_path)) .
                '/' . $lang . '/' . strtolower($fileName) . '.blade.php'
        );
    }


    private function updateWidgetTranslation(Widget $widget, string $lang, string $langUuid, Request $request, $languages = []): void
    {
        if ($languages) {
            foreach ($languages as $language) {
                $translation = WidgetTranslation::firstOrNew([
                    'lang' => $language->app_language_code,
                    'language_id' => $language->uuid,
                    'widget_uuid' => $widget->uuid
                ]);
                $translation->name = $request->name;
                $translation->html_code = $request->html_code;
                $translation->save();
            }
        } else {
            $translation = WidgetTranslation::firstOrNew([
                'lang' => $lang,
                'language_id' => $langUuid,
                'widget_uuid' => $widget->uuid
            ]);
            $translation->name = $request->name;
            $translation->html_code = $request->html_code;
            $translation->save();
        }
    }


    /**
     * Prepare module update data.
     */
    private function prepareUpdateData(Request $request, string $lang, string $shortkey): array
    {
        if ($lang == getConfigValue('defaul_lang')) {
            return [
                'html_code'   => $request->html_code,
            ];
        }

        return [
            'name'        => $request->name,
            'widget_image' => $request->widget_image,
            'widget_type' => $request->widget_type,
            'shortkey'    => $shortkey,
        ];
    }
}
