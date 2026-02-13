<?php

namespace App\Services;

use App\Models\OtherMenu;
use App\Models\CMS\Module;
use App\Models\CMS\Widget;
use App\Models\CMS\ModuleField;
use App\Models\BussinessSetting;
use App\Models\CMS\PageTemplate;
use App\Models\CMS\ProductDesign;
use App\Models\Page;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;

class ShortcodeService
{
    protected $shortcodes = [];

    public function register($name, $callback)
    {
        $this->shortcodes[$name] = $callback;
    }

    public function parse($content, $type = null, $page = null, $themeName = null,$lang = null)
    {
         
        try {
            $defaultLang = defaultLanguages()->app_language_code;
            $themeName = $themeName;
            // Handle cart count for default page
            $baseurl = BussinessSetting::where('type', 'api_base_url')->first();
            $baseurl = $baseurl ? rtrim($baseurl->value, '/') : '';
            if ($type == 'frontend' && $page) {
                if (empty($baseurl)) {
                    return 'Error: API base URL is not configured.';
                }
                $cartCountScript = $this->cartCountScript($baseurl);
                $content .= $cartCountScript;
            }

            // Handle other shortcodes for frontend
            if ($type == 'frontend') {
                $pattern = '/\[([A-Za-z_-]+)(.*?)\](?:\[\/\1\])?/';
                $shortcodes = str_replace('<shortcode>', '', $content);
                $shortcodes = str_replace('</shortcode>', '', $shortcodes);
                $content = preg_replace_callback($pattern, function ($matches) use ($shortcodes, $baseurl, $page, $themeName,$lang,$defaultLang) {
                    $shortcode = $matches[1];
                    $shortcodeString = $matches[0];
                    $attributes = $this->parseAttributes($shortcodeString, $themeName,$lang);
                    $viewPath = 'components.' . $themeName . '.'.$lang.'.' . strtolower($shortcode);
                    if (strpos($shortcode, 'insta-manage-') === 0 && $page != null) {
                        if ($page->page_type  === 'product_detail') {
                            $pageTemplate = PageTemplate::where('theme_uuid', $page->theme_id)
                                ->where('page_type', 'product_detail')
                                ->first();
                            if ($pageTemplate != null) {
                                $apical = $this->productDetail($page, $baseurl);
                                return $apical;
                            }
                        } elseif ($page->page_type === 'cart') {
                            $cartScript = $this->cart($page);
                            return $cartScript;
                        } elseif ($page->page_type === 'wishlist') {
                            $cartScript = $this->wishlist($page, $baseurl);
                            return $cartScript;
                        } elseif ($page->page_type == "checkout") {
                            $checkoutScript = $this->checkout($page, $baseurl);
                            return $checkoutScript;
                        } elseif ($page->page_type == 'thankyou') {
                            $cartScript = $this->thankyou($page, $baseurl);
                            return $cartScript;
                        } elseif ($page->page_type == 'login') {
                            $loginScript = $this->login($page, $baseurl);
                            return $loginScript;
                        } elseif ($page->page_type == 'signup') {
                            $signupScript = $this->singup($page,$baseurl);
                            return $signupScript;
                        } elseif ($page->page_type == 'dashboard') {
                            $dashboardSrcipt = $this->dashboard($page,$baseurl);
                            return $dashboardSrcipt;
                        } elseif ($page->page_type == 'order') {
                            $orderSrcipt = $this->order($page,$baseurl);
                            return $orderSrcipt;
                            
                        } elseif ($page->page_type == 'order_detail') {
                            $pageTemplate = PageTemplate::where('theme_uuid', $page->theme_id)
                                ->where('page_type', 'order_detail')
                                ->first();
                            if ($pageTemplate != null) {
                                $baseurl = BussinessSetting::where('type', 'api_base_url')->first();
                                $productbaseurl = BussinessSetting::where('type', 'api_base_product_url')->first();
                                $baseurl = $baseurl ? $baseurl->value : '';
                                $productbaseurl = $productbaseurl ? $productbaseurl->value : '';
                                $productClass = $pageTemplate->product_class;
                                $pageHtml = $pageTemplate->page_html;
                                $orderSrcipt = '<script>document.addEventListener("DOMContentLoaded", () => {
                                    profile();
                                    getOrders();
                                });
                                function getOrders() {
                                    const token = localStorage.getItem("customer_token");
                                    const urlParams = new URLSearchParams(window.location.search);
                                    const orderCode = urlParams.get("order_code");
                                    if (token) {
                                        fetch("' . $baseurl . 'customer/authOrderDetail/?order_code=" + orderCode, {
                                            headers: {
                                                "Authorization": token,
                                            },
                                        })
                                            .then(response => response.json())
                                            .then(data => {
                                                if (data.data.length == 0) {
                                                    document.querySelector(".' . $pageTemplate->product_class . '").innerHTML = "<tr class=\"hover:bg-gray-100\"><td colspan=\"3\" class=\"py-2 px-4 border-b text-center\">No records found</td></tr>";
                                                } else {
                                                    const item = data.data;
                                                    const htmlCurrent = `' . $pageTemplate->page_html . '`;
                                                    let htmlVaraint = ' . $pageTemplate->html_variant . ';
                                                    document.querySelector(".' . $pageTemplate->product_class . '").innerHTML = htmlCurrent;
                                                    if (htmlVaraint && htmlVaraint["order"] && Object.keys(htmlVaraint).length > 0) {
                                                        let itemVariant = item.order_details;
                                                        let newHTML = "";
                                                        let allColorHtml = "";
                                                        let variantHtml = htmlVaraint["order"];
                                                        // Get all dynamic variables from the template
                                                        allColorHtml = itemVariant.map(item => {
                                                            return variantHtml.replace(/\$\{item\.([^}]+)\}/g, (match, p1) => {
                                                                return item[p1] || "";
                                                            });
                                                        }).join("");
                                                        document.querySelector(".insta-order-product-list").innerHTML = allColorHtml;
                                                    }
                                                }                                                
                                            })
                                            .catch(error => console.error("There was an error!", error));
                                    } else {
                                        console.error("No authentication token found!");
                                    }
                                }
                                </script>';
                                return $orderSrcipt;
                            }
                        } elseif ($page->page_type == 'track_order') {
                            $pageTemplate = PageTemplate::where('theme_uuid', $page->theme_id)
                                ->where('page_type', 'track_order')
                                ->first();

                            if ($pageTemplate != null) {
                                $baseurl = BussinessSetting::where('type', 'api_base_url')->first();
                                $productbaseurl = BussinessSetting::where('type', 'api_base_product_url')->first();
                                $baseurl = $baseurl ? $baseurl->value : '';
                                $product_cart_html = $pageTemplate->product_cart_html;
                                $productbaseurl = $productbaseurl ? $productbaseurl->value : '';
                                $productClass = $pageTemplate->product_class;

                                // Then add the JavaScript
                                $html = '<script>
                                    document.addEventListener("DOMContentLoaded", () => {
                                        // Clear order_code from URL on page load/refresh
                                        const url = new URL(window.location);
                                        if (url.searchParams.has("order_code")) {
                                            url.searchParams.delete("order_code");
                                            window.history.replaceState({}, "", url);
                                        }
                                        profile();
                                        getOrders();
                                    });

                                    function searchOrder() {
                                        const trackingId = document.querySelector("#txtsearchField")?.value.trim();
                                        if (!trackingId) {
                                            alert("Please enter a tracking ID");
                                            return;
                                        }

                                        // Update URL with order_code query parameter
                                        const url = new URL(window.location);
                                        url.searchParams.set("order_code", trackingId);
                                        window.history.pushState({}, "", url);

                                        // Update the tracking ID display
                                        const trackingIdElement = document.querySelector("#txttrackingid");
                                        if (trackingIdElement) {
                                            trackingIdElement.textContent = `Tracking ID: ${trackingId}`;
                                        }

                                        // Fetch order details
                                        getOrders();
                                    }

                                    function getOrders() {
                                        const token = localStorage.getItem("customer_token");
                                        const urlParams = new URLSearchParams(window.location.search);
                                        const orderCode = urlParams.get("order_code");
                                        
                                        const divOrderDetail = document.querySelector(".insta-order-detail");
                                        const divEmptyOrderDetail = document.querySelector(".insta-empty-order-detail");

                                        // Clear previous content
                                        if (divOrderDetail) divOrderDetail.innerHTML = "";
                                        if (divEmptyOrderDetail) divEmptyOrderDetail.innerHTML = "";

                                        if (!orderCode) {
                                            if (divOrderDetail) divOrderDetail.classList.add("d-none");
                                            if (divEmptyOrderDetail) {
                                                divEmptyOrderDetail.classList.remove("d-none");
                                                divEmptyOrderDetail.innerHTML = `' . $pageTemplate->product_cart_html . '`;
                                            }
                                            return;
                                        }

                                        if (token) {
                                            fetch("' . $baseurl . 'customer/authOrderDetail/?order_code=" + orderCode, {
                                                headers: {
                                                    "Authorization": token,
                                                },
                                            })
                                                .then(response => response.json())
                                                .then(data => {
                                                    if (!data.data || data.data.length === 0) {
                                                        // Order not found - show only product_cart_html
                                                        if (divOrderDetail) divOrderDetail.classList.add("d-none");
                                                        if (divEmptyOrderDetail) {
                                                            divEmptyOrderDetail.classList.remove("d-none");
                                                            divEmptyOrderDetail.innerHTML = `' . $pageTemplate->product_cart_html . '` + 
                                                                `<p class="mb-0">No order found for Tracking ID: ${orderCode}</p>`;
                                                        }
                                                    } else {
                                                        // Order found - show order details with page_html
                                                        if (divEmptyOrderDetail) divEmptyOrderDetail.classList.add("d-none");
                                                        if (divOrderDetail) {
                                                            divOrderDetail.classList.remove("d-none");
                                                            const item = data.data;
                                                            const htmlCurrent = `' . $pageTemplate->page_html . '`;
                                                            let htmlVaraint = ' . $pageTemplate->html_variant . ';
                                                            
                                                            // Only set the content once in divOrderDetail
                                                            divOrderDetail.innerHTML = htmlCurrent;
                                                            
                                                            if (htmlVaraint && htmlVaraint["order"] && Object.keys(htmlVaraint).length > 0) {
                                                                let itemVariant = item.order_details;
                                                                let variantHtml = htmlVaraint["order"];
                                                                let allColorHtml = itemVariant.map(item => {
                                                                    return variantHtml.replace(/\$\{item\.([^}]+)\}/g, (match, p1) => {
                                                                        return item[p1] || "";
                                                                    });
                                                                }).join("");
                                                                
                                                                const productList = document.querySelector(".insta-order-product-list");
                                                                if (productList) {
                                                                    productList.innerHTML = allColorHtml;
                                                                }
                                                            }
                                                        }
                                                    }                                                
                                                })
                                                .catch(error => {
                                                    console.error("There was an error!", error);
                                                    if (divOrderDetail) divOrderDetail.classList.add("d-none");
                                                    if (divEmptyOrderDetail) {
                                                        divEmptyOrderDetail.classList.remove("d-none");
                                                        divEmptyOrderDetail.innerHTML = `' . $pageTemplate->product_cart_html . '` + 
                                                            `<p class="text-center">Error fetching order details. Please try again.</p>`;
                                                    }
                                                });
                                        } else {
                                            console.error("No authentication token found!");
                                            if (divOrderDetail) divOrderDetail.classList.add("d-none");
                                            if (divEmptyOrderDetail) {
                                                divEmptyOrderDetail.classList.remove("d-none");
                                                divEmptyOrderDetail.innerHTML = `' . $pageTemplate->product_cart_html . '` + 
                                                    `<p class="text-center">Please log in to track your order.</p>`;
                                            }
                                        }
                                    }
                                </script>';

                                return $html;
                            }
                        } elseif ($page->page_type == 'forget_password') {
                            $pageTemplate = PageTemplate::where('theme_uuid', $page->theme_id)
                                ->where('page_type', 'forget_password')
                                ->first();
                            if ($pageTemplate != null) {
                                $baseurl = BussinessSetting::where('type', 'api_base_url')->first();
                                $baseurl = $baseurl ? $baseurl->value : '';
                                $pageHtml = $pageTemplate->page_html;
                                $forgetPasswordScript = "<script>
                                    document.addEventListener('DOMContentLoaded', function () {
                                        profile();
                                        document.querySelector('.insta-forget-password-form').addEventListener('submit', forgetPasswordForm);
                                    });
                                    async function forgetPasswordForm(event) {
                                        event.preventDefault(); // Prevent the default form submission
                                        // Gather form data
                                        const form = event.target;
                                        const data = new FormData(form);
                                        fetch('" . $baseurl . "customer/forget-password', {
                                            method: 'POST',
                                            body: data
                                        })
                                        .then(response => response.json())
                                        .then(response => {
                                            if (response.status_code == 200) {
                                                showToast('Password reset link sent to your email!','success');
                                                // Redirect or update UI here
                                            } else {
                                                showToast('Error: ' + (response.message || 'Unknown error'),{type:'error'});
                                            }
                                        })
                                        .catch(() => {
                                            showToast('An error occurred. Please try again.',{type:'error'});
                                        });
                                    }
                                </script>";
                                return $pageHtml . $forgetPasswordScript;
                            }
                        } elseif ($page->page_type == 'reset_password') {
                            $pageTemplate = PageTemplate::where('theme_uuid', $page->theme_id)
                                ->where('page_type', 'reset_password')
                                ->first();
                            $pageTemplateLogin = Page::where('theme_id', $page->theme_id)
                                ->where('page_type', 'login')
                                ->first();
                            if ($pageTemplate != null) {
                                $baseurl = BussinessSetting::where('type', 'api_base_url')->first();
                                $baseurl = $baseurl ? $baseurl->value : '';
                                $pageHtml = $pageTemplate->page_html;
                                $resetPasswordScript = "<script>
                                    document.addEventListener('DOMContentLoaded', function () {
                                        profile();
                                        document.querySelector('.insta-reset-password-form').addEventListener('submit', resetPasswordForm);
                                    });

                                    const urlParams = new URLSearchParams(window.location.search);
                                    const token = urlParams.get('token');
                                    if (!token) {
                                        showToast('Token is missing!',{type:'error'});
                                        window.history.back();
                                    }

                                    async function resetPasswordForm(event) {
                                        event.preventDefault(); // Prevent the default form submission
                                        // Gather form data
                                        const form = event.target;
                                        const data = new FormData(form);
                                        data.append('token', token); // Append the token to the form data
                                        fetch('" . $baseurl . "customer/reset-password', {
                                            method: 'POST',
                                            body: data
                                        })
                                        .then(response => response.json())
                                        .then(response => {
                                            if (response.status_code == 200) {
                                                showToast('Password reset successfully!','success');
                                                // Redirect to login page
                                                const loginPage = '" . $pageTemplateLogin->slug . "';
                                                const currentURL = new URL(window.location.href);
                                                const newURL = new URL(currentURL.origin + currentURL.pathname.replace('" . $page->slug . "', loginPage), currentURL);
                                                window.location.href = newURL.toString();
                                            } else if (response.status_code == 422) {
                                                errorMessage = Object.entries(response.errors)
                                                    .map(([field, messages]) =>  field + ': ' + messages.join('\\n'))
                                                    .join('\\n');
                                                showToast('Failed to reset password:\\n' + errorMessage, {type:'error'});
                                            } else {
                                                showToast('Error: ' + (response.message || 'Unknown error'),{type:'error'});
                                            }
                                        })
                                        .catch(() => {
                                            showToast('An error occurred. Please try again.',{type:'error'});
                                        });
                                    }
                                </script>";
                                return $pageHtml . $resetPasswordScript;
                            }
                        } elseif ($page->page_type == 'customer_profile') {
                            $pageTemplate = PageTemplate::where('theme_uuid', $page->theme_id)
                                ->where('page_type', 'customer_profile')
                                ->first();
                            if ($pageTemplate != null) {
                                $baseurl = BussinessSetting::where('type', 'api_base_url')->first();


                                $baseurl = $baseurl ? $baseurl->value : '';
                                $pageHtml = $pageTemplate->page_html;
                                $productClass = $pageTemplate->product_class;
                                $profileScript = "<script>
                                    let iti;
                                    document.addEventListener('DOMContentLoaded', function () {
                                        profile().then(() => {
                                            let item =  localStorage.getItem('customer_data');
                                            item = item ? JSON.parse(item) : {};
                                            const htmlCurrent = `" . $pageHtml . "`;
                                            document.querySelector('." . $productClass . "').innerHTML = htmlCurrent;
                                            document.querySelector('.insta-customer-profile-form').addEventListener('submit', profileForm);

                                            // intlTelInput JS
                                            var input = document.querySelector('#myaccountphone');
                                            if (input) {
                                            iti = window.intlTelInput(input, {
                                                initialCountry: 'ae',
                                                preferredCountries: ['ae'],
                                                autoPlaceholder: 'polite',
                                                showSelectedDialCode: true,
                                                utilsScript: 'https://digitalgraphiks.co.uk/demo/nks/assets/js/utils.js',
                                                hiddenInput: () => ({ phone: 'myaccountphone' }) // Add hidden input for full phone number
                                            });

                                            if (item.phone) {
                                                iti.setNumber(item.phone);
                                            }

                                            function validatePhoneInput() {
                                                const form = document.querySelector('.insta-customer-profile-form');
                                                const errorText = input.parentElement.parentElement.querySelector('.error-txt');

                                                if (input.value.trim()) {
                                                if (iti.isValidNumber()) {
                                                    input.parentElement.parentElement.classList.remove('error');
                                                    errorText.innerHTML = '';
                                                    form.querySelector('.profile-save-btn').disabled = false;
                                                } else {
                                                    input.parentElement.parentElement.classList.add('error');
                                                    errorText.innerHTML = 'Invalid Number';
                                                    form.querySelector('.profile-save-btn').disabled = true; // disable on error
                                                }
                                                } else {
                                                input.parentElement.parentElement.classList.remove('error');
                                                errorText.innerHTML = '';
                                                form.querySelector('.profile-save-btn').disabled = false;
                                                }
                                            }

                                            input.addEventListener('blur', validatePhoneInput);
                                            input.addEventListener('keyup', validatePhoneInput);
                                            }
                                            // intlTelInput JS End

                                            const passwordForm = document.querySelector('.insta-customer-password-form');
                                            if (passwordForm) {
                                                passwordForm.addEventListener('submit', changePasswordForm);
                                            }
                                        });
                                        let publicurl = '" . getConfigValue('APP_ASSET_PATH') . "';
                                    });
                                    async function profileForm(event) {
                                        event.preventDefault(); // Prevent the default form submission
                                        // Gather form data
                                        const token = localStorage.getItem('customer_token');
                                        const form = event.target;
                                        const data = new FormData(form);
                                        data.append('phone', iti.getNumber());
                                        const imageFile = $('.insta-customer-user-img')[0].files[0];
                                        if (imageFile) {
                                            data.append('image', imageFile);
                                        }
                                        fetch('" . $baseurl . "customer/profile-update', {
                                            method: 'POST',
                                            body: data,
                                            headers: {
                                                'Authorization': token,
                                            },
                                        })
                                        .then(response => response.json())
                                        .then(response => {
                                            if (response.status_code == 200) {
                                                showToast('Profile updated successfully!','success');
                                                profile().then(() => {
                                                }).catch(error => {
                                                    showToast('Failed to refresh profile data.', 'error');
                                                });
                                                // Redirect or update UI here
                                            } else {
                                                let errorMessage = response.errors;
                                                if (errorMessage && typeof errorMessage === 'object') {
                                                    errorMessage = Object.values(errorMessage).join('\\n');
                                                    showToast('Error: ' + (errorMessage || 'Unknown error'),{type:'error'});
                                                }else{
                                                    showToast('Error: ' + (response.message || 'Unknown error'),{type:'error'});
                                                }
                                            }
                                        })
                                        .catch(() => {
                                            showToast('An error occurred. Please try again.',{type:'error'});
                                        });
                                    }

                                    async function changePasswordForm(event) {
                                        event.preventDefault();
                                        const token = localStorage.getItem('customer_token');
                                        const form = event.target;
                                        const data = new FormData(form);
                                        
                                        fetch('" . $baseurl . "customer/change-password', {
                                            method: 'POST',
                                            body: data,
                                            headers: {
                                                'Authorization': token,
                                            },
                                        })
                                        .then(response => response.json())
                                        .then(response => {
                                            if (response.status_code == 200) {
                                                showToast('Password changed successfully!','success');
                                                form.reset(); // Clear the form
                                            } else {
                                                let errorMessage = response.errors;
                                                if (errorMessage && typeof errorMessage === 'object') {
                                                    errorMessage = Object.values(errorMessage).join('\\n');
                                                    showToast('Error: ' + (errorMessage || 'Unknown error'),{type:'error'});
                                                } else {
                                                    showToast('Error: ' + (response.message || 'Unknown error'),{type:'error'});
                                                }
                                            }
                                        })
                                        .catch(() => {
                                            showToast('An error occurred. Please try again.',{type:'error'});
                                        });
                                    }
                                </script>";
                                return $profileScript;
                            }
                        } elseif ($page->page_type == 'product_listing') {
                            $pageTemplate = PageTemplate::where('theme_uuid', $page->theme_id)
                                ->where('page_type', 'product_listing')
                                ->first();
                            if ($pageTemplate != null) {
                                $baseurl = BussinessSetting::where('type', 'api_base_url')->first();
                                $baseurl = $baseurl ? $baseurl->value : '';
                                $pageHtml = $pageTemplate->page_html;
                                $product_cart_html = $pageTemplate->product_cart_html;
                                $productClass = $pageTemplate->product_class;
                                $productListingScript = "<script>
                                    let selectedParam = {};
                                    async function productListing() {
                                        let checkParam = {};

                                        // Collect all checked filter inputs
                                        document.querySelectorAll('.insta-manage-filter-input:checked').forEach(input => {
                                            const key = input.getAttribute('data-type');
                                            const value = input.value;
                                            if (checkParam[key]) {
                                                checkParam[key] += ',' + value;
                                            } else {
                                                checkParam[key] = value;
                                            }
                                        });

                                        let queryString = new URLSearchParams(checkParam);
                                       
                                        try {
                                            const response = await fetch('" . $baseurl . "get_product?'+queryString.toString());
                                            if (!response.ok) {
                                                if (response.status === 404) {
                                                    document.querySelector('.insta-manage-product-listing').innerHTML = '<p>No products found.</p>';
                                                    return;
                                                }
                                                throw new Error('HTTP error! status: '+response.status);
                                            }
                                            const data = await response.json();
                                            let productListing = data.data;
                                            if (data.collection?.meta_title) {
                                                document.title = data.collection.meta_title;
                                            }
                                            if (data.collection?.meta_description) {
                                                document.querySelector(`meta[name='description']`).setAttribute('content', data.collection.meta_description);
                                            }
                                            let productHtml = '';
                                            if (productListing.length > 0) {
                                                productListing.forEach(item => {
                                                    productHtml += `" . $product_cart_html . "`;
                                                });
                                            }
                                            document.querySelector('.insta-manage-product-listing').innerHTML = productHtml;
                                            checkWishlistStatus();
                                        } catch (error) {
                                            console.error('Product fetch error:', error);
                                        }
                                    }
                                    async function categoryfilter() {
                                        let htmlVaraint = " . preg_replace('/\s+/', ' ', $pageTemplate->html_variant) . ";
                                        const response = await fetch('" . $baseurl . "get_active_filter?');
                                        if (!response.ok) {
                                            throw new Error('HTTP error! status: '+response.status);
                                        }
                                        const data = await response.json();
                                        const filters = data.data;
                                        let item = data.data;
                                        const lastSegment = window.location.pathname.split('/').pop();
                                        const queryParams = new URLSearchParams(window.location.search);
                                        if (Object.keys(htmlVaraint).length > 0) {
                                            let htmlVariantObject = htmlVaraint;
                                            let htmlVariantObjectKey = Object.keys(htmlVaraint);
                                            for (let key in htmlVaraint) {
                                                const container = document.getElementById(key);
                                                if (!container) continue;

                                                const template = htmlVaraint[key];
                                                container.innerHTML = filters[key].map(item =>
                                                  template.replace(/\\\$\\{item\.([^}]+)}/g, (_, p1) => item[p1] || '')
                                                ).join('');
                                                

                                                // Auto-check matching checkboxes from selectedParam
                                                // Apply 'checked' from last segment or query param
                                                const expectedValues = [];

                                                // From last segment (collection)
                                                if (key === 'collection' && lastSegment) {
                                                    expectedValues.push(lastSegment);
                                                }

                                                // From query string
                                                const queryValue = queryParams.get(key);
                                                if (queryValue) {
                                                    expectedValues.push(...queryValue.split(','));
                                                }

                                                expectedValues.forEach(val => {
                                                    const checkbox = container.querySelector(`.insta-manage-filter-input[data-id='`+val+`']`);
                                                    if (checkbox) checkbox.checked = true;
                                                });

                                                // Event listener for live filtering
                                                container.querySelectorAll('.insta-manage-filter-input').forEach(cb => {
                                                    cb.addEventListener('change', productListing);
                                                });
                                            }
                                        }

                                    }
                                    document.addEventListener('DOMContentLoaded',async () => {
                                        const url = new URL(window.location.href);
                                        const lastSegment = url.pathname.split('/').pop();

                                        // Set default filter from last segment
                                        selectedParam['collection'] = lastSegment;

                                        // Also include any other params (like ?category=xyz)
                                        const queryParams = new URLSearchParams(window.location.search);
                                        queryParams.forEach((value, key) => {
                                            if (selectedParam[key]) {
                                                selectedParam[key] += ',' + value;
                                            } else {
                                                selectedParam[key] = value;
                                            }
                                        });

                                        // Call both functions
                                        await categoryfilter(); // This will render filters
                                        productListing();       // This will load products
                                    });
                                </script>";
                                return $pageHtml . $productListingScript;
                            }
                        } elseif ($page->page_type === 'address') {
                            $pageTemplate = PageTemplate::where('theme_uuid', $page->theme_id)
                                ->where('page_type', 'address')
                                ->first();
                            if ($pageTemplate != null) {
                                $baseurl = BussinessSetting::where('type', 'api_base_url')->first();
                                $baseurl = $baseurl ? $baseurl->value : '';
                                $pageHtml = $pageTemplate->page_html ?? '';
                                $product_cart_html = $pageTemplate->product_cart_html ?? '';

                                $addressScript = '<script>
                                    async function GetAddress() {
                                        try {
                                            const authId = localStorage.getItem("authid");
                                            const headers = {
                                                "Content-Type": "application/json",
                                                "Accept": "application/json",
                                                "Authorization": `${localStorage.getItem("customer_token")}`,
                                                "authid": authId
                                            };
                                            const response = await fetch("' . $baseurl . 'customer/address/get_address", { headers });
                                            if (!response.ok) {
                                                throw new Error(`HTTP error! status: ${response.status}`);
                                            }
                                            const data = await response.json();
                                            const addressContainer = document.querySelector(".insta-address-list");
                                            addressContainer.innerHTML = ""; // Clear existing content
                                            if (data.data && data.data.length > 0) {
                                                data.data.forEach(item => {
                                                    let addressHtml = `' . $product_cart_html . '`;
                                                    addressContainer.innerHTML += addressHtml;
                                                });
                                                
                                                
                                            } else {
                                                addressContainer.innerHTML = "<p>No addresses found</p>";
                                            }
                                        } catch (error) {
                                            console.error("Error fetching address:", error);
                                            showToast("Error loading addresses", "error");
                                        }
                                    }

                                    async function AddressForm(event) {
                                        event.preventDefault();
                                        const form = event.target;
                                        const data = new FormData(form);
                                        data.append("customer_id", localStorage.getItem("authid"));
                                        data.append("is_default", form.querySelector(`[name="default_shipping"]`).checked ? 1 : 0);
                                        try {
                                            const response = await fetch("' . $baseurl . 'customer/address/add_address", {
                                                method: "POST",
                                                headers: {
                                                    "Accept": "application/json",
                                                    "Authorization": localStorage.getItem("customer_token"),
                                                    "authid": localStorage.getItem("authid")
                                                },
                                                body: data
                                            });
                                            const result = await response.json();
                                            if (result.status_code === 201) {
                                                showToast(result.message, "success");
                                                GetAddress();
                                                $(".insta-address-add-modal").modal("hide");
                                            } else if (result.status_code === 422) {
                                                showToast(result.message, "error");
                                            } else {
                                                showToast(result.error || "Failed to update address", "error");
                                            }
                                        } catch (error) {
                                            console.error("Error adding address:", error);
                                            showToast("Something went wrong.", "error");
                                        }
                                    }

                                    async function populateEditForm(uuid) {
                                        try {
                                            const response = await fetch("' . $baseurl . 'customer/address/get_address", {
                                                headers: {
                                                    "Content-Type": "application/json",
                                                    "Accept": "application/json",
                                                    "Authorization": localStorage.getItem("customer_token"),
                                                    "authid": localStorage.getItem("authid")
                                                }
                                            });
                                            const data = await response.json();
                                            const address = data.data.find(item => item.uuid === uuid);
                                            if (address) {
                                                const form = document.querySelector(".insta-edit-address-form");
                                                form.querySelector(`[name="address"]`).value = address.address || "";
                                                form.querySelector(`[name="country"]`).value = address.country || "";
                                                form.querySelector(`[name="city"]`).value = address.city || "";
                                                form.querySelector(`[name="apartment"]`).value = address.apartment || "";
                                                form.querySelector(`[name="postal_code"]`).value = address.postal_code || "";
                                                form.querySelector(`[name="address_phone"]`).value = address.address_phone || "";
                                                form.querySelector(`[name="default_shipping"]`).checked = address.is_default === 1;
                                                const typeSelect = form.querySelector(`[name="type"]`);
                                                const typeValue = address.type || "";
                                                $(typeSelect).val(typeValue).trigger("change");
                                                form.dataset.uuid = uuid; // Store UUID in form dataset
                                            }
                                        } catch (error) {
                                            console.error("Error fetching address for edit:", error);
                                            showToast("Error loading address data", {type:"error"});
                                        }
                                    }

                                    async function EditAddress(event) {
                                        event.preventDefault();
                                        const form = event.target;
                                        const uuid = form.dataset.uuid;
                                        const data = new FormData(form);
                                        data.append("customer_id", localStorage.getItem("authid"));
                                        data.append("is_default", form.querySelector(`[name="default_shipping"]`).checked ? 1 : 0);
                                        try {
                                            const response = await fetch("' . $baseurl . 'customer/address/update_address/" + uuid, {
                                                method: "POST",
                                                headers: {
                                                    "Accept": "application/json",
                                                    "Authorization": localStorage.getItem("customer_token"),
                                                    "authid": localStorage.getItem("authid")
                                                },
                                                body: data
                                            });
                                            const result = await response.json();
                                            if (result.status_code === 200) {
                                                showToast(result.message, "success");
                                                GetAddress();
                                                $(".insta-address-edit-modal").modal("hide");
                                            } else if (result.status_code === 422) {
                                                showToast(result.message, {type:"error"});
                                            } else {
                                                showToast(result.error || "Failed to update address", {type:"error"});
                                            }

                                        } catch (error) {
                                            console.error("Error updating address:", error);
                                            showToast("Something went wrong.", {type:"error"});
                                        }
                                    }

                                    document.addEventListener("DOMContentLoaded", () => {
                                        profile();
                                        GetAddress();
                                        document.querySelector(".insta-add-address-form").addEventListener("submit", AddressForm);
                                        document.querySelector(".insta-edit-address-form").addEventListener("submit", EditAddress);
                                    });
                                </script>';

                                return $pageHtml . $addressScript;
                            }
                        }
                    }

                    // Handle other shortcodes
                    if ($attributes == []) {
                        $viewPathModule1 = 'components.' . $themeName . '.forms.' . $shortcode;
                        if (view()->exists($viewPathModule1)) {
                            return view($viewPathModule1)->render();
                        }
                    }
                    if (view()->exists($viewPath)) {
                        $viewContent = file_get_contents(resource_path('views/' . str_replace('.', '/', $viewPath) . '.blade.php'));
                        $viewPattern = '/\{\{\s*\$([A-Za-z0-9_]+)\s*\}\}/';
                        preg_match_all($viewPattern, $viewContent, $viewMatches);
                        $defaultAttributes = array_fill_keys($viewMatches[1], '');
                        $attributes = array_merge($defaultAttributes, $attributes);

                        preg_match_all('/{{\s*\$([a-zA-Z_][a-zA-Z0-9_]*)\s*}}|{{{\s*\$([a-zA-Z_][a-zA-Z0-9_]*)\s*}}}|{!!\s*\$([a-zA-Z_][a-zA-Z0-9_]*)\s*!!}/', $viewContent, $matches);
                        $variables = array_filter(array_merge($matches[1], $matches[2], $matches[3]));
                        $allfilevariables = array_unique($variables);

                        foreach ($allfilevariables as $allfilevariable) {
                            if (!in_array($allfilevariable, array_keys($attributes))) {
                                $attributes[$allfilevariable] = '';
                            }
                        }

                        return view($viewPath, $attributes)->render();
                    }else{
                        $viewPath = 'components.' . $themeName . '.'.$defaultLang.'.' . strtolower($shortcode);
                        if (view()->exists($viewPath)) {
                            $viewContent = file_get_contents(resource_path('views/' . str_replace('.', '/', $viewPath) . '.blade.php'));
                            $viewPattern = '/\{\{\s*\$([A-Za-z0-9_]+)\s*\}\}/';
                            preg_match_all($viewPattern, $viewContent, $viewMatches);
                            $defaultAttributes = array_fill_keys($viewMatches[1], '');
                            $attributes = array_merge($defaultAttributes, $attributes);

                            preg_match_all('/{{\s*\$([a-zA-Z_][a-zA-Z0-9_]*)\s*}}|{{{\s*\$([a-zA-Z_][a-zA-Z0-9_]*)\s*}}}|{!!\s*\$([a-zA-Z_][a-zA-Z0-9_]*)\s*!!}/', $viewContent, $matches);
                            $variables = array_filter(array_merge($matches[1], $matches[2], $matches[3]));
                            $allfilevariables = array_unique($variables);

                            foreach ($allfilevariables as $allfilevariable) {
                                if (!in_array($allfilevariable, array_keys($attributes))) {
                                    $attributes[$allfilevariable] = '';
                                }
                            }

                            return view($viewPath, $attributes)->render();
                        }
                    }

                    return $matches[0]; // Return original shortcode if view does not exist
                }, $shortcodes);

                return $content;
            } else {
                return json_decode($content, true);
            }
        } catch (\Throwable $th) {
            Log::error($th->getMessage());
            return response()->json([
                'status_code' => 500,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    protected function parseAttributes($shortcode, $themeName = null,$lang = null)
    {
        if (trim($shortcode) != '') {
            $pattern = '/(\w+)=\"([^\"]+)\"/';
            preg_match_all($pattern, $shortcode, $matches);
            $attributes = [];
            foreach ($matches[1] as $index => $attribute) {
                if (strpos($attribute, 'input_') === 0) {
                    $attributes[$attribute] = getConfigValue('APP_ASSET_PATH') . $matches[2][$index];
                    // $attributes[$attribute] = $matches[2][$index];
                } elseif (strpos($attribute, 'module_') === 0) { 
                    $module_file = str_replace("module_", "", $attribute);
                    // Set the value to the view for 'module_' attributes
                   
                    $viewPathModule = 'components.' . $themeName . '.modules.'.$lang.'.' . $module_file;
                    $module = Module::where('shortkey', $module_file)->where('moduletype', 'api')->first();
                    
                    $baseurl = BussinessSetting::where('type', 'api_base_url')->first();
                     //dd($module_file,$lang,$module,$baseurl);
                    if (view()->exists($viewPathModule) && $module != null && $module->moduletype == 'api') {
                        if ($module->api_url != '') {
                            $apiLink = $module->api_url;
                            $moduleClass = $module->moduleclass;
                            $baseurl = $baseurl ? $baseurl->value : '';
                            //dd(view($viewPathModule)->render());
                            $apical = '<script>
                                var urlParams = new URLSearchParams(window.location.search);
                                var apiLink = "' . $baseurl . $apiLink . '";
                                var hasQueryParams = (new URL(apiLink)).search.length > 0;
                                var queryString = hasQueryParams ? "&" : "?" ;
                                fetch(`${apiLink}${queryString}`, {
                                    method: "GET",
                                    headers: {
                                        "Content-Type": "application/json"
                                    }
                                })
                                .then(response => {
                                    if (!response.ok) {
                                        throw new Error(`HTTP error! status: ${response.status}`);
                                    }
                                    return response.json();
                                })
                                .then(data => {
                                    console.log(data);

                                    // Assuming view rendering is dynamically handled in JavaScript
                                    const html = data.data.map(item => {
                                        return `' . view($viewPathModule)->render() . '`; // Replace this with actual rendering logic
                                    }).join("");

                                    document.querySelector(`.' . $moduleClass . '`).innerHTML = html;
                                })
                                .catch(error => {
                                    console.error("There was an error!", error);
                                });</script>';

                            $attributes[$attribute] = $apical;
                        }
                    } else if (view()->exists($viewPathModule)) {
                        $attributes[$attribute] = view($viewPathModule)->render();
                    } else {
                        $attributes[$attribute] = '';
                    }
                } elseif (strpos($attribute, 'modulemenu_') === 0) {
                    $module_file = str_replace("modulemenu_", "", $attribute);
                    $otherMenu = OtherMenu::where('uuid', $matches[2][$index])->first();
                    if ($otherMenu != null) {
                        $viewPathModule = 'components.' . $themeName . '.' . strtolower(str_replace(' ', '_', $otherMenu->name));
                        if (view()->exists($viewPathModule)) {
                            $attributes[$attribute] = view($viewPathModule)->render();
                        } else {
                            $attributes[$attribute] = '';
                        }
                    }
                } elseif ($attribute == 'insta_order_paid') {
                } else {
                    $attributes[$attribute] = $matches[2][$index];
                }
            }
            return $attributes;
        }
    }

    private function cartCountScript($baseurl)
    {
        $cartCountScript = '
                <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
                <script>
                    async function updateCartBadge() {
                        try {
                            const response = await fetch("' . $baseurl . '/cart", {
                                headers: {
                                    "Accept": "application/json",
                                    "authid": localStorage.getItem("authid")
                                },
                            });
                            
                            if (!response.ok) {
                                throw new Error(`HTTP error! status: ${response.status}`);
                            }
                            
                            const data = await response.json();
                            const badge = document.querySelectorAll(".insta-manage-cart-count");
                                badge.forEach((element) => {
                                    element.textContent = data.cart_count;
                                });
                            let cartCount = data.cart_count;
                            if (badge) {
                                badge.textContent = cartCount;
                                localStorage.setItem("cartCount",cartCount);
                            }
                        } catch (error) {
                            console.error("Error updating cart badge:", error);
                            const storedCount = localStorage.getItem("cartCount");
                            if (storedCount) {
                                const badge = document.querySelectorAll(".insta-manage-cart-count");
                                badge.textContent = storedCount;
                                    badge.forEach((element) => {
                                        if (element) {
                                            element.textContent = data.cart_count;
                                        }
                                    });
                            
                            }
                        }
                    }

                    document.addEventListener("DOMContentLoaded", () => {
                        updateCartBadge();
                    });
                </script>';
        return $cartCountScript;
    }

    private function productDetail($page, $baseurl)
    {
        $pageTemplate = PageTemplate::where('theme_uuid', $page->theme_id)
            ->where('page_type', 'product_detail')
            ->first();
        if ($pageTemplate != null) {
            $productbaseurl = BussinessSetting::where('type', 'api_base_product_url')->first();
            $baseurl = $baseurl ? $baseurl->value : '';
            $productbaseurl = $productbaseurl ? $productbaseurl->value : '';
            $productClass = $pageTemplate->product_class;

            $apical = '
                <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
                <script>
                    function getSlug() {
                        const url = new URL(window.location.href);
                        const lastSegment = url.pathname.split("/").pop();
                        return lastSegment;
                    }

                    async function fetchProductDetails(slug) {
                        try {
                            const response = await fetch("' . $productbaseurl . '" + slug);
                            if (!response.ok) {
                                throw new Error(`HTTP error! status: ${response.status}`);
                            }
                            const data = await response.json();
                            const item = data.data;
                            if (item.meta_title) {
                                document.title = item.meta_title;
                            }
                            if (item.meta_description) {
                                document.querySelector(`meta[name="description"]`).setAttribute("content", item.meta_description);
                            }
                            const htmlCurrent = `' . $pageTemplate->page_html . '`;
                            let htmlVaraint = ' . $pageTemplate->html_variant . ';
                            let varaintCheck = 0;
                            if (Object.keys(htmlVaraint).length > 0) {
                                let htmlVariantObject = htmlVaraint;
                                let htmlVariantObjectKey = Object.keys(htmlVaraint);
                                document.querySelector(".' . $productClass . '").innerHTML = htmlCurrent;
                                for (let varaint = 0; varaint < htmlVariantObjectKey.length; varaint++) {
                                    if (item.hasOwnProperty(htmlVariantObjectKey)) {
                                        let itemVariant = item[htmlVariantObjectKey];
                                        let itemVariantKey = htmlVariantObjectKey[varaint];
                                        console.log("itemVariantKey",itemVariantKey);
                                        let variantHtml = htmlVariantObject[itemVariantKey]
                                        let newHTMl = "";
                                        let allColorHtml = "";
                                        for (const variant in itemVariant) {
                                            const element = itemVariant[variant];
                                            let elementKeys = Object.keys(element);
                                            // Get all dynamic variables from the template
                                            const matches = elementKeys || [];
                                            allColorHtml = itemVariant.map(item => {
                                                if (item.variant !== "") {
                                                    varaintCheck = 1
                                                    return variantHtml.replace(/\$\{item\.([^}]+)\}/g, (match, p1) => {
                                                        return item[p1] || "";
                                                    });
                                                }
                                            }).join("");
                                        }
                                        document.querySelector(".insta-manage-variation-show").innerHTML = allColorHtml;
                                    }
                                }
                                reinitialize();
                                updateCartBadge();
                                if(varaintCheck > 0){
                                    const firstAttributeId = document.querySelector(`#option-choice-form input[name="attribute_id"]`);
                                    firstAttributeId.checked = true;
                                    firstAttributeId.click();
                                }
                            }
                        } catch (error) {
                            console.error("Error fetching product details:", error);
                        }
                    }

                    function triggerVariationAPI() {
                        $.ajax({
                            url: "' . $baseurl . 'get-product-variation",
                            type: "POST",
                            data: $("#option-choice-form").serialize(),
                        })
                        .done(function(data) {
                            console.log("Data", data);
                            let productStock = data.data;
                            let price = productStock.price;
                            $(".insta-manage-product-detail-price").text(price);
                            const manageVariationImg = document.querySelector(".insta-manage-variation-img");
                            if (manageVariationImg) {
                                manageVariationImg.src = productStock.image;
                            }
                            document.querySelector(".insta-add-cart").disabled = false;
                            document.querySelector(`.insta-manage-quantity-input`).disabled = false;
                            let errorMessage = document.querySelector(".text-red-900");
                            if (errorMessage) {
                                errorMessage.remove();
                            }
                        })
                        .fail(function(error) {
                            console.log("Error", error.responseJSON.message);
                            document.querySelector(`.insta-manage-quantity-input`).disabled = false;
                            let oldError = document.querySelector(".out-of-stock-error");
                            if (oldError) {
                                oldError.remove();
                            }
                            let errorMessage = document.createElement("p");
                            errorMessage.classList.add("text-red-900", "out-of-stock-error");
                            errorMessage.textContent = error.responseJSON.message;
                            document.querySelector(`.insta-manage-quantity-input`).parentNode.appendChild(errorMessage);
                            document.querySelector(".insta-add-cart").disabled = true;
                            let productStock = error.responseJSON.data;
                            let price = productStock.price;
                            $(".insta-manage-product-detail-price").text(price);
                            console.error("There was an error! quantity", error);
                        });
                    }

                    function reinitialize() {
                        $(`input[name="attribute_id"]`).on("click", function() {
                            $(`input[name="quantity"]`).val(1);
                            triggerVariationAPI();
                        })
                        $(`.insta-manage-quantity-input`).on("input", function() {
                            triggerVariationAPI();
                        });
                        $("#option-choice-form input").on("click", function() {
                            $.ajax({
                                url: "' . $baseurl . 'get-product-variation",
                                type: "POST",
                                data: $(this).closest("form").serialize(),
                            })
                            .done(function(data) {
                                console.log("Data",data);
                                let productStock = data.data;
                                let price = productStock.price;
                                $(".insta-manage-product-detail-price").text(price);
                                const manageVariationImg = document.querySelector(".insta-manage-variation-img");
                                if (manageVariationImg) {
                                    manageVariationImg.src = productStock.image;
                                }
                                document.querySelector(".insta-add-cart").disabled = false;
                                document.querySelector(`.insta-manage-quantity-input`).disabled = false;
                                let errorMessage = document.querySelector(".text-red-900");
                                if(errorMessage){
                                    errorMessage.remove();
                                }
                            })
                            .fail(function(error) {
                                let data = error;
                                console.log("Error",error.responseJSON.message);
                                document.querySelector(`.insta-manage-quantity-input`).disabled = false;
                                let oldError = document.querySelector(".out-of-stock-error");
                                if(oldError){
                                    oldError.remove();
                                }
                                let errorMessage = document.createElement("p");
                                errorMessage.classList.add("text-red-900","out-of-stock-error");
                                errorMessage.textContent = error.responseJSON.message;
                                document.querySelector(`.insta-manage-quantity-input`).parentNode.appendChild(errorMessage);
                                document.querySelector(".insta-add-cart").disabled = true;
                                let productStock = data.data;
                                let price = productStock.price;
                                $(".insta-manage-product-detail-price").text(price);
                                console.error("There was an error! quantity", error);
                            });
                        });
                    }
                    function addToCart(uuid = "") {
                        if (!uuid) return;

                        let product_uuid = uuid;
                        let quantity = document.querySelector(`#quantity-${product_uuid}`).value;
                        let attribute_id = document.querySelector(`input[name="attribute_id"]:checked`)?.getAttribute(`attribute_id`);
                        let attribute_value = document.querySelector(`input[name="attribute_id"]:checked`)?.value;
                        
                        // Retrieve current cart from sessionStorage
                        sessionStorage.removeItem("cart");
                        let cart = [];
                        
                        // Check if the product already exists in the cart
                        // const existingItem = cart.find(item => item.product_id === product_uuid && item.variant_id === attribute_id);
                        // If not, add as a new item
                        cart.push({product_id: product_uuid, product_qty: quantity, variant_id: attribute_id});

                        // Save updated cart back to sessionStorage
                        // sessionStorage.setItem("cart", JSON.stringify(cart));

                        // Make API call to add item to cart
                        fetch("' . $baseurl . 'cart/add_to_cart", {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/json",
                                "authid": localStorage.getItem("authid")
                            },
                            body: JSON.stringify({
                                product_details: cart
                            })
                        })
                        .then(response => {
                            if (!response.ok) {
                                return response.json().then(errData => {
                                    throw new Error(errData.message || `HTTP error! status: ${response.status}`);
                                });
                            }
                            return response.json();
                        })
                        .then(data => {
                            console.log("cart data",data);
                            let cartCount = data.cart_count || 0;
                            let auth_id = data.auth_id;
                            let cartcounter = document.querySelectorAll(".insta-manage-cart-count");
                            cartcounter.forEach(element => {
                                element.innerHTML = cartCount;
                            });
                            localStorage.setItem("cartCount", cartCount);
                            localStorage.setItem("authid", auth_id);
                            updateCartBadge();
                            showToast("Product added to cart successfully!",{type:"success"});
                        })
                        .catch(error => {
                            showToast(error.message,{type:"error"});
                            // Handle error, e.g., show a message to the user
                            console.error("There was an error!", error);
                        });
                    }

                    function updateCartBadge() {
                        const badge = document.querySelector(".insta-manage-cart-count");
                        let cartCount = localStorage.getItem("cartCount");
                        badge.forEach(element => {
                            if (element) {
                                element.innerHTML = cartCount;
                            }
                        })
                    }

                        // Run on page load to show correct badge
                    document.addEventListener("DOMContentLoaded", () => {
                        const slug = getSlug();
                        if (slug) {
                            fetchProductDetails(slug);
                        } else {
                            console.error("Slug not found in URL");
                        }
                        updateCartBadge();
                    });
                </script>';

            return $apical;
        }
    }

    private function cart($page)
    {
        $pageTemplate = PageTemplate::where('theme_uuid', $page->theme_id)
            ->where('page_type', 'cart')
            ->first();
        if ($pageTemplate != null) {
            $baseurl = BussinessSetting::where('type', 'api_base_url')->first();
            $baseurl = $baseurl ? $baseurl->value : '';
            $cartClass = $pageTemplate->product_class;
            $cart_productList = $pageTemplate->product_cart_html ?? ''; // Load cart HTML from product_cart_html column
            $pageHtml = $pageTemplate->page_html ?? ''; // Load page HTML from page_html column
            $cartScript = '<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
                <script>
                let productDetails = []; 
                async function fetchCartItems() {
                    try {
                        const authId = localStorage.getItem("authid");
                        const headers = {
                            "Content-Type": "application/json",
                            "authid": authId
                        };
                        const response = await fetch("' . $baseurl . 'cart", {headers});
                        const data = await response.json();
                        let cartItems = data.data;
                        let cartItemsHtml = "";
                        
                        // Get cart container and empty message elements
                        const cartContainer = document.querySelector(".insta-manage-cart-list");
                        const emptyCartMessage = document.querySelector(".insta-manage-cart-empty");
                        
                        if (cartItems.length === 0) {
                            // Show empty cart message and hide cart items
                            if (cartContainer) {
                                cartContainer.style.display = "none";
                            }
                            if (emptyCartMessage) {
                                emptyCartMessage.style.display = "block";
                                emptyCartMessage.innerHTML = "<div style=\"text-align:center;padding:20px;\"><span>Your cart is empty</span></div>";
                            }
                            
                            // Hide cart summary sections
                            document.querySelectorAll(".insta-cart-basket-summary").forEach(el => {
                                if (el) el.style.display = "none";
                            });
                            
                            // Reset totals
                            document.querySelectorAll(".insta-manage-cart-sub-total, .insta-manage-cart-grand-total", ".insta-manage-cart-vat").forEach(el => {
                                if (el) el.textContent = "0.00";
                            });

                            // Hide "Clear All Basket" button
                            const clearAllButton = document.querySelector(".insta-clear-all-basket");
                            if (clearAllButton) {
                                clearAllButton.style.display = "none";
                            }

                            return; // Exit early if cart is empty
                        }
                        
                        // Cart has items - show everything
                        if (cartContainer) {
                            cartContainer.style.display = "block";
                        }
                        if (emptyCartMessage) {
                            emptyCartMessage.style.display = "none";
                        }
                        
                        // Show cart summary sections
                        document.querySelectorAll(".insta-cart-basket-summary").forEach(el => {
                            if (el) el.style.display = "block";
                        });

                        // Show "Clear All Basket" button
                        const clearAllButton = document.querySelector(".insta-clear-all-basket");
                        if (clearAllButton) {
                            clearAllButton.style.display = "inline-block"; // or "block" depending on your layout
                        }

                        
                        // Generate cart items HTML
                        cartItems.forEach(item => {
                            productDetails.push({
                                "product_id": item.product_id,
                                "variant_id" : item.variant_id,
                                "product_qty": item.product_qty,
                                "product_img": item.product_img
                            });
                            cartItemsHtml += `' . $cart_productList . '`;
                        });
                        
                        if (cartContainer) {
                            cartContainer.innerHTML = cartItemsHtml;
                        }
                        
                        // Calculate totals
                        let subTotal = 0;
                        let grandTotal = 0;
                        let totalVat = 0;
                        const singleCartItems = document.querySelectorAll(".insta-manage-single-cart");
                        singleCartItems.forEach((item, index) => {
                            const priceText = item.querySelector(".insta-manage-single-cart-price").textContent;
                            const price = parseFloat(priceText.replace(/,/g, ""));
                            const qty = parseInt(item.querySelector(".insta-manage-quantity-input").value);
                            const itemVatRate = cartItems[index]?.product?.[0]?.vat?.rate ?? 0;
                            const lineSubtotal = price * qty;
                            const vatAmount = (lineSubtotal * itemVatRate) / 100;
                            const lineGrandTotal = lineSubtotal + vatAmount;
                            subTotal += lineSubtotal;
                            totalVat += vatAmount;
                            grandTotal += lineGrandTotal;
                        });
                        
                        // Update totals display
                        const subTotalElement = document.querySelector(".insta-manage-cart-sub-total");
                        const grandTotalElement = document.querySelector(".insta-manage-cart-grand-total");
                        const vatElement = document.querySelector(".insta-manage-cart-vat");
                        
                        if (subTotalElement) {
                            subTotalElement.textContent = subTotal.toLocaleString("en-US", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                        }
                        if (grandTotalElement) {
                            grandTotalElement.textContent = grandTotal.toLocaleString("en-US", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                        }
                        if(vatElement){
                            vatElement.textContent = totalVat.toLocaleString("en-US", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                        } 
                        
                        
                    } catch (error) {
                        console.error("Error fetching cart items:", error);
                        // showEmptyCart();
                    }
                }

                function quantityChangeHandler(event) {
                    try {
                        const cartItem = event.target.closest(".insta-manage-single-cart");
                        if (!cartItem) return;

                        const quantityInput = cartItem.querySelector(".insta-manage-quantity-input");
                        if (!quantityInput) return;

                        const uuid = quantityInput.getAttribute("data-id");
                        let quantity = parseInt(quantityInput.value);
                        
                        if (isNaN(quantity)) {
                            quantity = 1; 
                        }

                        if (event.target.classList.contains("insta-manage-single-cart-qty-plus")) {
                            quantity += 1;
                        } else if (event.target.classList.contains("insta-manage-single-cart-qty-miuns")) {
                            quantity -= 1;
                            if (quantity < 1) {
                                quantity = 1;
                            }
                        }
                                                    
                        updateQuantity(uuid, quantity);
                        } catch (error) {
                            console.error("Error in quantityChangeHandler:", error);
                            console.log("Event target:", event.target);
                            console.log("Cart item:", event.target.closest(".insta-manage-single-car"));
                        }
                    }

                async function updateQuantity(uuid, quantity) {
                    try {
                        const response = await fetch("' . $baseurl . 'cart/update_cart_quantity", {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/json",
                                "authid": localStorage.getItem("authid")
                            },
                            body: JSON.stringify({
                                cart_id: uuid,
                                quantity: quantity
                            })
                        });
                        const data = await response.json();
                        if (response.ok && data.status_code === 200) {
                            document.querySelector(`.insta-manage-quantity-input[data-id="${uuid}"]`).value = quantity;
                            fetchCartItems();
                        } else {
                            showToast("Error: " + data.message,{type:"error"});
                        }
                    } catch (error) {
                        console.error("Error updating quantity:", error);
                    }
                }

                    document.addEventListener("DOMContentLoaded", () => {
                        console.log("DOMContentLoaded event triggered");
                        fetchCartItems();
                        
                    });
                </script>';

            return $pageHtml . $cartScript;
        }
    }

    private function wishlist($page, $baseurl){
        $pageTemplate = PageTemplate::where('theme_uuid', $page->theme_id)->where('page_type', 'wishlist')->first();
        if ($pageTemplate != null) {
            $baseurl = BussinessSetting::where('type', 'api_base_url')->first();
            $baseurl = $baseurl ? $baseurl->value : '';

            $pageHtml = $pageTemplate->page_html ?? ''; // Load page HTML from page_html column
            $wishlistClass = $pageTemplate->product_class;
            $wishlist_productList = $pageTemplate->product_cart_html ?? ''; // Load wishlist HTML
            $cartScript = '<script>  let productDetails = []; 
                async function fetchWishlistItems() {
                    try {
                        const authId = localStorage.getItem("authid");
                        const headers = {
                            "Content-Type": "application/json",
                            "Accept": "application/json",
                            "Authorization": `${localStorage.getItem("customer_token")}`,
                            "authid": authId
                        };
                        const response = await fetch("' . $baseurl . 'customer/wishlist", {headers});
                        if (!response.ok) {
                            throw new Error(`HTTP error! status: ${response.status}`);
                        }
                        const data = await response.json();
                        let wishlistItems = data.data;
                        let wishlistItemsHtml = "";
                        if (wishlistItems.length === 0) {
                            window.location.href =  "' . getConfigValue('WEB_URL') . '";
                        }
                        if (wishlistItems.length > 0) {
                            let publicurl = "' . getConfigValue('APP_ASSET_PATH') . '";
                            wishlistItems.forEach(item => {
                                productDetails.push({
                                    "product_id": item.product_id,
                                    "variant_id" : item.variant_id,
                                    "product_qty": item.product_qty
                                });
                                wishlistItemsHtml += `' . $wishlist_productList . '`;
                            });
                        }
                        document.querySelector(".insta-manage-wishlist-list").innerHTML = wishlistItemsHtml;
                    } catch (error) {
                        console.error("Error fetching wishlist items:", error);
                    }
                }

                function removeWishlistItem(itemId) {
                    fetch(`' . $baseurl . 'customer/wishlist/${itemId}`, {
                        method: "DELETE",
                        headers: {
                            "Content-Type": "application/json",
                            "Authorization": localStorage.getItem("customer_token"),
                            "authid": localStorage.getItem("authid")
                        },
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status_code === 200) {
                            showToast(data.message, {type:"success"});
                            fetchWishlistItems(); // Refresh wishlist items
                        } else {
                            showToast(data.message, {type:"error"});
                        }
                    })
                    .catch(error => {
                        console.error("Error removing wishlist item:", error);
                        showToast("Something went wrong.", {type:"error"});
                    });
                }

                document.addEventListener("DOMContentLoaded", () => {
                    profile();
                    fetchWishlistItems();
                });
                </script>';
            return $pageHtml . $cartScript;
        }
    }

    private function checkout($page, $baseurl)
    {
        $pageTemplate = PageTemplate::where('theme_uuid', $page->theme_id)->where('page_type', 'checkout')->first();
        $pageTemplateThankyou = PageTemplate::where('theme_uuid', $page->theme_id)
            ->where('page_type', 'thankyou')
            ->first();
        if ($pageTemplate != null) {
            $baseurl = BussinessSetting::where('type', 'api_base_url')->first();
            $baseurl = $baseurl ? $baseurl->value : '';
            $cartScript = '<script> let productDetails = []; 
                async function fetchCartItems() {
                    try {
                        const authId = localStorage.getItem("authid");
                        const headers = {
                            "Content-Type": "application/json",
                            "authid": authId
                        };
                        // Fetch cart items
                        const cartResponse = await fetch("' . $baseurl . 'cart", {headers});
                        if (!cartResponse.ok) {
                            throw new Error(`HTTP error! status: ${cartResponse.status}`);
                        }
                        const cartData = await cartResponse.json();
                        let cartItems = cartData.data;
                        let cartItemsHtml = "";
                        if (cartItems.length === 0) {
                            window.location.href = "' . getConfigValue('WEB_URL') . '";
                        }
                        if (cartItems.length > 0) {
                            cartItems.forEach(item => {
                                productDetails.push({
                                    "product_id": item.product_id,
                                    "variant_id": item.variant_id,
                                    "product_qty": item.product_qty
                                });
                                cartItemsHtml += `' . $pageTemplate->product_cart_html . '`;
                            });
                        }
                        document.querySelector(".insta-manage-checkout").innerHTML = `' . $pageTemplate->page_html . '`;
                        document.querySelector(".insta-manage-cart-list").innerHTML = cartItemsHtml;
                        let SubTotalPrice = 0;
                        let GrandTotalPrice = 0;
                        document.querySelectorAll(".insta-manage-single-cart").forEach((cartItem, index) => {
                            const priceString = cartItem.querySelector(".product-price").textContent.replace(/[^\d.-]/g, "");
                            const price = parseFloat(priceString);
                            const quantity = parseInt(cartItem.querySelector(".insta-manage-quanity").textContent);
                            const itemVatRate = cartItems[index]?.product?.[0]?.vat?.rate ?? 0;
                            const lineSubtotal = price * quantity;
                            const vatAmount = (lineSubtotal * itemVatRate) / 100;
                            const lineGrandTotal = lineSubtotal + vatAmount;
                            SubTotalPrice += lineSubtotal;
                            GrandTotalPrice += lineGrandTotal;
                        });
                        document.querySelector(".sub-total-price-checkout").textContent = SubTotalPrice.toLocaleString("en-US", {minimumFractionDigits: 2, maximumFractionDigits: 2});
                        document.querySelector(".grand-total-price-checkout").textContent = GrandTotalPrice.toLocaleString("en-US", {minimumFractionDigits: 2, maximumFractionDigits: 2});

                        // Fetch default address
                        try {
                            const addressResponse = await fetch("' . $baseurl . 'customer/address/get_address_default", {
                                headers: {
                                    "Accept": "application/json",
                                    "authid": authId
                                }
                            });
                            const addressData = await addressResponse.json();
                            if (addressData.data && addressData.data.length > 0) {
                                const defaultAddress = addressData.data[0];
                                const prefix = defaultAddress.type === "billing_address" ? "billing" : "shipping";

                                if (prefix === "billing") {
                                    // Populate billing fields including customer info
                                    document.querySelector(`input[name="${prefix}_first_name"]`)?.setAttribute("value", defaultAddress.customer.first_name || "");
                                    document.querySelector(`input[name="${prefix}_last_name"]`)?.setAttribute("value", defaultAddress.customer.last_name || "");
                                    document.querySelector(`input[name="${prefix}_email"]`)?.setAttribute("value", defaultAddress.customer.email || "");
                                    document.querySelector(`input[name="${prefix}_phone"]`)?.setAttribute("value", defaultAddress.address_phone || defaultAddress.customer.phone || "");
                                }

                                // Populate address fields common to both billing and shipping
                                document.querySelector(`input[name="${prefix}_address"]`)?.setAttribute("value", defaultAddress.address || "");
                                document.querySelector(`input[name="${prefix}_address2"]`)?.setAttribute("value", defaultAddress.apartment || "");
                                document.querySelector(`input[name="${prefix}_city"]`)?.setAttribute("value", defaultAddress.city || "");
                                document.querySelector(`input[name="${prefix}_state"]`)?.setAttribute("value", defaultAddress.state || "");
                                document.querySelector(`input[name="${prefix}_country"]`)?.setAttribute("value", defaultAddress.country || "");
                            }

                        } catch (error) {
                            console.error("Error fetching default address:", error);
                        }

                        // Billing Phone
                        let billingInput = document.querySelector("#billing_phone");
                        if (billingInput) {
                            const billingIti = window.intlTelInput(billingInput, {
                                initialCountry: "ae",
                                preferredCountries: ["ae"],
                                autoPlaceholder: "polite",
                                showSelectedDialCode: true,
                                utilsScript: "https://digitalgraphiks.co.uk/demo/nks/assets/js/utils.js",
                                hiddenInput: () => ({
                                    phone: "full_phone"
                                })
                            });

                            function validateBillingPhone() {
                                const saveBtn = document.querySelector(".place-order-btn"); // Update this selector if needed

                                if (billingInput.value.trim()) {
                                    if (billingIti.isValidNumber()) {
                                        billingInput.parentElement.parentElement.classList.remove("error");
                                        billingInput.parentElement.parentElement.querySelector(".error-txt").innerHTML = "";

                                        billingInput.setAttribute("data-full-phone", billingIti.getNumber());
                                        document.querySelector(`input[name="full_phone"]`)?.setAttribute("value", billingIti.getNumber());

                                        if (saveBtn) saveBtn.disabled = false;
                                    } else {
                                        billingInput.parentElement.parentElement.classList.add("error");
                                        billingInput.parentElement.parentElement.querySelector(".error-txt").innerHTML = "Invalid Number";

                                        billingInput.removeAttribute("data-full-phone");
                                        document.querySelector(`input[name="full_phone"]`)?.setAttribute("value", "");

                                        if (saveBtn) saveBtn.disabled = true;
                                    }
                                } else {
                                    billingInput.parentElement.parentElement.classList.remove("error");
                                    billingInput.parentElement.parentElement.querySelector(".error-txt").innerHTML = "";

                                    billingInput.removeAttribute("data-full-phone");
                                    document.querySelector(`input[name="full_phone"]`)?.setAttribute("value", "");

                                    if (saveBtn) saveBtn.disabled = false;
                                }
                            }

                            billingInput.addEventListener("blur", validateBillingPhone);
                            billingInput.addEventListener("keyup", validateBillingPhone);
                        }

                        // Shipping Phone
                        let shippingInput = document.querySelector("#shipping_phone");
                        if (shippingInput) {
                            const shippingIti = window.intlTelInput(shippingInput, {
                                initialCountry: "ae",
                                preferredCountries: ["ae"],
                                autoPlaceholder: "polite",
                                showSelectedDialCode: true,
                                utilsScript: "https://digitalgraphiks.co.uk/demo/nks/assets/js/utils.js"
                            });

                            function validateShippingPhone() {
                                const saveBtn = document.querySelector(".place-order-btn");
                                if (shippingInput.value.trim()) {
                                    if (shippingIti.isValidNumber()) {
                                        shippingInput.parentElement.parentElement.classList.remove("error");
                                        shippingInput.parentElement.parentElement.querySelector(".error-txt").innerHTML = "";
                                        shippingInput.setAttribute("data-full-phone", shippingIti.getNumber());
                                        if (saveBtn) saveBtn.disabled = false;
                                    } else {
                                        shippingInput.parentElement.parentElement.classList.add("error");
                                        shippingInput.parentElement.parentElement.querySelector(".error-txt").innerHTML = "Invalid Number";
                                        shippingInput.removeAttribute("data-full-phone");
                                        if (saveBtn) saveBtn.disabled = true;
                                    }
                                } else {
                                    shippingInput.parentElement.parentElement.classList.remove("error");
                                    shippingInput.parentElement.parentElement.querySelector(".error-txt").innerHTML = "";
                                    shippingInput.removeAttribute("data-full-phone");
                                    if (saveBtn) saveBtn.disabled = false;
                                }
                            }

                            shippingInput.addEventListener("blur", validateShippingPhone);
                            shippingInput.addEventListener("keyup", validateShippingPhone);
                        }

                        const billingCheckbox = document.querySelector(".insta-same-as-shipping-address");
                                if (billingCheckbox) {
                                    const shippingFields = [
                                        "shipping_first_name", "shipping_last_name", "shipping_email", 
                                        "shipping_phone", "shipping_address", "shipping_address2",
                                        "shipping_city", "shipping_state", "shipping_country"
                                    ];

                                    const updateShippingFields = () => {
                                        shippingFields.forEach(field => {
                                            const billingField = field.replace("shipping_", "billing_");
                                            const billingValue = document.querySelector(`input[name="${billingField}"]`)?.value;
                                            const shippingElement = document.querySelector(`input[name="${field}"]`);
                                            if (shippingElement) {
                                                shippingElement.value = billingValue || "";
                                            }
                                        });

                                        // Handle phone input with intlTelInput
                                        const shippingPhoneInput = document.querySelector("#shipping_phone");
                                        if (shippingPhoneInput && window.intlTelInputGlobals.getInstance(shippingPhoneInput)) {
                                            const billingPhone = document.querySelector("#billing_phone")?.value || "";
                                            const shippingIti = window.intlTelInputGlobals.getInstance(shippingPhoneInput);
                                            shippingIti.setNumber(billingPhone);
                                        }
                                    };

                                    billingCheckbox.addEventListener("change", function () {
                                        if (this.checked) {
                                            updateShippingFields();

                                            // Disable shipping fields
                                            shippingFields.forEach(field => {
                                                const shippingElement = document.querySelector(`input[name="${field}"]`);
                                                if (shippingElement) {
                                                    shippingElement.disabled = true;
                                                }
                                            });

                                            // Listen for billing field changes and sync them
                                            shippingFields.forEach(field => {
                                                const billingField = field.replace("shipping_", "billing_");
                                                const billingInput = document.querySelector(`input[name="${billingField}"]`);
                                                if (billingInput) {
                                                    billingInput.addEventListener("input", () => {
                                                        if (billingCheckbox.checked) {
                                                            updateShippingFields();
                                                        }
                                                    });
                                                }
                                            });

                                        } else {
                                            // Enable shipping fields
                                            shippingFields.forEach(field => {
                                                const shippingElement = document.querySelector(`input[name="${field}"]`);
                                                if (shippingElement) {
                                                    shippingElement.disabled = false;
                                                }
                                            });
                                        }
                                    });
                                }
                        } catch (error) {
                        console.error("Error fetching cart items:", error);
                        showEmptyCart();
                    }
                }

                async function orderStore() {
                    let auth_id = localStorage.getItem("authid");
                    let orderStore = {
                        "customer_id": auth_id,
                        "shipping_type": "DHL",
                        "shipping_price": 0,
                        "discount_type": "amount",
                        "discount_value": 0,
                        "product_details": productDetails,
                        "billing_first_name": document.querySelector(`input[name="billing_first_name"]`)?.value || "",
                        "billing_last_name": document.querySelector(`input[name="billing_last_name"]`)?.value || "",
                        "billing_email": document.querySelector(`input[name="billing_email"]`)?.value || "",
                        "billing_phone": document.querySelector(`.billing_phone`)?.value || "",
                        "billing_address": document.querySelector(`input[name="billing_address"]`)?.value || "",
                        "billing_address2": document.querySelector(`input[name="billing_address2"]`)?.value || "",
                        "billing_city": document.querySelector(`input[name="billing_city"]`)?.value || "",
                        "billing_state": document.querySelector(`input[name="billing_state"]`)?.value || "",
                        "billing_country": document.querySelector(`input[name="billing_country"]`)?.value || "",
                        "shipping_address_check": document.querySelector(`input[name="shipping_address_check"]`)?.checked ? 1 : 0,
                        "shipping_first_name": document.querySelector(`input[name="shipping_first_name"]`)?.value || "",
                        "shipping_last_name": document.querySelector(`input[name="shipping_last_name"]`)?.value || "",
                        "shipping_email": document.querySelector(`input[name="shipping_email"]`)?.value || "",
                        "shipping_phone": document.querySelector(`.shipping_phone`)?.value || "",
                        "shipping_address": document.querySelector(`input[name="shipping_address"]`)?.value || "",
                        "shipping_address2": document.querySelector(`input[name="shipping_address2"]`)?.value || "",
                        "shipping_city": document.querySelector(`input[name="shipping_city"]`)?.value || "",
                        "shipping_state": document.querySelector(`input[name="shipping_state"]`)?.value || "",
                        "shipping_country": document.querySelector(`input[name="shipping_country"]`)?.value || "",
                    };
                    try {
                        const authId = localStorage.getItem("authid");
                        const response = await fetch("' . $baseurl . 'customer_order/add_order", {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/json",
                                "authid": authId,
                            },
                            body: JSON.stringify(orderStore),
                        });

                        const data = await response.json();
                        if (!response.ok) {
                            let errorMessage = "An error occurred while processing your order.";
                            if (data.errors) {
                                errorMessage = Object.values(data.errors).flat().join("\n");
                            } else if (data.message) {
                                errorMessage = data.message;
                            }
                            showToast(errorMessage,{type:"error"});
                            throw new Error(errorMessage);
                        }

                        localStorage.removeItem("cart");
                        localStorage.removeItem("cartCount");
                        localStorage.setItem("order_id", data.order_id);
                        window.location.href = window.location.href.replace(/\/' . str_replace(' ', '-', strtolower($pageTemplate->name)) . '$/, "") + "/' . ($pageTemplateThankyou ? str_replace(' ', '-', strtolower($pageTemplateThankyou->name)) : str_replace(' ', '-', strtolower($pageTemplate->name))) . '";
                        console.log("Order stored successfully:", data);
                    } catch (error) {
                        console.error("Error storing order:", error);
                    }
                }

                fetchCartItems();
            </script>';

            return $cartScript;
        }

    }

    private function thankyou($page,$baseurl){
        $pageTemplate = PageTemplate::where('theme_uuid', $page->theme_id)
            ->where('page_type', 'thankyou')
            ->first();
        if ($pageTemplate != null) {
            $cartScript = '<script>  let productDetails = []; 
                async function fetchCartItems() {
                    try {
                        const authId = localStorage.getItem("authid");
                        const headers = {
                            "Content-Type": "application/json",
                            "authid": authId,
                            "orderid": localStorage.getItem("order_id")
                        };
                        const response = await fetch("' . $baseurl . 'customer_order/get-order", {headers});
                        if (!response.ok) {
                            throw new Error(`HTTP error! status: ${response.status}`);
                        }
                        const data = await response.json();
                        let orderDetails = data.data.order_details;
                        let item = data.data;
                        let orderItemsHtml = "";
                        if (orderDetails.length > 0) {
                            orderDetails.forEach(item => {
                                orderItemsHtml += `' . $pageTemplate->product_cart_html . '`;
                            });
                        }
                        document.querySelector(".insta-manage-thank-you").innerHTML = `' . $pageTemplate->page_html . '`;
                        // document.querySelector(".insta-manage-product-list").innerHTML = orderItemsHtml;
                        // let totalPrice = 0;
                        // document.querySelectorAll(".insta-manage-single-cart").forEach(cartItem => {
                        //     const price = cartItem.querySelector(".product-price").textContent.replace(/[^\d.-]/g, "");
                        //     const quantity = parseInt(cartItem.querySelector(".insta-manage-quanity").textContent);

                        //     totalPrice += price * quantity;
                        // });
                        let totalPrice = item.grand_total;
                        document.querySelector(".insta-manage-total-price").textContent = totalPrice.toLocaleString("en-US", {minimumFractionDigits: 2, maximumFractionDigits: 2})
                    } catch (error) {
                        console.error("Error fetching cart items:", error);
                    }
                }
                    fetchCartItems();
                </script>';
            return $cartScript;
        }
    }

    private function login($page, $baseurl){
        $pageProfile = Page::where('page_type', 'dashboard')->first();
        $pageProfileSlug = $pageProfile ? $pageProfile->slug : getConfigValue('THEME_CMS');
        $pageTemplate = PageTemplate::where('theme_uuid', $page->theme_id)
            ->where('page_type', 'login')
            ->first();
        // dd($pageTemplate->page_html);
        if ($pageTemplate != null) {
            $productClass = $pageTemplate->product_class;
            $pageHtml = $pageTemplate->page_html;
            $loginScript = "<script>
                    document.addEventListener('DOMContentLoaded', function () {
                        checkIfLoggedIn();
                        document.querySelector('.insta-login-form').addEventListener('submit', loginForm);
                    });

                        function checkIfLoggedIn() {
                        const authId = localStorage.getItem('authid');
                        const customerToken = localStorage.getItem('customer_token');
                        
                        if (authId && customerToken) {
                            const currentURL = new URL(window.location.href);
                            let newURL;
                            if (typeof '" . $pageProfileSlug . "' !== 'undefined' && '" . $pageProfileSlug . "') {
                                newURL = new URL(currentURL.origin + currentURL.pathname.replace('" . $page->slug . "', '" . $pageProfileSlug . "'), currentURL);
                            } else {
                                newURL = new URL(currentURL.origin + '/nks-ec-uat/custom_frontend/nks', currentURL);
                            }
                            window.location.href = newURL.toString();
                        }
                        }

                    async function loginForm(event) {
                        event.preventDefault(); // Prevent the default form submission

                        const recaptchaResponse = grecaptcha.getResponse();
                        if (!recaptchaResponse) {
                            showToast('Please verify reCAPTCHA', {type:'error'});
                            return;
                        }

                        // Gather form data
                        const form = event.target;
                        const data = new FormData(form);
                        data.append('g-recaptcha-response', recaptchaResponse);
                        fetch('" . $baseurl . "customer/login', {
                            method: 'POST',
                            body: data
                        })
                        .then(response => response.json())
                        .then(response => {
                            console.log(response);
                            
                            if (response.status_code == 200) {
                                localStorage.setItem('authid', response.customer.uuid);
                                localStorage.setItem('customer_token', 'Bearer '+response.token);
                                const currentURL = new URL(window.location.href);
                                let newURL;
                                    if (typeof '" . $pageProfileSlug . "' !== 'undefined' && '" . $pageProfileSlug . "') {
                                        newURL = new URL(currentURL.origin + currentURL.pathname.replace('" . $page->slug . "', '" . $pageProfileSlug . "'), currentURL);
                                    } else {
                                        newURL = new URL(currentURL.origin + '/nks-ec-uat/custom_frontend/nks', currentURL);
                                    }
                                window.location.href = newURL.toString();
                                showToast('Login successful!',{type:'success'});
                                // Redirect or update UI here
                            } else if(response.status_code == 422){
                                errorMessage = Object.entries(response.errors)
                                .map(([field, messages]) =>  field + ': ' + messages.join(', '))
                                .join('\\n');
                                showToast('Login failed:\\n' + errorMessage,{type:'error'});
                            }else {
                                showToast('Login failed: ' + (response.message || 'Unknown error'),{type:'error'});
                            }
                        })
                        .catch(() => {
                            showToast('An error occurred. Please try again.',{type:'error'});
                        });
                    }</script>";
            return $pageHtml . $loginScript;
        }
    }

    private function singup($page, $baseurl){
        $pageProfile = Page::where('page_type', 'dashboard')->first();
        $pageProfileSlug = $pageProfile ? $pageProfile->slug : '';
        $pageTemplate = PageTemplate::where('theme_uuid', $page->theme_id)
            ->where('page_type', 'signup')
            ->first();
        if ($pageTemplate != null) {
            $productbaseurl = BussinessSetting::where('type', 'api_base_product_url')->first();
            $productbaseurl = $productbaseurl ? $productbaseurl->value : '';
            $productClass = $pageTemplate->product_class;
            $pageHtml = $pageTemplate->page_html;
            $signupScript = "<script>
                    document.addEventListener('DOMContentLoaded', function () {
                        profile();
                        document.querySelector('.insta-signup-form').addEventListener('submit', registrationForm);
                    });

                    function noSpaces(event) {
                        if (event.key === ' ') {
                            event.preventDefault(); // block spacebar
                            return false;
                        }
                    }

                    async function registrationForm(event) {
                        event.preventDefault(); // Prevent the default form submission
                        const recaptchaResponse = grecaptcha.getResponse();
                        if (!recaptchaResponse) {
                            showToast('Please verify reCAPTCHA', {type:'error'});
                            return;
                        }
                        // Gather form data
                        const form = event.target;
                        const data = new FormData(form);
                        data.append('g-recaptcha-response', recaptchaResponse);
                        fetch('" . $baseurl . "customer/register', {
                            method: 'POST',
                            body: data
                        })
                        .then(response => response.json())
                        .then(response => {
                            if (response.status_code == 201) {
                                localStorage.setItem('authid', response.customer.uuid);
                                localStorage.setItem('customer_token', 'Bearer '+response.token);
                                const currentURL = new URL(window.location.href);
                                const newURL = new URL(currentURL.origin + currentURL.pathname.replace('" . $page->slug . "', '" . $pageProfileSlug . "'), currentURL);
                                window.location.href = newURL.toString();
                                showToast('Registration successful!','success');
                                // Redirect or update UI here
                            } else if(response.status_code == 422){
                                errorMessage = Object.entries(response.errors)
                                .map(([field, messages]) =>  field + ': ' + messages.join(', '))
                                .join('\\n');
                                showToast('Registration failed:\\n' + errorMessage,{type:'error'});
                            }else {
                                showToast('Registration failed: ' + (response.message || 'Unknown error'),{type:'error'});
                            }
                        })
                        .catch(() => {
                            showToast('An error occurred. Please try again.',{type:'error'});
                        });
                    }</script>";
            return $pageHtml . $signupScript;
        }
    }

    private function dashboard($page, $baseurl){
        $pageTemplate = PageTemplate::where('theme_uuid', $page->theme_id)
            ->where('page_type', 'dashboard')
            ->first();
        if ($pageTemplate) {
            $pageHtml = $pageTemplate->page_html;
            $productbaseurl = BussinessSetting::where('type', 'api_base_product_url')->first();
            $productbaseurl = $productbaseurl ? $productbaseurl->value : '';
            $product_cart_html = $pageTemplate->product_cart_html;
            $dashboardSrcipt = "<script>document.addEventListener('DOMContentLoaded', () => {
                profile();
                WishlistCount();
                OrderCount();
                OrderExpenditure();
                GetAddress();
                fetchWishlistItems();
                document.querySelector('.insta-address-form').addEventListener('submit', AddressForm);
            });

            async function fetchWishlistItems() {
                try {
                    const authId = localStorage.getItem('authid');
                    const headers = {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'Authorization': localStorage.getItem('customer_token'),
                        'authid': authId
                    };
                    const response = await fetch('" . $baseurl . "customer/wishlist', { headers });
                    if (!response.ok) {
                    }
                    const data = await response.json();
                    let wishlistItems = data.data;
                    let wishlistItemsHtml = '';
                    if (wishlistItems.length === 0) {
                        document.querySelector('.insta-wishlist-list').innerHTML = '<div class=\"swiper-slide\">No items in wishlist</div>';
                    } else {
                        wishlistItems.forEach(item => {
                            let publicurl = '" . getConfigValue('APP_ASSET_PATH') . "';
                            let productHtml = `" . $product_cart_html . "`;
                            // Replace placeholders with actual data
                            productHtml = productHtml
                                .replace(/{{product_id}}/g, item.product_id || '')
                                .replace(/{{variant_id}}/g, item.variant_id || '')
                                .replace(/{{product_image}}/g, item.product_image || './assets/images/product-ing-1.webp')
                                .replace(/{{product_category}}/g, item.product_category || 'Kitchens')
                                .replace(/{{product_title}}/g, item.product_title || 'Rosemary')
                                .replace(/{{original_price}}/g, item.original_price || 'AED860')
                                .replace(/{{discounted_price}}/g, item.discounted_price || 'AED640')
                                .replace(/{{discount}}/g, item.discount || '-25%');
                            wishlistItemsHtml += '<div class=\"swiper-slide\">' + productHtml + '</div>';
                        });
                        document.querySelector('.insta-wishlist-list').innerHTML = wishlistItemsHtml;
                        // Initialize Swiper after updating the DOM
                        new Swiper('#WishlistSlider', {
                            slidesPerView: 4,
                            spaceBetween: 20,
                            navigation: {
                                nextEl: '#WishlistSliderNext',
                                prevEl: '#WishlistSliderPrev',
                            },
                            breakpoints: {
                                320: { slidesPerView: 1 },
                                640: { slidesPerView: 2 },
                                1024: { slidesPerView: 4 },
                            }
                        });
                    }
                } catch (error) {
                    console.error('Error fetching wishlist items:', error);
                    document.querySelector('.insta-wishlist-list').innerHTML = '<div class=\"swiper-slide\">Error loading wishlist</div>';
                }
            }

            function removeWishlistItem(itemId) {
                fetch(`" . $baseurl . "customer/wishlist/\${itemId}`, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': localStorage.getItem('customer_token'),
                        'authid': localStorage.getItem('authid')
                    },
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status_code === 200) {
                        showToast(data.message, 'success');
                        fetchWishlistItems(); // Refresh wishlist items
                        WishlistCount();
                    } else {
                        showToast(data.message, {type:'error'});
                    }
                })
                .catch(error => {
                    console.error('Error removing wishlist item:', error);
                    showToast('Something went wrong.', {type:'error'});
                });
            }
                
            async function WishlistCount() {
                try {
                    const response = await fetch('" . $baseurl . "customer/wishlist_count', {
                        headers: {
                            'Accept': 'application/json',
                            'authid': localStorage.getItem('authid')
                        },
                    });

                    const data = await response.json();
                    const wishlistCount = data.wishlist_count;
                    document.querySelector('.insta-manage-wishlist-count').textContent = wishlistCount;

                } catch (error) {
                    console.error('Error fetching wishlist count:', error);
                }
            }

            async function OrderCount() {
                try {
                    const response = await fetch('" . $baseurl . "customer/order_count', {
                        headers: {
                            'Accept': 'application/json',
                            'authid': localStorage.getItem('authid')
                        },
                    });

                    const data = await response.json();
                    const orderCount = data.order_count;
                    document.querySelector('.insta-manage-order-count').textContent = orderCount;

                } catch (error) {
                    console.error('Error fetching order count:', error);
                }
            }

            async function OrderExpenditure() {
                try {
                    const response = await fetch('" . $baseurl . "customer/order_expenditure', {
                        headers: {
                            'Accept': 'application/json',
                            'authid': localStorage.getItem('authid')
                        },
                    });

                    const data = await response.json();
                    const orderExpenditure = data.order_grand_total;
                    document.querySelector('.insta-manage-order-expenditure').textContent = orderExpenditure;

                } catch (error) {
                    console.error('Error fetching order expenditure:', error);
                }
            }

            async function GetAddress() {
                try {
                    const response = await fetch('" . $baseurl . "customer/address/get_address_default', {
                        headers: {
                            'Accept': 'application/json',
                            'authid': localStorage.getItem('authid')
                        },
                    });

                    const data = await response.json();
                    if (data.data && data.data.length > 0) {
                        const country = data.data[0].country;
                        const city = data.data[0].city;
                        const state = data.data[0].state;
                        const address = data.data[0].address;
                        const address_phone = data.data[0].address_phone;
                        const apartment = data.data[0].apartment;

                        document.querySelector('.insta-manage-address-country').textContent = country;
                        document.querySelector('.insta-manage-address-city').textContent = city;
                        document.querySelector('.insta-manage-address-state').textContent = state;
                        document.querySelector('.insta-manage-address').textContent = address;
                        document.querySelector('.insta-manage-address-phone').textContent = address_phone;
                        document.querySelector('.insta-manage-address-apartment').textContent = apartment;
                    }

                } catch (error) {
                    console.error('Error fetching address:', error);
                }
            }

            async function AddressForm(event) {
                event.preventDefault(); // Prevent the default form submission
                // Gather form data
                const form = event.target;
                const data = new FormData(form);
                data.append('customer_id', localStorage.getItem('authid'));
                data.append('is_default', form.querySelector(`[name='default_shipping']`).checked ? 1 : 0);
                fetch('" . $baseurl . "customer/address/add_address', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                    },
                    body: data
                })
                .then(response => response.json())
                .then(response => {
                    if (response.status_code == 201) {
                        $('.insta-address-add-modal').modal('hide');
                        showToast(response.message);
                    } else if (response.status_code === 422) {
                        showToast(response.message, {type:'error'});
                    } else {
                        showToast(response.error || 'Failed to update address', {type:'error'});
                    }
                })
                .catch(() => {
                    showToast('An error occurred. Please try again.',{type:'error'});
                });
            }

            
            </script>";
            return $pageHtml . $dashboardSrcipt;
        }
    }

    private function order($page, $baseurl){
        $pageTemplate = PageTemplate::where('theme_uuid', $page->theme_id)
            ->where('page_type', 'order')
            ->first();
        if ($pageTemplate != null) {
            $productbaseurl = BussinessSetting::where('type', 'api_base_product_url')->first();
            $productbaseurl = $productbaseurl ? $productbaseurl->value : '';
            $productClass = $pageTemplate->product_class;
            $pageHtml = $pageTemplate->page_html;
            $orderSrcipt = '<script>document.addEventListener("DOMContentLoaded", () => {
                profile();
                const searchInput = document.querySelector(".insta-order-search-input");
                const dropdownLinks = document.querySelectorAll(".insta-order-sort-option");
                
                searchInput.addEventListener("input", function(e) {
                    const searchQuery = e.target.value.trim();
                    getOrders(searchQuery, currentSortOrder);
                });
                
                let currentSortOrder = "";
                
                dropdownLinks.forEach(link => {
                    link.addEventListener("click", function(e) {
                        e.preventDefault();
                        const val = this.getAttribute("val");
                        
                        document.querySelector(".insta-order-sort-display").textContent = val;
                        currentSortOrder = val;
                        
                        getOrders(searchInput.value.trim(), currentSortOrder);
                    });
                });
                getOrders();
            });
            function getOrders(searchQuery = "", sortOrder = "") {
                const token = localStorage.getItem("customer_token");
                if (token) {
                    fetch("' . $baseurl . 'customer/authOrder", {
                        headers: {
                            "Authorization": token,
                        },
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (!data.data || data.data.length === 0) {
                                document.querySelector(".insta-order-list").innerHTML =
                                    "<tr class=\"hover:bg-gray-100\"><td colspan=\"3\" class=\"py-2 px-4 border-b text-center\">No records found</td></tr>";
                                return;
                            }

                            let orders = data.data;
                            let htmlVariant = ' . $pageTemplate->html_variant . ';

                            if (htmlVariant && htmlVariant["order"] && Object.keys(htmlVariant).length > 0) {
                                let variantHtml = htmlVariant["order"];

                                // Add formatted_created_at
                                orders = orders.map(order => {
                                    let createdAtDate = new Date(order.created_at);
                                    let formattedDate = createdAtDate.toLocaleString("en-US", {
                                        year: "numeric",
                                        month: "long",
                                        day: "numeric",
                                        // hour: "2-digit",
                                        // minute: "2-digit",
                                        // hour12: true
                                    });
                                    order.formatted_created_at = formattedDate;

                                    order.statusClass = "table-badge-trasit";
                                    if (order.delivery_status === "Pending") {
                                        order.statusClass = "table-badge-pending";
                                    } else if (order.delivery_status === "delivered") {
                                        order.statusClass = "table-badge-delivered";
                                    }

                                    return order;
                                });

                                // Search filter
                                if (searchQuery) {
                                    const lowerQuery = searchQuery.toLowerCase();
                                    const lowerQueryforCode = searchQuery.toLowerCase().replace(/^#/, "");
                                    orders = orders.filter(order => {
                                        return (
                                            order.code.toLowerCase().includes(lowerQueryforCode) ||
                                            order.formatted_created_at.toLowerCase().includes(lowerQuery) ||
                                            order.grand_total.toString().includes(lowerQuery) ||
                                            (order.delivery_status && order.delivery_status.toLowerCase().includes(lowerQuery))
                                        );
                                    });
                                }

                                // Sort filter
                                if (sortOrder === "A-Z") {
                                    orders.sort((a, b) => a.code.localeCompare(b.code));
                                } else if (sortOrder === "Z-A") {
                                    orders.sort((a, b) => b.code.localeCompare(a.code));
                                } else if(sortOrder === "Filter By Status") {
                                    orders.sort((a, b) => a.delivery_status.localeCompare(b.delivery_status));
                                }

                                if (orders.length === 0) {
                                    document.querySelector(".insta-order-list").innerHTML =
                                        "<tr class=\"hover:bg-gray-100\"><td colspan=\"3\" class=\"py-2 px-4 border-b text-center\">No matching records found</td></tr>";
                                    return;
                                }

                                // Generate HTML after filtering/sorting
                                let renderedHtml = orders.map(order => {
                                    if (order.delivery_status === "On_the_way") {
                                            order.delivery_status = "On the way";
                                    } else if (order.delivery_status === "cancelled") {
                                            order.delivery_status = "Cancelled";
                                    }else if (order.delivery_status === "delivered") {
                                        order.delivery_status = "Delivered";
                                    }
                                    return variantHtml.replace(/\$\{item\.([^}]+)\}/g, (match, p1) => {
                                        return order[p1] || "";
                                    });
                                }).join("");

                                document.querySelector(".insta-order-list").innerHTML = renderedHtml;
                            }
                        })
                        .catch(error => console.error("There was an error!", error));
                } else {
                    console.error("No authentication token found!");
                }
            }
            </script>';
            return $pageHtml . $orderSrcipt;
        }
    }
}
