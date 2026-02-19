document.title = "Dashboard | Create Collection";
let currentPage = 1;
let per_page = 10;
let search = '';
let product_ids = [];
$(document).ready(function(){
  initializeProductSorting();
function get_view_base_url() {
    let urlWithoutQuery = window.location.href.split('?')[0];
    if (urlWithoutQuery.match(/\/[^\/]*\.[^\/]*$/)) {
      urlWithoutQuery = urlWithoutQuery.substring(0, urlWithoutQuery.lastIndexOf('/') + 1);
    }
    return urlWithoutQuery;
  }
  $(".site-link").html(get_view_base_url());

  // Seo card js
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
  $(".site-link").html(get_view_base_url() + '<span></span>');
  $("#seoLink").on("input", function () {
    $(".site-link span").html($(this).val());
  });
  // Seo card js end

  $(".product-conditions-box").on("click", ".items .item a", function(e) {
    e.preventDefault();
    $(this).parent().remove();
    isOneConditionItem();
  });

  $("#addConditionItemBtn").on("click", function(e) {
    e.preventDefault();
    
    $(".product-conditions-box .items").append(`
      <div class="item">
        <div>
          <select class="product-condition-if"></select>
        </div>
        <div>
          <select class="product-condition-equal"></select>
        </div>
        <div class="product-condition-to-parent">
          <div class="input">
            <input type="text" class="form-control md product-condition-to">
          </div>
        </div>
        <a href="#"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" width="16" height="16" fill="#4a4a4a"><path d="M9.5 6.25a.75.75 0 0 1 .75.75v4.25a.75.75 0 0 1-1.5 0v-4.25a.75.75 0 0 1 .75-.75"></path><path d="M7.25 7a.75.75 0 0 0-1.5 0v4.25a.75.75 0 0 0 1.5 0z"></path><path fill-rule="evenodd" d="M5.25 3.25a2.75 2.75 0 1 1 5.5 0h3a.75.75 0 0 1 0 1.5h-.75v5.45c0 1.68 0 2.52-.327 3.162a3 3 0 0 1-1.311 1.311c-.642.327-1.482.327-3.162.327h-.4c-1.68 0-2.52 0-3.162-.327a3 3 0 0 1-1.311-1.311c-.327-.642-.327-1.482-.327-3.162v-5.45h-.75a.75.75 0 0 1 0-1.5zm1.5 0a1.25 1.25 0 0 1 2.5 0zm-2.25 1.5h7v5.45c0 .865-.001 1.423-.036 1.848-.033.408-.09.559-.128.633a1.5 1.5 0 0 1-.655.655c-.074.038-.225.095-.633.128-.425.035-.983.036-1.848.036h-.4c-.865 0-1.423-.001-1.848-.036-.408-.033-.559-.09-.633-.128a1.5 1.5 0 0 1-.656-.655c-.037-.074-.094-.225-.127-.633-.035-.425-.036-.983-.036-1.848z"></path></svg></a>
      </div>
    `);

    conditionIfAndEqual(conditionsIfEqual);
    isOneConditionItem();

  });

  get_active_channel();
  get_active_type();
  get_active_categories();
  get_active_vendor();
  get_active_tags();

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
    const slug = slugify($(this).val()); // this helps $(this).val());
    $('#seoLink').val(slug);
    $('.site-link span').html(slug); // Update site-link span with new slug
  });
});

document.getElementById('addProductModal').addEventListener('shown.bs.modal', event => {
  if($("#table_product .simple-product-item").length == 0) {
    get_all_products();
  }
});

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
        const sales_checkboxs = $('#sales-checkboxs');
        sales_checkboxs.empty();
        const sales_channels = $('#sales-channels');
        sales_channels.empty();
        let htmlLi = ""
        response.data.forEach(channels => {
          htmlLi += `<div class="form-check mb-4">
                <input class="form-check-input" type="checkbox" value="${channels.uuid}"
                  id="${channels.name}" name="getChannelId[]" checked>
                <label class="form-check-label" for="${channels.name}">
                  <span>${channels.name}</span>
                </label>
              </div>`;
        });
        sales_checkboxs.html(htmlLi);
        $("#manageSalesChannels .manage-sales-select checkednumber").text($("#manageSalesChannels #sales-checkboxs input[type='checkbox']:checked").length);

        $('#channel_uuid').empty();
        response.data.forEach(channels => {
          $('#channel_uuid').append(`<option value="${channels.uuid}" selected>${channels.uuid}</option>`)
        });

        let salesChannelsArr = [];

        let htmlLi2 = "";
        response.data.forEach(channels => {
          salesChannelsArr.push({
            name: channels.name,
            featured: channels.featured
          });
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

          $('#channel_uuid').empty();
          getPushSalesChannel.forEach(element => {
            $('#channel_uuid').append(`<option value="${element}" selected>${element}</option>`);
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

        $('.manage-sales-text').on('click', function () {
          if (!$(this).hasClass('deselect')) {
            $(this).addClass('deselect');
            $(this).text('Deselect all');
            $('.sales-checkbox input[type="checkbox"]').prop('checked', true);
            let checkedCount = $(this).parents('.modal-body').find('input[type="checkbox"]:checked').length;
            $(this).parents('.modal-body').find('checkednumber').text(checkedCount);
          } else {
            $(this).removeClass('deselect');
            $(this).text('Select all');
            $('.sales-checkbox input[type="checkbox"]').prop('checked', false);
            let checkedCount = 0;
            $(this).parents('.modal-body').find('checkednumber').text(checkedCount);
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

function fetchProductsByIds(productIds) {
    if (productIds.length === 0) {
        return Promise.resolve({ 
            status_code: 200, 
            data: [] 
        });
    }
    
    return $.ajax({
        url: ApiPlugin + '/ecommerce/product/get_products_by_ids',
        type: "POST",
        contentType: "application/json",
        dataType: "json",
        data: JSON.stringify({ product_ids: productIds }),
        beforeSend: function (xhr) {
            xhr.setRequestHeader("Authorization", "Bearer " + strkey);
            xhr.setRequestHeader("menu-uuid", menu_id);
        }
    });
}

function get_all_products(page = 1, perPage = 10, searchTerm = '') {
  $.ajax({
    url: ApiPlugin + '/ecommerce/product/get_product?page=' + page + '&per_page=' + perPage + '&search=' + searchTerm,
    type: "Get",
    contentType: "application/json",
    dataType: "json",
    beforeSend: function (xhr) {
      xhr.setRequestHeader("Authorization", "Bearer " + strkey);
      xhr.setRequestHeader("menu-uuid", menu_id);
      $("#product_load").removeClass("d-none");
    },
    success: function (response) {

      if (response.status_code == 200) {
        
        $("#table_product").empty();
        if (response.data.data.length === 0) {
          $('#pagination').empty().hide();
          $("#table_product").html("<div class='text-center p-3'>No products found.</div>");
        }else{
          $('#pagination').show();
        $(response.data.data).each((i, element)=>{
          const isChecked = product_ids.includes(element.uuid);
          $("#table_product").append(`
            <label class="simple-product-item">
              <div>
                <div class="form-check m-0">
                  <input class="form-check-input product-checkbox" type="checkbox" value="${element.uuid}" ${isChecked ? "checked" : ""}>
                </div>
              </div>
              <div class="info">
                <div>
                  <div class="img">
                    ${element?.thumbnail_img == null ?
                      '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" width="16" height="16" fill="#8a8a8a"><path d="M10.5 7a1.5 1.5 0 1 0-.001-3.001 1.5 1.5 0 0 0 .001 3.001"></path><path fill-rule="evenodd" d="M7.018 1.5h1.964c.813 0 1.469 0 2 .043.546.045 1.026.14 1.47.366.706.36 1.28.933 1.64 1.639.226.444.32.924.365 1.47.043.531.043 1.187.043 2v1.964c0 .813 0 1.469-.043 2-.045.546-.14 1.026-.366 1.47a3.76 3.76 0 0 1-1.638 1.64c-.445.226-.925.32-1.471.365-.531.043-1.187.043-2 .043h-1.964c-.813 0-1.469 0-2-.043-.546-.045-1.026-.14-1.47-.366a3.76 3.76 0 0 1-1.64-1.638c-.226-.445-.32-.925-.365-1.471-.043-.531-.043-1.187-.043-2v-1.964c0-.813 0-1.469.043-2 .045-.546.14-1.026.366-1.47.36-.706.933-1.28 1.639-1.64.444-.226.924-.32 1.47-.365.531-.043 1.187-.043 2-.043m-1.877 1.538c-.454.037-.715.107-.912.207a2.25 2.25 0 0 0-.984.984c-.1.197-.17.458-.207.912-.037.462-.038 1.057-.038 1.909v1.429l.723-.868a1.75 1.75 0 0 1 2.582-.117l2.695 2.695 1.18-1.18a1.75 1.75 0 0 1 2.604.145l.216.27v-2.374c0-.852 0-1.447-.038-1.91-.037-.453-.107-.714-.207-.911a2.25 2.25 0 0 0-.984-.984c-.197-.1-.458-.17-.912-.207-.462-.037-1.057-.038-1.909-.038h-1.9c-.852 0-1.447 0-1.91.038Zm-2.103 7.821-.006-.08.044-.049 1.8-2.159a.25.25 0 0 1 .368-.016l3.226 3.225a.75.75 0 0 0 1.06 0l1.71-1.71a.25.25 0 0 1 .372.021l1.213 1.516q-.032.09-.07.165c-.216.423-.56.767-.984.983-.197.1-.458.17-.912.207-.462.037-1.057.038-1.909.038h-1.9c-.852 0-1.447 0-1.91-.038-.453-.037-.714-.107-.911-.207a2.25 2.25 0 0 1-.984-.984c-.1-.197-.17-.458-.207-.912"></path></svg>':`<img src="${AssetsPath + element?.thumbnail_img}" alt="">`
                    }
                  </div>
                  <div class="title">${element?.name}</div>
                </div>
              </div>
            </label>
          `);
        });

        const paginationData = response.data;
        let currentPage = paginationData.current_page;
        let lastPage = paginationData.last_page;
        //renderPagination(currentPage, lastPage);
         console.log("Total Records1 "+response.data.total);
        if (response.data.total > 10) {
          console.log("Total Records 2 "+response.data.total);
        renderPagination(currentPage, lastPage);
        }else{
           $('#pagination').empty().hide();
        }
        $(".product-checkbox").on("change", function() {
          const productId = $(this).val();
          const isChecked = $(this).prop("checked");
          
          if (isChecked) {
            // Add to global array if not already present
            if (!product_ids.includes(productId)) {
              product_ids.push(productId);
            }
          } else {
            // Remove from global array
            const index = product_ids.indexOf(productId);
            if (index > -1) {
              product_ids.splice(index, 1);
            }
          }
        });
        

            $("#product_load").addClass("d-none");

        // add product ids
         $("#addProductIds").on("click", function() {
          // product_ids is already maintained globally
          
          if (product_ids.length != 0) {
            $('#product_id').empty();
            product_ids.forEach(channels => {
              $('#product_id').append(`<option value="${channels}" selected>${channels}</option>`);
            });
            
            // Fetch and display all selected products
            fetchProductsByIds(product_ids).then(response => {
              if (response.status_code === 200) {
                addedProducts(response.data, product_ids);
              }
            });
          } else {
            $('#product_id').empty();
            $(".added-products").empty();
            $(".product-empty").removeClass("d-none");
            $(".added-products").addClass("d-none");
          }
          
        });
      }
        // add product ids end
        
      } else if (response.statusCode == 404) {
        $("#table_product").html("<img class='dataTable-custom-empty-icon' src='/images/no-record-found.svg'>");
        $("#product_load").addClass("d-none");
      }
      else {
        showToast(response.message, 'warning', 'warning');
        $("#product_load").addClass("d-none");
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

$(document).on('click', '#pagination .page-link', function (e) {
  e.preventDefault();
  const page = $(this).data('page'); 
  if (page) {
    currentPage = page;
    get_all_products(currentPage, per_page, search);
  }
});
function renderPagination(currentPage, lastPage) {
    let paginationHTML = `
        <div class="dataTables_wrapper dt-bootstrap5 no-footer">
            <div class="col-sm-12 col-md-5"></div>
            <div class="col-sm-12 col-md-7" style="float: right;">
                <div class="dataTables_paginate paging_simple_numbers" id="Table_View_paginate">
                    <ul class="pagination">
    `;

    const maxVisiblePages = 10;
    let startPage, endPage;

    // Calculate the range of pages to display
    if (lastPage <= maxVisiblePages) {
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
        paginationHTML += `<li class="paginate_button page-item previous"><a href="#" class="page-link" data-page="${currentPage - 1}">Previous</a></li>`;
    }

    // First page shortcut (optional)
    if (startPage > 1) {
        paginationHTML += `<li class="paginate_button page-item"><a href="#" class="page-link" data-page="1">1</a></li>`;
        if (startPage > 2) {
            paginationHTML += `<li class="paginate_button page-item disabled"><span class="page-link">...</span></li>`;
        }
    }

    // Numbered page buttons
    for (let i = startPage; i <= endPage; i++) {
        let activeClass = i === currentPage ? 'active' : '';
        paginationHTML += `<li class="paginate_button page-item ${activeClass}"><a href="#" class="page-link ${activeClass}" data-page="${i}">${i}</a></li>`;
    }

    // Last page shortcut (optional)
    if (endPage < lastPage) {
        if (endPage < lastPage - 1) {
            paginationHTML += `<li class="paginate_button page-item disabled"><span class="page-link">...</span></li>`;
        }
        paginationHTML += `<li class="paginate_button page-item"><a href="#" class="page-link" data-page="${lastPage}">${lastPage}</a></li>`;
    }

    // Next button
    if (currentPage < lastPage) {
        paginationHTML += `<li class="paginate_button page-item next"><a href="#" class="page-link" data-page="${currentPage + 1}">Next</a></li>`;
    }

    paginationHTML += `</ul></div></div></div>`;
    $('#pagination').html(paginationHTML);
}

function addedProducts(products, product_ids) {
  let added_product = [];
  
  products.forEach(element => {
    if(product_ids.includes(element.uuid)) {
      added_product.push(element);
    }
  });

  let product_html = "";
  $(".product-empty").addClass("d-none");
  $(".added-products").removeClass("d-none");
  added_product.forEach((element, i) => {
    product_html += `<div class="simple-product-item" 
                data-price="${element.unit_price}" 
                data-date="${element.created_at}"
                data-title="${element.name.toLowerCase()}">
        <div>${i + 1}.</div>
        <div class="info">
          <div>
            <div class="img">
              ${element?.thumbnail_img == null ?
                '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" width="16" height="16" fill="#8a8a8a"><path d="M10.5 7a1.5 1.5 0 1 0-.001-3.001 1.5 1.5 0 0 0 .001 3.001"></path><path fill-rule="evenodd" d="M7.018 1.5h1.964c.813 0 1.469 0 2 .043.546.045 1.026.14 1.47.366.706.36 1.28.933 1.64 1.639.226.444.32.924.365 1.47.043.531.043 1.187.043 2v1.964c0 .813 0 1.469-.043 2-.045.546-.14 1.026-.366 1.47a3.76 3.76 0 0 1-1.638 1.64c-.445.226-.925.32-1.471.365-.531.043-1.187.043-2 .043h-1.964c-.813 0-1.469 0-2-.043-.546-.045-1.026-.14-1.47-.366a3.76 3.76 0 0 1-1.64-1.638c-.226-.445-.32-.925-.365-1.471-.043-.531-.043-1.187-.043-2v-1.964c0-.813 0-1.469.043-2 .045-.546.14-1.026.366-1.47.36-.706.933-1.28 1.639-1.64.444-.226.924-.32 1.47-.365.531-.043 1.187-.043 2-.043m-1.877 1.538c-.454.037-.715.107-.912.207a2.25 2.25 0 0 0-.984.984c-.1.197-.17.458-.207.912-.037.462-.038 1.057-.038 1.909v1.429l.723-.868a1.75 1.75 0 0 1 2.582-.117l2.695 2.695 1.18-1.18a1.75 1.75 0 0 1 2.604.145l.216.27v-2.374c0-.852 0-1.447-.038-1.91-.037-.453-.107-.714-.207-.911a2.25 2.25 0 0 0-.984-.984c-.197-.1-.458-.17-.912-.207-.462-.037-1.057-.038-1.909-.038h-1.9c-.852 0-1.447 0-1.91.038Zm-2.103 7.821-.006-.08.044-.049 1.8-2.159a.25.25 0 0 1 .368-.016l3.226 3.225a.75.75 0 0 0 1.06 0l1.71-1.71a.25.25 0 0 1 .372.021l1.213 1.516q-.032.09-.07.165c-.216.423-.56.767-.984.983-.197.1-.458.17-.912.207-.462.037-1.057.038-1.909.038h-1.9c-.852 0-1.447 0-1.91-.038-.453-.037-.714-.107-.911-.207a2.25 2.25 0 0 1-.984-.984c-.1-.197-.17-.458-.207-.912"></path></svg>':`<img src="${AssetsPath + element?.thumbnail_img}" alt="">`
          }
            </div>
            <a href="" class="title">${element.name}</a>
          </div>
          <div class="actions">
            <div class="dropdown  d-none">
              <div class="dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" data-bs-auto-close="outside"><span class="active">Active</span></div>
              <div class="dropdown-menu dropdown-menu-end border">`;

              if(element.sales_channels.length != 0) {
                element.sales_channels.forEach(element1 => {
                  if(element1.name == "Online Store") {
                    product_html += `<div class="pos-item"><span class="circleBefore active">Online Store</span></div>`;
                  }
                  if(element1.name == "Point of Sale") {
                    product_html += `<div class="pos-item"><span class="circleBefore pos">Point of Sale</span>
                                <p class="ms-3">Channel needs attention. <a href="">View channel</a></p></div>`;
                  }
                });

              } else {
                product_html += `<span class="desc md">Not included in any sales channels</span>`
              }


    product_html += `</div>
            </div>
            <a href="" data-productid="${element.uuid}" class="del"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" width="16" height="16" fill="#616161"><path d="M10.72 11.78a.75.75 0 1 0 1.06-1.06l-2.72-2.72 2.72-2.72a.75.75 0 0 0-1.06-1.06l-2.72 2.72-2.72-2.72a.75.75 0 0 0-1.06 1.06l2.72 2.72-2.72 2.72a.75.75 0 1 0 1.06 1.06l2.72-2.72z"></path></svg></a>
          </div>
        </div>
      </div>`;

  });

  $(".added-products").empty();
  $(".added-products").html(product_html);
  
  // Initialize sorting after products are added
  initializeProductSorting();
}

$(".added-products").on("click", ".del", function(e){
  e.preventDefault();
  removeProductFromAddedProducts($(this), $(this).data("productid"));
});

$("[name='smart']").on("change", function(){
  if($(this).val() == 0) {
    $("#Conditions").addClass("d-none");
    $("#Manual").removeClass("d-none");
  }
  if($(this).val() == 1) {
    $("#Conditions").removeClass("d-none");
    $("#Manual").addClass("d-none");
  }
});

function removeProductFromAddedProducts(element, uuid) {
  // Remove from global array
  const index = product_ids.indexOf(uuid);
  if (index > -1) {
    product_ids.splice(index, 1);
  }

  // Uncheck the checkbox in the modal
  $("#table_product .product-checkbox").each((i, element1) => {
    if($(element1).val() == uuid) {
      $(element1).prop("checked", false);
    }
  });

  let newArray = [];
  $("#product_id option:selected").each(function () {
    if ($(this).val() !== uuid) {
      newArray.push($(this).val());
    }
  });

  $('#product_id').empty();
  newArray.forEach(id => {
    $('#product_id').append(`<option value="${id}" selected>${id}</option>`)
  });
  
  if(newArray.length == 0) {
    $(".product-empty").removeClass("d-none");
    $(".added-products").addClass("d-none");
  }

  $(element).parents(".simple-product-item").remove();
  
}

// Add search functionality for the product modal
$("#custom_search_po").off("input").on("input", function () {
  const searchQuery = $(this).val().trim();
  search = searchQuery;
  
  $("#table_product").html('<div class="text-center p-3">Searching...</div>');
  
  get_all_products(1, per_page, searchQuery);
});

// Search functionality for selected products in create collection
$("#createSearchProduct").on("input", function () {
  const searchQuery = $(this).val().trim().toLowerCase();

  if (product_ids.length === 0) {
    // If no products are selected, show empty state
    $(".product-empty").removeClass("d-none");
    $(".added-products").addClass("d-none");
    return;
  }

  // Fetch selected products by their IDs
  fetchProductsByIds(product_ids).then(response => {
    if (response.status_code === 200) {
      const allProducts = response.data;

      // Filter products based on search query
      const filteredProducts = allProducts.filter(product =>
        product.name.toLowerCase().includes(searchQuery)
      );

      // Update the UI with filtered products
      let product_html = "";
      $(".added-products").empty();

      if (filteredProducts.length > 0) {
        $(".product-empty").addClass("d-none");
        $(".added-products").removeClass("d-none");

        filteredProducts.forEach((element, i) => {
          product_html += `
            <div class="simple-product-item" 
                data-price="${element.unit_price}" 
                data-date="${element.created_at}"
                data-title="${element.name.toLowerCase()}">
              <div>${i + 1}.</div>
              <div class="info">
                <div>
                  <div class="img">
                    ${element?.thumbnail_img == null ?
                      '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" width="16" height="16" fill="#8a8a8a"><path d="M10.5 7a1.5 1.5 0 1 0-.001-3.001 1.5 1.5 0 0 0 .001 3.001"></path><path fill-rule="evenodd" d="M7.018 1.5h1.964c.813 0 1.469 0 2 .043.546.045 1.026.14 1.47.366.706.36 1.28.933 1.64 1.639.226.444.32.924.365 1.47.043.531.043 1.187.043 2v1.964c0 .813 0 1.469-.043 2-.045.546-.14 1.026-.366 1.47a3.76 3.76 0 0 1-1.638 1.64c-.445.226-.925.32-1.471.365-.531.043-1.187.043-2 .043h-1.964c-.813 0-1.469 0-2-.043-.546-.045-1.026-.14-1.47-.366a3.76 3.76 0 0 1-1.64-1.638c-.226-.445-.32-.925-.365-1.471-.043-.531-.043-1.187-.043-2v-1.964c0-.813 0-1.469.043-2 .045-.546.14-1.026.366-1.47.36-.706.933-1.28 1.639-1.64.444-.226.924-.32 1.47-.365.531-.043 1.187-.043 2-.043m-1.877 1.538c-.454.037-.715.107-.912.207a2.25 2.25 0 0 0-.984.984c-.1.197-.17.458-.207.912-.037.462-.038 1.057-.038 1.909v1.429l.723-.868a1.75 1.75 0 0 1 2.582-.117l2.695 2.695 1.18-1.18a1.75 1.75 0 0 1 2.604.145l.216.27v-2.374c0-.852 0-1.447-.038-1.91-.037-.453-.107-.714-.207-.911a2.25 2.25 0 0 0-.984-.984c-.197-.1-.458-.17-.912-.207-.462-.037-1.057-.038-1.909-.038h-1.9c-.852 0-1.447 0-1.91.038Zm-2.103 7.821-.006-.08.044-.049 1.8-2.159a.25.25 0 0 1 .368-.016l3.226 3.225a.75.75 0 0 0 1.06 0l1.71-1.71a.25.25 0 0 1 .372.021l1.213 1.516q-.032.09-.07.165c-.216.423-.56.767-.984.983-.197.1-.458.17-.912.207-.462.037-1.057.038-1.909.038h-1.9c-.852 0-1.447 0-1.91-.038-.453-.037-.714-.107-.911-.207a2.25 2.25 0 0 1-.984-.984c-.1-.197-.17-.458-.207-.912"></path></svg>' :
                      `<img src="${AssetsPath + element?.thumbnail_img}" alt="">`
                    }
                  </div>
                  <a href="" class="title">${element.name}</a>
                </div>
                <div class="actions">
                  <div class="dropdown">
                    <div class="dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" data-bs-auto-close="outside"><span class="active">Active</span></div>
                    <div class="dropdown-menu dropdown-menu-end border">
                      ${element.sales_channels && element.sales_channels.length > 0 ?
                        element.sales_channels.map(channel => {
                          if (channel.name === "Online Store") {
                            return `<div class="pos-item"><span class="circleBefore active">Online Store</span></div>`;
                          } else if (channel.name === "Point of Sale") {
                            return `<div class="pos-item"><span class="circleBefore pos">Point of Sale</span>
                                    <p class="ms-3">Channel needs attention. <a href="">View channel</a></p></div>`;
                          }
                          return '';
                        }).join('') :
                        `<span class="desc md">Not included in any sales channels</span>`
                      }
                    </div>
                  </div>
                  <a href="" data-productid="${element.uuid}" class="del"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" width="16" height="16" fill="#616161"><path d="M10.72 11.78a.75.75 0 1 0 1.06-1.06l-2.72-2.72 2.72-2.72a.75.75 0 0 0-1.06-1.06l-2.72 2.72-2.72-2.72a.75.75 0 0 0-1.06 1.06l2.72 2.72-2.72 2.72a.75.75 0 1 0 1.06 1.06l2.72-2.72z"></path></svg></a>
                </div>
              </div>
            </div>`;
        });
        $(".added-products").html(product_html);
        
        // Re-initialize sorting after products are added
        initializeProductSorting();
      } else {
        $(".product-empty").removeClass("d-none");
        $(".added-products").addClass("d-none");
      }
    } else {
      $(".product-empty").removeClass("d-none");
      $(".added-products").addClass("d-none");
      showToast('Error fetching products', 'Error', 'error');
    }
  }).catch(error => {
    console.error('Error fetching products:', error);
    $(".product-empty").removeClass("d-none");
    $(".added-products").addClass("d-none");
    showToast('Error fetching products', 'Error', 'error');
  });
});


// All Conditions

let activeTypes = [];
let activeCategories = [];
let activeVendors = [];
let activeTags = [];

const conditionsIfEqual = {
  "Title":[
    {
      name: "is equal to",
      _name: "is_equal_to"
    },
    {
      name: "is not equal to",
      _name: "is_not_equal_to"
    },
    {
      name: "starts with",
      _name: "starts_with"
    },
    {
      name: "ends with",
      _name: "ends_with"
    },
    {
      name: "contains",
      _name: "contains"
    },
    {
      name: "does not contain",
      _name: "does_not_contain"
    }
  ],
  "Type":[
    {
      name: "is equal to",
      _name: "is_equal_to"
    },
    {
      name: "is not equal to",
      _name: "is_not_equal_to"
    },
    {
      name: "starts with",
      _name: "starts_with"
    },
    {
      name: "ends with",
      _name: "ends_with"
    },
    {
      name: "contains",
      _name: "contains"
    },
    {
      name: "does not contain",
      _name: "does_not_contain"
    }
  ],
  "Category":[
    {
      name: "is equal to",
      _name: "is_equal_to"
    },
    {
      name: "is not equal to",
      _name: "is_not_equal_to"
    }
  ],
  "Vendor":[
    {
      name: "is equal to",
      _name: "is_equal_to"
    },
    {
      name: "is not equal to",
      _name: "is_not_equal_to"
    },
    {
      name: "starts with",
      _name: "starts_with"
    },
    {
      name: "ends with",
      _name: "ends_with"
    },
    {
      name: "contains",
      _name: "contains"
    },
    {
      name: "does not contain",
      _name: "does_not_contain"
    }
  ],
  "Tag":[
    {
      name: "is equal to",
      _name: "is_equal_to"
    }
  ],
  "Price":[
    {
      name: "is equal to",
      _name: "is_equal_to"
    },
    {
      name: "is not equal to",
      _name: "is_not_equal_to"
    },
    {
      name: "is greater than",
      _name: "is_greater_than"
    },
    {
      name: "is less than",
      _name: "is_less_than"
    }
  ],
  "Compare-at price":[
    {
      name: "is equal to",
      _name: "is_equal_to"
    },
    {
      name: "is not equal to",
      _name: "is_not_equal_to"
    },
    {
      name: "is greater than",
      _name: "is_greater_than"
    },
    {
      name: "is less than",
      _name: "is_less_than"
    },
    {
      name: "is not empty",
      _name: "is_not_empty"
    },
    {
      name: "is empty",
      _name: "is_empty"
    }
  ],
  "Weight":[
    {
      name: "is equal to",
      _name: "is_equal_to"
    },
    {
      name: "is not equal to",
      _name: "is_not_equal_to"
    },
    {
      name: "is greater than",
      _name: "is_greater_than"
    },
    {
      name: "is less than",
      _name: "is_less_than"
    }
  ],
  "Inventory stock":[
    {
      name: "is equal to",
      _name: "is_equal_to"
    },
    {
      name: "is greater than",
      _name: "is_greater_than"
    },
    {
      name: "is less than",
      _name: "is_less_than"
    }
  ],
  "Variant's title":[
    {
      name: "is equal to",
      _name: "is_equal_to"
    },
    {
      name: "is not equal to",
      _name: "is_not_equal_to"
    },
    {
      name: "starts with",
      _name: "starts_with"
    },
    {
      name: "ends with",
      _name: "ends_with"
    },
    {
      name: "contains",
      _name: "contains"
    },
    {
      name: "does not contain",
      _name: "does_not_contain"
    }
  ],"Meta Title": [
    {
      name: "is equal to",
      _name: "is_equal_to"
    }
  ],
  "Meta Description": [
    {
      name: "is equal to",
      _name: "is_equal_to"
    }
  ],
  "Meta URL": [
    {
      name: "is equal to",
      _name: "is_equal_to"
    }
  ],
}

const conditionsIfEqualTo = {
  "Title": function(element){
    rebuildEqualSelect("Title", element);
    $(element).parents(".item").find(".product-condition-to-parent").html(`
      <div class="input">
        <input type="text" class="form-control md product-condition-to">
      </div>
    `);
  },
  "Type": function(element){
    rebuildEqualSelect("Type", element);
    console.log(element);
    console.log($(element).parents(".item").find(".product-condition-to-parent select"));
    $(element).parents(".item").find(".product-condition-to-parent").html("");
    $(element).parents(".item").find(".product-condition-to-parent").html(`
      <select class="product-condition-equalto product-condition-to" data-placeholder="">
        <option></option>
      </select>
    `);
    activeTypes.forEach(element1 => {
      $(element).parents(".item").find(".product-condition-to-parent select").append(`<option value="${element1}">${element1}</option>`);
    });
    $(element).parents(".item").find(".product-condition-to-parent select").select2({
      allowClear: true,
      width: '100%',
      placeholder: function () {
        $(this).data('placeholder');
      },
    });
  },
  "Category": function(element){
    rebuildEqualSelect("Category", element);
    $(element).parents(".item").find(".product-condition-to-parent").html("");
    $(element).parents(".item").find(".product-condition-to-parent").html(`
      <select class="product-condition-equalto product-condition-to" data-placeholder="">
        <option></option>
      </select>
    `);
    activeCategories.forEach(element1 => {
      $(element).parents(".item").find(".product-condition-to-parent select").append(`<option value="${element1}">${element1}</option>`);
    });
    $(element).parents(".item").find(".product-condition-to-parent select").select2({
      allowClear: true,
      width: '100%',
      placeholder: function () {
        $(this).data('placeholder');
      },
    });
  },
  "Vendor": function(element){
    rebuildEqualSelect("Vendor", element);
    $(element).parents(".item").find(".product-condition-to-parent").html("");
    $(element).parents(".item").find(".product-condition-to-parent").html(`
      <select class="product-condition-equalto product-condition-to" data-placeholder="">
        <option></option>
      </select>
    `);
    activeVendors.forEach(element1 => {
      $(element).parents(".item").find(".product-condition-to-parent select").append(`<option value="${element1}">${element1}</option>`);
    });
    $(element).parents(".item").find(".product-condition-to-parent select").select2({
      allowClear: true,
      width: '100%',
      placeholder: function () {
        $(this).data('placeholder');
      },
    });
  },
  "Tag": function(element){
    rebuildEqualSelect("Tag", element);
    $(element).parents(".item").find(".product-condition-to-parent").html("");
    $(element).parents(".item").find(".product-condition-to-parent").html(`
      <select class="product-condition-equalto product-condition-to" data-placeholder="">
        <option></option>
      </select>
    `);
    activeTags.forEach(element1 => {
      $(element).parents(".item").find(".product-condition-to-parent select").append(`<option value="${element1}">${element1}</option>`);
    });
    $(element).parents(".item").find(".product-condition-to-parent select").select2({
      allowClear: true,
      width: '100%',
      placeholder: function () {
        $(this).data('placeholder');
      },
    });
  },
  "Price": function(element){
    rebuildEqualSelect("Price", element);
    $(element).parents(".item").find(".product-condition-to-parent").html(`
      <div class="input currency-input">
        <span class="currency-txt" style="top: 8px;">Rs</span>
        <input type="number" class="form-control md product-condition-to">
        <span class="error-txt"></span>
      </div>
    `);
  },
  "Compare-at price": function(element){
    rebuildEqualSelect("Compare-at price", element);
    $(element).parents(".item").find(".product-condition-to-parent").html(`
      <div class="input currency-input">
        <span class="currency-txt" style="top: 8px;">Rs</span>
        <input type="number" class="form-control md product-condition-to">
        <span class="error-txt"></span>
      </div>
    `);
  },
  "Weight": function(element){
    rebuildEqualSelect("Weight", element);
    $(element).parents(".item").find(".product-condition-to-parent").html(`
      <div class="input waight-input">
        <span class="waight-txt">kg</span>
        <input type="number" class="form-control md product-condition-to">
        <span class="error-txt"></span>
      </div>
    `);
  },
  "Inventory stock": function(element){
    rebuildEqualSelect("Inventory stock", element);
    $(element).parents(".item").find(".product-condition-to-parent").html(`
      <div class="input">
        <input type="text" class="form-control md product-condition-to">
      </div>
    `);
  },
  "Variant's title": function(element){
    rebuildEqualSelect("Variant's title", element);
    $(element).parents(".item").find(".product-condition-to-parent").html(`
      <div class="input">
        <input type="text" class="form-control md product-condition-to">
      </div>
    `);
  },
  "Meta Title": function(element) {
  rebuildEqualSelect("Meta Title", element);
  $(element).parents(".item").find(".product-condition-to-parent").html(`
    <div class="input">
      <input type="text" class="form-control md product-condition-to">
    </div>
  `);
},
"Meta Description": function(element) {
  rebuildEqualSelect("Meta Description", element);
  $(element).parents(".item").find(".product-condition-to-parent").html(`
    <div class="input">
      <input type="text" class="form-control md product-condition-to">
    </div>
  `);
},
"Meta URL": function(element) {
  rebuildEqualSelect("Meta URL", element);
  $(element).parents(".item").find(".product-condition-to-parent").html(`
    <div class="input">
      <input type="text" class="form-control md product-condition-to">
    </div>
  `);
},
}

$(".product-conditions-box").on("change", ".product-condition-if", function(){
  conditionsIfEqualTo[$(this).val()]($(this));
});

function rebuildEqualSelect(name, element) {
  $(element).parents(".item").find(".product-condition-equal").empty();
  conditionsIfEqual[name].forEach(element1 => {
    $(element).parents(".item").find(".product-condition-equal").append(`<option value="${element1._name}">${element1.name}</option>`)
  });
}

function conditionIfAndEqual(conditionsIfEqual) {
  $(".product-conditions-box .items .item:last-child .product-condition-if").empty();
  for (let key in conditionsIfEqual) {
    $(".product-conditions-box .items .item:last-child .product-condition-if").append(`<option value="${key}">${key}</option>`)
  }
  $(".product-conditions-box .items .item:last-child .product-condition-if").select2({
    allowClear: false,
    width: '100%'
  });

  $(".product-conditions-box .items .item:last-child .product-condition-equal").empty();
  conditionsIfEqual["Title"].forEach(element => {
    $(".product-conditions-box .items .item:last-child .product-condition-equal").append(`<option value="${element._name}">${element.name}</option>`)
  });
  $(".product-conditions-box .items .item:last-child .product-condition-equal").select2({
    allowClear: false,
    width: '100%',
    minimumResultsForSearch: Infinity
  });
}
conditionIfAndEqual(conditionsIfEqual);

function isOneConditionItem() {
  if($(".product-conditions-box .items .item").length == 1) {
    $(".product-conditions-box .items .item a").addClass("d-none");
  } else {
    $(".product-conditions-box .items .item a").removeClass("d-none");
  }
}
isOneConditionItem();

// All Conditions end

$(".productsSortSelect").select2({
  allowClear: false,
  minimumResultsForSearch: Infinity,
  width: '100%'
});

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
        response.data.forEach(types => {
          activeTypes.push(types.type);
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
        response.data.forEach(category => {
          let spaces = '-'.repeat(category.level);
          activeCategories.push(`${spaces} ${category.name}`);
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
        response.data.forEach(vendor => {
          activeVendors.push(vendor.vendor);
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
        response.data.forEach(tag => {
          activeTags.push(tag.name);
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

// let editorInstance;
function CKeditor() {
  // Destroy existing instance if it exists
  if (editorInstance) {
    editorInstance.destroy()
      .then(() => {
        initializeEditor();
      })
      .catch(error => {
        console.error("Error destroying the editor instance:", error);
      });
  } else {
    initializeEditor();
  }
}
CKeditor();

function initializeEditor() {
  ClassicEditor.create(document.querySelector('#editorCk'), {
    toolbar: ['heading',
      '|',
      'fontColor',
      'fontSize',
      'fontBackgroundColor',
      'fontFamily',
      'bold',
      'italic',
      'underline',
      'link',
      'strikethrough',
      'bulletedList',
      'numberedList',
      '|',
      'alignment',
      'direction',
      'outdent',
      'indent',
      '|',
      'htmlEmbed',
      'imageInsert',
      'ckfinder',
      'blockQuote',
      'insertTable',
      'mediaEmbed',
      'undo',
      'redo',
      'findAndReplace',
      'removeFormat',
      'sourceEditing',
      'codeBlock',
      'fullScreen'],
  }).then(editor => {
    editorInstance = editor;
    $(".use-button").click(async function () {
      let shortCodeKey = $(this).attr("data-shortcode");
      try {
        $(".shortcodeAttr-item").html('');
        let attr = await showwidgets(shortCodeKey);
        let attrCount = Object.keys(attr.data.widget_fields).length;
        let widgetAttr = attr.widget_fields;
        if (attrCount > 0) {
          $("#shortcodeAttr").modal('show');
          $(".shortcodeAttr-item").html(attr.view);
          $('#shortcodeForm').on('submit', function (event) {
            event.preventDefault();

            const formData = $(this).serializeArray();
            let attributes = '';

            formData.forEach(field => {
              attributes += `${field.name}="${field.value}" `;
            });

            const shortcode = `<shortcode>[${attr.data.shortkey} ${attributes.trim()}][/${attr.data.shortkey}]</shortcode>`;

            editor.model.change(writer => {
              const insertPosition = editor.model.document.selection.getFirstPosition();
              editor.setData(editor.getData() + shortcode);
            });
            $("#shortcodeAttr").modal('hide');
          });
        } else {
          const shortcode = '<shortcode>[' + shortCodeKey + ']' + '[/' + shortCodeKey + ']</shortcode>';
          editor.model.change(writer => {
            const insertPosition = editor.model.document.selection.getFirstPosition();
            editor.setData(editor.getData() + shortcode);
          })
        }
        $("#shortcodeModal").modal('hide');
      } catch (error) {
        console.error('Error fetching widget:', error);
      }
    });
    $(".use-button-form").click(async function () {
      let shortCodeKey = $(this).attr("data-shortcode");
      try {
        $(".shortcodeAttr-item").html('');
        const shortcode = '<shortcode>[' + shortCodeKey + ']' + '[/' + shortCodeKey + ']</shortcode>';
        editor.model.change(writer => {
          const insertPosition = editor.model.document.selection.getFirstPosition();
          editor.setData(editor.getData() + shortcode);
        })
        $("#shortcodeModal").modal('hide');
      } catch (error) {
        console.error('Error fetching widget:', error);
      }
    });
  }).catch(error => {
    console.error('There was a problem initializing the editor.', error);
  });
}
function sortProducts(sortBy) {
  const $container = $(".added-products");
  const $products = $container.find(".simple-product-item").get();
  
  $products.sort((a, b) => {
    const $a = $(a);
    const $b = $(b);
    
    switch(sortBy) {
      case 'BEST_SELLING':
        return 0;
        
      case 'ALPHA_ASC':
        return $a.data('title').localeCompare($b.data('title'));
        
      case 'ALPHA_DESC':
        return $b.data('title').localeCompare($a.data('title'));
        
      case 'PRICE_DESC':
        return parseFloat($b.data('price')) - parseFloat($a.data('price'));
        
      case 'PRICE_ASC':
        return parseFloat($a.data('price')) - parseFloat($b.data('price'));
        
      case 'CREATED_DESC':
        return new Date($b.data('date')) - new Date($a.data('date'));
        
      case 'CREATED':
        return new Date($a.data('date')) - new Date($b.data('date'));
        
      case 'MANUAL':
        return 0;
        
      default:
        return 0;
    }
  });
  
  // Reattach sorted elements
  $.each($products, (index, product) => {
    $container.append(product);
    $(product).find("> div:first-child").text(index + 1 + ".");
  });
}

function initializeProductSorting() {
  $(".productsSortSelect").on('change', function() {
    const sortBy = $(this).val();
    sortProducts(sortBy);
  });
  
  const initialSort = $(".productsSortSelect").val();
  sortProducts(initialSort);
}
$("#createCollectionFrom").on("submit", function (e) {
  e.preventDefault();

  let status = true;
  let errorMessages = [];

  // Validate name
  if ($('input[name="name"]').val().trim() === "") {
    $('input[name="name"]').parent().addClass('error');
    $('input[name="name"]').parent().find('.error-txt').html("Title can’t be blank");
    errorMessages.push("Title can’t be blank");
    status = false;
  }

  // Validate slug
  if ($('input[name="slug"]').val().trim() === "") {
    $('input[name="slug"]').parent().addClass('error');
    $('input[name="slug"]').parent().find('.error-txt').html("Slug can’t be blank");
    errorMessages.push("Slug can’t be blank");
    status = false;
  }

  // Show error messages in #formError
  if (errorMessages.length > 0) {
    $('#formError').find('.error-bottom').html(errorMessages.join("<br>"));
    $('#formError').find('.error-top').html(`There ${errorMessages.length === 1 ? 'is 1 error' : 'are ' + errorMessages.length + ' errors'} with this product:`);
    $('#formError').show();
    window.scrollTo(0, 0);
  }

  // Clear errors on input
  $('input[name="name"], input[name="slug"]').on("input", function () {
    $("#formError").hide();
    $(this).parent().removeClass("error");
    $(this).parent().find(".error-txt").html("");
  });

  let date = $("#putdate").val();
  let time = $("#putTime").val();

  let formattedDate = new Date(date + ' ' + time);

  let year = formattedDate.getFullYear();
  let month = ('0' + (formattedDate.getMonth() + 1)).slice(-2);
  let day = ('0' + formattedDate.getDate()).slice(-2);

  let hours = ('0' + formattedDate.getHours()).slice(-2);
  let minutes = ('0' + formattedDate.getMinutes()).slice(-2);
  let seconds = ('0' + formattedDate.getSeconds()).slice(-2);

  let published_datetime = `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;

  $("[name='published_datetime']").val(published_datetime);

  let formData = new FormData(this);

  let editorVal = getEditorValue();

  formData.append('description', editorVal);
  formData.append('featured', 1);
  formData.append('top', 1);
  formData.append('status', $("select[name='status']").val());
  
  // Conditions
  if($("[name='smart']:checked").val() === "1") {
    $(".product-conditions-box .items .item").each((i, element) => {
      formData.append('conditions[]', `${$(element).find(".product-condition-if").val()},${$(element).find(".product-condition-equal").val()},${$(element).find(".product-condition-to").val()}`);
    });
  }else {
    formData.append('conditions[]', []);
  } 
  // Conditions end

  // for (var pair of formData.entries()) {
  //   console.log(pair[0] + ': ' + pair[1]);
  // }
  // return

  if (status) {
    $.ajax({
      url: ApiPlugin + "/ecommerce/collection/store",
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
          showToast(title, 'Success', 'success', "?P=collections&M="+menu_id);
        }
      },
      error: function (xhr, status, err) {
        imgload.hide();
        if (xhr.status === 422) {
          let errors = xhr.responseJSON.errors;
          errors = JSON.parse(errors);
          let errorMessages = "";

          $.each(errors, function (field, messages) {
            messages.forEach(function (message) {
              errorMessages += `<li>${message}</li>`;
            });
          });
          let htmlError = `<ul>${errorMessages}</ul>`;
          showToast(htmlError, 'Error', 'error');
        } else {
          showToast("Something went wrong!", 'Error', 'error');
        }
      }
    });
  }

});