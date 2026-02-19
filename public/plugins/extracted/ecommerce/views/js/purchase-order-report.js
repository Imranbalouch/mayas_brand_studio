document.title = "Dashboard | Purchase Order Report";
let PageNo = 1;
let PageSize = 1;
let totalRecord = 0;
let PageLength = 0;
let Search = '';
let keyupTimer;
$(document).ready(function () {
  btnnew = $('#btn_new');
  btnnew.hide();
  btnnew.click(function (e) {
    e.preventDefault();
    let page = 'add-product';
    window.location.assign('?P=' + page + '&M=' + menu_id);
  });
  Onload();
});

function Onload() {
  alert
  $('#Table_View').DataTable().clear().destroy();
  $.ajax({
    url: ApiPlugin + '/ecommerce/purchase_order_report', // API end point
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
        } else {
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
            {
              data: 'po_number', // inventory
              render: function (data, type, row) {
                if (action_button !== 'undefined' && action_button !== '') {
                  return `<a class="order-number-text edit-purchase-order ViewPurchaseOrder" href="#">${data}</a>`;
                } else { return data }
              }
            },
            {
              data: 'supplier', // inventory
              render: function (data, type, row) {
                if (data !== null && data !== '') {
                  return `<span class="purchase-text">${data.company}</span>`;
                } else { return '<span class="purchase-text">-</span>' }
              }
            },
            {
              data: 'warehouse', // inventory
              render: function (data, type, row) {
                if (data !== null && data !== '') {
                  return `<span class="purchase-text">${data.warehouse_name}</span>`;
                } else { return '<span class="purchase-text">-</span>' }
              }

            },
            {
              data: 'status',
              render: function (data, type, row) {

                if (data === 1) {
                  return `<span class="badge-success">Received</span>`;
                } else if (data === 2) {
                  return `<span class="badge-warning">Ordered</span>`;
                }
                else {
                  return `<span class="badge-draft">Draft</span>`;
                }

              }
            },
            {
              data: 'total_amount', // inventory
              render: function (data, type, row) {
                if (action_button !== 'undefined' && action_button !== '') {
                  return `<span class="purchase-text">${data}</span>`;
                } else { return data }
              }
            },
            {
              data: 'ship_date',
              render: function (data, type, row) {
                if (action_button !== 'undefined' && action_button !== '') {
                  return `<span class="purchase-text">${data}</span>`;
                } else { return data }
              }
            },
            
          ];
          tableData = response["data"];
          let configFilter = {
            searching: true,
            paging: false,
            info: true,
            bLengthChange: false,
            responsive: false,
            dom: 'B',
            buttons: [
              'csv',
              'excel',
            ]
          };
        
          // EXPORT WORKING CODE
          let tableSearch = $('#Table_View').DataTable({
            data: tableData,
            columns: columnsConfig,
            responsive: true,
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
              return 'Details of ' + data['po_number'];
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

           tableSearch.buttons().container().appendTo('#export_buttons');

          // EXPORT WORKING CODE END

          imgload.hide();
        

        }
      } else if (response.statusCode == 404) {
        imgload.hide();
        $("#Table_View").DataTable({
          destroy: true,
          responsive: false,
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
      } else {
        showToast('Error', 'error', 'error');
      }
    }
  })
  return true;
}

$('table').on('click', '.ViewPurchaseOrder', function (e) {
  e.preventDefault();
  $("#popup_title").html(getTranslation("modify_menu"));
  let currentRow = $(this).closest("tr");
  let data = $('#Table_View').DataTable().row(currentRow).data();
  let uuid = data['uuid'];
  let page = 'view-purchase-order';  
  window.location.assign('?P='+page+'&M='+menu_id+'&id='+uuid);
});