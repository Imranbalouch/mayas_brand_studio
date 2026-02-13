<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PageType;
use Symfony\Component\HttpFoundation\Response;

class PageTypeController extends Controller
{
    /**
     * Get all page types
     */
    public function get_page_type()
    {
        try {
            $pageTypes = PageType::orderBy('id', 'desc')->get();

            return response()->json([
                'status_code' => Response::HTTP_OK,
                'data' => $pageTypes
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            dd($e);
            return response()->json([
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'Server error'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
