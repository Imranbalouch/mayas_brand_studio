document.title = "Dashboard | Add Gift Card Product";
$(document).ready(function () {
  initializeSelect2();

  // fetchProductTypes();
  // fetchProductVendors();
  fetchProductCollections();
  get_active_channel();
  get_active_markets();

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

  $("#addGiftCardProduct").on("submit", function (e) {
    e.preventDefault();

    // Validation for title
    let status = true;
    if ($('input[name="name"]').val() === "") {
      $('input[name="name"]').parent().addClass('error');
      $('input[name="name"]').parent().find('.error-txt').html("Title can’t be blank");
      showToast("Title can’t be blank", 'Error', 'error');
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

    if (!status) return;

    // Collect variants
    let variants = [];
    $('input[name="denomination"]').each(function () {
      let price = parseFloat($(this).val().replace(/[^\d.]/g, ''));
      if (!isNaN(price)) {
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

    // Collect tags
    let tags = $('#tags').val() || [];
    let tagsString = Array.isArray(tags) ? tags.join(',') : tags;

    // Collect collection IDs
    let collectionIds = $('#collection').val() || [];

    // Collect selected sales channels
    let saleChannelIds = $('input[name="getChannelId[]"]:checked').map(function () {
      return $(this).val();
    }).get();
    let saleChannelIdsString = Array.isArray(saleChannelIds) ? saleChannelIds.join(',') : saleChannelIds;

    // Get published_datetime
    let published_datetime = $('[name="published_datetime"]').val() || "";

    // Prepare data for submission
    let data = {
      title: $('input[name="name"]').val(),
      description: $("textarea[name='description']").val() || "",
      short_desc: $('#short_desc').val() || "",
      type: $('#type').val() || "",
      vendor: $('#vendor').val() || "",
      status: $('select[name="status"]').val() || 1,
      tags: tagsString,
      sale_channel_id: saleChannelIdsString,
      page_title: $('#seoTitle').val() || "",
      meta_description: $('#seoDesc').val() || "",
      media: $('input[name="media"]').val() || "",
      url_handle: $('#seoLink').val() || "",
      theme_template: $('#template_product').val() || "Default Template",
      giftcard_template: $('#template_product').val() || "Default Giftcard",
      collection_id: collectionIds,
      published_date: published_datetime, // Add published_datetime to the data
      variants: variants
    };

    console.log("Submitting data:", data);

    $.ajax({
      url: ApiPlugin + "/ecommerce/giftcard-product/store",
      type: "POST",
      data: JSON.stringify(data),
      contentType: 'application/json',
      dataType: 'json',
      headers: {
        "Authorization": "Bearer " + strkey,
        'menu-uuid': menu_id
      },
      beforeSend: function () {
        imgload.show();
      },
      success: function (response) {
        imgload.hide();
        if (response.status_code == 200) {
          showToast(response.message, 'Success', 'success', "?P=product&M="+menu_id);
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

  initializeDenominationControls();
  initializeSeoControls();
});
// function fetchProductTypes() {
//   $.ajax({
//     url: ApiPlugin + "/ecommerce/product/get_product_types",
//     type: "GET",
//     headers: {
//       "Authorization": "Bearer " + strkey,
//       'menu-uuid': menu_id
//     },
//     success: function(response) {
//       if (response.status_code === 200 && response.data) {
//         let $typeSelect = $('#type');
//         $typeSelect.empty(); 
        
//         $typeSelect.append($('<option>', {
//           value: '',
//         }));
        
//         response.data.forEach(function(type) {
//           if (type.type) { 
//             $typeSelect.append($('<option>', {
//               value: type.type,
//               text: type.type
//             }));
//           }
//         });
        
//         $typeSelect.trigger('change');
//       }
//     },
//     error: function(xhr, status, error) {
//       console.error("Error fetching product types:", error);
//     }
//   });
// }

// function fetchProductVendors() {
//   $.ajax({
//     url: ApiPlugin + "/ecommerce/product/get_product_vendors",
//     type: "GET",
//     headers: {
//       "Authorization": "Bearer " + strkey,
//       'menu-uuid': menu_id
//     },
//     success: function(response) {
//       if (response.status_code === 200 && response.data) {
//         let $vendorSelect = $('#vendor');
//         $vendorSelect.empty(); 
        
//         $vendorSelect.append($('<option>', {
//           value: '',
//         }));
        
//         response.data.forEach(function(vendor) {
//           if (vendor.vendor) { 
//             $vendorSelect.append($('<option>', {
//               value: vendor.vendor,
//               text: vendor.vendor
//             }));
//           }
//         });
        
//         $vendorSelect.trigger('change');
//       }
//     },
//     error: function(xhr, status, error) {
//       console.error("Error fetching product vendors:", error);
//     }
//   });
// }
function fetchProductCollections() {
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
        $collectionSelect.append($('<option>', {
          value: '',
        }));
        response.data.forEach(function(collection) {
          if (collection.uuid && collection.name) { 
            $collectionSelect.append($('<option>', {
              value: collection.uuid,
              text: collection.name
            }));
          }
        });
        
        $collectionSelect.trigger('change');
      }
    },
    error: function(xhr, status, error) {
      console.error("Error fetching product collections:", error);
    }
  });
}

function initializeDenominationControls() {
  $("#add-denomination-btn").on("click", function() {
    const newField = `
      <div class="input-group-wrapper mb-3">
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
                let market_names = [];
                response.data.forEach(element => {
                    market_names.push(element.market_name);
                });

                let formattedMarkets = "";
                if (market_names.length > 2) {
                    formattedMarkets = market_names.slice(0, -1).join(", ") + " and " + market_names.slice(-1);
                } else if (market_names.length === 2) {
                    formattedMarkets = market_names.join(" and ");
                } else if (market_names.length === 1) {
                    formattedMarkets = market_names[0];
                } else {
                    formattedMarkets = "Not included in any markets";
                }

                $("#marketNamesBox").removeClass("d-none");
                $("#marketNames").html(`<div class="mb-3 fv-row"><span class="circleBefore">${formattedMarkets}</span></div>`);

                $("#manageMarkets .modal-body").empty();
                response.data.forEach(element => {
                    $("#manageMarkets .modal-body").append(`
                        <div class="sales-checkbox-01 disabled mb-3">
                            <div class="form-check mb-4 align-items-start">
                                <input class="form-check-input" type="checkbox" value="${element.market_name}" checked>
                                <label class="form-check-label">
                                    <span>${element.market_name}</span>
                                    <p class="my-1">${element.market_name}</p>
                                    <div class="d-flex align-items-center">
                                        <svg width="16" height="16" class="me-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16">
                                            <path d="M8 4a.75.75 0 0 1 .75.75v3.5a.75.75 0 0 1-1.5 0v-3.5a.75.75 0 0 1 .75-.75"></path>
                                            <path d="M9 11a1 1 0 1 1-2 0 1 1 0 0 1 2 0"></path>
                                            <path fill-rule="evenodd" d="M15 8a7 7 0 1 1-14 0 7 7 0 0 1 14 0m-1.5 0a5.5 5.5 0 1 1-11 0 5.5 5.5 0 1 1 11 0"></path>
                                        </svg>
                                        <p class="mb-0">To make changes to this market, you need to first save the product.</p>
                                    </div>
                                </label>
                            </div>
                        </div>
                    `);
                });
            } else {
                console.error('Unexpected response:', response);
            }
        },
        error: function (xhr, status, error) {
            console.error('Error fetching markets:', status, error);
        }
    });
}

// Get active sales channels
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
  
  // Store channel data for later use
  window.salesChannelsData = channels.map(channel => ({
    uuid: channel.uuid,
    name: channel.name,
    featured: channel.featured
  }));
  
  // Populate checkboxes
  let htmlCheckboxes = "";
  window.salesChannelsData.forEach(channel => {
    htmlCheckboxes += `
      <div class="form-check mb-4">
        <input class="form-check-input" type="checkbox" value="${channel.uuid}"
          id="channel-${channel.uuid}" name="getChannelId[]" checked>
        <label class="form-check-label" for="channel-${channel.uuid}">
          <span>${channel.name}</span>
        </label>
      </div>`;
  });
  $salesCheckboxes.html(htmlCheckboxes);
  
  // Populate channels display
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
  
  // Initialize checkbox events
  initializeChannelCheckboxes();
}

function initializeChannelCheckboxes() {
  // Update selected count
  function updateSelectedCount() {
    const checkedCount = $('#sales-checkboxs input[type="checkbox"]:checked').length;
    $('.manage-sales-select checkednumber').text(checkedCount);
    
    if (checkedCount === $('#sales-checkboxs input[type="checkbox"]').length) {
      $('.manage-sales-text').addClass('deselect').text('Deselect all');
    } else {
      $('.manage-sales-text').removeClass('deselect').text('Select all');
    }
  }
  
  // Checkbox change event
  $('.sales-checkbox input[type="checkbox"]').on('change', updateSelectedCount);
  
  // Select all/deselect all
  $('.manage-sales-text').on('click', function() {
    const $checkboxes = $('.sales-checkbox input[type="checkbox"]');
    const shouldCheckAll = !$(this).hasClass('deselect');
    
    $checkboxes.prop('checked', shouldCheckAll);
    $(this).toggleClass('deselect', shouldCheckAll)
           .text(shouldCheckAll ? 'Deselect all' : 'Select all');
    
    updateSelectedCount();
  });
  
  // Done button - update display
  $('#salesChannel').on('click', function() {
    const selectedChannels = [];
    $('#sales-checkboxs input[type="checkbox"]:checked').each(function() {
      const channelId = $(this).val();
      const channel = window.salesChannelsData.find(c => c.uuid === channelId);
      if (channel) selectedChannels.push(channel);
    });
    
    updateChannelsDisplay(selectedChannels);
  });
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
  
  // Update hidden field with selected channel IDs
  const channelIds = channels.map(c => c.uuid);
  $('#channel_uuid').val(channelIds.join(','));
}

// Function to handle SEO controls
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
}
$("#seoLink").on("input", function () {
  $(".site-link span").html($(this).val());
});

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
