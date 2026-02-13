<?php

namespace App\Services;

use App\Models\Plugin\ReCaptcha;
use App\Models\Plugin\WhatsApp;
use App\Models\Plugins;

class PluginService
{
    public function loadPlugins(): array
    {
        $plugins = Plugins::where('status', 1)->get();
        $loaded = [];

        foreach ($plugins as $plugin) {
            if ($plugin->name === 'reCAPTCHA') {
                $loaded['recaptcha'] = $this->loadRecaptcha();
            }
            if ($plugin->name === 'whatsApp') {
                $loaded['whatsapp'] = $this->loadWhatsApp();
            }
        }

        return $loaded;
    }

    private function loadRecaptcha(): ?string
    {
        $recaptcha = ReCaptcha::first();
        if (!$recaptcha) return null;

        return view('partials.plugins.recaptcha', ['siteKey' => $recaptcha->site_key])->render();
    }

    private function loadWhatsApp(): array
    {
        $whatsapp = WhatsApp::first();
        if (!$whatsapp) return [];

        return [
            'css'  => $whatsapp->custom_css,
            'html' => str_replace(
                ['{{$phone_number}}', '{{$icon}}'],
                [$whatsapp->number, getConfigValue('APP_ASSET_PATH') . $whatsapp->whatsapp_logo],
                $whatsapp->html_code
            ),
        ];
    }
}
