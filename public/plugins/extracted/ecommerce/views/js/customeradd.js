document.title = "Dashboard | Add Customer";
$(document).ready(function() {
    fetchActiveLanguages();
    fetchActiveCountries();
    fetchProductTags();

    // Initialize Select2 for tags
    $("#tagsSelect").select2({
        placeholder: "Select Tags",
        allowClear: true
    });
function initializePhoneInputs() {
        const phoneInput = document.querySelector("#phone");
        const billingPhoneInput = document.querySelector("#billing_address_phone");

        if (phoneInput) {
            itiPhone = window.intlTelInput(phoneInput, {
                initialCountry: "ae",
                preferredCountries: ['ae'],
                autoPlaceholder: "polite",
                showSelectedDialCode: true,
                utilsScript: "./assets/plugins/intel-input/utils.js",
                hiddenInput: () => ({ phone: "phone" })
            });
            phoneInput.addEventListener("blur", validatePhoneInput);
            phoneInput.addEventListener("keyup", validatePhoneInput);
        }

        if (billingPhoneInput) {
            itiBillingPhone = window.intlTelInput(billingPhoneInput, {
                initialCountry: "ae",
                preferredCountries: ['ae'],
                autoPlaceholder: "polite",
                showSelectedDialCode: true,
                utilsScript: "./assets/plugins/intel-input/utils.js",
                hiddenInput: () => ({ phone: "billing_address[address_phone]" })
            });
            billingPhoneInput.addEventListener("blur", validatePhoneInput);
            billingPhoneInput.addEventListener("keyup", validatePhoneInput);
        }
    }

    // Validate phone input
    function validatePhoneInput(event) {
        const input = event.target;
        const iti = window.intlTelInputGlobals.getInstance(input);
        const parent = input.parentElement.parentElement;
        const errorTxt = parent.querySelector(".error-txt");
        const submitButton = $("#addCustomerForm").find("button[type=submit]");

        if (input.value.trim()) {
            if (iti.isValidNumber()) {
                parent.classList.remove("error");
                errorTxt.innerHTML = "";
                submitButton.prop("disabled", false);
                input.setAttribute("data-full-phone", iti.getNumber());
            } else {
                parent.classList.add("error");
                errorTxt.innerHTML = "Invalid Phone Number";
                submitButton.prop("disabled", true);
                input.removeAttribute("data-full-phone");
            }
        } else {
            parent.classList.remove("error");
            errorTxt.innerHTML = "";
            submitButton.prop("disabled", false);
            input.removeAttribute("data-full-phone");
        }
    }

    // Initialize phone inputs on page load
    initializePhoneInputs();
    // Counter for shipping address forms
    let shippingAddressCounter = 0;
let itiShippingPhones = []; // Array to store intlTelInput instances for shipping phones
    // Add new shipping address form
    $("#addShippingAddressBtn").on("click", function() {
        shippingAddressCounter++;
        const containerId = `shipping-address-${shippingAddressCounter}`;

        // Append new shipping address form
        $("#shippingAddressesContainer").append(`
            <div class="shipping-address-container" id="${containerId}">
                <span class="delete-shipping-address" data-id="${containerId}">&times;</span>
                <h4>Shipping Address (${shippingAddressCounter})</h4>
                <div class="row gap-lg-0 gap-5 mt-5">
                    <div class="mb-6 fv-row col-md-6">
                        <label for="shipping_address[${shippingAddressCounter}][address_first_name]" class="form-label required"><g-t>first_name</g-t></label>
                        <input type="text" class="form-control mb-2" name="shipping_address[${shippingAddressCounter}][address_first_name]">
                    </div>
                    <div class="mb-6 fv-row col-md-6">
                        <label for="shipping_address[${shippingAddressCounter}][address_last_name]" class="form-label required"><g-t>last_name</g-t></label>
                        <input type="text" class="form-control mb-2" name="shipping_address[${shippingAddressCounter}][address_last_name]">
                    </div>
                    <div class="mb-6 fv-row col-md-6">
                        <label for="shipping_address[${shippingAddressCounter}][address_email]" class="form-label required"><g-t>email</g-t></label>
                        <input type="email" class="form-control mb-2" name="shipping_address[${shippingAddressCounter}][address_email]">
                    </div>
                    <div class="mb-6 fv-row col-md-6">
                        <label for="shipping_address[${shippingAddressCounter}][company]" class="form-label"><g-t>company</g-t></label>
                        <input type="text" class="form-control mb-2" name="shipping_address[${shippingAddressCounter}][company]">
                    </div>
                    <div class="mb-6 fv-row col-md-6">
                        <label for="shipping_address[${shippingAddressCounter}][address_phone]" class="form-label required"><g-t>phone</g-t></label>
                        <input type="tel" class="form-control mb-2" name="shipping_address[${shippingAddressCounter}][address_phone]" id="shipping_address_phone_${shippingAddressCounter}">
                        <span class="error-txt"></span>
                    </div>
                    <div class="mb-6 fv-row col-md-12">
                        <label for="shipping_address[${shippingAddressCounter}][address]" class="form-label"><g-t>address</g-t></label>
                        <input type="text" class="form-control mb-2" name="shipping_address[${shippingAddressCounter}][address]">
                    </div>
                    <div class="mb-6 fv-row col-md-6">
                        <label for="shipping_address[${shippingAddressCounter}][apartment]" class="form-label"><g-t>apartment</g-t></label>
                        <input type="text" class="form-control mb-2" name="shipping_address[${shippingAddressCounter}][apartment]">
                    </div>
                    <div class="mb-6 fv-row col-md-6">
                        <label for="shipping_address[${shippingAddressCounter}][city]" class="form-label"><g-t>city</g-t></label>
                        <input type="text" class="form-control mb-2" name="shipping_address[${shippingAddressCounter}][city]">
                    </div>
                    <div class="mb-6 fv-row col-md-6">
                        <label for="shipping_address[${shippingAddressCounter}][state]" class="form-label"><g-t>state</g-t></label>
                        <input type="text" class="form-control mb-2" name="shipping_address[${shippingAddressCounter}][state]">
                    </div>
                    <div class="mb-6 fv-row col-md-6">
                    <label for="shipping_address[${shippingAddressCounter}][country]" class="form-label"><g-t>country</g-t></label>
                    <select class="form-control mb-2 shipping-country-select" name="shipping_address[${shippingAddressCounter}][country]">
                    <option value="" disabled selected>Select Country</option>
                    <!-- Options will be populated dynamically -->
                    </select>
                    </div>
                </div>
            </div>
            <hr>
        `);

        // Populate country dropdown for the new shipping address
const shippingPhoneInput = document.querySelector(`#shipping_address_phone_${shippingAddressCounter}`);
        if (shippingPhoneInput) {
            const itiShipping = window.intlTelInput(shippingPhoneInput, {
                initialCountry: "ae",
                preferredCountries: ['ae'],
                autoPlaceholder: "polite",
                showSelectedDialCode: true,
                utilsScript: "./assets/plugins/intel-input/utils.js",
                hiddenInput: () => ({ phone: `shipping_address[${shippingAddressCounter}][address_phone]` })
            });
            shippingPhoneInput.addEventListener("blur", validatePhoneInput);
            shippingPhoneInput.addEventListener("keyup", validatePhoneInput);
            itiShippingPhones.push({ id: shippingAddressCounter, iti: itiShipping });
        }

        // Populate country dropdown for the new shipping address
        populateCountryDropdownForShipping($(`select[name="shipping_address[${shippingAddressCounter}][country]"]`));    });

    // Remove shipping address form
    $(document).on("click", ".delete-shipping-address", function() {
        const containerId = $(this).data("id");
        $(`#${containerId}`).remove();
        itiShippingPhones = itiShippingPhones.filter(item => item.id !== parseInt(containerId.split("-")[2]));
    });

    // Fetch active languages from API
    function fetchActiveLanguages() {
        $.ajax({
            url: ApiForm + "/get_active_languages",
            type: "GET",
            dataType: "json",
            beforeSend: function (xhr) {
                xhr.setRequestHeader("Authorization", "Bearer " + strkey);
                xhr.setRequestHeader("menu-uuid", menu_id);
            },
            success: function (response) {
                if (response.status_code == 200) {
                    populateLanguageDropdown(response.data);
                }
            },
            error: function (error) {
                console.error("Error fetching languages:", error);
                $("#languageSelect").append('<option value="">Select Language</option>');
            }
        });
    }

    // Populate language dropdown with API data
    function populateLanguageDropdown(languages) {
        const $languageSelect = $("#languageSelect");
        $languageSelect.empty();
        $languageSelect.append('<option value="" disabled selected>Select Language</option>');
        $.each(languages, function (index, language) {
            $languageSelect.append(`<option value="${language.code}">${language.name}</option>`);
        });
    }

    // Fetch active countries from API
    function fetchActiveCountries() {
        $.ajax({
            url: ApiPlugin + "/ecommerce/get_active_countries",
            type: "GET",
            dataType: "json",
            beforeSend: function (xhr) {
                xhr.setRequestHeader("Authorization", "Bearer " + strkey);
                xhr.setRequestHeader("menu-uuid", menu_id);
            },
            success: function (response) {
                if (response.status_code == 200) {
                    populateCountryDropdown(response.data);
                }
            },
            error: function (error) {
                console.error("Error fetching countries:", error);
                $("#billingCountrySelect").append('<option value="">Select Country</option>');
            }
        });
    }

    // Populate country dropdown with API data
    function populateCountryDropdown(countries) {
        const $countrySelect = $("#billingCountrySelect");
        $countrySelect.empty();
        $countrySelect.append('<option value="" disabled selected>Select Country</option>');
        $.each(countries, function (index, country) {
            $countrySelect.append(`<option value="${country.code}">${country.name}</option>`);
        });
    }

    // Populate country dropdown for shipping addresses
    function populateCountryDropdownForShipping($select) {
        $.ajax({
            url: ApiPlugin + "/ecommerce/get_active_countries",
            type: "GET",
            dataType: "json",
            beforeSend: function (xhr) {
                xhr.setRequestHeader("Authorization", "Bearer " + strkey);
                xhr.setRequestHeader("menu-uuid", menu_id);
            },
            success: function (response) {
                if (response.status_code == 200) {
                    $select.empty();
                    $select.append('<option value="" disabled selected>Select Country</option>');
                    $.each(response.data, function (index, country) {
                        $select.append(`<option value="${country.code}">${country.name}</option>`);
                    });
                }
            },
            error: function (error) {
                console.error("Error fetching countries:", error);
                $select.append('<option value="">Select Country</option>');
            }
        });
    }

    // Fetch product tags from API
    function fetchProductTags() {
        $.ajax({
            url: ApiPlugin + "/ecommerce/product/get_product_tags",
            type: "GET",
            dataType: "json",
            beforeSend: function (xhr) {
                xhr.setRequestHeader("Authorization", "Bearer " + strkey);
                xhr.setRequestHeader("menu-uuid", menu_id);
            },
            success: function (response) {
                if (response.status_code == 200) {
                    populateTagsDropdown(response.data);
                }
            },
            error: function (error) {
                console.error("Error fetching tags:", error);
                $("#tagsSelect").append('<option value="">No Tags Available</option>');
            }
        });
    }

    // Populate tags dropdown with API data
    function populateTagsDropdown(tags) {
        const $tagsSelect = $("#tagsSelect");
        $tagsSelect.empty();
        $.each(tags, function (index, tag) {
            $tagsSelect.append(`<option value="${tag.name}">${tag.name}</option>`);
        });
    }

    // Submit form handler
    $("#addCustomerForm").off("submit").on("submit", function (e) {
        e.preventDefault();
        e.stopPropagation();

        // Validate Billing Fields
        const billingFirstName = $("input[name='billing_address[address_first_name]']").val();
        const billingLastName = $("input[name='billing_address[address_last_name]']").val();
        const billingPhone = $("input[name='billing_address[address_phone]']").val();

        if (!billingFirstName) {
            showToast('Billing first name is required.', 'Error', 'error');
            return;
        }
        if (!billingLastName) {
            showToast('Billing last name is required.', 'Error', 'error');
            return;
        }
        if (!billingPhone) {
            showToast('Billing phone is required.', 'Error', 'error');
            return;
        }
        if (!itiBillingPhone.isValidNumber()) {
            showToast('Invalid billing phone number.', 'Error', 'error');
            return;
        }

        // Validate Shipping Addresses
        let isValid = true;

        $(".shipping-address-container").each(function () {
            const $container = $(this);
            const index = $container.attr("id").split("-").pop();

            const shippingFirstName = $container.find(`input[name='shipping_address[${index}][address_first_name]']`).val();
            const shippingLastName = $container.find(`input[name='shipping_address[${index}][address_last_name]']`).val();
            const shippingPhone = $container.find(`input[name='shipping_address[${index}][address_phone]']`).val();
            const shippingEmail = $container.find(`input[name='shipping_address[${index}][address_email]']`).val();

            if (!shippingFirstName) {
                showToast(`Shipping first name is required (entry ${index}).`, 'Error', 'error');
                isValid = false;
                return false;
            }
            if (!shippingLastName) {
                showToast(`Shipping last name is required (entry ${index}).`, 'Error', 'error');
                isValid = false;
                return false;
            }
            if (!shippingPhone) {
                showToast(`Shipping phone is required (entry ${index}).`, 'Error', 'error');
                isValid = false;
                return false;
            }
            if (!shippingEmail) {
                showToast(`Shipping email is required (entry ${index}).`, 'Error', 'error');
                isValid = false;
                return false;
            }

            const itiShipping = itiShippingPhones.find(item => item.id === parseInt(index))?.iti;
            if (itiShipping && !itiShipping.isValidNumber()) {
                showToast(`Invalid shipping phone number (entry ${index}).`, 'Error', 'error');
                isValid = false;
                return false;
            }
        });

        if (!isValid) {
            return;
        }

        // Prepare form data
        var formData = new FormData(this);
        formData.set('phone', itiPhone.getNumber());
        formData.set('billing_address[address_phone]', itiBillingPhone.getNumber());
        formData.set('market_emails', $("#market_emails").is(':checked') ? 1 : 0);
        formData.set('market_sms', $("#market_sms").is(':checked') ? 1 : 0);
        let selectedTags = $("#tagsSelect").val();
        if (selectedTags && selectedTags.length > 0) {
            formData.set('tags', selectedTags.join(','));
        } else {
            formData.set('tags', '');
        }

        // Update shipping address phone numbers
        $(".shipping-address-container").each(function () {
            const index = $(this).attr("id").split("-").pop();
            const itiShipping = itiShippingPhones.find(item => item.id === parseInt(index))?.iti;
            if (itiShipping) {
                formData.set(`shipping_address[${index}][address_phone]`, itiShipping.getNumber());
            }
        });

        imgload.show();
        $.ajax({
            url: ApiPlugin + "/ecommerce/customer/store",
            type: "POST",
            data: formData,
            contentType: false,
            processData: false,
            beforeSend: function (xhr) {
                xhr.setRequestHeader("Authorization", "Bearer " + strkey);
                xhr.setRequestHeader("menu-uuid", menu_id);
            },
            success: function (response) {
                imgload.hide();
                if (response.status_code == 200) {
                    showToast(response.message, 'Success', 'success', '?P=customer&M=' + menu_id);
                }
            },
            error: function (xhr) {
                imgload.hide();
                if (xhr.status === 422) {
                    let errors = xhr.responseJSON.errors;
                    let errorMessages = "";
                    $.each(errors, function (field, messages) {
                        messages.forEach(function (message) {
                            errorMessages += `<li>${message}</li>`;
                        });
                    });
                    showToast(`<ul>${errorMessages}</ul>`, 'Error', 'error');
                } else {
                    showToast(getTranslation('something_went_wrong'), 'Error', 'error');
                }
            }
        });
    });
});