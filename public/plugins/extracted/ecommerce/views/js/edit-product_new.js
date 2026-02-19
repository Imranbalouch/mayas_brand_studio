document.title = "Dashboard | Edit Product";
var urlParams = new URLSearchParams(window.location.search);
var productID = urlParams.get('id');

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
  console.log('Triggered locationId:', locationId); 
    makeVariationsTempArray(); 
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
                        console.log("Variation Loaded");
                        
                        editVarientCard(variantsEditDataCards,variantsEditData);
                      // load_locations();
                      // await load_locations();
                        //updateTableValues(variantsEditData);
                        //console.log(variantsEditData);
                      }
              } 
        },
        error: function (xhr, status, error) {
          console.error('Error fetching categories:', status, error);
        } 
        });
 
}