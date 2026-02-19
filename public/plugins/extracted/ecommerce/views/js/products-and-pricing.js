document.title = "Dashboard | Products & Pricing";
$("#searchOpen").on("click", function(e){
    e.preventDefault();
    $(".pricing-table-filters .filter-search").removeClass("d-none");
    $(".selectDropdown").removeClass("d-none");
  });
  
  $("#searchClose").on("click", function(e){
    e.preventDefault();
    $(".pricing-table-filters .filter-search").addClass("d-none");
    $(".selectDropdown").addClass("d-none");
  });
  
  $(".custom-dropdown-main .droupdown-head").on("click", function (e) {
    e.preventDefault();
    $(".droupdown-body").not($(this).parent()).removeClass("show");
    $(this).parent().toggleClass("show");
    e.stopPropagation();
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
  
  $(".selectDropdown").on('change', '.select-dropdown .droupdown-body .custom-checkbox input[type=checkbox]', function (e) {
    const dropdown = $(this).closest(".select-dropdown");
    const checkedCheckboxes = dropdown.find(".droupdown-body input[type=checkbox]:checked");
    const dropdownHead = dropdown.find(".droupdown-head > span:not(.clear-drop):not(.arrow)");
    
    dropdownHead.empty();
  
    if (checkedCheckboxes.length > 0) {
      let selectedTexts = checkedCheckboxes.map((i, el) => `Status ${$(el).val()}`).get().join(", ");
      dropdownHead.text(selectedTexts);
    } else {
      dropdownHead.text("Status");
    }
  
    $(this).parents(".select-dropdown").find(".clear-drop").removeClass("d-none");
    $(this).parents(".select-dropdown").find(".arrow").addClass("d-none");
  });
  
  $(".selectDropdown").on('change', '.select-dropdown .droupdown-body .custom-radio input[type=radio]', function (e) {
    $(this).parents(".select-dropdown").find(".droupdown-head > span > span").html(`is ${$(this).val()}`);
    $(this).parents(".select-dropdown").find(".clear-drop").removeClass("d-none");
    $(this).parents(".select-dropdown").find(".arrow").addClass("d-none");
  });
  
  $(".selectDropdown").on('input', '.select-dropdown .droupdown-body input[type=text]', function (e) {
    $(this).parents(".select-dropdown").find(".droupdown-head > span > span").html(`is ${$(this).val()}`);
    $(this).parents(".select-dropdown").find(".clear-drop").removeClass("d-none");
    $(this).parents(".select-dropdown").find(".arrow").addClass("d-none");
  });
  
  $(".selectDropdown").on('click', '.select-dropdown.status .droupdown-body a', function (e) {
    e.preventDefault();
    $(this).parent().find("input[type=checkbox]").prop('checked', false);
    $(this).parent().find("input[type=radio]").prop('checked', false);
    $(this).parent().find("input[type=text]").val('');
    $(this).parents(".select-dropdown").find(".droupdown-head > span:not(.clear-drop):not(.arrow)").html("Status");
    $(this).parents(".select-dropdown").find(".clear-drop").addClass("d-none");
    $(this).parents(".select-dropdown").find(".arrow").removeClass("d-none");
  });
  
  $(".selectDropdown").on('click', '.select-dropdown.status .clear-drop', function (e) {
    e.preventDefault();
    $(this).parents(".select-dropdown").find(".droupdown-body input[type=checkbox]").prop('checked', false);
    $(this).parents(".select-dropdown").find(".droupdown-body input[type=radio]").prop('checked', false);
    $(this).parents(".select-dropdown").find(".droupdown-body input[type=text]").val("");
    $(this).parents(".select-dropdown").find(".droupdown-head > span:not(.clear-drop):not(.arrow)").html("Status");
    $(this).parents(".select-dropdown").find(".clear-drop").addClass("d-none");
    $(this).parents(".select-dropdown").find(".arrow").removeClass("d-none");
  });
  
  $(".selectDropdown").on('click', '.select-dropdown .droupdown-body a', function (e) {
    e.preventDefault();
    $(this).parent().find("input[type=checkbox]").prop('checked', false);
    $(this).parent().find("input[type=radio]").prop('checked', false);
    $(this).parent().find("input[type=text]").val('');
    $(this).parents(".select-dropdown").find(".droupdown-head > span > span").html("");
    $(this).parents(".select-dropdown").find(".clear-drop").addClass("d-none");
    $(this).parents(".select-dropdown").find(".arrow").removeClass("d-none");
  });
  
  $(".selectDropdown").on('click', '.select-dropdown .clear-drop', function (e) {
    e.preventDefault();
    $(this).parents(".select-dropdown").find(".droupdown-body input[type=checkbox]").prop('checked', false);
    $(this).parents(".select-dropdown").find(".droupdown-body input[type=radio]").prop('checked', false);
    $(this).parents(".select-dropdown").find(".droupdown-body input[type=text]").val("");
    $(this).parents(".select-dropdown").find(".droupdown-head > span > span").html("");
    $(this).parents(".select-dropdown").find(".clear-drop").addClass("d-none");
    $(this).parents(".select-dropdown").find(".arrow").removeClass("d-none");
  });
  
  $("#Table_View .select-all").on("change", function(){
    if($(this).prop("checked") === true) {
      $("#Table_View .thead-filter").removeClass("d-none");
      $("#Table_View .form-check-input:not(.select-all)").prop("checked", true);
    } else {
      $("#Table_View .thead-filter").addClass("d-none");
      $("#Table_View .form-check-input:not(.select-all)").prop("checked", false);
    }
    isAnyChecked();
  });
  
  $("#Table_View .form-check-input:not(.select-all)").on("change", function() {
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


  $('.add-quantity').on('click',function(){
    $('.add-quantity').hide();
    $('.volume-pricing-box').show();
    $('.delete-icon').hide();
  });

  $('.delete-btn').on('click',function(){
    $('.volume-pricing-box').hide();
    $('.add-quantity').show();
    $('.main-break-section .add-break:not(:first-child)').remove();
  })

  $('.break-btn').on('click',function(){
    let getLength = $('.add-break').length + 1;
    console.log(getLength);
    
    $('.main-break-section').append(`
      <div class="add-break">
        <div class="d-flex justify-content-between align-items-center gap-2 mt-4">
          <div>
            <div class="input start-box">
              <span class="currency-txt">Break <number></number></span>
              <input type="number" class="form-control" id="Price" required="" placeholder="">
            </div>
          </div>
          <div>
            <div class="input">
              <span class="currency-txt">Rs</span>
              <input type="number" class="form-control" id="Price" required="" placeholder="">
            </div>
          </div>
          <div class="delete-icon">
            <div>
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16"><path d="M9.5 6.25a.75.75 0 0 1 .75.75v4.25a.75.75 0 0 1-1.5 0v-4.25a.75.75 0 0 1 .75-.75"></path><path d="M7.25 7a.75.75 0 0 0-1.5 0v4.25a.75.75 0 0 0 1.5 0z"></path><path fill-rule="evenodd" d="M5.25 3.25a2.75 2.75 0 1 1 5.5 0h3a.75.75 0 0 1 0 1.5h-.75v5.45c0 1.68 0 2.52-.327 3.162a3 3 0 0 1-1.311 1.311c-.642.327-1.482.327-3.162.327h-.4c-1.68 0-2.52 0-3.162-.327a3 3 0 0 1-1.311-1.311c-.327-.642-.327-1.482-.327-3.162v-5.45h-.75a.75.75 0 0 1 0-1.5zm1.5 0a1.25 1.25 0 0 1 2.5 0zm-2.25 1.5h7v5.45c0 .865-.001 1.423-.036 1.848-.033.408-.09.559-.128.633a1.5 1.5 0 0 1-.655.655c-.074.038-.225.095-.633.128-.425.035-.983.036-1.848.036h-.4c-.865 0-1.423-.001-1.848-.036-.408-.033-.559-.09-.633-.128a1.5 1.5 0 0 1-.656-.655c-.037-.074-.094-.225-.127-.633-.035-.425-.036-.983-.036-1.848z"></path></svg>
            </div>
          </div>
        </div>
      </div>
      `);


    if(getLength > 1){
      $('.delete-icon').show();
    }
    if(getLength > 1){
      $('.delete-btn').text('Delete all')
    }
    else{
      $('.delete-btn').text('Delete break')
    }
    mainBreakSectionCount()
  });

  $('.main-break-section').on('click','.delete-icon',function(){
    $(this).parents('.add-break').remove();
    let getLength = $('.add-break').length;
    if(getLength == 1){
      $('.delete-icon').hide();
    }
    if(getLength > 1){
      $('.delete-btn').text('Delete all')
    }
    else{
      $('.delete-btn').text('Delete break')
    }
    mainBreakSectionCount()
  });


  function mainBreakSectionCount(){
    $('.main-break-section .add-break').each((i,e)=>{
      $(e).find('number').text(i+1)
    });
  }