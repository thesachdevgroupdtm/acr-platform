<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Order Detail</title>
    <style type="text/css">

    ::selection { background-color: #E13300; color: white; }
    ::-moz-selection { background-color: #E13300; color: white; }

    body {
        background-color: #fff;
        margin: 40px;
        /*font: 13px/20px normal Helvetica, Arial, sans-serif;*/
        color: #323232;
        font-family: Arial, sans-serif;
    }

    a {
        color: #003399;
        background-color: transparent;
        font-weight: normal;
    }

    h1 {
        color: black;
        background-color: transparent;
        /*border: 1px solid black;*/
        font-size: 19px;
        /*font-weight: normal;*/
        /*margin: 0 0 14px 0;*/
        /*padding: 14px 15px 10px 15px;*/
/*                padding: 10px !important;
                box-shadow: 0 3rem 5rem black !important;*/
    }

    code {
        /*font-family: Consolas, Monaco, Courier New, Courier, monospace;*/
        font-size: 12px;
        background-color: #f9f9f9;
        border: 1px solid #D0D0D0;
        color: #002166;
        display: block;
        margin: 14px 0 14px 0;
        padding: 12px 10px 12px 10px;
    }

    #body {
        margin: 0 15px 0 15px;
    }

    p.footer {
        text-align: right;
        font-size: 11px;
        border-top: 1px solid #D0D0D0;
        line-height: 32px;
        padding: 0 10px 0 10px;
        margin: 20px 0 0 0;
    }

    #container {
        margin: 10px;
        border: 1px solid #D0D0D0;
        box-shadow: 0 0 8px #D0D0D0;
    }
        #presc_table{
                border-top: 1px solid #D0D0D0;
        }
        #presc_table td{
                border-bottom: 1px solid #D0D0D0;
                line-height: 25px;
        }
        #ptable thead tr th{
            text-align: left;
            border-bottom: 1px solid #ffbc0e;
        }
        #ptable tbody tr td{
            text-align: left;
            line-height: 30px;
            background: #fbf8f8;
        }
        .itable tbody tr td{
            border: 1px solid black;
            line-height: 15px;
            font-size:10px;
            color:black;
            padding-left: 5px;
        }
/*        span{
            font-size:10px;
        }*/
        .bg-white{
            background: white;
        }
        #btable tr td{
            font-size: 12px;
        }
        @page {
            header: page-header;
            footer: page-footer;
        }
    </style>
</head>
<body>
<div>
    <table width="100%" style="margin-bottom: 5px;">
        <tbody>
            <tr>
                <td width="100%" style="text-align:right">
                    <img src="{{ asset('front/img/acr-logo.webp') }}" width="25%"/>
                </td>
            </tr>
        </tbody>
    </table>
    @php($detail = isset($order->detail) && $order->detail->count() ? $order->detail : array())
    @php($brand = $model = $fuel = '')
    @if($detail)
        @foreach($detail as $record)
            @if($record->service_id)
                @php($brand = isset($record->packageDetail->brandDetail->title) ? $record->packageDetail->brandDetail->title : NULL)
                @php($model = isset($record->packageDetail->modelDetail->title) ? $record->packageDetail->modelDetail->title : NULL)
                @php($fuel = isset($record->packageDetail->fuelTypeDetail->title) ? $record->packageDetail->fuelTypeDetail->title : NULL)
            @endif
        @endforeach
    @endif
    <table width="100%" style="margin-bottom: 5px; background-color: #dee6ed; padding: 10px;">
        <tbody>
            <tr>
                <td width="100%">
                    <table id="btable">
                        <tr>
                            <td><strong>{{$order->name}}</strong></td>
                        </tr>
                        <tr>
                            <td>{{$order->phone}}</td>
                        </tr>
                        <tr>
                            <td>{!! $order->address !!}, {{$order->city.', '.$order->zip}}</td>
                        </tr>
                        @if($brand && $model && $fuel)
                            <tr><td><b>{{$brand.' - '.$model.' - '.$fuel}}</b></td></tr>
                        @endif
                        <tr>
                            <td><strong>Vehicle Number :</strong> {{$order->vehicle_number}}</td>
                        </tr>
                    </table>
                </td>
                <td width="60%" style="text-align: right; font-size: 20px;">
                    <table id="btable">
                        <tr>
                            <td><strong>{{$site_name}}</strong></td>
                        </tr>
                        <tr>
                            <td><strong>Address : </strong></span><span>{!! $address !!}</td>
                        </tr>
                        <tr>
                            <td><strong>Invoice Number:</strong> {{isset($order->invoice_no) ? '#'.$order->invoice_no : ''}}</td>
                        </tr>
                        <tr>
                            <td><strong>Invoice Date:</strong> {{$order->order_date ? date('d/m/Y', strtotime($order->order_date)) : ''}}</td>
                        </tr>
                        @if($pan_card)
                            <tr>
                                <td><strong>Pan Card : </strong></span><span>{{$pan_card}}</td>
                            </tr>
                        @endif
                        @if($gst_number)
                            <tr>
                                <td><strong>Gst Number : </strong></span><span>{{$gst_number}}</td>
                            </tr>
                        @endif
                    </table>
                </td>
            </tr>
        </tbody>
    </table>
    <table width="100%" class="border" style="margin-top:20px;">
        <tbody>
            <tr style="background-color: #164498; color: white; margin: 5px;">
                <td style="padding: 10px;">Product/Service</td>
                <td style="padding: 10px; text-align: right;">Qty</td>
                <td style="padding: 10px; text-align: right;">Price</td>
                <td style="padding: 10px; text-align: right;">Gst(%)</td>
                <td style="padding: 10px; text-align: right;">Subtotal</td>
            </tr>
            @php($detail = isset($order->detail) && $order->detail->count() ? $order->detail : array())
            @if($detail)
                @php($subtotal = 0)
                @foreach($detail as $record)
                    <tr>
                        <td style="border-bottom: 1px solid #ccc; font-size: 13px; padding: 10px;">
                            @if($record->service_id)
                                @php($is_service_in_order = $order->id)
                                @php($service_category = isset($record->packageDetail->packageDetail->categoryDetail->title) ? $record->packageDetail->packageDetail->categoryDetail->title : NULL)
                                @php($service = isset($record->packageDetail->packageDetail->title) ? $record->packageDetail->packageDetail->title : NULL)
                                @php($brand = isset($record->packageDetail->brandDetail->title) ? $record->packageDetail->brandDetail->title : NULL)
                                @php($model = isset($record->packageDetail->modelDetail->title) ? $record->packageDetail->modelDetail->title : NULL)
                                @php($fuel = isset($record->packageDetail->fuelTypeDetail->title) ? $record->packageDetail->fuelTypeDetail->title : NULL)
                                {{$service_category}}<br/>
                                {{$service}}<br/>
                                <small class="font-small">
                                    {{$brand.' - '.$model.' - '.$fuel}}<br/>
                                    {{'Vehicle Number : '.$order->vehicle_number}}<br/>
                                </small>
                                <small class="font-small text-danger" style="font-size: 10px;">
                                    <b>Pick Up Details : 
                                        {{isset($order->slotDetail->slot_date) && $order->slotDetail->slot_date ? date('d/m/Y', strtotime($order->slotDetail->slot_date)) : '' }}
                                        {{isset($order->slotDetail->id) ? " ".$order->slotDetail->pick_up_time1.'-'.$order->slotDetail->pick_up_time2 : ''}}
                                        {{isset($order->slotDetail->time_type) && $order->slotDetail->time_type == '1' ? " PM" : ' AM'}}
                                        {{isset($order->slotDetail->time_takes) && $order->slotDetail->time_takes ? ', time takes '.$order->slotDetail->time_takes. ' hrs' : ''}}
                                    </b>
                                </small>
                            @endif
                            @if($record->product_id)
                                {{isset($record->productDetail->name) ? $record->productDetail->name : NULL}}<br/>
                            @endif
                        </td>
                        <td style="border-bottom: 1px solid #ccc; font-size: 13px; text-align: right; padding: 10px;">
                            @if($record->product_id)
                                {{$record->qty}}
                            @else
                                1
                            @endif
                        </td>
                        <td style="border-bottom: 1px solid #ccc; font-size: 13px; text-align: right; padding: 10px;">
                            {{$record->price}}
                        </td>
                        <td style="border-bottom: 1px solid #ccc; font-size: 13px; text-align: right; ">
                            @if($record->product_id)
                                {{$order->product_gst_rate.' (%)'}}
                            @else
                                {{$order->service_gst_rate.' (%)'}}
                            @endif
                        </td>
                        <td style="border-bottom: 1px solid #ccc; font-size: 13px; text-align: right;">
                            @php($subtotal = $record->subtotal + $subtotal)
                            {{$record->subtotal}}
                        </td>
                    </tr>
                @endforeach
            @endif
        </tbody>
        <tfoot>
            <tr>
                <th colspan="3" style="font-size: 13px; text-align: left;">
                    &nbsp;<br/>
                    <b style="padding-top:10px; margin-bottom: 10px;">TERMS & CONDITIONS:</b><br/>
                    &nbsp;<br/>
                    <span style='font-weight: normal;'>It is a long established fact that a reader will be distracted by the readable content of a page when looking at its layout.</span>
                </th>
                <th style="text-align: right;font-size: 13px;">SubTotal</th>
                <th style="text-align: right;font-size: 13px;">{{formatNumber($subtotal)}}</th>
            </tr>
            <tr>
                @php($gst_total = $order->product_gst + $order->service_gst)
                <th colspan="4" style="text-align: right;font-size: 13px;">Tax</th>
                <th style="text-align: right;font-size: 13px;">{{formatNumber($gst_total)}}</th>
            </tr>
            <tr>
                <th colspan="4" style="text-align: right;font-size: 13px;">Total</th>
                <th style="text-align: right;font-size: 13px;">{{$order->total}}</th>
            </tr>
        </tfoot>
    </table>
</div>
</body>
</html>
