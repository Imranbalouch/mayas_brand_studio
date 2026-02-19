document.title = "Dashboard | Add Discount (Amount Off Orders)";
let PageNo = 1;
let PageSize = 10;
let totalRecord = 0;
let PageLength = 0;
let Search = '';
let keyupTimer;

$(document).ready(function () {
  // Initialize form elements
  initializeForm();
  $('#enable_end_date').change(function() {
    if ($(this).is(':checked')) {
      $('.Enddate').show();
    } else {
      $('.Enddate').hide();
      $('#end_date').val(''); // Clear end date
      $('#end_time').val(''); // Clear end time
    }
    updateSummary(); // Update summary when checkbox changes
  });
   $('#generateCode').click(function() {
    const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    let result = '';
    for (let i = 0; i < 8; i++) {
      result += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    $('#code').val(result);
    updateSummary();
  });

  // Update summary card when form changes
  function updateSummary() {
    const page = $('#discount_type').val();
    if (page === 'amount_off_orders'){
      $('#summary-page').text('Amount off orders');
    }
    const method = $('#method').val();
    if (method === 'code') {
      $('#summary-method').html('<strong>' + ($('#code').val() || 'Not set') + '</strong><br>' + 'Code');
    } else {
      $('#summary-method').text('Automatic discount: ' + ($('#name').val() || 'Not set'));
    }
  
  // Update applies to
  const type = $('#type').val();
  const value = $('#value').val();
  const appliesTo = $('input[name="applies_to"]:checked').val();
   if (appliesTo === 'collections') {
    const collectionCount = $('#collectionTable tbody tr').length;
    if (collectionCount > 0) {
      const typeText = type === 'percentage' ? value + '% off' : value + ' AED off';
      $('#summary-applies').text(typeText + ' ' + collectionCount + ' collections');
    } else {
      $('#summary-applies').text('No collections selected');
    }
  } else if (appliesTo === 'specific') {
    const productCount = $('#productTable tbody tr').length;
    if (productCount > 0) {
      const typeText = type === 'percentage' ? value + '% off' : value + ' AED off';
      $('#summary-applies').text(typeText + ' ' + productCount + ' products');
    } else {
      $('#summary-applies').text('No products selected');
    }
  } else {
    const typeText = type === 'percentage' ? value + '% off' : value + ' AED off';
    $('#summary-applies').text(typeText + ' entire order');
  }
  
  // Update requirements
  const requirement = $('input[name="requirement_type"]:checked').val();
  if (requirement === 'amount') {
    $('#summary-requirements').text('Minimum purchase: ' + $('#requirement_value').val() + ' AED');
  } else if (requirement === 'quantity') {
    $('#summary-requirements').text('Minimum items: ' + $('#requirement_value').val());
  } else {
    $('#summary-requirements').text('None');
  }
  
  // Update customers
  const eligibility = $('input[name="eligibility"]:checked').val();
  if (eligibility === 'specific_groups') {
    // Get selected segments from the table
    let selectedSegments = [];
    $('#customerSegmentsTable tbody tr').each(function() {
      if ($(this).find('.selected-segment').is(':checked')) {
        selectedSegments.push($(this).find('td:first').text().trim());
      }
    });

    if (selectedSegments.length === 1) {
      $('#summary-customers').text('For ' + selectedSegments[0]);
    } else if (selectedSegments.length > 1) {
      $('#summary-customers').text(`For ${selectedSegments.length} customers segments`);
    } else {
      $('#summary-customers').text('Specific groups (none selected)');
    }
  }
 else if (eligibility === 'specific_customers') {
    // Get selected customers from the table
    let selectedCustomers = [];
    $('#specificCustomersTable tbody tr').each(function() {
      if ($(this).find('.selected-customer').is(':checked')) {
        selectedCustomers.push($(this).find('td:first').text());
      }
    });
    
    if (selectedCustomers.length > 0) {
      if (selectedCustomers.length === 1) {
        $('#summary-customers').text('For ' + selectedCustomers[0]);
      } else {
        $('#summary-customers').text(`For ${selectedCustomers.length} customers`);
      }
    }
  } else {
    $('#summary-customers').text('All customers');
  }
  
  // Update usage limits
  const totalLimit = $('#total_limit').is(':checked');
  const customerLimit = $('#customer_limit').is(':checked');
  if (totalLimit && customerLimit) {
    $('#summary-usage').text('Limit of ' + $('#uses_limit').val() + ' uses');
  } else if (totalLimit) {
    $('#summary-usage').text('Limit of ' + $('#uses_limit').val() + ' uses');
  } else if (customerLimit) {
    $('#summary-usage').text('One per customer');
  } else {
    $('#summary-usage').text('None');
  }

  const combinationTypes = $('input[name="combination_type[]"]:checked').map(function() {
  return $(this).val();
}).get();

    let combinationText = '';
    if (combinationTypes.length === 0) {
      combinationText = 'Can’t combine with other discounts';
    } else {
      const combinationNames = [];
      if (combinationTypes.includes('product_discount')) {
        combinationNames.push('product');
      }
      if (combinationTypes.includes('order_discount')) {
        combinationNames.push('order');
      }
      if (combinationTypes.includes('shipping_discount')) {
        combinationNames.push('shipping');
      }
      
      if (combinationNames.length > 0) {
        combinationText = 'Combines with ' + combinationNames.join(', ') + ' discounts';
      } else {
        combinationText = 'Can’t combine with other discounts';
      }
    }

$('#summary-combinations').text(combinationText);
  
  // Update dates
  const startDate = $('#start_date').val();
  const endDate = $('#end_date').val();
  if (startDate && endDate) {
    $('#summary-dates').text(startDate + ' to ' + endDate);
  } else if (startDate) {
    $('#summary-dates').text('Starts ' + startDate);
  } else if (endDate) {
    $('#summary-dates').text('Ends ' + endDate);
  } else {
    $('#summary-dates').text('No date restrictions');
  }
}

  // Listen to form changes
  $('input, select').on('change keyup', function() {
    updateSummary();
  });

  // Initial update
  updateSummary();
 $('.discount-method-card').click(function() {
  $('.discount-method-card .card').removeClass('border-primary');
  $(this).find('.card').addClass('border-primary');
  const method = $(this).data('method');
  $('#method').val(method);

  // Toggle discount code and name fields
  if (method === 'code') {
    $('#discountNameField').removeClass('d-none');
    $('#automaticNameField').addClass('d-none');
    $('#code').prop('required', true);
    $('#name').prop('required', false);
  } else {
    $('#discountNameField').addClass('d-none');
    $('#automaticNameField').removeClass('d-none');
    $('#code').prop('required', false);
    $('#name').prop('required', true);
  }

  toggleEligibilityAndUsageCards(method);
  toggleAvailabilityCard(method);

  updateSummary();
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
    updateSummary();

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
    updateSummary();

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
  $('input[name="applies_to"]').change(function() {
  if ($(this).val() === 'specific') {
    $('#productSelection').show();
    $('#collectionSelection').hide();
  } else if ($(this).val() === 'collections') {
    $('#collectionSelection').show();
    $('#productSelection').hide();
  } else {
    $('#productSelection').hide();
    $('#collectionSelection').hide();
  }
});

  // Save discount button click handler
  $('#saveDiscount').click(function () {
    saveDiscount();
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
    applies_to: $('input[name="applies_to"]:checked').val(),
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

      if (method1 === 'code') {
          if(code1==''){
            showToast('Please add code', 'Error', 'error');
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

    if (discountData.items.length === 0) {
      showToast('Please select at least one product', 'Error', 'error');
      return null;
    }
  }

  if (value1 > 100) {
    showToast('Please add value less than 100', 'Error', 'error');
    return null;
  }

  return discountData;
}


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
