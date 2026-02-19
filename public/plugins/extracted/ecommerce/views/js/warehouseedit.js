document.title = "Dashboard | Edit Warehouse";
var urlParams = new URLSearchParams(window.location.search);
var id = urlParams.get('id');

if (id) {
    $.ajax({
        url: ApiPlugin + "/ecommerce/warehouse/edit_warehouse/" + id,
        type: "GET", // Changed from POST to GET
        dataType: "json",
        beforeSend: function (xhr) {
            xhr.setRequestHeader("Authorization", "Bearer " + strkey);
            xhr.setRequestHeader("menu-uuid", menu_id);
        },
        success: function (response) {
            imgload.hide();
            console.log(response); // Debugging

            if (response.status_code == 200) {
                let data = response.data;

                console.log("Setting warehouse name to:", data.warehouse_name);
                $("#warehouse_name").val(data.warehouse_name);
                $("#prefix").val(data.prefix);


                // Dynamically append translation tabs and content
                let translations = data.translations;
                translations.forEach((translation, index) => {
                    let isActive = index === 0 ? "active show" : "";
                    let isSelected = index === 0 ? "true" : "false";

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
                                                 name="name_${translation.language_code}" value="${translation.warehouse_name}" required>
                                         </div>
                                         <div class="mb-6 fv-row col-md-12">
                                             <label class="form-label required" for="prefix_${translation.language_code}">
                                                 Prefix
                                             </label>
                                             <input type="text" class="form-control mb-2" id="prefix_${translation.language_code}"
                                                 name="prefix_${translation.language_code}" value="${translation.prefix}" required>
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
                console.log("Error: Data not found.");
            } else {
                console.log("Internal Server Error.");
            }
        }
    });
}


/// edit form submit
$("#editWarehouseForm").on("submit", function (e) {

    // var logoFile = document.getElementById('logoFile').value;

    // if (!logoFile) {
    //     alert('Please select Logo fields');
    //     return
    // }

    e.preventDefault();
    // Ensure css_file selects the right input elements

    var formData = new FormData(this);

    //console.log(formData);
    $.ajax({
        url: ApiPlugin + "/ecommerce/warehouse/update_warehouse",
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
            console.log(response);  // Check the response here
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