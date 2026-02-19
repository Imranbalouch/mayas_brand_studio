document.title = "Dashboard | Edit Vat";
$(document).ready(function () {
    const urlParams = new URLSearchParams(window.location.search);
    const id = urlParams.get('id');


        $.ajax({
            url: ApiPlugin + '/ecommerce/edit_vat/' + id,
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
                    populateCarrierData(response.data);
                } else {
                    showToast(response.message || 'Vat not found', 'Error', 'error');
                }
            },
            error: function (xhr) {
                imgload.hide();
                const error = xhr.responseJSON;
                if (error && error.status_code === 404) {
                    showToast(error.message, 'Error', 'error');
                } else {
                    showToast('Internal Server Error.', 'Error', 'error');
                }
            }
        });

    // Populate form with supplier data
    function populateCarrierData(data) {
        $("input[name='name']").val(data.name);
        $("input[name='rate']").val(data.rate || '');
    }


    // Form submission handler
    $("#VatEditForm").on("submit", function (e) {
        e.preventDefault();

        // Collect form data
        const name = this.querySelector("[name='name']").value.trim();
        const rate = parseFloat(this.querySelector("[name='rate']").value.trim() || 0);

        // Client-side validation
        if (!name) {
            showToast("Name can't be blank", "Error", "error");
            return;
        }

        if (!rate) {
            showToast("Rate can't be blank", "Error", "error");
            return;
        }

        if (rate < 0) {
            showToast("Rate can't be less than 0", "Error", "error");
            return;
        }

        if(rate > 100) {
            showToast("Rate can't be greater than 100", "Error", "error");
            return;
        }

        // Prepare data for API
        const submitData = {
            name,
            rate,
        };

        // Send AJAX request to update carriers
        $.ajax({
            url: ApiPlugin + '/ecommerce/update_vat/' + id,
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
                    showToast(response.message, "Success", "success", '?P=vat&M=' + menu_id);
                } else {
                    showToast(response.message || "Failed to update vat", "Error", "error");
                }
            },
            error: function (xhr) {
                imgload.hide();
                let errorMessage = "An error occurred while updating the vat.";
                if (xhr.status === 422) {
                    const response = xhr.responseJSON;
                    errorMessage = response.errors || "Validation errors occurred.";
                } else if (xhr.status === 404) {
                    errorMessage = xhr.responseJSON.message || "vat not found.";
                } else {
                    console.error("AJAX Error:", xhr.status, xhr.responseText);
                }
                showToast(errorMessage, "Error", "error");
            }
        });
    });
});