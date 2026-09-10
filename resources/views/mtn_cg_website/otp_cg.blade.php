@extends('layouts.app')

@section('content')
    <br>
    <br>

    <section class="sign-in">
      <div class="container">
        <div class="sign-in__top">

        </div>
        <div class="row">
          <div class="col-xl-6 col-lg-6 mx-auto">
            <div class="sign-in__single">
              <h3 class="sign-in__title">Generate OTP CG</h3>
              <form
                method="POST"
                action="{{ route('generateMtnOtpCG') }}"
                class="sign-in__form"
              >
              @csrf
                <div class="sign-in__form-input-box">
                  <input
                    type="radio"
                    required
                    name="phone_number"
                    placeholder="Enter otp"
                    checked
                  /> {{ $phone_number }}
                </div>

                <input type="hidden" name="phone_number" value="{{ $phone_number }}"/>
                <input type="hidden" name="first_name" value="{{ $first_name }}"/>


                <div class="sign-in__form-btn-box">
                  <button
                    type="submit"

                    class="thm-btn sign-in__form-btn"
                  >
                    Send OTP
                  </button>

              </form>
            </div>
          </div>
        </div>
      </div>
    </section>
@endsection
