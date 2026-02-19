document.title = "Dashboard | Edit Category";
let category_id  = '';
$(document).ready(async function () {
    var urlParams = new URLSearchParams(window.location.search);
    category_id = urlParams.get('id');

    await activeLanguages();
    if (category_id) {
        edit_category(category_id);
    }
});





async function edit_category(category_id, selectedLanguage = '') {
    selectedLanguage = selectedLanguage || $('#language').find('option:selected').val();
    try{
    await $.ajax({
        url: ApiPlugin + "/ecommerce/category/edit_category/" + category_id  + "?lang=" + selectedLanguage,
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
                $("input[name=name]").val(data.name); 
                $("input[name=slug]").val(data.slug);
                $("input[name=parent_id]").val(data.parent_id);
                $("input[name=order_level]").val(data.order_level);
                $("input[name=banner]").val(data.banner);
                $("input[name=icon]").val(data.icon);
                $("input[name=meta_title]").val(data.meta_title);
                $("textarea[name=meta_description]").val(data.meta_description);
                $("input[name=og_title]").val(data.og_title);
                $("textarea[name=og_description]").val(data.og_description);
                $("input[name=og_image]").val(data.og_image);
                $("input[name=x_title]").val(data.x_title);
                $("textarea[name=x_description]").val(data.x_description);
                $("input[name=x_image]").val(data.x_image);


                // Populate the parent_id dropdown
                const parentDropdown = $('#parent_id');
                parentDropdown.empty(); // Clear previous options
                parentDropdown.append(`<option value="0">No Parent</option>`); // Default "No Parent" option

                // Fetch all categories except the current category_uuid
                $.ajax({
                    url: ApiPlugin + '/ecommerce/category/get_active_categories',
                    type: "GET",
                    dataType: "json",
                    beforeSend: function (xhr) {
                        xhr.setRequestHeader("Authorization", "Bearer " + strkey);
                    },
                    success: function (categoryResponse) {
                        if (categoryResponse.status_code === 200) {
                            // Build a tree structure
                            const buildTree = (items, parentId = 0, level = 0) => {
                                const result = [];
                                const children = items.filter(item => item.parent_id == parentId);
                                
                                children.forEach(child => {
                                    result.push({
                                        ...child,
                                        level: level,
                                        children: buildTree(items, child.id, level + 1)
                                    });
                                });
                                
                                return result;
                            };

                            // Flatten tree in order with proper indentation
                            const flattenTree = (tree) => {
                                let result = [];
                                tree.forEach(node => {
                                    result.push(node);
                                    if (node.children.length > 0) {
                                        result = result.concat(flattenTree(node.children));
                                    }
                                });
                                return result;
                            };

                            const categoryTree = buildTree(categoryResponse.data);
                            const orderedCategories = flattenTree(categoryTree);

                            // Add options to dropdown
                            orderedCategories.forEach(category => {
                                // Exclude the current category_uuid
                                if (category.id !== data.id) {
                                    let dashes = '-'.repeat(category.level);
                                    let prefix = category.level > 0 ? `${dashes} ` : '';
                                    parentDropdown.append(
                                        `<option value="${category.id}" ${
                                            category.id == data.parent_id ? "selected" : ""
                                        }>${prefix}${category.name}</option>`
                                    );
                                }
                            });

                            // Initialize Select2 (optional)
                            parentDropdown.select2({
                                placeholder: "Select a parent category",
                                allowClear: true,
                                width: '100%'
                            });
                        } else {
                            console.error('Error fetching categories');
                        }
                    },
                    error: function (xhr, status, error) {
                        console.error('Error fetching categories:', status, error);
                    }
                });


                // Dynamically append translation tabs and content
                // let translations = data.translations;
                // translations.forEach((translation, index) => {
                //     // Determine if this is the first tab
                    
                //     let isActive = index === 0 ? "active show" : "";
                //     let isSelected = index === 0 ? "true" : "false";
                
                //     // Create new tab
                //     let tabId = `nav-lang-${translation.language_code}`;
                //     let tabButton = `
                //          <li class="nav-item" role="presentation">
                //              <button class="nav-link ${isActive} d-none" id="${tabId}-tab" data-bs-toggle="tab"
                //                  data-bs-target="#${tabId}" type="button" role="tab" aria-controls="${tabId}"
                //                  aria-selected="${isSelected}">
                //                  <span>
                //                      <img class="lang_image" src="${AssetsPath + translation.flag}" width="20" alt="">
                //                  </span>
                //                  ${translation.language_name}
                //              </button>
                //          </li>`;
                //     $('#myTab').append(tabButton);
                
                //     // Create corresponding tab content
                //     let tabContent = `
                //          <div class="tab-pane fade ${isActive}" id="${tabId}" role="tabpanel" aria-labelledby="${tabId}-tab">
                //              <div class="row gap-lg-0 gap-5">
                //                  <div class="col-lg-12 form-input mt-3">
                //                      <div class="row">
                
                //                          <div class="mb-6 fv-row col-md-12">
                //                              <label class="form-label required" for="name_${translation.language_code}">
                //                                  Name
                //                              </label>
                //                              <input type="text" class="form-control mb-2" id="name_${translation.language_code}"
                //                                  name="name_${translation.language_code}" value="${translation.name}" maxlength="150" required>
                //                          </div>
                
                //                      </div>
                //                  </div>
                //              </div>
                //          </div>`;
                //     $('.tab_content_1').append(tabContent);
                // });
                

                filemanagerImagepreview();
            }
        },
        error: function (error) {
            error = error.responseJSON;
            if (error.status_code == 404) {
                showToast(error.message, 'Error', 'error');
            } else {
                showToast('Internal Server Error.', 'Error', 'error');
            }
        }
    });
}catch(error){
        console.error('Error:', error);
}
  }


/// edit form submit
$("#editCategoryForm").on("submit", function (e) {

    // var bannerFile = document.getElementById('bannerFile').value;
    // var iconFile = document.getElementById('iconFile').value;

    // // Check if both fields are empty
    // if (!bannerFile) {
    //     alert('Please select Banner fields');
    //     // showToast("Something went wrong!", 'Error', 'error');
    //     return
    // }
    // if (!iconFile) {
    //     alert('Please select Icon fields');
    //     return
    // }

    e.preventDefault();
    // Ensure css_file selects the right input elements

    var formData = new FormData(this);

    //console.log(formData);
    imgload.show();
    $.ajax({
        url: ApiPlugin + "/ecommerce/category/update_category",
        type: "POST",
        data: formData,
        contentType: false,
        processData: false,
        beforeSend: function (xhr) {
            xhr.setRequestHeader("Authorization", "Bearer " + strkey);
            xhr.setRequestHeader("menu-uuid", menu_id);
            xhr.setRequestHeader("uuid", category_id);
            imgload.show();
        },
        success: function (response) {
            imgload.hide();
            if (response.status_code == 200) {
                let title = response.message;
                showToast(title, 'Success', 'success', '?P=category&M='+menu_id+'&id='+id);
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
    $(e).parent(".form-group").find("input[name='js_file']").val(sorting.join(","));
}
function activeLanguages() {
    return $.ajax({
        url: ApiForm + "/get_active_languages",
        type: "GET",
        dataType: "json",
        beforeSend: function (xhr) {
            xhr.setRequestHeader("Authorization", "Bearer " + strkey);
            imgload.show();
        }
    }).done(function (response) {
        imgload.hide();
        if (response.status_code == 200 && response.data) {
            let languageSelect = $('select[name="language"]');
            languageSelect.find('option:not(:first)').remove();
            const defaultLang = response.data.find(lang => lang.is_default == 1);
            response.data.forEach(lang => {
                const selected = defaultLang && lang.app_language_code === defaultLang.app_language_code ? 'selected' : '';
                languageSelect.append(`<option value="${lang.app_language_code}" ${selected}>${lang.name}</option>`);
            });
        } else {
            showToast('Failed to load languages', 'Error', 'error');
        }
    }).fail(function () {
        imgload.hide();
        showToast('Error fetching languages', 'Error', 'error');
    });
}

$("#language").change(function () {
    let selectedLanguage = $(this).find('option:selected').val();
    edit_category(category_id, selectedLanguage);
});