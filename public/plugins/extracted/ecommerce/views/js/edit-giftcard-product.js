document.title = "Dashboard | Edit Gift Card Product";
$(document).ready(function () {
  initializeSelect2();
  
  // Get the gift card product ID from URL
  var urlParams = new URLSearchParams(window.location.search);
  var id = urlParams.get('id');
  
  // Initialize all dropdowns and then load data
  Promise.all([
    // fetchProductTypes(),
    // fetchProductVendors(), 
    fetchProductCollections()
  ]).then(() => {
    // After all dropdowns are populated, load the gift card data
    if (id) {
      loadGiftCardData(id);
    }
  });
  get_active_markets();
  get_active_channel();
  initializeDenominationControls();
  initializeSeoControls();

  // Handle the scheduling of publishing datetime
  $("#schedulePublish").on("click", function () {
    let date = $("#putdate").val();
    let time = $("#putTime").val();
    if (date && time) {
      let formattedDate = new Date(date + ' ' + time);
      let year = formattedDate.getFullYear();
      let month = ('0' + (formattedDate.getMonth() + 1)).slice(-2);
      let day = ('0' + formattedDate.getDate()).slice(-2);
      let hours = ('0' + formattedDate.getHours()).slice(-2);
      let minutes = ('0' + formattedDate.getMinutes()).slice(-2);
      let seconds = ('0' + formattedDate.getSeconds()).slice(-2);
      let published_datetime = `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
      $("[name='published_datetime']").val(published_datetime);
    }
  });

  // Edit form submit
  $("#editGiftCardProduct").on("submit", function (e) {
    e.preventDefault();

    // Validation for title
    let status = true;
    if ($('input[name="name"]').val() === "") {
        $('input[name="name"]').parent().addClass('error');
        $('input[name="name"]').parent().find('.error-txt').html("Title can't be blank");
        showToast("Title can't be blank", 'Error', 'error');
        window.scrollTo(0, 0);
        status = false;
    }

    $('input[name="name"]').on("input", function () {
        $(this).parent().removeClass("error");
        $(this).parent().find(".error-txt").html("");
    });

     // Validation for vendor
    const vendor = $('#vendor');
    if (vendor.val().length > 100) {
      vendor.parent().addClass('error');
      vendor.parent().find('.error-txt').html("Vendor can't be more than 100 characters");
      showToast("Vendor can't be more than 100 characters", 'Error', 'error');
      window.scrollTo(0, 0);
      status = false;
    }

    vendor.on("input", function(){
      $(this).parent().find(".error-txt").hide();
      if ($(this).parent().hasClass('error')) {
        $(this).parent().removeClass('error');
      }
    });

    // Validation for type
    const type = $('#type');
    if (type.val().length > 100) {
      type.parent().addClass('error');
      type.parent().find('.error-txt').html("Type can't be more than 100 characters");
      showToast("Type can't be more than 100 characters", 'Error', 'error');
      window.scrollTo(0, 0);
      status = false;
    }

    type.on("input", function(){
      $(this).parent().find(".error-txt").hide();
      if ($(this).parent().hasClass('error')) {
        $(this).parent().removeClass('error');
      }
    })

   

    // Collect variants
    let variants = [];
    $('input[name="denomination"]').each(function () {
        let price = parseFloat($(this).val().replace(/[^\d.]/g, ''));
        if (!isNaN(price)) {
           if (price > 1000000) {
              $(this).parent().addClass('error');
              $(this).parent().find('.error-txt').html("Denomination price can't be more than 1000000");
              showToast("Denomination price can't be more than 1000000", 'Error', 'error');
               status = false;
            }
            variants.push({
                price: price,
                variant: "Denominations",
                sku: "",
                qty: 0,
                image: "",
                location_id: ""
            });
        }
    });

     if (!status) return;

    // Collect tags
    let tags = $('#tags').val() || [];
    let tagsString = Array.isArray(tags) ? tags.join(',') : tags;

     console.log("Tags data:", { 
    raw: $('#tags').val(), 
    processed: tagsString 
  });

    // Collect collection IDs
    let collectionIds = $('#collection').val() || [];

    // Collect selected sales channels
    let saleChannelIds = $('input[name="getChannelId[]"]:checked').map(function () {
        return $(this).val();
    }).get();

    // Collect selected market IDs
    let marketIds = $("input[name='market_value']:checked").map(function() {
        return $(this).val();
    }).get();

    // Get published_datetime
    let published_datetime = $('[name="published_datetime"]').val() || "";

    // Prepare data for submission
    let data = {
        title: $('input[name="name"]').val(),
        description: $('#editorCk').val() || "",
        short_desc: $('#short_desc').val() || "",
        type: $('#type').val() || "",
        vendor: $('#vendor').val() || "",
        status: $('select[name="status"]').val() || 1,
        tags: tagsString,
        sale_channel_id: saleChannelIds,
        market_id: marketIds,
        page_title: $('#seoTitle').val() || "",
        meta_description: $('#seoDesc').val() || "",
        media: $('input[name="media"]').val() || "",
        url_handle: $('#seoLink').val() || "",
        theme_template: $('#theme_template').val() || "Default Template",
        giftcard_template: $('#giftcard_template').val() || "Default Giftcard",
        collection_id: collectionIds,
        published_date: published_datetime,
        variants: variants
    };

    console.log("Submitting data:", data);

    $.ajax({
        url: ApiPlugin + "/ecommerce/giftcard-product/update",
        type: "POST",
        data: JSON.stringify(data),
        contentType: 'application/json',
        dataType: 'json',
        beforeSend: function (xhr) {
            xhr.setRequestHeader("Authorization", "Bearer " + strkey);
            xhr.setRequestHeader("menu-uuid", menu_id);
            xhr.setRequestHeader("uuid", id);
            imgload.show();
        },
        success: function (response) {
            imgload.hide();
            if (response.status_code == 200) {
                showToast(response.message, 'Success', 'success', "?P=product&M=" + menu_id + "&id=" + id);
            }
        },
        error: function (xhr, status, err) {
            imgload.hide();
            console.error("Error response:", xhr.responseText);
            if (xhr.status === 422) {
                let errors = xhr.responseJSON.errors;
                let errorMessages = "";
                if (typeof errors === 'string') {
                    errorMessages = errors;
                } else {
                    $.each(errors, function (field, messages) {
                        if (Array.isArray(messages)) {
                            messages.forEach(function (message) {
                                errorMessages += `<li>${field}: ${message}</li>`;
                            });
                        } else {
                            errorMessages += `<li>${field}: ${messages}</li>`;
                        }
                    });
                }
                let htmlError = `<ul>${errorMessages}</ul>`;
                showToast(htmlError, 'Error', 'error');
            } else {
                let errorMessage = "Something went wrong!";
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response && response.message) {
                        errorMessage += ": " + response.message;
                    }
                } catch (e) {
                    if (xhr.responseText) {
                        errorMessage += ": " + xhr.responseText;
                    }
                }
                showToast(errorMessage, 'Error', 'error');
            }
        }
    }); 
});
});
$('#market_value_update').on('click', function() {
    let market_values = [];
    $("input[name='market_value']:checked").each(function() {
        market_values.push($(this).val());
    });
    
    // Update the display
    updateMarketsDisplay(
        window.marketsData.filter(market => market_values.includes(market.uuid))
    );
    
    // Also update the hidden field if you have one
    $('#market_id').val(market_values);
});
function get_active_markets() {
  $.ajax({
    url: ApiPlugin + '/ecommerce/market/get_active_markets',
    type: "GET",
    contentType: "application/json",
    dataType: "json",
    beforeSend: function (request) {
      request.setRequestHeader("Authorization", 'Bearer ' + strkey);
    },
    success: function (response) {
      if (response.status_code === 200) {
        // Store markets data globally
        window.marketsData = response.data.map(market => ({
          uuid: market.uuid,
          name: market.market_name
        }));

        // Populate the manage markets modal
        $("#manageMarkets .modal-body").empty();
        response.data.forEach(element => {
          $("#manageMarkets .modal-body").append(`
            <div class="sales-checkbox-01 mb-3">
              <div class="form-check mb-4 align-items-start">
                <label class="form-check-label">
                  <input class="form-check-input" name="market_value" type="checkbox" value="${element.uuid}">
                  <span>${element.market_name}</span>
                  <div class="d-flex align-items-center">
                    <svg width="16" height="16" class="me-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16">
                      <path d="M8 4a.75.75 0 0 1 .75.75v3.5a.75.75 0 0 1-1.5 0v-3.5a.75.75 0 0 1 .75-.75"></path>
                      <path d="M9 11a1 1 0 1 1-2 0 1 1 0 0 1 2 0"></path>
                      <path fill-rule="evenodd" d="M15 8a7 7 0 1 1-14 0 7 7 0 0 1 14 0m-1.5 0a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0"></path>
                    </svg>
                    <p class="mb-0">To make changes to this market, you need to first save the product.</p>
                  </div>
                </label>
              </div>
            </div>
          `);
        });

        // Initialize market checkboxes
        initializeMarketCheckboxes();
      } else {
        console.error('Unexpected response:', response);
      }
    },
    error: function (xhr, status, error) {
      console.error('Error fetching markets:', status, error);
    }
  });
}

function initializeMarketCheckboxes() {
  function updateSelectedCount() {
    const checkedCount = $('#manageMarkets input[name="market_value"]:checked').length;
    $('#manageMarkets .manage-sales-select .checkednumber').text(checkedCount);
    if (checkedCount === $('#manageMarkets input[name="market_value"]').length) {
      $('#manageMarkets .manage-sales-text').addClass('deselect').text('Deselect all');
    } else {
      $('#manageMarkets .manage-sales-text').removeClass('deselect').text('Select all');
    }
  }

  $('#manageMarkets input[name="market_value"]').on('change', updateSelectedCount);

  $('#manageMarkets .manage-sales-text').on('click', function() {
    const $checkboxes = $('#manageMarkets input[name="market_value"]');
    const shouldCheckAll = !$(this).hasClass('deselect');
    $checkboxes.prop('checked', shouldCheckAll);
    $(this).toggleClass('deselect', shouldCheckAll)
           .text(shouldCheckAll ? 'Deselect all' : 'Select all');
    updateSelectedCount();
  });

  $('#market_value_update').on('click', function() {
    const selectedMarkets = [];
    $('#manageMarkets input[name="market_value"]:checked').each(function() {
      const marketId = $(this).val();
      const market = window.marketsData.find(m => m.uuid === marketId);
      if (market) selectedMarkets.push(market);
    });
    updateMarketsDisplay(selectedMarkets);
    updateSelectedCount();
  });

  // Initialize count on load
  updateSelectedCount();
}
function updateMarketsDisplay(markets) {
  const $marketNames = $('#marketNames');
  let html = "";
  if (markets.length === 0) {
    html = '<div class="mb-3 fv-row"><span class="circleBefore">Not included in any markets</span></div>';
  } else {
    markets.forEach(market => {
      html += `
        <div class="mb-3 fv-row"><span class="circleBefore active">${market.name}</span></div>
      `;
    });
  }
  $marketNames.html(html);
  $('#market_id').val(markets.map(m => m.uuid).join(','));
}

let selectedDateInfo = null;

function highlightSelectedDate() {
  if (!selectedDateInfo) return;
  $('.calendar .calendar_content > div').removeClass('selected');
  const { day, month, year } = selectedDateInfo;

  $('.calendar').each(function () {
    const calendar = $(this);
    const headerText = calendar.find('.calendar_header h2').text(); // e.g., "June 2025"
    
    if (!headerText) return; 
    
    const [monthName, calendarYear] = headerText.split(' ');
    const monthIndex = new Date(Date.parse(monthName + " 1, 2025")).getMonth(); // Convert month name to index
    const yearNum = parseInt(calendarYear, 10);

    // Check if this calendar matches the selected month and year
    if (monthIndex === month && yearNum === year) {
      // Target only the .calendar_content within this calendar
      calendar.find('.calendar_content > div').each(function () {
        const divDay = parseInt($(this).text(), 10);
        if (divDay === day) {
          $(this).addClass('selected');
        }
      });
    }
  });
}

$(document).on('click', '.switch-month', function () {
  // Add a small delay to ensure the calendar has been regenerated
  setTimeout(() => {
    highlightSelectedDate();

    // Also update the #putdate field again if selectedDateInfo exists
    if (selectedDateInfo) {
      const { day, month, year } = selectedDateInfo;

      // Format date as "DD MMMM YYYY"
      const dateObj = new Date(year, month, day);
      const options = { day: '2-digit', month: 'long', year: 'numeric' };
      const formattedDate = dateObj.toLocaleDateString('en-GB', options);

      $('#putdate').val(formattedDate);
      console.log('Restored #putdate to:', formattedDate);
    }
  }, 100);
});

function loadGiftCardData(id) {
    $.ajax({
        url: ApiPlugin + "/ecommerce/giftcard-product/edit/" + id,
        type: "GET",
        dataType: "json",
        beforeSend: function (xhr) {
            xhr.setRequestHeader("Authorization", "Bearer " + strkey);
            xhr.setRequestHeader("menu-uuid", menu_id);
            imgload.show();
        },
        success: function (response) {
            imgload.hide();
            if (response.status_code == 200) {
                let data = response.data;

                // Populate form fields
                $("input[name='name']").val(data.title);
                response.data.description != null && setEditorValue(data.description);
                $("input[name='media']").val(data.media);
                $("#seoTitle").val(data.page_title);
                $("#seoDesc").val(data.meta_description);
                $("#seoLink").val(data.url_handle);
                $("select[name='status']").val(data.status);

                // Set dropdown values
                // if (data.type) {
                //     $("select[name='type']").val(data.type).trigger('change');
                // }
                // if (data.vendor) {
                //     $("select[name='vendor']").val(data.vendor).trigger('change');
                // }

                if (data.type) {
                  $("input[name='type']").val(data.type);
                }
                if (data.vendor) {
                  $("input[name='vendor']").val(data.vendor);
                }

                $("select[name='theme_template']").val(data.theme_template);
                $("select[name='giftcard_template']").val(data.giftcard_template);
                $("[name='published_datetime']").val(data.published_date);

                // Populate tags
                if (data.tags) {
                    let tagsArray = data.tags.split(',').map(tag => tag.trim());
                    tagsArray.forEach(function(tag) {
                        if ($('#tags').find("option[value='" + tag + "']").length === 0) {
                            $('#tags').append(new Option(tag, tag, false, false));
                        }
                    });
                    $('#tags').val(tagsArray).trigger('change');
                }

                // Populate collections
                if (data.collections) {
                    let collectionArray = Array.isArray(data.collections)
                        ? data.collections
                        : data.collections.split(',').map(col => col.trim());
                    $('#collection').val(collectionArray).trigger('change');
                }

                // Populate sales channels
                if (data.sale_channel_id) {
                    let channelArray = data.sale_channel_id.split(',').map(ch => ch.trim());
                    channelArray.forEach(function(channelId) {
                        $(`#sales-checkboxs input[value="${channelId}"]`).prop('checked', true);
                    });
                    updateChannelsDisplay(window.salesChannelsData.filter(channel => channelArray.includes(channel.uuid)));
                    
                    // Add this line to update the count after loading data
                    setTimeout(() => {
                        $('#manageSalesChannels .manage-sales-select checkednumber').text(channelArray.length);
                    }, 100);
                }

                // Populate markets
                if (data.market_id) {
                  let marketArray = data.market_id.split(',').map(m => m.trim());
                  marketArray.forEach(function(marketId) {
                    $(`#manageMarkets input[value="${marketId}"]`).prop('checked', true);
                  });

                  if (window.marketsData) {
                    let selectedMarkets = window.marketsData.filter(market => marketArray.includes(market.uuid));
                    updateMarketsDisplay(selectedMarkets);
                  }
                  $('#market_id').val(marketArray.join(','));
                }

                if (response.data.published_date) {
                      const publishedDateTime = new Date(response.data.published_date);
                      console.log("date" + publishedDateTime);

                      if (!isNaN(publishedDateTime)) {
                        // Format date as "DD MMMM YYYY"
                        const options = { day: '2-digit', month: 'long', year: 'numeric' };
                        const formattedDate = publishedDateTime.toLocaleDateString('en-GB', options);
                        console.log(formattedDate);

                        // Format time as HH:MM AM/PM
                        const hours = publishedDateTime.getHours();
                        const minutes = ('0' + publishedDateTime.getMinutes()).slice(-2);
                        const ampm = hours >= 12 ? 'PM' : 'AM';
                        const formattedHours = hours % 12 || 12;
                        const time = `${formattedHours}:${minutes} ${ampm}`;

                        console.log(time);
                        console.log(formattedDate);
                        
                        if ($('#putdate').length && $('#putTime').length) {
                          console.log('Elements found, setting values:', formattedDate, time);
                          
                          // For the date input
                          setTimeout(() => {
                            $('#putdate').val(formattedDate);
                            console.log('Date input value after set:', $('#putdate').val());
                          }, 500);
                          
                          const timeWithLeadingZero = time.replace(/(\d+):/, (match, p1) => 
                            p1.length === 1 ? `0${p1}:` : match);
                          
                          console.log('Looking for time option:', timeWithLeadingZero);
                          
                          // Set the value and trigger change
                          $('#putTime').val(timeWithLeadingZero).trigger('change');
                          console.log('Time select value after set:', $('#putTime').val());
                        } else {
                          console.error('Could not find one or both elements: #putdate, #putTime');
                        }

                        // Store the selected date information globally
                        selectedDateInfo = {
                          day: publishedDateTime.getDate(),
                          month: publishedDateTime.getMonth(), // 0-based (e.g., 5 for June)
                          year: publishedDateTime.getFullYear()
                        };

                        // Highlight the selected date
                        highlightSelectedDate();
                        
                      } else {
                        console.warn('Invalid published_date format:', response.data.published_date);
                      }
                    } else {
                      console.warn('No published_date provided in response');
                    }

                // Populate denominations
                if (data.variants && Array.isArray(data.variants)) {
                    $("#denomination-container").empty();
                    data.variants.forEach(function (variant) {
                        let denominationField = `
                            <div class="input-group-wrapper mb-3 d-flex align-items-center">
                                <input type="text" class="form-control" name="denomination" value="${variant.price}" placeholder="RS 0.00"
                                    aria-label="Denomination value" aria-describedby="button-addon2">
                                <button class="btn btn-secondary delete-btn ms-5" type="button">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" width="16" height="16">
                                        <path d="M9.5 6.25a.75.75 0 0 1 .75.75v4.25a.75.75 0 0 1-1.5 0v-4.25a.75.75 0 0 1 .75-.75"></path>
                                        <path d="M7.25 7a.75.75 0 0 0-1.5 0v4.25a.75.75 0 0 0 1.5 0z"></path>
                                        <path fill-rule="evenodd"
                                        d="M5.25 3.25a2.75 2.75 0 1 1 5.5 0h3a.75.75 0 0 1 0 1.5h-.75v5.45c0 1.68 0 2.52-.327 3.162a3 3 0 0 1-1.311 1.311c-.642.327-1.482.327-3.162.327h-.4c-1.68 0-2.52 0-3.162-.327a3 3 0 0 1-1.311-1.311c-.327-.642-.327-1.482-.327-3.162v-5.45h-.75a.75.75 0 0 1 0-1.5zm1.5 0a1.25 1.25 0 0 1 2.5 0zm-2.25 1.5h7v5.45c0 .865-.001 1.423-.036 1.848-.033.408-.09.559-.128.633a1.5 1.5 0 0 1-.655.655c-.074.038-.225.095-.633.128-.425.035-.983.036-1.848.036h-.4c-.865 0-1.423-.001-1.848-.036-.408-.033-.559-.09-.633-.128a1.5 1.5 0 0 1-.656-.655c-.037-.074-.094-.225-.127-.633-.035-.425-.036-.983-.036-1.848z">
                                        </path>
                                    </svg>
                                 </button>
                             </div>
                         `;
                         $("#denomination-container").append(denominationField);
                     });
                 }

                 // Update SEO preview
                 $("#seoTitleCount").text(data.page_title ? data.page_title.length : 0);
                 $("#seoDescCount").text(data.meta_description ? data.meta_description.length : 0);
                 if (data.page_title) {
                     $(".seo-prev .site-title").html(data.page_title);
                     $(".seo-prev").show();
                     $("#seoMainDesc").hide();
                 }
                 if (data.meta_description) {
                     $(".seo-prev .site-desc").html(data.meta_description);
                 }
                 if (data.url_handle) {
                     $(".site-link span").html(data.url_handle);
                 }

                 filemanagerImagepreview();
             }
         },
        error: function (xhr, status, err) {
            imgload.hide();
            if (xhr.status === 404) {
                showToast(xhr.responseJSON.message, 'Error', 'error');
            } else {
                showToast('Internal Server Error.', 'Error', 'error');
            }
        }
    });
}

// function fetchProductTypes() {
//   return new Promise((resolve, reject) => {
//     $.ajax({
//       url: ApiPlugin + "/ecommerce/product/get_product_types",
//       type: "GET",
//       headers: {
//         "Authorization": "Bearer " + strkey,
//         'menu-uuid': menu_id
//       },
//       success: function(response) {
//         if (response.status_code === 200 && response.data) {
//           let $typeSelect = $('#type');
//           $typeSelect.empty();
//           $typeSelect.append($('<option>', { value: '' }));
//           response.data.forEach(function(type) {
//             if (type.type) {
//               $typeSelect.append($('<option>', {
//                 value: type.type,
//                 text: type.type
//               }));
//             }
//           });
//           resolve();
//         } else {
//           reject('Failed to fetch product types');
//         }
//       },
//       error: function(xhr, status, error) {
//         console.error("Error fetching product types:", error);
//         reject(error);
//       }
//     });
//   });
// }

// function fetchProductVendors() {
//   return new Promise((resolve, reject) => {
//     $.ajax({
//       url: ApiPlugin + "/ecommerce/product/get_product_vendors",
//       type: "GET",
//       headers: {
//         "Authorization": "Bearer " + strkey,
//         'menu-uuid': menu_id
//       },
//       success: function(response) {
//         if (response.status_code === 200 && response.data) {
//           let $vendorSelect = $('#vendor');
//           $vendorSelect.empty();
//           $vendorSelect.append($('<option>', { value: '' }));
//           response.data.forEach(function(vendor) {
//             if (vendor.vendor) {
//               $vendorSelect.append($('<option>', {
//                 value: vendor.vendor,
//                 text: vendor.vendor
//               }));
//             }
//           });
//           resolve();
//         } else {
//           reject('Failed to fetch product vendors');
//         }
//       },
//       error: function(xhr, status, error) {
//         console.error("Error fetching product vendors:", error);
//         reject(error);
//       }
//     });
//   });
// }

function fetchProductCollections() {
  return new Promise((resolve, reject) => {
    $.ajax({
      url: ApiPlugin + "/ecommerce/product/get_product_collections",
      type: "GET",
      headers: {
        "Authorization": "Bearer " + strkey,
        'menu-uuid': menu_id
      },
      success: function(response) {
        if (response.status_code === 200 && response.data) {
          let $collectionSelect = $('#collection');
          $collectionSelect.empty();
          $collectionSelect.append($('<option>', { value: '' }));
          response.data.forEach(function(collection) {
            if (collection.uuid && collection.name) {
              $collectionSelect.append($('<option>', {
                value: collection.uuid,
                text: collection.name
              }));
            }
          });
          resolve();
        } else {
          reject('Failed to fetch product collections');
        }
      },
      error: function(xhr, status, error) {
        console.error("Error fetching product collections:", error);
        reject(error);
      }
    });
  });
}

function initializeDenominationControls() {
  $("#add-denomination-btn").on("click", function() {
    const newField = `
      <div class="input-group-wrapper mb-3 d-flex align-items-center">
        <input type="text" class="form-control" name="denomination" placeholder="RS 0.00"
          aria-label="Denomination value" aria-describedby="button-addon2">
        <button class="btn btn-secondary delete-btn ms-5" type="button">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" width="16" height="16">
            <path d="M9.5 6.25a.75.75 0 0 1 .75.75v4.25a.75.75 0 0 1-1.5 0v-4.25a.75.75 0 0 1 .75-.75"></path>
            <path d="M7.25 7a.75.75 0 0 0-1.5 0v4.25a.75.75 0 0 0 1.5 0z"></path>
            <path fill-rule="evenodd"
              d="M5.25 3.25a2.75 2.75 0 1 1 5.5 0h3a.75.75 0 0 1 0 1.5h-.75v5.45c0 1.68 0 2.52-.327 3.162a3 3 0 0 1-1.311 1.311c-.642.327-1.482.327-3.162.327h-.4c-1.68 0-2.52 0-3.162-.327a3 3 0 0 1-1.311-1.311c-.327-.642-.327-1.482-.327-3.162v-5.45h-.75a.75.75 0 0 1 0-1.5zm1.5 0a1.25 1.25 0 0 1 2.5 0zm-2.25 1.5h7v5.45c0 .865-.001 1.423-.036 1.848-.033.408-.09.559-.128.633a1.5 1.5 0 0 1-.655.655c-.074.038-.225.095-.633.128-.425.035-.983.036-1.848.036h-.4c-.865 0-1.423-.001-1.848-.036-.408-.033-.559-.09-.633-.128a1.5 1.5 0 0 1-.656-.655c-.037-.074-.094-.225-.127-.633-.035-.425-.036-.983-.036-1.848z">
            </path>
          </svg>
        </button>
      </div>
    `;
    $("#denomination-container").append(newField);
  });

  $(document).on("click", ".delete-btn", function() {
    if ($("#denomination-container .input-group-wrapper").length > 1) {
      $(this).closest(".input-group-wrapper").remove();
    } else {
      showToast("You need at least one denomination value", 'Warning', 'warning');
    }
  });
}

function get_active_channel() {
  $.ajax({
    url: ApiPlugin + '/ecommerce/channel/get_active_channels',
    type: "GET",
    contentType: "application/json",
    dataType: "json",
    beforeSend: function(request) {
      request.setRequestHeader("Authorization", 'Bearer ' + strkey);
    },
    success: function(response) {
      if (response.status_code === 200) {
        populateSalesChannels(response.data);
      } else {
        console.error('Unexpected response:', response);
      }
    },
    error: function(xhr, status, error) {
      console.error('Error fetching channels:', status, error);
    }
  });
}

function populateSalesChannels(channels) {
  const $salesCheckboxes = $('#sales-checkboxs');
  const $salesChannels = $('#sales-channels');
  $salesCheckboxes.empty();
  $salesChannels.empty();

  window.salesChannelsData = channels.map(channel => ({
    uuid: channel.uuid,
    name: channel.name,
    featured: channel.featured
  }));

  let htmlCheckboxes = "";
  window.salesChannelsData.forEach(channel => {
    htmlCheckboxes += `
      <div class="form-check mb-4">
        <input class="form-check-input" type="checkbox" value="${channel.uuid}"
          id="channel-${channel.uuid}" name="getChannelId[]">
        <label class="form-check-label" for="channel-${channel.uuid}">
          <span>${channel.name}</span>
        </label>
      </div>`;
  });
  $salesCheckboxes.html(htmlCheckboxes);

  let htmlChannels = "";
  window.salesChannelsData.forEach(channel => {
    if (channel.name === "Online Store") {
      htmlChannels += `
        <div class="col-12 mb-1">
          <div class="d-flex justify-content-between">
            <span class="circleBefore">${channel.name}</span>
            <svg data-bs-toggle="modal" data-bs-target="#manageCalender" class="custom-svg" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16">
              <path fill-rule="evenodd" d="M4 .25a.75.75 0 0 1 .75.75v.528c.487-.028 1.07-.028 1.768-.028h2.482v-.5a.75.75 0 0 1 1.5 0v.604a3.04 3.04 0 0 1 2.25 2.936c0 .835-.678 1.513-1.513 1.513h-8.733c-.004.288-.004.617-.004.997v2.468c0 1.233 1 2.232 2.232 2.232a.75.75 0 0 1 0 1.5 3.73 3.73 0 0 1-3.732-3.732v-2.5c0-.813 0-1.469.043-2 .045-.546.14-1.026.366-1.47a3.75 3.75 0 0 1 1.841-1.733v-.815a.75.75 0 0 1 .75-.75m7.237 4.303h-8.61c.033-.13.072-.234.118-.324a2.25 2.25 0 0 1 .984-.984c.197-.1.458-.17.912-.207.462-.037 1.057-.038 1.909-.038h3.16c.85 0 1.54.69 1.54 1.54v.005l-.004.004-.004.003z"></path>
              <path d="M12.25 10a.75.75 0 0 0-1.5 0v1.293c0 .331.132.65.366.884l.854.853a.749.749 0 1 0 1.06-1.06l-.78-.78z"></path>
              <path fill-rule="evenodd" d="M11.5 16a4.5 4.5 0 1 0 0-9 4.5 4.5 0 0 0 0 9m0-1.5a3 3 0 1 0 0-6 3 3 0 0 0 0 6"></path>
            </svg>
          </div>
        </div>`;
    } else if (channel.name === "Point of Sale") {
      htmlChannels += `
        <div class="col-12 mb-1">
          <span class="circleBefore pos">${channel.name}</span>
          <p class="m-0">Point of Sale has not been set up. Finish the remaining steps to start selling in person.</p>
          <a href="#" class="pt-0 text-decoration-underline">Learn more</a>
        </div>`;
    }
  });
  $salesChannels.html(htmlChannels);

  initializeChannelCheckboxes();
}

function initializeChannelCheckboxes() {
  function updateSelectedCount() {
    const checkedCount = $('#sales-checkboxs input[type="checkbox"]:checked').length;
    // Fixed: Use the correct selector for the modal
    $('#manageSalesChannels .manage-sales-select checkednumber').text(checkedCount);
    
    // Also update the selector for the text toggle
    if (checkedCount === $('#sales-checkboxs input[type="checkbox"]').length) {
      $('#manageSalesChannels .manage-sales-text').addClass('deselect').text('Deselect all');
    } else {
      $('#manageSalesChannels .manage-sales-text').removeClass('deselect').text('Select all');
    }
  }

  // Fixed: Use the correct selector for the checkboxes
  $('#sales-checkboxs input[type="checkbox"]').on('change', updateSelectedCount);

  // Fixed: Use the correct selector for the select all text
  $('#manageSalesChannels .manage-sales-text').on('click', function() {
    const $checkboxes = $('#sales-checkboxs input[type="checkbox"]');
    const shouldCheckAll = !$(this).hasClass('deselect');
    $checkboxes.prop('checked', shouldCheckAll);
    $(this).toggleClass('deselect', shouldCheckAll)
           .text(shouldCheckAll ? 'Deselect all' : 'Select all');
    updateSelectedCount();
  });

  $('#salesChannel').on('click', function() {
    const selectedChannels = [];
    $('#sales-checkboxs input[type="checkbox"]:checked').each(function() {
      const channelId = $(this).val();
      const channel = window.salesChannelsData.find(c => c.uuid === channelId);
      if (channel) selectedChannels.push(channel);
    });
    updateChannelsDisplay(selectedChannels);
    updateSelectedCount(); // Call this to update the count after selection
  });
  
  // Initialize the count on first load
  updateSelectedCount();
}

function updateChannelsDisplay(channels) {
  const $salesChannels = $('#sales-channels');
  let html = "";
  if (channels.length === 0) {
    html = "Not included in any sales channels";
  } else {
    channels.forEach(channel => {
      if (channel.name === "Online Store") {
        html += `
          <div class="col-12 mb-1">
            <div class="d-flex justify-content-between">
              <span class="circleBefore">${channel.name}</span>
              <svg data-bs-toggle="modal" data-bs-target="#manageCalender" class="custom-svg" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16">
                <path fill-rule="evenodd" d="M4 .25a.75.75 0 0 1 .75.75v.528c.487-.028 1.07-.028 1.768-.028h2.482v-.5a.75.75 0 0 1 1.5 0v.604a3.04 3.04 0 0 1 2.25 2.936c0 .835-.678 1.513-1.513 1.513h-8.733c-.004.288-.004.617-.004.997v2.468c0 1.233 1 2.232 2.232 2.232a.75.75 0 0 1 0 1.5 3.73 3.73 0 0 1-3.732-3.732v-2.5c0-.813 0-1.469.043-2 .045-.546.14-1.026.366-1.47a3.75 3.75 0 0 1 1.841-1.733v-.815a.75.75 0 0 1 .75-.75m7.237 4.303h-8.61c.033-.13.072-.234.118-.324a2.25 2.25 0 0 1 .984-.984c.197-.1.458-.17.912-.207.462-.037 1.057-.038 1.909-.038h3.16c.85 0 1.54.69 1.54 1.54v.005l-.004.004-.004.003z"></path>
                <path d="M12.25 10a.75.75 0 0 0-1.5 0v1.293c0 .331.132.65.366.884l.854.853a.749.749 0 1 0 1.06-1.06l-.78-.78z"></path>
                <path fill-rule="evenodd" d="M11.5 16a4.5 4.5 0 1 0 0-9 4.5 4.5 0 0 0 0 9m0-1.5a3 3 0 1 0 0-6 3 3 0 0 0 0 6"></path>
              </svg>
            </div>
          </div>`;
      } else if (channel.name === "Point of Sale") {
        html += `
          <div class="col-12 mb-1">
            <span class="circleBefore pos">${channel.name}</span>
            <p class="m-0">Point of Sale has not been set up. Finish the remaining steps to start selling in person.</p>
            <a href="#" class="pt-0 text-decoration-underline">Learn more</a>
          </div>`;
      }
    });
  }
  $salesChannels.html(html);
  $('#channel_uuid').val(channels.map(c => c.uuid).join(','));
}

$(document).on('click', '#delete-giftcard-product', function(e) {
  e.preventDefault();
  
  const urlParams = new URLSearchParams(window.location.search);
  const productID = urlParams.get('id'); 

  if (!productID) {
    showToast('Product ID not found', 'Error', 'error');
    return;
  }

  Swal.fire({
    title: getTranslation('deleteConfirmMsg'),
    icon: 'warning',
    showCancelButton: true,
    cancelButtonText: getTranslation('cancel'),
    confirmButtonColor: '#15508A',
    cancelButtonColor: '#15508A',
    confirmButtonText: getTranslation('delete'),
    reverseButtons: true,
    showClass: {
      popup: 'animated fadeInDown faster'
    },
    hideClass: {
      popup: 'animated fadeOutUp faster'
    }
  }).then((result) => {
    if (result.value) {
      $.ajax({
        url: ApiPlugin + `/ecommerce/giftcard-product/delete`,
        type: 'DELETE',
        headers: {
          'Authorization': 'Bearer ' + strkey,
          'menu-uuid': menu_id,
          'uuid': productID
        },
        beforeSend: function() {
          imgload.show(); 
        },
        success: function(response) {
          imgload.hide();
          if (response.status_code === 200) {
            showToast(response.message, 'Success', 'success');
            setTimeout(() => {
              let page = 'product';
              window.location.assign('?P=' + page + '&M=' + menu_id);
            }, 1500);
          } else {
            let errorMsg = 'Failed to delete product';
            if (response.message) {
              errorMsg = response.message;
            }
            showToast(errorMsg, 'Error', 'error');
          }
        },
        error: function(xhr) {
          imgload.hide();
          let errorMsg = 'Failed to delete product';
          if (xhr.responseJSON && xhr.responseJSON.message) {
            errorMsg = xhr.responseJSON.message;
          }
          showToast(errorMsg, 'Error', 'error');
        }
      });
    }
  });
});
function initializeSeoControls() {
  $(".seoBtn").on("click", function(e) {
    e.preventDefault();
    $(this).hide();
    $("#seoTab").show();
  });

  $("#seoTitle").on("input", function() {
    $("#seoTitleCount").text($(this).val().length);
    if ($(this).val().length === 0) {
      $(".seo-prev .site-title").html("Title");
      $(".seo-prev").hide();
      $("#seoMainDesc").show();
    } else {
      $(".seo-prev .site-title").html($(this).val());
      $(".seo-prev").show();
      $("#seoMainDesc").hide();
    }
  });

  $("#seoDesc").on("input", function() {
    $("#seoDescCount").text($(this).val().length);
    if ($(this).val().length === 0) {
      $(".seo-prev .site-desc").html("Desc");
    } else {
      $(".seo-prev .site-desc").html($(this).val());
    }
  });

  $("#seoLink").on("input", function () {
    $(".site-link span").html($(this).val());
  });
}

function initializeSelect2() {
  $('#collection').select2({
    collection: true,
    allowClear: true,
    tokenSeparators: [',', ' '],
  });

  $('#tags').select2({
    tags: true,
    allowClear: true,
    tokenSeparators: [',', ' '],
  });

  // $('#type').select2();
  // $('#vendor').select2();
}