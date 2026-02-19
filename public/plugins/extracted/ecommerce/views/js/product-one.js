document.title = "Dashboard | Product";
let PageNo = 1;
let PageSize = 10;
let totalRecord = 0;
let PageLength = 0;
let Search = '';
let keyupTimer;
$(document).ready(function () {
    btnnew = $('#btn_new');
    btnnew.hide();
    btnnew.click(function (e) {
        e.preventDefault();
        let page = 'productadd';
        window.location.assign('?P='+page+'&M='+menu_id);
    });
    
    Onload();
});
function Onload() {
    $('#Table_View1').DataTable().clear().destroy();
    $('#buttons').html('');
    $.ajax({
        url: ApiPlugin + '/ecommerce/product/get_product',
        type: "Get",
        contentType: "application/json",
        dataType: "json",
        beforeSend: function (xhr) {
            xhr.setRequestHeader("Authorization", "Bearer " + strkey);
            xhr.setRequestHeader("menu-uuid", menu_id);
            imgload.show();
        },
        success: function (response) {

            if (response.status_code == 200) {
                let action_button = '';
                let switch_button = '';
                //New
                if (Boolean(response.permissions['add'])) {
                    btnnew.show();
                }

                //Update 
                if (Boolean(response.permissions["edit"])) {
                    action_button += `<a href='javascript:;' class='modify_btn btn-edit' data-toggle='tooltip' title='Edit'>${getTranslation('edit')}</a> | `;
                    // action_button += `<a href='javascript:;' class='modify_btn btn-edit-attribute' data-toggle='tooltip' title='Attributes'>${getTranslation('attributes')}</a> | `;
                }

                // //Delete
                if (Boolean(response.permissions["delete"])) {
                    action_button += `<a href='javascript:;' class='modify_btn btn-delete' data-toggle='tooltip' title='Delete'>${getTranslation('delete')}</a> `;
                }


                if (Boolean(response.permissions["update"])) {
                    switch_button = '';
                }else{
                    switch_button = 'disabled';
                }
                
                if (response["data"] != null) { 
                    // Ajax Data Settings
                    console.log(response["data"]);
                    
                    const columnsConfig = [ 
                        {
                            data: null, // No data source
                            render: function(data, type, row, meta) {
                                return meta.row + meta.settings._iDisplayStart + 1; // Serial number
                            },
                            title: '#', // AUTO Serial number Column
                        // orderable: false // Disable sorting on this column
                        },
                        { data: 'name',
                            render: function (data, type, row) {
                                if (action_button !== 'undefined' && action_button !== '') {
                                    console.log(data);
                                    
                                    nomankidiv=`<div class="d-flex gap-2 align-items-center product-desc-tb">
                                                    <div>
                                                        <img style="min-width: 48px;"
                                                            src="assets/images/products/product-1.png" width="48"
                                                            alt="image">
                                                    </div>
                                                    <p>Shirt</p>
                                                </div>`;
                                    return  nomankidiv
                                } else { return data }
                            } 
                        }, 
                        { data: 'name',
                            render: function (data, type, row) {
                                if (action_button !== 'undefined' && action_button !== '') {
                                    return `<span class="badge-success">Active</span>`;
                                } else { return data }
                            } 
                        }, 
                        { data: 'name',
                            render: function (data, type, row) {
                                if (action_button !== 'undefined' && action_button !== '') {
                                    return `<a class="product-variant" href="">1 in stock <span>for 2
                                                        variants</span></a>`;
                                } else { return data }
                            } 
                        }, 
                        { data: 'name',
                            render: function (data, type, row) {
                                if (action_button !== 'undefined' && action_button !== '') {
                                    return `<div class="product-dropdown dropdown">
                                                    <a class="dropdown-toggle custom-color" data-bs-toggle="dropdown"
                                                        aria-expanded="false">
                                                        2
                                                    </a>
                                                    <ul class="dropdown-menu">
                                                        <li>
                                                            <div class="dropdown-item">
                                                                <div class="table-dropdown-item">
                                                                    <div class="box-success"></div>
                                                                    <span>Online Store</span>
                                                                </div>
                                                                <div class="hr"></div>
                                                                <div class="table-dropdown-item">
                                                                    <div class="box-waring-outline"></div>
                                                                    <span>Point of Sales</span>
                                                                </div>
                                                                <div class="table-dropdown-item">
                                                                    <span>Channel needs attention. <a href="">View
                                                                            Channel</a></span>
                                                                </div>
                                                            </div>
                                                        </li>
                                                    </ul>
                                                </div>`;
                                } else { return data }
                            } 
                        }, 
                        { data: 'name',
                            render: function (data, type, row) {
                                if (action_button !== 'undefined' && action_button !== '') {
                                    return `<div class="product-dropdown dropdown">
                                                    <a class="dropdown-toggle custom-color" data-bs-toggle="dropdown"
                                                        aria-expanded="false">
                                                        2
                                                    </a>
                                                    <ul class="dropdown-menu">
                                                        <li>
                                                            <div class="dropdown-item">
                                                                <div class="table-dropdown-item">
                                                                    <div class="box-success"></div>
                                                                    <span>Online Store</span>
                                                                </div>
                                                                <div class="hr"></div>
                                                                <div class="table-dropdown-item">
                                                                    <div class="box-waring-outline"></div>
                                                                    <span>Point of Sales</span>
                                                                </div>
                                                                <div class="table-dropdown-item">
                                                                    <span>Channel needs attention. <a href="">View
                                                                            Channel</a></span>
                                                                </div>
                                                            </div>
                                                        </li>
                                                    </ul>
                                                </div>`;
                                } else { return data }
                            } 
                        }, 
                        { data: 'name',
                            render: function (data, type, row) {
                                if (action_button !== 'undefined' && action_button !== '') {
                                    return `<span class="product-variant">Shirts</span>`;
                                } else { return data }
                            } 
                        }, 
                        { data: 'name',
                            render: function (data, type, row) {
                                if (action_button !== 'undefined' && action_button !== '') {
                                    return `<span class="product-variant">1</span>`;
                                } else { return data }
                            } 
                        }, 
                        { data: 'name',
                            render: function (data, type, row) {
                                if (action_button !== 'undefined' && action_button !== '') {
                                    return `<span class="product-variant">My Store</span>`;
                                } else { return data }
                            } 
                        }, 
                        // { 
                        //     data: 'status', 
                        //     render: function (data, type, row) {
                        //         if (data == true) {
                        //             return `<label class="switch">
                        //                 <input type="checkbox" checked ${switch_button} class="switch-input" onchange="changestatus(this)" value="${row.uuid}" role="switch" id="switch1">
                        //                 <span class="switch-toggle-slider">
                        //                 <span class="switch-on"></span>
                        //                 <span class="switch-off"></span>
                        //                 </span> 
                        //             </label>`
                        //             } else { 
                        //             return `<label class="switch">
                        //             <input type="checkbox" ${switch_button} class="switch-input" onchange="changestatus(this)" value="${row.uuid}" role="switch" id="switch1">
                        //             <span class="switch-toggle-slider">
                        //             <span class="switch-on"></span>
                        //             <span class="switch-off"></span>
                        //             </span> 
                        //             </label>`;
                        //             }
                        //         }
                        // },
                        // { 
                        //     data: 'featured', 
                        //     render: function (data, type, row) {
                        //         if (data == true) {
                        //             return `<label class="switch">
                        //                 <input type="checkbox" checked ${switch_button} class="switch-input" onchange="changefeatured(this)" value="${row.uuid}" role="switch" id="switch1">
                        //                 <span class="switch-toggle-slider">
                        //                 <span class="switch-on"></span>
                        //                 <span class="switch-off"></span>
                        //                 </span> 
                        //             </label>`
                        //             } else { 
                        //             return `<label class="switch">
                        //             <input type="checkbox" ${switch_button} class="switch-input" onchange="changefeatured(this)" value="${row.uuid}" role="switch" id="switch1">
                        //             <span class="switch-toggle-slider">
                        //             <span class="switch-on"></span>
                        //             <span class="switch-off"></span>
                        //             </span> 
                        //             </label>`;
                        //             }
                        //         }
                        // }
                    ];
                    tableData= response["data"];
                    console.log(columnsConfig);
                    
                    makeDataTable('#Table_View1', tableData, columnsConfig, { 
                        showButtons: false, 
                    });
                    imgload.hide();
                }
            } else if (response.statusCode == 404) {
                imgload.hide();
                $("#Table_View").DataTable({
                    destroy: true,
                    retrieve: true,
                    ordering: false,
                    dom: "lrt",
                    bLengthChange: false,
                    oLanguage: {
                        sEmptyTable:
                            `<img class='dataTable-custom-empty-icon' src='/images/no-record-found.svg'><br>${tbNoFound}`,
                    },
                });
            }
            else {
                imgload.hide();
                showToast(response.message, 'warning', 'warning');
            }

        },
        error: function (xhr, status, err) {
            imgload.hide();
            if (xhr.status == 401) {
                showToast(err, 'error', 'error');
            }else{
                showToast('Error', 'error', 'error');
            }
        }
    })
    return true;
}


//edit theme
$('table').on('click', '.btn-edit', function (e) {
    e.preventDefault();
    $("#popup_title").html(getTranslation("modify_menu"));
    let currentRow = $(this).closest("tr");
    let data = $('#Table_View').DataTable().row(currentRow).data();
    let uuid = data['uuid'];
    imgload.show();
    let page = 'productedit';
    window.location.assign('?P='+page+'&M='+menu_id+'&id='+uuid);
});


// edit attribute
$('table').on('click', '.btn-edit-attribute', function (e) {
    e.preventDefault();
    $("#popup_title").html(getTranslation("modify_menu"));
    let currentRow = $(this).closest("tr");
    let data = $('#Table_View').DataTable().row(currentRow).data();
    let uuid = data['uuid'];
    imgload.show();
    let page = 'productattribute';
    window.location.assign('?P='+page+'&M='+menu_id+'&id='+uuid);
});


//edit theme
$('table').on('click', '.btn-edit', function (e) {
    e.preventDefault();
    $("#popup_title").html(getTranslation("modify_menu"));
    let currentRow = $(this).closest("tr");
    let data = $('#Table_View').DataTable().row(currentRow).data();
    let uuid = data['uuid'];
    imgload.show();
    let page = 'productedit';
    window.location.assign('?P='+page+'&M='+menu_id+'&id='+uuid);
});


//delete
$('table').on('click', '.btn-delete', function (e) {
    e.preventDefault();
    let currentRow = $(this).closest("tr");
    let data = $('#Table_View').DataTable().row(currentRow).data();
    let uuid = data['uuid'];
    Swal.fire({
        title: getTranslation('deleteConfirmMsg')+name,
        icon: 'warning',
        showCancelButton: true,
        cancelButtonText: getTranslation('cancel'),
        confirmButtonColor: '#15508A',
        cancelButtonColor: '#15508A',
        confirmButtonText:  getTranslation('delete'),
        showClass: {
            popup: 'animated fadeInDown faster'
        },
        hideClass: {
            popup: 'animated fadeOutUp faster'
        }
    }).then((result) => {
        if (result.value) {
            $.ajax({
                url: ApiPlugin + "/ecommerce/product/delete_product/"+uuid,
                type: "delete",
                data: {},
                beforeSend: function (xhr) {
                    xhr.setRequestHeader("Authorization", "Bearer " + strkey);
                    xhr.setRequestHeader("menu-uuid", menu_id);
                    imgload.show();
                },
                success: function (data) {
                    imgload.hide();
                    if (data.status_code == 200) {
                        showToast(data.message, 'Success', 'success', 'self');
                    }
                },
                error: function (xhr, status, err) {
                    imgload.hide();
                    showToast('Error', 'error', 'error');
                }
            })
        }
    });
});

function changestatus(el){
    if(el.checked){
        var status = 1;
    }
    else{
        var status = 0;
    }
    $.ajax({
        url: ApiPlugin + "/ecommerce/product/status/"+$(el).val(),
        type: "POST",
        data: {status:status},
        beforeSend: function (xhr) {
            xhr.setRequestHeader("Authorization", "Bearer " + strkey);
            xhr.setRequestHeader("menu-uuid", menu_id);
            imgload.show();
        },
        success: function (data) {
            imgload.hide();
            localStorage.setItem('theme_id', data.theme_id);
            showToast(data.message, 'Success', 'success', 'self');
        },
        error: function (xhr, status, err) {
            imgload.hide();
            showToast("Error", '', 'error' );
        }
    })
}


function changefeatured(el){
    if(el.checked){
        var featured = 1;
    }
    else{
        var featured = 0;
    }
    $.ajax({
        url: ApiPlugin + "/ecommerce/product/featured/"+$(el).val(),
        type: "POST",
        data: {featured:featured},
        beforeSend: function (xhr) {
            xhr.setRequestHeader("Authorization", "Bearer " + strkey);
            xhr.setRequestHeader("menu-uuid", menu_id);
            imgload.show();
        },
        success: function (data) {
            imgload.hide();
            localStorage.setItem('theme_id', data.theme_id);
            showToast(data.message, 'Success', 'success', 'self');
        },
        error: function (xhr, status, err) {
            imgload.hide();
            showToast("Error", '', 'error' );
        }
    })
}

// Noman Product JS Start
document.addEventListener("DOMContentLoaded", function () {
    const inputField = document.querySelector("#duplicateModalInput");
    const createButton = document.querySelector(".btn-primary.btn-modal-text");
    const currentCount = document.querySelector("#the-count #current");
    const maxLength = 40;

    // Pehle se button disable kar dete hain
    createButton.disabled = true;

    inputField.addEventListener("input", function () {
        const count = this.value.length;
        currentCount.innerHTML = count;

        // Button enable/disable logic
        createButton.disabled = count === 0;

        // Agar character limit exceed ho jaye, extra characters remove kar do
        if (count > maxLength) {
            this.value = this.value.substring(0, maxLength);
            currentCount.innerHTML = maxLength;
        }
    });

    inputField.addEventListener("keydown", function (e) {
        const count = this.value.length;

        // Agar character limit exceed ho jaye to naye characters ko allow na karein
        if (count >= maxLength && e.key !== "Backspace" && e.key !== "Delete" && !e.ctrlKey) {
            e.preventDefault();
            alert("Maximum 40 characters allowed!");
        }
    });
});

function addproductsell() {
    let container = document.getElementById("add_to_me");
    let widget = document.getElementById("sellThroughWidget");

    if (widget) {
        widget.remove(); // Remove widget if it exists
    } else {
        container.innerHTML += `
        <div id="sellThroughWidget" class="custom-widget-content p-0">
            <div class="row">
                <div class="col-lg-3 col-md-4 col-sm-6">
                    <a href="javascript:;" class="product-sell-throught-text">
                        <h4>Products by sell-through rate</h4>
                        <span>0% <svg viewBox="0 0 11 16" height="16" width="11" role="img" class="_SVG_186th_144" tabindex="-1"><title>No change</title><path d="M0.519531 1.79395H12.0039V0.249023H0.519531V1.79395Z" fill="#8a8a8a" transform="translate(0, 7)"></path></svg></span>
                    </a>
                </div>
                <div class="col-lg-3 col-md-4 col-sm-6">
                    <a href="javascript:;" class="product-sell-throught-text">
                        <h4>Products by sell-through rate</h4>
                        <span>0% <svg viewBox="0 0 11 16" height="16" width="11" role="img" class="_SVG_186th_144" tabindex="-1"><title>No change</title><path d="M0.519531 1.79395H12.0039V0.249023H0.519531V1.79395Z" fill="#8a8a8a" transform="translate(0, 7)"></path></svg></span>
                    </a>
                </div>
                <div class="col-lg-3 col-md-4 col-sm-6">
                    <a href="javascript:;" class="product-sell-throught-text">
                        <h4>Products by sell-through rate</h4>
                        <span>0% <svg viewBox="0 0 11 16" height="16" width="11" role="img" class="_SVG_186th_144" tabindex="-1"><title>No change</title><path d="M0.519531 1.79395H12.0039V0.249023H0.519531V1.79395Z" fill="#8a8a8a" transform="translate(0, 7)"></path></svg></span>
                    </a>
                </div>
            </div>
        </div>`;
    }
}


// alert();
/// ADD NEW RECORD END
// Noman Product JS End
document.addEventListener("DOMContentLoaded", function () {
    const inputField = document.querySelector("#duplicateModalInput");
    const createButton = document.querySelector(".btn-primary.btn-modal-text");
    const currentCount = document.querySelector("#the-count #current");
    const maxLength = 40;

    // Pehle se button disable kar dete hain
    createButton.disabled = true;

    inputField.addEventListener("input", function () {
        const count = this.value.length;
        currentCount.innerHTML = count;

        // Button enable/disable logic
        createButton.disabled = count === 0;

        // Agar character limit exceed ho jaye, extra characters remove kar do
        if (count > maxLength) {
            this.value = this.value.substring(0, maxLength);
            currentCount.innerHTML = maxLength;
        }
    });

    inputField.addEventListener("keydown", function (e) {
        const count = this.value.length;

        // Agar character limit exceed ho jaye to naye characters ko allow na karein
        if (count >= maxLength && e.key !== "Backspace" && e.key !== "Delete" && !e.ctrlKey) {
            e.preventDefault();
            alert("Maximum 40 characters allowed!");
        }
    });
});

document.addEventListener("DOMContentLoaded", function () {
    const fileInput = document.querySelector(".import-main input[type='file']");
    const importMain = document.querySelector(".import-main");
    const filePreview = document.querySelector(".import-flex-main");
    const fileNameSpan = document.querySelector(".file-name-icon span");
    const replaceFileInput = document.querySelector(".import-flex-main input[type='file']");
    const customCheckboxMain = document.querySelector(".custom-checkbox-main");

    // Hide file preview UI and checkbox section by default
    filePreview.style.display = "none";
    customCheckboxMain.style.display = "none";

    fileInput.addEventListener("change", function () {
        if (fileInput.files.length > 0) {
            const file = fileInput.files[0];
            const validExtensions = ["csv", "xlsx"];
            const fileExtension = file.name.split(".").pop().toLowerCase();

            if (validExtensions.includes(fileExtension)) {
                fileNameSpan.textContent = file.name;
                importMain.style.display = "none";
                filePreview.style.display = "flex";
                customCheckboxMain.style.display = "block";
            } else {
                alert("Only CSV and XLSX files are allowed.");
                fileInput.value = "";
            }
        }
    });

    replaceFileInput.addEventListener("change", function () {
        if (replaceFileInput.files.length > 0) {
            const file = replaceFileInput.files[0];
            const validExtensions = ["csv", "xlsx"];
            const fileExtension = file.name.split(".").pop().toLowerCase();

            if (validExtensions.includes(fileExtension)) {
                fileNameSpan.textContent = file.name;
            } else {
                alert("Only CSV and XLSX files are allowed.");
                replaceFileInput.value = "";
            }
        }
    });
});