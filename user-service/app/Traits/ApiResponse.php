<?php

namespace App\Traits;

use Carbon\Carbon;

trait ApiResponse
{

    protected function successResponse($data, $message = 'Success', $code = 200)
    {
        return response()->json([
            'status' => $code,
            'message' => $message,
            'data' => $data,
            'metadata' => [
                'timestamp' => Carbon::now()->toIso8601String(),
            ]
        ], $code);
    }

   
    protected function errorResponse($message, $code = 400, $errorCode = 'gen_error', $details = [])
    {
        return response()->json([
            'status' => $code,
            'error' => [
                'code' => $errorCode,
                'message' => $message,
                'details' => $details
            ],
            'data' => null
        ], $code);
    }
}
