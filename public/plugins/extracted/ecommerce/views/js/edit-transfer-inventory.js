document.title = "Dashboard | Edit Transfer Inventory";
$(document).ready(function () {
    var urlParams = new URLSearchParams(window.location.search);
    var uuid = urlParams.get('id');
    var edit_data = '';

    if (uuid) {
        $.ajax({
            url: ApiPlugin + '/ecommerce/transferinventory/edit/' + uuid,
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
                    // First populate dropdowns, then populate form
                    Promise.all([
                        select_supplier(),
                        select_location(),
                        shipping_carrier()
                    ]).then(() => {
                        populateForm(edit_data);
                        table_product();
                    });
                } else {
                    showToast(response.message || 'Failed to load transfer inventory data.', 'Error', 'error');
                }
            },
            error: function (xhr, status, error) {
                imgload.hide();
                console.error("AJAX Error: ", error);
                showToast('Server error occurred. Please try again.', 'Error', 'error');
            }
        });
    } else {
        // Initialize dropdowns for new form
        select_supplier();
        select_location();
        shipping_carrier();
    }

   function populateForm(data) {
    // Populate supplier dropdown selection
    if (data.origin_location_id) {
        setTimeout(() => {
            const originOption = $(`#select_supplier input[data-uuid="${data.origin_location_id}"]`);
            if (originOption.length) {
                const originText = originOption.val();
                const originType = originOption.attr("data-type");
                $("#Selectsupplier").attr("data-uuid", data.origin_location_id)
                    .attr("data-type", originType)
                    .text(originText);
            }
        }, 500);
    }
    
    // Populate location dropdown selection
    if (data.destination_location_id) {
        setTimeout(() => {
            const locationOption = $(`#select_location input[data-uuid="${data.destination_location_id}"]`);
            if (locationOption.length) {
                const locationText = locationOption.val();
                $("#Selectlocation").attr("data-uuid", data.destination_location_id).text(locationText);
            }
        }, 500);
    }

    // Populate form fields
    $("input[name='estimated-date']").val(data.estimated_date || '');
    $("input[name='tracking-number']").val(data.tracking_number || '');
    $("input[name='tag']").val(data.tags || '');
    
    // Populate note to supplier
    $("textarea[name='note_to_supplier']").val(data.note_to_supplier || '');

    // Populate shipping carrier
    setTimeout(() => {
        if (data.ship_carrier_id) {
            $("#shipping_carrier").val(data.ship_carrier_id);
            $("#shipping_carrier").selectpicker('refresh');
        }
    }, 500);

    // Populate product table
    if (data.transferInventoryItems && data.transferInventoryItems.length > 0) {
        $("#product_table_one").removeClass("d-none");
        let productTable = '';
        let totalItems = 0;

        data.transferInventoryItems.forEach(element => {
            totalItems += parseInt(element.quantity) || 0;
            let productImage = element.product?.thumbnail_img ? AssetsPath + element.product.thumbnail_img : '';
            let variantImage = element.variant?.image ? AssetsPath + element.variant.image : '';
            let imageSrc = variantImage || productImage;
            let placeholderSVG = `<svg width='60' height='60' viewBox='0 0 42 42' fill='none' xmlns='http://www.w3.org/2000/svg'>
                <rect x='1' y='1' width='40' height='40' rx='4' fill='white'/>
                <rect x='1' y='1' width='40' height='40' rx='4' stroke='#232323' stroke-opacity='0.1'/>
                <path d='M26.8333 13.5H15.1667C14.2462 13.5 13.5 14.2462 13.5 15.1667V26.8333C13.5 27.7538 14.2462 28.5 15.1667 28.5H26.8333C27.7538 28.5 28.5 27.7538 28.5 26.8333V15.1667C28.5 14.2462 27.7538 13.5 26.8333 13.5Z' stroke='#8D8D8D' stroke-linecap='round' stroke-linejoin='round'/>
                <path d='M18.0834 19.3334C18.7737 19.3334 19.3334 18.0834 19.3334 17.393 18.7737 16.8334 18.0834 16.8334C17.393 16.8334 16.8334 17.393 16.8334 18.0834C16.8334 18.7737 17.393 19.3334 18.0834 19.3334Z' stroke='#8D8D8D' stroke-linecap='round' stroke-linejoin='round'/>
                <path d='M28.5 23.5L24.3333 19.3334L15.1666 28.5' stroke='#8D8D8D' stroke-linecap='round' stroke-linejoin='round'/>
            </svg>`;

            productTable += `<tr>
                <td style="display: none;">
                    <input name="product_uuid" value="${element.product?.uuid || ''}" type="text" hidden>
                    <input name="variant_uuid" value="${element.variant?.uuid || ''}" type="text" hidden>
                    <input name="product_image" value="${productImage}" type="text" hidden>
                    <input name="variant_price" class="product-price" value="${element.unit_price || 0}" type="text" hidden>
                    <input name="sku" value="${element.variant?.sku || element.product?.sku || ''}" type="text" hidden>
                </td>
                <td>
                    <div style="width: auto;" class="product-name">
                        ${imageSrc ? `<img src="${imageSrc}" alt="Image" class="size-50px img-fit">` : placeholderSVG}
                        <div>
                            <span>${element.product?.name || 'Unknown Product'}</span>
                            ${element.variant ? `<span class="varient-text">${element.variant.variant}</span>` : ''}
                        </div>
                    </div>
                </td>
                <td>
                    <div class="sku-input">
                        <input type="text" name="sku" class="form-control d-none" value="${element.variant?.sku || element.product?.sku || ''}" placeholder="Enter SKU">
                    </div>
                </td>
                <td>
                    <div class="sku-input">
                        <input type="number" name="input-quantity" oninput="calculatePOS(this)" value="${element.quantity || 0}" class="form-control custom-qty">
                    </div>
                </td>
                <td>
                    <a class="close" href="#">
                        <svg width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16">
                            <path d="M10.72 11.78a.75.75 0 1 0 1.06-1.06l-2.72-2.72 2.72-2.72a.75.75 0 0 0-1.06-1.06l-2.72 2.72-2.72-2.72a.75.75 0 0 0-1.06 1.06l2.72 2.72-2.72 2.72a.75.75 0 1 0 1.06 1.06l2.72-2.72z" fill="#ed2121"></path>
                        </svg>
                    </a>
                </td>
            </tr>`;
        });

        $("#variantOrdernumber").html(totalItems);
        $("#product_table_one tbody").empty().append(productTable);
        calculatePOS();
    }
}

        $(document).on('click', '#product_table_one .close', function (e) {
            e.preventDefault();
            
            $(this).closest('tr').remove();
            
            calculatePOS();
            
            let remainingRows = $("#product_table_one tbody tr").length;
            $("#variantOrdernumber").html(remainingRows);
            
            if (remainingRows === 0) {
                $("#product_table_one").addClass("d-none");
            }
        });

       $('.receive-transfer-inventory').on('click', function (e) {
            e.preventDefault();
            const urlParams = new URLSearchParams(window.location.search);
            const id = urlParams.get('id');
            
            if (!id) {
                showToast('Purchase order ID not found in URL.', 'Error', 'error');
                return;
            }
            
                // Redirect to receiving page with ID parameter
                page = "transfer-receiving";
                window.location.assign(`?P=${page}&M=${menu_id}&id=${id}`);
            });

    $(document).on('click', '.custom-button-browse', function (e) {
    e.preventDefault();
    
    let originUUID = $("#Selectsupplier").attr("data-uuid");
    let destinationUUID = $("#Selectlocation").attr("data-uuid");
    
    if (!originUUID) {
        showToast('Please select an origin first', 'Error', 'error');
        return false; 
    }

    if (originUUID === destinationUUID) {
        showToast('Origin and destination locations cannot be the same', 'Error', 'error');
        return false; 
    }
    
    $('#exampleModalOne').modal('show');
});

    $("#duplicateTextarea").on("input", function () {
        var currentLength = $(this).val().length;
        $("#current").text(currentLength);
        if (currentLength >= 5000) {
            $(this).val($(this).val().substring(0, 5000));
        }
    });

    // Also handle the main note textarea
    $("textarea[name='note_to_supplier']").on("input", function () {
        var currentLength = $(this).val().length;
        $("#current").text(currentLength);
        if (currentLength >= 5000) {
            $(this).val($(this).val().substring(0, 5000));
        }
    });

    $(document).on("change", ".table-first-item .table-row.parent input[type='checkbox']", function () {
        let isChecked = $(this).prop("checked");
        $(this).closest('.table-first-item').find(".table-row.child input[type='checkbox']").prop("checked", isChecked);
    });

    $(document).on("change", ".table-first-item .table-row.child input[type='checkbox']", function () {
        let parentCheckbox = $(this).closest('.table-first-item').find(".table-row.parent input[type='checkbox']");
        let allChildren = $(this).closest('.table-first-item').find(".table-row.child input[type='checkbox']");
        let anyChildChecked = allChildren.is(":checked");
        parentCheckbox.prop("checked", anyChildChecked);
    });

    $(document).on("click", function (event) {
        if (!$(event.target).closest(".select-dropdown").length) {
            $(".select-dropdown").removeClass("show");
        }
    });

    $(".select-dropdown .droupdown-head").on("click", function (e) {
        e.preventDefault();
        $(".select-dropdown").not($(this).parent()).removeClass("show");
        $(this).parent().toggleClass("show");
    });

    $("#select_supplier").on("click", "li", function () {
    let selectedText = $(this).text().trim();
    let selectedUUID = $(this).find('input').attr("data-uuid");
    let selectedType = $(this).find('input').attr("data-type");
    $("#Selectsupplier").attr("data-uuid", selectedUUID);
    $("#Selectsupplier").attr("data-type", selectedType);
    $("#Selectsupplier").text(selectedText);
    $(".select-dropdown").removeClass("show");
});

    $("#select_location").on("click", "li", function () {
        let selectedText = $(this).text().trim();
        let selectedUUID = $(this).find('input').attr("data-uuid");
        $("#Selectlocation").attr("data-uuid", selectedUUID).text(selectedText);
        $(".select-dropdown").removeClass("show");
    });

    $("#AddProduct").on("click", function () {
        let productList = $(".table-first-item .custom-checkbox input[name='product_check']:checked").parents(".parent");
        let productArray = [];
        productList.each(function () {
            let checkProductVar = $(this).parents(".table-first-item").find(".child .custom-checkbox input[name='product_check']:checked");
            let productUuid = $(this).find(".product-name").attr("data-product-uuid");
            let productTitle = $(this).find(".product-name").text().trim();
            let productPrice = parseFloat($(this).find(".product-name").attr("data-product-price")) || 0;
            let productImage = $(this).find(".product-name").attr("data-product-image") || '';
            let productSku = $(this).find(".product-name").attr("data-product-sku") || '';

            if (checkProductVar.length > 0) {
                checkProductVar.each(function () {
                    let productVarUuid = $(this).parents(".child").find(".product-name").attr("data-variant-uuid");
                    let variantPrice = parseFloat($(this).parents(".child").find(".product-name").attr("data-product-price")) || 0;
                    let variantSku = $(this).parents(".child").find(".product-name").attr("data-variant-sku") || $(this).parents(".child").find(".product-name").text().trim();
                    let productStock = {
                        "variant_uuid": productVarUuid,
                        "product_uuid": productUuid,
                        "name": productTitle,
                        "variant": $(this).parents(".child").find(".product-name").text().trim(),
                        "qty": $(this).parents(".child").find(".total-name").text().trim(),
                        "img": $(this).parents(".child").find(".product-name img").attr("src"),
                        "sku": variantSku,
                        "price": variantPrice,
                        "product_image": $(this).parents(".child").find(".product-name").attr("data-product-image") || ''
                    };
                    productArray.push(productStock);
                });
            } else {
                let productData = {
                    "variant_uuid": "",
                    "product_uuid": productUuid,
                    "name": productTitle,
                    "variant": "",
                    "qty": $(this).find(".total-name").text().trim(),
                    "img": $(this).find(".product-name img").attr("src"),
                    "sku": productSku,
                    "price": productPrice,
                    "product_image": productImage
                };
                productArray.push(productData);
            }
        });

        $("#product_table_one").removeClass("d-none");
        let productTable = ``;
        productArray.forEach(element => {
            productTable += `<tr>
                <td style="display: none;">
                    <input name="product_uuid" value="${element.product_uuid}" type="text" hidden>
                    <input name="variant_uuid" value="${element.variant_uuid}" type="text" hidden>
                    <input name="product_image" value="${element.product_image}" type="text" hidden>
                    <input name="variant_price" class="product-price" value="${element.price}" type="text" hidden>
                    <input name="sku" value="${element.sku}" type="text" hidden>
                </td>
                <td>
                    <div style="width: auto;" class="product-name">
                        ${element.img ? `<img src="${element.img}" alt="Image" class="size-50px img-fit"
                            onerror="this.onerror=null; this.outerHTML=\`<svg width='60' height='60' viewBox='0 0 42 42' fill='none' xmlns='http://www.w3.org/2000/svg'><rect x='1' y='1' width='40' height='40' rx='4' fill='white'/><rect x='1' y='1' width='40' height='40' rx='4' stroke='#232323' stroke-opacity='0.1'/><path d='M26.8333 13.5H15.1667C14.2462 13.5 13.5 14.2462 13.5 15.1667V26.8333C13.5 27.7538 14.2462 28.5 15.1667 28.5H26.8333C27.7538 28.5 28.5 27.7538 28.5 26.8333V15.1667C28.5 14.2462 27.7538 13.5 26.8333 13.5Z' stroke='#8D8D8D' stroke-linecap='round' stroke-linejoin='round'/><path d='M18.0834 19.3334C18.7737 19.3334 19.3334 18.0834 19.3334 17.393 18.7737 16.8334 18.0834 16.8334C17.393 16.8334 16.8334 17.393 16.8334 18.0834C16.8334 18.7737 17.393 19.3334 18.0834 19.3334Z' stroke='#8D8D8D' stroke-linecap='round' stroke-linejoin='round'/><path d='M28.5 23.5L24.3333 19.3334L15.1666 28.5' stroke='#8D8D8D' stroke-linecap='round' stroke-linejoin='round'/></svg>\`;">`
                            : `<svg width='60' height='60' viewBox='0 0 42 42' fill='none' xmlns='http://www.w3.org/2000/svg'><rect x='1' y='1' width='40' height='40' rx='4' fill='white'/><rect x='1' y='1' width='40' height='40' rx='4' stroke='#232323' stroke-opacity='0.1'/><path d='M26.8333 13.5H15.1667C14.2462 13.5 13.5 14.2462 13.5 15.1667V26.8333C13.5 27.7538 14.2462 28.5 15.1667 28.5H26.8333C27.7538 28.5 28.5 27.7538 28.5 26.8333V15.1667C28.5 14.2462 27.7538 13.5 26.8333 13.5Z' stroke='#8D8D8D' stroke-linecap='round' stroke-linejoin='round'/><path d='M18.0834 19.3334C18.7737 19.3334 19.3334 18.0834 19.3334 17.393 18.7737 16.8334 18.0834 16.8334C17.393 16.8334 16.8334 17.393 16.8334 18.0834C16.8334 18.7737 17.393 19.3334 18.0834 19.3334Z' stroke='#8D8D8D' stroke-linecap='round' stroke-linejoin='round'/><path d='M28.5 23.5L24.3333 19.3334L15.1666 28.5' stroke='#8D8D8D' stroke-linecap='round' stroke-linejoin='round'/></svg>`}
                        <div>
                            <span>${element.name}</span>
                            ${element.variant ? `<span class="varient-text">${element.variant}</span>` : ''}
                        </div>
                    </div>
                </td>
                <td>
                    <div class="sku-input">
                        <input type="text" name="sku" class="form-control d-none" value="${element.sku || ''}" placeholder="Enter SKU">
                    </div>
                </td>
                <td>
                    <div class="sku-input">
                        <input type="number" name="input-quantity" oninput="calculatePOS(this)" value="${element.qty}" class="form-control custom-qty">
                    </div>
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
        $("#product_table_one tbody").empty().append(productTable);
        calculatePOS();
    });

    $("#dataSave").on("click", function () {
    let originLocationUUID = $("#Selectsupplier").attr("data-uuid");
    let originType = $("#Selectsupplier").attr("data-type");
    let destinationLocationUUID = $("#Selectlocation").attr("data-uuid");
    let shipCarrier = $("#shipping_carrier").val();
    let trackingNumber = $("input[name='tracking-number']").val();
    let EstimatedDate = $("input[name='estimated-date']").val();
    let tags = $("input[name='tag']").val();
    let note_to_supplier = $("textarea[name='note_to_supplier']").val();

    if (originLocationUUID === destinationLocationUUID) {
        showToast('Origin and destination locations cannot be the same', 'Error', 'error');
        return;
    }

    let submitData = {
        "origin_location_id": originLocationUUID,
        "origin_type": originType, // Add origin type (supplier or location)
        "destination_location_id": destinationLocationUUID,
        "ship_carrier_id": shipCarrier,
        "tracking_number": trackingNumber,
        "estimated_date": EstimatedDate,
        "tags": tags,
        "note_to_supplier": note_to_supplier,
        "status": "draft",
        "items": []
    };

    let tableRows = document.querySelectorAll("#product_table_one tbody tr");
    tableRows.forEach(row => {
        let quantity = parseInt(row.querySelector("input[name='input-quantity']").value) || 0;
        let unitPrice = parseFloat(row.querySelector("input[name='variant_price']").value) || 0;
        let rowData = {
            product_id: row.querySelector("input[name='product_uuid']").value,
            variant_id: row.querySelector("input[name='variant_uuid']").value,
            quantity: quantity,
            unit_price: unitPrice,
            sku: row.querySelector("input[name='sku']").value || '',
            image: row.querySelector("input[name='product_image']").value || '',
            tax: 0,
            total_amount: quantity * unitPrice
        };
        submitData.items.push(rowData);
    });

    if (!submitData.origin_location_id) {
        showToast("Origin field is required.", "Error", "error");
        return;
    }
    if (!submitData.destination_location_id) {
        showToast("Destination field is required.", "Error", "error");
        return;
    }
    if (submitData.items.length === 0) {
        showToast("At least one product must be selected.", "Error", "error");
        return;
    }

    let url = uuid ? ApiPlugin + '/ecommerce/transferinventory/update/' + uuid : ApiPlugin + '/ecommerce/transferinventory/store';
    $.ajax({
        url: url,
        type: "POST",
        data: JSON.stringify(submitData),
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
                $("#product_table_one").addClass("d-none");
                $("#product_table_one tbody").empty();
                $("#variantOrdernumber").html("0");
                showToast(response.message, 'Success', 'success', "?P=transfer-inventory&M=" + menu_id);
            } else {
                showToast(response.message || "Something went wrong!", 'Error', 'error');
            }
        },
        error: function (xhr, status, error) {
            imgload.hide();
            console.error("AJAX Error: ", error);
            showToast("Server error occurred. Please try again.", 'Error', 'error');
        }
    });
});

    $("#ProductItem").on("click", function () {
        table_product();
    });

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
});


function calculatePOS(e) {
    let subtotalPO = 0;
    let itemTotal = 0;

    $("#product_table_one tbody tr").each(function () {
        let qty = parseInt($(this).find("input[name='input-quantity']").val()) || 0;
        let price = parseFloat($(this).find("input[name='variant_price']").val()) || 0;
        itemTotal += qty;
        subtotalPO += qty * price;
    });

    $("#item-total").text(itemTotal);
    $("#po-subtotal").text("Rs " + subtotalPO.toFixed(2));
    $("#tax-included").text("Rs 0.00");
    $("#shipping-fee").text("Rs " + (typeof shippingFee !== 'undefined' ? shippingFee.toFixed(2) : '0.00'));
    $("#grand-total").text("Rs " + (subtotalPO + (typeof shippingFee !== 'undefined' ? shippingFee : 0)).toFixed(2));
}

// Fixed function to return Promise for proper sequencing
function select_supplier() {
    return Promise.all([
        $.ajax({
            url: ApiPlugin + '/ecommerce/get_active_supplier',
            type: "GET",
            contentType: "application/json",
            dataType: "json",
            beforeSend: function (xhr) {
                xhr.setRequestHeader("Authorization", "Bearer " + strkey);
                xhr.setRequestHeader("menu-uuid", menu_id);
                imgload.show();
            }
        }),
        $.ajax({
            url: ApiPlugin + '/ecommerce/warehouse/get_active_warehouse_locations',
            type: "GET",
            contentType: "application/json",
            dataType: "json",
            beforeSend: function (xhr) {
                xhr.setRequestHeader("Authorization", "Bearer " + strkey);
                xhr.setRequestHeader("menu-uuid", menu_id);
                imgload.show();
            }
        })
    ]).then(function ([supplierResponse, locationResponse]) {
        imgload.hide();
        let locationData = locationResponse.data;
        let htmlLi = ``;

        // Add warehouse locations to the dropdown
        for (const key in locationData) {
            let item = locationData[key];
            htmlLi += ` <li>
                            <label class="custom-checkbox">
                                <input style="display:none;" name="market-radio" data-uuid="${item.uuid}" data-type="location" value="${item.location_name}" type="checkbox">
                                ${item.location_name}
                            </label>
                        </li>`;
        }

        $("#select_supplier").empty(); // Clear existing options
        $("#select_supplier").append(htmlLi);
    }).catch(function (error) {
        imgload.hide();
        console.error("Error fetching origin data:", error);
        showToast("Error loading origin options. Please try again.", "Error", "error");
    });
}

// Fixed function to return Promise for proper sequencing
function select_location() {
    return $.ajax({
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
            $("#select_location").empty(); // Clear existing options to prevent duplicates
            for (const key in data) {
                let item = data[key];
                htmlLi += ` <li>
                                <label class="custom-checkbox">
                                    <input style="display:none;" name="market-radio" data-uuid="${item.uuid}" value="${item.location_name}" type="checkbox">
                                    ${item.location_name}
                                </label>
                            </li>`;
            }
            $("#select_location").append(htmlLi);
        }
    });
}

// Fixed function to return Promise and prevent duplicates
function shipping_carrier() {
    return $.ajax({
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
                parentDropdownCarrier.empty(); // Clear existing options to prevent duplicates
                parentDropdownCarrier.append('<option value="" selected>Select shipping carrier</option>');
                response.data.forEach(carrier => {
                    let spaces = ''.repeat(carrier.level || 0);
                    parentDropdownCarrier.append(
                        `<option value="${carrier.uuid}">${spaces} ${carrier.name}</option>`
                    );
                });
                } else {
                console.error('Unexpected response:', response);
            }
        },
        error: function (xhr, status, error) {
            console.error('Error fetching carriers:', status, error);
        }
    });
}


function table_product() {
    let location_id = $("#Selectsupplier").attr("data-uuid");
    $.ajax({
        url: ApiPlugin + '/ecommerce/product/get_active_products',
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
            let data = response.data;
            let placeholderSVG = `<svg width="60" height="60" viewBox="0 0 42 42" fill="none" xmlns="http://www.w3.org/2000/svg">
                <rect x="1" y="1" width="40" height="40" rx="4" fill="white"/>
                <rect x="1" y="1" width="40" height="40" rx="4" stroke="#232323" stroke-opacity="0.1"/>
                <path d="M26.8333 13.5H15.1667C14.2462 13.5 13.5 14.2462 13.5 15.1667V26.8333C13.5 27.7538 14.2462 28.5 15.1667 28.5H26.8333C27.7538 28.5 28.5 27.7538 28.5 26.8333V15.1667C28.5 14.2462 27.7538 13.5 26.8333 13.5Z" stroke="#8D8D8D" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M18.0834 19.3334C18.7737 19.3334 19.3334 18.0834C19.3334 17.393 18.7737 16.8334 18.0834 16.8334C17.393 16.8334 16.8334 17.393 16.8334 18.0834C16.8334 18.7737 17.393 19.3334 18.0834 19.3334Z" stroke="#8D8D8D" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M28.5 23.5L24.3333 19.3334L15.1666 28.5" stroke="#8D8D8D" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>`;

            // Collect UUIDs of products and variants already in the product table
            let selectedProducts = [];
            let selectedVariants = [];
            $("#product_table_one tbody tr").each(function () {
                let productUuid = $(this).find("input[name='product_uuid']").val();
                let variantUuid = $(this).find("input[name='variant_uuid']").val();
                selectedProducts.push(productUuid);
                if (variantUuid) {
                    selectedVariants.push(variantUuid);
                }
            });

            let htmlLi = ``;
            for (const key in data) {
                let item = data[key];
                let parentImage = item.thumbnail_img ? `<img src="${AssetsPath + item.thumbnail_img}" alt="Image" class="size-50px img-fit">` : placeholderSVG;
                // Check if the product is already selected
                let isProductChecked = selectedProducts.includes(item.uuid) ? 'checked' : '';
                htmlLi += ` <div class="table-first-item product-${item.uuid}">
                    <div class="table-row parent">
                        <div class="custom-checkbox-main form-group d-inline-block">
                            <label class="custom-checkbox">
                                <input type="checkbox" name="product_check" ${isProductChecked}>
                                <span class="aiz-square-check"></span>
                            </label>
                        </div>
                        <div class="product-name" data-product-uuid="${item.uuid}" data-product-price="${item.current_stock?.price || 0}" data-product-image="${item.thumbnail_img || ''}" data-product-sku="${item.sku || ''}">
                            ${parentImage}
                            <span>${item.name}</span>
                        </div>
                        <div class="total-name">
                            <span>${item.current_stock?.qty || 0}</span>
                        </div>
                    </div>`;
                if (item.product_stocks && item.product_stocks.length > 0) {
                    htmlLi += item.product_stocks.map((stockItem) => {
                        let childImage = stockItem.image ? `<img src="${AssetsPath + stockItem.image}" alt="Image" class="size-50px img-fit">` : placeholderSVG;
                        // Check if the variant is already selected
                        let isVariantChecked = selectedVariants.includes(stockItem.uuid) ? 'checked' : '';
                        return `
                        <div class="table-row child left-side-item productStock-${stockItem.uuid}">
                            <div class="custom-checkbox-main form-group d-inline-block">
                                <label class="custom-checkbox">
                                    <input type="checkbox" name="product_check" ${isVariantChecked}>
                                    <span class="aiz-square-check"></span>
                                </label>
                            </div>
                            <div class="product-name" data-product-uuid="${item.uuid}" data-variant-uuid="${stockItem.uuid}" data-product-price="${stockItem.price || 0}" data-product-image="${stockItem.image || ''}" data-variant-sku="${stockItem.sku || ''}">
                                ${childImage}
                                <span>${stockItem.sku || item.name}</span>
                            </div>
                            <div class="total-name">
                                <span>${stockItem.qty || 0}</span>
                            </div>
                        </div>`;
                    }).join('');
                }
                htmlLi += `</div>`;
            }
            $("#table_product").empty().append(htmlLi);
        }
    });
}

document.getElementById("SupplierForm").addEventListener("submit", function (e) {
    e.preventDefault();
    const company = this.querySelector("[name='company']");
    let status = true;
    if (company.value == "") {
        company.parentElement.classList.add("error");
        company.parentElement.querySelector(".error-txt").innerHTML = "Company can't be blank";
        status = false;
    }
    if (status) {
        alert("Supplier Form Submitted");
    }
});

document.querySelectorAll("#SupplierForm input").forEach(function (input) {
    input.addEventListener("input", function () {
        input.parentElement.classList.remove("error");
        input.parentElement.querySelector(".error-txt").innerHTML = "";
    });
});

$("#ProductItem").on("click", function () {
    table_product();
});

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

$(document).on('click', '#delete-transfer', function (e) {
    e.preventDefault();

    const urlParams = new URLSearchParams(window.location.search);
    const uuid = urlParams.get('id');

    if (!uuid) {
        showToast('Invalid transfer inventory ID.', 'Error', 'error');
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
                url: ApiPlugin + '/ecommerce/transferinventory/delete/' + uuid,
                type: "DELETE",
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
                        showToast(response.message || 'Transfer inventory deleted successfully.', 'Success', 'success');
                        setTimeout(() => {
                            window.location.href = '?P=transfer-inventory&M=' + menu_id;
                        }, 1500);
                    } else {
                        showToast(response.message || 'Failed to delete transfer inventory.', 'Error', 'error');
                    }
                },
                error: function (xhr) {
                    imgload.hide();
                    let errorMsg = 'Failed to delete transfer inventory';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMsg = xhr.responseJSON.message;
                    }
                    showToast(errorMsg, 'Error', 'error');
                }
            });
        }
    });
});
