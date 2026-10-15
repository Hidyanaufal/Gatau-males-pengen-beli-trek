<?php

namespace App\Http\Resources;

use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'payment_status' => $this->payment_status->value,
            'payment_status_label' => $this->payment_status->label(),
            'fulfillment_type' => $this->fulfillment_type,
            'total' => $this->total,
            'address' => $this->address,
            'scheduled_at' => $this->scheduled_at?->toIso8601String(),
            'notes' => $this->notes,
            'ordered_at' => $this->ordered_at?->toIso8601String(),
            'can_cancel' => OrderService::canCancel($this->resource),
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
        ];
    }
}
