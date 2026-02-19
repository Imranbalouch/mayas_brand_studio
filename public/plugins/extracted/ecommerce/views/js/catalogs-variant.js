document.title = "Dashboard | Catalogs Variant";


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

// $('.catalogs-variant').on('click','.error',function(){
$('.arrow').on('click',function(){  
   $(this).parents('tr').next().toggle();
});