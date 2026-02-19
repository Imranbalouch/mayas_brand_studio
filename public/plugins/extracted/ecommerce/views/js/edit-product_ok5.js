var urlParams = new URLSearchParams(window.location.search);
var productID = urlParams.get('id');
var all_active_locations=[];
$(document).ready(function () {
  get_locations();
  get_recordByID(productID);
});
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

$(document).on('change', '#v_locations', async function () {
  const locationId = $(this).val(); 
  //console.log('Triggered locationId:', locationId); 
    makeVariationsTempArray(); 
    updateTableValues(variantsEditData);
});
 

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
                //alert(response.data.type);
                let data = response.data;
                $("input[name='name']").val(response.data.name);
          
 
                const warehouse_location_ids = data.varient_market_location !== null && data.varient_market_location.split(",");
               // console.log(warehouse_location_ids);
                //$('#v_locations').val(warehouse_location_ids).trigger('change');
                // Edit Varient
                      if (response.data.choice_options !== "" || response.data.choice_options !== "null") {
                        const variantsEditData = JSON.stringify(response.data.stocks);
                        const variantsEditDataCards = JSON.parse(response.data.choice_options);
                        //const stocks =response.data.stocks;
                       // console.log("Variation Loaded");
                        
                        editVarientCard(variantsEditDataCards,variantsEditData);
                        editVarientTableData(variantsEditData);
                       // console.log($('#v_locations').val());
                        $('#v_locations').trigger('change');
                      // load_locations();
                      // await load_locations();
                        //  makeVariationsTempArray(); 
                        //  updateTableValues(variantsEditData);
                        //console.log(variantsEditData);
                      }
              } 
        },
        error: function (xhr, status, error) {
          console.error('Error fetching categories:', status, error);
        } 
        });
 
}


$("#addProductFrom").on("submit", function (e) {
//  makeVariationsTempArray();  
  //updateTableValues();
   var urlParams = new URLSearchParams(window.location.search);
   var id = urlParams.get('id');  
   e.preventDefault();
 
   let status = true;
   if ($('input[name="unit_price"]').val() === "") {
    $('input[name="unit_price"]').val(0.00);
   }
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
 