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
              <h3 class="sign-in__title">MTN Direct Automation APP Login</h3>

              <form
                method="POST"
                action="{{ route('generateMtnOtpApp') }}"
                class="sign-in__form"
              >
              @csrf
                <div class="sign-in__form-input-box">
                  <input
                    type="text"
                    class="form-control"
                    required
                    name="phone_number"
                    placeholder="Enter Phone Number e.g +23470...."
                  />
                </div>


                <div class="sign-in__form-btn-box">
                  <button
                    type="submit"

                    class="thm-btn sign-in__form-btn"
                  >
                    Submit
                  </button>



                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </section>
@endsection
