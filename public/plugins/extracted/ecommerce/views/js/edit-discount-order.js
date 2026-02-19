document.title = "Dashboard | Edit Discount (Amount Off Orders)";
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

 function loadCustomers(selectedCustomerIds = []) {
  // For edit mode, get selected customer IDs from edit_data
  if (edit_data && edit_data.eligibility === 'specific_customers' && edit_data.specific_customer) {
    // Ensure selectedCustomerIds is an array of strings
    selectedCustomerIds = Array.isArray(edit_data.specific_customer)
      ? edit_data.specific_customer.map(id => id.trim())
      : edit_data.specific_customer.split(',').map(id => id.trim());
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

  customers.forEach((customer) => {
    // Use consistent customer ID property (uuid or id)
    let customerId = customer.uuid || customer.id || customer.customer_id;
    let customerName = customer.name || customer.customer_name || 'N/A';
    let customerEmail = customer.email || customer.customer_email || 'N/A';

    // Convert customerId to string for comparison
    let isChecked = selectedCustomerIds.includes(customerId.toString()) ? 'checked' : '';

    tableBody.append(`
      <tr>
        <td>${customerName}</td>
        <td>${customerEmail}</td>
        <td>
          <input type="checkbox" class="form-check-input customer-checkbox" 
                 data-customer-id="${customerId}" ${isChecked}>
        </td>
      </tr>
    `);
  });

  // Populate the specific customers table for selected customers
  let selectedTableBody = $('#specificCustomersTable tbody');
  selectedTableBody.empty();

  customers.forEach(customer => {
    let customerId = customer.uuid || customer.id || customer.customer_id;
    if (selectedCustomerIds.includes(customerId.toString())) {
      let customerName = customer.name || customer.customer_name || 'N/A';
      let customerEmail = customer.email || customer.customer_email || 'N/A';
      selectedTableBody.append(`
        <tr data-customer-id="${customerId}">
          <td>${customerName}</td>
          <td>${customerEmail}</td>
          <td>
            <input type="checkbox" class="form-check-input selected-customer" 
                   data-customer-id="${customerId}" checked>
          </td>
        </tr>
      `);
    }
  });

  updateSummary();
}

  // Add selected customers
  $('#addCustomers').off('click').on('click', function() {
  let selectedCustomers = [];
  $('.customer-checkbox:checked').each(function() {
    let customerId = $(this).data('customer-id');
    let customerName = $(this).closest('tr').find('td:first').text();
    let customerEmail = $(this).closest('tr').find('td:nth-child(2)').text();

    // Only add if customerId is valid
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

  updateSummary();
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

  // Save discount button click handler
  $('#updateDiscount').click(function () {
    updateDiscount();
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
    url: ApiForm + '/ecommerce/edit_discount/' + uuid,
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
  $('#discount_type').val(data.discount_type) || 'amount_off_orders';
  $('#type').val(data.type).change();
  $('#value').val(data.value);
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
  $(`input[name="applies_to"][value="${data.applies_to}"]`).prop('checked', true).trigger('change');
  
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

  updateSummary();
}
function prepareDiscountData() {
  let discountData = {
    name: $('#name').val(),
    code: $('#code').val(),
    type: $('#type').val(),
    discount_type: $('#discount_type').val() || 'amount_off_orders',
    value: $('#value').val(),
    start_date: $('#start_date').val(),
    end_date: $('#end_date').val(),
    start_time: $('#start_time').val() || null,
    end_time: $('#end_time').val() || null,
    uses_limit: $('#uses_limit').val(),
    uses_customer_limit: $('input[name="uses_customer_limit[]"]:checked').map(function() {
      return $(this).val();
    }).get().join(','),
    requirement_value: $('#requirement_value').val(),
    requirement_type: $('input[name="requirement_type"]:checked').val(),
    applies_to: $('input[name="applies_to"]:checked').val(),
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

    if (discountData.items.length === 0) {
      showToast('Please select at least one product', 'Error', 'error');
      return null;
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

  if (value1 > 100) {
    showToast('Please add value less than 100', 'Error', 'error');
    return null;
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
$('input, select').on('change keyup', function() {
  updateSummary();
});
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