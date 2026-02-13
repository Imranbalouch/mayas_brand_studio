<?php

namespace App\Services;

use App\Models\CMS\Module;
use App\Models\CMS\Module_details;
use App\Models\CMS\Module_details_translation;
use App\Models\CMS\ModuleField;
use App\Models\CMS\ModuleTranslation;
use App\Models\CMS\Theme;
use App\Models\CMS\Widget;
use App\Models\CMS\WidgetTranslation;
use App\Models\Page;
use App\Models\PageTranslation;
use Illuminate\Support\Facades\File;

class WidgetTranslationService
{
    private $pageUpdateService;

    public function __construct(PageUpdateService $pageUpdateService)
    {
        $this->pageUpdateService = $pageUpdateService;
    }
    public function updateWidgetTranslations($lang_code, $lang_uuid)
    {
        $Activetheme = Theme::where('status', 1)->first();
        //// Add Module
        $all_widgets = Widget::where('theme_id', $Activetheme->uuid)->get();
        foreach ($all_widgets as $widget) {
            if ($widget->uuid) { 
                $widgetTranslationData = [
                    'name' => $widget->name,
                    'html_code' => $widget->html_code,
                    'widgetclass' => $widget->widgetclass,
                ];
                // dd($widget, $lang_code, $lang_uuid, $widgetTranslationData);
                $this->updateWidgetTranslation($widget, $lang_code, $lang_uuid, $widgetTranslationData);
                $this->saveWidgetBladeFile($widget, $widget->shortkey, $widget->shortkey, $widget->html_code, $lang_code);
            }
        }
    }




    private function updateWidgetTranslation(Widget $widget, string $lang_code, string $langUuid, array $widgetTranslationData, $languages = []): void
    {
        if ($languages) {
            foreach ($languages as $language) {
                $translation = WidgetTranslation::firstOrNew([
                    'lang' => $language->app_language_code,
                    'language_id' => $language->uuid,
                    'widget_uuid' => $widget->uuid
                ]);
                $translation->name = $widgetTranslationData['name'];
                $translation->html_code = $widgetTranslationData['html_code'];
                $translation->save();
            }
        } else {
            $translation = WidgetTranslation::firstOrNew([
                'lang' => $lang_code,
                'language_id' => $langUuid,
                'widget_uuid' => $widget->uuid
            ]);
            $translation->name = $widgetTranslationData['name'];
            $translation->html_code = $widgetTranslationData['html_code'];
            $translation->save();
        }
    }


    private function saveWidgetBladeFile(Widget $widget, string $newshortkey, string $oldshortkey, string $htmlCode, string $lang_code, $languages = []): void
    {

        if ($languages) {
            foreach ($languages as $language) {
                $path = $this->getModuleBladePath($widget, $newshortkey, $language->app_language_code);
                $this->putBladeFile($path, $htmlCode);
                if ($newshortkey !== $oldshortkey) {
                    $oldPath = $this->getModuleBladePath($widget, $oldshortkey, $language->app_language_code);
                    if (File::exists($oldPath)) {
                        File::delete($oldPath);
                    }
                }
            }
        } else {
            $path = $this->getModuleBladePath($widget, $newshortkey, $lang_code);
            $this->putBladeFile($path, $htmlCode);
            if ($newshortkey !== $oldshortkey) {
                $oldPath = $this->getModuleBladePath($widget, $oldshortkey, $lang_code);
                if (File::exists($oldPath)) {
                    File::delete($oldPath);
                }
            }
        }
    }

    private function getModuleBladePath(Widget $widget, string $fileName, string $lang_code): string
    {
        return base_path(
            'resources/views/components/' .
                str_replace(' ', '_', strtolower($widget->theme->theme_path)) .
                '/' . $lang_code . '/' . strtolower($fileName) . '.blade.php'
        );
    }

    private function putBladeFile(string $path, string $content): void
    {
        if (!File::exists(dirname($path))) {
            File::makeDirectory(dirname($path), 0755, true, true);
        }
        File::put($path, $content);
    }

}
