@extends('en.Emails.Layouts.MasterReceipt')

@section('message_content')
<div style="padding:10px; background: #fff; border: 1px solid #f1f1f1;">
    <h1 style="text-align: right;">RECEIPT</h1>
    <hr>
    <div></div>
    <table style="width:100%; margin:10px;">
        <tr>
            <td>
                <img style="max-width: 200px;margin: 0;padding: 0;font-family: &quot;Helvetica Neue&quot;, &quot;Helvetica&quot;, Helvetica, Arial, sans-serif;font-size: 100%;line-height: 1.6" src="{{ config('app.url') }}/assets/images/logo-dark.png" />
            </td>
            <td style="text-align: right;">
                Global Woman Club <br>
                07507 837123<br>
                Brentano Suite, Centennial Avenue, Elstree,<br>
                Borehamwood, England WD6 3SY<br>
                UK<br>
                <a href="https://globalwomanclub.com">https://globalwomanclub.com</a>
            </td>
        </tr>
    </table>
    <hr>

    <table style="width:100%; margin:10px;">
        <tr>
            <td>
                <strong>Billed to</strong> <br>
                @if($order->business_name != null)
                {{$order->business_name}} <br>
                {{$order->business_tax_number}} <br>
                {{$order->business_address_line_one}} <br>
                {{$order->business_address_city}} <br>
                @else
                {{$order->full_name}} <br>
                {{$order->contact_no}} <br>
                {{$order->email}} <br>
                @endif

            </td>
            <td>
                <strong>Receipt No</strong> <br>
                100000{{$order->id}} <br>
                <br>
                <br>
            </td>
        </tr>
        <tr>
            <td>

            </td>
            <td>
                <strong>Date Paid</strong> <br>
                {{$order->created_at->format('d F Y')}} <br>

            </td>
        </tr>
    </table>

    <table style="width:100%; margin:10px;">
        <tr>
            <td>
                <strong>Item Name</strong>
            </td>
            <td>
                <strong>Qty.</strong>
            </td>
            <td>
                <strong>Price</strong>
            </td>
            <td>
                <strong>Total</strong>
            </td>
        </tr>
        <tr>
            <td>

            </td>
        </tr>
        @foreach($order->orderItems as $order_item)
        <tr>
            @if($order_item->is_speaking_slot == false)
            <td style="width: 40%;">{{$order->event->title}}</td>
            @else
            <td style="width: 40%;">{{$order_item->title}}</td>
            @endif
            <td>{{$order_item->quantity}}</td>
            <td>
                @isFree($order_item->unit_price)
                FREE
                @else
                {{money($order_item->unit_price + $order_item->unit_booking_fee, $order->event->currency)}}
                @endif
            </td>
            <td>
                @isFree($order_item->unit_price)
                FREE
                @else
                {{money(($order_item->unit_price + $order_item->unit_booking_fee) * ($order_item->quantity),
                $order->event->currency)}}
                @endif
            </td>
        </tr>
        @endforeach
        <tr>
            <td>

            </td>
        </tr>
        <tr>
            <td>

            </td>
        </tr>
        <tr>
            <td colspan="2"></td>
            <td><strong>Sub Total</strong></td>
            <td>
                @php
                    $orderService->order = $order;
                @endphp
                {{$orderService->getOrderTotalWithBookingFee(true)}}
            </td>
        </tr>
        @if($order->event->organiser->charge_tax == 1)
        <tr>
            <td colspan="2"></td>
            <td>
                <strong>{{$order->event->organiser->tax_name}}</strong><em>({{$order->event->organiser->tax_value}}%)</em>
            </td>
            <td>
                {{$orderService->getTaxAmount(true)}}
            </td>
        </tr>
        @endif
        <tr>
            <td colspan="2"></td>
            <td><strong>Amount Paid</strong></td>
            <td>
                {{$orderService->getGrandTotal(true)}}
            </td>
        </tr>

        @if ($order->is_paid_points)
        <tr>
            <td colspan="2"></td>
            <td><strong>Paid with Points</strong></td>
            <td>
                <span class="text-success">{{ $order->points_deducted }} points used</span>
            </td>
        </tr>
        @endif
    </table>
    <table style="width:100%; margin:10px;">
        <tr>
            <td>
                <strong>Name</strong>
            </td>
            <td>
                <strong>Email</strong>
            </td>
            <td>
                <strong>Phone</strong>
            </td>
        </tr>
        @foreach($order->attendees as $attendee)
        <tr>
            <td>{{$attendee->full_name}}</td>
            <td>{{$attendee->email}}</td>
            <td>{{$attendee->mobile_no}}</td>
        </tr>
        @endforeach
    </table>
    <hr>
    <h6 style="text-align: right;">Generated on {{ $order->created_at->format('d F Y') }}</h6>
</div>
@stop