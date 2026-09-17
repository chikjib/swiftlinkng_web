<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>{{ config('app.name', 'Laravel') }}</title>
    <meta name="title" content="Swiftlinkng - Get Connected with ease">
    <meta name="description" content="Buy data, Buy airtime and Pay Bills @ a discount with Swiftlinkng. Connect at ease with loved ones and business.">
    <meta name="keywords" content="Buy data, Buy airtime, Pay Bills">
    <meta name="robots" content="index, follow">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta name="language" content="English">
    <meta name="author" content="swiftlinkng.com" />
    <!-- favicons Icons -->
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('frontend/images/swiftlogo.png') }}" />
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('frontend/images/swiftlogo.png') }}" />
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('frontend/images/swiftlogo.png') }}" />
    <link rel="manifest" href="{{ asset('frontend/images/favicons/site.webmanifest') }}" />
    <!-- fonts -->
    <meta http-equiv="refresh" content="{{ config('session.lifetime') * 60 }}">
    <link rel="preconnect" href="https://fonts.googleapis.com/">

    <link rel="preconnect" href="https://fonts.gstatic.com/" crossorigin>

    <link
        href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,wght@0,400;0,500;0,700;1,400;1,500;1,700&amp;display=swap"
        rel="stylesheet">

    <link rel="stylesheet" href="{{ asset('frontend/vendors/bootstrap/css/bootstrap.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('frontend/vendors/animate/animate.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('frontend/vendors/animate/custom-animate.css') }}" />
    <link rel="stylesheet" href="{{ asset('frontend/vendors/fontawesome/css/all.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('frontend/vendors/jarallax/jarallax.css') }}" />
    <link rel="stylesheet" href="{{ asset('frontend/vendors/jquery-magnific-popup/jquery.magnific-popup.css') }}" />
    <link rel="stylesheet" href="{{ asset('frontend/vendors/nouislider/nouislider.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('frontend/vendors/nouislider/nouislider.pips.css') }}" />
    <link rel="stylesheet" href="{{ asset('frontend/vendors/odometer/odometer.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('frontend/vendors/swiper/swiper.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('frontend/vendors/insur-icons/style.css') }}">
    <link rel="stylesheet" href="{{ asset('frontend/vendors/insur-two-icon/style.css') }}">
    <link rel="stylesheet" href="{{ asset('frontend/vendors/tiny-slider/tiny-slider.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('frontend/vendors/reey-font/stylesheet.css') }}" />
    <link rel="stylesheet" href="{{ asset('frontend/vendors/owl-carousel/owl.carousel.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('frontend/vendors/owl-carousel/owl.theme.default.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('frontend/vendors/bxslider/jquery.bxslider.css') }}" />
    <link rel="stylesheet" href="{{ asset('frontend/vendors/bootstrap-select/css/bootstrap-select.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('frontend/vendors/vegas/vegas.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('frontend/vendors/jquery-ui/jquery-ui.css') }}" />
    <link rel="stylesheet" href="{{ asset('frontend/vendors/timepicker/timePicker.css') }}" />
    <link rel="stylesheet" href="{{ asset('frontend/vendors/ion.rangeSlider/css/ion.rangeSlider.min.css') }}">
    <link rel="stylesheet" href="{{ asset('frontend/vendors/insur-three-icon/style.css') }}">
    <!-- template styles -->
    <link rel="stylesheet" id="langLtr" href="{{ asset('frontend/css/insur.css') }}" />
    <link rel="stylesheet" href="{{ asset('frontend/css/insur-responsive.css') }}" />
    <link rel="stylesheet" href="{{ asset('frontend/css/color-3.css') }}" />
    <script>
        (function () {
            var resetKey = 'swiftlink-ios-cache-reset-20260916';
            try {
                if (window.localStorage.getItem(resetKey)) return;
                window.localStorage.setItem(resetKey, '1');
            } catch (error) {
                return;
            }

            var cleanup = [];
            if ('serviceWorker' in navigator && navigator.serviceWorker.getRegistrations) {
                cleanup.push(navigator.serviceWorker.getRegistrations().then(function (registrations) {
                    return Promise.all(registrations.map(function (registration) {
                        return registration.unregister();
                    }));
                }));
            }
            if ('caches' in window && window.caches.keys) {
                cleanup.push(window.caches.keys().then(function (keys) {
                    return Promise.all(keys.map(function (key) {
                        return window.caches.delete(key);
                    }));
                }));
            }
            if (cleanup.length) {
                Promise.all(cleanup).then(function () { window.location.reload(); });
            }
        }());
    </script>
    @vite(['resources/sass/swiftlink.scss', 'resources/js/app.js'])
    <style>
        :root {
            --swift-auth-logo: url("{{ asset('frontend/images/swiftlogo.png') }}");
        }
    </style>

    <!-- Google Tag Manager -->
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','GTM-PRLDVTLX');</script>
<!-- End Google Tag Manager -->

</head>

<body>
    <!-- Google Tag Manager (noscript) -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-PRLDVTLX"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<!-- End Google Tag Manager (noscript) -->


    <div class="page-wrapper">

        @include('layouts.header')

        <div id="app">


        </div>

        @include('layouts.footer')
    </div>


    <div class="mobile-nav__wrapper">
        <div class="mobile-nav__overlay mobile-nav__toggler"></div>
        <!-- /.mobile-nav__overlay -->
        <div class="mobile-nav__content mobile-nav__content--three">
            <span class="mobile-nav__close mobile-nav__toggler"><i class="fa fa-times"></i></span>

            <div class="logo-box">
                <a href="/" aria-label="logo image"><img src="{{ asset('frontend/images/swiftlinklogo.png') }}"
                        width="143" alt="" /></a>
            </div>
            <!-- /.logo-box -->
            <div class="mobile-nav__container"></div>
            <!-- /.mobile-nav__container -->

            <ul class="mobile-nav__contact mobile-nav__contact--three list-unstyled">
                <li>
                    <i class="fa fa-envelope"></i>
                    <a href="#">{{ config('app.siteEmail') }}</a>
                </li>
                <li>
                    <i class="fa fa-phone-alt"></i>
                    <a href="#">{{ config('app.sitePhone') }}</a>
                </li>
            </ul><!-- /.mobile-nav__contact -->
            <div class="mobile-nav__top">

                <div class="mobile-nav__social">
                    <a href="https://twitter.com/{{ $data['twitter'] }}"><i class="fab fa-twitter"></i></a>
                    <a href="https://facebook.com/{{ $data['facebook'] }}"><i class="fab fa-facebook"></i></a>
                    <a href="https://instagram.com/{{ $data['instagram'] }}"><i class="fab fa-instagram"></i></a>
                </div><!-- /.mobile-nav__social -->
            </div><!-- /.mobile-nav__top -->



        </div>
        <!-- /.mobile-nav__content -->
    </div>
    <!-- /.mobile-nav__wrapper -->




    <a href="#" data-target="html" class="scroll-to-target scroll-to-top"><i class="fa fa-angle-up"></i></a>
    <script src="{{ asset('frontend/vendors/jquery/jquery-3.6.0.min.js') }}"></script>
    <script src="{{ asset('frontend/vendors/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('frontend/vendors/jarallax/jarallax.min.js') }}"></script>
    <script src="{{ asset('frontend/vendors/jquery-ajaxchimp/jquery.ajaxchimp.min.js') }}"></script>
    <script src="{{ asset('frontend/vendors/jquery-appear/jquery.appear.min.js') }}"></script>
    <script src="{{ asset('frontend/vendors/jquery-circle-progress/jquery.circle-progress.min.js') }}"></script>
    <script src="{{ asset('frontend/vendors/jquery-magnific-popup/jquery.magnific-popup.min.js') }}"></script>
    <script src="{{ asset('frontend/vendors/jquery-validate/jquery.validate.min.js') }}"></script>
    <script src="{{ asset('frontend/vendors/nouislider/nouislider.min.js') }}"></script>

    <script src="{{ asset('frontend/vendors/odometer/odometer.min.js') }}"></script>
    <script src="{{ asset('frontend/vendors/swiper/swiper.min.js') }}"></script>
    <script src="{{ asset('frontend/vendors/tiny-slider/tiny-slider.min.js') }}"></script>
    <script src="{{ asset('frontend/vendors/wnumb/wNumb.min.js') }}"></script>
    <script src="{{ asset('frontend/vendors/wow/wow.js') }}"></script>
    <script src="{{ asset('frontend/vendors/isotope/isotope.js') }}"></script>
    <script src="{{ asset('frontend/vendors/countdown/countdown.min.js') }}"></script>
    <script src="{{ asset('frontend/vendors/owl-carousel/owl.carousel.min.js') }}"></script>

    <script src="{{ asset('frontend/vendors/bxslider/jquery.bxslider.min.js') }}"></script>
    <script src="{{ asset('frontend/vendors/bootstrap-select/js/bootstrap-select.min.js') }}"></script>
    <script src="{{ asset('frontend/vendors/vegas/vegas.min.js') }}"></script>
    <script src="{{ asset('frontend/vendors/jquery-ui/jquery-ui.js') }}"></script>
    <script src="{{ asset('frontend/vendors/timepicker/timePicker.js') }}"></script>
    <script src="{{ asset('frontend/vendors/circleType/jquery.circleType.js') }}"></script>
    <script src="{{ asset('frontend/vendors/circleType/jquery.lettering.min.js') }}"></script>
    <script src="{{ asset('frontend/vendors/ion.rangeSlider/js/ion.rangeSlider.min.js') }}"></script>



    <!-- template js -->
    <script src="{{ asset('frontend/js/insur.js') }}"></script>
</body>

</html>
