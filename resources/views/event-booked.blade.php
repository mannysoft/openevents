@extends('en.Emails.Layouts.Master')

@section('message_content')

@if(!empty($emailTemplate))
    {!! $emailTemplate !!}
@else
Hello {{$order->first_name }},<br>

<p>Thank you for your recent booking to join us at the forthcoming Global Woman Club event <strong>{{ $order->event->title }}</strong> event in <strong>{{$order->event->location}}</strong> on <strong>{{$order->event->start_date->format('d M Y H:i A')}}</strong>.</p>

<p>We are so excited you are joining us on <strong>{{$order->event->start_date->format('d M Y H:i A')}}</strong> for the <strong>{{$order->event->title}}</strong> in <strong>{{$order->event->location}}</strong>.</p>

<p>We are thrilled you will be joining us and if this is your first experience of Global Woman we would like to extend a very warm welcome to you!</p>

<p>Please watch this space for further details which will be emailed to you as the event approaches.</p>

<p>Should you have any questions in the meantime, please reach out to <a href="mailto:support@globalwomanclub.com">support@globalwomanclub.com</a></p>

<p>Please note Global Woman event tickets are non refundable. Email <a href="mailto:support@globalwomanclub.com">support@globalwomanclub.com</a> should you wish to reschedule.</p>

<p>Look forward to meeting you there!</p>


<p>Regards,</p>

<p><strong>Global Woman Team</strong></p>
@endif

<br>
<div style="text-align:center;">
    {{--
    <a href="{!! route('showOrderDetails', ['order_reference' => $order->order_reference]) !!}" style="display: inline-block;padding: 8px;background-color: red;color: #fff;border-radius: 25px;text-decoration: none;width: 160px;border: solid 2px red;">View Ticket</a>
    --}}
</div>

@if($order->stripe_account_id == null)
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
@endif
@stop