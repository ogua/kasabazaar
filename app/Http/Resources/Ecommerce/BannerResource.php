<?php

namespace App\Http\Resources\Ecommerce;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Banner
 */
class BannerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'subtitle' => $this->subtitle,
            'eyebrow' => $this->eyebrow,
            'cta_label' => $this->cta_label,
            'link_url' => $this->link_url,
            'image_url' => $this->imageUrl(),
            'position' => $this->position,
            'sort_order' => $this->sort_order,
        ];
    }
}
