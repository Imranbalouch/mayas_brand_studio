document.title = "Dashboard | Create Gift Card";
$(document).ready(function () {
  const randomCode = generateGiftCardCode();
  $('input[name="code"]').val(randomCode); 
  // setting date to default today
  const today = new Date().toISOString().split("T")[0];
  $("#dateInput").attr("min", today);

  // Expires Input
  $(".expires-input").hide();
  $("input[name='expiration-date']").change(function () {
    if ($(this).val() == "expiration-date-set") {
      $(".expires-input").show();
    } else {
      $(".expires-input").hide();
    }
  });

    // intlTelInput JS
    let iti
    var input = document.querySelector("#phone");
    if (input) {
      iti = window.intlTelInput(input, {
        initialCountry: "ae",
        preferredCountries: ['ae'],
        autoPlaceholder: "polite",
        showSelectedDialCode: true,
        utilsScript: "./assets/plugins/intel-input/utils.js",
        //hiddenInput: () => ({ phone: "phone" })
      });
      input.addEventListener("input", function () {
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

    //Note length count
    $("#noteModalInput").on("input", function () {
      let currentLength = $(this).val().length;
      // Update the #current span within the same modal
      $(this).closest(".modal").find("#current").text(currentLength);
    });
});

// Generate Code 

function generateGiftCardCode(length = 16) {
  const characters = 'abcdefghijklmnopqrstuvwxyz0123456789';
  let result = '';
  
  for (let i = 0; i < length; i++) {
    const randomIndex = Math.floor(Math.random() * characters.length);
    result += characters.charAt(randomIndex);
  }
  
  return result;
}


// GET CUSTOMER DATA LIST

function get_customer_list() {
  $.ajax({
    url: ApiPlugin + '/ecommerce/customer',
    type: "GET",
    contentType: "application/json",
    dataType: "json",
    headers: {"Authorization": "Bearer "+strkey, 'menu-uuid': menu_id},
    beforeSend: function (request) {
      request.setRequestHeader("Authorization", 'Bearer ' + strkey);
    },
    success: function (response) {
      if (response.status_code === 200) {
        
        const parentDropdown = $('.customer-list ul');
        parentDropdown.empty();
        response.data.forEach(customer => {
          parentDropdown.append(
            `<li>
              <a href="javascript:;" value="${customer.uuid}" getName="${customer.name}">${customer.email}</a>
            </li>`
          );
        });

        // CUSTOMER SET DATA

        $('.customer-list ul li').on('click', function() {
          var getCustomerId = $(this).find('a').attr('value');
          var getCustomerName = $(this).find('a').attr('getName');
          var getCustomerEmail = $(this).find('a').text();
          $('#getCustomerId').val(getCustomerId);
          $('#getCustomerName').val(getCustomerName);
          $('#getCustomerEmail').val(getCustomerEmail);
          $('.selected-customer a').text(getCustomerEmail);
          $('.customer-dropdown').hide();
          $('.selected-customer').show();
        });

        $('.selected-customer .btn').on('click', function(e) {
          e.preventDefault();
          $('.selected-customer').hide();
          $('.customer-dropdown').show();
        })

      } 
      
      else {
        console.error('Unexpected response:', response);
      }
    },
    error: function (xhr, status, error) {
      console.error('Error fetching categories:', status, error);
    }
  });
}

$(document).ready(function () {
  get_customer_list();
});

// NOTES BUTTON CLICK

$('#noteData').on('click',function(){
  let note = $("textarea[name='dummynote']").val();
  console.log(note);
  $('.description p').text(note);
  $('#note').val(note);
  // formData.append('note', note);
});


// SUBMIT BUTTON

$("#addgiftCard").on("submit", function (e) {
  let formData = new FormData(this);
  e.preventDefault();

  let status = true;




  if ($('.custom-radio input:checked').length) {
    let getValueDate = $('.custom-radio input:checked').val();
    if(getValueDate == 'expiration-date-set'){
      let expiry_date = $('#dateInput').val();
      formData.append('expiry_date', expiry_date);
    }
    else{
      formData.append('expiry_date', '')
    }
  }
  

    
  let giftcardValue = $('input[name="code"]').val().replace(/\s/g, '');

  if (giftcardValue === "") {
    $('input[name="code"]').parent().addClass('error');
    $('input[name="code"]').parent().find('.error-txt').html("Title can’t be blank");
    $('.error-txt').show();
    window.scrollTo(0, 0);
    status = false;
  }
  
  if (giftcardValue.length < 8) {
    $('input[name="code"]').parent().addClass('error');
    $('input[name="code"]').parent().find('.error-txt').html("Code must be at least 8 characters long");
    $('.error-txt').show();
    window.scrollTo(0, 0);
    status = false;
  }
  
  $('input[name="code"]').on("input", function() {
    $(this).parent().find(".error-txt").hide();
    if ($(this).parent().hasClass('error')) {
      $(this).parent().removeClass('error');
    }
  });

  let getcode = $('input[name="code"]').val()
  let InitialValue = $('input[name="value"]').val()
  if(!InitialValue){
    showToast("Value can’t be blank", 'Error', 'error');
    return;
  }
  $('#giftcard').val(getcode);
  var lastFour = getcode.slice(-4);
  $('#giftcard').val(lastFour);
  formData.append('giftcard', lastFour);


  let customer_id = $("#getCustomerId").val();
  formData.append('customer_id', customer_id);

  formData.append('status', 'active');

  // let customer_name = $("#getCustomerName").val();
  // formData.append('customer_name', customer_name);

  // let customer_email = $("#getCustomerEmail").val();
  // formData.append('customer_email', customer_email);
  

  // for (var pair of formData.entries()) {
  //   console.log(pair[0] + ': ' + pair[1]);
  // }
  if(status){
  $.ajax({
    url: ApiPlugin + "/ecommerce/giftcard/add_giftcard", // updated endpoint
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
        showToast(title, 'Success', 'success', "?P=giftcard-listing&M="+menu_id);
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


// ADD CUSTOMER

$("#createCustomer").on("submit", function (e) {
  let formData = new FormData(this);
  e.preventDefault();

  let status = true;

  if(status){
  

  $.ajax({
    url: ApiPlugin + "/ecommerce/customer/store",
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
        showToast(title, 'Success', 'success');
        setTimeout(() => {
          location.reload();
        }, 1000);
      }
    },
    error: function (xhr, status, err) {
      imgload.hide();

      let errorMessage = 'An error occurred';

      if (xhr.status === 422 && xhr.responseJSON.errors) {
          const errors = xhr.responseJSON.errors;
          errorMessage = Object.values(errors)[0][0];
      }

      showToast(errorMessage, 'Error', 'error');
    }
  });
  }

});


// $(".input-focus").on("focus", function(){
//   $(this).select();
// });
