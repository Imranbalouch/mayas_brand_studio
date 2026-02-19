<?php

namespace App\Exports;

use App\Models\Ecommerce\Product;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ProductsExport implements FromCollection, WithHeadings
{
    protected $maxOptions = 3; // Limit to Option1, Option2, Option3

    public function collection()
    {
        $rows = [];

        $products = Product::with([
            'categories:name',
            'collections:name',
            'salesChannels:name',
            'markets:market_name',
            'country:uuid,name',
            'productStocks.warehouse:uuid,location_name',
        ])->get();

        foreach ($products as $product) {
            $choiceOptions = collect(json_decode($product->choice_options, true) ?: []);

            foreach ($product->productStocks as $stock) {
                $variantParts = explode('-', $stock->variant ?? '');
                $optionValues = [];

                foreach ($variantParts as $value) {
                    foreach ($choiceOptions as $option) {
                        if (in_array($value, $option['values'], true) && !isset($optionValues[$option['name']])) {
                            $optionValues[$option['name']] = $value;
                            break;
                        }
                    }
                }

                $optionData = [];
                for ($i = 0; $i < $this->maxOptions; $i++) {
                    $optionName = $choiceOptions[$i]['name'] ?? '';
                    $optionValue = $optionName ? ($optionValues[$optionName] ?? '') : '';
                    $optionData["Option" . ($i + 1) . " Name"] = $optionName;
                    $optionData["Option" . ($i + 1) . " Value"] = $optionValue;
                }

                $rows[] = array_merge([
                    'name' => $product->name,
                    'slug' => $product->slug, 
                    'warehouse_location' => $stock->warehouse->pluck('location_name')->join(', '),
                    'unit_price' => $stock->price ?? 0,
                    'compare_price' => $stock->compare_price ?? 0,
                    'cost_price' => $stock->cost_per_item ?? 0,
                    'current_stock' => $stock->qty ?? 0,
                    'description' => $product->description,
                    'categories' => $product->categories->pluck('name')->join(', '),
                    'weight' => $product->weight,
                    'unit' => $product->unit,
                    'meta_title' => $product->meta_title,
                    'meta_description' => $product->meta_description,
                    'vendor_2' => $product->vendor, // repeat field for your template
                    'country_name' => $product->productCountry->name ?? '',
                    'product_type' => $product->type,
                    'physical_product' => $product->physical_product_enabled,
                    'tags' => $product->tags,
                    'channels' => $product->salesChannels->pluck('name')->join(', '),
                    'markets' => $product->markets->pluck('market_name')->join(', '),
                    'hscode' => $stock->hs_code ?? '',
                    'collections' => $product->collections->pluck('name')->join(', '),
                    'thumbnail_img' => $product->thumbnail_img?getConfigValue('APP_ASSET_PATH') . $product->thumbnail_img:'',
                    'variant_image' => $product->thumbnail_img?getConfigValue('APP_ASSET_PATH') . $product->thumbnail_img:'',
                    'variant_price' => $stock->price ?? 0,
                ], $optionData);
            }
        }

        return collect($rows);
    }

    public function headings(): array
    {
        $base = [
            'name',
            'slug', 
            'warehouse_location',
            'unit_price',
            'compare_price',
            'cost_price',
            'current_stock',
            'description',
            'categories',
            'weight',
            'unit',
            'meta_title',
            'meta_description',
            'vendor_2',
            'country_name',
            'product_type',
            'physical_product',
            'tags',
            'channels',
            'markets',
            'hscode',
            'collections',
            'thumbnail_img',
            'variant_image',
            'variant_price',
        ];

        for ($i = 1; $i <= $this->maxOptions; $i++) {
            $base[] = "Option{$i} Name";
            $base[] = "Option{$i} Value";
        }

        return $base;
    }
}
