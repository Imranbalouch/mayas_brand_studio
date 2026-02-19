document.title = "Dashboard | Edit Market";
$(document).ready(function() {
  // Initialize Select2 for MarketPerformanceSort
  $("#MarketPerformanceSort").select2({
    allowClear: false,
    minimumResultsForSearch: Infinity
  });

  $("#link").html(get_view_base_url());

  // Handle dropdown active/inactive toggle
  $(".active-inactive .dropdown-menu a").on("click", function(e) {
    e.preventDefault();
    
    // Update active class on the clicked <a> element
    $(this).siblings().removeClass("active");
    $(this).addClass("active");

    // Update dropdown toggle text and input
    $(this).parents(".dropdown.active-inactive").find(".dropdown-toggle span").html($(this).attr("val"));
    $(this).parents(".dropdown.active-inactive").find(".dropdown-toggle input").val($(this).attr("val"));

    // Get the dropdown-menu element
    const $dropdownMenu = $(this).parents(".dropdown-menu");

    // Update classes on dropdown-menu based on selection
    if ($(this).attr("val") === "Active") {
      $(this).parents(".dropdown.active-inactive").find(".dropdown-toggle").addClass("active");
      $dropdownMenu.removeClass("has-desc").addClass("active");
    } else {
      $(this).parents(".dropdown.active-inactive").find(".dropdown-toggle").removeClass("active");
      $dropdownMenu.addClass("has-desc").removeClass("active");
    }

    // Handle cannot-activated-txt visibility
    if ($(this).attr("val") === "Inactive") {
      $dropdownMenu.find(".cannot-activated-txt").removeClass("d-none");
    } else {
      $dropdownMenu.find(".cannot-activated-txt").addClass("d-none");
    }

    var urlParams = new URLSearchParams(window.location.search);
    var uuid = urlParams.get('uuid');

    if (uuid) {
      // Use the specific status update API
      updateMarketStatus(uuid, $(this).attr("val"));
    } else {
      showToast("Market UUID not found.", "Error", "error");
    }
  });

  // Show countries/regions section
  $("#showCountriesRegions").on("click", function(e) {
    e.preventDefault();
    $(this).parent().addClass("d-none");
    $("#countriesRegions").removeClass("d-none");
  });

  // Show market handle input
  $("#showMarketHandleInput").on("click", function(e) {
    e.preventDefault();
    $(this).parent().addClass("d-none");
    $("#marketHandleInput").removeClass("d-none");
  });

  // Fetch market data if UUID is present
  var urlParams = new URLSearchParams(window.location.search);
  var uuid = urlParams.get('uuid');

  if (uuid) {
    fetchMarketData(uuid);
  }

  // Handle save button in edit market modal - use general update API
  $("#editMarket .btn-primary").on("click", function(e) {
    e.preventDefault();
    updateMarket(uuid);
  });

  $(".del").on("click", function(e) {
    e.preventDefault();
  
    // Get UUID from URL parameters
    var urlParams = new URLSearchParams(window.location.search);
    var uuid = urlParams.get('uuid');
  
    if (!uuid) {
      showToast("Market UUID not found.", "Error", "error");
      return;
    }
  
    Swal.fire({
      title: "Are you sure?",
      text: "This will permanently delete the market!",
      icon: "warning",
      showCancelButton: true,
      reverseButtons: true,
      confirmButtonText: "Delete",
      cancelButtonText: "Cancel"
    }).then((result) => {
      if (result.isConfirmed) {
        deleteMarket(uuid);
      }
    });
  });
// Function to update market delete only
function deleteMarket(uuid) {  
  $.ajax({
    url: ApiPlugin + "/ecommerce/market/delete_market/" + uuid,
    type: "Delete",
    contentType: "application/json",
    data: JSON.stringify({
      id: uuid
    }),
    beforeSend: function(xhr) {
      xhr.setRequestHeader("Authorization", "Bearer " + strkey);
      xhr.setRequestHeader("menu-uuid", menu_id);
      imgload.show();
    },
    success: function(response) {
      imgload.hide();
      if (response.status_code === 200) {
          message="Market Deleted Successfully"
        showToast(message, 'Success', 'success', "?P=markets&M="+menu_id+'&id='+id);
        //updateUIAfterStatusChange(statusValue);
      } else {
        showToast("Failed to update market deleted", "Error", "error");
        // Revert UI changes on failure
       // revertStatusUI();
      }
    },
    error: function(xhr, status, error) {
      imgload.hide();
      console.error("Status update error:", error, xhr.responseText);
      
      let errorMessage = "Failed to update market status. Please try again.";
      if (xhr.status === 404) {
        errorMessage = "Market not found.";
      } else if (xhr.status === 422) {
        let errors = xhr.responseJSON?.errors;
        if (errors) {
          let errorMessages = "";
          $.each(errors, function(field, messages) {
            messages.forEach(function(message) {
              errorMessages += `<li>${message}</li>`;
            });
          });
          errorMessage = `<ul>${errorMessages}</ul>`;
        }
      }
      
      showToast(errorMessage, "Error", "error");
      // Revert UI changes on failure
    //  revertStatusUI();
    }
  });
}
  // Navigation for market settings
  $(".country-languages").on("click", function(e) {
    e.preventDefault();
    let page = 'country-languages';
    window.location.assign('?P=' + page + '&M=' + menu_id + '&uuid=' + uuid);
  });
  $(".country-pricing").on("click", function(e) {
    e.preventDefault();
    let page = 'country-pricing';
    window.location.assign('?P=' + page + '&M=' + menu_id + '&uuid=' + uuid);
  });
  $(".country-duties").on("click", function(e) {
    e.preventDefault();
    let page = 'country-duties';
    window.location.assign('?P=' + page + '&M=' + menu_id + '&uuid=' + uuid);
  });
  $(".country-shipping").on("click", function(e) {
    e.preventDefault();
    let page = 'country-shipping';
    window.location.assign('?P=' + page + '&M=' + menu_id + '&uuid=' + uuid);
  });
});

// Function to update market status only
function updateMarketStatus(uuid, status) {
  let statusValue = status === "Active" ? 1 : 0;
  
  $.ajax({
    url: ApiPlugin + "/ecommerce/market/status/" + uuid,
    type: "POST",
    contentType: "application/json",
    data: JSON.stringify({
      status: statusValue
    }),
    beforeSend: function(xhr) {
      xhr.setRequestHeader("Authorization", "Bearer " + strkey);
      xhr.setRequestHeader("menu-uuid", menu_id);
      imgload.show();
    },
    success: function(response) {
      imgload.hide();
      if (response.status_code === 200) {
        showToast("Market status updated successfully", "Success", "success");
        updateUIAfterStatusChange(statusValue);
      } else {
        showToast("Failed to update market status", "Error", "error");
        // Revert UI changes on failure
        revertStatusUI();
      }
    },
    error: function(xhr, status, error) {
      imgload.hide();
      console.error("Status update error:", error, xhr.responseText);
      
      let errorMessage = "Failed to update market status. Please try again.";
      if (xhr.status === 404) {
        errorMessage = "Market not found.";
      } else if (xhr.status === 422) {
        let errors = xhr.responseJSON?.errors;
        if (errors) {
          let errorMessages = "";
          $.each(errors, function(field, messages) {
            messages.forEach(function(message) {
              errorMessages += `<li>${message}</li>`;
            });
          });
          errorMessage = `<ul>${errorMessages}</ul>`;
        }
      }
      
      showToast(errorMessage, "Error", "error");
      // Revert UI changes on failure
      revertStatusUI();
    }
  });
}

// Function to revert status UI changes on API failure
function revertStatusUI() {
  // Get current UI status
  const $dropdown = $(".dropdown.active-inactive");
  const currentStatus = $dropdown.find(".dropdown-toggle span").text();
  
  // Revert to opposite status
  const revertStatus = currentStatus === "Active" ? "Inactive" : "Active";
  
  // Update UI to reverted status
  $dropdown.find(".dropdown-toggle span").text(revertStatus);
  $dropdown.find(".dropdown-toggle input").val(revertStatus);
  
  // Update dropdown menu active states
  $dropdown.find(".dropdown-menu a").removeClass("active");
  $dropdown.find(`.dropdown-menu a[val="${revertStatus}"]`).addClass("active");
  
  updateUIAfterStatusChange(revertStatus === "Active" ? 1 : 0);
}

// Function to fetch market data
function fetchMarketData(uuid) {
  $.ajax({
    url: ApiPlugin + "/ecommerce/market/edit_market/" + uuid,
    type: "GET",
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
        const data = response.data;
        updateUIAfterStatusChange(data.status);
        // Update market name in header
        $("h3 g-t").text(data.market_name);
        $(".country-name").text(data.market_name);

        // Update modal fields
        $("#editMarket input[placeholder='e.g. North America']").val(data.market_name);
        $("#marketHandleInput input").val(data.slug);

        // Parse country_ids from JSON string
        const selectedCountryIds = data.country_id ? JSON.parse(data.country_id) : [];
        const countryImages = data.country_images ? data.country_images.split(',').map(img => img.trim()) : [];

        $(".edit-country-region-prev span").text(data.country_names || 'No countries selected');
        $(".edit-country-region-prev-handle span").text(data.slug || 'No Slug');
        $(".countries-regions-total").text(selectedCountryIds.length);

        fetchAndRenderCountries(selectedCountryIds, countryImages);
      } else {
        showToast(response.message || "Failed to load market data", "Error", "error");
      }
    },
    error: function(xhr, status, error) {
      imgload.hide();
      console.error("Error fetching market data:", error);
      showToast("Failed to load market data. Please try again.", "Error", "error");
    }
  });
}

function updateUIAfterStatusChange(status) {
  const $dropdown = $(".dropdown.active-inactive");
  const $dropdownMenu = $dropdown.find(".dropdown-menu");

  if (status === 1) {
    // Active
    $dropdown.find(".dropdown-toggle").addClass("active");
    $dropdown.find(".dropdown-toggle span").text("Active");
    $dropdown.find(".dropdown-toggle input").val("Active");
    $dropdownMenu.removeClass("has-desc").addClass("active");
    $dropdownMenu.find(".cannot-activated-txt").addClass("d-none");
    $dropdownMenu.find("a[val='Active']").addClass("active");
    $dropdownMenu.find("a[val='Inactive']").removeClass("active");
  } else {
    // Inactive
    $dropdown.find(".dropdown-toggle").removeClass("active");
    $dropdown.find(".dropdown-toggle span").text("Inactive");
    $dropdown.find(".dropdown-toggle input").val("Inactive");
    $dropdownMenu.addClass("has-desc").removeClass("active");
    $dropdownMenu.find(".cannot-activated-txt").removeClass("d-none");
    $dropdownMenu.find("a[val='Inactive']").addClass("active");
    $dropdownMenu.find("a[val='Active']").removeClass("active");
  }
}

// Function to fetch and render countries with selected ones checked
function fetchAndRenderCountries(selectedCountryIds = []) {
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
        renderCountries(response.data, selectedCountryIds);
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

// Function to render countries in the modal with selected ones checked
function renderCountries(countries, selectedCountryIds) {
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
    const selectedCount = countriesInRegion.filter(country => 
      selectedCountryIds.includes(country.uuid)
    ).length;
    
    const regionHtml = `
      <div class="countries-group">
        <div class="head">
          <div class="name">
            <div class="form-check m-0">
              <input class="form-check-input" type="checkbox" ${selectedCount === countriesInRegion.length ? 'checked' : ''}>
            </div>
            <span class="d-none">${region}</span>
          </div>
          <div class="count">
            <span><span class="total">${selectedCount}</span>/${countriesInRegion.length}</span>
            <span><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" width="16" height="16" fill="#4a4a4a"><path fill-rule="evenodd" d="M12.53 10.28a.75.75 0 0 1-1.06 0l-3.47-3.47-3.47 3.47a.749.749 0 1 1-1.06-1.06l4-4a.75.75 0 0 1 1.06 0l4 4a.75.75 0 0 1 0 1.06"></path></svg></span>
          </div>
        </div>
        <div class="body">
          ${countriesInRegion.map(country => `
            <label class="country-item">
              <div class="name">
                <div class="form-check m-0">
                  <input class="form-check-input" type="checkbox" data-uuid="${country.uuid}" 
                    ${selectedCountryIds.includes(country.uuid) ? 'checked' : ''}>
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
    $group.find(".head .form-check-input").prop("checked", 
      $group.find(".country-item .form-check-input:checked").length > 0 &&
      $group.find(".country-item .form-check-input:checked").length === $group.find(".country-item .form-check-input").length
    );
    updateTotalSelectedCountries();
  });
}

// Function to update the total selected countries count
function updateTotalSelectedCountries() {
  const totalSelected = $(".countries-regions-list .countries-group .body .country-item .form-check-input:checked").length;
  $(".countries-regions-total").html(totalSelected);
}

// Function to update market data (for full market updates via modal)
function updateMarket(uuid) {
  let marketName = $("#editMarket input[placeholder='e.g. North America']").val();
  let marketHandle = $("#marketHandleInput input").val();
  let status = $(".dropdown.active-inactive .dropdown-toggle input").val(); 

  let selectedCountries = [];
  let countryNames = [];
  let countryImages = [];

  $(".countries-group .country-item .form-check-input:checked").each(function() {
    selectedCountries.push($(this).data("uuid"));
    countryNames.push($(this).closest(".country-item").find("span").last().text());

    const imageSrc = $(this).closest(".country-item").find("img").attr("src");
    const relativePath = imageSrc?.replace(AssetsPath, '') || '';
    countryImages.push(relativePath);
  });

  if (!marketName) {
    showToast("Please enter a market name", "Error", "error");
    return;
  }

  let formData = {
    market_name: marketName,
    slug: marketHandle,
    country_names: countryNames.join(", "),
    country_id: selectedCountries,
    country_images: countryImages.join(", "),
    status: status === "Active" ? 1 : 0 
  };

  $.ajax({
    url: ApiPlugin + "/ecommerce/market/update_market",
    type: "POST",
    contentType: "application/json",
    data: JSON.stringify(formData),
    beforeSend: function(xhr) {
      xhr.setRequestHeader("Authorization", "Bearer " + strkey);
      xhr.setRequestHeader("menu-uuid", menu_id);
      xhr.setRequestHeader("uuid", uuid);
      imgload.show();
    },
    success: function(response) {
      imgload.hide();
      if (response.status_code === 200) {
        showToast(response.message || "Market updated successfully", "Success", "success", '?P=markets&M='+menu_id+'&id='+uuid);
        // Update UI
        $("h3 g-t").text(marketName);
        $(".edit-country-region-prev span").text(countryNames.join(", "));
        $(".countries-regions-total").text(selectedCountries.length);
        updateUIAfterStatusChange(status === "Active" ? 1 : 0); 
        $("#editMarket").modal("hide");
      } else {
        showToast(response.message || "Something went wrong!", "Error", "error");
      }
    },
    error: function(xhr, status, error) {
      imgload.hide();
      console.error("Update error:", error, xhr.responseText);
      if (xhr.status === 422) {
        let errors = xhr.responseJSON.errors;
        let errorMessages = "";
        $.each(errors, function(field, messages) {
          messages.forEach(function(message) {
            errorMessages += `<li>${message}</li>`;
          });
        });
        showToast(`<ul>${errorMessages}</ul>`, "Error", "error");
      } else {
        showToast("Failed to update market. Please try again.", "Error", "error");
      }
    }
  });
}

function deleteLanguage(uuid, $row) {
  Swal.fire({
      title: 'Are you sure?',
      text: 'You will not be able to recover this language!',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Yes, delete it!',
      cancelButtonText: 'Cancel'
  }).then((result) => {
      if (result.isConfirmed) {
          $.ajax({
              url: ApiForm + '/delete_language/' + uuid,
              method: 'DELETE',
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
                      showToast(response.message || 'Language deleted successfully', 'Success', 'success');
                      $row.fadeOut(300, function() {
                          $(this).remove();
                      });
                  } else {
                      showToast(response.message || 'Failed to delete language', 'Error', 'error');
                  }
              },
              error: function(xhr, status, error) {
                  imgload.hide();
                  console.error('Error deleting language:', error, xhr.responseText);
                  let errorMessage = 'Failed to delete language. Please try again.';
                  if (xhr.status === 404) {
                      errorMessage = 'Language not found.';
                  } else if (xhr.status === 500) {
                      errorMessage = 'Server error occurred.';
                  }
                  showToast(errorMessage, 'Error', 'error');
              }
          });
      }
  });
}

function get_view_base_url(){
  let urlWithoutQuery = window.location.href.split('?')[0];  
  if (urlWithoutQuery.match(/\/[^\/]*\.[^\/]*$/)) {
    urlWithoutQuery = urlWithoutQuery.substring(0, urlWithoutQuery.lastIndexOf('/') + 1);
  } 
  return urlWithoutQuery;
}