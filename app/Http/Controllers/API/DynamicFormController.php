<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\CMS\Theme;
use App\Models\CMS\Module;
use App\Models\CMS\ModuleField;
use App\Models\CMS\Module_details;
use App\Models\Menu;
use App\Models\CMS\DynamicForm;
use App\Models\Permission_assign;
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


class DynamicFormController extends Controller
{
    protected $pageUpdateService;
    protected $permissionService;
    public function __construct(PageUpdateService $pageUpdateService,PermissionService $permissionService)
    {
        $this->pageUpdateService = $pageUpdateService;
        $this->permissionService = $permissionService;
    }
    /**
     * Display a listing of the resource.
    */

    use MessageTrait;


    public function getform()
    {
    
        try {
            $menuUuid = request()->header('menu-uuid');
            $permissions = $this->permissionService->checkPermissions($menuUuid);
            $get_form = DynamicForm::where('theme_id', request()->header('theme-id'));
            if ($permissions['view']) {
                if (!$permissions['viewglobal']) {
                    $get_form = $get_form->where('auth_id', Auth::user()->uuid);
                }
            }else{
                if (Auth::user()->hasPermission('viewglobal')) {
                    $get_form = $get_form;
                } else {
                    return response()->json(['message' => 'You do not have permission to view this menu'], Response::HTTP_FORBIDDEN);
                }
            }
            $get_form = $get_form->get();

            if ($get_form) {

                return response()->json([
                    
                    'status_code'=>200,
                    'permission'=>$permissions,
                    'data'=>$get_form

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



    public function addform(Request $request)
    {
         try {
            $theme_id = $request->header('theme-id');
            $theme = Theme::findByUuid($theme_id);
            if ($theme == null) {
                return response()->json([
                    'status_code'=>404,
                    'message'=>$this->get_message('not_found'),
                ], 404);
            }
            $validator = Validator::make($request->all(), [
                 'form_name' => 'required|regex:/^[a-zA-Z0-9\s]+$/u',
                 'details' => '',
                 'is_recaptcha' => '',
                 'language_code' => '',
                 'from_email' => '',
                 'to_email' => '',
                 'submission_message' => '',
                 'redirect_url' => '',
            ]);

             if($validator->fails()) {
             
                 $message = $validator->messages();
                 
                 return response()->json([
                     'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                     'errors' => strval($message)
                 
                 ], Response::HTTP_UNPROCESSABLE_ENTITY);
             }
 
             $data = [];
             $toEmail = isset($request->to_email) && count($request->to_email) > 0 ? implode(',', $request->to_email) : '';
             $data = [
                 'uuid' => Str::uuid(),
                 'theme_id'=>$theme->uuid,
                 'form_name' => $request->form_name,
                 'status' => $request->status ?? 0,
                 'details' => $request->details,
                 'short_code'=> str_replace(' ','_',strtolower($request->form_name)),
                 'is_recaptcha'=> $request->is_recaptcha,
                 'language_code'=> $request->language_code,
                 'from_email'=> $request->from_email,
                 'to_email'=> json_encode($request->to_email),
                 'submission_message'=> $request->submission_message,
                 'redirect_url'=> $request->redirect_url,
                 'auth_id' => Auth::user()->uuid,
             ];

             $dynamic_Form = DynamicForm::create($data);
             
             if($dynamic_Form){

                $filePath = base_path('resources/views/components/'.str_replace(' ','_',env('THEME_NAME')).'/forms/'.str_replace(' ','_',strtolower($request->form_name)).'.blade.php');
                $fileContent  = $request->formrender;
                $fileContent  = '<form action="javascript:void(0)" class="contactForm" method="post" id="'.$dynamic_Form->short_code.'"><input type="hidden" value="'.$dynamic_Form->uuid.'" name="form_uuid"/>'.$fileContent.'</form>';
                if (!File::exists($filePath)) {
                    // if folder not exists then create folder
                    if (!File::exists(dirname($filePath))) {
                        File::makeDirectory(dirname($filePath), 0755, true, true);
                    }
                    // Create the file with the given content
                    File::put($filePath, $fileContent);
                } else {
                    File::put($filePath, $fileContent);
                }

                return response()->json([
                    'status_code'=>200,
                    'message'=>$this->get_message('add'),
                ],201);

             }
             

         } catch (\Exception $e) {

             return response()->json([
                 'status_code'=>500,
                //  'message'=>$this->get_message('server_error'),
                 'message'=>$e->getMessage(),
             ], 500);

         }

    }


    public function editform(string $id)
    {
    
        try {
        
            $module_detail = DynamicForm::where('uuid',$id)->first();

            if ($module_detail) {

                return response()->json([
                    'status_code' => 200,
                    'data' => $module_detail,
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



    public function updateform(Request $request)
    {
        try {
            $id = request()->header('uuid');
            $theme_id = $request->header('theme-id');
            $theme = Theme::findByUuid($theme_id);
           
            if ($theme == null) {
                return response()->json([
                    'status_code'=>404,
                    'message'=>$this->get_message('not_found'),
                ], 404);
            }
            $dynamic_Form = DynamicForm::where('uuid',$id)->first();
            $dynamicOldFormName = $dynamic_Form->short_code;
            if ($dynamic_Form) {

                $dynamic_Form->form_name = $request->form_name;
                $dynamic_Form->details = $request->details;
                $dynamic_Form->short_code = str_replace(' ','_',strtolower($request->form_name));
                $dynamic_Form->is_recaptcha = $request->is_recaptcha;

                $dynamic_Form->language_code = $request->language_code;
                $dynamic_Form->from_email = $request->from_email;
                $dynamic_Form->to_email = $request->to_email;
                $dynamic_Form->submission_message = $request->submission_message;
                $dynamic_Form->redirect_url = $request->redirect_url;
                $dynamic_Form->save();

                $filePath = base_path('resources/views/components/'.str_replace(' ','_',env('THEME_NAME')).'/forms/'.$dynamic_Form->short_code.'.blade.php');
                $fileContent  = $request->formrender;
                
                $fileContent  = '<form action="javascript:void(0)" class="contactForm" method="post" id="'.$dynamic_Form->short_code.'"><input type="hidden" value="'.$dynamic_Form->uuid.'" name="form_uuid"/>'.$fileContent.'</form>';
                if (!File::exists($filePath)) {
                    // if folder not exists then create folder
                    if (!File::exists(dirname($filePath))) {
                        File::makeDirectory(dirname($filePath), 0755, true, true);
                    }
                    // Create the file with the given content
                    File::put($filePath, $fileContent);
                } else {
                    File::put($filePath, $fileContent);
                }
                if($dynamic_Form->short_code != $dynamicOldFormName){
                    $filePath = base_path('resources/views/components/'.str_replace(' ','_',strtolower(env("THEME_NAME"))).'/forms/'.$dynamicOldFormName.'.blade.php');
                    if (File::exists($filePath)) {
                        File::delete($filePath);
                    }
                }
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

                    'status_code'=>200,
                    'message'=>$this->get_message('update'),

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
                // 'message'=>$this->get_message('server_error'),
                'message'=>$e->getMessage(),
            ], 500);

        }
        
    }


    public function deleteform(string $id)
    {
    
        try {
            
            $dynamic_Form = DynamicForm::where('uuid',$id)->first();

            if ($dynamic_Form) {

                $filePath = base_path('resources/views/components/'.str_replace(' ','_',env('THEME_NAME')).'/forms/'.str_replace(' ','_',strtolower($dynamic_Form->form_name)).'.blade.php');
                if (File::exists($filePath)) {
                    File::delete($filePath);
                }

                DynamicForm::destroy($dynamic_Form->id);

                return response()->json([
                    'status_code'=>200,
                    'message'=>$this->get_message('delete'),
                ],200);

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
    
    public function updateStatus(Request $request, string $id)
    {
        try {
            $dyForm = DynamicForm::findByUuid($id);
            if ($dyForm) {
                $dyForm->status = $request->status;
                if ($dyForm->save()) {
                    return response()->json([
                        'status_code'=>200,
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

}
