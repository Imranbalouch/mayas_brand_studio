document.title = "Dashboard | Warehouse";
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
        let page = 'warehouseadd';
        window.location.assign('?P='+page+'&M='+menu_id);
    });
    
    Onload();
});
function Onload() {
    $('#Table_View').DataTable().clear().destroy();
    $('#buttons').html('');
    $.ajax({
        url: ApiPlugin + '/ecommerce/warehouse/get_warehouse',
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
                
                // New
                if (Boolean(response.permissions['add'])) {
                    btnnew.show();
                }

                // Update 
                if (Boolean(response.permissions["edit"])) {
                    action_button += `<a href='javascript:;' class='modify_btn btn-edit' data-toggle='tooltip' title='Edit'>${getTranslation('edit')}</a> | `;
                }

                // Delete
                if (Boolean(response.permissions["delete"])) {
                    action_button += `<a href='javascript:;' class='modify_btn btn-delete' data-toggle='tooltip' title='Delete'>${getTranslation('delete')}</a> | `;
                }
                
                // warehouse Values
                if (Boolean(response.permissions["update"])) {
                    action_button += `<a href='javascript:;' class='modify_btn btn-warehousevalues' data-toggle='tooltip' title='Warehouses Values'>${getTranslation('warehouses_values')}</a> `;
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
                            data: null, // No data source
                            render: function (data, type, row, meta) {
                                return meta.row + meta.settings._iDisplayStart + 1; 
                            },
                            title: '#', // AUTO Serial number Column
                        },
                        { 
                            data: 'warehouse_name',
                            render: function (data, type, row) {
                                if (type === 'display') {
                                    return `<span class="name-only">${data}</span>
                                            <div class="modify_row">${action_button}</div>`;
                                } else {
                                    return data;
                                }
                            } 
                        },
                        {
                            data: 'prefix',
                            render: function (data, type, row) {
                                return data }
                            
                        },
                        {
                            data: null, // For 'values' column
                            render: function (data, type, row) {
                                if (row.warehouse_values && row.warehouse_values.length > 0) {
                                    return row.warehouse_values.map(val => `<span style="background-color: #d5d5d5;padding: 5px;border-radius: 5px;">${val.location_name}</span>`).join(', ');
                                } else {
                                    return '-';
                                }
                            },
                            createdCell: function (td) {
                                $(td).addClass('custom_span');
                            },
                            title: 'Location name' // New column title
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
                            },
                            orderable: false
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
                            },
                            orderable: false
                        }
                    ];

                    const tableData = response["data"];
                     $('#Table_View').DataTable({
                                data: tableData,
                                columns: columnsConfig,
                                responsive: {
                                    details: {
                                        display: $.fn.dataTable.Responsive.display.modal({
                                            header: function (row) {
                                                var data = row.data();
                                                // Construct full name from customer object
                                                var warehouse_name = data.warehouse_name  ? data.warehouse_name  : 'Unknown warehouse_name';
                                                return 'Details of ' + warehouse_name;
                                            }
                                        }),
                                        type: 'column',
                                        renderer: function (api, rowIdx, columns) {
                                            var data = $.map(columns, function (col, i) {
                                                return col.title !== '' 
                                                    ? '<tr data-dt-row="' +
                                                        col.rowIndex +
                                                        '" data-dt-column="' +
                                                        col.columnIndex +
                                                        '">' +
                                                        '<td>' +
                                                        col.title +
                                                        ':' +
                                                        '</td> ' +
                                                        '<td>' +
                                                        col.data +
                                                        '</td>' +
                                                        '</tr>'
                                                    : '';
                                            }).join('');

                                            return data ? $('<table class="table"/><tbody />').append(data) : false;
                                        }
                                    }
                                },
                                destroy: true
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
            } else {
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
    return true;
}



//edit theme
$(document).on('click', '.btn-edit', function (e) {
    e.preventDefault();
    $("#popup_title").html(getTranslation("modify_menu"));
    let currentRow = $(this).closest("tr");
    if (currentRow.hasClass('child')) {
        currentRow = currentRow.prev('tr.dtrg-start');
    }
    let data = $('#Table_View').DataTable().row(currentRow).data();
    let uuid = data['uuid'];
    imgload.show();
    let page = 'warehouseedit';
    window.location.assign('?P='+page+'&M='+menu_id+'&id='+uuid);
});


//Add warehouse Values
$(document).on('click', '.btn-warehousevalues', function (e) {
    e.preventDefault();
    $("#popup_title").html(getTranslation("modify_menu"));
    let currentRow = $(this).closest("tr");
    if (currentRow.hasClass('child')) {
        currentRow = currentRow.prev('tr.dtrg-start');
    }
    let data = $('#Table_View').DataTable().row(currentRow).data();
    let uuid = data['uuid'];
    imgload.show();
    let page = 'warehousevaluesadd';
    window.location.assign('?P='+page+'&M='+menu_id+'&id='+uuid);
});


//delete
$(document).on('click', '.btn-delete', function (e) {
    e.preventDefault();
    let currentRow = $(this).closest("tr");
    if (currentRow.hasClass('child')) {
        currentRow = currentRow.prev('tr.dtrg-start');
    }
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
                url: ApiPlugin + "/ecommerce/warehouse/delete_warehouse/"+uuid,
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
        url: ApiPlugin + "/ecommerce/warehouse/status/"+$(el).val(),
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
            url: ApiPlugin + "/ecommerce/warehouse/get_warehouse",
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
                        showToast("At least one warehouse must remain default", 'warning', 'warning');
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
            url: ApiPlugin + "/ecommerce/warehouse/get_warehouse",
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
            url: ApiPlugin + "/ecommerce/warehouse/featured/" + $(el).val(),
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
            url: ApiPlugin + "/ecommerce/warehouse/featured/" + warehouse.uuid,
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
