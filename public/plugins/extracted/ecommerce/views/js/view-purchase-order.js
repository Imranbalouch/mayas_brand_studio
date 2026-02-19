document.title = "Dashboard | View Purchase Order";
var urlParams = new URLSearchParams(window.location.search);
var id = urlParams.get('id');
$.ajax({
    url: ApiPlugin + "/ecommerce/purchase-order/edit/" + id,
    type: "GET",
    dataType: "json",
    beforeSend: function (xhr) {
        xhr.setRequestHeader("Authorization", "Bearer " + strkey);
        xhr.setRequestHeader("menu-uuid", menu_id);
        imgload.show();
    },
    success: function (response) {
    imgload.hide();
    if (response.status_code == 200) {
        let data = response.data;

        // Populate supplier (handle null case)
        $("#Viewsupplier").text(data.supplier?.company || "No supplier specified");

        $('#SupplierAddress').text(data.supplier?.address || "No address");
        $('#SupplierApartment').text(data.supplier?.apart_suite || "No apart suite");
        $('#SupplierCity').text(data.supplier?.city || "No city");
        $('#SupplierPostalCode').text(data.supplier?.postal_code || "No postal code");
        $('#SupplierContactName').text(data.supplier?.contact_name || "No contact name");
        $('#SupplierContactEmail').text(data.supplier?.email || "No contact email");
        $('#SupplierContactPhone').text(data.supplier?.phone_number || "No contact phone number");
        $('#SupplierCountry').text(data.supplier?.country || "No country");

        // Populate warehouse/destination (handle null case)
        $("#Shoplocation").text(data.warehouse?.location_name || "Shop location");

        // Populate status
        const status = data.status || "draft"; 
        $("#status_select").val(status).selectpicker("refresh");

        // Populate payment terms (handle null case)
        $("#paymentTerms").text(data.paymentterm?.name || "No payment terms specified");

        // Populate shipment details
        $("#Estimatedarrival").text(data.ship_date || "Not specified");
        $("#Shippingcarrier").text(data.shipcarrier?.name || "No carrier specified");
        $("#Trackingnumber").text(data.tracking_number || "Not specified");

        // Populate additional details
        $("#Referencenumber").text(data.reference_number || "Not specified");
        $("#Notesupplier").text(data.note_to_supplier || "No notes");
        $("#Tags").text(data.tags || "No tags");

        // Populate cost summary
        $("#Taxes").text('AED ' + data.total_tax || "0");
        $("#itemsNumber").text(data.purchaseOrderItems.length || "0");
        let subtotal = data.Subtotal || 0;
            if (!subtotal) {
                subtotal = data.purchaseOrderItems.reduce((sum, item) => {
                    // Calculate item total without tax first
                    const itemSubtotal = item.quantity * item.unit_price;
                    // Then add tax amount (which is percentage of itemSubtotal)
                    const itemTax = itemSubtotal * (item.tax / 100);
                    return sum + itemSubtotal + itemTax;
                }, 0);
            }
            $("#Subtotal").text('AED ' + subtotal.toFixed(2));
        $(".Shippingcost").text('AED ' + (parseFloat(data.total_shipping || 0).toFixed(2)));
        $("#Totalamount").text('AED ' + (parseFloat(data.total_amount || 0).toFixed(2)));

        // Get currency symbol
        const currencySymbol = data.currency?.symbol || "Rs";

        // Calculate total ordered, accepted, and rejected quantities
        let totalOrdered = 0;
        let totalAccepted = 0;
        let totalRejected = 0;

        data.purchaseOrderItems.forEach(function (item) {
            totalOrdered += item.quantity || 0;
            totalAccepted += item.accepted || 0;
            totalRejected += item.unreceived || 0;
        });
         if (totalAccepted >= totalOrdered) {
            $('.receive-inventory').hide();
        } else {
            $('.receive-inventory').show();
        }
        updateProgressBars(totalOrdered, totalAccepted, totalRejected);

        // Update the "Total received" section
        const totalReceivedText = `Total received ${totalAccepted + totalRejected} of ${totalOrdered}`;
        $(".view-head-dropdown span").first().text(totalReceivedText);
        $(".order-details-main .order-item-child .detail-num").first().text(totalAccepted);
        $(".order-details-main .order-item-child span").last().text(totalRejected);

        // Populate purchase order items
         let purchaseOrderItems = data.purchaseOrderItems;
        let productItemHtml = '';
        let productSvg = `<svg width="60" height="60" viewBox="0 0 42 42" fill="none" xmlns="http://www.w3.org/2000/svg">
            <rect x="1" y="1" width="40" height="40" rx="4" fill="white"/>
            <rect x="1" y="1" width="40" height="40" rx="4" stroke="#232323" stroke-opacity="0.1"/>
            <path d="M26.8333 13.5H15.1667C14.2462 13.5 13.5 14.2462 13.5 15.1667V26.8333C13.5 27.7538 14.2462 28.5 15.1667 28.5H26.8333C27.7538 28.5 28.5 27.7538 28.5 26.8333V15.1667C28.5 14.2462 27.7538 13.5 26.8333 13.5Z" stroke="#8D8D8D" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M18.0834 19.3334C18.7737 19.3334 19.3334 18.7737 19.3334 18.0834C19.3334 17.393 18.7737 16.8334 18.0834 16.8334C17.393 16.8334 16.8334 17.393 16.8334 18.0834C16.8334 18.7737 17.393 19.3334 18.0834 19.3334Z" stroke="#8D8D8D" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M28.5 23.5L24.3333 19.3334L15.1666 28.5" stroke="#8D8D8D" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>`;

        purchaseOrderItems.forEach(function (item, index) {
            const variantName = item.variant?.variant || 'No Variant';
            const itemSubtotal = item.quantity * item.unit_price;
            const itemTax = itemSubtotal * (item.tax / 100);
            const total = itemSubtotal + itemTax;
            const productName = item.product?.name || 'Product Not Found';
            const accepted = item.accepted || 0;
            const unreceived = item.unreceived || 0;
            const totalReceived = accepted + unreceived;
            const totalOrdered = item.quantity || 0;
            
            // Calculate progress percentages
            const acceptedPercent = totalOrdered > 0 ? (accepted / totalOrdered) * 100 : 0;
            const rejectedPercent = totalOrdered > 0 ? (unreceived / totalOrdered) * 100 : 0;

            let imageHtml = '';
            if (item.product?.thumbnail_img) {
                imageHtml = `<img src="${AssetsPath + item.product.thumbnail_img}">`;
            } else {
                imageHtml = productSvg;
            }

            productItemHtml += `
                <tr>
                    <td style="display: none;">
                        <input name="product_uuid" value="${item.product_id}" type="text" hidden="">
                        <input name="variant_uuid" value="${item.variant_id}" type="text" hidden="">
                    </td>
                    <td>
                        <div style="width: auto;" class="product-name">
                            ${imageHtml}
                            <div>
                                <span>${productName}</span>
                                <span class="varient-text">${variantName}</span>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="create-tab-text custom-total">${item.supplier_sku || 'N/A'}</span>
                    </td>
                    <td>
                        <div class="customize-border">
                            <span class="green-background" style="width: ${acceptedPercent}%"></span>
                            <span class="red-background" style="width: ${rejectedPercent}%"></span>
                        </div>
                        <div class="custom-dropdown-main">
                            <div class="droupdown-head open-inventory-droupdown view-head-dropdown">
                                <span>Total received ${totalReceived} of ${totalOrdered}</span>
                                <svg width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16">
                                    <path fill-rule="evenodd"
                                        d="M4.24 6.2a.75.75 0 0 1 1.06.04l2.7 2.908 2.7-2.908a.75.75 0 0 1 1.1 1.02l-3.25 3.5a.75.75 0 0 1-1.1 0l-3.25-3.5a.75.75 0 0 1 .04-1.06"
                                        fill="#6c757d"></path>
                                </svg>
                            </div>
                            <div class="droupdown-body right width-size">
                                <div class="order-details-main">
                                    <div class="order-item-child">
                                        <div class="order-item-child-one">
                                            <span class="dropdown-green-background"></span>
                                            <span class="detail-text">Accepted</span>
                                        </div>
                                        <span class="detail-num">${accepted}</span>
                                    </div>
                                    <div class="order-item-child">
                                        <div class="order-item-child-one">
                                            <span class="dropdown-red-background"></span>
                                            <span class="detail-text">Rejected</span>
                                        </div>
                                        <span>${unreceived}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="create-tab-text custom-total">${item.unit_price.toFixed(2)}</span>
                    </td>
                    <td>
                        <span class="create-tab-text custom-total">${item.tax}%</span>
                    </td>
                    <td>
                        <span class="create-tab-text custom-total">${total.toFixed(2)}</span>
                        <input class="item-total-amount" type="hidden" value="${total.toFixed(2)}">
                    </td>
                </tr>`;
        });

        $("#purchase-items-body").html(productItemHtml);

        // Update variant order number
        $("#variantOrdernumber").text(purchaseOrderItems.length);

        // Calculate totals
        calculatePOS({ target: $("#purchase-items-body tr:first")[0] });
    } else {
        showToast('Failed to load purchase order data.', 'Error', 'error');
    }
},
error: function (error) {
    imgload.hide();
    error = error.responseJSON || error;
    showToast(error.message || 'Internal Server Error.', 'Error', 'error');
}
});

function updateProgressBars(totalOrdered, totalAccepted, totalRejected) {
    const totalReceived = totalAccepted + totalRejected;
    
    // Update the header progress
    $(".view-head-dropdown span").first().text(`Total received ${totalReceived} of ${totalOrdered}`);
    $(".order-details-main .order-item-child .detail-num").first().text(totalAccepted);
    $(".order-details-main .order-item-child span").last().text(totalRejected);
    
    // Calculate percentages for the progress bars
    const acceptedPercent = totalOrdered > 0 ? (totalAccepted / totalOrdered) * 100 : 0;
    const rejectedPercent = totalOrdered > 0 ? (totalRejected / totalOrdered) * 100 : 0;
    
    // Update the progress bars in the header
    $(".customize-border .green-background").css("width", `${acceptedPercent}%`);
    $(".customize-border .red-background").css("width", `${rejectedPercent}%`);
}

$(document).on('click', '#delete-purchase', function(e) {
  e.preventDefault();
  
  const urlParams = new URLSearchParams(window.location.search);
  const purchaseorderId = urlParams.get('id'); // Consistent with edit_collection

  if (!purchaseorderId) {
    showToast('Purchase order ID not found', 'Error', 'error');
    return;
  }

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
        url: ApiPlugin + `/ecommerce/purchase-order/delete/${purchaseorderId}`,
        type: 'DELETE',
        headers: {
          'Authorization': 'Bearer ' + strkey,
          'menu-uuid': menu_id
        },
        beforeSend: function() {
          imgload.show(); 
        },
        success: function(response) {
          imgload.hide();
          if (response.status_code === 200) {
            showToast(response.message, 'Success', 'success');
            setTimeout(() => {
              let page = 'purchase-order';
              window.location.assign('?P=' + page + '&M=' + menu_id);
            }, 1500);
          } else {
            showToast(response.message || 'Failed to delete purchase order', 'Error', 'error');
          }
        },
        error: function(xhr) {
          imgload.hide();
          let errorMsg = 'Failed to delete purchase order';
          if (xhr.responseJSON && xhr.responseJSON.message) {
            errorMsg = xhr.responseJSON.message;
          }
          showToast(errorMsg, 'Error', 'error');
        }
      });
    }
  });
});


$(document).ready(function () {
    // Define status options
    const statusOptions = [
        { value: 'draft', text: 'Draft' },
        { value: 'complete', text: 'Complete' },
    ];

    // Populate select element dynamically
    $('#status_select').empty().append('<option disabled selected hidden>Select Status</option>');
    statusOptions.forEach(option => {
        $('#status_select').append(
            $('<option>', {
                value: option.value,
                text: option.text
            })
        );
    });

    // On status change, update purchase order status
    $("#status_select").on("change", function () {
        const newStatus = $(this).val();

        if (!id) {
            showToast('Purchase order ID not found', 'Error', 'error');
            return;
        }

        imgload.show(); // Show loading indicator

        $.ajax({
            url: ApiPlugin + `/ecommerce/purchase-order/status/${id}`,
            type: "POST",
            contentType: "application/json",
            data: JSON.stringify({ status: newStatus }),
            beforeSend: function (xhr) {
                xhr.setRequestHeader("Authorization", "Bearer " + strkey);
                xhr.setRequestHeader("menu-uuid", menu_id);
            },
            success: function (response) {
                imgload.hide();
                if (response.status_code === 200) {
                    location.reload();
                    showToast('Status updated successfully', 'Success', 'success');
                } else {
                    showToast(response.message || 'Failed to update status', 'Error', 'error');
                }
            },
            error: function (xhr) {
                imgload.hide();
                let errorMsg = 'Failed to update status';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                }
                showToast(errorMsg, 'Error', 'error');
            }
        });
    });

    $('.receive-inventory').on('click', function (e) {
    e.preventDefault();
    const urlParams = new URLSearchParams(window.location.search);
    const id = urlParams.get('id');
    
    if (!id) {
        showToast('Purchase order ID not found in URL.', 'Error', 'error');
        return;
    }
    
    // Redirect to receiving page with ID parameter
    page = "receive-item";
    window.location.assign(`?P=${page}&M=${menu_id}&id=${id}`);
});
    // table-first-item product-

    $("#duplicateTextarea").on("input", function () {
        var currentLength = $(this).val().length; // Current character count
        $("#current").text(currentLength); // Update count display

        if (currentLength >= 5000) {
            $(this).val($(this).val().substring(0, 5000)); // Prevent exceeding limit
        }
    });
    // Onload();


    $(document).on("change", ".table-first-item .table-row.parent input[type='checkbox']", function () {
        let isChecked = $(this).prop("checked");

        // Check/uncheck all child checkboxes based on parent
        $(this).closest('.table-first-item').find(".table-row.child input[type='checkbox']").prop("checked", isChecked);
    });

    // Child Checkbox Change Event
    $(document).on("change", ".table-first-item .table-row.child input[type='checkbox']", function () {
        let parentCheckbox = $(this).closest('.table-first-item').find(".table-row.parent input[type='checkbox']");
        let allChildren = $(this).closest('.table-first-item').find(".table-row.child input[type='checkbox']");

        // Check if any child is checked
        let anyChildChecked = allChildren.is(":checked");

        // Set parent checkbox based on child checkboxes
        parentCheckbox.prop("checked", anyChildChecked);
    });

    $(".select-dropdown .droupdown-head").on("click", function (e) {
        e.preventDefault();
        $(".select-dropdown").not($(this).parent()).removeClass("show");
        $(this).parent().toggleClass("show");
    });

    $(document).on("click", function (event) {
        if (!$(event.target).closest(".select-dropdown").length) {
            $(".select-dropdown").removeClass("show");
        }
    });

    // **Dropdown Selection for Location**
    $("#select_location").on("click", "li", function () {
        var selectedText = $(this).text().trim();
        let selectedUUID = $(this).find('input').attr("data-uuid");
        $("#Selectlocation").attr("data-uuid", selectedUUID);
        $("#Selectlocation").text(selectedText);
        $(".select-dropdown").removeClass("show");
    });

    // **Dropdown Selection for Supplier**
    $("#select_supplier").on("click", "li", function () {
        let selectedText = $(this).text().trim();
        let selectedUUID = $(this).find('input').attr("data-uuid");
        $("#Selectsupplier").attr("data-uuid", selectedUUID);
        $("#Selectsupplier").text(selectedText);
        $(".select-dropdown").removeClass("show");
    });

    // **Close dropdown if clicked outside**
    $(document).on("click", function (e) {
        if (!$(e.target).closest(".select-dropdown").length) {
            $(".select-dropdown").removeClass("show");
        }
    });

    select_supplier();
    select_location();
    payment_terms();
    supplier_currency();
    shipping_carrier();


});


let subtotalPO = 0;
let itemTotal = 0;
let taxIncluded = 0;
const shippingFee = 100; // Fixed Shipping Fee

function calculatePOS(e) {
    $('.droupdown-head').off('click').on('click', function () {
        $(this).siblings('.droupdown-body').toggle();
    });
    let getParentsRow = $(e).closest('tr');

    let customQTY = parseFloat(getParentsRow.find('.custom-qty').val()) || 0;
    let customCOST = parseFloat(getParentsRow.find('.custom-cost').val()) || 0;
    let customTAX = parseFloat(getParentsRow.find('.custom-tax').val()) || 0;

    // **Calculate Tax Per Row**
    let getTax = (customQTY * customCOST) * (customTAX / 100);
    let finalCal = Math.round((customQTY * customCOST) + getTax);
    finalCal = finalCal > 0 ? finalCal : 0.00;

    // **Update Individual Row Total**
    let totalField = getParentsRow.find('.custom-total');
    let totalFieldInput = getParentsRow.find('.item-total-amount');
    if (totalField.is('input')) {
        totalField.val(finalCal);
    } else {
        totalFieldInput.val(finalCal);
        totalField.text("AED " + finalCal);
    }

    // **Reset Totals Before Summing**
    subtotalPO = 0;
    itemTotal = 0;
    taxIncluded = 0;

    // **Loop Through All Rows to Sum Values**
    $(".custom-total").each(function () {
        let rowTotal = parseFloat($(this).text().replace("AED ", "")) || 0;
        subtotalPO += rowTotal;
    });

    $(".custom-qty").each(function () {
        let qty = parseFloat($(this).val()) || 0;
        itemTotal += qty;
    });

    $(".custom-qty").each(function () {
        let row = $(this).closest('tr');
        let qty = parseFloat(row.find('.custom-qty').val()) || 0;
        let cost = parseFloat(row.find('.custom-cost').val()) || 0;
        let tax = parseFloat(row.find('.custom-tax').val()) || 0;

        let rowTax = (qty * cost) * (tax / 100);
        taxIncluded += rowTax;
    });

    // **Calculate Grand Total (Subtotal + Shipping)**
    let grandTotal = subtotalPO + shippingFee;

    // **Update the UI**
    $("#po-subtotal").text("AED " + subtotalPO);
    $("#item-total").text(itemTotal);
    $("#tax-included").text("AED " + Math.round(taxIncluded));
    $("#tax-included-input").val(taxIncluded);
    $("#shipping-fee").text("AED " + shippingFee);
    $("#grand-total-input").val(grandTotal);
    $("#grand-total").text("AED " + grandTotal);
}



function addSelectInput() {
    var html =
        `<div class="row align-items-center mb-4">
            <div class="col-5">
                <div class="form-group form-input">
                    <select name="" class="form-control selectpicker select2" id="">
                        <option value="" selected>Select supplier</option>
                        <option value="">Cash and James Co</option>
                        <option value="">Dummy Text 1</option>
                        <option value="">Dummy Text 2</option>
                    </select>
                </div>
            </div>
            <div class="col-5">
                <div class="form-group form-input">
                    <input type="text" class="form-control" placeholder="AED 0.00" >
                </div>
            </div>
            <div class="col-2">
                <div class="">
                    <a onclick="this.parentNode.parentNode.parentNode.remove()" >
                        <svg width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16"><path d="M10.72 11.78a.75.75 0 1 0 1.06-1.06l-2.72-2.72 2.72-2.72a.75.75 0 0 0-1.06-1.06l-2.72 2.72-2.72-2.72a.75.75 0 0 0-1.06 1.06l2.72 2.72-2.72 2.72a.75.75 0 1 0 1.06 1.06l2.72-2.72z" fill="#9C845E" ></path></svg>
                    </a>
                </div>
            </div>
        </div>
`;
    document.getElementById('add-adjustment').insertAdjacentHTML("afterbegin", html);

    const selectPicker = $('.selectpicker');
    if (selectPicker.length) {
        selectPicker.selectpicker();
        handleBootstrapSelectEvents();
    }
}

function supplier_currency() {
    $.ajax({
        url: ApiPlugin + '/ecommerce/get_active_currencies',
        type: "GET",
        contentType: "application/json",
        dataType: "json",
        beforeSend: function (request) {
            request.setRequestHeader("Authorization", 'Bearer ' + strkey);
        },
        success: function (response) {
            if (response.status_code === 200) {
                const parentDropdownCurrency = $('#supplier_currency');
                parentDropdownCurrency.empty(); // Clear existing options

                response.data.forEach(currency => {
                    // console.log(response.data);
                    let spaces = '-'.repeat(currency.level);
                    parentDropdownCurrency.append(
                        `<option value="${currency.uuid}">${spaces} ${currency.name}</option>`
                    );
                });
                parentDropdownCurrency.selectpicker("refresh");
            } else {
                console.error('Unexpected response:', response);
            }
        },
        error: function (xhr, status, error) {
            console.error('Error fetching categories:', status, error);
        }
    });
}

function payment_terms() {
    $.ajax({
        url: ApiPlugin + '/ecommerce/get_active_paymentterm',
        type: "GET",
        contentType: "application/json",
        dataType: "json",
        beforeSend: function (request) {
            request.setRequestHeader("Authorization", 'Bearer ' + strkey);
        },
        success: function (response) {
            if (response.status_code === 200) {
                const parentDropdownPayment = $('#payment_terms');
                parentDropdownPayment.empty(); // Clear existing options

                // Default option add karna
                parentDropdownPayment.append('<option value="" selected>Select payment (optional)</option>');

                response.data.forEach(payment => {
                    let spaces = ''.repeat(payment.level);
                    parentDropdownPayment.append(
                        `<option value="${payment.uuid}">${spaces} ${payment.name}</option>`
                    );
                });

                parentDropdownPayment.selectpicker("refresh");
            } else {
                console.error('Unexpected response:', response);
            }
        },
        error: function (xhr, status, error) {
            console.error('Error fetching categories:', status, error);
        }
    });
}

function shipping_carrier() {
    $.ajax({
        url: ApiPlugin + '/ecommerce/get_active_carriers',
        type: "GET",
        contentType: "application/json",
        dataType: "json",
        beforeSend: function (request) {
            request.setRequestHeader("Authorization", 'Bearer ' + strkey);
        },
        success: function (response) {
            if (response.status_code === 200) {
                const parentDropdownCarrier = $('#shipping_carrier');
                parentDropdownCarrier.empty(); // Clear existing options

                // Default option add karna
                parentDropdownCarrier.append('<option value="" selected>Select shipping carrier</option>');

                response.data.forEach(carrier => {
                    let spaces = ''.repeat(carrier.level);
                    parentDropdownCarrier.append(
                        `<option value="${carrier.uuid}">${spaces} ${carrier.name}</option>`
                    );
                });

                parentDropdownCarrier.selectpicker("refresh");
            } else {
                console.error('Unexpected response:', response);
            }
        },
        error: function (xhr, status, error) {
            console.error('Error fetching categories:', status, error);
        }
    });
}

function select_supplier() {
    $.ajax({
        url: ApiPlugin + '/ecommerce/get_active_supplier', // API end point
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

            let data = response.data;
            console.log(response.data);

            let htmlLi = ``;
            for (const key in data) {
                let item = data[key];
                htmlLi += ` <li>
                                <label class="custom-checkbox">
                                    <input style="display:none;" name="market-radio" data-uuid="${item.uuid}" value="${item.company}" type="checkbox">
                                    ${item.company}
                                </label>
                            </li>`

            }
            $("#select_supplier").append(htmlLi);
        }
    });
}

function select_location() {
    $.ajax({
        url: ApiPlugin + '/ecommerce/warehouse/get_active_warehouse_locations', // API end point
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

            let data = response.data;
            console.log(response.data);

            let htmlLi = ``;
            for (const key in data) {
                let item = data[key];
                htmlLi += ` <li>
                                <label class="custom-checkbox">
                                    <input style="display:none;" name="market-radio" data-uuid="${item.uuid}" value="${item.location_name}" type="checkbox">
                                    ${item.location_name}
                                </label>
                            </li>`

            }
            $("#select_location").append(htmlLi);
        }
    });
}

$("#ProductItem").on("click", function () {
    table_product();
})

function table_product() {
    $.ajax({
        url: ApiPlugin + '/ecommerce/product/get_active_products', // API endpoint
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
            console.log(response.data);

            let placeholderSVG = `<svg width="60" height="60" viewBox="0 0 42 42" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <rect x="1" y="1" width="40" height="40" rx="4" fill="white"/>
                                    <rect x="1" y="1" width="40" height="40" rx="4" stroke="#232323" stroke-opacity="0.1"/>
                                    <path d="M26.8333 13.5H15.1667C14.2462 13.5 13.5 14.2462 13.5 15.1667V26.8333C13.5 27.7538 14.2462 28.5 15.1667 28.5H26.8333C27.7538 28.5 28.5 27.7538 28.5 26.8333V15.1667C28.5 14.2462 27.7538 13.5 26.8333 13.5Z" stroke="#8D8D8D" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M18.0834 19.3334C18.7737 19.3334 19.3334 18.7737 19.3334 18.0834C19.3334 17.393 18.7737 16.8334 18.0834 16.8334C17.393 16.8334 16.8334 17.393 16.8334 18.0834C16.8334 18.7737 17.393 19.3334 18.0834 19.3334Z" stroke="#8D8D8D" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M28.5 23.5L24.3333 19.3334L15.1666 28.5" stroke="#8D8D8D" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
`;

            let htmlLi = ``;
            for (const key in data) {
                let item = data[key];

                let parentImage = item.thumbnail_img ? `<img src="${AssetsPath + item.thumbnail_img}" alt="Image" class="size-50px img-fit">` : placeholderSVG;

                htmlLi += ` <div class="table-first-item product-${item.uuid}">
                                <div class="table-row parent">
                                    <div class="custom-checkbox-main form-group d-inline-block">
                                        <label class="custom-checkbox">
                                            <input type="checkbox" name="product_check">
                                            <span class="aiz-square-check"></span>
                                        </label>
                                    </div>
                                    <div class="product-name" data-product-uuid="${item.uuid}">
                                        ${parentImage}
                                        <span>${item.name}</span>
                                    </div>
                                    <div class="total-name">
                                        <span>${item.current_stock}</span>
                                    </div>
                                </div>`;

                if (item.product_stocks && item.product_stocks.length > 0) {
                    htmlLi += item.product_stocks.map((stockItem) => {
                        let childImage = stockItem.image ? `<img src="${AssetsPath + stockItem.image}" alt="Image" class="size-50px img-fit">` : placeholderSVG;

                        return `
                        <div class="table-row child left-side-item productStock-${stockItem.uuid}">
                            <div class="custom-checkbox-main form-group d-inline-block">
                                <label class="custom-checkbox">
                                    <input type="checkbox" name="product_check">
                                    <span class="aiz-square-check"></span>
                                </label>
                            </div>
                            <div class="product-name" data-product-uuid="${item.uuid}" data-variant-uuid="${stockItem.uuid}">
                                ${childImage}
                                <span>${stockItem.sku}</span>
                            </div>
                            <div class="total-name">
                                <span>${stockItem.qty}</span>
                            </div>
                        </div>`;
                    }).join('');
                }

                htmlLi += `</div>`;
            }

            $("#table_product").append(htmlLi);
        }
    });
}

// intlTelInput JS
let iti
var input = document.querySelector("#phone");
if (input) {
    iti = window.intlTelInput(input, {
        initialCountry: "ae",
        preferredCountries: ['ae'],
        autoPlaceholder: "polite",
        showSelectedDialCode: true,
        utilsScript: "assets/plugins/intel-input/utils.js",
        hiddenInput: () => ({ phone: "full_phone" })
    });
    input.addEventListener("blur", function () {
        if (input.value.trim()) {
            if (iti.isValidNumber()) {
                input.parentElement.parentElement.classList.remove("error");
                input.parentElement.parentElement.querySelector(".error-txt").innerHTML = "";
            } else {
                input.parentElement.parentElement.classList.add("error");
                input.parentElement.parentElement.querySelector(".error-txt").innerHTML = "Invalid Number";
            }
        }
        if (input.value === "") {
            input.parentElement.parentElement.classList.remove("error");
            input.parentElement.parentElement.querySelector(".error-txt").innerHTML = "";
        }
    });
}
// intlTelInput JS End
// alert();
document.getElementById("SupplierForm").addEventListener("submit", function (e) {
    e.preventDefault();
    // alert();
    const company = this.querySelector("[name='company']");

    let status = true;

    if (company.value == "") {
        company.parentElement.classList.add("error");
        company.parentElement.querySelector(".error-txt").innerHTML = "Company can't be blank";
        status = false;
    }

    if (status) {
        alert("Form Submited");

    }
});

document.querySelectorAll("#SupplierForm input").forEach(function (input) {
    input.addEventListener("input", function () {
        input.parentElement.classList.remove("error");
        input.parentElement.parentElement.querySelector(".error-txt").innerHTML = "";
        input.parentElement.querySelector(".error-txt").innerHTML = "";
    });
});

$(".create-new-supplier").on("click", function () {
    country_select();
})

function country_select() {
    $.ajax({
        url: ApiPlugin + '/ecommerce/get_active_countries',
        type: "GET",
        contentType: "application/json",
        dataType: "json",
        beforeSend: function (request) {
            request.setRequestHeader("Authorization", 'Bearer ' + strkey);
        },
        success: function (response) {
            if (response.status_code === 200) {
                const parentDropdownCountry = $('#country_select');
                parentDropdownCountry.empty(); // Clear existing options

                // Default option add karna
                parentDropdownCountry.append('');

                response.data.forEach(country => {
                    let spaces = ''.repeat(country.level);
                    parentDropdownCountry.append(
                        `<option value="${country.uuid}">${spaces} ${country.name}</option>`
                    );
                });

                parentDropdownCountry.selectpicker("refresh");
            } else {
                console.error('Unexpected response:', response);
            }
        },
        error: function (xhr, status, error) {
            console.error('Error fetching categories:', status, error);
        }
    });
}

