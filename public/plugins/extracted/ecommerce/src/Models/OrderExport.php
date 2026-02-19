<?php

namespace App\Models\Ecommerce;

use App\Models\Enquiry;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;

class OrderExport implements FromCollection, WithMapping, WithHeadings
{
    protected $orders;

    public function __construct($orders)
    {
        $this->orders = $orders;
    }

    public function collection()
    {
        return $this->orders;
    }

    public function headings(): array
    {
        return [
            'Order Code',
            'Order Date',
            'Num. of Products',
            'Customer Name',
            'Amount',
            'Delivery Status',
            'Payment Method',
            'Payment Status'
        ];
    }

    /**
    * @var Enquiry $contact
    */
    public function map($order): array
    { 
        $name = '';
        $paymentStatus = '';
        if ($order->user != null){
           $name =  $order->user->name;
        }else{
            $name = 'Guest '.($order->guest_id);
        }
        if ($order->payment_status == 'paid'){
            $paymentStatus = translate('Paid');
        }else{
            $paymentStatus = translate('Unpaid');
        }
        return [
            $order->code,
            date('d-m-Y', $order->date),
            count($order->orderDetails),
            $name,
            single_price($order->grand_total),
            translate(ucfirst(str_replace('_', ' ', $order->delivery_status))),
            translate(ucfirst(str_replace('_', ' ', $order->payment_type))),
            $paymentStatus
            
        ];
    }
}
