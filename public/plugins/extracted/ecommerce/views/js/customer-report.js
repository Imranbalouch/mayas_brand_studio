document.title = "Dashboard | Customer Report";
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
        let page = 'brandadd';
        window.location.assign('?P='+page+'&M='+menu_id);
    });
    
    Onload();
});
function Onload() {
    $('#Table_View').DataTable().clear().destroy();
    $('#buttons').html('');
    $.ajax({
        url: ApiPlugin + '/ecommerce/customer_report',
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
                    // action_button += `<a href='javascript:;' class='modify_btn btn-edit' data-toggle='tooltip' title='Edit'>${getTranslation('edit')}</a> | `;

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
                                    return data +  "<div class='modify_row'>" + action_button + "</div>"
                                } else { return data }
                            } 
                        }, 
                        { data: 'email',
                            render: function (data, type, row) {
                                return data;
                            } 
                        }, 
                        { data: 'phone',
                            render: function (data, type, row) {
                                return data;
                            } 
                        }, 
                        { data: 'recent_purchased',
                            render: function (data, type, row) {
                                return data ? data : 'None';
                            } 
                        }, 
                    ];
                    tableData= response["data"];
                    var table = $('#Table_View').DataTable({
                        data: tableData,
                        columns: columnsConfig,
                        buttons: [
                        {
                        extend: 'collection',
                        className: 'btn btn-primary dropdown-toggle float-end  mb-0 waves-effect waves-light',
                        text: '<i class="ri-upload-2-line me-2"></i>Export',
                        buttons: [
                            {
                            extend: 'print',
                            text: '<i class="ri-printer-line me-1" ></i>Print',
                            className: 'dropdown-item',
                            exportOptions: { columns: [1, 2, 3, 4] }
                            },
                            {
                            extend: 'csv',
                            text: '<i class="ri-file-text-line me-1" ></i>Csv',
                            className: 'dropdown-item',
                            exportOptions: { columns: [1, 2, 3, 4] }
                            },
                            {
                            extend: 'excel',
                            text: '<i class="ri-file-excel-line me-1"></i>Excel',
                            className: 'dropdown-item',
                            exportOptions: { columns: [1, 2, 3, 4] }
                            },
                            {
                            extend: 'pdf',
                            text: '<i class="ri-file-pdf-line me-1"></i>Pdf',
                            className: 'dropdown-item',
                            exportOptions: { columns: [1, 2, 3, 4] }
                            },
                            {
                            extend: 'copy',
                            text: '<i class="ri-file-copy-line me-1" ></i>Copy',
                            className: 'dropdown-item',
                            exportOptions: { columns: [1, 2, 3, 4] }
                            }
                        ]
                        }
                    ],
      // For responsive popup
      responsive: {
        details: {
          display: $.fn.dataTable.Responsive.display.modal({
            header: function (row) {
              var data = row.data();
              return 'Details of ' + data['full_name'];
            }
          }),
          type: 'column',
          renderer: function (api, rowIdx, columns) {
            var data = $.map(columns, function (col, i) {
              return col.title !== '' // ? Do not show row in modal popup if title is blank (for check box)
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

                    // Move buttons to custom div
                    table.buttons().container().appendTo('#export_buttons');

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
    let page = 'brandedit';
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
                url: ApiPlugin + "/ecommerce/customer/delete_customer/"+uuid,
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