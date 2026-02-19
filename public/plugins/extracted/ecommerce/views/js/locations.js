document.title = "Dashboard | Locations";

$(document).ready(function() {
  // Initialize variables
  let currentFilter = 'all';
  let currentSort = 'az';
  let currentStatusFilter = null;
  let currentPosFilter = null;
  let searchQuery = '';
  let locationsData = [];
  let selectedLocationUuid = null;
  let dataTable = null; // Store DataTable instance

  // Fetch locations data from API
  function fetchLocations() {
    $.ajax({
      url: ApiPlugin + '/ecommerce/warehouse/get_warehouse_values',
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
        if (response.status_code === 200 && response.data) {
          locationsData = response.data;
          renderLocationsTable(locationsData);
          renderDefaultLocationDropdown(locationsData);
          updateLocationsCount(locationsData);
        }
      },
      error: function(error) {
        imgload.hide();
        console.error('Error fetching locations:', error);
      }
    });
  }

  // Update locations count in header
  function updateLocationsCount(locations) {
    const activeCount = locations.filter(loc => loc.status).length;
    const totalAllowed = locations.filter(loc => loc.uuid).length;
    $('.card-header .activeline div').text(`Using ${activeCount} of ${totalAllowed} active locations available on your plan`);
  }

  // Initialize DataTable
  function initDataTable() {
    if (dataTable) {
      dataTable.destroy(); // Destroy existing DataTable if it exists
    }

    dataTable = $('#Table_View').DataTable({
      destroy: true,
      retrieve: true,
      ordering: false,
      dom: "<'row'<'col-sm-12'tr>>",
      bLengthChange: false,
      searching: true,
      order: [[0, currentSort === 'az' ? 'asc' : 'desc']],
      language: {
        sZeroRecords: `<div class="no-record-found">
                        <p>No matching records found</p>
                      </div>`
      },
      search: {
        return: false // Allow live search without Enter key
      }
    });

    // Apply custom filters
    $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
      const rowData = locationsData[dataIndex];
      if (!rowData) return false;

      const name = (rowData.location_name || '').toLowerCase();
      const address = (rowData.location_address || '').toLowerCase();
      const status = rowData.status ? '1' : '0';
      const pos = status === '1' ? 'pro' : 'lite';

      // Search filter
      const matchesSearch = !searchQuery ||
        name.startsWith(searchQuery.toLowerCase()) ||
        address.startsWith(searchQuery.toLowerCase());

      // Main filter (active, inactive, pro, lite)
      let matchesFilter = true;
      if (currentFilter === 'active') {
        matchesFilter = status === '1';
      } else if (currentFilter === 'inactive') {
        matchesFilter = status === '0';
      } else if (currentFilter === 'pro') {
        matchesFilter = pos === 'pro';
      } else if (currentFilter === 'lite') {
        matchesFilter = pos === 'lite';
      }

      // Status filter
      const matchesStatus = currentStatusFilter === null || status === currentStatusFilter;

      // POS filter
      const matchesPos = currentPosFilter === null || pos === currentPosFilter;

      return matchesSearch && matchesFilter && matchesStatus && matchesPos;
    });
  }

  // Render locations table
  function renderLocationsTable(data) {
    const $tableBody = $('#locationsTableBody');
    $tableBody.empty();
    locationsData = data; // Update global data

    if (!data || data.length === 0) {
      initDataTable();
      return;
    }

    // Populate table with data
    data.forEach(location => {
      const status = location.status ? '1' : '0';
      const posType = status === '1' ? 'pro' : 'lite';

      $tableBody.append(`
        <tr class="edit-location" data-uuid="${location.uuid}" data-name="${location.location_name.toLowerCase()}" data-status="${status}" data-pos="${posType}">
          <td>
            <div class="collection-table-info">
              <div>
                <div class="title">${location.location_name}</div>
                <div class="sub-title">${location.location_address || 'No address provided'}</div>
              </div>
            </div>
          </td>
          <td><span class="pos-${posType}">POS ${posType === 'pro' ? 'Pro' : 'Lite'}</span></td>
          <td><span class="badge-${status === '1' ? 'active' : 'inactive'}">${status === '1' ? 'Active' : 'Inactive'}</span></td>
        </tr>
      `);
    });

    initDataTable();
  }

  // Render default location dropdown
  function renderDefaultLocationDropdown(data) {
    const $dropdownMenu = $(".change-default-location-dropdown .dropdown-menu");
    const $defaultLocationBox = $(".change-default-location-box .info");
    $dropdownMenu.empty();

    let defaultLocation = data.find(loc => loc.is_default === 1);

    if (defaultLocation) {
      selectedLocationUuid = defaultLocation.uuid;
      $defaultLocationBox.find(".title").html(defaultLocation.location_name);
      $defaultLocationBox.find(".desc").html(defaultLocation.location_address || 'No address provided');
      $(".save-default-location").hide();
    } else {
      $defaultLocationBox.find(".title").html("No location set");
      $defaultLocationBox.find(".desc").html("Add a location first");
      $(".save-default-location").hide();
      $(".change-default-location-dropdown .dropdown-toggle").hide();
    }

    // Populate dropdown menu
    data.forEach(location => {
      const isActive = location.is_default === 1 ? 'active' : '';
      $dropdownMenu.append(
        `<a href="#" class="${isActive}" city="${location.location_name}" 
          country="${location.location_address || 'No address provided'}" 
          data-uuid="${location.uuid}">
            ${location.location_name}
        </a>`
      );
    });

    // Rebind dropdown click events
    $(".change-default-location-dropdown .dropdown-menu a").off("click").on("click", function(e) {
      e.preventDefault();
      $(this).siblings().removeClass("active");
      $(this).addClass("active");
      const city = $(this).attr("city");
      const country = $(this).attr("country");
      const locationUuid = $(this).data("uuid");

      $(this).parents(".change-default-location-box").find(".info .title").html(city);
      $(this).parents(".change-default-location-box").find(".info .desc").html(country);

      if (selectedLocationUuid !== locationUuid) {
        selectedLocationUuid = locationUuid;
        $(".save-default-location").show();
      } else {
        $(".save-default-location").hide();
      }
    });

    // Save default location button handler
    $(".save-default-location").off("click").on("click", function(e) {
      e.preventDefault();
      saveDefaultLocation(selectedLocationUuid);
    });
  }

  // Save default location to backend
  function saveDefaultLocation(locationUuid) {
    if (!locationUuid) return;

    $.ajax({
      url: ApiPlugin + '/ecommerce/warehouse/update_warehousevalue_isdefault/' + locationUuid,
      method: 'POST',
      contentType: "application/json",
      dataType: "json",
      data: JSON.stringify({
        is_default: 1
      }),
      beforeSend: function(xhr) {
        xhr.setRequestHeader("Authorization", "Bearer " + strkey);
        xhr.setRequestHeader("menu-uuid", menu_id);
        imgload.show();
      },
      success: function(response) {
        imgload.hide();
        if (response.status_code === 200) {
          $(".save-default-location").hide();
          showToast(response.message || 'Default location updated successfully', 'Success', 'success');
          fetchLocations();
        }
      },
      error: function(error) {
        imgload.hide();
        console.error('Error updating default location:', error);
      }
    });
  }

  // Filter locations (trigger DataTable redraw)
  function filterLocations() {
    if (dataTable) {
      dataTable.draw();
    }
  }

  // Sort handler
  function updateSort() {
    if (dataTable) {
      dataTable.order([0, currentSort === 'az' ? 'asc' : 'desc']).draw();
    }
  }

  // Search open/close handlers
  $("#searchOpen").on("click", function(e) {
    e.preventDefault();
    $(".pricing-table-filters .filter-search").removeClass("d-none");
    $(".selectDropdown").removeClass("d-none");
    $("#locationSearch").focus();
  });

  $("#searchClose").on("click", function(e) {
    e.preventDefault();
    $(".pricing-table-filters .filter-search").addClass("d-none");
    $(".selectDropdown").addClass("d-none");
    $("#locationSearch").val('');
    searchQuery = '';
    filterLocations();
  });

  // Search input handler
  $("#locationSearch").on("input", function() {
    searchQuery = $(this).val();
    filterLocations();
  });

  // Filter button click handler
  $(".filter-btn").on("click", function(e) {
    e.preventDefault();
    $(".filter-btn").removeClass("active");
    $(this).addClass("active");
    currentFilter = $(this).data("filter");
    filterLocations();
  });

  // Sort button click handler
  $(".sort-btn").on("click", function(e) {
    e.preventDefault();
    currentSort = $(this).data("sort");
    updateSort();
  });

  // Status filter radio button handler
  $('input[name="status-filter"]').on("change", function() {
    currentStatusFilter = $(this).val();
    if (currentStatusFilter) {
      $(".select-dropdown.status .droupdown-head > span > span").html(`is ${currentStatusFilter == '1' ? 'Active' : 'Inactive'}`);
      $(".select-dropdown.status .clear-drop").removeClass("d-none");
      $(".select-dropdown.status .arrow").addClass("d-none");
    }
    filterLocations();
  });

  // POS filter radio button handler
  $('input[name="pos-filter"]').on("change", function() {
    currentPosFilter = $(this).val();
    if (currentPosFilter) {
      $(".select-dropdown.pos-plan .droupdown-head > span > span").html(`is ${currentPosFilter == 'pro' ? 'Pro' : 'Lite'}`);
      $(".select-dropdown.pos-plan .clear-drop").removeClass("d-none");
      $(".select-dropdown.pos-plan .arrow").addClass("d-none");
    }
    filterLocations();
  });

  // Clear status filter
  $(".clear-status").on("click", function(e) {
    e.preventDefault();
    $('input[name="status-filter"]').prop('checked', false);
    currentStatusFilter = null;
    $(".select-dropdown.status .droupdown-head > span > span").html("");
    $(".select-dropdown.status .clear-drop").addClass("d-none");
    $(".select-dropdown.status .arrow").removeClass("d-none");
    filterLocations();
  });

  // Clear POS filter
  $(".clear-pos").on("click", function(e) {
    e.preventDefault();
    $('input[name="pos-filter"]').prop('checked', false);
    currentPosFilter = null;
    $(".select-dropdown.pos-plan .droupdown-head > span > span").html("");
    $(".select-dropdown.pos-plan .clear-drop").addClass("d-none");
    $(".select-dropdown.pos-plan .arrow").removeClass("d-none");
    filterLocations();
  });

  // Dropdown toggle handlers
  $(".custom-dropdown-main .droupdown-head").on("click", function(e) {
    e.preventDefault();
    $(".droupdown-body").not($(this).parent()).removeClass("show");
    $(this).parent().toggleClass("show");
    e.stopPropagation();
  });

  $(".select-dropdown .droupdown-head").on("click", function(e) {
    e.preventDefault();
    $(".select-dropdown").not($(this).parent()).removeClass("show");
    $(this).parent().toggleClass("show");
  });

  $(document).on("click", function(event) {
    if (!$(event.target).closest(".select-dropdown").length) {
      $(".select-dropdown").removeClass("show");
    }
    if (!$(event.target).closest(".custom-dropdown-main").length) {
      $(".droupdown-body").removeClass("show");
    }
  });

  // Add location button handler
  $(".add-location").on("click", function(e) {
    e.preventDefault();
    let page = 'add-location';
    window.location.assign('?P=' + page + '&M=' + menu_id);
  });

  // Initial fetch
  fetchLocations();
});