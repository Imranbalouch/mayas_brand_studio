document.title = "Dashboard | Payment Logs";
let PageNo = 1;
let PageSize = 10;
let totalRecord = 0;
let PageLength = 0;
let Search = '';

$(document).ready(function () {
    btnnew = $('#btn_new');
    btnnew.hide();
    btnnew.click(function (e) {
        e.preventDefault();
        let page = 'paymentlogadd';
        window.location.assign('?P=' + page + '&M=' + menu_id);
    });

    Onload();
});

function formatStatusText(status) {
    if (!status) return '';
    return status
        .split('_')
        .map((word, index) => {
            if (index === 0) {
                return word.charAt(0).toUpperCase() + word.slice(1).toLowerCase();
            } else {
                return word.toLowerCase();
            }
        })
        .join(' ');
}

function Onload() {
    $('#Table_View').DataTable().clear().destroy();

    $.ajax({
        url: ApiPlugin + '/ecommerce/payment/get_payment_logs',
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

            if (response.status_code == 200) {
                // Check permissions
                let action_button = '';
                if (Boolean(response.permissions?.['add'])) {
                    btnnew.show();
                }

                // Define DataTable Columns
                const columnsConfig = [
                    {
                        data: null,
                        render: function (data, type, row, meta) {
                            return meta.row + meta.settings._iDisplayStart + 1;
                        },
                        title: '#',
                    },
                    {
                        data: null,
                        render: function (data, type, row) {
                            if (row.order && row.order.code) {
                                return `<a href="?P=edit-order&M=${menu_id}&I=${row.order.uuid}">
                                            ${row.order.code}
                                        </a>`;
                            }
                            return 'N/A';
                        },
                    },
                    {
                        data: null,
                        render: function (data, type, row) {
                            if (row.order && row.order.customer) {
                                return (row.order.customer.first_name || '') + ' ' + (row.order.customer.last_name || '');
                            }
                            return 'N/A';
                        },
                    },
                    {
                        data: 'amount',
                        render: function (data) {
                            return data ? 'AED ' + parseFloat(data).toFixed(2) : 'AED 0.00';
                        },
                    },
                    {
                        data: null,
                        render: function (data, type, row) {
                            if (row.order && row.order.payment_method) {
                                return formatStatusText(row.order.payment_method);
                            }
                            return 'N/A';
                        },
                    },
                    {
                        data: 'status',
                        render: function (data) {
                            let badgeClass = 'badge-pending';
                            let statusText = 'Pending';

                            if (data === 'paid') {
                                badgeClass = 'badge-paid';
                                statusText = 'Paid';
                            } else if (data === 'failed') {
                                badgeClass = 'badge-failed';
                                statusText = 'Failed';
                            } else if (data === 'initiated') {
                                badgeClass = 'badge-pending';
                                statusText = 'In Process';
                            }

                            return `<span class="${badgeClass}">${statusText}</span>`;
                        },
                    },
                    {
                        data: 'response_message',
                        render: function (data) {
                            if (!data) return 'N/A';
                            return data.length > 50 ? data.substring(0, 50) + '...' : data;
                        },
                    },
                    {
                        data: 'created_at',
                        render: function (data) {
                            return data ? formatDate(data) : 'N/A';
                        },
                    }
                ];

                // Initialize DataTable
                $('#Table_View').DataTable({
                    data: response.data,
                    columns: columnsConfig,
                    pageLength: 10,
                    lengthMenu: [
                        [10, 25, 50, 100],
                        [10, 25, 50, 100],
                    ],
                });
            } else {
                showToast(response.message, 'warning', 'warning');
            }
        },
        error: function (xhr, status, err) {
            imgload.hide();
            if (xhr.status == 401) {
                showToast(err, 'error', 'error');
            } else {
                showToast('Error loading payment logs', 'error', 'error');
            }
        }
    });
    return true;
}
