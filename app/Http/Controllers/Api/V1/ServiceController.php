<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ServiceResource;
use App\Http\Responses\ApiResponse;
use App\Models\Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min(max($request->integer('per_page', 20), 1), 100);

        $services = Service::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->paginate($perPage);

        return ApiResponse::paginated($services, ServiceResource::class, 'Daftar layanan.');
    }

    public function show(int $id): JsonResponse
    {
        $service = Service::query()->where('is_active', true)->find($id);

        if (! $service) {
            return ApiResponse::error('Layanan tidak ditemukan.', 404);
        }

        return ApiResponse::resource(new ServiceResource($service), 'Detail layanan.');
    }
}
