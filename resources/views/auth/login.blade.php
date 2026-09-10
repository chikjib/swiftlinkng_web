@extends('layouts.app')

@section('content')
    <!--=================================
             inner-intro-->

    {{-- <section class="inner-intro bg-2 bg-opacity-black-70">
        <div class="container">
            <div class="row text-center intro-title">
                <h1 class="text-blue">Log In</h1>
                <ul class="page-breadcrumb">
                    <li><a href="/"><i class="fa fa-home"></i> Home</a> <i class="fa fa-angle-double-right"></i></li>
                    <li><span>login </span> </li>
                </ul>
            </div>
        </div>
    </section> --}}

    <!--=================================
             inner-intro-->


    <!--=================================
             login-->

    <section class="login-2 white-bg page-section-ptb">
        <div class="container">
            <div class="row">
                <div class="col-lg-12 col-md-12">
                    <div class="section-title-1 text-center">
                        <h1 class="text-blue">Login to your Account</h1>
                        <div class="title-line"></div>
                        <p>Log in with your email and password</p>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-8 col-md-offset-2">
                    <div class="login-2-form pb-50 clearfix">
                        <form class="pt-3" method="POST" action="{{ route('login') }}">
                            @csrf
                            <div class="section-field">
                                <label for="email">Email* </label>
                                <div class="field-widget">
                                    <i class="fa fa-user"></i>
                                    <input id="email" type="text" class="web @error('email') is-invalid @enderror"
                                        name="email" value="{{ old('email') }}" required autocomplete="email" autofocus
                                        placeholder="Email Address">
                                    @error('email')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                            <div class="section-field">
                                <label for="Password">Password* </label>
                                <div class="field-widget">
                                    <i class="fa fa-unlock-alt"></i>
                                    <input id="Password" type="password"
                                        class="Password @error('password') is-invalid @enderror" name="password" required
                                        autocomplete="current-password" placeholder="Password">

                                    @error('password')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                            <div class="section-field">

                                <div class="remember-checkbox mb-30">
                                    <input type="checkbox" class="form-check-input" type="checkbox" name="remember"
                                        id="remember" {{ old('remember') ? 'checked' : '' }}>
                                    {{ __('Keep me signed in') }}



                                    @if (Route::has('password.request'))
                                        <a class="pull-right" href="{{ route('password.request') }}">
                                            {{ __('Forgot Your Password?') }}
                                        </a>
                                    @endif
                                </div>
                            </div>
                            
                            <input type="submit" value="Log in" class="btn btn-primary">


                        </form>
                    </div>
                    <div class="login-2-social mt-50 text-center clearfix">
                        <div class="text-center mt-4 fw-light">
                            Don't have an account? <a href="register" class="text-primary">Create</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

@endsection
