@extends('layouts.master')

@section('content')
    <div class="row">
        <div class="col-sm-12">
            <div class="home-tab">
                <div class="tab-content tab-content-basic">
                    <div class="tab-pane fade show active" id="overview" role="tabpanel" aria-labelledby="overview">
                        <div class="row">
                            <div class="col-sm-12">
                                <div
                                    class="
                    statistics-details
                    d-flex
                    align-items-center
                    justify-content-between
                  ">
                                    <div class="boxsumarry">
                                        <p class="statistics-title">Reserved Account</p>
                                        <h3 class="rate-percentage"></h3>
                                    </div>


                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-lg-12 grid-margin stretch-card">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="col-md-12">

                                            <form method="post" class="forms-sample"
                                                action="{{ route('flutterwave.payment') }}">
                                                @csrf
                                                <div class="form-group row">

                                                    <input type="hidden" value="{{ Auth::user()->firstname }}"
                                                        name="first_name" required="">



                                                    <input type="hidden"
                                                        value="{{ Auth::user()->lastname }}"name="last_name" required="">


                                                    <input type="hidden" value="{{ Auth::user()->email }}" name="email"
                                                        required="">


                                                    <input type="hidden" value="{{ Auth::user()->phone }}" name="phone_no"
                                                        class="form-control" required="">

                                                    <div class="col-md-6">
                                                        <label class="form-control-label">Amount<span
                                                                class="text-danger">*</span></label>
                                                        <input type="number" name="amount" class="form-control"
                                                            required="">
                                                        @if ($errors->has('amount'))
                                                            <span class="text-danger help-block form-error">
                                                                <strong>{{ $errors->first('amount') }}</strong>
                                                            </span>
                                                        @endif
                                                    </div>

                                                    <input type="hidden" value="NGN" name="currency" required="">


                                                </div>
                                                <button type="submit" class="btn btn-primary me-2">Proceed</button>

                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
