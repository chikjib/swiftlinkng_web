@extends('layouts.app')

@section('content')
    <!--=================================
                                                     inner-intro-->

    <section class="bg-2">
        <div class="container">
            <div class="row text-center intro-title">

                <ul class="page-breadcrumb">
                    <li><a href="/"><i class="fa fa-home"></i> Home</a> <i class="fa fa-angle-double-right"></i></li>
                    <li><span>Privacy policy</span> </li>
                </ul>
            </div>
        </div>
    </section>

    <!--=================================
                                                     inner-intro-->

    <section class="about page-section-ptb">
        <div class="container">
            <div class="row">
                <div class="col-lg-12 col-md-12">
                    <div class="section-title-1 text-center">
                        <h1 class="text-blue">Privacy Policy</h1>
                        <div class="title-line"></div>
                        <p>Here is our privacy policy</p>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-12 col-md-12">
                    <div class="custom-content">
                        {!! $settings->value !!}
                    </div>
                </div>

            </div>
        </div>
    </section>
@endsection
