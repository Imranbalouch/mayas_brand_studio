document.title = "Dashboard | Edit Gift Card";
$(document).ready(function () {

  const today = new Date().toISOString().split('T')[0]; // Format: YYYY-MM-DD
  $('#dateInput').attr('min', today);
  // Expires Input
  $(".expires-input").hide();
  $("input[name='expiration-date']").change(function () {
    if ($(this).val() == "expiration-date-set") {
      $(".expires-input").show();
    } else {
      $(".expires-input").hide();
    }
  });
  $('#add-note').on('show.bs.modal', function () {
    const currentNote = $('.description p').text();
    $('#noteModalInput').val(currentNote);
    
    // Initialize character count
    const currentLength = currentNote.length;
    $('#current').text(currentLength);
  });
  
  // Character count update on input
  $('#noteModalInput').on('input', function() {
    const currentLength = $(this).val().length;
    $('#current').text(currentLength);
  });
  
  // Save note when Done is clicked
  $('#noteData').on('click', function() {
    const note = $('#noteModalInput').val();
    $('.description p').text(note);
    $('#note').val(note);
  });
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

$('#noteData').on('click',function(){
  let note = $("textarea[name='dummynote']").val();
  $('.description p').text(note);
  $('#note').val(note);
});

function toggleSubmitButton() {
  const status = $('#getStatus').val();
  const submitButton = $('.btn.btn-primary.waves-effect.waves-light');
  
  if (status === 'expired' || status === 'deactivate') {
    submitButton.hide();
  } else {
    submitButton.show();
  }
}

$(document).ready(function () {
  var urlParams = new URLSearchParams(window.location.search);
  var id = urlParams.get('id');
  // console.log(id);
  let customer_id = null;
  if (id) {
    $.ajax({
      url: ApiPlugin + "/ecommerce/giftcard/edit_giftcard/" + id,
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
          console.log("User",data.user);
          console.log("expiry_date",data.expiry_date);
          customer_id = data.customer_id;
          $("text").text(data.giftcard);
          $(".code-wrapper .codeText").text(data.giftcard);
          $(".expiration-title price").text(data.value);
          $(".recipient-title price").text(data.value);
          $(".description p").text(data.note);
          $("#getStatus").val(data.status);
          $("#getStatusText").text(data.status);
          if (data.user && data.user.first_name && data.user.last_name) {
            $("#created-by-name").text(`${data.user.first_name} ${data.user.last_name}`);
          } else {
            $("#created-by-name").text("Unknown"); 
          }
          if (data.expiry_date !== null && data.expiry_date !== '') {
            $("input[name='expiration-date'][value='expiration-date-set']").prop('checked',true);
            $('.expires-input').show();
            let expiryDate = data.expiry_date.split(' ')[0];
            $('#dateInput').val(expiryDate);
            $('.expiryData').text(expiryDate);

            // Add this status check when loading
            let today = new Date();
            today.setHours(0, 0, 0, 0);
            let expiry = new Date(expiryDate);
            expiry.setHours(0, 0, 0, 0);

            if (expiry.getTime() === today.getTime()) {
              $('#getStatus').val('expiring');
              $('#getStatusText').addClass('expiring');
              $('#getStatusText').text('Expiring');
            } else if (expiry < today) {
              $('#getStatus').val('expired');
              $('#getStatusText').addClass('expired');
              $('#getStatusText').text('Expired');
            } else {
              $('#getStatus').val('active');
              $('#getStatusText').removeClass('expired expiring');
              $('#getStatusText').text('Active');
            }
              toggleSubmitButton();
          }
          $("#note").val(data.note);
          
          if(data.customer_id !== null ){            
            $('.selected-customer').show();
            $('.selected-customer button').hide();
            console.log('email hai');

          }
          else{            
            $('.dropdown').show();
            $('.selected-customer').hide();
            $(".selected-customer a").text(data.customer_email);
        }
        let getStatusGiftCard = $("#getStatus").val();
        if (getStatusGiftCard == 'deactivate') {
          $('#edit-expiration').remove();
          $("#getStatus").val("deactivate");
          $("#getStatusText").addClass("deactivate");
          $("#getStatusGiftCard").css({
            'pointer-events': 'none',
            'opacity': '0.5'
          });
          $("#getStatusText").text("Deactivate");
          toggleSubmitButton();
      }
       let marketArr = response.data.market_id || [];
        $("#marketNames").empty();
        if (marketArr.length > 0) {
          $("#marketNamesBox").removeClass("d-none");
          marketArr.forEach(uuid => {
            // Find the market name from all_active_markets
            let market = all_active_markets.find(m => m.uuid === uuid);
            if (market) {
              $("#marketNames").append(`
                <div class="mb-3 fv-row"><span class="circleBefore active">${market.market_name}</span></div>
              `);
            }
          });
        } else {
          $("#marketNames").html('<div class="mb-3 fv-row"><span class="circleBefore">Not included in any markets</span></div>');
        }

        // Check the corresponding checkboxes in the manage markets modal
        marketArr.forEach(uuid => {
          const $checkbox = $(`input[type="checkbox"][value="${uuid}"]`);
          if ($checkbox.length) {
            $checkbox.prop('checked', true);
            $checkbox.trigger('change');
            $('#market_id').append(`<option value="${uuid}" selected>${uuid}</option>`);
          }
        });
      const timelineList = $('.timeline-list');
        timelineList.empty(); 

        if (data.giftcard_timeline && data.giftcard_timeline.length > 0) {
          data.giftcard_timeline.forEach(entry => {
            const isoDate = entry.created_at;
            const date = new Date(isoDate);
            const day = String(date.getDate()).padStart(2, '0');
            const month = date.toLocaleString('default', { month: 'short' });
            const year = date.getFullYear();
            const formattedDate = `${day}-${month}-${year}`;

            timelineList.append(`
              <li>
                <span class="message">${entry.message}</span>
                <span class="time" style="float: inline-end;">${formattedDate}</span>
              </li>
            `);
          });
        } else {
          timelineList.append('<li>No timeline entries available.</li>');
        }
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

    // FOR CUSTOMER DATA SHOW
    
    $.ajax({
      url: ApiPlugin + "/ecommerce/customer",
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
          let customerName = '';
          let customerEmail = '';
          console.log(data);
          data.forEach(element => {
            if (element.uuid == customer_id) {
              customerName = element.name;              
              customerEmail = element.email;
              $(".customer_name").text(customerName);
              $(".selected-customer a").text(customerEmail);
              console.log("customerName",customerEmail);
              
            }
          });
          
          $("#getCustomerId").val(customer_id);          
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



// EDIT GIFT CARD

$("#dateTimeEdit").on("submit", function (e) {
  let date = $('#dateInput').val();
  if ($('.custom-radio input:checked').length) {
    let getValueDate = $('.custom-radio input:checked').val();
    let getValueDateText = "Doesn't expire"
    
    if(getValueDate == 'expiration-date-set'){
      let expiry_date = $('#dateInput').val();
      $('#dateInputHide').val(expiry_date);
      $('.expiryData').text(expiry_date);

      // NEW CONDITION - FIXED DATE COMPARISON
      let today = new Date();
      today.setHours(0, 0, 0, 0); // Normalize today's date to midnight
      let expiryDate = new Date(expiry_date);
      expiryDate.setHours(0, 0, 0, 0); // Normalize expiry date to midnight

      if (expiryDate.getTime() === today.getTime()) {
          $('#getStatus').val('expiring');
          $('#getStatusText').addClass('expiring');
          $('#getStatusText').text('Expiring');
      } else if (expiryDate < today) {
          $('#getStatus').val('expired');
          $('#getStatusText').addClass('expired');
          $('#getStatusText').text('Expired');
      } else {
          $('#getStatus').val('active');
          $('#getStatusText').removeClass('expired expiring');
          $('#getStatusText').text('Active');
      }
    }
    else{
      $('#dateInputHide').val('');
      $('.expiryData').text(getValueDateText);      
      $('#getStatus').val('active');
      $('#getStatusText').removeClass('expired expiring');
      $('#getStatusText').text('Active');
    }
      toggleSubmitButton();
  }
  // $('#editgiftCard').submit();
})

$('#getStatusGiftCard').on('click',function(){
  let getStatusGiftCard = $("#getStatus").val();
  if(getStatusGiftCard == 'active'){
    $("#getStatus").val("deactivate");
    $("#getStatusText").addClass("deactivate");
    $("#getStatusGiftCard").css({
      'pointer-events': 'none',
      'opacity': '0.5'
    });
    $("#getStatusText").text("Deactivate");
  }
  else{
    getStatusGiftCard.val(getStatusGiftCard);
  }
  toggleSubmitButton();
  $('#editgiftCard').submit();
})

$("#editgiftCard").on("submit", function (e) {
  let formData = new FormData(this);
  e.preventDefault();

  let checkedStatus = true;

  if(checkedStatus){

  var urlParams = new URLSearchParams(window.location.search);
  var id = urlParams.get('id');  
  let expiry_date = $('#dateInputHide').val();
  let getStatusGiftCard = $("#getStatus").val();

  if (expiry_date != '') {
    formData.append('expiry_date', expiry_date);
  }else{
    formData.append('expiry_date', '')
  }
  formData.append('status', getStatusGiftCard);
  let customer_id = $("#getCustomerId").val();
  formData.append('customer_id', customer_id);


  $.ajax({
    url: ApiPlugin + "/ecommerce/giftcard/update_giftcard", // updated endpoint
    type: "POST",
    data: formData,
    contentType: false,
    processData: false,
    beforeSend: function (xhr) {
      xhr.setRequestHeader("Authorization", "Bearer " + strkey);
      xhr.setRequestHeader("menu-uuid", menu_id);
      xhr.setRequestHeader("uuid", id);
      imgload.show();
    },
    success: function (response) {
      imgload.hide();
      console.log('hussain',getStatusGiftCard)
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
          $('.customer_name').text(getCustomerName);
          $('.customer-dropdown').hide();
          $('.selected-customer').show();
          $('.customer_name').show();
        });

        $('.selected-customer .btn').on('click', function(e) {
          e.preventDefault();
          $('.selected-customer').hide();
          $('.customer_name').hide();
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

// ADD CUSTOMER

$("#createCustomer").on("submit", function (e) {
  let formData = new FormData(this);
  e.preventDefault();

  let checkedStatus = true;

  if(checkedStatus){
  

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
        setTimeout(function () {
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