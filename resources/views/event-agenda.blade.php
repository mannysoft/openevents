@extends('en.Emails.Layouts.Master')

@section('message_content')
Hello {{$order->first_name }},<br>

<p>We are so excited you are joining us on <strong>{{$order->event->start_date->format('d M Y H:i A')}}</strong> for the <strong>{{$order->event->title}}</strong> in <strong>{{$order->event->location}}</strong>.</p>

<p>Get ready to step into a world of inspiration, networking, and create a collaboration between Global Woman Club and Global Man Club. </p>

<p><strong>Venue: {{ $order->event->location }}</strong></p>

<p><strong>Time: {{$order->event->start_date->format('H:i')}} - {{$order->event->end_date->format('H:i')}}</strong></p>

<p>This event is the perfect blend of learning, networking, business, and entertainment.</p>

<p><strong>What to Expect:</strong></p>

<p><strong>International Networking:</strong> Engage with a diverse group of women investors, entrepreneurs, female founders and business owners. <strong>Build relationships, forge connections, and collaborate</strong> in an atmosphere buzzing with potential.</p>

<p><strong>Insightful Keynotes & Panels</strong> with <strong>industry leaders</strong>, will provide <strong>valuable insights</strong>. These sessions are designed to <strong>provoke thought and inspire action.</strong></p>

<p><strong>Start-up Pitches & Motivational Talks:</strong> Witness <strong>ground-breaking start-up pitches</strong> and be motivated by speakers who will share their journeys, filling you with passion and drive.</p>

<p><strong>Photo Booth:</strong> Dress to impress! We're rolling out the red carpet and <strong>setting up a professional photo booth.</strong> Capture the moments and <strong>cherish them forever.</strong></p>

<p><strong>Prizes & Surprises:</strong> Stay tuned for a host of <strong>exciting rewards and surprises</strong> that will add an extra layer of fun to your experience.</p>

<p>This event isn't just a networking, it's a <strong>nexus of global thinkers, innovators, and leaders.</strong> A day to remember, a day that <strong>could change your life.</strong></p>

<p><strong>Dress Code:</strong> Ladies, please bring your high heels with you, colors and lipstick. Remember to come dressed in your finest. It's not just an event; it's a statement of style and substance.</p>

<p><strong>Please find the agenda as below</strong></p>


<table width="600" align="center" border="0" cellpadding="4" cellspacing="1" style="min-width: 600px">
    <tr>
        <td style="width: 30%;">08.30</td>
        <td>Registration, Coffee Morning & Networking
            Photos, videos - Social media
        </td>
    </tr>

    <tr>
        <td style="width: 30%;">09.30</td>
        <td>Welcome - Mirela Sula - CEO of Global
            Woman <br>
            Topic: How To Grow Your Business Globally
        </td>
    </tr>

    <tr>
        <td style="width: 30%;">10.30</td>
        <td>Guest Speakers
        </td>
    </tr>

    <tr>
        <td style="width: 30%;">11.00</td>
        <td>The Members Pitch - Part 1
        </td>
    </tr>

    <tr>
        <td style="width: 30%;">11.15</td>
        <td>Break & Networking
        </td>
    </tr>

    <tr>
        <td style="width: 30%;">12.00</td>
        <td>The Member's pitch
        </td>
    </tr>

    <tr>
        <td style="width: 30%;">12.30</td>
        <td>Rafle & Networking
        </td>
    </tr>

    <tr>
        <td style="width: 30%;">13.00</td>
        <td>Group Lunch
        </td>
    </tr>

</table>

<p>Please email <a href="mailto:pa@globalwomanclub.com">pa@globalwomanclub.com</a> if you would like to reserve a place to join the influencer lunch</p>

<p>Looking forward to seeing you soon!</p>

<p>Best Regards,</p>

<p><strong>Global Woman Team</strong></p>

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