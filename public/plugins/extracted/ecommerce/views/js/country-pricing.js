document.title = "Dashboard | Product & Pricing";
// Initialize variables
let currentChannelUuid = ''; // Will store the channel UUID from URL
let currentFilter = 'all'; // 'all', 'included', 'excluded'
let allProducts = []; // Store all products for client-side operations
let currentSort = { field: 'name', direction: 'asc' }; // Track current sort

// Initialize the page
$(document).ready(function() {
    // Get channel UUID from URL parameters
    const urlParams = new URLSearchParams(window.location.search);
    currentChannelUuid = urlParams.get('uuid');
    
    if (!currentChannelUuid) {
        showToast("Market UUID not found.", "Error", "error");
        return;
    }
    fetchActiveCurrencies();
    // Set up filter buttons
    $(".filter-btns a").on("click", function(e) {
        e.preventDefault();
        $(".filter-btns a").removeClass("active");
        $(this).addClass("active");
        
        const filter = $(this).text().toLowerCase();
        if (filter.includes("all")) {
            fetchAndDisplayProducts('all');
        } else if (filter.includes("included")) {
            fetchAndDisplayProducts('included');
        } else if (filter.includes("excluded")) {
            fetchAndDisplayProducts('excluded');
        }
    });
    
    // Fetch market details to populate saved percentage
    fetchMarketDetails(currentChannelUuid);
    
    // Fetch and display products when page loads
    fetchAndDisplayProducts("all");
    
    // Apply price adjustment to all products
    $("#applyPriceAdjustment").off("click").on("click", function() {
        const adjustmentType = $("#PriceAdjustment").val();
        const percentage = parseFloat($("#adjustmentPercentage").val());
        
        if (isNaN(percentage) || percentage < 0) {
            showToast("Please enter a valid percentage greater than or equal to 0", "Error", "error");
            return;
        }

        if(percentage > 100){
            showToast("Please enter a valid percentage less than or equal to 100", "Error", "error");
            return;
        }
        
        if (!currentChannelUuid) {
            showToast("Market UUID not found.", "Error", "error");
            return;
        }
        
        // Prepare data for API
        const priceAdjustmentData = {
            price_adjustment: adjustmentType,
            price_adjustment_value: percentage
        };
        
        // Call API to update market with price adjustment
        updateMarketPriceAdjustment(currentChannelUuid, priceAdjustmentData);
    });
      let searchTimeout;
    $("#searchInput").on("input", function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            applySearch($(this).val());
        }, 300);
    });
    
    // Sort options handlers
    // Modify the radio button change handler
$(".product-sort input[type='radio']").on("change", function() {
    const sortOption = $(this).val();
    let sortField, sortDirection;
    
    switch(sortOption) {
        case "1": // Product title
            sortField = "name";
            sortDirection = currentSort.field === "name" && currentSort.direction === "asc" ? "desc" : "asc";
            // Show A-Z/Z-A buttons
            $("#sortAZ, #sortZA").show();
            $("#sortAsc, #sortDesc").hide();
            break;
        case "2": // Updated
            sortField = "updated_at";
            sortDirection = currentSort.field === "updated_at" && currentSort.direction === "asc" ? "desc" : "asc";
            // Show Ascending/Descending buttons
            $("#sortAZ, #sortZA").hide();
            $("#sortAsc, #sortDesc").show();
            break;
        case "3": // Created
            sortField = "created_at";
            sortDirection = currentSort.field === "created_at" && currentSort.direction === "asc" ? "desc" : "asc";
            // Show Ascending/Descending buttons
            $("#sortAZ, #sortZA").hide();
            $("#sortAsc, #sortDesc").show();
            break;
        default:
            sortField = "name";
            sortDirection = "asc";
    }
    
    currentSort = { field: sortField, direction: sortDirection };
    sortProducts(sortField, sortDirection);
});

// Add new sort buttons for Ascending/Descending
$("#sortAsc").on("click", function(e) {
    e.preventDefault();
    currentSort = { field: currentSort.field, direction: "asc" };
    sortProducts(currentSort.field, "asc");
});

$("#sortDesc").on("click", function(e) {
    e.preventDefault();
    currentSort = { field: currentSort.field, direction: "desc" };
    sortProducts(currentSort.field, "desc");
});
// Sort A-Z button
$("#sortAZ").on("click", function(e) {
    e.preventDefault(); // Prevent default anchor behavior
    currentSort = { field: "name", direction: "asc" };
    sortProducts("name", "asc");
});

// Sort Z-A button
$("#sortZA").on("click", function(e) {
    e.preventDefault(); // Prevent default anchor behavior
    currentSort = { field: "name", direction: "desc" };
    sortProducts("name", "desc");
});

// Initialize with A-Z/Z-A visible and others hidden
$("#sortAZ, #sortZA").show();
$("#sortAsc, #sortDesc").hide();
    
    // Filter dropdown handlers
    $(".select-dropdown.status input[type='checkbox']").on("change", function() {
        const statusFilters = [];
        $(".select-dropdown.status input[type='checkbox']:checked").each(function() {
            statusFilters.push($(this).val().toLowerCase());
        });
        
        if (statusFilters.length === 0) {
            populateProductTable(allProducts);
            return;
        }
        
        const filteredProducts = allProducts.filter(product => {
            // Assuming 'isIncluded' determines status (Included/Excluded)
            const productStatus = product.isIncluded ? "active" : "draft";
            return statusFilters.includes(productStatus);
        });
        
        populateProductTable(filteredProducts);
    });
    
    // Product type filter
    $(".select-dropdown input[name='product-type']").on("change", function() {
        const productType = $(this).val().toLowerCase();
        
        if (!productType) {
            populateProductTable(allProducts);
            return;
        }
        
        const filteredProducts = allProducts.filter(product => {
            return product.product_type && product.product_type.toLowerCase() === productType;
        });
        
        populateProductTable(filteredProducts);
    });
    
    // Vendor filter
    $(".select-dropdown input[name='product-vendor']").on("change", function() {
        const vendor = $(this).val().toLowerCase();
        
        if (!vendor) {
            populateProductTable(allProducts);
            return;
        }
        
        const filteredProducts = allProducts.filter(product => {
            return product.vendor && product.vendor.toLowerCase() === vendor;
        });
        
        populateProductTable(filteredProducts);
    });
    
    // Tagged with filter
    $(".select-dropdown input[type='text']").on("input", function() {
        const tag = $(this).val().toLowerCase();
        
        if (!tag) {
            populateProductTable(allProducts);
            return;
        }
        
        const filteredProducts = allProducts.filter(product => {
            if (!product.tags) return false;
            return product.tags.some(t => t.toLowerCase().includes(tag));
        });
        
        populateProductTable(filteredProducts);
    });
    
    // Clear filters
    $(".select-dropdown a").on("click", function(e) {
        e.preventDefault();
        $(this).closest(".select-dropdown").find("input").val("").prop("checked", false);
        populateProductTable(allProducts);
    });
});

// Function to fetch market details and populate price adjustment
function fetchMarketDetails(uuid) {
    $.ajax({
        url: ApiPlugin + "/ecommerce/market/edit_market/" + uuid,
        type: "GET",
        contentType: "application/json",
        beforeSend: function(xhr) {
            xhr.setRequestHeader("Authorization", "Bearer " + strkey);
            xhr.setRequestHeader("menu-uuid", menu_id);
            imgload.show();
        },
        success: function(response) {
            imgload.hide();
            if (response.status_code === 200 && response.data) {
                const marketData = response.data;
                console.log("value", marketData.percentage);
                // Populate price adjustment dropdown and percentage input
                $("#PriceAdjustment").val(marketData.price_adjustment || "increase").trigger("change");
                $("#adjustmentPercentage").val(marketData.percentage || 0);
                
                // Populate default currency
                if (marketData.default_currency) {
                    const currency = marketData.default_currency;
                    const currencyText = `${currency.name} (${currency.symbol})`;
                    $(".form-control.md").html(`<option value="${currency.name} (${currency.symbol})" selected>${currencyText}</option>`);
                }
            } else {
                showToast("Failed to load market details", "Error", "error");
            }
        },
        error: function(xhr, status, error) {
            imgload.hide();
            console.error("Fetch error:", error, xhr.responseText);
            showToast("Failed to load market details. Please try again.", "Error", "error");
        }
    });
}

// Function to update market with price adjustment
function updateMarketPriceAdjustment(uuid, priceAdjustmentData) {
    $.ajax({
        url: ApiPlugin + "/ecommerce/market/update_market",
        type: "POST",
        contentType: "application/json",
        data: JSON.stringify(priceAdjustmentData),
        beforeSend: function(xhr) {
            xhr.setRequestHeader("Authorization", "Bearer " + strkey);
            xhr.setRequestHeader("menu-uuid", menu_id);
            xhr.setRequestHeader("uuid", uuid);
            imgload.show();
        },
        success: function(response) {
            imgload.hide();
            if (response.status_code === 200) {
                showToast("Price adjustment applied successfully", "Success", "success");
                // Refresh products to show updated prices
                fetchAndDisplayProducts(currentFilter);
            } else {
                showToast(response.message || "Failed to apply price adjustment", "Error", "error");
            }
        },
        error: function(xhr, status, error) {
            imgload.hide();
            console.error("Update error:", error, xhr.responseText);
            showToast("Failed to apply price adjustment. Please try again.", "Error", "error");
        }
    });
}

// Function to calculate adjusted price (for UI display)
function calculateAdjustedPrice(basePrice, adjustmentType, percentage) {
    const adjustmentFactor = percentage / 100;
    if (adjustmentType === "increase") {
        return basePrice * (1 + adjustmentFactor);
    } else {
        return basePrice * (1 - adjustmentFactor);
    }
}

// Function to fetch and display products with filter
function fetchAndDisplayProducts(filter = 'all') {
    currentFilter = filter;
    
    if (!currentChannelUuid) {
        showToast("Market UUID not found.", "Error", "error");
        return;
    }
    
    $.ajax({
        url: ApiPlugin + "/ecommerce/product/get_products_with_channels",
        type: "GET",
        data: {
            channel_uuid: currentChannelUuid,
            filter: filter
        },
        contentType: "application/json",
        beforeSend: function(xhr) {
            xhr.setRequestHeader("Authorization", "Bearer " + strkey);
            xhr.setRequestHeader("menu-uuid", menu_id);
            imgload.show();
        },
        success: function(response) {
            imgload.hide();
            
            if (response.status_code === 200) {
                allProducts = response.data || []; // Store all products
                
                // Apply initial sorting
                sortProducts(currentSort.field, currentSort.direction);
                
                // Apply search if there's an active search term
                const searchTerm = $("#searchInput").val();
                if (searchTerm) {
                    applySearch(searchTerm);
                } else {
                    // If no products are returned, display empty table with message
                    populateProductTable(allProducts);
                }
                
                // Update counts
                if (response.counts) {
                    updateCounts(response.counts);
                }
            } else {
                showToast("Failed to load products", "Error", "error");
                populateProductTable([]); // Display empty table on failure
            }
        },
        error: function(xhr, status, error) {
            imgload.hide();
            console.error("Fetch products error:", error, xhr.responseText);
            showToast("Failed to load products. Please try again.", "Error", "error");
            populateProductTable([]); // Display empty table on error
        }
    });
}
function applySearch(searchTerm) {
    if (!searchTerm) {
        populateProductTable(allProducts);
        return;
    }
    
    const filteredProducts = allProducts.filter(product => {
        const searchLower = searchTerm.toLowerCase();
        return (
            product.name.toLowerCase().includes(searchLower) ||
            (product.description && product.description.toLowerCase().includes(searchLower)) ||
            (product.product_type && product.product_type.toLowerCase().includes(searchLower)) ||
            (product.vendor && product.vendor.toLowerCase().includes(searchLower))
        );
    });
    
    populateProductTable(filteredProducts);
}

// Function to sort products
function sortProducts(field, direction = 'asc') {
    if (!allProducts.length) return;
    
    allProducts.sort((a, b) => {
        let valueA = a[field];
        let valueB = b[field];
        
        // Handle cases where field might be undefined
        if (valueA === undefined) valueA = '';
        if (valueB === undefined) valueB = '';
        
        // For string comparison (product names)
        if (field === 'name') {
            valueA = valueA.toLowerCase();
            valueB = valueB.toLowerCase();
            
            if (direction === 'asc') {
                return valueA.localeCompare(valueB);
            } else {
                return valueB.localeCompare(valueA);
            }
        }
        // For date fields (created_at, updated_at)
        else if (field === 'created_at' || field === 'updated_at') {
            const dateA = new Date(valueA);
            const dateB = new Date(valueB);
            
            if (direction === 'asc') {
                return dateA - dateB;
            } else {
                return dateB - dateA;
            }
        }
        // For other fields (numbers, etc.)
        else {
            if (direction === 'asc') {
                return valueA - valueB;
            } else {
                return valueB - valueA;
            }
        }
    });
    
    populateProductTable(allProducts);
}

function fetchActiveCurrencies() {
    $.ajax({
        url: ApiPlugin + "/ecommerce/get_active_currencies",
        type: "GET",
        contentType: "application/json",
        beforeSend: function(xhr) {
            xhr.setRequestHeader("Authorization", "Bearer " + strkey);
            xhr.setRequestHeader("menu-uuid", menu_id);
            imgload.show();
        },
        success: function(response) {
            imgload.hide();
            if (response.status_code === 200 && response.data) {
                const currencies = response.data;
                let defaultCurrency = currencies.find(currency => currency.is_default === 1);
                
                // Fallback to PKR if no default currency is found
                if (!defaultCurrency) {
                    defaultCurrency = {
                        name: "Pakistani Rupee",
                        symbol: "PKR Rs"
                    };
                }
                
                const currencyText = `${defaultCurrency.name} (${defaultCurrency.symbol})`;
                const $currencySelect = $(".form-control.md");
                
                // Clear existing options and set the default currency
                $currencySelect.empty().append(
                    `<option value="${currencyText}" selected>${currencyText}</option>`
                );
            } else {
                showToast("Failed to load currencies", "Error", "error");
            }
        },
        error: function(xhr, status, error) {
            imgload.hide();
            console.error("Fetch currencies error:", error, xhr.responseText);
            showToast("Failed to load currencies. Please try again.", "Error", "error");
        }
    });
}
// Function to update the product counts in the filter tabs
function updateCounts(counts) {
    if (counts) {
        const totalCount = counts.included + counts.excluded;
        $(".filter-btns a:eq(0) span").text(totalCount); // All count
        $(".filter-btns a:eq(1) span").text(counts.included); // Included count
        $(".filter-btns a:eq(2) span").text(counts.excluded); // Excluded count
    }
}
$('table').on('click', 'a.title, a.country-attribute-pricing', function (e) {
    e.preventDefault();
    
    let currentRow = $(this).closest("tr");
    let uuid = currentRow.find('input[type="checkbox"]').data('uuid');
    
    let page = 'edit-product'; // Change to your actual edit page slug
    window.location.assign(`?P=${page}&M=${menu_id}&id=${uuid}`);
});

// Function to populate the table with product data
function populateProductTable(products) {
    const tbody = $("#Table_View tbody");
    tbody.empty();
    
    if (products.length === 0) {
        // Display empty message when no products are found
        tbody.append(`
            <tr>
                <td colspan="5" class="text-center">No products found.</td>
            </tr>
        `);
        return;
    }
    
    products.forEach(product => {
        // Check if product is included in current channel
        // Use the isIncluded property from API directly
        const isIncluded = product.isIncluded === true;
        const publishingStatus = isIncluded ? "Included" : "Excluded";
        
        const row = `
            <tr>
                <td style="width: 1px;">
                    <div class="form-check m-0">
                        <input class="form-check-input" type="checkbox" data-uuid="${product.uuid}">
                    </div>
                </td>
                <td colspan="2">
                    <div class="collection-table-info">
                        <div class="img">
                            ${product.thumbnail_img ? 
                                `<img src="${AssetsPath + product.thumbnail_img}" alt="${product.name}" style="width:40px;height:40px;object-fit:cover;">` : 
                                `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" width="16" height="16" fill="#8a8a8a"><path d="M10.5 7a1.5 1.5 0 1 0-.001-3.001 1.5 1.5 0 0 0 .001 3.001"></path><path fill-rule="evenodd" d="M7.018 1.5h1.964c.813 0 1.469 0 2 .043.546.045 1.026.14 1.47.366.706.36 1.28.933 1.64 1.639.226.444.32.924.365 1.47.043.531.043 1.187.043 2v1.964c0 .813 0 1.469-.043 2-.045.546-.14 1.026-.366 1.47a3.76 3.76 0 0 1-1.638 1.64c-.445.226-.925.32-1.471.365-.531.043-1.187.043-2 .043h-1.964c-.813 0-1.469 0-2-.043-.546-.045-1.026-.14-1.47-.366a3.76 3.76 0 0 1-1.64-1.638c-.226-.445-.32-.925-.365-1.471-.043-.531-.043-1.187-.043-2v-1.964c0-.813 0-1.469.043-2 .045-.546.14-1.026.366-1.47.36-.706.933-1.28 1.639-1.64.444-.226.924-.32 1.47-.365.531-.043 1.187-.043 2-.043m-1.877 1.538c-.454.037-.715.107-.912.207a2.25 2.25 0 0 0-.984.984c-.1.197-.17.458-.207.912-.037.462-.038 1.057-.038 1.909v1.429l.723-.868a1.75 1.75 0 0 1 2.582-.117l2.695 2.695 1.18-1.18a1.75 1.75 0 0 1 2.604.145l.216.27v-2.374c0-.852 0-1.447-.038-1.91-.037-.453-.107-.714-.207-.911a2.25 2.25 0 0 0-.984-.984c-.197-.1-.458-.17-.912-.207-.462-.037-1.057-.038-1.909-.038h-1.9c-.852 0-1.447 0-1.91.038Zm-2.103 7.821-.006-.08.044-.049 1.8-2.159a.25.25 0 0 1 .368-.016l3.226 3.225a.75.75 0 0 0 1.06 0l1.71-1.71a.25.25 0 0 1 .372.021l1.213 1.516q-.032.09-.07.165c-.216.423-.56.767-.984.983-.197.1-.458.17-.912.207-.462.037-1.057.038-1.909.038h-1.9c-.852 0-1.447 0-1.91-.038-.453-.037-.714-.107-.911-.207a2.25 2.25 0 0 1-.984-.984c-.1-.197-.17-.458-.207-.912"></path></svg>`}
                        </div>
                        <div>
                            <a href="#" class="title">${product.name}</a>
                            <div class="sub-title">
                                <a href="#" class="country-attribute-pricing">View prices</a> • 
                                ${product.total_variations || 0} variants
                            </div>
                        </div>
                    </div>
                </td>
                <td>${publishingStatus}</td>
                <td>
                    <div class="input currency-input">
                        <span class="currency-txt">Rs</span>
                        <input type="number" class="form-control unit-price" id="Price_${product.uuid}" 
                               value="${product.unit_price || 0}" required placeholder="0.00">
                    </div>
                </td>
            </tr>
        `;
        
        tbody.append(row);
    });
    
    bindProductTableEvents();
}

// Function to bind event handlers for product table
function bindProductTableEvents() {
    // Checkbox change events
    $("#Table_View .form-check-input:not(.select-all)").off("change").on("change", function() {
        isAnyChecked();
    });
    
    // Select all checkbox
    $("#Table_View .select-all").off("change").on("change", function() {
        if($(this).prop("checked") === true) {
            $("#Table_View .thead-filter").removeClass("d-none");
            $("#Table_View .form-check-input:not(.select-all)").prop("checked", true);
        } else {
            $("#Table_View .thead-filter").addClass("d-none");
            $("#Table_View .form-check-input:not(.select-all)").prop("checked", false);
        }
        isAnyChecked();
    });
}

// Function to check if any checkboxes are checked
function isAnyChecked() {
    let anyChecked = false;
    let totalCheckedCount = 0;

    $("#Table_View .form-check-input:not(.select-all)").each((i, element) => {
        if ($(element).prop("checked")) {
            totalCheckedCount++;
            if (!anyChecked) {
                anyChecked = true;
            }
        }
    });

    $("#Table_View .thead-filter .selected-count").html(totalCheckedCount);

    if(parseInt(totalCheckedCount) > 0) {
        $("#Table_View .thead-filter").removeClass("d-none");
    } else {
        $("#Table_View .thead-filter").addClass("d-none");
    }

    if (anyChecked) {
        $("#Table_View .select-all").prop("checked", true);
    } else {
        $("#Table_View .select-all").prop("checked", false);
    }
}

// Add function to toggle product inclusion/exclusion
function toggleProductsInChannel(productUuids, include = true) {
    if (!currentChannelUuid || !productUuids || productUuids.length === 0) {
        return;
    }
    
    $.ajax({
        url: ApiPlugin + "/ecommerce/product/toggle_channel_inclusion",
        type: "POST",
        contentType: "application/json",
        data: JSON.stringify({
            channel_uuid: currentChannelUuid,
            product_uuids: productUuids,
            include: include
        }),
        beforeSend: function(xhr) {
            xhr.setRequestHeader("Authorization", "Bearer " + strkey);
            xhr.setRequestHeader("menu-uuid", menu_id);
            imgload.show();
        },
        success: function(response) {
            imgload.hide();
            if (response.status_code === 200) {
                showToast(
                    include ? "Products included successfully" : "Products excluded successfully", 
                    "Success", 
                    "success"
                );
                // Refresh products list
                fetchAndDisplayProducts(currentFilter);
                window.location.reload();
            } else {
                showToast(response.message || "Operation failed", "Error", "error");
            }
        },
        error: function(xhr, status, error) {
            imgload.hide();
            console.error("Operation error:", error, xhr.responseText);
            showToast("Operation failed. Please try again.", "Error", "error");
        }
    });
}

// Add these buttons to the UI and their event handlers
$(document).ready(function() {
    // Bind click events for include/exclude buttons
    $(document).on("click", ".include-selected", function() {
        const selectedUuids = [];
        $("#Table_View .form-check-input:checked:not(.select-all)").each(function() {
            selectedUuids.push($(this).data("uuid"));
        });
        
        if (selectedUuids.length > 0) {
            toggleProductsInChannel(selectedUuids, true);
        } else {
            showToast("No products selected", "Warning", "warning");
        }
    });
    
    $(document).on("click", ".exclude-selected", function() {
        const selectedUuids = [];
        $("#Table_View .form-check-input:checked:not(.select-all)").each(function() {
            selectedUuids.push($(this).data("uuid"));
        });
        
        if (selectedUuids.length > 0) {
            toggleProductsInChannel(selectedUuids, false);
        } else {
            showToast("No products selected", "Warning", "warning");
        }
    });
});

$("#PriceAdjustment").select2({
  width: "100%",
  allowClear: false,
  minimumResultsForSearch: Infinity
});

$("#searchOpen").on("click", function(e){
  e.preventDefault();
  $(".pricing-table-filters .filter-search").removeClass("d-none");
  $(".selectDropdown").removeClass("d-none");
});

$("#searchClose").on("click", function(e){
  e.preventDefault();
  $(".pricing-table-filters .filter-search").addClass("d-none");
  $(".selectDropdown").addClass("d-none");
});

$(".custom-dropdown-main .droupdown-head").on("click", function (e) {
  e.preventDefault();
  $(".droupdown-body").not($(this).parent()).removeClass("show");
  $(this).parent().toggleClass("show");
  e.stopPropagation();
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

$(document).on("click", function (event) {
  if (!$(event.target).closest(".select-dropdown").length) {
    $(".select-dropdown").removeClass("show");
  }
});

$(".selectDropdown").on('change', '.select-dropdown .droupdown-body .custom-checkbox input[type=checkbox]', function (e) {
  const dropdown = $(this).closest(".select-dropdown");
  const checkedCheckboxes = dropdown.find(".droupdown-body input[type=checkbox]:checked");
  const dropdownHead = dropdown.find(".droupdown-head > span:not(.clear-drop):not(.arrow)");
  
  dropdownHead.empty();

  if (checkedCheckboxes.length > 0) {
    let selectedTexts = checkedCheckboxes.map((i, el) => `Status ${$(el).val()}`).get().join(", ");
    dropdownHead.text(selectedTexts);
  } else {
    dropdownHead.text("Status");
  }

  $(this).parents(".select-dropdown").find(".clear-drop").removeClass("d-none");
  $(this).parents(".select-dropdown").find(".arrow").addClass("d-none");
});

$(".selectDropdown").on('change', '.select-dropdown .droupdown-body .custom-radio input[type=radio]', function (e) {
  $(this).parents(".select-dropdown").find(".droupdown-head > span > span").html(`is ${$(this).val()}`);
  $(this).parents(".select-dropdown").find(".clear-drop").removeClass("d-none");
  $(this).parents(".select-dropdown").find(".arrow").addClass("d-none");
});

$(".selectDropdown").on('input', '.select-dropdown .droupdown-body input[type=text]', function (e) {
  $(this).parents(".select-dropdown").find(".droupdown-head > span > span").html(`is ${$(this).val()}`);
  $(this).parents(".select-dropdown").find(".clear-drop").removeClass("d-none");
  $(this).parents(".select-dropdown").find(".arrow").addClass("d-none");
});

$(".selectDropdown").on('click', '.select-dropdown.status .droupdown-body a', function (e) {
  e.preventDefault();
  $(this).parent().find("input[type=checkbox]").prop('checked', false);
  $(this).parent().find("input[type=radio]").prop('checked', false);
  $(this).parent().find("input[type=text]").val('');
  $(this).parents(".select-dropdown").find(".droupdown-head > span:not(.clear-drop):not(.arrow)").html("Status");
  $(this).parents(".select-dropdown").find(".clear-drop").addClass("d-none");
  $(this).parents(".select-dropdown").find(".arrow").removeClass("d-none");
});

$(".selectDropdown").on('click', '.select-dropdown.status .clear-drop', function (e) {
  e.preventDefault();
  $(this).parents(".select-dropdown").find(".droupdown-body input[type=checkbox]").prop('checked', false);
  $(this).parents(".select-dropdown").find(".droupdown-body input[type=radio]").prop('checked', false);
  $(this).parents(".select-dropdown").find(".droupdown-body input[type=text]").val("");
  $(this).parents(".select-dropdown").find(".droupdown-head > span:not(.clear-drop):not(.arrow)").html("Status");
  $(this).parents(".select-dropdown").find(".clear-drop").addClass("d-none");
  $(this).parents(".select-dropdown").find(".arrow").removeClass("d-none");
});

$(".selectDropdown").on('click', '.select-dropdown .droupdown-body a', function (e) {
  e.preventDefault();
  $(this).parent().find("input[type=checkbox]").prop('checked', false);
  $(this).parent().find("input[type=radio]").prop('checked', false);
  $(this).parent().find("input[type=text]").val('');
  $(this).parents(".select-dropdown").find(".droupdown-head > span > span").html("");
  $(this).parents(".select-dropdown").find(".clear-drop").addClass("d-none");
  $(this).parents(".select-dropdown").find(".arrow").removeClass("d-none");
});

$(".selectDropdown").on('click', '.select-dropdown .clear-drop', function (e) {
  e.preventDefault();
  $(this).parents(".select-dropdown").find(".droupdown-body input[type=checkbox]").prop('checked', false);
  $(this).parents(".select-dropdown").find(".droupdown-body input[type=radio]").prop('checked', false);
  $(this).parents(".select-dropdown").find(".droupdown-body input[type=text]").val("");
  $(this).parents(".select-dropdown").find(".droupdown-head > span > span").html("");
  $(this).parents(".select-dropdown").find(".clear-drop").addClass("d-none");
  $(this).parents(".select-dropdown").find(".arrow").removeClass("d-none");
});

$("#Table_View .select-all").on("change", function(){
  if($(this).prop("checked") === true) {
    $("#Table_View .thead-filter").removeClass("d-none");
    $("#Table_View .form-check-input:not(.select-all)").prop("checked", true);
  } else {
    $("#Table_View .thead-filter").addClass("d-none");
    $("#Table_View .form-check-input:not(.select-all)").prop("checked", false);
  }
  isAnyChecked();
});

$("#Table_View .form-check-input:not(.select-all)").on("change", function() {
  isAnyChecked();
});

function isAnyChecked() {
  let anyChecked = false;
  let totalCheckedCount = 0;

  $("#Table_View .form-check-input:not(.select-all)").each((i, element) => {
    if ($(element).prop("checked")) {
      totalCheckedCount++;
      if (!anyChecked) {
        anyChecked = true;
      }
    }
  });

  $("#Table_View .thead-filter .selected-count").html(totalCheckedCount);

  if(parseInt(totalCheckedCount) > 0) {
    $("#Table_View .thead-filter").removeClass("d-none");
  } else {
    $("#Table_View .thead-filter").addClass("d-none");
  }

  if (anyChecked) {
    $("#Table_View .select-all").prop("checked", true);
  } else {
    $("#Table_View .select-all").prop("checked", false);
  }
}


function bindNavItemClick() {
  $(".nav-item.nav-link").off("click").on("click", function () {

    $(".nav-item.nav-link").removeClass("active");
    $(this).addClass("active");

  });
}
bindNavItemClick();