<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Symfony\Component\HttpFoundation\Response;
use Mail;
use Auth;
use Session;
use Hash;
use DB;
use App\Models\OtherMenu;
use App\Models\OtherMenuTranslation;
use App\Models\Menu;
use App\Models\Permission_assign;
use App\Models\Language;
use App\Models\CMS\Theme;
use App\Models\Page;
use App\Services\PageUpdateService;
use App\Services\PermissionService;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon; 
use Illuminate\Support\Str;
use App\Traits\MessageTrait;
use Illuminate\Support\Facades\File;

class OtherMenuController extends Controller
{
    
    use MessageTrait;
    protected $pageUpdateService;
    protected $permissionService;

    public function __construct(PageUpdateService $pageUpdateService,PermissionService $permissionService)
    {
        $this->pageUpdateService = $pageUpdateService;
        $this->permissionService = $permissionService;
    }

    public function add_othermenu(Request $request)
{
    $validator = Validator::make($request->all(), [
        'name' => 'required|max:50|regex:/^[a-zA-Z0-9\s\-\(\)]+$/',
        'icon' => config('app.image_validation'),
        'url' => '',
        'status' => '',
        'sort_id' => '',
        'parent_id' => '',
        'menu_detail' => '',
        'parent_array' => '',
        'child_array' => '',
    ], [
        'name.required' => 'The name field is required.',
        'name.max' => 'The name field must not exceed 50 characters.',
        'name.regex' => 'The name field may only contain letters, numbers, spaces, hyphens and parentheses.'
    ]);

    if ($validator->fails()) {
        return response()->json([   
            'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
            'errors' => $validator->errors()->toJson(),
        ], Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    try {
        $theme = Theme::where('status', '1')->first();
        $check_if_already = OtherMenu::where('theme_id', $theme->uuid)->where('name', $request->name)->get();

        if (count($check_if_already) > 0) {
            return response()->json([
                'status_code' => Response::HTTP_CONFLICT,
                'message' => $this->get_message('conflict'),
            ], Response::HTTP_CONFLICT);
        } else {
           
            $othermenu = $request->all();
            $othermenu['uuid'] = Str::uuid();
            $othermenu['theme_id'] = $theme->uuid;
            $othermenu['auth_id'] = Auth::user()->uuid;

            if ($request->hasFile('icon')) {
                $file = $request->file('icon');
                $fileName = time() . '_' . $file->getClientOriginalName();
                $folderName = '/upload_files/othermenu_icon/';
                $destinationPath = public_path() . $folderName;

                if (!file_exists($destinationPath)) {
                    mkdir($destinationPath, 0755, true);
                }

                $file->move($destinationPath, $fileName);
                $othermenu['icon'] = $folderName . $fileName;
            }

            $save_othermenu = OtherMenu::create($othermenu);

            if ($save_othermenu) {
                return response()->json([
                    'status_code' => Response::HTTP_CREATED,
                    'message' => $this->get_message('add'),
                ], Response::HTTP_CREATED);
            }
        }
    } catch (QueryException $e) {
        if ($e->getCode() === '23000') {
            return response()->json([
                'status_code' => Response::HTTP_CONFLICT,
                'message' => $this->get_message('conflict'),
            ], Response::HTTP_CONFLICT);
        }

        return response()->json([
            'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
            'message' => $this->get_message('server_error'),
        ], Response::HTTP_INTERNAL_SERVER_ERROR);
    } catch (\Exception $e) {
        return response()->json([
            'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
            'message' => $this->get_message('server_error'),
        ], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}


    
    
    public function edit_othermenu($uuid){

        try {
            
            $menu_id = request()->header('menu-uuid');
            $get_menu = Menu::where('uuid', $menu_id)->first();
            $edit_othermenu_by_id = OtherMenu::where('uuid', $uuid)->first();

            if(!$edit_othermenu_by_id)
            {
                return response()->json([
                    'status_code' => Response::HTTP_NOT_FOUND,
                    'message' => $this->get_message('not_found'),
                ], Response::HTTP_NOT_FOUND);
            }

            $othermenu_translation_get = OtherMenuTranslation::where('menu_id', $edit_othermenu_by_id->id)
            ->where('status', '1')->get();
            
            $get_active_language = Language::where('status', '1')->get();

            $now = Carbon::now();
            $auth_id = Auth::user()->uuid;

            if(count($get_active_language) > 0){

                foreach($get_active_language as $key => $language){
                    
                    $check_othermenu_translation = OtherMenuTranslation::where('menu_id', $edit_othermenu_by_id->id)
                    ->where('language_id', $language->id)
                    ->where('status', '1')->first();

                    if($check_othermenu_translation)
                    {
                        
                       

                    }
                    else{

                        $save_othermenu_translation = OtherMenuTranslation::insert([
                            ['uuid' => Str::uuid(), 'menu_id' => $edit_othermenu_by_id->id, 'name' => $edit_othermenu_by_id->name , 'language_id' => $language->id , 'auth_id' => $auth_id , 'created_at' => $now, 'updated_at' => $now],
                        ]);

                    }


                }


            }

            $othermenu_translations = OtherMenuTranslation::where('menu_id', $edit_othermenu_by_id->id)
            ->where('other_menus_translations.status', '1')
            ->join('languages', 'other_menus_translations.language_id', '=', 'languages.id')
            ->select('languages.code as language_code', 'languages.name as language_name' , 'languages.flag as flag' , 'languages.is_default as is_default' , 'languages.rtl as dir', 'other_menus_translations.*')
            ->get();

            
            if ($edit_othermenu_by_id) {

                $edit_othermenu_by_id->translations = $othermenu_translations;
       
                return response()->json([

                    'status_code' => Response::HTTP_OK,
                    'data' => $edit_othermenu_by_id,

                ], Response::HTTP_OK);


            }else{

                return response()->json([

                    'status_code' => Response::HTTP_NOT_FOUND,
                    'message' => $this->get_message('not_found'),

                ], Response::HTTP_NOT_FOUND);

            }


        }catch(\Exception $e) { 
            // Handle general exceptions
            return response()->json([

                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $this->get_message('server_error'),

            ], Response::HTTP_INTERNAL_SERVER_ERROR); // 500 Internal Server Error
        }

    }


    public function update_othermenu(Request $request){

        
        $validator = Validator::make($request->all(), [
            'icon' => config('app.image_validation'),
            'url' => '',
            'status' => '',
            'sort_id' => '',
            'parent_id' => '',
            'menu_detail' => '',
            'parent_array' => '',
            'child_array' => '',
        ]);
        
        
        
        if($validator->fails()) {
            
            $message = $validator->messages();
            
            return response()->json([
                
                'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'errors' => strval($validator->errors())
            
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $languages = DB::table('languages')->where('status', 1)->get();
        foreach ($languages as $language) {
            $nameKey = 'name_' . $language->code;
            if ($request->has($nameKey)) {
                $nameValidator = Validator::make($request->all(), [
                    $nameKey => 'required|max:50|regex:/^[a-zA-Z0-9\s\-\(\)]+$/'
                ], [
                    $nameKey.'.required' => 'The name field is required.',
                    $nameKey.'.max' => 'The name field must not exceed 50 characters.',
                    $nameKey.'.regex' => 'The name field may only contain letters, numbers, spaces, hyphens and parentheses.'
                ]);

                if ($nameValidator->fails()) {
                    return response()->json([
                        'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                        'errors' => $nameValidator->errors()
                    ], Response::HTTP_UNPROCESSABLE_ENTITY);
                }
            }
        }

        
        try{
            
            $menu_id = request()->header('menu-uuid');
            $theme_uuid = request()->header('theme-uuid');
            $uuid = request()->header('uuid');

            // $get_theme_path = Theme::where('uuid', '45964e6e-b80b-46a0-a395-a23a1bbef47a')->first();
            $get_theme_path = Theme::where('status',1)->first();
            $theme_name = $get_theme_path->theme_path;
            
            $upd_othermenu = OtherMenu::where('uuid', $uuid)->first();
            $oldFilename = $upd_othermenu->shortcode;
            $theme_short_code = str_replace(' ', '_', strtolower($upd_othermenu->name));
            
            if (!$upd_othermenu) {

                return response()->json([
                    'status_code' => Response::HTTP_NOT_FOUND,
                    'message' => $this->get_message('not_found'),
                ], Response::HTTP_NOT_FOUND);

            }


            if ($request->hasFile('icon')) {

                $file = $request->file('icon');
                $fileName = time() . '_' . $file->getClientOriginalName();
                $folderName = '/upload_files/othermenu_icon/';
                $destinationPath = public_path() . $folderName;
                
                if (!file_exists($destinationPath)) {
                    mkdir($destinationPath, 0755, true);
                }

                $file->move($destinationPath, $fileName);
                $upd_othermenu->icon = $folderName . $fileName;
            }

            $upd_othermenu->sort_id = $request->sort_id;
            $upd_othermenu->parent_id = $request->parent_id;
            $upd_othermenu->url = $request->url;
            $upd_othermenu->status = $request->status;
            $upd_othermenu->menu_detail = $request->menu_detail;
            $upd_othermenu->theme_id = $get_theme_path->uuid;

            if($request->parent_array != "" && $request->child_array != "")
            {
                $upd_othermenu->parent_array = $request->parent_array;
                $upd_othermenu->child_array = $request->child_array;
            }

            $upd_othermenu->save();

            foreach ($request->all() as $key => $value) {
                
                if (strpos($key, 'name_') === 0) {
                    
                    $languageCode = substr($key, 5);
            
                    $languageId = DB::table('languages')->where('code', $languageCode)->value('id');
            
                    if($languageId){
                        
                        OtherMenuTranslation::where('language_id', $languageId)
                        ->where('menu_id', $upd_othermenu->id)
                        ->update(['name' => $value]);

                    }

                }

            }


            foreach ($request->all() as $key => $value) {
                
                if (strpos($key, 'menudetail_') === 0) {
                    
                    $languageCode = substr($key, 11);
            
                    $languageId = DB::table('languages')->where('code', $languageCode)->value('id');
            
                    if($languageId){
                        
                        OtherMenuTranslation::where('language_id', $languageId)
                        ->where('menu_id', $upd_othermenu->id)
                        ->update(['menudetail' => $value]);

                    }

                }

            }


            foreach ($request->all() as $key => $value) {
                
                if ($request->hasFile($key)) {

                    if (strpos($key, 'icon_') === 0) {
                        $languageCode = substr($key, 5);
                        $languageId = DB::table('languages')->where('code', $languageCode)->value('id');
            
                        if ($languageId) {
                            $file = $request->file($key);
                            $fileName = time() . '_' . $file->getClientOriginalName();
                            $folderName = '/upload_files/othermenu_icon/';
                            $destinationPath = public_path() . $folderName;
            
                            if (!file_exists($destinationPath)) {
                                mkdir($destinationPath, 0755, true);
                            }
            
                            $file->move($destinationPath, $fileName);
            
                            OtherMenuTranslation::where('language_id', $languageId)
                            ->where('menu_id', $upd_othermenu->id)
                            ->update(['icon' => $folderName . $fileName]);

                        }
                    }

                }

            }


            $get_active_language = Language::where('status', '1')->where('is_default', '1')->first();
            $get_role_trans_by_def_lang = OtherMenuTranslation::where('menu_id', $upd_othermenu->id)
            ->where('language_id', $get_active_language->id)
            ->first();

            $upd_othermenu2 = DB::table('other_menus')
            ->where('id', $upd_othermenu->id)
            ->update([
                'name' => $get_role_trans_by_def_lang->name,
                'menu_detail' => $get_role_trans_by_def_lang->menudetail,
            ]);

            
            function buildMenuHTML($oldFilename, $parentArray, $childArray, $menuDetail) {
                // Remove the dd($dropdownMenu) line that was causing issues
                
                // Validate input parameters
                if (!is_array($menuDetail) || empty($menuDetail)) {
                    return '';
                }
            
                if (!is_array($parentArray) || !is_array($childArray)) {
                    return '';
                }
            
                // Start building the HTML structure
                $html = '';
            
                // Check if parent_ul has a value and is not just a space
                if (!empty($parentArray['parent_ul']) && trim($parentArray['parent_ul']) !== "") {
                    $html .= '<' . htmlspecialchars($parentArray['parent_ul']);
            
                    // Add class and id only if they are not empty
                    if (!empty($parentArray['parent_ul_class'])) {
                        $html .= ' class="' . htmlspecialchars($parentArray['parent_ul_class']) . '"';
                    }
                    if (!empty($parentArray['parent_ul_id'])) {
                        $html .= ' id="' . htmlspecialchars($parentArray['parent_ul_id']) . '"';
                    }
                    $html .= '>';
                }
            
                foreach ($menuDetail as $menuItem) {
                    if (!is_array($menuItem)) {
                        continue;
                    }
            
                    // Only add parent_li tag if it's not empty and not a space
                    if (!empty($parentArray['parent_li']) && trim($parentArray['parent_li']) !== "") {
                        $html .= '<' . htmlspecialchars($parentArray['parent_li']);
                        
                        // Build the classes array
                        $classes = [];
                        
                        // Add parent_li_class if it exists
                        if (!empty($parentArray['parent_li_class'])) {
                            $classes[] = htmlspecialchars($parentArray['parent_li_class']);
                        }
                        
                        // Add dropdown class if this menu item has children and dropdown_menu is specified
                        if (isset($menuItem['children']) && !empty($menuItem['children']) && isset($parentArray['parent_li_dropdown_class'])) {
                            $classes[] = htmlspecialchars($parentArray['parent_li_dropdown_class']);
                        }
                        
                        // Add the classes to the li element if we have any
                        if (!empty($classes)) {
                            $html .= ' class="' . implode(' ', $classes) . '"';
                        }
                        
                        $html .= '>';
                    }
            
                    // Add the menu item anchor
                    $html .= '<a href="' . htmlspecialchars($menuItem['url'] ?? '#') . '"';
            
                    // Add class and id attributes to anchor only if they exist
                    if (!empty($menuItem['class'])) {
                        $html .= ' class="' . htmlspecialchars($menuItem['class']) . '"';
                    }
                    if (!empty($menuItem['id'])) {
                        $html .= ' id="' . htmlspecialchars($menuItem['id']) . '"';
                    }
                    $html .= '>';
                    
                    // Add icon if not empty
                    if (!empty($menuItem['icon'])) {
                        $imageUrl = env("APP_ASSET_PATH", '') . htmlspecialchars($menuItem['icon']);
                        $html .= '<img src="' . $imageUrl . '" alt="menu icon">';
                    }
                    
                    // Add the menu item name
                    $html .= htmlspecialchars($menuItem['name'] ?? '');
            
                    // Add parent icon for items with children
                    if (isset($menuItem['children']) && !empty($menuItem['children']) && !empty($parentArray['parent_icon'])) {
                        $imageUrl = env("APP_ASSET_PATH", '') . htmlspecialchars($parentArray['parent_icon']);
                        $html .= '<img src="' . $imageUrl . '" alt="parent icon">';
                    }
                    $html .= '</a>';
            
                    // Check if there are child items
                    if (isset($menuItem['children']) && is_array($menuItem['children']) && !empty($menuItem['children'])) {
                        // Only add child_ul tag if it's not empty and not a space
                        if (!empty($childArray['child_ul']) && trim($childArray['child_ul']) !== "") {
                            $html .= '<' . htmlspecialchars($childArray['child_ul']);
            
                            // Add class to child_ul if it exists
                            if (!empty($childArray['child_ul_class'])) {
                                $html .= ' class="' . htmlspecialchars($childArray['child_ul_class']) . '"';
                            }
                            $html .= '>';
                        }
            
                        foreach ($menuItem['children'] as $childMenuItem) {
                            if (!is_array($childMenuItem)) {
                                continue;
                            }
            
                            // Only add child_li tag if it's not empty and not a space
                            if (!empty($childArray['child_li']) && trim($childArray['child_li']) !== "") {
                                $html .= '<' . htmlspecialchars($childArray['child_li']);
                                
                                // Add child_li_class if it exists
                                if (!empty($childArray['child_li_class'])) {
                                    $html .= ' class="' . htmlspecialchars($childArray['child_li_class']) . '"';
                                }
                                
                                $html .= '>';
                            }
            
                            // Add child menu item anchor
                            $html .= '<a href="' . htmlspecialchars($childMenuItem['url'] ?? '#') . '"';
            
                            // Add class and id attributes to anchor only if they exist
                            if (!empty($childMenuItem['class'])) {
                                $html .= ' class="' . htmlspecialchars($childMenuItem['class']) . '"';
                            }
                            if (!empty($childMenuItem['id'])) {
                                $html .= ' id="' . htmlspecialchars($childMenuItem['id']) . '"';
                            }
                            $html .= '>';
                            
                            // Add the child menu item name
                            $html .= htmlspecialchars($childMenuItem['name'] ?? '');
                            $html .= '</a>';
            
                            // Close child_li tag if it was opened
                            if (!empty($childArray['child_li']) && trim($childArray['child_li']) !== "") {
                                $html .= '</' . htmlspecialchars($childArray['child_li']) . '>';
                            }
                        }
            
                        // Close child_ul tag if it was opened
                        if (!empty($childArray['child_ul']) && trim($childArray['child_ul']) !== "") {
                            $html .= '</' . htmlspecialchars($childArray['child_ul']) . '>';
                        }
                    }
            
                    // Close parent_li tag if it was opened
                    if (!empty($parentArray['parent_li']) && trim($parentArray['parent_li']) !== "") {
                        $html .= '</' . htmlspecialchars($parentArray['parent_li']) . '>';
                    }
                }
            
                // Close parent_ul tag if it was opened
                if (!empty($parentArray['parent_ul']) && trim($parentArray['parent_ul']) !== "") {
                    $html .= '</' . htmlspecialchars($parentArray['parent_ul']) . '>';
                }
            
                return $html;
            }
                       
            
            
            function createBladeTemplate($oldFilename,$tableName, $html, $theme_name) {
                // Replace spaces with underscores and convert to lowercase
                $newFileName = str_replace(' ', '_', strtolower($tableName));
                $bladeFileName = str_replace(' ', '_', strtolower($tableName)) . '.blade.php';
                
                // Set the path to store the Blade file
                $directoryPath = resource_path('views/components/' . $theme_name);
                $filePath = $directoryPath . '/' . $bladeFileName;
                if ($oldFilename != $newFileName) {
                    $filePathOld = $directoryPath . '/' . $oldFilename . '.blade.php';
                    if (File::exists($filePathOld)) {
                        File::delete($filePathOld);
                    }
                }
                // Check if directory exists, if not, create it
                if (!file_exists($directoryPath)) {
                    mkdir($directoryPath, 0755, true); // Create the directory with write permissions
                }
            
                // Save the HTML content into the Blade file
                file_put_contents($filePath, $html);
                
                return $bladeFileName; // Return the file name if needed
            }
            
            function processMenuData($oldFilename,$row, $theme_name) {
                // Step 1: Decode JSON data
                $menuDetail = json_decode($row->menu_detail, true);
                $parentArray = json_decode($row->parent_array, true);
                $childArray = json_decode($row->child_array, true);
            
                // Step 2: Build HTML
                $html = buildMenuHTML($oldFilename, $parentArray, $childArray, $menuDetail);
            
                // Step 3: Save the HTML in a Blade template file
                $bladeFileName = createBladeTemplate($oldFilename,$row->name, $html, $theme_name); // Assuming $row->name is the table column name
            
                return $bladeFileName; // Return the created file name
            }
            
            // Fetch the row data from the database based on the ID
            $row = DB::table('other_menus')->where('id', $upd_othermenu->id)->first();
            
            // Process the data and generate the Blade file
            $bladeFile = processMenuData($oldFilename,$row, $theme_name);
            
            $html = buildMenuHTML($oldFilename,json_decode($row->parent_array, true), json_decode($row->child_array, true), json_decode($row->menu_detail, true));

            $second_update = DB::table('other_menus')->where('id', $upd_othermenu->id)
            ->update(['shortcode' => $theme_short_code, 'html' => $html]);
            try {
                $theme = Theme::where("status",1)->with('pages')->first()->pages->pluck('slug')->toArray();
                foreach ($theme as $key => $slug) {
                    $this->pageUpdateService->updatePage($slug, getConfigValue('defaul_lang'));
                }
            } catch (\Exception $e) {
                // Handle theme or page not found exceptions
                return response()->json([
                    'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                    'message' => $e->getMessage(),
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
            return response()->json([
                'status_code' => Response::HTTP_OK,
                'message' => $this->get_message('update'),
            ], Response::HTTP_OK);
          

        }catch (\Exception $e) { 
            // Handle general exceptions
            return response()->json([

                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                // 'message' => $this->get_message('server_error'),
                'message' => $e->getMessage(),

            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
        
        
    }


    public function delete_othermenu($uuid){

        try{

            $del_othermenu = OtherMenu::where('uuid', $uuid)->first();
            
            if(!$del_othermenu)
            {
                
                return response()->json([

                    'status_code' => Response::HTTP_NOT_FOUND,
                    'message' => $this->get_message('not_found'),

                ], Response::HTTP_NOT_FOUND);


            }else{

                $delete_othermenu = OtherMenu::destroy($del_othermenu->id);

                if($delete_othermenu){
                    
                    $del_othermenu_translation = OtherMenuTranslation::where('menu_id', $del_othermenu->id)->delete();

                    return response()->json([
                        
                        'status_code' => Response::HTTP_OK,
                        'message' => "Menu list deleted successfully",
                    
                    ], Response::HTTP_OK);
    
                }

            }


        }catch (\Exception $e) { 
            // Handle general exceptions
            return response()->json([

                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $this->get_message('server_error'),
                

            ], Response::HTTP_INTERNAL_SERVER_ERROR); // 500 Internal Server Error
        } 
        
    }


    public function get_othermenu(){

        try{

            $menuUuid = request()->header('menu-uuid');
            $permissions = $this->permissionService->checkPermissions($menuUuid);

            $activeTheme = Theme::where('status', 1)->first();
            $get_othermenu = OtherMenu::where('theme_id',$activeTheme->uuid)->orderBy('created_at', 'desc');
            // $get_othermenu = OtherMenu::orderBy('created_at', 'desc');
            if ($permissions['view']) {
                if (!$permissions['viewglobal']) {
                    $get_othermenu = $get_othermenu->where('auth_id', Auth::user()->uuid);
                }
            }else{
                if (Auth::user()->hasPermission('viewglobal')) {
                    $get_othermenu = $get_othermenu;
                } else {
                    return response()->json(['message' => 'Invalid permission for this action'], Response::HTTP_FORBIDDEN);
                }
            }

            $get_othermenu = $get_othermenu->get();

            if($get_othermenu){
                
                return response()->json([
                        
                    'status_code' => Response::HTTP_OK,
                    'permissions' => $permissions,
                    'data' => $get_othermenu,

                ], Response::HTTP_OK);
    
            }

        }catch (\Exception $e) { 
            // Handle general exceptions
            return response()->json([

                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $this->get_message('server_error'),
                

            ], Response::HTTP_INTERNAL_SERVER_ERROR); // 500 Internal Server Error
        } 

    }



    public function get_sort_othermenu()
    {
        
        $othermenu = OtherMenu::where('parent_id', 0)->where('status' , '1')->orderBy('sort_id')->get();

        $othermenu = $this->addChildren($othermenu);

        return response()->json([
                        
            'status_code' => Response::HTTP_OK,
            'data' => $othermenu,
        
        ], Response::HTTP_OK);

    }

    private function addChildren($othermenu)
    {
        foreach ($othermenu as $menu) {

            
            $children = OtherMenu::where('parent_id', $menu->id)->where('status' , '1')->orderBy('sort_id')->get();
            
            
            if ($children->isNotEmpty()) {
                $menu->is_child = 1;
                $menu->children = $this->addChildren($children);
            } else {
                $menu->is_child = 0;
                unset($menu->children);
            }

        }

        return $othermenu;

    }


}
