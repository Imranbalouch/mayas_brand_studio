<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductStockResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $assetPath = getConfigValue('APP_ASSET_PATH');
        return [
            'uuid'      => $this->uuid,
            'variant'   => $this->variant,
            'sku'       => $this->sku,
            'price'     => $this->price,
            'quantity'  => $this->qty,
            'image'     => (!empty($this->image) && file_exists(public_path($this->image)))
                ? $assetPath . $this->image
                : $assetPath . 'assets/images/no-image.png',
            'is_stock'  => $this->qty > 0 ? 'In Stock' : 'Out of Stock',
        ];
    }
}
