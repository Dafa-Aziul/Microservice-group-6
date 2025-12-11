<?php

namespace App\Traits;

use Carbon\Carbon;

trait ApiResponse
{

    protected function successResponse($data, $message = 'Success', $code = 200)
    {
        $cid = request()->attributes->get('correlation_id');

        return response()->json([
            'status' => $code,
            'message' => $message,
            'data' => $data,
            'metadata' => [
                'timestamp' => Carbon::now()->toIso8601String(),
                'correlation_id' => $cid,
            ]
        ], $code);
    }



    protected function errorResponse($message, $code = 400, $errorCode = 'gen_error', $details = [])
    {
        $cid = request()->attributes->get('correlation_id');

        return response()->json([
            'status' => $code,
            'error' => [
                'code' => $errorCode,
                'message' => $message,
                'details' => $details
            ],
            'data' => null,
            'metadata' => [
                'timestamp' => Carbon::now()->toIso8601String(),
                'correlation_id' => $cid,
            ]
        ], $code);
    }
}
