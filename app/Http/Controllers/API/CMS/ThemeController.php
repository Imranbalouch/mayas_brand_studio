<?php

namespace App\Http\Controllers\API\CMS;

use App\Http\Controllers\Controller;
use App\Models\CMS\Theme;
use App\Models\Language;
use App\Services\PageUpdateService;
use App\Services\PermissionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Str;
use App\Traits\MessageTrait;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;

class ThemeController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    use MessageTrait;

    protected $pageUpdateService;
    protected $permissionService;
    public function __construct(PageUpdateService $pageUpdateService,PermissionService $permissionService)
    {
        $this->pageUpdateService = $pageUpdateService;
        $this->permissionService = $permissionService;
    }

    public function index(Request $request)
    {
        //
        $menuUuid = $request->header('menu-uuid');
        $widgetuuid = $request->header('widget-uuid');
        $moduleuuid = $request->header('module-uuid');
        $formbuilderuuid = $request->header('formbuilder-uuid');
        $widgetpermission = $this->permissionService->checkPermissions($widgetuuid);
        $modulepermission = $this->permissionService->checkPermissions($moduleuuid);
        $formbuilderpermission = $this->permissionService->checkPermissions($formbuilderuuid);
        $permissions = $this->permissionService->checkPermissions($menuUuid);

        $themes = Theme::select('uuid', 'name', 'version', 'status', 'thumbnail_img','theme_path','theme_type')->with(['pages' => function($query) {
            $query->select('uuid', 'title', 'slug', 'theme_id', 'default_page')->where('default_page', 1);
        }]);

        if ($permissions['view']) {
            if (!$permissions['viewglobal']) {
                $themes = $themes->where('auth_id', Auth::user()->uuid);
            }
        }else{
            if (Auth::user()->hasPermission('viewglobal')) {
                $themes = $themes;
            } else {
                return response()->json(['message' => 'You do not have permission to view this menu'], Response::HTTP_FORBIDDEN);
            }
        }
        $themes = $themes->get();
        try {
            return response()->json([
                'status_code'=>200,
                'permissions' => $permissions,
                'widgetpermission'=>$widgetpermission,
                'modulepermission'=>$modulepermission,
                'formbuilderpermission'=>$formbuilderpermission,
                'data'=>$themes
            ],200);
        } catch (\Exception $e) {
            return response()->json([
                'status_code'=>500,
                'message'=>$this->get_message('server_error'),
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
        //
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|unique:themes|string|max:50|regex:/^[a-zA-Z\s]+$/',
                'version' => 'required|string|max:20|regex:/^[a-zA-Z0-9_.]+$/',
                'is_active' => 'boolean',
                'css_link.*' => 'nullable|url|max:255',
                'js_link.*' => 'nullable|url|max:255',
                'js_head_link.*' => 'nullable|url|max:255',
            ], [
                'name.required' => 'The name field is required.',
                'name.unique' => 'The name has already been taken.',
                'name.regex' => 'The name field contain only letters, numbers, and underscores.',
                'version.required' => 'The version field is required.',
                'version.regex' => 'The version field contain only letters, numbers, and underscores.',
                'is_active.boolean' => 'The is active field must be boolean.',
                'css_link.*.url' => 'The css link field must be a valid URL.',
                'js_link.*.url' => 'The js link field must be a valid URL.',
                'js_head_link.*.url' => 'The js link field must be a valid URL.',
            ]);

            if($validator->fails()) {
            
                $message = $validator->messages();
                
                return response()->json([
                    'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                    'errors' => strval($message)
                
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $themeName = $request->name;
            $themeNameFolder = preg_replace('/[^a-zA-Z0-9_]+/', '_', strtolower($request->name));

            $css_link = !empty($request->css_link) ? implode(',',$request->css_link) : '';
            $js_link = !empty($request->js_link) ? implode(',',$request->js_link) : '';
            $js_head_link = !empty($request->js_head_link) ? implode(',',$request->js_head_link) : '';
           
            // Process CSS files
            $uuid = Str::uuid();
            $theme = Theme::count();
            if ($theme == 0) {
                $status = 1;
            }else{
                $status = 0;
            }
            $data = [
                'uuid' => $uuid,
                'auth_id' => Auth::user()->uuid,
                'name' => $themeName,
                'fav_icon' => $request->fav_icon,
                'theme_logo' => $request->theme_logo,
                'theme_path' => $themeNameFolder,
                'short_description' => $request->short_description,
                'version' => $request->version,
                'thumbnail_img' => $request->thumbnail_img,
                'css_file' => $request->css_file,
                'js_file'  => $request->js_file,
                'css_link' => $css_link,
                'js_link' => $js_link,
                'js_head_link' => $js_head_link,
                'status' => $status,
                'theme_type' => $request->theme_type
            ];
      
            $theme = Theme::create($data);

            if ($theme) {
                return response()->json([
                    'status_code'=>200,
                    'message'=>"Theme added successfully",
                ], 200);
            } else {
                return response()->json([
                    'status_code'=>500,
                    'message'=>$this->get_message('server_error'),
                ], 500);
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return response()->json([
                'status_code'=>500,
                // 'message'=>$this->get_message('server_error'),
                'message'=>$e->getMessage(),
            ], 500);
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
    public function edit(string $id)
    {
        // dd($id);
        try {
            $theme = Theme::where('uuid',$id)->first();
            if ($theme != null) {
                return response()->json([
                    'status_code'=>200,
                    'data'=>$theme
                ], 200);
            }else{
                return response()->json([
                    'status_code'=>404,
                    'message'=>$this->get_message('not_found'),
                ], 404);
            }
        }catch (\Exception $e) {
            return response()->json([
                'status_code'=>500,
                'message'=>$this->get_message('server_error'),
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */

    public function update(Request $request,string $id)
    {

        $data =[];
        $theme = Theme::where('uuid',$id)->first();
        
        if ($theme == null) {
            return response()->json([
                'status_code' => 404,
                'errors' => $this->get_message('not_found'),
            
            ], 404);
        }
        try {
            
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:150|regex:/^[a-zA-Z\s]+$/|unique:themes,name,' . $theme->id,
                'version' => 'required|string|max:20|regex:/^[a-zA-Z0-9_.]+$/',
                'is_active' => 'boolean',
                'css_link.*' => 'nullable|url',
                'js_link.*' => 'nullable|url',
                'js_head_link.*' => 'nullable|url',
            ], [
                'name.required' => 'The name field is required.',
                'name.unique' => 'The name has already been taken.',
                'name.regex' => 'The name field contain only letters.',
                'version.required' => 'The version field is required.',
                'version.regex' => 'The version field contain only letters, numbers, and underscores.',
                'is_active.boolean' => 'The is active field must be boolean.',
                'css_link.url' => 'Each CSS link must be a valid URL.',
                'js_link.url' => 'Each JS link must be a valid URL.',
                'js_head_link.*.url' => 'The js link field must be a valid URL.',
            ]);

            if($validator->fails()) {
            
                $message = $validator->messages();
                
                return response()->json([
                    'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                    'errors' => strval($message)
                
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $themeName = preg_replace('/[^a-zA-Z0-9_]+/', '_', strtolower($request->name));
            $oldThemeName = preg_replace('/[^a-zA-Z0-9_]+/', '_', strtolower($theme->name));
    
            $oldFolderPath = public_path($oldThemeName);
            $newFolderPath = public_path($themeName);
            // $folderPath = public_path($themeNameFolder);

            if (File::exists($oldFolderPath) && is_writable($oldFolderPath)) {
                File::move($oldFolderPath, $newFolderPath);
            }    
            
            $cssCdn = $request->has('css_link') && is_array($request->css_link) 
            ? implode(',', array_map('trim', $request->css_link)) 
            : null;
            $cssCdnRtl = $request->has('css_link_rtl') && is_array($request->css_link_rtl) 
            ? implode(',', array_map('trim', $request->css_link_rtl))
            : null;
            $jsCdnRtl = $request->has('js_link_rtl') && is_array($request->js_link_rtl) 
            ? implode(',', array_map('trim', $request->js_link_rtl))
            : null;
            // dd($cssCdnRtl);
            $jsCdn = $request->has('js_link') && is_array($request->js_link) 
                ? implode(',', array_map('trim', $request->js_link)) 
                : null;
            $jsHeadCdn = $request->has('js_head_link') && is_array($request->js_head_link) 
                ? implode(',', array_map('trim', $request->js_head_link)) 
                : null;
            
            $data = [
                'auth_id' => Auth::user()->uuid,
                'name' => $request->name,
                'fav_icon' => $request->fav_icon,
                'theme_logo' => $request->theme_logo,
                'thumbnail_img' => $request->thumbnail_img,
                'theme_path' => $themeName,
                'short_description' => $request->short_description,
                'version' => $request->version,
                'css_link' => $cssCdn,
                'css_link_rtl' => $cssCdnRtl,
                'js_link_rtl' => $jsCdnRtl,
                'js_link' => $jsCdn,
                'js_head_link' => $jsHeadCdn,
                'css_file' => $request->css_file,
                'js_file'  => $request->js_file,
                'theme_type' => $request->theme_type,
            ];
            
            $theme->fill($data);
            $theme->save();
            if ($theme) {
                Language::where('status', 1)->get()->pluck('app_language_code')->each(function ($lang) use($theme) {
                    $theme->pages()->pluck('slug')->each(function ($slug) use($lang) {
                        $this->pageUpdateService->updatePage($slug,$lang);
                    });
                });
                return response()->json([
                    'status_code'=>200,
                    'res' => $data,
                    'message'=>"Theme updated successfully",
                ], 200);
            } else {
                return response()->json([
                    'status_code'=>500,
                    'message'=>$this->get_message('server_error'),
                ], 500);
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return response()->json([
                'status_code'=>500,
                'message'=>$this->get_message('server_error'),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $theme = Theme::findByUuid($id);
            if ($theme) {
                $oldthemeNameFolder = strtolower($theme->name);
                $oldthemeNameFolder = preg_replace('/[^a-zA-Z0-9_]+/', '_', $oldthemeNameFolder);
                $oldthemeNameFolder = preg_replace('/^[^a-zA-Z_]/', '_', $oldthemeNameFolder);
                $theme_path = public_path($oldthemeNameFolder);
                File::deleteDirectory($theme_path, true);
                $theme->delete();
                return response()->json([
                    'status_code'=>200,
                    'message'=>'Theme deleted successfully'
                ], 200);
            } else {
                return response()->json([
                    'status_code'=>404,
                    'message'=>$this->get_message('not_found'),
                ], 404);
            }
        } catch (\Throwable $e) {
            return response()->json([
                'status_code'=>500,
                'message'=>$this->get_message('server_error'),
            ], 500);
        }
    }

    
    /**
     * Update the specified resource in storage.
     */
    public function updateStatus(Request $request, string $id)
    {
        try {
            $theme = Theme::where('uuid', $id)->where('status', 1)->first();
            if ($theme) {
                Theme::where('uuid', '!=', $id)->update(['status' => 0]);
            } else {
                $theme = Theme::findByUuid($id);
                Theme::where('uuid', '!=', $id)->update(['status' => 0]);
            }
            if ($theme) {
                $theme->status = $request->status;
                if ($theme->save()) {
                    $themeChanged = Theme::where('status', 1)->first();
                    $this->updateEnv('THEME_NAME', $themeChanged->theme_path);
                    Artisan::call('cache:clear');
                    return response()->json([
                        'status_code'=>200,
                        'theme_id'=> $themeChanged->uuid,
                        'message'=>$this->get_message('update'),
                    ], 200);
                } else {
                    return response()->json([
                        'status_code'=>500,
                        'message'=>$this->get_message('server_error'),
                    ], 500);
                }
            } else {
                return response()->json([
                    'status_code'=>404,
                    'message'=>$this->get_message('not_found'),
                ], 404);
            }
        } catch (\Throwable $e) {
            return response()->json([
                'status_code'=>500,
                'message'=>$this->get_message('server_error'),
            ], 500);
        }
    }

    private function updateEnv($type, $val)
    {
        $path = base_path('.env');
        if (File::exists($path)) {
            $path = base_path('.env');
            if (file_exists($path)) {
                $val = '"'.trim($val).'"';
                if(is_numeric(strpos(file_get_contents($path), $type)) && strpos(file_get_contents($path), $type) >= 0){
                    file_put_contents($path, str_replace(
                        $type.'="'.env($type).'"', $type.'='.$val, file_get_contents($path)
                    ));
                }
                else{
                    file_put_contents($path, file_get_contents($path)."\r\n".$type.'='.$val);
                }
            }
        }
    }
}
