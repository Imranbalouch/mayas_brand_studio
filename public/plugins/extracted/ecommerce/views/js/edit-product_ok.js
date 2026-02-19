var urlParams = new URLSearchParams(window.location.search);
var productID = urlParams.get('id');
$(".seoBtn").on("click", function (e) {
  e.preventDefault();

  $(this).hide();
  $("#seoTab").show();

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

// document.querySelectorAll("#addProductFrom input").forEach(function (input) {
//   input.addEventListener("input", function () {
//     input.parentElement.classList.remove("error");
//     input.parentElement.parentElement.querySelector(".error-txt").innerHTML = "";
//     input.parentElement.querySelector(".error-txt").innerHTML = "";
//   });
// });

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

  $("#Price, #cost_per_item").on("input", calculateProfitAndMargin);
  $("#Price, #cost_per_item, #compare_price").on("blur", function () {
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
      $("#margin_price").val(margin + "%");
    } else {
      $("#margin_price").val(margin.toFixed(1) + "%");
    }
  } else {
    $("#profit_price").val(price);
    $("#margin_price").val("100%");
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
  $("#mySelect").select2({
    placeholder: function () {
      $(this).data('placeholder');
    },
    width: '100%',
    theme: 'bootstrap-5',
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

function inventorySection() {
  $("#Trackquantity").change(function () {
    if ($(this).is(":checked")) {
      $(".out-of-stock").show();
      $("#notTracked").addClass("d-none");
      $("#Tracked").removeClass("d-none");
    } else {
      $("#notTracked").removeClass("d-none");
      $("#Tracked").addClass("d-none");
      $(".out-of-stock").hide();
    }
  });

  $('#locationDone1').on('click', function () {
    mainaddlocation();
  });

}
inventorySection();
$(document).ready(function () {
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
        $('#v_locations').empty();
        $('#v_locations').append(`<option value="all">All Locations</option>`); 
        response.data.forEach(element => {
          $('#v_locations').append(`<option value="${element.uuid}">${element.location_name}</option>`);
          
        });

        $("#v_locations").select2({
          allowClear: false,
          minimumResultsForSearch: Infinity
        });
        
      //  makeVariationActions(variantsEditDataTableParsed, variantsEditDataCardsParsed);
       // variationActions();
        //showHideAnotnerVariantBtn();
        
      } else {
        console.error('Unexpected response:', response);
      }
    },
    error: function (xhr, status, error) {
      console.error('Error fetching categories:', status, error);
    }
  });
  var urlParams = new URLSearchParams(window.location.search);
  var id = urlParams.get('id');
  if (id) {
    $.ajax({
      url: ApiPlugin + "/ecommerce/product/edit_product/" + id,
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
          //alert(response.data.type);
          let data = response.data;
          $("input[name='name']").val(response.data.name);
          $("input[name='images']").val(response.data.images);
          response.data.description != null && setEditorValue(response.data.description);
          $("input[name='unit_price']").val(response.data.unit_price.toFixed(2));
          $("input[name='compare_price']").val(response.data.compare_price);
          $("input[name='cost_per_item']").val(response.data.cost_per_item !== null ? response.data.cost_per_item : "0.00");
          $("input[name='profit_price']").val(response.data.profit_price);

          if (response.data.margin_price !== null && response.data.margin_price !== "") {
            // console.log(response.data.margin_price !== "");
            if (response.data.profit_price / response.data.unit_price * 100 % 1 === 0) {
              // $("input[name='margin_price']").val(margin +"%");
              $("input[name='margin_price']").val(response.data.margin_price + "%");
            } else {
              // $("input[name='margin_price']").val(margin.toFixed(1) +"%");
              $("input[name='margin_price']").val(response.data.margin_price.toFixed(1) + "%");
            }
          }
          $("input[name='sku']").val(response.data.sku);
          $("input[name='barcode']").val(response.data.barcode);
          $("input[name='weight']").val(response.data.weight);
          $("select[name='weight_unit']").val(response.data.weight_unit);
          $("select[name='country_id']").val(response.data.country_id);
          $("select[name='type']").val(response.data.type).trigger('change');
          $("select[name='vendor']").val(response.data.type).trigger('change');
          $("select[name='collection']").val(response.data.type).trigger('change');
          $("select[name='tags']").val(response.data.tags);
          $("select[name='category_id']").val(response.data.type).trigger('change');
          $('#putdate').val(response.data.publishingDate);
          $('#putdate').val(response.data.publishingTime);
          $(".site-name").text(response.data.vendor);
          $(".site-title").text(response.data.meta_title);
          $(".site-link span").text(response.data.slug);
          $(".site-desc").text(response.data.meta_description);
          $(".site-desc.price").text(`Rs ${response.data.current_stock.toFixed(2)} PKR`);
          $("input[name='meta_title']").val(response.data.meta_title);
          $("textarea[name='meta_description']").val(response.data.meta_description);
          $("input[name='slug']").val(response.data.slug);

          response.data.tax_enabled == "1" && $('.tax_enabled').prop('checked', true);
          response.data.inventory_track_enabled == "1" && $('.inventory_track_enabled').prop('checked', true);
          response.data.selling_stock_enabled == "1" && $('.selling_stock_enabled').prop('checked', true);

          filemanagerImagepreview();

          let saleChannelArr = response.data.sale_channel_id;

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

         
          
          // if (response.data.varient_data !== "" || response.data.varient_data !== "null") {

          //   const variantsEditDataTable = JSON.parse(response.data.varient_data);
          //   const variantsEditDataCards = JSON.parse(response.data.varient_data_view);
          //   const variantsEditDataTableParsed = JSON.parse(variantsEditDataTable);
          //   const variantsEditDataCardsParsed = JSON.parse(variantsEditDataCards);

          //   if(variantsEditDataCardsParsed.length != 0) {
          //     $(variantsEditDataCardsParsed).each(function(i, element){
          //       let html1 = `<div class="variants-option-card">
          //                     <div class="drag drag-card"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" width="16" height="16"><path d="M5.5 2.5a1 1 0 0 0-1 1v.5a1 1 0 0 0 1 1h.5a1 1 0 0 0 1-1v-.5a1 1 0 0 0-1-1z"></path><path d="M5.5 6.75a1 1 0 0 0-1 1v.5a1 1 0 0 0 1 1h.5a1 1 0 0 0 1-1v-.5a1 1 0 0 0-1-1z"></path><path d="M4.5 12a1 1 0 0 1 1-1h.5a1 1 0 0 1 1 1v.5a1 1 0 0 1-1 1h-.5a1 1 0 0 1-1-1z"></path><path d="M10 2.5a1 1 0 0 0-1 1v.5a1 1 0 0 0 1 1h.5a1 1 0 0 0 1-1v-.5a1 1 0 0 0-1-1z"></path><path d="M9 7.75a1 1 0 0 1 1-1h.5a1 1 0 0 1 1 1v.5a1 1 0 0 1-1 1h-.5a1 1 0 0 1-1-1z"></path><path d="M10 11a1 1 0 0 0-1 1v.5a1 1 0 0 0 1 1h.5a1 1 0 0 0 1-1v-.5a1 1 0 0 0-1-1z"></path></svg></div>`

          //       html1 += `<div class="variants-option-prev" style="display: none;">
          //                   <div class="option-prev-name">${element.name}</div>
          //                   <div class="option-prev-values">`;

          //       element.values.forEach(element1 => {
          //         html1 += `<span>${element1}</span>`;
          //       });

          //       html1 += `</div>
          //                   </div>
          //                   <div class="variants-option-inputs">
          //                     <div class="input mb-2">
          //                       <label class="form-label" for="name"><g-t>Option name</g-t></label>
          //                       <div class="input-wrapper">
          //                         <input type="text" class="form-control" value="${element.name}" placeholder="Add Name">
          //                       </div>
          //                       <span class="error-txt">Option name is required.</span>
          //                     </div>
          //                     <label class="form-label" for="name"><g-t>Option values</g-t></label>
          //                     <div class="another-inputs-wrapper">`
                      
          //       element.values.forEach(element1 => {
          //         html1 += `<div class="input value-input mb-2">
          //                     <div class="drag drag-input"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" width="16" height="16"><path d="M5.5 2.5a1 1 0 0 0-1 1v.5a1 1 0 0 0 1 1h.5a1 1 0 0 0 1-1v-.5a1 1 0 0 0-1-1z"></path><path d="M5.5 6.75a1 1 0 0 0-1 1v.5a1 1 0 0 0 1 1h.5a1 1 0 0 0 1-1v-.5a1 1 0 0 0-1-1z"></path><path d="M4.5 12a1 1 0 0 1 1-1h.5a1 1 0 0 1 1 1v.5a1 1 0 0 1-1 1h-.5a1 1 0 0 1-1-1z"></path><path d="M10 2.5a1 1 0 0 0-1 1v.5a1 1 0 0 0 1 1h.5a1 1 0 0 0 1-1v-.5a1 1 0 0 0-1-1z"></path><path d="M9 7.75a1 1 0 0 1 1-1h.5a1 1 0 0 1 1 1v.5a1 1 0 0 1-1 1h-.5a1 1 0 0 1-1-1z"></path><path d="M10 11a1 1 0 0 0-1 1v.5a1 1 0 0 0 1 1h.5a1 1 0 0 0 1-1v-.5a1 1 0 0 0-1-1z"></path></svg></div>
          //                     <div class="input-wrapper">
          //                       <input type="text" class="form-control" value="${element1}" placeholder="Add value">
          //                       <span class="icon" style="fill: #8a8a8a;"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16"><path d="M9.5 6.25a.75.75 0 0 1 .75.75v4.25a.75.75 0 0 1-1.5 0v-4.25a.75.75 0 0 1 .75-.75"></path><path d="M7.25 7a.75.75 0 0 0-1.5 0v4.25a.75.75 0 0 0 1.5 0z"></path><path fill-rule="evenodd" d="M5.25 3.25a2.75 2.75 0 1 1 5.5 0h3a.75.75 0 0 1 0 1.5h-.75v5.45c0 1.68 0 2.52-.327 3.162a3 3 0 0 1-1.311 1.311c-.642.327-1.482.327-3.162.327h-.4c-1.68 0-2.52 0-3.162-.327a3 3 0 0 1-1.311-1.311c-.327-.642-.327-1.482-.327-3.162v-5.45h-.75a.75.75 0 0 1 0-1.5zm1.5 0a1.25 1.25 0 0 1 2.5 0zm-2.25 1.5h7v5.45c0 .865-.001 1.423-.036 1.848-.033.408-.09.559-.128.633a1.5 1.5 0 0 1-.655.655c-.074.038-.225.095-.633.128-.425.035-.983.036-1.848.036h-.4c-.865 0-1.423-.001-1.848-.036-.408-.033-.559-.09-.633-.128a1.5 1.5 0 0 1-.656-.655c-.037-.074-.094-.225-.127-.633-.035-.425-.036-.983-.036-1.848z"></path></svg></span>
          //                     </div>
          //                     <span class="error-txt">Option value is required.</span>
          //                   </div>`;
          //       });

          //       html1 += `</div>
          //                   </div>
          //                   <div class="row option-card-action-parent">
          //                     <div class="col-12 option-card-action">
          //                       <a href="" class="del">Delete</a>
          //                       <a href="" class="done">Done</a>
          //                     </div>
          //                   </div>
          //                 </div>`

          //       $("#VariantsOptionCards .cards").append(html1);
          //     });

          //     $(".variants-option-card").find(".variants-option-prev").show();
          //     $(".variants-option-card").find(".variants-option-inputs").hide();
          //     $(".variants-option-card").find(".option-card-action-parent").hide();
          //     $(".variants-option-card").addClass("prev");
                const warehouse_location_ids = data.varient_market_location !== null && data.varient_market_location.split(",");
                console.log(warehouse_location_ids);
                $('#v_locations').val(warehouse_location_ids).trigger('change');
                // Edit Varient
                if (response.data.choice_options !== "" || response.data.choice_options !== "null") {
                  const variantsEditData = JSON.stringify(response.data.stocks);
                  const variantsEditDataCards = JSON.parse(response.data.choice_options);
                  //const stocks =response.data.stocks;
                  console.log("Variation Loaded");
                  editVarientCard(variantsEditDataCards);
                  updateTableValues(variantsEditData);
                  //console.log(variantsEditData);
                }
          //   }

          //   $("#AddAnotherOption").hide();
          //   $("#AddVariants").addClass("d-none");
          //   $("#VariantsOptionCards").removeClass("d-none");
          //   $(".variants-table").removeClass("d-none");

          //   initVariationAvtions();
            
          // }
          // Edit Varient End

          ////////////////////////////////
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
                parenttypeDropdown.empty();
                parenttypeDropdown.html('<option value="" disabled>Select an option</option>');

                response.data.forEach(types => {
                  selected = types.type === data.type ? 'selected' : '';
                  parenttypeDropdown.append(`<option value="${types.type}">${types.type}</option>`);
                });
              } else {
                console.error('Unexpected response:', response);
              }
            },
            error: function (xhr, status, error) {
              console.error('Error fetching categories:', status, error);
            }
          });
          ////////////////////////////////////
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
                parentvendorDropdown.html('<option value="" disabled>Select an option</option>'); // New placeholder
                response.data.forEach(vendor => {
                  selected = vendor.vendor === data.vendor ? 'selected' : '';
                  parentvendorDropdown.append(
                    `<option value="${vendor.vendor}"  ${selected}>${vendor.vendor}</option>`
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
          ///////////////////////////////////////////
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
                parenttagsDropdown.empty();

                const selectedTags = JSON.parse(data.tags);
                response.data.forEach(tag => {                  
                  const isSelected = selectedTags != null && selectedTags.includes(tag.name) ? 'selected' : '';
                  parenttagsDropdown.append(
                    `<option value="${tag.name}" ${isSelected}>${tag.name}</option>`
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
          //////////////////////////////////////////////////////////////
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
                  const isSelected = data.category_id.includes(category.uuid);
                  let spaces = '-'.repeat(category.level); // Adding spaces based on the category level
                  parentDropdown.append(
                    `<option value="${category.uuid}" ${isSelected ? "selected" : ""}>${spaces} ${category.name}</option>`
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
          //////////////////////////////////////////////////////////////
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
                  const isSelected = data.collection_id.includes(collection.uuid);
                  let spaces = '-'.repeat(collection.level); // Adding spaces based on the collection level
                  parentCollection.append(
                    `<option value="${collection.uuid}" ${isSelected ? "selected" : ""}>${spaces} ${collection.name}</option>`
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
          ////////////////////////////////////////////////////////////////////
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
                parentCountryDropdown.html('<option value="" disabled>Select an option</option>'); // New placeholder
                response.data.forEach(country => {
                  selected = country.uuid === data.country_id ? 'selected' : '';
                  parentCountryDropdown.append(
                    `<option value="${country.uuid}" ${selected}> ${country.name}</option>`
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
          ///////////////////////////////////////////////////////////////////////////////
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


                $("#sales-checkboxs input[type='checkbox']").each(function () {
                  let checkboxValue = $(this).val().trim();

                  if (Array.isArray(saleChannelArr)) {
                    if (saleChannelArr.includes(checkboxValue)) {
                      $(this).prop("checked", true);
                    }
                  }

                });

                let htmlLi1 = ""
                response.data.forEach(channels => {
                  saleChannelArr.forEach(element => {
                    if (element == channels.uuid) {

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

                    }
                  });
                });
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
          ///////////////////////////////////////////////////////////////////////////////
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
                // $("#marketNames").html(formattedMarkets);

                $("#marketNames").empty();
                response.data.forEach(element => {
                  // if () {
                    $("#marketNames").append(`
                      <div class="mb-3 fv-row"><span class="circleBefore active">${element.market_name}</span></div>
                    `);
                  // }
                });
        
                $("#manageMarkets .modal-body").empty();
                response.data.forEach(element => {
                  $("#manageMarkets .modal-body").append(`
                    <div class="sales-checkbox-01 mb-3">
                      <div class="form-check mb-4 align-items-start">
                        <label class="form-check-label">
                          <input class="form-check-input" type="checkbox" value="online-store" checked>
                          <span>International</span>
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
          ///////////////////////////////////////////////////////////////////////////////
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
                
                const warehouse_location_id = $('#warehouse_location_id');
                const location_checkboxs = $('#location-checkboxs');
                location_checkboxs.empty();
                warehouse_location_id.empty(); // Clear existing options
                let htmlLocation = "";
                $("#manage-sales-total").html(response.data.length);
                response.data.forEach(location => {
                  htmlLocation += `<div class="form-check mb-4">
                      <input class="form-check-input" type="checkbox" value="${location.uuid}"
                        id="${location.location_name}" name="getlocationId[]">
                      <label class="form-check-label" for="${location.location_name}">
                        <span>${location.location_name}</span>
                      </label>
                    </div>`;
                });
                location_checkboxs.html(htmlLocation);
                
                const warehouse_location_ids = data.warehouse_location_id !== null && data.warehouse_location_id.split(",");
                $("#location-checkboxs input[type='checkbox']").each(function () {
                  let checkboxValue = $(this).val().trim();

                  if (Array.isArray(warehouse_location_ids)) {
                    if (warehouse_location_ids.includes(checkboxValue)) {
                      $(this).prop("checked", true);
                    }
                  }

                });
                
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
              } else {
                console.error('Unexpected response:', response);
              }
            },
            error: function (xhr, status, error) {
              console.error('Error fetching categories:', status, error);
            }
          });

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

$("#addProductFrom").on("submit", function (e) {
 makeVariationsTempArray();  
 updateTableValues();
  var urlParams = new URLSearchParams(window.location.search);
  var id = urlParams.get('id');
  //updateTableValues();
  //return false;
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

  let status = true;

  if ($('input[name="name"]').val() === "") {
    $('input[name="name"]').parent().addClass('error');
    $('input[name="name"]').parent().find('.error-txt').html("Title can’t be blank");
    $('#formError').show();
    window.scrollTo(0, 0);
    status = false;
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

  let formData = new FormData(this);
  // alert($("input[name='description']").val());
  let editorVal = getEditorValue();
  formData.append('description', editorVal);
  formData.append('product_id', id);

  if (status) {
    $.ajax({
      //url: ApiPlugin + "/ecommerce/product/generate_multiple_products",
      url: ApiPlugin + "/ecommerce/product/update_product",
      type: "POST",
      data: formData,
      contentType: false,
      processData: false,
      beforeSend: function (xhr) {
        xhr.setRequestHeader("Authorization", "Bearer " + strkey);
        xhr.setRequestHeader("menu-uuid", menu_id);
        xhr.setRequestHeader("uuid", id);
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

$(".input-focus").on("focus", function () {
  $(this).select();
});

$(document).ready(function () {
  $('#adjustAvailable, #adjustReason, #adjustReason2').select2({
    allowClear: false,
    width: '100%',
    minimumResultsForSearch: Infinity
  });

  $('#adjustAvailable').on('change', function () {
    var selectedValue = $(this).val();

    var newOptions = [];
    if (selectedValue == 'adjust') {

      $("#AdjustAvailableQty").addClass('d-none');
      $("#AdjustAvailableByNew").removeClass('d-none');

      newOptions = [
        { id: 'correction', text: 'Correction (default)' },
        { id: 'cycle_count_available', text: 'Count' },
        { id: 'received', text: 'Received' },
        { id: 'restock', text: 'Return restock' },
        { id: 'damaged', text: 'Damaged' },
        { id: 'shrinkage', text: 'Theft or loss' },
        { id: 'promotion', text: 'Promotion or donation' },
      ];
    } else if (selectedValue == 'move') {

      $("#AdjustAvailableQty").removeClass('d-none');
      $("#AdjustAvailableByNew").addClass('d-none');

      newOptions = [
        { id: 'other', text: 'Other (default)' },
        { id: 'damaged', text: 'Damaged' },
        { id: 'quality_control', text: 'Quality control' },
        { id: 'safety_stock', text: 'Safety stock' },
      ];
    }

    $('#adjustReason').empty();

    $.each(newOptions, function (index, option) {
      $('#adjustReason').append(new Option(option.text, option.id, false, false));
    });

    $('#adjustReason').select2({
      allowClear: false,
      minimumResultsForSearch: Infinity,
      width: '100%'
    });
  });

  $(".inventory-dropdown .add-inventory").on("click", function (e) {
    e.preventDefault();
    $(this).parents(".unavailable-parent").find("> div").addClass("d-none");
    $(this).parents(".unavailable-parent").find(".unavailable-damaged").removeClass("d-none");
  });

  $(".inventory-dropdown .move-to-available").on("click", function (e) {
    e.preventDefault();
    $(this).parents(".unavailable-parent").find("> div").addClass("d-none");
    $(this).parents(".unavailable-parent").find(".unavailable-move-to-available").removeClass("d-none");
  });

  $(".inventory-dropdown .delete-damaged").on("click", function (e) {
    e.preventDefault();
    $(this).parents(".unavailable-parent").find("> div").addClass("d-none");
    $(this).parents(".unavailable-parent").find(".unavailable-delete-damaged").removeClass("d-none");
  });

  $(".inventory-dropdown.unavailable .dropdown-toggle").on("click", function () {
    $(this).parent().find(".dropdown-menu > div").addClass("d-none");
    $(this).parent().find(".unavailable-list").removeClass("d-none");
  });
});