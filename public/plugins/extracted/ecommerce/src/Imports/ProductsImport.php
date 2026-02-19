<?php

namespace App\Imports;
use App\Models\CMS\Theme;
use App\Models\Filemanager;
use App\Models\Ecommerce\Product;
use App\Models\Ecommerce\Inventory; 
use App\Models\Ecommerce\WarehouseValues; 
use App\Models\Ecommerce\Country;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;  
use Auth;
use Illuminate\Support\Facades\Log;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Illuminate\Support\Facades\Storage;
 
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Http\Request;


class ProductsImport implements ToCollection, WithHeadingRow, WithValidation
{
    use \Maatwebsite\Excel\Concerns\Importable;

    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function collection(Collection $rows)
    {
        $grouped = $rows->groupBy(function ($row) {
            return trim(strtolower($row['slug'] ?? ''));
        });

        foreach ($grouped as $slug => $groupRows) {
            $this->createOrUpdateProduct($groupRows);
        }
    }

    protected function createOrUpdateProduct(Collection $groupRows)
{
    $row = $groupRows->firstWhere('name', '!=', null) ?? $groupRows->first();

    Log::info('Starting model import', ['row' => $row]);

    try {
        if (empty($row['name']) && empty($row['slug']) && empty($row['warehouse_location'])) {
            Log::info('Skipping row due to missing name, slug, and location');
            return null;
        }

        $location = WarehouseValues::where('location_name', trim($row['warehouse_location']))->first();
        if (!$location) {
            abort(400, 'Location not found. Please add a valid location.');
        }

        $country = Country::where('name', trim($row['country_name']))->first();
        $country_id = $country ? $country->uuid : null;

        $slug = $row['slug'] ?: Str::slug($row['name']);

        // Choice Options Extraction
        $choiceMap = []; 
        foreach ($groupRows as $variantRow) {
            foreach ($variantRow as $key => $value) {
                if (Str::startsWith($key, 'option') && Str::endsWith($key, '_name') && !empty($value)) {
                    $optionNumber = Str::between($key, 'option', '_name');
                    $valueKey = 'option' . $optionNumber . '_value';

                    if (!empty($variantRow[$valueKey])) {
                        $optionName = trim($value);
                        $optionValue = trim($variantRow[$valueKey]);

                        $choiceMap[$optionName][] = $optionValue;
                    }
                }
            }
        }

        // Build choice_options array
        $choiceOptions = [];
        foreach ($choiceMap as $name => $values) {
            $choiceOptions[] = ['name' => $name, 'values' => array_values(array_unique($values))];
        }
        Log::info('choiceOptions', [
            'choice_options' => json_encode($choiceOptions),
            'variant_product' => count($choiceOptions) > 0 ? 1 : 0
        ]); 
            // $tags = !empty($row['tags']) ? json_encode(array_map('trim', explode(',', $row['tags'])), JSON_UNESCAPED_UNICODE) : json_encode([]);
            $tags = '';  

            if (!empty($row['tags'])) {
                if (is_array($row['tags'])) {
                    $tags = implode(',', $row['tags']);
                } else {
                    $tags = $row['tags'];
                }
            }
        $product = Product::updateOrCreate(['slug' => $slug], [
            'uuid' => $product->uuid ?? Str::uuid(),
            'name' => $row['name'],
            'auth_id' => Auth::user()->uuid,
            'compare_price' => $row['compare_price'] ?? 0,
            'unit_price' => $row['unit_price'] ?? 0,
            'cost_per_item' => $row['cost_price'] ?? 0,
            'weight' => $row['weight'] ?? 0,
            'current_stock' => $row['current_stock'] ?? 0,
            'description' => $row['description'] ?? null,
            'unit' => $row['unit'] ?? '',
            'meta_title' => $row['meta_title'] ?? $row['name'],
            'meta_description' => $row['meta_description'] ?? '',
            'vendor' => $row['vendor'] ?? '',
            'type' => $row['product_type'] ?? '',
            'hs_code' => $row['hscode'] ?? '',
            'country_id' => $country_id,
            'tags' => $tags,
            'published_date_time' => now()->startOfDay(),
            'physical_product_enabled' => $row['physical_product'] ? 1 : 0,
            'thumbnail_img' => $this->downloadThumbnail($row['thumbnail_img'] ?? null),
            'images' => $this->downloadThumbnail($row['thumbnail_img'] ?? null),
            'warehouse_location_id' => $location->uuid,
            'sort' => $row['sort'] ?? null,
            'shipping_type' => 'flat_rate',
            'slug' => $slug,
            'status' => $this->request->boolean('publish') ? 1 : 0,
            'choice_options' => json_encode($choiceOptions, JSON_UNESCAPED_UNICODE),
            'variant_product' => count($choiceOptions) > 0 ? 1 : 0,
        ]);

        // Sync collections, categories, channels, markets (unchanged)
        $this->syncRelations($product, $row); 
        // Handle Variants
        foreach ($groupRows as $variantRow) { 
            $vlocation = WarehouseValues::where('location_name', trim($variantRow['warehouse_location']))->first();
            if (!$vlocation) {
                abort(400, 'Location not found. Please add a valid location.'.$variantRow['warehouse_location']);
            }
        if (!empty($variantRow['option1_value']) || !empty($variantRow['option2_value'])) {
            $variant = $this->generateStandardSKU($this->buildVariantString($variantRow)); 
            $existingStock = \App\Models\ProductStock::where('product_id', $product->uuid)
                ->where('location_id',$vlocation->uuid)
                ->where('variant', $variant)
                ->first();
            }else{ 
            //Simple Products
            $variant = ''; 
            $existingStock = \App\Models\ProductStock::where('product_id', $product->uuid)
                ->where('location_id',$vlocation->uuid)
                ->where('variant', $variant)
                ->first();
        }
            
            $variantData = [
                'product_id' => $product->uuid,
                'variant' => $variant, 
                'price' => $variantRow['variant_price'] ?? $product->unit_price,
                'cost_per_item' => $product->cost_per_item,
                'compare_price' => $product->compare_price,
                'hs_code' => $product->hs_code,
                'image' => $this->downloadThumbnail($variantRow['variant_image'] ?? null),
                'sku' => $variant,
                'qty' => $variantRow['current_stock'] ?? 0,
                'location_id' => $vlocation->uuid,
                'auth_id' => Auth::user()->uuid, 
            ]; 
            if (!$existingStock) {
                $variantData['uuid'] = Str::uuid(); 
            } 
            \App\Models\ProductStock::updateOrCreate(
                ['product_id' => $product->uuid, 'variant' => $variant,'location_id'=>$vlocation->uuid],
                $variantData
            );

            $inventoryStatus = !$existingStock ? 'opening' : 'adjust';
            Inventory::create([
                'uuid' => Str::uuid(),
                'product_id' => $product->uuid,
                'stock_id' => $existingStock->uuid ?? $variantData['uuid'], // if new, we just created it
                'location_id' => $vlocation->uuid,
                'sku' => $variant, 
                'qty' => $variantRow['current_stock'] ?? 0,
                'status' => $inventoryStatus,
                'reason' => 'Item Import',
                'auth_id' => Auth::user()->uuid,
            ]);
        
    }

        return $product;

    } catch (\Exception $e) {
        Log::error('Product import failed', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'row_data' => $row
        ]);
        throw $e;
    }
}

protected function generateStandardSKU($variantName)
{
    if (empty($variantName) || !is_string($variantName)) {
        return '';
    } 
    $parts = array_filter(array_map('trim', explode('-', $variantName))); 
    sort($parts, SORT_NATURAL | SORT_FLAG_CASE); 
    return implode('-', $parts);
}

protected function buildVariantString($row)
{
    $optionPairs = [];

    foreach ($row as $key => $value) {
        if (Str::startsWith($key, 'option') && Str::endsWith($key, '_name') && !empty($value)) {
            $optionNumber = Str::between($key, 'option', '_name');
            $valueKey = 'option' . $optionNumber . '_value';

            if (!empty($row[$valueKey])) {
                $optionPairs[trim($value)] = trim($row[$valueKey]);
            }
        }
    }

    // Sort by option name for consistent order (e.g. Color, Size)
    ksort($optionPairs, SORT_NATURAL | SORT_FLAG_CASE); 
    // Return a string like: "Blue-Cotton-Small"
    return implode('-', array_values($optionPairs));
}

protected function syncRelations($product, $row)
{
    // Collections
    if (!empty($row['collections'])) {
        $collectionUuids = \App\Models\Collection::whereIn('name', array_map('trim', explode(',', $row['collections'])))->pluck('uuid')->toArray();
        $product->collections()->sync($collectionUuids);
    }

    // Categories
    if (!empty($row['categories'])) {
        $categoryUuids = \App\Models\Category::whereIn('name', array_map('trim', explode(',', $row['categories'])))->pluck('uuid')->toArray();
        $product->categories()->sync($categoryUuids);
    }

    // Channels
    if (!empty($row['channels'])) {
        $channelUuids = \App\Models\Channel::whereIn('name', array_map('trim', explode(',', $row['channels'])))->pluck('uuid')->toArray();
        $product->salesChannels()->sync($channelUuids);
    }

    // Markets
    if (!empty($row['markets'])) {
        $marketUuids = \App\Models\Market::whereIn('market_name', array_map('trim', explode(',', $row['markets'])))->pluck('uuid')->toArray();
        $product->markets()->sync($marketUuids);
    }
}  
    public function rules(): array
    {
        // Skip rules if it's likely a variant row (only Option values present)
        if (empty(request()->get('name')) && empty(request()->get('slug'))) {
            return [];
        } 
        $overwrite = [];
        if (request()->overwrite == "false") {
            $overwrite = [
                'nullable',
                Rule::unique('products', 'slug')->where(function ($query) {
                    return $query->where('name', '!=', request()->name);
                }),
            ];
        }

        return [
            'slug' => $overwrite,
            'warehouse_location' => ['required', 'string'],
            'thumbnail_img' => 'nullable|string',
            'description' => 'nullable|string',
        ];
    }

    public function prepareForValidation($data, $index)
    {
        // Trim all data values
        $cleaned = array_map(function ($value) {
            return is_string($value) ? trim($value) : $value;
        }, $data);

        // Log the headers once for debug
        if ($index === 0) {
             Log::info('CSV Headers Detected:', array_keys($cleaned));
        }

        return $cleaned;
    }

    public function customValidationMessages()
    {
        return [
            'code.unique' => 'The product code must be unique. The code already exists.',
            'sku.unique' => 'The SKU must be unique. The SKU already exists in the system.',
            'warehouse_location.required' => 'Warehouse location is required for inventory tracking.',
        ];
    }

    protected function generateUniqueSlug($slug)
    {
        $originalSlug = $slug;
        $count = 0; 
        while (Product::where('slug', $slug)->exists()) {
            $count++;
            $slug = $originalSlug . '-' . $count;
        } 
        return $slug;
    }
    
    public function downloadThumbnail($url)
    {
        if (empty($url)) {
            return null;
        } 
        try {
            $urlPath = parse_url($url, PHP_URL_PATH);
            $extension = pathinfo($urlPath, PATHINFO_EXTENSION);
            $file_original_name  = null;
            $validExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg'];
            if (empty($extension) || !in_array(strtolower($extension), $validExtensions)) {
                $headers = get_headers($url, 1);
                $contentType = $headers['Content-Type'] ?? ''; 
                if (strpos($contentType, 'image/jpeg') !== false) {
                    $extension = 'jpg';
                } elseif (strpos($contentType, 'image/png') !== false) {
                    $extension = 'png';
                } elseif (strpos($contentType, 'image/webp') !== false) {
                    $extension = 'webp';
                } elseif (strpos($contentType, 'image/gif') !== false) {
                    $extension = 'gif';
                } elseif (strpos($contentType, 'image/svg+xml') !== false) {
                    $extension = 'svg';
                } else {
                    $extension = 'jpg';
                }
            }
            $type = array(
                "jpg" => "image",
                "jpeg" => "image",
                "png" => "image",
                "svg" => "image",
                "ico" => "image",
                "webp" => "image",
                "gif" => "image",
                "mp4" => "video",
                "mpg" => "video",
                "mpeg" => "video",
                "webm" => "video",
                "ogg" => "video",
                "avi" => "video",
                "mov" => "video",
                "flv" => "video",
                "mkv" => "video",
                "wmv" => "video",
                "wma" => "audio",
                "aac" => "audio",
                "wav" => "audio",
                "mp3" => "audio",
                "zip" => "archive",
                "rar" => "archive",
                "7z" => "archive",
                "doc" => "document",
                "txt" => "document",
                "docx" => "document",
                "pdf" => "document",
                "csv" => "document",
                "xml" => "document",
                "ods" => "document",
                "xlr" => "document",
                "xls" => "document",
                "xlsx" => "document",
                'css' => 'css',
                'ttf' => 'css',
                'min.css' => 'css',
                'bundle.min.css' => 'css',
                'js' => 'js',
                'min.js' => 'js',
                'bundle.min.js' => 'js',
                'woff2' => 'font',
                'woff' => 'font',
            );
            $extension = strtolower($extension); 
            $imageContent = file_get_contents($url);
            if (!$imageContent) {
                Log::error('Image download failed for URL: ' . $url);
                return null;
            } 
            $filename = Str::random(32) . '.' . $extension;
            $theme = Theme::where('status', 1)->first();
            $path = 'uploads/all/' .$theme->theme_path.'/'. $filename; 
            Storage::disk('local')->put($path, $imageContent);
            $localPath = Storage::disk('local')->path($path); 
            $size = filesize($localPath); 
            if ($extension != 'svg') {
                try {
                    $img =  new ImageManager(new Driver());
                    $image = $img->read($localPath);
                    $height = $image->height();
                    $width = $image->width(); 
                    $image->save(base_path('public/') . $path);
                    clearstatcache();
                    $size = filesize(base_path('public/') . $path); // Get the file size
                } catch (\Exception $e) {
                    Log::error('Image optimization error: ' . $e->getMessage());
                }
            } 
           
            $file_original_name=pathinfo($urlPath, PATHINFO_BASENAME) ?: 'product_image';
            $upload=Filemanager::create([
                'uuid' => Str::uuid(),
                'theme_id' => $theme ? $theme->uuid : null,
                'theme_path' => $theme ? $theme->theme_path : null,
                'file_original_name' => $file_original_name,
                'extension' => $extension,
                'file_name' => $path,
                'created_by' => Auth::user()->id,
                'type' => $type[$extension],
                'file_size' => $size,
                'height' => $height, // Save height in pixels
                'width' => $width    // Save width in pixels
            ]); 
            return $upload->file_name;
        } catch (\Exception $e) {
            Log::error('Thumbnail download error: ' . $e->getMessage());
            return null;
        }
    }

     
}