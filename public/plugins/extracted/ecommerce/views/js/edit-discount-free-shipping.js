document.title = "Dashboard | Edit Discount (Free Shipping)";
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

  // Generate discount code
  $('#generateCode').click(function() {
    const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    let result = '';
    for (let i = 0; i < 8; i++) {
      result += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    $('#code').val(result);
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
    $('#collectionSelection').hide();
    if ($(this).val() === 'countries') {
      $('#collectionSelection').show();
      loadCollections();
    }
    updateSummary();
  });

  // Minimum requirements
  $('input[name="requirement_type"]').change(function() {
    if ($(this).val() === 'amount' || $(this).val() === 'quantity') {
      $('#requirementValueContainer').show();
    } else {
      $('#requirementValueContainer').hide();
    }
    updateSummary();
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
    updateSummary();
  });

  // Handle shipping rates checkbox
  $('#exclude_shipping_rates').change(function() {
    if ($(this).is(':checked')) {
      $('#shippingRateContainer').show();
    } else {
      $('#shippingRateContainer').hide();
      $('#shipping_rate').val('');
    }
    updateSummary();
  });

  // Usage limits
  $('input[name="uses_customer_limit[]"]').change(function() {
    if ($('input[name="uses_customer_limit[]"][value="total"]').is(':checked')) {
      $('#usageLimitContainer').show();
    } else {
      $('#usageLimitContainer').hide();
    }
    updateSummary();
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
    updateSummary();
  });

  // Initialize selectpickers
  $('.selectpicker').selectpicker();

  // Collection (Country) search
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

  // Add countries to discount form
  $('#addCollections').on('click', function() {
    let selectedCountries = [];
    $('.collection-checkbox:checked').each(function() {
      selectedCountries.push({
        uuid: $(this).data('countries-id'),
        name: $(this).closest('tr').find('td:first').text()
      });
    });
    
    updateSelectedCollectionsTable(selectedCountries);
    updateSummary();
    $('#collectionModal').modal('hide');
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
      let customerId = $(this).data('customer-id');
      let customerName = $(this).closest('tr').find('td:first').text();
      let customerEmail = $(this).closest('tr').find('td:nth-child(2)').text();
      
      if (customerId && customerId !== 'undefined') {
        selectedCustomers.push({
          id: customerId,
          name: customerName,
          email: customerEmail
        });
      }
    });
    
    updateSelectedCustomersTable(selectedCustomers);
    updateSummary();
    $('#customerModal').modal('hide');
  });

  // Add segments to table
  $('#addSegments').on('click', function() {
    let selectedSegments = [];
    $('.segment-checkbox:checked').each(function() {
      selectedSegments.push({
        id: $(this).data('segment-id'),
        name: $(this).closest('tr').find('td:first').text()
      });
    });
    
    updateSelectedSegmentsTable(selectedSegments);
    updateSummary();
    $('#customerSegmentsModal').modal('hide');
  });

  // Update discount button click handler
  $('#updateDiscount').click(function () {
    updateDiscount();
  });

  // Listen to form changes
  $('input, select').on('change keyup', function() {
    updateSummary();
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
  // Existing code for basic fields
  $('#name').val(data.name);
  $('#code').val(data.code);
  $('#discount_type').val(data.discount_type || 'free_shipping');
  $('#method').val(data.method || 'code');
  $('#start_date').val(data.start_date);
  $('#end_date').val(data.end_date);
  $('#start_time').val(data.start_time || '');
  $('#end_time').val(data.end_time || '');
  $('#uses_limit').val(data.uses_limit);
  $('#requirement_value').val(data.requirement_value);
  $('#shipping_rate').val(data.shipping_rate);
  if (data.exclude_shipping_rates === 1) {
      $('#exclude_shipping_rates').prop('checked', true);
      $('#shippingRateContainer').show();
    } else {
      $('#exclude_shipping_rates').prop('checked', false);
      $('#shippingRateContainer').hide();
    }
  // Set method
  $(`.discount-method-card[data-method="${data.method}"]`).click();

  // Set applies to (Customer Buys)
  const appliesTo = data.applies_to || 'all_country';
  $(`input[name="applies_to"][value="${appliesTo}"]`).prop('checked', true).trigger('change');

  // Show collection selection and load collections if applies_to is 'countries'
  if (appliesTo === 'countries' && data.discount_items && data.discount_items.length > 0) {
    $('#collectionSelection').show();
    loadCollections(); // Load collections to populate the table
  }

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

  // Set POS availability
  if (data.method === 'automatic' && data.apply_on_pos) {
    $('#apply_on_pos').prop('checked', true);
  }

  // New code for "Customer Gets" section
  // Set customer_gets_applies_to
  $(`select[name="customer_gets_applies_to"]`).val(data.customer_gets_applies_to || 'specific').trigger('change');

  // Set customer_get_quantity
  $('#customer_get_quantity').val(data.customer_get_quantity || 1);

  // Set discount type for customer gets
  if (data.customer_gets_discount_type) {
    $(`input[name="customer_gets_discount_type"][value="${data.customer_gets_discount_type}"]`).prop('checked', true).trigger('change');
    if (data.customer_gets_discount_type === 'percentage') {
      $('#customer_get_percentage').val(data.customer_get_percentage);
    } else if (data.customer_gets_discount_type === 'amount') {
      $('#customer_get_amount_off_each').val(data.customer_get_amount_off_each);
    }
  }

  // Load saved products or collections for "Customer Gets"
  if (data.customer_gets_applies_to === 'collections' && data.customer_gets_collections) {
    loadCustomerGetsCollections(data.customer_gets_collections);
  } else if (data.customer_gets_applies_to === 'specific' && data.customer_gets_products) {
    loadCustomerGetsProducts(data.customer_gets_products);
  }

  // Load applies_to data (Customer Buys)
  if (data.applies_to === 'collections' && data.collectionItems) {
    loadCollections(data.collectionItems);
  } else if (data.applies_to === 'specific' && data.productItems) {
    loadProducts(data.productItems);
  }

  // Load eligibility specific data
  if (data.eligibility === 'specific_groups' && data.customer_segments) {
    loadCustomerSegments(data.customer_segments);
  } else if (data.eligibility === 'specific_customers' && data.specific_customer) {
    loadCustomers(data.specific_customer.split(','));
  }

  updateSummary();
}

function loadCollections() {
  $.ajax({
    url: ApiPlugin + '/ecommerce/get_active_countries',
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
        showToast('Failed to load countries', 'Error', 'error');
      }
    },
    error: function(xhr, status, error) {
      $('#img_load').hide();
      showToast('Error loading countries: ' + error, 'Error', 'error');
    }
  });
}

function populateCollectionsTable(countries) {
  let tableBody = $('#allCollectionsTable tbody');
  tableBody.empty();
  
  countries.forEach(country => {
    let isChecked = edit_data && edit_data.discount_items && 
                   edit_data.discount_items.some(item => item.countries_id === country.uuid);
    
    tableBody.append(`
      <tr>
        <td>${country.name}</td>
        <td>
          <input type="checkbox" class="form-check-input collection-checkbox" 
                 data-countries-id="${country.uuid}" ${isChecked ? 'checked' : ''}>
        </td>
      </tr>
    `);
  });
  
  if (edit_data && edit_data.applies_to === 'countries' && edit_data.discount_items) {
    let selectedCountries = [];
    
    edit_data.discount_items.forEach(item => {
      if (item.countries_id) {
        let country = countries.find(c => c.uuid === item.countries_id);
        if (country) {
          selectedCountries.push({
            uuid: item.countries_id,
            name: country.name
          });
        }
      }
    });
    
    updateSelectedCollectionsTable(selectedCountries);
  }
}

function updateSelectedCollectionsTable(countries) {
  let tableBody = $('#collectionTable tbody');
  tableBody.empty();
  
  countries.forEach(country => {
    tableBody.append(`
      <tr data-countries-id="${country.uuid}">
        <td>${country.name}</td>
        <td>
          <input type="checkbox" class="form-check-input selected-collection" 
                 data-countries-id="${country.uuid}" checked>
        </td>
      </tr>
    `);
  });
}

function loadCustomerSegments(selectedSegmentIds = []) {
  if (edit_data && edit_data.eligibility === 'specific_groups' && edit_data.customer_segments) {
    selectedSegmentIds = edit_data.customer_segments.split(',');
  }
  
  $('.segment-checkbox').each(function() {
    const segmentId = $(this).data('segment-id');
    $(this).prop('checked', selectedSegmentIds.includes(segmentId));
  });
  
  if (selectedSegmentIds.length > 0) {
    let selectedSegments = [];
    
    $('#allSegmentsTable tbody tr').each(function() {
      const segmentId = $(this).find('.segment-checkbox').data('segment-id');
      if (selectedSegmentIds.includes(segmentId)) {
        selectedSegments.push({
          id: segmentId,
          name: $(this).find('td:first').text()
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

function loadCustomers(selectedCustomerIds = []) {
  if (edit_data && edit_data.eligibility === 'specific_customers' && edit_data.specific_customer) {
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

function populateCustomersTable(customers, selectedCustomerIds = []) {
  let tableBody = $('#allCustomersTable tbody');
  tableBody.empty();
  
  customers.forEach(customer => {
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

  if (selectedCustomerIds.length > 0) {
    let selectedCustomers = [];
    
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

function prepareDiscountData() {
  let discountData = {
    name: $('#name').val(),
    code: $('#code').val(),
    discount_type: $('#discount_type').val() || 'free_shipping',
    start_date: $('#start_date').val(),
    end_date: $('#end_date').val(),
    start_time: $('#start_time').val() || null,
    end_time: $('#end_time').val() || null,
    apply_on_pos: $('#apply_on_pos').is(':checked') ? 1 : 0,
    method: $('#method').val() || 'code',
    applies_to: $('input[name="applies_to"]:checked').val(),
    items: [],
    eligibility: $('input[name="eligibility"]:checked').val(),
    specific_customer: null,
    customer_segments: null,
    exclude_shipping_rates: $('#exclude_shipping_rates').is(':checked') ? 1 : 0,
    shipping_rate: $('#exclude_shipping_rates').is(':checked') ? $('#shipping_rate').val() : null,
    requirement_type: $('input[name="requirement_type"]:checked').val(),
    requirement_value: $('input[name="requirement_type"]:checked').val() ? $('#requirement_value').val() : null,
    uses_limit: $('#uses_limit').val(),
    uses_customer_limit: $('input[name="uses_customer_limit[]"]:checked').map(function() {
      return $(this).val();
    }).get().join(','),
    combination_type: $('input[name="combination_type[]"]:checked').map(function() {
      return $(this).val();
    }).get().join(',')
  };
   method1= $('#method').val();
    code1= $('#code').val();
    name1= $('#name').val();
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

  if (discountData.applies_to === 'countries') {
    $('.selected-collection:checked').each(function() {
      discountData.items.push({
        countries_id: $(this).data('countries-id'),
        product_id: null,
        variant_id: null,
        collection_id: null
      });
    });

    if (discountData.items.length === 0) {
      showToast('Please select at least one country', 'Error', 'error');
      return null;
    }
  }

  if (discountData.eligibility === 'specific_groups') {
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
  } else if (discountData.eligibility === 'specific_customers') {
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

function updateSummary() {
  const page = $('#discount_type').val();
  if (page === 'free_shipping') {
    $('#summary-page').text('Free Shipping');
  }
  const method = $('#method').val();
  if (method === 'code') {
    $('#summary-method').html('<strong>' + ($('#code').val() || 'Not set') + '</strong><br>' + 'Code');
  } else {
    $('#summary-method').text('Automatic discount: ' + ($('#name').val() || 'Not set'));
  }

  // Update applies to
  const appliesTo = $('input[name="applies_to"]:checked').val();
  if (appliesTo === 'countries') {
    let selectedCountries = [];
    $('#collectionTable tbody tr').each(function() {
      if ($(this).find('.selected-collection').is(':checked')) {
        selectedCountries.push($(this).find('td:first').text());
      }
    });
    
    if (selectedCountries.length > 0) {
      if (selectedCountries.length === 1) {
        $('#summary-applies').text('For ' + selectedCountries[0]);
      } else {
        $('#summary-applies').text(`For ${selectedCountries.length} countries`);
      }
    } else {
      $('#summary-applies').text('No countries selected');
    }
  } else {
    $('#summary-applies').text('All countries');
  }

  // Update shipping rates
  if ($('#exclude_shipping_rates').is(':checked')) {
    $('#summary-applies').append('<br>Excludes shipping rates over ' + $('#shipping_rate').val() + ' AED');
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
    let selectedSegments = [];
    $('#customerSegmentsTable tbody tr').each(function() {
      if ($(this).find('.selected-segment').is(':checked')) {
        selectedSegments.push($(this).find('td:first').text().trim());
      }
    });

    if (selectedSegments.length === 1) {
      $('#summary-customers').text('For ' + selectedSegments[0]);
    } else if (selectedSegments.length > 1) {
      $('#summary-customers').text(`For ${selectedSegments.length} customer segments`);
    } else {
      $('#summary-customers').text('Specific groups (none selected)');
    }
  } else if (eligibility === 'specific_customers') {
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
    } else {
      $('#summary-customers').text('Specific customers (none selected)');
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

  // Update combinations
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
