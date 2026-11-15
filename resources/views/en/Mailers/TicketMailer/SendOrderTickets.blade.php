@extends('en.Emails.Layouts.Master')

@section('message_content')
Hello {{$order->full_name}},<br><br>

Your order for the event <strong>{{$order->event->title}}</strong> was successful.<br><br>

Your tickets are attached to this email. You can also view you order details and download your tickets at:
<br>
<br>
<div style="text-align:center;">
  <a href="{!! route('showOrderDetails', ['order_reference' => $order->order_reference]) !!}" style="display: inline-block;padding: 8px;background-color: red;color: #fff;border-radius: 25px;text-decoration: none;width: 160px;border: solid 2px red;">View Ticket</a>
</div>

@if(!$order->is_payment_received)
<br><br>
<strong>Please note: This order still requires payment. Instructions on how to make payment can be found on your
    order page: {{route('showOrderDetails', ['order_reference' => $order->order_reference])}}</strong>
<br><br>
@endif
<h3>Order Details</h3>
Order Reference: <strong>{{$order->order_reference}}</strong><br>
Order Name: <strong>{{$order->full_name}}</strong><br>
Order Date: <strong>{{$order->created_at->format(config('attendize.default_datetime_format'))}}</strong><br>
Order Email: <strong>{{$order->email}}</strong>
<br><br>
<div style="text-align:center;">
<a href="{!! route('downloadCalendarIcs', ['event_id' => $order->event->id]) !!}" style="padding: 8px;color: #8c8c8c;border: solid 2px #cacaca;border-radius: 25px;text-decoration: none;display: inline-block;width: 160px;">Add To Calendar</a>
</div>

@if ($order->is_business)
<h3>Business Details</h3>
@if ($order->business_name) @lang("Public_ViewEvent.business_name"): <strong>{{$order->business_name}}</strong><br>@endif
@if ($order->business_tax_number) @lang("Public_ViewEvent.business_tax_number"): <strong>{{$order->business_tax_number}}</strong><br>@endif
@if ($order->business_address_line_one) @lang("Public_ViewEvent.business_address_line1"): <strong>{{$order->business_address_line_one}}</strong><br>@endif
@if ($order->business_address_line_two) @lang("Public_ViewEvent.business_address_line2"): <strong>{{$order->business_address_line_two}}</strong><br>@endif
@if ($order->business_address_state_province) @lang("Public_ViewEvent.business_address_state_province"): <strong>{{$order->business_address_state_province}}</strong><br>@endif
@if ($order->business_address_city) @lang("Public_ViewEvent.business_address_city"): <strong>{{$order->business_address_city}}</strong><br>@endif
@if ($order->business_address_code) @lang("Public_ViewEvent.business_address_code"): <strong>{{$order->business_address_code}}</strong><br>@endif
@endif

<h3>Order Items</h3>
<div style="padding:10px; background: #F9F9F9; border: 1px solid #f1f1f1;">
    <table style="width:100%; margin:10px;">
        <tr>
            <td>
                <strong>Ticket</strong>
            </td>
            <td>
                <strong>Qty.</strong>
            </td>
            <td>
                <strong>Price</strong>
            </td>
            <td>
                <strong>Fee</strong>
            </td>
            <td>
                <strong>Total</strong>
            </td>
        </tr>
        @foreach($order->orderItems as $order_item)
        <tr>
            <td>{{$order_item->title}}</td>
            <td>{{$order_item->quantity}}</td>
            <td>
                @isFree($order_item->unit_price)
                FREE
                @else
                {{money($order_item->unit_price, $order->event->currency)}}
                @endif
            </td>
            <td>
                @isFree($order_item->unit_price)
                -
                @else
                {{money($order_item->unit_booking_fee, $order->event->currency)}}
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
            <td colspan="3"></td>
            <td><strong>Sub Total</strong></td>
            <td colspan="2">
                {{$orderService->getOrderTotalWithBookingFee(true)}}
            </td>
        </tr>
        @if($order->event->organiser->charge_tax == 1)
        <tr>
            <td colspan="3"></td>
            <td>
                <strong>{{$order->event->organiser->tax_name}}</strong><em>({{$order->event->organiser->tax_value}}%)</em>
            </td>
            <td colspan="2">
                {{$orderService->getTaxAmount(true)}}
            </td>
        </tr>
        @endif
        <tr>
            <td colspan="3"></td>
            <td><strong>Total</strong></td>
            <td colspan="2">
                {{$orderService->getGrandTotal(true)}}
            </td>
        </tr>
    </table>
    <br><br>
</div>
<br><br>
<table width="600" align="center" border="0" cellpadding="0" cellspacing="0" style="min-width: 600px">
  <tr>
    <td style="line-height: 1px;height: 1px;font-size: 0px;border-bottom: 1px solid #d7d4d3">&nbsp;</td>
  </tr>

  <tr>
    <td height="17"></td>
  </tr>

  <tr>
    <td align="center">
      <table width="600" cellpadding="0" cellspacing="0" border="0" style="min-width: 600px">
        <tr>
          <th width="100" align="center">
            <table width="100" border="0" cellpadding="0" cellspacing="0">
              <tr>
                <td><img width="100" alt="img" src="https://davxegdvkjbks.cloudfront.net/emailtemplate.jpg"></td>
              </tr>
            </table>
          </th>

          <th width="17"></th>

          <th width="483" align="center">
            <table width="483" border="0" cellpadding="0" cellspacing="0">
              <tr>
                <td style="font-family: 'Roboto', sans-serif;font-size: 14px;color: #333333;font-weight: 500;">
                  Mirela Sula
                </td>
              </tr>
              <tr>
                <td align="left" style="font-family: 'Roboto', sans-serif;font-size: 14px;color: #333333;font-weight: 400;">
                  Founder of Global Woman
                </td>
              </tr>
              <tr>
                <td align="left" style="font-family: 'Roboto', sans-serif;font-size: 14px;color: #333333;font-weight: 400;">
                  CEO of Business Woman Today
                </td>
              </tr>
              <tr>
                <td align="left" style="font-family: 'Roboto', sans-serif;font-size: 14px;color: #333333;font-weight: 400;">
                  mirela@globalwomanclub.com
                </td>
              </tr>
              <tr>
                <td align="left" style="font-family: 'Roboto', sans-serif;font-size: 14px;color: #333333;font-weight: 400;">
                  +44 (0) 2036049575
                </td>
              </tr>
              <tr>
                <td align="left" style="font-family: 'Roboto', sans-serif;font-size: 14px;color: #333333;font-weight: 400;">
                  globalwomanclub.com
                </td>
              </tr>
            </table>
          </th>
        </tr>
      </table>
    </td>
  </tr>

  <tr>
    <td height="17"></td>
  </tr>

  <tr>
    <td style="line-height: 1px;height: 1px;font-size: 0px;border-bottom: 1px solid #d7d4d3">&nbsp;</td>
  </tr>

  <tr>
    <td height="14"></td>
  </tr>

  <tr>
    <td align="center" style="font-family: 'Roboto', sans-serif;font-size: 13px;color: #333333;font-weight: 400;">
      Jubilee House, 213 , Oxford Street, London , W1D 2LF | 44 (0) 20 3604 9575
    </td>
  </tr>
</table>
@stop
