document.title = "Dashboard | Collections";
let PageNo = 1;
let PageSize = 10;
let totalRecord = 0;
let PageLength = 0;
let Search = '';
let keyupTimer;
$(document).ready(function(){
  get_collection();
  get_active_channel_included();
  get_active_channel_excluded();
});

const searchOpen = $("#searchOpen");
const searchBox = $("#Table_View_filter"); // Search bar container
const saveCancelBtns = $(".search-icon-button"); // Save as & Cancel buttons
const customtaborder = $(".custom-tab-order");
const selectDropdown = $(".selectDropdown");

// Default state: searchOpen visible, searchBox and buttons hidden
searchBox.hide();
saveCancelBtns.hide();
selectDropdown.hide();
searchOpen.show();

searchOpen.on("click", function () {
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

cancelBtn.on("click", function () {
  // console.log("Cancel button clicked!");
  searchBox.hide(); // Hide search bar
  saveCancelBtns.hide(); // Hide buttons
  selectDropdown.hide(); // Hide buttons
  searchOpen.show(); // Show searchOpen button again
  customtaborder.css("display", "inline-flex"); // Show customtaborder
});

const $inputField = $("#duplicateModalInput");
const $currentCount = $("#the-count #current");
const maxLength = 40;

$inputField.on("input", function () {
  let count = $(this).val().length;
  $currentCount.text(count);

  if (count > maxLength) {
    $(this).val($(this).val().substring(0, maxLength));
    $currentCount.text(maxLength);
  }
});

$inputField.on("keydown", function (e) {
  let count = $(this).val().length;

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

$(document).on("click", function (event) {
  if (!$(event.target).closest(".select-dropdown").length) {
    $(".select-dropdown").removeClass("show");
  }
  if (!$(event.target).closest(".droupdown-body").length) {
    $(".droupdown-body").removeClass("show");
  }
});

$(".custom-dropdown-main .droupdown-head").on("click", function (e) {
  e.preventDefault();
  $(".droupdown-body").not($(this).parent()).removeClass("show");
  $(this).parent().toggleClass("show");
});

$(document).on("click", function (e) {
  if (!$(e.target).closest(".custom-dropdown-main").length) {
    $(".custom-dropdown-main").removeClass("show");
  }
});

$(".lodest-first a").on('click', function (e) {
  e.preventDefault();

  $(this).siblings().removeClass("active");
  $(this).addClass("active");
});

// Sales channel dropdown
$(".salesChannelDropdown").on('change', '.droupdown-body .custom-radio input[type=radio]', function (e) {
  let inEx = $(this).parents(".salesChannelDropdown").find(".included-excluded li.active").text();
  $(this).parents(".salesChannelDropdown").find(".droupdown-head > span:not(.clear-drop):not(.arrow)").html(`${inEx} in ${$(this).val()}`);
  $(this).parents(".salesChannelDropdown").find(".clear-drop").removeClass("d-none");
  $(this).parents(".salesChannelDropdown").find(".arrow").addClass("d-none");
  $(this).parents(".salesChannelDropdown").find(".included-excluded").removeClass("d-none");
});

$(".salesChannelDropdown").on('click', '.droupdown-body .included-excluded li', function (e) {
  $(this).siblings().removeClass("active");
  $(this).addClass("active");
  $(this).parents(".salesChannelDropdown").find(".droupdown-body .custom-radio input[type=radio]:checked").trigger("change");
  $(this).find("input").prop("checked", true)
});

$(".salesChannelDropdown").on('click', '.clear-drop', function (e) {
  e.preventDefault();
  $(this).parents(".salesChannelDropdown").find(".droupdown-body input[type=radio]").prop('checked', false);
  $(this).parents(".salesChannelDropdown").find(".included-excluded").addClass("d-none");
  $(this).parents(".salesChannelDropdown").find(".droupdown-head > span:not(.clear-drop):not(.arrow)").html("Sales channel");
  $(this).parents(".salesChannelDropdown").find(".clear-drop").addClass("d-none");
  $(this).parents(".salesChannelDropdown").find(".arrow").removeClass("d-none");
});

$(".salesChannelDropdown .droupdown-body a").on("click", function(e){
  e.preventDefault();
  $(this).parent().find("ul:not(.included-excluded) input").prop("checked", false);
  $(this).parent().find(".included-excluded").addClass("d-none");
  $(this).parents(".salesChannelDropdown").find(".droupdown-head > span:not(.clear-drop):not(.arrow)").html("Sales channel");
  $(this).parents(".salesChannelDropdown").find(".clear-drop").addClass("d-none");
  $(this).parents(".salesChannelDropdown").find(".arrow").removeClass("d-none");
});
// Sales channel dropdown end

// Type dropdown end
$(".typeDropdown").on('change', '.droupdown-body .custom-radio input[type=radio]', function (e) {
  $(this).parents(".typeDropdown").find(".droupdown-head > span > span").html(`is ${$(this).val()}`);
  $(this).parents(".typeDropdown").find(".clear-drop").removeClass("d-none");
  $(this).parents(".typeDropdown").find(".arrow").addClass("d-none");
});

$(".typeDropdown").on('click', '.clear-drop', function (e) {
  e.preventDefault();
  $(this).parents(".typeDropdown").find(".droupdown-body input[type=radio]").prop('checked', false);
  $(this).parents(".typeDropdown").find(".droupdown-head > span > span").html("");
  $(this).parents(".typeDropdown").find(".clear-drop").addClass("d-none");
  $(this).parents(".typeDropdown").find(".arrow").removeClass("d-none");
});

$(".typeDropdown").on('click', '.droupdown-body a', function (e) {
  e.preventDefault();
  $(this).parent().find("input[type=radio]").prop('checked', false);
  $(this).parents(".typeDropdown").find(".droupdown-head > span > span").html("");
  $(this).parents(".typeDropdown").find(".clear-drop").addClass("d-none");
  $(this).parents(".typeDropdown").find(".arrow").removeClass("d-none");
});
// Type dropdown end

$("#Table_View").on("change", ".select-all", function(){
  if($(this).prop("checked") === true) {
    $("#Table_View .thead-filter").removeClass("d-none");
    $("#Table_View .form-check-input:not(.select-all)").prop("checked", true);
  } else {
    $("#Table_View .thead-filter").addClass("d-none");
    $("#Table_View .form-check-input:not(.select-all)").prop("checked", false);
  }
  isAnyChecked();
});

$("#Table_View").on("change", ".form-check-input:not(.select-all)", function() {
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

  if(parseInt(totalCheckedCount) > 0) {
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

function get_collection() {
  $.ajax({
      url: ApiPlugin + '/ecommerce/collection',
      type: "GET",
      contentType: "application/json",
      dataType: "json",
      beforeSend: function (xhr) {
          xhr.setRequestHeader("Authorization", "Bearer " + strkey);
          xhr.setRequestHeader("menu-uuid", menu_id);
          imgload.show();
      },
      success: function (response) {
          if (response.status_code === 200) {
              if (response.data.length != 0) {
                  const columnsConfig = [
                      {
                          data: 'uuid',
                          visible: false,
                      },
                      {
                          data: 'name',
                          render: function(data, type, row) {
                              return `<div class="collection-table-info">
                                  <div class="img">
                                      ${row.image != "" && row.image != null ? `<img src="${AssetsPath + row.image}" alt="collection">` : `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" width="16" height="16" fill="#8a8a8a"><path d="M10.5 7a1.5 1.5 0 1 0-.001-3.001 1.5 1.5 0 0 0 .001 3.001"></path><path fill-rule="evenodd" d="M7.018 1.5h1.964c.813 0 1.469 0 2 .043.546.045 1.026.14 1.47.366.706.36 1.28.933 1.64 1.639.226.444.32.924.365 1.47.043.531.043 1.187.043 2v1.964c0 .813 0 1.469-.043 2-.045.546-.14 1.026-.366 1.47a3.76 3.76 0 0 1-1.638 1.64c-.445.226-.925.32-1.471.365-.531.043-1.187.043-2 .043h-1.964c-.813 0-1.469 0-2-.043-.546-.045-1.026-.14-1.47-.366a3.76 3.76 0 0 1-1.64-1.638c-.226-.445-.32-.925-.365-1.471-.043-.531-.043-1.187-.043-2v-1.964c0-.813 0-1.469.043-2 .045-.546.14-1.026.366-1.47.36-.706.933-1.28 1.639-1.64.444-.226.924-.32 1.47-.365.531-.043 1.187-.043 2-.043m-1.877 1.538c-.454.037-.715.107-.912.207a2.25 2.25 0 0 0-.984.984c-.1.197-.17.458-.207.912-.037.462-.038 1.057-.038 1.909v1.429l.723-.868a1.75 1.75 0 0 1 2.582-.117l2.695 2.695 1.18-1.18a1.75 1.75 0 0 1 2.604.145l.216.27v-2.374c0-.852 0-1.447-.038-1.91-.037-.453-.107-.714-.207-.911a2.25 2.25 0 0 0-.984-.984c-.197-.1-.458-.17-.912-.207-.462-.037-1.057-.038-1.909-.038h-1.9c-.852 0-1.447 0-1.91.038Zm-2.103 7.821-.006-.08.044-.049 1.8-2.159a.25.25 0 0 1 .368-.016l3.226 3.225a.75.75 0 0 0 1.06 0l1.71-1.71a.25.25 0 0 1 .372.021l1.213 1.516q-.032.09-.07.165c-.216.423-.56.767-.984.983-.197.1-.458.17-.912.207-.462.037-1.057.038-1.909.038h-1.9c-.852 0-1.447 0-1.91-.038-.453-.037-.714-.107-.911-.207a2.25 2.25 0 0 1-.984-.984c-.1-.197-.17-.458-.207-.912"></path></svg>`}
                                  </div>
                                  <div>
                                      <a href="#" class="title">${data}</a>
                                  </div>
                              </div>`;
                          }
                      },
                      {
                          data: 'products_count',
                          render: function(data, type, row) {
                              return data;
                          }
                      },
                      {
                          data: 'conditions',
                          render: function(data, type, row) {
                              if(data !== null && data !== "null" && data !== "[null]") {
                                  let conditionData = "";

                                  if(JSON.parse(data).length >= 3) {
                                      JSON.parse(data).slice(0, 3).forEach(element => {
                                          conditionData += `<div class="condition-txt">${element.split(",")[0]} - ${element.split(",")[1]} - ${element.split(",")[2]}</div>`
                                      });
                                      conditionData += `<div class="condition-txt">... and ${JSON.parse(data).length - 3} more</div>`
                                  } else {
                                      JSON.parse(data).forEach(element => {
                                          conditionData += `<div class="condition-txt">${element.split(",")[0]} - ${element.split(",")[1]} - ${element.split(",")[2]}</div>`
                                      });
                                  }

                                  return conditionData;
                              } else {
                                  return ``;
                              }
                          }
                      },
                      {
                          data: 'smart',
                          visible: false,
                          render: function(data, type, row) {
                              return data == "1" ? "Smart" : "Manual";
                          }
                      },
                      {
                          data: null,
                          render: function(data, type, row) {
                              return ``;
                          },
                          className: 'p-0'
                      },{
                        data: 'created_at',
                        visible: false,
                      }
                  ];
                  
                  tableData = response["data"];
          // tableData = data;
          // let configFilter = {
          //   searching: true,
          //   paging: false,
          //   info: false,
          //   bLengthChange: false,
          //   responsive: false,
          // };
          // let tableSearch = makeDataTable('#Table_View', tableData, columnsConfig, {
          //   showButtons: false,
          //   responsive: false,
          // }, configFilter);


          let configFilter = {
            searching: true,
            paging: false,
            info: true,
            bLengthChange: false,
            responsive: false,
            dom: 'B',
            buttons: [
              'csv',
              'excel',
            ]
          };
          // let tableSearch = $('#Table_View').DataTable({
          //   data: tableData,  // Assuming tableData is an array of row data
          //   columns: columnsConfig,  // Assuming columnsConfig is an array of column definitions
          //   showButtons: true,
          //   responsive: false,
          //   ...configFilter,// Spread operator to merge additional configurations
          //   initComplete: function () {
          //     $('.dt-buttons').hide();
          //   }
          // });

          // $('#export-product').on('click', function () {
          //   tableSearch.button(0).trigger(); // 0 = first export button (Excel)
          // });


          // EXPORT WORKING CODE
          let tableSearch = $('#Table_View').DataTable({
            data: tableData,
            columns: columnsConfig,
            responsive: false,
            dom: 'Bfrtip',
            buttons: [
              {
                extend: 'excel',
                text: 'Export to Excel',
                exportOptions: {
                  modifier: { page: 'all' } // Default: Export all products
                }
              },
              {
                extend: 'csv',
                text: 'Export to CSV',
                exportOptions: {
                  modifier: { page: 'all' } // Default: Export all products
                }
              }
            ],
            ...configFilter,
            initComplete: function () {
              $('.dt-buttons').hide();
            }
          });

          $('#export-product').prop("disabled", false); // Enable the button

          // Handle export button click
          $('#export-product').on('click', function () {
            let exportType = $('input[name="all"]:checked').val(); // Get selected radio button value

            // Remove old buttons
            // tableSearch.buttons().destroy();

            console.log('exportType', exportType);
            // Reinitialize buttons with updated exportOptions
            tableSearch.buttons([
              {
                extend: 'excel',
                text: 'Export to Excel',
                exportOptions: {
                  modifier: { page: exportType === "current" ? 'current' : 'all' }
                }
              },
              {
                extend: 'csv',
                text: 'Export to CSV',
                exportOptions: {
                  modifier: { page: exportType === "current" ? 'current' : 'all' }
                }
              }
            ]).container().appendTo($('.dt-buttons'));

            // Trigger the export (0 = Excel, 1 = CSV)
            tableSearch.button(0).trigger();
          });
          // EXPORT WORKING CODE END


          $(".dataTables_filter").hide();
          $('#custom_search').keyup(function () {
            tableSearch.search($(this).val()).draw();
          });
                  
                  // custom search input
                  $('#custom_search').on('input', function () {
                      $('#Table_View').DataTable().column(1).search(this.value).draw(); // column index 1 = 'name'
                  });
                  
                  // Filter by type
                  $('[name=type]').on('change', function () {
                      $('#Table_View').DataTable().column(4).search($(this).val()).draw();
                  });
                  $('.typeDropdown .clear-drop, .typeDropdown .droupdown-body a').on('click', function () {
                      $('#Table_View').DataTable().column(4).search("").draw();
                  });
                  
                  // A-Z sorting
                  var selectedColumn = 1;
                  var isDateColumn = false;

                  $(".custom-dropdown-main input[type=radio][name=sort-option]").on("change", function() {
                      selectedColumn = $(this).val();
                      isDateColumn = (selectedColumn === "2"); 
                      
                      const sortAZ = $("#sortAZ");
                      const sortZA = $("#sortZA");
                      
                      if (isDateColumn) {
                          sortAZ.html(`<svg width="16" height="16" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16"> <path fill-rule="evenodd" d="M8 14.5a.75.75 0 0 1-.75-.75v-9.69l-2.72 2.72a.749.749 0 1 1-1.06-1.06l4-4a.747.747 0 0 1 1.06 0l4 4a.749.749 0 1 1-1.06 1.06l-2.72-2.72v9.69a.75.75 0 0 1-.75.75"> </path> </svg> Oldest first`);
                          sortZA.html(`<svg width="16" height="16" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16"> <path fill-rule="evenodd" d="M8 1.5a.75.75 0 0 1 .75.75v9.69l2.72-2.72a.749.749 0 1 1 1.06 1.06l-4 4a.747.747 0 0 1-1.06 0l-4-4a.749.749 0 1 1 1.06-1.06l2.72 2.72v-9.69a.75.75 0 0 1 .75-.75"> </path></svg> Newest first`);
                      } else {
                          sortAZ.html(`<svg width="16" height="16" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16"> <path fill-rule="evenodd" d="M8 14.5a.75.75 0 0 1-.75-.75v-9.69l-2.72 2.72a.749.749 0 1 1-1.06-1.06l4-4a.747.747 0 0 1 1.06 0l4 4a.749.749 0 1 1-1.06 1.06l-2.72-2.72v9.69a.75.75 0 0 1-.75.75"> </path> </svg> A-Z`);
                          sortZA.html(`<svg width="16" height="16" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16"> <path fill-rule="evenodd" d="M8 1.5a.75.75 0 0 1 .75.75v9.69l2.72-2.72a.749.749 0 1 1 1.06 1.06l-4 4a.747.747 0 0 1-1.06 0l-4-4a.749.749 0 1 1 1.06-1.06l2.72 2.72v-9.69a.75.75 0 0 1 .75-.75"> </path></svg> Z-A`);
                      }
                  });

                  $("#sortAZ").on("click", function (e) {
                      e.preventDefault();
                      const table = $('#Table_View').DataTable();
                      if (isDateColumn) {
                          table.order([selectedColumn, 'desc']).draw();
                      } else {
                          table.order([selectedColumn, 'asc']).draw();
                      }
                  });

                  $("#sortZA").on("click", function (e) {
                      e.preventDefault();
                      const table = $('#Table_View').DataTable();
                      if (isDateColumn) {
                          table.order([selectedColumn, 'asc']).draw();
                      } else {
                          table.order([selectedColumn, 'desc']).draw();
                      }
                  });

                  imgload.hide();
              } else {
                  $("#Table_View").DataTable({
                      destroy: true,
                      retrieve: true,
                      ordering: false,
                      dom: "lrt",
                      bLengthChange: false,
                      oLanguage: {
                          sEmptyTable:
                              `<img class='dataTable-custom-empty-icon' src='/images/no-record-found.svg'><br>${tbNoFound}`,
                      },
                      order: [[4, 'desc']]
                  });
                  imgload.hide();
              }
          } else {
              imgload.hide();
              showToast(response.message, 'warning', 'warning');
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
  });
}

function get_active_channel_included() {
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
        const sales_checkboxs = $('#includeInManageSalesChannels #sales-checkboxs');
        sales_checkboxs.empty();
        let htmlLi = ""
        response.data.forEach(channels => {
          htmlLi += `<div class="form-check mb-4">
                <label class="form-check-label">
                  <input class="form-check-input" type="checkbox" value="${channels.uuid}" name="getChannelId[]">
                  <span>${channels.name}</span>
                </label>
              </div>`;
        });
        sales_checkboxs.html(htmlLi);
        $("#includeInManageSalesChannels .manage-sales-select checkednumber").text($("#includeInManageSalesChannels #sales-checkboxs input[type='checkbox']:checked").length);

        $('#includeInManageSalesChannels .sales-checkbox input[type="checkbox"]').on('change', function () {
          let modalBody = $(this).closest('.modal-body');
          let totalCheckboxes = modalBody.find('input[type="checkbox"]').length;
          let checkedCount = modalBody.find('input[type="checkbox"]:checked').length;

          modalBody.find('checkednumber').text(checkedCount);

          if (checkedCount === totalCheckboxes) {
            $('#includeInManageSalesChannels .manage-sales-text').addClass('deselect');
            $('#includeInManageSalesChannels .manage-sales-text').text('Deselect all');
          } else {
            $('#includeInManageSalesChannels .manage-sales-text').removeClass('deselect');
            $('#includeInManageSalesChannels .manage-sales-text').text('Select all');
          }

          if($('#includeInManageSalesChannels .sales-checkbox input[type="checkbox"]:checked').length === 0) {
            $('#includeInManageSalesChannels #salesChannel').prop("disabled", true);
          } else {
            $('#includeInManageSalesChannels #salesChannel').prop("disabled", false);
          }
        });

        $('#includeInManageSalesChannels #salesChannel').on('click', function () {
          
        });

        $('#includeInManageSalesChannels .manage-sales-text').on('click', function () {
          if (!$(this).hasClass('deselect')) {
            $(this).addClass('deselect');
            $(this).text('Deselect all');
            $('#includeInManageSalesChannels .sales-checkbox input[type="checkbox"]').prop('checked', true);
            let checkedCount = $(this).parents('.modal-body').find('input[type="checkbox"]:checked').length;
            $(this).parents('.modal-body').find('checkednumber').text(checkedCount);
          } else {
            $(this).removeClass('deselect');
            $(this).text('Select all');
            $('#includeInManageSalesChannels .sales-checkbox input[type="checkbox"]').prop('checked', false);
            let checkedCount = 0;
            $(this).parents('.modal-body').find('checkednumber').text(checkedCount);
          }

          if($('#includeInManageSalesChannels .sales-checkbox input[type="checkbox"]:checked').length === 0) {
            $('#includeInManageSalesChannels #salesChannel').prop("disabled", true);
          } else {
            $('#includeInManageSalesChannels #salesChannel').prop("disabled", false);
          }
        });

        if($('#includeInManageSalesChannels .sales-checkbox input[type="checkbox"]:checked').length === 0) {
          $('#includeInManageSalesChannels #salesChannel').prop("disabled", true);
        } else {
          $('#includeInManageSalesChannels #salesChannel').prop("disabled", false);
        }
      } else {
        console.error('Unexpected response:', response);
      }
    },
    error: function (xhr, status, error) {
      console.error('Error fetching categories:', status, error);
    }
  });
}

function get_active_channel_excluded() {
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
        const sales_checkboxs = $('#excludeInManageSalesChannels #sales-checkboxs');
        sales_checkboxs.empty();
        let htmlLi = ""
        response.data.forEach(channels => {
          htmlLi += `<div class="form-check mb-4">
                <label class="form-check-label">
                  <input class="form-check-input" type="checkbox" value="${channels.uuid}" name="getChannelId[]">
                  <span>${channels.name}</span>
                </label>
              </div>`;
        });
        sales_checkboxs.html(htmlLi);
        $("#excludeInManageSalesChannels .manage-sales-select checkednumber").text($("#excludeInManageSalesChannels #sales-checkboxs input[type='checkbox']:checked").length);

        $('#excludeInManageSalesChannels .sales-checkbox input[type="checkbox"]').on('change', function () {
          let modalBody = $(this).closest('.modal-body');
          let totalCheckboxes = modalBody.find('input[type="checkbox"]').length;
          let checkedCount = modalBody.find('input[type="checkbox"]:checked').length;

          modalBody.find('checkednumber').text(checkedCount);

          if (checkedCount === totalCheckboxes) {
            $('#excludeInManageSalesChannels .manage-sales-text').addClass('deselect');
            $('#excludeInManageSalesChannels .manage-sales-text').text('Deselect all');
          } else {
            $('#excludeInManageSalesChannels .manage-sales-text').removeClass('deselect');
            $('#excludeInManageSalesChannels .manage-sales-text').text('Select all');
          }

          if($('#excludeInManageSalesChannels .sales-checkbox input[type="checkbox"]:checked').length === 0) {
            $('#excludeInManageSalesChannels #salesChannel').prop("disabled", true);
          } else {
            $('#excludeInManageSalesChannels #salesChannel').prop("disabled", false);
          }
        });

        $('#excludeInManageSalesChannels #salesChannel').on('click', function () {
          
        });

        $('#excludeInManageSalesChannels .manage-sales-text').on('click', function () {
          if (!$(this).hasClass('deselect')) {
            $(this).addClass('deselect');
            $(this).text('Deselect all');
            $('#excludeInManageSalesChannels .sales-checkbox input[type="checkbox"]').prop('checked', true);
            let checkedCount = $(this).parents('.modal-body').find('input[type="checkbox"]:checked').length;
            $(this).parents('.modal-body').find('checkednumber').text(checkedCount);
          } else {
            $(this).removeClass('deselect');
            $(this).text('Select all');
            $('#excludeInManageSalesChannels .sales-checkbox input[type="checkbox"]').prop('checked', false);
            let checkedCount = 0;
            $(this).parents('.modal-body').find('checkednumber').text(checkedCount);
          }

          if($('#excludeInManageSalesChannels .sales-checkbox input[type="checkbox"]:checked').length === 0) {
            $('#excludeInManageSalesChannels #salesChannel').prop("disabled", true);
          } else {
            $('#excludeInManageSalesChannels #salesChannel').prop("disabled", false);
          }
        });

        if($('#excludeInManageSalesChannels .sales-checkbox input[type="checkbox"]:checked').length === 0) {
          $('#excludeInManageSalesChannels #salesChannel').prop("disabled", true);
        } else {
          $('#excludeInManageSalesChannels #salesChannel').prop("disabled", false);
        }
      } else {
        console.error('Unexpected response:', response);
      }
    },
    error: function (xhr, status, error) {
      console.error('Error fetching categories:', status, error);
    }
  });
}