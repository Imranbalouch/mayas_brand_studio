document.title="Dashboard | Add Payment Term";
$(document).ready(function () {
    // Form submission handler
    $("#PaymentTermAddForm").on("submit", function (e) {
        e.preventDefault();

        // Collect form data
        const name = this.querySelector("[name='name']").value.trim();
        const description = this.querySelector("[name='description']").value.trim() || "";
        const url = this.querySelector("[name='url']").value.trim() || "";

        // Client-side validation
        if (!name) {
            showToast("Name can't be blank", "Error", "error");
            return;
        }

        // Prepare data for API
        const submitData = {
            name,
            description,
            url,
            status: 1
        };

        // Send AJAX request to store supplier
        $.ajax({
            url: ApiPlugin + '/ecommerce/payment-terms/store',
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
                    showToast(response.message, "Success", "success", '?P=payment-terms&M=' + menu_id);
                } else {
                    showToast(response.message || "Failed to add payment term", "Error", "error");
                }
            },
            error: function(xhr, status, err) {
                imgload.hide();
                
                if (xhr.status === 422) {
                    let errors = xhr.responseJSON.errors;
                    errors = JSON.parse(errors);
                    let errorMessages = "";
                    
                    $.each(errors, function (field, messages) {
                        messages.forEach(function(message) {
                            errorMessages += `<li>${message}</li>`;
                        });
                    });
                    let htmlError = `<ul>${errorMessages}</ul>`;
                    showToast(htmlError, 'Error', 'error');
                }else{
                    showToast(xhr.responseJSON.message, 'Error', 'error');
                }        
            }
        });
    });
});