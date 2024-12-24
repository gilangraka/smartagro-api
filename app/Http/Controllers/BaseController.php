<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class BaseController extends Controller
{
    public function sendResponse($result, $message = 'success', $metadata = false)
    {
        $response = [
            'success' => true,
            'message' => $message,
            'metadata' => $metadata == true ? [
                'per_page' => $result->perPage(),
                'current_page' => $result->currentPage(),
                'total_row' => $result->total(),
                'total_page' => $result->lastPage()
            ] : [],
            'data'    => $metadata == true ? $result->getCollection() : $result,
        ];
        return response()->json($response, 200);
    }

    public function sendError($errorMessage, $code = 400)
    {
        $response = [
            'success' => false,
            'message' => $errorMessage,
            'data'    => null,
        ];
        return response()->json($response, $code);
    }
}
