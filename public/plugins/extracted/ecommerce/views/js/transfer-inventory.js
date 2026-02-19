document.title="Dashboard | Transfer Inventory";
let PageNo = 1;
let PageSize = 10;
let totalRecord = 0;
let PageLength = 0;
let Search = '';
let keyupTimer;
let locationMap = {};

$(document).ready(function () {
    btnnew = $('#btn_new');
    btnnew.hide();
    btnnew.click(function (e) {
        e.preventDefault();
        let page = 'create-transfer';
        window.location.assign('?P=' + page + '&M=' + menu_id);
    });

    // Load warehouse locations and table data on page load
    loadWarehouseLocations();
    Onload();

    // Handle custom search
    $("#custom_search").keyup(function () {
        clearTimeout(keyupTimer);
        keyupTimer = setTimeout(function () {
            Search = $("#custom_search").val();
            Onload();
        }, 500);
    });
});

function formatStatusText(status) {
    if (!status) return '';
    return status
        .split('_')
        .map(word => word.charAt(0).toUpperCase() + word.slice(1).toLowerCase())
        .join(' ');
}

// Function to load warehouse locations
function loadWarehouseLocations() {
    $.ajax({
        url: ApiPlugin + '/ecommerce/warehouse/get_active_warehouse_locations',
        type: "GET",
        contentType: "application/json",
        dataType: "json",
        beforeSend: function (xhr) {
            xhr.setRequestHeader("Authorization", "Bearer " + strkey);
            xhr.setRequestHeader("menu-uuid", menu_id);
            imgload.show();
        },
        success: function (response) {
            imgload.hide();
            let data = response.data;
            let htmlLi = ``;

            // Populate the locationMap
            for (const key in data) {
                let item = data[key];
                locationMap[item.uuid] = item.location_name; // Store UUID to name mapping
                htmlLi += ` <li>
                                <label class="custom-checkbox">
                                    <input style="display:none;" name="market-radio" data-uuid="${item.uuid}" value="${item.location_name}" type="checkbox">
                                    ${item.location_name}
                                </label>
                            </li>`;
            }
            $("#select_location").append(htmlLi);
        },
        error: function (xhr, status, err) {
            imgload.hide();
            showToast('Error loading warehouse locations', 'error', 'error');
        }
    });
}

function Onload() {
    $('#Table_View').DataTable().clear().destroy();
    $.ajax({
        url: ApiPlugin + '/ecommerce/transferinventory/get_transferinventory',
        type: "GET",
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
                let view_details = '';
                let delete_transfer = '';

                // Permissions handling
                if (Boolean(response.permissions['add'])) {
                    btnnew.show();
                }
                if (Boolean(response.permissions["edit"])) {
                    action_button += `<a href='javascript:;' class='modify_btn btn-edit span_class' data-toggle='tooltip' title='Edit'><span class='fa fa-edit'></span></a> `;
                    view_details += `<a href='javascript:;' class='modify_btn btn-view span_class' data-toggle='tooltip' title='View Details'><span class='fa fa-eye'></span></a> `;
                }
                if (Boolean(response.permissions["delete"])) {
                    delete_transfer += `<a href='javascript:;' class='modify_btn btn-delete span_class' data-toggle='tooltip' title='Delete Transfer'><span class='fa fa-trash'></span></a> `;
                }

                if (response["data"] != null) {
                    const columnsConfig = [
                        {
                            data: null,
                            render: function (data, type, row, meta) {
                                return meta.row + meta.settings._iDisplayStart + 1;
                            },
                            title: '#',
                        },
                        {
                            data: 'ti_number',
                            render: function (data, type, row) {
                                return `<a href="?P=edit-transfer-inventory&M=${menu_id}&id=${row.uuid}">${data}</a>`;
                            }
                        },
                        {
                            data: 'destination_location_id',
                            render: function (data, type, row) {
                                const locationName = locationMap[data] || data;
                                return locationName ? `<span class="purchase-text">${locationName}</span>` : '<span class="purchase-text">-</span>';
                            }
                        },
                        {
                            data: 'status',
                            render: function (data, type, row) {
                                if (data === 'draft') {
                                    return `<span class="badge" style="background-color: rgba(213, 235, 255, 1); color: rgba(0, 58, 90, 1)">Draft</span>`;
                                } else if (data === 'in_progress') {
                                    return `<span class="badge" style="color:rgba(97, 97, 97, 1); background-color: rgba(0, 0, 0, .06)">In Progress</span>`;
                                } else if (data === 'transferred') {
                                    return `<span class="badge" style="color:rgba(1, 75, 64, 1); background-color: rgba(175, 254, 191, 1)">Transferred</span>`;
                                } else {
                                    return `<span class="badge bg-secondary">${formatStatusText(data)}</span>`;
                                }
                            }
                        },
                        {
                            data: 'estimated_date',
                            render: function (data, type, row) {
                                return data ? `<span class="purchase-text">${data}</span>` : '<span class="purchase-text">-</span>';
                            }
                        },
                        {
                            data: 'shipcarrier',
                            render: function (data, type, row) {
                                return data && data.name ? `<span class="purchase-text">${data.name}</span>` : '<span class="purchase-text">-</span>';
                            }
                        },
                    ];

                    let tableData = response["data"];
                    let configFilter = {
                        searching: true,
                        paging: true,
                        info: true,
                        bLengthChange: false,
                        responsive: false,
                        dom: 'Bfrtip',
                        buttons: [
                            {
                                extend: 'excel',
                                text: 'Export to Excel',
                                exportOptions: { modifier: { page: 'all' } }
                            },
                            {
                                extend: 'csv',
                                text: 'Export to CSV',
                                exportOptions: { modifier: { page: 'all' } }
                            }
                        ]
                    };

                    let tableSearch = $('#Table_View').DataTable({
                        data: tableData,
                        columns: columnsConfig,
                        responsive: false,
                        ...configFilter,
                        initComplete: function () {
                            $('.dt-buttons').hide();
                        }
                    });

                    $('#export-product').prop("disabled", false);
                    $('#export-product').on('click', function () {
                        let exportType = $('input[name="all"]:checked').val();
                        tableSearch.buttons([
                            {
                                extend: 'excel',
                                text: 'Export to Excel',
                                exportOptions: { modifier: { page: exportType === "current" ? 'current' : 'all' } }
                            },
                            {
                                extend: 'csv',
                                text: 'Export to CSV',
                                exportOptions: { modifier: { page: exportType === "current" ? 'current' : 'all' } }
                            }
                        ]).container().appendTo($('.dt-buttons'));
                        tableSearch.button(0).trigger();
                    });

                    $(".dataTables_filter").hide();
                    $('#custom_search').keyup(function () {
                        tableSearch.search($(this).val()).draw();
                    });

                    $(".lodest-first a").on('click', function (e) {
                        e.preventDefault();
                        $(this).siblings().removeClass("active");
                        $(this).addClass("active");
                    });

                    var selectedColumn = 1;
                    $("input[name='sort-option']").on("change", function () {
                        selectedColumn = $(this).val();
                    });

                    $("#sortAZ").on("click", function (e) {
                        e.preventDefault();
                        tableSearch.order([selectedColumn, 'asc']).draw();
                    });

                    $("#sortZA").on("click", function (e) {
                        e.preventDefault();
                        tableSearch.order([selectedColumn, 'desc']).draw();
                    });

                    imgload.hide();
                }
            } else if (response.status_code == 404) {
                imgload.hide();
                $("#Table_View").DataTable({
                    destroy: true,
                    responsive: false,
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
}

// Edit Transfer Inventory
$('table').on('click', '.btn-edit', function (e) {
    e.preventDefault();
    let currentRow = $(this).closest("tr");
    let data = $('#Table_View').DataTable().row(currentRow).data();
    let uuid = data['uuid'];
    imgload.show();
    let page = 'edit-transfer-inventory';
    window.location.assign('?P=' + page + '&M=' + menu_id + '&id=' + uuid);
});

// View Transfer Inventory Details
$('table').on('click', '.btn-view', function (e) {
    e.preventDefault();
    $("#popup_title_add").html("Transfer Inventory Details");
    let currentRow = $(this).closest("tr");
    let data = $('#Table_View').DataTable().row(currentRow).data();
    let uuid = data['uuid'];
    imgload.show();

    $.ajax({
        url: ApiPlugin + "/ecommerce/transferinventory/get_specific_transfer/" + uuid,
        type: "GET",
        dataType: "json",
        beforeSend: function (xhr) {
            xhr.setRequestHeader("Authorization", "Bearer " + strkey);
            xhr.setRequestHeader("menu-uuid", menu_id);
            imgload.show();
        },
        success: function (response) {
            imgload.hide();
            $('#transfer_details').empty();

            if (response.status_code == 200) {
                const transferDetails = response.data.transfer_details;
                transferDetails.forEach(detail => {
                    const product = detail.product;
                    const qty = detail.quantity;
                    const productName = product ? product.name : 'N/A';
                    const productImage = product && product.thumbnail_img ? product.thumbnail_img : 'default_image.jpg';

                    $('#transfer_details').append(`
                        <tr>
                            <td>${productName}</td>
                            <td><img src="${productImage}" alt="${productName}" style="width: 50px; height: 50px;"></td>
                            <td>${qty}</td>
                        </tr>
                    `);
                });
                $('#edit_Modal').modal('show');
            } else {
                showToast(response.message, 'warning', 'warning');
            }
        },
        error: function (xhr, status, error) {
            imgload.hide();
            showToast('Error', 'error', 'error');
        }
    });
});

// Delete Transfer Inventory
$('table').on('click', '.btn-delete', function (e) {
    e.preventDefault();
    let currentRow = $(this).closest("tr");
    let data = $('#Table_View').DataTable().row(currentRow).data();
    let uuid = data['uuid'];
    Swal.fire({
        title: getTranslation('deleteConfirmMsg') + data['ti_number'],
        icon: 'warning',
        showCancelButton: true,
        cancelButtonText: getTranslation('cancel'),
        confirmButtonColor: '#15508A',
        cancelButtonColor: '#15508A',
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
                url: ApiPlugin + "/ecommerce/transferinventory/delete_transfer/" + uuid,
                type: "DELETE",
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

function discon() {
    $('#edit_Modal').modal('hide');
}