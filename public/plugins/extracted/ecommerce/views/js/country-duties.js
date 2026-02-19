document.title = "Dashboard | Country Duties";
$(document).ready(function(){
  $(".market-country-card .head .form-check-input").on("change", function(){
    if($(this).prop("checked")) {
      $(this).parents(".market-country-card").find(".market-country-item .form-check-input").prop("checked", true);
    } else {
      $(this).parents(".market-country-card").find(".market-country-item .form-check-input").prop("checked", false);
    }
    $(this).parents(".market-country-card").find(".head .total").html($(this).parents(".market-country-card").find(".market-country-item .form-check-input:checked").length);
  });

  $(".market-country-card .market-country-item .form-check-input").on("change", function(){
    $(this).parents(".market-country-card").find(".head .total").html($(this).parents(".body").find(".form-check-input:checked").length);
    if($(this).parents(".market-country-card").find(".market-country-item .form-check-input:checked").length > 0) {
      $(this).parents(".market-country-card").find(".head .form-check-input").prop("checked", true);
    } else {
      $(this).parents(".market-country-card").find(".head .form-check-input").prop("checked", false);
    }
  });

  $(".custom-select-group .selected-btn").on("click", function(e){
    e.preventDefault();
    $(this).next().show();
  });
  $(".custom-select-group .custom-select-option").on("click", function (e) {
    e.preventDefault();
    $(this).siblings().removeClass("active");
    $(this).addClass("active");
    $(this).parents(".custom-select-group").find(".selected-btn input").val($(this).find(".name").html());
    $(this).parents(".custom-select-group").find(".selected-btn span").html($(this).find(".name").html());
    $(this).parent().hide();
  });
  $(document).on("click", function (e) {
    if (!$(e.target).closest(".custom-select-group").length) {
      $(".custom-select-dropdown").hide();
    }
  });

  $("#showTaxes").on("click", function(e){
    e.preventDefault();
    $(this).parent().addClass("d-none");
    $("#Taxes").removeClass("d-none");
  });

  $(".add-taxes .dropdown-toggle").on("click", function(){
    $("#showTaxes").parent().removeClass("d-none");
    $("#Taxes").addClass("d-none");
  });
});