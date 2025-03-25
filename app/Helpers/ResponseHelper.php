<?php

namespace App\Helpers;

use Symfony\Component\HttpFoundation\Response;

class ResponseHelper
{
    /**
     * Returns success json response
     *
     * @param  String  $message
     * @param  Mixed  $payload
     * @param  Int  $status_code
     * @return \Illuminate\Http\JsonResponse
     */
    public static function withSuccess(String $message = '', $payload = [], Int $status_code = Response::HTTP_OK)
    {
        return response()->json(['status' => 'success', 'message' => $message, 'payload' => $payload], $status_code);
    }

    /**
     * Returns error json response
     *
     * @param  String  $message
     * @param  Mixed  $payload
     * @param  Int  $status_code
     * @param  Array  $headers
     * @return \Illuminate\Http\JsonResponse
     */
    public static function withError(String $message = '', $payload = [], Int $status_code = Response::HTTP_BAD_REQUEST, $headers = [])
    {
        return response()->json(['status' => 'error', 'message' => $message, 'payload' => $payload], $status_code)->withHeaders($headers);
    }

    /**
     * Returns error json response
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public static function noContent()
    {
        return response()->json([], Response::HTTP_NO_CONTENT);
    }
}
