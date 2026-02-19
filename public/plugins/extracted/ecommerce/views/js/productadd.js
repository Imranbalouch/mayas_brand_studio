document.title = "Dashboard | Add Product";
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
                    // parentDropdown.append(
                    //     `<option value="${category.id}">${category.name}</option>`
                    // );
                    let spaces = '-'.repeat(category.level); // Adding spaces based on the category level
                    parentDropdown.append(
                        `<option value="${category.id}">${spaces} ${category.name}</option>`
                    );
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


            } else {
                console.error('Unexpected response:', response);
            }
        },
        error: function (xhr, status, error) {
            console.error('Error fetching data:', status, error);
        }
    });


    $.ajax({
        url: ApiPlugin + '/ecommerce/attribute/get_active_attributes',
        type: "GET",
        contentType: "application/json",
        dataType: "json",
        beforeSend: function (request) {
            request.setRequestHeader("Authorization", 'Bearer ' + strkey);
        },
        success: function (response) {
            if (response.status_code === 200) {
                const attributesDropdown = $('#choice_attributes');
                attributesDropdown.empty(); // Clear existing options
                
                // Loop through the attributes and add them to the dropdown
                response.data.forEach(attribute => {
                   // console.log(attribute);
                    attributesDropdown.append(
                        `<option value="${attribute.id}">${attribute.attribute_name}</option>`
                    );
                });
    
                // Refresh the selectpicker to synchronize it with the new options
                attributesDropdown.selectpicker('refresh');
            } else {
                console.error('Unexpected response:', response);
            }
        },
        error: function (xhr, status, error) {
            console.error('Error fetching attributes:', status, error);
        }
    });



    $.ajax({
        url: ApiPlugin + '/ecommerce/warehouse/get_active_warehouse_locations',
        type: "GET",
        contentType: "application/json",
        dataType: "json",
        beforeSend: function (request) {
            request.setRequestHeader("Authorization", 'Bearer ' + strkey);
        },
        success: function (response) {
            if (response.status_code === 200) {
                const locationDropdown = $('#warehouse_location_id');
                locationDropdown.empty(); // Clear existing options

                // Loop through the brands and add them to the dropdown
                response.data.forEach(location => {
                    locationDropdown.append(
                        `<option value="${location.id}" data-subtext='${location.warehouse_name}'>${location.location_name} <small>(${location.warehouse_name})</small></option>`
                    );
                }); 

            } else {
                console.error('Unexpected response:', response);
            }
        },
        error: function (xhr, status, error) {
            console.error('Error fetching brands:', status, error);
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

// Product Stock Js Start From Here

function add_more_customer_choice_option(i, name) {
    $.ajax({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        type: "POST",
        url: ApiPlugin + "/ecommerce/product/add-more-choice-option",
        data: {
            attribute_id: i
        },
        beforeSend: function (xhr) {
            xhr.setRequestHeader("Authorization", "Bearer " + strkey);
            xhr.setRequestHeader("menu-uuid", menu_id);
            imgload.show();
            buttonDisable('show');
        }, 
        success: function (data) {
            try {
                var obj = JSON.parse(data); // Parse the JSON response
                
                // Show the customer options section
                $('.customer_option').removeClass('d-none');
                
                // Check if the choice option already exists
                if ($('#customer_choice_options .form-group.row[data-attr="' + name + '"]').length === 0) {
                    // Append new choice option
                    $('#customer_choice_options').append(`
                    <div class="form-group row mb-3" data-attr="${name}">
                        <div class="col-md-3">
                            <input type="hidden" name="choice_no[]" value="${i}">
                            <input type="text" class="form-control" name="choice[]" value="${name}" placeholder="{{ translate('Choice Title') }}" readonly>
                        </div>
                        <div class="col-md-8">
                            <select class="form-control selectpicker attribute_choice" data-live-search="true" name="choice_options_${i}[]" multiple    >
                                ${obj}
                            </select>
                        </div>
                    </div>`);

                    $('.attribute_choice').selectpicker();
                    
                }
                
               
                
            } catch (error) {
                console.error('Error parsing response:', error);
            }

            // Re-enable the button
            buttonDisable('hide');
        },
        error: function (xhr, status, error) {
            console.error('Error during AJAX request:', error);
            buttonDisable('hide'); // Ensure button is re-enabled even if there’s an error
        }
    });
}

$(document).on("change", ".attribute_choice",function() {
    update_sku();
});

$('#colors').on('change', function() {
    update_sku();
});

$('input[name="unit_price"]').on('keyup', function() {
    update_sku();
});

$('input[name="name"]').on('keyup', function() {
    update_sku();
});

function delete_row(em){
    $(em).closest('.form-group row').remove();
    update_sku();
}

function delete_variant(em){
    $(em).closest('.variant').remove();
}

function update_sku() {
    $.ajax({
        type: "POST",
        url: ApiPlugin + "/ecommerce/product/sku-combination",
        data: $('#addBannerForm').serialize(),
        beforeSend: function (xhr) {
            xhr.setRequestHeader("Authorization", "Bearer " + strkey);
            xhr.setRequestHeader("menu-uuid", menu_id);
            imgload.show();
        },
        success: function (data) {
            // Update the HTML content of the SKU combination table
            $('#sku_combination').html(data);
            imgload.show();

            // Hide or show the div based on data content length
            if (data.trim().length > 1) {
                $('#show-hide-div').hide();
            } else {
                $('#show-hide-div').show();
            }

            buttonDisable('hide');
        }
    });
}


$('#choice_attributes').on('change', function() {
    $('#customer_choice_options').html(null);
    $.each($("#choice_attributes option:selected"), function(){
        add_more_customer_choice_option($(this).val(), $(this).text());
    });
    update_sku();
});
function buttonDisable(status){
    if (status == "show") {
        $("#img_load").removeClass('d-none');
        $("body").addClass('body-load');
        $(".add-new-product-button button").prop('disabled', true);
    }else{
        $("#img_load").addClass('d-none');
        $("body").removeClass('body-load');
        $(".add-new-product-button button").prop('disabled', false);
    }
}