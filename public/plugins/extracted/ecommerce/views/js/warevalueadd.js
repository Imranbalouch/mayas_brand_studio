document.title = "Dashboard | Add Warehouse Values";
$("#addWarehouseForm").on("submit", function (e) {

    // var logoFile = document.getElementById('logoFile').value;

    // Check if both fields are empty
    // if (!logoFile) {
    //     alert('Please select Logo fields');
    //     // showToast("Something went wrong!", 'Error', 'error');
    //     return
    // }

    var urlParams = new URLSearchParams(window.location.search);
    var id = urlParams.get('id');

    e.preventDefault(); 

    let formData = new FormData(this);
    $.ajax({
        url: ApiPlugin + "/ecommerce/warehouse/store_warehouse_value",
        type: "POST",
        data: formData,
        contentType: false,
        processData: false,
        beforeSend: function (xhr) {
            xhr.setRequestHeader("Authorization", "Bearer " + strkey);
            xhr.setRequestHeader("menu-uuid", menu_id);
            xhr.setRequestHeader("uuid", id);
            imgload.show();
        }, 
        success: function (response) {
            imgload.hide();
            if (response.status_code == 200) {
                let title = response.message;
                showToast(title, 'Success', 'success', "?P=warehousevaluesadd&M="+menu_id+"&id="+id);
            }
        },
        error: function (xhr, status, err) {
            imgload.hide();
            if (xhr.status === 422) {
                // Validation error
                let errors = xhr.responseJSON.errors;
                errors = JSON.parse(errors);
                let errorMessages = "";
                
                // Iterate over all errors and concatenate them
                $.each(errors, function (field, messages) {
                    messages.forEach(function(message) {
                        errorMessages += `<li>${message}</li>`; // Append each error message
                    });
                });
                let htmlError = `<ul>${errorMessages}</ul>`;
                showToast(htmlError, 'Error', 'error');
            }else{
                // Handle other errors
                showToast("Something went wrong!", 'Error', 'error');
            }
        }
    });
});


function loadDropdowns() {
    // Load Managers
    $.ajax({
        url: ApiPlugin + "/ecommerce/warehouse/get-managers",
        type: "GET",
        dataType: "json",
        beforeSend: function (xhr) {
            xhr.setRequestHeader("Authorization", "Bearer " + strkey);
            xhr.setRequestHeader("menu-uuid", menu_id);
        },
        success: function (response) {
            if (response.status_code == 200) {
                let managers = response.data;
                let managerSelect = $('#manager_uuid');
                // Add a default empty option
                managerSelect.html('<option value="">Select Manager</option>');
                managers.forEach(manager => {
                    managerSelect.append(`<option value="${manager.uuid}">${manager.full_name}</option>`);
                });
            }
        }
    });

    // Load Warehouses
    $.ajax({
        url: ApiPlugin + "/ecommerce/warehouse/get-warehouses",
        type: "GET",
        dataType: "json",
        beforeSend: function (xhr) {
            xhr.setRequestHeader("Authorization", "Bearer " + strkey);
            xhr.setRequestHeader("menu-uuid", menu_id);
        },
        success: function (response) {
            if (response.status_code == 200) {
                let warehouses = response.data;
                let warehouseSelect = $('#warehouse_uuid');
                // Add a default empty option
                warehouseSelect.html('<option value="">Select Warehouse</option>');
                warehouses.forEach(warehouse => {
                    warehouseSelect.append(`<option value="${warehouse.uuid}">${warehouse.warehouse_name}</option>`);
                });
            }
        }
    });
}

// Function to load manager contact info
function loadManagerContactInfo(managerUuid) {
    // Clear the fields if no manager is selected
    if (!managerUuid) {
        $('#manager_phone').val('');
        $('#manager_email').val('');
        return;
    }

    $.ajax({
        url: ApiPlugin + "/ecommerce/warehouse/get-manager-contact/" + managerUuid,
        type: "GET",
        dataType: "json",
        beforeSend: function (xhr) {
            xhr.setRequestHeader("Authorization", "Bearer " + strkey);
            xhr.setRequestHeader("menu-uuid", menu_id);
        },
        success: function (response) {
            if (response.status_code == 200) {
                $('#manager_phone').val(response.data.phone);
                $('#manager_email').val(response.data.email);
            }
        },
        error: function(xhr, status, err) {
            // Clear fields on error
            $('#manager_phone').val('');
            $('#manager_email').val('');
        }
    });
}

// Initialize form
$(document).ready(function() {
    // Load dropdowns
    loadDropdowns();
    
    // Add change event handler for manager selection
    $('#manager_uuid').on('change', function() {
        const selectedManagerUuid = $(this).val();
        loadManagerContactInfo(selectedManagerUuid);
    });
});

$(".ui-sortable-css").sortable({
    update: function(event, ui) {
        updateSorting(this);
    }
});
function updateSorting(e) {
    var sorting = [];
    let fileItem = $(e).find(".preview-card");
    fileItem.each(function() {
        sorting.push($(this).data("previewimage"));
    });
    console.log(sorting);
    console.log($(e).parent(".form-group"));
    
    $(e).parent(".form-group").find("input[name='css_file']").val(sorting.join(","));
}
$(".ui-sortable-js").sortable({
    update: function(event, ui) {
        updateSortingJs(this);
    }
});
function updateSortingJs(e) {
    var sorting = [];
    let fileItem = $(e).find(".preview-card");
    fileItem.each(function() {
        sorting.push($(this).data("previewimage"));
    });
    console.log(sorting);
    console.log($(e).parent(".form-group"));
    
    $(e).parent(".form-group").find("input[name='js_file']").val(sorting.join(","));
}