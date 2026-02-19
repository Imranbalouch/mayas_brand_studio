document.title = "Dashboard | Receive Item";
$(document).ready(function () {
    fetchPurchaseOrderReceiving();

    $(document).on('click', '.progress-count p', function () {
        $(this).toggleClass('open');
        $(this).parent().find('.dropdown-menu').toggleClass('show');
    });

    // Handle input changes in the receive table
    $('.receive-table').on('input', 'td .form-control', function (e) {
        const $row = $(this).closest('tr');
        const inputValue = parseInt($(this).val()) || 0;
        
        // Validate input is not negative
        if (inputValue < 0) {
            showToast('Quantity cannot be negative', 'Error', 'error');
            $(this).val(0); // Reset the input
            updateRowProgress($row);
            setHeaderProgress();
            return;
        }
        
        const rec = parseInt($row.find('td:nth-child(3) .form-control').val()) || 0;
        const rej = parseInt($row.find('td:nth-child(4) .form-control').val()) || 0;
        const total = parseInt($row.data('tot')) || 0;
        const prevAccepted = parseInt($row.data('prev-accepted')) || 0;
        const prevRejected = parseInt($row.data('prev-rejected')) || 0;
        const maxRemaining = total - (prevAccepted + prevRejected);


        // Validate total received does not exceed ordered quantity
        if ((rec + rej) > total) {
            showToast('Total of received and rejected cannot exceed ordered quantity.', 'Error', 'error');
            $(this).val(0); // Reset the input
            updateRowProgress($row);
            setHeaderProgress();
            return;
        }


        updateRowProgress($row);
        setHeaderProgress();
    });

    // Handle "Accept all unreceived" click
    $('.receive-card .accept-all-unreceived').on('click', function (e) {
        e.preventDefault();
        $('.receive-table tbody tr').each(function () {
            const $row = $(this);
            if ($row.find('td:nth-child(3) .form-control').prop('disabled')) return; // Skip disabled rows
            
            const total = parseInt($row.data('tot')) || 0;
            $row.find('td:nth-child(4) .form-control').val(0);
            const rejected = 0;
            const accepted = parseInt($row.find('td:nth-child(3) .form-control').val()) || 0;
            const unreceived = total - (accepted + rejected);
            
            if (unreceived > 0) {
                $row.find('td:nth-child(3) .form-control').val(accepted + unreceived);
                updateRowProgress($row);
            }
        });
        setHeaderProgress();
    });

    // Handle "Reject all unreceived" click
    $('.receive-card .reject-all-unreceived').on('click', function (e) {
        e.preventDefault();
        $('.receive-table tbody tr').each(function () {
            const $row = $(this);
            if ($row.find('td:nth-child(4) .form-control').prop('disabled')) return; // Skip disabled rows
            
            const total = parseInt($row.data('tot')) || 0;
            $row.find('td:nth-child(3) .form-control').val(0);
            const accepted = 0;
            const rejected = parseInt($row.find('td:nth-child(4) .form-control').val()) || 0;
            const unreceived = total - (accepted + rejected);
            
            if (unreceived > 0) {
                $row.find('td:nth-child(4) .form-control').val(rejected + unreceived);
                updateRowProgress($row);
            }
        });
        setHeaderProgress();
    });

    // Submit receiving data
    $('#submitReceiving').on('click', function (e) {
        e.preventDefault();
        submitReceivingData();
    });
});

$('table').on('click', 'a.title', function (e) {
    e.preventDefault();

    let currentRow = $(this).closest("tr");
    let uuid = currentRow.data('product-id'); 

    if (!uuid) {
        showToast('Product UUID not found.', 'Error', 'error');
        return;
    }

    let page = 'edit-product'; 
    window.location.assign(`?P=${page}&M=${menu_id}&id=${uuid}`);
});


// Fetch purchase order receiving data
function fetchPurchaseOrderReceiving() {
    const urlParams = new URLSearchParams(window.location.search);
    const id = urlParams.get('id');
    if (!id) {
        showToast('Purchase order ID not found in URL.', 'Error', 'error');
        return;
    }
    $.ajax({
        url: `${ApiPlugin}/ecommerce/purchase-order/po-receving/${id}`,
        type: 'GET',
        dataType: 'json',
        beforeSend: function (xhr) {
            xhr.setRequestHeader('Authorization', 'Bearer ' + strkey);
            xhr.setRequestHeader('menu-uuid', menu_id);
            imgload.show();
        },
        success: function (response) {
            imgload.hide();
            if (response.status_code === 200) {
                populateReceivingTable(response.data);
            } else {
                showToast(response.message || 'Failed to load purchase order receiving data.', 'Error', 'error');
            }
        },
        error: function (error) {
            imgload.hide();
            error = error.responseJSON || error;
            showToast(error.message || 'Internal Server Error.', 'Error', 'error');
        }
    });
}

// Populate the receiving table
function populateReceivingTable(data) {
    const $tbody = $('#Table_View tbody');
    $tbody.empty();
    $('.purchase-title').next('span').text(data.po_number);

    const currencySymbol = data.currency?.symbol || 'Rs';
    let productItemHtml = '';

    data.purchaseOrderItems.forEach(function (item) {
        const productName = item.product?.name || 'Product Not Found';
        const variantName = item.variant?.variant || 'No Variant';
        const total = item.quantity;
        const accepted = item.accepted || 0;
        const unreceived = item.unreceived || 0;
        const isProductDeleted = item.is_product_deleted || false;
        const isVariantDeleted = item.is_variant_deleted || false;
        const imageHtml = item.product?.thumbnail_img
            ? `<img src="${AssetsPath + item.product.thumbnail_img}" alt="${productName}">`
            : `<svg width="60" height="60" viewBox="0 0 42 42" fill="none" xmlns="http://www.w3.org/2000/svg">
                <rect x="1" y="1" width="40" height="40" rx="4" fill="white"/>
                <rect x="1" y="1" width="40" height="40" rx="4" stroke="#232323" stroke-opacity="0.1"/>
                <path d="M26.8333 13.5H15.1667C14.2462 13.5 13.5 14.2462 13.5 15.1667V26.8333C13.5 27.7538 14.2462 28.5 15.1667 28.5H26.8333C27.7538 28.5 28.5 27.7538 28.5 26.8333V15.1667C28.5 14.2462 27.7538 13.5 26.8333 13.5Z" stroke="#8D8D8D" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M18.0834 19.3334C18.7737 19.3334 19.3334 18.7737 19.3334 18.0834C19.3334 17.393 18.7737 16.8334 18.0834 16.8334C17.393 16.8334 16.8334 17.393 16.8334 18.0834C16.8334 18.7737 17.393 19.3334 18.0834 19.3334Z" stroke="#8D8D8D" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M28.5 23.5L24.3333 19.3334L15.1666 28.5" stroke="#8D8D8D" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>`;

        let statusMessage = '';
        if (isProductDeleted) {
            statusMessage = '<p class="text-danger mb-0">This product was deleted</p>';
        } else if (isVariantDeleted) {
            statusMessage = '<p class="text-danger mb-0">This variant was deleted</p>';
        }

        productItemHtml += `
            <tr 
                data-tot="${total}" 
                data-unreceived="${unreceived}" 
                data-po-item-id="${item.po_item_id}" 
                data-product-id="${item.product_uuid}" 
                data-variant-id="${item.variant_uuid}" 
                data-prev-accepted="${accepted}" 
                data-prev-rejected="${item.rejected || 0}"
            >

                <td>
                    <div class="received dtr-control">
                        ${imageHtml}
                        <div>
                            <a href="#" class="title">${productName}</a>
                            <p class="mb-0">${variantName}</p>
                            ${statusMessage}
                        </div>
                    </td>
                    <td>${item.supplier_sku || 'N/A'}</td>
                    <td>
                        <div class="sku-input">
                            <input class="form-control" default="0" placeholder="0" type="number" min="0" value="${accepted}" ${isProductDeleted || isVariantDeleted ? 'disabled' : ''}>
                        </div>
                    </td>
                    <td>
                        <div class="sku-input">
                            <input class="form-control" default="0" placeholder="0" type="number" min="0" value="${unreceived}" ${isProductDeleted || isVariantDeleted ? 'disabled' : ''}>
                        </div>
                    </td>
                    <td>
                        <div class="text-right">
                            <div class="progress">
                                <span class="received-progress" style="width: ${(accepted / total) * 100}%"></span>
                                <span class="rejected-progress" style="width: ${(unreceived / total) * 100}%"></span>
                            </div>
                            <div class="progress-count">
                                <p>
                                    <span><b>${accepted + unreceived}</b> of ${total}</span>
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16">
                                        <path fill-rule="evenodd" d="M4.24 6.2a.75.75 0 0 1 1.06.04l2.7 2.908 2.7-2.908a.75.75 0 0 1 1.1 1.02l-3.25 3.5a.75.75 0 0 1-1.1 0l-3.25-3.5a.75.75 0 0 1 .04-1.06"></path>
                                    </svg>
                                </p>
                                <div class="dropdown-menu dropdown-menu-right dropdown-menu-animated dropdown-menu-md">
                                    <div class="dropdown-item">
                                        <i class="prog green"></i>
                                        <span>Received</span>
                                        <span>${accepted}</span>
                                    </div>
                                    <div class="dropdown-item">
                                        <i class="prog red"></i>
                                        <span>Rejected</span>
                                        <span>${unreceived}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>`;
    });

    $tbody.html(productItemHtml);
    $('#variantOrdernumber').text(data.purchaseOrderItems.length);
    setHeaderProgress();
}
// Update row progress
function updateRowProgress($row) {
    const rec = parseInt($row.find('td:nth-child(3) .form-control').val()) || 0;
    const rej = parseInt($row.find('td:nth-child(4) .form-control').val()) || 0;
    const total = parseInt($row.data('tot')) || 0;

    $row.find('.progress .received-progress').css('width', `${(rec / total) * 100}%`);
    $row.find('.progress .rejected-progress').css('width', `${(rej / total) * 100}%`);
    $row.find('.progress-count p b').text(rec + rej);
    $row.find('.dropdown-item:first-child span:last-child').text(rec);
    $row.find('.dropdown-item:last-child span:last-child').text(rej);
}

// Update header progress
function setHeaderProgress() {
    let totalRec = 0;
    let totalRej = 0;
    let totalItems = 0;

    $('.receive-table tbody tr').each(function () {
        totalRec += parseInt($(this).find('td:nth-child(3) .form-control').val()) || 0;
        totalRej += parseInt($(this).find('td:nth-child(4) .form-control').val()) || 0;
        totalItems += parseInt($(this).data('tot')) || 0;
    });

    const $progWrap = $('.receive-card');
    $progWrap.find('.progress-count p span').text(`${totalRec + totalRej} of ${totalItems}`);
    $progWrap.find('.progress .received-progress').css('width', `${(totalRec / totalItems) * 100}%`);
    $progWrap.find('.progress .rejected-progress').css('width', `${(totalRej / totalItems) * 100}%`);
    $progWrap.find('.dropdown-menu .dropdown-item:first-child span:last-child').text(totalRec);
    $progWrap.find('.dropdown-menu .dropdown-item:last-child span:last-child').text(totalRej);
}

// Submit receiving data
function submitReceivingData() {
    const urlParams = new URLSearchParams(window.location.search);
    const id = urlParams.get('id');
    const items = [];

    let isError = false;

    $('.receive-table tbody tr').each(function () {
        const $row = $(this);
        const acceptQty = parseInt($row.find('td:nth-child(3) .form-control').val()) || 0;
        const rejectQty = parseInt($row.find('td:nth-child(4) .form-control').val()) || 0;
        const total = parseInt($row.data('tot')) || 0;
        const isDisabled = $row.find('td:nth-child(3) .form-control').prop('disabled');
        const prevAccepted = parseInt($row.data('prev-accepted')) || 0;
        const prevRejected = parseInt($row.data('prev-rejected')) || 0;
        const maxRemaining = total - (prevAccepted + prevRejected);


        // Validate negative values
        if (acceptQty < 0 || rejectQty < 0) {
            isError = true;
            showToast('Quantity cannot be negative for one or more items.', 'Error', 'error');
            return false; // Stop processing
        }

        if (!isDisabled && (acceptQty + rejectQty > total)) {
            isError = true;
            showToast('You are exceeding the total ordered quantity.', 'Error', 'error');
            return false;
        }

        items.push({
            po_item_id: $row.data('po-item-id'),
            product_id: $row.data('product-id'),
            variant_id: $row.data('variant-id'),
            sku: $row.find('td:nth-child(2)').text() || 'N/A',
            accept_qty: isDisabled ? 0 : acceptQty, // Set to 0 if disabled (product or variant deleted)
            reject_qty: isDisabled ? 0 : rejectQty // Set to 0 if disabled (product or variant deleted)
        });
    });

    if (isError) {
        return false; // Stop processing
    }

    $.ajax({
        url: `${ApiPlugin}/ecommerce/purchase-order/po-receving-add/${id}`,
        type: 'POST',
        contentType: 'application/json',
        dataType: 'json',
        data: JSON.stringify({ items }),
        beforeSend: function (xhr) {
            xhr.setRequestHeader('Authorization', 'Bearer ' + strkey);
            xhr.setRequestHeader('menu-uuid', menu_id);
            imgload.show();
        },
        success: function (response) {
            imgload.hide();
            if (response.status_code === 200) {
                setTimeout(() => {
                let page = 'view-purchase-order';
                window.location.assign('?P=' + page + '&M=' + menu_id + '&id=' + id);
                }, 1500);
                showToast(response.message || 'Receiving data submitted successfully.', 'Success', 'success');
                fetchPurchaseOrderReceiving(); // Refresh the table
            } else {
                showToast(response.message || 'Failed to submit receiving data.', 'Error', 'error');
            }
        },
        error: function (error) {
            imgload.hide();
            error = error.responseJSON || error;
            showToast(error.message || 'Internal Server Error.', 'Error', 'error');
        }
    });
}
