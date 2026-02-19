document.title = "Dashboard | Edit Carrier";
$(document).ready(function () {
    const urlParams = new URLSearchParams(window.location.search);
    const id = urlParams.get('id');


        $.ajax({
            url: ApiPlugin + '/ecommerce/carriers/edit/' + id,
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
                    showToast(response.message || 'Carrier not found', 'Error', 'error', '?P=carriers&M=' + menu_id);
                }
            },
            error: function (xhr) {
                imgload.hide();
                const error = xhr.responseJSON;
                if (error && error.status_code === 404) {
                    showToast(error.message, 'Error', 'error', '?P=carriers&M=' + menu_id);
                } else {
                    showToast('Internal Server Error.', 'Error', 'error', '?P=carriers&M=' + menu_id);
                }
            }
        });

    // Populate form with supplier data
    function populateCarrierData(data) {
        $("input[name='name']").val(data.name);
        $("input[name='description']").val(data.description || '');
        $("input[name='url']").val(data.url || '');
    }


    // Form submission handler
    $("#CarrierEditForm").on("submit", function (e) {
        e.preventDefault();

        // Collect form data
        const name = this.querySelector("[name='name']").value.trim();
        const description = this.querySelector("[name='description']").value.trim() || "";
        const url = this.querySelector("[name='url']").value.trim() || "";

        // Client-side validation
        if (!name) {
            showToast("Company can't be blank", "Error", "error");
            return;
        }

        // Prepare data for API
        const submitData = {
            name,
            description,
            url,
        };

        // Send AJAX request to update carriers
        $.ajax({
            url: ApiPlugin + '/ecommerce/carriers/update/' + id,
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
                    showToast(response.message, "Success", "success", '?P=carriers&M=' + menu_id);
                } else {
                    showToast(response.message || "Failed to update carrier", "Error", "error");
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