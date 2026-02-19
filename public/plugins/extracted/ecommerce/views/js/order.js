document.title = "Dashboard | Order";
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
        let page = 'add-order';
        window.location.assign('?P='+page+'&M='+menu_id);
    });
    Onload();
});

function formatStatusText(status) {
    if (!status) return '';
    return status
        .split('_')
        .map((word, index) => {
            if (index === 0) {
                return word.charAt(0).toUpperCase() + word.slice(1).toLowerCase();
            } else {
                return word.toLowerCase();
            }
        })
        .join(' ');
}
function Onload() {
    $('#Table_View').DataTable().clear().destroy();
    $('#buttons').html('');
    $.ajax({
        url: ApiPlugin + '/ecommerce/order/get_order',
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
                let view_product = '';
                let view_invoice = '';
                let invoice_download = '';
                let delete_order = ''; 
                //New
                if (Boolean(response.permissions['add'])) {
                    btnnew.show();
                } 
                //Update 
                if (Boolean(response.permissions["edit"])) {
                    // action_button += `<a href='javascript:;' class='modify_btn btn-edit' data-toggle='tooltip' title='Edit'>${getTranslation('edit')}</a> | `;
                    view_product += `<a href='javascript:;' class='modify_btn btn-product span_class' data-toggle='tooltip' title='View Product'>View</a>`;
                    view_invoice += `<a href='javascript:;' class='modify_btn  span_class' data-toggle='tooltip' title='View Invoice'><span class="fa fa-eye"></span></a>`;
                    invoice_download += `<a href='javascript:;' class='modify_btn span_class' data-toggle='tooltip' title='Download Invoice'><span class="fa fa-download"></span></a>`;
                    
                } 
                // //Delete
                if (Boolean(response.permissions["delete"])) {
                    // action_button += `<a href='javascript:;' class='modify_btn btn-delete' data-toggle='tooltip' title='Delete'>${getTranslation('delete')}</a> `;
                    delete_order += `<a href='javascript:;' class='modify_btn btn-delete span_class' data-toggle='tooltip' title='Delete Order'><span class="fa fa-trash"></span></a> `;
                } 
                if (Boolean(response.permissions["update"])) {
                    switch_button = '';
                }else{
                    switch_button = 'disabled';
                } 
                if (response["data"] != null) { 
                    // console.log(response["data"]["orders"]); 
                    // Ajax Data Settings
                    const columnsConfig = [ 
                        {
                            data: null, 
                            render: function(data, type, row, meta) {
                                return meta.row + meta.settings._iDisplayStart + 1; 
                            },
                            title: '#',  
                        },
                        { data: 'code',
                            render: function (data, type, row) {
                                if(row.code){
                                    view=`<a href="?P=edit-order&M=${menu_id}&I=${row.uuid}">${row.code}</a>`;
                                }else{
                                    view='';
                                }
                               return view;
                            }
                        },
                        { data: 'created_at',
                            render: function (data, type, row) {
                               return row.created_at ? formatDate(row.created_at) : '';
                            }
                        },
                        // { 
                        //     data: null,
                        //     render: function (data, type, row) {
                        //       //  console.log(row.channel);
                        //         return row.channel ? row.channel.name: '';
                        //     }
                        // },
                        { data: null,
                            render: function (data, type, row) {
                               return (row.billing_first_name || '') + ' ' + (row.billing_last_name || '');
                            }
                        },
                        { data: 'grand_total',
                            render: function (data, type, row) {
                               return row.grand_total ? 'AED '+row.grand_total.toFixed(2) : '';
                            }
                        },
                        { data: 'mark_as_paid',
                            render: function (data, type, row) {
                                p_status='Payment pending';
                                if(row.mark_as_paid==1){p_status="Paid"}
                               return p_status;
                            }
                        },
                        { data: 'fulfilled_status',
                            render: function (data, type, row) {
                                fullfill_status='Unfulfilled';
                                if(row.fulfilled_status==1){fullfill_status="Fulfilled"}
                                
                               return fullfill_status;
                            }
                        },
                        { data: 'items_count',
                            render: function (data, type, row) {
                                items_count='';  
                                if(row.items_count>1 && row.items_count!=null) {
                                    items_count= row.items_count +' items';
                                }else if(row.items_count!=null) { 
                                    items_count= row.items_count +' item';
                                }else{
                                    items_count='0 items';  
                                }
                                    
                               return items_count;
                            }
                        },
                        { data: 'delivery_status',
                            render: function (data, type, row) {
                                return formatStatusText(row.delivery_status) ? formatStatusText(row.delivery_status) : '';
                            }
                        },
                        { 
                            data: 'tracking',
                            render: function (data, type, row) {
                                if (Array.isArray(row.tracking) && row.tracking.length > 0) {
                                    return row.tracking[0].tracking_number;
                                }
                                return '';
                            }
                        },
                         { data: 'payment_method',
                            render: function (data, type, row) {
                                return row.payment_method ? formatStatusText(row.payment_method) : '';
                            }
                        },
                        // { 
                        //     data: 'tags',
                        //     render: function (data, type, row) { 
                        //         if (row.tags && Array.isArray(row.tags)) {
                        //             return row.tags.map(tag => 
                        //                 `<span class="badge bg-primary" style="margin-right: 5px;">${tag}</span>`
                        //             ).join('');
                        //         } else {
                        //             return ''; 
                        //         }
                        //     }
                        // },
                        // {
                        //     data: 'option',
                        //     render: function (data, type, row) {
                        //         // Wrap the actions in a div with the specified styles
                        //         return `
                        //             <div class="flex-container">
                        //                 ${view_product} ${view_invoice} ${invoice_download} ${delete_order}
                        //             </div>
                        //         `;
                        //     },
                        //     title: 'Action',
                        //     width: '350px',
                        // }
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
                                        var Code = data.code ? data.code : '';
                                        return 'Details of ' + Code;
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
    let currentRow = $(this).closest("tr");
    let data = $('#Table_View').DataTable().row(currentRow).data();
    let uuid = data['uuid'];
    imgload.show();
    let page = 'brandedit';
    window.location.assign('?P='+page+'&M='+menu_id+'&id='+uuid);
});


$('table').on('click', '.btn-product', function (e) {
    e.preventDefault();
    $("#popup_title_add").html("Order Details");
    let currentRow = $(this).closest("tr");
    let data = $('#Table_View').DataTable().row(currentRow).data();
    let uuid = data['uuid'];
    imgload.show();

    $.ajax({
        url: ApiPlugin + "/ecommerce/order/get_specific_order/" + uuid,
        type: "GET",
        dataType: "json",
        beforeSend: function (xhr) {
            xhr.setRequestHeader("Authorization", "Bearer " + strkey);
            xhr.setRequestHeader("menu-uuid", menu_id);
            imgload.show();
        },
        success: function (response) {
            imgload.hide();

            console.log("API Response:", response);

            // Clear the previous data
            $('#order_details').empty();

            if (response.status_code == 200) {
                const orderDetails = response.data.order_details; 
                // Iterate through the order details
                orderDetails.forEach(detail => {
                    const product = detail.product;
                    const qty = detail.quantity;
                    const price = detail.price;
                    const total = qty * price;
                    const productName = product ? product.name : 'N/A';
                    const productImage = product && product.thumbnail_img ? product.thumbnail_img : 'default_image.jpg'; // Replace with default image path
                   // Append a new row to the table
                    $('#order_details').append(`
                        <tr>
                            <td>${productName}</td>
                            <td><img src="${productImage}" alt="${productName}" style="width: 50px; height: 50px;"></td>
                            <td>${qty}</td>
                            <td>${price}</td>
                            <td>${total}</td>
                        </tr>
                    `);
                }); 
                // Show the modal
                $('#edit_Modal').modal('show');
            } else {
                console.log("Error:", response.message);
            }
        },
        error: function (xhr, status, error) {
            imgload.hide();
            console.log("AJAX Error:", {
                status: status,
                error: error,
                response: xhr.responseText
            });
        }
    });
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
                url: ApiPlugin + "/ecommerce/order/delete_order/"+uuid,
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
        var status = 0;
    }
    else{
        var status = 1;
    }
    $.ajax({
        url: ApiPlugin + "/ecommerce/customer/status/"+$(el).val(),
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