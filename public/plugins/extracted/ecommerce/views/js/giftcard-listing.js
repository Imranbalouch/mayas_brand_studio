document.title = "Dashboard | Gift Card Listing";
const searchOpen = $("#searchOpen");
const searchBox = $("#Table_View_filter"); // Search bar container
const saveCancelBtns = $(".search-icon-button"); // Save as & Cancel buttons
const customtaborder = $(".custom-tab-order");
const selectDropdown = $(".selectDropdown");

if (searchOpen.length === 0) {
  console.error("Error: searchOpen ID wala element nahi mila!");
}

// console.log("searchOpen found:", searchOpen);

// Default state: searchOpen visible, searchBox and buttons hidden
searchBox.hide();
saveCancelBtns.hide();
selectDropdown.hide();
searchOpen.show();

searchOpen.on("click", function () {
  // console.log("searchOpen clicked!");
  searchBox.css("display", "flex"); // Show search bar
  saveCancelBtns.css("display", "flex"); // Show buttons
  searchOpen.hide(); // Hide searchOpen button
  customtaborder.hide(); // Hide customtaborder
  selectDropdown.show(); // Hide customtaborder
});

// Hide search and show searchOpen again when "Cancel" is clicked
const cancelBtn = saveCancelBtns.find("span:first");

if (cancelBtn.length === 0) {
  console.error("Error: Cancel button nahi mila!");
}

// console.log("Cancel button found:", cancelBtn);

cancelBtn.on("click", function () {
  // console.log("Cancel button clicked!");
  searchBox.hide(); // Hide search bar
  saveCancelBtns.hide(); // Hide buttons
  selectDropdown.hide(); // Hide buttons
  searchOpen.show(); // Show searchOpen button again
  customtaborder.css("display", "inline-flex"); // Show customtaborder
});


const $inputField = $("#duplicateModalInput");
const $createButton = $(".btn-primary.btn-modal-text");
const $currentCount = $("#the-count #current");
const maxLength = 40;

// Pehle se button disable kar dete hain
$createButton.prop("disabled", true);

$inputField.on("input", function () {
  let count = $(this).val().length;
  $currentCount.text(count);

  // Button enable/disable logic
  $createButton.prop("disabled", count === 0);

  // Agar character limit exceed ho jaye, extra characters remove kar do
  if (count > maxLength) {
    $(this).val($(this).val().substring(0, maxLength));
    $currentCount.text(maxLength);
  }
});

$inputField.on("keydown", function (e) {
  let count = $(this).val().length;

  // Agar character limit exceed ho jaye to naye characters ko allow na karein
  if (count >= maxLength && e.key !== "Backspace" && e.key !== "Delete" && !e.ctrlKey) {
    e.preventDefault();
    alert("Maximum 40 characters allowed!");
  }
});

$(".select-dropdown .droupdown-head").on("click", function (e) {
  e.preventDefault();
  $(".select-dropdown").not($(this).parent()).removeClass("show");
  $(this).parent().toggleClass("show");
});

$(".custom-dropdown-main .droupdown-head").on("click", function (e) {
  e.preventDefault();
  $(".droupdown-body").not($(this).parent()).removeClass("show");
  $(this).parent().toggleClass("show");
});

$(document).on("click", function (event) {
  if (!$(event.target).closest(".select-dropdown").length) {
    $(".select-dropdown").removeClass("show");
  }
  if (!$(event.target).closest(".droupdown-body").length) {
    $(".droupdown-body").removeClass("show");
  }
});

$(".select-dropdown.filter-dropdown .droupdown-body a").on("click", function (e) {
  e.preventDefault();
  $(this).parent().find("input").prop("checked", false);
});

$("#Table_View .select-all").on("change", function () {
  if ($(this).prop("checked") === true) {
    $("#Table_View .thead-filter").removeClass("d-none");
    $("#Table_View .form-check-input:not(.select-all)").prop("checked", true);
  } else {
    $("#Table_View .thead-filter").addClass("d-none");
    $("#Table_View .form-check-input:not(.select-all)").prop("checked", false);
  }
  isAnyChecked();
});

$("#Table_View .form-check-input:not(.select-all)").on("change", function () {
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

  if (parseInt(totalCheckedCount) > 0) {
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

function applyFilters() {
  // Collect all selected status filters
  // let selectedStatusFilters = [];
  // $("input[name='status-checkbox']:checked").each(function () {
  //     selectedStatusFilters.push($(this).val().toLowerCase());
  // });

  // // Collect all selected vendor filters
  // let selectedVendorFilters = [];
  // $("input[name='vendor-checkbox']:checked").each(function () {
  //     selectedVendorFilters.push($(this).val().toLowerCase());
  // });

  // // Collect all selected category filters
  // let selectedCategoryFilters = [];
  // $("input[name='category-checkbox']:checked").each(function () {
  //     selectedCategoryFilters.push($(this).val().toLowerCase());
  // });

  // let selectedMarketFilters = [];
  // $("input[name='market-radio']:checked").each(function () {
  //     selectedMarketFilters.push($(this).val().toLowerCase());
  // });

  // // Show all rows if no filters are selected
  // if (
  //     selectedStatusFilters.length === 0 &&
  //     selectedVendorFilters.length === 0 &&
  //     selectedCategoryFilters.length === 0 &&
  //     selectedMarketFilters.length === 0
  // ) {
  //     $("#Table_View tbody tr").show();
  //     return;
  // }

  let selectedFilters = [
    ...$("input[name='status-checkbox']:checked").map((_, el) => el.value.toLowerCase()).get(),
    ...$("input[name='vendor-checkbox']:checked").map((_, el) => el.value.toLowerCase()).get(),
    ...$("input[name='category-checkbox']:checked").map((_, el) => el.value.toLowerCase()).get(),
    ...$("input[name='market-radio']:checked").map((_, el) => el.value.toLowerCase()).get(),
    ...$("input[name='saleschannel-radio']:checked").map((_, el) => el.value.toLowerCase()).get(),
    ...$("input[name='type-checkbox']:checked").map((_, el) => el.value.toLowerCase()).get(),
    ...$("input[name='tag-checkbox']:checked").map((_, el) => el.value.toLowerCase()).get()
  ];

  // Show all rows if no filters are selected
  if (selectedFilters.length === 0 || selectedFilters.includes("allchannel")) {
    $("#Table_View tbody tr").show();
    return;
  }
  // Loop through table rows and filter based on selected filters
  // $("#Table_View tbody tr").each(function () {
  //     let statusText = $(this).find("td:nth-child(3) span").text().trim().toLowerCase();
  //     let vendorText = $(this).find("td:nth-child(8)").text().trim().toLowerCase();
  //     let categoryText = $(this).find("td:nth-child(7)").text().trim().toLowerCase(); // Assuming category is in 4th column
  //     let marketText = $(this).find("td:nth-child(6)").text().trim().toLowerCase(); // Assuming category is in 4th column

  //     // Show row if all filters match (or if one is empty)
  //     console.log('categoryText', categoryText);
  //     if (
  //         (selectedStatusFilters.length === 0 || selectedStatusFilters.includes(statusText)) &&
  //         (selectedVendorFilters.length === 0 || selectedVendorFilters.includes(vendorText)) &&
  //         (selectedCategoryFilters.length === 0 || selectedCategoryFilters.includes(categoryText)) &&
  //         (selectedMarketFilters.length === 0 || selectedMarketFilters.includes(marketText))
  //     ) {
  //         $(this).show();
  //     } else {
  //         $(this).hide();
  //     }
  // });

  $("#Table_View tbody tr").each(function () {
    // Collect text values of the relevant columns
    let rowText = [
      $(this).find("td:nth-child(3) span").text().trim().toLowerCase(), // Status
      $(this).find("td:nth-child(9)").text().trim().toLowerCase(),      // Vendor
      $(this).find("td:nth-child(7)").text().trim().toLowerCase(),      // Category
      $(this).find("td:nth-child(6) .market-value").text().trim().toLowerCase(),     // Market
      $(this).find("td:nth-child(5) .channel-value").text().trim().toLowerCase(),       // Saleschannel
      $(this).find("td:nth-child(8)").text().trim().toLowerCase()       // Type
    ];

    // Show the row if no filters are selected or if at least one text matches any filter
    $(this).toggle(
      selectedFilters.length === 0 || rowText.some(text =>
        selectedFilters.some(filter => text.includes(filter))
      )
    );
  });
}

// Modify the DataTable initialization to properly handle pagination with filters
let tableSearch;

// Initialize DataTable
function get_giftcard_listing() {
  $.ajax({
    url: ApiPlugin + '/ecommerce/giftcard/get_giftcard',
    type: "GET",
    contentType: "application/json",
    dataType: "json",
    headers: { "Authorization": "Bearer " + strkey, 'menu-uuid': menu_id },
    beforeSend: function (request) {
      request.setRequestHeader("Authorization", 'Bearer ' + strkey);
    },
    success: function (response) {
      if (response.status_code === 200) {
        const parentDropdown = $('#Table_View tbody');
        parentDropdown.empty();
        
        response.data.forEach(listGiftCard => {
          let getGiftcardID = listGiftCard.uuid;
          let getGiftcardName = listGiftCard.giftcard;
          let getStatus = listGiftCard.status;
          let getGiftcardCustomer = listGiftCard.customer ? listGiftCard.customer.name : "No Customer";
          let customerLastName = listGiftCard.customer ? listGiftCard.customer.name.split(' ').pop() : "";
          let getGiftcardcreated = listGiftCard.created_at;
          let getGiftcardupdated = listGiftCard.updated_at; // Use updated date
          let getGiftcardexpirydate = listGiftCard.expiry_date; // Use a far future date for sorting if no expiry
          
          // Format dates properly
          let createdDate = new Date(getGiftcardcreated).toISOString().split('T')[0];
          let updatedDate = new Date(getGiftcardupdated).toISOString().split('T')[0];
          let expiryDate = getGiftcardexpirydate ? 
            new Date(getGiftcardexpirydate).toISOString().split('T')[0] : 
            "No Expiry";

            let expirySortValue = getGiftcardexpirydate || null;

          
          // Status handling
          let getStatusValue = '';
          let getStatuslbl = '';

          let today = new Date();
          today.setHours(0, 0, 0, 0);

          if (getStatus == 'deactivate') {
            getStatusValue = "badge-deactivate";
            getStatuslbl = 'Deactivated';
          } else if (getGiftcardexpirydate) {
            let expiryDate = new Date(getGiftcardexpirydate);
            expiryDate.setHours(0, 0, 0, 0); // Normalize expiry date to midnight
            
            if (expiryDate.getTime() === today.getTime()) {
              getStatusValue = "badge-expiring";
              getStatuslbl = 'Expiring';
            } else if (expiryDate < today) {
              getStatusValue = "badge-expired";
              getStatuslbl = 'Expired';
            } else {
              getStatusValue = "badge-success";
              getStatuslbl = 'Active';
            }
          } else {
            getStatusValue = "badge-success";
            getStatuslbl = 'Active'; 
          }
          
          // Balance handling
          let getGiftcardvalueBalance = typeof listGiftCard.balance !== 'undefined' ? 
            listGiftCard.balance : listGiftCard.value;
          let getGiftcardvalue = listGiftCard.value;
          
          let balanceFinal = '';
          if (getGiftcardvalueBalance == getGiftcardvalue) {
            balanceFinal = "full";
          } else if (getGiftcardvalueBalance < getGiftcardvalue && getGiftcardvalueBalance != 0) {
            balanceFinal = "partial";
          } else if (getGiftcardvalueBalance == 0) {
            balanceFinal = "empty";
          }
          
          parentDropdown.append(
            `<tr class="" data-uuid="${getGiftcardID}" data-balance="${balanceFinal}"> 
              <td>
                <div class="btn-edit collection-table-info d-flex">
                  <span class="title edit-giftcard">${getGiftcardName}</span>
                </div>
              </td>
              <td><span class="text-capitalize ${getStatusValue}">${getStatuslbl}</span></td>
              <td data-sort="${customerLastName.toLowerCase()}" data-has-customer="${getGiftcardCustomer !== 'No Customer' ? 'yes' : 'no'}">
                ${getGiftcardCustomer !== '' ? getGiftcardCustomer : 'No Customer'}
              </td>

              <td class="d-none">No Recipient</td>
              <td data-sort="${getGiftcardcreated}">${createdDate}</td>
              <td class="text-end">
                <div class="condition-txt d-flex gap-1 justify-content-end">AED<div class="current-value" balance-value="${balanceFinal}">${getGiftcardvalueBalance}</div> / AED <div class="initial-value">${getGiftcardvalue}</div></div>
              </td>
              <td class="d-none" data-sort="${getGiftcardupdated}">${updatedDate}</td>
              <td class="d-none" data-sort="${expirySortValue || ''}" data-has-expiry="${expirySortValue ? 'yes' : 'no'}">${expiryDate}</td>
              <td class="d-none">${getGiftcardvalue}</td>
            </tr>`
          );
        });

        // Initialize DataTable with pagination enabled
        if ($.fn.DataTable.isDataTable('#Table_View')) {
          $('#Table_View').DataTable().destroy();
        }
        
        tableSearch = $('#Table_View').DataTable({
          paginate: true,
          searching: true,
          ordering: true,
          info: true,
          responsive: true,
          pageLength: 10,
          dom: 'lrtip',
          language: {
            info: "Showing _START_ to _END_ of _TOTAL_ entries",
            paginate: {
              first: "First",
              last: "Last",
              next: "Next",
              previous: "Previous"
            }
          },
          columnDefs: [
            { type: 'customer-last-name', targets: 2, orderDataType: 'customer-last-name' }, // Customer column
             { type: 'date', targets: [4, 6] }, // Date created and updated columns
             { type: 'expiry-date', orderDataType: 'expiry-date', targets: 7 } // Expiry date column
          ],
          order: [[4, 'desc']] // Default sort by Date Created (descending)
        });

        // SEARCH FILTER
        $(".dataTables_filter").hide();
        $('#custom_search').on('input', function () {
          tableSearch.search($(this).val()).draw();
        });

        // Sorting Dropdown
        $("input[name='sort-option']").on("change", function () {
          let sortValue = $(this).val();
          let sortOrder = $("#sortAZ").hasClass("active") ? "asc" : "desc";
          let columnIndex;
          
          // Update sort button labels based on selected column
          updateSortButtonLabels(sortValue);
          
          // Map sort value to column index
          switch (sortValue) {
            case "2": // Customer last name
              columnIndex = 2;
              break;
            case "4": // Date created
              columnIndex = 4;
              break;
            case "5": // Date edited
              columnIndex = 6;
              break;
            case "6": // Expiry date
              columnIndex = 7;
              break;
            default:
              columnIndex = 4; // Default to Date Created
          }
          
          // Apply sorting without resetting filters
          tableSearch.order([columnIndex, sortOrder]).draw();
        });

        // Update sort button labels based on selected sort option
        function updateSortButtonLabels(sortValue) {
          let ascLabel = "A-Z";
          let descLabel = "Z-A";
        
          switch (sortValue) {
            case "6": // Expiry date
            case "4": // Date created
            case "5": // Date edited
              ascLabel = "Oldest";
              descLabel = "Newest";
              break;
            case "2": // Customer last name
              ascLabel = "A-Z";
              descLabel = "Z-A";
              break;
          }
        
          document.getElementById("ascLabel").textContent = ascLabel;
          document.getElementById("descLabel").textContent = descLabel;
        }
        
        // Ascending/Descending Toggle
        $("#sortAZ, #sortZA").on("click", function (e) {
          e.preventDefault();
          $("#sortAZ, #sortZA").removeClass("active");
          $(this).addClass("active");
          let sortOrder = $(this).attr("id") === "sortAZ" ? "asc" : "desc";
          let currentSortValue = $("input[name='sort-option']:checked").val() || "4"; // Default to Date Created
          let columnIndex;

          switch (currentSortValue) {
            case "2": columnIndex = 2; break; // Customer last name
            case "4": columnIndex = 4; break; // Date created
            case "5": columnIndex = 6; break; // Date edited
            case "6": columnIndex = 7; break; // Expiry date
            default: columnIndex = 4; // Default to Date Created
          }

          // Apply sorting without resetting filters
          tableSearch.order([columnIndex, sortOrder]).draw();
        });

        // Custom sorting for customer last name
        // Custom sorting for customer last name
$.fn.dataTable.ext.order['customer-last-name'] = function (settings, col) {
  return this.api().column(col, { order: 'index' }).nodes().map(function (td, i) {
    const hasCustomer = $(td).attr('data-has-customer');
    const sortValue = $(td).attr('data-sort') || '';
    
    if (hasCustomer === 'no') {
      // For records with no customer, return a special marker
      return { hasCustomer: false, name: '' };
    } else {
      // For records with customer, return the actual name
      return { hasCustomer: true, name: sortValue };
    }
  });
};

// Custom comparison functions for ascending and descending sorts:
$.fn.dataTable.ext.type.order['customer-last-name-asc'] = function (a, b) {
  // Records with customers come first, then records without customers
  if (a.hasCustomer && !b.hasCustomer) return -1;
  if (!a.hasCustomer && b.hasCustomer) return 1;
  if (!a.hasCustomer && !b.hasCustomer) return 0;
  
  // Both have customers, compare normally
  return a.name.localeCompare(b.name);
};

$.fn.dataTable.ext.type.order['customer-last-name-desc'] = function (a, b) {
  // Records with customers come first, then records without customers
  if (a.hasCustomer && !b.hasCustomer) return -1;
  if (!a.hasCustomer && b.hasCustomer) return 1;
  if (!a.hasCustomer && !b.hasCustomer) return 0;
  
  // Both have customers, compare in reverse order
  return b.name.localeCompare(a.name);
};

        $.fn.dataTable.ext.order['expiry-date'] = function (settings, col) {
          return this.api().column(col, { order: 'index' }).nodes().map(function (td, i) {
            const hasExpiry = $(td).attr('data-has-expiry');
            const sortValue = $(td).attr('data-sort');
            
            if (hasExpiry === 'no' || !sortValue) {
              // For records with no expiry, return a special marker
              return { hasExpiry: false, date: null };
            } else {
              // For records with expiry, return the actual date
              return { hasExpiry: true, date: new Date(sortValue).getTime() };
            }
          });
        };

        // 4. Custom comparison functions for ascending and descending sorts:
        $.fn.dataTable.ext.type.order['expiry-date-asc'] = function (a, b) {
          // Records with expiry dates come first, then records without expiry
          if (a.hasExpiry && !b.hasExpiry) return -1;
          if (!a.hasExpiry && b.hasExpiry) return 1;
          if (!a.hasExpiry && !b.hasExpiry) return 0;
          
          // Both have expiry dates, compare normally
          return a.date - b.date;
        };

        $.fn.dataTable.ext.type.order['expiry-date-desc'] = function (a, b) {
          // Records with expiry dates come first (newest first), then records without expiry
          if (a.hasExpiry && !b.hasExpiry) return -1;
          if (!a.hasExpiry && b.hasExpiry) return 1;
          if (!a.hasExpiry && !b.hasExpiry) return 0;
          
          // Both have expiry dates, compare in reverse order (newest first)
          return b.date - a.date;
        };

        // FILTER STATUS
        $('input[name="status-radio"]').on("change", function () {
          const val = $(this).val();
          $('#valueItem').text("(" + val + ")");
          $(this).parents('.select-dropdown').find(".arrow-drop").hide();
          $(this).parents('.select-dropdown').find(".clear-drop").show();
          tableSearch.column(2).search(val).draw();
        });

        // FILTER BALANCE
        $('.droupdown-body input[name="balance-radio"]').on('change', function () {
          $(this).parents('.select-dropdown').find(".arrow-drop").hide();
          $(this).parents('.select-dropdown').find(".clear-drop").show();
          const selectedVal = $(this).val();
          let getTextBalance = $(this).parents('.custom-radio').text().trim();
          $(this).parents('.filter-dropdown').find('#valueItemBalance').text(' ' + getTextBalance);

          // Clear existing search
          $.fn.dataTable.ext.search.pop();
          
          $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
            const row = tableSearch.row(dataIndex).node();
            const balanceStatus = $(row).find('.current-value').attr('balance-value');
            const currentVal = parseFloat($(row).find('.current-value').text().trim());
            const initialVal = parseFloat($(row).find('.initial-value').text().trim());

            switch (selectedVal) {
              case 'fullorpartial':
                return (balanceStatus === 'full' || balanceStatus === 'partial');
              case 'full':
                return (balanceStatus === 'full' || (currentVal === initialVal && currentVal > 0));
              case 'partial':
                return (balanceStatus === 'partial' || (currentVal > 0 && currentVal < initialVal));
              case 'empty':
                return (balanceStatus === 'empty' || currentVal === 0);
              default:
                return true;
            }
          });
          
          tableSearch.draw();
        });

        // CLEAR FILTER FOR BALANCE
        function clearSelectionBalance() {
          $('.clearSelectionBalance, .clear-drop-balance').on('click', function (e) {
            e.preventDefault();
            $('.droupdown-body input[name="balance-radio"]').prop('checked', false);
            $("#valueItemBalance").text('');
            $(this).parents('.select-dropdown').find(".arrow-drop").show();
            $(this).parents('.select-dropdown').find(".clear-drop").hide();
            
            $.fn.dataTable.ext.search.pop();
            tableSearch.draw();
          });
        }
        clearSelectionBalance();

        // FILTER GIFT CARD VALUE
        $('.currency-input').hide();
        $('input[name="card-value"]').on('change', function () {
          $('.currency-input').hide();
          $(this).parents('.select-dropdown').find(".arrow-drop").hide();
          $(this).parents('.select-dropdown').find(".clear-drop").show();
          $(this).closest('.giftCardValue').find('.currency-input').show();

          const selectedFilter = $(this).data('filter');
          $('#valueItemFilter').text("(" + selectedFilter + ")");

          $('.card-value-price').on("input", function () {
            let enteredValue = parseFloat($(this).val());
            
            if (isNaN(enteredValue)) {
              $.fn.dataTable.ext.search.pop();
              tableSearch.draw();
              return;
            }
            
            $.fn.dataTable.ext.search.pop();
            
            $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
              const row = tableSearch.row(dataIndex).node();
              const initialValue = parseFloat($(row).find('.initial-value').text()) || 0;
              
              switch (selectedFilter) {
                case 'more':
                  return initialValue > enteredValue;
                case 'less':
                  return initialValue < enteredValue;
                case 'exact':
                  return initialValue === enteredValue;
                case 'not-exact':
                  return initialValue !== enteredValue;
                default:
                  return true;
              }
            });
            
            tableSearch.draw();
          });
        });
        
        // CLEAR FILTER FOR VALUE
        $('.clearSelectionValue, .clear-drop-value').off('click').on('click', function (e) {
          e.preventDefault();
          $(this).parents('.select-dropdown').find(".arrow-drop").show();
          $(this).parents('.select-dropdown').find(".clear-drop").hide();
          $('.card-value-price').val('');
          $('input[name="card-value"]').prop('checked', false);
          $('.currency-input').hide();
          $('#valueItemFilter').text('');
          
          $.fn.dataTable.ext.search.pop();
          tableSearch.draw();
        });

        // DATE FILTER
        $('input[name="date-method"]').on("change", function () {
          const val = $(this).data("filter");
          let getText = $(this).parent().text().trim();
          $(this).parents('.select-dropdown').find(".arrow-drop").hide();
          $(this).parents('.select-dropdown').find(".clear-drop").show();
          $('#valueItemDate').text("(" + getText + ")");
          
          if (val === 'custom') {
            $(this).parents('.droupdown-body').addClass('show-custom-date');
            $(this).parents('.droupdown-body').find('.publishing-store-date').addClass('active');
            $('td').removeClass('active');
            $("#startDate").val('');
            $("#endDate").val('');
          } else {
            $(this).parents('.droupdown-body').removeClass('show-custom-date');
            $(this).parents('.droupdown-body').find('.publishing-store-date').removeClass('active');
          }

          applyDateFilter();
        });
        
        // Custom date filter
        function applyDateFilter() {
          // Remove existing date filter
          $.fn.dataTable.ext.search.pop();
          
          const selectedFilter = $('input[name="date-method"]:checked').data("filter");
          if (!selectedFilter) return;
          
          const columnIndex = selectedFilter === "5" ? 6 : 4; // Use 6 for Date Edited, 4 for Date Created
          const today = new Date();
          
          $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
            const row = tableSearch.row(dataIndex).node();
            const dateStr = data[columnIndex];
            const tableDate = new Date(dateStr);
            
            if (isNaN(tableDate.getTime())) return false;
            
            if (selectedFilter === "custom") {
              const start = $('#startDate').val();
              const end = $('#endDate').val();

              if (start && end) {
                const startDate = new Date(start);
                const endDate = new Date(end);
                return tableDate >= startDate && tableDate <= endDate;
              } else if (start) {
                const startDate = new Date(start);
                return tableDate >= startDate;
              } else if (end) {
                const endDate = new Date(end);
                return tableDate <= endDate;
              } else {
                return true;
              }
            }

            let fromDate;
            switch (selectedFilter) {
              case "7":
              case "30":
              case "90":
                fromDate = new Date();
                fromDate.setDate(today.getDate() - parseInt(selectedFilter));
                return tableDate >= fromDate && tableDate <= today;
              case "12-month":
                fromDate = new Date();
                fromDate.setMonth(today.getMonth() - 12);
                return tableDate >= fromDate && tableDate <= today;
              case "last-month":
                fromDate = new Date(today.getFullYear(), today.getMonth() - 1, 1);
                const endLastMonth = new Date(today.getFullYear(), today.getMonth(), 0);
                return tableDate >= fromDate && tableDate <= endLastMonth;
              case "this-month":
                fromDate = new Date(today.getFullYear(), today.getMonth(), 1);
                const endThisMonth = new Date(today.getFullYear(), today.getMonth() + 1, 0);
                return tableDate >= fromDate && tableDate <= endThisMonth;
              default:
                return true;
            }
          });
          
          // Apply default sorting (descending for dates)
          tableSearch.order([columnIndex, 'desc']).draw();
        }
        
        $('#startDate, #endDate').on("change", function () {
          applyDateFilter();
        });
        
        // CLEAR DATE FILTER
        $('.clearSelectionDateTime, .clear-drop-dateTime').on('click', function (e) {
          e.preventDefault();
          $(this).parents('.select-dropdown').find(".arrow-drop").show();
          $(this).parents('.select-dropdown').find(".clear-drop").hide();
          $(this).parents('#dateCreatedDiv').hide();
          $('#dateCreated').parent().show();
          $('.droupdown-body input[name="date-method"]').prop('checked', false);
          $("#valueItemDate").text('');
          $('.publishing-store-date').removeClass('active');
          $('.droupdown-body').removeClass('show-custom-date');
          $('td').removeClass('active');
          $("#startDate").val('');
          $("#endDate").val('');
          
          $.fn.dataTable.ext.search.pop();
          tableSearch.order([4, 'desc']).draw(); // Reset to default sorting (Date Created, descending)
        });

        // CUSTOM TOP FILTERS
        $("#tabsFull").on('click', function () {
          clearAllFilters();
          $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
            const row = tableSearch.row(dataIndex).node();
            const balanceStatus = $(row).find('.current-value').attr('balance-value');
            return balanceStatus === 'full';
          });
          tableSearch.draw();
        });

        $("#tabsPartial").on('click', function () {
          clearAllFilters();
          $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
            const row = tableSearch.row(dataIndex).node();
            const balanceStatus = $(row).find('.current-value').attr('balance-value');
            return balanceStatus === 'partial';
          });
          tableSearch.draw();
        });

        $("#tabsEmpty").on('click', function () {
          clearAllFilters();
          $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
            const row = tableSearch.row(dataIndex).node();
            const balanceStatus = $(row).find('.current-value').attr('balance-value');
            return balanceStatus === 'empty';
          });
          tableSearch.draw();
        });

        $("#tabsRadeemable").on('click', function () {
          clearAllFilters();
          $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
            const row = tableSearch.row(dataIndex).node();
            const currentValue = parseFloat($(row).find('.current-value').text());
            const balanceStatus = $(row).find('.current-value').attr('balance-value');
            return currentValue > 0 || balanceStatus === 'full' || balanceStatus === 'partial';
          });
          tableSearch.draw();
        });

        $("#tabsAll").on('click', function () {
          clearAllFilters();
        });
        
        // Helper function to clear all filters
        function clearAllFilters() {
          $.fn.dataTable.ext.search.pop();
          $('.clearSelectionBalance, .clear-drop-balance').trigger('click');
          $('.clearSelectionValue, .clear-drop-value').trigger('click');
          $('.clearSelectionDateTime, .clear-drop-dateTime').trigger('click');
          tableSearch.order([4, 'desc']).draw(); // Reset to default sorting
        }

        $('#dateCreated').on('click', function () {
          $('#dateCreatedDiv').show();
          $('#dateCreated').parent().hide();
          $(this).parents('.filter-dropdown').removeClass('show');
        });

        $('#issueMethod').on('click', function () {
          $('#issueMethodDiv').show();
          $(this).parents('.filter-dropdown').removeClass('show');
        });
      } else {
        console.error('Unexpected response:', response);
      }
    },
    error: function (xhr, status, error) {
      console.error('Error fetching categories:', status, error);
    }
  });
}

// Initialize on document ready
$(document).ready(function () {
  get_giftcard_listing();
});

$('#dublicateForm').on('submit', function (e) {
  e.preventDefault(); // Prevent default form submission

  let inputValue = $('#duplicateModalInput').val().trim();
  if (inputValue === '') return; // Exit if input is empty
  const search_type = $('#search_type').val();  // Get value from hidden input

  $.ajax({
    url: ApiPlugin + '/ecommerce/product/add_filter_view', // API endpoint
    type: "POST",
    contentType: "application/json",
    dataType: "json",
    data: JSON.stringify({
      name: inputValue,  // Pass input value
      search_type: search_type // Pass search_type
    }),
    beforeSend: function (xhr) {
      xhr.setRequestHeader("Authorization", "Bearer " + strkey); // Token
      xhr.setRequestHeader("menu-uuid", menu_id); // UUID Header
      imgload.show(); // Show loader
    },
    success: function (response) {
      imgload.hide(); // Hide loader
      window.location.reload(); // Reload page on success
    },
    error: function (xhr, status, error) {
      imgload.hide(); // Hide loader on error
      console.error('Error:', xhr.responseText);
    }
  });

  // Rebind click event for dynamically added elements
  bindNavItemClick();

  // Reset the input field
  $('#duplicateModalInput').val('');
  $('#current').text('0');
});

$('table').on('click', '.btn-edit', function (e) {
  e.preventDefault();
  let page = 'edit-giftcard';
  window.location.assign('?P=' + page + '&M=' + menu_id + '&id=' + $(this).closest('tr').data("uuid"));
});
