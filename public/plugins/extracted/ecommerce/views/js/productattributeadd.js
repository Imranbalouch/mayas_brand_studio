document.title = "Dashboard | Add Product Attribute";
$(document).ready(function () {
   
    Onload();

});

function Onload() {
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

                // Clear any existing options
                parentDropdown.empty();

                // Loop through the data to add categories
                response.data.forEach(category => {
                    parentDropdown.append(
                        `<option value="${category.id}">${category.name}</option>`
                    );
                });

                parentDropdown.select2({
                    placeholder: "Select a category id",
                    allowClear: true,
                    width: '100%' // Adjust width if needed
                });

            } else {
                console.error('Unexpected response:', response);
            }
        },
        error: function (xhr, status, error) {
            console.error('Error fetching data:', status, error);
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
                const parentDropdown = $('#brand_id');

                // Clear any existing options
                parentDropdown.empty();

                // Loop through the data to add categories
                response.data.forEach(category => {
                    parentDropdown.append(
                        `<option value="${category.id}">${category.brand}</option>`
                    );
                });

                parentDropdown.select2({
                    placeholder: "Select a category id",
                    allowClear: true,
                    width: '100%' // Adjust width if needed
                });

            } else {
                console.error('Unexpected response:', response);
            }
        },
        error: function (xhr, status, error) {
            console.error('Error fetching data:', status, error);
        }
    });

}


$("#addBannerForm").on("submit", function (e) {

    var logoFile = document.getElementById('logoFile').value;

    // Check if both fields are empty
    // if (!logoFile) {
    //     alert('Please select Logo fields');
    //     // showToast("Something went wrong!", 'Error', 'error');
    //     return
    // }

    e.preventDefault(); 

    let formData = new FormData(this);
    $.ajax({
        url: ApiPlugin + "/ecommerce/product/add_product",
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
            if (response.status_code == 200) {
                let title = response.message;
                showToast(title, 'Success', 'success', "?P=product&M="+menu_id);
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