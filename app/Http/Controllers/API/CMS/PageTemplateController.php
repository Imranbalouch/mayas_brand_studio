<?php

namespace App\Http\Controllers\API\CMS;

use Illuminate\Support\Str;
use App\Traits\MessageTrait;
use Illuminate\Http\Request;
use App\Models\BussinessSetting;
use App\Models\CMS\PageTemplate;
use App\Services\PageUpdateService;
use App\Services\PermissionService;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class PageTemplateController extends Controller
{
    use MessageTrait;

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
    public function index(Request $request)
    {
        //
        $menuUuid = $request->header('menu-uuid');
        $permissions = $this->permissionService->checkPermissions($menuUuid);
        $pageTemplates = PageTemplate::where('theme_uuid',request()->header('theme-id'))->get();
        return response()->json([
            'status_code'=>200,
            'permissions' => $permissions,
            'data'=>$pageTemplates
        ],200);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
        $api_url = BussinessSetting::where('type','api_base_url')->first()->value ?? null;
        return response()->json([
            'status_code'=>200,
            'api_url'=>$api_url
        ],200);
        
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'product_slug' => 'nullable|string|max:255',
                'page_type' => 'required|in:product_detail,cart,checkout,thankyou,address,login,signup,dashboard,order,order_detail,forget_password,reset_password,customer_profile,wishlist,product_listing,track_order',
                'api_url' => 'nullable|string|max:255',
                'product_class' => 'nullable|string|max:255',
                'html_variant' => 'nullable',
                'product_cart_html' => 'nullable|string',
                'page_html' => 'required|string',
            ]);
    
            if ($validator->fails()) {
                return response()->json([
                    'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                    'errors' => $validator->messages(),
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
    
            // Check if a record with the same theme_uuid and page_type already exists
            $existingTemplate = PageTemplate::where('theme_uuid', $request->header('theme-id'))
                ->where('page_type', $request->page_type)
                ->first();
    
            if ($existingTemplate) {
                return response()->json([
                    'status_code' => Response::HTTP_CONFLICT,
                    'message' => 'A template with this page type already exists for the theme.',
                ], Response::HTTP_CONFLICT);
            }
    
            $shortkey = strtolower(str_replace(' ', '_', $request->name));
    
            $data = [
                'uuid' => Str::uuid(),
                'auth_id' => Auth::user()->uuid,
                'theme_uuid' => $request->header('theme-id'),
                'name' => $request->name,
                'product_slug' => $request->product_slug,
                'shortkey' => $shortkey,
                'page_type' => $request->page_type,
                'api_url' => $request->api_url,
                'product_class' => $request->product_class,
                'html_variant' => $request->html_variant,
                'product_cart_html' => $request->product_cart_html,
                'product_cart_slider_html' => $request->product_cart_slider_html,
                'page_html' => $request->page_html,
            ];
    
            PageTemplate::create($data);
    
            return response()->json([
                'status_code' => 200,
                'message' => 'Page template saved successfully',
            ], 200);
    
        } catch (\Throwable $th) {
            return response()->json([
                'status_code' => 500,
                'message' => 'An error occurred while saving the page template.',
                'error' => $th->getMessage()
            ], 500);
        }
    }
    

    /**
     * Display the specified resource.
     */
      public function show(Request $request)
    {
        $productTemplate = PageTemplate::where('theme_uuid', request()->header('theme-id'))
            ->where('page_type', $request->page_type)
            ->first();
        try{
            if ($productTemplate != null) {
                $shortkey = $productTemplate->shortkey;
                return response()->json([
                   'status_code' => 200,
                   'data' => "<shortcode>[insta-manage-".$shortkey."][/insta-manage-".$shortkey."]</shortcode>"
                ],200);
            }else{
                return response()->json([
                    'status_code' => 404,
                    'message' => "Product Design Page not found"
                 ],404);
            }
        } catch (\Throwable $th) {
            Log::error($th->getMessage());
            return response()->json([
                'status_code' => 500,
                'message' => $th->getMessage(),
            ]);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $uuid)
    {
        try {
            
            $page_template = PageTemplate::where('uuid', $uuid)->first();
            $page_template_translation = PageTemplate::where('uuid', $uuid)->first();

            if($page_template)
            {

                return response()->json([

                    'status_code' => Response::HTTP_OK,
                    'data' => $page_template,

                ], Response::HTTP_OK);


            }else{

                return response()->json([

                    'status_code' => Response::HTTP_NOT_FOUND,
                    'message' => $this->get_message('not_found'),

                ], Response::HTTP_NOT_FOUND);

            }

        
        }catch (\Exception $e) { 
            // Handle general exceptions
            return response()->json([

                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $this->get_message('server_error'),

            ], Response::HTTP_INTERNAL_SERVER_ERROR); // 500 Internal Server Error
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $uuid)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'product_slug' => 'nullable|string|max:255',
                'page_type' => 'required|in:product_detail,cart,checkout,thankyou,address,login,signup,dashboard,order,order_detail,forget_password,reset_password,customer_profile,wishlist,product_listing,track_order',
                'api_url' => 'nullable|string|max:255',
                'product_class' => 'nullable|string|max:255',
                'html_variant' => 'nullable',
                'product_cart_html' => 'nullable|string',
                'page_html' => 'required|string',
            ]);
    
            if ($validator->fails()) {
                return response()->json([
                    'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                    'errors' => $validator->messages(),
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
    
            $pageTemplate = PageTemplate::where('uuid', $uuid)->first();
    
            if (!$pageTemplate) {
                return response()->json([
                    'status_code' => Response::HTTP_NOT_FOUND,
                    'message' => $this->get_message('not_found'),
                ], Response::HTTP_NOT_FOUND);
            }
    
            // Check if page_type is being changed, and if so, make sure no duplicate exists
            if ($request->page_type !== $pageTemplate->page_type) {
                $existingTemplate = PageTemplate::where('theme_uuid', $pageTemplate->theme_uuid)
                    ->where('page_type', $request->page_type)
                    ->where('uuid', '!=', $uuid)
                    ->first();
    
                if ($existingTemplate) {
                    return response()->json([
                        'status_code' => Response::HTTP_CONFLICT,
                        'message' => 'A template with this page type already exists for the theme.',
                    ], Response::HTTP_CONFLICT);
                }
            }
    
            $shortkey = strtolower(str_replace(' ', '_', $request->name));    
            $pageTemplate->update([
                'name' => $request->name,
                'product_slug' => $request->product_slug,
                'shortkey' => $shortkey,
                'page_type' => $request->page_type,
                'api_url' => $request->api_url,
                'product_class' => $request->product_class,
                'html_variant' => $request->html_variant,
                'product_cart_html' => $request->product_cart_html,
                'product_cart_slider_html' => $request->product_cart_slider_html,
                'page_html' => $request->page_html,
            ]);

            $pages = Page::where('theme_id', $pageTemplate->theme_uuid)->pluck('slug')->toArray();
            if (count($pages) > 0) {
                foreach ($pages as $key => $slug) {
                    $this->pageUpdateService->updatePage($slug);
                }
            }
            return response()->json([
                'status_code' => Response::HTTP_OK,
                'message' => 'Page template updated successfully',
            ], Response::HTTP_OK);
    
        } catch (\Throwable $th) {
            return response()->json([
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'An error occurred while updating the page template.',
                'error' => $th->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $uuid)
    {
        try {
            $pageTemplate = PageTemplate::where('uuid', $uuid)->first();

            if (!$pageTemplate) {
                return response()->json([
                    'status_code' => Response::HTTP_NOT_FOUND,
                    'message' => $this->get_message('not_found'),
                ], Response::HTTP_NOT_FOUND);
            }

            $pageTemplate->delete();

            return response()->json([
                'status_code' => Response::HTTP_OK,
                'message' => 'Page template deleted successfully',
            ], Response::HTTP_OK);

        } catch (\Throwable $th) {
            return response()->json([
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'An error occurred while deleting the page template.',
                'error' => $th->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

     public function update_page_template_status(Request $request, string $id)
    {
        $request->validate([
            'status' => 'required|in:0,1', // Ensure status is either 0 or 1
        ]);

        try {
            
            $page_template = PageTemplate::where('uuid', $id)->first();

            if ($page_template) {

                // Update the status
                $page_template->status = $request->status;
                $page_template->save();

                return response()->json([
                    'status_code' => 200,
                    'message' => $this->get_message('update'),
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
