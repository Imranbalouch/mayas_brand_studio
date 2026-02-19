document.title = "Dashboard | Add Country";
$(document).ready(function () {
    // Form submission handler
    $("#CountryAddForm").on("submit", function (e) {
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

        // Send AJAX request to store supplier
        $.ajax({
            url: ApiPlugin + '/ecommerce/add_country',
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
                if (response.status_code === 200 || response.status_code === 201) {
                    showToast(response.message, "Success", "success", '?P=countries&M=' + menu_id);
                } else {
                    showToast(response.message || "Failed to add country", "Error", "error");
                }
            },
            error: function (xhr) {
                imgload.hide();
                let errorMessage = "An error occurred while saving the country.";

                if (xhr.status === 422) {
                    const response = xhr.responseJSON;
                    if (response && response.errors) {
                        // If errors is an object, extract its values
                        if (typeof response.errors === 'object') {
                            errorMessage = Object.values(response.errors).flat().join('<br>');
                        } else {
                            errorMessage = response.errors;
                        }
                    }
                } else if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                } else {
                    console.error("AJAX Error:", xhr.status, xhr.responseText);
                }

                showToast(errorMessage, "Error", "error");
            }
        });
    });
});
