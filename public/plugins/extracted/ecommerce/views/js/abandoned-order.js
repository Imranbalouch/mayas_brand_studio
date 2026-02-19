document.title="Dashboard | Abandoned Order";
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
        let page = 'customeradd';
        window.location.assign('?P='+page+'&M='+menu_id);
    });
    
    Onload();
});
function Onload() {
    $('#Table_View').DataTable().clear().destroy();
    $('#buttons').html('');
    $.ajax({
        url: ApiPlugin + '/ecommerce/admin-cart',
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

                
                if (response["data"] != null) { 
                    // Ajax Data Settings
                    const columnsConfig = [ 
                        {
                            data: null, // No data source
                            render: function(data, type, row, meta) {
                                return meta.row + meta.settings._iDisplayStart + 1; // Serial number
                            },
                            title: '#', // AUTO Serial number Column
                        // orderable: false // Disable sorting on this column
                        },
                        { data: 'customer',
                            render: function (data, type, row) {
                                return row.customer?.name ? row.customer.name : 'Guest User';
                            } 
                        }, 
                        { data: 'email',
                            render: function (data, type, row) {
                                return row.customer?.email ? row.customer.email : '<div class="text-center">-</div>';
                            } 
                        }, 
                        { data: 'product_name',
                            render: function (data, type, row) {
                                return data;
                            } 
                        }, 
                        { data: 'varaint_name',
                            render: function (data, type, row) {
                                return data ? data : "N/A";
                            } 
                        }, 
                        { data: 'product_price',
                            render: function (data, type, row) {
                                return data.toLocaleString();
                            } 
                        }, 
                        // { data: 'recent_purchased',
                        //     render: function (data, type, row) {
                        //         return data ? data : 'None';
                        //     } 
                        // }, 
                        { data: 'product_qty',
                            render: function (data, type, row) {
                                return data;
                            } 
                        }, 
                        {
                            data: 'product',
                            render: function (data, type, row) {
                                if (row.variant && row.variant[0] && row.variant[0].image) {
                                    return `<img style="min-width: 48px;" src="${AssetsPath + row.variant[0].image}" width="48" alt="image">`;
                                }
                                else if (row.product && row.product[0] && row.product[0].thumbnail_img) {
                                    return `<img style="min-width: 48px;" src="${AssetsPath + row.product[0].thumbnail_img}" width="48" alt="image">`;
                                }
                                else {
                                    return '-';
                                }
                            }
                        }
                    ];
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
                                        var Customer = data.customer?.name  ? data.customer?.name  : 'Unknown Customer';
                                        return 'Details of ' + Customer;
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


//edit theme
$('table').on('click', '.btn-edit', function (e) {
    e.preventDefault();
    $("#popup_title").html(getTranslation("modify_menu"));
    let currentRow = $(this).closest("tr");
    let data = $('#Table_View').DataTable().row(currentRow).data();
    let uuid = data['uuid'];
    let page = 'customeredit';
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
        reverseButtons: true, 
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
                url: ApiPlugin + "/ecommerce/customer/delete/"+uuid,
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

function changestatus(el) {
    let status = el.checked ? 1 : 0; // Checked = active (1), Unchecked = inactive (0)
    $.ajax({
        url: ApiPlugin + "/ecommerce/customer/status/" + $(el).val(),
        type: "POST",
        data: { status: status },
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
            showToast("Error", '', 'error');
        }
    });
}