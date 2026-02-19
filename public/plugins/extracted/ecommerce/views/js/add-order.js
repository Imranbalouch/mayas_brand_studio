document.title="Dashboard | Add Order";
let PageNo = 1;
let PageSize = 1;
let totalRecord = 0;
let PageLength = 0;
let Search = '';
let keyupTimer;


document.getElementById('addOrderForm').addEventListener('keydown', function(event) {
    if (event.key === 'Enter' && event.target.tagName !== 'TEXTAREA') {
      event.preventDefault();
    }
  });


$(document).ready(function () {
 

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

    // $(document).on("click", function (event) {
    //     if (!$(event.target).closest(".select-dropdown").length) {
    //         $(".select-dropdown").removeClass("show");
    //     }
    // });

    // $(".select-dropdown .droupdown-head").on("click", function (e) {
    //     e.preventDefault();
    //     $(this).parent().toggleClass("show");
    // });

    // $(".droupdown-body .custom-checkbox").on("click", function () {
    //     var selectedText = $(this).text().trim(); // Clicked label ka text lein
    //     var dropdown = $(this).closest(".select-dropdown"); // Us dropdown ko find karein jisme click hua
    //     dropdown.find("#Selectsupplier").text(selectedText); // Us specific dropdown ke span mein text set karein
    // });

    // $(".select-dropdown .droupdown-head").on("click", function (e) {
    //     e.preventDefault();
    //     $(this).parent().toggleClass("show");
    // });

    // // **Dropdown Selection for Dynamic List Items**
    // $("#select_supplier").on("click", "li", function () { 
    //     var selectedText = $(this).text().trim(); // Get clicked supplier name
    //     $(".select-dropdown #Selectsupplier").text(selectedText); // Set selected supplier name
    //     $(".select-dropdown").removeClass("show"); // Close dropdown after selection
    // });

    // // **Close dropdown if clicked outside**
    // $(document).on("click", function (e) {
    //     if (!$(e.target).closest(".select-dropdown").length) {
    //         $(".select-dropdown").removeClass("show");
    //     }
    // });

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
  

    // **Close dropdown if clicked outside**
    $(document).on("click", function (e) {
        if (!$(e.target).closest(".select-dropdown").length) {
            $(".select-dropdown").removeClass("show");
        }
    });

    $("#AddProduct").on("click", function () {
        // console.log($(".table-first-item .custom-checkbox input[name='product_check']:checked").parents(".parent"));
       // console.log($(".table-first-item .custom-checkbox input[name='product_check']:checked").parents(".table-first-item").find(".child"));
        let productList = $(".table-first-item .custom-checkbox input[name='product_check']:checked").parents(".parent");
        let productListVar = $(".table-first-item .custom-checkbox input[name='product_check']:checked").parents(".table-first-item").find(".child");
        let productArray = []; // Empty array to store text
        // Loop through each selected product and push its text into the array
        productList.each(function (key) {
           // console.log("$(this)", $(this).parents(".table-first-item").find(".child .custom-checkbox input[name='product_check']:checked"));
            let checkProductVar = $(this).parents(".table-first-item").find(".child .custom-checkbox input[name='product_check']:checked"); //product variant check;
           // console.log("checkProductVar", checkProductVar.length);
            let productUuid = $(this).find(".product-name").attr("data-product-uuid");
            let productPrice = $(this).find(".product-name").attr("data-product-price");
            let product_image = $(this).find(".product-name").attr("data-product-image");
            let productTitle = $(this).find(".product-name").text().trim();
            let variantProduct = [];
            // console.log("variantProduct",$(this).find(".product-name").text().trim());
            // console.log("variantProduct",$(this).find(".product-name img").attr("src"));
            // let productStock = $(this).parents(".table-first-item").find(".child")
            if (checkProductVar.length > 0) {
                checkProductVar.each(function () {
                    let productVarUuid = $(this).parents(".child").find(".product-name").attr("data-variant-uuid");
                    productPrice= $(this).parents(".child").find(".product-name").attr("data-product-price");
                     product_image = $(this).parents(".child").find(".product-name").attr("data-product-image");
                    //console.log("productVarUuid", productVarUuid);
                    let productStock = {
                        "variant_uuid": productVarUuid,
                        "product_uuid": productUuid,
                        "name": productTitle,
                        "price":productPrice,
                        "variant": $(this).parents(".child").find(".product-name").text().trim(),
                        "qty": $(this).parents(".child").find(".total-name").text().trim(),
                        "product_image": product_image,
                        "img": $(this).parents(".child").find(".product-name img").attr("src")
                    }
                    // console.log("roduct-name", $(this).parents(".child").find(".product-name").text().trim());

                    productArray.push(productStock);
                });
            } else {
                let productData = {
                    "variant_uuid": "",
                    "product_uuid": productUuid,
                    "name": $(this).find(".product-name").text().trim(),
                    "price":productPrice,
                    "variant": "",
                    "qty": $(this).find(".total-name").text().trim(),
                    "product_image": product_image,
                    "img": $(this).find(".product-name img").attr("src")
                }
                productArray.push(productData); // Extract text and push
            }
        });
       // console.log("productArray", productArray);

        // console.log("productArray",productArray);
        $("#product_table_one").removeClass("d-none");
        let productTable = ``;
        productArray.forEach(element => {
          // console.log("element", element);

            productTable += `<tr>
                                <td style="display: none;">
                                    <input name="product_uuid" value="${element.product_uuid}" type="text" hidden>
                                    <input name="variant_uuid" value="${element.variant_uuid}" type="text" hidden>
                                    <input name="product_image" value="${element.product_image}" type="text" hidden>
                                    <input name="variant_price" class="product-price" value="${element.price}" type="text" hidden>
                                </td>
                            
                                <!-- <td style="width: 100px;"><input type="checkbox" class="form-check-input"></td> -->
                                <td>
                                    <div style="width: auto;" class="product-name">
                                            ${element.img ? `<img src="${element.img}" alt="Image" class="size-50px img-fit"
                                                        onerror="this.onerror=null; this.outerHTML=\`<svg width='60' height='60' viewBox='0 0 42 42' fill='none' xmlns='http://www.w3.org/2000/svg'><rect x='1' y='1' width='40' height='40' rx='4' fill='white'/><rect x='1' y='1' width='40' height='40' rx='4' stroke='#232323' stroke-opacity='0.1'/><path d='M26.8333 13.5H15.1667C14.2462 13.5 13.5 14.2462 13.5 15.1667V26.8333C13.5 27.7538 14.2462 28.5 15.1667 28.5H26.8333C27.7538 28.5 28.5 27.7538 28.5 26.8333V15.1667C28.5 14.2462 27.7538 13.5 26.8333 13.5Z' stroke='#8D8D8D' stroke-linecap='round' stroke-linejoin='round'/><path d='M18.0834 19.3334C18.7737 19.3334 19.3334 18.7737 19.3334 18.0834C19.3334 17.393 18.7737 16.8334 18.0834 16.8334C17.393 16.8334 16.8334 17.393 16.8334 18.0834C16.8334 18.7737 17.393 19.3334 18.0834 19.3334Z' stroke='#8D8D8D' stroke-linecap='round' stroke-linejoin='round'/><path d='M28.5 23.5L24.3333 19.3334L15.1666 28.5' stroke='#8D8D8D' stroke-linecap='round' stroke-linejoin='round'/></svg>\`;">`
                    : `<svg width='60' height='60' viewBox='0 0 42 42' fill='none' xmlns='http://www.w3.org/2000/svg'><rect x='1' y='1' width='40' height='40' rx='4' fill='white'/><rect x='1' y='1' width='40' height='40' rx='4' stroke='#232323' stroke-opacity='0.1'/><path d='M26.8333 13.5H15.1667C14.2462 13.5 13.5 14.2462 13.5 15.1667V26.8333C13.5 27.7538 14.2462 28.5 15.1667 28.5H26.8333C27.7538 28.5 28.5 27.7538 28.5 26.8333V15.1667C28.5 14.2462 27.7538 13.5 26.8333 13.5Z' stroke='#8D8D8D' stroke-linecap='round' stroke-linejoin='round'/><path d='M18.0834 19.3334C18.7737 19.3334 19.3334 18.7737 19.3334 18.0834C19.3334 17.393 18.7737 16.8334 18.0834 16.8334C17.393 16.8334 16.8334 17.393 16.8334 18.0834C16.8334 18.7737 17.393 19.3334 18.0834 19.3334Z' stroke='#8D8D8D' stroke-linecap='round' stroke-linejoin='round'/><path d='M28.5 23.5L24.3333 19.3334L15.1666 28.5' stroke='#8D8D8D' stroke-linecap='round' stroke-linejoin='round'/></svg>`
                }
                                        <div class="midle-name">
                                            <span>${element.name}</span>
                                            ${element.variant ? `<span class="varient-text">${element.variant}</span>` : ''}
                                        </div>
                                    </div>
                                </td> 
                                <td>
                                    <div class="sku-input">
                                        <input type="number" name="input-quantity" oninput="calculateSummary(this)" value="${element.qty == 0 ? 0 : 1}" class="form-control custom-qty">
                                    </div>
                                    <!-- <span class="create-tab-text">Shop location</span> -->
                                </td>  
                                <td>
                                    <span class="create-tab-text custom-total">AED ${element.price}</span>
                                    <input type="hidden" class="item-total-amount" >
                                </td>
                                <td>
                                    <a class="close" href="">
                                        <svg width="20" height="20" xmlns="http://www.w3.org/2000/svg"
                                            viewBox="0 0 16 16">
                                            <path
                                                d="M10.72 11.78a.75.75 0 1 0 1.06-1.06l-2.72-2.72 2.72-2.72a.75.75 0 0 0-1.06-1.06l-2.72 2.72-2.72-2.72a.75.75 0 0 0-1.06 1.06l2.72 2.72-2.72 2.72a.75.75 0 1 0 1.06 1.06l2.72-2.72z"
                                                fill="#ed2121"></path>
                                        </svg>
                                    </a>
                                </td>
                            </tr>`;
        });
        $("#variantOrdernumber").html(productArray.length + ' items');
        $("#product_table_one").removeClass("d-none");
        $("#product_table_one tbody").empty();
        $("#product_table_one tbody").append(productTable);
         
        calculateSummary();
        $(document).on("click", ".close", function (e) {
            e.preventDefault(); // Prevent default link behavior

            let row = $(this).closest("tr"); // Get the clicked row
            row.remove(); // Remove the row

            // ✅ Agar table ke tbody mai koi bhi row nahi bachi, toh table hide kar do
            if ($("#product_table_one tbody tr").length === 0) {
                $("#product_table_one").addClass("d-none"); // Hide the table
            }
            calculateSummary();
        });


        // for (const product in productList) {
        //   console.log(product)
        // //   console.log(product)
        // }
        // $(".table-first-item .custom-checkbox input[name=product_check]").on("change", function () {

        //     console.log($(".table-first-item .custom-checkbox input[name=product_check]"));


        // });
    });

    $("#dataSave").on("click", function () {
        let customer_id = $("#customer_id").val(); 
        let note = $("#note").val(); 
        let location_id = $("#location_id").val();
        let discount_value = $("#discount_value").val();
        let discount_type = $("#discount_type").val();
        let shipping_charges = $("#shipping_charges").val();
        let shipping_name = $("#shipping_name").val();
        let grandTotal = $("#grand-total-input").val();
        let market_id=$("#market_id").val();
        $("#Table_View tbody tr").each(function (index) {
            $(this).find("input[name='input-quantity']").val()
            $(this).find("input[name='input-quantity']").val()
           // console.log();
            // let tableRow = 
        });

        let submitData = {
            "customer_id": customer_id, 
            "note": note,
            "location_id": location_id,
            "shipping_type":shipping_name,
            "shipping_price":shipping_charges,
            "market_id": market_id,
            "discount_type":discount_type,
            "discount_value":discount_value,
            "total_amount": grandTotal,
        }

        let tableRows = document.querySelectorAll("#Table_View tbody tr"); // Apne table ka ID yahan daalain
        let rowDataArray = [];

        tableRows.forEach(row => {
            let rowData = {
                product_id: row.querySelector("input[name='product_uuid']").value,
                variant_id: row.querySelector("input[name='variant_uuid']").value,
                name: row.querySelector(".product-name span").textContent.trim(),
               // sku: row.querySelector("input[name='sku']").value,
                product_qty: row.querySelector("input[name='input-quantity']").value,
                unit_price: row.querySelector(".product-price").value,
                image: row.querySelector("input[name='product_image']").value,
                //tax: row.querySelector(".custom-tax").value,
                // total_amount: row.querySelector(".custom-total").textContent.trim(),
               // total_amount: row.querySelector(".item-total-amount").value
            };
            // console.log(row.querySelector(".item-total-amount").val());
            

            rowDataArray.push(rowData);
        });
        submitData["items"] = rowDataArray
        //console.log(submitData); // Yeh array sari row ki values dega
        if(customer_id=='' || customer_id==null || customer_id=='undefined'){
            errorMessages='Please Select customer';
            let htmlError =  errorMessages ;
            showToast(htmlError, 'Error', 'error');
            return false;
        }
        if(discount_value < 0){
            showToast("The Discount Value should not be in negative", 'Error', 'error');
            return false;
        }
        if(shipping_charges < 0){
            showToast("The Shipping Charges should not be in negative", 'Error', 'error');
            return false;
        }
        if(discount_value > 0) {
            let subtotalText = $("#po-subtotal").text().replace("AED ", "");
            let subtotalValue = parseFloat(subtotalText) || 0;
            
            if(discount_value > subtotalValue) {
                showToast("The Discount Value should not be greater than the Order Subtotal", 'Error', 'error');
                return false;
            }
        }
        if(rowDataArray<=0){
            errorMessages='Please Select Product';
            let htmlError =  errorMessages ;
            showToast(htmlError, 'Error', 'error');
            return false;
        }
        $.ajax({
            url: ApiPlugin + '/ecommerce/order/add_order', // Aapka backend API ya PHP file ka path
            type: "POST",          // POST request use kar rahe hain
            data: JSON.stringify(submitData), // JSON mein convert karna
            contentType: "application/json",
            dataType: "json",
            headers: { "Authorization": "Bearer " + strkey, 'menu-uuid': menu_id },
            beforeSend: function (request) {
                request.setRequestHeader("Authorization", 'Bearer ' + strkey);
            },
            success: function (response) {
                if (response.status_code=== 200) {
                    message="Data saved successfully!";
                   // showToast(message, 'Success', 'success');
                    showToast(message, 'Success', 'success', "?P=order&M="+menu_id);
                } else { 
                    showToast(response.message, 'Error', 'error');
                }
            },
            error: function (xhr, status, error) {
                console.error("AJAX Error: ", error);

                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    let messages = [];

                    // Loop through errors and collect messages
                    for (const field in xhr.responseJSON.errors) {
                        xhr.responseJSON.errors[field].forEach(msg => {
                            messages.push(msg);
                        });
                    }

                    showToast(messages.join('\n'));
                } else {
                    showToast("An unexpected error occurred.");
                }
            }

        });


    })

    get_customers(); 
    get_locations();
    get_active_markets();
});

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
    let discount_value=0;
    // Update summary UI
    $("#po-subtotal").text("AED " + subtotalOrder.toFixed(2));
    $("#item-total").text(itemTotal);
    
    // Update the subtotal display with total quantity instead of product count
    $("#variantOrdernumber").html(itemTotal + ' items');
    
    $("#tax-included").text("AED " + Math.round(taxIncluded));
    $("#tax-included-input").val(taxIncluded);
      discount_value=parseInt($("#discount_value").val());
      discount_type=$("#discount_type").val();
      discounted_value=0;
     // console.log('discount_value',discount_value);
    if(discount_value>0){
        //console.log(discount_value,discount_type);
        // if(discount_value>grandTotal){
        //     discount_value=grandTotal;
        // }
        if(discount_type=="amount"){
            discounted_value=(discount_value);
            
        }else{
            discounted_value = grandTotal - (grandTotal * (discount_value / 100));
        }
       // console.log('discounted_value',discounted_value);
    } 
    shipping_charges_value=0;
    shipping_charges=$("#shipping_charges").val();
    //console.log('shipping_charges',shipping_charges_value);
    if(shipping_charges>0){ 
        shipping_charges_value = parseInt(shipping_charges);
        //console.log('shipping_charges_value',shipping_charges_value);
    }
    if (discounted_value <= 0) {
        $('#discounted_label').text("AED 0.00"); // or handle it differently if needed
    } else {
        $('#discounted_label').text("AED -" + discounted_value.toFixed(2));
    }

    $("#shipping_charges_label").text("AED " + shipping_charges_value.toFixed(2));
    $("#grand-total").text("AED " + (grandTotal-discounted_value +shipping_charges_value).toFixed(2));
    $("#grand-total-input").val((grandTotal-discounted_value +shipping_charges_value).toFixed(2));
    if(rows.length>0){
        $("#discount_label").addClass('d-none');
        $("#discount_modal").removeClass('d-none');

        $("#shipping_label").addClass('d-none');
        $("#shipping_modal").removeClass('d-none');
    }else{
        $("#discount_label").removeClass('d-none');
        $("#discount_modal").addClass('d-none');

        $("#shipping_label").removeClass('d-none');
        $("#shipping_modal").addClass('d-none');
    }
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
                parentDropdownCarrier.append(`<option value="">Select Customer</option>`); 
                response.data.forEach(customers => { 
                    //console.log("customers",customers.customer.uuid);
                    parentDropdownCarrier.append(
                        `<option value="${customers.uuid}"> ${customers.name} </option>`
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

$("#ProductItem").on("click", function () {
    table_product();
})

function table_product() {
    $.ajax({
        url: ApiPlugin + '/ecommerce/product/get_active_products', // API endpoint
        type: "GET",
        contentType: "application/json",
        dataType: "json",
        data: {
            location_id: $('#location_id').val()
        },
        beforeSend: function (xhr) {
            xhr.setRequestHeader("Authorization", "Bearer " + strkey);
            xhr.setRequestHeader("menu-uuid", menu_id);
            imgload.show();
        },
        success: function (response) {
            imgload.hide();

            let data = response.data;

            let placeholderSVG = `<svg width="60" height="60" viewBox="0 0 42 42" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <rect x="1" y="1" width="40" height="40" rx="4" fill="white"/>
                                    <rect x="1" y="1" width="40" height="40" rx="4" stroke="#232323" stroke-opacity="0.1"/>
                                    <path d="M26.8333 13.5H15.1667C14.2462 13.5 13.5 14.2462 13.5 15.1667V26.8333C13.5 27.7538 14.2462 28.5 15.1667 28.5H26.8333C27.7538 28.5 28.5 27.7538 28.5 26.8333V15.1667C28.5 14.2462 27.7538 13.5 26.8333 13.5Z" stroke="#8D8D8D" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M18.0834 19.3334C18.7737 19.3334 19.3334 18.7737 19.3334 18.0834C19.3334 17.393 18.7737 16.8334 18.0834 16.8334C17.393 16.8334 16.8334 17.393 16.8334 18.0834C16.8334 18.7737 17.393 19.3334 18.0834 19.3334Z" stroke="#8D8D8D" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M28.5 23.5L24.3333 19.3334L15.1666 28.5" stroke="#8D8D8D" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>`;

            let htmlLi = ``;
            for (const key in data) {
                let item = data[key];

                let isVariantProduct = item.product_stocks && item.product_stocks.some(stock => stock.sku);
                let parentQty = 0;
                if (isVariantProduct) {
                    parentQty = item.product_stocks.reduce((sum, stock) => {
                        return stock.sku ? sum + (stock.qty || 0) : sum;
                    }, 0);
                } else if (item.product_stocks && item.product_stocks.length > 0) {
                    parentQty = item.product_stocks[0].qty || 0;
                }


                let parentImage = item.thumbnail_img ? `<img src="${AssetsPath + item.thumbnail_img}" alt="Image" class="size-50px img-fit">` : placeholderSVG;
                
                // Check if product has variants (product_stocks with SKU)
                let hasVariants = item.product_stocks && item.product_stocks.some(stock => stock.sku);

                htmlLi += `<div class="table-first-item product-${item.uuid}">
                            <div class="table-row parent">
                                <div class="custom-checkbox-main form-group d-inline-block">
                                    <label class="custom-checkbox">
                                        <input type="checkbox" name="product_check">
                                        <span class="aiz-square-check"></span>
                                    </label>
                                </div>
                                <div class="product-name" data-product-uuid="${item.uuid}" data-product-price="${item.current_stock ? item.current_stock.price : ''}" data-product-image="${item.thumbnail_img}">
                                    ${parentImage}
                                    <span>${item.name}</span>
                                </div>
                                <div class="total-name">
                                    <span>${parentQty}</span>
                                </div>

                            </div>`;

                // Only show variants if the product actually has variants
                if (hasVariants) {
                    htmlLi += item.product_stocks.map((stockItem) => {
                        if (!stockItem.sku) return ''; // Skip non-variant items
                        let childImage = stockItem.image ? `<img src="${AssetsPath + stockItem.image}" alt="Image" class="size-50px img-fit">` : placeholderSVG;

                        return `
                        <div class="table-row child left-side-item productStock-${stockItem.uuid}">
                            <div class="custom-checkbox-main form-group d-inline-block">
                                <label class="custom-checkbox">
                                    <input type="checkbox" name="product_check">
                                    <span class="aiz-square-check"></span>
                                </label>
                            </div>
                            <div class="product-name" data-product-uuid="${item.uuid}" data-variant-uuid="${stockItem.uuid}" data-product-price="${stockItem.price}" data-product-image="${stockItem.image}">
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
            $("#table_product").empty();
            $("#table_product").append(htmlLi);
        }
    });
}
 

    $(document).on('shown.bs.modal', '#exampleModalTwo', function () {
        $('#custom_search_po').off('keyup').on('keyup', function () {
            var searchTerm = $(this).val().toLowerCase(); 
            var anyVisible = false;

            $('.product-table-body .table-first-item').each(function () {
                var productName = $(this).find('.parent .product-name span').text().toLowerCase(); 
                if (productName.includes(searchTerm)) {
                    $(this).show();
                    anyVisible = true;
                } else {
                    $(this).hide();
                }
            });

            // Show or hide the "No results found" message
            if (!anyVisible) {
                if ($('.no-results-message').length === 0) {
                $('.product-table-body').append(`
                        <tr class="no-results-message">
                            <td colspan="100%" class="text-center" style="padding: 20px;">
                                <span class="text-center p-3" style="width: 96%;display: inline-block;position: absolute;overflow-x: hidden !important;font-size: large;">No matching records found</span>
                            </td>
                        </tr>
                    `);
                }
            } else {
                $('.no-results-message').remove();
            }
        });
    });
   
   $(document).on("change", "#customer_id", function () {
    var customer_id=$('#customer_id').val();
    //alert(customer_id);
    if(customer_id!=''){
        set_customer_info(customer_id);

    }else{
        $('#customer_info').addClass('d-none');
    }
     
    });

    function set_customer_info(customer_id){
        $.ajax({
        url: ApiPlugin + '/ecommerce/customer/show/'+customer_id, // API end point
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
                   var customer=response.data.customer;
                    //console.log(customer);
                    $('#customer_info').removeClass('d-none');
                     if(customer.name!='' && customer.name!=null){
                        $("#customer_name").html(customer.name);
                        $("#customer_email").html(customer.email);
                        $("#customer_phone").html(customer.phone);

                        $("#customer_billing_name").html(customer.name);
                        $("#customer_billing_phone").html(customer.phone);
                        $("#customer_billing_email").html(customer.email);
                        $("#customer_billing_address").html(customer.address);
                        $("#customer_billing_city").html(customer.city); 
                        $("#customer_billing_country").html(customer.country);
                    }else{
                        $("#customer_shipping_address").html('No shipping address provided');
                    }
                    
                } else {
                    console.error('Unexpected response:', response);
                }
            }
        });
    }