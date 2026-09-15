<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="title" content="Swiftlinkng - Get Connected with ease">
    <meta name="description" content="Buy data, Buy airtime and Pay Bills @ a discount with Swiftlinkng. Connect at ease with loved ones and business.">
    <meta name="keywords" content="Buy data, Buy airtime, Pay Bills">
    <meta name="robots" content="index, follow">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta name="language" content="English">
    <meta name="author" content="swiftlinkng.com" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('frontend/images/swiftlogo.png') }}" />
    <meta http-equiv="refresh" content="{{ config('session.lifetime') * 60 }}">
    <title>{{ config('app.name', 'Laravel') }}</title>
    <link href="{{ asset('template/vendor/fontawesome-free/css/all.min.css') }}" rel="stylesheet" type="text/css">
    <link href="{{ asset('template/vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet" type="text/css">
    <link href="{{ asset('template/css/ruang-admin.min.css') }}" rel="stylesheet">
    @vite(['resources/sass/swiftlink.scss', 'resources/js/app.js'])
    <style>
        a, a:hover, a:focus, a:active { text-decoration: none !important; }
        .swift-admin-shell .swift-topbar-leading,
        .swift-admin-shell .swift-app-downloads { display: flex; align-items: center; gap: 8px; }
        .swift-admin-shell .swift-app-downloads a { display: inline-flex; align-items: center; padding: 5px 7px; background: #fff; border: 1px solid #e3e6f0; border-radius: 9px; }
        .swift-admin-shell .swift-app-downloads .swift-store-badge { display: block !important; width: 100px !important; min-width: 100px; max-width: none !important; height: auto !important; object-fit: contain; opacity: 1 !important; visibility: visible !important; }
        .swift-admin-shell .swift-app-downloads .swift-store-icon { display: none; }
        .swift-admin-shell .alert-danger { color: #fff !important; background: #9f0711 !important; border-color: #79040b !important; }
        .swift-admin-shell .alert-success { color: #fff !important; background: #087a34 !important; border-color: #055b26 !important; }
        .swift-admin-shell .alert-danger a,
        .swift-admin-shell .alert-danger i,
        .swift-admin-shell .alert-danger .close,
        .swift-admin-shell .alert-success a,
        .swift-admin-shell .alert-success i,
        .swift-admin-shell .alert-success .close { color: #fff !important; }
        .swift-admin-edit-user { align-items: flex-start; }
        .swift-admin-edit-user > [class*="col-"] { min-width: 0; }
        .swift-admin-edit-user .card-header > .col-md-12 { padding: 0; }
        .swift-admin-shell .table-responsive { max-width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; }
        .swift-admin-shell .swift-admin-scroll-table-wrap { width: 100%; max-width: 100%; overflow-x: auto !important; overflow-y: hidden; }
        .swift-admin-shell .swift-admin-scroll-table { width: max-content !important; min-width: 100%; table-layout: auto !important; white-space: nowrap !important; }
        .swift-admin-shell .swift-admin-scroll-table th,
        .swift-admin-shell .swift-admin-scroll-table td,
        .swift-admin-shell .swift-admin-scroll-table .box,
        .swift-admin-shell .swift-admin-scroll-table .swift-reference-cell { width: auto !important; max-width: none !important; white-space: nowrap !important; overflow-wrap: normal !important; word-break: normal !important; }
        .swift-admin-shell .swift-admin-scroll-table .btn-group { flex-wrap: nowrap; }
        .swift-admin-shell .swift-admin-transactions-table {
            width: 100% !important;
            min-width: 1050px !important;
            table-layout: fixed !important;
            white-space: normal !important;
        }
        .swift-admin-shell .swift-admin-transactions-table th,
        .swift-admin-shell .swift-admin-transactions-table td,
        .swift-admin-shell .swift-admin-transactions-table .box,
        .swift-admin-shell .swift-admin-transactions-table .swift-reference-cell {
            width: auto !important;
            max-width: 120px !important;
            padding: 5px 4px !important;
            line-height: 1.3;
            white-space: normal !important;
            overflow-wrap: anywhere !important;
            word-break: break-word !important;
        }
        .swift-admin-shell .swift-admin-transactions-table th:last-child,
        .swift-admin-shell .swift-admin-transactions-table td:last-child {
            width: 120px !important;
            max-width: 120px !important;
        }
        .swift-admin-shell .swift-admin-transactions-table .btn-group {
            flex-wrap: wrap;
            gap: 4px;
        }
        .swift-admin-shell .swift-admin-transactions-table .swift-col-user { width: 155px !important; max-width: 155px !important; }
        .swift-admin-shell .swift-admin-transactions-table .swift-col-description { width: 165px !important; max-width: 165px !important; }
        .swift-admin-shell .swift-admin-transactions-table .swift-col-phone { width: 110px !important; max-width: 110px !important; }
        .swift-admin-shell .swift-admin-transactions-table .swift-col-response { width: 165px !important; max-width: 165px !important; }
        .swift-admin-shell .swift-admin-transactions-table .swift-col-iuc-meter { width: 105px !important; max-width: 105px !important; }
        .swift-admin-shell .swift-admin-transactions-table .swift-col-created-at { width: 125px !important; max-width: 125px !important; }
        .swift-admin-shell .swift-all-transactions-table { width: 1760px !important; min-width: 1760px !important; table-layout: fixed !important; }
        .swift-admin-shell .swift-all-transactions-table .swift-col-reference { width: 125px !important; max-width: 125px !important; }
        .swift-admin-shell .swift-all-transactions-table .swift-col-user { width: 190px !important; max-width: 190px !important; }
        .swift-admin-shell .swift-all-transactions-table .swift-col-type { width: 95px !important; max-width: 95px !important; }
        .swift-admin-shell .swift-all-transactions-table .swift-col-description { width: 210px !important; max-width: 210px !important; }
        .swift-admin-shell .swift-all-transactions-table .swift-col-amount { width: 100px !important; max-width: 100px !important; }
        .swift-admin-shell .swift-all-transactions-table .swift-col-phone { width: 135px !important; max-width: 135px !important; }
        .swift-admin-shell .swift-all-transactions-table .swift-col-iuc-meter { width: 120px !important; max-width: 120px !important; }
        .swift-admin-shell .swift-all-transactions-table .swift-col-balance { width: 95px !important; max-width: 95px !important; }
        .swift-admin-shell .swift-all-transactions-table .swift-col-prev-balance { width: 105px !important; max-width: 105px !important; }
        .swift-admin-shell .swift-all-transactions-table .swift-col-response { width: 220px !important; max-width: 220px !important; }
        .swift-admin-shell .swift-all-transactions-table .swift-col-status { width: 90px !important; max-width: 90px !important; }
        .swift-admin-shell .swift-all-transactions-table .swift-col-created-at { width: 155px !important; max-width: 155px !important; }
        .swift-admin-shell .swift-all-transactions-table .swift-col-action { width: 120px !important; max-width: 120px !important; }
        .swift-admin-shell .swift-unconfirmed-transactions-table { width: 1845px !important; min-width: 1845px !important; table-layout: fixed !important; }
        .swift-admin-shell .swift-unconfirmed-transactions-table .swift-col-select { width: 45px !important; max-width: 45px !important; text-align: center; }
        .swift-admin-shell .swift-unconfirmed-transactions-table .swift-col-reference { width: 125px !important; max-width: 125px !important; }
        .swift-admin-shell .swift-unconfirmed-transactions-table .swift-col-user { width: 190px !important; max-width: 190px !important; }
        .swift-admin-shell .swift-unconfirmed-transactions-table .swift-col-type { width: 95px !important; max-width: 95px !important; }
        .swift-admin-shell .swift-unconfirmed-transactions-table .swift-col-description { width: 210px !important; max-width: 210px !important; }
        .swift-admin-shell .swift-unconfirmed-transactions-table .swift-col-amount { width: 100px !important; max-width: 100px !important; }
        .swift-admin-shell .swift-unconfirmed-transactions-table .swift-col-phone { width: 135px !important; max-width: 135px !important; }
        .swift-admin-shell .swift-unconfirmed-transactions-table .swift-col-iuc-meter { width: 120px !important; max-width: 120px !important; }
        .swift-admin-shell .swift-unconfirmed-transactions-table .swift-col-balance { width: 95px !important; max-width: 95px !important; }
        .swift-admin-shell .swift-unconfirmed-transactions-table .swift-col-prev-balance { width: 105px !important; max-width: 105px !important; }
        .swift-admin-shell .swift-unconfirmed-transactions-table .swift-col-response { width: 220px !important; max-width: 220px !important; }
        .swift-admin-shell .swift-unconfirmed-transactions-table .swift-col-status { width: 90px !important; max-width: 90px !important; }
        .swift-admin-shell .swift-unconfirmed-transactions-table .swift-col-created-at { width: 155px !important; max-width: 155px !important; }
        .swift-admin-shell .swift-unconfirmed-transactions-table .swift-col-action { width: 160px !important; max-width: 160px !important; }
    </style>

    <!-- Meta Pixel Code -->
    <script>
        ! function(f, b, e, v, n, t, s) {
            if (f.fbq) return;
            n = f.fbq = function() {
                n.callMethod ?
                    n.callMethod.apply(n, arguments) : n.queue.push(arguments)
            };
            if (!f._fbq) f._fbq = n;
            n.push = n;
            n.loaded = !0;
            n.version = '2.0';
            n.queue = [];
            t = b.createElement(e);
            t.async = !0;
            t.src = v;
            s = b.getElementsByTagName(e)[0];
            s.parentNode.insertBefore(t, s)
        }(window, document, 'script',
            'https://connect.facebook.net/en_US/fbevents.js');
        fbq('init', '1189822208568969');
        fbq('track', 'PageView');
    </script>
    <noscript><img height="1" width="1" style="display:none"
            src="https://www.facebook.com/tr?id=1189822208568969&ev=PageView&noscript=1" /></noscript>
    <!-- End Meta Pixel Code -->


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



    <div id="app">


    </div>


    <!-- Scroll to top -->
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>
    <script src="{{ asset('template/vendor/jquery/jquery.min.js') }}"></script>
    <script src="{{ asset('template/vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('template/vendor/jquery-easing/jquery.easing.min.js') }}"></script>
    <script src="{{ asset('template/js/ruang-admin.js') }}"></script>
</body>

</html>
