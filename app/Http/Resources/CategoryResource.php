<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
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
            'uuid' => $this->uuid,
            'name' => $this->name,
            'slug' => $this->slug,
            'banner' => imageOrPlaceholder($this->banner),
            'icon' => imageOrPlaceholder($this->icon),
        ];
    }
}
