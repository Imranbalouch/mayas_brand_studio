document.title = "Dashboard | Company";
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
        let page = 'add-company';
        window.location.assign('?P='+page+'&M='+menu_id);
    });

    fetchActivePaymentTerm().then(() => {
        Onload();
    });
    
});


function fetchActivePaymentTerm() {
    return $.ajax({
        url: ApiPlugin + "/ecommerce/get_active_paymentterm",
        type: "GET",
        dataType: "json",
        beforeSend: function (xhr) {
            xhr.setRequestHeader("Authorization", "Bearer " + strkey);
            xhr.setRequestHeader("menu-uuid", menu_id);
        },
        success: function (response) {
            if (response.status_code == 200) {
                // Store payment terms in an object for easy lookup
                paymentTerms = {};
                response.data.forEach(term => {
                    paymentTerms[term.uuid] = term.name;
                });
            }
        },
        error: function (error) {
            console.error("Error fetching payment term:", error);
        }
    });
}

function Onload() {
    $('#Table_View').DataTable().clear().destroy();
    $('#buttons').html('');
    $.ajax({
        url: ApiPlugin + '/ecommerce/company',
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
                
                // New button
                if (Boolean(response.permissions['add'])) {
                    btnnew.show();
                }

                // Update button
                if (Boolean(response.permissions["edit"])) {
                    action_button += `<a href='javascript:;' class='modify_btn btn-edit' data-toggle='tooltip' title='Edit'>${getTranslation('edit')}</a> | `;
                }

                // Delete button
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
                            data: 'company_name',
                            render: function (data, type, row) {
                                if (type === 'display') {
                                    return `<span class="name-only">${data}</span>
                                            <div class="modify_row">${action_button}</div>`;
                                } else {
                                    return data; // Only return plain name for filtering and sorting
                                }
                            } 
                        }, 
                        { 
                            data: 'company_id',
                            render: function (data, type, row) {
                                return data ? data : '-';
                            } 
                        }, 
                        { 
                            data: 'tax_id',
                            render: function (data, type, row) {
                                return data ? data : '-';
                            } 
                        }, 
                        { 
                            data: 'payment_terms_id',
                            render: function (data, type, row) {
                                if (data && paymentTerms[data]) {
                                    return paymentTerms[data];
                                }
                                return '-';
                            } 
                        },
                        {
                            data: 'approved',
                            render: function (data, type, row) {
                                if (data == 1) {
                                    return `<label class="switch">
                                        <input type="checkbox" ${switch_button} checked class="switch-input" onchange="change_approval_status(this)" value="${row.uuid}" role="switch" id="switchApproval">
                                        <span class="switch-toggle-slider">
                                            <span class="switch-on"></span>
                                            <span class="switch-off"></span>
                                        </span>
                                    </label>`;
                                } else {
                                    return `<label class="switch">
                                        <input type="checkbox" ${switch_button} class="switch-input" onchange="change_approval_status(this)" value="${row.uuid}" role="switch" id="switchApproval">
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
                        $('#Table_View').DataTable({
                            data: tableData,
                            columns: columnsConfig,
                            responsive: {
                                details: {
                                    display: $.fn.dataTable.Responsive.display.modal({
                                        header: function (row) {
                                            var data = row.data();
                                            // Construct full name from customer object
                                            var company = data.company_name  ? data.company_name  : 'Unknown company';
                                            return 'Details of ' + company;
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

// Edit company
$(document).on('click', '.btn-edit', function (e) {
    e.preventDefault();
     let currentRow = $(this).closest("tr");
     if (currentRow.hasClass('child')) {
        currentRow = currentRow.prev('tr.dtrg-start');
    }
    let data = $('#Table_View').DataTable().row(currentRow).data();
    let uuid = data['uuid'];
    let page = 'edit-company';
    window.location.assign('?P='+page+'&M='+menu_id+'&id='+uuid);
});

// Delete company
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
                url: ApiPlugin + "/ecommerce/company/delete/" + uuid,
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
                        Onload();
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


function change_approval_status(el) {
    let approved = el.checked ? 1 : 0;
    $.ajax({
        url: ApiPlugin + "/ecommerce/company/approved_status/" + $(el).val(),
        type: "POST",
        data: { approved: approved },
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