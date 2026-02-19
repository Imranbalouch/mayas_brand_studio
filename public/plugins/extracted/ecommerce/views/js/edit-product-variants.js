let AllLocationVariantData = {};
let ProductVariationPreviousRecord = [];  
let ProductVariationRecord = [];  
let variation_data=[];
let tableIndex = 0;
let variantsEditData=[];
function editVarientCard(data='',edit='') {  
          variantsEditData=edit;
          // console.log(data);
        $(data).each(function(i, element){
          let html1 = `<div class="variants-option-card">
                        <div class="drag drag-card"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" width="16" height="16"><path d="M5.5 2.5a1 1 0 0 0-1 1v.5a1 1 0 0 0 1 1h.5a1 1 0 0 0 1-1v-.5a1 1 0 0 0-1-1z"></path><path d="M5.5 6.75a1 1 0 0 0-1 1v.5a1 1 0 0 0 1 1h.5a1 1 0 0 0 1-1v-.5a1 1 0 0 0-1-1z"></path><path d="M4.5 12a1 1 0 0 1 1-1h.5a1 1 0 0 1 1 1v.5a1 1 0 0 1-1 1h-.5a1 1 0 0 1-1-1z"></path><path d="M10 2.5a1 1 0 0 0-1 1v.5a1 1 0 0 0 1 1h.5a1 1 0 0 0 1-1v-.5a1 1 0 0 0-1-1z"></path><path d="M9 7.75a1 1 0 0 1 1-1h.5a1 1 0 0 1 1 1v.5a1 1 0 0 1-1 1h-.5a1 1 0 0 1-1-1z"></path><path d="M10 11a1 1 0 0 0-1 1v.5a1 1 0 0 0 1 1h.5a1 1 0 0 0 1-1v-.5a1 1 0 0 0-1-1z"></path></svg></div>`

          html1 += `<div class="variants-option-prev" style="display: none;">
                      <div class="option-prev-name">${element.name}</div>
                      <div class="option-prev-values">`;

          element.values.forEach(element1 => {
            html1 += `<span>${element1}</span>`;
          });

          html1 += `</div>
                      </div>
                      <div class="variants-option-inputs">
                        <div class="input mb-2">
                          <label class="form-label" for="name"><g-t>Option name</g-t></label>
                          <div class="input-wrapper">
                            <input type="text" class="form-control" name="choice[]" value="${element.name}" placeholder="Add Name">
                          </div>
                          <span class="error-txt">Option name is required.</span>
                        </div>
                        <label class="form-label" for="name"><g-t>Option values</g-t></label>
                        <div class="another-inputs-wrapper">`
                
          element.values.forEach(cardvalue => {
            html1 += `<div class="input value-input mb-2">
                        <div class="drag drag-input"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" width="16" height="16"><path d="M5.5 2.5a1 1 0 0 0-1 1v.5a1 1 0 0 0 1 1h.5a1 1 0 0 0 1-1v-.5a1 1 0 0 0-1-1z"></path><path d="M5.5 6.75a1 1 0 0 0-1 1v.5a1 1 0 0 0 1 1h.5a1 1 0 0 0 1-1v-.5a1 1 0 0 0-1-1z"></path><path d="M4.5 12a1 1 0 0 1 1-1h.5a1 1 0 0 1 1 1v.5a1 1 0 0 1-1 1h-.5a1 1 0 0 1-1-1z"></path><path d="M10 2.5a1 1 0 0 0-1 1v.5a1 1 0 0 0 1 1h.5a1 1 0 0 0 1-1v-.5a1 1 0 0 0-1-1z"></path><path d="M9 7.75a1 1 0 0 1 1-1h.5a1 1 0 0 1 1 1v.5a1 1 0 0 1-1 1h-.5a1 1 0 0 1-1-1z"></path><path d="M10 11a1 1 0 0 0-1 1v.5a1 1 0 0 0 1 1h.5a1 1 0 0 0 1-1v-.5a1 1 0 0 0-1-1z"></path></svg></div>
                        <div class="input-wrapper">
                          <input type="text" class="form-control" name="choice_options_${i+1}[]" value="${cardvalue}" placeholder="Add value">
                          <span class="icon" style="fill: #8a8a8a;"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16"><path d="M9.5 6.25a.75.75 0 0 1 .75.75v4.25a.75.75 0 0 1-1.5 0v-4.25a.75.75 0 0 1 .75-.75"></path><path d="M7.25 7a.75.75 0 0 0-1.5 0v4.25a.75.75 0 0 0 1.5 0z"></path><path fill-rule="evenodd" d="M5.25 3.25a2.75 2.75 0 1 1 5.5 0h3a.75.75 0 0 1 0 1.5h-.75v5.45c0 1.68 0 2.52-.327 3.162a3 3 0 0 1-1.311 1.311c-.642.327-1.482.327-3.162.327h-.4c-1.68 0-2.52 0-3.162-.327a3 3 0 0 1-1.311-1.311c-.327-.642-.327-1.482-.327-3.162v-5.45h-.75a.75.75 0 0 1 0-1.5zm1.5 0a1.25 1.25 0 0 1 2.5 0zm-2.25 1.5h7v5.45c0 .865-.001 1.423-.036 1.848-.033.408-.09.559-.128.633a1.5 1.5 0 0 1-.655.655c-.074.038-.225.095-.633.128-.425.035-.983.036-1.848.036h-.4c-.865 0-1.423-.001-1.848-.036-.408-.033-.559-.09-.633-.128a1.5 1.5 0 0 1-.656-.655c-.037-.074-.094-.225-.127-.633-.035-.425-.036-.983-.036-1.848z"></path></svg></span>
                        </div>
                        <span class="error-txt">Option value is required.</span>
                      </div>`;
          });

          html1 += `</div>
                      </div>
                      <div class="row option-card-action-parent">
                        <div class="col-12 option-card-action">
                          <a href="javascript:;" class="del">Delete</a>
                          <a href="javascript:;" class="done">Done</a>
                        </div>
                      </div>
                    </div>`

          $("#VariantsOptionCards .cards").append(html1);
          tableIndex=i+1;
      });

      $(".variants-option-card").find(".variants-option-prev").show();
      $(".variants-option-card").find(".variants-option-inputs").hide();
      $(".variants-option-card").find(".option-card-action-parent").hide();
      $(".variants-option-card").addClass("prev");

     // $("#AddAnotherOption").hide();
      $("#AddVariants").addClass("d-none");
      $("#VariantsOptionCards").removeClass("d-none");
      $(".variants-table").removeClass("d-none");
     // alert(tableIndex);
     tableIndex=tableIndex+1;

      $(".variants-option-cards .cards").sortable({
        handle: '.drag-card',
        update: function (event, ui) {
         updateSortingVariantsCard();
        }
      });
      $(".variants-option-cards .another-inputs-wrapper").sortable({
          handle: '.drag-input',
          update: function (event, ui) {
          updateSortingVariantsCardInput();
          }
        });
      $(".filemanager-image-preview").sortable({
        update: function (event, ui) {
          updateSortingImagesCard();
        }
      });
      makeVariationsArray(); 
    }

    
/// Typeing Input card Remove Error
$("#VariantsOptionCards").on('input', '.cards .variants-option-card input', function (e) {
  $(this).parent().parent().removeClass("error");
});
//Typeing Input card Remove Error

/// Typeing Input card Value
$("#VariantsOptionCards").on("input", ".variants-option-card .input.value-input input", function (e) {
  if ($(this).val() !== "") {
    if (!$(this).parent().parent().next().hasClass("value-input")) {
      $(this).parent().parent().after(`
        <div class="input value-input mb-2">
          <div class="drag drag-input"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" width="16" height="16"><path d="M5.5 2.5a1 1 0 0 0-1 1v.5a1 1 0 0 0 1 1h.5a1 1 0 0 0 1-1v-.5a1 1 0 0 0-1-1z"></path><path d="M5.5 6.75a1 1 0 0 0-1 1v.5a1 1 0 0 0 1 1h.5a1 1 0 0 0 1-1v-.5a1 1 0 0 0-1-1z"></path><path d="M4.5 12a1 1 0 0 1 1-1h.5a1 1 0 0 1 1 1v.5a1 1 0 0 1-1 1h-.5a1 1 0 0 1-1-1z"></path><path d="M10 2.5a1 1 0 0 0-1 1v.5a1 1 0 0 0 1 1h.5a1 1 0 0 0 1-1v-.5a1 1 0 0 0-1-1z"></path><path d="M9 7.75a1 1 0 0 1 1-1h.5a1 1 0 0 1 1 1v.5a1 1 0 0 1-1 1h-.5a1 1 0 0 1-1-1z"></path><path d="M10 11a1 1 0 0 0-1 1v.5a1 1 0 0 0 1 1h.5a1 1 0 0 0 1-1v-.5a1 1 0 0 0-1-1z"></path></svg></div>
          <div class="input-wrapper">
            <input type="text" class="form-control" placeholder="Add another value" name="${$(this).attr("name")}">
            <span class="icon" style="fill: #8a8a8a;"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16"><path d="M9.5 6.25a.75.75 0 0 1 .75.75v4.25a.75.75 0 0 1-1.5 0v-4.25a.75.75 0 0 1 .75-.75"></path><path d="M7.25 7a.75.75 0 0 0-1.5 0v4.25a.75.75 0 0 0 1.5 0z"></path><path fill-rule="evenodd" d="M5.25 3.25a2.75 2.75 0 1 1 5.5 0h3a.75.75 0 0 1 0 1.5h-.75v5.45c0 1.68 0 2.52-.327 3.162a3 3 0 0 1-1.311 1.311c-.642.327-1.482.327-3.162.327h-.4c-1.68 0-2.52 0-3.162-.327a3 3 0 0 1-1.311-1.311c-.327-.642-.327-1.482-.327-3.162v-5.45h-.75a.75.75 0 0 1 0-1.5zm1.5 0a1.25 1.25 0 0 1 2.5 0zm-2.25 1.5h7v5.45c0 .865-.001 1.423-.036 1.848-.033.408-.09.559-.128.633a1.5 1.5 0 0 1-.655.655c-.074.038-.225.095-.633.128-.425.035-.983.036-1.848.036h-.4c-.865 0-1.423-.001-1.848-.036-.408-.033-.559-.09-.633-.128a1.5 1.5 0 0 1-.656-.655c-.037-.074-.094-.225-.127-.633-.035-.425-.036-.983-.036-1.848z"></path></svg></span>
          </div>
          <span class="error-txt">Option value is required.</span>
        </div>
      `);
      $(".variants-option-cards .another-inputs-wrapper").sortable({
        handle: '.drag-input',
        update: function (event, ui) {
          updateSortingVariantsCardInput();
        }
      });
      $("#VariantsOptionCards .cards .variants-option-card input").on("input", function (e) {
        $(this).parent().parent().removeClass("error");
      });
    }
  }

  if ($(this).parents(".variants-option-card").find(".input.value-input:not(:last-child)").length > 1) {
    $(this).parents(".variants-option-card").find(".input.value-input:not(:last-child)").addClass("showDel");
  } else {
    $(this).parents(".variants-option-card").find(".input.value-input:not(:last-child)").removeClass("showDel");
  }

  $(this).parent().removeClass("error");
});
/// Typeing Input card Value

/// delete card value input
$("#VariantsOptionCards").on("click", ".variants-option-card .input.value-input .icon", function (e) {
  e.preventDefault();
  $(this).parents(".input").remove();
});
/// delete card value input
/// delete card
$("#VariantsOptionCards").on("click", ".cards .variants-option-card .del", function (e) {
  e.preventDefault(); 
  $(this).parents(".variants-option-card").remove(); 
  if ($("#VariantsOptionCards .cards .variants-option-card").length === 0) {
    $("#AddVariants").removeClass("d-none");
    $("#VariantsOptionCards").addClass("d-none");
  } 
  updateCardIndex(); 
  makeVariationsArray();
});
/// delete card
function updateCardIndex(){
  let no = 1;
  $("#VariantsOptionCards .cards .variants-option-card").each((i, element)=>{
    $(element).find(".input.value-input input").attr("name", `choice_options${no}[]`);
    no++
  });
  tableIndex=no;
} 
/// card option Preview
$("#VariantsOptionCards").on("click", ".variants-option-prev", function (e) {
  e.preventDefault();
  $(this).parent().removeClass("prev");
  $(this).hide();
  $(this).next().show();
  $(this).next().next().show();
});
/// card option Preview

//// When Click on Done
$("#VariantsOptionCards").on("click", ".cards .variants-option-card .done", function (e) {
  e.preventDefault();
  let status = true; 
  const firstInput = $(this).parents(".variants-option-card").find(".input:not(.value-input) input");  
  if (checkIfTaken(firstInput, $(firstInput).val())) {
    status = false;
  }  
      $(this).parents(".variants-option-card").find(".input:not(.value-input) input").each(function (i, element) {
        if (element.value === "") {
          $(element).parent().parent().addClass("error");
          $(element).parent().parent().find(".error-txt").html("Option name is required.");
          status = false;
        }
      });

    if ($(this).parents(".variants-option-card").find(".input").length <= 2) {  
      $(this).parents(".variants-option-card").find(".input.value-input input").each(function (i, element) {
        if (element.value === "") {
          $(element).parent().parent().addClass("error");
          $(element).parent().parent().find(".error-txt").html("Option value is required.");
          status = false;
        }
      });
    } else { 
      $(this).parents(".variants-option-card").find(".input.value-input:not(:last-child) input").each(function (i, element) {
        if (element.value === "") {
          $(element).parent().parent().addClass("error");
          $(element).parent().parent().find(".error-txt").html("Option value is required.");
          status = false;
        }
      });
    }

  if (status) {
    $(this).parents(".variants-option-card").find(".variants-option-prev .option-prev-name").html($(this).parents(".variants-option-card").find(".input:not(.value-input) input").val())
    $(this).parents(".variants-option-card").find(".variants-option-prev .option-prev-values").empty();
    $(this).parents(".variants-option-card").find(".variants-option-inputs .value-input input").each(function (i, element) {
      if (element.value !== "") {
        $(this).parents(".variants-option-card").find(".variants-option-prev .option-prev-values").append(`<span>${element.value}</span>`)
      }
    }); 
    $(this).parents(".variants-option-card").find(".variants-option-prev").show();
    $(this).parents(".variants-option-card").find(".variants-option-inputs").hide();
    $(this).parents(".variants-option-card").find(".option-card-action-parent").hide();
    $(this).parents(".variants-option-card").addClass("prev");
    makeVariationsTempArray(); 
    if(ProductVariationRecord.length>0){
      ProductVariationPreviousRecord=ProductVariationRecord; 
    }
    
    makeVariationsArray();  
    updateTableValues(); 
  }

});
//// When Click on Done

function makeVariationsArray(GroupBy="") { 
  // console.log(GroupBy);
   let variationsArray = []; 
   $("#VariantsOptionCards .variants-option-card").each((i, element) => {
     let attrName = $(element).find(".variants-option-prev .option-prev-name").html();
     let attrValues = [];
     $(element).find(".variants-option-prev .option-prev-values span").each((i, element1) => {
       if ($(element1).html() !== "") {
         attrValues.push($(element1).html());
       }
     }); 
  
     if (attrName !== "" && attrValues.length > 0) {
       variationsArray.push({
         name: attrName,
         values: attrValues
       });
     } 
   });
   if (variationsArray.length > 0) {
     //console.log(variationsArray);
 
   
   if(GroupBy!=''){
    // console.log(ProductVariationRecord);
     makeVariationsTempArray();
     if(ProductVariationRecord.length>0){
       ProductVariationPreviousRecord=ProductVariationRecord;
      // console.log("Previus",ProductVariationPreviousRecord);
      // console.log("New",ProductVariationRecord);
     }
         let groupedData = makegroupfirst(variationsArray, GroupBy); 
         makeVariationTable(variationsArray,groupedData);
    
   }else{
 
     if (variationsArray.length>1) { 
       $("#GroupBy").empty(); 
       let gOptions = "";
         variationsArray.forEach(element => {
         gOptions += '<option value="' + element.name + '">' + element.name + '</option>';
       });
       $("#GroupBy").html(gOptions); 
       $("#variatonTableFilters").empty(); 
       $(".variants-table .group-by").removeClass('d-none');
     } else {
       $("#GroupBy").empty();
       $(".variants-table .group-by").addClass('d-none');
     }
 
     // Table Filters
     let variatonTableFilters = "";
     variationsArray.forEach(element => {
       variatonTableFilters += `<div class="select-dropdown">
         <div class="droupdown-head one">
           <span>${element.name} <span></span></span>
           <span class="clear-drop d-none">
             <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 12 12" width="12" height="12"><path fill-rule="evenodd" d="M2.185 2.185a.625.625 0 0 1 .884 0l2.931 2.931 2.931-2.931a.625.625 0 1 1 .884.884l-2.931 2.931 2.933 2.933a.625.625 0 1 1-.884.884l-2.933-2.933-2.933 2.933a.625.625 0 0 1-.884-.884l2.933-2.933-2.931-2.931a.625.625 0 0 1 0-.884"></path></svg>
           </span>
           <span class="arrow">
             <svg width="16" height="16" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16">
               <path fill-rule="evenodd" d="M3.72 6.47a.75.75 0 0 1 1.06 0l3.47 3.47 3.47-3.47a.749.749 0 1 1 1.06 1.06l-4 4a.75.75 0 0 1-1.06 0l-4-4a.75.75 0 0 1 0-1.06"></path>
             </svg>
           </span>
         </div>
         <div class="droupdown-body">
           <ul>`;
 
       element.values.forEach(value => {
         variatonTableFilters += `
               <li>
                 <label class="custom-radio">
                   <input type="radio" name="${element.name}" value="${value}">
                   ${value}
                 </label>
               </li>`;
       });
 
       variatonTableFilters += `
           </ul>
           <a href="#">Clear</a>
         </div>
       </div>`;
     });
     $("#variatonTableFilters").html(variatonTableFilters);
 
     $(".select-dropdown .droupdown-head").on("click", function (e) {
       e.preventDefault();
       $(".select-dropdown").not($(this).parent()).removeClass("show");
       $(this).parent().toggleClass("show");
     });
 
     $(".variants-search-filter .select-dropdown .droupdown-body .custom-radio input").on("change", function (e) {
       $(this).parents(".select-dropdown").find(".droupdown-head > span > span").html(`is ${$(this).val()}`);
       $(this).parents(".select-dropdown").find(".clear-drop").removeClass("d-none");
       $(this).parents(".select-dropdown").find(".arrow").addClass("d-none");
     });
 
     $(".variants-search-filter .select-dropdown .droupdown-body a").on("click", function (e) {
       e.preventDefault();
       $(this).parent().find("input").prop('checked', false);
       $(this).parents(".select-dropdown").find(".droupdown-head > span > span").html("");
       $(this).parents(".select-dropdown").find(".clear-drop").addClass("d-none");
       $(this).parents(".select-dropdown").find(".arrow").removeClass("d-none");
     });
 
     $(".variants-search-filter .select-dropdown .clear-drop").on("click", function (e) {
       e.preventDefault();
       $(this).parents(".select-dropdown").find(".droupdown-body input").prop('checked', false);
       $(this).parents(".select-dropdown").find(".droupdown-head > span > span").html("");
       $(this).parents(".select-dropdown").find(".clear-drop").addClass("d-none");
       $(this).parents(".select-dropdown").find(".arrow").removeClass("d-none");
     });
     // Table Filters end
 
      makeVariationTable(variationsArray);
     //console.log(variationsArray);
   }
     
   }else {
     $('.v_table .tbody').empty();
     $(".variants-table").addClass("d-none");
   }
     
 
 } 

 function makegroupfirst(data, groupBy) { 
  let newData = data; 
  const index = newData.findIndex(item => item.name === groupBy); 
  if (index !== -1) { 
    const [item] = newData.splice(index, 1);  
    newData.unshift(item);
  } 
  return newData;
}


   function cartesianProduct(arrays) {
    return arrays.reduce((acc, curr) => {
      return acc.flatMap(a => curr.map(b => `${a}-${b}`));
    });
  }



function makeVariationTable(arr,GroupByData=[]) {
 // console.log(arr);
  let variationTableData = arr[0]?.values?.map((name, i) => {
    const variationArrays = arr.slice(1).map(item => Array.isArray(item?.values) ? item.values : []); 
    const nonEmptyVariations = variationArrays.filter(v => v.length > 0); 
    return {
      name,
      values: nonEmptyVariations.length > 0 ? cartesianProduct(nonEmptyVariations) : []
    };
  }); 

  let tableHtml = "";
  $(variationTableData).each(function (i, element) { 
    if (i === 0) {
      $('.v_table .tbody').empty();
    } 
    tableHtml += `
        <div class="tr ${element.values.length > 0 ? "has-child" : ""}">
          <div class="td">
            <div class="form-check m-0">
              <input class="form-check-input" type="checkbox" />
            </div>
          </div>
          <div class="td">
            <div class="img-name"> 
               <div class="img-prev" onclick="customFilemanager(this)">
                <input type="hidden" ${element.values.length > 0 ?"": `name='img_${element.name}'`}  class="selected-files">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" width="18" height="18"> <path d="M7.018 1.5h1.964c.813 0 1.469 0 2 .043.546.045 1.026.14 1.47.366.706.36 1.28.933 1.64 1.639.226.444.32.924.365 1.47.043.531.043 1.187.043 2v.982a.75.75 0 0 1-1.5 0v-.95c0-.852 0-1.447-.038-1.91-.037-.453-.107-.714-.207-.911a2.25 2.25 0 0 0-.984-.984c-.197-.1-.458-.17-.912-.207-.462-.037-1.057-.038-1.909-.038h-1.9c-.852 0-1.447 0-1.91.038-.453.037-.714.107-.911.207a2.25 2.25 0 0 0-.984.984c-.1.197-.17.458-.207.912-.037.462-.038 1.057-.038 1.909v1.39l1.013-1.013a1.75 1.75 0 0 1 2.474 0l2.543 2.543a.749.749 0 1 1-1.06 1.06l-2.543-2.543a.25.25 0 0 0-.354 0l-2.054 2.055q.007.169.02.317c.036.454.106.715.206.912.216.424.56.768.984.984.197.1.458.17.912.207.462.037 1.057.038 1.909.038h.95a.75.75 0 0 1 0 1.5h-.982c-.813 0-1.469 0-2-.043-.546-.045-1.026-.14-1.47-.366a3.76 3.76 0 0 1-1.64-1.638c-.226-.445-.32-.925-.365-1.471a11 11 0 0 1-.03-.597.7.7 0 0 1-.006-.234q-.009-.52-.007-1.169v-1.964c0-.813 0-1.469.043-2 .045-.546.14-1.026.366-1.47.36-.706.933-1.28 1.639-1.64.444-.226.924-.32 1.47-.365.531-.043 1.187-.043 2-.043"> </path> <path d="M10.5 7a1.5 1.5 0 1 0-.001-3.001 1.5 1.5 0 0 0 .001 3.001"></path> <path d="M12.5 9.25a.75.75 0 0 1 .75.75v1.75h1.75a.75.75 0 0 1 0 1.5h-1.75v1.75a.75.75 0 0 1-1.5 0v-1.75h-1.75a.75.75 0 0 1 0-1.5h1.75v-1.75a.75.75 0 0 1 .75-.75"> </path></svg>
                <img src="" class="d-none" />
              </div>
              <div class="name">
                <h5>${element.name}</h5>
                ${element.values.length == 0 ? `<small class="lbl_sku" id="lbl_sku_${element.name}"></small>` : ""}
                <span>${element.values.length} variant
                  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" width="16" height="16">
                    <path fill-rule="evenodd" d="M3.72 6.47a.75.75 0 0 1 1.06 0l3.47 3.47 3.47-3.47a.749.749 0 1 1 1.06 1.06l-4 4a.75.75 0 0 1-1.06 0l-4-4a.75.75 0 0 1 0-1.06"> </path> </svg>
                </span>
              </div>
            </div>
          </div>
          <div class="td">
            <div class="input currency-input">
              <span class="currency-txt">AED</span>
              <input type="text" oninput="this.value = this.value.replace(/[^0-9]/g, '');" class="form-control price" value="0.00" ${element.values.length > 0 ?"": `name='price_${element.name}'` } min="0.1">
            </div>
          </div>
          <div class="td">
            <div class="input">
              <input type="text" oninput="this.value = this.value.replace(/[^0-9]/g, '');" class="form-control available-qty" value="0" min="0" ${element.values.length > 0 ?"": `name='qty_${element.name}'`} ${$("#v_locations").val() == "all" ? "disabled" : ""}>
            </div>
          </div>
          <div class="td d-none">
            <div class="input">
              <input class="sku" type="text" ${element.values.length > 0 ?"": `name='sku_${element.name}'`} value="sku_${element.name}">
              <input class="cost-price" type="text" ${element.values.length > 0 ?"": `name='cost_price_${element.name}'`} value="0.00">
              <input class="variant-sku" type="text" ${element.values.length > 0 ?"": `name='variant_sku_${element.name}'`} value="${element.name}">
              <input class="barcode" type="text" ${element.values.length > 0 ?"": `name='barcode_${element.name}'`} value="">
              <input class="hscode" type="text" ${element.values.length > 0 ?"": `name='hscode_${element.name}'`} value="">
            </div>
          </div>
          <div class="v_collapse"></div>
        </div>
      `;

    if (element.values.length > 0) {

      tableHtml += `<div class="childs" style="display: none;">`;
      $(element.values).each(function (i, childElement) {
        tableHtml += `
            <div class="tr child">
              <div class="td">
                <div class="form-check m-0">
                  <input class="form-check-input" type="checkbox" />
                </div>
              </div>
              <div class="td">  
                <div class="img-name">
                  <div class="img-prev" onclick="customFilemanager(this)">
                    <input type="hidden" name="img_${element.name + "-" + childElement}" class="selected-files">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" width="18" height="18"> <path d="M7.018 1.5h1.964c.813 0 1.469 0 2 .043.546.045 1.026.14 1.47.366.706.36 1.28.933 1.64 1.639.226.444.32.924.365 1.47.043.531.043 1.187.043 2v.982a.75.75 0 0 1-1.5 0v-.95c0-.852 0-1.447-.038-1.91-.037-.453-.107-.714-.207-.911a2.25 2.25 0 0 0-.984-.984c-.197-.1-.458-.17-.912-.207-.462-.037-1.057-.038-1.909-.038h-1.9c-.852 0-1.447 0-1.91.038-.453.037-.714.107-.911.207a2.25 2.25 0 0 0-.984.984c-.1.197-.17.458-.207.912-.037.462-.038 1.057-.038 1.909v1.39l1.013-1.013a1.75 1.75 0 0 1 2.474 0l2.543 2.543a.749.749 0 1 1-1.06 1.06l-2.543-2.543a.25.25 0 0 0-.354 0l-2.054 2.055q.007.169.02.317c.036.454.106.715.206.912.216.424.56.768.984.984.197.1.458.17.912.207.462.037 1.057.038 1.909.038h.95a.75.75 0 0 1 0 1.5h-.982c-.813 0-1.469 0-2-.043-.546-.045-1.026-.14-1.47-.366a3.76 3.76 0 0 1-1.64-1.638c-.226-.445-.32-.925-.365-1.471a11 11 0 0 1-.03-.597.7.7 0 0 1-.006-.234q-.009-.52-.007-1.169v-1.964c0-.813 0-1.469.043-2 .045-.546.14-1.026.366-1.47.36-.706.933-1.28 1.639-1.64.444-.226.924-.32 1.47-.365.531-.043 1.187-.043 2-.043"> </path> <path d="M10.5 7a1.5 1.5 0 1 0-.001-3.001 1.5 1.5 0 0 0 .001 3.001"></path> <path d="M12.5 9.25a.75.75 0 0 1 .75.75v1.75h1.75a.75.75 0 0 1 0 1.5h-1.75v1.75a.75.75 0 0 1-1.5 0v-1.75h-1.75a.75.75 0 0 1 0-1.5h1.75v-1.75a.75.75 0 0 1 .75-.75"> </path> </svg>
                    <img src="" class="d-none" />
                  </div>
                  <div class="name">
                    <h5>${childElement}</h5>
                    <small class="lbl_sku"  id="lbl_sku_${element.name + "-" + childElement}"></small>
                  </div>
                </div>
              </div>
              <div class="td">
                <div class="input currency-input">
                  <span class="currency-txt">AED</span>
                  <input type="text" oninput="this.value = this.value.replace(/[^0-9]/g, '');" class="form-control price" value="0.00" min="0.1" name="price_${element.name + "-" + childElement}">
                </div>
              </div>
              <div class="td">
                <div class="input">
                  <input type="text" oninput="this.value = this.value.replace(/[^0-9]/g, '');" class="form-control available-qty" value="0" min="0" name="qty_${element.name + "-" + childElement}" ${$("#v_locations").val() == "all" ? "disabled" : ""}>
                </div>
              </div>
              <div class="td d-none">
                <div class="input">
                  <input class="sku" type="text" name="sku_${element.name + "-" + childElement}" value="sku_${element.name + "-" + childElement}">
                  <input class="cost-price" type="text" name='cost_price_${element.name+ "-" + childElement}' value="0.00">
                  <input class="variant-sku" type="text" name='variant_sku_${element.name+ "-" + childElement}' value="${element.name+ "-" + childElement}">
                  <input class="barcode" type="text" name='barcode_${element.name+ "-" + childElement}' value="">
                  <input class="hscode" type="text" name='hscode_${element.name+ "-" + childElement}' value="">
                </div>
              </div>
              <div class="v_collapse"></div>
            </div>
          `;
      });
      tableHtml += `</div>`; 
    }
    
  });

 
  $(".v_table .tbody").html(tableHtml);
  //$(".varient_data").val(JSON.stringify(variationTableData));
  $(".varient_data_view").val(JSON.stringify(arr));
  $(".variants-table").removeClass("d-none");

  // Varient image
  $(".v_table").on("input", ".tr.has-child .td .img-name .img-prev input", function(){
    // console.log("parent image", $(this).val(), $(this));
    if($(this).val() !== "") {
      $(this).parent().find("svg").addClass("d-none");
      $(this).parent().find("img").attr("src", AssetsPath + $(this).val());
      $(this).parent().find("img").removeClass("d-none");

      $(this).parents(".has-child").next().find(".img-prev input").val($(this).val()).trigger("input");
      $(this).parents(".has-child").next().find(".img-prev img").attr("src", AssetsPath + $(this).val());
      $(this).parents(".has-child").next().find(".img-prev img").removeClass("d-none");
      $(this).parents(".has-child").next().find(".img-prev svg").addClass("d-none");
    }
  });
  $(".v_table").on("input", ".tr:not(.has-child) .td .img-name .img-prev input", function(){
    //console.log("child image: ", $(this), $(this).attr("name"), $(this).val());
     
    if($(this).val() !== "") {
      $(this).parent().find("svg").addClass("d-none");
      $(this).parent().find("img").attr("src", AssetsPath + $(this).val());
      $(this).parent().find("img").removeClass("d-none");
      var input_sku = generateStandardSKU($(this).attr("name").replace("img_", "")); 
      update_variation_all_fields('selectedImageFile',input_sku,$(this).val());
       makeAllLocationArrays();
    }
    //updateTableValues();
   
  });
  // Varient image end
  //
 // console.log("Table Loaded");
   //makeVariationsTempArray();
  // updateTableValues();
  makeAllLocationArrays();
  
}

function editVarientTableData(variantsEditData){
  ProductVariationRecord=[];
  const ProductVariationTableRows = document.querySelectorAll('.v_table .tbody .tr:not(.has-child)');
   
    if(variantsEditData!='' && variantsEditData!=null && variantsEditData.length>0){
        variantsEditData1=JSON.parse(variantsEditData);
        if(!all_active_locations || !Array.isArray(all_active_locations)){
         // window.location.reload();
        }
        all_active_locations.forEach(location => {
          ProductVariationRecord=[];
          variantsEditData1.forEach(editData => { 
                  // console.log(editData);
                        if (editData.location_id==location.uuid) {  

                          ProductVariationTableRows.forEach(row => {
                             const t_sku = generateStandardSKU(row.querySelector('.sku').value.replace('sku_','')); 
                            // console.log(t_sku);
                            // console.log(editData.variant);
                             if(normalizeVariantName(t_sku) === normalizeVariantName(editData.variant) ){
                              //console.log(t_sku);
                                ProductVariationRecord.push({
                                    'product_id': editData.product_id,
                                    'variantName': editData.variant,
                                    'variantPrice':editData.price,
                                    'variantCostPrice':editData.cost_per_item,
                                    'SKU':editData.sku,
                                    'variantBarCode':editData.barcode,
                                    'variantHSCode':editData.hs_code,
                                    'variantSKU':editData.variant_sku,
                                    'variantQuantity':editData.qty,
                                    'selectedImageFile':editData.image,
                                    'location_id':editData.location_id, 
                                });
                              }
                          });
                          } 
            });
            
             ProductVariationRecordByLocation[location.uuid] = ProductVariationRecord; 
           // console.log('edit varient table ',location.uuid,ProductVariationRecord);
    });
      // ProductVariationRecordByLocation[location_id] = ProductVariationRecord;
      // console.log('Edited Data',ProductVariationRecordByLocation);
    } 
   // ProductVariationRecordByLocation['all'] = ProductVariationRecord;
    
   // makeVariationsTempArray();
    //updateTableValues();
    //console.log(ProductVariationRecordByLocation);
    $('#variation_data').val(JSON.stringify(ProductVariationRecordByLocation));
}



let ProductVariationRecordByLocation = {}; // Global object
let variation_data_by_location={};
$(".variants-table").on('change', '.body .v_table .childs .form-control', function () {  
  //console.log(this);
  updateVariationsTempArray(this); 
});
$(".variants-table").on('change', '.body .v_table .form-control', function () {  
  //console.log(this);
  updateVariationsTempArray(this); 
});
setTimeout(() => {
  $('#GroupBy').select2({
    allowClear: false,
    minimumResultsForSearch: Infinity
  });
}, 100);
$(".variants-table").on('change', '#GroupBy', function () { 
  makeVariationsArray($(this).val()); 
  makeVariationsTempArray();
  updateTableValues();
});



function updateVariationsTempArray(elem) {
  const location_id = $('#v_locations').val();
  //console.log(location_id); 
  const ProductVariationTableRows = document.querySelectorAll('.v_table .tbody .tr:not(.has-child)');
  const ProductVariationRecord = [];
  
  ProductVariationTableRows.forEach(row => {
    const sku = generateStandardSKU(row.querySelector('.sku').value.replace('sku_', '')) || '';
    const variantName = sku.replace('sku_', '');
    const variantPrice = parseFloat(row.querySelector('.price').value);
    const variantCostPrice = parseFloat(row.querySelector('.cost-price').value) || 0;
    const variantSKU = row.querySelector('.variant-sku').value;
    const variantBarCode = row.querySelector('.barcode').value;
    const variantHSCode = row.querySelector('.hscode').value;
    const variantQuantity = row.querySelector('.available-qty').value;
    const selectedImageFile = row.querySelector('.selected-files').value;

    ProductVariationRecord.push({
      product_id: productID,
      location_id, // ← save location_id directly in each record
      variantName,
      SKU: sku.replace('sku_', ''),
      variantPrice,
      variantCostPrice,
      variantSKU,
      variantBarCode,
      variantHSCode,
      variantQuantity,
      selectedImageFile: selectedImageFile
    });
  });
  ProductVariationRecordByLocation[location_id] = ProductVariationRecord;




            /// Update all the existing Arrays
            all_active_locations.forEach(location => {
              const existingRecords = ProductVariationRecordByLocation[location.uuid] || [];
             // console.log(existingRecords);
              // // Create a map for quick lookup by SKU
              // const existingBySKU = {};
              // existingRecords.forEach(record => {
              //     if (record.SKU) {
              //         existingBySKU[record.SKU] = record;
              //     }
              // });
          
              // const updatedRecords = [];
          
              // ProductVariationTableRows.forEach(row => {
              //     const skuInput = row.querySelector('.sku');
              //     const sku = skuInput?.value ? skuInput.value.replace('sku_', '') : '';
          
              //     const newData = { 
              //         variantPrice: row.variantPrice || 0,
              //         variantCostPrice: row.variantCostPrice || 0,
              //         SKU: row.SKU || '',
              //         variantBarCode: row.variantBarCode || '',
              //         variantHSCode: row.variantHSCode || '',
              //         variantSKU: row.variantSKU || '',
              //         selectedImageFile: row.selectedImageFile || null,
              //     };
          
              //     if (existingBySKU[sku]) {
              //         // Update only matching SKU record by merging new fields
              //         updatedRecords.push({
              //             ...existingBySKU[sku],
              //             ...newData
              //         });
              //     } else {
              //         // Keep non-matching records unchanged
              //         updatedRecords.push(newData);
              //     }
              // });
          
              // ProductVariationRecordByLocation[location.uuid] = updatedRecords;
          });
          
  /// Update all the existing Arrays end
  // if(location_id=='all'){
  //   raw=elem.name.replace('price_','');
  //   sku=generateStandardSKU(raw);
  //  // console.log(sku); 
  //   //console.log(ProductVariationRecordByLocation);
  //   if(Array.isArray(ProductVariationRecordByLocation)) {
      
  //   }
  // }
 //console.log("from Table Temp Aray update" ,ProductVariationRecordByLocation);
  $('#variation_data').val(JSON.stringify(ProductVariationRecordByLocation));
}


function makeVariationsTempArray() {
  const location_id = $('#v_locations').val();
 // console.log('location_id temp',location_id);
  const ProductVariationTableRows = document.querySelectorAll('.v_table .tbody .tr:not(.has-child)');
  const ProductVariationRecord = [];
 // console.log(ProductVariationRecordByLocation[location_id]);
  if (!Array.isArray(ProductVariationRecordByLocation[location_id])
    ||
  ProductVariationRecordByLocation[location_id].length == 0) {
   // console.log('location_id temp',location_id);
  ProductVariationTableRows.forEach(row => {
    const sku = generateStandardSKU(row.querySelector('.sku').value.replace('sku_', '')) || '';
    const variantName = sku.replace('sku_', '');
    const variantPrice = parseFloat(0.00);
    const variantCostPrice = parseFloat(row.querySelector('.cost-price').value) || 0;
    const variantSKU = row.querySelector('.variant-sku').value;
    const variantBarCode = row.querySelector('.barcode').value;
    const variantHSCode = row.querySelector('.hscode').value;
    const variantQuantity = row.querySelector('.available-qty').value;
    const selectedImageFile = row.querySelector('.selected-files').value;

    ProductVariationRecord.push({
      product_id: productID,
      location_id, // ← save location_id directly in each record
      variantName,
      SKU: sku,
      variantPrice,
      variantCostPrice,
      variantSKU,
      variantBarCode,
      variantHSCode,
      variantQuantity,
      selectedImageFile: selectedImageFile
    });
  });
 // console.log("from Table Temp Aray" , ProductVariationRecord);
 // console.log('all arrays',ProductVariationRecordByLocation);
}else{
    ProductVariationRecordByLocation[location_id].forEach(record => {
      let str = 'sku_'+record.SKU;
      let price = str.replace("sku_", "price_");
      let costprice = str.replace("sku_", "cost_price_");
      let variantsku = str.replace("sku_", "variant_sku_");
      let barcode = str.replace("sku_", "barcode_");
      let hs_code = str.replace("sku_", "hscode_");
      let qty = str.replace("sku_", "qty_");
      let img = str.replace("sku_", "img_");
      //console.log('record.variantPrice',record);
    
    $(`input[name="${price}"]`).val(record.variantPrice).trigger("input").trigger("blur");
    $(`input[name="${costprice}"]`).val(record.variantCostPrice);
    $(`input[name="${variantsku}"]`).val(record.variantSKU);
    $(`input[name="${barcode}"]`).val(record.variantBarCode);
    $(`input[name="${hs_code}"]`).val(record.variantHSCode);
    $(`input[name="${qty}"]`).val(record.variantQuantity).trigger("input");
    $(`input[name="${img}"]`).val(record.selectedImageFile).trigger("input");

      ProductVariationRecord.push({
        product_id: productID,
        location_id: location_id, 
        variantName: record.variantName,
        variantPrice: record.variantPrice,
        variantCostPrice: record.variantCostPrice,
        SKU: record.variantName,
        variantBarCode: record.variantBarCode,
        variantHSCode: record.variantHSCode,
        variantSKU: record.variantSKU,
        variantQuantity: record.variantQuantity,
        selectedImageFile: record.selectedImageFile,
        
      });
    });
  //console.log("from Temp Aray",ProductVariationRecord);
  //console.log('all arrays',ProductVariationRecordByLocation);
}

  ProductVariationRecordByLocation[location_id] = ProductVariationRecord;
  // console.log("Temp Array",ProductVariationRecordByLocation[location_id]);
   
}


// function generateStandardSKU(variantName) {
//   const parts = variantName.split('-'); 
//   const sorted = parts.sort((a, b) => a.localeCompare(b)); // Alphabetical
//   return 'sku_' + sorted.join('-');
// }

// function generateStandardSKU(variantName) {
//   const parts = variantName.split('-'); 
//   const sorted = parts.sort((a, b) => a.localeCompare(b)); // Alphabetical
//   return sorted.join('-');
// }

function generateStandardSKU(variantName) {
  if (!variantName || typeof variantName !== 'string') return '';

  const parts = variantName
    .split('-')
    .map(part => part.trim())  // Trim spaces around each part
    .filter(Boolean);          // Remove empty strings if any

  // const sorted = parts.sort((a, b) => a.localeCompare(b));
  return parts.join('-');
}

function normalizeVariantName(name) {
  if (typeof name !== 'string') return '';
  return name.split('-').sort().join('-');
}


function updateTableValues() {
  const location_id = $('#v_locations').val();  
   //  console.log("Before",ProductVariationRecordByLocation);
   get_total_qty_for_allArray(ProductVariationRecordByLocation);
  //  console.log("After",ProductVariationRecordByLocation);
  //console.log(ProductVariationRecordByLocation[location_id]);
  const productRecords = ProductVariationRecordByLocation[location_id] || []; 

  $('.v_table .tbody .tr:not(.has-child)').each(function () {
    const row = $(this);
    const skuField = row.find('.sku').val();
    if (!skuField) return;

    const variantName = generateStandardSKU(skuField.replace('sku_', ''));

    // Reset fields
    row.find('.price').val('0.00');
    row.find('.cost-price').val('0.00');
    row.find('.variant-sku').val('');
    row.find('.barcode').val('');
    row.find('.hscode').val('');
    row.find('.available-qty').val('0');
    row.find('.selected-files').val('');

    // Match and populate
    const match = productRecords.find(r =>
      normalizeVariantName(r.variantName) === normalizeVariantName(variantName) &&
      r.location_id == location_id
    );

    if (match) {
   //   console.log('Table variantName '+variantName,', Array variantName '+match.variantName,', Array variantQuantity '+match.variantQuantity);
      row.find('.price').val(match.variantPrice).trigger('input').trigger('blur');
      row.find('.cost-price').val(match.variantCostPrice);
      row.find('.lbl_sku').html(match.variantSKU);
      row.find('.variant-sku').val(match.variantSKU);
      row.find('.barcode').val(match.variantBarCode);
      row.find('.hscode').val(match.variantHSCode);
      row.find('.available-qty').val(match.variantQuantity).trigger('input');
      row.find('.selected-files').val(match.selectedImageFile).trigger("input");
      //console.log('variantSKU',match.variantSKU);
    }
  });  
  //console.log(ProductVariationRecordByLocation);
  //console.log(productRecords);
  
  $('#variation_data').val(JSON.stringify(ProductVariationRecordByLocation));
}


$(".variants-table").on('change', '.body .v_table .price', function () {
  var input = $(this); 
  if (input.length > 0) {
    var input_name = input.attr('name'); 
    if (input_name !== undefined) {
      var input_price = parseFloat(input.val()); 
      //console.log('Name:', input_name, 'Price:', input_price);  
      var input_sku = generateStandardSKU(input_name.replace("price_", "")); 
      // console.log('SKU:', input_sku, 'Price:', input_price); 
      // console.log('Arrays:', ProductVariationRecordByLocation); 
       update_variation_all_fields('variantPrice',input_sku,input_price);
    }
  } 
  // update_variation_price();
});



$(".variants-table").on('change', '.body .v_table .cost-price', function () {
  var input = $(this); 
  if (input.length > 0) {
    var input_name = input.attr('name'); 
    if (input_name !== undefined) {
      var input_cost_price = parseFloat(input.val());  
      var input_sku = generateStandardSKU(input_name.replace("cost_price_", ""));  
      //console.log('SKU:', input_sku, 'Price:', input_cost_price);  
       update_variation_all_fields('variantCostPrice',input_sku,input_cost_price);
    }
  }  
});

$(".variants-table").on('change', '.body .v_table .variant-sku', function () {
  var input = $(this); 
  if (input.length > 0) {
    var input_name = input.attr('name'); 
    if (input_name !== undefined) {
      var input_variant_sku = input.val();  
      var input_sku = generateStandardSKU(input_name.replace("variant_sku_", ""));  
    //  console.log('SKU:', input_sku, 'variantSKU:', input_variant_sku);  
       update_variation_all_fields('variantSKU',input_sku,input_variant_sku);
    }
  }  
});


$(".variants-table").on('change', '.body .v_table .barcode', function () {
  var input = $(this); 
  if (input.length > 0) {
    var input_name = input.attr('name'); 
    if (input_name !== undefined) {
      var input_barcode = input.val();  
      var input_sku = generateStandardSKU(input_name.replace("barcode_", ""));  
    //  console.log('SKU:', input_sku, 'variantBarCode:', input_barcode);  
       update_variation_all_fields('variantBarCode',input_sku,input_barcode);
    }
  }  
});



$(".variants-table").on('change', '.body .v_table .hscode', function () {
  var input = $(this); 
  if (input.length > 0) {
    var input_name = input.attr('name'); 
    if (input_name !== undefined) {
      var input_hscode = input.val();  
      var input_sku = generateStandardSKU(input_name.replace("hscode_", ""));  
      //console.log('SKU:', input_sku, 'variantHSCode:', input_hscode);  
       update_variation_all_fields('variantHSCode',input_sku,input_hscode);
    }
  }  
});


function update_variation_all_fields(field, sku, newValue) {
  if (!sku || typeof newValue === 'undefined') return; 
  Object.keys(ProductVariationRecordByLocation).forEach(locId => {
    if (locId === 'all') return; 
    const locationVariants = ProductVariationRecordByLocation[locId];
    if (Array.isArray(locationVariants)) {
      locationVariants.forEach(variant => {
        if (variant.SKU === sku) {
          variant[field] = newValue;
          //console.log(`Updated SKU ${sku} in ${locId}: Set ${field} = ${newValue}`);
        }
      });
    }
  });
}


function get_total_qty_for_allArray(data) {
  if (!data?.all || !Array.isArray(data.all)) {
    console.error("Invalid data structure: 'all' array missing.");
    return;
  }

  const allVariants = data.all;
  const locationKeys = Object.keys(data).filter(key => key !== "all");

  //console.log(`Processing ${allVariants.length} variants from "all" array...`);
  //console.log(`Found location keys: ${locationKeys.join(', ')}`);

  allVariants.forEach(variant => {
    const targetSKU = generateStandardSKU(variant.SKU);
    let totalQuantity = 0;
    let priceAssigned = false;
    let vSKU = '';

    //console.log(`\n--- Processing SKU: ${targetSKU} ---`);

    locationKeys.forEach(locationId => {
      const locationVariants = data[locationId];
      if (!Array.isArray(locationVariants)) {
        //console.warn(`Skipping location '${locationId}' - not an array.`);
        return;
      }

      //console.log(`Checking location: ${locationId}`);

      locationVariants.forEach(locVariant => {
       // console.log('locVariant.SKU',locVariant.SKU);
        if (locVariant.SKU !== targetSKU) return;

        const quantity = Number(locVariant.variantQuantity) || 0;
        const price = Number(locVariant.variantPrice) || 0;
        const selectedImageFile = locVariant.selectedImageFile || '';
        let vSKU=locVariant.variantSKU || '';
       // console.log(`  ✔ Match found in ${locationId} → Qty: ${quantity}, Price: ${price}, SKU: ${locVariant.SKU}`);

        totalQuantity += quantity;

        if (!priceAssigned && price > 0) {
          variant.variantPrice = price;
          priceAssigned = true;
         // console.log(`  → Price assigned: ${price}`);
        }
        variant.selectedImageFile=selectedImageFile;
        variant.variantSKU=vSKU;
        //console.log('selectedImageFile '+targetSKU,variant.selectedImageFile);
      });
    });

    variant.variantQuantity = totalQuantity;
   
    if (!priceAssigned) {
      variant.variantPrice = 0;
    ///  console.log(`  ⚠ No price found. Defaulting to 0.`);
    }

   // console.log(`✅ Final total for SKU ${targetSKU}: Qty = ${totalQuantity}, Price = ${variant.variantPrice}`);
  });

  //console.log(`\n🎉 Done processing all variants.`);
}





 



 

// Table Filters 
$(".search-filter-btn").on("click", function (e) {
  e.preventDefault();
  $(this).toggleClass("cancel");
  $(".variants-search-filter").slideToggle(100);
});
function checkIfTaken(element, val) {
  let inputValue = val.trim(); 
  let isDuplicate = false;
  $('#VariantsOptionCards .cards .variants-option-card .input:not(.value-input) input').not(element).each(function () {
    if ($(this).val().trim() === inputValue) {
      isDuplicate = true;
      return false;
    }
  });

  if (isDuplicate) {
    return true;
  } else {
    return false;
  }
}

function collapseAll() {
  $(".variants-table .body .v_table").each(function () {
    let $table = $(this);
    let $childElements = $table.find(".childs");
    let $collapseButton = $table.find(".collapse-all");

    if ($childElements.is(":visible")) {
      $collapseButton.text("Collapse all");
    } else {
      $collapseButton.text("Expand all");
    }
  });
}
$(".variants-table .body .v_table .collapse-all").on("click", function (e) {
  e.preventDefault(); 
  let table = $(this).parents(".v_table");
  let childElements = table.find(".childs"); 
  if (childElements.is(":visible")) {
    childElements.slideUp();
    $(this).text("Expand all");
  } else {
    childElements.slideDown();
    $(this).text("Collapse all");
  }
}); 
$(".variants-table").on('click', '.body .v_table .tr.has-child .v_collapse', function (e) {
  e.preventDefault(); 
  $(this).parent().next().slideToggle(); 
  collapseAll();
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
$(".variants-search-filter").on('click', '.select-dropdown .droupdown-body .custom-radio input', function (e) {
  $(this).parents(".select-dropdown").find(".droupdown-head > span > span").html(`is ${$(this).val()}`);
  $(this).parents(".select-dropdown").find(".clear-drop").removeClass("d-none");
  $(this).parents(".select-dropdown").find(".arrow").addClass("d-none");
}); 
$(".variants-search-filter").on('click', '.select-dropdown .droupdown-body a', function (e) {
  e.preventDefault();
  $(this).parent().find("input").prop('checked', false);
  $(this).parents(".select-dropdown").find(".droupdown-head > span > span").html("");
  $(this).parents(".select-dropdown").find(".clear-drop").addClass("d-none");
  $(this).parents(".select-dropdown").find(".arrow").removeClass("d-none");
}); 
$(".variants-search-filter").on('click', '.select-dropdown .clear-drop', function (e) {
  e.preventDefault();
  $(this).parents(".select-dropdown").find(".droupdown-body input").prop('checked', false);
  $(this).parents(".select-dropdown").find(".droupdown-head > span > span").html("");
  $(this).parents(".select-dropdown").find(".clear-drop").addClass("d-none");
  $(this).parents(".select-dropdown").find(".arrow").removeClass("d-none");
});

$(".variants-table").on('input', '.body .v_table .available-qty:not([disabled])', function () {
  let total = 0;
  $(".variants-table .body .v_table .available-qty:not([disabled])").each((i, element) => {
    total = total + parseInt(element.value);
  }); 
  $(".available-locations span").html(total); 
});

$(".variants-table").on('input', '.body .v_table .childs .available-qty', function () {
  let total = 0;

  $(this).parents(".childs").find(".available-qty").each((i, element) => {
    let currentval = element.value == "" ? 0 : element.value
    total = total + parseInt(currentval);
  });

  $(this).parents(".childs").prev().find(".available-qty").val(total);
});

$(".variants-table").on('input', '.body .v_table .tr.has-child .available-qty', function () {    
  $(this).parents(".tr").next().find(".available-qty").val($(this).val());
});

$(".variants-table").on('keyup', '.body .v_table .form-control:not(.available-qty)', function () {
  $(this).removeAttr("placeholder");
  $(this).attr("name", $(this).attr("name-val"));
});

$(".variants-table").on('input', '.body .v_table .childs .form-control:not(.available-qty)', function () {

  if ($(this).parents(".childs").find(".form-control:not(.available-qty)").length <= 1) {
    let total = 0;

    $(this).parents(".childs").find(".form-control:not(.available-qty)").each((i, element) => {
      let currentval = element.value === "" ? 0.00 : parseFloat(element.value);
      total = total + currentval;
    });

    let formattedTotal = total.toFixed(2);

    $(this).parents(".childs").prev().find(".form-control:not(.available-qty)").val(formattedTotal);

  } else {
    let values = [];
    $(this).parents(".childs").find(".form-control:not(.available-qty)").each(function () {
      let value = $(this).val() === "" ? 0 : parseFloat($(this).val());
      values.push(value);
    });

    let min = Math.min(...values);
    let max = Math.max(...values);

    let formattedMin = min.toFixed(2);
    let formattedMax = max.toFixed(2);

    let allSame = values.every(val => val === values[0]);

    if (allSame) {
      $(this).parents(".childs").prev().find(".form-control:not(.available-qty)").val(values[0].toFixed(2));
    } else {
      $(this).parents(".childs").prev().find(".form-control:not(.available-qty)").val("");
      $(this).parents(".childs").prev().find(".form-control:not(.available-qty)").attr("name-val",  $(this).parents(".childs").prev().find(".form-control:not(.available-qty)").attr("name"));
      $(this).parents(".childs").prev().find(".form-control:not(.available-qty)").removeAttr("name");
      $(this).parents(".childs").prev().find(".form-control:not(.available-qty)").attr("placeholder", formattedMin + " - " + formattedMax);
    }

  }

});

$(".variants-table").on('change', '.body .v_table #toggleAllVarientCheck', function () {
  
  if ($(this).prop("checked")) {
    $(".v_table .tbody .form-check-input").each((i, element) => {
      $(element).prop("checked", true)
    });
  } else {
    $(".v_table .tbody .form-check-input").each((i, element) => {
      $(element).prop("checked", false)
    });
  }
});

$(".variants-table").on('change', '.body .v_table .form-check-input', function () {

  if ($(this).parents(".tr").hasClass("has-child")) {
    if ($(this).prop('checked')) {
      $(this).parents(".tr").next().find(".form-check-input").prop('checked', true);
    } else {
      $(this).parents(".tr").next().find(".form-check-input").prop('checked', false);
    }
    isAnyVariantChecked();
  } else {
    if ($(this).prop('checked')) {
      $(this).parents(".childs").prev().find(".form-check-input").prop('checked', true);
    } else {
      var anySiblingChecked = $(this).parents(".tr").siblings().find(".form-check-input:checked").length > 0;

      if (!anySiblingChecked) {
        $(this).parents(".childs").prev().find(".form-check-input").prop('checked', false);
      }
    }
    isAnyVariantChecked();
  }

});

$(".variants-table").on('input', '.body .v_table .tr.has-child .form-control:not(.available-qty)', function () {

  let currentValue = parseFloat($(this).val()) || 0;

  let formattedValue = currentValue.toFixed(2);

  $(this).parents(".tr").next().find(".form-control:not(.available-qty)").each((i, element) => {
    $(element).val(formattedValue).trigger('change');
  });

});

$(".variants-table").on('blur', '.body .v_table .available-qty:not([disabled])', function () {
  if ($(this).val() === "") {
    $(this).val(0);
  }
});

$(".variants-table").on('focus', '.body .v_table .form-control', function () {
  $(this).select();
});

$(".variants-table").on('blur', '.body .v_table .form-control:not(.available-qty)', function () {
  let value = $(this).val();

  if (value === "") {

    if (!$(this).attr("placeholder")) {
      $(this).val("0.00");
    }
    return;
  }

  value = value.replace(/[^0-9.]/g, "");

  let parts = value.split(".");

  if (parts.length > 1) {
    parts[1] = parts[1].substring(0, 2);
  }

  let formattedValue = parts.join(".");
  if (!formattedValue.includes(".")) {
    formattedValue += ".00";
  } else if (parts[1].length === 1) {
    formattedValue += "0";
  }

  $(this).val(formattedValue);
});

$("#v_locations").on('change', function () {

  if ($("#v_locations").val() != "all") {
    $(".variants-table .body .v_table .tr:not(.has-child) .td .available-qty").removeAttr("disabled");
    //$(".variants-table .body .v_table .tr:not(.has-child) .td .available-qty").val("0");
    $(".available-locations span").html("0");
  } else {
    $(".variants-table .body .v_table .tr:not(.has-child) .td .available-qty").attr("disabled", true);
   // $(".variants-table .body .v_table .tr:not(.has-child) .td .available-qty").val("0");
    $(".available-locations span").html("0");
  }

});




 

function updateSortingImagesCard(e) {
 // console.log("Done. Images Card");
  let allURLS = [];
  $(".filemanager-image-preview .filemanager-preview-image").each(function(i,e){
    allURLS.push($(e).data('previewimage'));
  });
  $("input[name='images']").val(allURLS.join(','));
}

function updateSortingVariantsCard(e) { 
  makeVariationsArray("GroupBy");  
  updateTableValues();
   
}

function updateSortingVariantsCardInput(e) { 
  //makeVariationsArray("GroupBy");  
 // updateTableValues();
}
 



function showHideAnotnerVariantBtn() {
  if ($("#VariantsOptionCards .cards .variants-option-card").length >= 3) {
    $("#AddAnotherOption").hide();
  } else {
    $("#AddAnotherOption").show();
  }
}

function isAnyVariantChecked() {
  let anyChecked = false;
  let totalCheckedCount = 0;

  $(".v_table .tbody .tr:not(.has-child) .form-check-input").each((i, element) => {
    if ($(element).prop("checked")) {
      totalCheckedCount++;
      if (!anyChecked) {
        anyChecked = true;
      }
    }
  });

  $(".v_table .selected-options .selected-options-count").html(totalCheckedCount);

  if(parseInt(totalCheckedCount) > 0) {
    $(".v_table .selected-options").removeClass("d-none");
  } else {
    $(".v_table .selected-options").addClass("d-none");
  }

  if (anyChecked) {
    $("#toggleAllVarientCheck").prop("checked", true);
  } else {
    $("#toggleAllVarientCheck").prop("checked", false);
  }
}

function mainaddlocation() {

  let getPushlocation = [];

  $("#location-checkboxs input[type=checkbox]").each((i, element)=>{
    
    if ($(element).prop("checked")) {
      getPushlocation.push($(element).val());
    }
    
  });

  
  $('input[name=warehouse_location_id]').val(getPushlocation.join(","));
  // console.log($('input[name=warehouse_location_id]').val());
}



// let tableIndex = 1;
function addVarientCard(data='') {  
  const template = document.getElementById("addVarientCardTemp");
  if (!template) return; 
  let html = template.innerHTML.replace(/__INDEX__/g, tableIndex);  
  const container = document.querySelector("#VariantsOptionCards .cards");
  if (!container) {
    console.error("option_container not found!");
    return;
  } 
  container.insertAdjacentHTML("beforeend", html); 
  $("#AddAnotherOption").show();
  $("#AddVariants").addClass("d-none");
  $("#VariantsOptionCards").removeClass("d-none"); 
  tableIndex++;   
  $("#VariantsOptionCards .cards .variants-option-card .input:not(.value-input) input").focus();
  checkValidations();
}
function checkValidations(){
  $(document).on('blur', '#VariantsOptionCards .cards .variants-option-card .input:not(.value-input) input', function () { 
    if ($(this).val() === "") {
      $(this).parent().parent().addClass("error");
      $(this).parent().parent().find(".error-txt").html("Option name is required.");
      return;
    } 
    let inputValue = $(this).val().trim();
    let isDuplicate = false;
    $('#VariantsOptionCards .cards .variants-option-card .input:not(.value-input) input').not(this).each(function () {
      if ($(this).val().trim() === inputValue && inputValue !== '') {
        isDuplicate = true;
        return false;
      }
    }); 
    if (isDuplicate) {
      $(this).parent().parent().addClass("error");
      $(this).parent().parent().find(".error-txt").html(`You've already used the option name "${$(this).val()}".`);
    } else {
      $(this).parent().parent().removeClass("error");
    }
  }); 
 
} 


function makeAllLocationArrays() {
 // console.log('makeAllLocationArrays running');
  const ProductVariationTableRows = document.querySelectorAll('.v_table .tbody .tr:not(.has-child)');

  if (!all_active_locations || !Array.isArray(all_active_locations)) {
    return;
  }

  // Step 1: Get all current variantNames from table rows
  const currentVariantNames = [];
  ProductVariationTableRows.forEach(row => {
    const rawSku = row.querySelector('.sku').value;
    const n_sku = generateStandardSKU(rawSku.replace('sku_', ''));
    if (n_sku) {
      currentVariantNames.push(n_sku);
    }
  });

  all_active_locations.forEach(location => {
    if (!ProductVariationRecordByLocation[location.uuid] || !Array.isArray(ProductVariationRecordByLocation[location.uuid])) {
      ProductVariationRecordByLocation[location.uuid] = [];
    }

    let currentLocationArray = ProductVariationRecordByLocation[location.uuid];
    
    // Step 2: Remove extra variants not present in currentVariantNames
    currentLocationArray = currentLocationArray.filter(record =>
      currentVariantNames.includes(record.variantName)
    );
    
    // Step 3: Add or update variants
    ProductVariationTableRows.forEach(row => {
      const rawSku = row.querySelector('.sku').value;
      const n_sku = generateStandardSKU(rawSku.replace('sku_', ''));
      if (!n_sku) return;

      const existingIndex = currentLocationArray.findIndex(record => record.variantName === n_sku);
      if (existingIndex === -1) {
       // console.log('New Variation', n_sku);
        currentLocationArray.push({
          product_id: productID,
          variantName: n_sku,
          variantPrice: 0.00,
          variantCostPrice: 0.00,
          SKU: n_sku,
          variantBarCode: '',
          variantHSCode: '',
          variantSKU: '',
          variantQuantity: 0,
          selectedImageFile: '',
          location_id: location.uuid,
        });
      } else {
        currentLocationArray[existingIndex].location_id = location.uuid;
        // console.log('Existing Variation', n_sku);
      }
    });

    ProductVariationRecordByLocation[location.uuid] = currentLocationArray;
  });

  // === Update 'all' section ===
  if (!ProductVariationRecordByLocation['all'] || !Array.isArray(ProductVariationRecordByLocation['all'])) {
    ProductVariationRecordByLocation['all'] = [];
  }

  let allArray = ProductVariationRecordByLocation['all'];

  // Remove extra variants not in currentVariantNames
  allArray = allArray.filter(record =>
    currentVariantNames.includes(record.variantName)
  );

  // Add missing variants from table rows
  currentVariantNames.forEach(n_sku => {
    const existsInAll = allArray.some(record => record.variantName === n_sku);
    if (!existsInAll) {
     // console.log('Adding to all:', n_sku);
      allArray.push({
        product_id: productID,
        variantName: n_sku,
        variantPrice: 0.00,
        variantCostPrice: 0.00,
        SKU: n_sku,
        variantBarCode: '',
        variantHSCode: '',
        variantSKU: '',
        variantQuantity: 0,
        selectedImageFile: '',
        location_id: 'all', // Could be set to null or skipped depending on your data model
      });
    }
  });

  ProductVariationRecordByLocation['all'] = allArray;

  // Save final result
  $('#variation_data').val(JSON.stringify(ProductVariationRecordByLocation));
}







  
function syncAllPriceFromLocations(data) {
  if (!data || !Array.isArray(data["all"])) return;

  const locationKeys = Object.keys(data).filter(key => key !== "all");

  data["all"].forEach(allVariant => {
      const sku = allVariant.SKU;

      for (const locationId of locationKeys) {
          const variants = data[locationId];
          if (!Array.isArray(variants)) continue;

          const matched = variants.find(v => v.SKU === sku && Number(v.variantPrice) > 0);
          if (matched) {
              allVariant.variantPrice = matched.variantPrice;
              break; // Only take the first available non-zero price
          }
      }
  });
}

function syncLocationsPriceFromAll(data) {
  if (!data || !Array.isArray(data["all"])) return;

  const locationKeys = Object.keys(data).filter(key => key !== "all");

  data["all"].forEach(allVariant => {
      const sku = allVariant.SKU;
      const priceToSet = allVariant.variantPrice;

      for (const locationId of locationKeys) {
          const variants = data[locationId];
          if (!Array.isArray(variants)) continue;

          variants.forEach(locVariant => {
              if (locVariant.SKU === sku) {
                  locVariant.variantPrice = priceToSet;
              }
          });
      }
  });
}

  
