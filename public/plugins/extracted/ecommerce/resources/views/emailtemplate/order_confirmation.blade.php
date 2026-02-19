<!DOCTYPE html>
<html>

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Order Confirmation</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta charset="UTF-8">
    <style media="all">
        @page {
            margin: 0;
            padding: 0;
        }

        body {
            font-size: 0.875rem;
            font-family: 'Arial', sans-serif;
            font-weight: normal;
            padding: 0;
            margin: 0;
            color: #333333;
        }

        .gry-color * {
            color: #333333;
        }

        table {
            width: 100%;
        }

        table th {
            font-weight: normal;
        }

        table.padding th {
            padding: .25rem .7rem;
        }

        table.padding td {
            padding: .25rem .7rem;
        }

        table.sm-padding td {
            padding: .1rem .7rem;
        }

        .border-bottom td,
        .border-bottom th {
            border-bottom: 1px solid #e0e0e0;
        }

        .text-left {
            text-align: left;
        }

        .text-right {
            text-align: right;
        }

        .header-section {
            background: #f5f5f5;
            padding: 1rem;
        }

        .logo {
            height: 60px;
        }

        .strong {
            font-weight: bold;
        }

        .small {
            font-size: 0.75rem;
        }

        .product-table {
            margin-top: 0px;
            margin-bottom: 20px;
        }

        .product-table th {
            background: #f5f5f5;
            padding: 8px;
            font-weight: bold;
        }

        .product-table td {
            padding: 8px;
            border-bottom: 1px solid #e0e0e0;
        }

        .totals-table {
            width: 100%;
            margin-top: 20px;
        }

        .totals-table th {
            text-align: left;
            padding: 5px;
        }

        .totals-table td {
            text-align: right;
            padding: 5px;
        }

        .grand-total {
            font-weight: bold;
            font-size: 1rem;
        }

        .address-section {
            padding: 1rem;
        }
    </style>
</head>

<body>
    <div>
        <!-- Header Section -->
        <div class="header-section">
            <table>
                <tr>
                    <td>
                        <img src="{{ env('APP_ASSET_PATH') . 'assets/images/instamanage.png' ?? '' }}" alt="Company Logo" style="height: 25px; width: auto;" class="logo">
                    </td>
                    <td style="font-size: 1.5rem;" class="text-right strong">Order Confirmation</td>
                </tr>
            </table>
            <table>
                <tr>
                    <td class="gry-color small">{{ env('COMPANY_ADDRESS') }}</td>
                    <td class="text-right">Date: {{ $details['order']['created_at']}}</td>
                </tr>
                <tr>
                    <td class="gry-color small">Email: {{ env('COMPANY_MAIL') }}</td>
                    <td class="text-right small"><span class="gry-color small">Order ID:</span> <span class="strong">{{ $details['order']['code'] }}</span></td>
                </tr>
                <tr>
                    <td class="gry-color small">Phone: {{ env('COMPANY_PHONE')  }}</td>
                    <td class="text-right small">
                        <span class="gry-color small">
                            Payment method:
                        </span>
                        <span class="strong">
                            {{ $details['order']['payment_method'] ?? '' }}
                        </span>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Billing and Shipping Address Section -->
            <div class="address-section">
                <table width="100%">
                    <tr>
                        <td width="50%" valign="top">
                            <table>
                                <tr>
                                    <td class="strong small gry-color"><strong>Bill to:</strong></td>
                                </tr>
                                <tr>
                                    <td class="strong">
                                        {{ ($details['order']['billing_first_name'] ?? '') . ' ' . ($details['order']['billing_last_name'] ?? '') }}
                                    </td>
                                </tr>
                                <tr>
                                    <td class="gry-color small">{{ $details['order']['billing_address'] ?? '' }}</td>
                                </tr>
                                <tr>
                                    <td class="gry-color small">Email: {{ $details['order']['billing_email'] ?? '' }}</td>
                                </tr>
                                <tr>
                                    <td class="gry-color small">Phone: {{ $details['order']['billing_phone'] ?? '' }}</td>
                                </tr>
                            </table>
                        </td>
                        <td width="50%" valign="top" style="text-align: right;">
                            <table style="margin-left: auto;">
                                <tr>
                                    <td class="strong small gry-color"><strong>Ship to:</strong></td>
                                </tr>
                                <tr>
                                    <td class="strong">
                                        {{ ($details['order']['shipping_first_name'] ?? '') . ' ' . ($details['order']['shipping_last_name'] ?? '') }}
                                    </td>
                                </tr>
                                <tr>
                                    <td class="gry-color small">{{ $details['order']['shipping_address'] ?? '' }}</td>
                                </tr>
                                <tr>
                                    <td class="gry-color small">Email: {{ $details['order']['shipping_email'] ?? '' }}</td>
                                </tr>
                                <tr>
                                    <td class="gry-color small">Phone: {{ $details['order']['shipping_phone'] ?? '' }}</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </div>

        </div>

        
        <!-- Product Table Section -->
        <div style="padding: 1rem;">
            <table class="padding text-left small border-bottom product-table">
                <thead>
                    <h2 style="padding: 1rem 1rem 0.5rem 1rem; font-weight: bold;">Order Details:</h2>
                    <tr class="gry-color">
                        <th width="35%" class="text-left">Product Name</th>
                        <th width="10%" class="text-left">Qty</th>
                        <th width="15%" class="text-left">Unit Price</th>
                        <th width="15%" class="text-right">Total</th>
                    </tr>
                </thead>
                <tbody class="strong">
                    @foreach($details['orderDetail'] as $item)
                    <tr>
                        <td>
                            {{ $item['product_name'] ?? $item['item_name'] }}
                            @if(isset($item['variant']))
                            - {{ $item['variant'] }}
                            @endif
                        </td>
                        <td>{{ $item['product_qty'] ?? $item['qty'] }}</td>
                        <td>{{ number_format(($item['product_price'] ?? 0), 2) }}</td>
                        <td class="text-right">
                            {{ number_format(($item['product_price'] ?? 0) * ($item['product_qty'] ?? 0), 2) }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Order Summary Section -->
        <div style="padding:0 1.5rem; text-align:right;">
            <table class="sm-padding small strong">
                <thead>
                    <tr>
                        <th width="60%"></th>
                    </tr>
                </thead>
                <tbody>
                    @php
                    $subtotal = 0;
                    foreach ($details['orderDetail'] as $item) {
                    $price = $item['product_price'] ?? 0;
                    $qty = $item['product_qty'] ?? $item['qty'] ?? 0;
                    $subtotal += $price * $qty;
                    }
                    @endphp

                    <tr>
                        <td>
                            <table class="sm-padding small strong totals-table">
                                <tbody>
                                    <tr>
                                        <th class="gry-color text-left">Sub Total</th>
                                        <td>{{ number_format($subtotal, 2) }}</td>
                                    </tr>
                                    @if(!empty($details['order']['shipping_price']))
                                    <tr>
                                        <th class="gry-color text-left">Shipping Price</th>
                                        <td>{{ number_format($details['order']['shipping_price'], 2) }}</td>
                                    </tr>
                                    @endif
                                    @if(!empty($details['order']['discount_value']))
                                    <tr>
                                        <th class="gry-color text-left">
                                            @if(isset($details['order']['discount_type']) && $details['order']['discount_type'] == 'percentage')
                                            Discount ({{ $details['order']['discount_value'] }}%)
                                            @else
                                            Discount
                                            @endif
                                        </th>
                                        <td>-{{ number_format($details['order']['discount_value'], 2) }}</td>
                                    </tr>
                                    @endif
                                    <tr>
                                        <th class="text-left grand-total">Grand Total</th>
                                        <td class="grand-total">{{ $details['order']['currency'] ?? '' }}{{ number_format($details['order']['grand_total'], 2) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Footer Section -->
        <div style="padding:1.5rem; text-align:center; margin-top:30px; border-top: 1px solid #e0e0e0;">
            <p>Thank you for your purchase!</p>
            <p class="small">If you have any questions about your order, please contact our customer service team at <strong>{{ env('SUPPORT_MAIL') }}</strong></p>
        </div>
    </div>
</body>

</html>