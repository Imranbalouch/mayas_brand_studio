document.title = "Dashboard | Inventory";
let PageNo = 1;
let PageSize = 10;
let totalRecord = 0;
let PageLength = 0;
let Search = '';
let keyupTimer;
let inventoryCurrentPage = 1;
let inventoryPerPage = 10;
let inventoryTotalRecords = 0;
let inventoryLastPage = 0;
$(document).on('click', '.open-inventory-droupdown', function (e) {
  e.stopPropagation(); // Prevent event bubbling
  const $dropdown = $(this).closest('.custom-dropdown-main');
  $('.droupdown-body').not($dropdown.find('.droupdown-body')).slideUp(); // Close others
  $dropdown.find('.droupdown-body').slideToggle();
});
$(document).on('click', function () {
  $('.droupdown-body').slideUp();
});

$(document).ready(function () {

  $('#pageLengthSelect').on('change', function () {
    inventoryPerPage = $(this).val();
    get_inventories($('#warehouse_location_id').val(), 1);
});

// Add search functionality
$('#custom_search').on('keypress', function (e) {
    if (e.which === 13) { // 13 is the Enter key
        e.preventDefault();
        get_inventories($('#warehouse_location_id').val(), 1);
    }
});

// Add a button for search if needed
$('.search-icon-button .search').on('click', function () {
    get_inventories($('#warehouse_location_id').val(), 1);
});
  
  $(document).on("click", ".create-transfer", function (e) {
  e.preventDefault();
  let page = 'create-transfer';
  window.location.assign('?P=' + page + '&M=' + menu_id);
});

$(document).on("click", ".create-purchase-order", function (e) {
  e.preventDefault();
  let page = 'create-purchase-order';
  window.location.assign('?P=' + page + '&M=' + menu_id);
});


  btnnew = $('#btn_new');
  btnnew.hide();
  btnnew.click(function (e) {
    e.preventDefault();
    let page = 'productadd';
    window.location.assign('?P=' + page + '&M=' + menu_id);
  });

  $("#custom_search").on("input", function () {
    if ($(this).val().trim() !== "") {
      $(".search-icon-button a:nth-child(2)").addClass("active");
    } else {
      $(".search-icon-button a:nth-child(2)").removeClass("active");
    }
  });

  // Call Onload to initialize locations and inventories
  Onload();
});

function Onload() {
  get_locations();
}

$('#warehouse_location_id').on('change', function () {
  const location_id = $(this).val();
  default_warehouse_location(location_id);
  get_inventories(location_id);
});


function get_locations() {
  /// Load Locations of Warehouse
  $.ajax({
    url: ApiPlugin + '/ecommerce/warehouse/get_active_warehouse_locations', // API endpoint
    type: "GET", // HTTP method
    contentType: "application/json", // Request content type
    dataType: "json", // Expected response data type
    beforeSend: function (request) {
      request.setRequestHeader("Authorization", 'Bearer ' + strkey); // Add Authorization header
       imgload.show();
    },
    success: function (response) {
      if (response.status_code === 200) { // Check if the response is successful
         imgload.hide();
        const locationDropdown = $('#warehouse_location_id'); // Get the dropdown element
        locationDropdown.empty(); // Clear existing options

        // Check if response.data is an array and not empty
        if (Array.isArray(response.data) && response.data.length > 0) {
          // Map through the data and create <option> elements
          var default_location = localStorage.getItem('default_location');

          let selectedSet = false;
          const options = response.data.map(function (item) {
            let isSelected = '';
            if (!selectedSet && default_location === item.uuid) {
              isSelected = 'selected';
              selectedSet = true;
            }
            return `<option value="${item.uuid}" ${isSelected} data-subtext="${item.warehouse_name}">${item.location_name}</option>`;
          }).join('')

          locationDropdown.html(options); // Add the options to the dropdown
          locationDropdown.selectpicker('refresh'); // Refresh the Bootstrap Selectpicker if you're using it
           const location_id = $('#warehouse_location_id').val();
          if (location_id) {
            get_inventories(location_id);
          } else {
            console.warn('No location ID selected after loading locations.');
          }
        } else {
          console.warn('No data found in the response.'); // Log a warning if no data is found
        }
      } else {
        console.error('Unexpected response:', response); // Log an error for unexpected responses
      }
     
    },
    error: function (xhr, status, error) {
       imgload.hide();
      console.error('Error fetching warehouse locations:', status, error); // Log AJAX errors
    }
  });
  /// Load Locations of Warehouse  
}


function get_inventories(location_id = false, page = 1) {
  /// Inventories
  if (!location_id) {
    console.warn('No location ID provided.');
    return;
  }
  $('#Table_View').DataTable().clear().destroy();
  $('#buttons').html('');
  //location_id=$('#warehouse_location_id').val();
  $.ajax({
    url: ApiPlugin + '/ecommerce/inventory/get_inventory',
    type: "Get",
    data: { location_id: location_id, page: page,
    per_page: inventoryPerPage,
    search: $('#custom_search').val() },
   
    contentType: "application/json",
    dataType: "json",
    beforeSend: function (xhr) {
      xhr.setRequestHeader("Authorization", "Bearer " + strkey);
      xhr.setRequestHeader("menu-uuid", menu_id);
      imgload.show();
    },
    success: function (response) {

      if (response.status_code == 200) {
        imgload.hide();
        let action_button = '';
        let view_inventory_btn = '';
        let switch_button = '';

        //New
        if (Boolean(response.permissions['add'])) {
          btnnew.show();
        }

        //Update 
        if (Boolean(response.permissions["edit"])) {
          action_button += `<a href='javascript:;' class='modify_btn btn-edit' data-toggle='tooltip' title='Edit'>${getTranslation('edit')}</a> | `;
          view_inventory_btn += `<a href='javascript:;' class='modify_btn btn-edit btn-view-inventory'><span class="fa fa-user">View</span></a>`;
        }

        // //Delete
        if (Boolean(response.permissions["delete"])) {
          action_button += `<a href='javascript:;' class='modify_btn btn-delete' data-toggle='tooltip' title='Delete'>${getTranslation('delete')}</a> `;
        }


        if (Boolean(response.permissions["update"])) {
          switch_button = '';
        } else {
          switch_button = 'disabled';
        }

        if (response["data"] != null) {
            inventoryCurrentPage = response.pagination.current_page;
            inventoryLastPage = response.pagination.last_page;
            inventoryTotalRecords = response.pagination.total;
                      
            // Show inventory count
            $('#inventory_count').text(`Showing ${response.pagination.from}–${response.pagination.to} of ${response.pagination.total} inventories`);
            $('#inventory_count').show();
          const columnsConfig = [
            {
              data: null, // No data source
              // visible: false,
              render: function (data, type, row, meta) {
                return `
                  <input type="hidden" name="product_id" value="${row.inventory.product_id}">
                  <input type="hidden" name="location_id" value="${row.inventory.location_id}">
                  <input type="hidden" name="inventory_id" value="${row.inventory.id}">
                  <input type="hidden" name="inventory_sku" value="${row.inventory.sku}">
                  <input type="hidden" name="stock_id" value="${row.inventory.inventory[0].stock_uuid}">
                  <div class="form-check m-0">
                     ${meta.row + meta.settings._iDisplayStart + 1}
                  </div>`; // Serial number
              },
              title:
                `<div class="form-check m-0">
                  sno
                </div>`,
              orderable: false
            },
            {
              data: 'inventory',
              render: function (data, type, row) {
                return `
                  <div class="row gutters-5 mw-100 align-items-center">
                      <div class="col-auto">
                        <img src="${row.inventory.image}" alt="" class="size-50px img-fit img_custom">
                      </div>
                    <div class="col">
                      <span class="text-muted text-truncate-2 custom-width">${row.inventory.product.name}</span>
                          <span class="text-muted text-truncate-2 custom-width">${row.inventory.variant ? row.inventory.variant : ''}</span>
                    </div>
                  </div>`;
              },
              title: 'Product Details'
            },
            {
              data: 'inventory.sku', // SKU is inside the 'inventory' object
              render: function (data, type, row) {
                return `${(data? data:'')}`;
              },
              title: 'SKU'
            },
            {
              data: 'net_unavailable_qty', // Assuming the product data is in the response
              render: function (data, type, row) {
                return `
                <div class="dropdown inventory-dropdown unavailable">
                <a class="dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" data-bs-auto-close="outside">${data}</a>
                <div class="dropdown-menu dropdown-menu-end unavailable-parent">
                  <div class="unavailable-list">
                    <div class="title">Unavailable inventory</div>
                    <div class="unavailable-item">
                      <span>Damaged</span>
                      <div class="dropdown inventory-dropdown inner">
                        <a class="dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">${row?.net_damaged_qty}</a>
                        <div class="dropdown-menu dropdown-menu-start">
                          <a href="" class="link add-inventory" data-id="damaged">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" width="16" height="16">
                              <path
                                d="M4.25 8a.75.75 0 0 1 .75-.75h2.25v-2.25a.75.75 0 0 1 1.5 0v2.25h2.25a.75.75 0 0 1 0 1.5h-2.25v2.25a.75.75 0 0 1-1.5 0v-2.25h-2.25a.75.75 0 0 1-.75-.75">
                              </path>
                              <path fill-rule="evenodd"
                                d="M8 15a7 7 0 1 0 0-14 7 7 0 0 0 0 14m0-1.5a5.5 5.5 0 1 0 0-11 5.5 5.5 0 1 0 0 11"></path>
                            </svg>
                            Add inventory
                          </a>
                          <a href="" class="link move-to-available" data-id="damaged" data-qty="${row?.net_damaged_qty}">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" width="16" height="16">
                              <path fill-rule="evenodd"
                                d="M1.5 8a.75.75 0 0 1 .75-.75h9.69l-2.72-2.72a.749.749 0 1 1 1.06-1.06l4 4a.75.75 0 0 1 0 1.06l-4 4a.749.749 0 1 1-1.06-1.06l2.72-2.72h-9.69a.75.75 0 0 1-.75-.75">
                              </path>
                            </svg>
                            Move to Available
                          </a>
                          <a href="" class="link delete delete-damaged d-none" data-id="damaged" data-qty="${row?.net_damaged_qty}">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" width="16" height="16" fill="#8e0b21">
                              <path d="M9.5 6.25a.75.75 0 0 1 .75.75v4.25a.75.75 0 0 1-1.5 0v-4.25a.75.75 0 0 1 .75-.75"></path>
                              <path d="M7.25 7a.75.75 0 0 0-1.5 0v4.25a.75.75 0 0 0 1.5 0z"></path>
                              <path fill-rule="evenodd"
                                d="M5.25 3.25a2.75 2.75 0 1 1 5.5 0h3a.75.75 0 0 1 0 1.5h-.75v5.45c0 1.68 0 2.52-.327 3.162a3 3 0 0 1-1.311 1.311c-.642.327-1.482.327-3.162.327h-.4c-1.68 0-2.52 0-3.162-.327a3 3 0 0 1-1.311-1.311c-.327-.642-.327-1.482-.327-3.162v-5.45h-.75a.75.75 0 0 1 0-1.5zm1.5 0a1.25 1.25 0 0 1 2.5 0zm-2.25 1.5h7v5.45c0 .865-.001 1.423-.036 1.848-.033.408-.09.559-.128.633a1.5 1.5 0 0 1-.655.655c-.074.038-.225.095-.633.128-.425.035-.983.036-1.848.036h-.4c-.865 0-1.423-.001-1.848-.036-.408-.033-.559-.09-.633-.128a1.5 1.5 0 0 1-.656-.655c-.037-.074-.094-.225-.127-.633-.035-.425-.036-.983-.036-1.848z">
                              </path>
                            </svg>
                            Delete inventory
                          </a>
                          <!-- <a href="" class="link disabled">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" width="16" height="16">
                              <path d="M9.5 6.25a.75.75 0 0 1 .75.75v4.25a.75.75 0 0 1-1.5 0v-4.25a.75.75 0 0 1 .75-.75"></path>
                              <path d="M7.25 7a.75.75 0 0 0-1.5 0v4.25a.75.75 0 0 0 1.5 0z"></path>
                              <path fill-rule="evenodd"
                               d="M5.25 3.25a2.75 2.75 0 1 1 5.5 0h3a.75.75 0 0 1 0 1.5h-.75v5.45c0 1.68 0 2.52-.327 3.162a3 3 0 0 1-1.311 1.311c-.642.327-1.482.327-3.162.327h-.4c-1.68 0-2.52 0-3.162-.327a3 3 0 0 1-1.311-1.311c-.327-.642-.327-1.482-.327-3.162v-5.45h-.75a.75.75 0 0 1 0-1.5zm1.5 0a1.25 1.25 0 0 1 2.5 0zm-2.25 1.5h7v5.45c0 .865-.001 1.423-.036 1.848-.033.408-.09.559-.128.633a1.5 1.5 0 0 1-.655.655c-.074.038-.225.095-.633.128-.425.035-.983.036-1.848.036h-.4c-.865 0-1.423-.001-1.848-.036-.408-.033-.559-.09-.633-.128a1.5 1.5 0 0 1-.656-.655c-.037-.074-.094-.225-.127-.633-.035-.425-.036-.983-.036-1.848z">
                              </path>
                            </svg>
                           Disabled
                          </a>
                          -->
                        </div>
                      </div>
                    </div>
                    <!-- They are not functional -->
                    <div class="unavailable-item">
                      <span>Quality control</span>
                      <div class="dropdown inventory-dropdown inner">
                       <a class="dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">${row?.net_quality_control_qty}</a>
                        <div class="dropdown-menu dropdown-menu-start">
                          <a href="" class="link add-inventory" data-id="qualitycontrol">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" width="16" height="16">
                              <path
                                d="M4.25 8a.75.75 0 0 1 .75-.75h2.25v-2.25a.75.75 0 0 1 1.5 0v2.25h2.25a.75.75 0 0 1 0 1.5h-2.25v2.25a.75.75 0 0 1-1.5 0v-2.25h-2.25a.75.75 0 0 1-.75-.75">
                              </path>
                              <path fill-rule="evenodd"
                                d="M8 15a7 7 0 1 0 0-14 7 7 0 0 0 0 14m0-1.5a5.5 5.5 0 1 0 0-11 5.5 5.5 0 1 0 0 11"></path>
                            </svg>
                            Add inventory
                          </a>
                          <a href="" class="link move-to-available" data-id="qualitycontrol" data-qty="${row?.net_quality_control_qty}">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" width="16" height="16">
                              <path fill-rule="evenodd"
                                d="M1.5 8a.75.75 0 0 1 .75-.75h9.69l-2.72-2.72a.749.749 0 1 1 1.06-1.06l4 4a.75.75 0 0 1 0 1.06l-4 4a.749.749 0 1 1-1.06-1.06l2.72-2.72h-9.69a.75.75 0 0 1-.75-.75">
                              </path>
                            </svg>
                           Move to Available
                          </a>
                          <a href="" class="link delete delete-damaged d-none" data-id="qualitycontrol" data-qty="${row?.net_quality_control_qty}">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" width="16" height="16" fill="#8e0b21">
                              <path d="M9.5 6.25a.75.75 0 0 1 .75.75v4.25a.75.75 0 0 1-1.5 0v-4.25a.75.75 0 0 1 .75-.75"></path>
                              <path d="M7.25 7a.75.75 0 0 0-1.5 0v4.25a.75.75 0 0 0 1.5 0z"></path>
                              <path fill-rule="evenodd"
                                d="M5.25 3.25a2.75 2.75 0 1 1 5.5 0h3a.75.75 0 0 1 0 1.5h-.75v5.45c0 1.68 0 2.52-.327 3.162a3 3 0 0 1-1.311 1.311c-.642.327-1.482.327-3.162.327h-.4c-1.68 0-2.52 0-3.162-.327a3 3 0 0 1-1.311-1.311c-.327-.642-.327-1.482-.327-3.162v-5.45h-.75a.75.75 0 0 1 0-1.5zm1.5 0a1.25 1.25 0 0 1 2.5 0zm-2.25 1.5h7v5.45c0 .865-.001 1.423-.036 1.848-.033.408-.09.559-.128.633a1.5 1.5 0 0 1-.655.655c-.074.038-.225.095-.633.128-.425.035-.983.036-1.848.036h-.4c-.865 0-1.423-.001-1.848-.036-.408-.033-.559-.09-.633-.128a1.5 1.5 0 0 1-.656-.655c-.037-.074-.094-.225-.127-.633-.035-.425-.036-.983-.036-1.848z">
                              </path>
                            </svg>
                            Delete inventory
                          </a>
                        </div>
                     </div>
                    </div>
                    <div class="unavailable-item">
                      <span>Safety stock</span>
                      <div class="dropdown inventory-dropdown inner">
                        <a class="dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">${row?.net_safety_stock_qty}</a>
                        <div class="dropdown-menu dropdown-menu-start">
                          <a href="" class="link add-inventory" data-id="safetystock">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" width="16" height="16">
                              <path
                                d="M4.25 8a.75.75 0 0 1 .75-.75h2.25v-2.25a.75.75 0 0 1 1.5 0v2.25h2.25a.75.75 0 0 1 0 1.5h-2.25v2.25a.75.75 0 0 1-1.5 0v-2.25h-2.25a.75.75 0 0 1-.75-.75">
                              </path>
                              <path fill-rule="evenodd"
                                d="M8 15a7 7 0 1 0 0-14 7 7 0 0 0 0 14m0-1.5a5.5 5.5 0 1 0 0-11 5.5 5.5 0 1 0 0 11"></path>
                            </svg>
                            Add inventory
                          </a>
                          <a href="" class="link move-to-available" data-id="safetystock" data-qty="${row?.net_safety_stock_qty}">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" width="16" height="16">
                              <path fill-rule="evenodd"
                                d="M1.5 8a.75.75 0 0 1 .75-.75h9.69l-2.72-2.72a.749.749 0 1 1 1.06-1.06l4 4a.75.75 0 0 1 0 1.06l-4 4a.749.749 0 1 1-1.06-1.06l2.72-2.72h-9.69a.75.75 0 0 1-.75-.75">
                              </path>
                            </svg>
                            Move to Available
                          </a>
                          <a href="" class="link delete delete-damaged d-none"  data-id="safetystock" data-qty="${row?.net_safety_stock_qty}">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" width="16" height="16" fill="#8e0b21">
                              <path d="M9.5 6.25a.75.75 0 0 1 .75.75v4.25a.75.75 0 0 1-1.5 0v-4.25a.75.75 0 0 1 .75-.75"></path>
                              <path d="M7.25 7a.75.75 0 0 0-1.5 0v4.25a.75.75 0 0 0 1.5 0z"></path>
                              <path fill-rule="evenodd"
                                d="M5.25 3.25a2.75 2.75 0 1 1 5.5 0h3a.75.75 0 0 1 0 1.5h-.75v5.45c0 1.68 0 2.52-.327 3.162a3 3 0 0 1-1.311 1.311c-.642.327-1.482.327-3.162.327h-.4c-1.68 0-2.52 0-3.162-.327a3 3 0 0 1-1.311-1.311c-.327-.642-.327-1.482-.327-3.162v-5.45h-.75a.75.75 0 0 1 0-1.5zm1.5 0a1.25 1.25 0 0 1 2.5 0zm-2.25 1.5h7v5.45c0 .865-.001 1.423-.036 1.848-.033.408-.09.559-.128.633a1.5 1.5 0 0 1-.655.655c-.074.038-.225.095-.633.128-.425.035-.983.036-1.848.036h-.4c-.865 0-1.423-.001-1.848-.036-.408-.033-.559-.09-.633-.128a1.5 1.5 0 0 1-.656-.655c-.037-.074-.094-.225-.127-.633-.035-.425-.036-.983-.036-1.848z">
                              </path>
                            </svg>
                            Delete inventory
                          </a>
                        </div>
                      </div>
                    </div>
                    <div class="unavailable-item">
                      <span>Other</span>
                      <div class="dropdown inventory-dropdown inner">
                        <a class="dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">${row?.net_other_qty}</a>
                        <div class="dropdown-menu dropdown-menu-start">
                          <a href="" class="link add-inventory" data-id="other">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" width="16" height="16">
                              <path
                                d="M4.25 8a.75.75 0 0 1 .75-.75h2.25v-2.25a.75.75 0 0 1 1.5 0v2.25h2.25a.75.75 0 0 1 0 1.5h-2.25v2.25a.75.75 0 0 1-1.5 0v-2.25h-2.25a.75.75 0 0 1-.75-.75">
                              </path>
                              <path fill-rule="evenodd"
                                d="M8 15a7 7 0 1 0 0-14 7 7 0 0 0 0 14m0-1.5a5.5 5.5 0 1 0 0-11 5.5 5.5 0 1 0 0 11"></path>
                            </svg>
                            Add inventory
                          </a>
                          <a href="" class="link move-to-available" data-id="other" data-qty="${row?.net_other_qty}">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" width="16" height="16">
                              <path fill-rule="evenodd"
                                d="M1.5 8a.75.75 0 0 1 .75-.75h9.69l-2.72-2.72a.749.749 0 1 1 1.06-1.06l4 4a.75.75 0 0 1 0 1.06l-4 4a.749.749 0 1 1-1.06-1.06l2.72-2.72h-9.69a.75.75 0 0 1-.75-.75">
                              </path>
                            </svg>
                            Move to Available
                          </a>
                          <a href="" class="link delete delete-damaged d-none"  data-id="other" data-qty="${row?.net_other_qty}">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" width="16" height="16" fill="#8e0b21">
                              <path d="M9.5 6.25a.75.75 0 0 1 .75.75v4.25a.75.75 0 0 1-1.5 0v-4.25a.75.75 0 0 1 .75-.75"></path>
                              <path d="M7.25 7a.75.75 0 0 0-1.5 0v4.25a.75.75 0 0 0 1.5 0z"></path>
                              <path fill-rule="evenodd"
                                d="M5.25 3.25a2.75 2.75 0 1 1 5.5 0h3a.75.75 0 0 1 0 1.5h-.75v5.45c0 1.68 0 2.52-.327 3.162a3 3 0 0 1-1.311 1.311c-.642.327-1.482.327-3.162.327h-.4c-1.68 0-2.52 0-3.162-.327a3 3 0 0 1-1.311-1.311c-.327-.642-.327-1.482-.327-3.162v-5.45h-.75a.75.75 0 0 1 0-1.5zm1.5 0a1.25 1.25 0 0 1 2.5 0zm-2.25 1.5h7v5.45c0 .865-.001 1.423-.036 1.848-.033.408-.09.559-.128.633a1.5 1.5 0 0 1-.655.655c-.074.038-.225.095-.633.128-.425.035-.983.036-1.848.036h-.4c-.865 0-1.423-.001-1.848-.036-.408-.033-.559-.09-.633-.128a1.5 1.5 0 0 1-.656-.655c-.037-.074-.094-.225-.127-.633-.035-.425-.036-.983-.036-1.848z">
                              </path>
                            </svg>
                            Delete inventory
                          </a>
                        </div>
                      </div>
                    </div>
                  </div>
                  <!-- Add Damaged -->
                  <div class="unavailable-damaged">
                    <div class="title">Add</div>
                    <div class="col-8">
                      <label class="form-label"><g-t>Quantity</g-t></label>
                      <div class="input mb-3">
                        <input name="unavailable_reason" type="hidden" id="statusReason" value="">
                        <input type="number" name="quantity_damaged" value="0"  class="form-control md input-focus">
                      </div>
                      <div class="d-flex gap-2">
                        <a href="javascript:;" onclick="document.body.click()" class="btn border btn-modal-text">Cancel</a>
                        <button class="btn btn-primary btn-modal-text unavailable-btn">Save</button>
                      </div>
                    </div>
                  </div>
                  <!-- Move Damaged to Available -->
                  <div class="unavailable-move-to-available">
                    <div class="title" id="moveTitle"></div>
                    <div class="col-8">
                      <label class="form-label"><g-t>Quantity</g-t></label>
                      <div class="input">
                        <input name="unavailable_status" type="hidden" id="statusInput" value="">
                        <input name="quantity_unavailable_old" class="qty_text" type="hidden" value="">
                        <input type="number" value="0" name="quantity_unavailable" class="form-control md input-focus">
                      </div>
                      <div class="uptotxt" id="upToText">Move up to <span class="qty_text"></span></div>
                      <div class="d-flex gap-2 mt-3">
                        <a href="javascript:;" onclick="document.body.click()" class="btn border btn-modal-text">Cancel</a>
                        <button class="btn btn-primary btn-modal-text unavailable-btn">Save</button>
                      </div>
                    </div>
                  </div>
                  <!-- Delete Damaged -->
                  <div class="unavailable-delete-damaged">
                    <div class="title">Delete Damaged</div>
                    <div class="col-8">
                      <label class="form-label"><g-t>Quantity</g-t></label>
                      <div class="input">
                        <input name="unavailable_reason_delete" type="hidden" id="statusReasonDelete" value="">
                         <input name="quantity_delete_old" class="qty_text" type="hidden" value="">
                        <input type="number" value="0" name="unavailable_delete" class="form-control md input-focus">
                      </div>
                       <div class="uptotxt" id="upToText">Move up to <span class="qty_text"></span></div>
                      <div class="d-flex gap-2 mt-3">
                        <a href="javascript:;" onclick="document.body.click()" class="btn border btn-modal-text">Cancel</a>
                        <button class="btn btn-primary btn-modal-text delete-btn" disabled>Save</button>
                      </div>
                    </div>
                  </div>
                </div>
              </div>`;
              },
              title: 'Unavailable'
            },
           {
  data: 'committed_orders',
  render: function (data, type, row) {
    const orders = data ? Object.values(data) : []; // ✅ Convert object to array
    console.log("Committed orders count:", orders.length);

    if (orders.length > 0) {
      return `
        <div class="custom-dropdown-main">
          <div class="droupdown-head open-inventory-droupdown">
            <span>${row.order_count}</span>
            <svg width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16">
              <path fill-rule="evenodd" d="M4.24 6.2a.75.75 0 0 1 1.06.04l2.7 2.908 2.7-2.908a.75.75 0 0 1 1.1 1.02l-3.25 3.5a.75.75 0 0 1-1.1 0l-3.25-3.5a.75.75 0 0 1 .04-1.06" fill="#6c757d"></path>
            </svg>
          </div>
          <div class="droupdown-body">
            <div class="order-num-main">
              <span class="add-damaged-title">Order</span>
              ${orders.map((order, index) => `
                <div class="order-main">
                  <a href="?P=edit-order&M=${menu_id}&I=${order.order_id}"># ${order.order_code}</a>
                  <span>${order.qty}</span>
                </div>
              `).join('')}
            </div>
          </div>
        </div>
      `;
    } else {
      return `
        <div class="dropdown inventory-dropdown committed">
          <a class="dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" data-bs-auto-close="outside">0</a>
          <div class="dropdown-menu dropdown-menu-end">
            <span class="empty">No unfulfilled orders for this item</span>
          </div>
        </div>`;
    }
  },
  title: 'Committed'
},

            {
              data: 'net_available_qty',
              render: function (data, type, row) {
                return `
                  <div class="dropdown inventory-dropdown available">
                    <a class="dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" data-bs-auto-close="outside">${data}</a>
                    <div class="dropdown-menu dropdown-menu-end">
                      <div class="row">
                        <div class="col-12 mb-3">
                          <select class="adjustAvailable" name="available_status">
                            <option value="adjust">Adjust Available</option>
                            <option value="unavailable">Move to Unavailable</option>
                          </select>
                        </div>
                        <div class="col-12">
                          <div class="row">
                            <div class="col-6 p-0">
                              <label class="form-label d-none" id="AdjustAvailableQty"><g-t>Quantity</g-t></label>
                              <label class="form-label" id="AdjustAvailableByNew"><g-t>Adjust by</g-t></label>
                                <div class="input mb-3">
                                  <input type="number" value="" name="available_adjust_qty" class="form-control md input-focus">
                                </div>
                              </div>
                            </div>
                        </div>
                        <div class="col-12">
                          <label class="form-label"><g-t>Reason</g-t></label>
                          <select class="adjustReason" name="available_reason">
                            <option value="correction">Correction (default)</option>
                            <option value="cycle_count_available">Count</option>
                            <option value="received">Received</option>
                            <option value="restock">Return restock</option>
                            <option value="damaged">Damaged</option>
                            <option value="shrinkage">Theft or loss</option>
                            <option value="promotion">Promotion or donation</option>
                          </select>
                        </div>
                        <div class="col-12">
                          <div class="d-flex gap-2 mt-3">
                            <a href="javascript:;" onclick="document.body.click()" class="btn border btn-modal-text" >Cancel</a>
                            <button class="btn btn-primary btn-modal-text available-btn" >Save</button>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>`;
                // return `
                //     <div class="custom-dropdown-main">
                //         <div class="droupdown-head available open-inventory-droupdown">
                //             <input readonly class="available-input"  name="available_qty" value="${row.net_available_qty}" type="number">
                //             <svg width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16">
                //                 <path fill-rule="evenodd" d="M4.24 6.2a.75.75 0 0 1 1.06.04l2.7 2.908 2.7-2.908a.75.75 0 0 1 1.1 1.02l-3.25 3.5a.75.75 0 0 1-1.1 0l-3.25-3.5a.75.75 0 0 1 .04-1.06" fill="#6c757d"></path>
                //             </svg>
                //         </div>
                //         <div class="droupdown-body available-drop">
                //             <div class="select-input-main">
                //                 <div class="custom-dropdown-main">
                //                     <select class="select2 form-control" name="available_status"
                //                     data-toggle="select2" data-placeholder="Choose ..." onchange="availableSelect(this)" data-live-search="true">
                //                         <option value="adjust">Adjust Available</option>
                //                         <option value="unavailable">Move to Unavailable</option>
                //                     </select>
                //                 </div>
                //                 <div class="adjust-table">
                //                     <div class="quantity-main">
                //                         <span>Adjust by</span>
                //                         <input class="quantity" name="available_adjust_qty" required min="1" step="1" data-max="${row.net_available_qty}" type="number">
                //                     </div>
                //                     <div class="quantity-main">
                //                         <span>Reason</span>
                //                         <div class="custom-dropdown-main">
                //                             <select class="select2 form-control" name="available_reason"
                //                                 data-toggle="select2" data-placeholder="Choose ..." data-live-search="true">
                //                                 <option value="correction">Correction (default)</option>
                //                                 <option value="count">Count</option>
                //                                 <option value="received">Received</option>
                //                             </select>
                //                         </div>
                //                     </div>
                //                 </div>
                //             </div>
                //             <button class="btn btn-sm btn-custom-primary mt-2 available-btn" type="button" >Apply</button>
                //         </div>
                //     </div>
                // `;
              },
              title: 'Available'
            },
            {
              data: 'inventory_on_hand', // 'inventory' object
              render: function (data, type, row) {
                return `
                        <div class="dropdown inventory-dropdown available">
                            <a class="dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" data-bs-auto-close="outside">${data}</a>
                            <div class="dropdown-menu dropdown-menu-end">
                              <div class="row gx-2">
                                <div class="col-12 mb-3">
                                  <label class="form-label"><g-t>Adjust by</g-t></label>
                                  <div class="input">
                                    <input type="number" value="0" name="onhand_adjust_qty" class="form-control md input-focus md">
                                  </div>
                                  <div class="uptotxt d-none">(Original quantity: 0)</div>
                                </div>
                                <div class="col-12">
                                  <label class="form-label"><g-t>Reason</g-t></label>
                                  <select class="adjustReason2" name="onhold_reason">
                                    <option value="correction">Correction (default)</option>
                                    <option value="cycle_count_available">Count</option>
                                    <option value="received">Received</option>
                                    <option value="restock">Return restock</option>
                                    <option value="damaged">Damaged</option>
                                    <option value="shrinkage">Theft or loss</option>
                                    <option value="promotion">Promotion or donation</option>
                                  </select>
                                </div>
                                <div class="col-12">
                                  <div class="d-flex gap-2 mt-3">
                                    <a href="javascript:;" onclick="document.body.click()" class="btn border btn-modal-text" >Cancel</a>
                                    <button class="btn btn-primary btn-modal-text onhold-btn" disabled>Save</button>
                                  </div>
                                </div>
                              </div>
                            </div>
                          </div>
                                `;
              },
              title: 'OnHand'
            },
            {
              data: 'in_coming', //'inventory' object
              visible: false,
              render: function (data, type, row) {
                return `
                    <div class="dropdown inventory-dropdown committed">
                        <a class="dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" data-bs-auto-close="outside">${data}</a>
                        <div class="dropdown-menu dropdown-menu-end">
                            <span class="empty">Create <a href="" class="create-transfer">transfer</a> or <a href="" class="create-purchase-order">purchase order</a></span>
                        </div>
                    </div> `;
              },
              title: 'InComing'
            },
            {
              data: 'vendor',
              visible: false,
              render: function (data, type, row) {
                return `
                    <div class="dropdown inventory-dropdown committed">
                        <a class="dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" data-bs-auto-close="outside">${row?.inventory?.product?.vendor}</a>
                    </div> `;
              },
              title: 'Vendor'
            },
            {
              data: 'type',
              visible: false,
              render: function (data, type, row) {
                return `
                    <div class="dropdown inventory-dropdown committed">
                        <a class="dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" data-bs-auto-close="outside">${row?.inventory?.product?.type}</a>
                    </div> `;
              },
              title: 'Type'
            },
            {
              data: 'sale_channel_id',
              visible: false,
              render: function (data, type, row) {
                return `
                    <div class="dropdown inventory-dropdown committed">
                        <a class="dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" data-bs-auto-close="outside">${row?.inventory?.product?.sale_channel_id}</a>
                    </div> `;
              },
              title: 'Sales Channel'
            },
            {
              data: 'tags',
              visible: false,
              render: function (data, type, row) {
                let tags = row?.inventory?.product?.tags || "[]";
                let tagString = "-"; // default to dash

                if (typeof tags === "string") {
                  // Check if it's a JSON stringified array
                  if (tags.trim().startsWith("[")) {
                    try {
                      const parsed = JSON.parse(tags);
                      if (Array.isArray(parsed) && parsed.length) {
                        tagString = parsed.join(", ");
                      }
                    } catch (e) {
                      tagString = tags || "-";
                    }
                  } else {
                    tagString = tags || "-";
                  }
                } else if (Array.isArray(tags) && tags.length) {
                  tagString = tags.join(", ");
                }

                return `
                    <div class="dropdown inventory-dropdown committed">
                        <a class="dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" data-bs-auto-close="outside">${tagString}</a>
                    </div> `;
              },
              title: 'Tags'
            }
          ];

          let tableData = response.data;
          let pageLength = inventoryPerPage === 'all' ? tableData.length : parseInt(inventoryPerPage);
          
          let configFilter = {
              searching: false,
              paging: true,
              info: false,
              bLengthChange: false,
              responsive: false,
              pageLength: pageLength,
              paging: inventoryPerPage !== 'all',
              dom: 't'
          };
          let tableSearch = $('#Table_View').DataTable({
            data: tableData,
            columns: columnsConfig,
            ...configFilter,
            drawCallback: function (settings) {
              if (typeof buildInventoryPagination === 'function') {
                  buildInventoryPagination(settings);
              } else {
                  console.warn('buildInventoryPagination is not defined.');
              }
          },

            initComplete: function () {
                $('#Table_View thead th').removeClass('sorting sorting_asc sorting_desc');
            }
          });

          $('#Table_View_paginate').hide();
                    
                    // Render custom pagination
          renderInventoryPagination(inventoryCurrentPage, inventoryLastPage);
          // let tableSearch = makeDataTable('#Table_View', tableData, columnsConfig, {
          //   showButtons: false,
          // });
          // imgload.hide();

          $('#custom_search').keyup(function () {
            tableSearch.search($(this).val()).draw();
          });


          $(".lodest-first a").on('click', function (e) {
            e.preventDefault();
            $(this).siblings().removeClass("active");
            $(this).addClass("active");
          });

          var selectedColumn = 1;
          $(".custom-dropdown-main input[type=radio]").on("change", function () {
            console.log('ee', $(this).val())
            selectedColumn = $(this).val();
          });

          $("#sortAZ").on("click", function (e) {
            e.preventDefault();
            tableSearch.order([selectedColumn, 'asc']).draw();
          });

          $("#sortZA").on("click", function (e) {
            e.preventDefault();
            tableSearch.order([selectedColumn, 'desc']).draw();
          });

          // $(".dataTables_filter , .dataTables_length").hide();
          $(".dt-action-buttons").addClass("d-none");

          // INCOMING 
          // Custom filter for range (Tab 2)
          $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
            if (!$('.tab-item-two').hasClass('active')) return true;

            let isValid = true;

            // Loop through all range inputs (pairs)
            $('input[data-type="min"]').each(function () {
              const columnIndex = $(this).data('column');
              const minVal = parseFloat($(this).val()) || -Infinity;
              const maxVal = parseFloat($(`input[data-column="${columnIndex}"][data-type="max"]`).val()) || Infinity;
              const cellValue = parseFloat(data[columnIndex]) || 0;

              if (cellValue < minVal || cellValue > maxVal) {
                isValid = false;
                return false; // exit each loop early
              }
            });

            return isValid;
          });

          $('input[name="search-input"]').on("input", function () {
            const val = $(this).val();
            tableSearch.column(8).search(val).draw();
          });

          $('input[name="vendor-checkbox"]').on("input", function () {
            const val = $(this).val();
            tableSearch.column(8).search(val).draw();
          });

          $('input[name="type-radio"]').on("input", function () {
            const val = $(this).val();
            tableSearch.column(9).search(val).draw();
          });

          $('input[name="saleschannel-radio"]').on("input", function () {
            const val = this.id; // or $(this).attr('id')
            console.log('val', val);
            tableSearch.column(10).search(val).draw();
          });

          $('input[name="search-tags-input"]').on("input", function () {
            console.log($(this).val())
            const val = $(this).val();
            tableSearch.column(11).search(val).draw();
          });

          // Tab 1: Exact match
          $('input[data-column]').on("input", function () {
            if ($('.tab-item-one').hasClass('active')) {
              const column = $(this).data('column');
              const value = $(this).val();
              tableSearch.column(column).search(value).draw();
            }
          });

          $('input[data-type="min"], input[data-type="max"]').on("input", function () {
            if ($('.tab-item-two').hasClass('active')) {
              // Clear tab 1 inputs
              $('input[data-column]').not('[data-type]').val('');
              tableSearch.draw();
            }
          });
          tableDropdown();
        }
      } else if (response.statusCode == 404) {
        imgload.hide();

        $("#Table_View").DataTable({
          destroy: true,
          searching: true,
          retrieve: true,
          ordering: false,
          resposive: true,
          dom: "",
          bLengthChange: false,
          oLanguage: {
            sEmptyTable:
              `<img class='dataTable-custom-empty-icon' src='/images/no-record-found.svg'><br>${tbNoFound}`,
          },
        });
      }
      else {
        imgload.hide();
        showToast(response.message, 'warning', 'warning');
      }

      $("#Table_View .select-all").on("change", function () {
        console.log('hello world')
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
    },
    error: function (xhr, status, err) {
      imgload.hide();
      if (xhr.status == 401) {
        showToast(err, 'error', 'error');
      } else {
        showToast('Error', 'error', 'error');
      }
    }
  })
  return true;
}

function renderInventoryPagination(currentPage, lastPage) {
  console.log(currentPage, lastPage)
    let paginationHTML = '<div class="dataTables_wrapper dt-bootstrap5 no-footer"><div class="col-sm-12 col-md-5"></div><div class="col-sm-12 col-md-7" style="float: right;"><div class="dataTables_paginate paging_simple_numbers" id="Table_View_paginate"><ul class="pagination">';

    const maxVisiblePages = 10;
    let startPage, endPage;

    // Calculate the range of pages to display
    if (lastPage <= maxVisiblePages) {
        // Less than max pages: show all
        startPage = 1;
        endPage = lastPage;
    } else {
        const half = Math.floor(maxVisiblePages / 2);
        if (currentPage <= half) {
            startPage = 1;
            endPage = maxVisiblePages;
        } else if (currentPage + half >= lastPage) {
            startPage = lastPage - maxVisiblePages + 1;
            endPage = lastPage;
        } else {
            startPage = currentPage - half;
            endPage = currentPage + half - 1;
        }
    }

    // Previous button
    if (currentPage > 1) {
        paginationHTML += `<li class="paginate_button page-item previous"><a href="#" class="page-link" onclick="get_inventories($('#warehouse_location_id').val(), ${currentPage - 1})">Previous</a></li>`;
    }

    // First page shortcut (optional)
    if (startPage > 1) {
        paginationHTML += `<li class="paginate_button page-item"><a href="#" class="page-link" onclick="get_inventories($('#warehouse_location_id').val(), 1)">1</a></li>`;
        if (startPage > 2) {
            paginationHTML += `<li class="paginate_button page-item disabled"><span class="page-link">...</span></li>`;
        }
    }

    // Numbered page buttons
    for (let i = startPage; i <= endPage; i++) {
        let activeClass = i === currentPage ? 'active' : '';
        paginationHTML += `<li class="paginate_button page-item ${activeClass}"><a href="#" class="page-link ${activeClass}" onclick="get_inventories($('#warehouse_location_id').val(), ${i})">${i}</a></li>`;
    }

    // Last page shortcut (optional)
    if (endPage < lastPage) {
        if (endPage < lastPage - 1) {
            paginationHTML += `<li class="paginate_button page-item disabled"><span class="page-link">...</span></li>`;
        }
        paginationHTML += `<li class="paginate_button page-item"><a href="#" class="page-link" onclick="get_inventories($('#warehouse_location_id').val(), ${lastPage})">${lastPage}</a></li>`;
    }

    // Next button
    if (currentPage < lastPage) {
        paginationHTML += `<li class="paginate_button page-item next"><a href="#" class="page-link" onclick="get_inventories($('#warehouse_location_id').val(), ${currentPage + 1})">Next</a></li>`;
    }

    paginationHTML += `</ul></div></div></div>`;
    $('#pagination').html(paginationHTML);
}
//edit theme
$('table').on('click', '.btn-edit', function (e) {
  e.preventDefault();
  $("#popup_title").html(getTranslation("modify_menu"));
  let currentRow = $(this).closest("tr");
  let data = $('#Table_View').DataTable().row(currentRow).data();
  let uuid = data['uuid'];
  imgload.show();
  let page = 'productedit';
  window.location.assign('?P=' + page + '&M=' + menu_id + '&id=' + uuid);
});

$('table').on('click', '.btn-view-inventory', function (e) {
  e.preventDefault();
  $("#popup_title").html(getTranslation("modify_menu"));
  let currentRow = $(this).closest("tr");
  let data = $('#Table_View').DataTable().row(currentRow).data();
  console.log(data);

  let uuid = data['inventory']['uuid'];
  imgload.show();
  let page = 'inventorydetails';
  window.location.assign('?P=' + page + '&M=' + menu_id + '&id=' + uuid);
});

// edit attribute
$('table').on('click', '.btn-edit-attribute', function (e) {
  e.preventDefault();
  $("#popup_title").html(getTranslation("modify_menu"));
  let currentRow = $(this).closest("tr");
  let data = $('#Table_View').DataTable().row(currentRow).data();
  let uuid = data['uuid'];
  imgload.show();
  let page = 'productattribute';
  window.location.assign('?P=' + page + '&M=' + menu_id + '&id=' + uuid);
});

//delete
$('table').on('click', '.btn-delete', function (e) {
  e.preventDefault();
  let currentRow = $(this).closest("tr");
  let data = $('#Table_View').DataTable().row(currentRow).data();
  let uuid = data['uuid'];
  Swal.fire({
    title: getTranslation('deleteConfirmMsg') + name,
    icon: 'warning',
    showCancelButton: true,
    cancelButtonText: getTranslation('cancel'),
    confirmButtonColor: '#15508A',
    cancelButtonColor: '#15508A',
    confirmButtonText: getTranslation('delete'),
    showClass: {
      popup: 'animated fadeInDown faster'
    },
    hideClass: {
      popup: 'animated fadeOutUp faster'
    }
  }).then((result) => {
    if (result.value) {
      $.ajax({
        url: ApiPlugin + "/ecommerce/product/delete_product/" + uuid,
        type: "delete",
        data: {},
        beforeSend: function (xhr) {
          xhr.setRequestHeader("Authorization", "Bearer " + strkey);
          xhr.setRequestHeader("menu-uuid", menu_id);
          imgload.show();
        },
        success: function (data) {
          imgload.hide();
          if (data.status_code == 200) {
            showToast(data.message, 'Success', 'success', 'self');
          }
        },
        error: function (xhr, status, err) {
          imgload.hide();
          showToast('Error', 'error', 'error');
        }
      })
    }
  });
});

function changestatus(el) {
  if (el.checked) {
    var status = 1;
  }
  else {
    var status = 0;
  }
  $.ajax({
    url: ApiPlugin + "/ecommerce/product/status/" + $(el).val(),
    type: "POST",
    data: { status: status },
    beforeSend: function (xhr) {
      xhr.setRequestHeader("Authorization", "Bearer " + strkey);
      xhr.setRequestHeader("menu-uuid", menu_id);
      imgload.show();
    },
    success: function (data) {
      imgload.hide();
      localStorage.setItem('theme_id', data.theme_id);
      showToast(data.message, 'Success', 'success', 'self');
    },
    error: function (xhr, status, err) {
      imgload.hide();
      showToast("Error", '', 'error');
    }
  })
}

function changefeatured(el) {
  if (el.checked) {
    var featured = 1;
  }
  else {
    var featured = 0;
  }
  $.ajax({
    url: ApiPlugin + "/ecommerce/product/featured/" + $(el).val(),
    type: "POST",
    data: { featured: featured },
    beforeSend: function (xhr) {
      xhr.setRequestHeader("Authorization", "Bearer " + strkey);
      xhr.setRequestHeader("menu-uuid", menu_id);
      imgload.show();
    },
    success: function (data) {
      imgload.hide();
      localStorage.setItem('theme_id', data.theme_id);
      showToast(data.message, 'Success', 'success', 'self');
    },
    error: function (xhr, status, err) {
      imgload.hide();
      showToast("Error", '', 'error');
    }
  })
}

function tableDropdown() {
  // $(".custom-dropdown-main .droupdown-head.open-inventory-droupdown").on("click", function (e) {
  //     e.preventDefault();
  //     $(this).parent().find(".unavailable-inventory").show(100);
  //     $(this).parent().find(".add-damaged").hide(100);
  //     $(this).parent().find(".move-available").hide(100);
  //     $(this).parent().find(".delete-control").hide(100);
  //     $('.aiz-selectpicker').selectpicker("destory");
  //     $('.aiz-selectpicker').selectpicker("refresh");
  // });

  // $(".open-add-damaged").on("click", function (e) {
  //     e.preventDefault();
  //     $(this).parents(".unavailable-inventory").first().fadeToggle(100);
  //     $(this).parents(".unavailable-inventory").next().fadeToggle(100);
  // });

  // $(".custom-dropdown-main .droupdown-head.open-inventory-droupdown").on("click", function (e) {
  //     e.preventDefault();
  //     $(this).parent().find(".unavailable-inventory").show(100);
  //     $(this).parent().find(".move-available").hide(100);
  //     $('.aiz-selectpicker').selectpicker("destory");
  //     $('.aiz-selectpicker').selectpicker("refresh");
  // });

  // $(".open-available-modal").on("click", function (e) {
  //     e.preventDefault();
  //     $(this).parents(".unavailable-inventory").first().fadeToggle(100);
  //     $(this).parents(".unavailable-inventory").next().next().fadeToggle(100);
  // });
  // $(".open-deleted-modal").on("click", function (e) {
  //     e.preventDefault();
  //     $(this).parents(".unavailable-inventory").first().fadeToggle(100);
  //     $(this).parents(".unavailable-inventory").next().next().next().fadeToggle(100);
  // });

  // console.log($('#adjustAvailable, #adjustReason, #adjustReason2'));

  $('.adjustAvailable, .adjustReason, .adjustReason2').select2({
    allowClear: false,
    width: '100%',
    minimumResultsForSearch: Infinity
  });

  $('.adjustAvailable').on('change', function () {
    var selectedValue = $(this).val();

    var newOptions = [];
    if (selectedValue == 'adjust') {

      $(this).parents(".dropdown-menu").find("#AdjustAvailableQty").addClass('d-none');
      $(this).parents(".dropdown-menu").find("#AdjustAvailableByNew").removeClass('d-none');

      newOptions = [
        { id: 'correction', text: 'Correction (default)' },
        { id: 'cycle_count_available', text: 'Count' },
        { id: 'received', text: 'Received' },
        { id: 'restock', text: 'Return restock' },
        { id: 'damaged', text: 'Damaged' },
        { id: 'shrinkage', text: 'Theft or loss' },
        { id: 'promotion', text: 'Promotion or donation' },
      ];
    } else if (selectedValue == 'unavailable') {

      $(this).parents(".dropdown-menu").find("#AdjustAvailableQty").removeClass('d-none');
      $(this).parents(".dropdown-menu").find("#AdjustAvailableByNew").addClass('d-none');

      newOptions = [
        { id: 'other', text: 'Other (default)' },
        { id: 'damaged', text: 'Damaged' },
        { id: 'qualitycontrol', text: 'Quality control' },
        { id: 'safetystock', text: 'Safety stock' },
      ];
    }

    let adjustReason = $(this).parents(".dropdown-menu").find('.adjustReason');

    $(adjustReason).empty();

    // console.log(newOptions);
    $.each(newOptions, function (index, option) {
      $(adjustReason).append(new Option(option.text, option.id, false, false));
    });

    $(adjustReason).select2({
      allowClear: false,
      minimumResultsForSearch: Infinity,
      width: '100%'
    });
  });

  $(".inventory-dropdown .add-inventory").on("click", function (e) {
    e.preventDefault();
    $(this).parents(".unavailable-parent").find("> div").addClass("d-none");
    $(this).parents(".unavailable-parent").find(".unavailable-damaged").removeClass("d-none");

    const optionId = $(this).data('id');
    $(this).parents('.unavailable-parent').find('#statusReason').val(optionId)
  });

  let damagedOption = [
    {
      id: 'damaged',
      text: 'Move Damaged to Available',
      value: 'damaged',
    },
    {
      id: 'qualitycontrol',
      text: 'Move Quality Control to Available',
      value: 'qualitycontrol',
    },
    {
      id: 'safetystock',
      text: 'Move Safety stock to Available',
      value: 'safetystock',
    },
    {
      id: 'other',
      text: 'Move Other to Available',
      value: 'other',
    }
  ]

  $(".inventory-dropdown .move-to-available").on("click", function (e) {
    e.preventDefault();
    $(this).parents(".unavailable-parent").find("> div").addClass("d-none");
    $(this).parents(".unavailable-parent").find(".unavailable-move-to-available").removeClass("d-none");

    const optionId = $(this).data('id');
    const qty = $(this).data('qty');
    const option = damagedOption.find(opt => opt.id === optionId);

    if (option) {
      $(this).parents('.unavailable-parent').find('#moveTitle').text(option.text);
      $(this).parents('.unavailable-parent').find('#statusInput').val(option.value);
      $(this).parents('.unavailable-parent').find('.qty_text').text(qty);
      $(this).parents('.unavailable-parent').find('.qty_text').val(qty);
    }
  });

  $(".inventory-dropdown .delete-damaged").on("click", function (e) {
    e.preventDefault();
    $(this).parents(".unavailable-parent").find("> div").addClass("d-none");
    $(this).parents(".unavailable-parent").find(".unavailable-delete-damaged").removeClass("d-none");

    const optionId = $(this).data('id');
    const qty = $(this).data('qty');
    const option = damagedOption.find(opt => opt.id === optionId);

    if (option) {
      $(this).parents('.unavailable-parent').find('.qty_text').text(qty);
      $(this).parents('.unavailable-parent').find('.qty_text').val(qty);
      $(this).parents('.unavailable-parent').find('#statusReasonDelete').val(optionId)
    }
  });

  $(".inventory-dropdown.unavailable .dropdown-toggle").on("click", function () {
    $(this).parent().find(".dropdown-menu > div").addClass("d-none");
    $(this).parent().find(".unavailable-list").removeClass("d-none");
  });

  $(".droupdown-body.right.select-body .select-width.select-two li").on("click", function (e) {
    $(".droupdown-head.open-inventory-droupdown.customize-slect.select-two").parent().toggleClass("show");
    $(".droupdown-head.open-inventory-droupdown.customize-slect.select-two span").text($(this).text())
  });

  $(".droupdown-body.right.select-body .select-width.selectjs li").on("click", function (e) {
    $(".droupdown-head.open-inventory-droupdown.customize-slect.selectjs").parent().toggleClass("show");
    $(".droupdown-head.open-inventory-droupdown.customize-slect.selectjs span").text($(this).text())
  })

  $(".droupdown-body.right.select-body .select-width.selectone li").on("click", function (e) {
    $(".droupdown-head.open-inventory-droupdown.customize-slect.selectone").parent().toggleClass("show");
    $(".droupdown-head.open-inventory-droupdown.customize-slect.selectone span").text($(this).text())
  });

  $('input[name="onhand_adjust_qty"]').on('input', function () {
    $('.onhold-btn').prop('disabled', false);
  })

  $(document).off('click', '.onhold-btn').on('click', '.onhold-btn', function () {
    $('.onhold-btn').prop('disabled', true);
    let availableMain = $(this).parents(".inventory-dropdown").first();
    let product_id = $(this).parents('tr').find('input[name="product_id"]').val();
    let stock_id = $(this).parents('tr').find('input[name="stock_id"]').val();
    let location_id = $(this).parents('tr').find('input[name="location_id"]').val();
    let inventory_sku = $(this).parents('tr').find('input[name="inventory_sku"]').val();
    let onhold_reason = availableMain.find('select[name="onhold_reason"]').find("option:selected").val();
    let onhandAdjustQty = availableMain.find('input[name="onhand_adjust_qty"]').val();
    let appUrl = $('meta[name="app-url"]').attr('content');

    if (!onhandAdjustQty) {
      $('.onhold-btn').prop('disabled', false);
      showToast('Please enter a quantity', 'Error', 'error');
      return;
    }

    if(onhandAdjustQty <= 0){
       $('.onhold-btn').prop('disabled', false);
        showToast('Please enter a quantity greater than 0', 'Error', 'error');
        return;
    }

    if (isNaN(onhandAdjustQty) || (onhandAdjustQty) % 1 != 0) {
      $('.onhold-btn').prop('disabled', false);
      showToast('Invalid quantity. Please enter a valid quantity', 'Error', 'error');
      return;
    }
    if(onhandAdjustQty > 1000000){
      showToast('Quantity should be less than or equal to 1000000', 'Error', 'error');
       return;
    }
    $.ajax({
      type: "POST",
      url: ApiPlugin + '/ecommerce/inventory/add-inventory',
      data: {
        _token: $('meta[name="csrf-token"]').attr('content'),
        stock_id: stock_id,
        location_id: location_id,
        product_id: product_id,
        stock_sku: inventory_sku,
        available_status: 'adjust',
        available_reason: onhold_reason,
        available_adjust_qty: onhandAdjustQty
      },
      beforeSend: function (xhr) {
        xhr.setRequestHeader("Authorization", "Bearer " + strkey);
        xhr.setRequestHeader("menu-uuid", menu_id);
        imgload.show()
      },
      success: function (data) {
        if (data.status == 200) {
          imgload.hide();
          showToast(data.message, 'Success', 'success', 'self');
          $('.onhold-btn').prop('disabled', false);
        }
      },
      error: function (xhr, status, error) {
        let errorMessage = xhr.responseJSON.status + ': ' + xhr.responseJSON.message
        showToast(errorMessage, 'Error', 'error');
        $('.onhold-btn').prop('disabled', false);
      }
    });

  });

  // Start Available
  //inventory actions
  $(document).off('click', '.available-btn').on('click', '.available-btn', function() {
    $('.available-btn').prop('disabled', true);
    let availableMain = $(this).parents(".inventory-dropdown").first();
    let product_id = $(this).parents('tr').find('input[name="product_id"]').val();
    let stock_id = $(this).parents('tr').find('input[name="stock_id"]').val();
    let location_id = $(this).parents('tr').find('input[name="location_id"]').val();
    let inventory_sku = $(this).parents('tr').find('input[name="inventory_sku"]').val();
    let available_status = availableMain.find('select[name="available_status"]').find("option:selected").val();
    let available_reason = availableMain.find('select[name="available_reason"]').find("option:selected").val();
    let available_adjust_qty = availableMain.find('input[name="available_adjust_qty"]').val();
    let net_available_qty = parseInt(availableMain.find('.dropdown-toggle').text()); 
    let appUrl = $('meta[name="app-url"]').attr('content');

    if (!available_adjust_qty) {
      $('.available-btn').prop('disabled', false);
      showToast('Please enter a quantity', 'Error', 'error');
      return;
    }

    if(available_adjust_qty <= 0){
       $('.available-btn').prop('disabled', false);
        showToast('Please enter a quantity greater than 0', 'Error', 'error');
        return;
    }

    if (isNaN(available_adjust_qty) || (available_adjust_qty) % 1 != 0) {
      $('.available-btn').prop('disabled', false);
      showToast('Invalid quantity. Please enter a valid quantity', 'Error', 'error');
      return;
    }

    if (available_status === 'unavailable' && available_adjust_qty > net_available_qty) {
        $('.available-btn').prop('disabled', false);
        showToast(`Cannot move ${available_adjust_qty} to unavailable. Only ${net_available_qty} available.`, 'Error', 'error');
        return;
    }
    if(available_adjust_qty > 1000000){
      showToast('Quantity should be less than or equal to 1000000', 'Error', 'error');
       return;
    }

    if (available_status == 'unavailable') {
      let availableQty = availableMain.find('input[name="available_adjust_qty"]').data('max');
      if (isNaN(available_adjust_qty) || parseInt(available_adjust_qty) < 0 || parseInt(available_adjust_qty) > availableQty) {
        $('.available-btn').prop('disabled', false);
        showToast('Quantity must be less than or equal to available quantity', 'Error', 'error');
        return;
      }
      $.ajax({
        type: "POST",
        url: ApiPlugin + '/ecommerce/inventory/add-unavailable',
        data: {
          _token: $('meta[name="csrf-token"]').attr('content'),
          stock_id: stock_id,
          location_id: location_id,
          product_id: product_id,
          stock_sku: inventory_sku,
          available_status: available_status,
          available_reason: available_reason,
          available_adjust_qty: available_adjust_qty
        },
        beforeSend: function (xhr) {
          xhr.setRequestHeader("Authorization", "Bearer " + strkey);
          xhr.setRequestHeader("menu-uuid", menu_id);
          imgload.show()
        },
        success: function (data) {
          if (data.status == 200) {
            imgload.hide();
            showToast(data.message, 'Success', 'success', 'self');
          }
        },
        error: function (xhr, status, error) {
          let errorMessage = xhr.responseJSON.status + ': ' + xhr.responseJSON.message
          showToast(errorMessage, 'Error', 'error');
          $('.available-btn').prop('disabled', false);
          // location.reload();
        }
      });
    }
    else if (available_status == 'adjust') {
      $.ajax({
        type: "POST",
        url: ApiPlugin + '/ecommerce/inventory/add-inventory',
        data: {
          _token: $('meta[name="csrf-token"]').attr('content'),
          stock_id: stock_id,
          location_id: location_id,
          product_id: product_id,
          stock_sku: inventory_sku,
          available_status: available_status,
          available_reason: available_reason,
          available_adjust_qty: available_adjust_qty
        },
        beforeSend: function (xhr) {
          xhr.setRequestHeader("Authorization", "Bearer " + strkey);
          xhr.setRequestHeader("menu-uuid", menu_id);
          imgload.show()
        },
        success: function (data) {
          if (data.status == 200) {
            imgload.hide();
            showToast(data.message, 'Success', 'success', 'self');
          }
        },
        error: function (xhr, status, error) {
          let errorMessage = xhr.responseJSON.status + ': ' + xhr.responseJSON.message
          showToast(errorMessage, 'Error', 'error');
          $('.available-btn').prop('disabled', false);
        }
      });
    }
  });
  // End Available

  // Unavialable Start
  $(document).off('click', '.unavailable-btn').on('click', '.unavailable-btn', function() { 
    $('.unavailable-btn').prop('disabled', true);
    let unavailableMain = $(this).parents(".inventory-dropdown").first();
    let unavailableStatus = unavailableMain.find('input[name="unavailable_status"]').val();
    let unavailabletoAvailableQty = unavailableMain.find('input[name="quantity_unavailable"]').val();
    let quantityDamaged = unavailableMain.find('input[name="quantity_damaged"]').val();
    let unavailableReason = unavailableMain.find('input[name="unavailable_reason"]').val();
    let quantity_unavailable_old = unavailableMain.find('input[name="quantity_unavailable_old"]').val();
    let product_id = $(this).parents('tr').find('input[name="product_id"]').val();
    let location_id = $(this).parents('tr').find('input[name="location_id"]').val();
    let stock_id = $(this).parents('tr').find('input[name="stock_id"]').val();
    let inventory_sku = $(this).parents('tr').find('input[name="inventory_sku"]').val();

    unavailableQty = unavailableReason ? quantityDamaged : unavailabletoAvailableQty


    let appUrl = $('meta[name="app-url"]').attr('content');
    if (unavailableQty == '') {
      $('.unavailable-btn').prop('disabled', false);
      showToast('Please enter a quantity', 'Error', 'error');
      return;
    }

    if(unavailableQty > 1000000){
      showToast('Quantity should be less than or equal to 1000000', 'Error', 'error');
       return;
    }
    // console.log(isNaN(unavailableQty));
    // console.log(parseInt(unavailableQty) <= 0);
    // console.log(parseInt(unavailableQty) % 1 != 0);
    if (isNaN(unavailableQty) || parseInt(unavailableQty) <= 0 || parseInt(unavailableQty) % 1 != 0) {
      $('.unavailable-btn').prop('disabled', false);
      showToast('Invalid quantity. Please enter a valid quantity.', 'Error', 'error');
      return;
    } else if (parseInt(unavailableQty) > parseInt(quantity_unavailable_old)) {
      $('.unavailable-btn').prop('disabled', false);
      showToast('Unable to set unavailable quantity more than current unavailable quantity', 'Error', 'error');
      return;
    }

    $.ajax({
      type: "POST",
      url: ApiPlugin + '/ecommerce/inventory/add-unavailable',
      data: {
        _token: $('meta[name="csrf-token"]').attr('content'),
        stock_id: stock_id,
        location_id: location_id,
        product_id: product_id,
        stock_sku: inventory_sku,
        available_status: `${unavailableReason ? 'unavailable' : 'available'}`,
        available_reason: unavailableReason ? unavailableReason : unavailableStatus,
        available_adjust_qty: unavailableQty
      },
      beforeSend: function (xhr) {
        xhr.setRequestHeader("Authorization", "Bearer " + strkey);
        xhr.setRequestHeader("menu-uuid", menu_id);
        imgload.show();
      },
      success: function (data) {
        imgload.hide();
        if (data.status == 200) {
          showToast(data.message, 'Success', 'success', 'self');
        }
      },
      error: function (xhr, status, error) {
        let errorMessage = xhr.responseJSON.status + ': ' + xhr.responseJSON.message
        showToast(errorMessage, 'Error', 'error');
        $('.unavailable-btn').prop('disabled', false);
      }
    });
  });
  // Unavialable END

  // Unavialable Delete Start
  $('input[name="unavailable_delete"]').on('input', function () {
    $('.delete-btn').prop('disabled', false);
  })

  $(document).off('click', '.delete-btn').on('click', '.delete-btn', function() {
    $('.delete-btn').prop('disabled', true);
    let unavailableMain = $(this).parents(".inventory-dropdown").first();
    let unavailableStatus = unavailableMain.find('input[name="unavailable_status"]').val();
    let unavailableDeleteQty = unavailableMain.find('input[name="unavailable_delete"]').val();
    let unavailableReason = unavailableMain.find('input[name="unavailable_reason_delete"]').val();
    let quantity_delete_old = unavailableMain.find('input[name="quantity_delete_old"]').val();
    let product_id = $(this).parents('tr').find('input[name="product_id"]').val();
    let location_id = $(this).parents('tr').find('input[name="location_id"]').val();
    let stock_id = $(this).parents('tr').find('input[name="stock_id"]').val();
    let inventory_sku = $(this).parents('tr').find('input[name="inventory_sku"]').val();

    if (unavailableDeleteQty == '') {
      $('.delete-btn').prop('disabled', false);
      showToast('Please enter a quantity', 'Error', 'error');
      return;
    }
    if (isNaN(unavailableDeleteQty) || parseInt(unavailableDeleteQty) <= 0 || parseInt(unavailableDeleteQty) % 1 != 0) {
      $('.delete-btn').prop('disabled', false);
      showToast('Invalid quantity. Please enter a valid quantity.', 'Error', 'error');
      return;
    } else if (parseInt(unavailableDeleteQty) > parseInt(quantity_delete_old)) {
      $('.delete-btn').prop('disabled', false);
      showToast('Unable to set unavailable quantity more than current unavailable quantity', 'Error', 'error');
      return;
    }

    $.ajax({
      type: "POST",
      url: ApiPlugin + '/ecommerce/inventory/delete-unavailable',
      data: {
        _token: $('meta[name="csrf-token"]').attr('content'),
        stock_id: stock_id,
        location_id: location_id,
        product_id: product_id,
        stock_sku: inventory_sku,
        unavailable_status: 'delete',
        unavailable_reason: unavailableReason,
        unavailable_delete_qty: unavailableDeleteQty
      },
      beforeSend: function (xhr) {
        xhr.setRequestHeader("Authorization", "Bearer " + strkey);
        xhr.setRequestHeader("menu-uuid", menu_id);
        imgload.show();
      },
      success: function (data) {
        imgload.hide();
        if (data.status == 200) {
          showToast(data.message, 'Success', 'success', 'self');
        }
      },
      error: function (xhr, status, error) {
        let errorMessage = xhr.responseJSON.status + ': ' + xhr.responseJSON.message
        showToast(errorMessage, 'Error', 'error');
        $('.unavailable-btn').prop('disabled', false);
      }
    });

  })
  // Unavialable Delete End
}

const availableSelect = (e) => {
  let availableStatus = $(e).find("option:selected").val();
  let availableReason = $(e).parents(".available-drop").find('select[name="available_reason"]');
  if (availableStatus == 'unavailable') {
    availableReason.html('');
    availableReason.selectpicker('destroy');
    let optionsHtml = '';
    optionsHtml += '<option value="damaged">Damaged</option>';
    optionsHtml += '<option value="qualitycontrol">Quality control</option>';
    optionsHtml += '<option value="safetystock">Safety stock</option>';
    optionsHtml += '<option value="other">Other</option>';

    availableReason.html(optionsHtml); // Append the options to the select element
  } else {
    availableReason.html('');
    availableReason.selectpicker('destroy');
    let optionsHtml = '';
    optionsHtml += '<option value="correction">Correction (default)</option>';
    optionsHtml += '<option value="count">Count</option>';
    optionsHtml += '<option value="received">Received</option>';

    availableReason.html(optionsHtml); // Append the options to the select element
  }
};

const unavailableStatus = (e) => {
  // Extract data attributes from the element.
  const unavailableStatus = $(e).data('status');
  const unavailableName = $(e).data('statusname');
  const unavailableMaxqty = $(e).data('qty');

  // Cache the parent element for performance.
  const $parent = $('.unavailable-inventory').parent();

  // Update the unavailable status field.
  $parent.find('.move-available > .quantity-main > input[name="unavailable_status"]').val(unavailableStatus);

  // Update the quantity field with the maximum value.
  $parent.find('.move-available > .quantity-main > input[name="quantity_unavailable"]').attr("max", unavailableMaxqty);
  $parent.find('.move-available > .quantity-main > input[name="quantity_unavailable_old"]').val(unavailableMaxqty);

  // Update the quantity text.
  $parent.find('.move-available > .quantity-main > p').text(`Move up to ${unavailableMaxqty}`);

  // Update the add damaged title.
  $parent.find('.move-available > .add-damaged-title').text(`Move ${unavailableName} to Available`);
};

// Inventory Js Start
$("#select_location").on("click", "li", function () {
  var selectedText = $(this).text().trim();
  let selectedUUID = $(this).find('input').attr("data-uuid");
  $("#Selectlocation").attr("data-uuid", selectedUUID);
  $("#Selectlocation").text(selectedText);
  $(".select-dropdown").removeClass("show");
});

$(document).on("click", function (event) {
  if (!$(event.target).closest(".select-dropdown").length) {
    $(".select-dropdown").removeClass("show");
  }
});

$(".select-dropdown .droupdown-head").on("click", function (e) {
  e.preventDefault();
  $(this).parent().toggleClass("show");
});

// RAFAY WORK START


function handleTabButtonClick() {
  const modalButtons = document.querySelectorAll('.custom-button-browse');
  modalButtons.forEach(button => {
    button.addEventListener('click', (e) => {
      e.preventDefault(); // Prevent default anchor behavior
      const activeNavItem = document.querySelector('.nav-item.active');
      const dataFilter = activeNavItem ? activeNavItem.getAttribute('data-filter') : 'all'; // Get data-filter or 'all'
      console.log('filter', dataFilter)
      const modalInput = document.getElementById('search_type'); // Get the hidden input
      if (modalInput) {
        modalInput.value = dataFilter; // Set input value
      }
    });
  });
}

// Character Count Update
$('#duplicateModalInput').on('input', function () {
  $('#current').text($(this).val().length);
});

// Function to handle dynamic click event binding for new elements
function bindNavItemClick() {
  $(".nav-item.nav-link").off("click").on("click", function () {
    let filter = $(this).data("filter").toLowerCase();

    // Remove 'active' from all and add to the clicked one
    $(".nav-item.nav-link").removeClass("active");
    $(this).addClass("active");

    // Dynamically filter table rows
    $("#Table_View tbody tr").each(function () {
      let statusText = $(this).find("td:nth-child(3) span").text().trim().toLowerCase();
      console.log(statusText, filter);

      if (filter === "all" || statusText === filter) {
        $(this).show();
      } else {
        $(this).hide();
      }
    });
  });
}
// Initial binding for existing nav items
bindNavItemClick();

function addtabs() {
  $.ajax({
    url: ApiPlugin + '/ecommerce/product/get_filter_views', // API end point
    type: "Get",
    contentType: "application/json",
    dataType: "json",
    beforeSend: function (xhr) {
      xhr.setRequestHeader("Authorization", "Bearer " + strkey);
      xhr.setRequestHeader("menu-uuid", menu_id);
      imgload.show();
    },
    success: function (response) {
      imgload.hide();
      let data = response.data;
      let htmlLi = '';
      for (const key in data) {
        let item = data[key];
        htmlLi += `   
                <li class="nav-item nav-link" id="${item?.uuid}" data-filter="${item?.search_type?.toLowerCase()}">
                ${item.name}
                <span class="dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false"></span>
                <ul class="dropdown-menu">
                    <li>
                      <a class="dropdown-item d-flex align-items-center gap-2 custom-button-browse modify_btn"
                        data-bs-toggle="modal" href="#MaximumCharactor">
                          <svg width="16" height="16" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16">
                            <path fill="#000"
                              d="M9.25 6.5a.75.75 0 0 0-.75.75v1.25h-1.25a.75.75 0 0 0 0 1.5h1.25v1.25a.75.75 0 1 0 1.5 0v-1.25h1.25a.75.75 0 1 0 0-1.5h-1.25v-1.25a.75.75 0 0 0-.75-.75"></path>
                                <path fill="#000" fill-rule="evenodd"
                                  d="M9.25 1.5a2.75 2.75 0 0 1 2.74 2.51 2.75 2.75 0 0 1 2.51 2.74v5a2.75 2.75 0 0 1-2.75 2.75h-5a2.75 2.75 0 0 1-2.74-2.51 2.75 2.75 0 0 1-2.51-2.74v-5a2.75 2.75 0 0 1 2.75-2.75zm-2.5 2.5h3.725c-.116-.57-.62-1-1.225-1h-5c-.69 0-1.25.56-1.25 1.25v5c0 .605.43 1.11 1 1.225v-3.725a2.75 2.75 0 0 1 2.75-2.75m5 1.5c.69 0 1.25.56 1.25 1.25v5c0 .69-.56 1.25-1.25 1.25h-5c-.69 0-1.25-.56-1.25-1.25v-5c0-.69.56-1.25 1.25-1.25z">
                                </path>
                            </svg>
                            Duplicate View
                        </a>
                    </li>
                    <li>
                      <a class="dropdown-item d-flex align-items-center gap-2 custom-button-browse modify_btn"
                        data-bs-toggle="modal" href="#updateModalForm">
                          <svg width="16" height="16" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16">
                            <path fill="#000"
                              d="M9.25 6.5a.75.75 0 0 0-.75.75v1.25h-1.25a.75.75 0 0 0 0 1.5h1.25v1.25a.75.75 0 1 0 1.5 0v-1.25h1.25a.75.75 0 1 0 0-1.5h-1.25v-1.25a.75.75 0 0 0-.75-.75"></path>
                                <path fill="#000" fill-rule="evenodd"
                                  d="M9.25 1.5a2.75 2.75 0 0 1 2.74 2.51 2.75 2.75 0 0 1 2.51 2.74v5a2.75 2.75 0 0 1-2.75 2.75h-5a2.75 2.75 0 0 1-2.74-2.51 2.75 2.75 0 0 1-2.51-2.74v-5a2.75 2.75 0 0 1 2.75-2.75zm-2.5 2.5h3.725c-.116-.57-.62-1-1.225-1h-5c-.69 0-1.25.56-1.25 1.25v5c0 .605.43 1.11 1 1.225v-3.725a2.75 2.75 0 0 1 2.75-2.75m5 1.5c.69 0 1.25.56 1.25 1.25v5c0 .69-.56 1.25-1.25 1.25h-5c-.69 0-1.25-.56-1.25-1.25v-5c0-.69.56-1.25 1.25-1.25z">
                                </path>
                            </svg>
                            Update View
                        </a>
                    </li>
                    <li>
                      <a class="dropdown-item d-flex align-items-center gap-2 custom-button-browse modify_btn"
                        data-bs-toggle="modal" href="#deleteModal">
                          <svg width="16" height="16" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16">
                            <path fill="#000"
                              d="M9.25 6.5a.75.75 0 0 0-.75.75v1.25h-1.25a.75.75 0 0 0 0 1.5h1.25v1.25a.75.75 0 1 0 1.5 0v-1.25h1.25a.75.75 0 1 0 0-1.5h-1.25v-1.25a.75.75 0 0 0-.75-.75"></path>
                                <path fill="#000" fill-rule="evenodd"
                                      d="M9.25 1.5a2.75 2.75 0 0 1 2.74 2.51 2.75 2.75 0 0 1 2.51 2.74v5a2.75 2.75 0 0 1-2.75 2.75h-5a2.75 2.75 0 0 1-2.74-2.51 2.75 2.75 0 0 1-2.51-2.74v-5a2.75 2.75 0 0 1 2.75-2.75zm-2.5 2.5h3.725c-.116-.57-.62-1-1.225-1h-5c-.69 0-1.25.56-1.25 1.25v5c0 .605.43 1.11 1 1.225v-3.725a2.75 2.75 0 0 1 2.75-2.75m5 1.5c.69 0 1.25.56 1.25 1.25v5c0 .69-.56 1.25-1.25 1.25h-5c-.69 0-1.25-.56-1.25-1.25v-5c0-.69.56-1.25 1.25-1.25z">
                                </path>
                            </svg>
                            Delete View
                        </a>
                    </li>
                </ul>
            </li> `
      }
      $('#navList li').last().before(htmlLi);
      bindNavItemClick()
      handleTabButtonClick();
    }
  });
}
addtabs();

$(document).on('click', '.dropdown-item[href="#deleteModal"]', function () {
  // Get the parent list item's data-filter value
  const $navItem = $(this).closest('.nav-item');
  const textValue = $(this).closest('.nav-item').contents().filter(function () {
    return this.nodeType === 3; // Get only text nodes (ignore elements)
  }).text().trim();

  if (textValue) {
    const $createButton = $(".btn-primary.btn-modal-text");
    $createButton.prop("disabled", false);
  }
  const itemId = $navItem.attr('id');
  $('#deleteValue').text(textValue);  // For displaying text
  $('#deleteInput').val(itemId);   // For sending the value to the server
});

$(document).on('click', '.dropdown-item[href="#updateModalForm"]', function () {
  // Get the parent list item's data-filter value
  const $navItem = $(this).closest('.nav-item');
  const textValue = $(this).closest('.nav-item').contents().filter(function () {
    return this.nodeType === 3; // Get only text nodes (ignore elements)
  }).text().trim();

  if (textValue) {
    const $createButton = $(".btn-primary.btn-modal-text");
    $createButton.prop("disabled", false);
  }
  const itemId = $navItem.attr('id');
  $('.input-wrapper').val(textValue);
  $('#updateForm #the-count #current').text(textValue.length);
  $('.input-wrapper').attr('id', itemId);
});

$('#updateForm input').on('input', function () {
  $('#updateForm #the-count #current').text($(this).val().length);
});

$('#updateModalForm').on('submit', function (e) {
  e.preventDefault();
  let inputValue = $('.input-wrapper').val().trim();
  let id = $('.input-wrapper').attr('id');

  $.ajax({
    url: `${ApiPlugin}/ecommerce/product/update_filter_view`,
    type: "POST",
    contentType: "application/json",
    dataType: "json",
    data: JSON.stringify({ name: inputValue }),
    beforeSend: function (xhr) {
      xhr.setRequestHeader("Authorization", "Bearer " + strkey);
      xhr.setRequestHeader("menu-uuid", menu_id);
      xhr.setRequestHeader("uuid", id);
      imgload.show();
    },
    success: function (response) {
      console.log('Update successful:', response);
      imgload.hide();
      $('#updateModalForm').modal('hide');
      window.location.reload();
    },
    error: function (xhr, status, error) {
      console.error('Update failed:', error);
      imgload.hide(); // Hide loader on error
    }
  });
});

$('#deleteForm').on('submit', function (e) {
  e.preventDefault(); // Prevent default form submission  
  let inputValue = $('#deleteInput').val().trim();
  console.log('Deleted item ID:', inputValue);

  $.ajax({
    url: ApiPlugin + '/ecommerce/product/delete_filter_view/' + inputValue, // DELETE endpoint with ID
    type: "DELETE",
    contentType: "application/json",
    dataType: "json",
    beforeSend: function (xhr) {
      xhr.setRequestHeader("Authorization", "Bearer " + strkey);
      xhr.setRequestHeader("menu-uuid", menu_id);
      imgload.show();
    },
    success: function (response) {
      console.log('Delete successful:', response);
      imgload.hide();
      $('#deleteModal').modal('hide'); // Close modal on success

      // Optionally remove the item from the DOM
      $('#' + inputValue).remove();
    },
    error: function (xhr, status, error) {
      console.error('Delete failed:', error);
      imgload.hide();
    }
  });
});

$('#nameForm').on('submit', function (e) {
  e.preventDefault(); // Prevent default form submission

  let inputValue = $('#duplicateModalInput').val().trim();
  if (inputValue === '') return; // Exit if input is empty
  const search_type = $('#search_type').val();  // Get value from hidden input

  console.log('search_type:', search_type); // Debugging line
  console.log('Input value:', inputValue); // Debugging line

  // $.ajax({
  //   url: ApiPlugin + '/ecommerce/product/add_filter_view', // API endpoint
  //   type: "POST",
  //   contentType: "application/json",
  //   dataType: "json",
  //   data: JSON.stringify({
  //     name: inputValue,  // Pass input value
  //     search_type: search_type // Pass search_type
  //   }),
  //   beforeSend: function (xhr) {
  //     xhr.setRequestHeader("Authorization", "Bearer " + strkey); // Token
  //     xhr.setRequestHeader("menu-uuid", menu_id); // UUID Header
  //     imgload.show(); // Show loader
  //   },
  //   success: function (response) {
  //     imgload.hide(); // Hide loader
  //     window.location.reload(); // Reload page on success
  //   },
  //   error: function (xhr, status, error) {
  //     imgload.hide(); // Hide loader on error
  //     console.error('Error:', xhr.responseText);
  //   }
  // });

  // Rebind click event for dynamically added elements
  bindNavItemClick();


  // Reset the input field
  $('#duplicateModalInput').val('');
  $('#current').text('0');
});

$('.search-icon-button .save').on('click', function () {
  var searchValue = $('#custom_search').val().trim();
  const activeNavItem = document.querySelector('.nav-item.active');
  const dataFilter = activeNavItem ? activeNavItem.getAttribute('data-filter') : 'all';
  const modalInput = document.querySelector('#SaveAs #search_type');
  if (modalInput) {
    modalInput.value = dataFilter;
  }
  $('#SaveAs #searchInput').val(searchValue);
  $('#SaveAs #the-count #current').text(searchValue.length);

  if (searchValue) {
    const $createButton = $(".btn-primary.btn-modal-text");
    $createButton.prop("disabled", false);
  }
});

$('#SaveAs input').on('input', function () {
  $('#SaveAs #the-count #current').text($(this).val().length);
});

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

const fileInput = $(".import-main input[type='file']");
const importMain = $(".import-main");
const filePreview = $(".import-flex-main");
const fileNameSpan = $(".file-name-icon span");
const replaceFileInput = $(".import-flex-main input[type='file']");
const customCheckboxMain = $(".custom-checkbox-main");
// Hide file preview UI and checkbox section by default
filePreview.hide();
customCheckboxMain.hide();

fileInput.on("change", function () {
  if (this.files.length > 0) {
    const file = this.files[0];
    $('.btn.btn-primary.btn-modal-text').prop('disabled', false);
    const validExtensions = ["csv", "xlsx"];
    const fileExtension = file.name.split(".").pop().toLowerCase();

    if (validExtensions.includes(fileExtension)) {
      fileNameSpan.text(file.name);
      importMain.hide();
      filePreview.css("display", "flex");
      customCheckboxMain.show();
    } else {
      alert("Only CSV and XLSX files are allowed.");
      fileInput.val("");
    }
  }
});

replaceFileInput.on("change", function () {
  if (this.files.length > 0) {
    const file = this.files[0];
    const validExtensions = ["csv", "xlsx"];
    const fileExtension = file.name.split(".").pop().toLowerCase();

    if (validExtensions.includes(fileExtension)) {
      fileNameSpan.text(file.name);
    } else {
      alert("Only CSV and XLSX files are allowed.");
      replaceFileInput.val("");
    }
  }
});


$(document).on("click", function (e) {
  if (!$(e.target).closest(".custom-dropdown-main").length) {
    $(".custom-dropdown-main").removeClass("show");
  }
});

$(".custom-dropdown-main .droupdown-head").on("click", function (e) {
  e.preventDefault();
  $(this).parent().toggleClass("show");
  e.stopPropagation(); // Yeh click event ko document tak jane se rokay ga
});

// Initialize dropdowns
setupDropdown('saleschannelDropdown', 'status-radio');
setupDropdown('typeDropdown', 'status-checkbox');
setupDropdown('vendorDropdown', 'vendor-radio');
setupDropdown('taggedDropdown', 'category-checkbox');
setupDropdown('incomingDropdown', 'incoming-radio');
setupDropdown('committedDropdown', 'committed-checkbox');
setupDropdown('availableDropdown', 'available-checkbox');
setupDropdown('onhandDropdown', 'onhand-checkbox');
setupDropdown('unavailableDropdown', 'unavailable-checkbox');
// dropdown hide and show js end 

function setupDropdown(dropdownId, checkboxName) {
  // Hide Dropdown by default
  $(`#${dropdownId}.select-dropdown`).hide();

  // Toggle Dropdown on clicking the filter
  $(`#${dropdownId}`).on('click', function () {
    let hasSelected = $(`input[name="${checkboxName}"]:checked`).length > 0;

    // Toggle dropdown visibility if no selection is made
    if (!hasSelected) {
      $(`#${dropdownId}.select-dropdown`).toggle();
    }
  });

  // Close Dropdown if no selection is made when clicking outside
  $(document).on('click', function (e) {
    if (!$(e.target).closest(`#${dropdownId}, .select-dropdown`).length) {
      let hasSelected = $(`input[name="${checkboxName}"]:checked`).length > 0;
      if (!hasSelected) {
        $(`#${dropdownId}.select-dropdown`).hide();
      }
    }
  });
}

// $(document).ready(function () {
//   saleschannel_list();
//   vendor_list();
//   type_list();
// })

// function vendor_list() {
//   $.ajax({
//     url: ApiPlugin + '/ecommerce/product/get_product_vendors', // API end point
//     type: "Get",
//     contentType: "application/json",
//     dataType: "json",
//     beforeSend: function (xhr) {
//       xhr.setRequestHeader("Authorization", "Bearer " + strkey);
//       xhr.setRequestHeader("menu-uuid", menu_id);
//       imgload.show();
//     },
//     success: function (response) {
//       imgload.hide();

//       let data = response.data;


//       let htmlLi = '';
//       for (const key in data) {
//         let item = data[key];


//         htmlLi += ` 
//         <li>
//          <label data-filter="${item.vendor.toLowerCase()}" class="custom-radio">
//            <input name="vendor-checkbox" value="${item.vendor}" type="radio" data-column="8">${item.vendor}</label>
//         </li>`;

//       }
//       // console.log($("#saleschannel_list"))
//       $("#vendor_list").append(htmlLi);
//       $("input[name='vendor-checkbox']").on("change", function () {
//         $(this).parent().toggleClass("active");
//       });

//       $("input[name='vendor-checkbox']").on("change", function () {
//         updateSelectedValues("vendor-checkbox", "valueItem");
//       });

//       // Clear the selected radio button and clear saleschannelItem span
//       $('.clearSelection').on('click', function (e) {
//         e.preventDefault(); // Prevent default action of anchor tag
//         $('input[name="vendor-checkbox"]').prop('checked', false); // Uncheck all radio buttons
//         $('#valueItem').text(''); // Clear the marketItem span
//       });
//     }
//   });
// }

// function saleschannel_list() {
//   $.ajax({
//     url: ApiPlugin + '/ecommerce/channel/get_active_channels', // API end point
//     type: "Get",
//     contentType: "application/json",
//     dataType: "json",
//     beforeSend: function (xhr) {
//       xhr.setRequestHeader("Authorization", "Bearer " + strkey);
//       xhr.setRequestHeader("menu-uuid", menu_id);
//       imgload.show();
//     },
//     success: function (response) {
//       imgload.hide();

//       let data = response.data;


//       let htmlLi = ` 
//       <li>
//         <label data-filter="allchannel" class="custom-radio">
//           <input name="saleschannel-radio" value="allchannel" type="radio">
//             All channel
//         </label>
//       </li>`;
//       for (const key in data) {
//         let item = data[key];


//         htmlLi += ` 
//         <li>
//           <label data-filter="${item.name.toLowerCase()}" class="custom-radio">
//             <input name="saleschannel-radio" value="${item.name}" id="${item?.uuid}" type="radio">
//               ${item.name}
//           </label>
//         </li>`

//       }
//       // console.log($("#saleschannel_list"))
//       $("#saleschannel_list").append(htmlLi);
//       $("input[name='saleschannel-radio']").on("change", function () {
//         // $(this).parent().toggleClass("active");
//         var selectedValue = $(this).val(); // Get the selected radio button value
//         $('#saleschannelItem').text(selectedValue);
//       });


//       $(".clearSelection[data-target='saleschannel-radio']").on("click", function (e) {
//         e.preventDefault();
//         $(".custom-checkbox").removeClass("active");
//         $("input[name='saleschannel-radio']").prop("checked", false);
//       });

//       // Clear the selected radio button and clear saleschannelItem span
//       $('.clearSelection').on('click', function (e) {
//         e.preventDefault(); // Prevent default action of anchor tag
//         $('input[name="saleschannel-radio"]').prop('checked', false); // Uncheck all radio buttons
//         $('#saleschannelItem').text(''); // Clear the marketItem span
//       });
//     }
//   });
// }

// function type_list() {
//   $.ajax({
//     url: ApiPlugin + '/ecommerce/product/get_product_types', // API end point
//     type: "Get",
//     contentType: "application/json",
//     dataType: "json",
//     beforeSend: function (xhr) {
//       xhr.setRequestHeader("Authorization", "Bearer " + strkey);
//       xhr.setRequestHeader("menu-uuid", menu_id);
//       imgload.show();
//     },
//     success: function (response) {
//       imgload.hide();
//       let data = response.data;
//       let htmlLi = '';
//       for (const key in data) {
//         let item = data[key];
//         htmlLi += ` 
//           <li>
//             <label data-filter="${item?.type?.toLowerCase()}"  class="custom-radio">
//               <input name="type-radio" value="${item?.type}" type="radio">
//                 ${item?.type}
//             </label>
//           </li>`;
//       }
//       // console.log($("#saleschannel_list"))
//       $("#type_list").append(htmlLi);
//       $("input[name='type-radio']").on("change", function () {
//         // $(this).parent().toggleClass("active");
//         var selectedValue = $(this).val(); // Get the selected radio button value
//         $('#typeItem').text(selectedValue);
//       });

//       // Clear the selected radio button and clear saleschannelItem span
//       $('.clearSelection').on('click', function (e) {
//         e.preventDefault(); // Prevent default action of anchor tag
//         $('input[name="type-radio"]').prop('checked', false); // Uncheck all radio buttons
//         $('#typeItem').text(''); // Clear the marketItem span
//       });
//     }
//   });
// }

function updateSelectedValues(checkboxName, displayElementId) {
  let selectedValues = [];
  $(`input[name='${checkboxName}']:checked`).each(function () {
    selectedValues.push($(this).val());
  });

  // Display selected values or clear if none selected
  if (selectedValues.length > 0) {
    $(`#${displayElementId}`).text(`(${selectedValues.join(", ")})`);
  } else {
    $(`#${displayElementId}`).text("");
  }
}
function refreshSelect(){
    $('.select2').select2({
    allowClear: false,
    width: '100%',
    minimumResultsForSearch: Infinity
  });
}

$(".paginate_button").on("click", function (e) {
  refreshSelect();
});

// Inventory Js End




