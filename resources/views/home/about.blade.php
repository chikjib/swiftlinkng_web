@extends('layouts.app')

@section('content')
    <br>
    <br>
    <!--About Four Start-->
    <section class="about-four">
        <div class="container">
            <div class="row">
                <div class="col-xl-6">
                    <div class="about-four__left">
                        <div class="about-four__img-box wow slideInLeft" data-wow-delay="100ms" data-wow-duration="2500ms">
                            <div class="about-four__img">
                                <img src="{{ asset('frontend/images/swiftlogo.png') }}" width="60px" alt="">
                            </div>

                        </div>
                    </div>
                </div>
                <div class="col-xl-6">
                    <div class="about-four__right">
                        <div class="section-title text-left">
                            <div class="section-sub-title-box">
                                <p class="section-sub-title">About us</p>
                                <div class="section-title-shape-1">
                                    <img src="{{ asset('frontend/images/shapes/section-title-shape-1.png') }}"
                                        alt="">
                                </div>
                                <div class="section-title-shape-2">
                                    <img src="{{ asset('frontend/images/shapes/section-title-shape-2.png') }}"
                                        alt="">
                                </div>
                            </div>
                            <h2 class="section-title__title">More about us</h2>
                        </div>
                        <p class="about-four__text-2"> {!! $settings->value !!}</p>

                    </div>
                </div>
            </div>
        </div>
    </section>
    <!--About Four End-->
@endsection
