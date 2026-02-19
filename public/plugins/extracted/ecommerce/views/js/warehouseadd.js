document.title = "Dashboard | Add Warehouse";
$("#addWarehouseForm").on("submit", function (e) {

    // var logoFile = document.getElementById('logoFile').value;

    // Check if both fields are empty
    // if (!logoFile) {
    //     alert('Please select Logo fields');
    //     // showToast("Something went wrong!", 'Error', 'error');
    //     return
    // }

    e.preventDefault();

    let formData = new FormData(this);
    $.ajax({
        url: ApiPlugin + "/ecommerce/warehouse/add_warehouse",
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
                showToast(title, 'Success', 'success', "?P=warehouse&M="+menu_id);
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