document.title = "Dashboard | Add Product";
var urlParams = new URLSearchParams(window.location.search);
var productID = urlParams.get('id');
var all_active_locations=[];
 
$(document).ready(function () {
  get_active_categories();
  get_active_countries();
  get_active_collection();
  get_active_vendor(); 
  // get_active_tags();
  get_active_type();
  get_active_location();
  get_active_channel();
  get_active_countries1();
  get_active_markets();
  get_active_v_location();
   initializeSelect2();
  loadVatRates();

  $(".view-base-url").html(get_view_base_url());
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


function slugify(text) {
    return text
      .toString()
      .toLowerCase()
      .trim()
      .replace(/\s+/g, '-')        // Replace spaces with -
      .replace(/[^\w\-]+/g, '')    // Remove all non-word chars
      .replace(/\-\-+/g, '-');     // Replace multiple - with single -
  }
  
  $('#name').on('input', function () {
    const slug = slugify($(this).val());
    $('#seoLink').val(slug);
  });

$("#continue_selling_out_of_stock").on("change", function () {

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

        parentDropdown.selectpicker("refresh");

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
        parentDropdownCollection.selectpicker("destroy");
        response.data.forEach(category => {
          // console.log(response.data);
          let spaces = '-'.repeat(category.level);
          parentDropdownCollection.append(
            `<option value="${category.uuid}">${spaces} ${category.name}</option>`
          );
        });
        
        parentDropdownCollection.selectpicker();
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
        const parentCountryDropdown = $('#simple_country_id');
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

// function get_active_tags() {
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
//         parenttagsDropdown.empty(); // Clear existing options
        
//         response.data.forEach(tags => {
//           // console.log(response.data);
//           parenttagsDropdown.append(
//             `<option value="${tags.name}"> ${tags.name}</option>`
//           );
//          // console.log(tags.name);
//         });
//         //console.log('a');
//         parenttagsDropdown.selectpicker();
        
//       } else {
//         console.error('Unexpected response:', response);
//       }
//     },
//     error: function (xhr, status, error) {
//       console.error('Error fetching categories:', status, error);
//     }
//   });

// }

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
                  id="${location.location_name}" name="getlocationId[]" ${location?.is_default == 1 ? "checked" : ""}>
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
        $("#marketNames").html(`<div class="mb-3 fv-row"><span class="circleBefore">${formattedMarkets}</span></div>`);

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

// document.querySelectorAll("#addProductFrom input").forEach(function (input) {
//   input.addEventListener("input", function () {
//     input.parentElement.classList.remove("error");
//     input.parentElement.parentElement.querySelector(".error-txt").innerHTML = "";
//     input.parentElement.querySelector(".error-txt").innerHTML = "";
//   });
// });

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

document.getElementById("sipmle_shipping_weight").addEventListener("input", function () {
  let inputValue = parseFloat(this.value);

  if (inputValue < 0) {
    this.value = "0.1";
    showError(this, "Negative weight is not allowed. Automatically set to 0.1");
  } else {
    //clearError(this);
  }
});

$(document).ready(function () {

//   $('#type').select2({
//     tags: true,
//     allowClear: true,
//     tokenSeparators: [',', ' '],
//   });

//   $('#vendor').select2({
//     tags: true,
//     allowClear: true,
//     tokenSeparators: [',', ' '],
//   });

//   $('#collection').select2({
//     tags: true,
//     allowClear: true,
//     tokenSeparators: [',', ' '],
//   });

//   $('#tags').select2({
//     tags: true,
//     allowClear: true,
//     tokenSeparators: [',', ' '],
//   });

//   $('#category_id').select2({
//     tags: true,
//     allowClear: true,
//     tokenSeparators: [',', ' '],
//   });

//   setTimeout(() => {
//     $('#GroupBy').select2({
//       allowClear: false,
//       minimumResultsForSearch: Infinity
//     });
//   }, 100);

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
  const checkbox = document.getElementById("physical_product_enabled");
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
  $("#track_quantity").change(function () {
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

    // track_quantity checkbox ka current state check karna
    if ($("#track_quantity").is(":checked")) {
      newLocation.find(".not-tracked").hide();
      newLocation.find(".tracked, .out-of-stock").show();
    }

    $('.main-add-location').append(newLocation);
  });

  $('input[name=warehouse_location_id]').val(getPushlocation.join(","));
}
inventorySection();
function setEditorValue(content) {
  if (editorInstance) {
      editorInstance.setData(content);
  } else {
      console.error('Editor not initialized');
  }
}

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



// Function to load VAT rates from API
function loadVatRates() {
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
      if (response.status_code === 200) {
        const vatDropdown = $('#vat_id');
        vatDropdown.empty();
        vatDropdown.append('<option value="">Select VAT Rate</option>');
        
        response.data.forEach(vat => {
          vatDropdown.append(
            `<option value="${vat.uuid}">${vat.name} (${vat.rate}%)</option>`
          );
        });
        
        // Initialize Select2
        vatDropdown.select2({
          placeholder: "Select VAT Rate",
          allowClear: true,
          width: '100%' // Ensure full width for consistency
        });
      } else {
        console.error('Error loading VAT rates:', response);
      }
    },
    error: function (xhr, status, error) {
      console.error('Error fetching VAT rates:', status, error);
    }
  });
}

function getEditorValue () {
  return editorInstance.getData();
}
$("#addProductFrom").on("submit", function (e) {  
  e.preventDefault();
  makeVariationsTempArray();  
  updateTableValues();
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
    compare_price.parent().addClass('error');
    compare_price.parent().find('.error-txt').html("Compare at Price can't be more than 1000000");
    errors.push("Compare at Price can't be more than 1000000");
    status = false;
  }
 
  if (comparepriceValue < priceValue) {
   // alert(comparepriceValue);
    compare_price.parent().addClass('error');
    compare_price.parent().find('.error-txt').html("Compare at Price can't be less than price");
    errors.push("Compare at Price can't be less than price");
    status = false; 
  }
//alert(status);
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
  let tags = $('#tags').val() || [];
  let tagsString = Array.isArray(tags) ? tags.join(',') : tags;

  let formData = new FormData(this); 
  let editorVal = getEditorValue();
  formData.append('description', editorVal); 
  formData.append('tags', tagsString);
 
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

$(".input-focus").on("focus", function(){
  $(this).select();
});





///////////////// Edit Varient Table Row 
$(".variants-table").on("click", ".v_table .tr:not(.has-child) .v_collapse", function(e) {
  e.preventDefault();    
  $("#editVariantValue").modal("show");
  //console.log($(this).parent(".tr").next().find(".td.d-none .input"));
  let m_sku='';
  let m_variant_sku='';
  let m_price=0.00;
  let m_cost_price=0.00;
  let m_qty=0;
  let m_barcode=0;
  let m_hscode=0;
  $(this).parents(".tr").find(".td").each((i, element) => {
   // console.log($(element).find(".sku").val()); 
      if($(element).find(".sku").val()!=undefined){
          m_sku=$(element).find(".sku").val();
      }
      if($(element).find(".variant-sku").val()!=undefined){
          m_variant_sku=$(element).find(".variant-sku").val();
      }
      
      if($(element).find(".price").val()!=undefined){
        m_price=$(element).find(".price").val();
      }
      if($(element).find(".cost-price").val()!=undefined){
        m_cost_price=$(element).find(".cost-price").val();
      }
      if($(element).find(".available-qty").val()!=undefined){
        m_qty=$(element).find(".available-qty").val();
      } 
      if($(element).find(".barcode").val()!=undefined){
        m_barcode=$(element).find(".barcode").val();
      } 
      if($(element).find(".hscode").val()!=undefined){
        m_hscode=$(element).find(".hscode").val();
      }
  });
 // console.log(m_sku);
  $("#variant_edit_sku").html(m_sku.replace('sku_',''));
  $("#edit_variant_price").val(m_price);
  $("#edit_variant_cost_per_item").val(m_cost_price).trigger("input");
  $("#edit_variant_sku").val(m_variant_sku);
  $("#edit_variant_barcode").val(m_barcode);
  $("#edit_variant_hscode").val(m_hscode);
});


$('#edit_variant_done').on("click", function(e) {
  e.preventDefault(); 
  edit_variant_done();
});

function edit_variant_done(){
  _price=$('#edit_variant_price').val();
  _cost_per_item=$('#edit_variant_cost_per_item').val();
  _sku=$('#variant_edit_sku').html();
  _Vsku=$('#edit_variant_sku').val();
  _barcode=$('#edit_variant_barcode').val();
  _hscode=$('#edit_variant_hscode').val();
  //console.log(_sku,_cost_per_item);
 
  $('input[name=price_'+_sku+']').val(_price);
  $('input[name=cost_price_'+_sku+']').val(_cost_per_item);
  $('#lbl_sku_'+_sku+'').html(_Vsku);
  $('input[name=variant_sku_'+_sku+']').val(_Vsku);
  $('input[name=barcode_'+_sku+']').val(_barcode);
  $('input[name=hscode_'+_sku+']').val(_hscode);
  //console.log($('input[name=cost_price_'+_sku+']').val());
  $(".price").trigger('change');
  $(".cost-price").trigger('change');
  $(".variant-sku").trigger('change');
  $(".barcode").trigger('change');
  $(".hscode").trigger('change');
  
  updateVariationsTempArray(); 
 
}



$("#edit_variant_price, #edit_variant_cost_per_item").on("input", calculateProfitAndMarginEdit);
  $("#edit_variant_price, #edit_variant_cost_per_item, #edit_variant_profit_price").on("blur", function () {
    var value = parseFloat($(this).val()) || 0;
    if ($(this).val() == "") {
      $(this).val("0.00")
    }
    $(this).val(value.toFixed(2));
  });

  function calculateProfitAndMarginEdit() {
    var price = parseFloat($("#edit_variant_price").val()) || 0;
    var cost = parseFloat($("#edit_variant_cost_per_item").val()) || 0;
  
    if (price > 0 && cost > 0) {
      var profit = price - cost;
      var margin = (profit / price) * 100;
  
      $("#edit_variant_profit_price").val(profit.toFixed(2));
      if (margin % 1 === 0) {
        $("#edit_variant_margin_price").val(margin + "%");
      } else {
        $("#edit_variant_margin_price").val(margin.toFixed(1) + "%");
      }
    } else {
      $("#edit_variant_profit_price").val(price);
      $("#edit_variant_margin_price").val("100%");
    }
  } 

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
}
  ///////////////// Edit Varient Table Row