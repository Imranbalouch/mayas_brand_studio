document.title = "Dashboard | Edit Catalog";
var urlParams = new URLSearchParams(window.location.search);
var id = urlParams.get('id');

$(document).ready(function () {
    // Initialize form elements
    initializeForm();
    

    // If an ID is provided, load catalog data
    if (id) {
        fetchCatalogData();
    }

    // Load products when "Browse Products" is clicked
    $('.arrow-svg').on('click', function () {
        table_product();
    });

    // Load currencies when currency edit icon is clicked
    $('.currency-edit').on('click', function () {
        loadCurrencies();
    });

    // Product search in modal
    $(document).on('shown.bs.modal', '#productModal', function () {
        $('#custom_search_po').off('keyup').on('keyup', function () {
            let searchTerm = $(this).val().toLowerCase();
            $('.product-table-body .table-first-item').each(function () {
                let productName = $(this).find('.parent .product-name span').text().toLowerCase();
                $(this).toggle(productName.includes(searchTerm));
            });
        });
    });

    $('#browseProductBtn').on('click', function () {
        getDefaultWarehouseUUID(function (location_id) {
            if (!location_id) {
                return; // No default location, exit
            }

            // Open modal manually
            $('#productModal').modal('show');

            // Call your product loading function
            table_product(location_id);
        });
    });

    // Add products to catalog table
$('#AddProductModal').on('click', function () {
    let productArray = [];
    let baseProductsWithVariants = new Set();

    // First pass: collect all selected variants and mark their base products
    $('.table-first-item .table-row.child input[name="product_check"]:checked').each(function() {
        let parentRow = $(this).closest('.table-first-item');
        let productUuid = parentRow.find('.parent .product-name').data('product-uuid');
        baseProductsWithVariants.add(productUuid);
    });

    // Get all currently selected products from the modal
    let productList = $('.table-first-item .custom-checkbox input[name="product_check"]:checked').closest('.table-row');

    productList.each(function () {
        let isChild = $(this).hasClass('child');
        let parentRow = $(this).closest('.table-first-item');
        let productUuid = isChild 
            ? parentRow.find('.parent .product-name').data('product-uuid')
            : $(this).find('.product-name').data('product-uuid');
        
        // Skip base product if any of its variants are selected
        if (!isChild && baseProductsWithVariants.has(productUuid)) {
            return true; // continue to next iteration
        }

        let variantUuid = isChild ? $(this).find('.product-name').data('variant-uuid') : '';
        let productName = $(this).find('.product-name span').text().trim();
        let productImage = isChild 
            ? $(this).find('.product-name').data('product-image')
            : parentRow.find('.parent .product-name').data('product-image');
        let imgSrc = isChild 
            ? $(this).find('.product-name img').attr('src')
            : parentRow.find('.parent .product-name img').attr('src');
        let productPrice = parseFloat($(this).find('.product-name').data('product-price')) || 0;
        let sku = isChild ? $(this).find('.product-name span').text().trim() : '';

        // For simple products, check variant data for price if productPrice is 0
        if (!isChild && productPrice === 0) {
            let varientData = $(this).find('.product-name').data('varient-data');
            if (varientData && varientData.all && varientData.all.length > 0) {
                productPrice = parseFloat(varientData.all[0].variantPrice) || 0;
            }
        }

        // Format product name - include SKU if it's a variant
        let displayName = sku ? `${parentRow.find('.parent .product-name span').text().trim()} (${sku})` : productName;

        // Only add if not already in the array
        let exists = productArray.some(p => p.product_uuid === productUuid && p.variant_uuid === variantUuid);
        if (!exists) {
            productArray.push({
                product_uuid: productUuid,
                variant_uuid: variantUuid || '',
                name: displayName,
                product_image: productImage,
                img: imgSrc,
                price: productPrice,
                sku: sku
            });
        }
    });

    // Update the product table
    let tableBody = $('#productTable tbody');
    tableBody.empty();

    // Add price column header if not already present
    if ($('#productTable thead tr th').length === 3) {
        $('#productTable thead tr').append('<th>Price</th>');
    }

    productArray.forEach(product => {
        let adjustedPrice = product.price;
        
        tableBody.append(`
            <tr class="product-row" data-product-id="${product.product_uuid}" data-original-price="${product.price}">
                <td>
                    <div class="d-flex align-items-center">
                        ${product.img ? `<img src="${product.img}" alt="${product.name}" class="size-50px img-fit" style="height: 50px; width:50px;">` : placeholderSVG}
                        <span class="ms-2">${product.name}</span>
                    </div>
                </td>
                <td>${product.variant_uuid ? 'Variant' : 'Base Product'}</td>
                <td class="d-none">
                    <input type="checkbox" class="form-check-input product-checkbox" 
                           data-product-id="${product.product_uuid}" 
                           data-variant-id="${product.variant_uuid}" 
                           checked>
                </td>
                <td class="price-cell">
                    <div>
                        <input class="form-control adjusted-price" value="${adjustedPrice.toFixed(2)}" disabled>
                    </div>
                </td>
            </tr>
        `);
    });

    // Update prices based on current percentage and adjustment type
    updateProductPrices();

    $('#productModal').modal('hide');
});

// Function to update prices when percentage or adjustment type changes
function updateProductPrices() {
    let percentage = parseFloat($('input[name="percentage"]').val()) || 0;
    let adjustmentType = $('select[name="price_adjustment"]').val();
    
    $('#productTable tbody tr').each(function() {
        let originalPrice = parseFloat($(this).data('original-price')) || 0;
        let adjustedPrice = originalPrice;
        
        if (adjustmentType === 'Price increase') {
            adjustedPrice = originalPrice * (1 + (percentage / 100));
        } else if (adjustmentType === 'Price decrease') {
            adjustedPrice = originalPrice * (1 - (percentage / 100));
        }
        
        $(this).find('.adjusted-price').text(adjustedPrice.toFixed(2));
    });
}

// Listen for changes in percentage or adjustment type
$('input[name="percentage"]').on('input', function() {
    updateProductPrices();
});

$('select[name="price_adjustment"]').on('change', function() {
    updateProductPrices();
});

    // Parent checkbox controls child checkboxes
    $(document).on('change', '.table-first-item .table-row.parent input[name="product_check"]', function () {
        let isChecked = $(this).prop('checked');
        $(this).closest('.table-first-item').find('.table-row.child input[name="product_check"]').prop('checked', isChecked);
    });

    // Child checkbox affects parent checkbox
    $(document).on('change', '.table-first-item .table-row.child input[name="product_check"]', function () {
        let parentCheckbox = $(this).closest('.table-first-item').find('.table-row.parent input[name="product_check"]');
        let allChildren = $(this).closest('.table-first-item').find('.table-row.child input[name="product_check"]');
        parentCheckbox.prop('checked', allChildren.is(':checked'));
    });

    // Save catalog button click handler
    $('.update').click(function () {
        updateCatalog();
    });

    // Initialize selectpickers
    $('.selectpicker').selectpicker();
});

function initializeForm() {
    // Set default currency
   
}

function fetchCatalogData() {
    $.ajax({
        url: ApiPlugin + '/ecommerce/catalog/edit_catalog/' + id,
        type: 'GET',
        contentType: 'application/json',
        dataType: 'json',
        beforeSend: function (xhr) {
            xhr.setRequestHeader('Authorization', 'Bearer ' + strkey);
            xhr.setRequestHeader('menu-uuid', menu_id);
            $('#img_load').show();
        },
        success: function (response) {
            $('#img_load').hide();
            if (response.status_code === 200) {
                populateCatalogData(response.data);
            } else {
                showToast('Failed to load catalog data', 'Error', 'error');
            }
        },
        error: function (xhr, status, error) {
            $('#img_load').hide();
            showToast('Error loading catalog data: ' + error, 'Error', 'error');
        }
    });
}

function populateCatalogData(catalog) {
    // Basic catalog info
    $('#name').val(catalog.catalog);
    $('.create-title-text').text(catalog.catalog);
    
    // Status
    $('.selectpicker').selectpicker('destroy');
    $('select[name="status"]').val(catalog.status);
    $('.selectpicker').selectpicker('refresh');
    $('#statusDisplay').html(catalog.status == '1' ? '<span class="badge badge-success">Active</span>' : '<span class="badge badge-warning">Draft</span>');
    
    // Pricing adjustment
    $('select[name="price_adjustment"]').val(catalog.price_adjustment);
    $('input[name="percentage"]').val(catalog.percentage);
    
    // Currency
    if (catalog.currency) {
        $('#currencyUuid').val(catalog.currency.uuid);
        $('#currencyDisplay').text(catalog.currency.code);
        $('#currencyDescription').text(catalog.currency.name);
    }

    // Add price column to the table
    $('#productTable thead tr').append('<th>Price</th>');

    // Fetch product details to get names
    if (catalog.products && catalog.products.length > 0) {
        let productIds = catalog.products.map(product => product.product_id);
        
        $.ajax({
            url: ApiPlugin + '/ecommerce/product/get_active_products',
            type: 'GET',
            contentType: 'application/json',
            dataType: 'json',
            beforeSend: function (xhr) {
                xhr.setRequestHeader('Authorization', 'Bearer ' + strkey);
                xhr.setRequestHeader('menu-uuid', menu_id);
                $('#img_load').show();
            },
            success: function (response) {
                $('#img_load').hide();
                if (response.status_code === 200) {
                    let tableBody = $('#productTable tbody');
                    tableBody.empty();

                    // Track base products that have variants
                    let baseProductsWithVariants = new Set();
                    catalog.products.forEach(product => {
                        if (product.variant_id) {
                            baseProductsWithVariants.add(product.product_id);
                        }
                    });

                    catalog.products.forEach(product => {
                        // Skip base product if any of its variants are in the catalog
                        if (!product.variant_id && baseProductsWithVariants.has(product.product_id)) {
                            return;
                        }

                        // Find the product details in the response
                        let productDetails = response.data.find(p => p.uuid === product.product_id);
                        let productName = productDetails ? productDetails.name : 'Unknown Product';
                        let variantId = product.variant_id || '';
                        let sku = '';
                        let imgSrc = '';
                        
                        if (variantId) {
                            // Find the stock entry matching the variant_id
                            let stock = product.product.product_stocks.find(s => s.uuid === variantId);
                            sku = stock ? stock.sku : '';
                            // Use variant image if available, otherwise use product image
                            imgSrc = stock && stock.image 
                                ? AssetsPath + stock.image 
                                : (productDetails && productDetails.thumbnail_img 
                                    ? AssetsPath + productDetails.thumbnail_img 
                                    : '');
                            // Append SKU to product name if variant exists
                            productName = sku ? `${productName} (${sku})` : productName;
                        } else {
                            // For base product, use product thumbnail
                            imgSrc = productDetails && productDetails.thumbnail_img 
                                ? AssetsPath + productDetails.thumbnail_img 
                                : '';
                        }

                        // Format price
                        let originalPrice = product.original_price || 0;
                        let adjustedPrice = product.price || 0;
                        let priceHtml = `
                            <div>
                               
                                <input type="number" class="mb-2 form-control" value="${adjustedPrice.toFixed(2)}" disabled>
                            </div>
                        `;

                        tableBody.append(`
                            <tr class="product-row" data-product-id="${product.product_id}" data-original-price="${originalPrice}">
                                <td>
                                    <div class="d-flex align-items-center">
                                        ${imgSrc ? `<img src="${imgSrc}" alt="${productName}" class="size-50px img-fit" style="height: 50px; width:50px;">` : placeholderSVG}
                                        <span class="ms-2">${productName}</span>
                                    </div>
                                </td>
                                <td>${variantId ? 'Variant' : 'Base Product'}</td>
                                <td class="d-none">
                                    <input type="checkbox" class="form-check-input product-checkbox" 
                                           data-product-id="${product.product_id}" 
                                           data-variant-id="${variantId}" 
                                           checked>
                                </td>
                                <td>${priceHtml}</td>
                            </tr>
                        `);
                    });
                } else {
                    showToast('Failed to load product details', 'Error', 'error');
                }
            },
            error: function (xhr, status, error) {
                $('#img_load').hide();
                showToast('Error loading product details: ' + error, 'Error', 'error');
            }
        });
    }
}

function loadCurrencies() {
    $.ajax({
        url: ApiPlugin + '/ecommerce/get_active_currencies',
        type: 'GET',
        contentType: 'application/json',
        dataType: 'json',
        beforeSend: function (xhr) {
            xhr.setRequestHeader('Authorization', 'Bearer ' + strkey);
            xhr.setRequestHeader('menu-uuid', menu_id);
            $('#img_load').show();
        },
        success: function (response) {
            $('#img_load').hide();
            if (response.status_code === 200) {
                populateCurrenciesModal(response.data);
            } else {
                showToast('Failed to load currencies', 'Error', 'error');
            }
        },
        error: function (xhr, status, error) {
            $('#img_load').hide();
            showToast('Error loading currencies: ' + error, 'Error', 'error');
        }
    });
}

function populateCurrenciesModal(currencies) {
    let modalBody = $('#currencyModal .modal-body');
    modalBody.empty();

    currencies.forEach(currency => {
        modalBody.append(`
            <div class="form-check">
                <input class="form-check-input currency-checkbox" type="radio" name="currency" 
                       id="currency_${currency.uuid}" value="${currency.uuid}" 
                       data-code="${currency.code}" data-name="${currency.name}"
                       ${$('#currencyUuid').val() === currency.uuid ? 'checked' : ''}>
                <label class="form-check-label" for="currency_${currency.uuid}">
                    ${currency.code} - ${currency.name}
                </label>
            </div>
        `);
    });

    // Add currency selection handler
    $('.currency-checkbox').on('change', function () {
        let selectedCurrency = $(this).data('code');
        let currencyName = $(this).data('name');
        $('#currencyDisplay').text(selectedCurrency);
        $('#currencyDescription').text(currencyName);
        $('#currencyUuid').val($(this).val());
        $('#currencyModal').modal('hide');
    });

    $('#currencyModal').modal('show');
}

function getDefaultWarehouseUUID(callback) {
    $.ajax({
        url: ApiPlugin + '/ecommerce/warehouse/get_active_warehouse_locations',
        type: 'GET',
        contentType: 'application/json',
        dataType: 'json',
        beforeSend: function (xhr) {
            xhr.setRequestHeader('Authorization', 'Bearer ' + strkey);
            xhr.setRequestHeader('menu-uuid', menu_id);
            $('#img_load').show();
        },
        success: function (response) {
            $('#img_load').hide();
            if (response.status_code === 200) {
                // Find the warehouse with is_default = 1
                const defaultWarehouse = response.data.find(warehouse => warehouse.is_default === 1);
                if (defaultWarehouse) {
                    callback(defaultWarehouse.uuid); 
                } else {
                    showToast('No default location found', 'Error', 'error');
                    callback(null);
                }
            } else {
                showToast('Failed to load location locations', 'Error', 'error');
                callback(null);
            }
        },
        error: function (xhr, status, error) {
            $('#img_load').hide();
            showToast('Error loading location locations: ' + error, 'Error', 'error');
            callback(null);
        }
    });
}

function table_product(location_id) {
    $.ajax({
        url: ApiPlugin + '/ecommerce/product/get_active_products',
        type: 'GET',
        contentType: 'application/json',
        dataType: 'json',
        data: {
            location_id: location_id
        },
        beforeSend: function (xhr) {
            xhr.setRequestHeader('Authorization', 'Bearer ' + strkey);
            xhr.setRequestHeader('menu-uuid', menu_id);
            $('#img_load').show();
        },
        success: function (response) {
            $('#img_load').hide();
            if (response.status_code === 200) {
                let data = response.data;
                let htmlLi = '';

                // Get currently selected products from the table (if any)
                let selectedProducts = {};
                $('#productTable tbody tr').each(function () {
                    let productId = $(this).data('product-id');
                    let variantId = $(this).find('.product-checkbox').data('variant-id') || '';
                    selectedProducts[`${productId}_${variantId}`] = true;
                });

                data.forEach(item => {
                    // Check if the base product is selected
                    let isParentChecked = selectedProducts[`${item.uuid}_`] ? 'checked' : '';

                    let parentImage = item.thumbnail_img
                        ? `<img src="${AssetsPath + item.thumbnail_img}" alt="Image" class="size-50px img-fit">`
                        : placeholderSVG;

                    // Calculate parent quantity
                    let isVariantProduct = item.product_stocks && item.product_stocks.some(stock => stock.sku);
                    let parentQty = 0;
                    if (isVariantProduct) {
                        parentQty = item.product_stocks.reduce((sum, stock) => {
                            return stock.sku ? sum + (stock.qty || 0) : sum;
                        }, 0);
                    } else if (item.product_stocks && item.product_stocks.length > 0) {
                        parentQty = item.product_stocks[0].qty || 0;
                    }

                    // Set parent price, use variant price if unit_price is 0
                    let parentPrice = item.unit_price || 0;
                    let varientData = item.varient_data ? JSON.parse(item.varient_data) : null;
                    if (parentPrice === 0 && varientData && varientData.all && varientData.all.length > 0) {
                        parentPrice = parseFloat(varientData.all[0].variantPrice) || 0;
                    }

                    htmlLi += `
                        <div class="table-first-item product-${item.uuid}">
                            <div class="table-row parent">
                                <div class="custom-checkbox-main form-group d-inline-block">
                                    <label class="custom-checkbox">
                                        <input type="checkbox" name="product_check" ${isParentChecked}>
                                        <span class="aiz-square-check"></span>
                                    </label>
                                </div>
                                <div class="product-name" data-product-uuid="${item.uuid}" data-product-image="${item.thumbnail_img}" data-product-price="${parentPrice}" data-varient-data='${JSON.stringify(varientData || {})}'>
                                    ${parentImage}
                                    <span>${item.name}</span>
                                </div>
                                <div class="total-name">
                                    <span>${parentQty}</span>
                                </div>
                            </div>`;

                    // Check if product has variants
                    let hasVariants = item.product_stocks && item.product_stocks.some(stock => stock.sku);

                    if (hasVariants) {
                        htmlLi += item.product_stocks.map(stockItem => {
                            if (!stockItem.sku) return '';

                            let isChildChecked = selectedProducts[`${item.uuid}_${stockItem.uuid}`] ? 'checked' : '';

                            let childImage = stockItem.image
                                ? `<img src="${AssetsPath + stockItem.image}" alt="Image" class="size-50px img-fit">`
                                : placeholderSVG;

                            let variantPrice = stockItem.price || 0;

                            return `
                                <div class="table-row child left-side-item productStock-${stockItem.uuid}">
                                    <div class="custom-checkbox-main form-group d-inline-block">
                                        <label class="custom-checkbox">
                                            <input type="checkbox" name="product_check" ${isChildChecked}>
                                            <span class="aiz-square-check"></span>
                                        </label>
                                    </div>
                                    <div class="product-name" data-product-uuid="${item.uuid}" data-variant-uuid="${stockItem.uuid}" data-product-image="${stockItem.image}" data-product-price="${variantPrice}">
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
                });
                $('#table_product').empty().append(htmlLi);
            } else {
                showToast('Failed to load products', 'Error', 'error');
            }
        },
        error: function (xhr, status, error) {
            $('#img_load').hide();
            showToast('Error loading products: ' + error, 'Error', 'error');
        }
    });
}

function prepareCatalogData() {
    let catalogData = {
        catalog: $('#name').val(),
        currency: $('#currencyUuid').val(),
        price_adjustment: $('select[name="price_adjustment"]').val(), // Explicitly target the select element
        percentage: $('input[name="percentage"]').val(),
        status: $('select[name="status"]').val(),
        items: []
    };

    console.log(catalogData);

    // Validate required fields
    if (!catalogData.catalog) {
        showToast('Please enter a catalog title', 'Error', 'error');
        return null;
    }

    if (!catalogData.currency) {
        showToast('Please select a currency', 'Error', 'error');
        return null;
    }

    if (!catalogData.percentage) {
        showToast('Please enter a percentage value', 'Error', 'error');
        return null;
    }

    if (catalogData.percentage > 100) {
        showToast('Please enter a percentage value less than 100', 'Error', 'error');
        return null;
    }

    // Handle products
    $('.product-checkbox:checked').each(function () {
        catalogData.items.push({
            product_id: $(this).data('product-id'),
            variant_id: $(this).data('variant-id') || null
        });
    });

    if (catalogData.items.length === 0) {
        showToast('Please select at least one product', 'Error', 'error');
        return null;
    }

    return catalogData;
}

function updateCatalog() {
    let catalogData = prepareCatalogData();
    if (!catalogData) return;

    $.ajax({
        url: ApiPlugin + '/ecommerce/catalog/update_catalog',
        type: 'POST',
        contentType: 'application/json',
        dataType: 'json',
        data: JSON.stringify({...catalogData, uuid: id}),
        headers: {
            'Authorization': 'Bearer ' + strkey,
            'menu-uuid': menu_id,
            'uuid': id
        },
        beforeSend: function () {
            $('#img_load').show();
        },
        success: function (response) {
            $('#img_load').hide();
            if (response.status_code === 200) {
                showToast('Catalog updated successfully', 'Success', 'success');
                setTimeout(() => {
                    window.location.href = '?P=catalogs&M=' + menu_id;
                }, 1500);
            } else {
                showToast(response.message || 'Error updating catalog', 'Error', 'error');
            }
        },
        error: function (xhr, status, error) {
            $('#img_load').hide();
            let errorMsg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Error updating catalog';
            showToast(errorMsg, 'Error', 'error');
        }
    });
}

const placeholderSVG = `<svg width="60" height="60" viewBox="0 0 42 42" fill="none" xmlns="http://www.w3.org/2000/svg">
    <rect x="1" y="1" width="40" height="40" rx="4" fill="white"/>
    <rect x="1" y="1" width="40" height="40" rx="4" stroke="#232323" stroke-opacity="0.1"/>
    <path d="M26.8333 13.5H15.1667C14.2462 13.5 13.5 14.2462 13.5 15.1667V26.8333C13.5 27.7538 14.2462 28.5 15.1667 28.5H26.8333C27.7538 28.5 28.5 27.7538 28.5 26.8333V15.1667C28.5 14.2462 27.7538 13.5 26.8333 13.5Z" stroke="#8D8D8D" stroke-linecap="round" stroke-linejoin="round"/>
    <path d="M18.0834 19.3334C18.7737 19.3334 19.3334 18.7737 19.3334 18.0834C19.3334 17.393 18.7737 16.8334 18.0834 16.8334C17.393 16.8334 16.8334 17.393 16.8334 18.0834C16.8334 18.7737 17.393 19.3334 18.0834 19.3334Z" stroke="#8D8D8D" stroke-linecap="round" stroke-linejoin="round"/>
    <path d="M28.5 23.5L24.3333 19.3334L15.1666 28.5" stroke="#8D8D8D" stroke-linecap="round" stroke-linejoin="round"/>
</svg>`;