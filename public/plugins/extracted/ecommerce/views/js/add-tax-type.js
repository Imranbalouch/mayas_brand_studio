document.title="Dashboard | Add Tax Type";
$(document).ready(function () {
    // Form submission handler
    $("#TaxTypeAddForm").on("submit", function (e) {
        e.preventDefault();

        // Collect form data
        const name = this.querySelector("[name='name']").value.trim();
        const value = this.querySelector("[name='value']").value.trim() || "";

        // Client-side validation
        if (!name) {
            showToast("Name can't be blank", "Error", "error");
            return;
        }

        // Prepare data for API
        const submitData = {
            name,
            value,
            status: 1
        };

        // Send AJAX request to store supplier
        $.ajax({
            url: ApiPlugin + '/ecommerce/add_tax_type',
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
                    showToast(response.message, "Success", "success", '?P=tax-type&M=' + menu_id);
                } else {
                    showToast(response.message || "Failed to add tax type", "Error", "error");
                }
            },
            error: function (xhr) {
                imgload.hide();
                let errorMessage = "An error occurred while saving the tax type.";
                if (xhr.status === 422) {
                    const response = xhr.responseJSON;
                    errorMessage = response.errors || "Validation errors occurred.";
                } else {
                    console.error("AJAX Error:", xhr.status, xhr.responseText);
                }
                showToast(errorMessage, "Error", "error");
            }
        });
    });
});