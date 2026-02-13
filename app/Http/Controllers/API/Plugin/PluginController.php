<?php

namespace App\Http\Controllers\API\Plugin;

use ZipArchive;
use App\Models\Menu;
use App\Models\Plugins;
use App\Models\PageType;
use App\Models\CMS\Theme;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Services\PageUpdateService;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class PluginController extends Controller
{
    protected $pageUpdateService;

    public function __construct(PageUpdateService $pageUpdateService)
    {
        $this->pageUpdateService = $pageUpdateService;
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //        
        $plugins = Plugins::all();
        return response()->json([
            'status_code' => 200,
            'data' => $plugins
        ], 200);
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
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function activeOrDeactive(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $plugin = Plugins::where('uuid', $request->uuid)->first();

        if (!$plugin) {
            return response()->json([
                'status_code' => Response::HTTP_NOT_FOUND,
                'message' => 'Plugin not found'
            ], Response::HTTP_NOT_FOUND);
        }

        $plugin->status = !$plugin->status ? 1 : 0;
        $plugin->save();
        try {
            $theme = Theme::where("status", 1)->with('pages')->first()->pages->pluck('slug')->toArray();
            $languages = all_languages();
            foreach ($languages as $lang) {
                foreach ($theme as $key => $slug) {
                    $this->pageUpdateService->updatePage($slug, $lang->app_language_code);
                }
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
            'message' => 'Plugin status updated successfully'
        ], Response::HTTP_OK);
    }

    // public function pluginInstall(Request $request){
    //     $file = $request->file('plugin_zip');
    //     $filename = $file->getClientOriginalName();
    //     $filePath = $file->storeAs('plugins', $filename);
    //     return response()->json([
    //         'status_code' => Response::HTTP_OK,
    //         'message' => 'Plugin installed successfully',
    //         'file_path' => $filePath
    //     ], Response::HTTP_OK);
    // }
    public function pluginInstall(Request $request)
    {
        try {
            // Validate file
            $request->validate([
                'plugin_zip' => 'required|file|mimes:zip|max:10240', // max 10MB
            ]);

            $file = $request->file('plugin_zip');
            $filenameWithoutExtension = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $filePath = $file->storeAs('plugins', $file->getClientOriginalName());
            $fullPath = public_path($filePath);
            $extractPath = public_path('plugins/extracted/');
            $zip = new ZipArchive;
            if ($zip->open($fullPath) === true) {
                // Try to read plugin.json directly
                $zip->extractTo($extractPath);
                $zip->close();

                $pluginBasePath = $extractPath . '/' . $filenameWithoutExtension;
                $pluginJsonPath = $extractPath . '/' . $filenameWithoutExtension . '/plugin.json';

                if (!file_exists($pluginJsonPath)) {
                    return response()->json([
                        'status_code' => 400,
                        'message' => 'plugin.json not found in plugin zip',
                    ], 400);
                }

                $jsonData = json_decode(file_get_contents($pluginJsonPath), true);
                if (!$jsonData || !isset($jsonData['name'])) {
                    return response()->json([
                        'status_code' => 400,
                        'message' => 'Invalid plugin.json format',
                    ], 400);
                }

                $pluginName = $jsonData['name'];
                $pluginSlug = Str::slug($pluginName);

                $existingPlugin = Plugins::where('name', $pluginName)
                    ->orWhere('slug', $pluginSlug)
                    ->first();

                if ($existingPlugin) {
                    return response()->json([
                        'status_code' => 400,
                        'message' => 'Plugin already exists',
                    ], 400);
                }

                // ---- NEW FEATURE: Move Controller & Models ----
                $fs = new \Illuminate\Filesystem\Filesystem();

                // Get user-defined paths from plugin.json
                $controllerDest = base_path($jsonData['controller_path'] ?? 'app/Http/Controllers/API/Plugin');
                $modelDest      = base_path($jsonData['model_path'] ?? 'app/Models/Plugin');

                // Ensure directories exist
                if (!$fs->isDirectory($controllerDest)) {
                    $fs->makeDirectory($controllerDest, 0755, true);
                }
                if (!$fs->isDirectory($modelDest)) {
                    $fs->makeDirectory($modelDest, 0755, true);
                }

                // Copy Controllers
                $controllerSrc = $pluginBasePath . '/src/Controller';
                if ($fs->isDirectory($controllerSrc)) {
                    $fs->copyDirectory($controllerSrc, $controllerDest);
                }

                // Copy Models
                $modelSrc = $pluginBasePath . '/src/Models';
                if ($fs->isDirectory($modelSrc)) {
                    $fs->copyDirectory($modelSrc, $modelDest);
                }

                // Copy Resources
                if (isset($jsonData['resources_path'])) {
                    $src = $pluginBasePath . '/src/Resources';
                    $dest = base_path($jsonData['resources_path'] ?? 'app/Http/Resources/Plugin');
                    if ($fs->isDirectory($src)) {
                        if (!$fs->isDirectory($dest)) {
                            $fs->makeDirectory($dest, 0755, true);
                        }

                        $fs->copyDirectory($src, $dest);
                        Log::info("Copied Resources folder → {$dest}");
                    }
                }

                // Copy Exports
                if (isset($jsonData['exports_path'])) {

                    $src = $pluginBasePath . '/src/Exports';
                    $dest = base_path($jsonData['exports_path'] ?? 'app/Exports/Plugin');

                    if ($fs->isDirectory($src)) {

                        if (!$fs->isDirectory($dest)) {
                            $fs->makeDirectory($dest, 0755, true);
                        }

                        $fs->copyDirectory($src, $dest);
                        Log::info("Copied Exports folder → {$dest}");
                    }
                }

                // Copy Imports
                if (isset($jsonData['imports_path'])) {

                    $src = $pluginBasePath . '/src/Imports';
                    $dest = base_path($jsonData['imports_path'] ?? 'app/Imports/Plugin');

                    if ($fs->isDirectory($src)) {

                        if (!$fs->isDirectory($dest)) {
                            $fs->makeDirectory($dest, 0755, true);
                        }

                        $fs->copyDirectory($src, $dest);
                        Log::info("Copied Imports folder → {$dest}");
                    }
                }

                // Copy Utility
                if (isset($jsonData['utility_path'])) {

                    $src = $pluginBasePath . '/src/Utility';
                    $dest = base_path($jsonData['utility_path'] ?? 'app/Utility/Plugin');

                    if ($fs->isDirectory($src)) {

                        if (!$fs->isDirectory($dest)) {
                            $fs->makeDirectory($dest, 0755, true);
                        }

                        $fs->copyDirectory($src, $dest);
                        Log::info("Copied Utility folder → {$dest}");
                    }
                }


                // Copy Services
                if (isset($jsonData['services_path'])) {

                    $src = $pluginBasePath . '/src/Services';
                    $dest = base_path($jsonData['services_path'] ?? 'app/Services/Plugin');

                    if ($fs->isDirectory($src)) {

                        if (!$fs->isDirectory($dest)) {
                            $fs->makeDirectory($dest, 0755, true);
                        }

                        $fs->copyDirectory($src, $dest);
                        Log::info("Copied Services folder → {$dest}");
                    }
                }

                // Copy emailtemplate views
                $emailTemplateSrc = $pluginBasePath . '/resources/views/emailtemplate';

                if ($fs->isDirectory($emailTemplateSrc)) {

                    // Read Email template destination path from plugin.json
                    $emailTemplateDest = base_path($jsonData['emailtemplate_view_path'] ?? 'resources/views/emailtemplate');

                    // Ensure destination directory exists
                    if (!$fs->isDirectory($emailTemplateDest)) {
                        $fs->makeDirectory($emailTemplateDest, 0755, true);
                    }

                    // Copy only top-level files (ignore folders)
                    $EmailTemplateFiles = $fs->files($emailTemplateSrc);
                    foreach ($EmailTemplateFiles as $file) {
                        $fs->copy($file->getRealPath(), $emailTemplateDest . '/' . $file->getFilename());
                    }

                    Log::info("Email template views copied to: " . $emailTemplateDest);
                } else {
                    Log::info("No emailtemplate views folder found: " . $emailTemplateSrc);
                }

                // ---- NEW FEATURE: Copy Route file----
                $routesSrc = $pluginBasePath . '/routes';
                if ($fs->isDirectory($routesSrc)) {
                    foreach ($fs->files($routesSrc) as $routeFile) {
                        $target = base_path('routes/' . $routeFile->getFilename());
                        $fs->copy($routeFile->getRealPath(), $target);
                    }
                }

                // ---- NEW FEATURE: Copy HTML Views to Frontend Directory ----
                $htmlSrc = $pluginBasePath . '/views';
                if ($fs->isDirectory($htmlSrc)) {
                    $htmlDest = getConfigValue('Admin_Dir');
                    if ($htmlDest && $fs->isDirectory($htmlDest)) {
                        // Create views directory if it doesn't exist
                        if (!$fs->isDirectory($htmlDest)) {
                            $fs->makeDirectory($htmlDest, 0755, true);
                        }
                            
                        // Copy all files from views directory
                        // $fs->copyDirectory($htmlSrc, $htmlDest);

                        // Copy only top-level files (ignore subdirectories)
                        $files = $fs->files($htmlSrc);
                        foreach ($files as $file) {
                            $fs->copy($file->getPathname(), $htmlDest . '/' . $file->getFilename());
                        }
                        
                        Log::info("Plugin views copied to frontend: " . $htmlDest);
                    } else {
                        Log::warning("Frontend directory not found or invalid: " . $htmlDest);
                    }
                }

                // ---- NEW FEATURE: Copy CSS Files to Frontend ----
                $cssSrc = $pluginBasePath . '/views/css';
                if ($fs->isDirectory($cssSrc)) {
                    $cssDest = getConfigValue('Admin_Dir') . '/assets/css/';
                    
                    // Create destination directory if it doesn't exist
                    if (!$fs->isDirectory($cssDest)) {
                        $fs->makeDirectory($cssDest, 0755, true);
                    }

                    // Copy only top-level CSS files
                    $files = $fs->files($cssSrc);
                    foreach ($files as $file) {
                        $fs->copy($file->getPathname(), $cssDest . '/' . $file->getFilename());
                    }
                    Log::info("Plugin CSS copied to: " . $cssDest);
                } else {
                    Log::info("No CSS directory found in plugin: " . $cssSrc);
                }


                // ---- NEW FEATURE: Copy JS Files to Frontend ----
                $jsSrc = $pluginBasePath . '/views/js';
                if ($fs->isDirectory($jsSrc)) {
                    $jsDest = getConfigValue('Admin_Dir'). '/assets/js/';
                    // Create destination directory if it doesn't exist
                    if (!$fs->isDirectory($jsDest)) {
                        $fs->makeDirectory($jsDest, 0755, true);
                    }

                    // Copy all files from views directory
                    // $fs->copyDirectory($jsSrc, $jsDest);
                    // Copy only top-level JS files
                    $files = $fs->files($jsSrc);
                    foreach ($files as $file) {
                        $fs->copy($file->getPathname(), $jsDest . '/' . $file->getFilename());
                    }
                    Log::info("Plugin JS copied to: " . $jsDest);
                } else {
                    Log::info("No JS directory found in plugin: " . $jsSrc);
                }

                // ---- NEW FEATURE: Copy PDF View Files ----
                $pdfSrc = $pluginBasePath . '/resources/views/pdf';
                if ($fs->isDirectory($pdfSrc)) {

                    // Read PDF destination path from plugin.json
                    $pdfDest = base_path($jsonData['pdf_view_path'] ?? 'resources/views/pdf');

                    // Ensure destination directory exists
                    if (!$fs->isDirectory($pdfDest)) {
                        $fs->makeDirectory($pdfDest, 0755, true);
                    }

                    // Copy only top-level files (ignore folders)
                    $pdfFiles = $fs->files($pdfSrc);
                    foreach ($pdfFiles as $file) {
                        $fs->copy($file->getRealPath(), $pdfDest . '/' . $file->getFilename());
                    }

                    Log::info("PDF views copied to: " . $pdfDest);
                } else {
                    Log::info("No PDF views folder found: " . $pdfSrc);
                }

                // ---- NEW FEATURE: Copy Custom Frontend Files ----
                $customFrontendSrc = $pluginBasePath . '/custom_frontend';
                if ($fs->isDirectory($customFrontendSrc)) {
                    // Read custom frontend destination from plugin.json
                    $customFrontendDest = getConfigValue('Frontend_Dir');
                    
                    // Ensure destination directory exists
                    if (!$fs->isDirectory($customFrontendDest)) {
                        $fs->makeDirectory($customFrontendDest, 0755, true);
                    }
                    
                    // Copy all files and subdirectories recursively
                    $fs->copyDirectory($customFrontendSrc, $customFrontendDest);
                    
                    Log::info("Custom frontend files copied to: " . $customFrontendDest);
                } else {
                    Log::info("No custom frontend directory found: " . $customFrontendSrc);
                }


                // ---- NEW FEATURE: Copy Plugin Icon to Frontend ----
                $iconRelativePath = $jsonData['icon'] ?? null;
                if ($iconRelativePath) {
                    $iconSrc = $pluginBasePath . '/' . $iconRelativePath;
                    $iconDestDir = getConfigValue('Admin_Dir') . '/assets/images/plugin/';
                    $fs = new \Illuminate\Filesystem\Filesystem();

                    if ($fs->exists($iconSrc)) {
                        if (!$fs->isDirectory($iconDestDir)) {
                            $fs->makeDirectory($iconDestDir, 0755, true);
                        }

                        $fs->copy($iconSrc, $iconDestDir . basename($iconRelativePath));
                        Log::info("Plugin icon copied to frontend: " . $iconDestDir . basename($iconRelativePath));
                    } else {
                        Log::warning("Plugin icon not found at: " . $iconSrc);
                    }
                }

                // ---- NEW FEATURE: Insert Menus from plugin.json ----
                if (!empty($jsonData['menus'])) {

                    foreach ($jsonData['menus'] as $menuData) {

                        $parentMenuExists = Menu::where('name', $menuData['name'])->first();
                        
                        if(!$parentMenuExists){
                            // Insert Parent Menu
                            $parentMenu = Menu::create([
                                'uuid'        => Str::uuid(),
                                'name'        => $menuData['name'],
                                'description' => $menuData['description'] ?? null,
                                'sort_id'     => $menuData['sort_id'] ?? 0,
                                'icon'        => $menuData['icon'] ?? null,
                                'auth_id'     => Auth::user()->uuid ?? 0,
                                'status'      => $menuData['status'] ?? 1,
                                'parent_id'   => 0,                     // PARENT SHOULD ALWAYS BE 0
                                'url'         => $menuData['url'] ?? '#',
                            ]);
                        }

                        // Insert Child Menus (if any)
                        if (!empty($menuData['child'])) {
                            foreach ($menuData['child'] as $child) {
                                $childMenuExists = Menu::where('name', $child['name'])->first();
                                if(!$childMenuExists){
                                    Menu::create([
                                    'uuid'        => Str::uuid(),
                                    'name'        => $child['name'],
                                    'description' => null,
                                    'sort_id'     => $child['sort_id'] ?? 0,
                                    'icon'        => $child['icon'] ?? null,
                                    'auth_id'     => Auth::user()->uuid ?? 0,
                                    'status'      => $child['status'] ?? 1,
                                    'parent_id'   => $parentMenu->id,  // CHILD SHOULD HAVE PARENT'S ID
                                    'url'         => $child['url'] ?? '',
                                ]);       
                                }
                            }
                        }
                    }
                }

                // ---- NEW FEATURE: Page Type ----
                if(!empty($jsonData['page_types'])){
                    foreach($jsonData['page_types'] as $pageType){
                        $pageTypeExists = PageType::where('name', $pageType['name'])->first();
                        if(!$pageTypeExists){
                            PageType::create([
                                'uuid'        => Str::uuid(),
                                'name'        => $pageType['name'],
                                'page_type'   => $pageType['page_type'],
                            ]);
                        }
                    }
                }


                // Run migrations if exists
                // $migrationPath = $pluginBasePath . '/migrations';
                // if ($fs->isDirectory($migrationPath)) {
                //     Log::info("Outputting migration path: " . $migrationPath);
                //     Artisan::call('migrate', [
                //         '--path' => 'public/plugins/extracted/' . $filenameWithoutExtension . '/migrations',
                //         '--force' => true
                //     ]);
                // }

                // ---- SMART MIGRATION SYSTEM ----
                $migrationPath = $pluginBasePath . '/migrations';

                if ($fs->isDirectory($migrationPath)) {

                    $migrationFiles = $fs->files($migrationPath);

                    foreach ($migrationFiles as $file) {

                        $content = file_get_contents($file->getRealPath());

                        // Extract table name from Schema::create
                        if (preg_match("/Schema::create\(['\"]([^'\"]+)['\"]/", $content, $matches)) {
                            $tableName = $matches[1];

                            // Skip migration if table already exists
                            if (Schema::hasTable($tableName)) {
                                Log::warning("Skipping migration for existing table: " . $tableName);
                                continue;
                            }
                        }

                        // Run single migration manually
                        Artisan::call('migrate', [
                            '--path'  => 'public/plugins/extracted/' . $filenameWithoutExtension . '/migrations/' . $file->getFilename(),
                            '--force' => true
                        ]);

                        Log::info("Migrated file: " . $file->getFilename());
                    }
                }


                // ---- NEW FEATURE: Run Seeders if exists ----
                $seederPath = $pluginBasePath . '/seeders';
                if ($fs->isDirectory($seederPath)) {
                    Log::info("Found seeders directory: " . $seederPath);
                    
                    // Get all seeder files
                    $seederFiles = $fs->files($seederPath);
                    
                    foreach ($seederFiles as $seederFile) {
                        $seederFileName = $seederFile->getFilenameWithoutExtension();
                        
                        // Copy seeder to database/seeders temporarily
                        $tempSeederPath = database_path('seeders/' . $seederFile->getFilename());
                        $fs->copy($seederFile->getRealPath(), $tempSeederPath);
                        
                        try {
                            // Run the seeder
                            Artisan::call('db:seed', [
                                '--class' => $seederFileName,
                                '--force' => true
                            ]);
                            
                            Log::info("Seeder executed successfully: " . $seederFileName);
                            
                            // Remove temporary seeder file
                            if ($fs->exists($tempSeederPath)) {
                                $fs->delete($tempSeederPath);
                            }
                        } catch (\Exception $e) {
                            Log::error("Failed to run seeder {$seederFileName}: " . $e->getMessage());
                            
                            // Clean up temporary file even if seeder fails
                            if ($fs->exists($tempSeederPath)) {
                                $fs->delete($tempSeederPath);
                            }
                        }
                    }
                }
                // Insert into DB
                $plugin = Plugins::create([
                    'name' => $jsonData['name'],
                    'slug' => Str::slug($jsonData['name']),
                    'type' => $jsonData['type'] ?? null,
                    'version' => $jsonData['version'] ?? null,
                    'description' => $jsonData['description'] ?? null,
                    'icon' => $jsonData['icon'] != null ? 'plugins/extracted/' . $filenameWithoutExtension . '/' . $jsonData['icon'] : null,
                    'status' => $jsonData['status'] ?? 0,
                    'settings' => $jsonData['settings'] ?? null,
                ]);

                return response()->json([
                    'status_code' => 200,
                    'message' => 'Plugin installed successfully',
                    'plugin' => $plugin,
                ], 200);
            } else {
                return response()->json([
                    'status_code' => 500,
                    'message' => 'Failed to open plugin zip',
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status_code' => 500,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function pluginUninstall(Request $request)
    {
        try {
            // Validate uuid
            $request->validate([
                'uuid' => 'required|uuid',
            ]);

            // Find plugin
            $plugin = Plugins::where('uuid', $request->uuid)->first();
            if (!$plugin) {
                return response()->json([
                    'status_code' => 404,
                    'message' => 'Plugin not found',
                ], 404);
            }

            $pluginName = Str::slug($plugin->name); // safer for filenames/folders
            // $pluginName = str_replace(' ', '', strtolower($plugin->name));
            // Paths
            $extractPath    = public_path('plugins/extracted/' . $pluginName);
            $extractPathZip = public_path('plugins/' . $pluginName . '.zip');
            $extractPath = public_path('plugins/extracted/');
            $fs = new \Illuminate\Filesystem\Filesystem();
            $pluginBasePath = $extractPath . '/' . $pluginName;

            // ---- NEW FEATURE: Rollback Seeders (Remove seeded data) ----
            $seederPath = $pluginBasePath . '/seeders';
            if ($fs->isDirectory($seederPath)) {
                Log::info("Found seeders directory for rollback: " . $seederPath);
                
                // Get all seeder files
                $seederFiles = $fs->files($seederPath);
                
                foreach ($seederFiles as $seederFile) {
                    $seederFileName = $seederFile->getFilenameWithoutExtension();
                    
                    // Copy seeder to database/seeders temporarily
                    $tempSeederPath = database_path('seeders/' . $seederFile->getFilename());
                    $fs->copy($seederFile->getRealPath(), $tempSeederPath);
                    
                    try {
                        // Check if the seeder class has a rollback method
                        require_once $tempSeederPath;
                        
                        $seederClass = "Database\\Seeders\\{$seederFileName}";
                        
                        if (class_exists($seederClass)) {
                            $seederInstance = new $seederClass();
                            
                            // Check if rollback method exists
                            if (method_exists($seederInstance, 'rollback')) {
                                $seederInstance->rollback();
                                Log::info("Seeder data rolled back successfully: " . $seederFileName);
                            } else {
                                Log::warning("Seeder {$seederFileName} does not have a rollback method");
                            }
                        }
                        
                        // Remove temporary seeder file
                        if ($fs->exists($tempSeederPath)) {
                            $fs->delete($tempSeederPath);
                        }
                    } catch (\Exception $e) {
                        Log::error("Failed to rollback seeder {$seederFileName}: " . $e->getMessage());
                        
                        // Clean up temporary file even if rollback fails
                        if ($fs->exists($tempSeederPath)) {
                            $fs->delete($tempSeederPath);
                        }
                    }
                }
            }

            // $migrationPath = $pluginBasePath . '/migrations';
            //     if ($fs->isDirectory($migrationPath)) {
            //         Artisan::call('migrate', [
            //             '--path' => 'public/plugins/extracted/' . $pluginName . '/migrations',
            //             '--force' => true
            //         ]);
            //     }
            // if ($fs->isDirectory($migrationPath)) {
            //     Artisan::call('migrate:rollback', [
            //         '--path' => 'public/plugins/extracted/' . $pluginName . '/migrations',
            //         '--force' => true,
            //     ]);
            // }

             // ---- NEW FEATURE: Remove only specific Controller & Model files ----
                // $controllerPath = app_path('Http/Controllers/API/Plugin');
                // $modelPath      = app_path('Models/Plugin');

                // // Look for plugin files in controller folder
                // if (File::isDirectory($controllerPath)) {
                //     $controllerFiles = File::files($controllerPath);
                //     foreach ($controllerFiles as $file) {
                //         if (Str::contains($pluginName, '-')) {
                //             $formattedpluginName = str_replace(['-', ' '], '', strtolower($pluginName));
                //         }
                //         if (Str::contains(strtolower($file->getFilename()), strtolower($formattedpluginName))) {
                //             File::delete($file->getRealPath());
                //         }
                //     }
                // }

                // // Look for plugin files in model folder
                // if (File::isDirectory($modelPath)) {
                //     $modelFiles = File::files($modelPath);
                //     foreach ($modelFiles as $file) {
                //         if (Str::contains($pluginName, '-')) {
                //             $formattedpluginName = str_replace(['-', ' '], '', strtolower($pluginName));
                //         }
                //         if (Str::contains(strtolower($file->getFilename()), strtolower($formattedpluginName))) {
                //             File::delete($file->getRealPath());
                //         }
                //     }
                // }

            // Load plugin.json if it exists
            $jsonPath = $pluginBasePath . '/plugin.json';
            $jsonData = [];

            if (File::exists($jsonPath)) {
                $jsonContent = File::get($jsonPath);
                $jsonData = json_decode($jsonContent, true) ?? [];
            }

            // ---- NEW FEATURE: Remove only specific Controller & Model files ----
            $controllerPath = base_path($jsonData['controller_path'] ?? 'app/Http/Controllers/API/Plugin');
            $modelPath      = base_path($jsonData['model_path'] ?? 'app/Models/Plugin');
            
            $pluginControllerSrc = $pluginBasePath . '/src/Controller';
            $pluginModelSrc      = $pluginBasePath . '/src/Models';
            // Look for plugin files in controller folder
            if ($fs->isDirectory($pluginControllerSrc) && $fs->isDirectory($controllerPath)) {
                $controllerFiles = $fs->files($pluginControllerSrc);

                foreach ($controllerFiles as $file) {
                    $targetFile = $controllerPath . '/' . $file->getFilename();
                    if ($fs->exists($targetFile)) {
                        $fs->delete($targetFile);
                        Log::info("Deleted controller: " . $targetFile);
                    }
                }
            }

            // Remove Models that were originally inside plugin zip
            if ($fs->isDirectory($pluginModelSrc) && $fs->isDirectory($modelPath)) {
                $modelFiles = $fs->files($pluginModelSrc);

                foreach ($modelFiles as $file) {
                    $targetFile = $modelPath . '/' . $file->getFilename();
                    if ($fs->exists($targetFile)) {
                        $fs->delete($targetFile);
                        Log::info("Deleted model: " . $targetFile);
                    }
                }
            }

            // ---- Remove Resources ----
            if (isset($jsonData['resources_path'])) {
                $dest = base_path($jsonData['resources_path'] ?? 'app/Http/Resources/Plugin');
                $pluginResourcesSrc = $pluginBasePath . '/src/Resources';
                
                if ($fs->isDirectory($pluginResourcesSrc) && $fs->isDirectory($dest)) {
                    $resourceFiles = $fs->allFiles($pluginResourcesSrc);
                    
                    foreach ($resourceFiles as $file) {
                        $relativePath = $file->getRelativePathname();
                        $targetFile = $dest . '/' . $relativePath;
                        
                        if ($fs->exists($targetFile)) {
                            $fs->delete($targetFile);
                            Log::info("Deleted resource file: " . $targetFile);
                        }
                    }
                    
                    Log::info("Removed plugin Resources files");
                }
            }

            // ---- Remove Exports ----
            if (isset($jsonData['exports_path'])) {
                $dest = base_path($jsonData['exports_path'] ?? 'app/Exports/Plugin');
                $pluginExportsSrc = $pluginBasePath . '/src/Exports';
                
                if ($fs->isDirectory($pluginExportsSrc) && $fs->isDirectory($dest)) {
                    $exportFiles = $fs->allFiles($pluginExportsSrc);
                    
                    foreach ($exportFiles as $file) {
                        $relativePath = $file->getRelativePathname();
                        $targetFile = $dest . '/' . $relativePath;
                        
                        if ($fs->exists($targetFile)) {
                            $fs->delete($targetFile);
                            Log::info("Deleted export file: " . $targetFile);
                        }
                    }
                    
                    Log::info("Removed plugin Exports files");
                }
            }

            // ---- Remove Imports ----
            if (isset($jsonData['imports_path'])) {
                $dest = base_path($jsonData['imports_path'] ?? 'app/Imports/Plugin');
                $pluginImportsSrc = $pluginBasePath . '/src/Imports';
                
                if ($fs->isDirectory($pluginImportsSrc) && $fs->isDirectory($dest)) {
                    $importFiles = $fs->allFiles($pluginImportsSrc);
                    
                    foreach ($importFiles as $file) {
                        $relativePath = $file->getRelativePathname();
                        $targetFile = $dest . '/' . $relativePath;
                        
                        if ($fs->exists($targetFile)) {
                            $fs->delete($targetFile);
                            Log::info("Deleted import file: " . $targetFile);
                        }
                    }
                    
                    Log::info("Removed plugin Imports files");
                }
            }

            // ---- Remove Utility ----
            if (isset($jsonData['utility_path'])) {
                $dest = base_path($jsonData['utility_path'] ?? 'app/Utility/Plugin');
                $pluginUtilitySrc = $pluginBasePath . '/src/Utility';
                
                if ($fs->isDirectory($pluginUtilitySrc) && $fs->isDirectory($dest)) {
                    $utilityFiles = $fs->allFiles($pluginUtilitySrc);
                    
                    foreach ($utilityFiles as $file) {
                        $relativePath = $file->getRelativePathname();
                        $targetFile = $dest . '/' . $relativePath;
                        
                        if ($fs->exists($targetFile)) {
                            $fs->delete($targetFile);
                            Log::info("Deleted utility file: " . $targetFile);
                        }
                    }
                    
                    Log::info("Removed plugin Utility files");
                }
            }

            // ---- Remove Services ----
            if (isset($jsonData['services_path'])) {
                $dest = base_path($jsonData['services_path'] ?? 'app/Services/Plugin');
                $pluginServicesSrc = $pluginBasePath . '/src/Services';
                
                if ($fs->isDirectory($pluginServicesSrc) && $fs->isDirectory($dest)) {
                    $serviceFiles = $fs->allFiles($pluginServicesSrc);
                    
                    foreach ($serviceFiles as $file) {
                        $relativePath = $file->getRelativePathname();
                        $targetFile = $dest . '/' . $relativePath;
                        
                        if ($fs->exists($targetFile)) {
                            $fs->delete($targetFile);
                            Log::info("Deleted service file: " . $targetFile);
                        }
                    }
                    
                    Log::info("Removed plugin Services files");
                }
            }


            // ---- NEW FEATURE: Remove Route file and its registration ----
            $routesPath = base_path('routes');
            $pluginRouteFile = $routesPath . '/' . $pluginName . '.php';

            if ($fs->exists($pluginRouteFile)) {
                $fs->delete($pluginRouteFile);
            }

            // ---- NEW FEATURE: Remove HTML Views from Frontend Directory ----
            $frontendDest = getConfigValue('Admin_Dir');
            // if ($frontendDest) {
            //     // Assuming plugin views are in a folder named after the plugin inside Admin_Dir
            //     $pluginHtmlPath = $frontendDest . '/' . $pluginName. '.html';
            //     if ($fs->exists($pluginHtmlPath)) {
            //         $fs->delete($pluginHtmlPath);
            //         Log::info("Deleted plugin views from: " . $pluginHtmlPath);
            //     } else {
            //         Log::warning("No plugin views found at: " . $pluginHtmlPath);
            //     }

            //     // Assuming plugin views are in a folder named after the plugin inside Admin_Dir
            //     $jsPath = $frontendDest . '/assets/js/pages/' . $pluginName. '.js';
            //     if ($fs->exists($jsPath)) {
            //         $fs->delete($jsPath);
            //         Log::info("Deleted plugin views from: " . $jsPath);
            //     } else {
            //         Log::warning("No plugin views found at: " . $jsPath);
            //     }
            // }
            if ($frontendDest) {
                // Check if plugin has HTML files in its structure
                $pluginHtmlSourcePath = $pluginBasePath . '/views'; // or wherever HTML files are in the zip
                
                if ($fs->isDirectory($pluginHtmlSourcePath)) {
                    $htmlFiles = $fs->files($pluginHtmlSourcePath);
                    foreach ($htmlFiles as $htmlFile) {
                        $htmlFileName = $htmlFile->getFilename();
                        $targetHtmlPath = $frontendDest . '/' . $htmlFileName;
                        if ($fs->exists($targetHtmlPath)) {
                            $fs->delete($targetHtmlPath);
                            Log::info("Deleted plugin HTML file: " . $targetHtmlPath);
                        }
                    }
                }
                
                // Check if plugin has JS files in its structure
                $pluginJsSourcePath = $pluginBasePath . '/views/js'; // or wherever JS files are in the zip
                
                if ($fs->isDirectory($pluginJsSourcePath)) {
                    $jsFiles = $fs->files($pluginJsSourcePath);
                    foreach ($jsFiles as $jsFile) {
                        $jsFileName = $jsFile->getFilename();
                        $targetJsPath = $frontendDest . '/assets/js/' . $jsFileName;
                        
                        if ($fs->exists($targetJsPath)) {
                            $fs->delete($targetJsPath);
                            Log::info("Deleted plugin JS file: " . $targetJsPath);
                        }
                    }
                }

                // ---- NEW FEATURE: Remove Plugin CSS Files ----
                $cssDest = getConfigValue('Admin_Dir') . '/assets/css/';
                $pluginCssSrc = $pluginBasePath . '/views/css';

                if ($fs->isDirectory($pluginCssSrc) && $fs->isDirectory($cssDest)) {
                    $cssFiles = $fs->files($pluginCssSrc);

                    foreach ($cssFiles as $file) {
                        $targetFile = $cssDest . '/' . $file->getFilename();
                        if ($fs->exists($targetFile)) {
                            $fs->delete($targetFile);
                            Log::info("Deleted plugin CSS file: " . $targetFile);
                        }
                    }
                }

            }

            // ---- NEW FEATURE: Remove Plugin PDF Views ----
            if (!empty($jsonData['pdf_view_path'])) {

                $pdfDest = base_path($jsonData['pdf_view_path']);
                $pluginPdfSrc = $pluginBasePath . '/resources/views/pdf';

                if ($fs->isDirectory($pluginPdfSrc) && $fs->isDirectory($pdfDest)) {

                    $pdfFiles = $fs->files($pluginPdfSrc);

                    foreach ($pdfFiles as $file) {
                        $targetFile = $pdfDest . '/' . $file->getFilename();

                        if ($fs->exists($targetFile)) {
                            $fs->delete($targetFile);
                            Log::info("Deleted PDF view: " . $targetFile);
                        }
                    }
                }
            }

             // ---- NEW FEATURE: Remove Plugin EMail Template Views ----
           
            if (!empty($jsonData['emailtemplate_view_path'])) {

                $emailTemplateDest = base_path($jsonData['emailtemplate_view_path']);
                $pluginemailTemplateSrc = $pluginBasePath . '/resources/views/emailtemplate';

                if ($fs->isDirectory($pluginemailTemplateSrc) && $fs->isDirectory($emailTemplateDest)) {

                    $emailTemplateFiles = $fs->files($pluginemailTemplateSrc);

                    foreach ($emailTemplateFiles as $file) {
                        $targetFile = $emailTemplateDest . '/' . $file->getFilename();

                        if ($fs->exists($targetFile)) {
                            $fs->delete($targetFile);
                            Log::info("Deleted Email Template view: " . $targetFile);
                        }
                    }
                }
            }


            // ---- NEW FEATURE: Remove Custom Frontend Files ----
            if (!empty($jsonData['custom_frontend'])) {
                $customFrontendDest = getConfigValue('Frontend_Dir');
                $pluginCustomFrontendSrc = $pluginBasePath . '/custom_frontend';
                
                if ($fs->isDirectory($pluginCustomFrontendSrc) && $fs->isDirectory($customFrontendDest)) {
                    // Get all files from the plugin's custom_frontend directory
                    $allFiles = $fs->allFiles($pluginCustomFrontendSrc);
                    
                    foreach ($allFiles as $file) {
                        // Calculate relative path from plugin's custom_frontend directory
                        $relativePath = $file->getRelativePathname();
                        $targetFile = $customFrontendDest . $relativePath;
                        if ($fs->exists($targetFile)) {
                            $fs->delete($targetFile);
                            Log::info("Deleted custom frontend file: " . $targetFile);
                        }
                        
                        // Clean up empty directories
                        $targetDir = dirname($targetFile);
                        if ($fs->isDirectory($targetDir) && count($fs->files($targetDir)) === 0 && count($fs->directories($targetDir)) === 0) {
                            $fs->deleteDirectory($targetDir);
                            Log::info("Removed empty directory: " . $targetDir);
                        }
                    }
                    
                    Log::info("Custom frontend files removed from: " . $customFrontendDest);
                }
            }


            // ---- NEW FEATURE: Remove Plugin Icon from Frontend ----
            $iconPath = null;
            if ($plugin->icon) {
                $iconPath = getConfigValue('Admin_Dir') . '/assets/images/plugin/' . basename($plugin->icon);
                if (File::exists($iconPath)) {
                    File::delete($iconPath);
                    Log::info("Deleted plugin icon from: " . $iconPath);
                } else {
                    Log::warning("Plugin icon not found at: " . $iconPath);
                }
            }

            // ---- REMOVE MENUS ADDED BY PLUGIN ----
            if (!empty($jsonData['menus'])) {

                foreach ($jsonData['menus'] as $menuData) {

                    // Always locate parent menu by name
                    $parentMenu = Menu::where('name', $menuData['name'])->first();

                    if ($parentMenu) {

                        // Delete child menus first
                        Menu::where('parent_id', $parentMenu->id)->delete();

                        // Delete the parent menu
                        $parentMenu->delete();
                    }
                }
            }

            // ---- NEW FEATURE: Remove Page Types ----
            if(!empty($jsonData['page_types'])){
                foreach ($jsonData['page_types'] as $pageTypeData) {
                    PageType::where('page_type', $pageTypeData['page_type'])->delete();
                }
            }


            // Remove extracted plugin folder
            if (File::exists($pluginBasePath)) {
                // dd($pluginBasePath);
                File::deleteDirectory($pluginBasePath);
            }

            // Remove plugin zip file
            if (File::exists($extractPathZip)) {
                // dd($extractPathZip);
                File::delete($extractPathZip);
            }
           


            // Finally remove from DB
            $plugin->delete();

            return response()->json([
                'status_code' => 200,
                'message' => 'Plugin uninstalled successfully',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status_code' => 500,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }
}
