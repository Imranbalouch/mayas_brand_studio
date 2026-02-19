document.title = "Dashboard | Add Category";
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
                const parentDropdown = $('#parent_id');
                
                // Clear any existing options
                parentDropdown.empty();

                // Add the "No Parent" option
                parentDropdown.append(`<option value="0">No Parent</option>`);

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

                const categoryTree = buildTree(response.data);
                const orderedCategories = flattenTree(categoryTree);

                // Add options to dropdown with proper hierarchy
                orderedCategories.forEach(category => {
                    let dashes = '-'.repeat(category.level);
                    let prefix = category.level > 0 ? `${dashes} ` : '';
                    parentDropdown.append(
                        `<option value="${category.id}">${prefix}${category.name}</option>`
                    );
                });

                // Initialize Select2
                parentDropdown.select2({
                    placeholder: "Select a parent category",
                    allowClear: true,
                    width: '100%'
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


$("#addCategoryForm").on("submit", function (e) {

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

    let formData = new FormData(this);
    $.ajax({
        url: ApiPlugin + "/ecommerce/category/add_category",
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
                showToast(title, 'Success', 'success', "?P=category&M="+menu_id);
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


