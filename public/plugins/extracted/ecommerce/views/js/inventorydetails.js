document.title = "Dashboard | Inventory Details";
var urlParams = new URLSearchParams(window.location.search);
var id = urlParams.get('id');

let PageNo = 1;
let PageSize = 10;
let totalRecord = 0;
let PageLength = 0;
let Search = '';
let keyupTimer;
$(document).ready(function () {
   
    Onload();
});
function Onload() {
    $('#Table_View').DataTable().clear().destroy();
    $('#buttons').html('');
    $.ajax({
        url: ApiPlugin + '/ecommerce/inventory/inventory-detail/'+id,
        type: "Get",
        contentType: "application/json",
        dataType: "json",
        beforeSend: function (xhr) {
            xhr.setRequestHeader("Authorization", "Bearer " + strkey);
            xhr.setRequestHeader("menu-uuid", menu_id);
            imgload.show();
        },
        success: function (response) {

            // console.log(response["data"][0]["inventory_available"]);
            
            if (response.status_code == 200) {
            
                if (response["data"] != null) { 

                     let inventoryproduct_name = response["data"][0]["product"]["name"];
                     let inventory_product_sku = response["data"][0]["product_stock"]["variant"];
                    // console.log(inventoryproduct_name);
                    let pqty=0;
                    let total = 0;
                    // Ajax Data Settings
                    const columnsConfig = [
                        {
                            data: null, // No specific data source
                            render: function (data, type, row, meta) {
                                return meta.row + meta.settings._iDisplayStart + 1;
                            },
                            title: '#', // Serial number
                            orderable: false
                        },
                        {
                            data: 'created_at',
                            render: function (data, type, row) {
                                return `${formatDate(row.created_at)}`;
                            },
                            title: 'DATE'
                        },
                        {
                            data: 'reason',
                            render: function (data, type, row) {
                                return `${row.reason}`;
                            },
                            title: 'REASON'
                        },
                        // {
                        //     data: 'name', // Product Name
                        //     render: function (data, type, row) {
                        //         return `${row.product.name}`;
                        //     },
                        //     title: 'Product'
                        // },
                        {
                            data: 'sku', // SKU
                            render: function (data, type, row) {
                                return `${row.product_stock.sku?row.product_stock.sku:''}`;
                            },
                            title: 'SKU'
                        },
                        // {
                        //     data: 'status',
                        //     render: function (data, type, row) {
                        //         return `${row.status}`;
                        //     },
                        //     title: 'STATUS'
                        // },
                        
                        {
                            data: 'qty',
                            render: function (data, type, row) {
                               
                                if (row.status === 'unavailable') { 
                                    return `-${row.qty}`; // Add "-" before qty if reason is 'unavailable'
                                }  
                                return `${row.qty}`; // Otherwise, return qty as it is
                            },
                            title: 'QTY'
                        },
                        {
                            data: 'qty',
                            render: function (data, type, row) {
                                let pqty = '';
                                if (row.status === 'unavailable') {
                                    pqty=-Math.abs(parseInt(row.qty, 10));
                                   // console.log(pqty);
                                   // pqty= `-${row.qty}`;
                                 //   return `-${row.qty}`; // Add "-" before qty if reason is 'unavailable'
                                }else{
                                    pqty=row.qty;
                                }
                                    
                                   // console.log(pqty);
                               // return `${row.qty}`; // Otherwise, return qty as it is
                                total +=  pqty; 
                                //console.log("Total so far: " + total); // For debugging 
                                return total;
                            },
                            title: 'TOTAL'
                        },
                        
                        
                    ];
                    
                    tableData= response["data"];
                    makeDataTable('#Table_View', tableData, columnsConfig, { 
                        showButtons: false, 
                    });
                    let pfullname ='';
                    let pname = '<span style="display:inline-block; margin-right:10px;font-size: large;">'+ inventoryproduct_name +'</span>';
                    let psku = '<span style="display:inline-block;font-size: large;"> -> '+ inventory_product_sku +'</span>';
                    if(inventoryproduct_name!='' || inventoryproduct_name!=null){
                        pfullname=pname;
                    }
                    if(inventory_product_sku !==''){ 
                       // console.log(psku);
                        pfullname=pname+psku;
                    } 

                    $("#mytitle").html(pfullname);
                   
                    imgload.hide();
                }
            } else if (response.status_code == 404) {
                imgload.hide();
                $("#Table_View").DataTable({
                    destroy: true,
                    retrieve: true,
                    ordering: false,
                    responsive: true,
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
            }else{
                showToast('Error', 'error', 'error');
            }
        }
    })
    return true;
}
