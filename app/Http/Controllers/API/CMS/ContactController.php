<?php

namespace App\Http\Controllers\API\CMS;

use App\Http\Controllers\Controller;
use App\Models\CMS\ContactUs;
use App\Models\CMS\DynamicForm;
use App\Services\PermissionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ContactController extends Controller
{

    protected $permissionService;
    public function __construct(PermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $menuUuid = $request->header('menu-uuid');
            $permissions = $this->permissionService->checkPermissions($menuUuid);
            
            $dynamicForms = DynamicForm::select('uuid','form_name','theme_id','status')->whereHas('theme', function ($q) {
                $q->where('status', 1);
            })->orderBy('created_at','desc')->get();

            return response()->json([
                'status_code'=>200,
                'message' => 'Forms fetched successfully',
                'data' => $dynamicForms,
                'permissions'=> $permissions
            ],200);
        } catch (\Throwable $th) {
            //throw $th;
            Log::error('Error dynamicForms list:' . $th->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong while fetching contact forms',
            ], 500);
        }
    }

    /**
     * Show the form for list show contact us a new resource.
     */
    public function contactList(Request $request)
    {
        //
        try {
            $columns = []; // Initialize an array to capture column names
            $contactUs = ContactUs::where('form_id', $request->form_id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($item) use (&$columns) {
                $details = json_decode($item->details, true) ?? [];
                unset($details['template_subject']);

                // Capture column names
                $columns = array_merge($columns, array_keys($details));

                // Flatten the details into top-level
                foreach ($details as $key => $value) {
                    $item->$key = $value;
                }

                $item->form_name = $item->dynamicForm->form_name ?? '-';

                unset($item->details); // optional cleanup

                return $item;
            });
            
            return response()->json([
                'status_code'=>200,
                'data'=> $contactUs,
                'columnName'=> array_values(array_unique($columns)) // make sure it's a clean array
            ], 200);
        } catch (\Throwable $th) {
            //throw $th;
            Log::error('Error fetching contact us list: ' . $th->getMessage());
             return response()->json([
                'status_code'=>500,
                'message'=>$th->getMessage()
            ], 500);
        }
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
}
