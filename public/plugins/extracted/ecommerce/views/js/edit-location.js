document.title="Dashboard | Edit Location";
$(document).ready(function() {
  // Initialize Select2 for dropdowns
  $("#countryRegion").select2({
    width: "100%",
    allowClear: false,
    dropdownParent: $('#addLocation'),
    minimumResultsForSearch: Infinity
  });

  $("#selectLocation").select2({
    placeholder: "Select",
    width: "100%",
    allowClear: false,
    dropdownParent: $('#deactivateLocation'),
    minimumResultsForSearch: Infinity
  });

  $("#warehouseSelect").select2({
    width: "100%",
    allowClear: false,
    dropdownParent: $('#addLocationName'),
    placeholder: "Select Warehouse",
    minimumResultsForSearch: Infinity
  });

  $("#managerSelect").select2({
    width: "100%",
    allowClear: false,
    dropdownParent: $('#addLocationName'),
    placeholder: "Select Warehouse",
    minimumResultsForSearch: Infinity
  });

  // Initialize intlTelInput
  let iti;
  var input = document.querySelector("#phone");
  if (input) {
    iti = window.intlTelInput(input, {
      initialCountry: "ae",
      preferredCountries: ['ae'],
      autoPlaceholder: "polite",
      showSelectedDialCode: true,
      utilsScript: "./assets/plugins/intel-input/utils.js",
      hiddenInput: () => ({ phone: "full_phone" })
    });
    input.addEventListener("input", function () {
      if (input.value.trim()) {
        if (iti.isValidNumber()) {
          input.parentElement.parentElement.classList.remove("error");
          input.parentElement.parentElement.querySelector(".error-txt").innerHTML = "";
        } else {
          input.parentElement.parentElement.classList.add("error");
          input.parentElement.parentElement.querySelector(".error-txt").innerHTML = "Invalid Number";
        }
      }
      if (input.value === "") {
        input.parentElement.parentElement.classList.remove("error");
        input.parentElement.parentElement.querySelector(".error-txt").innerHTML = "";
      }
    });
  }
  // intlTelInput JS End

  // Toggle Fulfillment List visibility
  $("#squar-checkbox").on("change", function(){
    if($(this).prop("checked")){
      $("#FulfillmentList").removeClass("d-none");
    } else {
      $("#FulfillmentList").addClass("d-none");
    }
  });

  // Fetch active countries
  get_active_countries();

  // Fetch and populate location data if editing
  var urlParams = new URLSearchParams(window.location.search);
  var uuid = urlParams.get('uuid');
  
  if (uuid) {
    fetchLocationData(uuid);
  } else {
    // If no UUID, still load warehouses
    get_active_warehouses();
    get_active_managers();
  }

  // Handle location name update
  $("#addLocationName .btn-primary").on("click", function() {
    let locationName = $("#addLocationName input[name='name']").val();
    if (locationName) {
      $(".add-location-item .desc").first().text(locationName);
      $("h3 g-t").text(locationName);
    }
  });

  // Handle address update
  $("#addLocation .btn-primary").on("click", function() {
    let address = $("#addLocation input[name='address']").val();
    let apartment = $("#addLocation input[name=apartment]").val();
    let city = $("#addLocation input[name='city']").val();
    let postalCode = $("#addLocation input[name='postalcode']").eq(0).val();
    let phone = $("#phone").val();
    
    let addressDisplay = `${address}, ${apartment}, ${city}, ${postalCode}, ${phone}`;
    $(".add-location-item .desc").eq(1).text(addressDisplay);
    $(".add-location-item .desc").eq(2).text(phone);
  });

  // Handle form submission for updating location
  $(".col-12.mt-5 .btn-primary").on("click", function(e) {
    e.preventDefault();
    
    // Validate required fields
    const locationName = $("#addLocationName input[name='name']").val();
    if (!locationName) {
      $('#addLocationName').modal('show');
      showToast('Please enter a location name', 'Error', 'error');
      return;
    }

    const warehouseUuid = $("#warehouseSelect").val();
    const managerUuid = $("#managerSelect").val();
    if (!warehouseUuid) {
      $('#addLocationName').modal('show');
      showToast('Please select a warehouse', 'Error', 'error');
      return;
    }
    if (!managerUuid) {
      $('#addLocationName').modal('show');
      showToast('Please select a manager', 'Error', 'error');
      return;
    }

    const address = $("#addLocation input[name='address']").val();
    if (!address) {
      $('#addLocation').modal('show');
      showToast('Please enter an address', 'Error', 'error');
      return;
    }

    updateLocation(uuid);
  });

  // Handle inventory button click
  $(".inventory").on("click", function(e) {
    e.preventDefault();
    let page = 'inventory';
    window.location.assign('?P=' + page + '&M=' + menu_id);
  });

  $("#confirmDeactivate").on("click", function () {
    var urlParams = new URLSearchParams(window.location.search);
    var uuid = urlParams.get("uuid");
    if (uuid) {
      updateWarehouseStatus(uuid, 0); // Deactivate (status = 0)
    }
  });

  // Handle activate button click
  $("#activateLocation").on("click", function () {
    var urlParams = new URLSearchParams(window.location.search);
    var uuid = urlParams.get("uuid");
    if (uuid) {
      updateWarehouseStatus(uuid, 1); // Activate (status = 1)
    }
  });

  // Handle delete button click
  $("#deleteLocation").on("click", function(e) {
    e.preventDefault();
    const urlParams = new URLSearchParams(window.location.search);
    const uuid = urlParams.get("uuid");
    
    if (!uuid) {
      showToast("UUID not found in URL", "Error", "error");
      return;
    }

    Swal.fire({
      title: "Are you sure?",
      text: "This will permanently delete the location!",
      icon: "warning",
      showCancelButton: true,
      reverseButtons: true, 
      confirmButtonText: "Delete",
      cancelButtonText: "Cancel"
    }).then((result) => {
      if (result.isConfirmed) {
        $.ajax({
          url: ApiPlugin + "/ecommerce/warehouse/delete_warehouse_value/" + uuid,
          type: "DELETE",
          contentType: "application/json",
          dataType: "json",
          beforeSend: function(xhr) {
            console.log("Sending DELETE request for UUID:", uuid);
            xhr.setRequestHeader("Authorization", "Bearer " + strkey);
            xhr.setRequestHeader("menu-uuid", menu_id);
          },
          success: function(response) {
            if (response.status_code === 200) {
              showToast("Location deleted successfully", "Success", "success");
              setTimeout(() => {
                window.location.assign("?P=locations&M=" + menu_id);
              }, 1000);
            } else {
              showToast(response.message || "Deletion failed", "Error", "error");
            }
          },
          error: function(xhr, status, error) {
            console.error("Delete error:", xhr.responseText);
            showToast("Failed to delete location: " + xhr.responseText, "Error", "error");
          }
        });
      }
    });
  });
});

function get_active_warehouses() {
  $.ajax({
    url: ApiPlugin + '/ecommerce/warehouse/get_active_warehouses',
    type: "GET",
    contentType: "application/json",
    dataType: "json",
    beforeSend: function (request) {
      request.setRequestHeader("Authorization", 'Bearer ' + strkey);
    },
    success: function (response) {
      if (response.status_code === 200) {
        const warehouseDropdown = $('#warehouseSelect');
        warehouseDropdown.empty();
        warehouseDropdown.append('<option value="">Select Warehouse</option>');
        response.data.forEach(warehouse => {
          warehouseDropdown.append(
            `<option value="${warehouse.uuid}">${warehouse.warehouse_name}</option>`
          );
        });
        // Pre-select warehouse if available
        if (window.warehouseUuid) {
          warehouseDropdown.val(window.warehouseUuid).trigger('change');
        }
      } else {
        console.error('Unexpected response:', response);
      }
    },
    error: function (xhr, status, error) {
      console.error('Error fetching warehouses:', status, error);
      showToast('Failed to load warehouses', 'Error', 'error');
    }
  });
}

function get_active_managers() {
  $.ajax({
    url: ApiForm + '/get_active_users',
    type: "GET",
    contentType: "application/json",
    dataType: "json",
    beforeSend: function (request) {
      request.setRequestHeader("Authorization", 'Bearer ' + strkey);
    },
    success: function (response) {
      if (response.status_code === 200) {
        const managerDropdown = $('#managerSelect');
        managerDropdown.empty();
        managerDropdown.append('<option value="">Select Manager</option>');
        response.data.forEach(manager => {
          managerDropdown.append(
            `<option value="${manager.uuid}">${manager.name}</option>`
          );
        });
        // Pre-select manager if available
        if (window.managerUuid) {
          managerDropdown.val(window.managerUuid).trigger('change');
        }
      } else {
        console.error('Unexpected response:', response);
      }
    },
    error: function (xhr, status, error) {
      console.error('Error fetching warehouses:', status, error);
      showToast('Failed to load warehouses', 'Error', 'error');
    }
  });
}

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
        const parentCountryDropdown = $('#countryRegion');
        parentCountryDropdown.empty();
        
        // Add placeholder option
        parentCountryDropdown.append('<option value="">Select Country</option>');
        
        response.data.forEach(country => {
          parentCountryDropdown.append(
            `<option value="${country.uuid}">${country.name}</option>`
          );
        });
        
        // If there's a country value to select (from fetchLocationData)
        if (window.selectedCountryValue) {
          parentCountryDropdown.val(window.selectedCountryValue).trigger('change');
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

function updateWarehouseStatus(uuid, status) {
  $.ajax({
    url: ApiPlugin + "/ecommerce/warehouse/update_warehousevalue_Status/" + uuid,
    type: "POST",
    contentType: "application/json",
    dataType: "json",
    data: JSON.stringify({ status: status }),
    beforeSend: function (xhr) {
      xhr.setRequestHeader("Authorization", "Bearer " + strkey);
      xhr.setRequestHeader("menu-uuid", menu_id);
      imgload.show();
    },
    success: function (response) {
      imgload.hide();
      if (response.status_code === 200) {
        showToast(response.message || (status === 1 ? "Location activated successfully" : "Location deactivated successfully"), "Success", "success");
        // Update UI based on status
        updateUIAfterStatusChange(status);
        // Close modal
        $("#simpledeactivateLocation").modal("hide");
      } else {
        showToast(response.message || "Something went wrong!", "Error", "error");
      }
    },
    error: function (xhr, status, error) {
      imgload.hide();
      console.error("Status update error:", error, xhr.responseText);
      showToast("Failed to update location status. Please try again.", "Error", "error");
    },
  });
}



// Function to update UI after status change
function updateUIAfterStatusChange(status) {
  if (status === 1) {
    // Activated
    $("h3 .pos-pro").show();
    $(".btn-activate").addClass("d-none");
    $(".btn-delete").addClass("d-none");
    $(".btn-deactivate").removeClass("d-none");
  } else {
    // Deactivated
    $("h3 .pos-pro").hide();
    $(".btn-activate").removeClass("d-none");
    $(".btn-delete").removeClass("d-none");
    $(".btn-deactivate").addClass("d-none");
  }
}

function fetchLocationData(uuid) {
  $.ajax({
    url: ApiPlugin + "/ecommerce/warehouse/edit_specific_warehouse_value/" + uuid,
    type: "GET",
    contentType: "application/json",
    dataType: "json",
    beforeSend: function (xhr) {
      xhr.setRequestHeader("Authorization", "Bearer " + strkey);
      xhr.setRequestHeader("menu-uuid", menu_id);
      imgload.show();
    },
    success: function (response) {
      imgload.hide();
      if (response.status_code == 200) {
        console.log("Fetched location data:", response);

        const data = response.data;

        window.selectedCountryValue = data.country;
        window.warehouseUuid = data.warehouse_uuid;
        window.managerUuid = data.manager_uuid;

        // Load countries and warehouses
        get_active_countries();
        get_active_warehouses();
        get_active_managers();

        // Update header with location name
        $(".location-name").text(data.location_name);

        // Update UI based on status
        updateUIAfterStatusChange(data.status);

        // Populate location name in card
        $(".add-location-item .desc").first().text(data.location_name);

        // Populate location name modal
        $("#addLocationName input[name='name']").val(data.location_name);

        // Populate address info in card
        let addressParts = [];
        if (data.location_address) addressParts.push(data.location_address);
        if (data.apartment) addressParts.push(data.apartment);
        if (data.city) addressParts.push(data.city);
        if (data.phone) addressParts.push(data.phone);
        if (data.postal_code) addressParts.push(data.postal_code);

        let addressDisplay = addressParts.join(", ");
        $(".add-location-item .desc").eq(1).text(addressDisplay);

        // Populate phone in card
        $(".add-location-item .desc").eq(2).text(data.phone || "");

        // Populate address modal fields
        $("#addLocation input[name='address']").val(data.location_address);
        $("#addLocation input[name='apartment']").val(data.apartment);
        $("#addLocation input[name='city']").val(data.city);
        $("#addLocation input[name='phone']").val(data.phone);
        $("#addLocation input[name='postalcode']").eq(0).val(data.postal_code);

        // Set phone number
        if (data.phone) {
          $("#phone").val(data.phone);
          if (iti) {
            iti.setNumber(data.phone);
          }
        }

        // Set country
        setTimeout(function () {
          if (data.country) {
            $("#countryRegion").val(data.country).trigger("change");
          }
        }, 1000);
      }
    },
    error: function (xhr, status, error) {
      imgload.hide();
      console.error("Error fetching location data:", error);
      showToast("Failed to load location data. Please try again.", "Error", "error");
    },
  });
}

function updateLocation(uuid) {
  // Get updated values from the form
  let locationName = $("#addLocationName input[name='name']").val();
  let address = $("#addLocation input[name='address']").val();
  let apartment = $("#addLocation input[name='apartment']").val();
  let city = $("#addLocation input[name='city']").val();
  let postalCode = $("#addLocation input[name='postalcode']").eq(0).val();
  let phone = $("#phone").val();
  let country = $("#countryRegion").val();
  let warehouseUuid = $("#warehouseSelect").val();
  let managerUuid = $("#managerSelect").val();
  
  let formData = new FormData();
  formData.append('location_name', locationName);
  formData.append('location_address', address);
  formData.append('apartment', apartment);
  formData.append('city', city);
  formData.append('postal_code', postalCode);
  formData.append('phone', phone);
  formData.append('country', country);
  formData.append('warehouse_uuid', warehouseUuid);
  formData.append('manager_uuid', managerUuid);

  console.log("Submitting data:", {
    location_name: locationName,
    location_address: address,
    apartment: apartment,
    city: city,
    postal_code: postalCode,
    phone: phone,
    country: country,
    warehouse_uuid: warehouseUuid
  });

  $.ajax({
    url: ApiPlugin + "/ecommerce/warehouse/update_warehouse_value",
    type: "POST",
    data: formData,
    contentType: false,
    processData: false,
    beforeSend: function (xhr) {
      xhr.setRequestHeader("Authorization", "Bearer " + strkey);
      xhr.setRequestHeader("menu-uuid", menu_id);
      xhr.setRequestHeader("uuid", uuid);
      imgload.show();
    },
    success: function (response) {
      imgload.hide();
      if (response.status_code === 200) {
        showToast(response.message || "Location updated successfully", 'Success', 'success');
        // Redirect after successful update
        setTimeout(() => {
          window.location.assign('?P=locations&M=' + menu_id);
        }, 1000);
      } else {
        showToast(response.message || "Something went wrong!", 'Error', 'error');
      }
    },
    error: function (xhr, status, error) {
      imgload.hide();
      console.error("Update error:", error, xhr.responseText);
      
      if (xhr.status === 422) {
        let errors = xhr.responseJSON.errors;
        let errorMessages = "";
        $.each(errors, function (field, messages) {
          messages.forEach(function (message) {
            errorMessages += `<li>${message}</li>`;
          });
        });
        showToast(`<ul>${errorMessages}</ul>`, 'Error', 'error');
      } else {
        showToast("Failed to update location. Please try again.", 'Error', 'error');
      }
    }
  });
}