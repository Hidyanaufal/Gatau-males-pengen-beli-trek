<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreOrderRequest;
use App\Http\Resources\OrderResource;
use App\Http\Resources\PaymentResource;
use App\Http\Responses\ApiResponse;
use App\Models\Order;
use App\Services\OrderService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(private readonly OrderService $orders)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = min(max($request->integer('per_page', 10), 1), 50);

        $query = $request->user()
            ->orders()
            ->with('items')
            ->latest('ordered_at')
            ->latest('id');

        $status = $request->query('status');
        if (is_string($status) && OrderStatus::tryFrom($status)) {
            $query->where('status', $status);
        }

        return ApiResponse::paginated($query->paginate($perPage), OrderResource::class, 'Riwayat pesanan.');
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $order = $this->findOwnOrder($request, $id);

        if (! $order) {
            return ApiResponse::error('Pesanan tidak ditemukan.', 404);
        }

        return ApiResponse::resource(new OrderResource($order), 'Detail pesanan.');
    }

    public function store(StoreOrderRequest $request): JsonResponse
    {
        try {
            $order = $this->orders->create($request->user(), $request->validated());
        } catch (DomainException $e) {
            return ApiResponse::error($e->getMessage(), 422);
        }

        return ApiResponse::resource(new OrderResource($order), 'Pesanan berhasil dibuat.', 201);
    }

    public function cancel(Request $request, int $id): JsonResponse
    {
        $order = $this->findOwnOrder($request, $id);

        if (! $order) {
            return ApiResponse::error('Pesanan tidak ditemukan.', 404);
        }

        try {
            $order = $this->orders->cancel($order);
        } catch (DomainException $e) {
            return ApiResponse::error($e->getMessage(), 422);
        }

        return ApiResponse::resource(new OrderResource($order), 'Pesanan dibatalkan.');
    }

    public function payment(Request $request, int $id): JsonResponse
    {
        $order = $this->findOwnOrder($request, $id);

        if (! $order) {
            return ApiResponse::error('Pesanan tidak ditemukan.', 404);
        }

        return ApiResponse::success([
            'order_number' => $order->order_number,
            'total' => $order->total,
            'payment_status' => $order->payment_status->value,
            'payment_status_label' => $order->payment_status->label(),
            'payments' => PaymentResource::collection(
                $order->payments()->latest('id')->get()
            )->resolve(),
        ], 'Status pembayaran.');
    }

    /** Hanya pesanan milik pengguna yang login; milik orang lain dianggap tidak ada (404). */
    private function findOwnOrder(Request $request, int $id): ?Order
    {
        return $request->user()->orders()->with('items')->find($id);
    }
}
