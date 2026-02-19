document.title = "Dashboard | Edit Product Attribute";
var urlParams = new URLSearchParams(window.location.search);
var id = urlParams.get('id');
if (id) {
    
    $.ajax({
        url: ApiPlugin + "/ecommerce/product/edit_product/" + id,
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

                $("input[name=slug]").val(data.slug);
                $("input[name=unit_price]").val(data.unit_price);
                $("input[name=thumbnail_img]").val(data.thumbnail_img);
    
            
                // Populate Category and Brand dropdowns and set selected values
                $.ajax({
                    url: ApiPlugin + '/ecommerce/category/get_active_categories',
                    type: "GET",
                    contentType: "application/json",
                    dataType: "json",
                    beforeSend: function (request) {
                        request.setRequestHeader("Authorization", 'Bearer ' + strkey);
                    },
                    success: function (response) {
                        if (response.status_code === 200) {
                            const parentDropdown = $('#category_id');
                            parentDropdown.empty(); // Clear existing options
    
                            // Loop through the categories and add them to the dropdown
                            response.data.forEach(category => {
                                parentDropdown.append(
                                    `<option value="${category.id}" ${category.id === data.category_id ? 'selected' : ''}>${category.name}</option>`
                                );
                            });
    
                            parentDropdown.select2({
                                placeholder: "Select a category",
                                allowClear: true,
                                width: '100%'
                            });
                        } else {
                            console.error('Unexpected response:', response);
                        }
                    },
                    error: function (xhr, status, error) {
                        console.error('Error fetching categories:', status, error);
                    }
                });
    
                $.ajax({
                    url: ApiForm + '/brand/get_active_brands',
                    type: "GET",
                    contentType: "application/json",
                    dataType: "json",
                    beforeSend: function (request) {
                        request.setRequestHeader("Authorization", 'Bearer ' + strkey);
                    },
                    success: function (response) {
                        if (response.status_code === 200) {
                            const brandDropdown = $('#brand_id');
                            brandDropdown.empty(); // Clear existing options
    
                            // Loop through the brands and add them to the dropdown
                            response.data.forEach(brand => {
                                brandDropdown.append(
                                    `<option value="${brand.id}" ${brand.id === data.brand_id ? 'selected' : ''}>${brand.brand}</option>`
                                );
                            });
    
                            brandDropdown.select2({
                                placeholder: "Select a brand",
                                allowClear: true,
                                width: '100%'
                            });
                        } else {
                            console.error('Unexpected response:', response);
                        }
                    },
                    error: function (xhr, status, error) {
                        console.error('Error fetching brands:', status, error);
                    }
                });
    
                filemanagerImagepreview();
            }
        },
        error: function (error) {
            error = error.responseJSON;
            if (error.status_code == 404) {
                showToast(error.message, 'Error', 'error', '?P=product&M=' + menu_id + '&id=' + id);
            } else {
                showToast('Internal Server Error.', 'Error', 'error', '?P=product&M=' + menu_id + '&id=' + id);
            }
        }
    });

    
}

/// edit form submit
$("#editBrandForm").on("submit", function (e) {

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
        url: ApiPlugin + "/ecommerce/product/update_product",
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
                showToast(title, 'Success', 'success', '?P=product&M=' + menu_id + '&id=' + id);
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