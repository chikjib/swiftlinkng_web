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
                                        <p class="statistics-title">Wallet Balance</p>
                                        <h3 class="rate-percentage">&#8358;{{ Auth::user()->wallet }}</h3>
                                    </div>

                                    <div class="boxsumarry">
                                        <p class="statistics-title">Comission Received</p>
                                        <h3 class="rate-percentage"> &#8358;{{ $commissionstotal }}</h3>
                                    </div>
                                    <div class="boxsumarry">
                                        <p class="statistics-title">Withdrawals</p>
                                        <h3 class="rate-percentage">&#8358;{{ $withdrawals }}</h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">

                            <Allorders :user='{!! json_encode(Auth::user()) !!}'>
                            </Allorders>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
