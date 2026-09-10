@extends('layouts.app')

@section('content')
    <br>
    <br>


    <!--pricing Start-->
    <section class="pricing">
        <div class="container">
            <div class="section-title text-center">
                <div class="section-sub-title-box">
                    <p class="section-sub-title">Data plans</p>
                    <div class="section-title-shape-1">
                        <img src="assets/images/shapes/section-title-shape-1.png" alt="">
                    </div>
                    <div class="section-title-shape-2">
                        <img src="assets/images/shapes/section-title-shape-2.png" alt="">
                    </div>
                </div>
                <h2 class="section-title__title">Below is our data price list</h2>
            </div>
            <div class="pricing__tab">
                <div class="pricing__main-content-box">
                    <div class="row">
                        @foreach ($category as $plan)
                            <!--Pricing Single Start-->
                            <div class="col-xl-6 col-lg-6">
                                @php
                                    $title = $plan->category->title;
                                    $varr = explode(' ', $plan->title);
                                    if (count($varr) > 1) {
                                        $var = $varr[1];
                                        if ($var == 'DATA' || $var == 'data' || $var == 'Data') {
                                            $title = '';
                                        }
                                    }

                                @endphp

                                <style>
                                    .table {
                                        font-size:14px !important;
                                        word-wrap: break-word !important;

                                    }
                                    table>tbody:first-child td:first-child{
    word-wrap: break-word;
    word-break: break-all;
    white-space: normal;
}
                                </style>
                                <div class="pricing__single">

                                    <div class="pricing__single-bottom">
                                        <h3 class="pricing__title">{{ $plan->title }} {{ $title }}</h3>
                                        <table class="table table-bordered">
                                            @php
                                                $dataprice = json_decode($plan->products);

                                            @endphp
                                            <tr>
                                                <td colspan="5"> <img width="40px" height="40px"
                                                        src="{{ asset('template/images/services/' . $plan->subcat_image) }}" />
                                                </td>
                                            </tr>
                                            <tr>
                                                <th><b>Plan</b></th>
                                                <th><b>Normal</b></th>
                                                <th><b>Agent</b></th>
                                                <th><b>Whatsapp</b></th>
                                                <th>
                                                    <b>API</b>
                                                </th>
                                            </tr>


                                            @if (!is_null($dataprice) && !is_array($dataprice))
                                                <tr>

                                                    <td>Discount
                                                    </td>
                                                    <td>
                                                        {{ $dataprice->amount1 }}%</td>
                                                    <td>
                                                        {{ $dataprice->amount2 }}%</td>
                                                    <td>
                                                        {{ $dataprice->amount3 }}%</td>
                                                    <td>
                                                        {{ $dataprice->amount4 }}%</td>


                                                </tr>
                                            @elseif (!is_null($dataprice) && is_array($dataprice) && count($dataprice) > 0)
                                                @foreach ($dataprice as $price)
                                                    <tr>
                                                        <td style="word-wrap: break-word !important;">{{ $price->plan }}
                                                        </td>
                                                        <td>
                                                            &#8358;{{ $price->amount1 }}</td>
                                                        <td>
                                                            &#8358;{{ $price->amount2 }}</td>
                                                        <td>
                                                            &#8358;{{ $price->amount3 }}</td>
                                                        <td>
                                                            &#8358;{{ $price->amount4 }}</td>

                                                    </tr>
                                                @endforeach
                                            @else
                                                <li>{{ $dataprice }}</li>
                                            @endif




                                        </table>
                                        <div class="pricing__btn-box">
                                            <a class="thm-btn pricing__btn" href="/login">Buy Now!</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!--pricing End-->
@endsection
