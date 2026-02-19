document.title = "Dashboard | Edit Country";
$(document).ready(function () {
    const urlParams = new URLSearchParams(window.location.search);
    const id = urlParams.get('id');
    
    $.ajax({
        url: ApiPlugin + '/ecommerce/edit_country/' + id,
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
                populateCountryData(response.data);
            } else {
                showToast(response.message || 'Country not found', 'Error', 'error');
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

    // Populate form with country data
    function populateCountryData(data) {
        $("input[name='name']").val(data.name);
        $("input[name='code']").val(data.code || '');
        $("input[name='image']").val(data.image || '');
        filemanagerImagepreview();
    }

    // Form submission handler (same as add but with update endpoint)
    $("#CountryEditForm").on("submit", function (e) {
        e.preventDefault();

        // Collect form data
        const name = this.querySelector("[name='name']").value.trim();
        const code = this.querySelector("[name='code']").value.trim() || "";
        const image = this.querySelector("[name='image']").value.trim() || "";

        // Client-side validation
        if (!name) {
            showToast("Name can't be blank", "Error", "error");
            return;
        }

        if (!code || code.length !== 2) {
            showToast("Code can't be blank or must be 2 characters", "Error", "error");
            return;
        }

        // Prepare data for API
        const submitData = {
            name,
            code,
            image,
            status: 1
        };

        // Send AJAX request to update country
        $.ajax({
            url: ApiPlugin + '/ecommerce/update_country',
            type: "POST",
            contentType: "application/json",
            dataType: "json",
            data: JSON.stringify(submitData),
            headers: {
                "Authorization": "Bearer " + strkey,
                "menu-uuid": menu_id,
                "uuid": id
            },
            beforeSend: function () {
                imgload.show();
            },
            success: function (response) {
                imgload.hide();
                if (response.status_code === 200) {
                    showToast(response.message, "Success", "success", '?P=countries&M=' + menu_id);
                } else {
                    showToast(response.message || "Failed to update country", "Error", "error");
                }
            },
            error: function (xhr) {
                imgload.hide();
                let errorMessage = "An error occurred while saving the country.";
                if (xhr.status === 422) {
                    const response = xhr.responseJSON;
                    if (response && response.errors) {
                        errorMessage = Object.values(response.errors).flat().join('<br>');
                    }
                } else if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                showToast(errorMessage, "Error", "error");
            }
        });
    });
});