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
use App\Models\Ecommerce\Category;
use App\Models\Ecommerce\CategoryTranslation;
use App\Models\Language;
use App\Models\Role;
use App\Services\PermissionService;
use App\Traits\MessageTrait;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon; 
use Illuminate\Support\Str;

class ApiCategoryController extends Controller
{
    use MessageTrait;

    public function get_category($lang = "")
    {
        try {
            // Check if the requested language exists in the languages table
            $language_exists = Language::where('app_language_code', $lang)->exists();

            // If the language doesn't exist, default to English ('en')
            if (!$language_exists) {
                $lang = 'en';
            }

            // Get all categories with status = 1
            $categories = Category::where('status', '1')->get();

            // If no active categories are found, return not found response
            if ($categories->isEmpty()) {
                return response()->json([
                    'status_code' => Response::HTTP_NOT_FOUND,
                    'message' => $this->get_message('not_found'),
                ], Response::HTTP_NOT_FOUND);
            }

            $response = [];
            foreach ($categories as $category) {
                // Try to get the translation for the requested language
                $translation = CategoryTranslation::where('category_id', $category->id)
                    ->where('category_translations.status', '1')
                    ->join('languages', 'category_translations.language_id', '=', 'languages.id')
                    ->where('languages.app_language_code', $lang)
                    ->select(
                        'languages.app_language_code as language_code',
                        'languages.name as language_name',
                        'languages.flag as flag',
                        'languages.rtl as dir',
                        'category_translations.name as translate_name'
                    )
                    ->first();

                // If no translation is found, fall back to English
                if (!$translation) {
                    $translation = CategoryTranslation::where('category_id', $category->id)
                        ->where('category_translations.status', '1')
                        ->join('languages', 'category_translations.language_id', '=', 'languages.id')
                        ->where('languages.app_language_code', 'en')
                        ->select(
                            'languages.app_language_code as language_code',
                            'languages.name as language_name',
                            'languages.flag as flag',
                            'languages.rtl as dir',
                            'category_translations.name as translate_name'
                        )
                        ->first();
                }

                // Prepare the response structure
                $response[] = [
                    'uuid' => $category->uuid,
                    'parent_id' => $category->parent_id,
                    'level' => $category->level,
                    'name' => $category->name,
                    'order_level' => $category->order_level,
                    'banner' => env('APP_ASSET_PATH').$category->banner,
                    'icon' => env('APP_ASSET_PATH').$category->icon,
                    'cover_image' => $category->cover_image,
                    'featured' => $category->featured,
                    'slug' => $category->slug,
                    'meta_title' => $category->meta_title,
                    'meta_description' => $category->meta_description,
                    'og_title' => $category->og_title,
                    'og_description' => $category->og_description,
                    'og_image' => $category->og_image,
                    'x_title' => $category->x_title,
                    'x_description' => $category->x_description,
                    'x_image' => $category->x_image,
                    'auth_id' => $category->auth_id,
                    'status' => $category->status,
                    'created_at' => $category->created_at,
                    'updated_at' => $category->updated_at,
                    'language_code' => $translation->language_code ?? null,
                    'language_name' => $translation->language_name ?? null,
                    // 'flag' => env('APP_ASSET_PATH').$translation->flag ?? null,
                    'dir' => $translation->dir ?? null,
                    'translate_name' => $translation->translate_name ?? null,
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


    public function get_featured_category($lang = "")
    {
        try {
            // Check if the requested language exists in the languages table
            $language_exists = Language::where('app_language_code', $lang)->exists();

            // If the language doesn't exist, default to English ('en')
            if (!$language_exists) {
                $lang = 'en';
            }

            // Get all categories with status = 1
            $categories = Category::where('status', '1')->where('featured', '1')->get();

            // If no active categories are found, return not found response
            if ($categories->isEmpty()) {
                return response()->json([
                    'status_code' => Response::HTTP_NOT_FOUND,
                    'message' => $this->get_message('not_found'),
                ], Response::HTTP_NOT_FOUND);
            }

            $response = [];
            foreach ($categories as $category) {
                // Try to get the translation for the requested language
                $translation = CategoryTranslation::where('category_id', $category->id)
                    ->where('category_translations.status', '1')
                    ->join('languages', 'category_translations.language_id', '=', 'languages.id')
                    ->where('languages.app_language_code', $lang)
                    ->select(
                        'languages.app_language_code as language_code',
                        'languages.name as language_name',
                        'languages.flag as flag',
                        'languages.rtl as dir',
                        'category_translations.name as translate_name'
                    )
                    ->first();

                // If no translation is found, fall back to English
                if (!$translation) {
                    $translation = CategoryTranslation::where('category_id', $category->id)
                        ->where('category_translations.status', '1')
                        ->join('languages', 'category_translations.language_id', '=', 'languages.id')
                        ->where('languages.app_language_code', 'en')
                        ->select(
                            'languages.app_language_code as language_code',
                            'languages.name as language_name',
                            'languages.flag as flag',
                            'languages.rtl as dir',
                            'category_translations.name as translate_name'
                        )
                        ->first();
                }

                // Prepare the response structure
                $response[] = [
                    'uuid' => $category->uuid,
                    'parent_id' => $category->parent_id,
                    'level' => $category->level,
                    'name' => $category->name,
                    'order_level' => $category->order_level,
                    'banner' => env('APP_ASSET_PATH').$category->banner,
                    'icon' => env('APP_ASSET_PATH').$category->icon,
                    'cover_image' => $category->cover_image,
                    'featured' => $category->featured,
                    'slug' => $category->slug,
                    'meta_title' => $category->meta_title,
                    'meta_description' => $category->meta_description,
                    'og_title' => $category->og_title,
                    'og_description' => $category->og_description,
                    'og_image' => $category->og_image,
                    'x_title' => $category->x_title,
                    'x_description' => $category->x_description,
                    'x_image' => $category->x_image,
                    'auth_id' => $category->auth_id,
                    'status' => $category->status,
                    'created_at' => $category->created_at,
                    'updated_at' => $category->updated_at,
                    'language_code' => $translation->language_code ?? null,
                    'language_name' => $translation->language_name ?? null,
                    // 'flag' => env('APP_ASSET_PATH').$translation->flag ?? null,
                    'dir' => $translation->dir ?? null,
                    'translate_name' => $translation->translate_name ?? null,
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
