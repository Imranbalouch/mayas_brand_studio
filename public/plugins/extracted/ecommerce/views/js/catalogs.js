document.title = "Dashboard | Catalogs";
$(document).ready(function () {
    // Initialize variables
    let PageNo = 1;
    let PageSize = 10;
    let totalRecord = 0;
    let PageLength = 0;
    let Search = '';
    let keyupTimer;
    
    // Initialize the page
    Onload();
    
    // Event handlers
    $(".create-catalogs, .create-catalogs-btn").on("click", function(e) {
        e.preventDefault();
        let page = 'create-catalogs';
        window.location.assign('?P=' + page + '&M=' + menu_id);
    });
    
    // Load catalog data
    function Onload() {
        $('#Table_View').DataTable().clear().destroy();
        
        $.ajax({
            url: ApiPlugin + '/ecommerce/catalog/get_catalog',
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
                
                // Check permissions first
                if (!response.permissions || !response.permissions.view) {
                    $("#emptyState").addClass("d-none");
                    $("#catalogTableContainer").addClass("d-none");
                    showToast('You do not have permission to view this menu', 'error', 'error');
                    return;
                }
                
                if (response.status_code == 200) {
                    let action_button = '';
                    let switch_button = '';
                    
                    // Show/hide create button based on permissions
                    if (response.permissions && response.permissions['add']) {
                        $(".create-catalogs").show();
                    } else {
                        $(".create-catalogs").hide();
                    }

                    // Check if there's data to display
                    if (response.data && response.data.length > 0) {
                        $("#emptyState").addClass("d-none");
                        $("#catalogTableContainer").removeClass("d-none");
                        
                        // Prepare table data with the correct structure
                        const tableData = response.data.map(catalog => {
                            return {
                                uuid: catalog.uuid,
                                catalog: catalog.catalog,
                                status: catalog.status,
                                adjustment: catalog.price_adjustment || 'None',
                                overrides: catalog.percentage || 'None',
                                actions: getActionButtons(catalog, response.permissions)
                            };
                        });
                        
                        // Initialize DataTable
                        const columnsConfig = [ 
                            {
                                data: null,
                                render: function(data, type, row, meta) {
                                    return meta.row + meta.settings._iDisplayStart + 1;
                                },
                                title: '#'
                            },
                            { 
                            data: 'catalog',
                                render: function (data, type, row) {
                                    if (type === 'display') {
                                        return `<span class="name-only">${data}</span>
                                                <div class="modify_row">${row.actions}</div>`;
                                    } else {
                                        return data; // Only return plain name for filtering and sorting
                                    }
                                } 
                            }, 
                            { 
                                data: 'status',
                                render: function (data, type, row) {
                                    const statusText = data === 1 ? 'Active' : (data === 0 ? 'Draft' : 'Archived');
                                    const badgeClass = data === 1 ? 'badge-success' : (data === 0 ? 'badge-warning' : 'badge-secondary');
                                    return `<span class="badge ${badgeClass}">${statusText}</span>`;
                                } 
                            }, 
                            { 
                                data: 'adjustment',
                                className: 'text-end',
                                render: function (data) {
                                    return data;
                                } 
                            }, 
                            { 
                                data: 'overrides',
                                render: function (data) {
                                    return data;
                                } 
                            }
                        ];
                        
                        // Use the mapped tableData with the correct structure
                        $('#Table_View').DataTable({
                            data: tableData, // Use the mapped data with adjustment and overrides properties
                            columns: columnsConfig,
                            responsive: {
                                details: {
                                    display: $.fn.dataTable.Responsive.display.modal({
                                        header: function (row) {
                                            var data = row.data();
                                            // Construct full name from customer object
                                            var catalog = data.catalog  ? data.catalog  : 'Unknown Attribute catalog';
                                            return 'Details of ' + catalog;
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

                        
                        // Bind edit click events
                        $('.catalog-title').on('click', function() {
                            const uuid = $(this).data('uuid');
                            let page = 'edit-catalogs';
                            window.location.assign('?P=' + page + '&M=' + menu_id + '&id=' + uuid);
                        });
                        
                    } else {
                        // No data - show empty state
                        $("#emptyState").removeClass("d-none");
                        $("#catalogTableContainer").addClass("d-none");
                    }
                } else if (response.status_code == 404) {
                    $("#emptyState").removeClass("d-none");
                    $("#catalogTableContainer").addClass("d-none");
                } else {
                    showToast(response.message || 'Error loading catalogs', 'warning', 'warning');
                }
            },
            error: function (xhr, status, err) {
                imgload.hide();
                if (xhr.status == 401) {
                    showToast(err, 'error', 'error');
                } else {
                    showToast('Error loading catalogs', 'error', 'error');
                }
            }
        });
    }
    
    // Helper function to get action buttons based on permissions
    function getActionButtons(catalog, permissions) {
        let buttons = '<div class="modify_row">';
        
        if (permissions.edit) {
            buttons += `<a href="javascript:;" class="modify_btn btn-edit" data-uuid="${catalog.uuid}" data-toggle="tooltip" title="Edit">${getTranslation('edit')}</a> | `;
        }
        
        if (permissions.delete) {
            buttons += `<a href="javascript:;" class="modify_btn btn-delete" data-uuid="${catalog.uuid}" data-toggle="tooltip" title="Delete">${getTranslation('delete')}</a>`;
        }
        
        
        buttons += '</div>';
        return buttons;
    }
    
    // Update catalog status
    
    // Delete catalog
    $(document).on('click', '.btn-delete, .delete-catalog', function(e) {
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
            confirmButtonText: getTranslation('delete')
        }).then((result) => {
            if (result.value) {
                $.ajax({
                    url: ApiPlugin + "/ecommerce/catalog/delete_catalog/" + uuid,
                    type: "DELETE",
                    beforeSend: function (xhr) {
                        xhr.setRequestHeader("Authorization", "Bearer " + strkey);
                        xhr.setRequestHeader("menu-uuid", menu_id);
                        imgload.show();
                    },
                    success: function (response) {
                        imgload.hide();
                        if (response.status_code == 200) {
                            showToast(response.message, 'Success', 'success');
                            Onload(); // Refresh the table
                        } else {
                            showToast(response.message || 'Error deleting catalog', 'error', 'error');
                        }
                    },
                    error: function (xhr, status, err) {
                        imgload.hide();
                        showToast('Error deleting catalog', 'error', 'error');
                    }
                });
            }
        });
    });
    
    // Edit catalog
    $(document).on('click', '.btn-edit', function(e) {
        e.preventDefault();
        let currentRow = $(this).closest("tr");
        if (currentRow.hasClass('child')) {
            currentRow = currentRow.prev('tr.dtrg-start');
        }
        let data = $('#Table_View').DataTable().row(currentRow).data();
        let uuid = data['uuid'];
        let page = 'edit-catalogs';
        window.location.assign('?P=' + page + '&M=' + menu_id + '&id=' + uuid);
    });
    
    // Search functionality
    $('#searchInput').on('keyup', function() {
        clearTimeout(keyupTimer);
        keyupTimer = setTimeout(function() {
            Search = $('#searchInput').val();
            Onload();
        }, 500);
    });
    
    // Sort functionality
    $('#sortAZ').on('click', function(e) {
        e.preventDefault();
        // Implement A-Z sorting
        Onload();
    });
    
    $('#sortZA').on('click', function(e) {
        e.preventDefault();
        // Implement Z-A sorting
        Onload();
    });
    
    // Status filter
    $('input[name="status"]').on('change', function() {
        // Implement status filtering
        Onload();
    });
});