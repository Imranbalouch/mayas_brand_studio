document.title = "Dashboard | Duplicate Purchase Order";
var urlParams = new URLSearchParams(window.location.search);
var id = urlParams.get('id');
let productArray = [];
if (id) {
  $.ajax({
    url: ApiPlugin + "/ecommerce/purchase-order/edit/" + id,
    type: "GET",
    dataType: "json",
    beforeSend: function (xhr) {
      xhr.setRequestHeader("Authorization", "Bearer " + strkey);
      xhr.setRequestHeader("menu-uuid", menu_id);
      imgload.show();
    },
    success: async function (response) {
      imgload.hide();
      if (response.status_code == 200) {
        let data = response.data;
        console.log(data);
        await select_supplier();
        await select_location();
        await payment_terms();
        await supplier_currency();
        await shipping_carrier();
        $("#Selectsupplier").attr('data-uuid', data.supplier_id);
        $("#Selectsupplier").text(data.supplier.company);
        $("#Selectlocation").attr('data-uuid', data.warehouse_id);
        $("#Selectlocation").text(data.warehouse.warehouse_name);
        $("input[name=reference-number]").val(data.reference_number);
        $("input[name=note-supplier]").val(data.note_to_supplier);
        $("input[name=tag]").val(data.tags);
        $("input[name=tracking-number]").val(data.tracking_number);
        $("input[name=estimated-arrival]").val(data.ship_date);

        //payment_terms
        $("#payment_terms").val(data.payment_term_id);
        $("#payment_terms").selectpicker("refresh");
        $("#supplier_currency").val(data.supplier_currency_id);
        $("#supplier_currency").selectpicker("refresh");
        $("#shipping_carrier").val(data.ship_carrier_id);
        $("#shipping_carrier").selectpicker("refresh");
        // product items show
        let preSelectedItems = response.data.purchaseOrderItems;
        // 2. Load full product list and then check items
        // table_product(() => {
        //     markPreselectedProducts(preSelectedItems);
        // });
        await table_product();
        renderProductTable(preSelectedItems);
        calculatePOS();
      }
    },
    error: function (error) {
      error = error.responseJSON;
      if (error.status_code == 404) {
        showToast(error.message, 'Error', 'error', '?P=view-purchase-order&M=' + menu_id + '&id=' + id);
      } else {
        showToast('Internal Server Error.', 'Error', 'error', '?P=view-purchase-order&M=' + menu_id + '&id=' + id);
      }
    }
  });
}

function markPreselectedProducts(preSelectedItems) {
  console.log("preSelectedItems", preSelectedItems);

  preSelectedItems.forEach(item => {
    if (item.variant_id) {
      // Check variant
      console.log("Check variant", $(`.productStock-${item.variant_id} input[name='product_check']`));

      // $(`.product-${item.product_id} > .parent input[name='product_check']`).prop('checked', true);
      $(`.productStock-${item.variant_id} input[name='product_check']`).prop('checked', true);
    } else {
      console.log("Check parent product", $(`.product-${item.product_id} > .parent input[name='product_check']`).prop('checked', true));
      // Check parent product
      $(`.product-${item.product_id} > .parent input[name='product_check']`).prop('checked', true);
    }
  });

  // Then update the product table with selected items
  updateSelectedProducts();

  // Fill the existing qty/cost/tax
  preSelectedItems.forEach(item => {
    let row = $(`input[name='product_uuid'][value='${item.product_id}']`)
      .closest("tr");

    if (item.variant_id) {
      row = $(`input[name='variant_uuid'][value='${item.variant_id}']`)
        .closest("tr");
    }

    row.find("input[name='input-quantity']").val(item.qty);
    row.find(".custom-cost").val(item.cost);
    row.find(".custom-tax").val(item.tax);
  });

  calculatePOS(); // recalculate totals
}

function updateSelectedProducts() {
  let productList = $(".table-first-item .custom-checkbox input[name='product_check']:checked");
  let productArray = [];
  console.log("productList", productList);

  productList.each(function () {
    let parentRow = $(this);
    console.log("parentRow", parentRow);

    let checkProductVar = parentRow.parents(".table-first-item").find(".child .custom-checkbox input[name='product_check']:checked");
    let productUuid = parentRow.parents(".table-first-item").find(".child").find(".product-name").attr("data-product-uuid");
    let productTitle = parentRow.parents(".table-first-item").find(".parent").find('.product-name').text().trim();
    let productImage = parentRow.parents(".table-first-item").find(".parent").find('.product-name img').attr("src");
    let productQty = parentRow.find(".total-name").text().trim();
    console.log("checkProductVar", checkProductVar);
    console.log("productImage", productImage);

    if (checkProductVar.length > 0) {
      checkProductVar.each(function () {
        let variantRow = $(this).closest(".child");
        console.log("variantRow", variantRow.find(".total-name").text().trim(), variantRow.find(".product-name img").attr("src"));

        let productVarUuid = variantRow.find(".product-name").attr("data-variant-uuid");
        let productStock = {
          "variant_uuid": productVarUuid,
          "product_uuid": productUuid,
          "name": productTitle,
          "varaint": variantRow.find(".product-name").text().trim(),
          "qty": variantRow.find(".total-name").text().trim(),
          "img": variantRow.find(".product-name img").attr("src")
        }
        productArray.push(productStock);
      });
    } else {
      productArray.push({
        "variant_uuid": "",
        "product_uuid": productUuid,
        "name": productTitle,
        "varaint": "",
        "qty": productQty,
        "img": productImage
      });
    }
  });
  console.log("productArray", productArray);

  renderProductTable(productArray);
}

function renderProductTable(productData) {
  let productTable = "";

  productData.forEach(element => {
    // purchase order product selected
    const selectedData = {
      product_id: element.product_id,  // parent product ID
      variant_id: element.variant_id   // child variant ID
    };
    // Find the matching child checkbox and check it
    const childRows = document.querySelectorAll('.table-row.child');

    childRows.forEach(row => {
      const productUuid = row.querySelector('.product-name')?.dataset.productUuid;
      const variantUuid = row.querySelector('.product-name')?.dataset.variantUuid;

      if (productUuid === selectedData.product_id && variantUuid === selectedData.variant_id) {
        // Check the child checkbox
        const childCheckbox = row.querySelector('input[type="checkbox"][name="product_check"]');
        if (childCheckbox) {
          childCheckbox.checked = true;
          childCheckbox.disabled = true;
          $(childCheckbox).parents('.table-row').find('.product-name').after('<span>Item already selected</span>');
        }

        // Check the parent checkbox
        const parentRow = row.closest('.table-first-item')?.querySelector('.table-row.parent');
        const parentCheckbox = parentRow?.querySelector('input[type="checkbox"][name="product_check"]');

        if (parentCheckbox) {
          parentCheckbox.checked = true;
          parentCheckbox.disabled = true;

        }
      }
    });

    //// product table show in purchase order
    let producrObj = {
      "variant_uuid": element.variant_id,
      "product_uuid": element.product_id,
      "name": element.product.name,
      "varaint": element.variant ? element.variant.sku : '',
      "qty": element.quantity,
      "img": element.variant ? AssetsPath + element.variant.image : AssetsPath + element.product.thumbnail_img
    };
    // // console.log("productArray",productArray);
    // // console.log("producrObj",producrObj);

    productArray.push(producrObj);
    productTable += `<tr>
                                <td style="display: none;">
                                    <input name="product_uuid" value="${element.product_id}" type="text" hidden>
                                    <input name="variant_uuid" value="${element.variant_id}" type="text" hidden>
                                </td>
                            
                                <!-- <td style="width: 100px;"><input type="checkbox" class="form-check-input"></td> -->
                                <td>
                                    <div style="width: auto;" class="product-name">
                                            ${element.variant ? `<img src="${element.variant ? AssetsPath + element.variant.image : AssetsPath + element.product.thumbnail_img}" alt="Image" class="size-50px img-fit"
                                                        onerror="this.onerror=null; this.outerHTML=\`<svg width='60' height='60' viewBox='0 0 42 42' fill='none' xmlns='http://www.w3.org/2000/svg'><rect x='1' y='1' width='40' height='40' rx='4' fill='white'/><rect x='1' y='1' width='40' height='40' rx='4' stroke='#232323' stroke-opacity='0.1'/><path d='M26.8333 13.5H15.1667C14.2462 13.5 13.5 14.2462 13.5 15.1667V26.8333C13.5 27.7538 14.2462 28.5 15.1667 28.5H26.8333C27.7538 28.5 28.5 27.7538 28.5 26.8333V15.1667C28.5 14.2462 27.7538 13.5 26.8333 13.5Z' stroke='#8D8D8D' stroke-linecap='round' stroke-linejoin='round'/><path d='M18.0834 19.3334C18.7737 19.3334 19.3334 18.7737 19.3334 18.0834C19.3334 17.393 18.7737 16.8334 18.0834 16.8334C17.393 16.8334 16.8334 17.393 16.8334 18.0834C16.8334 18.7737 17.393 19.3334 18.0834 19.3334Z' stroke='#8D8D8D' stroke-linecap='round' stroke-linejoin='round'/><path d='M28.5 23.5L24.3333 19.3334L15.1666 28.5' stroke='#8D8D8D' stroke-linecap='round' stroke-linejoin='round'/></svg>\`;">`
        : `<svg width='60' height='60' viewBox='0 0 42 42' fill='none' xmlns='http://www.w3.org/2000/svg'><rect x='1' y='1' width='40' height='40' rx='4' fill='white'/><rect x='1' y='1' width='40' height='40' rx='4' stroke='#232323' stroke-opacity='0.1'/><path d='M26.8333 13.5H15.1667C14.2462 13.5 13.5 14.2462 13.5 15.1667V26.8333C13.5 27.7538 14.2462 28.5 15.1667 28.5H26.8333C27.7538 28.5 28.5 27.7538 28.5 26.8333V15.1667C28.5 14.2462 27.7538 13.5 26.8333 13.5Z' stroke='#8D8D8D' stroke-linecap='round' stroke-linejoin='round'/><path d='M18.0834 19.3334C18.7737 19.3334 19.3334 18.7737 19.3334 18.0834C19.3334 17.393 18.7737 16.8334 18.0834 16.8334C17.393 16.8334 16.8334 17.393 16.8334 18.0834C16.8334 18.7737 17.393 19.3334 18.0834 19.3334Z' stroke='#8D8D8D' stroke-linecap='round' stroke-linejoin='round'/><path d='M28.5 23.5L24.3333 19.3334L15.1666 28.5' stroke='#8D8D8D' stroke-linecap='round' stroke-linejoin='round'/></svg>`
      }
                                        <div>
                                            <span>${element.product.name}</span>
                                            ${element.variant ? `<span class="varient-text">${element.variant.sku}</span>` : ''}
                                        </div>
                                    </div>

                                </td>
                                <td> 
                                    <div class="sku-input">
                                        <input type="text" name="sku" value="${element.supplier_sku ? element.supplier_sku : ''}" class="form-control">
                                    </div>
                                </td>
                                <td>
                                    <div class="sku-input">
                                        <input type="number" name="input-quantity" min="0" oninput="calculatePOS(this)" value="${element.quantity}" class="form-control custom-qty">
                                    </div>
                                    <!-- <span class="create-tab-text">Shop location</span> -->
                                </td>
                                <td>
                                    <div class="sku-input">
                                        <input oninput="calculatePOS(this)" value="${element.unit_price}" style="padding-left: 30px !important;" type="number" min="0" placeholder="0.00" class="form-control custom-cost">
                                        <div class="rupees-text">Rs</div>
                                    </div>
                                </td>
                                <td>
                                    <div class="sku-input">
                                        <input oninput="calculatePOS(this)" min="0" value="${element.tax}" min="0" value="0" style="padding-right: 30px !important;" type="number" class="form-control custom-tax">
                                        <div style="right: 10px; left: auto;" class="rupees-text">%</div>
                                    </div>
                                </td>
                                <td>
                                    <span class="create-tab-text custom-total">Rs ${element.total_amount}</span>
                                    <input type="hidden" class="item-total-amount" value="${element.total_amount}">
                                </td>
                                <td>
                                    
                                </td>
                        </tr>`; // Your table row HTML here (as already written)


  });

  $("#variantOrdernumber").html(productArray.length);
  $("#product_table_one").removeClass("d-none");
  $("#product_table_one tbody").empty().append(productTable);


}


$(document).ready(function () {
  // table-first-item product-

  $("#duplicateTextarea").on("input", function () {
    var currentLength = $(this).val().length; // Current character count
    $("#current").text(currentLength); // Update count display

    if (currentLength >= 5000) {
      $(this).val($(this).val().substring(0, 5000)); // Prevent exceeding limit
    }
  });
  // Onload();


  $(document).on("change", ".table-first-item .table-row.parent input[type='checkbox']", function () {
    let isChecked = $(this).prop("checked");

    // Check/uncheck all child checkboxes based on parent
    $(this).closest('.table-first-item').find(".table-row.child input[type='checkbox']").prop("checked", isChecked);
  });

  // Child Checkbox Change Event
  $(document).on("change", ".table-first-item .table-row.child input[type='checkbox']", function () {
    let parentCheckbox = $(this).closest('.table-first-item').find(".table-row.parent input[type='checkbox']");
    let allChildren = $(this).closest('.table-first-item').find(".table-row.child input[type='checkbox']");

    // Check if any child is checked
    let anyChildChecked = allChildren.is(":checked");

    // Set parent checkbox based on child checkboxes
    parentCheckbox.prop("checked", anyChildChecked);
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

  // **Dropdown Selection for Location**
  $("#select_location").on("click", "li", function () {
    var selectedText = $(this).text().trim();
    let selectedUUID = $(this).find('input').attr("data-uuid");
    $("#Selectlocation").attr("data-uuid", selectedUUID);
    $("#Selectlocation").text(selectedText);
    $(".select-dropdown").removeClass("show");
  });

  // **Dropdown Selection for Supplier**
  $("#select_supplier").on("click", "li", function () {
    let selectedText = $(this).text().trim();
    let selectedUUID = $(this).find('input').attr("data-uuid");
    $("#Selectsupplier").attr("data-uuid", selectedUUID);
    $("#Selectsupplier").text(selectedText);
    $(".select-dropdown").removeClass("show");
  });

  // **Close dropdown if clicked outside**
  $(document).on("click", function (e) {
    if (!$(e.target).closest(".select-dropdown").length) {
      $(".select-dropdown").removeClass("show");
    }
  });

  $("#AddProduct").on("click", function () {
    let productList = $(".table-first-item .custom-checkbox input[name='product_check']:checked").parents(".parent");
    let productListVar = $(".table-first-item .custom-checkbox input[name='product_check']:checked").parents(".table-first-item").find(".child");
    let existingProductsVar = $("#product_table_one tbody tr").map(function () {
      return $(this).find("input[name='variant_uuid']").val();
    }).get();
    let existingProducts = $("#product_table_one tbody tr").map(function () {
      return $(this).find("input[name='product_uuid']").val();
    }).get();

    let productData = [];
    productList.each(function (key) {
      let checkProductVar = $(this).parents(".table-first-item").find(".child .custom-checkbox input[name='product_check']:checked");
      let productUuid = $(this).find(".product-name").attr("data-product-uuid");
      let productTitle = $(this).find(".product-name").text().trim();
      if (checkProductVar.length > 0) {
        checkProductVar.each(function () {
          let productVarUuid = $(this).parents(".child").find(".product-name").attr("data-variant-uuid");
          if (!existingProductsVar.includes(productVarUuid)) {
            let productStock = {
              "variant_uuid": productVarUuid,
              "product_uuid": productUuid,
              "name": productTitle,
              "varaint": $(this).parents(".child").find(".product-name").text().trim(),
              "qty": $(this).parents(".child").find(".total-name").text().trim(),
              "img": $(this).parents(".child").find(".product-name img").attr("src")
            };
            productData.push(productStock);
          }
        });
      } else if (!existingProducts.includes(productUuid)) {
        let productData = {
          "variant_uuid": "",
          "product_uuid": productUuid,
          "name": $(this).find(".product-name").text().trim(),
          "varaint": "",
          "qty": $(this).find(".total-name").text().trim(),
          "img": $(this).find(".product-name img").attr("src")
        };
        productData.push(productData);
      }
    });
    let productTable = ``;
    productData.forEach(element => {
      productTable += `<tr>
                                <td style="display: none;">
                                    <input name="product_uuid" value="${element.product_uuid}" type="text" hidden>
                                    <input name="variant_uuid" value="${element.variant_uuid}" type="text" hidden>
                                </td>
                                <td>
                                    <div style="width: auto;" class="product-name">
                                            ${element.img ? `<img src="${element.img}" alt="Image" class="size-50px img-fit"
                                                        onerror="this.onerror=null; this.outerHTML=\`<svg width='60' height='60' viewBox='0 0 42 42' fill='none' xmlns='http://www.w3.org/2000/svg'><rect x='1' y='1' width='40' height='40' rx='4' fill='white'/><rect x='1' y='1' width='40' height='40' rx='4' stroke='#232323' stroke-opacity='0.1'/><path d='M26.8333 13.5H15.1667C14.2462 13.5 13.5 14.2462 13.5 15.1667V26.8333C13.5 27.7538 14.2462 28.5 15.1667 28.5H26.8333C27.7538 28.5 28.5 27.7538 28.5 26.8333V15.1667C28.5 14.2462 27.7538 13.5 26.8333 13.5Z' stroke='#8D8D8D' stroke-linecap='round' stroke-linejoin='round'/><path d='M18.0834 19.3334C18.7737 19.3334 19.3334 18.7737 19.3334 18.0834C19.3334 17.393 18.7737 16.8334 18.0834 16.8334C17.393 16.8334 16.8334 17.393 16.8334 18.0834C16.8334 18.7737 17.393 19.3334 18.0834 19.3334Z' stroke='#8D8D8D' stroke-linecap='round' stroke-linejoin='round'/><path d='M28.5 23.5L24.3333 19.3334L15.1666 28.5' stroke='#8D8D8D' stroke-linecap='round' stroke-linejoin='round'/></svg>\`;">`
          : `<svg width='60' height='60' viewBox='0 0 42 42' fill='none' xmlns='http://www.w3.org/2000/svg'><rect x='1' y='1' width='40' height='40' rx='4' fill='white'/><rect x='1' y='1' width='40' height='40' rx='4' stroke='#232323' stroke-opacity='0.1'/><path d='M26.8333 13.5H15.1667C14.2462 13.5 13.5 14.2462 13.5 15.1667V26.8333C13.5 27.7538 14.2462 28.5 15.1667 28.5H26.8333C27.7538 28.5 28.5 27.7538 28.5 26.8333V15.1667C28.5 14.2462 27.7538 13.5 26.8333 13.5Z' stroke='#8D8D8D' stroke-linecap='round' stroke-linejoin='round'/><path d='M18.0834 19.3334C18.7737 19.3334 19.3334 18.7737 19.3334 18.0834C19.3334 17.393 18.7737 16.8334 18.0834 16.8334C17.393 16.8334 16.8334 17.393 16.8334 18.0834C16.8334 18.7737 17.393 19.3334 18.0834 19.3334Z' stroke='#8D8D8D' stroke-linecap='round' stroke-linejoin='round'/><path d='M28.5 23.5L24.3333 19.3334L15.1666 28.5' stroke='#8D8D8D' stroke-linecap='round' stroke-linejoin='round'/></svg>`}
                                        <div>
                                            <span>${element.name}</span>
                                            ${element.varaint ? `<span class="varient-text">${element.varaint}</span>` : ''}
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="sku-input">
                                        <input type="text" name="sku" class="form-control">
                                    </div>
                                </td>
                                <td>
                                    <div class="sku-input">
                                        <input type="number" name="input-quantity" oninput="calculatePOS(this)" value="${element.qty}" class="form-control custom-qty">
                                    </div>
                                </td>
                                <td>
                                    <div class="sku-input">
                                        <input oninput="calculatePOS(this)" style="padding-left: 30px !important;" type="number" placeholder="0.00" class="form-control custom-cost">
                                        <div class="rupees-text">Rs</div>
                                    </div>
                                </td>
                                <td>
                                    <div class="sku-input">
                                        <input oninput="calculatePOS(this)" min="0" value="0" style="padding-right: 30px !important;" type="number" class="form-control custom-tax">
                                        <div style="right: 10px; left: auto;" class="rupees-text">%</div>
                                    </div>
                                </td>
                                <td>
                                    <span class="create-tab-text custom-total">Rs 0.00</span>
                                    <input type="hidden" class="item-total-amount" >
                                </td>
                                <td>
                                    <a class="close" href="">
                                        <svg width="20" height="20" xmlns="http://www.w3.org/2000/svg"
                                            viewBox="0 0 16 16">
                                            <path
                                                d="M10.72 11.78a.75.75 0 1 0 1.06-1.06l-2.72-2.72 2.72-2.72a.75.75 0 0 0-1.06-1.06l-2.72 2.72-2.72 2.72a.75.75 0 1 0 1.06 1.06l2.72-2.72z"
                                                fill="#ed2121"></path>
                                        </svg>
                                    </a>
                                </td>
                            </tr>`;
    });
    let productCount = productData.length + productArray.length;
    $("#variantOrdernumber").html(productCount);
    $("#product_table_one").removeClass("d-none");
    $("#product_table_one tbody").append(productTable);

    $(document).on("click", ".close", function (e) {
      e.preventDefault();

      let row = $(this).closest("tr");

      // Get product_id and variant_id from hidden inputs
      let productUuid = row.find("input[name=product_uuid]").val();
      let variantUuid = row.find("input[name=variant_uuid]").val();

      // Uncheck the matching child checkbox
      $(`.table-row.child .product-name[data-product-uuid='${productUuid}'][data-variant-uuid='${variantUuid}']`)
        .closest(".table-row")
        .find("input[type='checkbox'][name='product_check']")
        .prop("checked", false);

      // Check if any other child with the same productUuid is still checked
      let anyChildChecked = $(`.table-row.child .product-name[data-product-uuid="${productUuid}"]`)
        .closest(".table-row")
        .find("input[type='checkbox']:checked")
        .length > 0;

      // If no child is checked, also uncheck the parent
      if (!anyChildChecked) {
        // Uncheck the parent if any child is not checked
        let anyChildNotChecked = $(`.table-row.child .product-name[data-product-uuid='${productUuid}']`)
          .closest(".table-row")
          .find("input[type='checkbox'][name='product_check']:not(:checked)").length > 0;

        if (anyChildNotChecked) {
          $(`.table-row.parent .product-name[data-product-uuid='${productUuid}']`)
            .closest(".table-row")
            .find("input[type='checkbox'][name='product_check']")
            .prop("checked", false);
        }
      }

      // Optional: remove the row from the cart/list if needed
      row.remove();

      // Optional: if table is empty, hide it
      if ($("#product_table_one tbody tr").length === 0) {
        $("#product_table_one").addClass("d-none");
      }
      calculatePOS();
    });
  });

  $("#dataSave").on("click", function () {
    let supplierUUID = $("#Selectsupplier").attr("data-uuid");
    let destinationUUID = $("#Selectlocation").attr("data-uuid");
    let paymentTerms = $("#payment_terms").val();
    let supplierCurrency = $("#supplier_currency").val();
    let estimatedArrival = $("input[name='estimated-arrival']").val();
    let shippingCarrier = $("#shipping_carrier").val();
    let trackingNumber = $("input[name='tracking-number']").val();
    let referenceNumber = $("input[name='reference-number']").val();
    let noteSupplier = $("textarea[name='note-supplier']").val();
    let tag = $("input[name='tag']").val();
    // let taxIncluded = $("#tax-included").text();
    let taxIncluded = $("#tax-included-input").val();
    let poSubtotal = $("#po-subtotal").text();
    let itemTotal = $("#item-total").text();
    let shippingFee = $("#shipping-fee").text();
    let grandTotal = $("#grand-total-input").val();


    $("#Table_View tbody tr").each(function (index) {
      $(this).find("input[name='input-quantity']").val()
      $(this).find("input[name='input-quantity']").val()
    });

    let submitData = {
      "supplier_id": supplierUUID,
      "warehouse_id": destinationUUID,
      "payment_term_id": paymentTerms,
      "supplier_currency_id": supplierCurrency,
      "ship_date": estimatedArrival,
      "ship_carrier_id": shippingCarrier,
      "tracking_number": trackingNumber,
      "reference_number": referenceNumber,
      "note_to_supplier": noteSupplier,
      "tags": tag,
      "status": "draft",
      "total_shipping": shippingFee,
      "total_tax": taxIncluded,
      "total_amount": grandTotal,
    }

    let tableRows = document.querySelectorAll("#Table_View tbody tr"); // Apne table ka ID yahan daalain
    let rowDataArray = [];

    tableRows.forEach(row => {
      let rowData = {
        product_id: row.querySelector("input[name='product_uuid']").value,
        variant_id: row.querySelector("input[name='variant_uuid']").value,
        name: row.querySelector(".product-name span").textContent.trim(),
        variant: row.querySelector(".product-name .varient-text").textContent.trim(),
        sku: row.querySelector("input[name='sku']").value,
        quantity: row.querySelector("input[name='input-quantity']").value,
        unit_price: row.querySelector(".custom-cost").value,
        tax: row.querySelector(".custom-tax").value,
        // total_amount: row.querySelector(".custom-total").textContent.trim(),
        total_amount: row.querySelector(".item-total-amount").value
      };
      // console.log(row.querySelector(".item-total-amount").val());


      rowDataArray.push(rowData);
    });
    submitData["items"] = rowDataArray
    console.log(submitData); // Yeh array sari row ki values dega

    $.ajax({
      url: ApiPlugin + '/ecommerce/purchase-order/store', // Aapka backend API ya PHP file ka path
      type: "POST",          // POST request use kar rahe hain
      data: JSON.stringify(submitData), // JSON mein convert karna
      contentType: "application/json",
      dataType: "json",
      headers: { "Authorization": "Bearer " + strkey, 'menu-uuid': menu_id },
      beforeSend: function (request) {
        request.setRequestHeader("Authorization", 'Bearer ' + strkey);
      },
      success: function (response) {
        if (response.status_code == 200) {
          let title = response.message;
          showToast(title, 'Success', 'success', "?P=purchase-order&M=" + menu_id);
        }
      },
      error: function (xhr, status, error) {
        console.error("AJAX Error: ", error);
      }
    });


  })


});


let subtotalPO = 0;
let itemTotal = 0;
let taxIncluded = 0;
const shippingFee = 100; // Fixed Shipping Fee

function calculatePOS(e) {
  let getParentsRow = $(e).closest('tr');

  let customQTY = parseFloat(getParentsRow.find('.custom-qty').val()) || 0;
  let customCOST = parseFloat(getParentsRow.find('.custom-cost').val()) || 0;
  let customTAX = parseFloat(getParentsRow.find('.custom-tax').val()) || 0;

  // **Calculate Tax Per Row**
  let getTax = (customQTY * customCOST) * (customTAX / 100);
  let finalCal = Math.round((customQTY * customCOST) + getTax);
  finalCal = finalCal > 0 ? finalCal : 0.00;

  // **Update Individual Row Total**
  let totalField = getParentsRow.find('.custom-total');
  let totalFieldInput = getParentsRow.find('.item-total-amount');
  if (totalField.is('input')) {
    totalField.val(finalCal);
  } else {
    totalFieldInput.val(finalCal);
    totalField.text("Rs " + finalCal);
  }

  // **Reset Totals Before Summing**
  subtotalPO = 0;
  itemTotal = 0;
  taxIncluded = 0;

  // **Loop Through All Rows to Sum Values**
  $(".custom-total").each(function () {
    let rowTotal = parseFloat($(this).text().replace("Rs ", "")) || 0;
    subtotalPO += rowTotal;
  });

  $(".custom-qty").each(function () {
    let qty = parseFloat($(this).val()) || 0;
    itemTotal += qty;
  });

  $(".custom-qty").each(function () {
    let row = $(this).closest('tr');
    let qty = parseFloat(row.find('.custom-qty').val()) || 0;
    let cost = parseFloat(row.find('.custom-cost').val()) || 0;
    let tax = parseFloat(row.find('.custom-tax').val()) || 0;

    let rowTax = (qty * cost) * (tax / 100);
    taxIncluded += rowTax;
  });

  // **Calculate Grand Total (Subtotal + Shipping)**
  let grandTotal = subtotalPO + shippingFee;

  // **Update the UI**
  $("#po-subtotal").text("Rs " + subtotalPO);
  $("#item-total").text(itemTotal);
  $("#tax-included").text("Rs " + Math.round(taxIncluded));
  $("#tax-included-input").val(taxIncluded);
  $("#shipping-fee").text(shippingFee);
  $("#grand-total-input").val(grandTotal);
  $("#grand-total").text("Rs " + grandTotal);
}

function addSelectInput() {
  var html =
    `<div class="row align-items-center mb-4">
            <div class="col-5">
                <div class="form-group form-input">
                    <select name="" class="form-control selectpicker select2" id="">
                        <option value="" selected>Select supplier</option>
                        <option value="">Cash and James Co</option>
                        <option value="">Dummy Text 1</option>
                        <option value="">Dummy Text 2</option>
                    </select>
                </div>
            </div>
            <div class="col-5">
                <div class="form-group form-input">
                    <input type="text" class="form-control" placeholder="Rs 0.00" >
                </div>
            </div>
            <div class="col-2">
                <div class="">
                    <a onclick="this.parentNode.parentNode.parentNode.remove()" >
                        <svg width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16"><path d="M10.72 11.78a.75.75 0 1 0 1.06-1.06l-2.72-2.72 2.72-2.72a.75.75 0 0 0-1.06-1.06l-2.72 2.72-2.72-2.72a.75.75 0 0 0-1.06 1.06l2.72 2.72-2.72 2.72a.75.75 0 1 0 1.06 1.06l2.72-2.72z" fill="#9C845E" ></path></svg>
                    </a>
                </div>
            </div>
        </div>
`;
  document.getElementById('add-adjustment').insertAdjacentHTML("afterbegin", html);

  const selectPicker = $('.selectpicker');
  if (selectPicker.length) {
    selectPicker.selectpicker();
    handleBootstrapSelectEvents();
  }
}

function supplier_currency() {
  return new Promise((resolve, reject) => {
    $.ajax({
      url: ApiPlugin + '/ecommerce/get_active_currencies',
      type: "GET",
      contentType: "application/json",
      dataType: "json",
      beforeSend: function (request) {
        request.setRequestHeader("Authorization", 'Bearer ' + strkey);
      },
      success: function (response) {
        if (response.status_code === 200) {
          const parentDropdownCurrency = $('#supplier_currency');
          parentDropdownCurrency.empty(); // Clear existing options
          response.data.forEach(currency => {
            // console.log(response.data);
            let spaces = '-'.repeat(currency.level);
            parentDropdownCurrency.append(
              `<option value="${currency.uuid}">${spaces} ${currency.name}</option>`
            );
          });
          // parentDropdownCurrency.selectpicker("refresh");
          resolve(); // Mark as completed
        } else {
          reject(response);
        }
      },
      error: function (xhr, status, error) {
        console.error('Error fetching categories:', status, error);
        reject(error);
      }
    });
  });
}

function payment_terms() {
  return new Promise((resolve, reject) => {
    $.ajax({
      url: ApiPlugin + '/ecommerce/get_active_paymentterm',
      type: "GET",
      contentType: "application/json",
      dataType: "json",
      beforeSend: function (request) {
        request.setRequestHeader("Authorization", 'Bearer ' + strkey);
      },
      success: function (response) {
        console.log("payment-terms");

        if (response.status_code === 200) {
          const parentDropdownPayment = $('#payment_terms');
          parentDropdownPayment.empty();

          parentDropdownPayment.append('<option value="" selected>Select payment (optional)</option>');

          response.data.forEach(payment => {
            let spaces = ''.repeat(payment.level);
            parentDropdownPayment.append(
              `<option value="${payment.uuid}">${spaces} ${payment.name}</option>`
            );
          });

          // parentDropdownPayment.selectpicker("refresh");
          resolve(); // Mark as completed
        } else {
          console.error('Unexpected response:', response);
          reject(response);
        }
      },
      error: function (xhr, status, error) {
        console.error('Error fetching payment terms:', status, error);
        reject(error);
      }
    });
  });
}


function shipping_carrier() {
  return new Promise((resolve, reject) => {
    $.ajax({
      url: ApiPlugin + '/ecommerce/get_active_carriers',
      type: "GET",
      contentType: "application/json",
      dataType: "json",
      beforeSend: function (request) {
        request.setRequestHeader("Authorization", 'Bearer ' + strkey);
      },
      success: function (response) {
        if (response.status_code === 200) {
          const parentDropdownCarrier = $('#shipping_carrier');
          parentDropdownCarrier.empty(); // Clear existing options

          // Default option add karna
          parentDropdownCarrier.append('<option value="" selected>Select shipping carrier</option>');

          response.data.forEach(carrier => {
            let spaces = ''.repeat(carrier.level);
            parentDropdownCarrier.append(
              `<option value="${carrier.uuid}">${spaces} ${carrier.name}</option>`
            );
          });
          // parentDropdownCarrier.selectpicker("refresh");
          resolve(); // Mark as completed
        } else {
          console.error('Unexpected response:', response);
          reject(response);
        }
      },
      error: function (xhr, status, error) {
        reject(error);
        console.error('Error fetching categories:', status, error);
      }
    });
  });
}

function select_supplier() {
  $.ajax({
    url: ApiPlugin + '/ecommerce/get_active_supplier', // API end point
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

      let htmlLi = ``;
      for (const key in data) {
        let item = data[key];
        htmlLi += ` <li>
                                <label class="custom-checkbox">
                                    <input style="display:none;" name="market-radio" data-uuid="${item.uuid}" value="${item.company}" type="checkbox">
                                    ${item.company}
                                </label>
                            </li>`

      }
      $("#select_supplier").append(htmlLi);
    }
  });
}

function select_location() {
  $.ajax({
    url: ApiPlugin + '/ecommerce/warehouse/get_active_warehouse_locations', // API end point
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

      let htmlLi = ``;
      for (const key in data) {
        let item = data[key];
        htmlLi += ` <li>
                                <label class="custom-checkbox">
                                    <input style="display:none;" name="market-radio" data-uuid="${item.uuid}" value="${item.location_name}" type="checkbox">
                                    ${item.location_name}
                                </label>
                            </li>`

      }
      $("#select_location").append(htmlLi);
    }
  });
}

$("#ProductItem").on("click", function () {
  // table_product();
})

function table_product(callback) {
  return new Promise((resolve, reject) => {
    $.ajax({
      url: ApiPlugin + '/ecommerce/product/get_active_products', // API endpoint
      type: "GET",
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

        let placeholderSVG = `<svg width="60" height="60" viewBox="0 0 42 42" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <rect x="1" y="1" width="40" height="40" rx="4" fill="white"/>
                                        <rect x="1" y="1" width="40" height="40" rx="4" stroke="#232323" stroke-opacity="0.1"/>
                                        <path d="M26.8333 13.5H15.1667C14.2462 13.5 13.5 14.2462 13.5 15.1667V26.8333C13.5 27.7538 14.2462 28.5 15.1667 28.5H26.8333C27.7538 28.5 28.5 27.7538 28.5 26.8333V15.1667C28.5 14.2462 27.7538 13.5 26.8333 13.5Z" stroke="#8D8D8D" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M18.0834 19.3334C18.7737 19.3334 19.3334 18.7737 19.3334 18.0834C19.3334 17.393 18.7737 16.8334 18.0834 16.8334C17.393 16.8334 16.8334 17.393 16.8334 18.0834C16.8334 18.7737 17.393 19.3334 18.0834 19.3334Z" stroke="#8D8D8D" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M28.5 23.5L24.3333 19.3334L15.1666 28.5" stroke="#8D8D8D" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
    `;

        let htmlLi = ``;
        for (const key in data) {
          let item = data[key];

          let parentImage = item.thumbnail_img ? `<img src="${AssetsPath + item.thumbnail_img}" alt="Image" class="size-50px img-fit">` : placeholderSVG;

          htmlLi += ` <div class="table-first-item product-${item.uuid}">
                                    <div class="table-row parent">
                                        <div class="custom-checkbox-main form-group d-inline-block">
                                            <label class="custom-checkbox">
                                                <input type="checkbox" name="product_check">
                                                <span class="aiz-square-check"></span>
                                            </label>
                                        </div>
                                        <div class="product-name" data-product-uuid="${item.uuid}">
                                            ${parentImage}
                                            <span>${item.name}</span>
                                        </div>
                                        <div class="total-name">
                                            <span>${item.current_stock}</span>
                                        </div>
                                    </div>`;

          if (item.product_stocks && item.product_stocks.length > 0) {
            htmlLi += item.product_stocks.map((stockItem) => {
              let childImage = stockItem.image ? `<img src="${AssetsPath + stockItem.image}" alt="Image" class="size-50px img-fit">` : placeholderSVG;

              return `
                            <div class="table-row child left-side-item productStock-${stockItem.uuid}">
                                <div class="custom-checkbox-main form-group d-inline-block">
                                    <label class="custom-checkbox">
                                        <input type="checkbox" name="product_check">
                                        <span class="aiz-square-check"></span>
                                    </label>
                                </div>
                                <div class="product-name" data-product-uuid="${item.uuid}" data-variant-uuid="${stockItem.uuid}">
                                    ${childImage}
                                    <span>${stockItem.sku}</span>
                                </div>
                                <div class="total-name">
                                    <span>${stockItem.qty}</span>
                                </div>
                            </div>`;
            }).join('');
          }

          htmlLi += `</div>`;
        }

        $("#table_product").append(htmlLi);
        if (typeof callback === "function") {
          callback(response.data);
        }
        resolve(); // Mark as completed
      },
      error: function (xhr, status, error) {
        console.error('Error fetching products:', status, error);
        reject(error);
      }
    });
  });
}

// intlTelInput JS
let iti
var input = document.querySelector("#phone");
if (input) {
  iti = window.intlTelInput(input, {
    initialCountry: "ae",
    preferredCountries: ['ae'],
    autoPlaceholder: "polite",
    showSelectedDialCode: true,
    utilsScript: "assets/plugins/intel-input/utils.js",
    hiddenInput: () => ({ phone: "full_phone" })
  });
  input.addEventListener("blur", function () {
    if (input.value.trim()) {
      if (iti.isValidNumber()) {
        input.parentElement.parentElement.classList.remove("error");
        input.parentElement.parentElement.querySelector(".error-txt").innerHTML = "";
      } else {
        input.parentElement.parentElement.classList.add("error");
        input.parentElement.parentElement.querySelector(".error-txt").innerHTML = "Invalid Number";
      }
    }
    if (input.value === "") {
      input.parentElement.parentElement.classList.remove("error");
      input.parentElement.parentElement.querySelector(".error-txt").innerHTML = "";
    }
  });
}
// intlTelInput JS End
// alert();
document.getElementById("SupplierForm").addEventListener("submit", function (e) {
  e.preventDefault();
  // alert();
  const company = this.querySelector("[name='company']");

  let status = true;

  if (company.value == "") {
    company.parentElement.classList.add("error");
    company.parentElement.querySelector(".error-txt").innerHTML = "Company can't be blank";
    status = false;
  }

  if (status) {
    alert("Form Submited");

  }
});

document.querySelectorAll("#SupplierForm input").forEach(function (input) {
  input.addEventListener("input", function () {
    input.parentElement.classList.remove("error");
    input.parentElement.parentElement.querySelector(".error-txt").innerHTML = "";
    input.parentElement.querySelector(".error-txt").innerHTML = "";
  });
});


$(".create-new-supplier").on("click", function () {
  country_select();
})

function country_select() {
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
        const parentDropdownCountry = $('#country_select');
        parentDropdownCountry.empty(); // Clear existing options

        // Default option add karna
        parentDropdownCountry.append('');

        response.data.forEach(country => {
          let spaces = ''.repeat(country.level);
          parentDropdownCountry.append(
            `<option value="${country.uuid}">${spaces} ${country.name}</option>`
          );
        });

        parentDropdownCountry.selectpicker("refresh");
      } else {
        console.error('Unexpected response:', response);
      }
    },
    error: function (xhr, status, error) {
      console.error('Error fetching categories:', status, error);
    }
  });
}

