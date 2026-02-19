document.title = "Dashboard | Product";
let PageNo = 1;
let PageSize = 1;
let totalRecord = 0;
let PageLength = 0;
let Search = '';
let keyupTimer;
$(document).ready(function () {
  btnnew = $('#btn_new');
  btnexport = $('#export-products');
  btnimport = $('#import-products');
  // btnexport.hide();
  // btnimport.hide();
  // btnnew.hide();
  btnnew.click(function (e) {
    e.preventDefault();
    let page = 'add-product';
    window.location.assign('?P=' + page + '&M=' + menu_id);
  });

  $('#pageLengthSelect').on('change', function () {
    per_page = $(this).val();
    Onload(1);
    // tableSearch.page.len(per_page).draw();
  });

  $('#ImportModal').on('hidden.bs.modal', function () {
    // Reset file input
    $('#import-file').val('');
    // Reset file name display
    $('.file-name-icon span').text('');
    // Hide file preview and show initial "Add File" UI
    $('.import-main').show();
    $('.import-flex-main').hide();
    $('.custom-checkbox-main').hide();
    // Clear error messages
    $('.error-messages').text('');
    // Reset checkboxes
    $('#chk_overwrite').prop('checked', false);
    $('#chk_publish').prop('checked', false);
});


  $('#btn_delete_all').click(function (e) {
    e.preventDefault(); 
    emptyTables();
  });
function emptyTables(){

$.ajax({
      url: ApiPlugin + '/ecommerce/product/emptyTables', // API end point
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
        let message = response.message;
       console.log(message);
       location.reload();
      }
    });
  }

  $('#export-products').click(function (e) {
    e.preventDefault(); 
    export_products();
  });

  $('#export-sample-product').click(function (e) {
        e.preventDefault();
        window.location.href =   AssetsPath + 'uploads/sample_products.xlsx';
        
    });

//   $('#export-products').click(function (e) {
//     e.preventDefault(); 
//     const token = strkey;
//     const menu_uuid = menu_id;
//     const exportUrl = `${ApiPlugin}/ecommerce/product/export-products?token=${token}&M=${menu_uuid}`;

//     const link = document.createElement('a');
//     link.href = exportUrl;
//     console.log(exportUrl);
//     link.download = 'products.csv';
//     document.body.appendChild(link);
//     link.click();
//     document.body.removeChild(link);
// });
function export_products(){

$.ajax({
      url: ApiPlugin + '/ecommerce/product/export-products', // API end point
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
        let message = response.message;
       if (response.success && response.url) {
        // Trigger the download
        console.log(response.url);
        window.location.href = response.url;
      } else {
        alert('Export failed: No file URL returned.');
      }
      // location.reload();
      }
    });
  }
  // alert();
  // 

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
      const validExtensions = ["xlsx"];
      const fileExtension = file.name.split(".").pop().toLowerCase();

      if (validExtensions.includes(fileExtension)) {
        fileNameSpan.text(file.name);
        importMain.hide();
        filePreview.css("display", "flex");
        customCheckboxMain.show();
      } else {
        alert("Only XLSX file is allowed.");
        fileInput.val("");
      }
    }
  });

  replaceFileInput.on("change", function () {
    if (this.files.length > 0) {
      const file = this.files[0];
      const validExtensions = ["xlsx"];
      const fileExtension = file.name.split(".").pop().toLowerCase();

      if (validExtensions.includes(fileExtension)) {
        fileNameSpan.text(file.name);
        $('#import-file').val(''); // Clear the main file input
        $('#import-file')[0].files = this.files; // Sync the replace file input with the main file input
      } else {
        alert("Only XLSX files is allowed.");
        this.value = ""; // Clear the replace file input
      }
    }
  });

  // RAFAY WORK START


  function handleTabButtonClick() {
    const modalButtons = document.querySelectorAll('.custom-button-browse');
    modalButtons.forEach(button => {
      button.addEventListener('click', (e) => {
        e.preventDefault(); // Prevent default anchor behavior
        const activeNavItem = document.querySelector('.nav-item.active');
        const dataFilter = activeNavItem ? activeNavItem.getAttribute('data-filter') : 'all'; // Get data-filter or 'all'
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
        // console.log('Update successful:', response);
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
    // console.log('Deleted item ID:', inputValue);

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
        // console.log('Delete successful:', response);
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

    $.ajax({
      url: ApiPlugin + '/ecommerce/product/add_filter_view', // API endpoint
      type: "POST",
      contentType: "application/json",
      dataType: "json",
      data: JSON.stringify({
        name: inputValue,  // Pass input value
        search_type: search_type // Pass search_type
      }),
      beforeSend: function (xhr) {
        xhr.setRequestHeader("Authorization", "Bearer " + strkey); // Token
        xhr.setRequestHeader("menu-uuid", menu_id); // UUID Header
        imgload.show(); // Show loader
      },
      success: function (response) {
        imgload.hide(); // Hide loader
        window.location.reload(); // Reload page on success
      },
      error: function (xhr, status, error) {
        imgload.hide(); // Hide loader on error
        console.error('Error:', xhr.responseText);
      }
    });

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

  $('#ImportModal #import-form').on('submit', function (e) {
    e.preventDefault(); // Prevent default form submission

    let fileInput = $('#import-file')[0]; // Get the raw DOM element
    let formData = new FormData();  // Create a new FormData object   
   // console.log($('#chk_overwrite').val());
    if (fileInput.files.length > 0) {
       formData.append('excel_file', fileInput.files[0]); // Append the file
       formData.append('overwrite',  $('#chk_overwrite').is(':checked'));
       formData.append('publish', $('#chk_publish').is(':checked'));
    } else {
      alert("Please select a file to upload.");
      return;
    }

    $.ajax({
      url: ApiPlugin + '/ecommerce/product/import_products', // API endpoint
      type: "POST",
      data: formData, // Data to send
      contentType: false, // No content type
      processData: false, // Prevent jQuery from processing the data
      beforeSend: function (xhr) {
        xhr.setRequestHeader("Authorization", "Bearer " + strkey); // Token
        xhr.setRequestHeader("menu-uuid", menu_id); // UUID Header
        imgload.show(); // Show loader
      },
      success: function (response) {
        imgload.hide(); // Hide loader on success
        $('#ImportModal').modal('hide'); // Close modal on success
        showToast(response.message, 'Success', 'success');
        Onload();
        $('#import-file').val('');
        $('.file-name-icon span').text('');
        $('.import-main').show();
        $('.import-flex-main').hide();
        $('.custom-checkbox-main').hide();
        $('.error-messages').text('');
        $('#chk_overwrite').prop('checked', false);
        $('#chk_publish').prop('checked', false);
      },
      error: function (xhr, status, error) {
        imgload.hide(); // Hide loader on error
        console.error('Error:', xhr.responseText);
        $('.error-messages').empty();
         try {
              const res = JSON.parse(xhr.responseText);
              if (res.message) {
                  $('.error-messages').append(res.message);
              } else {
                  $('.error-messages').text('An unknown error occurred.');
              }
          } catch (e) {
              alert('Unexpected server error.');
          }
      }
    });
  });


  // RAFAY WORK END

  // $("ul li .custom-checkbox").on("click", function () {
  //     let filter = $(this).data("filter");

  //     $(this).addClass("active");


  //     $("#Table_View tbody tr").each(function () {
  //         let statusText = $(this).find("td:nth-child(3) span").text().trim().toLowerCase();

  //         if (filter === "all") {
  //             $(this).show();
  //         } else if (filter === "active" && statusText === "active") {
  //             $(this).show();
  //         } else if (filter === "draft" && statusText === "draft") {
  //             $(this).show();

  //         } else if (filter === "archived" && statusText === "archived") {
  //             $(this).show();
  //         } else {
  //             $(this).hide();
  //         }
  //     });
  // });




  // Status filter logic on checkbox click
  $("input[name='status-checkbox']").on("click", function () {
    $(this).parent().toggleClass("active");
    applyFilters();
  });

  // Vendor filter logic on checkbox click
  // $("input[name='vendor-checkbox']").on("click", function () {
  //     $(this).parent().toggleClass("active");
  //     applyFilters();
  // });


  // $("input[name='category-checkbox']")?.on("click", function () {
  //     $(this).parent().toggleClass("active");
  //     applyFilters();
  // });

  // Clear Status Filters
  $(".clearSelection[data-target='status-checkbox']").on("click", function (e) {
    e.preventDefault();
    $(".custom-checkbox").removeClass("active");
    $("input[name='status-checkbox']").prop("checked", false);
    applyFilters();
  });

  // Clear Vendor Filters
  $(".clearSelection[data-target='vendor-checkbox']").on("click", function (e) {
    e.preventDefault();
    $(".custom-checkbox").removeClass("active");
    $("input[name='vendor-checkbox']").prop("checked", false);
    applyFilters();
  });

  // Clear Category Filters
  $(".clearSelection[data-target='category-checkbox']").on("click", function (e) {
    e.preventDefault();
    $(".custom-checkbox").removeClass("active");
    $("input[name='category-checkbox']").prop("checked", false);
    applyFilters();
  });

  $(".clearSelection[data-target='market-radio']").on("click", function (e) {
    e.preventDefault();
    $(".custom-checkbox").removeClass("active");
    $("input[name='market-radio']").prop("checked", false);
    applyFilters();
  });

  $(".clearSelection[data-target='saleschannel-radio']").on("click", function (e) {
    e.preventDefault();
    $(".custom-checkbox").removeClass("active");
    $("input[name='saleschannel-radio']").prop("checked", false);
    applyFilters();
  });

  $(".clearSelection[data-target='type-checkbox']").on("click", function (e) {
    e.preventDefault();
    $(".custom-checkbox").removeClass("active");
    $("input[name='type-checkbox']").prop("checked", false);
    applyFilters();
  });

  $(".clearSelection[data-target='tag-checkbox']").on("click", function (e) {
    e.preventDefault();
    $(".custom-checkbox").removeClass("active");
    $("input[name='tag-checkbox']").prop("checked", false);
    applyFilters();
  });



  const searchOpen = $("#searchOpen");
  const searchBox = $("#Table_View_filter"); // Search bar container
  const saveCancelBtns = $(".search-icon-button"); // Save as & Cancel buttons
  const customtaborder = $(".custom-tab-order");
  const selectDropdown = $(".selectDropdown");

  if (searchOpen.length === 0) {
    console.error("Error: searchOpen ID wala element nahi mila!");
    return;
  }

  // console.log("searchOpen found:", searchOpen);

  // Default state: searchOpen visible, searchBox and buttons hidden
  searchBox.show();
  saveCancelBtns.hide();
  selectDropdown.hide();
  searchOpen.hide();

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
    return;
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




  const $inputFieldtwo = $("#duplicateModalInputtwo");
  const $createButtontwo = $(".btn-primary.btn-modal-text.two");
  const $currentCounttwo = $("#the-counttwo #currenttwo");
  const maxLengthtwo = 40;


  // Pehle se button disable kar dete hain
  $createButton.prop("disabled", true);

  $inputFieldtwo.on("input", function () {
    let count = $(this).val().length;
    $currentCounttwo.text(count);

    // Button enable/disable logic
    $createButtontwo.prop("disabled", count === 0);

    // Agar character limit exceed ho jaye, extra characters remove kar do
    if (count > maxLengthtwo) {
      $(this).val($(this).val().substring(0, maxLengthtwo));
      $currentCounttwo.text(maxLengthtwo);
    }
  });

  $inputFieldtwo.on("keydown", function (e) {
    let count = $(this).val().length;

    // Agar character limit exceed ho jaye to naye characters ko allow na karein
    if (count >= maxLengthtwo && e.key !== "Backspace" && e.key !== "Delete" && !e.ctrlKey) {
      e.preventDefault();
      alert("Maximum 40 characters allowed!");
    }
  });

  $(document).ready(function () {
    $("#custom_search").on("input", function () {
      if ($(this).val().trim() !== "") {
        $(".search-icon-button a:nth-child(2)").addClass("active");
      } else {
        $(".search-icon-button a:nth-child(2)").removeClass("active");
      }
    });
  });

  // $(".custom-dropdown-main .droupdown-head").on("click", function (e) {
  //   e.preventDefault();
  //   $(this).parent().toggleClass("show");
  // });
  $(document).on("click", function (e) {
    if (!$(e.target).closest(".custom-dropdown-main").length) {
      $(".custom-dropdown-main").removeClass("show");
    }
  });

  $(".custom-dropdown-main .droupdown-head").on("click", function (e) {
    e.preventDefault();
    $(".droupdown-body").not($(this).parent()).removeClass("show");
    $(this).parent().toggleClass("show");
    e.stopPropagation(); // Yeh click event ko document tak jane se rokay ga
  });


  $(document).on("click", function (event) {
    if (!$(event.target).closest(".select-dropdown").length) {
      $(".select-dropdown").removeClass("show");
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
  });


  // Vendor filter: Update selected vendor values
  $("input[name='vendor-checkbox']").on("change", function () {
    updateSelectedValues("vendor-checkbox", "valueItem");
  });

  $("input[name='saleschannel-radio']").on("change", function () {
    updateSelectedValues("saleschannel-radio", "saleschannelItem");
  });

  $("input[name='type-checkbox']").on("change", function () {
    updateSelectedValues("type-checkbox", "typeItem");
  });

  // Status filter: Update selected status values
  $("input[name='status-checkbox']").on("change", function () {
    updateSelectedValues("status-checkbox", "statusItem");
  });

  $("input[name='tag-checkbox']").on("change", function () {
    updateSelectedValues("tag-checkbox", "tagItem");
  });


  $(".custom-select #custom-select-input").on("click", function () {
    $(".custom-select .dropdown-menu").toggle();
  });

  $(".custom-select #search-input").on("keyup", function () {
    let value = $(this).val().toLowerCase();
    $(".custom-select #dropdown-options li").filter(function () {
      $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
    });
  });

  $(".custom-select #dropdown-options li").on("click", function () {
    let selectedValue = $(this).text();
    $(".custom-select #custom-select-input").val(selectedValue);
    $(".custom-select .dropdown-menu").hide();
  });

  $(document).on("click", function (e) {
    if (!$(e.target).closest(".custom-select .custom-dropdown").length) {
      $(".custom-select .dropdown-menu").hide();
    }
  });


  // dropdown hide and show js start 
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

  // $('#export-product').on('click', function () {
  //   $('#Table_View_filter').DataTable({ 
  //   })
  // })

  // Initialize DataTable
  // var table = $('#Table_View_filter').DataTable({
  //   dom: 'B',
  //   buttons: [
  //     {
  //       extend: 'excel',
  //       text: 'Export to Excel', // Not visible in UI
  //       title: 'Product Export'
  //     }
  //   ],
  //   // Hide buttons from UI
  //   initComplete: function () {
  //     $('.dt-buttons').hide();
  //   }
  // });


  // Initialize dropdowns
  setupDropdown('categoriesDropdown', 'category-checkbox');
  setupDropdown('saleschannelDropdown', 'status-radio');
  setupDropdown('marketDropdown', 'market-radio');
  setupDropdown('typeDropdown', 'status-checkbox');
  setupDropdown('collectionDropdown', 'status-radio');
  setupDropdown('giftcardDropdown', 'status-checkbox');

  // dropdown hide and show js end 

  categoryList();
  tag_list();
  saleschannel_list();
  type_list();
  vendor_list();
  market_list();
  Onload();
});

// Common function to filter table based on selected filters
function applyFilters() {
  // Collect all selected status filters
  // let selectedStatusFilters = [];
  // $("input[name='status-checkbox']:checked").each(function () {
  //     selectedStatusFilters.push($(this).val().toLowerCase());
  // });

  // // Collect all selected vendor filters
  // let selectedVendorFilters = [];
  // $("input[name='vendor-checkbox']:checked").each(function () {
  //     selectedVendorFilters.push($(this).val().toLowerCase());
  // });

  // // Collect all selected category filters
  // let selectedCategoryFilters = [];
  // $("input[name='category-checkbox']:checked").each(function () {
  //     selectedCategoryFilters.push($(this).val().toLowerCase());
  // });

  // let selectedMarketFilters = [];
  // $("input[name='market-radio']:checked").each(function () {
  //     selectedMarketFilters.push($(this).val().toLowerCase());
  // });

  // // Show all rows if no filters are selected
  // if (
  //     selectedStatusFilters.length === 0 &&
  //     selectedVendorFilters.length === 0 &&
  //     selectedCategoryFilters.length === 0 &&
  //     selectedMarketFilters.length === 0
  // ) {
  //     $("#Table_View tbody tr").show();
  //     return;
  // }

  let selectedFilters = [
    ...$("input[name='status-checkbox']:checked").map((_, el) => el.value.toLowerCase()).get(),
    ...$("input[name='vendor-checkbox']:checked").map((_, el) => el.value.toLowerCase()).get(),
    ...$("input[name='category-checkbox']:checked").map((_, el) => el.value.toLowerCase()).get(),
    ...$("input[name='market-radio']:checked").map((_, el) => el.value.toLowerCase()).get(),
    ...$("input[name='saleschannel-radio']:checked").map((_, el) => el.value.toLowerCase()).get(),
    ...$("input[name='type-checkbox']:checked").map((_, el) => el.value.toLowerCase()).get(),
    ...$("input[name='tag-checkbox']:checked").map((_, el) => el.value.toLowerCase()).get()
  ];

  // Show all rows if no filters are selected
  if (selectedFilters.length === 0 || selectedFilters.includes("allchannel")) {
    $("#Table_View tbody tr").show();
    return;
  }
  // Loop through table rows and filter based on selected filters
  // $("#Table_View tbody tr").each(function () {
  //     let statusText = $(this).find("td:nth-child(3) span").text().trim().toLowerCase();
  //     let vendorText = $(this).find("td:nth-child(8)").text().trim().toLowerCase();
  //     let categoryText = $(this).find("td:nth-child(7)").text().trim().toLowerCase(); // Assuming category is in 4th column
  //     let marketText = $(this).find("td:nth-child(6)").text().trim().toLowerCase(); // Assuming category is in 4th column

  //     // Show row if all filters match (or if one is empty)
  //     console.log('categoryText', categoryText);
  //     if (
  //         (selectedStatusFilters.length === 0 || selectedStatusFilters.includes(statusText)) &&
  //         (selectedVendorFilters.length === 0 || selectedVendorFilters.includes(vendorText)) &&
  //         (selectedCategoryFilters.length === 0 || selectedCategoryFilters.includes(categoryText)) &&
  //         (selectedMarketFilters.length === 0 || selectedMarketFilters.includes(marketText))
  //     ) {
  //         $(this).show();
  //     } else {
  //         $(this).hide();
  //     }
  // });

  $("#Table_View tbody tr").each(function () {
    // Collect text values of the relevant columns
    let rowText = [
      $(this).find("td:nth-child(3) span").text().trim().toLowerCase(), // Status
      $(this).find("td:nth-child(9)").text().trim().toLowerCase(),      // Vendor
      $(this).find("td:nth-child(7)").text().trim().toLowerCase(),      // Category
      $(this).find("td:nth-child(6) .market-value").text().trim().toLowerCase(),     // Market
      $(this).find("td:nth-child(5) .channel-value").text().trim().toLowerCase(),       // Saleschannel
      $(this).find("td:nth-child(8)").text().trim().toLowerCase()       // Type
    ];

    // Show the row if no filters are selected or if at least one text matches any filter
    $(this).toggle(
      selectedFilters.length === 0 || rowText.some(text =>
        selectedFilters.some(filter => text.includes(filter))
      )
    );
  });
}

// Function to update selected values display
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

function categoryList() {
  // $("#category_list").empty(); 
  $.ajax({
    url: ApiPlugin + '/ecommerce/category/get_active_categories', // API end point
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
        htmlLi += `<li>
                    <label data-filter="${item?.name?.toLowerCase()}" class="custom-checkbox">
                      <input name="category-checkbox" type="checkbox" value="${item?.name}">
                        ${item?.name}
                    </label>
                  </li>
                            `
      }
      $("#category_list").append(htmlLi);
      $("input[name='category-checkbox']").on("change", function () {
        $(this).parent().toggleClass("active");
        applyFilters();
      });

      // Category filter: Update selected category values
      $("input[name='category-checkbox']").on("change", function () {
        updateSelectedValues("category-checkbox", "categoryItem");
      });

      // Clear Selection Functionality for all filters
      $(".clearSelection").on("click", function (e) {
        e.preventDefault();
        let targetName = $(this).data("target");
        $(`input[name='${targetName}']`).prop("checked", false);
        updateSelectedValues(
          targetName,
          targetName === "vendor-checkbox"
            ? "valueItem"
            : targetName === "status-checkbox"
              ? "statusItem"
              : "categoryItem"
        );
      });
    }
  });
}

function market_list() {
  $.ajax({
    url: ApiPlugin + '/ecommerce/market/get_active_markets', // API end point
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
      // console.log(response.data);

      let htmlLi = `  <li>
                                <label data-filter="allchannel" class="custom-radio">
                                    <input name="market-radio" value="allchannel" type="radio">
                                    All channel
                                </label>
                            </li>`;
      for (const key in data) {
        let item = data[key];
        htmlLi += ` <li>
                                <label data-filter="${item.market_name.toLowerCase()}" class="custom-radio">
                                    <input name="market-radio" value="${item.market_name}" type="radio">
                                    ${item.market_name}
                                </label>
                            </li>`

      }
      $("#market_list").append(htmlLi);
      $("input[name='market-radio']").on("change", function () {
        // $(this).parent().toggleClass("active");
        var selectedValue = $(this).val(); // Get the selected radio button value
        $('#marketItem').text(selectedValue);
        applyFilters();
      });

      // Clear the selected radio button and clear marketItem span
      $('.clearSelection').on('click', function (e) {
        e.preventDefault(); // Prevent default action of anchor tag
        $('input[name="market-radio"]').prop('checked', false); // Uncheck all radio buttons
        $('#marketItem').text(''); // Clear the marketItem span
      });
    }
  });
}

function saleschannel_list() {
  $.ajax({
    url: ApiPlugin + '/ecommerce/channel/get_active_channels', // API end point
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


      let htmlLi = `  <li>
                                <label data-filter="allchannel" class="custom-radio">
                                    <input name="saleschannel-radio" value="allchannel" type="radio">
                                    All channel
                                </label>
                            </li>`;
      for (const key in data) {
        let item = data[key];


        htmlLi += ` <li>
                                <label data-filter="${item.name.toLowerCase()}" class="custom-radio">
                                    <input name="saleschannel-radio" value="${item.name}" type="radio">
                                    ${item.name}
                                </label>
                            </li>`

      }
      // console.log($("#saleschannel_list"))
      $("#saleschannel_list").append(htmlLi);
      $("input[name='saleschannel-radio']").on("change", function () {
        // $(this).parent().toggleClass("active");
        var selectedValue = $(this).val(); // Get the selected radio button value
        $('#saleschannelItem').text(selectedValue);
        applyFilters();
      });

      // Clear the selected radio button and clear saleschannelItem span
      $('.clearSelection').on('click', function (e) {
        e.preventDefault(); // Prevent default action of anchor tag
        $('input[name="saleschannel-radio"]').prop('checked', false); // Uncheck all radio buttons
        $('#saleschannelItem').text(''); // Clear the marketItem span
      });
    }
  });
}

function type_list() {
  $.ajax({
    url: ApiPlugin + '/ecommerce/product/get_product_types', // API end point
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
        if(item.type){
        htmlLi += ` <li>
                                <label data-filter="${item?.type?.toLowerCase()}" class="custom-checkbox">
                                    <input name="type-checkbox" value="${item?.type}" type="checkbox">
                                    ${item?.type}
                                </label>
                            </li>`;
        }
      }
      // console.log($("#saleschannel_list"))
      $("#type_list").append(htmlLi);
      $("input[name='type-checkbox']").on("change", function () {
        // $(this).parent().toggleClass("active");
        var selectedValue = $(this).val(); // Get the selected radio button value
        $('#typeItem').text(selectedValue);
        applyFilters();
      });

      // Clear the selected radio button and clear saleschannelItem span
      $('.clearSelection').on('click', function (e) {
        e.preventDefault(); // Prevent default action of anchor tag
        $('input[name="type-checkbox"]').prop('checked', false); // Uncheck all radio buttons
        $('#typeItem').text(''); // Clear the marketItem span
      });
    }
  });
}

function vendor_list() {
  $.ajax({
    url: ApiPlugin + '/ecommerce/product/get_product_vendors', // API end point
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

        if (item.vendor) {
        htmlLi += ` 
        <li>
         <label data-filter="${item.vendor.toLowerCase()}" class="custom-checkbox">
           <input name="vendor-checkbox" value="${item.vendor}" type="checkbox">${item.vendor}</label>
        </li>`;

      }
    }
      // console.log($("#saleschannel_list"))
      $("#vendor_list").append(htmlLi);
      $("input[name='vendor-checkbox']").on("change", function () {
        $(this).parent().toggleClass("active");
        applyFilters();
      });

      $("input[name='vendor-checkbox']").on("change", function () {
        updateSelectedValues("vendor-checkbox", "valueItem");
      });

      // Clear the selected radio button and clear saleschannelItem span
      $('.clearSelection').on('click', function (e) {
        e.preventDefault(); // Prevent default action of anchor tag
        $('input[name="vendor-checkbox"]').prop('checked', false); // Uncheck all radio buttons
        $('#valueItem').text(''); // Clear the marketItem span
      });
    }
  });
}

function tag_list() {
  $.ajax({
    url: ApiPlugin + '/ecommerce/product/get_product_tags', // API end point
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
                             <li>
                                <label data-filter="${item?.name?.toLowerCase()}" class="custom-checkbox">
                                    <input style="display:none;" name="tag-checkbox" value="${item?.name}" type="radio">
                                    ${item?.name}
                                </label>
                            </li>`;

      }
      // console.log($("#saleschannel_list"))
      $(".tag_list").append(htmlLi);
      $("input[name='tag-checkbox']").on("change", function () {
        $(this).parent().toggleClass("active");
        applyFilters();
      });

      $("input[name='tag-checkbox']").on("change", function () {
        updateSelectedValues("tag-checkbox", "tagItem");
      });

      // Clear the selected radio button and clear saleschannelItem span
      $('.clearSelection').on('click', function (e) {
        e.preventDefault(); // Prevent default action of anchor tag
        $('input[name="tag-checkbox"]').prop('checked', false); // Uncheck all radio buttons
        $('#tagItem').text(''); // Clear the marketItem span
      });
    }
  });
}

function addproductsell() {
  // $("#add_to_me").show();
  $("#add_to_me").toggle();
}


      $('#custom_search').on('keypress', function (e) {
  if (e.which === 13) { // 13 is the Enter key
    e.preventDefault(); // optional: prevents form submission if inside a form
    Onload();
  }
});
let currentPage = 1;
let per_page=10;
function Onload(page) {
  currentPage = page;
  search=$('#custom_search').val();
 
  $('#Table_View').DataTable().clear().destroy();
  $.ajax({
    url: ApiPlugin + '/ecommerce/product/get_product?page='+currentPage+'&per_page='+per_page+'&search='+search, // API end point
    type: "Get",
    contentType: "application/json",
    dataType: "json",
    beforeSend: function (xhr) {
      xhr.setRequestHeader("Authorization", "Bearer " + strkey);
      xhr.setRequestHeader("menu-uuid", menu_id);
      imgload.show();
    },
    success: function (response) {

      if (response.status_code == 200) {
        let action_button = '';
        let switch_button = '';
        //New
        if (Boolean(response.permissions['add'])) { 
          btnnew.removeClass('d-none'); 
          btnexport.removeClass('d-none');
          btnimport.removeClass('d-none');
        }

        //Update 
        if (Boolean(response.permissions["edit"])) {
          action_button += `<a href='javascript:;' class='modify_btn btn-edit' data-toggle='tooltip' title='Edit'>${getTranslation('edit')}</a> | `;
          // action_button += `<a href='javascript:;' class='modify_btn btn-edit-attribute' data-toggle='tooltip' title='Attributes'>${getTranslation('attributes')}</a> | `;
        }

        // //Delete
        if (Boolean(response.permissions["delete"])) {
          $(".bulk-delete").removeClass('d-none');
          action_button += `<a href='javascript:;' class='modify_btn btn-delete' data-toggle='tooltip' title='Delete'>${getTranslation('delete')}</a> `;
        }

        if (Boolean(response.permissions["update"])) {
          switch_button = '';
        } else {
          switch_button = 'disabled';
        }

         if (response["data"]["data"] == null || response["data"]["data"] <1) {
          // Ajax Data Settings
          imgload.hide();
          console.log("NONO");
          $("#select-all-checkbox").prop('checked',false);
          updateCount();
          $('#product_count').hide();
          $('#Table_View_paginate').hide();
          $('#Table_View').DataTable().clear().destroy();
            $("#Table_View").DataTable({
              destroy: true,
              responsive: false,
              retrieve: true,
              ordering: false,
              dom: "lrt",
              bLengthChange: false,
              oLanguage: {
                sEmptyTable:
                  `NO Data`,
              },
            });
            return false;
          }else{
            $('#Table_View').DataTable().clear().destroy();
          const columnsConfig = [
            {
              data: null, // No data source
              render: function (data, type, row, meta) {
                return `<input type="checkbox" class="bulk-delete-checkbox" data-uuid="${data.uuid}">`;
              },
              title: '<input type="checkbox" id="select-all-checkbox">', // Select All Checkbox
              orderable: false // Disable sorting on this column
            },
            {
              data: 'name',
              render: function (data, type, row) {
                if (action_button !== 'undefined' && action_button !== '') {
                  img_div = '';
                  if (row.thumbnail_img != null && row.thumbnail_img != '') {
                    img_div = `<img style="min-width: 48px;"src="${AssetsPath + row.thumbnail_img}" width="48" alt="image">`;
                  }
                  img_title_div = `
                  <div class="d-flex gap-2 align-items-center product-desc-tb btn-edit">
                    <div>${img_div}</div>
                      <p>${data}</p>
                  </div>`;
                  return img_title_div
                } else { return data }
              }
            },
            {
              data: 'created_at', // inventory
              render: function (data, type, row) {
                if (action_button !== 'undefined' && action_button !== '') {
                  return `<a class="product-variant" href="">${data}</a>`;
                } else { return data }
              }
            },
            {
              data: 'updated_at', // inventory
              render: function (data, type, row) {
                if (action_button !== 'undefined' && action_button !== '') {
                  return `<a class="product-variant" href="">${data}</a>`;
                } else { return data }
              }
            },
            {
              data: 'status',
              render: function (data, type, row) {

                if (data === 1) {
                  return `<span class="badge-success">Active</span>`;
                } else if (data === 2) {
                  return `<span class="badge-warning">Archived</span>`;
                }
                else {
                  return `<span class="badge-draft">Draft</span>`;
                }

              }
            },
            {
              data: 'total_qty', // inventory
              render: function (data, type, row) {
                if (action_button !== 'undefined' && action_button !== '') {
                  return `<a class="product-variant" href="javascript:;">${data} in stock <span>for ${row.total_variations}variants</span></a>`;
                } else { return data }
              }
            },
            // {
            //   data: 'sale_channel_name',
            //   render: function (data, type, row) {

            //     if (data === null || data === undefined || data === '') {
            //       return '-';
            //     }


            //     let sales_channels_html = `<div class="product-dropdown dropdown">
            //                 <a class="dropdown-toggle custom-color" data-bs-toggle="dropdown" aria-expanded="false">
            //                   <span class="channel-value">${row.sales_channels.length}</span>
            //                 </a>
            //                 <ul class="dropdown-menu">
            //                 <li>
            //                     <div class="dropdown-item px-3">`;

            //     row.sales_channels.forEach(element => {
            //       if(element.name == "Online Store") {
            //         sales_channels_html += `<div class="table-dropdown-item">
            //                           <div class="box-success"></div>
            //                           <span>${element.name}</span>
            //                         </div>`
            //       } 
            //       if(element.name == "Point of Sale") {
            //         sales_channels_html += `<div class="table-dropdown-item flex-wrap align-items-start">
            //                           <div class="box-success mt-1"></div>
            //                           <div>
            //                             <div>${element.name}</div>
            //                           </div>
            //                         </div>`
            //       }
            //     });

            //     sales_channels_html += `</div>
            //                   </li>
            //                 </ul>
            //             </div>`;


            //     if (action_button !== 'undefined' && action_button !== '') {
            //       return sales_channels_html;
            //     } else { return data }
            //   }
            // },
            // {
            //   data: 'market_name',
            //   render: function (data, type, row) {
            //     if (data === null || data === undefined || data === '') {
            //       return '-';
            //     }

            //     if (action_button !== 'undefined' && action_button !== '') {
            //       return `<div class="product-dropdown dropdown">
                     
            //                     <span class="channel-value">${data}</span>
                         
                         
            //             </div>`;
            //     } else { return data }
            //   }
            // },
            {
              data: 'category_name',
              render: function (data, type, row) {
                if (row.product_type === 'giftcard') {
                  return '<span class="product-variant">Gift Card</span>';
                }

                if (data === null || data === undefined || data === '') {
                  return '-';
                }

                if (action_button !== 'undefined' && action_button !== '') {
                  return `<span class="product-variant">${data}</span>`;
                } else { return data }
              }
            },
            
            {
              data: 'type',
              render: function (data, type, row) {

                if (data === null || data === undefined || data === '') {
                  return '-';
                }

                if (action_button !== 'undefined' && action_button !== '') {
                  return `<span class="product-variant">${data}</span>`;
                } else { return data }
              }
            },
            {
              data: 'vendor',
              render: function (data, type, row) {
                if (data === null || data === undefined || data === '') {
                  return '-';
                }

                if (action_button !== 'undefined' && action_button !== '') {
                  return `<span class="product-variant">${data}</span>`;
                } else { return data }
              }
            },
          ];
          const paginationData = response.data; 
          let tableData = paginationData.data;
          let currentPage = paginationData.current_page;
          let lastPage = paginationData.last_page;
          tableData = response["data"]["data"];
          if (per_page === 'all' || lastPage <= 1) {
          $('#Table_View_paginate').hide(); // Hide pagination for "all" or single page
        } else {
          $('#Table_View_paginate').show();
          renderPagination(currentPage, lastPage);
        }

          //tableData = response["data"];
          let pageLength = per_page === 'all' ? tableData.length : parseInt(per_page);
          let configFilter = {
            searching: false,
            paging: true,
            info: false,
            bLengthChange: false,
            responsive: false,
            pageLength: pageLength,
            paging: per_page !== 'all',
            dom: 't',
            buttons: [ 
              'excel',
            ]
          };

          // EXPORT WORKING CODE
          let tableSearch = $('#Table_View').DataTable({
            data: tableData,
            columns: columnsConfig,
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
             pagingType: "simple", // required for .page() API to work with basic numbers
            drawCallback: function (settings) {
              buildCustomPagination(settings);
            },
            initComplete: function () {
              $('.dt-buttons').hide();
                $('#Table_View thead th').removeClass('sorting sorting_asc sorting_desc');
            }
          });

          $('#export-product').prop("disabled", false); // Enable the button
          let from = response.data.from;
          let to = response.data.to;
          let total = response.data.total;
          $('#product_count').text(`Showing ${from}–${to} of ${total} products`);

          $('#export-product').prop("disabled", false); // Enable the button

          // Handle export button click
          $('#export-product').on('click', function () {
            let exportType = $('input[name="all"]:checked').val(); // Get selected radio button value

            // Remove old buttons
            // tableSearch.buttons().destroy();
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
          $(".dataTables_info").show();
          $("#select-all-checkbox").prop('checked',false);
          updateCount();
         // $(".dataTables_filter").hide();

          // $('#custom_search').keyup(function () {
          //   tableSearch.search($(this).val()).draw();
          // });

          $(".lodest-first a").on('click', function (e) {
            e.preventDefault();

            $(this).siblings().removeClass("active");
            $(this).addClass("active");
          });

          var selectedColumn = 1;
          $("input[name='sort-option']").on("change", function () {
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

          imgload.hide();
          $("#analysis").html(response.analysis);
          $("#inventory_remaining").html(response.inventory_remaining);
          $("#sale_rate").html(response.sale_rate);

          tableSearch.column(2).visible(false);
          tableSearch.column(3).visible(false);
          // tableSearch.column(9).visible(false);

        }
      } else if (response.statusCode == 404) {
        imgload.hide();
        $("#select-all-checkbox").prop('checked',false);
        updateCount();
        $("#Table_View").DataTable({
          destroy: true,
          responsive: false,
          retrieve: true,
          ordering: false,
          dom: "lrt",
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

function renderPagination(currentPage, lastPage) {
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
    paginationHTML += `<li class="paginate_button page-item previous"><a href="#" class="page-link" onclick="Onload(${currentPage - 1})">Previous</a></li>`;
  }

  // First page shortcut (optional)
  if (startPage > 1) {
    paginationHTML += `<li class="paginate_button page-item"><a href="#" class="page-link" onclick="Onload(1)">1</a></li>`;
    if (startPage > 2) {
      paginationHTML += `<li class="paginate_button page-item disabled"><span class="page-link">...</span></li>`;
    }
  }

  // Numbered page buttons
  for (let i = startPage; i <= endPage; i++) {
    let activeClass = i === currentPage ? 'active' : '';
    paginationHTML += `<li class="paginate_button page-item ${activeClass}"><a href="#" class="page-link ${activeClass}" onclick="Onload(${i})">${i}</a></li>`;
  }

  // Last page shortcut (optional)
  if (endPage < lastPage) {
    if (endPage < lastPage - 1) {
      paginationHTML += `<li class="paginate_button page-item disabled"><span class="page-link">...</span></li>`;
    }
    paginationHTML += `<li class="paginate_button page-item"><a href="#" class="page-link" onclick="Onload(${lastPage})">${lastPage}</a></li>`;
  }

  // Next button
  if (currentPage < lastPage) {
    paginationHTML += `<li class="paginate_button page-item next"><a href="#" class="page-link" onclick="Onload(${currentPage + 1})">Next</a></li>`;
  }

  paginationHTML += `</ul></div></div></div>`;
  $('#pagination').html(paginationHTML);
}


 
function updateCount() {
      const count = $('.bulk-delete-checkbox:checked').length;
      $('#selected-count').text(count);
    }

    // Event for individual checkboxes
    $(document).on('change', '.bulk-delete-checkbox', function () {
      updateCount();
    });

    // Event for "Select All" checkbox
    // $('#select-all-checkbox').on('change', function () {
    //   alert(1);
    //   // const isChecked = $(this).is(':checked');
    //   // $('.bulk-delete-checkbox').prop('checked', isChecked);
    //   updateCount();
    // });

    // // Optional: initialize count on load
    // updateCount();

 

 $('#pageLengthSelect').on('change', function () {
                  per_page = $(this).val();
                   Onload(1);
                  // tableSearch.page.len(per_page).draw();
                });
$(document).on("change", "#select-all-checkbox", function () {
  if ($(this).is(':checked')) {
    $(".bulk-delete-checkbox").prop('checked', true);
  } else {
    $(".bulk-delete-checkbox").prop('checked', false);
  }
  updateCount();
});

$("#delete_selected_products").on("click", function (e) {
  e.preventDefault();
  let selectedUuids = [];
  $(".bulk-delete-checkbox:checked").each(function () {
    selectedUuids.push($(this).data("uuid"));
  });

  if (selectedUuids.length === 0) {
    showToast('Please select at least one product to delete.', 'warning', 'warning');
    return;
  }

  // Confirm deletion
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
        url: ApiPlugin + '/ecommerce/product/bulk-delete',
        type: "POST",
        contentType: "application/json",
        dataType: "json",
        data: JSON.stringify({ uuids: selectedUuids }),
        beforeSend: function (xhr) {
          xhr.setRequestHeader("Authorization", "Bearer " + strkey);
          xhr.setRequestHeader("menu-uuid", menu_id);
          imgload.show();
        },
        success: function (response) {
          imgload.hide();
          if (response.status_code == 200) {
            showToast(response.message, 'Success', 'success');
            Onload(); // Reload the product list
          } else {
            showToast(response.message, 'error', 'error');
          }
        },
        error: function (xhr, status, err) {
          imgload.hide();
          showToast(err, 'error', 'error');
        }
      });
    }
  })
  
});

function buildCustomPagination(settings) {
  let paginationContainer = $('#custom-pagination');
  paginationContainer.empty();

  let pageInfo = $('#Table_View').DataTable().page.info();

  let currentPage = pageInfo.page;
  let totalPages = pageInfo.pages;

  if (totalPages <= 1) return; // no pagination needed

  // Previous
  let prevDisabled = currentPage === 0 ? 'disabled' : '';
  paginationContainer.append(`<button class="page-btn page-link prev ${prevDisabled}" data-page="${currentPage - 1}"><i class="arrow left"></i></button>`);

  // Page numbers
  for (let i = 0; i < totalPages; i++) {
    let active = currentPage === i ? 'active' : '';
    paginationContainer.append(`<div class="page-number page-link ${active}" data-page="${i}">${i + 1}</div>`);
  }

  // Next
  let nextDisabled = currentPage === totalPages - 1 ? 'disabled' : '';
  paginationContainer.append(`<button class="page-btn page-link next ${nextDisabled}" data-page="${currentPage + 1}"><i class="arrow right"></i></button>`);
}

$(document).on('click', '#custom-pagination .page-link', function () {
  if ($(this).hasClass('disabled') || $(this).hasClass('active')) return;

  let page = parseInt($(this).attr('data-page'));
  
  $('#Table_View').DataTable().page(page).draw('page'); // change page
});

$('table').on('click', '.btn-edit', function (e) {
  e.preventDefault();
  $("#popup_title").html(getTranslation("modify_menu"));
  let currentRow = $(this).closest("tr");
  let data = $('#Table_View').DataTable().row(currentRow).data();
  let uuid = data['uuid'];
  let giftcard_product_id = data['giftcard_product_id'];
  let type = data['product_type'];
  if(type == 'giftcard'){
    let page = 'edit-giftcard-product';
    window.location.assign('?P=' + page + '&M=' + menu_id + '&id=' + giftcard_product_id);
  }else{
  let page = 'edit-product';
  window.location.assign('?P=' + page + '&M=' + menu_id + '&id=' + uuid);
  }
});