<?php

namespace App\Http\Controllers\API\Ecommerce;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Symfony\Component\HttpFoundation\Response;
use Mail;
use Auth;
use Session;
use Hash;
use DB;
use App\Models\Brand;
use App\Models\Brand_translation;
use App\Models\Menu;
use App\Models\Permission_assign;
use App\Models\Language;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon; 
use Illuminate\Support\Str;
use App\Services\PermissionService;
use App\Traits\MessageTrait;
use DeepCopy\f001\B;

class ApiBrandController extends Controller
{
    use MessageTrait;
   

    public function get_brand($lang = "")
    {
        try {
            // Check if the requested language exists in the languages table
            $language_exists = Language::where('app_language_code', $lang)->exists();

            // If the language doesn't exist, default to English ('en')
            if (!$language_exists) {
                $lang = 'en';
            }

            // Get all brands with status = 1
            $brands = Brand::where('status', '1')->get();

            // If no active brands are found, return not found response
            if ($brands->isEmpty()) {
                return response()->json([
                    'status_code' => Response::HTTP_NOT_FOUND,
                    'message' => $this->get_message('not_found'),
                ], Response::HTTP_NOT_FOUND);
            }

            $response = [];
            foreach ($brands as $brand) {
                // Try to get the translation for the requested language
                $translation = Brand_translation::where('brand_id', $brand->id)
                    ->where('brand_translations.status', '1')
                    ->join('languages', 'brand_translations.language_id', '=', 'languages.id')
                    ->where('languages.app_language_code', $lang)
                    ->select(
                        'languages.app_language_code as language_code',
                        'languages.name as language_name',
                        'languages.flag as flag',
                        'languages.rtl as dir',
                        'brand_translations.brand as translate_brand',
                        'brand_translations.logo as translate_logo',
                        'brand_translations.description as translate_description',
                        'brand_translations.meta_title as translate_meta_title',
                        'brand_translations.meta_description as translate_meta_description'
                    )
                    ->first();

                // If no translation is found, fall back to English
                if (!$translation) {
                    $translation = Brand_translation::where('brand_id', $brand->id)
                        ->where('brand_translations.status', '1')
                        ->join('languages', 'brand_translations.language_id', '=', 'languages.id')
                        ->where('languages.app_language_code', 'en')
                        ->select(
                            'languages.app_language_code as language_code',
                            'languages.name as language_name',
                            'languages.flag as flag',
                            'languages.rtl as dir',
                            'brand_translations.brand as translate_brand',
                            'brand_translations.logo as translate_logo',
                            'brand_translations.description as translate_description',
                            'brand_translations.meta_title as translate_meta_title',
                            'brand_translations.meta_description as translate_meta_description'
                        )
                        ->first();
                }

                // Prepare the response structure
                $response[] = [
                    'uuid' => $brand->uuid,
                    'brand' => $brand->brand,
                    'slug' => $brand->slug,
                    'logo' => env('APP_ASSET_PATH').$brand->logo,
                    'order_level' => $brand->order_level,
                    'description' => $brand->description,
                    'meta_title' => $brand->meta_title,
                    'meta_description' => $brand->meta_description,
                    'og_title' => $brand->og_title,
                    'og_description' => $brand->og_description,
                    'og_image' => $brand->og_image,
                    'x_title' => $brand->x_title,
                    'x_description' => $brand->x_description,
                    'x_image' => $brand->x_image,
                    'auth_id' => $brand->auth_id,
                    'status' => $brand->status,
                    'featured' => $brand->featured,
                    'created_at' => $brand->created_at,
                    'updated_at' => $brand->updated_at,
                    'language_code' => $translation->language_code ?? null,
                    'language_name' => $translation->language_name ?? null,
                    // 'flag' => env('APP_ASSET_PATH').$translation->flag ?? null,
                    'dir' => $translation->dir ?? null,
                    'translate_brand' => $translation->translate_brand ?? $brand->brand,
                    // 'translate_logo' => $translation->translate_logo ?? $brand->logo,
                    // 'translate_description' => $translation->translate_description ?? $brand->description,
                    // 'translate_meta_title' => $translation->translate_meta_title ?? $brand->meta_title,
                    // 'translate_meta_description' => $translation->translate_meta_description ?? $brand->meta_description,
                    'lang' => $translation->language_code ?? 'en',
                ];
            }

            // Return the structured response
            return response()->json([
                'status_code' => Response::HTTP_OK,
                'data' => $response,
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            // Handle any exception that occurs during the process
            return response()->json([
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    
    public function get_featured_brand($lang = "")
    {
        try {
            // Check if the requested language exists in the languages table
            $language_exists = Language::where('app_language_code', $lang)->exists();

            // If the language doesn't exist, default to English ('en')
            if (!$language_exists) {
                $lang = 'en';
            }

            // Get all brands with status = 1
            $brands = Brand::where('status', '1')->where('featured', '1')->get();

            // If no active brands are found, return not found response
            if ($brands->isEmpty()) {
                return response()->json([
                    'status_code' => Response::HTTP_NOT_FOUND,
                    'message' => $this->get_message('not_found'),
                ], Response::HTTP_NOT_FOUND);
            }

            $response = [];
            foreach ($brands as $brand) {
                // Try to get the translation for the requested language
                $translation = Brand_translation::where('brand_id', $brand->id)
                    ->where('brand_translations.status', '1')
                    ->join('languages', 'brand_translations.language_id', '=', 'languages.id')
                    ->where('languages.app_language_code', $lang)
                    ->select(
                        'languages.app_language_code as language_code',
                        'languages.name as language_name',
                        'languages.flag as flag',
                        'languages.rtl as dir',
                        'brand_translations.brand as translate_brand',
                        'brand_translations.logo as translate_logo',
                        'brand_translations.description as translate_description',
                        'brand_translations.meta_title as translate_meta_title',
                        'brand_translations.meta_description as translate_meta_description'
                    )
                    ->first();

                // If no translation is found, fall back to English
                if (!$translation) {
                    $translation = Brand_translation::where('brand_id', $brand->id)
                        ->where('brand_translations.status', '1')
                        ->join('languages', 'brand_translations.language_id', '=', 'languages.id')
                        ->where('languages.app_language_code', 'en')
                        ->select(
                            'languages.app_language_code as language_code',
                            'languages.name as language_name',
                            'languages.flag as flag',
                            'languages.rtl as dir',
                            'brand_translations.brand as translate_brand',
                            'brand_translations.logo as translate_logo',
                            'brand_translations.description as translate_description',
                            'brand_translations.meta_title as translate_meta_title',
                            'brand_translations.meta_description as translate_meta_description'
                        )
                        ->first();
                }

                // Prepare the response structure
                $response[] = [
                    'uuid' => $brand->uuid,
                    'brand' => $brand->brand,
                    'slug' => $brand->slug,
                    'logo' => env('APP_ASSET_PATH').$brand->logo,
                    'order_level' => $brand->order_level,
                    'description' => $brand->description,
                    'meta_title' => $brand->meta_title,
                    'meta_description' => $brand->meta_description,
                    'og_title' => $brand->og_title,
                    'og_description' => $brand->og_description,
                    'og_image' => $brand->og_image,
                    'x_title' => $brand->x_title,
                    'x_description' => $brand->x_description,
                    'x_image' => $brand->x_image,
                    'auth_id' => $brand->auth_id,
                    'status' => $brand->status,
                    'featured' => $brand->featured,
                    'created_at' => $brand->created_at,
                    'updated_at' => $brand->updated_at,
                    'language_code' => $translation->language_code ?? null,
                    'language_name' => $translation->language_name ?? null,
                    // 'flag' => env('APP_ASSET_PATH').$translation->flag ?? null,
                    'dir' => $translation->dir ?? null,
                    'translate_brand' => $translation->translate_brand ?? $brand->brand,
                    // 'translate_logo' => $translation->translate_logo ?? $brand->logo,
                    // 'translate_description' => $translation->translate_description ?? $brand->description,
                    // 'translate_meta_title' => $translation->translate_meta_title ?? $brand->meta_title,
                    // 'translate_meta_description' => $translation->translate_meta_description ?? $brand->meta_description,
                    'lang' => $translation->language_code ?? 'en',
                ];
            }

            // Return the structured response
            return response()->json([
                'status_code' => Response::HTTP_OK,
                'data' => $response,
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            // Handle any exception that occurs during the process
            return response()->json([
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    
}
