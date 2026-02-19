document.title = "Dashboard | Create Purchase Order";
let PageNo = 1;
let PageSize = 1;
let totalRecord = 0;
let PageLength = 0;
let Search = '';
let keyupTimer;
$(document).ready(function () {
    country_select();
    const today = new Date().toISOString().split('T')[0];
    document.getElementById("estimated-arrival").setAttribute("min", today);
  
    $(document).on('click', '.custom-button-browse', function(e) {
    e.preventDefault();
    
    // Check if location is selected
    let locationUUID = $("#Selectlocation").attr("data-uuid");
    
    if (!locationUUID) {
        // Show toast notification instead of opening modal
        showToast('Please select a location first', 'Error', 'error');
        return false; // Prevent modal from opening
    }
    
    // If location is selected, open the modal
    $('#exampleModalOne').modal('show');
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

    $(document).on("click", "#exampleModal .btn-primary", function () {
    let shippingAmount = parseFloat($("input[name='total_shipping']").val()) || 0;
    $("#shipping-fee").text("AED " + shippingAmount.toFixed(2));
    calculatePOS();
    $("#exampleModal").modal("hide");
});

    $("#AddProduct").on("click", function () {
        let productList = $(".table-first-item .custom-checkbox input[name='product_check']:checked").parents(".parent");
        let productArray = [];

        productList.each(function () {
            let checkProductVar = $(this).parents(".table-first-item").find(".child .custom-checkbox input[name='product_check']:checked");
            let productUuid = $(this).find(".product-name").attr("data-product-uuid");
            let productTitle = $(this).find(".product-name").text().trim();

            if (checkProductVar.length > 0) {
                checkProductVar.each(function () {
                    let productVarUuid = $(this).parents(".child").find(".product-name").attr("data-variant-uuid");
                    let productStock = {
                        variant_uuid: productVarUuid,
                        product_uuid: productUuid,
                        name: productTitle,
                        variant: $(this).parents(".child").find(".product-name").text().trim(),
                        qty: $(this).parents(".child").find(".total-name").text().trim(),
                        img: $(this).parents(".child").find(".product-name img").attr("src")
                    };
                    productArray.push(productStock);
                });
            } else {
                let productData = {
                    variant_uuid: "",
                    product_uuid: productUuid,
                    name: productTitle,
                    variant: "",
                    qty: $(this).find(".total-name").text().trim(),
                    img: $(this).find(".product-name img").attr("src")
                };
                productArray.push(productData);
            }
        });

        console.log("Selected Products and Variants:", productArray); // Debug log

        // Rest of the table rendering logic remains the same
        $("#product_table_one").removeClass("d-none");
        let productTable = ``;
        productArray.forEach(element => {
            productTable += `<tr>
                <td style="display: none;">
                    <input name="product_uuid" value="${element.product_uuid}" type="text" hidden>
                    <input name="variant_uuid" value="${element.variant_uuid}" type="text" hidden>
                </td>
                <td>
                    <div style="max-width: 300px;" class="product-name">
                        ${element.img ? `<img src="${element.img}" alt="Image" class="size-50px img-fit">`
                        : `<svg width='60' height='60' viewBox='0 0 42 42' fill='none' xmlns='http://www.w3.org/2000/svg'><rect x='1' y='1' width='40' height='40' rx='4' fill='white'/><rect x='1' y='1' width='40' height='40' rx='4' stroke='#232323' stroke-opacity='0.1'/><path d='M26.8333 13.5H15.1667C14.2462 13.5 13.5 14.2462 13.5 15.1667V26.8333C13.5 27.7538 14.2462 28.5 15.1667 28.5H26.8333C27.7538 28.5 28.5 27.7538 28.5 26.8333V15.1667C28.5 14.2462 27.7538 13.5 26.8333 13.5Z' stroke='#8D8D8D' stroke-linecap='round' stroke-linejoin='round'/><path d='M18.0834 19.3334C18.7737 19.3334 19.3334 18.7737 19.3334 18.0834C19.3334 17.393 18.7737 16.8334 18.0834 16.8334C17.393 16.8334 16.8334 17.393 16.8334 18.0834C16.8334 18.7737 17.393 19.3334 18.0834 19.3334Z' stroke='#8D8D8D' stroke-linecap='round' stroke-linejoin='round'/><path d='M28.5 23.5L24.3333 19.3334L15.1666 28.5' stroke='#8D8D8D' stroke-linecap='round' stroke-linejoin='round'/></svg>`}
                        <div>
                            <span>${element.name}</span>
                            ${element.variant ? `<span class="varient-text">${element.variant}</span>` : ''}
                        </div>
                    </div>
                </td>
                <td>
                    <div class="sku-input">
                        <input type="text" name="sku" class="form-control">
                    </div>
                </td>
                <td>
                    <div class="sku-input">
                        <input type="number" name="input-quantity" oninput="calculatePOS(this)" value="${element.qty}" class="form-control custom-qty">
                    </div>
                </td>
                <td>
                    <div class="sku-input">
                        <input oninput="calculatePOS(this)" style="padding-left: 40px !important;" type="number" placeholder="0.00" class="form-control custom-cost">
                        <div class="rupees-text">AED</div>
                    </div>
                </td>
                <td>
                    <div class="sku-input">
                        <input oninput="calculatePOS(this)" style="padding-right: 30px !important;" type="number" class="form-control custom-tax">
                        <div style="right: 10px; left: auto;" class="rupees-text">%</div>
                    </div>
                </td>
                <td>
                    <span class="create-tab-text custom-total">AED 0.00</span>
                    <input type="hidden" class="item-total-amount">
                </td>
                <td>
                    <a class="close" href="">
                        <svg width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16">
                            <path d="M10.72 11.78a.75.75 0 1 0 1.06-1.06l-2.72-2.72 2.72-2.72a.75.75 0 0 0-1.06-1.06l-2.72 2.72-2.72-2.72a.75.75 0 0 0-1.06 1.06l2.72 2.72-2.72 2.72a.75.75 0 1 0 1.06 1.06l2.72-2.72z" fill="#ed2121"></path>
                        </svg>
                    </a>
                </td>
            </tr>`;
        });

        $("#variantOrdernumber").html(productArray.length);
        $("#product_table_one").removeClass("d-none");
        $("#product_table_one tbody").empty();
        $("#product_table_one tbody").append(productTable);

        $(document).on("click", ".close", function (e) {
            e.preventDefault();
            let row = $(this).closest("tr");
            row.remove();
            if ($("#product_table_one tbody tr").length === 0) {
                $("#product_table_one").addClass("d-none");
            }
            calculatePOS();
        });
    });

    $("#dataSave").on("click", function () {
        let supplierUUID = $("#Selectsupplier").attr("data-uuid");
        let destinationUUID = $("#Selectlocation").attr("data-uuid");
        let paymentTerms = $("#payment_terms").val();
        let supplierCurrency = $("#supplier_currency").val();
        let estimatedArrival = $("input[name='estimated-arrival']").val();
        let shippingCarrier = $("#shipping_carrier").val();
        let trackingNumber = $("input[name='tracking-number']").val();
        let referenceNumber = $("input[name='reference-number']").val();
        let noteSupplier = $("textarea[name='note-supplier']").val();
        let tag = $("input[name='tag']").val();
        // let taxIncluded = $("#tax-included").text();
        let taxIncluded = $("#tax-included-input").val();
        let poSubtotal = $("#po-subtotal").text();
        let itemTotal = $("#item-total").text();
        let shippingFee = parseFloat($("#shipping-fee").text().replace("AED ", "")) || 0; 
        let grandTotal = $("#grand-total-input").val();
        let quantity = $("input[name='input-quantity']").val();
        let unit_price = $(".custom-cost").val();
        let tax = $(".custom-tax").val();
        let sku = $("input[name='sku']").val();

        if(quantity < 0){
            showToast('Quantity cannot be negative', 'Error', 'error');
            return false;
        }

        if(unit_price < 0){
            showToast('Cost cannot be negative', 'Error', 'error');
            return false;
        }

        if(tax < 0){
            showToast('Tax cannot be negative', 'Error', 'error');
            return false;
        }

        if(tax > 100){
            showToast('Tax cannot be greater than 100%', 'Error', 'error');
            return false;
        }

        if(shippingFee < 0){
            showToast('Shipping fee cannot be negative', 'Error', 'error');
            return false;
        }
        

        $("#Table_View tbody tr").each(function (index) {
            $(this).find("input[name='input-quantity']").val()
            $(this).find("input[name='input-quantity']").val()
            console.log();
            // let tableRow = 
        });

        let submitData = {
            "supplier_id": supplierUUID,
            "warehouse_id": destinationUUID,
            "payment_term_id": paymentTerms,
            "supplier_currency_id": supplierCurrency,
            "ship_date": estimatedArrival,
            "ship_carrier_id": shippingCarrier,
            "total_shipping": shippingFee,
            "tracking_number": trackingNumber,
            "reference_number": referenceNumber,
            "note_to_supplier": noteSupplier,
            "tags": tag,
            "status": "draft",
            "total_tax": taxIncluded,
            "total_amount": grandTotal,
        }

        let tableRows = document.querySelectorAll("#Table_View tbody tr"); // Apne table ka ID yahan daalain
        let rowDataArray = [];

        let skuSet = new Set(); // To track unique SKUs

        // Iterate through table rows
        for (let row of tableRows) {
            let skuValue = row.querySelector("input[name='sku']").value.trim();

            // Check for duplicate SKU
            if (skuSet.has(skuValue)) {
                showToast(`Duplicate SKU "${skuValue}" found. SKUs must be unique.`, 'Error', 'error');
                return false;
            }

            // Add SKU to the set if it's not empty
            if (skuValue) {
                skuSet.add(skuValue);
            } 

            let rowData = {
                product_id: row.querySelector("input[name='product_uuid']").value,
                variant_id: row.querySelector("input[name='variant_uuid']").value,
                name: row.querySelector(".product-name span").textContent.trim(),
                sku: skuValue,
                quantity: row.querySelector("input[name='input-quantity']").value,
                unit_price: row.querySelector(".custom-cost").value,
                tax: row.querySelector(".custom-tax").value,
                // total_amount: row.querySelector(".custom-total").textContent.trim(),
                total_amount: row.querySelector(".item-total-amount").value
            };
            // console.log(row.querySelector(".item-total-amount").val());
            

            rowDataArray.push(rowData);
        }
        submitData["items"] = rowDataArray
        console.log(submitData); // Yeh array sari row ki values dega
        $.ajax({
            url: ApiPlugin + '/ecommerce/purchase-order/store', // Aapka backend API ya PHP file ka path
            type: "POST",          // POST request use kar rahe hain
            data: JSON.stringify(submitData), // JSON mein convert karna
            contentType: "application/json",
            dataType: "json",
            headers: { "Authorization": "Bearer " + strkey, 'menu-uuid': menu_id },
            beforeSend: function (request) {
                request.setRequestHeader("Authorization", 'Bearer ' + strkey);
            },
            success: function (response) { 
                if (response.status_code) {
                    showToast(response.message, 'Success', 'success', "?P=purchase-order&M="+menu_id);
                } else {
                    showToast(response.message, 'Error', 'error');
                   // alert("Error saving data: " + response.message);
                }
            },
            error: function (xhr, status, error) {
                console.error("AJAX Error: ", error);
            }
        });


    })

    select_supplier();
    select_location();
    payment_terms();
    supplier_currency();
    shipping_carrier();


});


let subtotalPO = 0;
let itemTotal = 0;
let taxIncluded = 0;
// const shippingFee = 100; // Fixed Shipping Fee

function calculatePOS(e) {
    let getParentsRow = $(e).closest('tr');

    let customQTY = parseFloat(getParentsRow.find('.custom-qty').val()) || 0;
    let customCOST = parseFloat(getParentsRow.find('.custom-cost').val()) || 0;
    let customTAX = parseFloat(getParentsRow.find('.custom-tax').val()) || 0;

    // **Calculate Tax Per Row**
    let getTax = (customQTY * customCOST) * (customTAX / 100);
    let finalCal = (customQTY * customCOST) + getTax;
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

    let shippingFee = parseFloat($("#shipping-fee").text().replace("AED ", "")) || 0;

    // **Calculate Grand Total (Subtotal + Shipping)**
    let grandTotal = subtotalPO + shippingFee;

    // **Update the UI**
    $("#po-subtotal").text("AED " + subtotalPO);
    $("#item-total").text(itemTotal);
    $("#tax-included").text("AED " + taxIncluded);
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
    // Ensure location_id is valid before making the API call
    let location_id = $("#Selectlocation").attr("data-uuid");
    if (!location_id) {
        console.warn("Location ID is not set. Please select a location.");
        showToast("Please select a location before adding products.", "Warning", "warning");
        return;
    }

    $.ajax({
        url: ApiPlugin + '/ecommerce/product/get_active_products', // API endpoint
        type: "GET",
        contentType: "application/json",
        dataType: "json",
        data: {
            location_id: location_id
        },
        beforeSend: function (xhr) {
            xhr.setRequestHeader("Authorization", "Bearer " + strkey);
            xhr.setRequestHeader("menu-uuid", menu_id);
            imgload.show();
        },
        success: function (response) {
            imgload.hide();

            if (response.status_code !== 200) {
                console.error("API error:", response.message);
                showToast("Failed to load products: " + response.message, "Error", "error");
                return;
            }

            let data = response.data;
            console.log("API Response:", data); // Debug the response

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

                let parentImage = item.thumbnail_img ? `<img src="${AssetsPath + item.thumbnail_img}" alt="Image" class="size-50px img-fit">` : placeholderSVG;

                let isVariantProduct = item.product_stocks && item.product_stocks.some(stock => stock.sku);

                let parentQty = 0;
                if (isVariantProduct) {
                    parentQty = item.product_stocks.reduce((sum, stock) => {
                        return stock.sku ? sum + (stock.qty || 0) : sum;
                    }, 0);
                } else if (item.product_stocks && item.product_stocks.length > 0) {
                    parentQty = item.product_stocks[0].qty || 0;
                }

                htmlLi += `<div class="table-first-item product-${item.uuid}">
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
                                    <span>${parentQty}</span>
                                </div>
                            </div>`;

                if (isVariantProduct) {
                    htmlLi += item.product_stocks.map((stockItem) => {
                        if (!stockItem.sku) return ''; // skip non-variant rows
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
                                <span>${stockItem.sku || item.name}</span>
                            </div>
                            <div class="total-name">
                                <span>${stockItem.qty}</span>
                            </div>
                        </div>`;
                    }).join('');
                } else {
                    console.warn(`No variants found for product: ${item.name}`);
                }

                htmlLi += `</div>`;
            }

            $("#table_product").empty();
            $("#table_product").append(htmlLi);

            // Ensure child rows are visible
            $(".table-row.child").show();
        },
        error: function (xhr, status, error) {
            imgload.hide();
            console.error("AJAX Error:", status, error);
            showToast("Failed to load products.", "Error", "error");
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
        utilsScript: "./assets/plugins/intel-input/utils.js",
        hiddenInput: () => ({ phone: "txt_phone" })
      });
      function validateInput() {
        if (input.value.trim()) {
            if (iti.isValidNumber()) {
              input.parentElement.parentElement.classList.remove("error");
              input.parentElement.parentElement.querySelector(".error-txt").innerHTML = "";
              $("#SupplierForm").find("#btn_upd").prop("disabled", false);
              $("#SupplierForm").find("#btn_sav").prop("disabled", false);
              $("input[name='txt_phone']").val(iti.getNumber());
              input.setAttribute("data-full-phone", iti.getNumber());
            } else {
              input.parentElement.parentElement.classList.add("error");
              input.parentElement.parentElement.querySelector(".error-txt").innerHTML = "Invalid Number";
              $("#SupplierForm").find("#btn_upd").prop("disabled", true);
              $("#SupplierForm").find("#btn_sav").prop("disabled", true);
              $("input[name='txt_phone']").val('');
              input.removeAttribute("data-full-phone");
            }
          } else {
            input.parentElement.parentElement.classList.remove("error");
            input.parentElement.parentElement.querySelector(".error-txt").innerHTML = "";
            $("#SupplierForm").find("#btn_upd").prop("disabled", false);
            $("#SupplierForm").find("#btn_sav").prop("disabled", false);
            $("input[name='txt_phone']").val(iti.getNumber());
            input.removeAttribute("data-full-phone");
        }
      }

      input.addEventListener("blur", validateInput);
      input.addEventListener("keyup", validateInput);
    }
// intlTelInput JS End
    // alert();
document.getElementById("SupplierForm").addEventListener("submit", function (e) {
    e.preventDefault();

    // Collect form data
    const company = this.querySelector("[name='company']").value.trim();
    const country_id = this.querySelector("#country_select").value; // Assuming country_select is the ID of the country dropdown
    const address = this.querySelector("[name='address']")?.value.trim() || "";
    const apart_suite = this.querySelector("[name='apart_suite']")?.value.trim() || "";
    const city = this.querySelector("[name='city']")?.value.trim() || "";
    const postal_code = this.querySelector("[name='postal_code']")?.value.trim() || "";
    const contact_name = this.querySelector("[name='contact_name']")?.value.trim() || "";
    const email = this.querySelector("[name='email']")?.value.trim() || "";
    const phone_number = iti ? iti.getNumber() : "";

    if (!company) {
        showToast("Company can't be blank", "Error", "error");
        return;
    }

    if (!country_id) {
        showToast("Country can't be blank", "Error", "error");
        return;
    }

    if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        showToast("Invalid Email Format", "Error", "error");
        return;
    }

    if (phone_number && !iti.isValidNumber()) {
        showToast("Invalid Phone Number", "Error", "error");
        return;
    }

    // Prepare data for API
    const submitData = {
        company,
        country_id,
        address,
        apart_suite,
        city,
        postal_code,
        contact_name,
        email,
        phone_number,
        status: 1
    };

    // Send AJAX request to /supplier/store
    $.ajax({
        url: ApiPlugin + '/ecommerce/supplier/store',
        type: "POST",
        contentType: "application/json",
        dataType: "json",
        data: JSON.stringify(submitData),
        headers: {
            "Authorization": "Bearer " + strkey,
            "menu-uuid": menu_id
        },
        beforeSend: function (request) {
            imgload.show(); // Show loading indicator
        },
        success: function (response) {
            imgload.hide();
            if (response.status_code === 200) {
                showToast(response.message, "Success", "success");
                // Close modal if applicable
                $('#createSupplier').modal('hide');
                window.location.reload();
            } else {
                showToast(response.message || "Failed to add supplier", "Error", "error");
            }
        },
        error: function (xhr, status, error) {
            imgload.hide();
            let errorMessage = "An error occurred while saving the supplier.";
            if (xhr.status === 422) {
                const response = xhr.responseJSON;
                errorMessage = response.errors || "Validation errors occurred.";
            } else {
                console.error("AJAX Error:", status, error);
            }
            showToast(errorMessage, "Error", "error");
        }
    });
});

// document.querySelectorAll("#SupplierForm input").forEach(function (input) {
//     input.addEventListener("input", function () {
//         input.parentElement.classList.remove("error");
//         input.parentElement.parentElement.querySelector(".error-txt").innerHTML = "";
//         input.parentElement.querySelector(".error-txt").innerHTML = "";
//     });
// });


$(".create-new-supplier").on("click", function () {
    // country_select();
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
