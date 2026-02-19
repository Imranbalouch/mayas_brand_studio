document.title="Dashboard | Add Company";
$(document).ready(function() {
    fetchActiveCustomers();
    fetchActiveLocations();
    fetchActivePaymentTerm();
    fetchActiveTaxType();
    // fetchActiveCountries();
    fetchCatalogs();

    // Initialize Select2 for catalogs and customers
    $("#catalogsSelect").select2({
        placeholder: "Select Catalogs",
        allowClear: true
    });
    $("#customerSelect").select2({
        placeholder: "Select Customers",
        allowClear: true
    });
    $("#locationSelect").select2({
        placeholder: "Select Location",
    });
    $("#PaymenttermSelect").select2({
        placeholder: "Select Payment Term",
    });
    $("#taxtypeSelect").select2({
        placeholder: "Select Tax Type",
    });

    function initializePhoneInputs() {
        const billingPhoneInput = document.querySelector("#billing_address_phone");

        if (billingPhoneInput) {
            itiBillingPhone = window.intlTelInput(billingPhoneInput, {
                initialCountry: "ae",
                preferredCountries: ['ae'],
                autoPlaceholder: "polite",
                showSelectedDialCode: true,
                utilsScript: "./assets/plugins/intel-input/utils.js",
                hiddenInput: () => ({ phone: "billing_address_phone" })
            });
            billingPhoneInput.addEventListener("blur", validatePhoneInput);
            billingPhoneInput.addEventListener("keyup", validatePhoneInput);
        }
    }

    function validatePhoneInput(event) {
        const input = event.target;
        const iti = window.intlTelInputGlobals.getInstance(input);
        const parent = input.parentElement.parentElement;
        const errorTxt = parent.querySelector(".error-txt");
        const submitButton = $("#addCompanyForm").find("button[type=submit]");

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

    initializePhoneInputs();

    // let shippingAddressCounter = 0;
    // let itiShippingPhones = [];

    // $("#addShippingAddressBtn").on("click", function() {
    //     shippingAddressCounter++;
    //     const containerId = `shipping-address-${shippingAddressCounter}`;

    //     $("#shippingAddressesContainer").append(`
    //         <div class="shipping-address-container" id="${containerId}">
    //             <span class="delete-shipping-address" data-id="${containerId}">×</span>
    //             <h4>Shipping Address (${shippingAddressCounter})</h4>
    //             <div class="row gap-lg-0 gap-5 mt-5">
    //                 <div class="mb-6 fv-row col-md-6">
    //                     <label for="shipping_address[${shippingAddressCounter}][address_first_name]" class="form-label required"><g-t>first_name</g-t></label>
    //                     <input type="text" class="form-control mb-2" name="shipping_address[${shippingAddressCounter}][address_first_name]">
    //                 </div>
    //                 <div class="mb-6 fv-row col-md-6">
    //                     <label for="shipping_address[${shippingAddressCounter}][address_last_name]" class="form-label required"><g-t>last_name</g-t></label>
    //                     <input type="text" class="form-control mb-2" name="shipping_address[${shippingAddressCounter}][address_last_name]">
    //                 </div>
    //                 <div class="mb-6 fv-row col-md-6">
    //                     <label for="shipping_address[${shippingAddressCounter}][address_email]" class="form-label required"><g-t>email</g-t></label>
    //                     <input type="email" class="form-control mb-2" name="shipping_address[${shippingAddressCounter}][address_email]">
    //                 </div>
    //                 <div class="mb-6 fv-row col-md-6">
    //                     <label for="shipping_address[${shippingAddressCounter}][company]" class="form-label"><g-t>company</g-t></label>
    //                     <input type="text" class="form-control mb-2" name="shipping_address[${shippingAddressCounter}][company]">
    //                 </div>
    //                 <div class="mb-6 fv-row col-md-6">
    //                     <label for="shipping_address[${shippingAddressCounter}][address_phone]" class="form-label required"><g-t>phone</g-t></label>
    //                     <input type="tel" class="form-control mb-2" name="shipping_address[${shippingAddressCounter}][address_phone]" id="shipping_address_phone_${shippingAddressCounter}">
    //                     <span class="error-txt"></span>
    //                 </div>
    //                 <div class="mb-6 fv-row col-md-12">
    //                     <label for="shipping_address[${shippingAddressCounter}][address]" class="form-label"><g-t>address</g-t></label>
    //                     <input type="text" class="form-control mb-2" name="shipping_address[${shippingAddressCounter}][address]">
    //                 </div>
    //                 <div class="mb-6 fv-row col-md-6">
    //                     <label for="shipping_address[${shippingAddressCounter}][apartment]" class="form-label"><g-t>apartment</g-t></label>
    //                     <input type="text" class="form-control mb-2" name="shipping_address[${shippingAddressCounter}][apartment]">
    //                 </div>
    //                 <div class="mb-6 fv-row col-md-6">
    //                     <label for="shipping_address[${shippingAddressCounter}][city]" class="form-label"><g-t>city</g-t></label>
    //                     <input type="text" class="form-control mb-2" name="shipping_address[${shippingAddressCounter}][city]">
    //                 </div>
    //                 <div class="mb-6 fv-row col-md-6">
    //                     <label for="shipping_address[${shippingAddressCounter}][state]" class="form-label"><g-t>state</g-t></label>
    //                     <input type="text" class="form-control mb-2" name="shipping_address[${shippingAddressCounter}][state]">
    //                 </div>
    //                 <div class="mb-6 fv-row col-md-6">
    //                     <label for="shipping_address[${shippingAddressCounter}][country]" class="form-label"><g-t>country</g-t></label>
    //                     <select class="form-control mb-2 shipping-country-select" name="shipping_address[${shippingAddressCounter}][country]">
    //                         <option value="" disabled selected>Select Country</option>
    //                     </select>
    //                 </div>
    //             </div>
    //         </div>
    //         <hr>
    //     `);

    //     const shippingPhoneInput = document.querySelector(`#shipping_address_phone_${shippingAddressCounter}`);
    //     if (shippingPhoneInput) {
    //         const itiShipping = window.intlTelInput(shippingPhoneInput, {
    //             initialCountry: "ae",
    //             preferredCountries: ['ae'],
    //             autoPlaceholder: "polite",
    //             showSelectedDialCode: true,
    //             utilsScript: "./assets/plugins/intel-input/utils.js",
    //             hiddenInput: () => ({ phone: `shipping_address[${shippingAddressCounter}][address_phone]` })
    //         });
    //         shippingPhoneInput.addEventListener("blur", validatePhoneInput);
    //         shippingPhoneInput.addEventListener("keyup", validatePhoneInput);
    //         itiShippingPhones.push({ id: shippingAddressCounter, iti: itiShipping });
    //     }

    //     populateCountryDropdownForShipping($(`select[name="shipping_address[${shippingAddressCounter}][country]"]`));
    // });

    // $(document).on("click", ".delete-shipping-address", function() {
    //     const containerId = $(this).data("id");
    //     $(`#${containerId}`).remove();
    //     itiShippingPhones = itiShippingPhones.filter(item => item.id !== parseInt(containerId.split("-")[2]));
    // });

    function fetchActiveCustomers() {
        $.ajax({
            url: ApiPlugin + "/ecommerce/customer/active_customers",
            type: "GET",
            dataType: "json",
            beforeSend: function (xhr) {
                xhr.setRequestHeader("Authorization", "Bearer " + strkey);
                xhr.setRequestHeader("menu-uuid", menu_id);
            },
            success: function (response) {
                if (response.status_code == 200) {
                    populateCustomerDropdown(response.data);
                }
            },
            error: function (error) {
                console.error("Error fetching customers:", error);
                $("#customerSelect").append('<option value="">Select Customer</option>');
            }
        });
    }

    function populateCustomerDropdown(customers) {
        const $customerSelect = $("#customerSelect");
        $customerSelect.empty();
        $customerSelect.append('');
        $.each(customers, function (index, customer) {
            $customerSelect.append(`<option value="${customer.uuid}">${customer.first_name} ${customer.last_name}</option>`);
        });
    }

    function fetchActiveLocations() {
        $.ajax({
            url: ApiPlugin + "/ecommerce/warehouse/get_active_warehouse_locations",
            type: "GET",
            dataType: "json",
            beforeSend: function (xhr) {
                xhr.setRequestHeader("Authorization", "Bearer " + strkey);
                xhr.setRequestHeader("menu-uuid", menu_id);
            },
            success: function (response) {
                if (response.status_code == 200) {
                    populateLocationDropdown(response.data);
                }
            },
            error: function (error) {
                console.error("Error fetching locations:", error);
                $("#locationSelect").append('<option value="">Select Location</option>');
            }
        });
    }

    function populateLocationDropdown(locations) {
        const $locationSelect = $("#locationSelect");
        $locationSelect.empty();
        $locationSelect.append('<option value="" disabled selected>Select Location</option>');
        $.each(locations, function (index, location) {
            $locationSelect.append(`<option value="${location.uuid}">${location.location_name}</option>`);
        });
    }
    function fetchActiveTaxType() {
        $.ajax({
            url: ApiPlugin + "/ecommerce/get_active_tax_type",
            type: "GET",
            dataType: "json",
            beforeSend: function (xhr) {
                xhr.setRequestHeader("Authorization", "Bearer " + strkey);
                xhr.setRequestHeader("menu-uuid", menu_id);
            },
            success: function (response) {
                if (response.status_code == 200) {
                    populateTaxTypeDropdown(response.data);
                }
            },
            error: function (error) {
                console.error("Error fetching tax types:", error);
                $("#taxtypeSelect").append('<option value="">Select Tax Type</option>');
            }
        });
    }

    function populateTaxTypeDropdown(taxes) {
        const $taxSelect = $("#taxtypeSelect");
        $taxSelect.empty();
        $taxSelect.append('<option value="" disabled selected>Select Tax Type</option>');
        $.each(taxes, function (index, tax) {
            $taxSelect.append(`<option value="${tax.uuid}">${tax.name}</option>`);
        });
    }
    function fetchActivePaymentTerm() {
        $.ajax({
            url: ApiPlugin + "/ecommerce/get_active_paymentterm",
            type: "GET",
            dataType: "json",
            beforeSend: function (xhr) {
                xhr.setRequestHeader("Authorization", "Bearer " + strkey);
                xhr.setRequestHeader("menu-uuid", menu_id);
            },
            success: function (response) {
                if (response.status_code == 200) {
                    populatePaymentTermDropdown(response.data);
                }
            },
            error: function (error) {
                console.error("Error fetching payment term:", error);
                $("#PaymenttermSelect").append('<option value="">Select Payment Term</option>');
            }
        });
    }

    function populatePaymentTermDropdown(paymentterm) {
        const $PaymenttermSelect = $("#PaymenttermSelect");
        $PaymenttermSelect.empty();
        $PaymenttermSelect.append('<option value="" disabled selected>Select Payment Term</option>');
        $.each(paymentterm, function (index, payment) {
            $PaymenttermSelect.append(`<option value="${payment.uuid}">${payment.name}</option>`);
        });
    }

    // function fetchActiveCountries() {
    //     $.ajax({
    //         url: ApiPlugin + "/ecommerce/get_active_countries",
    //         type: "GET",
    //         dataType: "json",
    //         beforeSend: function (xhr) {
    //             xhr.setRequestHeader("Authorization", "Bearer " + strkey);
    //             xhr.setRequestHeader("menu-uuid", menu_id);
    //         },
    //         success: function (response) {
    //             if (response.status_code == 200) {
    //                 populateCountryDropdown(response.data);
    //             }
    //         },
    //         error: function (error) {
    //             console.error("Error fetching countries:", error);
    //             $("#billingCountrySelect").append('<option value="">Select Country</option>');
    //         }
    //     });
    // }

    // function populateCountryDropdown(countries) {
    //     const $countrySelect = $("#billingCountrySelect");
    //     $countrySelect.empty();
    //     $countrySelect.append('<option value="" disabled selected>Select Country</option>');
    //     $.each(countries, function (index, country) {
    //         $countrySelect.append(`<option value="${country.code}">${country.name}</option>`);
    //     });
    // }

    // function populateCountryDropdownForShipping($select) {
    //     $.ajax({
    //         url: ApiPlugin + "/ecommerce/get_active_countries",
    //         type: "GET",
    //         dataType: "json",
    //         beforeSend: function (xhr) {
    //             xhr.setRequestHeader("Authorization", "Bearer " + strkey);
    //             xhr.setRequestHeader("menu-uuid", menu_id);
    //         },
    //         success: function (response) {
    //             if (response.status_code == 200) {
    //                 $select.empty();
    //                 $select.append('<option value="" disabled selected>Select Country</option>');
    //                 $.each(response.data, function (index, country) {
    //                     $select.append(`<option value="${country.code}">${country.name}</option>`);
    //                 });
    //             }
    //         },
    //         error: function (error) {
    //             console.error("Error fetching countries:", error);
    //             $select.append('<option value="">Select Country</option>');
    //         }
    //     });
    // }

    function fetchCatalogs() {
        // Placeholder for fetching catalogs; adjust API endpoint as needed
        $.ajax({
            url: ApiPlugin + "/ecommerce/catalog/get_active_catalogs", // Replace with actual endpoint
            type: "GET",
            dataType: "json",
            beforeSend: function (xhr) {
                xhr.setRequestHeader("Authorization", "Bearer " + strkey);
                xhr.setRequestHeader("menu-uuid", menu_id);
            },
            success: function (response) {
                if (response.status_code == 200) {
                    populateCatalogsDropdown(response.data);
                }
            },
            error: function (error) {
                console.error("Error fetching catalogs:", error);
                $("#catalogsSelect").append('<option value="">No Catalogs Available</option>');
            }
        });
    }

    function populateCatalogsDropdown(catalogs) {
        const $catalogsSelect = $("#catalogsSelect");
        $catalogsSelect.empty();
        $.each(catalogs, function (index, catalog) {
            $catalogsSelect.append(`<option value="${catalog.uuid}">${catalog.catalog}</option>`);
        });
    }

    $("#addCompanyForm").on("submit", function (e) {
        e.preventDefault();
        e.stopPropagation();

        const companyName = $("input[name='company_name']").val();
        const customerIds = $("#customerSelect").val();
        // const billingFirstName = $("input[name='billing_address[address_first_name]']").val();
        // const billingLastName = $("input[name='billing_address[address_last_name]']").val();
        // const billingPhone = $("input[name='billing_address[address_phone]']").val();

        if (!companyName) {
            showToast('Company name is required.', 'Error', 'error');
            return;
        }
        if (!customerIds || customerIds.length === 0) {
            showToast('At least one customer selection is required.', 'Error', 'error');
            return;
        }
        // if (!billingFirstName) {
        //     showToast('Billing first name is required.', 'Error', 'error');
        //     return;
        // }
        // if (!billingLastName) {
        //     showToast('Billing last name is required.', 'Error', 'error');
        //     return;
        // }
        // if (!billingPhone) {
        //     showToast('Billing phone is required.', 'Error', 'error');
        //     return;
        // }
        // if (!itiBillingPhone.isValidNumber()) {
        //     showToast('Invalid billing phone number.', 'Error', 'error');
        //     return;
        // }

        // let isValid = true;
        // $(".shipping-address-container").each(function () {
        //     const $container = $(this);
        //     const index = $container.attr("id").split("-").pop();

        //     const shippingFirstName = $container.find(`input[name='shipping_address[${index}][address_first_name]']`).val();
        //     const shippingLastName = $container.find(`input[name='shipping_address[${index}][address_last_name]']`).val();
        //     const shippingPhone = $container.find(`input[name='shipping_address[${index}][address_phone]']`).val();
        //     const shippingEmail = $container.find(`input[name='shipping_address[${index}][address_email]']`).val();

        //     if (!shippingFirstName) {
        //         showToast(`Shipping first name is required (entry ${index}).`, 'Error', 'error');
        //         isValid = false;
        //         return false;
        //     }
        //     if (!shippingLastName) {
        //         showToast(`Shipping last name is required (entry ${index}).`, 'Error', 'error');
        //         isValid = false;
        //         return false;
        //     }
        //     if (!shippingPhone) {
        //         showToast(`Shipping phone is required (entry ${index}).`, 'Error', 'error');
        //         isValid = false;
        //         return false;
        //     }
        //     if (!shippingEmail) {
        //         showToast(`Shipping email is required (entry ${index}).`, 'Error', 'error');
        //         isValid = false;
        //         return false;
        //     }

        //     const itiShipping = itiShippingPhones.find(item => item.id === parseInt(index))?.iti;
        //     if (itiShipping && !itiShipping.isValidNumber()) {
        //         showToast(`Invalid shipping phone number (entry ${index}).`, 'Error', 'error');
        //         isValid = false;
        //         return false;
        //     }
        // });

        // if (!isValid) {
        //     return;
        // }

        var formData = new FormData(this);
        // formData.set('billing_address[address_phone]', itiBillingPhone.getNumber());
        // formData.set('ship_to_address', $("#ship_to_address").is(':checked') ? 1 : 0);
        formData.set('approved',  1);

        // $(".shipping-address-container").each(function () {
        //     const index = $(this).attr("id").split("-").pop();
        //     const itiShipping = itiShippingPhones.find(item => item.id === parseInt(index))?.iti;
        //     if (itiShipping) {
        //         formData.set(`shipping_address[${index}][address_phone]`, itiShipping.getNumber());
        //     }
        // });

        imgload.show();
        $.ajax({
            url: ApiPlugin + "/ecommerce/company/store",
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
                    showToast(response.message, 'Success', 'success', '?P=company&M=' + menu_id);
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