<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SaleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'venta_id' => $this->id,
            'producto_id' => $this->producto_id,
            'cantidad' => $this->cantidad,
            'cliente' => $this->cliente,
            'fecha' => $this->created_at->format('Y-m-d'),
        ];
    }
}
