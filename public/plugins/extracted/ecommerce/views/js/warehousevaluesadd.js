document.title = "Dashboard | Warehouse Values";
let PageNo = 1;
let PageSize = 10;
let totalRecord = 0;
let PageLength = 0;
let Search = '';
let keyupTimer;
var urlParams = new URLSearchParams(window.location.search);
var id = urlParams.get('id');

$(document).ready(function () {
    btnnew = $('#btn_new');
    btnnew.hide();
    btnnew.click(function (e) {
        e.preventDefault();
        let page = 'warevalueadd';
        window.location.assign('?P='+page+'&M='+menu_id+'&id='+id);
    });
    
    Onload();
});

function Onload() {
    var urlParams = new URLSearchParams(window.location.search);
    var id = urlParams.get('id');

    $('#Table_View').DataTable().clear().destroy();
    $('#buttons').html('');

    if (id) {
        $.ajax({
            url: ApiPlugin + '/ecommerce/warehouse/edit_warehouse_value/' + id,
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
                    //New
                    if (Boolean(response.permissions['add'])) {
                        btnnew.show();
                    }

                    //Update 
                    if (Boolean(response.permissions["edit"])) {
                        action_button += `<a href='javascript:;' class='modify_btn btn-edit' data-toggle='tooltip' title='Edit'>${getTranslation('edit')}</a> | `;
                    }

                    //Delete
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
                        const columnsConfig = [ 
                            {
                                data: null,
                                render: function(data, type, row, meta) {
                                    return meta.row + meta.settings._iDisplayStart + 1;
                                },
                                title: '#',
                            },
                            { 
                                data: 'location_name',
                                title: 'Location Name',
                                render: function (data, type, row) {
                                    let content = data || '-';
                                    if (action_button) {
                                        content += "<div class='modify_row'>" + action_button + "</div>";
                                    }
                                    return content;
                                } 
                            }, 
                            { 
                                data: 'location_address',
                                title: 'Location Address',
                                render: function (data, type, row) {
                                    return data || '-';
                                }
                            },
                            { 
                                data: 'warehouse_name',
                                title: 'Warehouse Name',
                                render: function (data, type, row) {
                                    return data || '-';
                                }
                            },
                            {
                                data: 'status',
                                render: function (data, type, row) {
                                    if (data == true) {
                                        return `<label class="switch">
                                            <input type="checkbox" ${switch_button} checked class="switch-input" onchange="changestatus(this)" value="${row.uuid}" role="switch" id="switch1">
                                            <span class="switch-toggle-slider">
                                            <span class="switch-on"></span>
                                            <span class="switch-off"></span>
                                            </span>
                                        </label>`;
                                    } else {
                                        return `<label class="switch">
                                            <input type="checkbox" ${switch_button} class="switch-input" onchange="changestatus(this)" value="${row.uuid}" role="switch" id="switch1">
                                            <span class="switch-toggle-slider">
                                            <span class="switch-on"></span>
                                            <span class="switch-off"></span>
                                            </span>
                                        </label>`;
                                    }
                                }
                            },
                            {
                                data: 'featured',
                                render: function (data, type, row) {
                                    if (data == true) {
                                        return `<label class="switch">
                                            <input type="checkbox" ${switch_button} checked class="switch-input" onchange="changefeatured(this)" value="${row.uuid}" role="switch" id="switch1">
                                            <span class="switch-toggle-slider">
                                            <span class="switch-on"></span>
                                            <span class="switch-off"></span>
                                            </span>
                                        </label>`;
                                    } else {
                                        return `<label class="switch">
                                            <input type="checkbox" ${switch_button} class="switch-input" onchange="changefeatured(this)" value="${row.uuid}" role="switch" id="switch1">
                                            <span class="switch-toggle-slider">
                                            <span class="switch-on"></span>
                                            <span class="switch-off"></span>
                                            </span>
                                        </label>`;
                                    }
                                }
                            }
                        ];

                        // Create DataTable
                        $('#Table_View').DataTable({
                            data: response.data,
                            columns: columnsConfig,
                            destroy: true,
                            dom: 'lrtip',
                            ordering: true,
                            responsive: true,
                            lengthChange: false,
                            pageLength: PageSize
                        });
                        
                        imgload.hide();
                    }
                } else if (response.status_code == 404) {
                    imgload.hide();
                    $("#Table_View").DataTable({
                        destroy: true,
                        retrieve: true,
                        ordering: false,
                        dom: "lrt",
                        bLengthChange: false,
                        oLanguage: {
                            sEmptyTable: `<img class='dataTable-custom-empty-icon' src='/images/no-record-found.svg'><br>${tbNoFound}`,
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
                } else {
                    showToast('Error', 'error', 'error');
                }
            }
        });
    }
}


//edit theme
$('table').on('click', '.btn-edit', function (e) {
    e.preventDefault();
    $("#popup_title").html(getTranslation("modify_menu"));
    let currentRow = $(this).closest("tr");
    let data = $('#Table_View').DataTable().row(currentRow).data();
    let uuid = data['uuid'];
    imgload.show();
    let page = 'warevalueedit';
    window.location.assign('?P='+page+'&M='+menu_id+'&id='+uuid);
});


//Add warehouse Values
$('table').on('click', '.btn-warehousevalues', function (e) {
    e.preventDefault();
    $("#popup_title").html(getTranslation("modify_menu"));
    let currentRow = $(this).closest("tr");
    let data = $('#Table_View').DataTable().row(currentRow).data();
    let uuid = data['uuid'];
    imgload.show();
    let page = 'warevaluesadd';
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
                url: ApiPlugin + "/ecommerce/warehouse/delete_warehouse_value/"+uuid,
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
        url: ApiPlugin + "/ecommerce/warehouse/update_warehousevalue_Status/"+$(el).val(),
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


function changefeatured(el) {
    const newFeaturedState = el.checked ? 1 : 0;
    if (newFeaturedState === 0) {
        $.ajax({
            url: ApiPlugin + "/ecommerce/warehouse/get_warehouse_values",
            type: "GET",
            contentType: "application/json",
            dataType: "json",
            beforeSend: function (xhr) {
                xhr.setRequestHeader("Authorization", "Bearer " + strkey);
                xhr.setRequestHeader("menu-uuid", menu_id);
                imgload.show();
            },
            success: function (response) {
                if (response.status_code == 200 && response.data) {
                    const featuredCount = response.data.filter(warehouse => warehouse.featured && warehouse.uuid !== $(el).val()).length;
                    if (featuredCount === 0) {
                        imgload.hide();
                        showToast("At least one location must remain default", 'warning', 'warning');
                        el.checked = true;
                        return;
                    } else {
                        updateFeaturedStatus(el, newFeaturedState);
                    }
                }
                imgload.hide();
            },
            error: function () {
                imgload.hide();
                showToast("Error checking featured status", '', 'error');
                el.checked = !el.checked;
            }
        });
    } else {
        // Check if another warehouse is featured, and un-feature it before updating
        $.ajax({
            url: ApiPlugin + "/ecommerce/warehouse/get_warehouse_values",
            type: "GET",
            contentType: "application/json",
            dataType: "json",
            beforeSend: function (xhr) {
                xhr.setRequestHeader("Authorization", "Bearer " + strkey);
                xhr.setRequestHeader("menu-uuid", menu_id);
                imgload.show();
            },
            success: function (response) {
                if (response.status_code == 200 && response.data) {
                    // Find a warehouse that's currently featured
                    const featuredWarehouse = response.data.find(warehouse => warehouse.featured && warehouse.uuid !== $(el).val());
                    if (featuredWarehouse) {
                        // Un-feature the current featured warehouse first
                        unFeatureWarehouse(featuredWarehouse);
                    } else {
                        // No warehouse is featured, so just feature the current one
                        updateFeaturedStatus(el, newFeaturedState);
                    }
                }
                imgload.hide();
            },
            error: function () {
                imgload.hide();
                showToast("Error checking featured status", '', 'error');
                el.checked = !el.checked;
            }
        });
    }

    function updateFeaturedStatus(el, featured) {
        $.ajax({
            url: ApiPlugin + "/ecommerce/warehouse/update_warehousevalue_featured/" + $(el).val(),
            type: "POST",
            data: { featured: featured },
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
            error: function () {
                imgload.hide();
                showToast("Error", '', 'error');
                el.checked = !el.checked;
            }
        });
    }

    function unFeatureWarehouse(warehouse) {
        $.ajax({
            url: ApiPlugin + "/ecommerce/warehouse/update_warehousevalue_featured/" + warehouse.uuid,
            type: "POST",
            data: { featured: 0 },
            beforeSend: function (xhr) {
                xhr.setRequestHeader("Authorization", "Bearer " + strkey);
                xhr.setRequestHeader("menu-uuid", menu_id);
                imgload.show();
            },
            success: function (data) {
                updateFeaturedStatus(el, 1); // Now, feature the current one
            },
            error: function () {
                imgload.hide();
                showToast("Error unfeaturing warehouse", '', 'error');
                el.checked = false;
            }
        });
    }
}
