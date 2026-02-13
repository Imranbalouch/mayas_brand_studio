<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\CMS\Theme;
use App\Models\Filemanager;
use App\Models\Page;
use App\Models\User;
use App\Services\PermissionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class FilemanagerController extends Controller
{
    protected $permissionService;
    public function __construct(PermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    public function index(Request $request)
    {
        try {
            $images = Filemanager::orderby('id', 'DESC');
            $menuUuid = $request->header('menu-uuid');
            $permissions = $this->permissionService->checkPermissions($menuUuid);
            if ($request->has('search')) {
                $images =  $images->where('file_original_name', 'like', '%' . $request->search . '%');
            }
            if ($permissions['view']) {
                if (!$permissions['viewglobal']) {
                    $images = $images->where('auth_id', Auth::user()->uuid);
                }
            }else{
                if (Auth::user()->hasPermission('viewglobal')) {
                    $images = $images;
                } else {
                    return response()->json(['message' => 'Invalid permission for this action'], Response::HTTP_FORBIDDEN);
                }
            }
            if ($request->has('dataType')) {
                if ($request->dataType !== 'all') {
                    $images =  $images->where('type', $request->dataType);
                }
            }
            $images = $images->paginate(12);
            return response()->json([
                'status_code'=>200,
                'new' => $add,
                'edit' => $edit,
                'update' => $update,
                'delete' => $delete,
                'view' => $view,
                'data'=>$images
            ],200);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'status_code'=>500,
               //  'message'=>$this->get_message('server_error'),
                'message'=>$th->getMessage(),
            ], 500);
        }
    }
    public function create()
    {
       
    }
    
    public function store(Request $request)
    {
        $type = array(
            "jpg" => "image",
            "jpeg" => "image",
            "png" => "image",
            "svg" => "image",
            "ico" => "image",
            "webp" => "image",
            "gif" => "image",
            "mp4" => "video",
            "mpg" => "video",
            "mpeg" => "video",
            "webm" => "video",
            "ogg" => "video",
            "avi" => "video",
            "mov" => "video",
            "flv" => "video",
            "mkv" => "video",
            "wmv" => "video",
            "wma" => "audio",
            "aac" => "audio",
            "wav" => "audio",
            "mp3" => "audio",
            "zip" => "archive",
            "rar" => "archive",
            "7z" => "archive",
            "doc" => "document",
            "txt" => "document",
            "docx" => "document",
            "pdf" => "document",
            "csv" => "document",
            "otf" => "document",
            "xml" => "document",
            "ods" => "document",
            "xlr" => "document",
            "xls" => "document",
            "xlsx" => "document",
            "glb" => "3d",
            'css' => 'css',
            'scss' => 'scss',
            'ttf' => 'css',
            'min.css' => 'css',
            'bundle.min.css' => 'css',
            'js' => 'js',
            'min.js' => 'js',
            'bundle.min.js' => 'js',
            'woff2' => 'font',
            'woff' => 'font',
        );

        $disable_image_optimization = 0;
        // $validator = Validator::make($request->all(), [
        //     'file' => 'required|mimes:' . implode(",", array_keys($type)),
        // ]);

        // if ($validator->fails()) {
        //     $message = $validator->messages();

        //     return response()->json([
        //         'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
        //         'errors' => strval($message)
        //     ], Response::HTTP_UNPROCESSABLE_ENTITY);
        // }
        $theme = Theme::where('uuid', $request->header('current-theme'))->first();
        try {
            if ($request->hasFile('file')) {
                $files = $request->file('file');
                $file_original_name  = null;
                // Get the original file name and extension
                $originalFileName = $request->file('file')->getClientOriginalName();
                $extension = strtolower($request->file('file')->getClientOriginalExtension());
                
                $arr = explode('.', $originalFileName);
                if (count($arr) > 1) {
                    $file_original_name = str_replace(end($arr),$arr[0],end($arr));
                }else {
                    return response()->json([
                        'status_code' => Response::HTTP_BAD_REQUEST,
                        'message' => 'File name must have extension'
                    ], Response::HTTP_BAD_REQUEST);
                }
                if(count($arr) > 2){
                    $extension = end($arr);
                } 
                if (isset($type[$extension])) {
                    // Store the file with the original name in the uploads folder
                    if ($theme != null) {
                        $path = $request->file('file')->storeAs('uploads/all/'.$theme->theme_path, $file_original_name.'_'.Str::uuid() . '.' . $extension, 'local');
                    }else{
                        $path = $request->file('file')->storeAs('uploads/all', $file_original_name.'_'.Str::uuid() . '.' . $extension, 'local');
                    }
                    $size = $request->file('file')->getSize();

                     // Initialize height and width
                    $height = null;
                    $width = null;

                    // Return MIME type ala mimetype extension
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);

                    // Get the MIME type of the file
                    $file_mime = finfo_file($finfo, base_path('public/') . $path);
                    // dd($originalFileName,$extension,$file_mime);
                    if ($type[$extension] == 'image' && $disable_image_optimization != 1 && $extension != 'svg'  && $extension != 'ico') {
                        try {
                            $manager = new ImageManager(new Driver());
                            $image = $manager->read($request->file('file')->getRealPath());
                            $height = $image->height();
                            $width = $image->width();
                            $image->save(base_path('public/') . $path);
                            clearstatcache();
                            $size = filesize(base_path('public/') . $path); // Get the file size
                        } catch (\Exception $e) {
                            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
                        }
                    }

                    Filemanager::create([
                        'uuid' => Str::uuid(),
                        'theme_id' => $theme ? $theme->uuid : null,
                        'theme_path' => $theme ? $theme->theme_path : null,
                        'file_original_name' => $file_original_name,
                        'extension' => $extension,
                        'file_name' => $path,
                        'created_by' => Auth::user()->id,
                        'type' => $type[$extension],
                        'file_size' => $size,
                        'height' => $height, // Save height in pixels
                        'width' => $width    // Save width in pixels
                    ]);

                    return response()->json(['status_code' => 201, 'message' => 'File uploaded successfully'], 201);
                } else {
                    return response()->json(['status_code' => 400, 'message' => 'File extension is not valid.'], 400);
                }
            }
        } catch (\Throwable $th) {
            Log::error($th->getMessage());
            return response()->json([
                'status_code' => 500,
                'message' => $th->getMessage(),
            ], 500);
        }
    }


    public function getfiles(Request $request)
    {
        $menuUuid = $request->header('menu-uuid');
        $permissions = $this->permissionService->checkPermissions($menuUuid);
        if ($request->header('current-theme')) {
            $images = Filemanager::where('theme_id',$request->header('current-theme'))->orderby('id', 'DESC');
        }else{
            $images = Filemanager::orderby('id', 'DESC');
        }
        if ($request->has('search') && $request->search != '') {
            $images =  $images->where('file_original_name', 'like', '%' . $request->search . '%');
        }
        if ($request->has('dataType') && $request->dataType != '') {
            if ($request->dataType !== 'all') {
                if ($request->dataType == 'css') {
                    $images =  $images->whereIn('type', [$request->dataType,'font']);
                }else{
                    $images =  $images->where('type', $request->dataType);
                }
            }
        }
        if ($request->has('search')) {
            $images =  $images->where('file_original_name', 'like', '%' . $request->search . '%');
        }
        if ($permissions['view']) {
            if (!$permissions['viewglobal']) {
                $images = $images->where('auth_id', Auth::user()->uuid);
            }
        }else{
            if (Auth::user()->hasPermission('viewglobal')) {
                $images = $images;
            } else {
                return response()->json(['message' => 'Invalid permission for this action'], Response::HTTP_FORBIDDEN);
            }
        }

        $images =  $images->paginate(12);
        return response()->json([
            'images'=>$images,
            'status_code' => 200,
            'permissions' => $permissions
            ]);
    }

    public function ckstore(Request $request)
    {

        $uploadedFile = $request->file('upload');

        // Generate a unique name for the file
        $fileName = uniqid() . '_' . $uploadedFile->getClientOriginalName();

        // Move the file to the public/upload/ckeditor directory
        $filePath = $uploadedFile->storeAs('/uploads/ckeditor', $fileName);

        // Get the public URL for the file
        $fileUrl = env('ASSET_URL') . $filePath;
        $function_number = $_GET['CKEditorFuncNum'];
        $message = '';
        // Return the file URL in the response
        echo "<script type='text/javascript'>window.parent.CKEDITOR.tools.callFunction($function_number,'$fileUrl','$message');</script>";
    }

    public function destroy($id)
    {
        try {
            $file = Filemanager::findByUuid($id);
            if ($file == null) {
                return response()->json(['status_code' => 404, 'message' => 'File Not Found'],404);
            }
            $unlinkFile = public_path() . '/' . $file->file_name;
            if (File::exists($unlinkFile)) {
                unlink($unlinkFile);
            }
            $file->delete();
            return response()->json(['status_code' => 200, 'message' => 'File deleted successfully'],200);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return response()->json(['status_code' => 500, 'message' =>'Something went wrong.'], 500);
        }
    }


    public function getitdone(Request $request)
    {
        $images = Filemanager::all();
        $pages = Page::orderby('id', 'DESC')->get();

         // foreach ($pages as $page) { // File Manager List
            //     // You can access the properties of each image like this:
            //     echo "Image ID: " . $page->id . "<br>";
            //     echo "Image Name: " . $page->title . "<br>"; 
            // }

        // return response()->json([
        //     'status_code'=>200, 
        //     'data'=>$images
        // ],200);

        foreach ($images as $image) { // File Manager List
            $matchingPages = Page::where('description', 'LIKE', '%' . $image->file_name . '%')->orderby('id', 'DESC')->get();

            // If there are matching pages, display them
            if ($matchingPages->isNotEmpty()) {
                echo  $image->file_name."<br> image in page description: <br>";
                foreach ($matchingPages as $page) {
                    echo "theme_id: " . $page->theme_id . "<br>";
                    echo "theme_path: " . $page->theme->theme_path . "<br><br><br>";

                    $oldFile = basename($image->file_name);
                    $newpath = "uploads/all/{$page->theme->theme_path}/{$oldFile}";
 
                        $page->description = str_replace($image->file_name,$newpath, $page->description);
                        $page->save();
                
                     
                    $this->updateFileManagerPaths($page->theme_id,$page->theme->theme_path);
                    $updatefile = Filemanager::where('file_name', $image->file_name )->update(['theme_id' =>  $page->theme_id,'theme_path' =>  $page->theme->theme_path,'file_name'=>$newpath]);
                }

            } else {
                echo "No pages found with this image in description.<br>";
            }
        }

           
    }

    function updateFileManagerPaths($themeId,$newPath)
        {
            // Fetch the active theme path
        // $themePath = DB::table('theme')->where('active', 1)->value('path'); // Adjust condition as necessary
        $themePath = \App\Models\CMS\Theme::where('uuid', $themeId)->first();
        
            if (!$themePath) {
                echo "No theme found.";
                return;
            }

            $filemanagers = Filemanager::where('theme_id', $themeId)->pluck('file_name');

            foreach ($filemanagers as $key => $value) {
                $oldFile = basename($value);
                $dirpath = public_path("uploads/all/{$newPath}/{$oldFile}");
                $destinaDir = dirname($dirpath);
                if(!is_dir($destinaDir)){
                    mkdir($destinaDir,0777,true);
                }

                if (file_exists(public_path($value))) {
                    # code...
                    move_uploaded_file(public_path($value), $dirpath);
                }
                // dd($dirpath);
                // $oldpath = public_path("uploads/all/{$oldFile}");

                // dd($oldFile,$newFile);
            }
            // Update file paths in the filemanager table
            // DB::table('filemanager')->update([
            //     'file_path' => DB::raw("CONCAT('public/uploads/all/{$themePath}/', SUBSTRING_INDEX(file_path, '/', -1))")
            // ]);

            echo "All file paths updated successfully.";
        }

    public function getselectedfile(Request $request)
    {
        $file_name = $request->fileid;
        $file = Filemanager::select('file_name','file_original_name')->where('file_name',$file_name)->first();
        return response()->json(['file'=>$file]);
    }
}
