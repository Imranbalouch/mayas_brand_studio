document.title = "Dashboard | Edit Warehouse Value";
var urlParams = new URLSearchParams(window.location.search);
var id = urlParams.get('id');
if (id) {
    $.ajax({
        url: ApiPlugin + "/ecommerce/warehouse/edit_specific_warehouse_value/" + id,
        type: "GET",
        dataType: "json",
        beforeSend: function (xhr) {
            xhr.setRequestHeader("Authorization", "Bearer " + strkey);
            xhr.setRequestHeader("menu-uuid", menu_id);
            imgload.show();
        },
        success: function (response) {
            imgload.hide();
            if (response.status_code == 200) {
                let data = response.data;
                $("input[name=location_name]").val(data.location_name);
                $("input[name=location_address]").val(data.location_address);
                $("input[name=capacity]").val(data.capacity);
                $("input[name=current_stock_level]").val(data.current_stock_level);
                
                // Load dropdowns with selected values
                loadDropdowns(data.manager_uuid, data.warehouse_uuid);


                // Dynamically append translation tabs and content
                let translations = data.translations;
                translations.forEach((translation, index) => {
                    // Determine if this is the first tab
                    
                    let isActive = index === 0 ? "active show" : "";
                    let isSelected = index === 0 ? "true" : "false";
                
                    // Create new tab
                    let tabId = `nav-lang-${translation.language_code}`;
                    let tabButton = `
                         <li class="nav-item d-none" role="presentation">
                             <button class="nav-link ${isActive}" id="${tabId}-tab" data-bs-toggle="tab"
                                 data-bs-target="#${tabId}" type="button" role="tab" aria-controls="${tabId}"
                                 aria-selected="${isSelected}">
                                 <span>
                                     <img class="lang_image" src="${AssetsPath + translation.flag}" width="20" alt="">
                                 </span>
                                 ${translation.language_name}
                             </button>
                         </li>`;
                    $('#myTab').append(tabButton);
                
                    // Create corresponding tab content
                    let tabContent = `
                         <div class="tab-pane fade ${isActive}" id="${tabId}" role="tabpanel" aria-labelledby="${tabId}-tab">
                             <div class="row gap-lg-0 gap-5">
                                 <div class="col-lg-12 form-input mt-3">
                                     <div class="row">
                
                                         <div class="mb-6 fv-row col-md-12">
                                             <label class="form-label required" for="name_${translation.language_code}">
                                                 Name
                                             </label>
                                             <input type="text" class="form-control mb-2" id="name_${translation.language_code}"
                                                 name="name_${translation.language_code}" value="${translation.location_name}" required>
                                         </div>
                                         <div class="mb-6 fv-row col-md-12">
                                             <label class="form-label required" for="location_address_${translation.language_code}">
                                                 Location Address
                                             </label>
                                             <input type="text" class="form-control mb-2" id="location_address_${translation.language_code}"
                                                 name="location_address_${translation.language_code}" value="${translation.location_address}" required>
                                         </div>
                
                                     </div>
                                 </div>
                             </div>
                         </div>`;
                    $('.tab_content_1').append(tabContent);
                });
                

                filemanagerImagepreview();

            }
        },
        error: function (error) {
            error = error.responseJSON;
            if (error.status_code == 404) {
                showToast(error.message, 'Error', 'error', '?P=brand&M=' + menu_id + '&id=' + id);
            } else {
                showToast('Internal Server Error.', 'Error', 'error', '?P=brand&M=' + menu_id + '&id=' + id);
            }
        }
    });
}

/// edit form submit
$("#editWarehouseValueForm").on("submit", function (e) {
    

    // var logoFile = document.getElementById('logoFile').value;

    // if (!logoFile) {
    //     alert('Please select Logo fields');
    //     return
    // }

    e.preventDefault();
    // Ensure css_file selects the right input elements

    var formData = new FormData(this);

    //console.log(formData);
    imgload.show();
    $.ajax({
        url: ApiPlugin + "/ecommerce/warehouse/update_warehouse_value",
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
                showToast(title, 'Success', 'success', '?P=warehouse&M=' + menu_id + '&id=' + id);
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
                    messages.forEach(function (message) {
                        errorMessages += `<li>${message}</li>`; // Append each error message
                    });
                });
                let htmlError = `<ul>${errorMessages}</ul>`;
                showToast(htmlError, 'Error', 'error');
            } else {
                // Handle other errors
                showToast(getTranslation('somthing_went_worng'), 'Error', 'error');
            }
        }
    });
});

    // Load Managers
    function loadDropdowns(selectedManagerUuid, selectedWarehouseUuid) {
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
                    
                    // Set the selected manager if provided
                    if (selectedManagerUuid) {
                        managerSelect.val(selectedManagerUuid);
                        // Load the manager's contact info
                        loadManagerContactInfo(selectedManagerUuid);
                    }
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
                    warehouseSelect.html('<option value="">Select Warehouse</option>');
                    warehouses.forEach(warehouse => {
                        warehouseSelect.append(`<option value="${warehouse.uuid}">${warehouse.warehouse_name}</option>`);
                    });
                    
                    // Set the selected warehouse if provided
                    if (selectedWarehouseUuid) {
                        warehouseSelect.val(selectedWarehouseUuid);
                    }
                }
            }
        });
    }
    

// New function to load manager contact info
function loadManagerContactInfo(managerUuid) {
    if (!managerUuid) {
        // Clear the fields if no manager is selected
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
        }
    });
}

$(document).ready(function() {
    loadDropdowns();
    
    // Add change event handler for manager selection
    $('#manager_uuid').on('change', function() {
        const selectedManagerUuid = $(this).val();
        loadManagerContactInfo(selectedManagerUuid);
    });
});



$(".ui-sortable-css").sortable({
    update: function (event, ui) {
        updateSorting(this);
    }
});
function updateSorting(e) {
    var sorting = [];
    let fileItem = $(e).find(".preview-card");
    fileItem.each(function () {
        sorting.push($(this).data("previewimage"));
    });
    $(e).parent(".form-group").find("input[name='css_file']").val(sorting.join(","));
}
$(".ui-sortable-js").sortable({
    update: function (event, ui) {
        updateSortingJs(this);
    }
});
function updateSortingJs(e) {
    var sorting = [];
    let fileItem = $(e).find(".preview-card");
    fileItem.each(function () {
        sorting.push($(this).data("previewimage"));
    });
    $(e).parent(".form-group").find("input[name='js_file']").val(sorting.join(","));
}