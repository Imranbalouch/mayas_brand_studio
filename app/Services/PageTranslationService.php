<?php

namespace App\Services;

use App\Models\CMS\Module;
use App\Models\CMS\Module_details;
use App\Models\CMS\Module_details_translation;
use App\Models\CMS\ModuleField;
use App\Models\CMS\ModuleTranslation;
use App\Models\CMS\Theme;
use App\Models\Page;
use App\Models\PageTranslation;
use Illuminate\Support\Facades\File;

class PageTranslationService
{
    private $pageUpdateService;

    public function __construct(PageUpdateService $pageUpdateService)
    {
        $this->pageUpdateService = $pageUpdateService;
    }
    public function updatePageTranslations($lang_code, $lang_uuid)
    {
        $Activetheme = Theme::where('status', 1)->first();
        //// Add Page Translation Start  
        //dd($Activetheme->uuid);
        $all_pages = Page::where('theme_id', $Activetheme->uuid)->get();
        foreach ($all_pages as $page) {
            if ($page->uuid) {
                //  dd($page); 
                $pageData = [
                    'content' => $page->description,
                    'meta_title' => $page->meta_title,
                    'meta_description' => $page->meta_description,
                ];
                //dd($pageData); 
                $this->updatePageTranslation($page, $lang_code, $lang_uuid, $pageData);
                $this->pageUpdateService->updatePage($page->slug, $lang_code);
            }
        }
        //// Add Page Translation End
    }
    private function updatePageTranslation(Page $page, string $lang, string $langUuid, array $pageData, $languages = []): void
    {
        if ($languages) {
            foreach ($languages as $language) {
                $translation = PageTranslation::firstOrNew([
                    'lang' => $language->app_language_code,
                    'language_id' => $language->uuid,
                    'page_uuid' => $page->uuid
                ]);
                $translation->description = $pageData['content'] ?? null;
                $translation->meta_title = $pageData['meta_title'] ?? null;
                $translation->meta_description = $pageData['meta_description'] ?? null;
                $translation->save();
            }
        } else {
            $translation = PageTranslation::firstOrNew([
                'lang' => $lang,
                'language_id' => $langUuid,
                'page_uuid' => $page->uuid
            ]);
            $translation->description = $pageData['content'] ?? null;
            $translation->meta_title = $pageData['meta_title'] ?? null;
            $translation->meta_description = $pageData['meta_description'] ?? null;
            $translation->save();
        }
    }


    public function page_delete(string $delete_lang_uuid)
    {
        try {
            $Activetheme = Theme::where('status', 1)->first();
            $all_pages = Page::has('theme')->where('theme_id', $Activetheme->uuid)->get();
            foreach ($all_pages as $page) {
                if ($page->uuid) {
                    // $page = Page::has('theme')->where('uuid', $uuid)->first(); 
                    $all_languages = all_languages();
                    $is_default_lang = getConfigValue('defaul_lang');
                    foreach ($all_languages as $language) {
                        if ($delete_lang_uuid == $language->uuid) {
                            if ($is_default_lang == $language->app_language_code) {
                                $filePath = getConfigValue('Frontend_Dir') . $page->theme->theme_path . '/' . $page->slug . '.html';
                                if (File::exists($filePath)) {
                                    File::delete($filePath);
                                }
                            } else {
                                $filePath = getConfigValue('Frontend_Dir') . $page->theme->theme_path . '/' . $language->app_language_code . '/' . $page->slug . '.html';
                                if (File::exists($filePath)) {
                                    File::delete($filePath);
                                }
                            }
                            //dd($filePath);
                            $page->page_translations()->delete();
                            // $page->delete();
                        }
                    }
                }
            }
            $filePath = getConfigValue('Frontend_Dir') . $page->theme->theme_path . '/' . $language->app_language_code . '/';
            if (File::exists($filePath)) {
                File::deleteDirectory($filePath);
            }
            return true;
        } catch (\Throwable $e) {
            return $e;
        }
    }
}
