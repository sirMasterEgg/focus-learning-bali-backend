<?php

namespace App\Classes;

use Symfony\Component\HttpFoundation\Response;

class ResponseBuilder
{
    public static function build($result, string|null $message, int $code = Response::HTTP_OK, $meta = null): \Illuminate\Http\JsonResponse
    {
        $response = [];

        if (!empty($message)) {
            $response['message'] = $message;
        }

        $response['data'] = $result;

        if ($meta) {
            $response['meta'] = $meta;
        }

        return response()->json($response, $code);
    }
}
