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
use App\Models\Ecommerce\Attribute;
use App\Models\Ecommerce\AttributeTranslation;
use App\Models\AttributeValue;
use App\Models\Language;
use App\Services\PermissionService;
use App\Traits\MessageTrait;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon; 
use Illuminate\Support\Str;


class ApiAttributeController extends Controller
{
    use MessageTrait;

    public function get_attribute($lang = "")
{
    try {
        // Check if the requested language exists in the languages table
        $language_exists = Language::where('app_language_code', $lang)->exists();

        // If the language doesn't exist, default to English ('en')
        if (!$language_exists) {
            $lang = 'en';
        }

        // Get all attributes with status = 1
        $attributes = Attribute::where('status', '1')->get();

        // If no active attributes are found, return not found response
        if ($attributes->isEmpty()) {
            return response()->json([
                'status_code' => Response::HTTP_NOT_FOUND,
                'message' => $this->get_message('not_found'),
            ], Response::HTTP_NOT_FOUND);
        }

        $response = [];
        foreach ($attributes as $attribute) {
            // Try to get the translation for the requested language
            $translation = AttributeTranslation::where('attribute_id', $attribute->id)
                ->where('attribute_translations.status', '1')
                ->join('languages', 'attribute_translations.language_id', '=', 'languages.id')
                ->where('languages.app_language_code', $lang)
                ->select(
                    'languages.app_language_code as language_code',
                    'languages.name as language_name',
                    'languages.flag as flag',
                    'languages.rtl as dir',
                    'attribute_translations.name as translate_name',
                    'attribute_translations.description as translate_description'
                )
                ->first();

            // If no translation is found, fall back to English
            if (!$translation) {
                $translation = AttributeTranslation::where('attribute_id', $attribute->id)
                    ->where('attribute_translations.status', '1')
                    ->join('languages', 'attribute_translations.language_id', '=', 'languages.id')
                    ->where('languages.app_language_code', 'en')
                    ->select(
                        'languages.app_language_code as language_code',
                        'languages.name as language_name',
                        'languages.flag as flag',
                        'languages.rtl as dir',
                        'attribute_translations.name as translate_name',
                        'attribute_translations.description as translate_description'
                    )
                    ->first();
            }

            // Prepare the response structure
            $response[] = [
                'uuid' => $attribute->uuid,
                'name' => $attribute->name,
                'description' => $attribute->description,
                'auth_id' => $attribute->auth_id,
                'status' => $attribute->status,
                'created_at' => $attribute->created_at,
                'updated_at' => $attribute->updated_at,
                'language_code' => $translation->language_code ?? null,
                'language_name' => $translation->language_name ?? null,
                // 'flag' => env('APP_ASSET_PATH').$translation->flag ?? null,
                'dir' => $translation->dir ?? null,
                'translate_name' => $translation->translate_name ?? $attribute->name,
                // 'translate_description' => $translation->translate_description ?? $attribute->description,
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
