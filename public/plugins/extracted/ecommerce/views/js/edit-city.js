document.title = "Dashboard | Edit City";
$(document).ready(function () {
    const urlParams = new URLSearchParams(window.location.search);
    const id = urlParams.get('id');


        $.ajax({
            url: ApiPlugin + '/ecommerce/cities/edit/' + id,
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
                    populateCitiesData(response.data);
                } else {
                    showToast(response.message || 'City not found', 'Error', 'error', '?P=cities&M=' + menu_id);
                }
            },
            error: function (xhr) {
                imgload.hide();
                const error = xhr.responseJSON;
                if (error && error.status_code === 404) {
                    showToast(error.message, 'Error', 'error', '?P=cities&M=' + menu_id);
                } else {
                    showToast('Internal Server Error.', 'Error', 'error', '?P=cities&M=' + menu_id);
                }
            }
        });

    // Populate form with supplier data
    function populateCitiesData(data) {

        if (data) {
            get_active_countries(data.country_uuid);
        }
        $("input[name='name']").val(data.name);
        $("input[name='price']").val(data.price || 0);
        $("input[name='min_price']").val(data.min_price || 0);
        $("input[name='vat_percent']").val(data.vat_percent || 0);
    }


    // Form submission handler
    $("#CityEditForm").on("submit", function (e) {
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

        // Send AJAX request to update cities
        $.ajax({
            url: ApiPlugin + '/ecommerce/cities/update/' + id,
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
                    showToast(response.message || "Failed to update cities", "Error", "error");
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

function get_active_countries(selectedCountry) {
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
        const parentCountryDropdown = $('#countrySelect');
        parentCountryDropdown.empty();
        
        // Add placeholder option
        parentCountryDropdown.append('<option value="">Select Country</option>');
        
        response.data.forEach(country => {
          parentCountryDropdown.append(
            `<option value="${country.uuid}">${country.name}</option>`
          );
        });

        if (selectedCountry) {
          parentCountryDropdown.val(selectedCountry).trigger('change');
        }
      
      } else {
        console.error('Unexpected response:', response);
      }
    },
    error: function (xhr, status, error) {
      console.error('Error fetching countries:', status, error);
    }
  });
}