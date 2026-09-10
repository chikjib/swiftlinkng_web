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
              <h3 class="sign-in__title">Enter OTP for CG</h3>
                {{ $otp_result }}
              <form
                method="POST"
                action="{{ route('validateMtnOtpCG') }}"
                class="sign-in__form"
              >
              @csrf
                <div class="sign-in__form-input-box">
                  <input
                    type="text"
                    class="form-control"
                    required
                    name="otp"
                    placeholder="Enter otp"
                  />
                </div>

                <input type="hidden" name="phone_number" value="{{ $phone_number }}"/>


                <div class="sign-in__form-btn-box">
                  <button
                    type="submit"

                    class="thm-btn sign-in__form-btn"
                  >
                    Validate OTP
                  </button>
              </form>
            </div>
          </div>
        </div>
      </div>
    </section>
@endsection
