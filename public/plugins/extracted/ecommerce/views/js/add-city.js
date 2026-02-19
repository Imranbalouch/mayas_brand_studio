document.title="Dashboard | Add City";
$(document).ready(function () {

    $("#countrySelect").select2({
        width: "100%",
        allowClear: false,
        placeholder: "Select Country",
        minimumResultsForSearch: Infinity
    });
    get_active_countries();
    // Form submission handler
    $("#CityAddForm").on("submit", function (e) {
        e.preventDefault();

        // Collect form data
        const name = this.querySelector("[name='name']").value.trim();
        const price = this.querySelector("[name='price']").value.trim() || "";
        const min_price = this.querySelector("[name='min_price']").value.trim() || "";
        const vat_percent = this.querySelector("[name='vat_percent']").value.trim() || "";
        const country_uuid = $("#countrySelect").val();

        // Client-side validation
        if (!name) {
            showToast("Name can't be blank", "Error", "error");
            return;
        }
        
        if (!price) {
            showToast("Price can't be blank", "Error", "error");
            return;
        }
        if (!min_price) {
            showToast("Min Price can't be blank", "Error", "error");
            return;
        }
        if (!country_uuid) {
            showToast("Country can't be blank", "Error", "error");
            return;
        }

        // Prepare data for API
        const submitData = {
            name,
            price,
            min_price,
            vat_percent,
            country_uuid,
        };

        // Send AJAX request to store supplier
        $.ajax({
            url: ApiPlugin + '/ecommerce/cities/store',
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
                    showToast(response.message, "Success", "success", '?P=cities&M=' + menu_id);
                } else {
                    showToast(response.message || "Failed to add city", "Error", "error");
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

function get_active_countries() {
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
        const countryDropdown = $('#countrySelect');
        countryDropdown.empty();
        countryDropdown.append('<option value="">Select Country</option>');
        response.data.forEach(country => {
          countryDropdown.append(
            `<option value="${country.uuid}">${country.name}</option>`
          );
        });
        // Trigger Select2 to update
        countryDropdown.trigger('change');
      } else {
        console.error('Unexpected response:', response);
      }
    },
    error: function (xhr, status, error) {
      console.error('Error fetching warehouses:', status, error);
    }
  });
}