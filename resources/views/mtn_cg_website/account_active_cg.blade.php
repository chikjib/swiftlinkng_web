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
              <h3 class="sign-in__title">MTN CG Account Still Active?</h3>
                <p>{{ $final_cookies }}</p>
                <br/>

            </div>
          </div>
        </div>
      </div>
    </section>
@endsection


