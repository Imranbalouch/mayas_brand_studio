document.title = "Dashboard | Edit Order";
var urlParams = new URLSearchParams(window.location.search);
var uuid = urlParams.get('I');
var edit_data = '';
if (uuid) {

    get_customers(); 
    get_locations();
    get_active_markets();
    shipping_carrier();
    $.ajax({
        url: ApiPlugin + "/ecommerce/order/edit_order/" + uuid,
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
                 edit_data = response.data;
                 let data = response.data;
                $("#order_code").html("#"+data.code);
                $("#order_date").html(formatedDate(data.created_at));
                
                if(data.mark_as_paid == 0){
                    $("#order_payment_status").html('Payment pending');
                } else {
                    $("#order_payment_status").html('Paid');
                }

                if(data.fulfilled_status == 0){
                    $("#order_fulfilled_status").html('Unfulfilled');
                } else {
                    $("#order_fulfilled_status").html('Fulfilled');
                }

                if(data.payment_method == 'cash_on_delivery'){
                    $("#order_payment_method").html('Cash on Delivery');
                } else if(data.payment_method == 'stcpay') {
                    $("#order_payment_method").html('STC Pay');
                } else if(data.payment_method == 'card') {
                    $("#order_payment_method").html('Card');
                }

                // Delivery status logic - only show if order is fulfilled
                if(data.fulfilled_status == 1 && data.delivery_status) {
                    // Remove underscores and capitalize first letter of each word
                    let deliveryText = data.delivery_status.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                    $("#order_delivery_status").html(deliveryText).show();
                } else {
                    $("#order_delivery_status").hide();
                }

                                   
                //download invoice
                if (data.customer_id && data.customer_id !== '' && (!data.auth_id || data.auth_id === '')) {
                    $('#downloadInvoiceBtn').removeClass('d-none').show();
                } else {
                    $('#downloadInvoiceBtn').hide();
                }



                if(data.billing_first_name!='' && data.billing_first_name!=null){
                     if(data.customer){
                         $("#customer_name").html(data.customer.first_name+' '+data.customer.last_name);
                         $("#customer_email").html(data.customer.email);
                         $("#customer_phone").html(data.customer.phone);
                     }else{
                        $("#customer_name").html(data.billing_first_name+' '+data.billing_last_name);
                        $("#customer_email").html(data.billing_email);
                        $("#customer_phone").html(data.billing_phone);
                     }
                }else{
                    $("#customer_name").html('-');
                }

                if(data.shipping_address!='' && data.shipping_address!=null){
                    $("#customer_shipping_name").html(data.shipping_first_name+' '+data.shipping_last_name);
                    $("#customer_shipping_phone").html(data.shipping_phone);
                    $("#customer_shipping_email").html(data.shipping_email);
                    $("#customer_shipping_address").html(data.shipping_address);
                    $("#customer_shipping_city").html(data.shipping_city);
                    $("#customer_shipping_state").html(data.shipping_state);
                    $("#customer_shipping_country").html(data.shipping_country);
                }else{
                    $("#customer_shipping_address").html('No shipping address provided');
                }
               // console.log('data.shipping_address',data.shipping_address);
                if(data.billing_address!='' && data.billing_address!=null){
                    $("#customer_billing_name").html(data.billing_first_name+' '+data.billing_last_name);
                    $("#customer_billing_phone").html(data.billing_phone);
                    $("#customer_billing_email").html(data.billing_email);
                    $("#customer_billing_address").html(data.billing_address);
                    $("#customer_billing_city").html(data.billing_city);
                    $("#customer_billing_state").html(data.billing_state);
                    $("#customer_billing_country").html(data.billing_country);
                }else{
                    $("#customer_billing_address").html('No billing address provided');
                }

                $("#note").html(data.notes);
                //$("#tags").val(data.tags);

                $("#discount_type").val(data.discount_type);
                // $("#discount_value").val(data.discount_value);
                $("#discount_value").val(data.discount_amount);
                console.log(data.discount_amount);

                $("#shipping_name").val(data.shipping_type);
                $("#shipping_charges").val(data.shipping_price);

                $("#grand_total").val(data.grand_total);
                $("#total_coupon_amount").val(data.total_coupon_amount);
                $("#total_vat").val(data.total_vat + data.shipping_vat_amount);

                $('#customer_id').selectpicker('destroy');
                $("#customer_id").val(data.customer_id);
                $("#customer_id").selectpicker();


                $('#location_id').selectpicker('destroy');
                $("#location_id").val(data.location_id);
                $("#location_id").selectpicker();


                $('#market_id').selectpicker('destroy');
                $("#market_id").val(data.market_id);
                $("#market_id").selectpicker();

                console.log(data);
                time_line='';
                $.each(data.time_line, function(index, item) {
                  //  console.log(item.message);
                    const isoDate = item.created_at;
                    const date = new Date(isoDate); 
                    const day = String(date.getDate()).padStart(2, '0');
                    const month = date.toLocaleString('default', { month: 'short' });
                    const year = date.getFullYear(); 
                    const formattedDate = `${day}-${month}-${year}`;
                    const cleanedMessage = item.message.replace(/_/g, ' ');
                    time_line += `<li>${cleanedMessage} <span style="float: inline-end;">${formattedDate}</span></li>`;
                });
                $('#time_line').html('');
                $('#time_line').html(time_line);
                productArray=data.order_details;

                if (data.delivery_status === 'delivered' || data.delivery_status === 'cancelled') {
                $('.tracking_status_group .btn').prop('disabled', true);
                } else {
                    $('.tracking_status_group .btn').prop('disabled', false);
                }

                if(data.mark_as_paid==0){
                    $('.mark_as_paid').removeClass('d-none');
                    $('.fullfill_items').addClass('d-none');
                    $('.add_tracking').addClass('d-none');
                    $('.tracking_status_group').addClass('d-none');
                }else{
                    $('.mark_as_paid').addClass('d-none');
                    $('.fullfill_items').removeClass('d-none');
                    $('.add_tracking').addClass('d-none');
                    $('.tracking_status_group').addClass('d-none');
                }

                if(data.mark_as_paid==1 && data.fulfilled_status==0){
                    $('.fullfill_items').removeClass('d-none');
                    $('.add_tracking').addClass('d-none');
                    $('.tracking_status_group').addClass('d-none');
                }else if(data.mark_as_paid==1 && data.fulfilled_status==1){
                    $('.fullfill_items').addClass('d-none');
                    if(data.delivery_status === 'Tracking added') {
                        $('.add_tracking').addClass('d-none');
                        $('.tracking_status_group').removeClass('d-none');
                    } else if(data.tracking_status==1) {
                        $('.tracking_status_group').removeClass('d-none');
                    } else {
                        $('.add_tracking').removeClass('d-none');
                    }
                }
                if(data.tracking_status==1){
                     $('.add_tracking').addClass('d-none');
                }
                $("#product_table_one").removeClass("d-none");
                    let productTable = ``;
                    productArray.forEach(element => {
                   // console.log("element", element);

                        productTable += `<tr>
                                            <td style="display: none;">
                                                <input name="product_uuid" value="${element.product_uuid}" type="text" hidden>
                                                <input name="variant_uuid" value="${element.variant_id}" type="text" hidden>
                                                <input name="variant_price" class="product-price" value="${element.product_price}" type="text" hidden>
                                            </td>
                                        
                                            <!-- <td style="width: 100px;"><input type="checkbox" class="form-check-input"></td> -->
                                            <td style="width: 100px;">
                                                <div style="width: auto;" class="product-name">
                                                        ${element.image ? `<img src="${element.image}" alt="Image" class="size-50px img-fit"       `
                                                            : `<svg width='60' height='60' viewBox='0 0 42 42' fill='none' xmlns='http://www.w3.org/2000/svg'><rect x='1' y='1' width='40' height='40' rx='4' fill='white'/><rect x='1' y='1' width='40' height='40' rx='4' stroke='#232323' stroke-opacity='0.1'/><path d='M26.8333 13.5H15.1667C14.2462 13.5 13.5 14.2462 13.5 15.1667V26.8333C13.5 27.7538 14.2462 28.5 15.1667 28.5H26.8333C27.7538 28.5 28.5 27.7538 28.5 26.8333V15.1667C28.5 14.2462 27.7538 13.5 26.8333 13.5Z' stroke='#8D8D8D' stroke-linecap='round' stroke-linejoin='round'/><path d='M18.0834 19.3334C18.7737 19.3334 19.3334 18.7737 19.3334 18.0834C19.3334 17.393 18.7737 16.8334 18.0834 16.8334C17.393 16.8334 16.8334 17.393 16.8334 18.0834C16.8334 18.7737 17.393 19.3334 18.0834 19.3334Z' stroke='#8D8D8D' stroke-linecap='round' stroke-linejoin='round'/><path d='M28.5 23.5L24.3333 19.3334L15.1666 28.5' stroke='#8D8D8D' stroke-linecap='round' stroke-linejoin='round'/></svg>`
                                                            }
                                                    <div class="midle-name">
                                                        <span>${element.product_name}</span>
                                                        ${element.variant ? `<span class="varient-text">${element.variant}</span>` : ''}
                                                    </div>
                                                </div>
                                            </td> 
                                            <td>
                                                <div class="sku-input">
                                                 <div class="custom-input-wrapper">
                                                    <input type="number" name="input-quantity" data-min="1" data-max="${element.product_qty}" oninput="calculateSummary(this)" value="${element.product_qty}" style="border: 0px;" class="form-control custom-qty" readonly="readonly">
                                                
                                                </div>
                                                     
                                            </td>
                                            <!--<td>
                                                <div class="sku-input custom-connected">
                                                 <div class="custom-input-wrapper">
                                                    <input type="number" name="input-quantity" data-min="1" data-max="${element.product_qty}" oninput="calculateSummary(this)" value="${element.product_qty}" class="form-control custom-qty quantityInput">
                                                <span class="suffix">of ${element.product_qty}</span>
                                                </div>
                                                    <div class="stepper">
                                                    <span  class="stepUp">▲</span>
                                                    <span  class="stepDown">▼</span>
                                                </div> 
                                            </td> -->
                                            <td>
                                                <span class="create-tab-text custom-total">AED ${element.product_price}</span>
                                                <input type="hidden" class="item-total-amount" >
                                            </td> 
                                        </tr>`;
                    });
                    let totalQuantity = productArray.reduce((total, element) => {
                        return total + parseInt(element.product_qty || 0);
                    }, 0);
                    $("#variantOrdernumber").html(totalQuantity + ' items');
                    $("#product_table_one").removeClass("d-none");
                    $("#product_table_one tbody").empty();
                    $("#product_table_one tbody").append(productTable);
                     
                    calculateSummary();

                filemanagerImagepreview();

            }
        },
        error: function (error) {
            error = error.responseJSON;
            if (error.status_code == 404) {
                //showToast(error.message, 'Error', 'error', '?P=brand&M=' + menu_id + '&id=' + id);
            } else {
                //showToast('Internal Server Error.', 'Error', 'error', '?P=brand&M=' + menu_id + '&id=' + id);
            }
        }
    });
}
 
$(document).on('click', '.update-status', function() {
    const status = $(this).data('status');
    updateDeliveryStatus(status);
});

$(document).on('click', '#downloadInvoiceBtn', function() {
    downloadOrderInvoice();
});

function updateDeliveryStatus(status) {
    let Data = {
        "delivery_status": status
    }

    $.ajax({
        url: ApiPlugin + '/ecommerce/order/update_delivery_status/' + uuid,  
        type: "POST",         
        data: JSON.stringify(Data),  
        contentType: "application/json",
        dataType: "json",
        headers: { "Authorization": "Bearer " + strkey, 'menu-uuid': menu_id, 'uuid': uuid },
        beforeSend: function (request) {
            request.setRequestHeader("Authorization", 'Bearer ' + strkey);
            imgload.show();
        },
        success: function (response) {
            imgload.hide();
            if (response.status_code == 200) {
                showToast('Delivery status updated successfully', 'Success', 'success');
                // Update the UI to reflect the new status
                $("#order_delivery_status").html(status.replace('_', ' ').capitalize()).show();
                // You might want to reload the page or update other UI elements
                location.reload();
            } else { 
                showToast(response.message, 'Error', 'error');
            }
        },
        error: function (xhr, status, error) {
            imgload.hide();
            console.error("AJAX Error: ", error);
            showToast('Error updating delivery status', 'Error', 'error');
        }
    });
}

// Helper function to capitalize words
String.prototype.capitalize = function() {
    return this.replace(/(?:^|\s)\S/g, function(a) { return a.toUpperCase(); });
};

function downloadOrderInvoice() {
    if (!uuid) {
        showToast('Order ID not found', 'Error', 'error');
        return;
    }
    
    imgload.show();
    
    // Create a temporary link element
    const link = document.createElement('a');
    link.href = ApiPlugin + '/ecommerce/order/download-invoice/' + uuid;
    link.download = 'invoice-' + edit_data.code + '.pdf';
    
    // Set authorization headers by opening in new window with token
    // Since we can't set headers on link download, we'll use fetch
    fetch(ApiPlugin + '/ecommerce/order/download-invoice/' + uuid, {
        method: 'GET',
        headers: {
            'Authorization': 'Bearer ' + strkey,
            'menu-uuid': menu_id
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Failed to download invoice');
        }
        return response.blob();
    })
    .then(blob => {
        // Create blob link to download
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.style.display = 'none';
        a.href = url;
        a.download = 'invoice-' + edit_data.code + '.pdf';
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
        document.body.removeChild(a);
        
        imgload.hide();
        showToast('Invoice downloaded successfully', 'Success', 'success');
    })
    .catch(error => {
        imgload.hide();
        console.error('Download error:', error);
        showToast('Failed to download invoice', 'Error', 'error');
    });
}

function get_customers() {
    $.ajax({
        url: ApiPlugin + '/ecommerce/customer/active_customers', // API end point
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

            if (response.status_code === 200) {
                const parentDropdownCarrier = $('#customer_id');
                parentDropdownCarrier.empty();  
                response.data.forEach(customers => { 
                    //console.log("customers",customers.customer.uuid);
                    parentDropdownCarrier.append(
                        `<option value="${customers.customer.uuid}"> ${customers.customer.name} </option>`
                    );
                });

                parentDropdownCarrier.selectpicker("refresh");
            } else {
                console.error('Unexpected response:', response);
            }
        }
    });
}
  
function get_locations() {
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

            if (response.status_code === 200) {
                const parentDropdownCarrier = $('#location_id');
                parentDropdownCarrier.empty(); 
               // parentDropdownCarrier.append('<option value="" selected>Select shipping carrier</option>'); 
                response.data.forEach(location => { 
                    parentDropdownCarrier.append(
                        `<option value="${location.uuid}">${location.location_name}</option>`
                    );
                });

                parentDropdownCarrier.selectpicker("refresh");
            } else {
                console.error('Unexpected response:', response);
            }
        }
    });
}
function get_active_markets() {
    $.ajax({
      url: ApiPlugin + '/ecommerce/market/get_active_markets',
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

            if (response.status_code === 200) {
                const parentDropdownCarrier = $('#market_id');
                parentDropdownCarrier.empty();  
                response.data.forEach(markets => { 
                   // console.log("markets",markets);
                    parentDropdownCarrier.append(
                        `<option value="${markets.uuid}"> ${markets.market_name} </option>`
                    );
                });

                parentDropdownCarrier.selectpicker("refresh");
            } else {
                console.error('Unexpected response:', response);
            }
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
              //  parentDropdownCarrier.append('<option value="" selected>Select shipping carrier</option>');

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




let subtotalOrder = 0;
let itemTotal = 0;
let taxIncluded = 0;
const shippingFee = 100; // Fixed Shipping Fee

function calculateSummary(e = null) {
    let rows = $("#Table_View tbody tr"); 
    let subtotalOrder = 0;
    let itemTotal = 0;
    let taxIncluded = 0;
  //  let shippingFee = parseFloat($("#shipping-fee").text().replace("AED ", "")) || 0;
   // console.log(rows);
    rows.each(function () {
        let row = $(this);
        let qty = parseFloat(row.find('.custom-qty').val()) || 0;
        let price = parseFloat(row.find('.product-price').val()) || 0;
        let tax = parseFloat(row.find('.custom-tax').val()) || 0;
       // console.log(row,qty,price,tax);
        // Calculate tax and total per row
        let rowTax = (qty * price) * (tax / 100);
        let rowTotal = Math.round((qty * price) + rowTax);
        rowTotal = rowTotal > 0 ? rowTotal : 0;

        // Update individual row total
        let totalField = row.find('.custom-total');
        let totalFieldInput = row.find('.item-total-amount');
        if (totalField.is('input')) {
            totalField.val(rowTotal);
        } else {
            totalField.text("AED " + rowTotal);
        }
        totalFieldInput.val(rowTotal);

        // Aggregate totals
        subtotalOrder += rowTotal;
        itemTotal += qty;
        taxIncluded += rowTax;
    });

    // Calculate final total
    let grandTotal = subtotalOrder;
    // let discount_value=0;
    let discounted_value=0;
    // Update summary UI
    $("#po-subtotal").text("AED " + subtotalOrder.toFixed(2));
    $("#item-total").text(itemTotal);
    $("#tax-included").text("AED " + Math.round(taxIncluded));
    $("#tax-included-input").val(taxIncluded);
      discounted_value=parseFloat($("#discount_value").val());
    //   discount_value=parseFloat($("#discount_value").val());
    //   discount_type=$("#discount_type").val();
    //   discounted_value=0;
     // console.log('discount_value',discount_value);
    // if(discount_value>0){
        //console.log(discount_value,discount_type);
        // if(discount_value>grandTotal){
        //     discount_value=grandTotal;
        // }
        // if(discount_type=="amount"){
        //     discounted_value=(discount_value);
            
        // }else{
        //     discounted_value = grandTotal - (grandTotal * (discount_value / 100));
        // }
       // console.log('discounted_value',discounted_value);
    // } 
    shipping_charges_value=0;
    shipping_charges=$("#shipping_charges").val();
    let totalAmount = $("#grand_total").val();
    let total_coupon_amount = $("#total_coupon_amount").val();
    let total_vat = $("#total_vat").val();
    //console.log('shipping_charges',shipping_charges_value);
    if(shipping_charges>0){ 
        shipping_charges_value = parseInt(shipping_charges);
        //console.log('shipping_charges_value',shipping_charges_value);
    }
    if(totalAmount>0){ 
        totalAmount = parseFloat(totalAmount);
        //console.log('shipping_charges_value',shipping_charges_value);
    }
        total_coupon_amount = parseFloat(total_coupon_amount);
        total_vat = parseFloat(total_vat);
        //console.log('shipping_charges_value',shipping_charges_value);
    if (discounted_value <= 0) {
        $('#discounted_label').text("AED 0.00"); // or handle it differently if needed
    } else {
        $('#discounted_label').text("AED -" + discounted_value.toFixed(2));
    }

    $("#shipping_charges_label").text("AED " + shipping_charges_value.toFixed(2));
    // $("#grand-total").text("AED " + (grandTotal-discounted_value +shipping_charges_value).toFixed(2));
    $("#grand-total").text("AED " + (totalAmount).toFixed(2));
    $("#total-coupon-amount").text("AED -" + (total_coupon_amount).toFixed(2));
    $("#total-vat").text("AED " + (total_vat).toFixed(2));
    $("#grand-total-input").val((grandTotal-discounted_value +shipping_charges_value).toFixed(2));
    // if(rows.length>0){
    //     $("#discount_label").addClass('d-none');
    //     $("#discount_modal").removeClass('d-none');

    //     $("#shipping_label").addClass('d-none');
    //     $("#shipping_modal").removeClass('d-none');
    // }else{
    //     $("#discount_label").removeClass('d-none');
    //     $("#discount_modal").addClass('d-none');

    //     $("#shipping_label").removeClass('d-none');
    //     $("#shipping_modal").addClass('d-none');
    // }
}


function handleStepperClick(button, direction) {
    const input = $(button).closest('.custom-connected').find('.quantityInput');
    const min = parseInt(input.data('min'), 10);
    const max = parseInt(input.data('max'), 10);
    let currentVal = parseInt(input.val(), 10);

    if (direction === 'up' && currentVal < max) {
      input.val(currentVal + 1);
    } else if (direction === 'down' && currentVal > min) {
      input.val(currentVal - 1);
    }
  }

  $(document).on('click', '.stepUp', function () {
    handleStepperClick(this, 'up');
  });

  $(document).on('click', '.stepDown', function () {
    handleStepperClick(this, 'down');
  });

  $(document).on('click', '.mark_as_paid', function () {
    total=$('#grand-total-input').val();
     $('#grand-total-order').html('AED '+total);
  });
  

  function mark_as_paid(){   
    payment_method=$('#payment_method').val(); 
        let Data = {
            "payment_method": payment_method, 
            "mark_as_paid": 1, 
        }

        $.ajax({
            url: ApiPlugin + '/ecommerce/order/mark_as_paid/'+uuid,  
            type: "POST",         
            data: JSON.stringify(Data),  
            contentType: "application/json",
            dataType: "json",
            headers: { "Authorization": "Bearer " + strkey, 'menu-uuid': menu_id , 'uuid': uuid},
            beforeSend: function (request) {
                request.setRequestHeader("Authorization", 'Bearer ' + strkey);
                imgload.show();
            },
            success: function (response) {
                imgload.hide();
                if (response.status_code) {
                   // console.log(response);
                    //message="Order mark as paid successfully!";
                   // showToast(message, 'Success', 'success');
                     location.reload();
                    //showToast(message, 'Success', 'success', "?P=product&M="+menu_id);
                } else { 
                    showToast(response.message, 'Error', 'error');
                }
            },
             error: function (xhr, status, error) {
                imgload.hide();
                let errorMessage = "An error occurred";

                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                } 

                showToast(errorMessage, 'Error', 'error');
                reject(errorMessage);
            }
        });
  }


   $(document).on('click', '.fullfill_items', function () {
    fullfillitems();
  });

  async function fullfillitems() {   
    payment_method = $('#payment_method').val();  
    const requests = [];  
    for (let item of edit_data.order_details) {
        let data = { 
            "order_id": edit_data.uuid, 
            "order_detail_id": item.uuid,  
            "quantity": item.product_qty,
        }; 
        const request = new Promise((resolve, reject) => {
            $.ajax({
                url: ApiPlugin + '/ecommerce/fulfillment/create_fulfillment',  
                type: "POST",         
                data: JSON.stringify(data),  
                contentType: "application/json",
                dataType: "json",
                headers: { "Authorization": "Bearer " + strkey, 'menu-uuid': menu_id, 'uuid': uuid },
                beforeSend: function (request) {
                    request.setRequestHeader("Authorization", 'Bearer ' + strkey);
                    imgload.show();
                },
                success: function (response) {
                    imgload.hide();
                   // console.log(response);
                    if (response.status==200) { 
                        resolve(response);
                    } else { 
                        showToast(response.message, 'Error', 'error');
                        reject(response.message);
                    }
                },
                 error: function (xhr, status, error) {
                imgload.hide();
                let errorMessage = "An error occurred";
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                } 

                showToast(errorMessage, 'Error', 'error');
                reject(errorMessage);
            }
            });
        });

        requests.push(request);  
     
    } 
    try {
        await Promise.all(requests);  
        location.reload(); 
    } catch (error) {
        console.error("Error in fulfillment process:", error);
    }
}


//   $(document).on('click', '.add_tracking', function () {
//     shipping_carrier();
//   });

  

  async function addTracking(){
    tracking_number = $('#tracking_number').val();  
    shipping_carrier_value = $('#shipping_carrier').val(); 

    let tracking_numbers = [];
    $('.tracking_number_input').each(function () {
        const value = $(this).val().trim();
        if (value) tracking_numbers.push(value);
    });

    if (tracking_numbers.length === 0) {
        showToast("Please add at least one tracking number", 'Error', 'error');
        return false;
    }
    
          const requests = [];  
            for (let item of edit_data.fullfillments) {
                let data = { 
                    "order_id":edit_data.uuid,
                    "fulfillment_id":item.uuid,
                    "shipping_carrier": shipping_carrier_value,  
                    "tracking_numbers": tracking_numbers, 
                    "tracking_urls":''
                }; 
                const request = new Promise((resolve, reject) => {
                    $.ajax({
                        url: ApiPlugin + '/ecommerce/tracking/add_tracking',  
                        type: "POST",         
                        data: JSON.stringify(data),  
                        contentType: "application/json",
                        dataType: "json",
                        headers: { "Authorization": "Bearer " + strkey, 'menu-uuid': menu_id, 'uuid': uuid },
                        beforeSend: function (request) {
                            request.setRequestHeader("Authorization", 'Bearer ' + strkey);
                            imgload.show();
                        },
                        success: function (response) {
                            imgload.hide();
                        // console.log(response);
                            if (response.status==200) { 
                                resolve(response);
                                location.reload();
                            } else { 
                                showToast(response.message, 'Error', 'error');
                                reject(response.message);
                                location.reload();
                            }
                        },
                         error: function (xhr, status, error) {
                            imgload.hide();
                        let errorMessage = "An error occurred";
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        } 
                        showToast(errorMessage, 'Error', 'error');
                        reject(errorMessage);
                    }
                    });
                });

                requests.push(request);  
            
            } 
            try {
                await Promise.all(requests);  
                location.reload(); 
            } catch (error) {
                console.error("Error in tracking process:", error);
            }
  }


     $(document).on('shown.bs.modal', '#exampleModalTwo', function () {
        $('#custom_search_po').off('keyup').on('keyup', function () {
            var searchTerm = $(this).val().toLowerCase(); 
            $('.product-table-body .table-first-item').each(function () {
                var productName = $(this).find('.parent .product-name span').text().toLowerCase(); 
                if (productName.includes(searchTerm)) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        });
    });