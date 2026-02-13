<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\Plugin\PluginController;
use App\Http\Controllers\API\Plugin\ReCaptchaController;
use App\Http\Controllers\API\Plugin\SMTPController;
use App\Http\Controllers\API\Plugin\WhatsAppController;

// Plugin


Route::get('get_plugin', [PluginController::class, 'get_plugin']);
Route::post('plugin_status', [PluginController::class, 'plugin_status']);


Route::prefix('plugins')->group(function () {
    Route::controller(PluginController::class)->group(function () {
        Route::get('/', 'index')->middleware('check.permission:viewglobal');
        Route::post('/update-status', 'activeOrDeactive')->middleware('check.permission:add');
        Route::post('/plugin-install', 'pluginInstall')->middleware('check.permission:add');
        Route::post('/plugin-uninstall', 'pluginUnInstall')->middleware('check.permission:add');
    });
    Route::prefix('recaptcha')->group(function () {
        Route::controller(ReCaptchaController::class)->group(function () {
            Route::get('/', 'index')->middleware('check.permission:viewglobal');
            Route::post('/store', 'store')->middleware('check.permission:add');
        });
    });
    Route::prefix('smtp')->group(function () {
        Route::controller(SMTPController::class)->group(function () {
            Route::get('/', 'index')->middleware('check.permission:viewglobal');
            Route::post('/store', 'store')->middleware('check.permission:add');
        });
    });
    Route::prefix('whatsapp')->group(function () {
        Route::controller(WhatsAppController::class)->group(function () {
            Route::get('/', 'index')->middleware('check.permission:viewglobal');
            Route::post('/store', 'store')->middleware('check.permission:add');
        });
    });
});
