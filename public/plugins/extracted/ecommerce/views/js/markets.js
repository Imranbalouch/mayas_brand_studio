document.title = "Dashboard | Markets";
$(document).ready(function() {

  function fetchAndRenderCountries() {
    $.ajax({
      url: ApiPlugin + '/ecommerce/get_active_countries',
      method: 'GET',
      contentType: "application/json",
      dataType: "json",
      beforeSend: function(xhr) {
        xhr.setRequestHeader("Authorization", "Bearer " + strkey);
        xhr.setRequestHeader("menu-uuid", menu_id);
        imgload.show();
      },
      success: function(response) {
        imgload.hide();
        if (response.status_code === 200) {
          renderCountries(response.data);
        } else {
          console.error('Error fetching countries:', response.message);
        }
      },
      error: function(xhr, status, error) {
        imgload.hide();
        console.error('API request failed:', error);
      }
    });
  }

  // Function to render countries in the modal
  function renderCountries(countries) {
    const $countriesGroups = $('.countries-regions-groups');
    $countriesGroups.empty(); // Clear existing content

    // Group countries by region
    const regions = {};
    countries.forEach(country => {
      if (!regions[country.region]) {
        regions[country.region] = [];
      }
      regions[country.region].push(country);
    });

    // Render each region and its countries
    Object.keys(regions).forEach(region => {
      const countriesInRegion = regions[region];
      const regionHtml = `
        <div class="countries-group">
          <div class="head">
            <div class="name">
              <div class="form-check m-0">
                <input class="form-check-input" type="checkbox">
              </div>
              <span class="d-none">${region}</span>
            </div>
            <div class="count">
              <span><span class="total">0</span>/${countriesInRegion.length}</span>
              <span><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" width="16" height="16" fill="#4a4a4a"><path fill-rule="evenodd" d="M12.53 10.28a.75.75 0 0 1-1.06 0l-3.47-3.47-3.47 3.47a.749.749 0 1 1-1.06-1.06l4-4a.75.75 0 0 1 1.06 0l4 4a.75.75 0 0 1 0 1.06"></path></svg></span>
            </div>
          </div>
          <div class="body">
            ${countriesInRegion.map(country => `
              <label class="country-item">
                <div class="name">
                  <div class="form-check m-0">
                    <input class="form-check-input" type="checkbox" data-uuid="${country.uuid}">
                  </div>
                  <span>
                    <img src="${AssetsPath + country.image}" width="32" alt="">
                  </span>
                  <span>${country.name}</span>
                </div>
                <div class="count">${country.in_market ? `in ${country.in_market}` : ''}</div>
              </label>
            `).join('')}
          </div>
        </div>
      `;
      $countriesGroups.append(regionHtml);
    });

    // Rebind event handlers for dynamically added elements
    bindCountryEventHandlers();
  }

  // Function to bind event handlers for countries
  function bindCountryEventHandlers() {
    // Toggle region body
    $(".countries-group .head").off("click").on("click", function(e) {
      if ($(e.target).is(".form-check-input")) return;
      e.preventDefault();
      $(this).next().slideToggle(200);
    });

    // Region checkbox: select/deselect all countries in the region
    $(".countries-group .head .form-check-input").off("change").on("change", function() {
      const $group = $(this).parents(".countries-group");
      const isChecked = $(this).prop("checked");
      $group.find(".country-item .form-check-input").prop("checked", isChecked);
      $group.find(".head .total").html($group.find(".country-item .form-check-input:checked").length);
      updateTotalSelectedCountries();
    });

    // Country checkbox: update region checkbox and total
    $(".countries-group .country-item .form-check-input").off("change").on("change", function() {
      const $group = $(this).parents(".countries-group");
      const $body = $(this).parents(".body");
      $group.find(".head .total").html($body.find(".form-check-input:checked").length);
      $group.find(".head .form-check-input").prop("checked", $group.find(".country-item .form-check-input:checked").length > 0);
      updateTotalSelectedCountries();
    });
  }

  // Function to update the total selected countries count
  function updateTotalSelectedCountries() {
    const totalSelected = $(".countries-regions-list .countries-group .body .country-item .form-check-input:checked").length;
    $(".countries-regions-total").html(totalSelected);
  }

  // Fetch countries when the "Add Market" modal is shown
  $('#addMarket').on('shown.bs.modal', function() {
    fetchAndRenderCountries();
  });
  // Existing event handlers (search, toggle, checkboxes) remain unchanged
  $(".countries-regions-list .filter .search-icon").on("click", function(e) {
    e.preventDefault();
    $(".countries-regions-list .filter .search-bar").removeClass("d-none");
    $(".countries-regions-list .filter .search-bar .input.has-icon input").focus();
  });
  $(".countries-regions-list .filter .search-bar a").on("click", function(e) {
    e.preventDefault();
    $(".countries-regions-list .filter .search-bar").addClass("d-none");
  });

  $(".countries-group .head").on("click", function(e) {
    if ($(e.target).is(".form-check-input")) return;
    e.preventDefault();
    $(this).next().slideToggle(200);
  });

  $(".countries-group .head .form-check-input").on("change", function() {
    if ($(this).prop("checked")) {
      $(this).parents(".countries-group").find(".country-item .form-check-input").prop("checked", true);
    } else {
      $(this).parents(".countries-group").find(".country-item .form-check-input").prop("checked", false);
    }
    $(this).parents(".countries-group").find(".head .total").html($(this).parents(".countries-group").find(".country-item .form-check-input:checked").length);
  });

  $(".countries-group .country-item .form-check-input").on("change", function() {
    $(this).parents(".countries-group").find(".head .total").html($(this).parents(".body").find(".form-check-input:checked").length);

    if ($(this).parents(".countries-group").find(".country-item .form-check-input:checked").length > 0) {
      $(this).parents(".countries-group").find(".head .form-check-input").prop("checked", true);
    } else {
      $(this).parents(".countries-group").find(".head .form-check-input").prop("checked", false);
    }
  });

  $(".countries-group .form-check-input").on("change", function() {
    $(".countries-regions-total").html($(".countries-regions-list .countries-group .body .country-item .name .form-check-input:checked").length);
  });
  
  // New function to fetch and render markets
  function fetchAndRenderMarkets() {
    $.ajax({
      url: ApiPlugin + '/ecommerce/market/get_market',
      method: 'GET',
      contentType: "application/json",
      dataType: "json",
      beforeSend: function (xhr) {
        xhr.setRequestHeader("Authorization", "Bearer " + strkey);
        xhr.setRequestHeader("menu-uuid", menu_id);
        imgload.show();
      },
      success: function(response) {
        imgload.hide();
        if (response.status_code === 200) {
          renderMarkets(response.data);
        } else {
          console.error('Error fetching markets:', response.message);
        }
      },
      error: function(xhr, status, error) {
        console.error('API request failed:', error);
      }
    });
  }

  // Function to render markets in the DOM
  function renderMarkets(markets) {
    const $activeBody = $('.market-country-card .body').eq(0); // Active markets body
    const $inactiveBody = $('.market-country-card .body').eq(1); // Inactive markets body
    const $activeHeader = $('.market-country-card .head').eq(0); // Active header

    // Clear existing content
    $activeBody.empty();
    $inactiveBody.empty();

    let activeCount = 0;
    let totalCount = markets.length;

    // Iterate through markets and append to appropriate section
    markets.forEach(market => {
      const isActive = market.status === 1;
      if (isActive) activeCount++;

      const marketHtml = `
        <a href="" class="market-country-item edit-market d-flex align-items-center justify-content-between" data-uuid="${market.uuid}">
          <div class="name">
            
            <div>
              <h5>${market.market_name}</h5>
              ${market.primary ? '<p>Primary</p>' : ''}
            </div>
          </div>
          <div class="ssc d-none">
            <h5>
              <span>0%</span>
              <span class="percentage">
                <svg viewBox="0 0 10 16" height="16" role="img" tabindex="-1">
                  <g color="#8a8a8a">
                    <g transform="translate(0, 5.25)">
                      <svg xmlns="http://www.w3.org/2000/svg" width="6" height="6" fill="none" viewBox="0 0 6 6">
                        <path fill="currentColor" fill-rule="evenodd" d="M1 .25a.75.75 0 1 0 0 1.5h2.19L.594 4.345a.75.75 0 0 0 1.06 1.06L4.25 2.811V5a.75.75 0 0 0 1.5 0V1A.748.748 0 0 0 5 .25H1Z" clip-rule="evenodd"></path>
                      </svg>
                    </g>
                  </g>
                </svg> 0%
              </span>
            </h5>
            <p>Share of total sales</p>
          </div>
          <div class="ssc d-none">
            <h5>
              <span>Rs 0.00</span>
              <span class="percentage">
                <svg viewBox="0 0 10 16" height="16" role="img" tabindex="-1">
                  <g color="#8a8a8a">
                    <g transform="translate(0, 5.25)">
                      <svg xmlns="http://www.w3.org/2000/svg" width="6" height="6" fill="none" viewBox="0 0 6 6">
                        <path fill="currentColor" fill-rule="evenodd" d="M1 .25a.75.75 0 1 0 0 1.5h2.19L.594 4.345a.75.75 0 0 0 1.06 1.06L4.25 2.811V5a.75.75 0 0 0 1.5 0V1A.748.748 0 0 0 5 .25H1Z" clip-rule="evenodd"></path>
                      </svg>
                    </g>
                  </g>
                </svg> 0%
              </span>
            </h5>
            <p>Sales</p>
          </div>
          <div class="ssc d-none">
            <h5>
              <span>0%</span>
              <span class="percentage">
                <svg viewBox="0 0 10 16" height="16" role="img" tabindex="-1">
                  <g color="#8a8a8a">
                    <g transform="translate(0, 5.25)">
                      <svg xmlns="http://www.w3.org/2000/svg" width="6" height="6" fill="none" viewBox="0 0 6 6">
                        <path fill="currentColor" fill-rule="evenodd" d="M1 .25a.75.75 0 1 0 0 1.5h2.19L.594 4.345a.75.75 0 0 0 1.06 1.06L4.25 2.811V5a.75.75 0 0 0 1.5 0V1A.748.748 0 0 0 5 .25H1Z" clip-rule="evenodd"></path>
                      </svg>
                    </g>
                  </g>
                </svg> 0%
              </span>
            </h5>
            <p>Conversion rate</p>
          </div>
          <div class="arrow">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" width="16" hieght="16">
              <path fill-rule="evenodd" d="M5.72 12.53a.75.75 0 0 1 0-1.06l3.47-3.47-3.47-3.47a.749.749 0 1 1 1.06-1.06l4 4a.75.75 0 0 1 0 1.06l-4 4a.75.75 0 0 1-1.06 0"></path>
            </svg>
          </div>
        </a>
      `;

      if (isActive) {
        $activeBody.append(marketHtml);
      } else {
        $inactiveBody.append(marketHtml);
      }
    });

    // Update the Active header
    $activeHeader.text(`Active (${activeCount} of ${totalCount})`);

    // Bind click events for edit buttons
    $(".edit-market").on("click", function(e) {
      e.preventDefault();
      const marketUuid = $(this).data("uuid");
      let page = 'edit-country';
      window.location.assign('?P=' + page + '&M=' + menu_id + '&uuid=' + marketUuid);
    });
  }

  // Call the function to fetch markets on page load
  fetchAndRenderMarkets();

    $('#addMarket .modal-footer .btn-primary').off('click').on('click', function() {
    const marketName = $('#addMarket input.form-control.md').val().trim();
    const selectedCountries = [];
    const countryNames = [];
    const countryImages = []; // Array to store country image relative paths


    $('.countries-regions-groups .country-item .form-check-input:checked').each(function() {
      selectedCountries.push($(this).data('uuid'));
      countryNames.push($(this).closest('.country-item').find('span').last().text());
      const imageSrc = $(this).closest('.country-item').find('img').attr('src');
      const relativePath = imageSrc.replace(AssetsPath, ''); 
      countryImages.push(relativePath);
    });

    if (!marketName) {
      showToast('Please enter a market name', 'Error', 'error');
      return;
    }

    if (selectedCountries.length === 0) {
      showToast('Please select at least one country', 'Error', 'error');
      return;
    }

    // Prepare the data to send
    const marketData = {
      name: marketName,
      country_id: selectedCountries, // Array of country UUIDs
      country_names: countryNames.join(', '), // Comma-separated string of country names
      country_images: countryImages.join(', '), // Comma-separated string of relative image paths
    };

    // Send the AJAX request
    $.ajax({
      url: ApiPlugin + '/ecommerce/market/add_market',
      method: 'POST',
      contentType: "application/json",
      dataType: "json",
      data: JSON.stringify(marketData),
      beforeSend: function(xhr) {
        xhr.setRequestHeader("Authorization", "Bearer " + strkey);
        xhr.setRequestHeader("menu-uuid", menu_id);
        imgload.show();
      },
      success: function(response) {
        imgload.hide();
        if (response.status_code === 200) {
          showToast(response.message || "Market added successfully", 'Success', 'success');
          // Success - close modal and refresh markets list
          $('#addMarket').modal('hide');
          fetchAndRenderMarkets();
          // Clear the form
          $('#addMarket input.form-control.md').val('');
          $('.countries-regions-groups .form-check-input').prop('checked', false);
          $('.countries-regions-total').text('0');
        } else {
          console.error('Error adding market:', response.message);
          showToast(response.message || 'Failed to add market', 'Error', 'error');
        }
      },
      error: function(xhr, status, error) {
        imgload.hide();
        console.error('API request failed:', error);
        showToast('Error: Failed to add market', 'Error', 'error');
      }
    });
  });
});