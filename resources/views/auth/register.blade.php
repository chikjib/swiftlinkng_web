@extends('layouts.app')

@section('content')
    <section class="login-2 white-bg page-section-ptb">
        <div class="container">
            <div class="row">
                <div class="col-lg-12 col-md-12">
                    <div class="section-title-1 text-center">
                        <h1 class="text-blue">Create an Account</h1>
                        <div class="title-line"></div>
                        <p>Fill the form below to create an account</p>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-8 col-md-offset-2">
                    <div class="login-2-form pb-50 clearfix">
                        <form method="POST" action="{{ route('register') }}">
                            @csrf
                            <div class="section-field">
                                <label for="firstname">Firstname* </label>
                                <div class="field-widget">
                                    <i class="fa fa-user"></i>
                                    <input id="firstname" type="text"
                                        class="web @error('firstname') is-invalid @enderror" name="firstname"
                                        value="{{ old('firstname') }}" required autocomplete="firstname" autofocus>

                                    @error('firstname')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <div class="section-field">
                                <label for="lastname">Lastname* </label>
                                <div class="field-widget">
                                    <i class="fa fa-user"></i>
                                    <input id="lastname" type="text" class="web @error('lastname') is-invalid @enderror"
                                        name="lastname" value="{{ old('lastname') }}" required autocomplete="lastname"
                                        autofocus>

                                    @error('lastname')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

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
                                <label for="phone">Phone Number* </label>
                                <div class="field-widget">
                                    <i class="fa fa-user"></i>
                                    <input id="phone" type="text" class="web @error('phone') is-invalid @enderror"
                                        name="phone" value="{{ old('phone') }}" required autocomplete="phone"
                                        autofocus>

                                    @error('phone')
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
                                <label for="password-confirm">Confirm Password* </label>
                                <div class="field-widget">
                                    <i class="fa fa-unlock-alt"></i>
                                    <input id="password-confirm" type="password" class="password"
                                        name="password_confirmation" required autocomplete="new-password">


                                </div>
                            </div>


                            <input type="submit" value="Create Account" class="btn btn-primary">


                        </form>
                    </div>
                    <div class="login-2-social mt-50 text-center clearfix">
                        <div class="text-center mt-4 fw-light">
                            Already have an account? <a href="login" class="text-primary">Login</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
