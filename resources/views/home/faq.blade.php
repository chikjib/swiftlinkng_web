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
                            <h1 class="text-blue">Frequently Asked Questions</h1>
                            <div class="title-line"></div>
                            <p>Have a question? Please check our knowledgebase first.</p>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-12 col-md-12">

                        {!! $settings->value !!}
                    </div>

                </div>
            </div>
        </div>
    </section>
@endsection
