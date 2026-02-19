document.title = "Dashboard | Add Discount";
let PageNo = 1;
let PageSize = 10;
let totalRecord = 0;
let PageLength = 0;
let Search = '';
let keyupTimer;

$(document).ready(function () {
  // Initialize form elements
  initializeForm();

  const appliesToValue = $('#applies_to').val();
  if (appliesToValue === 'collections') {
    $('#collectionSelection').show();
    loadCollections();
  } else if (appliesToValue === 'specific') {
    $('#productSelection').show();
  }
  
  $('#enable_end_date').change(function() {
    if ($(this).is(':checked')) {
      $('.Enddate').show();
    } else {
      $('.Enddate').hide();
      $('#end_date').val(''); // Clear end date
      $('#end_time').val(''); // Clear end time
    }
    // updateSummary(); // Update summary when checkbox changes
  });
   $('#generateCode').click(function() {
    const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    let result = '';
    for (let i = 0; i < 8; i++) {
      result += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    $('#code').val(result);
    // updateSummary();
  });

  // Update summary card when form changes
//   function updateSummary() {
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
//   const appliesTo = $('#applies_to').val();
//     if (appliesTo === 'collections') {
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

  // Listen to form changes
  // $('input, select').on('change keyup', function() {
  //   updateSummary();
  // });

  // Initial update
  // updateSummary();
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
    $('#code').attr('required', 'required');
    $('#name').attr('required', '');
    $('#name').val('');
  } else {
    $('#discountNameField').addClass('d-none');
    $('#minmaxCard').addClass('d-none');
    $('#appliesToContainer').removeClass('d-none');
    $('#automaticNameField').removeClass('d-none');
    $('#code').attr('required', false); 
     $('#name').attr('required', 'required');
     $('#code').val('');
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
  
  // Applies to radio buttons
  $('input[name="applies_to"]').change(function() {
    $('#collectionSelection, #productSelection').hide();
    if ($(this).val() === 'collections') {
      $('#collectionSelection').show();
      loadCollections();
    } else if ($(this).val() === 'specific') {
      $('#productSelection').show();
    }
  });
  
  // Minimum requirements
  $('input[name="requirement_type"]').change(function() {
    if ($(this).val() === 'amount' || $(this).val() === 'quantity') {
      $('#requirementValueContainer').show();
    } else {
      $('#requirementValueContainer').hide();
    }
  });
  
  // Customer eligibility
  $('input[name="eligibility"]').change(function() {
  const value = $(this).val();
  
  // Hide all containers first
  $('#customerSegmentsSelection').hide();
  $('#specificCustomersSelection').hide();
  $('#eligibilityValueContainer').hide();
  
  if (value === 'specific_groups') {
    $('#customerSegmentsSelection').show();
    loadCustomerSegments();
  } else if (value === 'specific_customers') {
    $('#specificCustomersSelection').show();
    loadCustomers();
  }
});

function loadCustomerSegments() {
  // For edit mode, check already selected segments
  if (edit_data && edit_data.eligibility === 'specific_groups' && edit_data.customer_segments) {
    edit_data.customer_segments.forEach(segmentId => {
      $(`.segment-checkbox[data-segment-id="${segmentId}"]`).prop('checked', true);
    });
  }
}

// Add selected segments to the table
$('#addSegments').on('click', function() {
  let selectedSegments = [];
  
  $('.segment-checkbox:checked').each(function() {
    const segmentId = $(this).data('segment-id');
    const segmentName = $(this).closest('tr').find('td:first').text();
    
    selectedSegments.push({
      id: segmentId,
      name: segmentName
    });
  });
  
  let tableBody = $('#customerSegmentsTable tbody');
  tableBody.empty();
  
  selectedSegments.forEach(segment => {
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
    // updateSummary();

});


function loadCustomers() {
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
        populateCustomersTable(response.data);
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

function populateCustomersTable(customers) {
  let tableBody = $('#allCustomersTable tbody');
  tableBody.empty();
  
  // Debug log to check the customers data structure
  console.log('Customers data:', customers);
  
  customers.forEach((customer, index) => {
    // Debug log each customer
    console.log(`Customer ${index}:`, customer);
    
    // Make sure we're using the correct property for the customer ID
    let customerId = customer.uuid || customer.id || customer.customer_id;
    let customerName = customer.name || customer.customer_name || 'N/A';
    let customerEmail = customer.email || customer.customer_email || 'N/A';
    
    tableBody.append(`
      <tr>
        <td>${customerName}</td>
        <td>${customerEmail}</td>
        <td>
          <input type="checkbox" class="form-check-input customer-checkbox" 
                 data-customer-id="${customerId}">
        </td>
      </tr>
    `);
  });
  
  // For edit mode, check already selected customers
  if (edit_data && edit_data.eligibility === 'specific_customers' && edit_data.specific_customer) {
    edit_data.specific_customer.forEach(customerId => {
      $(`.customer-checkbox[data-customer-id="${customerId}"]`).prop('checked', true);
    });
  }
}

// Add selected customers
// Add selected customers
$('#addCustomers').off('click').on('click', function() {
  let selectedCustomers = [];
  $('.customer-checkbox:checked').each(function() {
    let customerId = $(this).data('customer-id');
    let customerName = $(this).closest('tr').find('td:first').text();
    let customerEmail = $(this).closest('tr').find('td:nth-child(2)').text();
    
    // Debug log to check the data
    console.log('Customer ID:', customerId, 'Name:', customerName, 'Email:', customerEmail);
    
    // Only add if customerId is not undefined
    if (customerId && customerId !== 'undefined') {
      selectedCustomers.push({
        uuid: customerId,
        name: customerName,
        email: customerEmail
      });
    }
  });

  let tableBody = $('#specificCustomersTable tbody');
  tableBody.empty();

  selectedCustomers.forEach(customer => {
    // Debug log to verify customer data before adding to table
    console.log('Adding customer to table:', customer);
    
    tableBody.append(`
      <tr data-customer-id="${customer.uuid}">
        <td>${customer.name}</td>
        <td>${customer.email}</td>
        <td>
          <input type="checkbox" class="form-check-input selected-customer" 
                 data-customer-id="${customer.uuid}" checked>
        </td>
      </tr>
    `);
  });

  // Update the eligibility value field with customer names (for display)
  if (selectedCustomers.length > 0) {
    $('#eligibility_value').val(selectedCustomers.map(c => c.name).join(', '));
  }
    // updateSummary();

  // Close the modal after adding customers
  $('#customerModal').modal('hide');
});



// Customer search functionality
$('#customerSearch').on('keyup', function() {
  let searchTerm = $(this).val().toLowerCase();
  $('#allCustomersTable tbody tr').each(function() {
    let customerName = $(this).find('td:first').text().toLowerCase();
    let customerEmail = $(this).find('td:nth-child(2)').text().toLowerCase();
    $(this).toggle(customerName.includes(searchTerm) || customerEmail.includes(searchTerm));
  });
});
  
  // Usage limits
  // Usage limits
$('input[name="uses_customer_limit[]"]').change(function() {
  if ($('input[name="uses_customer_limit[]"][value="total"]').is(':checked')) {
    $('#usageLimitContainer').show();
  } else {
    $('#usageLimitContainer').hide();
  }
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
  });

  // Initialize selectpickers
  $('.selectpicker').selectpicker();

  // Load products when specific products is selected
 $('#applies_to').change(function() {
  const selectedValue = $(this).val();
  
  // Hide both selection sections first
  $('#collectionSelection').hide();
  $('#productSelection').hide();
  
  // Show the appropriate section based on selection
  if (selectedValue === 'collections') {
    $('#collectionSelection').show();
    loadCollections();
  } else if (selectedValue === 'specific') {
    $('#productSelection').show();
  }
  
  // updateSummary();
});

  // Save discount button click handler
  $('#saveDiscount').click(function () {
    saveDiscount();
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

  $(document).on('shown.bs.modal', '#collectionModal', function () {
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
  $('#exampleModalTwo').modal('hide'); // Close the modal
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
});

function initializeForm() {
  let today = new Date(); 

  $('#start_date').val(formattedDate(today));
  $('#start_time').val(formatTime(today));

  $('#end_date').val(formattedDate(today));
  $('#end_time').val(formatTime(today));

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
    tableBody.append(`
      <tr>
        <td>${collection.name}</td>
        <td>
          <input type="checkbox" class="form-check-input collection-checkbox" 
                 data-collection-id="${collection.uuid}">
        </td>
      </tr>
    `);
  });
  
  // For edit mode, check already selected collections
  if (edit_data && edit_data.applies_to === 'collections' && edit_data.collectionItems) {
    edit_data.collectionItems.forEach(item => {
      $(`.collection-checkbox[data-collection-id="${item.collection_id}"]`).prop('checked', true);
    });
  }
}
$('#addCollections').on('click', function() {
  let selectedCollections = [];
  $('.collection-checkbox:checked').each(function() {
    selectedCollections.push({
      uuid: $(this).data('collection-id'),
      name: $(this).closest('tr').find('td:first').text()
    });
  });
  
  let tableBody = $('#collectionTable tbody');
  tableBody.empty();
  
  selectedCollections.forEach(collection => {
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
});

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
    value: $('#value').val(),
    discount_type: $('#discount_type').val(),
    start_date: $('#start_date').val(),
    end_date: $('#end_date').val(),
    start_time: $('#start_time').val() || null,
    end_time: $('#end_time').val() || null,
    apply_on_pos: $('#apply_on_pos').val(),
    method: $('#method').val() || 'code',
    applies_to: $('#applies_to').val(),
    minimum_shopping: $('#minimum_shopping').val(),
    maximum_discount_amount: $('#maximum_discount_amount').val(),
    items: [],
    // Customer Eligibility - FIXED
    eligibility: $('input[name="eligibility"]:checked').val(),
    specific_customer: null,
    customer_segments: null,
    eligibility_value: null,
    // Minimum Requirements
    requirement_type: $('input[name="requirement_type"]:checked').val(),
    requirement_value: $('input[name="requirement_type"]:checked').val() ? $('#requirement_value').val() : null,
    // Maximum Discount Uses
    uses_limit: $('#uses_limit').val(),
    uses_customer_limit: $('input[name="uses_customer_limit[]"]:checked').map(function() {
      return $(this).val();
    }).get().join(','),
    // Combinations
    combination_type: $('input[name="combination_type[]"]:checked').map(function() {
      return $(this).val();
    }).get().join(',')
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

  // Handle customer eligibility properly
  const eligibility = $('input[name="eligibility"]:checked').val();
  
  if (eligibility === 'specific_customers') {
    // Get customer IDs from the selected customers table
    let customerIds = [];
    $('#specificCustomersTable tbody tr').each(function() {
      if ($(this).find('.selected-customer').is(':checked')) {
        customerIds.push($(this).data('customer-id'));
      }
    });
    

    
    if (customerIds.length === 0) {
      showToast('Please select at least one customer', 'Error', 'error');
      return null;
    }
    
    discountData.specific_customer = customerIds.join(',');
    discountData.eligibility_value = $('#eligibility_value').val();
  } else if (eligibility === 'specific_groups') {
    // Get segment IDs from the selected segments table
    let segmentIds = [];
    $('#customerSegmentsTable tbody tr').each(function() {
      if ($(this).find('.selected-segment').is(':checked')) {
        segmentIds.push($(this).data('segment-id'));
      }
    });
    
    if (segmentIds.length === 0) {
      showToast('Please select at least one customer segment', 'Error', 'error');
      return null;
    }
    
    discountData.customer_segments = segmentIds.join(',');
    discountData.eligibility_value = $('#eligibility_value').val();
  }

  // Handle collections
  if (discountData.applies_to === 'collections') {
    $('.selected-collection:checked').each(function() {
      discountData.items.push({
        product_id: null,
        variant_id: null,
        collection_id: $(this).data('collection-id')
      });
    });

    if (discountData.items.length === 0) {
      showToast('Please select at least one collection', 'Error', 'error');
      return null;
    }

    
  } 
  // Handle specific products
  else if (discountData.applies_to === 'specific') {
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




// Update the saveDiscount function to use debug
function saveDiscount() {

  
   let discountData = prepareDiscountData();
  if (!discountData) return;
 
  $.ajax({
    url: ApiPlugin + '/ecommerce/add_discount',
    type: 'POST',
    contentType: 'application/json',
    dataType: 'json',
    data: JSON.stringify(discountData),
    headers: { 'Authorization': 'Bearer ' + strkey, 'menu-uuid': menu_id },
    beforeSend: function() {
      $('#img_load').show();
    },
    success: function(response) {
      $('#img_load').hide();
      console.log('Response:', response); // Debug log
      if (response.status_code === 201) {
        showToast('Discount added successfully', 'Success', 'success');
        setTimeout(() => {
          window.location.href = '?P=discounts&M=' + menu_id;
        }, 1500);
      } else {
        showToast(response.message || 'Error adding discount', 'Error', 'error');
      }
    },
    error: function(xhr, status, error) {
      $('#img_load').hide();
      console.log('Error response:', xhr.responseJSON); // Debug log
      let errorMsg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Error adding discount';
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
