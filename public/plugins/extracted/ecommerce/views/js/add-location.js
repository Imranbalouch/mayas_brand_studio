document.title="Dashboard | Add Location";
$(document).ready(function(){
  $("#countryRegion").select2({
    width: "100%",
    allowClear: false,
    dropdownParent: $('#addLocation'),
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

  // intlTelInput JS
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

  // Handle location name update
  $("#addLocationName .btn-primary").on("click", function() {
    let locationName = $("#addLocationName input[name='name']").val();
    if (locationName) {
      $(".add-location-item .desc").first().text(locationName);
      // Optionally update a header if needed, similar to edit flow
      // $("h3 g-t").text(locationName);
    }
  });

  // Handle address update
  $("#addLocation .btn-primary").on("click", function() {
    let address = $("#addLocation input[name='address']").val();
    let apartment = $("#addLocation input[name='apartment']").val();
    let city = $("#addLocation  input[name='city']").val();
    let postalCode = $("#addLocation input[name='postalcode']").val();
    let phone = $("#phone").val();
    let country = $("#countryRegion").val();
    
    let addressDisplay = `${address}, ${apartment}, ${city}, ${postalCode}, ${phone}`;
    $(".add-location-item .desc").eq(1).text(addressDisplay);
    $(".add-location-item .desc").eq(2).text(phone);
  });

  get_active_countries();
  get_active_warehouses();
  get_active_managers();

  $('.save').on('click', function(e) {
    e.preventDefault();
    
    // Validate required fields
    const locationName = $('#addLocationName input[name="name"]').val();
    if (!locationName) {
      $('#addLocationName').modal('show');
      return;
    }
    const warehouseUuid = $('#warehouseSelect').val();
    const managerUuid = $('#managerSelect').val();
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
    
    const address = $('#addLocation input[name="address"]').val();
    if (!address) {
      $('#addLocation').modal('show');
      return;
    }
    
    // If all validations pass, create the warehouse
    createWarehouse();
  });

  $('.btn.border').on('click', function(e) {
    e.preventDefault();
    // Add functionality for activating the location if needed
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
        const parentCountryDropdown = $('#countryRegion');
        parentCountryDropdown.empty();
        parentCountryDropdown.append('<option value="">Select Country</option>');
        response.data.forEach(country => {
          parentCountryDropdown.append(
            `<option value="${country.uuid}">${country.name}</option>`
          );
        });
        // Remove selectpicker if not used; it seems inconsistent with Select2
        // parentCountryDropdown.selectpicker("refresh");
      } else {
        console.error('Unexpected response:', response);
      }
    },
    error: function (xhr, status, error) {
      console.error('Error fetching countries:', status, error);
    }
  });
}
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
        // Trigger Select2 to update
        warehouseDropdown.trigger('change');
      } else {
        console.error('Unexpected response:', response);
      }
    },
    error: function (xhr, status, error) {
      console.error('Error fetching warehouses:', status, error);
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
        const ManagerDropdown = $('#managerSelect');
        ManagerDropdown.empty();
        ManagerDropdown.append('<option value="">Select Manager</option>');
        response.data.forEach(manager => {
          ManagerDropdown.append(
            `<option value="${manager.uuid}">${manager.name}</option>`
          );
        });
        // Trigger Select2 to update
        ManagerDropdown.trigger('change');
      } else {
        console.error('Unexpected response:', response);
      }
    },
    error: function (xhr, status, error) {
      console.error('Error fetching warehouses:', status, error);
    }
  });
}

function createWarehouse() {
  // Get values from the modals
  const locationName = $('#addLocationName input[name="name"]').val();
  const countryRegion = $('#countryRegion').val();
  const address = $('#addLocation input[name="address"]').val();
  const apartment = $('#addLocation input[name="apartment"]').val();
  const city = $('#addLocation input[name="city"]').val();
  const postalCode = $('#addLocation input[name="postalcode"]').val();
  const phone = $('#phone').val();
  const warehouseUuid = $('#warehouseSelect').val(); 
  const managerUuid = $('#managerSelect').val(); 

  if (!warehouseUuid) {
    showToast('Please select a warehouse', 'Error', 'error');
    $('#addLocationName').modal('show');
    return;
  }

  if (!managerUuid) {
    showToast('Please select a manager', 'Error', 'error');
    $('#addLocationName').modal('show');
    return;
  }

  // Update the UI immediately
  $(".add-location-item .desc").first().text(locationName);
  let addressDisplay = `${address}, ${apartment}, ${city}, ${countryRegion}, ${postalCode}`;
  $(".add-location-item .desc").eq(1).text(addressDisplay);
  $(".add-location-item .desc").eq(2).text(phone);

  // Prepare the data object
  const formData = new FormData();
  formData.append('location_name', locationName);
  formData.append('location_address', address);
  formData.append('warehouse_uuid', warehouseUuid); 
  formData.append('manager_uuid', managerUuid); 
  formData.append('country', countryRegion);
  formData.append('apartment', apartment);
  formData.append('city', city);
  formData.append('postal_code', postalCode);
  formData.append('phone', phone);
  
  // Make the AJAX request
  $.ajax({
    url: ApiPlugin + '/ecommerce/warehouse/store_warehouse_value',
    type: "POST",
    data: formData,
    contentType: false,
    processData: false,
    beforeSend: function (xhr) {
      xhr.setRequestHeader("Authorization", "Bearer " + strkey);
      xhr.setRequestHeader("menu-uuid", menu_id);
      imgload.show();
    }, 
    success: function (response) {
      imgload.hide();
      if (response.status_code === 200) {
        // Show success message
        let title = response.message;
        showToast(title, 'Success', 'success', "?P=locations&M="+menu_id);
      } else {
        showToast(response.message || 'Failed to create warehouse', 'Error', 'error');
        $('.save').prop('disabled', false).text('Save');
      }
    },
    error: function (xhr, status, error) {
      imgload.hide();
      let errorMessage = 'An error occurred while creating the warehouse';
      if (xhr.responseJSON && xhr.responseJSON.message) {
        errorMessage = xhr.responseJSON.message;
      } else if (xhr.responseJSON && xhr.responseJSON.errors) {
        // Handle validation errors
        errorMessage = Object.values(xhr.responseJSON.errors).join('<br>');
      }
      showToast(errorMessage, 'Error', 'error');
      $('.save').prop('disabled', false).text('Save');
    }
  });
}