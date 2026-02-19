<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\ProductStockResource;
use App\Http\Resources\CollectionResource;
use App\Http\Resources\CategoryResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $core_discount_value = 0;
        
        // Check if discounts exist before accessing properties
        if ($this->discounts && $this->discounts->isNotEmpty()) {
            $firstDiscount = $this->discounts->first();
            $discount_type = $firstDiscount->type;
            
            if ($discount_type == 'percentage') {
                // Calculate percentage discount on unit price
                $core_discount_value = $this->unit_price * $firstDiscount->value / 100;
            } else {
                // For fixed amount discount, use the value directly
                $core_discount_value = $firstDiscount->value;
            }
        }

        // Calculate price after discount
        $priceAfterDiscount = $this->unit_price - $core_discount_value;

        $productVatAmount=0;
        $vatWithoutDiscount=0;
        // Calculate VAT on discounted price
        if(!empty($this->vat->rate) && $this->vat->rate!=null){
        $productVatAmount = $this->vat->rate * $priceAfterDiscount / 100;
        
        // Calculate VAT without discount (on original price)
        $vatWithoutDiscount = $this->vat->rate * $this->unit_price / 100;
        }

        return [
            'uuid'                  => $this->uuid,
            'name'                  => $this->name,
            'brand_name'            => $this->brand->brand ?? null,
            'brand_slug'            => $this->brand->slug ?? null,
            'thumbnail_img'         => $this->thumbnail_img,
            'images'                => $this->images ? implode(',', array_map(fn($img) => imageOrPlaceholder($img), explode(',', $this->images))) : null,
            'tags'                  => $this->tags,
            'description'           => $this->description,
            'short_description'     => $this->short_description,
            'unit_price'            => $this->unit_price,
            'compare_price'         => $this->compare_price,
            'todays_deal'           => $this->todays_deal,
            'published'             => $this->published,
            'approved'              => $this->approved,
            'stock_visibility_state'=> $this->stock_visibility_state,
            'cash_on_delivery'      => $this->cash_on_delivery,
            'featured'              => $this->featured,
            'current_stock'         => $this->current_stock,
            'unit'                  => $this->unit,
            'weight'                => $this->weight,
            'min_qty'               => $this->min_qty,
            'meta_title'            => $this->meta_title,
            'meta_description'      => $this->meta_description,
            'meta_img'              => $this->meta_img,
            'pdf'                   => $this->pdf,
            'slug'                  => $this->slug,
            'is_stock'              => $this->total_stock > 0 ? 'In Stock' : 'Out of Stock',
            'sort'                  => $this->sort,
            'type'                  => $this->type,
            'stocks'                => ProductStockResource::collection($this->whenLoaded('productStocks')),
            'firstCollection'       => new CollectionResource($this->firstCollection->first()),
            // 'collections'           => optional($this->collections)->first() ? CollectionResource::collection($this->whenLoaded('collections')) : null,
            'category'              => optional($this->categories)->first() ? new CategoryResource($this->whenLoaded('categories')->first()) : null,
            // 'categories'            => optional($this->categories)->first() ? CategoryResource::collection($this->categories) : null,
            'discounts'             => $this->whenLoaded('discounts'),
            'vat'                   => new VatResource($this->whenLoaded('vat')),
            'discount_amount'       => $core_discount_value,
            'price'                 => number_format($priceAfterDiscount + $productVatAmount, 2, '.', ''),
            'price_with_vat'        => number_format($this->unit_price + $vatWithoutDiscount, 2, '.', '') 
        ];
    }
}

