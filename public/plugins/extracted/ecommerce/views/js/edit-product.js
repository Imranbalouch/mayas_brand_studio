document.title = "Dashboard | Edit Product";
var urlParams = new URLSearchParams(window.location.search);
const baseUrl = window.location.origin;
var productID = urlParams.get('id');
var all_active_locations=[];
let all_active_markets = []; 
var saleChannelArr=[];
function get_view_base_url(){
  let urlWithoutQuery = window.location.href.split('?')[0];  
  if (urlWithoutQuery.match(/\/[^\/]*\.[^\/]*$/)) {
    urlWithoutQuery = urlWithoutQuery.substring(0, urlWithoutQuery.lastIndexOf('/') + 1);
  } 
  return urlWithoutQuery;
}

$("#seoLink").on("input", function () { 
  const value = $(this).val(); 
  $(".slug").html(value);
});

$("#seoTitle").on("input", function () {
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
$("#seoDesc").on("input", function () {
  $("#seoDescCount").text($(this).val().length);

  if ($(this).val().length === 0) {
    $(".seo-prev .site-desc").html("Desc");
  } else {
    $(".seo-prev .site-desc").html($(this).val());
  }

});
$(".productTitle").on("input", function () {
  $("#productTitleCount").text($(this).val().length);
});
$("#Price").on("change", function () {
  //console.log($(this).val());
  $(".seo_price").html("AED "+$(this).val());
});

$(document).ready(function () {
  imgload.show();
  get_locations();
  try{ 
  get_active_categories();
  }catch(error) {
    console.error('Error loading categories:', error);
  }
  get_active_countries(); 
  get_active_sales_channels();
  get_active_markets();
  // get_active_tags();
  get_active_types();
  get_active_vendors();
  get_active_collections();
  setTimeout(function () { 
   get_recordByID(productID);     
  }, 3000);
  $(".view-base-url").html(get_view_base_url());
});

 initializeSelect2();
 if ($('#tax').is(':checked')) {
    $('.vat-dropdown').show();
  }
  loadVatRates();
  loadDiscountRates();
$(document).on('change', '#v_locations', async function () {
  const locationId = $(this).val(); 
  console.log('Triggered locationId:', locationId); 
    makeVariationsTempArray(); 
    updateTableValues(variantsEditData);
}); 

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




// Function to load VAT rates from API
function loadVatRates() {
  return new Promise((resolve, reject) => {
    $.ajax({
      url: ApiPlugin + '/ecommerce/get_active_vat',
      type: "GET",
      contentType: "application/json",
      dataType: "json",
      beforeSend: function (xhr) {
        xhr.setRequestHeader("Authorization", "Bearer " + strkey);
        xhr.setRequestHeader("menu-uuid", menu_id);
      },
      success: function (response) {
        const vatDropdown = $('#vat_id');
        vatDropdown.empty();
        vatDropdown.append('<option value="">Select VAT Rate</option>');

        if (response.status_code === 200 && response.data && response.data.length > 0) {
          response.data.forEach(vat => {
            vatDropdown.append(
              `<option value="${vat.uuid}">${vat.name} (${vat.rate}%)</option>`
            );
          });

          // Initialize or reinitialize Select2
          vatDropdown.select2({
            placeholder: "Select VAT Rate",
            allowClear: true,
            width: '100%'
          });

          resolve(response); // Resolve the promise
        } 
      },
      error: function (xhr, status, error) {
        console.error('Error fetching VAT rates:', status, error);
        const vatDropdown = $('#vat_id');
        vatDropdown.empty();
        vatDropdown.append('<option value="">Error Loading VAT Rates</option>');
        vatDropdown.select2({
          placeholder: "Error Loading VAT Rates",
          allowClear: true,
          width: '100%'
        });
        showToast('Failed to load VAT rates', 'Error', 'error');
        reject(error); // Reject the promise on error
      }
    });
  });
}

function loadDiscountRates() {
  return new Promise((resolve, reject) => {
    $.ajax({
      url: ApiPlugin + '/ecommerce/get_active_discounts',
      type: "GET",
      contentType: "application/json",
      dataType: "json",
      beforeSend: function (xhr) {
        xhr.setRequestHeader("Authorization", "Bearer " + strkey);
        xhr.setRequestHeader("menu-uuid", menu_id);
      },
      success: function (response) {
        const discountDropdown = $('#discount_id');
        discountDropdown.empty();
        discountDropdown.append('<option value="">Select Discount Rate</option>');

        if (response.status_code === 200 && response.data && response.data.length > 0) {
          response.data.forEach(discount => {
            discountDropdown.append(
              `<option value="${discount.uuid}">${discount.name} (${discount.value}%)</option>`
            );
          });

          // Initialize or reinitialize Select2
          discountDropdown.select2({
            placeholder: "Select Discount Rate",
            allowClear: true,
            width: '100%'
          });

          resolve(response); // Resolve the promise
        } 
      },
      error: function (xhr, status, error) {
        console.error('Error fetching Discount rates:', status, error);
        const discountDropdown = $('#discount_id');
        discountDropdown.empty();
        discountDropdown.append('<option value="">Error Loading Discount Rates</option>');
        discountDropdown.select2({
          placeholder: "Error Loading Discount Rates",
          allowClear: true,
          width: '100%'
        });
        showToast('Failed to load Discount rates', 'Error', 'error');
        reject(error); // Reject the promise on error
      }
    });
  });
}


function get_recordByID(productID){ 
  $.ajax({
    url: ApiPlugin + "/ecommerce/product/edit_product/" + productID,
    type: "get",
    contentType: false,
    processData: false,
    beforeSend: function (xhr) {
      xhr.setRequestHeader("Authorization", "Bearer " + strkey);
      xhr.setRequestHeader("menu-uuid", menu_id);
      imgload.show();
    },
    success: async function (response) {
      imgload.hide();
            if (response.status_code == 200) {
              let title = response.message; 
              let data = response.data; 
              if (response.data.published_date_time) {
                      const publishedDateTime = new Date(response.data.published_date_time);
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
                        console.warn('Invalid published_date_time format:', response.data.published_date_time);
                      }
                    } else {
                      console.warn('No published_date_time provided in response');
                    }

                    const warehouse_location_ids = data.varient_market_location !== null && data.varient_market_location.split(",");
         
                    if (response.data.choice_options !== "" || response.data.choice_options !== "null") {
                        const variantsEditData = JSON.stringify(response.data.stocks);
                        const variantsEditDataCards = JSON.parse(response.data.choice_options); 
                        if (Array.isArray(variantsEditDataCards) && variantsEditDataCards.length > 0) { 
                          $("#price_section").hide();
                          $("#inventory_section").hide(); 
                         $("#shipping_section").hide();
                        } else { 
                          $("#price_section").show();
                          $("#inventory_section").hide();
                          $("#shipping_section").show();
                        }
                        editVarientCard(variantsEditDataCards,variantsEditData); 
                        editVarientTableData(variantsEditData); 
                      // get_active_categories(data);
                     }
                      var selected_categories = [];
                      $.each(data.category_id, function (key, value) {
                          selected_categories.push(value);// += "'" + value + "',";
                          //console.log(key);
                      });
                      $('#category_id').val(selected_categories);
                      $('#category_id').selectpicker("refresh"); 
                      $('#simple_country_id').val(data.country_id);
                      //console.log(data.country_id);
                      $('#simple_country_id').selectpicker("refresh");


                      // console.log(selected_categories);
                      $("#name").val(response.data.name).trigger('input');
                      $("input[name='images']").val(response.data.images);
                      filemanagerImagepreview();
                      //$("input[name='images']").trigger("input");
                      response.data.description != null && setEditorValue(response.data.description);
                      $("input[name='unit_price']").val(parseFloat(response.data.unit_price).toFixed(2)).trigger("change");
                      $("input[name='compare_price']").val(parseFloat(response.data.compare_price).toFixed(2));
                      $("input[name='cost_per_item']").val(response.data.cost_per_item !== null ? parseFloat(response.data.cost_per_item).toFixed(2) : "0.00");
                      //$("input[name='profit_price']").val(response.data.profit_price);
                      profit_price=0;
                      if (response.data.cost_per_item !== null && response.data.cost_per_item !== "") {
                        // console.log(response.data.margin_price !== "");
                        profit_price=(response.data.unit_price-response.data.cost_per_item);

                        margin_price= profit_price / response.data.unit_price * 100;
                        if (margin_price) { 
                          $("input[name='profit_price']").val(profit_price.toFixed(2));
                          $("input[name='margin_price']").val(margin_price.toFixed(1) + "%");
                        } else {
                          $("input[name='profit_price']").val(profit_price.toFixed(2));
                          $("input[name='margin_price']").val(margin_price.toFixed(1) + "%");
                        }
                      }

                      
                    
                      response.data.sku_barcode_enabled == "1" && $('.sku_barcode_enabled').prop('checked', true);
                      $("input[name='simple_sku']").val(response.data.sku);
                      $("input[name='simple_barcode']").val(response.data.barcode);
                      $("#sipmle_shipping_weight").val(parseFloat(response.data.weight).toFixed(2));
                      $('select[name="sipmle_shipping_weight_unit"]').selectpicker('destroy');
                      $("select[name='sipmle_shipping_weight_unit']").val(response.data.unit);
                      $("select[name='sipmle_shipping_weight_unit']").selectpicker();
                     // $('#sipmle_shipping_weight_unit').selectpicker("refresh");
                      $("select[name='country_id']").val(response.data.country_id);
                      $('#simple_hscode').val(response.data.hs_code);
                       

                       $("#status").selectpicker('destroy');
                       $("#status").val(response.data.status); 
                       $("#status").selectpicker();


                     //  $("#type").selectpicker('destroy');
                       $("#type").val(response.data.type); 
                      // $("#type").selectpicker();

                       //$("#vendor").selectpicker('destroy');
                       $("#vendor").val(response.data.vendor); 
                       //$("#vendor").selectpicker();
                    //  $("select[name='collection']").val(response.data.type).trigger('change');
                      // $("#tags").selectpicker('destroy');
                      // $("#tags").val(response.data.tags);
                      // $("#tags").selectpicker(); 
                      //console.log(JSON.parse(data.tags));
                      if (data.tags) {
                      let tagsArray = data.tags.split(',').map(tag => tag.trim());
                      tagsArray.forEach(function(tag) {
                          if ($('#tags').find("option[value='" + tag + "']").length === 0) {
                              $('#tags').append(new Option(tag, tag, false, false));
                          }
                      });
                      $('#tags').val(tagsArray).trigger('change');
                  }

                      if (data.collection_id) {
                        let collectionArray = Array.isArray(data.collection_id)
                          ? data.collection_id
                          : data.collection_id.split(',').map(col => col.trim());
                        collectionArray.forEach(function(collection) {
                          if ($('#collection').find(`option[value="${collection}"]`).length === 0) {
                            $('#collection').append(new Option(collection, collection, false, false));
                          }
                        });
                        $('#collection').val(collectionArray).trigger('change');
                      }

                      if (response.data) {
                        loadVatRates().then(() => {
                          if (response.data.vat_id) {
                            $('#vat_id').val(response.data.vat_id).trigger('change');
                          }
                        });
                      }

                      if (response.data && response.data.product_discount) {
                        loadDiscountRates().then(() => {
                          if (response.data.product_discount.di_id) {
                            $('#discount_id').val(response.data.product_discount.di_id).trigger('change');
                          }
                        });
                      } else {
                        // If no discount exists, ensure it's cleared
                        $('#discount_id').val('').trigger('change');
                      }

                      $('#putdate').val(response.data.publishingDate);
                      $('#putdate').val(response.data.publishingTime);
                      //$(".site-name").text(response.data.vendor);
                      $(".site-title").text(response.data.meta_title).trigger('input');
                      $(".slug").text(response.data.slug);
                      $(".site-desc").text(response.data.meta_description).trigger('input');
                     // $(".seo_price").text(`AED ${response.data.current_stock.toFixed(2)}`);
                      $("input[name='meta_title']").val(response.data.meta_title);
                      $("textarea[name='meta_description']").val(response.data.meta_description);
                      $("input[name='slug']").val(response.data.slug);

                      response.data.tax_enabled == "1" && $('.tax_enabled').prop('checked', true);
                      response.data.inventory_track_enabled == "1" && $('.inventory_track_enabled').prop('checked', true);
                      response.data.selling_stock_enabled == "1" && $('.selling_stock_enabled').prop('checked', true);
                      response.data.physical_product_enabled== "1" && $('.physical_product_enabled').prop('checked', true);

                      let saleChannelArr = response.data.sale_channel_id; 
                      saleChannelArr.forEach(uuid => { 
                        const $checkbox = $(`input[type="checkbox"][value="${uuid}"]`);
                        if ($checkbox.length) {
                          $checkbox.prop('checked', true);
                          $checkbox.trigger('change');
                          $('#sale_channel_id').append(`<option value="${uuid}" selected>${uuid}</option>`);
                        }
                      });
                      
                      let marketArr = response.data.market_id || [];
        $("#marketNames").empty();
        if (marketArr.length > 0) {
          $("#marketNamesBox").removeClass("d-none");
          marketArr.forEach(uuid => {
            // Find the market name from all_active_markets
            let market = all_active_markets.find(m => m.uuid === uuid);
            if (market) {
              $("#marketNames").append(`
                <div class="mb-3 fv-row"><span class="circleBefore active">${market.market_name}</span></div>
              `);
            }
          });
        } else {
          $("#marketNames").html('<div class="mb-3 fv-row"><span class="circleBefore">Not included in any markets</span></div>');
        }

        // Check the corresponding checkboxes in the manage markets modal
        marketArr.forEach(uuid => {
          const $checkbox = $(`input[type="checkbox"][value="${uuid}"]`);
          if ($checkbox.length) {
            $checkbox.prop('checked', true);
            $checkbox.trigger('change');
            $('#market_id').append(`<option value="${uuid}" selected>${uuid}</option>`);
          }
        });
                      if ($('#shippingweight').val() != '') {
                        $('#physicalproduct').click();
                        $('.physicalproductHide').show();
                      } 
                      if ($("select[name='country_id']").val(response.data.country_id)) {
                        $('.custom-information-main').show();
                        $('#AddCustomInformation').hide();
                      } 

                      $('.seo-prev').show();
                      $('#seoMainDesc').hide();

                      $('#v_locations').trigger('change');  
                    
            } 
      },
      error: function (xhr, status, error) {
        console.error('Error fetching categories:', status, error);
      } 
      });

}
$('.manage-sales-text').on('click', function () {
  if (!$(this).hasClass('deselect')) {
    $(this).addClass('deselect');
    $(this).text('Deselect all');
    $('.sales-checkbox input[type="checkbox"]').prop('checked', true);
    let checkedCount = $(this).parents('.modal-body').find('input[type="checkbox"]:checked').length;
    $(this).parents('.modal-body').find('checkednumber').text(checkedCount);

    $("#location-checkboxs-error").addClass("d-none");
    $("#locationDone1").attr("disabled", false);
  } else {
    $(this).removeClass('deselect');
    $(this).text('Select all');
    $('.sales-checkbox input[type="checkbox"]').prop('checked', false);
    let checkedCount = 0;
    $(this).parents('.modal-body').find('checkednumber').text(checkedCount);
    
    $("#location-checkboxs-error").removeClass("d-none");
    $("#locationDone1").attr("disabled", true);
  }
});

document.getElementById("sipmle_shipping_weight").addEventListener("input", function () {
  let inputValue = parseFloat(this.value);

  if (inputValue < 0) {
    this.value = "0.1";
    showError(this, "Negative weight is not allowed. Automatically set to 0.1");
  } else {
    //clearError(this);
  }
});

function get_active_sales_channels(){
$.ajax({
  url: ApiPlugin + '/ecommerce/channel/get_active_channels',
  type: "GET",
  contentType: "application/json",
  dataType: "json",
  beforeSend: function (request) {
    request.setRequestHeader("Authorization", 'Bearer ' + strkey);
  },
  success: function (response) {
    if (response.status_code === 200) {
      const saleChannel = $('#sales-channels');
      const sales_checkboxs = $('#sales-checkboxs');
      saleChannel.empty();
      sales_checkboxs.empty();

      let salesChannelsArr = [];

      let htmlLi = ""
      response.data.forEach(channels => {

        salesChannelsArr.push({
          name: channels.name,
          featured: channels.featured
        });

        htmlLi += `<div class="form-check mb-4">
            <input class="form-check-input" type="checkbox" value="${channels.uuid}"
              id="${channels.name}" name="getChannelId[]">
            <label class="form-check-label" for="${channels.name}">
              <span>${channels.name}</span>
            </label>
          </div>`;
      });
      sales_checkboxs.html(htmlLi);

 

      let htmlLi1 = ""
      response.data.forEach(channels => {
      // saleChannelArr.forEach(element => {
         // if (element == channels.uuid) {

            if (channels.name == "Online Store") {
              htmlLi1 += `<div class="col-12 mb-1">
                            <div class="d-flex justify-content-between">
                              <span class="circleBefore active">${channels.name}</span>
                              <svg data-bs-toggle="modal" data-bs-target="#manageCalender" class="custom-svg" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16"> <path fill-rule="evenodd" d="M4 .25a.75.75 0 0 1 .75.75v.528c.487-.028 1.07-.028 1.768-.028h2.482v-.5a.75.75 0 0 1 1.5 0v.604a3.04 3.04 0 0 1 2.25 2.936c0 .835-.678 1.513-1.513 1.513h-8.733c-.004.288-.004.617-.004.997v2.468c0 1.233 1 2.232 2.232 2.232a.75.75 0 0 1 0 1.5 3.73 3.73 0 0 1-3.732-3.732v-2.5c0-.813 0-1.469.043-2 .045-.546.14-1.026.366-1.47a3.75 3.75 0 0 1 1.841-1.733v-.815a.75.75 0 0 1 .75-.75m7.237 4.303h-8.61c.033-.13.072-.234.118-.324a2.25 2.25 0 0 1 .984-.984c.197-.1.458-.17.912-.207.462-.037 1.057-.038 1.909-.038h3.16c.85 0 1.54.69 1.54 1.54v.005l-.004.004-.004.003z"> </path> <path d="M12.25 10a.75.75 0 0 0-1.5 0v1.293c0 .331.132.65.366.884l.854.853a.749.749 0 1 0 1.06-1.06l-.78-.78z"> </path> <path fill-rule="evenodd" d="M11.5 16a4.5 4.5 0 1 0 0-9 4.5 4.5 0 0 0 0 9m0-1.5a3 3 0 1 0 0-6 3 3 0 0 0 0 6"></path> </svg>
                            </div>
                          </div>`;
            }
            if (channels.name == "Point of Sale") {
              htmlLi1 += `<div class="col-12 mb-1">
                            <span class="circleBefore pos">${channels.name}</span>
                            <p class="m-0">Point of Sale has not been set up. Finish the remaining steps to start selling in person.</p>
                            <a href="#" class="pt-0 text-decoration-underline">Learn more</a>
                          </div>`;
            }

         // }
        });
     // });
      saleChannel.html(htmlLi1);

      $('.sales-checkbox input[type="checkbox"]').on('change', function () {
        let modalBody = $(this).closest('.modal-body');
        let totalCheckboxes = modalBody.find('input[type="checkbox"]').length;
        let checkedCount = modalBody.find('input[type="checkbox"]:checked').length;

        modalBody.find('checkednumber').text(checkedCount);

        if (checkedCount === totalCheckboxes) {
          $('.manage-sales-text').addClass('deselect');
          $('.manage-sales-text').text('Deselect all');
        } else {
          $('.manage-sales-text').removeClass('deselect');
          $('.manage-sales-text').text('Select all');
        }
      });

      let getPushSalesChannel = [];

      $('#sale_channel_id').empty();
        getPushSalesChannel.forEach(element => {
        $('#sale_channel_id').append(`<option value="${element}" selected>${element}</option>`);
      });

      $('#salesChannel').on('click', function () {
        getPushSalesChannel = [];
        $("input[name='getChannelId[]']:checked").each(function () {
          let getSalesChannel = $(this).val();
          getPushSalesChannel.push(getSalesChannel);
        });
        // $('input[name=sale_channel_id').val(getPushSalesChannel.join(","));

        $('#sale_channel_id').empty();
        getPushSalesChannel.forEach(element => {
          $('#sale_channel_id').append(`<option value="${element}" selected>${element}</option>`);
        });

        let htmlLi3 = "";
        $("#manageSalesChannels #sales-checkboxs input:checked").each((i, e) => {

          salesChannelsArr.forEach(element => {

            if (element.name == $(e).next().text().trim()) {

              if (element.name == "Online Store") {
                htmlLi3 += `<div class="col-12 mb-1">
                              <div class="d-flex justify-content-between">
                                <span class="circleBefore active">${element.name}</span>
                                <svg data-bs-toggle="modal" data-bs-target="#manageCalender" class="custom-svg" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16"> <path fill-rule="evenodd" d="M4 .25a.75.75 0 0 1 .75.75v.528c.487-.028 1.07-.028 1.768-.028h2.482v-.5a.75.75 0 0 1 1.5 0v.604a3.04 3.04 0 0 1 2.25 2.936c0 .835-.678 1.513-1.513 1.513h-8.733c-.004.288-.004.617-.004.997v2.468c0 1.233 1 2.232 2.232 2.232a.75.75 0 0 1 0 1.5 3.73 3.73 0 0 1-3.732-3.732v-2.5c0-.813 0-1.469.043-2 .045-.546.14-1.026.366-1.47a3.75 3.75 0 0 1 1.841-1.733v-.815a.75.75 0 0 1 .75-.75m7.237 4.303h-8.61c.033-.13.072-.234.118-.324a2.25 2.25 0 0 1 .984-.984c.197-.1.458-.17.912-.207.462-.037 1.057-.038 1.909-.038h3.16c.85 0 1.54.69 1.54 1.54v.005l-.004.004-.004.003z"> </path> <path d="M12.25 10a.75.75 0 0 0-1.5 0v1.293c0 .331.132.65.366.884l.854.853a.749.749 0 1 0 1.06-1.06l-.78-.78z"> </path> <path fill-rule="evenodd" d="M11.5 16a4.5 4.5 0 1 0 0-9 4.5 4.5 0 0 0 0 9m0-1.5a3 3 0 1 0 0-6 3 3 0 0 0 0 6"></path> </svg>
                              </div>
                            </div>`;
              }
              if (element.name == "Point of Sale") {
                htmlLi3 += `<div class="col-12 mb-1">
                              <span class="circleBefore pos">${element.name}</span>
                              <p class="m-0">Point of Sale has not been set up. Finish the remaining steps to start selling in person.</p>
                              <a href="#" class="pt-0 text-decoration-underline">Learn more</a>
                            </div>`;
              }

            }

          });

        });

        if ($("#manageSalesChannels #sales-checkboxs input:checked").length == 0) {
          htmlLi3 = "Not included in any sales channels"
        }
        saleChannel.html(htmlLi3);
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

// function get_active_tags(){
//   $.ajax({
//     url: ApiPlugin + '/ecommerce/product/get_product_tags',
//     type: "GET",
//     contentType: "application/json",
//     dataType: "json",
//     beforeSend: function (request) {
//       request.setRequestHeader("Authorization", 'Bearer ' + strkey);
//     },
//     success: function (response) {
//       if (response.status_code === 200) {
//         const parenttagsDropdown = $('#tags');
//         parenttagsDropdown.empty();

//         //const selectedTags = JSON.parse(data.tags);
//         response.data.forEach(tag => {                  
//         //  const isSelected = selectedTags != null && selectedTags.includes(tag.name) ? 'selected' : '';
//           parenttagsDropdown.append(
//             `<option value="${tag.name}">${tag.name}</option>`
//           );
//         });
//         parenttagsDropdown.selectpicker("refresh");
//       } else {
//         console.error('Unexpected response:', response);
//       }
//     },
//     error: function (xhr, status, error) {
//       console.error('Error fetching tags:', status, error);
//     }
//   });
// }


function get_active_collections(){
  $.ajax({
    url: ApiPlugin + '/ecommerce/collection/get_active_collections',
    type: "GET",
    contentType: "application/json",
    dataType: "json",
    beforeSend: function (request) {
      request.setRequestHeader("Authorization", 'Bearer ' + strkey);
    },
    success: function (response) {
      if (response.status_code === 200) {

        const parentCollection = $('#collection');
        parentCollection.empty(); // Clear existing options
        response.data.forEach(collection => { 
         // console.log(collection);
          parentCollection.append(
            `<option value="${collection.uuid}">${collection.name}</option>`
          );
        });

      } else {
        console.error('Unexpected response:', response);
      }
    },
    error: function (xhr, status, error) {
      console.error('Error fetching collections:', status, error);
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
        all_active_markets = response.data; // Store market data
        let market_names = response.data.map(element => element.market_name);

        // Format market names for display
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
        $("#marketNames").empty();
        response.data.forEach(element => {
          $("#marketNames").append(`
            <div class="mb-3 fv-row"><span class="circleBefore active">${element.market_name}</span></div>
          `);
        });

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
      } else {
        console.error('Unexpected response:', response);
      }
    },
    error: function (xhr, status, error) {
      console.error('Error fetching markets:', status, error);
    }
  });
}
function get_locations(){
  $.ajax({
    url: ApiPlugin + '/ecommerce/warehouse/get_active_warehouse_locations',
    type: "GET",
    contentType: "application/json",
    dataType: "json",
    beforeSend: function (request) {
      request.setRequestHeader("Authorization", 'Bearer ' + strkey);
    },
    success: function (response) {
      if (response.status_code === 200) {
       // console.log("Locations Loaded");
        all_active_locations=response.data;
        $('#v_locations').empty();
        $('#v_locations').append(`<option value="all">All Locations</option>`); 
        response.data.forEach(element => {
          $('#v_locations').append(`<option value="${element.uuid}">${element.location_name}</option>`);
          
        });

        $("#v_locations").select2({
          allowClear: false,
          minimumResultsForSearch: Infinity
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
function get_active_types(){
  $.ajax({
    url: ApiPlugin + '/ecommerce/product/get_product_types',
    type: "GET",
    contentType: "application/json",
    dataType: "json",
    beforeSend: function (request) {
      request.setRequestHeader("Authorization", 'Bearer ' + strkey);
    },
    success: function (response) {
      if (response.status_code === 200) {
        const parenttypeDropdown = $('#type1');
        parenttypeDropdown.empty();
        parenttypeDropdown.html('<option value="" disabled>Select Type</option>'); 
        response.data.forEach(types => { 
          parenttypeDropdown.append(`<option value="${types.type}">${types.type}</option>`);
        });
         parenttypeDropdown.selectpicker("refresh");
      } else {
        console.error('Unexpected response:', response);
      }
    },
    error: function (xhr, status, error) {
      console.error('Error fetching types:', status, error);
    }
  });
}


function get_active_vendors(){
  $.ajax({
    url: ApiPlugin + '/ecommerce/product/get_product_vendors',
    type: "GET",
    contentType: "application/json",
    dataType: "json",
    beforeSend: function (request) {
      request.setRequestHeader("Authorization", 'Bearer ' + strkey);
    },
    success: function (response) {
      if (response.status_code === 200) {
        const parentvendorDropdown = $('#vendor1');
        parentvendorDropdown.empty();
        parentvendorDropdown.html('<option value="" disabled>Select Vendor</option>'); 
        response.data.forEach(vendors => { 
          parentvendorDropdown.append(`<option value="${vendors.vendor}">${vendors.vendor}</option>`);
        });
        parentvendorDropdown.selectpicker("refresh");
      } else {
        console.error('Unexpected response:', response);
      }
    },
    error: function (xhr, status, error) {
      console.error('Error fetching Vendors:', status, error);
    }
  });
}
function get_active_categories() {
  $.ajax({
    url: ApiPlugin + '/ecommerce/category/get_active_categories',
    type: "GET",
    contentType: "application/json",
    dataType: "json",
    beforeSend: function (request) {
      request.setRequestHeader("Authorization", 'Bearer ' + strkey);
    },
    success: function (response) {
      if (response.status_code === 200) {
        const parentDropdown = $('#category_id');
        parentDropdown.empty(); // Clear existing options

        // Build a tree structure
        const buildTree = (items, parentId = 0, level = 0) => {
          const result = [];
          const children = items.filter(item => item.parent_id == parentId);
          
          children.forEach(child => {
            result.push({
              ...child,
              level: level,
              children: buildTree(items, child.id, level + 1)
            });
          });
          
          return result;
        };

        // Flatten tree in order with proper indentation
        const flattenTree = (tree) => {
          let result = [];
          tree.forEach(node => {
            result.push(node);
            if (node.children.length > 0) {
              result = result.concat(flattenTree(node.children));
            }
          });
          return result;
        };

        const categoryTree = buildTree(response.data);
        const orderedCategories = flattenTree(categoryTree);

        // Add options to dropdown
        orderedCategories.forEach(category => {
          let dashes = '-'.repeat(category.level);
          let prefix = category.level > 0 ? `${dashes} ` : '';
          parentDropdown.append(
            `<option value="${category.uuid}">${prefix}${category.name}</option>`
          );
        });
      } else {
        console.error('Unexpected response:', response);
      }
    },
    error: function (xhr, status, error) {
      console.error('Error fetching categories:', status, error);
      // Show user-friendly error message
      showToast('Failed to load categories', 'Error', 'error');
    }
  });
}
 

  function get_active_countries(){ 
    $.ajax({
      url: ApiPlugin + '/ecommerce/get_active_countries',
      type: "GET",
      contentType: "application/json",
      dataType: "json",
      beforeSend: function (request) {
          request.setRequestHeader("Authorization", 'Bearer ' + strkey);
      },
      success: function (response) {
          if (response.status_code === 200) {
              const parentDropdown = $('#simple_country_id');
              parentDropdown.empty();  
              response.data.forEach(country => {   
                      parentDropdown.append(
                          `<option value="${country.uuid}">${country.name}</option>`
                      ); 
              }); 
               parentDropdown.trigger("change");
          } else {
              console.error('Unexpected response country:', response);
          }
         
      },
      error: function (xhr, status, error) {
          console.error('Error fetching country:', status, error);
      }
    });
    }
   
   
    // const $picker = $('#simple_hscode').selectpicker();

    // // Delay typing to avoid too many API calls
    // let debounceTimer = null;
  
    // // Observe changes to selectpicker's internal search input
    // $(document).on('input', '.bs-searchbox input', function () {
    //   const searchTerm = $(this).val().trim();
  
    //   if (searchTerm.length < 2) return;
  
    //   clearTimeout(debounceTimer);
    //   debounceTimer = setTimeout(() => {
    //     fetchHSCodes(searchTerm);
    //   }, 300); // debounce delay
    // });
  
    // function fetchHSCodes(searchTerm) {
    //   const requestData = {
    //     operationName: "HSCodesServiceQuery",
    //     variables: {
    //       query: searchTerm,
    //       locale: "en"
    //     },
    //     query: `
    //       query HSCodesServiceQuery($query: String!, $locale: String) {
    //         hsCodes(searchTerm: $query, locale: $locale) {
    //           code
    //           description
    //           __typename
    //         }
    //       }
    //     `
    //   };
  
    //   $.ajax({
    //     url: "https://hs-codes-service.shopifyapps.com/graphql",
    //     method: "POST",
    //     contentType: "application/json",
    //     data: JSON.stringify(requestData),
    //     success: function (response) {
    //       const hsCodes = response?.data?.hsCodes || [];
    //       $picker.empty();
  
    //       if (hsCodes.length > 0) {
    //         hsCodes.forEach(code => {
    //           $picker.append(`<option value="${code.code}">${code.code} — ${code.description}</option>`);
    //         });
    //       } else {
    //         $picker.append(`<option disabled>No results found</option>`);
    //       }
  
    //       $picker.selectpicker('refresh');
    //     },
    //     error: function (xhr, status, error) {
    //       console.error('Error fetching HS Codes:', error);
    //     }
    //   });
    // }
  //   function initHSCodesSelect() {
  //     $('#simple_hscode').select2({
  //         placeholder: 'Search HS Code...',
  //         minimumInputLength: 2,
  //         ajax: {
  //             url: "https://hs-codes-service.shopifyapps.com/graphql",
  //             type: "POST",
  //             contentType: "application/json",
  //             dataType: "json",
  //             delay: 300, // Debounce for typing
  //             data: function (params) {
  //                 return JSON.stringify({
  //                     operationName: "HSCodesServiceQuery",
  //                     variables: {
  //                         query: params.term, // search term
  //                         locale: "en"
  //                     },
  //                     query: `
  //                         query HSCodesServiceQuery($query: String!, $locale: String) {
  //                             hsCodes(searchTerm: $query, locale: $locale) {
  //                                 code
  //                                 description
  //                                 __typename
  //                             }
  //                         }
  //                     `
  //                 });
  //             },
  //             processResults: function (data) {
  //                 if (data.data && data.data.hsCodes) {
  //                     return {
  //                         results: data.data.hsCodes.map(hs => ({
  //                             id: hs.code,
  //                             text: `${hs.code} — ${hs.description}`
  //                         }))
  //                     };
  //                 } else {
  //                     return { results: [] };
  //                 }
  //             },
  //             cache: true
  //         }
  //     });
  // }
  

$("#addProductFrom").on("submit", function (e) {
//  makeVariationsTempArray();  
  //updateTableValues();
 
   e.preventDefault();
  let status = true;
   const errors = [];
  $('.error-txt').html(""); // Clear previous inline errors
  $('.form-group').removeClass('error'); // Remove previous error class

  const name = $('input[name="name"]');
  const vendor = $('#vendor');
  const type = $('#type');
  const price = $('#Price');
  const compare_price = $('#compare_price');

  // Title validation
  if (name.val().trim() === "") {
    name.parent().addClass('error');
    name.parent().find('.error-txt').html("Title can’t be blank");
    errors.push("Title can’t be blank");
    status = false;
  }

    name.on("input", function(){
    $("#formError").hide();
  });

  // Vendor validation
  if (vendor.val().length > 100) {
    vendor.parent().addClass('error');
    vendor.parent().find('.error-txt').html("Vendor can't be more than 100 characters");
    errors.push("Vendor can't be more than 100 characters");
    status = false;
  }

  vendor.on("input", function(){
    $("#formError").hide();
  });

  const priceValue = parseFloat(price.val()) || 0; // Get the numeric value
  if (priceValue > 1000000) {
    price.parent().addClass('error');
    price.parent().find('.error-txt').html("Price can't be more than 1000000");
    errors.push("Price can't be more than 1000000");
    status = false;
  }

  price.on("input", function() {
    $("#formError").hide();
  });

  const comparepriceValue = parseFloat(compare_price.val()) || 0; // Get the numeric value 
  if (comparepriceValue > 1000000) {
   //  alert(comparepriceValue);
    compare_price.parent().addClass('error');
    compare_price.parent().find('.error-txt').html("Compare at Price can't be more than 1000000");
    errors.push("Compare at Price can't be more than 1000000");
    status = false; 
  }

  if (comparepriceValue < priceValue) {
     //alert(comparepriceValue);
    compare_price.parent().addClass('error');
    compare_price.parent().find('.error-txt').html("Compare at Price can't be less than price");
    errors.push("Compare at Price can't be less than price");
    status = false; 
  }


  compare_price.on("input", function() {
    $("#formError").hide();
  });

  // Type validation
  if (type.val().length > 100) {
    type.parent().addClass('error');
    type.parent().find('.error-txt').html("Type can't be more than 100 characters");
    errors.push("Type can't be more than 100 characters");
    status = false;
  }

  type.on("input", function(){
    $("#formError").hide();
  });

  // Show all errors
  if (!status) {
    let errorHtml = "";
    errors.forEach(err => {
      errorHtml += `<li>${err}</li>`;
    });
    $("#formError .error-bottom").html(errorHtml);
    $("#formError").show();
    window.scrollTo(0, 0);
  } else {
    $("#formError").hide();
  }

  let date = $("#putdate").val();
  let time = $("#putTime").val();

  // Convert the date to a proper format (Month, Day, Year -> YYYY-MM-DD)
  let formattedDate = new Date(date + ' ' + time);

  // Extract the components of the date and time
  let year = formattedDate.getFullYear();
  let month = ('0' + (formattedDate.getMonth() + 1)).slice(-2); // Adding leading zero
  let day = ('0' + formattedDate.getDate()).slice(-2); // Adding leading zero

  // Extract the time components (hours, minutes, and seconds)
  let hours = ('0' + formattedDate.getHours()).slice(-2);
  let minutes = ('0' + formattedDate.getMinutes()).slice(-2);
  let seconds = ('0' + formattedDate.getSeconds()).slice(-2);

  // Combine all parts into the desired format
  let published_date_time = `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;

  $("[name='published_date_time']").val(published_date_time);
   // No changes needed here, your current code is fine:
  let tags = $('#tags').val() || [];
  let tagsString = Array.isArray(tags) ? tags.join(',') : tags;

  let formData = new FormData(this); 
  let editorVal = getEditorValue();
  formData.append('description', editorVal); 
  formData.append('product_id', productID);
  formData.append('tags', tagsString); 
   if (status) {
     $.ajax({ 
       url: ApiPlugin + "/ecommerce/product/update_product",
       type: "POST",
       data: formData,
       contentType: false,
       processData: false,
       beforeSend: function (xhr) {
        xhr.setRequestHeader("Authorization", "Bearer " + strkey);
        xhr.setRequestHeader("menu-uuid", menu_id);
        xhr.setRequestHeader("uuid", productID);
        imgload.show();
        },
       success: function (response) {
         imgload.hide();
         if (response.status_code == 200) {
           let title = response.message;
              showToast(title, 'Success', 'success', "?P=product&M="+menu_id);
         }
       },
       error: function (xhr, status, err) {
         imgload.hide();
         if (xhr.status === 422) {
           // Validation error
           let errors = xhr.responseJSON.errors;
           errors = JSON.parse(errors);
           let errorMessages = "";
 
           // Iterate over all errors and concatenate them
           $.each(errors, function (field, messages) {
             messages.forEach(function (message) {
               errorMessages += `<li>${message}</li>`; // Append each error message
             });
           });
           let htmlError = `<ul>${errorMessages}</ul>`;
           showToast(htmlError, 'Error', 'error');
         } else {
           // Handle other errors
           showToast("Something went wrong!", 'Error', 'error');
         }
       }
     });
   }
 
 });

 $(document).on('click', '#delete-product', function(e) {
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
        url: ApiPlugin + `/ecommerce/product/delete_product/${productID}`,
        type: 'DELETE',
        headers: {
          'Authorization': 'Bearer ' + strkey,
          'menu-uuid': menu_id
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
 


 
 
 $(".seoBtn").on("click", function (e) {
  e.preventDefault(); 
  $(this).hide();
  $("#seoTab").show();

});

 document.querySelectorAll("#Price, #compare_price, #cost_per_item").forEach(function (input) {
  input.addEventListener("input", function () {
    this.value = this.value.replace(/-/g, "");
  });
});

document.querySelectorAll("#Price, #compare_price, #cost_per_item").forEach(function (input) {
  input.addEventListener("focus", function () {
    $(input).select();
  });
});

$("#Price, #cost_per_item").on("input", calculateProfitAndMargin);
$("#Price, #cost_per_item").on("blur", function () {
  var value = parseFloat($(this).val()) || 0;
  if ($(this).val() == "") {
    $(this).val("0.00")
  }
  $(this).val(value.toFixed(2));
});

function calculateProfitAndMargin() {
  var price = parseFloat($("#Price").val()) || 0;
  var cost = parseFloat($("#cost_per_item").val()) || 0;

  if (price > 0 && cost > 0) {
    var profit = price - cost;
    var margin = (profit / price) * 100;

    $("#profit_price").val(profit.toFixed(2));
    if (margin % 1 === 0) {
      $("#margin_price").val(margin + "%");
    } else {
      $("#margin_price").val(margin.toFixed(1) + "%");
    }
  } else {
    $("#profit_price").val("");
    $("#margin_price").val("");
  }
}


$('#market_value_update').on('click', function () {
  let market_values = [];
  $("input[name='market_value']:checked").each(function () {
    let market_value = $(this).val();
    market_values.push(market_value);
  });

  // Update the market_id select element
  $('#market_id').empty();
  market_values.forEach(uuid => {
    let market = all_active_markets.find(m => m.uuid === uuid);
    if (market) {
      $('#market_id').append(`<option value="${uuid}" selected>${market.market_name}</option>`);
    }
  });

  // Update the #marketNames div
  $("#marketNames").empty();
  if (market_values.length > 0) {
    $("#marketNamesBox").removeClass("d-none");
    market_values.forEach(uuid => {
      let market = all_active_markets.find(m => m.uuid === uuid);
      if (market) {
        $("#marketNames").append(`
          <div class="mb-3 fv-row"><span class="circleBefore active">${market.market_name}</span></div>
        `);
      }
    });
  } else {
    $("#marketNames").html('<div class="mb-3 fv-row"><span class="circleBefore">Not included in any markets</span></div>');
  }
});

function initializeSelect2() {
  $('#tags').select2({
    tags: true,
    allowClear: true,
    tokenSeparators: [',', ' '],
  });

  $('#collection').select2({
    tags: true,
    allowClear: true,
    tokenSeparators: [',', ' '],
  });

  $('#vat_id').select2({
    placeholder: "Select VAT Rate",
    allowClear: true,
    width: '100%'
  });

  $('#discount_id').select2({
    placeholder: "Select Discount Rate",
    allowClear: true,
    width: '100%'
  });
}