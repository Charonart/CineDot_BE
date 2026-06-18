<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FormatApiResponse
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Chỉ định dạng lại các response JSON thuộc nhóm API
        if ($request->is('api/*') && $response instanceof JsonResponse) {
            $data = $response->getData(true);
            
            // Tránh bọc 2 lần nếu data đã có format chuẩn (req, success, data)
            if (is_array($data) && isset($data['req']) && array_key_exists('data', $data) && isset($data['success'])) {
                return $response;
            }

            $success = $response->isSuccessful();
            
            if (is_array($data)) {
                // Nếu Controller đã tự set success
                if (array_key_exists('success', $data)) {
                    $success = (bool) $data['success'];
                    unset($data['success']);
                }

                // Nếu Controller đã bọc payload trong 'data'
                if (array_key_exists('data', $data)) {
                    $payloadData = $data['data'];
                } else {
                    $payloadData = empty($data) ? null : $data;
                }
            } else {
                $payloadData = $data;
            }

            $formatted = [
                'success' => $success,
                'req' => [
                    'method' => $request->method(),
                    'path' => '/' . ltrim($request->path(), '/'),
                    'query' => (object) $request->query(),
                ],
                'data' => $payloadData,
            ];

            $response->setData($formatted);
        }

        return $response;
    }
}
