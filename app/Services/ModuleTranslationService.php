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

class ModuleTranslationService
{
    private $pageUpdateService;

    public function __construct(PageUpdateService $pageUpdateService)
    {
        $this->pageUpdateService = $pageUpdateService;
    }
    public function updateModuleTranslations($lang_code, $lang_uuid)
    {
        $Activetheme = Theme::where('status', 1)->first();
        //// Add Module
        $all_modules = Module::where('theme_id', $Activetheme->uuid)->get();
        foreach ($all_modules as $module) {
            if ($module->uuid) {
                $module_details = Module_details::where('module_id', $module->uuid)->get();
                foreach ($module_details as $module_detail) {
                    $this->updateModuleDetailsTranslation($module_detail->uuid, $module_detail->details, $module_detail->view, $lang_code, $lang_uuid);
                    $this->updateModuleViewFile($module, $module->uuid, $lang_code);
                }
                $moduleTranslationData = [
                    'name' => $module->name,
                    'html_code' => $module->html_code,
                    'moduleclass' => $module->moduleclass,
                ];
                //dd($module, $lang_code, $lang_uuid, $moduleTranslationData);
                $this->updateModuleTranslation($module, $lang_code, $lang_uuid, $moduleTranslationData);
                $this->updateModuleBlade($module, $module->html_code, $module->shortkey, $module->shortkey, $lang_code);
                 $this->updateModuleViewFile($module, $module->uuid, $lang_code);
            }
        }
    }




    private function updateModuleDetailsTranslation(string $module_detail_uuid, string $module_detail_details, string $module_detail_view, $lang_code, $lang_uuid): void
    {
        $translation = Module_details_translation::firstOrNew([
            'lang'            => $lang_code,
            'language_id'     => $lang_uuid,
            'module_detail_id' => $module_detail_uuid,
        ]);
        $translation->details = $module_detail_details;
        $translation->view    = $module_detail_view;
        $translation->save();
    }


    private function updateModuleViewFile(Module $module, string $moduleUuid, string $lang_code): void
    {
        $views = Module_details::where('module_id', $moduleUuid)
            ->with(['moduleDetailsTranslation' => fn($q) => $q->where('lang', $lang_code)])
            ->get()
            ->pluck('moduleDetailsTranslation.*.view')
            ->flatten()
            ->implode('');

        $fileName = $module->shortkey . '.blade.php';
        $filePath = base_path('resources/views/components/' .
            str_replace(' ', '_', strtolower($module->theme->theme_path)) .
            '/modules/' . $lang_code . '/' . $fileName);

        $this->createOrUpdateFile($filePath, $views);
    }
    private function createOrUpdateFile(string $filePath, string $content): void
    {
        if (!File::exists(dirname($filePath))) {
            File::makeDirectory(dirname($filePath), 0755, true);
        }
        File::put($filePath, $content);
    }


    private function updateThemePages($lang_code = null): void
    {
        try {
            $themeSlugs = Theme::where('status', 1)
                ->with('pages')
                ->first()
                ->pages
                ->pluck('slug')
                ->toArray();

            foreach ($themeSlugs as $slug) {
                $this->pageUpdateService->updatePage($slug, $lang_code);
            }
        } catch (\Exception $e) {
            throw new \RuntimeException($e->getMessage());
        }
    }

    private function updateModuleBlade(Module $module, string $html, string $newName, string $oldName, string $lang, $languages = []): void
    {
        if ($languages) {
            foreach ($languages as $language) {
                $path = $this->getModuleBladePath($module, $newName, $language->app_language_code);
                $this->putBladeFile($path, $html);
                if ($newName !== $oldName) {
                    $oldPath = $this->getModuleBladePath($module, $oldName, $language->app_language_code);
                    if (File::exists($oldPath)) {
                        File::delete($oldPath);
                    }
                }
            }
        } else {
            $path = $this->getModuleBladePath($module, $newName, $lang);
            $this->putBladeFile($path, $html);
            if ($newName !== $oldName) {
                $oldPath = $this->getModuleBladePath($module, $oldName, $lang);
                if (File::exists($oldPath)) {
                    File::delete($oldPath);
                }
            }
        }
    }
    private function getModuleBladePath(Module $module, string $fileName, string $lang): string
    {
        return base_path(
            'resources/views/components/' .
                str_replace(' ', '_', strtolower($module->theme->theme_path)) .
                '/modules/' . $lang . '/' . strtolower($fileName) . '.blade.php'
        );
    }
    private function putBladeFile(string $path, string $content): void
    {
        if (!File::exists(dirname($path))) {
            File::makeDirectory(dirname($path), 0755, true, true);
        }
        File::put($path, $content);
    }


    private function updateModuleTranslation(Module $module, string $lang, string $langUuid, array $moduleTranslationData, $languages = []): void
    {
        if ($languages) {
            foreach ($languages as $language) {
                $translation = ModuleTranslation::firstOrNew([
                    'lang' => $language->app_language_code,
                    'language_id' => $language->uuid,
                    'module_id' => $module->uuid
                ]);
                $translation->name = $moduleTranslationData['name'];
                $translation->html_code = $moduleTranslationData['html_code'];
                $translation->moduleclass = $moduleTranslationData['moduleclass'];
                $translation->save();
            }
        } else {
            $translation = ModuleTranslation::firstOrNew([
                'lang' => $lang,
                'language_id' => $langUuid,
                'module_id' => $module->uuid
            ]);
            $translation->name = $moduleTranslationData['name'];
            $translation->html_code = $moduleTranslationData['html_code'];
            $translation->moduleclass = $moduleTranslationData['moduleclass'];
            $translation->save();
        }
    }

 
}
