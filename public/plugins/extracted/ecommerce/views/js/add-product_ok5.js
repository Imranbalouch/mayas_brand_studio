var urlParams = new URLSearchParams(window.location.search);
var productID = urlParams.get('id');
var all_active_locations=[];
$(".seoBtn").on("click", function (e) {
  e.preventDefault();

  $(this).hide();
  $("#seoTab").show();

});
$(".variants-option-cards .cards").sortable({
  handle: '.drag-card',
  update: function (event, ui) {
   updateSortingVariantsCard();
  }
});
$(".variants-option-cards .another-inputs-wrapper").sortable({
    handle: '.drag-input',
    update: function (event, ui) {
    updateSortingVariantsCardInput();
    }
  });
$(".filemanager-image-preview").sortable({
  update: function (event, ui) {
    updateSortingImagesCard();
  }
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

$(".productTitle").on("input", function () {
  $("#productTitleCount").text($(this).val().length);
});

$("#seoDesc").on("input", function () {
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

$("#Continueselling").on("change", function () {

  if ($(this).prop("checked")) {
    $(this).next().find(".desc").removeClass("d-none");
    // mainaddlocation();
  } else {
    $(this).next().find(".desc").addClass("d-none");
  }

});

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
        response.data.forEach(category => {
          let spaces = '-'.repeat(category.level); // Adding spaces based on the category level
          parentDropdown.append(
            `<option value="${category.uuid}" >${spaces} ${category.name}</option>`
          );
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

function get_active_collection() {
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
        const parentDropdownCollection = $('#collection');
        parentDropdownCollection.empty(); // Clear existing options

        response.data.forEach(category => {
          // console.log(response.data);
          let spaces = '-'.repeat(category.level);
          parentDropdownCollection.append(
            `<option value="${category.uuid}">${spaces} ${category.name}</option>`
          );
        });
        parentDropdownCollection.selectpicker("refresh");
      } else {
        console.error('Unexpected response:', response);
      }
    },
    error: function (xhr, status, error) {
      console.error('Error fetching categories:', status, error);
    }
  });
}

function get_active_countries1() {
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
        const parentCountryDropdown = $('#country_id');
        parentCountryDropdown.empty(); // Clear existing options
        parentCountryDropdown.html('<option value="" disabled selected>Select an option</option>'); // New placeholder
        response.data.forEach(country => {
          parentCountryDropdown.append(
            `<option value="${country.uuid}"> ${country.name}</option>`
          );
        });
        parentCountryDropdown.selectpicker("refresh");
      } else {
        console.error('Unexpected response:', response);
      }
    },
    error: function (xhr, status, error) {
      console.error('Error fetching categories:', status, error);
    }
  });
}

function get_active_vendor() {
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
        const parentvendorDropdown = $('#vendor');
        parentvendorDropdown.empty(); // Clear existing options
        parentvendorDropdown.html('<option value=""></option>'); // New placeholder

        response.data.forEach(vendor => {
          // console.log(vendor.data);
          parentvendorDropdown.append(
            `<option value="${vendor.vendor}">${vendor.vendor}</option>`
          );
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

function get_active_tags() {
  $.ajax({
    url: ApiPlugin + '/ecommerce/product/get_product_tags',
    type: "GET",
    contentType: "application/json",
    dataType: "json",
    beforeSend: function (request) {
      request.setRequestHeader("Authorization", 'Bearer ' + strkey);
    },
    success: function (response) {
      if (response.status_code === 200) {
        const parenttagsDropdown = $('#tags');
        parenttagsDropdown.empty(); // Clear existing options

        response.data.forEach(tags => {
          // console.log(response.data);
          parenttagsDropdown.append(
            `<option value="${tags.name}"> ${tags.name}</option>`
          );
        });
        parenttagsDropdown.selectpicker("refresh");
      } else {
        console.error('Unexpected response:', response);
      }
    },
    error: function (xhr, status, error) {
      console.error('Error fetching categories:', status, error);
    }
  });
}

function get_active_type() {
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
        const parenttypeDropdown = $('#type');
        parenttypeDropdown.empty(); // Clear existing options
        // let getOptionTypes = "<option>Select Type</option>"
        parenttypeDropdown.html('<option value=""></option>'); // New placeholder
        response.data.forEach(types => {
          parenttypeDropdown.append(`<option value="${types.type}" >${types.type}</option>`);
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
$(document).on('change', '#v_locations', async function () {
  const locationId = $(this).val(); 
  //console.log('Triggered locationId:', locationId); 
    makeVariationsTempArray(); 
    //updateTableValues(variantsEditData);
    updateTableValues(variantsEditData);
});
function get_active_location() {
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
        all_active_locations=response.data;
        const warehouse_location_id = $('#warehouse_location_id');
        const location_checkboxs = $('#location-checkboxs');
        location_checkboxs.empty();
        warehouse_location_id.empty(); // Clear existing options
        let htmlLocation = "";
        $("#manage-sales-total").html(response.data.length);
        response.data.forEach(location => {
          // console.log(response.data);
          htmlLocation += `<div class="form-check mb-4">
                <input class="form-check-input" type="checkbox" value="${location.uuid}"
                  id="${location.location_name}" name="getlocationId[]" ${location?.featured == 1 ? "checked" : ""}>
                <label class="form-check-label" for="${location.location_name}">
                  <span>${location.location_name}</span>
                </label>
              </div>`;
        });
        location_checkboxs.html(htmlLocation);
        $("#manage-sales-total-checked").html($('#location-checkboxs input[type="checkbox"]:checked').length);
        $('#location-checkboxs input[type="checkbox"]').on('change', function () {
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

          if(checkedCount == 0) {
            $("#location-checkboxs-error").removeClass("d-none");
            $("#locationDone1").attr("disabled", true);
          } else {
            $("#location-checkboxs-error").addClass("d-none");
            $("#locationDone1").attr("disabled", false);
          }

        });
        mainaddlocation();
      } else {
        console.error('Unexpected response:', response);
      }
    },
    error: function (xhr, status, error) {
      console.error('Error fetching categories:', status, error);
    }
  });
}

function get_active_v_location() {
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

        $('#v_locations').empty();
        $('#v_locations').append(`<option value="all">All Locations</option>`);
       // selected =   'selected';
        //alert(selected);
        response.data.forEach(element => {
          $('#v_locations').append(`<option value="${element.uuid}">${element.location_name}</option>`);
       //   selected =   '';
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

function get_active_channel() {
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
        const saleChannel = $('#sale_channel_id');
        const sales_checkboxs = $('#sales-checkboxs');
        sales_checkboxs.empty();
        const sales_channels = $('#sales-channels');
        sales_channels.empty();
        saleChannel.empty(); // Clear existing options
        let htmlLi1 = ""
        response.data.forEach(channels => {
          // console.log(response.data);
          htmlLi1 += `<div class="form-check mb-4">
                <input class="form-check-input" type="checkbox" value="${channels.uuid}"
                  id="${channels.name}" name="getChannelId[]" ${channels.featured == 1 ? "checked" : ""}>
                <label class="form-check-label" for="${channels.name}">
                  <span>${channels.name}</span>
                </label>
              </div>`;
        });
        sales_checkboxs.html(htmlLi1);
        $("#manageSalesChannels .manage-sales-select checkednumber").text($("#manageSalesChannels #sales-checkboxs input[type='checkbox']:checked").length);
        
        $('#sale_channel_id').empty();
        response.data.forEach(channels => {
          if (channels.featured) {
            $('#sale_channel_id').append(`<option value="${channels.uuid}" selected>${channels.uuid}</option>`)
          }
        });
        
        let salesChannelsArr = [];

        let htmlLi2 = "";
        response.data.forEach(channels => {
          // console.log(response.data);

          salesChannelsArr.push({
            name: channels.name,
            featured: channels.featured
          });

          if(channels.featured == 1) {
            if(channels.name == "Online Store") {
              htmlLi2 += `<div class="col-12 mb-1">
                            <div class="d-flex justify-content-between">
                              <span class="circleBefore">${channels.name}</span>
                              <svg data-bs-toggle="modal" data-bs-target="#manageCalender" class="custom-svg" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16"> <path fill-rule="evenodd" d="M4 .25a.75.75 0 0 1 .75.75v.528c.487-.028 1.07-.028 1.768-.028h2.482v-.5a.75.75 0 0 1 1.5 0v.604a3.04 3.04 0 0 1 2.25 2.936c0 .835-.678 1.513-1.513 1.513h-8.733c-.004.288-.004.617-.004.997v2.468c0 1.233 1 2.232 2.232 2.232a.75.75 0 0 1 0 1.5 3.73 3.73 0 0 1-3.732-3.732v-2.5c0-.813 0-1.469.043-2 .045-.546.14-1.026.366-1.47a3.75 3.75 0 0 1 1.841-1.733v-.815a.75.75 0 0 1 .75-.75m7.237 4.303h-8.61c.033-.13.072-.234.118-.324a2.25 2.25 0 0 1 .984-.984c.197-.1.458-.17.912-.207.462-.037 1.057-.038 1.909-.038h3.16c.85 0 1.54.69 1.54 1.54v.005l-.004.004-.004.003z"> </path> <path d="M12.25 10a.75.75 0 0 0-1.5 0v1.293c0 .331.132.65.366.884l.854.853a.749.749 0 1 0 1.06-1.06l-.78-.78z"> </path> <path fill-rule="evenodd" d="M11.5 16a4.5 4.5 0 1 0 0-9 4.5 4.5 0 0 0 0 9m0-1.5a3 3 0 1 0 0-6 3 3 0 0 0 0 6"></path> </svg>
                            </div>
                          </div>`;
            }
            if(channels.name == "Point of Sale") {
              htmlLi2 += `<div class="col-12 mb-1">
                            <span class="circleBefore">${channels.name}</span>
                            <p class="m-0">Point of Sale has not been set up. Finish the remaining steps to start selling in person.</p>
                            <a href="#" class="pt-0 text-decoration-underline">Learn more</a>
                          </div>`;
            }
          }
        });
        
        sales_channels.html(htmlLi2);

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

        $('#salesChannel').on('click', function () {
          let getPushSalesChannel = [];
        
          $("input[name='getChannelId[]']:checked").each(function () {
            let getSalesChannel = $(this).val();
        
            getPushSalesChannel.push(getSalesChannel);
        
          });
          // $('input[name=sale_channel_id[]').val(getPushSalesChannel.join(","));
          
          $('#sale_channel_id').empty();
          getPushSalesChannel.forEach(element => {
            $('#sale_channel_id').append(`<option value="${element}" selected>${element}</option>`);
          });

          let htmlLi3 = "";
          $("#manageSalesChannels #sales-checkboxs input:checked").each((i, e)=>{

            salesChannelsArr.forEach(element => {

              if (element.name == $(e).next().text().trim()) {

                if(element.name == "Online Store") {
                  htmlLi3 += `<div class="col-12 mb-1">
                                <div class="d-flex justify-content-between">
                                  <span class="circleBefore">${element.name}</span>
                                  <svg data-bs-toggle="modal" data-bs-target="#manageCalender" class="custom-svg" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16"> <path fill-rule="evenodd" d="M4 .25a.75.75 0 0 1 .75.75v.528c.487-.028 1.07-.028 1.768-.028h2.482v-.5a.75.75 0 0 1 1.5 0v.604a3.04 3.04 0 0 1 2.25 2.936c0 .835-.678 1.513-1.513 1.513h-8.733c-.004.288-.004.617-.004.997v2.468c0 1.233 1 2.232 2.232 2.232a.75.75 0 0 1 0 1.5 3.73 3.73 0 0 1-3.732-3.732v-2.5c0-.813 0-1.469.043-2 .045-.546.14-1.026.366-1.47a3.75 3.75 0 0 1 1.841-1.733v-.815a.75.75 0 0 1 .75-.75m7.237 4.303h-8.61c.033-.13.072-.234.118-.324a2.25 2.25 0 0 1 .984-.984c.197-.1.458-.17.912-.207.462-.037 1.057-.038 1.909-.038h3.16c.85 0 1.54.69 1.54 1.54v.005l-.004.004-.004.003z"> </path> <path d="M12.25 10a.75.75 0 0 0-1.5 0v1.293c0 .331.132.65.366.884l.854.853a.749.749 0 1 0 1.06-1.06l-.78-.78z"> </path> <path fill-rule="evenodd" d="M11.5 16a4.5 4.5 0 1 0 0-9 4.5 4.5 0 0 0 0 9m0-1.5a3 3 0 1 0 0-6 3 3 0 0 0 0 6"></path> </svg>
                                </div>
                              </div>`;
                }
                if(element.name == "Point of Sale") {
                  htmlLi3 += `<div class="col-12 mb-1">
                                <span class="circleBefore">${element.name}</span>
                                <p class="m-0">Point of Sale has not been set up. Finish the remaining steps to start selling in person.</p>
                                <a href="#" class="pt-0 text-decoration-underline">Learn more</a>
                              </div>`;
                }

              }
              
            });
            
          });
          
          if($("#manageSalesChannels #sales-checkboxs input:checked").length == 0) {
            htmlLi3 = "Not included in any sales channels"
          }
          sales_channels.html(htmlLi3);
        
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

function get_active_countries() {
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
        $('#countryRegion').empty();
        $('#countryRegion').html('<option value=""></option>');
        
        response.data.forEach(country => {
          $('#countryRegion').append(
            `<option value="${country.name}"> ${country.name}</option>`
          );
        });
        $('#countryRegion').select2({
          dropdownParent: $('#editCountryRegionModal')
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
        
        let market_names = []

        response.data.forEach(element => {
          market_names.push(element.market_name)
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
        $("#marketNames").html(formattedMarkets);

        $("#manageMarkets .modal-body").empty();
        response.data.forEach(element => {
          $("#manageMarkets .modal-body").append(`
            <div class="sales-checkbox-01 disabled mb-3">
              <div class="form-check mb-4 align-items-start">
                <input class="form-check-input" type="checkbox" value="online-store" checked>
                <label class="form-check-label">
                  <span>${element.market_name}</span>
                  <p class="my-1">${element.market_name}</p>
                  <div class="d-flex align-items-center">
                    <svg width="16" height="16" class="me-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16">
                      <path d="M8 4a.75.75 0 0 1 .75.75v3.5a.75.75 0 0 1-1.5 0v-3.5a.75.75 0 0 1 .75-.75"></path>
                      <path d="M9 11a1 1 0 1 1-2 0 1 1 0 0 1 2 0"></path>
                      <path fill-rule="evenodd"
                        d="M15 8a7 7 0 1 1-14 0 7 7 0 0 1 14 0m-1.5 0a5.5 5.5 0 1 1-11 0 5.5 5.5 0 1 1 11 0"></path>
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
      console.error('Error fetching categories:', status, error);
    }
  });
}

document.querySelectorAll("#addProductFrom input").forEach(function (input) {
  input.addEventListener("input", function () {
    input.parentElement.classList.remove("error");
    input.parentElement.parentElement.querySelector(".error-txt").innerHTML = "";
    input.parentElement.querySelector(".error-txt").innerHTML = "";
  });
});

document.querySelectorAll("#Price, #compare_price, #cost_per_item", "#Price1, #cost_per_item1").forEach(function (input) {
  input.addEventListener("input", function () {
    this.value = this.value.replace(/-/g, "");
  });
});

document.querySelectorAll("#Price, #compare_price, #cost_per_item, #Price1, #cost_per_item1").forEach(function (input) {
  input.addEventListener("focus", function () {
    $(input).select();
  });
});

document.getElementById("shippingweight").addEventListener("input", function () {
  let inputValue = parseFloat(this.value);

  if (inputValue < 0) {
    this.value = "0.1";
    showError(this, "Negative weight is not allowed. Automatically set to 0.1");
  } else {
    clearError(this);
  }
});

$(document).ready(function () {

  $('#type').select2({
    tags: true,
    allowClear: true,
    tokenSeparators: [',', ' '],
  });

  $('#vendor').select2({
    tags: true,
    allowClear: true,
    tokenSeparators: [',', ' '],
  });

  $('#collection').select2({
    tags: true,
    allowClear: true,
    tokenSeparators: [',', ' '],
  });

  $('#tags').select2({
    tags: true,
    allowClear: true,
    tokenSeparators: [',', ' '],
  });

  $('#category_id').select2({
    tags: true,
    allowClear: true,
    tokenSeparators: [',', ' '],
  });

  setTimeout(() => {
    $('#GroupBy').select2({
      allowClear: false,
      minimumResultsForSearch: Infinity
    });
  }, 100);

});

$(document).ready(function () {
  get_active_categories();
  get_active_countries();
  get_active_collection();
  get_active_vendor();
  get_active_tags();
  get_active_type();
  get_active_location();
  get_active_channel();
  get_active_countries1();
  get_active_markets();
  get_active_v_location();

  $("#Price, #cost_per_item").on("input", calculateProfitAndMargin);
  $("#Price, #cost_per_item, #compare_price").on("blur", function() {
    var value = parseFloat($(this).val()) || 0;
    if ($(this).val() == "") {
      $(this).val("0.00")
    }
    $(this).val(value.toFixed(2));
  });
  
  $("#Price1, #cost_per_item1").on("input", calculateProfitAndMargin1);
  $("#Price1, #cost_per_item1, #compare_price1").on("blur", function() {
    var value = parseFloat($(this).val()) || 0;
    if ($(this).val() == "") {
      $(this).val("0.00")
    }
    $(this).val(value.toFixed(2));
  });
});

function calculateProfitAndMargin() {
  var price = parseFloat($("#Price").val()) || 0;
  var cost = parseFloat($("#cost_per_item").val()) || 0;

  if (price > 0 && cost > 0) {
    var profit = price - cost;
    var margin = (profit / price) * 100;

    $("#profit_price").val(profit.toFixed(2));
    if (margin % 1 === 0) {
      $("#margin_price").val(margin);
    } else {
      $("#margin_price").val(margin.toFixed(1));
    }
  } else {
    $("#profit_price").val("");
    $("#margin_price").val("");
  }
}

function calculateProfitAndMargin1() {
  var price = parseFloat($("#Price1").val()) || 0;
  var cost = parseFloat($("#cost_per_item1").val()) || 0;

  if (price > 0 && cost > 0) {
    var profit = price - cost;
    var margin = (profit / price) * 100;

    $("#profit_price1").val(profit.toFixed(2));
    if (margin % 1 === 0) {
      $("#margin_price1").val(margin +"%");
    } else {
      $("#margin_price1").val(margin.toFixed(1) +"%");
    }
  } else {
    $("#profit_price1").val("");
    $("#margin_price1").val("");
  }
}

document.addEventListener("DOMContentLoaded", function () {
  const checkbox = document.getElementById("physicalproduct");
  const physicalProductHide = document.querySelectorAll("#physicalproductHide");
  const physicalProductShow = document.querySelectorAll("#physicalproductshow");

  function toggleVisibility() {
    physicalProductHide.forEach(div => {
      div.style.display = checkbox.checked ? "block" : "none";
    });

    physicalProductShow.forEach(div => {
      div.style.display = checkbox.checked ? "none" : "block";
    });
  }

  checkbox.addEventListener("change", toggleVisibility);
  toggleVisibility();
});


$('.custom-information-main').hide();
$('#AddCustomInformation').on('click', function () {
  $(this).hide();
  $('.custom-information-main').show();
  $('#mySelect').select2({
    allowClear: false,
  });
});

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

$('.openSKU').hide();
$('#productsku').on('change', function () {
  if ($(this).is(':checked')) {
    $('.openSKU').show();
  } else {
    $('.openSKU').hide();
  }
});

function inventorySection() {
  $("#Trackquantity").change(function () {
    if ($(this).is(":checked")) {
      $(".not-tracked").hide();
      $(".tracked, .out-of-stock").show();
    } else {
      $(".not-tracked").show();
      $(".tracked, .out-of-stock").hide();
    }
  });

  $('#locationDone1').on('click', function () {
    mainaddlocation();
  });

}

function mainaddlocation() {
  let getPushlocation = [];
  $('.main-add-location').empty();

  $("input[name='getlocationId[]']:checked").each(function () {
    let getlocation = $(this).val();
    getPushlocation.push(getlocation);
    let getlocationName = $(this).parent().find('span').text();

    let newLocation = $(`
      <div class="col-12 mb-5 d-flex justify-content-between align-items-center">
        <label class="form-label mb-0" for="name"><g-t>${getlocationName}</g-t></label>
        <div class="not-tracked">Not tracked</div>
        <input type="number" class="tracked form-control w-25" min="0" name="location_stock[]" id="current_stock" value="0" style="display: none;">
      </div>
    `);

    // Trackquantity checkbox ka current state check karna
    if ($("#Trackquantity").is(":checked")) {
      newLocation.find(".not-tracked").hide();
      newLocation.find(".tracked, .out-of-stock").show();
    }

    $('.main-add-location').append(newLocation);
  });

  $('input[name=warehouse_location_id]').val(getPushlocation.join(","));
}
inventorySection();

$("#addProductFrom").on("submit", function (e) {
  // $productData = [
  //       "name" => "Product " . str()->random(5),
  //       "slug" => "product-" . $i,
  //       "category_id" => "020136be-3b46-469e-8616-e4f786a90a3f",
  //       "brand_id" => "1190c376-3c26-4c83-ae89-dda161004e4e",
  //       'sale_channel_id'=> "de784a53-09bc-424b-98f7-ca902a14ed1c",
  //       "short_description" => "test",
  //       "type" => "type",
  //       "vendor" => "vendor",
  //       "tags" => "tags",
  //       "weight" => "weight",
  //       "weight_unit" => "weight_unit",
  //       "country_id" => "country_id",
  //       "hs_code" => "hs_code",
  //       "template_product" => "template_product",
  //       "description" => "test",
  //       "warehouse_location_id" => "f75f9ce2-c494-482a-aa78-ad42e1910f87",
  //       "unit_price" => rand(0,50000),
  //       "compare_price" => rand(0,50000),
  //       "cost_per_item" => rand(0,50000),
  //       "profit_price" => rand(0,50000),
  //       "margin_price" => rand(0,50000),
  //       "images" => "uploads/all/br_f765f798-9c9f-423d-8c4b-6b57f30c9eed.png",
  //       "current_stock" => rand(2,50000),
  //       "sku" => "sku-" . $i,  // Unique SKU for each product
  //       "barcode" => "barcode-" . $i,  // Unique barcode for each product
  //       "images" => null,
  //       "meta_title" => null,
  //       "meta_description" => null,
  //       "og_title" => null,
  //       "og_description" => null,
  //       "og_image" => null,
  //       "x_title" => null,
  //       "x_description" => null,
  //       "x_image" => null,
  //   ];
  // var logoFile = document.getElementById('logoFile').value;

  // Check if both fields are empty
  // if (!logoFile) {
  //     alert('Please select Logo fields');
  //     // showToast("Something went wrong!", 'Error', 'error');
  //     return
  // }

  e.preventDefault();
  makeVariationsTempArray();  
  updateTableValues();
  let status = true;

  if ($('input[name="name"]').val() === "") {
    $('input[name="name"]').parent().addClass('error');
    $('input[name="name"]').parent().find('.error-txt').html("Title can’t be blank");
    $('#formError').show();
    window.scrollTo(0, 0);
    status = false;
  }

  $('input[name="name"]').on("input", function(){
    $("#formError").hide();
  });

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

  let formData = new FormData(this);
  // alert($("input[name='description']").val());
  let editorVal = getEditorValue();
  
  formData.append('description', editorVal);
  formData.append('tax_enabled', $(".tax_enabled").prop("checked") ? 1 : 0);
  formData.append('inventory_track_enabled', $(".inventory_track_enabled").prop("checked") ? 1 : 0);
  formData.append('selling_stock_enabled', $(".selling_stock_enabled").prop("checked") ? 1 : 0);
  formData.append('sku_barcode_enabled', $(".sku_barcode_enabled").prop("checked") ? 1 : 0);
  formData.append('physical_product_enabled', $(".physical_product_enabled").prop("checked") ? 1 : 0);

  // varirnt data 
  if ($(".varient_data").val() != "") {
    let updatedVarients = [];
    $(JSON.parse($(".varient_data").val())).each(function(i, element1){

      if (element1.values.length == 0) {
        let varient = element1;
        let prices = [];
        let qtys = [];
        let skus = [];
          
        let input_val1 = $(`input[name=price_${varient.name}]`).val();
        prices.push(input_val1);
        
        let input_val2 = $(`input[name=qty_${varient.name}]`).val();
        qtys.push(input_val2);
        
        let input_val3 = $(`input[name=sku_${varient.name}]`).val();
        skus.push(input_val3);
        
        varient.price = prices;
        varient.quantity = qtys;
        varient.sku = skus;
        updatedVarients.push(varient);
      } else {
        let varient = element1;
        let prices = [];
        let qtys = [];
        let skus = [];
        
        $(varient.values).each(function(i, attrVal){         
          let input_val = $(`input[name=price_${varient.name}-${attrVal}]`).val();
          prices.push(input_val);
        });
        
        $(varient.values).each(function(i, attrVal){
          let input_val = $(`input[name=qty_${varient.name}-${attrVal}]`).val();
          qtys.push(input_val);
        });
        
        $(varient.values).each(function(i, attrVal){
          let input_val = $(`input[name=sku_${varient.name}-${attrVal}]`).val();
          skus.push(input_val);
        });
        
        varient.price = prices;
        varient.quantity = qtys;
        varient.sku = skus;
        updatedVarients.push(varient);
      }
    });
    formData.append('varient_data', JSON.stringify(updatedVarients));
  }
  // varient data end

  // for (var pair of formData.entries()) {
  //   console.log(pair[0] + ': ' + pair[1]);
  // }
  // return

  if (status) {
    $.ajax({
      //url: ApiPlugin + "/ecommerce/product/generate_multiple_products",
      url: ApiPlugin + "/ecommerce/product/add_product",
      type: "POST",
      data: formData,
      contentType: false,
      processData: false,
      beforeSend: function (xhr) {
        xhr.setRequestHeader("Authorization", "Bearer " + strkey);
        xhr.setRequestHeader("menu-uuid", menu_id);
        imgload.show();
      },
      success: function (response) {
        imgload.hide();
        if (response.status_code == 200) {
          let title = response.message;
          // showToast(title, 'Success', 'success', "?P=product&M="+menu_id);
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

$(".input-focus").on("focus", function(){
  $(this).select();
});