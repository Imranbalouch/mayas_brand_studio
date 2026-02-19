document.title = "Dashboard | Edit Product";
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
                
                $("input[name=id]").val(data.id);
                $("input[name=slug]").val(data.slug);
                $("input[name=thumbnail_img]").val(data.thumbnail_img);
                $("input[name=images]").val(data.images);

                $("input[name=unit_price]").val(data.stocks[0].price);
                $("input[name=current_stock]").val(data.stocks[0].qty);
                $("input[name=sku]").val(data.stocks[0].sku);

                $("input[name=meta_title]").val(data.meta_title);
                $("textarea[name=meta_description]").val(data.meta_description);
                $("input[name=og_title]").val(data.og_title);
                $("textarea[name=og_description]").val(data.og_description);
                $("input[name=og_image]").val(data.og_image);
                $("input[name=x_title]").val(data.x_title);
                $("textarea[name=x_description]").val(data.x_description);
                $("input[name=x_image]").val(data.x_image);
    
                const attributesDropdown = $('#choice_attributes'); 
                attributesDropdown.empty();
                $("#choice_attributes").html(data.html_attribute);
                attributesDropdown.selectpicker('refresh');


                $("#customer_choice_options").html(data.html);
                $('.attribute_choice').selectpicker();
                                

                // Dynamically append translation tabs and content
                let translations = data.translations;
                
                    translations.forEach((translation, index) => {
                    let isActive = index === 0 ? "active show" : "";
                    let isSelected = index === 0 ? "true" : "false";
                    let tabId = `nav-lang-${translation.language_code}`;
                    let tabButton = `
                         <li class="nav-item" role="presentation">
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
                                                 Name (${translation.language_code.toUpperCase()})
                                             </label>
                                             <input type="text" class="form-control mb-2" id="name_${translation.language_code}"
                                                 name="name_${translation.language_code}" value="${translation.name}" required>
                                        </div>
                                        
                                        <div class="col-lg-12">
                                            <label for="short_description_${translation.language_code}" class="form-label"> Short Description (${translation.language_code.toUpperCase()}) </label>
                                            <textarea name="short_description_${translation.language_code}" class="form-control mb-2" row="10"
                                                > ${translation.short_description} </textarea>
                                        </div>

                                        <div class="col-lg-12">
                                            <label for="description_${translation.language_code}" class="form-label"> Description (${translation.language_code.toUpperCase()}) </label>
                                            <textarea name="description_${translation.language_code}" class="form-control mb-2" row="10"
                                                > ${translation.description} </textarea>
                                        </div>

                                     </div>
                                 </div>
                             </div>
                         </div>`;

                        $('.tab_content_1').append(tabContent);
                       
                    
                });
    
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
                                // parentDropdown.append(
                                //     `<option value="${category.id}" ${category.id === data.category_id ? 'selected' : ''}>${category.name}</option>`
                                // );
                                let spaces = '-'.repeat(category.level); // Adding spaces based on the category level
                                    parentDropdown.append(
                                        `<option value="${category.id}" ${
                                            category.id === data.category_id ? "selected" : ""
                                        }>${spaces} ${category.name}</option>`
                                    );
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
    
                        } else {
                            console.error('Unexpected response:', response);
                        }
                    },
                    error: function (xhr, status, error) {
                        console.error('Error fetching brands:', status, error);
                    }
                });

                $.ajax({
                    url: ApiPlugin + '/ecommerce/warehouse/get_active_warehouse_locations', // API endpoint
                    type: "GET", // HTTP method
                    contentType: "application/json", // Request content type
                    dataType: "json", // Expected response data type
                    beforeSend: function (request) {
                        request.setRequestHeader("Authorization", 'Bearer ' + strkey); // Add Authorization header
                    },
                    success: function (response) {
                        if (response.status_code === 200) { // Check if the response is successful
                            const locationDropdown = $('#warehouse_location_id'); // Get the dropdown element
                            locationDropdown.empty(); // Clear existing options
                
                            // Check if response.data is an array and not empty
                            if (Array.isArray(response.data) && response.data.length > 0) { 
                                var options = response.data.map(function(item) { 
                                    return `<option value="${item.id}" ${location.id === data.warehouse_location_id ? 'selected' : ''} data-subtext="${item.warehouse_name}">${item.location_name}</option>`;
                                }).join(''); // Join the array into a single string
                
                                locationDropdown.html(options); // Add the options to the dropdown
                                locationDropdown.selectpicker('refresh'); // Refresh the Bootstrap Selectpicker if you're using it
                            } else {
                                console.warn('No data found in the response.'); // Log a warning if no data is found
                            }
                        } else {
                            console.error('Unexpected response:', response); // Log an error for unexpected responses
                        }
                    },
                    error: function (xhr, status, error) {
                        console.error('Error fetching warehouse locations:', status, error); // Log AJAX errors
                    }
                }); 
                //filemanagerImagepreview();

                 //console.log(data.warehouse_location_id);
                
                setTimeout(function () {
                    const location_id = $('#warehouse_location_id').val(); 
                    if (location_id!='') { 
                        update_sku();
                    }
                   
                  }, 1000);
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
$("#editProductForm").on("submit", function (e) {

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





// Product Stock JS Start From Here

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
                            <select class="form-control aiz-selectpicker attribute_choice" data-live-search="true" name="choice_options_${i}[]" multiple required>
                                ${obj} <!-- Populate the options dynamically -->
                            </select>
                        </div>
                    </div>`);
                }
                
                $('.attribute_choice').selectpicker();
                
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

// $('input[name="unit_price"]').on('keyup', function() {
//     update_sku();
// });

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
$('#warehouse_location_id').on('change', function() {  
    update_sku();
});
function update_sku() {
    $.ajax({
        type: "POST",
        url: ApiPlugin + "/ecommerce/product/sku-combination-edit",
        data: $('#editProductForm').serialize(),
        beforeSend: function (xhr) {
            xhr.setRequestHeader("Authorization", "Bearer " + strkey);
            xhr.setRequestHeader("menu-uuid", menu_id);
           //  imgload.show();
        },
        success: function (data) { 
           // Update the HTML content of the SKU combination table
           $('#sku_combination').html(data);
           filemanagerImagepreview();
           imgload.hide();

           // Hide or show the div based on data content length
           if (data.trim().length > 1) {
               $('#show-hide-div').hide();
           } else {
               $('#show-hide-div').show();
               get_siple_product_inventory();
           }
           buttonDisable('hide');
        }
    }); 
}
function get_siple_product_inventory(){
   // setTimeout(function () {
        const location_id = $('#warehouse_location_id').val(); 
        const producy_id = $('input[name="id"]').val(); 
        if (location_id!='' && producy_id!='') {  
           $.ajax({
            type: "POST",
            url: ApiPlugin + "/ecommerce/product/sku-simple-edit",
            data: $('#editProductForm').serialize(),
                beforeSend: function (xhr) {
                    xhr.setRequestHeader("Authorization", "Bearer " + strkey);
                    xhr.setRequestHeader("menu-uuid", menu_id);
                    imgload.show();
                },
                success: function (data) {   
                   $('input[name="unit_price"]').val(data.data.price);
                   $('input[name="current_stock"]').val(data.data.qty);
                   $('input[name="sku"]').val(data.data.sku);
                imgload.hide(); 
                buttonDisable('hide');
                }
            }); 
        }
       
     // }, 1000);
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