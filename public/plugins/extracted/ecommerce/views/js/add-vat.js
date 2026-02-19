document.title="Dashboard | Add Vat";
$(document).ready(function () {
    // Form submission handler
    $("#VatAddForm").on("submit", function (e) {
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

        if(rate < 0) {
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
            status: 1
        };

        // Send AJAX request to store supplier
        $.ajax({
            url: ApiPlugin + '/ecommerce/add_vat',
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
                    showToast(response.message || "Failed to add vat", "Error", "error");
                }
            },
            error: function (xhr) {
                imgload.hide();
                let errorMessage = "An error occurred while saving the vat.";
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