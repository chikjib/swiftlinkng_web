@extends('layouts.app')

@section('content')
    <br>
    <br>



    <section class="contact-page">
        <div class="container">
            <div class="row">
                <div class="row">
                    <div class="col-lg-12 col-md-12">
                        <div class="section-title-1 text-center">
                            <h1 class="text-blue">Let's Get in Touch!</h1>
                            <div class="title-line"></div>
                            <p>Feel free to reach us with the details below</p>
                        </div>
                    </div>
                </div>
                @php
                    $setting = explode('|', $settings->value);
                    $address = $setting[0];
                    $phone = $setting[1];
                    $email = $setting[2];
                    
                @endphp
                @php
                    $settings = \App\Models\Setting::where('key', 'SOCIAL')->first();
                    $setting2 = explode('|', $settings->value);
                    $facebook = $setting2[0];
                    $instagram = $setting2[1];
                    $twitter = $setting2[2];
                    
                @endphp

                <div class="row">
                    <div class="col-lg-4 col-md-4 col-sm-4">
                        <div class="contact-box text-center">
                            <i class="fa fa-map-marker"></i>
                            <h3 class="mt-20">Address</h3>
                            <p class="mt-20">{{ $address }} </p>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-4 col-sm-4">
                        <div class="contact-box text-center">
                            <i class="fa fa-phone"></i>
                            <h3 class="mt-20">Phone</h3>
                            <p class="mt-20">{{ $phone }}</p>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-4 col-sm-4">
                        <div class="contact-box text-center">
                            <i class="fa fa-envelope-o"></i>
                            <h3 class="mt-20">Email</h3>
                            <p class="mt-20">{{ $email }}</p>
                        </div>
                    </div>

                </div>
            </div>
            <div class="row mt-20">
                <div class="col-lg-4 col-md-4 col-sm-4">
                    <div class="contact-box text-center">
                        <h3 class="mt-20">Twitter</h3>

                        <a href="https://twitter.com/{{ $twitter }}"><i class="fab fa-twitter"></i></a>

                    </div>
                </div>
                <div class="col-lg-4 col-md-4 col-sm-4">
                    <div class="contact-box text-center">
                        <h3 class="mt-20">Facebook</h3>
                        <a href="https://facebook.com/{{ $facebook }}"><i class="fab fa-facebook"></i></a>
                    </div>
                </div>
                <div class="col-lg-4 col-md-4 col-sm-4">
                    <div class="contact-box text-center">
                        <h3 class="mt-20">Instagram</h3>
                        <a href="https://instagram.com/{{ $instagram }}"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
