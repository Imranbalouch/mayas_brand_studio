<?php
namespace App\Exports;

use App\Models\Ecommerce\Product;
use App\Models\Ecommerce\ProductStock;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ProductsExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        // Fetch all products with their related stocks and warehouse location
        $products = Product::with(['stocks', 'warehouseLocation'])->get();

        $data = new Collection();

        foreach ($products as $product) {
            foreach ($product->stocks as $stock) {
                $data->push([
                    'name' => $product->name,
                    'slug' => $product->slug,
                    'description' => $product->description,
                    'unit_price' => $product->unit_price,
                    'compare_price' => $product->compare_price,
                    'cost_price' => $product->cost_per_item,
                    'weight' => $product->weight,
                    'unit' => $product->unit,
                   // 'tags' => implode(',', json_decode($product->tags ?? '[]', true)),
                    'tags' => '"' . str_replace('"', '""', $product->tags ?? '') . '"',
                    'variant' => $stock->variant,
                    'variant_price' => $stock->price,
                    //'variant_image' => $stock->image,
                    'variant_image' => '',
                    'sku' => $stock->sku,
                    'current_stock' => $stock->qty,
                    'warehouse_location' => optional($stock->location)->location_name ?? '',
                    //'thumbnail_img' => $product->thumbnail_img, 
                    'thumbnail_img' => '',
                    'collections' => implode(',', $product->collections->pluck('name')->toArray()),
                    'categories' => implode(',', $product->categories->pluck('name')->toArray()),
                    'channels' => implode(',', $product->salesChannels->pluck('name')->toArray()),
                    'markets' => implode(',', $product->markets->pluck('market_name')->toArray()),
                    'country_name' => optional($product->country)->name,
                ]);
            }
        }

        return $data;
    }

    public function headings(): array
    {
        return [
            'name',
            'slug',
            'description',
            'unit_price',
            'compare_price',
            'cost_price',
            'weight',
            'unit',
            'tags',
            'variant',
            'variant_price',
            'variant_image',
            'sku',
            'current_stock',
            'warehouse_location',
            'thumbnail_img',
            'collections',
            'categories',
            'channels',
            'markets',
            'country_name',
        ];
    }
}
