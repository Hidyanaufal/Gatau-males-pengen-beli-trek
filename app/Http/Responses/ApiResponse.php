<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Format respons JSON yang konsisten untuk seluruh API.
 *
 * Sukses : { "success": true,  "message": "...", "data": ... }
 * Gagal  : { "success": false, "message": "...", "errors": {...} (opsional) }
 */
class ApiResponse
{
    public static function success(mixed $data = null, string $message = 'OK', int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    public static function resource(JsonResource $resource, string $message = 'OK', int $status = 200): JsonResponse
    {
        return self::success($resource->resolve(), $message, $status);
    }

    public static function paginated(LengthAwarePaginator $paginator, string $resourceClass, string $message = 'OK'): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $resourceClass::collection($paginator->getCollection())->resolve(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    public static function error(string $message, int $status = 400, mixed $errors = null): JsonResponse
    {
        $body = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== null) {
            $body['errors'] = $errors;
        }

        return response()->json($body, $status);
    }
}
