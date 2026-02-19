document.title = "Dashboard | Cities";
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
        let page = 'add-city';
        window.location.assign('?P=' + page + '&M=' + menu_id);
    });
    
    Onload();
});

function Onload() {
    $('#Table_View').DataTable().clear().destroy();
    $('#buttons').html('');
    $.ajax({
        url: ApiPlugin + '/ecommerce/cities',
        type: "Get",
        contentType: "application/json",
        dataType: "json",
        beforeSend: function (xhr) {
            xhr.setRequestHeader("Authorization", "Bearer " + strkey);
            xhr.setRequestHeader("menu-uuid", menu_id);
            imgload.show();
        },
        success: function (response) {
            imgload.hide();
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
                    action_button += `<a href='javascript:;' class='modify_btn btn-delete' data-toggle='tooltip' title='Delete'>${getTranslation('delete')}</a> `;
                }

                if (Boolean(response.permissions["update"])) {
                    switch_button = '';
                } else {
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
                            data: 'name',
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
                            data: 'price',
                            render: function (data, type, row) {
                                return data || '-';
                            } 
                        }, 
                        { 
                            data: 'min_price',
                            render: function (data, type, row) {
                                return data || '-';
                            } 
                        }, 
                        { 
                            data: 'vat_percent',
                            render: function (data, type, row) {
                                return data || '-';
                            } 
                        }, 
                        { 
                            data: 'country_uuid',
                            render: function (data, type, row) {
                                if (row.country && row.country.length > 0) {
                                    return row.country[0].name;
                                } else {
                                    return '-';
                                }
                            } 
                        }, 
                        {
                            data: 'status',
                            render: function (data, type, row) {
                                if (data == 1) {
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
                    ];
                    tableData = response["data"];
                    makeDataTable('#Table_View', tableData, columnsConfig, { 
                        showButtons: false, 
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

// Edit Payment Term
$(document).on('click', '.btn-edit', function (e) {
    e.preventDefault();
    let currentRow = $(this).closest("tr");
    if (currentRow.hasClass('child')) {
        currentRow = currentRow.prev('tr.dtrg-start');
    }
    let data = $('#Table_View').DataTable().row(currentRow).data();
    let uuid = data['uuid'];
    let page = 'edit-city';
    window.location.assign('?P=' + page + '&M=' + menu_id + '&id=' + uuid);
});

// Delete Payment Term
$(document).on('click', '.btn-delete', function (e) {
    e.preventDefault();
    let currentRow = $(this).closest("tr");
    if (currentRow.hasClass('child')) {
        currentRow = currentRow.prev('tr.dtrg-start');
    }
    let data = $('#Table_View').DataTable().row(currentRow).data();
    let uuid = data['uuid'];
    Swal.fire({
        title: getTranslation('deleteConfirmMsg'),
        icon: 'warning',
        showCancelButton: true,
        cancelButtonText: getTranslation('cancel'),
        confirmButtonColor: '#15508A',
        cancelButtonColor: '#15508A',
        reverseButtons: true, 
        confirmButtonText: getTranslation('delete'),
        showClass: {
            popup: 'animated fadeInDown faster'
        },
        hideClass: {
            popup: 'animated fadeOutUp faster'
        }
    }).then((result) => {
        if (result.value) {
            $.ajax({
                url: ApiPlugin + "/ecommerce/cities/delete/" + uuid,
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
            });
        }
    });
});

function changestatus(el) {
    let status = el.checked ? 1 : 0;
    $.ajax({
        url: ApiPlugin + "/ecommerce/cities/status/" + $(el).val(),
        type: "POST",
        data: { status: status },
        beforeSend: function (xhr) {
            xhr.setRequestHeader("Authorization", "Bearer " + strkey);
            xhr.setRequestHeader("menu-uuid", menu_id);
            imgload.show();
        },
        success: function (data) {
            imgload.hide();
            showToast(data.message, 'Success', 'success', 'self');
        },
        error: function (xhr, status, err) {
            imgload.hide();
            showToast("Error", '', 'error');
        }
    });
}
