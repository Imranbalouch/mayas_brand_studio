document.title = "Dashboard | Discounts";
let PageNo = 1;
let PageSize = 10;
let totalRecord = 0;
let PageLength = 0;
let Search = '';
let keyupTimer;

function formatDiscountType(value) {
    return value
        .split('_')                       
        .map(word => word.charAt(0).toUpperCase() + word.slice(1)) 
        .join(' ');                          
}

function formatMethodType(value) {
    return value.charAt(0).toUpperCase() + value.slice(1);
}

$(document).ready(function () {
    btnnew = $('#btn_new');
    btnnew.hide();
    btnnew.click(function (e) {
        e.preventDefault();
        // $('#exampleModalOne').modal('show');
        window.location.assign('?P=' + 'add-discount' + '&M=' + menu_id);
    });
    
    // Handle discount option clicks
//     $(document).on('click', '.discount-option', function() {
//     let discountType = $(this).data('type');
//     let page;
    
//     switch (discountType) {
//         case 'amount-off-products':
//             page = 'add-discount'; 
//             break;
//         case 'amount-off-order':
//             page = 'add-discount-order';
//             break;
//         case 'buy-x-get-y':
//             page = 'add-discount-buy-x-get-y'; 
//             break;
//         case 'free-shipping':
//             page = 'add-discount-free-shipping'; 
//             break;
//         default:
//             page = 'add-discount'; 
//     }
    
//     window.location.assign('?P=' + page + '&M=' + menu_id + '&T=' + discountType);
// });
    
    Onload();
});
function Onload() {
    $('#Table_View').DataTable().clear().destroy();
    $('#buttons').html('');
    $.ajax({
        url: ApiPlugin + '/ecommerce/get_discount',
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
                            data: 'code',
                            render: function (data, type, row) {
                                let view = row.code || row.name || '';
                                if (type === 'display') {
                                    return `<span class="name-only">${view}</span>
                                            <div class="modify_row">${action_button}</div>`;
                                } else {
                                    return view;
                                }
                            } 
                        }, 
                        { data: 'status',
                           render: function (data, type, row) {
                                return row.status == 1 ? '<span class="active-discount-status-badge">Active</span>' : '<span class="inactive-discount-status-badge">Inactive</span>';
                            }
                        },
                        // { data: 'method',
                        //     render: function (data, type, row) {
                        //         if (!row.method) {
                        //             return 'None';
                        //         }
                        //         return formatMethodType(row.method);
                        //     }
                        // },
                        // { data: 'discount_type',
                        //     render: function (data, type, row) {
                        //         if (!row.discount_type) {
                        //             return 'None';
                        //         }
                        //         return formatDiscountType(row.discount_type);
                        //     }
                        // },
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
                                    </label>`
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
                        }
                    ];
                    tableData= response["data"];
                    tableData= response["data"];
                        $('#Table_View').DataTable({
                            data: tableData,
                            columns: columnsConfig,
                            responsive: {
                                details: {
                                    display: $.fn.dataTable.Responsive.display.modal({
                                        header: function (row) {
                                            var data = row.data();
                                            // Construct full name from customer object
                                            var discount = data.name  || data.code || 'Unknown discount';
                                            return 'Details of ' + discount;
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

//edit discount
$(document).on('click', '.btn-edit', function (e) {
    e.preventDefault();
    let currentRow = $(this).closest("tr");
    if (currentRow.hasClass('child')) {
        currentRow = currentRow.prev('tr.dtrg-start');
    }
    let data = $('#Table_View').DataTable().row(currentRow).data();
    let uuid = data['uuid'];
    let DiscountType = data['discount_type']; 
    
    let page;
    switch(DiscountType) {
        case 'amount_off_orders':
            page = 'edit-discount-order';
            break;
        case 'amount_off_products':
            page = 'edit-discount';
            break;
        case 'buy_x_get_y':
            page = 'edit-discount-buy-x-get-y';
            break;
        case 'free_shipping':
            page = 'edit-discount-free-shipping';
            break;
    }
    
    window.location.assign('?P='+page+'&M='+menu_id+'&I='+uuid);
});

//delete discount
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
        confirmButtonText: getTranslation('delete'),
        reverseButtons: true, 
        showClass: {
            popup: 'animated fadeInDown faster'
        },
        hideClass: {
            popup: 'animated fadeOutUp faster'
        }
    }).then((result) => {
        if (result.value) {
            $.ajax({
                url: ApiPlugin + "/ecommerce/delete_discount/"+uuid,
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
                        Onload(); // Refresh the table
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
    if(el.checked) {
        var status = 1;
    } else {
        var status = 0;
    }
    
    $.ajax({
        url: ApiPlugin + "/ecommerce/status_discount/"+$(el).val(),
        type: "POST",
        data: {status: status},
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