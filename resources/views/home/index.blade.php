@extends('layouts.app')

@section('content')
    <!--Main Slider Start-->
    <section class="main-slider-five clearfix">
        <div class="swiper-container thm-swiper__slider"
            data-swiper-options='{"slidesPerView": 1, "loop": true,
                "effect": "fade",
                "pagination": {
                "el": "#main-slider-pagination",
                "type": "bullets",
                "clickable": true
                },
                "navigation": {
                "nextEl": "#main-slider__swiper-button-next",
                "prevEl": "#main-slider__swiper-button-prev"
                },
                "autoplay": {
                "delay": 5000
                }}'>
            <div class="swiper-wrapper">

                @foreach ($slides as $slide)
                    @php
                        if (!empty($slide->description)) {
                            $sliders = explode('|', $slide->description);
                            if (count($sliders) > 1) {
                                $header = $sliders[0];
                                $description = $sliders[1];
                            } else {
                                $header = $sliders[0];
                                $description = '';
                            }
                        } else {
                            $header = '';
                            $description = '';
                        }
                        
                    @endphp

                    <div class="swiper-slide">
                        {{-- <div class="main-slider-five__img">
                            <img src="{{ asset('storage/category/' . $slide->cat_image) }}" alt="">
                        </div> --}}
                        <div class="main-slider-five__shape-1 float-bob-x">
                            <img src="{{ asset('frontend/images/update-10-02-2023/shapes/main-slider-five-shape-1.jpg') }}"
                                alt="">
                        </div>
                        <div class="main-slider-five__shape-2 float-bob-y">
                            <img src="{{ asset('frontend/images/update-10-02-2023/shapes/main-slider-five-shape-2.png') }}"
                                alt="">
                        </div>
                        <div class="container">
                            <div class="row">
                                <div class="col-xl-12">
                                    <div class="main-slider-five__content">
                                        <h2 class="main-slider-five__title">{!! $header !!}</h2>
                                        <p class="main-slider-five__text">{!! html_entity_decode($description) !!}</p>
                                        <div class="main-slider-five__btn-box">

                                            <a href="login" class="thm-btn-two main-slider-five__btn">Login
                                            </a>
                                            <br><br>
                                            <a href="register" class="thm-btn pricing__btn main-slider-five__btn">Register

                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach


            </div>

            <!-- If we need navigation buttons -->
            <div class="main-slider-five__nav">
                <div class="swiper-button-prev" id="main-slider__swiper-button-next">
                    <i class="icon-right-arrow"></i>
                </div>
                <div class="swiper-button-next" id="main-slider__swiper-button-prev">
                    <i class="icon-right-arrow1"></i>
                </div>
            </div>

        </div>
    </section>
    <!--Main Slider End-->


    <!--Services Five Start-->
    <section class="services-five">
        <div class="container">
            <div class="services-five__inner">
                <div class="section-title-three text-center">
                    <div class="section-title-three__sub-title-box">
                        <p class="section-title-three__sub-title">Our Top Services</p>
                        <div class="section-title-three__shape"></div>
                    </div>
                    <h2 class="section-title-three__title">Our Services are <br> Top Notch</h2>
                </div>
                <div class="owl-carousel thm-owl__carousel--range owl-theme services-five__carousel"
                    data-owl-options='{"loop": false,
                    "nav": false,
                    "autoWidth": true,
                    "navText": ["<span class=\"fa fa-angle-left\"></span>","<span class=\"fa fa-angle-right\"></span>"],
                    "dots": false,
                    "margin": 10,
                    "items": 1,
                    "smartSpeed": 700,
                    "responsive": {
                        "0": {
                            "margin": 30,
                            "items": 1,
                            "autoWidth": false
                        },
                        "768": {
                            "margin": 30,
                            "items": 2,
                            "autoWidth": false
                        },
                        "992": {
                            "margin": 30,
                            "items": 3
                        },
                        "1200": {
                            "margin": 30,
                            "items": 4
                        }
                    }}'>
                    <!--Services Five Single Start-->
                    <!--Services Five Single Start-->
                    <div class="item">
                        <div class="services-five__single">
                            <div class="services-five__img-box">
                                <div class="services-five__img">
                                    <img style="width:300px"
                                        src="{{ asset('frontend/images/update-10-02-2023/services/Airtime.jpg') }}"
                                        alt="">
                                    <div class="services-five__hover-content">
                                        <div class="services-five__hover-content-icon">
                                            <span class="insur-three-icon-home"></span>
                                        </div>
                                        <h4 class="services-five__hover-content-title"><a href="#">Airtime
                                            </a></h4>
                                        <p class="services-five__hover-content-text">Get up to 3% discount when you
                                            recharge
                                        </p>
                                    </div>
                                </div>
                                <div class="services-five__title-box">
                                    <h4 class="services-five__title"><a href="#">Airtime
                                        </a>
                                    </h4>
                                    <div class="services-five__icon">
                                        <span class="icon-family"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!--Services Five Single End-->
                    <!--Services Five Single Start-->
                    <div class="item">
                        <div class="services-five__single">
                            <div class="services-five__img-box">
                                <div class="services-five__img">
                                    <img style="width:300px"
                                        src="{{ asset('frontend/images/update-10-02-2023/services/Data.jpg') }}"
                                        alt="">
                                    <div class="services-five__hover-content">
                                        <div class="services-five__hover-content-icon">
                                            <span class="insur-three-icon-cross"></span>
                                        </div>
                                        <h4 class="services-five__hover-content-title"><a href="#">Data</a></h4>
                                        <p class="services-five__hover-content-text">Get internet Data for as low as 65
                                            Naira.</p>
                                    </div>
                                </div>
                                <div class="services-five__title-box">
                                    <h4 class="services-five__title"><a href="#">Data
                                        </a>
                                    </h4>
                                    <div class="services-five__icon">
                                        <span class="icon-heart-beat"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="item">

                        <div class="services-five__single">
                            <div class="services-five__img-box">
                                <div class="services-five__img">
                                    <img style="width:300px"
                                        src="{{ asset('frontend/images/update-10-02-2023/services/Electricity.jpg') }}"
                                        alt="">
                                    <div class="services-five__hover-content">
                                        <div class="services-five__hover-content-icon">
                                            <span class="insur-three-icon-car"></span>
                                        </div>
                                        <h4 class="services-five__hover-content-title"><a href="#">Electricity
                                                Bill
                                            </a></h4>
                                        <p class="services-five__hover-content-text">Pay Electricity Bill with ease
                                        </p>
                                    </div>
                                </div>
                                <div class="services-five__title-box">
                                    <h4 class="services-five__title"><a href="#">Electricity Bill</a>
                                    </h4>
                                    <div class="services-five__icon">
                                        <span class="icon-briefcase"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!--Services Five Single End-->
                    <!--Services Five Single Start-->
                    <div class="item">
                        <div class="services-five__single">
                            <div class="services-five__img-box">
                                <div class="services-five__img">
                                    <img style="width:300px"
                                        src="{{ asset('frontend/images/update-10-02-2023/services/cablesubscription.jpg') }}"
                                        alt="">
                                    <div class="services-five__hover-content">
                                        <div class="services-five__hover-content-icon">
                                            <span class="insur-three-icon-cardiogram"></span>
                                        </div>
                                        <h4 class="services-five__hover-content-title"><a href="#">Cable
                                                Subscription
                                            </a></h4>
                                        <p class="services-five__hover-content-text">Make Payment for your Dstv, Gotv,
                                            Startimes easily
                                        </p>
                                    </div>
                                </div>
                                <div class="services-five__title-box">
                                    <h4 class="services-five__title"><a href="#">Cable Subscription</a>
                                    </h4>
                                    <div class="services-five__icon">
                                        <span class="insur-three-icon-car"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!--Services Five Single End-->
                    <div class="item">
                        <div class="services-five__single">
                            <div class="services-five__img-box">
                                <div class="services-five__img">
                                    <img style="width:300px"
                                        src="{{ asset('frontend/images/update-10-02-2023/services/AitimetoCash.jpg') }}"
                                        alt="">
                                    <div class="services-five__hover-content">
                                        <div class="services-five__hover-content-icon">
                                            <span class="insur-three-icon-cardiogram"></span>
                                        </div>
                                        <h4 class="services-five__hover-content-title"><a href="#">Airtime to Cash

                                            </a></h4>
                                        <p class="services-five__hover-content-text">Convert your Airtime to Cash here
                                        </p>
                                    </div>
                                </div>
                                <div class="services-five__title-box">
                                    <h4 class="services-five__title"><a href="#">Airtime to Cash</a>
                                    </h4>
                                    <div class="services-five__icon">
                                        <span class="insur-three-icon-car"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="item">
                        <div class="services-five__single">
                            <div class="services-five__img-box">
                                <div class="services-five__img">
                                    <img style="width:300px"
                                        src="{{ asset('frontend/images/update-10-02-2023/services/bulk.jpg') }}"
                                        alt="">
                                    <div class="services-five__hover-content">
                                        <div class="services-five__hover-content-icon">
                                            <span class="insur-three-icon-cardiogram"></span>
                                        </div>
                                        <h4 class="services-five__hover-content-title"><a href="#">Bulk SMS &
                                                Education

                                            </a></h4>
                                        <p class="services-five__hover-content-text">Educational Token and Bulk SMS
                                            Services
                                        </p>
                                    </div>
                                </div>
                                <div class="services-five__title-box">
                                    <h4 class="services-five__title"><a href="#">Bulk SMS & Education</a>
                                    </h4>
                                    <div class="services-five__icon">
                                        <span class="insur-three-icon-car"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
                <div class="thm-owl__carousel--range__input"><input type="text" value="" name="range" />
                </div>
            </div>
        </div>
    </section>
    <!--Services Five End-->

    <!--About Six Start-->
    <section class="about-six">
        <div class="container">
            <div class="row">
                <div class="col-xl-6">
                    <div class="about-six__left">
                        <div class="about-six__img-box wow slideInLeft" data-wow-delay="100ms"
                            data-wow-duration="2500ms">
                            <div class="about-six__img">
                                <img src="{{ asset('frontend/images/update-10-02-2023/resources/aboutusimage.jpg') }}"
                                    alt="">
                            </div>
                            <div class="about-six__help">
                                <div class="about-six__help-icon">
                                    <span class="icon-insurance-2"></span>
                                </div>
                                <div class="about-six__help-content">
                                    <p class="about-six__help-text">We’re always here <br> to help you</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-6">
                    <div class="about-six__right">
                        <div class="section-title-three text-left">
                            <div class="section-title-three__sub-title-box">
                                <p class="section-title-three__sub-title">We are {{ config('app.siteName') }}</p>
                                <div class="section-title-three__shape"></div>
                            </div>
                            <h2 class="section-title-three__title">We’re Providing Best Data Services</h2>
                        </div>
                        <p class="about-six__text-1">Fast easy reliable way to purchase airtime, data, tv subscription and
                            more

                        </p>
                        <p class="about-six__text-2"> {!! Str::limit($settings->value, 350) !!}</p>
                        <div class="about-six__progress-ber">
                            <div class="about-six__progress-single">
                                <div class="about-six__progress-box">
                                    <div class="circle-progress"
                                        data-options='{ "value": 0.99,"thickness": 2,"emptyFill": "#f3f3f3","lineCap": "square", "size": 118, "fill": { "color": "#ff494a" } }'>
                                    </div><!-- /.circle-progress -->
                                    <div class="about-six__pack">
                                        <p>99%</p>
                                    </div>
                                </div>
                                <p class="about-six__progress-text">Service <br> Success Rates</p>
                            </div>
                            <div class="about-six__progress-single">
                                <div class="about-six__progress-box">
                                    <div class="circle-progress"
                                        data-options='{ "value": 0.95,"thickness": 2,"emptyFill": "#f3f3f3","lineCap": "square", "size": 118, "fill": { "color": "#ff494a" } }'>
                                    </div><!-- /.circle-progress -->
                                    <div class="about-six__pack">
                                        <p>95%</p>
                                    </div>
                                </div>
                                <p class="about-six__progress-text">Our Satisfied <br> Customers</p>
                            </div>
                            <div class="about-six__progress-single">
                                <div class="about-six__progress-box">
                                    <div class="circle-progress"
                                        data-options='{ "value": 0.3,"thickness": 2,"emptyFill": "#f3f3f3","lineCap": "square", "size": 118, "fill": { "color": "#ff494a" } }'>
                                    </div><!-- /.circle-progress -->
                                    <div class="about-six__pack">
                                        <p>30%</p>
                                    </div>
                                </div>
                                <p class="about-six__progress-text">Save Your <br> Money</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!--About Six End-->

    <!--Get Insurance Four Start-->
    <section class="get-insurance-four">
        <div class="get-insurance-four__shape-1"
            style="background-image: url({{ asset('frontend/images/update-10-02-2023/shapes/get-insurance-four-shape-1.png') }});">
        </div>
        <div class="get-insurance-four__shape-2 float-bob-y">
            <img src="{{ asset('frontend/images/update-10-02-2023/shapes/get-insurance-four-shape-2.png') }}"
                alt="">
        </div>
        <div class="container">
            <div class="section-title-three text-left">
                <div class="section-title-three__sub-title-box">
                    <p class="section-title-three__sub-title">Get Started</p>
                    <div class="section-title-three__shape"></div>
                </div>
                <h2 class="section-title-three__title">Follow 3 Easy Steps <br> to Get Started</h2>
            </div>
            <div class="get-insurance-four__main-tab-box tabs-box">
                <div class="row">
                    <div class="col-xl-6">
                        <ul class="tab-buttons clearfix list-unstyled">
                            <li data-tab="#car-insurance" class="tab-btn">
                                <div class="content-box">
                                    <div class="icon-box">
                                        <span class="insur-three-icon-car"></span>
                                    </div>
                                    <div class="text-box">
                                        <p>Register</p>
                                    </div>
                                </div>
                            </li>
                            <li data-tab="#life-insurance" class="tab-btn active-btn">
                                <div class="content-box">
                                    <div class="icon-box">
                                        <span class="insur-three-icon-cardiogram"></span>
                                    </div>
                                    <div class="text-box">
                                        <p>Fund wallet/buy from bank
                                        </p>
                                    </div>
                                </div>
                            </li>
                            <li data-tab="#home-insurance" class="tab-btn">
                                <div class="content-box">
                                    <div class="icon-box">
                                        <span class="insur-three-icon-home"></span>
                                    </div>
                                    <div class="text-box">
                                        <p>Make purchase

                                        </p>
                                    </div>
                                </div>
                            </li>

                        </ul>
                    </div>
                    <div class="col-xl-6">
                        <div class="tabs-content">
                            <!--tab-->
                            <div class="tab" id="car-insurance">
                                <div class="get-insurance-four__main-content">
                                    <div class="get-insurance-four__main-content-left">
                                        <h4 class="get-insurance-four__main-content-title">Register</h4>
                                        <p class="get-insurance-four__main-content-text">
                                            Its very easy to create an account by clicking on register. Fill the
                                            registration form, verify your email address and you are set
                                        </p>
                                        <div class="get-insurance-four__call">
                                            <div class="get-insurance-four__call-icon">
                                                <i class="fas fa-phone-alt"></i>
                                            </div>
                                            <div class="get-insurance-four__call-content">
                                                <p>Any complain? Please reach us</p>
                                                <a href="#">{{ config('app.sitePhone') }}</a>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>
                            <!--tab-->
                            <div class="tab active-tab" id="life-insurance">
                                <div class="get-insurance-four__main-content">
                                    <div class="get-insurance-four__main-content-left">
                                        <h4 class="get-insurance-four__main-content-title">Fund Wallet</h4>
                                        <p class="get-insurance-four__main-content-text">
                                            We provide the fastest and easiest ways to fund your wallet for
                                            transaction. You can use card payment or transfer to dedicated virtual account.
                                            You can also
                                            convert airtime to cash
                                        </p>
                                        <div class="get-insurance-four__call">
                                            <div class="get-insurance-four__call-icon">
                                                <i class="fas fa-phone-alt"></i>
                                            </div>
                                            <div class="get-insurance-four__call-content">
                                                <p>Any complain? Please reach us</p>
                                                <a href="#">{{ config('app.sitePhone') }}</a>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>
                            <!--tab-->
                            <div class="tab" id="home-insurance">
                                <div class="get-insurance-four__main-content">
                                    <div class="get-insurance-four__main-content-left">
                                        <h4 class="get-insurance-four__main-content-title">Pay Bills</h4>
                                        <p class="get-insurance-four__main-content-text">ou can now buy airtime, data, pay
                                            tv bills, electricity bills and more.
                                            The process is very straight forward with dedicated customer service to attend
                                            to you at any
                                            given time</p>
                                        <div class="get-insurance-four__call">
                                            <div class="get-insurance-four__call-icon">
                                                <i class="fas fa-phone-alt"></i>
                                            </div>
                                            <div class="get-insurance-four__call-content">
                                                <p>Any complain? Please reach us</p>
                                                <a href="#">{{ config('app.sitePhone') }}</a>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!--Get Insurance Four End-->
@endsection
