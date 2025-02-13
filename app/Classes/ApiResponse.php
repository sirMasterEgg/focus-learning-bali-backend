<?php

namespace App\Classes;

use Symfony\Component\HttpFoundation\Response;

class ApiResponse
{
    public static function sendResponse($result, $message, $code = Response::HTTP_OK): \Illuminate\Http\JsonResponse
    {
        $response = [];

        if (!empty($message)) {
            $response['message'] = $message;
        }

        $response['data'] = $result;


        return response()->json($response, $code);
    }
}
