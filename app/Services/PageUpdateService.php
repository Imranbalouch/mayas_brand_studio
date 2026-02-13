<?php

namespace App\Services;

use App\Models\Page;
use App\Models\Plugins;
use App\Models\CMS\Theme;
use App\Models\OtherMenu;
use App\Models\CMS\Widget;
use App\Models\Plugin\WhatsApp;
use App\Models\BussinessSetting;
use App\Models\CMS\PageTemplate;
use App\Models\Plugin\ReCaptcha;
use App\Models\CMS\ProductDesign;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;
use App\Models\CMS\WidgetTranslation;

class PageUpdateService
{
    private $shortcodeService;

    public function __construct(ShortcodeService $shortcodeService)
    {
        $this->shortcodeService = $shortcodeService;
    }
    public function updatePage(string $id, $lang, $oldSlug = null)
    {
        $theme = Theme::where('status', 1)->first();
        $baseurl = BussinessSetting::where('type', 'api_base_url')->first();
        $baseurl = $baseurl ? $baseurl->value : '';

        $page = Page::where('slug', $id)->where('theme_id', $theme->uuid)->where('status', 1)->first();
        if (!$page) {
            return response()->json([
                'status_code' => 404,
                'message'     => 'Not found',
            ], 404);
        }
        // Related pages
        $related = $this->getRelatedPages($theme, $lang);
        // Build content
        $content = $this->buildContent($theme, $page, $lang);
        // Assets
        $assets = $this->buildAssets($theme, $lang);
        // Plugins
        $plugins = $this->loadPlugins();
        // Meta
        $meta = $this->buildMeta($page, $lang);
        // Scripts
        $scripts = $this->buildScripts($page, $related, $assets, $lang);
        // Render final HTML
        $html = $this->renderHtml($theme, $page, $content, $assets, $plugins, $meta, $scripts, $lang);
        // Save to file system
        $this->savePageFile($theme, $page, $html, $oldSlug, $lang);

        return response([
            'status_code' => 200,
            'css_file'    => $assets['css'],
            'js_file'     => $assets['js'],
            'view'        => $content,
            'custom_css'  => $page->custom_css,
            'custom_js'   => $page->custom_js,
        ]);
    }


    public function updatehtaccess()
    {
        $theme = Theme::where('status', 1)->first();
        $page = Page::where('theme_id', $theme->uuid)->where('default_page', 1)->first();
        $pageProductList = Page::where('theme_id', $theme->uuid)
            ->whereIn('page_type', ['product_listing', 'product_detail'])
            ->get();
              
        $careerDetailPage = Page::where('theme_id', $theme->uuid)->where('page_type','career-detail')->first();
        $fleetDetailPage = Page::where('theme_id', $theme->uuid)->where('page_type','fleet-detail')->first();
        $blogDetailPage = Page::where('theme_id', $theme->uuid)->where('page_type','blog-detail')->first();             
        if ($page == null) {
            return response()->json([
                'status_code' => 404,
                'message' => 'Default page not found'
            ], 404);
        }
        $htaccess = '';
        if ($page->slug != null) {
            $htaccess .= "# Set the default page to " . $page->slug . ".html if no file is specified in directory
            DirectoryIndex " . $page->slug . ".html";
        }
        // $htaccess .= "
        //     # Avoid MultiViews interfering with clean URLs
        //     Options -MultiViews

        //     RewriteEngine On

        //     # --- Hard stop: if it's already .html, or a real file/dir, don't rewrite ---
        //     RewriteCond %{REQUEST_URI} \.html$ [OR]
        //     RewriteCond %{REQUEST_FILENAME} -f [OR]
        //     RewriteCond %{REQUEST_FILENAME} -d
        //     RewriteRule ^ - [L]

        //     # --- Catch-all: add .html to anything without an extension, with optional trailing slash ---
        //     # Examples:
        //     #   about-us            -> about-us.html
        //     #   about-us/           -> about-us.html
        //     #   ar/about-us         -> ar/about-us.html
        //     #   ar/about-us/        -> ar/about-us.html
        //     #   ar/products/laptops -> ar/products/laptops.html
        //     RewriteRule ^(.+?)/?$ $1.html [L,NC]
        //     ";
        $htaccess .= "
        # Set the default page to home.html if no file is specified in directory

            # Enable URL rewriting
            RewriteEngine On

            # If the request is not a real file and doesn't contain a file extension
            # rewrite /something → /something.html
            RewriteCond %{REQUEST_FILENAME} !-f
            RewriteCond %{REQUEST_FILENAME} !-d
            RewriteRule ^([^/]+)$ $1.html [NC,L]

            # Redirect all product-listing/* URLs to product-listing.html
            # This will let JavaScript read the actual category from the path

        ";

            
        if ($careerDetailPage && $careerDetailPage->page_type == 'career-detail') { 
            $careerTranslations = $careerDetailPage->page_translations; 
            foreach($careerTranslations as $careerTranslation) {
                if($careerTranslation->lang != defaultLanguages()->app_language_code){ 
                    $htaccess .= "\nRewriteRule ^".$careerTranslation->lang.'/'. $careerDetailPage->page_type . "/[^/]+$ " . getConfigValue('Frontend_Dir').'/' . $theme->theme_path . '/' .$careerTranslation->lang . "/" . $careerDetailPage->page_type . ".html [L]"; 
                } else { 
                    $htaccess .= "\nRewriteRule ^". $careerDetailPage->page_type . "/[^/]+$ " . getConfigValue('Frontend_Dir'). '/' . $theme->theme_path . '/' . $careerDetailPage->page_type . ".html [L]"; 
                }
            }
        }

        if ($fleetDetailPage && $fleetDetailPage->page_type == 'fleet-detail') { 
            $fleetTranslations = $fleetDetailPage->page_translations; 
            foreach($fleetTranslations as $fleetTranslation) {
                if($fleetTranslation->lang != defaultLanguages()->app_language_code){ 
                    $htaccess .= "\nRewriteRule ^".$fleetTranslation->lang.'/'. $fleetDetailPage->page_type . "/[^/]+$ " . getConfigValue('Frontend_Dir').'/' . $theme->theme_path . '/' .$fleetTranslation->lang . "/" . $fleetDetailPage->page_type . ".html [L]"; 
                } else { 
                    $htaccess .= "\nRewriteRule ^". $fleetDetailPage->page_type . "/[^/]+$ " . getConfigValue('Frontend_Dir'). '/' . $theme->theme_path . '/' . $fleetDetailPage->page_type . ".html [L]"; 
                }
            }
        }

        if ($blogDetailPage && $blogDetailPage->page_type == 'blog-detail') { 
            $blogTranslations = $blogDetailPage->page_translations; 
            foreach($blogTranslations as $blogTranslation) {
                if($blogTranslation->lang != defaultLanguages()->app_language_code){ 
                    $htaccess .= "\nRewriteRule ^".$blogTranslation->lang.'/'. $blogDetailPage->page_type . "/[^/]+$ " . getConfigValue('Frontend_Dir').'/' . $theme->theme_path . '/' .$blogTranslation->lang . "/" . $blogDetailPage->page_type . ".html [L]"; 
                } else { 
                    $htaccess .= "\nRewriteRule ^". $blogDetailPage->page_type . "/[^/]+$ " . getConfigValue('Frontend_Dir'). '/' . $theme->theme_path . '/' . $blogDetailPage->page_type . ".html [L]"; 
                }
            }
        }

        if ($pageProductList->count() > 0) {
            foreach ($pageProductList as $key => $pageProduct) {
                $htaccess .= "\nRewriteRule ^" . $pageProduct->slug . "/[^/]+$ " . getConfigValue('Frontend_Dir') . $theme->theme_path . "/" . $pageProduct->slug . ".html [L]";
            }
        }

        $sanitizedPath = basename($page->theme->theme_path); // Ensures no "../" or absolute paths
        $filePath = getConfigValue('Frontend_Dir') . $sanitizedPath . '/.htaccess';

        if (!File::exists($filePath)) {
            // if folder not exists then create folder
            if (!File::exists(dirname($filePath))) {
                File::makeDirectory(dirname($filePath), 0755, true, true);
            }
            // Create the file with the given content
            File::put($filePath, $htaccess);
        } else {
            File::put($filePath, $htaccess);
        }
    }

    private function loadPlugins(): array
    {
        $plugins = Plugins::where('status', 1)->get();
        $output  = [];

        foreach ($plugins as $plugin) {
            if ($plugin->name == 'reCAPTCHA' && ($recaptcha = ReCaptcha::first())) {
                $output['recaptcha'] = '<script src="https://www.google.com/recaptcha/api.js?render=' . $recaptcha->site_key . '"></script>';
            }

            if ($plugin->name == 'whatsApp' && ($whatsapp = WhatsApp::first())) {
                $output['whatsapp'] = [
                    'css'  => $whatsapp->custom_css,
                    'html' => str_replace(
                        ['{{$phone_number}}', '{{$icon}}'],
                        [$whatsapp->number, getConfigValue('APP_ASSET_PATH') . $whatsapp->whatsapp_logo],
                        $whatsapp->html_code
                    ),
                ];
            }
        }

        return $output;
    }

    private function buildMeta(Page $page, $lang): array
    {
        return [
            'title'       => $page->getTranslation("meta_title",$lang) ?? $page->title,
            'description' => $page->getTranslation("meta_description",$lang),
            'og_title'    => $page->og_title ?? $page->title,
            'og_desc'     => $page->og_description,
            'og_image'    => $page->og_image,
            'x_title'     => $page->x_title ?? $page->title,
            'x_desc'      => $page->x_description,
            'x_image'     => $page->x_image,
        ];
    }

    private function buildScripts(Page $page, array $related, array $assets, $lang): array
    {
        $baseurl = getConfigValue('API_URL');
        $scripts = [];

        if ($related['login']) {
            $scripts['logout'] = $this->logout(
                $baseurl,
                getConfigValue("WEB_URL") . '/' . $related['login']->slug
            );
        }

        if ($related['product']) {
            $cartFunctions = $this->removedCartItem($baseurl, $related['cart']);
            $scripts['removeCartItem']     = $cartFunctions['removeCartItem'];
            $scripts['removeAllCartItems'] = $cartFunctions['removeAllCartItems'];
            $scripts['search']             = $this->searchProducts($baseurl, $related['product']);
        }

        if ($related['wishlist']) {
            $scripts['wishlist'] = $this->whishList($baseurl);
        }

        if ($related['cart']) {
            $scripts['cart'] = $this->cart($baseurl, $related['cart']);
        }

        $scripts['toaster'] = $this->toasterMsg();
        $scripts['profile'] = $this->profileLoad();

        return $scripts;
    }


    private function renderHtml(Theme $theme, Page $page, string $content, array $assets, array $plugins, array $meta, array $scripts, $lang): string
    {
        $langauge = getAllLanguage($lang);
        $themeFavicon = getConfigValue('APP_ASSET_PATH') . $theme->fav_icon;
        $dir = $langauge->rtl == 1 ? 'rtl' : 'ltr';
        $cssLinks  = implode('', array_map(fn($c) => "<link rel=\"stylesheet\" href=\"$c\">", $assets['css']));
        $headJs    = implode('', array_map(fn($j) => "<script src=\"$j\"></script>", $assets['js_head']));
        $preloaderJs = "<script>(function(global) {
                const instaPreloader = {
                    show: function() {
                        const loader = document.getElementById('insta-global-preloader');
                        if (loader) {
                            loader.style.display = 'flex';
                        }
                    },
                    hide: function() {
                        const loader = document.getElementById('insta-global-preloader');
                        if (loader) {
                            loader.style.display = 'none';
                        }
                    }
                };
                global.instaPreloader = instaPreloader;
            })(window);</script>";
        $preloaderDiv = "<div id='insta-global-preloader'><img src='".getConfigValue('APP_ASSET_PATH').$theme->preloader."' alt='Preloader'></div>";
        $bodyJs    = implode('', array_map(fn($j) => "<script src=\"$j\"></script>", $assets['js']));
        $customCss = "<style>{$page->custom_css}</style>";
        $preloaderCss = "<style>
                    #insta-global-preloader {
                        position: fixed;
                        top: 0;
                        left: 0;
                        width: 100%;
                        height: 100%;
                        background: rgba(0, 0, 0, 0.5);
                        display: none;
                        align-items: center;
                        justify-content: center;
                        z-index: 999999;
                    }
                </style>";
        $toastCss = "<style>.insta-toast-container {
                        position: fixed;
                        top: 1.25rem;
                        right: 1.25rem;
                        z-index: 9999;
                        display: flex;
                        flex-direction: column;
                        gap: 0.5rem;
                    }

                    .insta-toast {
                        padding: 0.75rem 1rem;
                        border-radius: 0.375rem;
                        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                        color: white;
                        animation: slide-in-down 0.3s ease-out;
                        opacity: 1;
                        transition: opacity 0.3s ease-in-out;
                    }

                    .insta-toast-success {
                        background-color: #16a34a;
                    }

                    .insta-toast-error {
                        background-color: #dc2626;
                    }

                    @keyframes slide-in-down {
                        from {
                        transform: translateY(-10px);
                        opacity: 0;
                        }
                        to {
                        transform: translateY(0);
                        opacity: 1;
                        }
                    }</style>";                
        $customJs  = "<script>{$page->custom_js}</script>";
        $languageSwitcher = "<script>
                            const languageSwitcher = document.querySelector('.insta-manage-language-switcher');
                            if(languageSwitcher){
                                languageSwitcher.addEventListener('change', function() {
                                    let url = this.value;
                                    if (url) {
                                        window.location.href = url;
                                    }
                                });
                            }   
                        </script>";

        $whatsappCss  = $plugins['whatsapp']['css'] ?? '';
        $whatsappHtml = $plugins['whatsapp']['html'] ?? '';
        $recaptcha    = $plugins['recaptcha'] ?? '';

        $extraScripts = implode("\n", array_filter($scripts));

        return <<<HTML
        <!doctype html>
        <html lang="{$lang}" dir="{$dir}">
        <head>
            <meta charset="utf-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>{$meta['title']}</title>
            <meta name="description" content="{$meta['description']}">
            <link rel="icon" href="{$themeFavicon}" type="image/x-icon">
            <meta property="og:title" content="{$meta['og_title']}"/>
            <meta property="og:description" content="{$meta['og_desc']}"/>
            <meta property="og:image" content="{$meta['og_image']}"/>
            {$cssLinks}
            {$headJs}
            {$customCss}
            {$preloaderCss}
            {$toastCss}
            {$whatsappCss}
        </head>
        <body>
            {$preloaderJs}
            {$preloaderDiv}
            {$content}
            {$whatsappHtml}
            {$bodyJs}
            {$languageSwitcher}
            {$customJs}
            <script>{$extraScripts}</script>
            {$recaptcha}
        </body>
        </html>
        HTML;
    }

    private function savePageFile(Theme $theme, Page $page, string $html, ?string $oldSlug, $lang): void
    { 
        if ($lang == getConfigValue('defaul_lang')) {
            $filePath = getConfigValue('Frontend_Dir') . $theme->theme_path . '/' . $page->slug . '.html';
        } else {
            
            $filePath = getConfigValue('Frontend_Dir') . $theme->theme_path . '/' . $lang . '/' . $page->slug . '.html';
        }

        if (!File::exists(dirname($filePath))) {
            File::makeDirectory(dirname($filePath), 0755, true);
        }

        File::put($filePath, $html);

        if ($oldSlug) {
            if ($lang == getConfigValue('defaul_lang')) {
                $oldFile = getConfigValue('Frontend_Dir') . $theme->theme_path . '/' . $oldSlug . '.html';
            } else {
                $oldFile = getConfigValue('Frontend_Dir') . $theme->theme_path . '/'. $lang . '/' . $oldSlug . '.html';
            }
            if (File::exists($oldFile)) {
                File::delete($oldFile);
            }
        }

        $this->updatehtaccess();
    }

    private function buildAssets(Theme $theme, $lang): array
    {
        $langList = getAllLanguage($lang);
        $cssLink = [];
        $jsLink = [];
        Log::info('Lang idea '.$langList->rtl);
        if ($langList->rtl == 1 && $theme->css_link_rtl) { 
            $cssLink = array_filter(explode(',', $theme->css_link_rtl), 'strlen');
        } elseif ($theme->css_link) {
            $cssLink = array_filter(explode(',', $theme->css_link), 'strlen');
        }        
        if ($langList->rtl == 1 && $theme->js_link_rtl) {
            $jsLink = array_filter(explode(',', $theme->js_link_rtl), 'strlen');
        } elseif ($theme->js_link) {
            $jsLink = array_filter(explode(',', $theme->js_link), 'strlen');
        }        
        $cssLocal = [];
        if ($theme->css_file) {
            $cssLocal = array_filter(
                array_map(fn($f) => getConfigValue("APP_ASSET_PATH") . trim($f), explode(',', $theme->css_file)),
                'strlen'
            );
        }        
        $jsLocal = [];
        if ($theme->js_file) {
            $jsLocal = array_filter(
                array_map(fn($f) => getConfigValue("APP_ASSET_PATH") . trim($f), explode(',', $theme->js_file)),
                'strlen'
            );
        }        
        $jsHead = [];
        if ($theme->js_head_link) {
            $jsHead = array_filter(explode(',', $theme->js_head_link), 'strlen');
        }

        return [
            'css'     => array_merge($cssLink, $cssLocal),
            // 'js'      => array_merge($jsLocal, explode(',', $theme->js_link)),
            'js'      => array_merge($jsLink, $jsLocal),
            'js_head' => $jsHead,
        ];
    }

    private function buildContent(Theme $theme, Page $page, $lang): string
    {
        $widgets = Widget::where('theme_id', $theme->uuid)
            ->whereIn('widget_type', ['header', 'footer'])
            ->where('status', 1)
            ->get()
            ->keyBy('widget_type');

        $content = '';
        $defaultLang = getConfigValue('defaul_lang');
        if (($widgets['header']->default_data ?? null) && $page->default_header != 1) {
            if ($lang !== $defaultLang) {
                $translation = WidgetTranslation::where('widget_uuid', $widgets['header']->uuid)
                    ->where('lang', $lang)
                    ->first();
                $content .= $translation && $translation->default_data ? $translation->default_data : $widgets['header']->default_data;
            } else {
                $content .= $widgets['header']->default_data;
            }
        }

        $content .= $page->getTranslation('description',$lang);

        if (($widgets['footer']->default_data ?? null) && $page->default_footer != 1) {
            if ($lang !== $defaultLang) {
                $translation = WidgetTranslation::where('widget_uuid', $widgets['footer']->uuid)
                    ->where('lang', $lang)
                    ->first();
                $content .= $translation && $translation->default_data ? $translation->default_data : $widgets['footer']->default_data;
            } else {
                $content .= $widgets['footer']->default_data;
            }
        }
        //dd($this->shortcodeService->parse($content, 'frontend', $page, $theme->theme_path,$lang));
        return $this->shortcodeService->parse($content, 'frontend', $page, $theme->theme_path,$lang);
    }


    private function getRelatedPages(Theme $theme, $lang): array
    {
        $types = [
            'login',
            'signup',
            'forget_password',
            'reset_password',
            'dashboard',
            'product_detail',
            'wishlist',
            'cart',
        ];

        $pages = Page::where('theme_id', $theme->uuid)
            ->where('status', 1)
            ->whereIn('page_type', $types)
            ->get()
            ->keyBy('page_type');

        return [
            'login'    => $pages['login']           ?? null,
            'signup'   => $pages['signup']          ?? null,
            'forget'   => $pages['forget_password'] ?? null,
            'reset'    => $pages['reset_password']  ?? null,
            'profile'  => $pages['dashboard']       ?? null,
            'product'  => $pages['product_detail']  ?? null,
            'wishlist' => $pages['wishlist']        ?? null,
            'cart'     => $pages['cart']            ?? null,
        ];
    }


    private function toasterMsg()
    {
        $toasterMsg = '(function(global) {
            function createContainer() {
                let container = document.getElementById("insta-toast-container");
                if (!container) {
                container = document.createElement("div");
                container.id = "insta-toast-container";
                container.className = "insta-toast-container";
                document.body.appendChild(container);
                }
                return container;
            }

            function showToast(message, {
                type = "success",
                duration = 3000,
                containerId = "insta-toast-container"
            } = {}) {
                const toast = document.createElement("div");
                toast.className = `insta-toast insta-toast-${type}`;
                toast.innerText = message;

                const container = createContainer();
                container.appendChild(toast);

                setTimeout(() => {
                toast.style.opacity = "0";
                setTimeout(() => toast.remove(), 300);
                }, duration);
            }

            // Expose globally
            global.showToast = showToast;
        })(window);';
        return $toasterMsg;
    }

    private function profileLoad()
    {
        $profileload = '// Check if user is logged in and add dropdown attributes
                           document.addEventListener("DOMContentLoaded", function() {
                            if (localStorage.getItem("customer_token")) {
                                const userDropdown = document.getElementById("userDropdown");
                                userDropdown.setAttribute("data-bs-toggle", "dropdown");
                                userDropdown.setAttribute("aria-expanded", "false");
                                profile(); 
                            }
                        });';
        return $profileload;
    }

    private function logout($baseurl, $authRedirect)
    {
        $logout = 'function logout() {
            const token = localStorage.getItem("customer_token");
            fetch("' . $baseurl . 'customer/logout", {
                method: "POST",
                headers: {
                    "Authorization": token,
                    "Accept": "application/json",
                    "Content-Type": "application/json"
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.status_code == 200) {
                    localStorage.removeItem("customer_token");
                    localStorage.removeItem("customer_data");
                    localStorage.removeItem("authid");
                    localStorage.removeItem("cartCount");
                    
                    // Clear the cart via API
                    fetch("' . $baseurl . 'cart/remove-all", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json"
                        },
                        body: JSON.stringify({ authid: localStorage.getItem("authid") })
                    });
                    window.location.href = "' . $authRedirect . '";
                }
            })
            .catch(() => {
                window.location.href = "' . $authRedirect . '";
            });
        }';
        return $logout;
    }

    private function removedCartItem($baseurl, $cartPage)
    {
        $removedCartItem = 'async function removeCartItem(itemId) {
                const cartItem = document.querySelectorAll(`.insta-manage-single-cart[data-id="${itemId}"]`);
                if (!cartItem) return;
                try {
                    const response = await fetch(`' . $baseurl . 'cart/remove`, {
                    method: "POST",
                        headers: {
                            "Content-Type": "application/json"
                        },
                        body: JSON.stringify({ uuid: itemId, authid: localStorage.getItem("authid") })
                    });

                    if (response.ok) {
                    const data = await response.json(); // Parse response if needed
                    cartItem.forEach(el => el.remove()); // Remove from DOM

                    const cartCount = data.cart_count || 0;
                    updateCartBadge(cartCount); // If your function needs the new count
                    const currentPath = window.location.pathname;
                    const isCartPage = currentPath.includes("' . $cartPage->slug . '") || currentPath.endsWith("cart") || currentPath.endsWith("cart.html");
                        if (cartCount == 0 && isCartPage) {
                            window.location.reload();
                        }else{
                            showToast("Item removed from cart successfully.",{type:"success"});
                            const cartManager = initializeCartManager();
                            cartManager.fetchCartSliderItems();
                            fetchCartItems();
                        }
                    } else {
                        showToast("Failed to remove item from cart.", {type:"error"});
                    }
                } catch (error) {
                    console.error("There was an error!", error);
                    showToast("Something went wrong.",{type:"error"});
                }
            }';
        $removeAllCartItems = 'async function removeAllCartItems() {
                    try {
                        const response = await fetch(`' . $baseurl . 'cart/remove-all`, {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/json"
                            },
                            body: JSON.stringify({ authid: localStorage.getItem("authid") })
                        });

                        if (response.ok) {
                            const data = await response.json();
                            showToast("All items removed from cart successfully.", {type:"success"});
                            updateCartBadge(0); // Update cart badge to show 0 items
                            window.location.reload(); // Redirect to home or refresh
                        } else {
                            showToast("Failed to remove all items from cart.", {type:"error"});
                        }
                    } catch (error) {
                        console.error("There was an error!", error);
                        showToast("Something went wrong.", {type:"error"});
                    }
                }';
        return [
            'removeCartItem'     => $removedCartItem,
            'removeAllCartItems' => $removeAllCartItems,
        ];
    }

    private function whishList($baseurl)
    {
        $wishlistScript = 'async function addToWishlist(itemId, element) {
                    try {
                        const response = await fetch(`' . $baseurl . 'customer/wishlist/add`, {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/json",
                                "Accept": "application/json",
                                "Authorization": `${localStorage.getItem("customer_token")}`,
                            },
                            body: JSON.stringify({ product_id: itemId })
                        });
                        const data = await response.json();
                        if (response.ok) {
                            const isInWishlist = element.getAttribute("data-wishlist") === "true";
                            if (isInWishlist) {
                                element.querySelector(".insta-wishlist-filled").classList.add("d-none");
                                element.querySelector(".insta-wishlist-unfilled").classList.remove("d-none");
                                element.setAttribute("data-wishlist", "false");
                                showToast("Removed from wishlist.", {type:"success"});
                            } else {
                                element.querySelector(".insta-wishlist-unfilled").classList.add("d-none");
                                element.querySelector(".insta-wishlist-filled").classList.remove("d-none");
                                element.setAttribute("data-wishlist", "true");
                                showToast(data.message, {type:"success"});
                            }
                        } else if (response.status === 400) {
                            showToast(data.error, {type:"error"});
                        } else {
                            if (response.status === 401) {
                                showToast("Please login.", {type:"error"});
                            } else {
                                showToast("Failed to update wishlist.", {type:"error"});
                            }
                        }
                    } catch (error) {
                        console.error("There was an error!", error);
                        showToast("Something went wrong.", {type:"error"});
                    }
                }
                async function checkWishlistStatus() {
                    const token = localStorage.getItem("customer_token");
                    if (!token) {
                        console.warn("No customer token found in localStorage");
                        return;
                    }

                    try {
                        const response = await fetch(`' . $baseurl . 'customer/wishlist`, {
                            method: "GET",
                            headers: {
                                "Content-Type": "application/json",
                                "Accept": "application/json",
                                "Authorization": `${localStorage.getItem("customer_token")}`,
                            }
                        });

                        if (!response.ok) {
                            if (response.status === 401) {
                                showToast("Please login to view wishlist.", {type:"error"});
                            } else {
                                showToast(`Failed to load wishlist: HTTP ${response.status}`, {type:"error"});
                            }
                            return;
                        }

                        const data = await response.json();

                        if (!data.data || !Array.isArray(data.data)) {
                            console.error("Invalid wishlist data structure:", data);
                            showToast("Invalid wishlist data.", {type:"error"});
                            return;
                        }

                        const wishlistItems = data.data.map(item => item.product_id);

                        const wishlistIcons = document.querySelectorAll(".insta-wishlist-icon");
                        if (wishlistIcons.length === 0) {
                            console.warn("No wishlist-icon elements found in DOM");
                            return;
                        }

                        wishlistIcons.forEach(element => {
                            const onclick = element.getAttribute("onclick");
                            if (!onclick) {
                                console.warn("Wishlist icon missing onclick attribute:", element);
                                return;
                            }

                            const match = onclick.match(/\'([^\']+)\'/);
                            if (!match || !match[1]) {
                                console.warn("Failed to extract product ID from onclick:", onclick);
                                return;
                            }

                            const itemId = match[1];
                            const isInWishlist = wishlistItems.includes(itemId);
                            element.setAttribute("data-wishlist", isInWishlist ? "true" : "false");
                            element.querySelector(".insta-wishlist-filled").classList.toggle("d-none", !isInWishlist);
                            element.querySelector(".insta-wishlist-unfilled").classList.toggle("d-none", isInWishlist);
                        });
                    } catch (error) {
                        console.error("Error fetching wishlist:", error);
                    }
                }

                document.addEventListener("DOMContentLoaded", () => {
                    checkWishlistStatus();
                });';
        return $wishlistScript;
    }

    private function cart($baseurl, $cartPage)
    {
        // Get the cart template to access slider HTML
        $pageTemplate = PageTemplate::where('theme_uuid', $cartPage->theme_id)
            ->where('page_type', 'cart')
            ->first();

        if ($pageTemplate) {
            $cartSliderHtml = $pageTemplate->product_cart_slider_html ?? '';
            $cartPageHtml   = $pageTemplate->product_cart_html ?? '';

            // CartManager initialization script
            $initScript = <<<JS
            let globalCartManager = null;

            function initializeCartManager() {
                if (!globalCartManager) {
                    globalCartManager = new CartManager('{$baseurl}');
                }
                return globalCartManager;
            }

            document.querySelectorAll(".openShoppingCartMainVariation").forEach(element => {
                element.addEventListener("click", function (e) {
                    e.preventDefault();
                    document.body.classList.add("open-shopping-cart");

                    // Initialize cart manager and fetch items
                    const cartManager = initializeCartManager();
                    cartManager.fetchCartSliderItems();
                });
            });

            document.addEventListener("click", function(e) {
                if (e.target.closest(".closeShoppingCartMain")) {
                    e.preventDefault();
                    document.body.classList.remove("open-shopping-cart");
                }
            });
            JS;

            // CartManager class definition
            $cartManagerClass = <<<JS
            class CartManager {
                constructor(baseUrl) {
                    this.baseUrl = baseUrl;
                    this.productDetails = [];
                }

                async fetchCartSliderItems() {
                    try {
                        const authId = localStorage.getItem("authid");
                        const response = await fetch(this.baseUrl + "cart", {
                            headers: { "Content-Type": "application/json", "authid": authId }
                        });

                        const data = await response.json();
                        let cartItems = data.data || [];

                        let sliderContainer = document.querySelector(".insta-manage-cart-slider");
                        if (!sliderContainer) {
                            sliderContainer = document.createElement("div");
                            sliderContainer.className = "insta-manage-cart-slider";
                            document.body.appendChild(sliderContainer);
                        }

                        let sliderItemsHtml = "";
                        let cartItemsHtml   = "";

                        if (cartItems.length > 0) {
                            this.productDetails = [];
                            cartItems.forEach(item => {
                                this.productDetails.push({
                                    "product_id": item.product_id,
                                    "variant_id": item.variant_id,
                                    "product_qty": item.product_qty,
                                    "product_img": item.product_img,
                                    "uuid": item.uuid,
                                    "product_name": item.product_name,
                                    "product_code": item.product_code,
                                    "product_price": item.product_price
                                });

                                sliderItemsHtml += this.processSliderTemplate(item, `{$cartSliderHtml}`);
                                cartItemsHtml   += `{$cartPageHtml}`;
                            });

                            sliderContainer.innerHTML = sliderItemsHtml;

                            const cartListContainer = document.querySelector(".insta-manage-cart-list");
                            if (cartListContainer) {
                                cartListContainer.innerHTML = cartItemsHtml;
                            }

                            this.updateCartTotal();
                            updateCartBadge(data.cart_count);
                        } else {
                            sliderContainer.innerHTML = this.processSliderTemplate({}, `{$cartSliderHtml}`);
                            const emptyCartDiv = document.querySelector(".insta-manage-cart-empty");
                            if (emptyCartDiv) {
                                emptyCartDiv.innerHTML = "<span>Your cart is empty</span>";
                            }
                            document.querySelectorAll(".insta-manage-cart-total").forEach(el => el.textContent = "0.00");
                            updateCartBadge(0);
                        }

                        this.setupEventListeners();
                    } catch (error) {
                        console.error("Error fetching cart items:", error);
                        showToast("Failed to load cart items.", "error");
                    }
                }

                processSliderTemplate(item, template) {
                    return template
                        .replace(/\${item\.uuid}/g, item.uuid || "")
                        .replace(/\${item\.product_img}/g, item.product_img || "")
                        .replace(/\${item\.product_name}/g, item.product_name || "")
                        .replace(/\${item\.product_code}/g, item.product_code || item.uuid || "")
                        .replace(/\${item\.product_price}/g, item.product_price || "0.00")
                        .replace(/\${item\.product_qty}/g, item.product_qty || 0);
                }

                setupEventListeners() {
                    document.querySelectorAll(".insta-manage-single-cart-qty-plus, .insta-manage-single-cart-qty-miuns")
                        .forEach(element => element.addEventListener("click", (event) => this.handleQuantityChange(event)));
                }

                updateCartTotal() {
                    let subTotal = 0;

                    this.productDetails.forEach(item => {
                        const price = parseFloat(item.product_price.replace(/,/g, "") || 0);
                        const qty   = parseInt(item.product_qty || 1);
                        subTotal   += price * qty;
                    });

                    const totalText = subTotal.toLocaleString("en-US", { minimumFractionDigits: 2 });

                    document.querySelectorAll(".insta-manage-cart-total").forEach(el => el.textContent = totalText);
                    document.querySelectorAll(".insta-manage-cart-grand-total").forEach(el => el.textContent = totalText);
                    document.querySelectorAll(".insta-manage-cart-sub-total").forEach(el => el.textContent = totalText);
                }

                async handleQuantityChange(event) {
                    try {
                        const cartItem = event.target.closest(".insta-manage-single-cart-slider");
                        if (!cartItem) return;

                        const quantityInput = cartItem.querySelector(".insta-manage-quantity-input");
                        if (!quantityInput) return;

                        const uuid = quantityInput.getAttribute("data-id");
                        let quantity = parseInt(quantityInput.value) || 1;

                        if (event.target.classList.contains("insta-manage-single-cart-qty-plus")) {
                            quantity += 1;
                        } else if (event.target.classList.contains("insta-manage-single-cart-qty-miuns")) {
                            quantity = Math.max(1, quantity - 1);
                        }

                        await this.updateQuantity(uuid, quantity);
                    } catch (error) {
                        console.error("Error in handleQuantityChange:", error);
                    }
                }

                async updateQuantity(uuid, quantity) {
                    try {
                        const response = await fetch(this.baseUrl + "cart/update_cart_quantity", {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/json",
                                "authid": localStorage.getItem("authid")
                            },
                            body: JSON.stringify({ cart_id: uuid, quantity: quantity })
                        });

                        const data = await response.json();
                        if (response.ok && data.status_code === 200) {
                            document.querySelector(`.insta-manage-quantity-input[data-id="\${uuid}"]`).value = quantity;
                            this.fetchCartSliderItems();
                        } else {
                            console.error("Error updating quantity:", data.message);
                        }
                    } catch (error) {
                        console.error("Error updating quantity:", error);
                    }
                }
            }
            JS;

            // Final script (assembled)
            $CartScript = $initScript . "\n" . $cartManagerClass;

            return $CartScript;
        }
    }

    private function searchProducts($baseurl, $productPage)
    {
        $webUrl = getConfigValue('WEB_URL');
        $productSlug = $productPage->slug ?? 'product';

        $search = <<<JS
            const searchProducts = async (query) => {
                const searchContainer = document.querySelector(".insta-manage-ecommerce-search-results");
                const productCount    = document.querySelector(".insta-search-list-count");
                const overlay         = document.querySelector(".header-search-box-bg");
                const listBox         = document.querySelector(".search-list");

                // Reset previous results
                searchContainer.innerHTML = "";

                // If no query, hide search UI and exit
                if (!query) {
                    toggleSearchUI(false);
                    productCount.textContent = "(0)";
                    return;
                }

                try {
                    const response = await fetch("{$baseurl}search?search=" + encodeURIComponent(query), {
                        method: "GET",
                        headers: { "Content-Type": "application/json" }
                    });

                    if (!response.ok) {
                        throw new Error("HTTP error! status: " + response.status);
                    }

                    const data = await response.json();

                    if (data.data && data.data.length > 0) {
                        toggleSearchUI(true);
                        productCount.textContent = `(\${data.total || data.data.length})`;

                        data.data.forEach(item => {
                            const resultItem = document.createElement("div");
                            resultItem.className = "search-list-item";
                            resultItem.innerHTML = `
                                <div class="search-item-text">
                                    <div class="search-item-img">
                                        <img src="./assets/images/search-img-1.webp" alt="">
                                    </div>
                                    <a href="{$webUrl}/{$productSlug}/\${item.slug}" class="search-item-info">
                                        <span>\${item.name}</span>
                                        <h5>\${item.categories && item.categories.length ? item.categories[0].name : ""}</h5>
                                    </a>
                                </div>
                                <div class="search-item-price">
                                    <span>\${item.unit_price}</span>
                                </div>
                            `;
                            searchContainer.appendChild(resultItem);
                        });
                    } else {
                        toggleSearchUI(false);
                        productCount.textContent = "(0)";
                    }
                } catch (error) {
                    console.error("Search error:", error);
                }

                function toggleSearchUI(show) {
                    if (show) {
                        searchContainer.classList.remove("hidden", "d-none");
                        overlay.classList.add("show");
                        listBox.classList.add("show");
                    } else {
                        searchContainer.classList.add("hidden", "d-none");
                        overlay.classList.remove("show");
                        listBox.classList.remove("show");
                    }
                }
            };
            JS;

        return $search;
    }
}
