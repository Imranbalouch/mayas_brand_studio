<?php

namespace App\Http\Controllers;

use Mail;
use App\Models\Page;
use App\Models\CMS\Theme;
use App\Models\OtherMenu;
use App\Mail\EmailManager;
use App\Models\CMS\Module;
use App\Models\CMS\Widget;
use Illuminate\Support\Str;
use App\Traits\MessageTrait;
use Illuminate\Http\Request;
use App\Models\CMS\ContactUs;
use App\Models\CMS\DynamicForm;
use App\Models\CMS\ModuleField;
use App\Models\PageTranslation;
use Illuminate\Validation\Rule;
use App\Models\CMS\Module_details;
use App\Services\ShortcodeService;
use Illuminate\Support\Facades\DB;
use App\Services\PageUpdateService;
use App\Services\PermissionService;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use App\Models\CMS\WidgetTranslation;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class PageController extends Controller
{

    use MessageTrait;

    protected $shortcodeService;
    protected $pageUpdateService;
    protected $permissionService;

    public function __construct(ShortcodeService $shortcodeService, PageUpdateService $pageUpdateService, PermissionService $permissionService)
    {
        $this->shortcodeService = $shortcodeService;
        $this->pageUpdateService = $pageUpdateService;
        $this->permissionService = $permissionService;
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $menuUuid   = $request->header('menu-uuid');
            $themeId    = $request->header('theme_id');
            $user       = Auth::user();

            $permissions = $this->permissionService->checkPermissions($menuUuid);

            // Deny access early if no view permission
            if (!$permissions['view'] && !$user->hasPermission('viewglobal')) {
                return response()->json([
                    'message' => 'Invalid permission for this action'
                ], Response::HTTP_FORBIDDEN);
            }

            $pagesQuery = Page::has('theme')
                ->select('uuid', 'title', 'slug', 'status', 'default_page')
                ->where('theme_id', $themeId);

            // Restrict to own pages if "view" but not "viewglobal"
            if ($permissions['view'] && !$permissions['viewglobal']) {
                $pagesQuery->where('auth_id', $user->uuid);
            }

            $pages = $pagesQuery->get();

            return response()->json([
                'status_code' => Response::HTTP_OK,
                'permissions' => $permissions,
                'data'        => $pages,
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message'     => $e->getMessage()
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
        try {
            $all_languages = all_languages();
            $themeId = $request->header('theme-id');

            // Validation rules
            $rules = [
                'title' => [
                    'required',
                    'regex:/^[a-zA-Z0-9_ ]+$/',
                    'min:3',
                    'max:30'
                ],
                'slug' => [
                    'required',
                    'min:3',
                    'max:30',
                    Rule::unique('pages')->where(fn($query) => $query->where('theme_id', $themeId)),
                ],
                'meta_title' => 'nullable|min:3',
                'meta_description' => 'nullable|min:3',
            ];

            // Custom messages
            $messages = [
                'title.required' => 'The name field is required.',
                'slug.unique'    => 'The page slug has already been taken.',
            ];

            // Validate request
            $validator = Validator::make($request->all(), $rules, $messages);
            if ($validator->fails()) {
                return response()->json([
                    'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                    'errors'      => $validator->errors(),
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
            $lang   = $request->language ?? defaultLanguages()->app_language_code;
            $langId = getLanguage($lang);
            // Prepare data
            $data = $request->only([
                'title',
                'meta_title',
                'meta_description',
                'og_title',
                'og_description',
                'og_image',
                'x_title',
                'x_description',
                'x_image',
                'custom_css',
                'custom_js',
                'page_type',
            ]);

            $data['theme_id']        = $themeId;
            $data['slug']            = Str::slug($request->title);
            $data['description']     = $request->content;
            $data['default_header']  = (int) ($request->default_header ?? 0);
            $data['default_footer']  = (int) ($request->default_footer ?? 0);
            DB::beginTransaction();
            // Create page
            $page = Page::create($data);

            //dd($all_languages);
            $this->updatePageTranslation($page, $lang, $langId->uuid, $request, $all_languages);
            DB::commit();
            // Update page metadata (if needed)
            //dd($all_languages);
            $this->show($page->slug, $lang, null, $all_languages);

            return response()->json([
                'status_code' => 200,
                'message'     => 'Page added successfully',
            ], 200);
        } catch (\Exception $e) {
            Log::error($e);
            DB::rollBack();
            return response()->json([
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message'     => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $slug, $lang, $oldSlug = null, $languages = [])
    {
        if ($languages) {
            foreach ($languages as $language) {
                $this->pageUpdateService->updatePage($slug, $language->app_language_code, $oldSlug);
            }
        } else {
            $this->pageUpdateService->updatePage($slug, $lang, $oldSlug);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id, Request $request)
    {
        try {
            $lang = getConfigValue('defaul_lang');
            if ($request->has('language')) {
                $lang = $request->language;
            }
            $page = Page::findByUuid($id);

            if (!$page) {
                return response()->json([
                    'status_code' => Response::HTTP_NOT_FOUND,
                    'message'     => 'Page not found',
                ], Response::HTTP_NOT_FOUND);
            }

            $page = [
                'uuid' => $page->uuid,
                'title' => $page->title,
                'theme_id' => $page->theme_id,
                'custom_css' => $page->custom_css,
                'slug' => $page->slug,
                'custom_js' => $page->custom_js,
                'default_header' => $page->default_header,
                'default_footer' => $page->default_footer,
                'description' => $page->getTranslation('description', $lang),
                'meta_title' => $page->getTranslation('meta_title', $lang),
                'meta_description' => $page->getTranslation('meta_description', $lang),
                'og_description' => $page->og_description,
                'og_image' => $page->og_image,
                'og_title' => $page->og_title,
                'x_description' => $page->x_description,
                'x_image' => $page->x_image,
                'x_title' => $page->x_title,
                'page_type' => $page->page_type,
                'product_detail' => $page->product_detail,
                'status' => $page->status,
            ];

            return response()->json([
                'status_code' => Response::HTTP_OK,
                'data'        => $page,
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message'     => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $page = Page::findByUuid($id);
        $pageSlugOld = $page->slug;
        if ($page == null) {
            return response()->json([
                'status_code' => 404,
                'errors' => "Not found"
            ], 404);
        }
        try {
            $validator = Validator::make($request->all(), [
                'title' => 'required|min:3|max:30|regex:/^[a-zA-Z0-9_ ]+$/',
                'slug' => [
                    'required',
                    'min:3',
                    'max:30',
                    Rule::unique('pages')->where(function ($query) use ($request, $pageSlugOld) {
                        return $query->where('theme_id', request()->header('theme-id'))->where('slug', '<>', $pageSlugOld);
                    }),
                ],
                'meta_title' => [
                    'nullable',
                    'min:3',
                ],
                'meta_description' => [
                    'nullable',
                    'min:3',
                ],
            ], [
                'title.required' => 'The name field is required.',
                'slug.unique' => 'The page slug has already been taken..',
            ]);
            if ($validator->fails()) {

                $message = $validator->messages();

                return response()->json([
                    'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                    'errors' => strval($message)

                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
            $lang   = $request->language ?? defaultLanguages()->app_language_code;
            $langId = getLanguage($lang);
            $data = [
                'title' => $request->title,
                'slug' => Str::slug($request->slug),
                'custom_css' => $request->custom_css,
                'custom_js' => $request->custom_js,
                'default_header' => (int) ($request->default_header ?? 0),
                'default_footer' => (int) ($request->default_footer ?? 0),
                'product_detail' => (int) ($request->product_detail ?? 0),
                'page_type' => $request->page_type,
            ];
            if ($lang == defaultLanguages()->app_language_code) {
                $data = array_merge($data, [
                    'description' => $request->content,
                    'meta_title' => $request->meta_title,
                    'meta_description' => $request->meta_description,
                ]);
            }
            // dd($data);
            $page->update($data);
            if ($pageSlugOld == $page->slug) {
                $this->updatePageTranslation($page, $lang, $langId->uuid, $request);
                DB::commit();
                // Update page metadata (if needed)
                $this->show($page->slug, $lang);
            } else {
                $this->updatePageTranslation($page, $lang, $langId->uuid, $request);
                DB::commit();
                // Update page metadata (if needed)
                $this->show($page->slug, $lang, $pageSlugOld);
            }
            return response()->json([
                'status_code' => 200,
                'message' => 'Page updated successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status_code' => 500,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    private function updatePageTranslation(Page $page, string $lang, string $langUuid, Request $request, $languages = []): void
    {
        if ($languages) {
            foreach ($languages as $language) {
                $translation = PageTranslation::firstOrNew([
                    'lang' => $language->app_language_code,
                    'language_id' => $language->uuid,
                    'page_uuid' => $page->uuid
                ]);
                $translation->description = $request->content;
                $translation->meta_title = $request->meta_title;
                $translation->meta_description = $request->meta_description;
                $translation->save();
            }
        } else {
            $translation = PageTranslation::firstOrNew([
                'lang' => $lang,
                'language_id' => $langUuid,
                'page_uuid' => $page->uuid
            ]);
            $translation->description = $request->content;
            $translation->meta_title = $request->meta_title;
            $translation->meta_description = $request->meta_description;
            $translation->save();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $page = Page::has('theme')->where('uuid', $id)->first();
            if ($page) {
                $all_languages = all_languages();
                $is_default_lang=getConfigValue('defaul_lang');
                foreach ($all_languages as $language) {
                    if($is_default_lang==$language->app_language_code){
                        $filePath = getConfigValue('Frontend_Dir') . $page->theme->theme_path . '/' . $page->slug . '.html';
                        if (File::exists($filePath)) {
                            File::delete($filePath);
                        }
                    }else{
                        $filePath = getConfigValue('Frontend_Dir') . $page->theme->theme_path . '/'.$language->app_language_code.'/' . $page->slug . '.html';
                        if (File::exists($filePath)) {
                            File::delete($filePath);
                        }
                    }
                    
                }
                $page->page_translations()->delete();
                $page->delete();
                return response()->json([
                    'status_code' => 200,
                    'message' => 'Page deleted successfully'
                ], 200);
            } else {
                return response()->json([
                    'status_code' => 404,
                    'message' => 'Page not found'
                ], 404);
            }
        } catch (\Throwable $e) {
            return response()->json([
                'status_code' => 500,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function updateStatus(Request $request, string $id)
    {
        try {
            $page = Page::findByUuid($id);
            $lang = getConfigValue('defaul_lang');
            if ($page) {
                $page->status = $request->status;
                if ($page->save()) {
                    $filePath = getConfigValue('Frontend_Dir') . $page->theme->theme_path . '/' . $page->slug . '.html';
                    if ($page->status == 0) {
                        if (File::exists($filePath)) {
                            File::delete($filePath);
                        }
                    }
                    if ($page->status == 1) {
                        $this->pageUpdateService->updatePage($page->slug, $lang, $page->slug);
                    }
                    return response()->json([
                        'status_code' => 200,
                        'message' => 'Page status updated successfully'
                    ], 200);
                } else {
                    return response()->json([
                        'status_code' => 500,
                        'message' => 'Internal Server Error'
                    ], 500);
                }
            } else {
                return response()->json([
                    'status_code' => 404,
                    'message' => 'Page not found'
                ], 404);
            }
        } catch (\Throwable $e) {
            return response()->json([
                'status_code' => 500,
                'message' => $e->getMessage()
            ], 500);
        }
    }



    /**
     * Update the specified resource in storage.
     */
    public function updateDefault(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'default' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            $message = $validator->messages();
            return response()->json([
                'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'errors' => strval($message)
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $page = Page::where('uuid', $id)->where('default_page', 1)->first();
            if ($page) {
                Page::where('uuid', '!=', $id)->whereHas('theme', function ($query) {
                    $query->where('status', 1);
                })->update(['default_page' => 0]);
            } else {
                $page = Page::findByUuid($id);
                Page::where('uuid', '!=', $id)->whereHas('theme', function ($query) {
                    $query->where('status', 1);
                })->update(['default_page' => 0]);
            }
            if ($page) {
                $page->default_page = $request->default;
                if ($page->save()) {
                    $this->pageUpdateService->updatePage($page->slug, getConfigValue('defaul_lang'));
                    return response()->json([
                        'status_code' => 200,
                        'message' => 'Default page updated successfully'
                    ], 200);
                } else {
                    return response()->json([
                        'status_code' => 500,
                        'message' => 'Internal Server Error'
                    ], 500);
                }
            } else {
                return response()->json([
                    'status_code' => 404,
                    'message' => 'Page not found'
                ], 404);
            }
        } catch (\Throwable $e) {
            Log::error('Error updating default page', ['exception' => $e]);
            return response()->json([
                'status_code' => 500,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function all_widgets()
    {
        $theme = Theme::has('widgets')
            ->with([
                'widgets' => function ($query) {
                    $query->where('status', 1)
                        ->select('uuid', 'theme_id', 'name', 'widget_image', 'shortkey');
                }
            ])->where("status", 1)
            ->first();
        if ($theme != null) {
            return response()->json([
                'status_code' => 200,
                'data' => $theme->widgets
            ]);
        } else {
            response()->json([
                'status_code' => 404,
                'message' => 'Theme not found'
            ]);
        }
    }

    public function all_forms()
    {
        $dynamicforms = DynamicForm::where("status", 1)->get();
        if ($dynamicforms != null) {
            return response()->json([
                'status_code' => 200,
                'data' => $dynamicforms
            ]);
        } else {
            return response()->json([
                'status_code' => 404,
                'message' => 'Form not found'
            ]);
        }
    }
    public function widgetshow($id)
    {
        $themeId = request()->header('theme-id');
        $lang = request()->lang; 
        $defaultLang = getConfigValue('defaul_lang');
        $widget = Widget::where('shortkey', $id)->where('theme_id', $themeId)->has('theme')->with(["widgetFields:uuid,widget_id,field_name,field_id,field_type,field_options,is_required", "theme" => function ($query) {
            $query->select('uuid', 'name');
        }])->first();
        if ($widget != null) {
            $widgetFields = $widget->widgetFields;
            if ($lang !== $defaultLang) {
                $translation = WidgetTranslation::where('widget_uuid', $widget->uuid)
                    ->where('lang', $lang)
                    ->first();


                if ($translation && $translation->default_data) {
                    $widget->default_data = $translation->default_data;
                }else{
                    $widget->default_data = $widget->default_data;
                }
            }
            return response()->json([
                'status_code' => 200,
                'data' => $widget,
                'view' => view('cms.widgets.widget_attribute', compact('widgetFields'))->render(),
            ]);
        } else {
            response()->json([
                'status_code' => 404,
                'message' => 'Widget not found'
            ]);
        }
    }

    public function widgetshowPage($id)
    {
        $pageType = request()->header('page-type');
        $themeId = request()->header('theme-id');
        $activeTheme = Theme::where('status', 1)->first(); 
        $widget = Widget::where('shortkey', $id)->where('theme_id', $activeTheme->uuid)->has('theme')->with(["widgetFields:uuid,widget_id,field_name,field_id,field_type,field_options,is_required", "theme" => function ($query) {
            $query->select('*')->where('status', 1);
        }])->first();
        if ($widget != null) {
            $widgetFields = $widget->widgetFields;
            return response()->json([
                'status_code' => 200,
                'data' => $widget,
                'view' => view('cms.widgets.widget_attribute', compact('widgetFields'))->render(),
            ]);
        } elseif($pageType) {
            return response()->json([
                'status_code' => 200,
                'data' => $widget,
                'view' => view('cms.widgets.widget_attribute', compact('widgetFields'))->render(),
            ]);
        } else {
            response()->json([
                'status_code' => 404,
                'message' => 'Widget not found'
            ]);
        }
    }


    public function moduleshow($id)
    {
        $module = Module::where('uuid', $id)->whereHas('theme')->with("moduleFields:uuid,module_id,field_name,field_id,field_type,field_options,status,auth_id,is_required")->first();
        if ($module != null) {
            $widgetFields = $module->moduleFields;
            return response()->json([
                'status_code' => 200,
                'data' => $module,
                'view' => view('cms.widgets.widget_attribute', compact('widgetFields'))->render(),
            ]);
        } else {
            response()->json([
                'status_code' => 404,
                'message' => $this->get_message('not_found'),
            ]);
        }
    }


    public function upload_image(Request $request)
    {
        try {
            $file = $request->file('image');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('uploads'), $filename);
            return response()->json(['location' => asset('uploads/' . $filename)], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function form_submit(Request $request)
    {
        $dyForm = DynamicForm::where('uuid', $request->form_uuid)->first();
        if ($dyForm) {
            $contact = new ContactUs;
            $contact->uuid = Str::uuid();
            $contact->form_id = $request->form_uuid;
            $contact->details = json_encode($request->except('form_uuid'));
            $contact->status = 1;
            $contact->save();

            $dyFormName = $dyForm->theme ? $dyForm->theme->name : '-';
            $dyFormLogo = $dyForm->theme ? $dyForm->theme->theme_logo : '-';
            $contactDetails = json_decode($contact->details, true);
            $merged = array_merge(
                $contactDetails ?? [],
                [
                    'theme_name' => $dyFormName,
                    'theme_logo' => $dyFormLogo ? getConfigValue('APP_ASSET_PATH') . $dyFormLogo : null,
                ]
            );
            if (env('MAIL_USERNAME') != null) {
                $array['view'] = 'emailtemplate.thanks_contact';
                $array['subject'] = isset($request->template_subject) ? $request->template_subject : 'New form submitted';
                $array['from'] = env('MAIL_FROM_ADDRESS');
                $array['contact'] = $merged;
                try {
                    Mail::to(json_decode($dyForm->to_email))->queue(new EmailManager($array));
                    return response()->json([
                        'status_code' => 200,
                        'message' => 'Form submitted successfully'
                    ], 200);
                } catch (\Exception $e) {
                    Log::error('Error sending email: ' . $e->getMessage());
                    return response()->json([
                        'status_code' => 500,
                        'message' => 'Something went wrong, please try again later'
                    ], 500);
                }
            }
        } else {
            return response()->json([
                'status' => 'error',
                'status_code' => 404,
                'message' => 'The provided form ID is invalid or not found.'
            ], 404);
        }
    }


    public function apiShow(string $themeName, string $slug)
    {
        $theme = Theme::where('theme_path', $themeName)->first();
        $page = Page::where('slug', $slug)->first();
        $finalResult = [];
        if ($page != null) {
            $widgets = Widget::where('theme_id', $theme->uuid)
                ->whereIn('widget_type', ['header', 'footer'])
                ->get()
                ->keyBy('widget_type');

            $header = $widgets['header']?->default_data ?? null;
            $footer = $widgets['footer']?->default_data ?? null;
            $content = '';

            if ($header != null && $page->default_header != 1) {
                $content .= $header;
            }

            $content .= $page->description;
            if ($footer != null && $page->default_footer != 1) {
                $content .= $footer;
            }

            $pattern = '/\[([A-Za-z_-]+)(.*?)\](?:\[\/\1\])?/';
            preg_match_all($pattern, $content, $matches);
            foreach ($matches[0] as $index => $shortcodeString) {
                $shortcode = $matches[1][$index];
                $attributes = $this->parseAttributes($shortcodeString); // Parse attributes for this shortcode instance
                if ($attributes == []) {
                    $dynamicForm = DynamicForm::select('uuid', 'form_name', 'details', 'is_recaptcha')->where('short_code', $shortcode)->first();
                    if ($dynamicForm != null) {
                        $dynamicFormData = ['form_id' => $dynamicForm->uuid, 'form_url' => route('form_submit')];
                        $dynamicFormData = array_merge($dynamicFormData, json_decode($dynamicForm->details));
                        $finalResult[$shortcode] = $dynamicFormData;
                    }
                } else {
                    $finalResult[$shortcode] = $attributes;
                }
            }
        }
        // dd($finalResult);
        return response()->json([
            'status_code' => 200,
            'data' => $finalResult
        ]);
    }

    protected function parseAttributes($shortcode)
    {
        $themeName = env('THEME_NAME');
        if (trim($shortcode) != '') {
            $pattern = '/(\w+)=\"([^\"]+)\"/';
            preg_match_all($pattern, $shortcode, $matches);
            $attributes = [];
            foreach ($matches[1] as $index => $attribute) {
                if (strpos($attribute, 'input_') === 0) {
                    $attributes[$attribute] = env('APP_ASSET_PATH') . $matches[2][$index];
                    // $attributes[$attribute] = $matches[2][$index];
                } elseif (strpos($attribute, 'module_') === 0) {
                    $module_file = str_replace("module_", "", $attribute);
                    // Set the value to the view for 'module_' attributes
                    $module = Module::with('moduleDetails:uuid,module_id,details,status')->where('shortkey', $module_file)->first();
                    // dd($module);
                    $viewPathModule = 'components.' . $themeName . '.modules.' . $module_file;
                    if (view()->exists($viewPathModule)) {
                        $attributes[$attribute] = $module->moduleDetails->pluck('details')->toArray();
                        $attributes[$attribute] = array_map('json_decode', $attributes[$attribute], array_fill(0, count($attributes[$attribute]), true));
                    } else {
                        $attributes[$attribute] = '';
                    }
                } elseif (strpos($attribute, 'modulemenu_') === 0) {
                    $module_file = str_replace("modulemenu_", "", $attribute);
                    $otherMenu = OtherMenu::select('uuid', 'name', 'status', 'menu_detail')->where('uuid', $matches[2][$index])->first();
                    if ($otherMenu != null) {
                        $menuDetail = $this->menuList(json_decode($otherMenu->menu_detail));
                        $viewPathModule = 'components.' . $themeName . '.' . strtolower(str_replace(' ', '_', $otherMenu->name));
                        if (view()->exists($viewPathModule)) {
                            $attributes[$attribute] = $menuDetail;
                        } else {
                            $attributes[$attribute] = '';
                        }
                    }
                } else {
                    $attributes[$attribute] = $matches[2][$index];
                }
            }
            return $attributes;
        }
    }

    protected function menuList($data)
    {
        $result = [];

        foreach ($data as $item) {
            $parent = [
                'name' => $item->name,
                'url' => $item->url,
                'target' => $item->target,
                'child' => []
            ];

            // If children exist, map them to a new structure
            if (isset($item->children) && is_array($item->children)) {
                foreach ($item->children as $child) {
                    $parent['child'][] = [
                        'name' => $child->name,
                        'url' => $child->url,
                        'target' => $child->target
                    ];
                }
            }

            $result[] = $parent;
        }

        return $result;
    }
}
