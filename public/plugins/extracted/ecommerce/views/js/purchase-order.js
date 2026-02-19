document.title = "Dashboard | Purchase Order";
let PageNo = 1;
let PageSize = 1;
let totalRecord = 0;
let PageLength = 0;
let Search = '';
let keyupTimer;
$(document).ready(function () {
  btnnew = $('#btn_new');
  btnnew.hide();
  btnnew.click(function (e) {
    e.preventDefault();
    let page = 'add-product';
    window.location.assign('?P=' + page + '&M=' + menu_id);
  });
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

    if (fileInput.files.length > 0) {
      formData.append('excel_file', fileInput.files[0]); // Append the file
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
        window.location.reload(); // Reload page on success
      },
      error: function (xhr, status, error) {
        imgload.hide(); // Hide loader on error
        console.error('Error:', xhr.responseText);
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

  $(".custom-dropdown-main .droupdown-head").on("click", function (e) {
    e.preventDefault();
    $(this).parent().toggleClass("show");
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
  setupDropdown('ExpectedarrivalDropdown', 'category-checkbox');
  setupDropdown('TaggedwithDropdown', 'status-radio');
  setupDropdown('marketDropdown', 'market-radio');
  setupDropdown('typeDropdown', 'status-checkbox');
  setupDropdown('collectionDropdown', 'status-radio');
  setupDropdown('giftcardDropdown', 'status-checkbox');

  // dropdown hide and show js end 

  // categoryList();
  // tag_list();
  // saleschannel_list();
  // type_list();
  // vendor_list();
  // market_list();
  Onload();
});

function Onload() {
  alert
  $('#Table_View').DataTable().clear().destroy();
  $.ajax({
    url: ApiPlugin + '/ecommerce/purchase-order', // API end point
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
          btnnew.show();
        }

        //Update 
        if (Boolean(response.permissions["edit"])) {
          action_button += `<a href='javascript:;' class='modify_btn btn-edit' data-toggle='tooltip' title='Edit'>${getTranslation('edit')}</a> | `;
          // action_button += `<a href='javascript:;' class='modify_btn btn-edit-attribute' data-toggle='tooltip' title='Attributes'>${getTranslation('attributes')}</a> | `;
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
          // Ajax Data Settings

          const columnsConfig = [
            {
              data: null, // No data source
              visible: false,
              render: function (data, type, row, meta) {
                
                return `<div class="form-check m-0">
                    <input class="form-check-input" type="checkbox">
                  </div>` // Serial number
              },
              title: `<div class="form-check m-0">
                    <input class="form-check-input" type="checkbox">
                  </div>`, // AUTO Serial number Column
              // orderable: false // Disable sorting on this column
            },
            {
              data: 'po_number', // inventory
              render: function (data, type, row) {
                if (action_button !== 'undefined' && action_button !== '') {
                  return `<a class="order-number-text edit-purchase-order ViewPurchaseOrder" href="#">${data}</a>`;
                } else { return data }
              }
            },
            {
              data: 'supplier', // inventory
              render: function (data, type, row) {
                if (data !== null && data !== '') {
                  return `<span class="purchase-text">${data.company}</span>`;
                } else { return '<span class="purchase-text">-</span>' }
              }
            },
            {
              data: 'warehouse', // inventory
              render: function (data, type, row) {
                if (data !== null && data !== '') {
                  return `<span class="purchase-text">${data.location_name}</span>`;
                } else { return '<span class="purchase-text">-</span>' }
              }

            },
            {
              data: 'status',
              render: function (data, type, row) {

                if (data === 'complete') {
                  return `<span class="badge-success">Completed</span>`;
                } 
                else {
                  return `<span class="badge-draft">Draft</span>`;
                }

              }
            },
            {
              data: 'total_amount', // inventory
              render: function (data, type, row) {
                if (action_button !== 'undefined' && action_button !== '') {
                  return `<span class="purchase-text">${data}</span>`;
                } else { return data }
              }
            },
            {
              data: 'ship_date',
              render: function (data, type, row) {
                if (action_button !== 'undefined' && action_button !== '') {
                  return `<span class="purchase-text">${data || '-'}</span>`;
                } else { return data || '-' }
              }
            },
            
            // { 
            //     data: 'status', 
            //     render: function (data, type, row) {
            //         if (data == true) {
            //             return `<label class="switch">
            //                 <input type="checkbox" checked ${switch_button} class="switch-input" onchange="changestatus(this)" value="${row.uuid}" role="switch" id="switch1">
            //                 <span class="switch-toggle-slider">
            //                 <span class="switch-on"></span>
            //                 <span class="switch-off"></span>
            //                 </span> 
            //             </label>`
            //             } else { 
            //             return `<label class="switch">
            //             <input type="checkbox" ${switch_button} class="switch-input" onchange="changestatus(this)" value="${row.uuid}" role="switch" id="switch1">
            //             <span class="switch-toggle-slider">
            //             <span class="switch-on"></span>
            //             <span class="switch-off"></span>
            //             </span> 
            //             </label>`;
            //             }
            //         }
            // },
            // { 
            //     data: 'featured', 
            //     render: function (data, type, row) {
            //         if (data == true) {
            //             return `<label class="switch">
            //                 <input type="checkbox" checked ${switch_button} class="switch-input" onchange="changefeatured(this)" value="${row.uuid}" role="switch" id="switch1">
            //                 <span class="switch-toggle-slider">
            //                 <span class="switch-on"></span>
            //                 <span class="switch-off"></span>
            //                 </span> 
            //             </label>`
            //             } else { 
            //             return `<label class="switch">
            //             <input type="checkbox" ${switch_button} class="switch-input" onchange="changefeatured(this)" value="${row.uuid}" role="switch" id="switch1">
            //             <span class="switch-toggle-slider">
            //             <span class="switch-on"></span>
            //             <span class="switch-off"></span>
            //             </span> 
            //             </label>`;
            //             }
            //         }
            // }
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

          // tableSearch.column(2).visible(false);
          // tableSearch.column(3).visible(false);
          // tableSearch.column(9).visible(false);

        }
      } else if (response.statusCode == 404) {
        imgload.hide();
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
        console.log('item', item)
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
      console.log(response.data);

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




$('table').on('click', '.ViewPurchaseOrder', function (e) {
  e.preventDefault();
  $("#popup_title").html(getTranslation("modify_menu"));
  let currentRow = $(this).closest("tr");
  let data = $('#Table_View').DataTable().row(currentRow).data();
  let uuid = data['uuid'];
  let page = 'view-purchase-order';  
  window.location.assign('?P='+page+'&M='+menu_id+'&id='+uuid);
});