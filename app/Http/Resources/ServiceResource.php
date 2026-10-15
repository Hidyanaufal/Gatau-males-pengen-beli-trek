<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
            'unit' => $this->unit->value,
            'unit_label' => $this->unit->label(),
            'estimated_hours' => $this->estimated_hours,
        ];
    }
}
