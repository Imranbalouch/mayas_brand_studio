document.title = "Dashboard | Edit Discount";
let uuid = '';
let edit_data = '';

$(document).ready(function () {
  // Get UUID from URL
  var urlParams = new URLSearchParams(window.location.search);
  uuid = urlParams.get('I');

  if (uuid) {
    loadDiscountData(uuid);
  }

  // Initialize form elements
  initializeForm();

  // Method selection toggle
  $('.discount-method-card').click(function() {
    $('.discount-method-card .card').removeClass('border-primary');
    $(this).find('.card').addClass('border-primary');
    const method = $(this).data('method');
    $('#method').val(method);

    // Toggle discount code and name fields
    if (method === 'code') {
      $('#appliesToContainer').addClass('d-none');
      $('#minmaxCard').removeClass('d-none');
      $('#discountNameField').removeClass('d-none');
      $('#automaticNameField').addClass('d-none');
      $('#code').prop('required', true);
      $('#name').prop('required', false);
    } else {
      $('#discountNameField').addClass('d-none');
      $('#minmaxCard').addClass('d-none');
      $('#appliesToContainer').removeClass('d-none');
      $('#automaticNameField').removeClass('d-none');
      $('#code').prop('required', false);
      $('#name').prop('required', true);
    }

    toggleEligibilityAndUsageCards(method);
    toggleAvailabilityCard(method);
    // updateSummary();
  });

  function toggleEligibilityAndUsageCards(method) {
    if (method === 'automatic') {
      $('.customerEligibilityCard').addClass('d-none');
      $('.usageLimitCard').addClass('d-none');
    } else {
      $('.customerEligibilityCard').removeClass('d-none');
      $('.usageLimitCard').removeClass('d-none');
    }
  }

  function toggleAvailabilityCard(method) {
    if (method === 'automatic') {
      $('.availabilityCard').removeClass('d-none');
    } else {
      $('.availabilityCard').addClass('d-none');
    }
  }

  // Set first method card as active by default
  $('.discount-method-card:first').click();

  // Applies to dropdown
  $('#applies_to').change(function() {
    $('#collectionSelection, #productSelection').hide();
    const selectedValue = $(this).val();
    if (selectedValue === 'collections') {
      $('#collectionSelection').show();
      loadCollections();
    } else if (selectedValue === 'specific') {
      $('#productSelection').show();
    }
    // updateSummary();
  });

  // Minimum requirements
  $('input[name="requirement_type"]').change(function() {
    if ($(this).val() === 'amount' || $(this).val() === 'quantity') {
      $('#requirementValueContainer').show();
    } else {
      $('#requirementValueContainer').hide();
    }
    // updateSummary();
  });

  // Customer eligibility
  $('input[name="eligibility"]').change(function() {
    const value = $(this).val();
    
    // Hide all containers first
    $('#customerSegmentsSelection').hide();
    $('#specificCustomersSelection').hide();
    
    if (value === 'specific_groups') {
      $('#customerSegmentsSelection').show();
      loadCustomerSegments();
    } else if (value === 'specific_customers') {
      $('#specificCustomersSelection').show();
      loadCustomers();
    }
    // updateSummary();
  });

  // Usage limits
  $('input[name="uses_customer_limit[]"]').change(function() {
    if ($('input[name="uses_customer_limit[]"][value="total"]').is(':checked')) {
      $('#usageLimitContainer').show();
    } else {
      $('#usageLimitContainer').hide();
    }
    // updateSummary();
  });

  // Discount type change
  $('#type').change(function() {
    if ($(this).val() === 'percentage') {
      $('#value-addon').text('%');
      $('#value').attr('max', '100');
    } else {
      $('#value-addon').text('AED');
      $('#value').removeAttr('max');
    }
    // updateSummary();
  });

  // End date toggle
  $('#enable_end_date').change(function() {
    if ($(this).is(':checked')) {
      $('.Enddate').show();
    } else {
      $('.Enddate').hide();
      $('#end_date').val('');
      $('#end_time').val('');
    }
    // updateSummary();
  });

  // Initialize selectpickers
  $('.selectpicker').selectpicker();

  // Collection search
  $('#collectionSearch').off('keyup').on('keyup', function() {
    let searchTerm = $(this).val().toLowerCase();
    let anyVisible = false;

    $('#allCollectionsTable tbody tr').each(function() {
        // Skip the "no-results-message" row if it already exists
        if ($(this).hasClass('no-results-message')) return;

        let collectionName = $(this).find('td:first').text().toLowerCase();
        if (collectionName.includes(searchTerm)) {
            $(this).show();
            anyVisible = true;
        } else {
            $(this).hide();
        }
    });

    // Show or hide the "No results found" message
    if (!anyVisible) {
        if ($('#allCollectionsTable .no-results-message').length === 0) {
            $('#allCollectionsTable tbody').append(`
                <tr class="no-results-message">
                    <td colspan="100%" class="text-center" style="padding: 20px;">
                        <span class="text-center p-3" style="width: 96%; display: inline-block; position: relative; overflow-x: hidden !important; font-size: large;">
                            No matching records found
                        </span>
                    </td>
                </tr>
            `);
        }
    } else {
        $('#allCollectionsTable .no-results-message').remove();
    }
});

  // Add collections to discount form
  $('#addCollections').on('click', function() {
    let selectedCollections = [];
    $('.collection-checkbox:checked').each(function() {
      selectedCollections.push({
        uuid: $(this).data('collection-id'),
        name: $(this).closest('tr').find('td:first').text()
      });
    });
    
    updateSelectedCollectionsTable(selectedCollections);
    // updateSummary();
  });

  // Customer search
  $('#customerSearch').on('keyup', function() {
    let searchTerm = $(this).val().toLowerCase();
    $('#allCustomersTable tbody tr').each(function() {
      let customerName = $(this).find('td:first').text().toLowerCase();
      let customerEmail = $(this).find('td:nth-child(2)').text().toLowerCase();
      $(this).toggle(customerName.includes(searchTerm) || customerEmail.includes(searchTerm));
    });
  });

  // Add customers to table
  $('#addCustomers').on('click', function() {
    let selectedCustomers = [];
    $('.customer-checkbox:checked').each(function() {
      selectedCustomers.push({
        id: $(this).data('customer-id'),
        name: $(this).closest('tr').find('td:first').text(),
        email: $(this).closest('tr').find('td:nth-child(2)').text()
      });
    });
    
    updateSelectedCustomersTable(selectedCustomers);
    // updateSummary();
  });

  // Add segments to table
  $('#addSegments').on('click', function() {
    let selectedSegments = [];
    $('.segment-checkbox:checked').each(function() {
      selectedSegments.push({
        id: $(this).data('segment-id'),
        name: $(this).closest('tr').find('td:first').text(),
        description: $(this).closest('tr').find('td:nth-child(2)').text()
      });
    });
    
    updateSelectedSegmentsTable(selectedSegments);
    // updateSummary();
  });

  // Update discount button click handler
  $('#updateDiscount').click(function () {
    updateDiscount();
  });

  // Load products when Browse or All Products is clicked
  $('#ProductItem, .custom-button-browse').on('click', function () {
    table_product();
  });

  // Product search in modal
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

  // Add products to discount table
  $('#AddProductModal').on('click', function () {
    let productArray = [];

    // Get all currently selected products from the modal
    let productList = $('.table-first-item .custom-checkbox input[name="product_check"]:checked').closest('.table-first-item, .table-row.child');

    productList.each(function () {
      let isChild = $(this).hasClass('child');
      let productUuid = isChild 
        ? $(this).find('.product-name').data('product-uuid')
        : $(this).find('.parent .product-name').data('product-uuid');
      let variantUuid = isChild ? $(this).find('.product-name').data('variant-uuid') : '';
      let productName = isChild 
        ? $(this).find('.product-name').text().trim()
        : $(this).find('.parent .product-name').text().trim();
      let productImage = isChild 
        ? $(this).find('.product-name').data('product-image')
        : $(this).find('.parent .product-name').data('product-image');
      let imgSrc = isChild 
        ? $(this).find('.product-name img').attr('src')
        : $(this).find('.parent .product-name img').attr('src');
      let productPrice = isChild 
        ? $(this).find('.product-name').data('product-price')
        : $(this).find('.parent .product-name').data('product-price');
      let qty = isChild 
        ? $(this).find('.total-name').text().trim()
        : $(this).find('.parent .total-name').text().trim();

      // Only add if not already in the array
      let exists = productArray.some(p => p.product_uuid === productUuid && p.variant_uuid === variantUuid);
      if (!exists) {
        productArray.push({
          product_uuid: productUuid,
          variant_uuid: variantUuid || '',
          name: productName,
          price: productPrice,
          variant: variantUuid ? productName : '',
          qty: qty,
          product_image: productImage,
          img: imgSrc
        });
      }
    });

    // Update the product table
    let tableBody = $('#productTable tbody');
    tableBody.empty();

    productArray.forEach(product => {
      tableBody.append(`
        <tr class="product-row" data-product-id="${product.product_uuid}">
          <td>
            <div class="d-flex align-items-center">
              ${product.img ? `<img src="${product.img}" alt="${product.name}" class="size-50px img-fit" style="height: 50px; width:50px;">` : placeholderSVG}
              <span class="ms-2">${product.name}</span>
            </div>
          </td>
          <td>${product.variant || 'Base Product'}</td>
          <td>
            <input type="checkbox" class="form-check-input product-checkbox" 
                   data-product-id="${product.product_uuid}" 
                   data-variant-id="${product.variant_uuid}" 
                   checked>
          </td>
        </tr>
      `);
    });

    $('#productSelection').show();
    $('#exampleModalTwo').modal('hide');
    // updateSummary();
  });
});

function initializeForm() {
  let today = new Date();
  let nextWeek = new Date();
  nextWeek.setDate(today.getDate() + 7);
  $('#start_date').val(formatDate(today));
  $('#end_date').val(formatDate(nextWeek));
}

function loadDiscountData(uuid) {
  $.ajax({
    url: ApiPlugin + '/ecommerce/edit_discount/' + uuid,
    type: "GET",
    dataType: "json",
    beforeSend: function(xhr) {
      xhr.setRequestHeader("Authorization", "Bearer " + strkey);
      xhr.setRequestHeader("menu-uuid", menu_id);
      $('#img_load').show();
    },
    success: function(response) {
      $('#img_load').hide();
      if (response.status_code == 200) {
        edit_data = response.data;
        populateForm(edit_data);
        $("#discount_status").html(edit_data.status.charAt(0).toUpperCase() + edit_data.status.slice(1));
      } else {
        showToast(response.message || 'Failed to load discount data', 'Error', 'error');
      }
    },
    error: function(error) {
      $('#img_load').hide();
      error = error.responseJSON;
      showToast(error.message || 'Error loading discount data', 'Error', 'error');
    }
  });
}

function populateForm(data) {
  // Set basic fields
  $('#name').val(data.name);
  $('#code').val(data.code);
  $('#discount_type').val(data.discount_type) || 'amount_off_products';
  $('#type').val(data.type);
  $('#type').selectpicker('val', data.type);

  if (data.type === 'percentage') {
    $('#value-addon').text('%');
    $('#value').attr('max', '100');
  } else if (data.type === 'amount') {
    $('#value-addon').text('AED');
    $('#value').removeAttr('max');
  }
  $('#value').val(data.value);
  $('#minimum_shopping').val(data.minimum_shopping  );
  $('#maximum_discount_amount').val(data.maximum_discount_amount);
  $('#method').val(data.method || 'code');
  $('#start_date').val(data.start_date);
  $('#end_date').val(data.end_date);
  $('#start_time').val(data.start_time || '');
  $('#end_time').val(data.end_time || '');
  $('#uses_limit').val(data.uses_limit);
  $('#requirement_value').val(data.requirement_value);

  // Set method
  $(`.discount-method-card[data-method="${data.method}"]`).click();
  
  // Set applies to
  $('#applies_to').val(data.applies_to).trigger('change');
  
  // Set requirement type
  if (data.requirement_type) {
    $(`input[name="requirement_type"][value="${data.requirement_type}"]`).prop('checked', true).trigger('change');
  }

  // Set eligibility
  $(`input[name="eligibility"][value="${data.eligibility || 'everyone'}"]`).prop('checked', true).trigger('change');

  // Set usage limits
  if (data.uses_customer_limit) {
    let limits = data.uses_customer_limit.split(',');
    limits.forEach(limit => {
      $(`input[name="uses_customer_limit[]"][value="${limit.trim()}"]`).prop('checked', true);
    });
    if (limits.includes('total')) {
      $('#usageLimitContainer').show();
    }
  }

  // Set combinations
  if (data.combination_type) {
    let combinations = data.combination_type.split(',');
    combinations.forEach(combo => {
      $(`input[name="combination_type[]"][value="${combo.trim()}"]`).prop('checked', true);
    });
  }

  // Set end date visibility
  if (data.end_date) {
    $('#enable_end_date').prop('checked', true);
    $('.Enddate').show();
  }

  // Set POS availability if automatic discount
  if (data.method === 'automatic' && data.apply_on_pos) {
    $('#apply_on_pos').prop('checked', true);
  }

  // Load specific data based on applies_to
  if (data.applies_to === 'collections' && data.discount_items) {
    loadCollections();
  } else if (data.applies_to === 'specific' && data.discount_items) {
    loadProductsForEdit(data.discount_items);
  }

  // Load eligibility specific data
  if (data.eligibility === 'specific_groups' && data.customer_segments) {
    loadCustomerSegments(data.customer_segments);
  } else if (data.eligibility === 'specific_customers' && data.specific_customer) {
    loadCustomers(data.specific_customer.split(','));
  }

  // updateSummary();
}

function loadCollections() {
  $.ajax({
    url: ApiPlugin + '/ecommerce/collection/get_active_collections',
    type: 'GET',
    contentType: 'application/json',
    dataType: 'json',
    beforeSend: function(xhr) {
      xhr.setRequestHeader('Authorization', 'Bearer ' + strkey);
      xhr.setRequestHeader('menu-uuid', menu_id);
      $('#img_load').show();
    },
    success: function(response) {
      $('#img_load').hide();
      if (response.status_code === 200) {
        populateCollectionsTable(response.data);
      } else {
        showToast('Failed to load collections', 'Error', 'error');
      }
    },
    error: function(xhr, status, error) {
      $('#img_load').hide();
      showToast('Error loading collections: ' + error, 'Error', 'error');
    }
  });
}

function populateCollectionsTable(collections) {
  let tableBody = $('#allCollectionsTable tbody');
  tableBody.empty();
  
  collections.forEach(collection => {
    // Check if this collection is in the discount's collectionItems
    let isChecked = edit_data && edit_data.discount_items && 
                   edit_data.discount_items.some(item => item.collection_id === collection.uuid);
    
    tableBody.append(`
      <tr>
        <td>${collection.name}</td>
        <td>
          <input type="checkbox" class="form-check-input collection-checkbox" 
                 data-collection-id="${collection.uuid}" ${isChecked ? 'checked' : ''}>
        </td>
      </tr>
    `);
  });
  
  // Also populate the selected collections table if in edit mode
  if (edit_data && edit_data.applies_to === 'collections' && edit_data.discount_items) {
    let selectedCollections = [];
    
    edit_data.discount_items.forEach(item => {
      if (item.collection_id) {
        // Find the collection name from the collections array
        let collection = collections.find(c => c.uuid === item.collection_id);
        if (collection) {
          selectedCollections.push({
            uuid: item.collection_id,
            name: collection.name
          });
        }
      }
    });
    
    updateSelectedCollectionsTable(selectedCollections);
  }
}

function updateSelectedCollectionsTable(collections) {
  let tableBody = $('#collectionTable tbody');
  tableBody.empty();
  
  collections.forEach(collection => {
    tableBody.append(`
      <tr data-collection-id="${collection.uuid}">
        <td>${collection.name}</td>
        <td>
          <input type="checkbox" class="form-check-input selected-collection" 
                 data-collection-id="${collection.uuid}" checked>
        </td>
      </tr>
    `);
  });
}

function loadProductsForEdit(discountItems) {
  $.ajax({
    url: ApiPlugin + '/ecommerce/product/get_active_products',
    type: 'GET',
    contentType: 'application/json',
    dataType: 'json',
    beforeSend: function(xhr) {
      xhr.setRequestHeader('Authorization', 'Bearer ' + strkey);
      xhr.setRequestHeader('menu-uuid', menu_id);
      $('#img_load').show();
    },
    success: function(response) {
      $('#img_load').hide();
      if (response.status_code === 200) {
        let products = response.data;
        let selectedProducts = [];
        
        // Get all product items from discount data
        discountItems.forEach(item => {
          if (item.product_id) {
            let product = products.find(p => p.uuid === item.product_id);
            if (product) {
              // For base product
              if (!item.variant_id) {
                selectedProducts.push({
                  product_uuid: product.uuid,
                  variant_uuid: '',
                  name: product.name,
                  price: product.current_stock?.price || 0,
                  variant: '',
                  qty: product.current_stock?.qty || 0,
                  product_image: product.thumbnail_img,
                  img: product.thumbnail_img ? AssetsPath + product.thumbnail_img : ''
                });
              } 
              // For variants
              else {
                let variant = product.product_stocks?.find(v => v.uuid === item.variant_id);
                if (variant) {
                  selectedProducts.push({
                    product_uuid: product.uuid,
                    variant_uuid: variant.uuid,
                    name: product.name,
                    price: variant.price,
                    variant: variant.sku || product.name,
                    qty: variant.qty,
                    product_image: variant.image,
                    img: variant.image ? AssetsPath + variant.image : ''
                  });
                }
              }
            }
          }
        });

        // Update the product table
        let tableBody = $('#productTable tbody');
        tableBody.empty();

        selectedProducts.forEach(product => {
          tableBody.append(`
            <tr class="product-row" data-product-id="${product.product_uuid}">
              <td>
                <div class="d-flex align-items-center">
                  ${product.img ? `<img src="${product.img}" alt="${product.name}" class="size-50px img-fit" style="height: 50px; width:50px;">` : placeholderSVG}
                  <span class="ms-2">${product.name}</span>
                </div>
              </td>
              <td>${product.variant || 'Base Product'}</td>
              <td>
                <input type="checkbox" class="form-check-input product-checkbox" 
                       data-product-id="${product.product_uuid}" 
                       data-variant-id="${product.variant_uuid}" 
                       checked>
              </td>
            </tr>
          `);
        });

        $('#productSelection').show();
      } else {
        showToast('Failed to load products', 'Error', 'error');
      }
    },
    error: function(xhr, status, error) {
      $('#img_load').hide();
      showToast('Error loading products: ' + error, 'Error', 'error');
    }
  });
}

function loadCustomerSegments(selectedSegmentIds = []) {
  // For edit mode, check already selected segments
  if (edit_data && edit_data.eligibility === 'specific_groups' && edit_data.customer_segments) {
    selectedSegmentIds = edit_data.customer_segments.split(',');
  }
  
  // Check the checkboxes in the modal
  $('.segment-checkbox').each(function() {
    const segmentId = $(this).data('segment-id');
    $(this).prop('checked', selectedSegmentIds.includes(segmentId));
  });
  
  // If we have selected segments, update the table
  if (selectedSegmentIds.length > 0) {
    let selectedSegments = [];
    
    $('#allSegmentsTable tbody tr').each(function() {
      const segmentId = $(this).find('.segment-checkbox').data('segment-id');
      if (selectedSegmentIds.includes(segmentId)) {
        selectedSegments.push({
          id: segmentId,
          name: $(this).find('td:first').text(),
          description: $(this).find('td:nth-child(2)').text()
        });
      }
    });
    
    updateSelectedSegmentsTable(selectedSegments);
  }
}

function updateSelectedSegmentsTable(segments) {
  let tableBody = $('#customerSegmentsTable tbody');
  tableBody.empty();
  
  segments.forEach(segment => {
    tableBody.append(`
      <tr data-segment-id="${segment.id}">
        <td>${segment.name}</td>
        <td>
          <input type="checkbox" class="form-check-input selected-segment" 
                 data-segment-id="${segment.id}" checked>
        </td>
      </tr>
    `);
  });
}

// Fixed loadCustomers function
function loadCustomers(selectedCustomerIds = []) {
  // For edit mode, get selected customer IDs from edit_data
  if (edit_data && edit_data.eligibility === 'specific_customers' && edit_data.specific_customer) {
    // Split the comma-separated string and convert to array of strings
    selectedCustomerIds = edit_data.specific_customer.split(',').map(id => id.trim());
  }
  
  $.ajax({
    url: ApiForm + '/get_active_users',
    type: 'GET',
    contentType: 'application/json',
    dataType: 'json',
    beforeSend: function(xhr) {
      xhr.setRequestHeader('Authorization', 'Bearer ' + strkey);
      xhr.setRequestHeader('menu-uuid', menu_id);
      $('#img_load').show();
    },
    success: function(response) {
      $('#img_load').hide();
      if (response.status_code === 200) {
        populateCustomersTable(response.data, selectedCustomerIds);
      } else {
        showToast('Failed to load customers', 'Error', 'error');
      }
    },
    error: function(xhr, status, error) {
      $('#img_load').hide();
      showToast('Error loading customers: ' + error, 'Error', 'error');
    }
  });
}

// Fixed populateCustomersTable function
function populateCustomersTable(customers, selectedCustomerIds = []) {
  let tableBody = $('#allCustomersTable tbody');
  tableBody.empty();
  
  customers.forEach(customer => {
    // Convert customer.id to string for comparison since selectedCustomerIds are strings
    let isChecked = selectedCustomerIds.includes(customer.id.toString()) ? 'checked' : '';
    
    tableBody.append(`
      <tr>
        <td>${customer.name}</td>
        <td>${customer.email}</td>
        <td>
          <input type="checkbox" class="form-check-input customer-checkbox" 
                 data-customer-id="${customer.id}" ${isChecked}>
        </td>
      </tr>
    `);
  });

  // Also populate the selected customers table if we have selected IDs
  if (selectedCustomerIds.length > 0) {
    let selectedCustomers = [];
    
    // Filter customers that match selected IDs (convert to string for comparison)
    customers.filter(c => selectedCustomerIds.includes(c.id.toString())).forEach(customer => {
      selectedCustomers.push({
        id: customer.id,
        name: customer.name,
        email: customer.email
      });
    });
    
    updateSelectedCustomersTable(selectedCustomers);
  }
}

function updateSelectedCustomersTable(customers) {
  let tableBody = $('#specificCustomersTable tbody');
  tableBody.empty();
  
  customers.forEach(customer => {
    tableBody.append(`
      <tr data-customer-id="${customer.id}">
        <td>${customer.name}</td>
        <td>${customer.email}</td>
        <td>
          <input type="checkbox" class="form-check-input selected-customer" 
                 data-customer-id="${customer.id}" checked>
        </td>
      </tr>
    `);
  });
}

function table_product() {
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
        let data = response.data;
        let htmlLi = '';
        
        // Get currently selected products from the table (if any)
        let selectedProducts = {};
        $('#productTable tbody tr').each(function() {
          let productId = $(this).data('product-id');
          let variantId = $(this).find('.product-checkbox').data('variant-id') || '';
          selectedProducts[`${productId}_${variantId}`] = true;
        });
        
        // Also include products from edit_data if available
        if (edit_data && edit_data.discount_items) {
          edit_data.discount_items.forEach(item => {
            if (item.product_id) {
              let variantId = item.variant_id || '';
              selectedProducts[`${item.product_id}_${variantId}`] = true;
            }
          });
        }

        data.forEach(item => {
          // Check if the base product is selected
          let isParentChecked = selectedProducts[`${item.uuid}_`] ? 'checked' : '';
          
          let parentImage = item.thumbnail_img ? 
                           `<img src="${AssetsPath + item.thumbnail_img}" alt="Image" class="size-50px img-fit">` : 
                           placeholderSVG;
          
          htmlLi += `
            <div class="table-first-item product-${item.uuid}">
              <div class="table-row parent">
                <div class="custom-checkbox-main form-group d-inline-block">
                  <label class="custom-checkbox">
                    <input type="checkbox" name="product_check" ${isParentChecked}>
                    <span class="aiz-square-check"></span>
                  </label>
                </div>
                <div class="product-name" data-product-uuid="${item.uuid}" data-product-price="${item.current_stock?.price || 0}" data-product-image="${item.thumbnail_img}">
                  ${parentImage}
                  <span>${item.name}</span>
                </div>
              </div>`;

          if (item.product_stocks && item.product_stocks.length > 0) {
            htmlLi += item.product_stocks.map(stockItem => {
              // Check if the variant is selected
              let isChildChecked = selectedProducts[`${item.uuid}_${stockItem.uuid}`] ? 'checked' : '';
              
              let childImage = stockItem.image ? 
                              `<img src="${AssetsPath + stockItem.image}" alt="Image" class="size-50px img-fit">` : 
                              placeholderSVG;
              
              return `
                <div class="table-row child left-side-item productStock-${stockItem.uuid}">
                  <div class="custom-checkbox-main form-group d-inline-block">
                    <label class="custom-checkbox">
                      <input type="checkbox" name="product_check" ${isChildChecked}>
                      <span class="aiz-square-check"></span>
                    </label>
                  </div>
                  <div class="product-name" data-product-uuid="${item.uuid}" data-variant-uuid="${stockItem.uuid}" data-product-price="${stockItem.price}" data-product-image="${stockItem.image}">
                    ${childImage}
                    <span>${stockItem.sku || item.name}</span>
                  </div>
                  <div class="total-name">
                    <span>${stockItem.qty}</span>
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

function prepareDiscountData() {
  let discountData = {
    name: $('#name').val(),
    code: $('#code').val(),
    type: $('#type').val(),
    discount_type: $('#discount_type').val() || 'amount_off_products',
    value: $('#value').val(),
    start_date: $('#start_date').val(),
    end_date: $('#end_date').val(),
    start_time: $('#start_time').val() || null,
    end_time: $('#end_time').val() || null,
    uses_limit: $('#uses_limit').val(),
    minimum_shopping: $('#minimum_shopping').val(),
    maximum_discount_amount: $('#maximum_discount_amount').val(),
    uses_customer_limit: $('input[name="uses_customer_limit[]"]:checked').map(function() {
      return $(this).val();
    }).get().join(','),
    requirement_value: $('#requirement_value').val(),
    requirement_type: $('input[name="requirement_type"]:checked').val(),
    applies_to: $('#applies_to').val(),
    method: $('#method').val(),
    eligibility: $('input[name="eligibility"]:checked').val(),
    combination_type: $('input[name="combination_type[]"]:checked').map(function() {
      return $(this).val();
    }).get().join(','),
    items: []
  };
    method1= $('#method').val();
    code1= $('#code').val();
    name1= $('#name').val();
    value1 =  $('#value').val();
    type1 =  $('#type').val();
    minimum_shopping1 =  $('#minimum_shopping').val();
    maximum_discount_amount1 =  $('#maximum_discount_amount').val();
      if (method1 === 'code') {
          if(code1==''){
            showToast('Please add code', 'Error', 'error');
            return false;
          }
          if(minimum_shopping1==''){
            showToast('Please add minimum shopping', 'Error', 'error');
            return false;
          }
        } else {
          if(name1==''){
            showToast('Please add name', 'Error', 'error');
            return false;
          }
        }
        if(value1 == ''){
          showToast('Please add value', 'Error', 'error');
          return false;
        }

  // Handle collections or products
  if (discountData.applies_to === 'collections') {
    $('.selected-collection:checked').each(function() {
      discountData.items.push({
        collection_id: $(this).data('collection-id'),
        product_id: null,
        variant_id: null
      });
    });

    if (discountData.items.length === 0) {
      showToast('Please select at least one collection', 'Error', 'error');
      return null;
    }
  } else if (discountData.applies_to === 'specific') {
    $('.product-checkbox:checked').each(function() {
      discountData.items.push({
        product_id: $(this).data('product-id'),
        variant_id: $(this).data('variant-id') || null,
        collection_id: null
      });
    });

     const method = $('#method').val();
  if (method === 'automatic') {
    if (discountData.items.length === 0) {
      showToast('Please select at least one product', 'Error', 'error');
      return null;
    }
  }
  }

  // Handle customer eligibility
  if (discountData.eligibility === 'specific_groups') {
    let segmentIds = [];
    $('#customerSegmentsTable tbody tr').each(function() {
      if ($(this).find('.selected-segment').is(':checked')) {
        segmentIds.push($(this).data('segment-id'));
      }
    });
    
    discountData.customer_segments = segmentIds.join(',');
  } else if (discountData.eligibility === 'specific_customers') {
    let customerIds = [];
    $('#specificCustomersTable tbody tr').each(function() {
      if ($(this).find('.selected-customer').is(':checked')) {
        customerIds.push($(this).data('customer-id'));
      }
    });
    
    discountData.specific_customer = customerIds.join(',');
  }

  if(value1 < 0){
    showToast('Please add value greater than 0', 'Error', 'error');
    return null;
  }

  if(type1 === 'percentage'){
  if (value1 > 100) {
    showToast('Please add value less than 100', 'Error', 'error');
    return null;
  }
  }
  return discountData;
}

function updateDiscount() {
  let discountData = prepareDiscountData();
  if (!discountData) return;

  discountData.uuid = uuid;

  $.ajax({
    url: ApiPlugin + '/ecommerce/update_discount',
    type: "POST",
    contentType: "application/json",
    dataType: "json",
    data: JSON.stringify(discountData),
    headers: {
      "Authorization": "Bearer " + strkey,
      'menu-uuid': menu_id,
      'uuid': uuid
    },
    beforeSend: function() {
      $('#img_load').show();
    },
    success: function(response) {
      $('#img_load').hide();
      if (response.status_code === 200) {
        showToast('Discount updated successfully', 'Success', 'success');
        setTimeout(() => {
          window.location.href = '?P=discounts&M=' + menu_id;
        }, 1500);
      } else {
        showToast(response.message || 'Error updating discount', 'Error', 'error');
      }
    },
    error: function(xhr, status, error) {
      $('#img_load').hide();
      let errorMsg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Error updating discount';
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

// Listen to form changes
// $('input, select').on('change keyup', function() {
//   updateSummary();
// });
// function updateSummary() {
//     const page = $('#discount_type').val();
//     if (page === 'amount_off_products'){
//       $('#summary-page').text('Amount off products');
//     }
//     const method = $('#method').val();
//     if (method === 'code') {
//       $('#summary-method').html('<strong>' + ($('#code').val() || 'Not set') + '</strong><br>' + 'Code');
//     } else {
//       $('#summary-method').text('Automatic discount: ' + ($('#name').val() || 'Not set'));
//     }
  
//   // Update applies to
//   const type = $('#type').val();
//   const value = $('#value').val();
//   const appliesTo = $('input[name="applies_to"]:checked').val();
//    if (appliesTo === 'collections') {
//     const collectionCount = $('#collectionTable tbody tr').length;
//     if (collectionCount > 0) {
//       const typeText = type === 'percentage' ? value + '% off' : value + ' AED off';
//       $('#summary-applies').text(typeText + ' ' + collectionCount + ' collections');
//     } else {
//       $('#summary-applies').text('No collections selected');
//     }
//   } else if (appliesTo === 'specific') {
//     const productCount = $('#productTable tbody tr').length;
//     if (productCount > 0) {
//       const typeText = type === 'percentage' ? value + '% off' : value + ' AED off';
//       $('#summary-applies').text(typeText + ' ' + productCount + ' products');
//     } else {
//       $('#summary-applies').text('No products selected');
//     }
//   } else {
//     const typeText = type === 'percentage' ? value + '% off' : value + ' AED off';
//     $('#summary-applies').text(typeText + ' all products');
//   }
  
//   // Update requirements
//   const requirement = $('input[name="requirement_type"]:checked').val();
//   if (requirement === 'amount') {
//     $('#summary-requirements').text('Minimum purchase: ' + $('#requirement_value').val() + ' AED');
//   } else if (requirement === 'quantity') {
//     $('#summary-requirements').text('Minimum items: ' + $('#requirement_value').val());
//   } else {
//     $('#summary-requirements').text('None');
//   }
  
//   // Update customers
//   const eligibility = $('input[name="eligibility"]:checked').val();
//   if (eligibility === 'specific_groups') {
//     // Get selected segments from the table
//     let selectedSegments = [];
//     $('#customerSegmentsTable tbody tr').each(function() {
//       if ($(this).find('.selected-segment').is(':checked')) {
//         selectedSegments.push($(this).find('td:first').text().trim());
//       }
//     });

//     if (selectedSegments.length === 1) {
//       $('#summary-customers').text('For ' + selectedSegments[0]);
//     } else if (selectedSegments.length > 1) {
//       $('#summary-customers').text(`For ${selectedSegments.length} customers segments`);
//     } else {
//       $('#summary-customers').text('Specific groups (none selected)');
//     }
//   }
//  else if (eligibility === 'specific_customers') {
//     // Get selected customers from the table
//     let selectedCustomers = [];
//     $('#specificCustomersTable tbody tr').each(function() {
//       if ($(this).find('.selected-customer').is(':checked')) {
//         selectedCustomers.push($(this).find('td:first').text());
//       }
//     });
    
//     if (selectedCustomers.length > 0) {
//       if (selectedCustomers.length === 1) {
//         $('#summary-customers').text('For ' + selectedCustomers[0]);
//       } else {
//         $('#summary-customers').text(`For ${selectedCustomers.length} customers`);
//       }
//     }
//   } else {
//     $('#summary-customers').text('All customers');
//   }
  
//   // Update usage limits
//   const totalLimit = $('#total_limit').is(':checked');
//   const customerLimit = $('#customer_limit').is(':checked');
//   if (totalLimit && customerLimit) {
//     $('#summary-usage').text('Limit of ' + $('#uses_limit').val() + ' uses');
//   } else if (totalLimit) {
//     $('#summary-usage').text('Limit of ' + $('#uses_limit').val() + ' uses');
//   } else if (customerLimit) {
//     $('#summary-usage').text('One per customer');
//   } else {
//     $('#summary-usage').text('None');
//   }

//   const combinationTypes = $('input[name="combination_type[]"]:checked').map(function() {
//   return $(this).val();
// }).get();

//     let combinationText = '';
//     if (combinationTypes.length === 0) {
//       combinationText = 'Can’t combine with other discounts';
//     } else {
//       const combinationNames = [];
//       if (combinationTypes.includes('product_discount')) {
//         combinationNames.push('product');
//       }
//       if (combinationTypes.includes('order_discount')) {
//         combinationNames.push('order');
//       }
//       if (combinationTypes.includes('shipping_discount')) {
//         combinationNames.push('shipping');
//       }
      
//       if (combinationNames.length > 0) {
//         combinationText = 'Combines with ' + combinationNames.join(', ') + ' discounts';
//       } else {
//         combinationText = 'Can’t combine with other discounts';
//       }
//     }

// $('#summary-combinations').text(combinationText);
  
//   // Update dates
//   const startDate = $('#start_date').val();
//   const endDate = $('#end_date').val();
//   if (startDate && endDate) {
//     $('#summary-dates').text(startDate + ' to ' + endDate);
//   } else if (startDate) {
//     $('#summary-dates').text('Starts ' + startDate);
//   } else if (endDate) {
//     $('#summary-dates').text('Ends ' + endDate);
//   } else {
//     $('#summary-dates').text('No date restrictions');
//   }
// }