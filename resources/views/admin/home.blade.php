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
                                        <p class="statistics-title">Farmers</p>
                                        <h3 class="rate-percentage">{{ number_format($farmerscount) }}</h3>
                                    </div>
                                    <div class="boxsumarry">
                                        <p class="statistics-title">Agents</p>
                                        <h3 class="rate-percentage">{{ number_format($agentscount) }}</h3>
                                    </div>
                                    <div class="boxsumarry">
                                        <p class="statistics-title">Offtakers</p>
                                        <h3 class="rate-percentage">{{ number_format($offtakerscount) }}</h3>
                                    </div>
                                    <div class="boxsumarry">
                                        <p class="statistics-title">Products</p>
                                        <h3 class="rate-percentage">{{ number_format($productscount) }}</h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-lg-5 d-flex flex-column">
                                <div class="row flex-grow">
                                    <div class="col-12 col-lg-4 col-lg-12 grid-margin stretch-card">
                                        <div class="card card-rounded">
                                            <div class="card-body">
                                                <div
                                                    class="
                            d-sm-flex
                            justify-content-between
                            align-items-start
                          ">
                                                    <div>
                                                        <h4 class="card-title card-title-dash">
                                                            No. of Products Onboarded
                                                        </h4>

                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <productbarchart>

                                                    </productbarchart>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-4 d-flex flex-column">
                                <div class="row flex-grow">
                                    <div class="col-md-6 col-lg-12 grid-margin stretch-card">
                                        <div class="card card-rounded">
                                            <div class="card-body pb-0">
                                                <h6 class="card-title card-title-dash text-red mb-4">
                                                    Stock Summary By SubCategory
                                                </h6>
                                                <div class="row">
                                                    <subcategorypiechart>

                                                    </subcategorypiechart>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>
                            <div class="col-lg-3 d-flex flex-column">
                                <div class="row flex-grow">
                                    <div class="col-md-6 col-lg-12 grid-margin stretch-card">
                                        <div class="card card-rounded">
                                            <div class="card-body pb-0">
                                                <h6 class="card-title card-title-dash text-red mb-4">
                                                    Stock Summary By Category
                                                </h6>
                                                <div class="row">
                                                    <categorypiechart>

                                                    </categorypiechart>
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
        </div>
    </div>
@endsection
