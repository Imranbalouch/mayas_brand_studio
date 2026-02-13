<?php

namespace App\Http\Controllers\API\Plugin;

use App\Http\Controllers\Controller;
use App\Models\CMS\Theme;
use App\Models\Plugin\SMTP;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class SMTPController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        $smtp = SMTP::first();

        return response()->json([
            'status_code' => Response::HTTP_OK,
            'data' => $smtp
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
        try {
            $data = $request->all();
            $validator = Validator::make($data, [
                'smtp_server' => 'required|regex:/^[a-zA-Z0-9._ ]+$/',
                'smtp_port' => 'required|numeric',
                'smtp_encryption' => 'required|in:tls,ssl,null',
                'smtp_username' => 'required',
                'smtp_password' => 'required',
                'from_email' => 'required|email',
                'from_name' => 'required|regex:/^[a-zA-Z0-9._ ]+$/',
            ]);

            if($validator->fails()) {
                $message = $validator->messages();
                return response()->json([
                    'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                    'errors' => strval($message)
                
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $data = [
                'host'=>$request->smtp_server,
                'port'=>$request->smtp_port,
                'encryption'=>$request->smtp_encryption,
                'username'=>$request->smtp_username,
                'password'=>$request->smtp_password,
                'from_address'=>$request->from_email,
                'from_name'=>$request->from_name,
            ];

            $smtp = SMTP::first();
            if($smtp) {
                $smtp->update($data);
                return response()->json([
                    'status_code'=>200,
                    'message'=>'Successfully Updated',
                ],201);
            } else {
                SMTP::create($data);
                return response()->json([
                    'status_code'=>200,
                    'message'=>'Successfully Added',
                ],201);
            }
            
        } catch (\Throwable $th) {
            Log::error($th->getMessage());
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
