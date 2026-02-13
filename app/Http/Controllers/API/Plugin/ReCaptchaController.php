<?php

namespace App\Http\Controllers\API\Plugin;

use App\Http\Controllers\Controller;
use App\Models\CMS\Theme;
use App\Models\Plugin\ReCaptcha;
use App\Services\PageUpdateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class ReCaptchaController extends Controller
{
    
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        $recaptcha = ReCaptcha::first();

        return response()->json([
            'status_code' => Response::HTTP_OK,
            'data' => $recaptcha
        ], Response::HTTP_OK);
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
            $validatedData = Validator::make($request->all(),[
                'site_key' => 'required|string',
                'secret_key' => 'required|string',
            ]);
            if($validatedData->fails()) {
            
                $message = $validatedData->messages();
                
                return response()->json([
                    'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                    'errors' => strval($message)
                
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $data = [
              'site_key'=>$request->site_key,
              'secret_key'=>$request->secret_key
            ];
            
            $recaptcha = ReCaptcha::first();
            if($recaptcha) {
                $recaptcha->update($data);
                return response()->json([
                    'status_code'=>200,
                    'message'=>'Successfully Updated',
                ],201);
            } else {
                ReCaptcha::create($data);
                return response()->json([
                    'status_code'=>200,
                    'message'=>'Successfully Added',
                ],201);
            }
            
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return response()->json([
                'status_code'=>500,
                'message'=>'Internal Server Error',
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
