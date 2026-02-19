document.title = "Dashboard | Edit Company";
var urlParams = new URLSearchParams(window.location.search);
var id = urlParams.get('id');

$(document).ready(function() {
    let itiShippingPhones = [];
    let itiBillingPhone;

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
    $("#taxtypeSelect").select2({
        placeholder: "Select Tax Type",
    });
    $("#PaymenttermSelect").select2({
        placeholder: "Select Payment Term",
    });

     Promise.all([
        fetchActiveCustomers(),
        // fetchActiveCountries(),
        fetchActiveLocations(),
        fetchActivePaymentTerm(),
        fetchActiveTaxType(),
        fetchCatalogs()
    ]).then(() => {
        // Only after all data is loaded, initialize phone inputs and fetch company data
        initializePhoneInputs();
        
        if (id) {
            fetchCompanyData();
        }
    }).catch(error => {
        console.error("Error loading initial data:", error);
        showToast('Error loading required data', 'Error', 'error');
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
        const submitButton = $("#editCompanyForm").find("button[type=submit]");

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

    // $("#addShippingAddressBtn").on("click", function() {
    //     shippingAddressCounter++;
    //     const containerId = `shipping-address-${shippingAddressCounter}`;

    //     $("#shippingAddressesContainer").append(`
    //         <div class="shipping-address-container" id="${containerId}">
    //             <span class="delete-shipping-address" data-id="${containerId}">×</span>
    //             <h4>Shipping Address (${shippingAddressCounter})</h4>
    //             <div class="row gap-lg-0 gap-5 mt-5">
    //                 <input type="hidden" name="shipping_address[${shippingAddressCounter}][uuid]" value="">
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

    // $(document).on("click", ".delete-shipping-address", function(e) {
    //     e.stopPropagation();
    //     const containerId = $(this).data("id");
    //     const addressUuid = $(this).data("address-uuid");
    //     if (addressUuid) {
    //         Swal.fire({
    //             title: getTranslation('deleteConfirmMsg'),
    //             icon: 'warning',
    //             showCancelButton: true,
    //             cancelButtonText: getTranslation('cancel'),
    //             confirmButtonColor: '#15508A',
    //             cancelButtonColor: '#15508A',
    //             confirmButtonText: getTranslation('delete'),
    //             reverseButtons: true,
    //             showClass: { popup: 'animated fadeInDown faster' },
    //             hideClass: { popup: 'animated fadeOutUp faster' }
    //         }).then((result) => {
    //             if (result.isConfirmed) {
    //                 $.ajax({
    //                     url: ApiPlugin + `/ecommerce/company/${id}/shipping-address/${addressUuid}`,
    //                     type: "DELETE",
    //                     beforeSend: function(xhr) {
    //                         xhr.setRequestHeader("Authorization", "Bearer " + strkey);
    //                         xhr.setRequestHeader("menu-uuid", menu_id);
    //                     },
    //                     success: function(response) {
    //                         if (response.status_code == 200) {
    //                             $(`#${containerId}`).remove();
    //                             showToast('Shipping address deleted successfully', 'Success', 'success');
    //                         }
    //                     },
    //                     error: function(error) {
    //                         showToast('Error deleting shipping address', 'Error', 'error');
    //                     }
    //                 });
    //             }
    //         });
    //     } else {
    //         $(`#${containerId}`).remove();
    //     }
    // });

    function fetchActiveCustomers() {
        return new Promise((resolve, reject) => {
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
                        resolve();
                    } else {
                        reject(response.message);
                    }
                },
                error: function (error) {
                    console.error("Error fetching customers:", error);
                    $("#customerSelect").append('<option value="">Select Customer</option>');
                    reject(error);
                }
            });
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
        return new Promise((resolve, reject) => {
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
                        resolve();
                    } else {
                        reject(response.message);
                    }
                },
                error: function (error) {
                    console.error("Error fetching locations:", error);
                    $("#locationSelect").append('<option value="">Select Location</option>');
                    reject(error);
                }
            });
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
        return new Promise((resolve, reject) => {
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
                        resolve();
                    } else {
                        reject(response.message);
                    }
                },
                error: function (error) {
                    console.error("Error fetching payment term:", error);
                    $("#PaymenttermSelect").append('<option value="">Select Payment Term</option>');
                    reject(error);
                }
            });
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
    //     return new Promise((resolve, reject) => {
    //         $.ajax({
    //             url: ApiPlugin + "/ecommerce/get_active_countries",
    //             type: "GET",
    //             dataType: "json",
    //             beforeSend: function (xhr) {
    //                 xhr.setRequestHeader("Authorization", "Bearer " + strkey);
    //                 xhr.setRequestHeader("menu-uuid", menu_id);
    //             },
    //             success: function (response) {
    //                 if (response.status_code == 200) {
    //                     populateCountryDropdown(response.data);
    //                     window.activeCountries = response.data;
    //                     resolve();
    //                 } else {
    //                     reject(response.message);
    //                 }
    //             },
    //             error: function (error) {
    //                 console.error("Error fetching countries:", error);
    //                 $("#billingCountrySelect").append('<option value="">Select Country</option>');
    //                 reject(error);
    //             }
    //         });
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

    // function populateCountryDropdownForShipping($select, callback) {
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
    //                 if (callback) callback();
    //             }
    //         },
    //         error: function (error) {
    //             console.error("Error fetching countries:", error);
    //             $select.append('<option value="">Select Country</option>');
    //             if (callback) callback();
    //         }
    //     });
    // }

     function fetchCatalogs() {
        return new Promise((resolve, reject) => {
            $.ajax({
                url: ApiPlugin + "/ecommerce/catalog/get_active_catalogs",
                type: "GET",
                dataType: "json",
                beforeSend: function (xhr) {
                    xhr.setRequestHeader("Authorization", "Bearer " + strkey);
                    xhr.setRequestHeader("menu-uuid", menu_id);
                },
                success: function (response) {
                    if (response.status_code == 200) {
                        populateCatalogsDropdown(response.data);
                        resolve();
                    } else {
                        reject(response.message);
                    }
                },
                error: function (error) {
                    console.error("Error fetching catalogs:", error);
                    $("#catalogsSelect").append('<option value="">No Catalogs Available</option>');
                    reject(error);
                }
            });
        });
    }


    function populateCatalogsDropdown(catalogs) {
        const $catalogsSelect = $("#catalogsSelect");
        $catalogsSelect.empty();
        $.each(catalogs, function (index, catalog) {
            $catalogsSelect.append(`<option value="${catalog.uuid}">${catalog.catalog}</option>`);
        });
    }

    function fetchCompanyData() {
        $.ajax({
            url: ApiPlugin + "/ecommerce/company/edit/" + id,
            type: "GET",
            dataType: "json",
            beforeSend: function (xhr) {
                xhr.setRequestHeader("Authorization", "Bearer " + strkey);
                xhr.setRequestHeader("menu-uuid", menu_id);
                imgload.show();
            },
            success: function (response) {
                imgload.hide();
                if (response.status_code == 200) {
                    populateCompanyData(response.data);
                }
            },
            error: function (error) {
                imgload.hide();
                error = error.responseJSON;
                if (error && error.status_code == 404) {
                    showToast(error.message, 'Error', 'error', '?P=company&M=' + menu_id);
                } else {
                    showToast('Internal Server Error.', 'Error', 'error', '?P=company&M=' + menu_id);
                }
            }
        });
    }

    function populateCompanyData(data) {
        if (data.length === 0) return;
        const company = data[0];

        $("input[name=company_name]").val(company.company_name);
        $("input[name=company_id]").val(company.company_id);
        if (company.main_contact_id) {
            const customerArray = Array.isArray(company.main_contact_id) ? company.main_contact_id : JSON.parse(company.main_contact_id || '[]');
            $("#customerSelect").val(customerArray).trigger('change');
        }
        $("#locationSelect").val(company.location_id).trigger('change.select2'); 
        $("input[name=tax_id]").val(company.tax_id);
        $("#taxtypeSelect").val(company.tax_setting).trigger('change.select2'); 
        $("#PaymenttermSelect").val(company.payment_terms_id).trigger('change.select2'); 
        $("input[name=deposit]").val(company.deposit);
        $("input[name=order_submission]").val(company.order_submission);
        if (company.catalogs_id) {
            const catalogsArray = company.catalogs_id;
            $("#catalogsSelect").val(catalogsArray).trigger('change');
        }
        // $("#ship_to_address").prop('checked', company.ship_to_address == 1);

        // if (company.billingAddress) {
        //     const billing = company.billingAddress;
        //     $("input[name='billing_address[address_first_name]']").val(billing.address_first_name);
        //     $("input[name='billing_address[address_last_name]']").val(billing.address_last_name);
        //     $("input[name='billing_address[company]']").val(billing.company);
        //     $("input[name='billing_address[address]']").val(billing.address);
        //     $("input[name='billing_address[apartment]']").val(billing.apartment);
        //     $("input[name='billing_address[city]']").val(billing.city);
        //     $("input[name='billing_address[state]']").val(billing.state);
        //     if (billing.address_phone) {
        //         $("input[name='billing_address[address_phone]']").val(billing.address_phone);
        //         itiBillingPhone.setNumber(billing.address_phone);
        //     }
        //     $("select[name='billing_address[country]']").val(billing.country);
        // }

        // if (company.shippingAddress && Array.isArray(company.shippingAddress)) {
        //     company.shippingAddress.forEach((shipping, index) => {
        //         shippingAddressCounter++;
        //         const containerId = `shipping-address-${shippingAddressCounter}`;

        //         $("#shippingAddressesContainer").append(`
        //             <div class="shipping-address-container" id="${containerId}" data-address-uuid="${shipping.uuid || ''}">
        //                 <span class="delete-shipping-address" data-id="${containerId}" data-address-uuid="${shipping.uuid || ''}">×</span>
        //                 <h4>Shipping Address (${shippingAddressCounter})</h4>
        //                 <div class="row gap-lg-0 gap-5 mt-5">
        //                     <input type="hidden" name="shipping_address[${shippingAddressCounter}][uuid]" value="${shipping.uuid || ''}">
        //                     <div class="mb-6 fv-row col-md-6">
        //                         <label for="shipping_address[${shippingAddressCounter}][address_first_name]" class="form-label required"><g-t>first_name</g-t></label>
        //                         <input type="text" class="form-control mb-2" name="shipping_address[${shippingAddressCounter}][address_first_name]" value="${shipping.address_first_name || ''}">
        //                     </div>
        //                     <div class="mb-6 fv-row col-md-6">
        //                         <label for="shipping_address[${shippingAddressCounter}][address_last_name]" class="form-label required"><g-t>last_name</g-t></label>
        //                         <input type="text" class="form-control mb-2" name="shipping_address[${shippingAddressCounter}][address_last_name]" value="${shipping.address_last_name || ''}">
        //                     </div>
        //                     <div class="mb-6 fv-row col-md-6">
        //                         <label for="shipping_address[${shippingAddressCounter}][address_email]" class="form-label required"><g-t>email</g-t></label>
        //                         <input type="email" class="form-control mb-2" name="shipping_address[${shippingAddressCounter}][address_email]" value="${shipping.address_email || ''}">
        //                     </div>
        //                     <div class="mb-6 fv-row col-md-6">
        //                         <label for="shipping_address[${shippingAddressCounter}][company]" class="form-label"><g-t>company</g-t></label>
        //                         <input type="text" class="form-control mb-2" name="shipping_address[${shippingAddressCounter}][company]" value="${shipping.company || ''}">
        //                     </div>
        //                     <div class="mb-6 fv-row col-md-6">
        //                         <label for="shipping_address[${shippingAddressCounter}][address_phone]" class="form-label required"><g-t>phone</g-t></label>
        //                         <input type="tel" class="form-control mb-2" name="shipping_address[${shippingAddressCounter}][address_phone]" id="shipping_address_phone_${shippingAddressCounter}" value="${shipping.address_phone || ''}">
        //                         <span class="error-txt"></span>
        //                     </div>
        //                     <div class="mb-6 fv-row col-md-12">
        //                         <label for="shipping_address[${shippingAddressCounter}][address]" class="form-label"><g-t>address</g-t></label>
        //                         <input type="text" class="form-control mb-2" name="shipping_address[${shippingAddressCounter}][address]" value="${shipping.address || ''}">
        //                     </div>
        //                     <div class="mb-6 fv-row col-md-6">
        //                         <label for="shipping_address[${shippingAddressCounter}][apartment]" class="form-label"><g-t>apartment</g-t></label>
        //                         <input type="text" class="form-control mb-2" name="shipping_address[${shippingAddressCounter}][apartment]" value="${shipping.apartment || ''}">
        //                     </div>
        //                     <div class="mb-6 fv-row col-md-6">
        //                         <label for="shipping_address[${shippingAddressCounter}][city]" class="form-label"><g-t>city</g-t></label>
        //                         <input type="text" class="form-control mb-2" name="shipping_address[${shippingAddressCounter}][city]" value="${shipping.city || ''}">
        //                     </div>
        //                     <div class="mb-6 fv-row col-md-6">
        //                         <label for="shipping_address[${shippingAddressCounter}][state]" class="form-label"><g-t>state</g-t></label>
        //                         <input type="text" class="form-control mb-2" name="shipping_address[${shippingAddressCounter}][state]" value="${shipping.state || ''}">
        //                     </div>
        //                     <div class="mb-6 fv-row col-md-6">
        //                         <label for="shipping_address[${shippingAddressCounter}][country]" class="form-label"><g-t>country</g-t></label>
        //                         <select class="form-control mb-2 shipping-country-select" name="shipping_address[${shippingAddressCounter}][country]">
        //                             <option value="" disabled selected>Select Country</option>
        //                         </select>
        //                     </div>
        //                 </div>
        //             </div>
        //             <hr>
        //         `);

        //         const shippingPhoneInput = document.querySelector(`#shipping_address_phone_${shippingAddressCounter}`);
        //         if (shippingPhoneInput) {
        //             const itiShipping = window.intlTelInput(shippingPhoneInput, {
        //                 initialCountry: "ae",
        //                 preferredCountries: ['ae'],
        //                 autoPlaceholder: "polite",
        //                 showSelectedDialCode: true,
        //                 utilsScript: "./assets/plugins/intel-input/utils.js",
        //                 hiddenInput: () => ({ phone: `shipping_address[${shippingAddressCounter}][address_phone]` })
        //             });
        //             if (shipping.address_phone) {
        //                 itiShipping.setNumber(shipping.address_phone);
        //             }
        //             shippingPhoneInput.addEventListener("blur", validatePhoneInput);
        //             shippingPhoneInput.addEventListener("keyup", validatePhoneInput);
        //             itiShippingPhones.push({ id: shippingAddressCounter, iti: itiShipping });
        //         }

        //         const $countrySelect = $(`select[name="shipping_address[${shippingAddressCounter}][country]"]`);
        //         populateCountryDropdownForShipping($countrySelect, function() {
        //             $countrySelect.val(shipping.country || '');
        //         });
        //     });
        // }
    }

    $("#editCompanyForm").on("submit", function (e) {
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

        // $(".shipping-address-container").each(function () {
        //     const index = $(this).attr("id").split("-").pop();
        //     const itiShipping = itiShippingPhones.find(item => item.id === parseInt(index))?.iti;
        //     if (itiShipping) {
        //         formData.set(`shipping_address[${index}][address_phone]`, itiShipping.getNumber());
        //     }
        // });

        imgload.show();
        $.ajax({
            url: ApiPlugin + "/ecommerce/company/update/" + id,
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