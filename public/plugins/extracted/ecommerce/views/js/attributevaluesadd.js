document.title="Dashboard | Attribute Values Add";
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
        let page = 'attvalueadd';
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
            url: ApiPlugin + '/ecommerce/attribute/edit_attribute_value/' + id,
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

                    // //Delete
                    if (Boolean(response.permissions["delete"])) {
                        action_button += `<a href='javascript:;' class='modify_btn btn-delete' data-toggle='tooltip' title='Delete'>${getTranslation('delete')}</a> `;
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
                            { data: 'value',
                                render: function (data, type, row) {
                                    if (action_button !== 'undefined' && action_button !== '') {
                                        return data +  "<div class='modify_row'>" + action_button + "</div>"
                                    } else { return '-' }
                                } 
                            }, 
                            { 
                                data: 'color_code'
                            },
                            { 
                                data: 'attribute_name'
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
                                            var value = data.value  ? data.value  : 'Unknown Attribute Value';
                                            return 'Details of ' + value;
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
    let page = 'attvalueedit';
    window.location.assign('?P='+page+'&M='+menu_id+'&id='+uuid);
});


//Add Attribute Values
$(document).on('click', '.btn-attributevalues', function (e) {
    e.preventDefault();
    $("#popup_title").html(getTranslation("modify_menu"));
    let currentRow = $(this).closest("tr");
    let data = $('#Table_View').DataTable().row(currentRow).data();
    let uuid = data['uuid'];
    imgload.show();
    let page = 'attributevaluesadd';
    window.location.assign('?P='+page+'&M='+menu_id+'&id='+uuid);
});


//delete
$(document).on('click', '.btn-delete', function (e) {
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
                url: ApiPlugin + "/ecommerce/attribute/delete_attribute_value/"+uuid,
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
        url: ApiPlugin + "/ecommerce/attribute/status/"+$(el).val(),
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


function changefeatured(el){
    if(el.checked){
        var featured = 1;
    }
    else{
        var featured = 0;
    }
    $.ajax({
        url: ApiForm + "/brand/featured/"+$(el).val(),
        type: "POST",
        data: {featured:featured},
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