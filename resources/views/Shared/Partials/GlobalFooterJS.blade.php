@if(session()->get('message'))
    <script>showMessage('{{\Session::get('message')}}');</script>
@endif

@if(config('attendize.google_analytics_id'))
<script>
        (function (i, s, o, g, r, a, m) {
            i['GoogleAnalyticsObject'] = r;
            i[r] = i[r] || function () {
                        (i[r].q = i[r].q || []).push(arguments)
                    }, i[r].l = 1 * new Date();
            a = s.createElement(o),
                    m = s.getElementsByTagName(o)[0];
            a.async = 1;
            a.src = g;
            m.parentNode.insertBefore(a, m)
        })(window, document, 'script', '//www.google-analytics.com/analytics.js', 'ga');

        ga('create', '{{config('attendize.google_analytics_id')}}', 'auto');
        ga('require', 'displayfeatures');
        ga('send', 'pageview');
</script>
<!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=G-5T8FWW4Z1P"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'G-5T8FWW4Z1P');
</script>

@endif
