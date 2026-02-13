<?php
 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\BlogController;
use App\Http\Controllers\API\MenuController;
use App\Http\Controllers\API\RoleController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\BrandController;
use App\Http\Controllers\API\PluginController;
use App\Http\Controllers\API\StaffController; 

use App\Http\Controllers\API\CountryController;
use App\Http\Controllers\API\InquiryController;
use App\Http\Controllers\API\CategoryController;
use App\Http\Controllers\API\CurrencyController;
use App\Http\Controllers\API\LanguageController;
use App\Http\Controllers\API\PageTypeController;
use App\Http\Controllers\API\RegisterController;
use App\Http\Controllers\Api\CMS\ThemeController;
use App\Http\Controllers\API\OtherMenuController;
use App\Http\Controllers\API\NewsletterController;
use App\Http\Controllers\API\PermissionController;
use App\Http\Controllers\API\ActivityLogController;
use App\Http\Controllers\API\FilemanagerController;
use App\Http\Controllers\API\Plugin\SMTPController;
use App\Http\Controllers\API\TestimonialController;
use App\Http\Controllers\API\BlogCategoryController;
use App\Http\Controllers\API\WebsiteConfigController;
use App\Http\Controllers\API\ForgotPasswordController;
use App\Http\Controllers\API\BlogTranslationController;

use App\Http\Controllers\API\GalleryCategoryController;
use App\Http\Controllers\API\Plugin\WhatsAppController;
use App\Http\Controllers\API\BrandtranslationController;
use App\Http\Controllers\API\BusinessSettingsController;
use App\Http\Controllers\API\Menu_translationController;
use App\Http\Controllers\API\Plugin\ReCaptchaController;
use App\Http\Controllers\API\Permission_assignsController;
use App\Http\Controllers\API\Special_permissionController;
use App\Http\Controllers\API\CategoryTranslationController;
use App\Http\Controllers\API\BlogCategoryTranslationController;
use App\Http\Controllers\API\Plugin\PluginController as PluginPluginController;

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

// Route::middleware('auth:api')->get('/user', function (Request $request) {
//     return $request->user();
// });
 
Route::post('login', [RegisterController::class, 'login'])->middleware(["throttle:5,1"]);
Route::post('verify_otp', [RegisterController::class, 'verify_otp'])->middleware(["throttle:5,1"]);
Route::post('resend_otp', [RegisterController::class, 'resend_otp'])->middleware(["throttle:5,1"]);
Route::post('forgot_password', [ForgotPasswordController::class, 'forgot_password'])->middleware(["throttle:5,1"]); 
Route::post('reset_password', [ForgotPasswordController::class, 'reset_password'])->middleware(["throttle:5,1"]);

Route::middleware(['auth:admin', 'scopes:admin'])->group(function(){
    
    Route::post('register', [RegisterController::class, 'register']);

    // Logout
    Route::post('logout', [RegisterController::class, 'logout']);

    // Permission
    Route::post('add_permission', [PermissionController::class, 'add_permission'])->middleware('check.permission:add'); 
    Route::get('edit_permission/{id?}', [PermissionController::class, 'edit_permission'])->middleware('check.permission:edit');
    Route::post('update_permission', [PermissionController::class, 'update_permission'])->middleware('check.permission:update');
    Route::delete('delete_permission/{id?}', [PermissionController::class, 'delete_permission'])->middleware('check.permission:delete');
    Route::get('get_permission', [PermissionController::class, 'get_permission'])->middleware('check.permission:viewglobal,view');

    // Menues
    Route::post('add_menu', [MenuController::class, 'add_menu'])->middleware('check.permission:add'); 
    Route::get('edit_menu/{id?}', [MenuController::class, 'edit_menu'])->middleware('check.permission:edit'); 
    Route::post('update_menu/{id}', [MenuController::class, 'update_menu'])->middleware('check.permission:update'); 
    Route::delete('delete_menu/{id?}', [MenuController::class, 'delete_menu'])->middleware('check.permission:delete'); 
    Route::get('get_menu', [MenuController::class, 'get_menu'])->middleware('check.permission:viewglobal,view');
    Route::get('get_sort_menu', [MenuController::class, 'get_sort_menu']);
 
    // Menue Translation
    Route::post('add_menu_translation', [Menu_translationController::class, 'add_menu_translation'])->middleware('check.permission:add');
    Route::get('edit_menu_translation/{id?}', [Menu_translationController::class, 'edit_menu_translation'])->middleware('check.permission:edit');
    Route::post('update_menu_translation/{id?}', [Menu_translationController::class, 'update_menu_translation'])->middleware('check.permission:update');
    Route::delete('delete_menu_translation/{id?}', [Menu_translationController::class, 'delete_menu_translation'])->middleware('check.permission:delete');
    Route::get('get_menu_translation', [Menu_translationController::class, 'get_menu_translation'])->middleware('check.permission:viewglobal');
    
    // Roles
    Route::post('add_role', [RoleController::class, 'add_role'])->middleware('check.permission:add');  
    Route::get('edit_role/{id?}', [RoleController::class, 'edit_role'])->middleware('check.permission:edit'); 
    Route::post('update_role', [RoleController::class, 'update_role'])->middleware('check.permission:update'); 
    Route::delete('delete_role/{id?}', [RoleController::class, 'delete_role'])->middleware('check.permission:delete'); 
    Route::get('get_roles', [RoleController::class, 'get_roles'])->middleware('check.permission:viewglobal,view');
    Route::get('get_active_roles', [RoleController::class, 'get_active_roles']);
     
    // Permission Assign
    Route::post('add_permission_assign', [Permission_assignsController::class, 'add_permission_assign'])->middleware('check.permission:add');
    Route::get('edit_permission_assign/{id?}', [Permission_assignsController::class, 'edit_permission_assign'])->middleware('check.permission:edit'); 
    Route::post('update_permission_assign', [Permission_assignsController::class, 'update_permission_assign'])->middleware('check.permission:update'); 
    Route::delete('delete_permission_assign/{id?}', [Permission_assignsController::class, 'delete_permission_assign'])->middleware('check.permission:delete'); 
    Route::get('get_permission_assign', [Permission_assignsController::class, 'get_permission_assign'])->middleware('check.permission:viewglobal');
 
    // Special Permission Assign 
    Route::post('add_special_permission_assign', [Special_permissionController::class, 'add_special_permission_assign'])->middleware('check.permission:add');
    Route::get('edit_special_permission_assign/{id?}', [Special_permissionController::class, 'edit_special_permission_assign'])->middleware('check.permission:edit');
    Route::post('update_special_permission_assign', [Special_permissionController::class, 'update_special_permission_assign'])->middleware('check.permission:update');
    Route::delete('delete_special_permission_assign/{id?}', [Special_permissionController::class, 'delete_special_permission_assign'])->middleware('check.permission:delete');
    Route::get('get_special_permission_assign', [Special_permissionController::class, 'get_special_permission_assign'])->middleware('check.permission:viewglobal');
    Route::get('get_permission_menus', [Special_permissionController::class, 'get_permission_menus'])->middleware('check.permission:viewglobal');
    Route::get('permissions_menu', [Special_permissionController::class, 'permissions_menu'])->middleware('check.permission:viewglobal');


    // User
    Route::get('get_users', [UserController::class, 'get_users'])->middleware('check.permission:viewglobal,view');
    Route::get('edit_user/{id?}', [UserController::class, 'edit_user'])->middleware('check.permission:edit');
    Route::post('update_user', [UserController::class, 'update_user'])->middleware('check.permission:update');
    Route::delete('delete_user/{id?}', [UserController::class, 'delete_user'])->middleware('check.permission:delete');
    Route::post('change_password', [UserController::class, 'change_password'])->middleware('check.permission:changepassword');
    Route::get('get_active_users', [UserController::class, 'get_active_users']);
    //profile update
    Route::get('profile/edit/{id?}', [UserController::class, 'profile_edit']);
    Route::get('profile', [UserController::class, 'profile']);
    Route::post('profile_update', [UserController::class, 'profile_update']);
    Route::post('profile/verify_otp', [UserController::class, 'verify_otp']);

    //Staff
    Route::get('get_staff', [StaffController::class, 'get_staff'])->middleware('check.permission:viewglobal,view');
    Route::post('add_staff', [StaffController::class, 'add_staff'])->middleware('check.permission:add');
    Route::post('update_staff/{uuid}', [StaffController::class, 'update_staff'])->middleware('check.permission:update');
    Route::get('edit_staff/{uuid}', [StaffController::class, 'edit_staff'])->middleware('check.permission:edit');
    Route::delete('delete_staff/{uuid}', [StaffController::class, 'delete_staff'])->middleware('check.permission:delete');
    Route::post('update_staff_status/{uuid}', [StaffController::class, 'update_staff_status'])->middleware('check.permission:update');

     // Countries
     Route::post('add_currency', [CurrencyController::class, 'add_currency'])->middleware('check.permission:add');
     Route::get('edit_currency/{id?}', [CurrencyController::class, 'edit_currency'])->middleware('check.permission:edit');
     Route::post('update_currency/{id?}', [CurrencyController::class, 'update_currency'])->middleware('check.permission:update');
     Route::delete('delete_currency/{id?}', [CurrencyController::class, 'delete_currency'])->middleware('check.permission:delete');
     Route::get('get_currency', [CurrencyController::class, 'get_currency'])->middleware('check.permission:view');
     Route::post('status/{id}', [CurrencyController::class,'updateCurrencyStatus'])->middleware('check.permission:Update');
     Route::get('get_active_currencies', [CurrencyController::class, 'get_active_currencies']);

     // Countries
     Route::post('add_currency', [CurrencyController::class, 'add_currency'])->middleware('check.permission:add');
     Route::get('edit_currency/{id?}', [CurrencyController::class, 'edit_currency'])->middleware('check.permission:edit');
     Route::post('update_currency/{id?}', [CurrencyController::class, 'update_currency'])->middleware('check.permission:update');
     Route::delete('delete_currency/{id?}', [CurrencyController::class, 'delete_currency'])->middleware('check.permission:delete');
     Route::get('get_currency', [CurrencyController::class, 'get_currency'])->middleware('check.permission:view');
     Route::post('status_currency/{id}', [CurrencyController::class,'updateCurrencyStatus'])->middleware('check.permission:Update');
     Route::get('get_active_currencies', [CurrencyController::class, 'get_active_currencies']);

     //Countries
     Route::post('add_country', [CountryController::class, 'add_country'])->middleware('check.permission:add');
     Route::get('edit_country/{id?}', [CountryController::class, 'edit_country'])->middleware('check.permission:edit');
     Route::post('update_country/{id?}', [CountryController::class, 'update_country'])->middleware('check.permission:update');
     Route::delete('delete_country/{id?}', [CountryController::class, 'delete_country'])->middleware('check.permission:delete');
     Route::get('get_country', [CountryController::class, 'get_country'])->middleware('check.permission:view');
     Route::post('status_country/{id}', [CountryController::class,'updateCountryStatus'])->middleware('check.permission:Update');
     Route::get('get_active_countries', [CountryController::class, 'get_active_countries']);

    // Language
    Route::post('add_language', [LanguageController::class, 'add_language'])->middleware('check.permission:add');
    Route::get('edit_language/{id?}', [LanguageController::class, 'edit_language'])->middleware('check.permission:edit');
    Route::post('update_language/{id?}', [LanguageController::class, 'update_language'])->middleware('check.permission:update');
    Route::delete('delete_language/{id?}', [LanguageController::class, 'delete_language'])->middleware('check.permission:delete');
    Route::get('get_language', [LanguageController::class, 'get_language'])->middleware('check.permission:view');
    Route::post('status_language/{id}', [LanguageController::class,'updateLanguageStatus'])->middleware('check.permission:Update');
    Route::post('set_default_language/{uuid}', [LanguageController::class,'set_default_language'])->middleware('check.permission:Update');
    Route::get('get_active_languages', [LanguageController::class, 'get_active_languages']);
     
    // Category
    Route::post('add_category', [CategoryController::class, 'add_category'])->middleware('check.permission:add');
    Route::get('edit_category/{id?}', [CategoryController::class, 'edit_category'])->middleware('check.permission:edit');
    Route::put('update_category/{id?}', [CategoryController::class, 'update_category'])->middleware('check.permission:update');
    Route::delete('delete_category/{id?}', [CategoryController::class, 'delete_category'])->middleware('check.permission:delete');
    Route::get('get_own_category/{authid?}', [CategoryController::class, 'get_own_category'])->middleware('check.permission:view');
    Route::get('get_category', [CategoryController::class, 'get_category'])->middleware('check.permission:viewglobal');


    // Category Translation
    Route::post('add_category_translation', [CategoryTranslationController::class, 'add_category_translation'])->middleware('check.permission:add');
    Route::get('edit_category_translation/{id?}', [CategoryTranslationController::class, 'edit_category_translation'])->middleware('check.permission:edit');
    Route::put('update_category_translation/{id?}', [CategoryTranslationController::class, 'update_category_translation'])->middleware('check.permission:update');
    Route::delete('delete_category_translation/{id?}', [CategoryTranslationController::class, 'delete_category_translation'])->middleware('check.permission:delete');
    Route::get('get_own_category_translation/{authid?}', [CategoryTranslationController::class, 'get_own_category_translation'])->middleware('check.permission:view');
    Route::get('get_category_translation', [CategoryTranslationController::class, 'get_category_translation'])->middleware('check.permission:viewglobal');

 

    // Blog Category
    Route::post('add_blog_category', [BlogCategoryController::class, 'add_blog_category'])->middleware('check.permission:add');
    Route::get('edit_blog_category/{id?}', [BlogCategoryController::class, 'edit_blog_category'])->middleware('check.permission:edit');
    Route::put('update_blog_category/{id?}', [BlogCategoryController::class, 'update_blog_category'])->middleware('check.permission:update');
    Route::delete('delete_blog_category/{id?}', [BlogCategoryController::class, 'delete_blog_category'])->middleware('check.permission:delete');
    Route::get('get_own_blog_category', [BlogCategoryController::class, 'get_own_blog_category'])->middleware('check.permission:view');
    Route::get('get_all_blog_category', [BlogCategoryController::class, 'get_all_blog_category'])->middleware('check.permission:viewglobal');


    // Blog Category Translation
    Route::post('add_blog_category_translation', [BlogCategoryTranslationController::class, 'add_blog_category_translation'])->middleware('check.permission:add');
    Route::get('edit_blog_category_translation/{id?}', [BlogCategoryTranslationController::class, 'edit_blog_category_translation'])->middleware('check.permission:edit');
    Route::put('update_blog_category_translation/{id?}', [BlogCategoryTranslationController::class, 'update_blog_category_translation'])->middleware('check.permission:update');
    Route::delete('delete_blog_category_translation/{id?}', [BlogCategoryTranslationController::class, 'delete_blog_category_translation'])->middleware('check.permission:delete');
    Route::get('get_own_blog_category_translation/{authid?}', [BlogCategoryTranslationController::class, 'get_own_blog_category_translation'])->middleware('check.permission:view');
    Route::get('get_blog_category_translation', [BlogCategoryTranslationController::class, 'get_blog_category_translation'])->middleware('check.permission:viewglobal');


    // Blog
    Route::post('add_blog', [BlogController::class, 'add_blog'])->middleware('check.permission:add');
    Route::get('edit_blog/{id?}', [BlogController::class, 'edit_blog'])->middleware('check.permission:edit');
    Route::post('update_blog', [BlogController::class, 'update_blog'])->middleware('check.permission:update');
    Route::delete('delete_blog/{id?}', [BlogController::class, 'delete_blog'])->middleware('check.permission:delete');
    Route::get('get_own_blog', [BlogController::class, 'get_own_blog'])->middleware('check.permission:view');
    Route::get('get_all_blog', [BlogController::class, 'get_all_blog'])->middleware('check.permission:viewglobal');


    // Blog Translation
    Route::post('add_blog_translation', [BlogTranslationController::class, 'add_blog_translation'])->middleware('check.permission:add');
    Route::get('edit_blog_translation/{id?}', [BlogTranslationController::class, 'edit_blog_translation'])->middleware('check.permission:edit');
    Route::post('update_blog_translation', [BlogTranslationController::class, 'update_blog_translation'])->middleware('check.permission:update');
    Route::delete('delete_blog_translation/{id?}', [BlogTranslationController::class, 'delete_blog_translation'])->middleware('check.permission:delete');
    Route::get('get_own_blog_translation/{authid?}', [BlogTranslationController::class, 'get_own_blog_translation'])->middleware('check.permission:view');
    Route::get('get_all_blog_translation', [BlogTranslationController::class, 'get_all_blog_translation'])->middleware('check.permission:viewglobal');


    // Blog Category Routes
    Route::prefix('blog-categories')->group(function () {
        Route::get('/', [BlogCategoryController::class, 'index'])->middleware('check.permission:view');
        Route::post('/', [BlogCategoryController::class, 'store'])->middleware('check.permission:add');
        Route::get('/with-count', [BlogCategoryController::class, 'getCategoriesWithCount'])->middleware('check.permission:view');
        Route::get('edit_category/{id}', [BlogCategoryController::class, 'edit'])->middleware('check.permission:edit');
        Route::post('update_category/{id}', [BlogCategoryController::class, 'update'])->middleware('check.permission:update');
        Route::delete('delete_category/{id}', [BlogCategoryController::class, 'destroy'])->middleware('check.permission:delete');
        Route::post('/{id}/status', [BlogCategoryController::class, 'updateStatus'])->middleware('check.permission:update');
        Route::post('/sort-order', [BlogCategoryController::class, 'updateSortOrder'])->middleware('check.permission:update');
    });

    // Blog Routes
    Route::prefix('blogs')->group(function () {
        Route::get('/', [BlogController::class, 'index'])->middleware('check.permission:view');
        Route::get('/categories', [BlogCategoryController::class, 'getAllCategories']);
        Route::post('/add_blog', [BlogController::class, 'store'])->middleware('check.permission:add');
        Route::get('/category/{categoryId}', [BlogController::class, 'getBlogsByCategory'])->middleware('check.permission:view');
        Route::get('/slug/{slug}', [BlogController::class, 'getBlogBySlug'])->middleware('check.permission:view');
        Route::get('/{id}', [BlogController::class, 'edit'])->middleware('check.permission:edit');
        Route::post('update_blog/{id}', [BlogController::class, 'update'])->middleware('check.permission:update');
        Route::delete('/{id}', [BlogController::class, 'destroy'])->middleware('check.permission:delete');
        Route::post('/{id}/status', [BlogController::class, 'updateStatus'])->middleware('check.permission:update');
    });


    // Activity Log 
    Route::get('get_all_activity', [ActivityLogController::class, 'get_all_activity'])->middleware('check.permission:viewglobal');
    Route::get('get_active_activity', [ActivityLogController::class, 'get_active_activity']);
    Route::get('get_own_activity', [ActivityLogController::class, 'get_own_activity'])->middleware('check.permission:view');
    Route::get('get_activity_by_id/{id?}', [ActivityLogController::class, 'get_activity_by_id'])->middleware('check.permission:edit');
    Route::get('activity-show/{id?}', [ActivityLogController::class, 'show'])->middleware('check.permission:view');


    // Testimonial
    // Route::post('add_testimonial', [TestimonialController::class, 'add_testimonial'])->middleware('check.permission:add');
    // Route::get('edit_testimonial/{id?}', [TestimonialController::class, 'edit_testimonial'])->middleware('check.permission:edit');
    // Route::post('update_testimonial', [TestimonialController::class, 'update_testimonial'])->middleware('check.permission:update');
    // Route::delete('delete_testimonial/{id?}', [TestimonialController::class, 'delete_testimonial'])->middleware('check.permission:delete');
    // Route::get('get_testimonial', [TestimonialController::class, 'get_testimonial'])->middleware('check.permission:viewglobal');
    

    // Gallery Category
    Route::post('add_gallery_category', [GalleryCategoryController::class, 'add_gallery_category'])->middleware('check.permission:add');
    Route::get('edit_gallery_category/{id?}', [GalleryCategoryController::class, 'edit_gallery_category'])->middleware('check.permission:edit');
    Route::put('update_gallery_category/{id?}', [GalleryCategoryController::class, 'update_gallery_category'])->middleware('check.permission:update');
    Route::delete('delete_gallery_category/{id?}', [GalleryCategoryController::class, 'delete_gallery_category'])->middleware('check.permission:delete');
    Route::get('get_own_gallery_category/{authid?}', [GalleryCategoryController::class, 'get_own_gallery_category'])->middleware('check.permission:view');
    Route::get('get_all_gallery_category', [GalleryCategoryController::class, 'get_all_gallery_category'])->middleware('check.permission:viewglobal');    

    Route::get('get_all_gallery_category', [GalleryCategoryController::class, 'get_all_gallery_category'])->middleware('check.permission:viewglobal');


    // Website Config
    // Route::post('add_websiteconfig', [WebsiteConfigController::class, 'add_websiteconfig'])->middleware('check.permission:add');
    // Route::get('edit_websiteconfig/{id?}', [WebsiteConfigController::class, 'edit_websiteconfig'])->middleware('check.permission:edit');
    // Route::post('update_websiteconfig', [WebsiteConfigController::class, 'update_websiteconfig'])->middleware('check.permission:update');
    // Route::delete('delete_websiteconfig/{id?}', [WebsiteConfigController::class, 'delete_websiteconfig'])->middleware('check.permission:delete');
    // Route::get('get_websiteconfig', [WebsiteConfigController::class, 'get_websiteconfig'])->middleware('check.permission:viewglobal');

    
    // Inquiry
    // Route::post('add_inquiry', [InquiryController::class, 'add_inquiry'])->middleware('check.permission:add');
    // Route::get('edit_inquiry/{id?}', [InquiryController::class, 'edit_inquiry'])->middleware('check.permission:edit');
    // Route::put('update_inquiry/{id?}', [InquiryController::class, 'update_inquiry'])->middleware('check.permission:update');
    // Route::delete('delete_inquiry/{id?}', [InquiryController::class, 'delete_inquiry'])->middleware('check.permission:delete');
    // Route::get('get_all_inquiry', [InquiryController::class, 'get_all_inquiry'])->middleware('check.permission:viewglobal');


    // Newsletter
    // Route::post('add_newsletter', [NewsletterController::class, 'add_newsletter'])->middleware('check.permission:add');
    // Route::get('edit_newsletter/{id?}', [NewsletterController::class, 'edit_newsletter'])->middleware('check.permission:edit');
    // Route::delete('delete_newsletter/{id?}', [NewsletterController::class, 'delete_newsletter'])->middleware('check.permission:delete');
    // Route::get('get_all_newsletter', [NewsletterController::class, 'get_all_newsletter'])->middleware('check.permission:viewglobal');


    // Other Menu
    Route::post('add_othermenu', [OtherMenuController::class, 'add_othermenu'])->middleware('check.permission:add'); 
    Route::get('edit_othermenu/{id?}', [OtherMenuController::class, 'edit_othermenu'])->middleware('check.permission:edit'); 
    Route::post('update_othermenu', [OtherMenuController::class, 'update_othermenu'])->middleware('check.permission:update'); 
    Route::delete('delete_othermenu/{id?}', [OtherMenuController::class, 'delete_othermenu'])->middleware('check.permission:delete'); 
    Route::get('get_othermenu', [OtherMenuController::class, 'get_othermenu'])->middleware('check.permission:viewglobal,view');
    Route::get('get_sort_othermenu', [OtherMenuController::class, 'get_sort_othermenu']);

    Route::controller(BusinessSettingsController::class)->group(function () {
        Route::get('/business-settings/get', 'general_setting');
        Route::post('/business-settings/update', 'update');
    });

    


    Route::prefix('category')->group(function () {
        Route::controller(CategoryController::class)->group(function () {
            Route::get('/get_category', 'get_category');
            Route::post('/add_category', 'add_category')->middleware('check.permission:add');
            Route::get('/edit_category/{id?}', 'edit_category')->middleware('check.permission:edit');
            Route::post('/update_category', 'update_category')->middleware('check.permission:update');
            Route::delete('/delete_category/{id?}', 'delete_category')->middleware('check.permission:delete');
            Route::post('/status/{id}', 'updateCategoryStatus')->middleware('check.permission:Update');
            Route::post('/featured/{id}', 'updateCategoryFeatured')->middleware('check.permission:Update');
            Route::get('/get_active_categories', 'get_active_categories');
        });
    });




    Route::prefix('brand')->group(function () {
        Route::controller(BrandController::class)->group(function () {
            Route::get('get_brand', 'get_brand')->middleware('check.permission:viewglobal,view');
            Route::post('add_brand', 'add_brand')->middleware('check.permission:add');
            Route::get('edit_brand/{id?}', 'edit_brand')->middleware('check.permission:edit');
            Route::post('update_brand', 'update_brand')->middleware('check.permission:update');
            Route::delete('delete_brand/{id?}', 'delete_brand')->middleware('check.permission:delete');
            Route::post('/status/{id}', 'updateCategoryStatus')->middleware('check.permission:Update');
            Route::post('/featured/{id}', 'updateCategoryFeatured')->middleware('check.permission:Update');
            Route::get('/get_active_brands', 'get_active_brands');
        });
    });


             
});

   