<?php
 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PageController;

 
use App\Http\Controllers\API\PageTypeController;
use App\Http\Controllers\API\CMS\BrandController;
use App\Http\Controllers\API\CMS\ThemeController;
use App\Http\Controllers\API\CMS\ModuleController;
use App\Http\Controllers\API\DynamicFormController;
use App\Http\Controllers\API\FilemanagerController;
use App\Http\Controllers\API\CMS\ContactController; 
use App\Http\Controllers\API\CMS\WidgetController;  
use App\Http\Controllers\API\CMS\CategoryController; 
use App\Http\Controllers\API\CMS\PageTemplateController; 

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });


Route::middleware('auth:api')->group(function () {
    Route::prefix('v1')->controller(ThemeController::class)->group(function () {
        Route::controller(FilemanagerController::class)->group(function () {
            Route::prefix('filemanager')->group(function () {
                Route::get('/', 'index')->name('filemanager.index')->middleware('check.permission:viewglobal,view');
                Route::post('/store', 'store')->name('filemanager.store')->middleware('check.permission:add');
                Route::post('/destroy/{id}', 'destroy')->name('filemanager.destroy')->middleware('check.permission:delete');
                Route::post('/ckstore', 'ckstore')->name('filemanager.ckstore');
                Route::get('/getfiles', 'getfiles')->name('filemanager.getfiles')->middleware('check.permission:viewglobal,view');
                Route::get('/getselectedfile', 'getselectedfile')->name('filemanager.getselectedfile');
            });
        });

        Route::get('/themes', 'index')->middleware('check.permission:view,viewglobal');
        Route::post('/theme/store', 'store')->middleware('check.permission:add');
        Route::get('/theme/edit/{id}', 'edit')->middleware('check.permission:edit');
        Route::post('/theme/update/{id}', 'update')->middleware('check.permission:update');
        Route::delete('theme/delete/{id}', 'destroy')->middleware('check.permission:delete');
        Route::post('/theme/status/{id}', 'updateStatus')->middleware('check.permission:update');

        Route::prefix('theme')->controller(WidgetController::class)->group(function () {
            Route::get('/widgets', 'index')->middleware('check.permission:view,viewglobal');
            Route::post('/widget/store', 'store')->middleware('check.permission:add');
            Route::get('/widget/edit/{id}', 'edit')->middleware('check.permission:edit');
            Route::post('/widget/update/{id}', 'update')->middleware('check.permission:update');
            Route::delete('/widget/delete/{id}', 'destroy')->middleware('check.permission:delete');
            Route::post('/widget/status/{id}', 'updateStatus')->middleware('check.permission:update');
            Route::post('/widget/default-data/{id}', 'default_data')->middleware('check.permission:update');
            Route::prefix('widget')->group(function () {
                Route::get("fields", 'getWidgetsfields')->middleware('check.permission:View');
                Route::post("field/store", 'storeWidgetsfields')->middleware('check.permission:add');
                Route::get("field/edit/{id}", 'editWidgetsfields')->middleware('check.permission:edit');
                Route::post("field/update/{id}", 'updateWidgetsfields')->middleware('check.permission:update');
                Route::delete("field/delete/{id}", 'deleteWidgetsfields')->middleware('check.permission:delete');
                Route::post('/field/status/{id}', 'updateStatusWidgetsfields')->middleware('check.permission:update');
            });
        });

        Route::prefix('theme')->controller(ModuleController::class)->group(function () {

            Route::get('/modules', 'index')->middleware('check.permission:view,viewglobal');
            Route::post('/module/store', 'store')->middleware('check.permission:add');
            Route::get('/module/edit/{id}', 'edit')->middleware('check.permission:edit');
            Route::get('/module/getmodulebyid/{id}', 'getModuleById')->middleware('check.permission:view,viewglobal');
            Route::post('/module/update/{id}', 'update')->middleware('check.permission:update');
            Route::delete('/module/delete/{id}', 'destroy')->middleware('check.permission:delete');
            Route::post('/module/status/{id}', 'updateStatus')->middleware('check.permission:update');
            Route::prefix('module')->group(function () {
                Route::get("fields", 'getModulesfields')->middleware('check.permission:view,viewglobal');
                Route::post("field/store", 'storeModulesfields')->middleware('check.permission:add');
                Route::get("field/edit/{id}", 'editModulesfields')->middleware('check.permission:edit');
                Route::post("field/update/{id}", 'updateModulesfields')->middleware('check.permission:update');
                Route::delete("field/delete/{id}", 'deleteModulesfields')->middleware('check.permission:delete');
                Route::post('/field/status/{id}', 'updateStatusModulesfields')->middleware('check.permission:update');

                Route::get('/details/view/{id}', 'viewModulesdetails');
                Route::post('/details/add', 'addModulesdetails')->middleware('check.permission:Add');
                Route::get('/details/edit/{id}', 'editModulesdetails')->middleware('check.permission:Edit');
                Route::post('/details/update', 'updateModulesdetails')->middleware('check.permission:Update');
                Route::post('/details/status/{id}', 'updateModulesdetailsStatus')->middleware('check.permission:Update');
                Route::delete('/details/delete/{id}', 'deleteModulesdetails')->middleware('check.permission:Delete');
            });

            Route::controller(DynamicFormController::class)->group(function () {
                Route::get('/forms', 'getform')->middleware('check.permission:view,viewglobal');
                Route::post('/form/store', 'addform')->middleware('check.permission:add');
                Route::get('/form/edit/{id}', 'editform')->middleware('check.permission:edit');
                Route::post('/form/update', 'updateform')->middleware('check.permission:update');
                Route::delete('/form/delete/{id}', 'deleteform')->middleware('check.permission:delete');
                Route::post('/form/status/{id}', 'updateStatus')->middleware('check.permission:update');
            });

            

            Route::controller(PageTemplateController::class)->group(function () {
                Route::get('/page-template', 'index')->middleware('check.permission:view,viewglobal');
                Route::get('/page-template/create', 'create')->middleware('check.permission:view,viewglobal');
                Route::post('/page-template/store', 'store')->middleware('check.permission:add');
                Route::get('/page-template/edit/{uuid}', 'edit')->middleware('check.permission:edit');
                Route::get('/page-template/show', 'show')->middleware('check.permission:edit');
                Route::post('/page-template/update/{id}', 'update')->middleware('check.permission:update');
                Route::post('/page-template/status/{id}', 'update_page_template_status')->middleware('check.permission:update');
                Route::delete('/page-template/delete/{id}', 'destroy')->middleware('check.permission:delete');
            });
        });

        Route::controller(PageController::class)->group(function () {
            Route::get('/pages', 'index')->middleware('check.permission:view,viewglobal');
            Route::post('/page/store', 'store')->middleware('check.permission:add');
            Route::get('/page/edit/{id}', 'edit')->middleware('check.permission:edit');
            Route::post('/page/update/{id}', 'update')->middleware('check.permission:update');
            Route::delete('/page/delete/{id}', 'destroy')->middleware('check.permission:delete');
            Route::post('/page/status/{id}', 'updateStatus')->middleware('check.permission:update');
            Route::post('/page/default/{id}', 'updateDefault')->middleware('check.permission:update');
            Route::get('/page/all-widgets', 'all_widgets')->middleware('check.permission:view,viewglobal');
            Route::get('/page/all-forms', 'all_forms')->middleware('check.permission:view,viewglobal');
            Route::get('/page/widget-show/{id}', 'widgetshow')->middleware('check.permission:view,viewglobal');
            Route::get('/page/widget-show-page/{id}', 'widgetshowPage')->middleware('check.permission:view,viewglobal');
            Route::get('/page/module-show/{id}', 'moduleshow')->middleware('check.permission:view,viewglobal');
            Route::post('/page/upload/image', 'upload_image')->name('upload.image');
        });

        Route::prefix('page-type')->group(function () {
            Route::controller(PageTypeController::class)->group(function () {
                Route::get('get_page_type', 'get_page_type');
            });
        });

        Route::controller(ContactController::class)->group(function () {
            Route::get('/forms', 'index')->middleware('check.permission:viewglobal');
            Route::get('/contactus', 'contactList')->middleware('check.permission:viewglobal');
        }); 
    });
});

Route::prefix('v1')->group(function () {
    Route::controller(PageController::class)->group(function () {
        // Route::get('/page/show/{id}', 'show');
        Route::get('{themeName}/page/show/{id}', 'apiShow');
        Route::post('/contactus/submit', 'form_submit')->name('form_submit');
        Route::get('/server-timezone', function () {
            return date_default_timezone_get();
        });
    });
});



// For Frontend API without authentication

Route::prefix('frontend')->group(function () {
    Route::controller(CategoryController::class)->group(function () {
        Route::get('/get_category/{id?}', 'get_category');
        Route::get('/get_featured_category/{id?}', 'get_featured_category');
    });
});


Route::prefix('frontend')->group(function () {
    Route::controller(BrandController::class)->group(function () {
        Route::get('get_brand/{id?}', [BrandController::class, 'get_brand']);
        Route::get('get_featured_brand/{id?}', [BrandController::class, 'get_featured_brand']);
    });
});

  
