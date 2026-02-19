document.title = "Dashboard | Edit Supplier";
$(document).ready(function () {
    let iti;
    const urlParams = new URLSearchParams(window.location.search);
    const id = urlParams.get('id');

    // Initialize intlTelInput for phone number
    const phoneInput = document.querySelector("#phone");
    if (phoneInput) {
        iti = window.intlTelInput(phoneInput, {
            initialCountry: "ae",
            preferredCountries: ['ae'],
            autoPlaceholder: "polite",
            showSelectedDialCode: true,
            utilsScript: "./assets/plugins/intel-input/utils.js",
            hiddenInput: () => ({ phone: "phone_number" })
        });

        function validatePhoneInput() {
            if (phoneInput.value.trim()) {
                if (iti.isValidNumber()) {
                    phoneInput.parentElement.parentElement.classList.remove("error");
                    phoneInput.parentElement.parentElement.querySelector(".error-txt").innerHTML = "";
                    $("#SupplierEditForm").find("#btn_upd").prop("disabled", false);
                    $("input[name='phone_number']").val(iti.getNumber());
                    phoneInput.setAttribute("data-full-phone", iti.getNumber());
                } else {
                    phoneInput.parentElement.parentElement.classList.add("error");
                    phoneInput.parentElement.parentElement.querySelector(".error-txt").innerHTML = "Invalid Phone Number";
                    $("#SupplierEditForm").find("#btn_upd").prop("disabled", true);
                    $("input[name='phone_number']").val('');
                    phoneInput.removeAttribute("data-full-phone");
                }
            } else {
                phoneInput.parentElement.parentElement.classList.remove("error");
                phoneInput.parentElement.parentElement.querySelector(".error-txt").innerHTML = "";
                $("#SupplierEditForm").find("#btn_upd").prop("disabled", false);
                phoneInput.removeAttribute("data-full-phone");
            }
        }

        phoneInput.addEventListener("blur", validatePhoneInput);
        phoneInput.addEventListener("keyup", validatePhoneInput);
    }

    // Fetch active countries for dropdown
    function fetchActiveCountries() {
        $.ajax({
            url: ApiPlugin + '/ecommerce/get_active_countries',
            type: "GET",
            contentType: "application/json",
            dataType: "json",
            beforeSend: function (request) {
                request.setRequestHeader("Authorization", 'Bearer ' + strkey);
            },
            success: function (response) {
                if (response.status_code === 200) {
                    const parentDropdownCountry = $('#country_select');
                    parentDropdownCountry.empty();
                    parentDropdownCountry.append('<option value="" disabled>Select Country</option>');

                    response.data.forEach(country => {
                        parentDropdownCountry.append(
                            `<option value="${country.uuid}">${country.name}</option>`
                        );
                    });
                    

                    // Fetch supplier data after countries are loaded
                    if (id) {
                        fetchSupplierData();
                    }
                } else {
                    console.error('Unexpected response:', response);
                    showToast('Failed to load countries', 'Error', 'error');
                }
            },
            error: function (xhr, status, error) {
                console.error('Error fetching countries:', status, error);
                showToast('Error loading countries', 'Error', 'error');
            }
        });
    }

    // Fetch supplier data
    function fetchSupplierData() {
        $.ajax({
            url: ApiPlugin + '/ecommerce/supplier/edit/' + id,
            type: "GET",
            dataType: "json",
            beforeSend: function (xhr) {
                xhr.setRequestHeader("Authorization", "Bearer " + strkey);
                xhr.setRequestHeader("menu-uuid", menu_id);
                imgload.show();
            },
            success: function (response) {
                imgload.hide();
                if (response.status_code === 200) {
                    populateSupplierData(response.data);
                } else {
                    showToast(response.message || 'Supplier not found', 'Error', 'error', '?P=supplier&M=' + menu_id);
                }
            },
            error: function (xhr) {
                imgload.hide();
                const error = xhr.responseJSON;
                if (error && error.status_code === 404) {
                    showToast(error.message, 'Error', 'error', '?P=supplier&M=' + menu_id);
                } else {
                    showToast('Internal Server Error.', 'Error', 'error', '?P=supplier&M=' + menu_id);
                }
            }
        });
    }

    // Populate form with supplier data
    function populateSupplierData(data) {
        $("input[name='company']").val(data.company);
        $("select[name='country_id']").val(data.country_id);
        $("input[name='address']").val(data.address || '');
        $("input[name='apart_suite']").val(data.apart_suite || '');
        $("input[name='city']").val(data.city || '');
        $("input[name='postal_code']").val(data.postal_code || '');
        $("input[name='contact_name']").val(data.contact_name || '');
        $("input[name='email']").val(data.email || '');
        if (data.phone_number) {
            $("input[name='phone']").val(data.phone_number);
            iti.setNumber(data.phone_number);
        }
        $("#country_select").selectpicker("refresh");
    }

    // Call fetchActiveCountries on page load
    fetchActiveCountries();

    // Form submission handler
    $("#SupplierEditForm").on("submit", function (e) {
        e.preventDefault();

        // Collect form data
        const company = this.querySelector("[name='company']").value.trim();
        const country_id = this.querySelector("#country_select").value;
        const address = this.querySelector("[name='address']").value.trim() || "";
        const apart_suite = this.querySelector("[name='apart_suite']").value.trim() || "";
        const city = this.querySelector("[name='city']").value.trim() || "";
        const postal_code = this.querySelector("[name='postal_code']").value.trim() || "";
        const contact_name = this.querySelector("[name='contact_name']").value.trim() || "";
        const email = this.querySelector("[name='email']").value.trim() || "";
        const phone_number = iti ? iti.getNumber() : "";

        // Client-side validation
        if (!company) {
            showToast("Company can't be blank", "Error", "error");
            return;
        }

        if (!country_id) {
            showToast("Country can't be blank", "Error", "error");
            return;
        }

        if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            showToast("Invalid Email Format", "Error", "error");
            return;
        }

        if (phone_number && !iti.isValidNumber()) {
            showToast("Invalid Phone Number", "Error", "error");
            return;
        }

        // Prepare data for API
        const submitData = {
            company,
            country_id,
            address,
            apart_suite,
            city,
            postal_code,
            contact_name,
            email,
            phone_number,
            status: 1
        };

        // Send AJAX request to update supplier
        $.ajax({
            url: ApiPlugin + '/ecommerce/supplier/update/' + id,
            type: "POST",
            contentType: "application/json",
            dataType: "json",
            data: JSON.stringify(submitData),
            headers: {
                "Authorization": "Bearer " + strkey,
                "menu-uuid": menu_id
            },
            beforeSend: function () {
                imgload.show(); // Show loading indicator
            },
            success: function (response) {
                imgload.hide();
                if (response.status_code === 200) {
                    showToast(response.message, "Success", "success", '?P=supplier&M=' + menu_id);
                } else {
                    showToast(response.message || "Failed to update supplier", "Error", "error");
                }
            },
            error: function (xhr) {
                imgload.hide();
                let errorMessage = "An error occurred while updating the supplier.";
                if (xhr.status === 422) {
                    const response = xhr.responseJSON;
                    errorMessage = response.errors || "Validation errors occurred.";
                } else if (xhr.status === 404) {
                    errorMessage = xhr.responseJSON.message || "Supplier not found.";
                } else {
                    console.error("AJAX Error:", xhr.status, xhr.responseText);
                }
                showToast(errorMessage, "Error", "error");
            }
        });
    });

    // Clear error messages on input
    $("#SupplierEditForm input, #SupplierEditForm select").on("input change", function () {
        $(this).parent().removeClass("error");
        $(this).parent().find(".error-txt").html("");
    });
});