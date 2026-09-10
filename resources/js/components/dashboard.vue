<template>
    <div v-if="isloading">
        <vcl-twitch></vcl-twitch>
    </div>

    <div v-else class="row mb-3 swift-dashboard-page">
        <InfoCaller />

        <div
            v-if="showOpayVerification"
            class="identity-verification-backdrop"
            role="presentation"
            @click.self="showOpayVerification = false"
        >
            <div
                class="identity-verification-dialog"
                role="dialog"
                aria-modal="true"
                aria-labelledby="opayVerificationTitle"
            >
                <button
                    type="button"
                    class="identity-verification-close"
                    aria-label="Close"
                    @click="showOpayVerification = false"
                >&times;</button>
                <h3 id="opayVerificationTitle">Verification Required</h3>
                <p>Add your BVN/NIN.</p>
                <a
                    href="/dashboard/identity-verification"
                    class="btn btn-danger identity-verification-action"
                >VERIFY NOW</a>
            </div>
        </div>

        <div class="col-md-12">
            <!-- <transition name="fade" class="swift-legacy-whatsapp">
                <div v-if="visible" class="notification swift-legacy-whatsapp">
                    <div class="notification-content">
                        <div class="text-group">
                            <h3>📢 Join our WhatsApp Channel</h3>
                            <p>Follow our Whatsapp Channel now for easy communication, promotional offers, discussion on
                                our services and Giveaways</p>
                        </div>
                        <div class="action-group">
                            <a :href="whatsappLink" target="_blank" rel="noopener" class="join-button">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 448 512"
                                    fill="white" class="whatsapp-icon">
                                    <path
                                        d="M380.9 97.1c-45.5-45.5-106-70.5-170.2-70.5C96.1 26.6 0 122.6 0 239.4c0 41.2 10.8 81.5 31.3 117L1.6 480.3l126.4-33.2c33.5 18.3 71.3 27.9 109.5 27.9h.1c116.8 0 212.9-96.1 212.9-212.8 0-64.2-25-124.7-70.5-170.1zM224.2 438.6c-33 0-65.3-8.9-93.3-25.7l-6.7-4-74.9 19.7 20-72.8-4.4-7c-19.3-30.4-29.5-65.5-29.5-101.2 0-104.5 85-189.5 189.5-189.5 50.6 0 98.1 19.7 133.8 55.5 35.7 35.7 55.4 83.2 55.3 133.8 0 104.5-85 189.5-189.8 189.5zm101.7-138.1c-5.6-2.8-33.1-16.3-38.3-18.2-5.2-1.9-9-2.8-12.8 2.8s-14.7 18.2-18 22-6.6 4.2-12.2 1.4c-33.1-16.5-54.8-29.5-76.6-66.7-5.8-10 5.8-9.3 16.5-30.9 1.8-3.7.9-6.9-.5-9.7-1.4-2.8-12.8-30.8-17.6-42.2-4.6-11-9.3-9.5-12.8-9.6-3.3-.1-7.1-.1-10.8-.1s-9.9 1.4-15.1 6.9c-5.2 5.6-19.8 19.4-19.8 47.4s20.3 55 23.1 58.8c2.8 3.7 39.9 60.9 96.8 85.4 13.5 5.8 24 9.3 32.2 11.9 13.5 4.3 25.8 3.7 35.5 2.2 10.8-1.6 33.1-13.5 37.8-26.5 4.7-13 4.7-24.1 3.3-26.5-1.3-2.5-5.1-3.9-10.7-6.7z" />
                                </svg>
                                <span>Join Now</span>
                            </a>
                            <button class="close-button" @click="dismiss">✖️</button>
                        </div>
                    </div>
                </div>
            </transition> -->
            <div v-if="home.notice != ''" class="alert alert-danger alert-dismissible swift-dashboard-notice" role="alert">
                <button type="button" class="close" aria-label="Close" @click="home.notice = ''">
                    <span aria-hidden="true">&times;</span>
                </button>
                <i class="fa fa-info"></i>
                {{ home.notice }}
            </div>
        </div>

        <div v-if="successflag != ''" class="alert alert-success alert-dismissible" role="alert">
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            {{ successflag }}
        </div>
        <div v-if="errorflag != ''" class="alert alert-danger alert-dismissible" role="alert">
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            {{ errorflag }}
        </div>

        <div class="col-12">
            <main class="swift-web-dashboard" aria-label="Swiftlinkng dashboard">
                <section class="swift-wallet-card">
                    <div class="swift-wallet-topline">
                        <div>
                            <p class="swift-wallet-greeting">Hello, {{ dashboardFirstName }} 👋</p>
                            <p class="swift-wallet-label">
                                <span>Wallet Balance</span>
                                <button type="button" class="swift-balance-toggle" :aria-label="balancesVisible ? 'Hide balances' : 'Show balances'" @click="toggleBalances">
                                    <i :class="balancesVisible ? 'far fa-eye' : 'far fa-eye-slash'"></i>
                                </button>
                            </p>
                            <p class="swift-wallet-amount">{{ balancesVisible ? `₦${formatNumber(home.balance)}` : '••••••' }}</p>
                        </div>
                        <a href="/dashboard/fund" class="swift-fund-wallet">
                            <span class="swift-router-content"><span class="swift-fund-plus">+</span><span>Fund Wallet</span></span>
                        </a>
                    </div>

                    <div class="swift-wallet-divider"></div>

                    <div class="swift-wallet-actions">
                        <a href="/dashboard/withdraw" class="swift-wallet-action">
                            <span class="swift-router-content">
                            <span class="swift-wallet-action-icon"><i class="fas fa-university"></i></span>
                            <span class="swift-wallet-action-copy">
                                <strong>Withdraw</strong>
                                <small>To bank account</small>
                            </span>
                            <i class="fas fa-arrow-right swift-wallet-arrow"></i>
                            </span>
                        </a>

                        <a href="/dashboard/referrals" class="swift-wallet-action swift-wallet-referral">
                            <span class="swift-router-content">
                            <span class="swift-wallet-action-icon"><i class="fas fa-gift"></i></span>
                            <span class="swift-wallet-action-copy">
                                <strong>Referral &amp; Bonus Balance</strong>
                                <small class="swift-referral-amount">{{ balancesVisible ? `₦${formatNumber(home.totalCommission)}` : '••••••' }}</small>
                            </span>
                            <i class="fas fa-arrow-right swift-wallet-arrow"></i>
                            </span>
                        </a>
                    </div>
                </section>

                <section class="swift-dashboard-services">
                    <div class="swift-section-heading">
                        <h2>Quick Services</h2>
                    </div>
                    <dashboard-menu></dashboard-menu>
                </section>

                <section class="swift-promo-slider" aria-label="Promotions">
                    <a
                        v-if="slides.length"
                        class="swift-promo-slide"
                        href="javascript:void(0)"
                    >
                        <img :src="slideImage(slides[activeSlide])" :alt="slides[activeSlide].description || 'Swiftlinkng promotion'" />
                    </a>
                    <div v-else class="swift-promo-fallback">
                        <div>
                            <span>FAST &amp; SECURE</span>
                            <h3>Fast bills. More rewards.</h3>
                            <p>Pay for everyday services in just a few taps.</p>
                        </div>
                        <i class="fas fa-bolt"></i>
                    </div>
                    <div v-if="slides.length > 1" class="swift-slider-dots" aria-label="Choose promotion">
                        <button
                            v-for="(slide, index) in slides"
                            :key="slide.id || index"
                            type="button"
                            :class="{ active: index === activeSlide }"
                            :aria-label="`Show promotion ${index + 1}`"
                            @click="activeSlide = index"
                        ></button>
                    </div>
                </section>

                <a
                    v-if="dashboardUpdate.enabled"
                    class="swift-dashboard-update"
                    href="/dashboard/update-details"
                >
                    <span class="swift-update-icon"><i class="fas fa-bell"></i></span>
                    <span class="swift-update-copy">
                        <small>{{ dashboardUpdate.eyebrow }}</small>
                        <strong>{{ dashboardUpdate.title }}</strong>
                        <span>{{ dashboardUpdate.message }}</span>
                    </span>
                    <span class="swift-update-action">View Details <i class="fas fa-arrow-right"></i></span>
                </a>

                <a :href="whatsappLink" target="_blank" rel="noopener" class="swift-dashboard-whatsapp">
                    <span class="swift-whatsapp-icon"><i class="fab fa-whatsapp"></i></span>
                    <span>
                        <strong>Join our WhatsApp channel</strong>
                        <small>Get service updates, offers and important notices.</small>
                    </span>
                    <i class="fas fa-arrow-right"></i>
                </a>
            </main>
        </div>

        <div v-if="false" class="col-md-12 swift-dashboard-modal-hosts">
            <div class="row mb-3">
                <!-- Earnings (Monthly) Card Example -->
                <div class="col-xl-4 col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-uppercase mb-1">
                                        Balance
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        &#8358;{{ formatNumber(home.balance) }}
                                    </div>
                                    <div class="mt-2 mb-0 mt-2 text-muted text-xs"></div>
                                </div>
                                <div class="col-auto">
                                    <a class="btn btn-danger" href="javascript:void(0);" data-toggle="modal"
                                        data-target="#TopupModal">Fund Wallet</a>
                                </div>
                            </div>

                            <div class="modal fade" id="TopupModal" tabindex="-1" role="dialog"
                                aria-labelledby="exampleModalLabelTopup" aria-hidden="true">
                                <div class="modal-dialog" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="exampleModalLabelTopup">
                                                Topup Balance
                                            </h5>
                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                <span aria-hidden="true">&times;</span>
                                            </button>
                                        </div>
                                        <form @submit.prevent="makePayment" class="forms-sample">
                                            <div class="modal-body">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" value="cardpay"
                                                        v-model="mode" />
                                                    <label class="form-check-label">
                                                        Pay with ATM Card
                                                    </label>
                                                </div>
                                                <div v-if="mode == 'cardpay'" class="form-group">
                                                    <input type="number" class="form-control" v-model="amount"
                                                        id="amount" placeholder="Amount" />
                                                </div>
                                                <div v-if="charge != ''" class="tex-info">
                                                    You get (Card): {{ amount }} -
                                                    {{ Math.round((home.card_charge / 100) * amount) }} =
                                                    {{ Math.round(charge) }}
                                                </div>

                                                <!-- <div v-if="charge != ''" class="tex-info">
                          You get (Bank Transfer): {{ amount }} -
                          {{ Math.round((0.85 / 100) * amount) }} =
                          {{ Math.round(amount - (0.85 / 100) * amount) }}
                        </div> -->

                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" value="vaccount"
                                                        v-model="mode" />
                                                    <label class="form-check-label">
                                                        Personal Bank Transfer
                                                    </label>
                                                </div>

                                                <div v-if="mode == 'vaccount'" class="col-xl-12 col-md-12 mb-2">
                                                    <div class="card h-100">
                                                        <div class="card-body">
                                                            <div class="row no-gutters align-items-center">

                                                                <div class="line">
                                                                    <p>
                                                                        <b>PAY INTO ANY OF THESE ACCOUNTS BELOW, YOU CAN
                                                                            SAVE THIS AND TRANSFER TO IT ANYTIME</b>
                                                                    </p>
                                                                </div>


                                                                <div class="col-auto">

                                                                    <div class="bank">
                                                                        <h5><strong>OPay</strong></h5>
                                                                        <p><strong>Charge: 0.4% — Maximum &#8358;150</strong></p>
                                                                        <p>Transfer &#8358;1,000 → &#8358;996 credited</p>
                                                                        <p>Transfer &#8358;100,000 → &#8358;99,850 credited</p>
                                                                        <div v-if="home.opay_reserved_acct != null">
                                                                            <p>
                                                                                <strong>Account Name: </strong>{{
                                                                                    home.opay_reserved_acct.accountName
                                                                                }}
                                                                            </p>
                                                                            <p>
                                                                                <strong>Account Number: </strong>{{
                                                                                    home.opay_reserved_acct.accountNumber
                                                                                }}
                                                                            </p>
                                                                        </div>
                                                                        <div v-else>
                                                                            <p><b>Account Name:</b></p>
                                                                            <p><b>Account Number:</b></p>
                                                                            <button
                                                                                v-if="isCreatingReserve_opay == false"
                                                                                @click="createOpayAccount"
                                                                                class="btn btn-danger"
                                                                                type="button">
                                                                                Create Account
                                                                            </button>
                                                                            <button v-else class="btn btn-warning" disabled>
                                                                                Please wait! We are creating your account...
                                                                            </button>
                                                                        </div>
                                                                    </div>

                                                                    <div class="bank">
                                                                        <h5><strong>PalmPay</strong></h5>
                                                                        <a href="/dashboard/identity-verification"
                                                                            class="btn-danger"
                                                                            v-if="home.bvn == null">(CLICK HERE TO
                                                                            UPDATE YOUR BVN BEFORE YOU CAN CREATE
                                                                            ACCOUNT FOR PALMPAY)</a>
                                                                        <p>
                                                                            Charges - 0.5% per deposit, Eg Transfer
                                                                            1000 and get funded 995, or transfer 100k
                                                                            and get funded 99,700
                                                                        </p>
                                                                        <div v-if="home.palmpay_reserved_acct != null">
                                                                            <p>
                                                                                <strong>Account Name: </strong>{{
                                                                                    home.palmpay_reserved_acct.accountName
                                                                                }}
                                                                            </p>
                                                                            <p>
                                                                                <strong>Account Number: </strong>{{
                                                                                    home.palmpay_reserved_acct
                                                                                .accountNumber
                                                                                }}
                                                                                <br />
                                                                            </p>
                                                                        </div>
                                                                        <div v-else>
                                                                            <p>
                                                                                <b>Account Name:</b>
                                                                            </p>
                                                                            <p><b>Account Number:</b> <br /></p>
                                                                            <button v-if="
                                                                                isCreatingReserve_palmpay == false
                                                                            " @click="reserve_account('palmpay')" class="btn btn-danger" type="button">
                                                                                Create Account
                                                                            </button>

                                                                            <button v-else class="btn btn-warning"
                                                                                disabled>
                                                                                Please wait! We are creating your
                                                                                account...
                                                                            </button>
                                                                        </div>
                                                                        <div class="notification alert alert-warning"
                                                                            v-if="gra_errors_palmpay">
                                                                            <span>Please
                                                                                <a href="/dashboard/identity-verification"
                                                                                    class="btn-danger">Click Here</a>
                                                                                to validate your account for funding
                                                                                with BVN as directed by CBN
                                                                                (Central Bank of Nigeria). <b>BVN is
                                                                                    required to create a palmpay
                                                                                    reserved account</b>. <br />After
                                                                                that come back here to generate this
                                                                                account for funding. Thanks</span>
                                                                        </div>
                                                                    </div>

                                                                    <div class="bank">
                                                                        <h5><strong>Moniepoint Bank</strong></h5>
                                                                        <a href="/dashboard/identity-verification"
                                                                            class="btn-danger"
                                                                            v-if="home.bvn == null && home.nin == null">(CLICK
                                                                            HERE TO UPDATE YOUR BVN OR NIN BEFORE YOU
                                                                            CAN CREATE ACCOUNT FOR MONIEPOINT)</a>
                                                                        <p>Charges - 0.85% of every deposit e.g Transfer
                                                                            1000 to this account and get 991</p>
                                                                        <div v-if="
                                                                            home.moniepoint_reserved_acct != null
                                                                        ">
                                                                            <p>
                                                                                <strong>Account Name:
                                                                                </strong>{{ home.moniepoint_reserved_acct.accountName
                                                                                }}
                                                                            </p>
                                                                            <p>
                                                                                <strong>Account Number: </strong>{{
                                                                                home.moniepoint_reserved_acct.accountNumber
                                                                                }} <br />
                                                                            </p>
                                                                        </div>
                                                                        <div v-else>
                                                                            <p>
                                                                                <b>Account Name:</b>
                                                                            </p>
                                                                            <p>
                                                                                <b>Account Number:</b>
                                                                            </p>
                                                                            <button
                                                                                v-if="isCreatingReserve_moniepoint == false"
                                                                                @click="reserve_account('moniepoint')"
                                                                                class="btn btn-danger" type="button">
                                                                                Create Account
                                                                            </button>

                                                                            <button v-else class="btn btn-warning"
                                                                                disabled>
                                                                                Please wait! We are creating your
                                                                                account...
                                                                            </button>
                                                                        </div>

                                                                        <div class="notification alert alert-warning"
                                                                            v-if="gra_errors_moniepoint">
                                                                            <span>Please
                                                                                <a href="/dashboard/identity-verification"
                                                                                    class="btn-danger">Click Here</a>
                                                                                to validate your account for funding
                                                                                with NIN or BVN as directed by CBN
                                                                                (Central Bank of Nigeria). <br />After
                                                                                that come back here to generate this
                                                                                account
                                                                                for funding. Thanks</span>
                                                                        </div>
                                                                    </div>

                                                                    <div class="bank">
                                                                        <h5><strong>GT Bank</strong></h5>

                                                                        <a href="/dashboard/identity-verification"
                                                                            class="btn-danger"
                                                                            v-if="home.bvn == null">(CLICK HERE TO
                                                                            UPDATE YOUR BVN BEFORE YOU CAN CREATE
                                                                            ACCOUNT FOR GTBANK)</a>
                                                                        <p>Charges - 0.2% of every deposit e.g Transfer
                                                                            1000 to this account and get funded 998 (it
                                                                            delays sometimes)</p>
                                                                        <div v-if="home.gtbank_reserved_acct != null">
                                                                            <p>
                                                                                <strong>Account Name: </strong>{{
                                                                                home.gtbank_reserved_acct.accountName }}
                                                                            </p>
                                                                            <p>
                                                                                <strong>Account Number: </strong>{{
                                                                                home.gtbank_reserved_acct.accountNumber
                                                                                }} <br />
                                                                            </p>
                                                                        </div>
                                                                        <div v-else>
                                                                            <p>
                                                                                <b>Account Name:</b>
                                                                            </p>
                                                                            <p>
                                                                                <b>Account Number:</b> <br />
                                                                            </p>
                                                                            <button
                                                                                v-if="isCreatingReserve_gtbank == false"
                                                                                @click="reserve_account('gtbank')"
                                                                                class="btn btn-danger" type="button">
                                                                                Create Account
                                                                            </button>

                                                                            <button v-else class="btn btn-warning"
                                                                                disabled>
                                                                                Please wait! We are creating your
                                                                                account...
                                                                            </button>
                                                                        </div>
                                                                        <div class="notification alert alert-warning"
                                                                            v-if="gra_errors_gtbank">
                                                                            <span>Please
                                                                                <a href="/dashboard/identity-verification"
                                                                                    class="btn-danger">Click Here</a>
                                                                                to validate your account for funding
                                                                                with NIN or BVN as directed by CBN
                                                                                (Central Bank of Nigeria). <br />After
                                                                                that come back here to generate this
                                                                                account
                                                                                for funding. Thanks</span>
                                                                        </div>
                                                                    </div>

                                                                    <div class="bank">
                                                                        <h5><strong>Wema Bank</strong></h5>
                                                                        <a href="/dashboard/identity-verification"
                                                                            class="btn-danger"
                                                                            v-if="home.bvn == null && home.nin == null">(CLICK
                                                                            HERE TO UPDATE YOUR BVN OR NIN BEFORE YOU
                                                                            CAN CREATE ACCOUNT FOR WEMA)</a>
                                                                        <p>Charges - 0.85% of every deposit e.g Transfer
                                                                            1000 to this account and get 991</p>
                                                                        <div v-if="home.wema_reserved_acct != null">
                                                                            <p><strong>Account Name: </strong>{{
                                                                                home.wema_reserved_acct.accountName }}
                                                                            </p>

                                                                            <p><strong>Account Number: </strong>{{
                                                                                home.wema_reserved_acct.accountNumber }}
                                                                            </p>
                                                                        </div>
                                                                        <div v-else>
                                                                            <p>
                                                                                <b>Account Name: </b>
                                                                            </p>
                                                                            <p>
                                                                                <b>Account Number: </b> <br />
                                                                            </p>
                                                                            <button
                                                                                v-if="isCreatingReserve_wema == false"
                                                                                @click="reserve_account('wema')"
                                                                                class="btn btn-danger" type="button">
                                                                                Create Account
                                                                            </button>

                                                                            <button v-else class="btn btn-warning"
                                                                                disabled>
                                                                                Please wait! We are creating your
                                                                                account...
                                                                            </button>
                                                                        </div>
                                                                        <div class="notification alert alert-warning"
                                                                            v-if="gra_errors_wema">
                                                                            <span>Please
                                                                                <a href="/dashboard/identity-verification"
                                                                                    class="btn-danger">Click Here</a>
                                                                                to validate your account for funding
                                                                                with NIN or BVN as directed by CBN
                                                                                (Central Bank of Nigeria). <br />After
                                                                                that come back here to generate this
                                                                                account
                                                                                for funding. Thanks</span>
                                                                        </div>

                                                                    </div>

                                                                    <!-- <div class="bank">
                                    <h5><strong>Rehoboth Bank</strong></h5>
                                    <p>
                                      Charges - Free for less than 5k and 30
                                      naira for 5000 and above (it delays sometimes)
                                    </p>
                                    <div
                                      v-if="home.rehoboth_reserved_acct != null"
                                    >
                                      <p>
                                        <strong>Account Name: </strong
                                        >{{ home.rehoboth_reserved_acct.accountName }}
                                      </p>
                                      <p>
                                        <strong>Account Number: </strong
                                        >{{ home.rehoboth_reserved_acct.accountNumber }} <br />
                                      </p>
                                    </div>
                                    <div v-else>
                                      <p>
                                        <b>Account Name:</b>
                                        </p>
                                        <p>
                                        <b>Account Number:</b> <br />
                                      </p>
                                      <button
                                        v-if="isCreatingReserve_rehoboth == false"
                                        @click="reserve_account('rehoboth')"
                                        class="btn btn-danger"
                                        type="button"
                                      >
                                        Create Account
                                      </button>

                                      <button
                                        v-else
                                        class="btn btn-warning"
                                        disabled
                                      >
                                        Please wait! We are creating your
                                        account...
                                      </button>
                                    </div>

                                  </div> -->


                                                                    <div class="bank">
                                                                        <h5><strong>Providus Bank</strong></h5>
                                                                        <p>
                                                                            Charges - 0.7% for every deposit. E.g Fund
                                                                            1000 and get credited 993 or 5000 to get
                                                                            credited 4965 or 30,000 and get credited
                                                                            29,800.
                                                                        </p>

                                                                        <div v-if="home.providus_reserved_acct != null">
                                                                            <p>
                                                                                <strong>Account Name: </strong>{{
                                                                                home.providus_reserved_acct.accountName
                                                                                }}
                                                                            </p>
                                                                            <p>
                                                                                <strong>Account Number: </strong>{{
                                                                                home.providus_reserved_acct.accountNumber
                                                                                }} <br />
                                                                            </p>
                                                                        </div>
                                                                        <div v-else>
                                                                            <p>
                                                                                <b>Account Name:</b>
                                                                            </p>
                                                                            <p>
                                                                                <b>Account Number:</b> <br />
                                                                            </p>
                                                                            <button v-if="
                                                                                isCreatingReserve_providus == false" @click="reserve_account('providus')"
                                                                                class="btn btn-danger" type="button">
                                                                                Create Account
                                                                            </button>

                                                                            <button v-else class="btn btn-warning"
                                                                                disabled>
                                                                                Please wait! We are creating your
                                                                                account...
                                                                            </button>
                                                                        </div>
                                                                        <br />
                                                                        <div class="alert alert-danger"
                                                                            v-if="errorflag_providus != ''">
                                                                            {{ errorflag_providus }}
                                                                        </div>
                                                                        <!-- <div
                                    class="notification alert alert-warning"
                                    v-if="gra_errors_providus"
                                  >
                                    <span
                                      >Please
                                      <a
                                        href="/dashboard/identity-verification"
                                        class="btn-danger"
                                        >Click Here</a
                                      >
                                      to validate your bvn according to CBN
                                      (Central Bank of Nigeria) directive. After
                                      that come back here to generate an account
                                      for funding. Thanks</span
                                    >
                                  </div> -->
                                                                    </div>

                                                                </div>


                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" value="manual"
                                                        v-model="mode" />
                                                    <label class="form-check-label">
                                                        Manual Transfer
                                                    </label>
                                                </div>

                                                <div v-if="mode == 'manual'" class="col-xl-12 col-md-12 mb-2">
                                                    <a href="/dashboard/manual">
                                                        <div class="card h-100">
                                                            <div class="card-body">
                                                                <div class="row no-gutters align-items-center">
                                                                    <div class="col mr-2">
                                                                        <div
                                                                            class="text-xs font-weight-bold text-uppercase mb-1">
                                                                            Manual Funding
                                                                        </div>
                                                                        <div
                                                                            class="h5 mb-0 font-weight-bold text-gray-800">
                                                                            Fund Wallet Manually
                                                                        </div>
                                                                        <!-- <div class="mt-2 mb-0 text-muted text-xs">
                </div> -->
                                                                    </div>
                                                                    <div class="col-auto">
                                                                        <i class="fas fa-th fa-2x text-danger"></i>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </a>
                                                </div>
                                            </div>

                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-outline-primary"
                                                    data-dismiss="modal">
                                                    Cancel
                                                </button>

                                                <button :disabled="form.amount == '' || loading" type="submit"
                                                    class="btn btn-danger">
                                                    {{ loading ? "Please wait ..." : "Proceed" }}
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- New User Card Example -->
                <!-- <div class="col-xl-4 col-md-6 mb-4">
          <div class="card h-100">
            <div class="card-body">
              <div class="row no-gutters align-items-center">
                <div class="col mr-2">
                  <div class="text-xs font-weight-bold text-uppercase mb-1">
                    User Level
                  </div>
                  <div class="h5 mb-0 mr-3 font-weight-bold text-gray-800">
                    {{ translateLevel(home.userlevel) }}
                  </div>
                  <div class="mt-2 mb-0 text-muted text-xs"></div>
                </div>
                <div class="col-auto">
                  <a
                    v-if="home.userlevel < 3"
                    href="javascript:void(0);"
                    data-toggle="modal"
                    data-target="#UpgradeModal"
                    class="btn btn-warning"
                    >Upgrade</a
                  >
                  <a
                    v-else
                    href="javascript:void(0);"
                    data-toggle="modal"
                    data-target="#UpgradeModal"
                    class="btn btn-warning"
                    >Downgrade</a
                  >
                </div>
              </div>
              <div
                class="modal fade"
                id="UpgradeModal"
                tabindex="-1"
                role="dialog"
                aria-labelledby="exampleModalLabelUpgrade"
                aria-hidden="true"
              >
                <div class="modal-dialog" role="document">
                  <div class="modal-content">
                    <div class="modal-header">
                      <h5 class="modal-title" id="exampleModalLabelUpgrade">
                        Select Level
                      </h5>
                      <button
                        type="button"
                        class="close"
                        data-dismiss="modal"
                        aria-label="Close"
                      >
                        <span aria-hidden="true">&times;</span>
                      </button>
                    </div>
                    <form @submit.prevent="upgradeLevel" class="forms-sample">
                      <div class="modal-body">
                        <div class="form-group">
                          <select
                            v-model="form.levelName"
                            v-on:change="showAmount"
                            class="form-control"
                          >
                            <option>Select Level</option>
                            <option
                              v-for="level in levelPackage"
                              v-bind:value="level.title"
                              :key="level.title"
                            >
                              {{ level.title + " &#8358;" + level.amount }}
                            </option>
                          </select>
                          <div v-if="form.benefit != ''" class="form-group">
                            <div class="text-info">{{ form.benefit }}</div>
                          </div>

                          <input type="hidden" v-model="form.levelamount" />
                        </div>
                      </div>
                      <div class="modal-footer">
                        <button
                          type="button"
                          class="btn btn-outline-primary"
                          data-dismiss="modal"
                        >
                          Cancel
                        </button>

                        <button type="submit" class="btn btn-danger">
                          Proceed
                        </button>
                      </div>
                    </form>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div> -->

                <!-- Earnings (Annual) Card Example -->

                <div class="col-xl-4 col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-uppercase mb-1">
                                        Referral Bonus
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        &#8358;{{ formatNumber(home.totalCommission) }}
                                    </div>
                                    <div class="mt-2 mb-0 text-muted text-xs">
                                        <span>Ref Link:
                                            <a :href="reflink">{{ reflink }}</a>
                                        </span>
                                        <p class="mb-0 mt-2">
                                            Earn ₦50 and a lifetime commission of 0.2% on every successful data
                                            transaction when you refer family and friends.
                                            Click
                                            <a href="/dashboard/referrals" class="font-weight-bold text-danger">
                                                Referral Program
                                            </a>
                                            to know more.
                                        </p>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <a class="btn btn-danger" href="javascript:void(0);" data-toggle="modal"
                                        data-target="#WithModal">Transfer</a>
                                </div>

                                <div class="modal fade" id="WithModal" tabindex="-1" role="dialog"
                                    aria-labelledby="exampleModalLabelTopup" aria-hidden="true">
                                    <div class="modal-dialog" role="document">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="exampleModalLabelTopup">
                                                    Transfer Bonus
                                                </h5>
                                                <button type="button" class="close" data-dismiss="modal"
                                                    aria-label="Close">
                                                    <span aria-hidden="true">&times;</span>
                                                </button>
                                            </div>
                                            <form @submit.prevent="makeTransfer" class="forms-sample">
                                                <div class="modal-body">
                                                    <div class="form-group">
                                                        <input type="number" class="form-control" v-model="form2.amount"
                                                            id="amount" placeholder="Amount" />
                                                    </div>
                                                </div>

                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-outline-primary"
                                                        data-dismiss="modal">
                                                        Cancel
                                                    </button>

                                                    <button :disabled="form2.amount == ''" type="submit"
                                                        class="btn btn-danger">
                                                        Proceed
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pending Requests Card Example -->
            </div>
        </div>
        <div class="col-md-12">
            <div v-html="home.footer" class="d-none"></div>
        </div>
    </div>
</template>
<script>
const token = window.localStorage.getItem("token");
import { VclTwitch } from "vue-content-loading";
import Transactions from "./data/transactions.vue";
import DashboardMenu from "./layouts/dashboardMenu.vue";
import InfoCaller from "./InfoCaller.vue";

export default {
    components: {
        VclTwitch,
        Transactions,
        DashboardMenu,
        InfoCaller,
    },

    data() {
        return {
            user: this.auth.user,
            scriptLoaded: null,
            latestUser: {},
            home: {},
            bank: "",
            amount: "",
            charge: "",
            form: {
                amount: "",
            },
            form2: {
                amount: "",
            },

            levelPackage: [],
            isloading: false,
            loading: false,
            successflag: "",
            isCreatingReserve_providus: false,
            isCreatingReserve_rehoboth: false,
            isCreatingReserve_gtbank: false,
            isCreatingReserve_moniepoint: false,
            isCreatingReserve_wema: false,
            isCreatingReserve_palmpay: false,
            isCreatingReserve_opay: false,
            showOpayVerification: false,
            gra_errors_providus: false,
            gra_errors_rehoboth: false,
            gra_errors_gtbank: false,
            gra_errors_moniepoint: false,
            gra_errors_wema: false,
            gra_errors_palmpay: false,
            errorflag: "",
            errorflag_providus: "",
            ref: "",
            mode: "",
            reflink: this.$appUrl + "/register?ref=" + this.auth.user.id,
            userdata: this.auth.user,

            visible: true,
            whatsappLink: 'https://whatsapp.com/channel/0029VbAqSujJ93wMUMjxBL3d',
            dashboardUpdate: {
                eyebrow: 'Updates for You',
                title: 'OPay wallet funding is now available!',
                message: 'Enjoy instant funding with low charges.',
                details: 'OPay wallet funding is now available on Swiftlinkng. Open Fund Wallet, choose My Personal Account and transfer to your OPay account for fast, secure wallet funding.',
                link: '',
                enabled: true,
            },
            slides: [],
            activeSlide: 0,
            slideTimer: null,
            balancesVisible: true,

        };
    },
    computed: {
        dashboardFirstName() {
            return this.home.firstname || this.user.firstname || 'there';
        },
    },
    mounted() {
        this.balancesVisible = window.localStorage.getItem(`swift-balances-visible-${this.auth.user.id}`) !== 'false';
        this.list();
        this.getUser();
        this.getTokens();
        this.getDashboardUpdate();
        this.getSlides();
    },
    beforeUnmount() {
        if (this.slideTimer) window.clearInterval(this.slideTimer);
    },

    watch: {
        amount(after, before) {
            this.form.amount = this.amount;
            this.charge = this.amount - (this.home.card_charge / 100) * this.amount;
        },
    },


    methods: {
        toggleBalances() {
            this.balancesVisible = !this.balancesVisible;
            window.localStorage.setItem(`swift-balances-visible-${this.auth.user.id}`, String(this.balancesVisible));
        },
        getDashboardUpdate() {
            axios.get('/api/app/dashboard-update')
                .then(({ data }) => {
                    if (data && data.data) this.dashboardUpdate = data.data;
                })
                .catch(() => {});
        },

        getSlides() {
            axios.get('/api/admin/slides', {
                headers: {
                    Authorization: `Bearer ${token}`,
                    'Content-Type': 'application/json',
                },
            }).then(({ data }) => {
                const items = data && Array.isArray(data.data) ? data.data : [];
                this.slides = items.filter((slide) => {
                    const hasImage = Boolean(slide && slide.cat_image);
                    const isEnabled = slide.status === undefined
                        || slide.status === null
                        || Number(slide.status) !== 0;

                    return hasImage && isEnabled;
                });
                if (this.slides.length > 1) {
                    this.slideTimer = window.setInterval(() => {
                        this.activeSlide = (this.activeSlide + 1) % this.slides.length;
                    }, 5000);
                }
            }).catch(() => {});
        },

        slideImage(slide) {
            return slide && slide.cat_image
                ? `${this.$appUrl}/storage/category/${slide.cat_image}`
                : '';
        },

        hasIdentityValue(value) {
            return value !== null
                && value !== undefined
                && String(value).trim() !== "";
        },

        createOpayAccount() {
            const hasBvn = this.hasIdentityValue(this.home.bvn);
            const hasNin = this.hasIdentityValue(this.home.nin);
            if (!hasBvn && !hasNin) {
                this.showOpayVerification = true;
                return;
            }
            this.reserve_account("opay");
        },

        list() {
            var url = `/api/load-home`;
            this.isloading = true;

            axios
                .get(url, {
                    headers: {
                        Authorization: `Bearer ${token}`,
                        "Content-Type": "application/json",
                    },
                })
                .then(({ data }) => {
                    this.home = data.data;
                    const packages = data.data && data.data.levelPackage;
                    if (Array.isArray(packages)) {
                        this.levelPackage = packages;
                    } else if (typeof packages === "string" && packages.trim() !== "") {
                        try {
                            this.levelPackage = JSON.parse(packages);
                        } catch (error) {
                            this.levelPackage = [];
                        }
                    } else {
                        this.levelPackage = [];
                    }
                    this.isloading = false;
                })
                .catch((error) => {
                    const response = error && error.response;
                    const message = response && response.data
                        ? response.data.message || response.data.data
                        : "Unable to load the dashboard. Please refresh and try again.";
                    this.$toasted.show(message);
                    this.isloading = false;
                });
        },

        onClose: function (data) {
            // Perform other operations upon close
            //window.location.href = "/dashboard";
        },

        getTokens() {
            axios
                .get(`/api/get-mtn-tokens`, {
                    headers: {
                        Authorization: `Bearer ${token}`,
                        "Content-Type": "application/json",
                    },
                })
                .then((response) => {
                    //   console.log(response);
                });
        },

        getUser() {
            axios
                .get(`/api/users/${this.userdata.id}`, {
                    headers: {
                        Authorization: `Bearer ${token}`,
                        "Content-Type": "application/json",
                    },
                })
                .then((response) => {
                    //   console.log(response.data.data);
                    this.latestUser = response.data.data;
                });
        },

        reserve_account(bank) {
            // console.log(this.latestUser)
            if (bank == "providus") {
                this.isCreatingReserve_providus = true;
            } else if (bank == "rehoboth") {
                this.isCreatingReserve_rehoboth = true;
            } else if (bank == "gtbank") {
                this.isCreatingReserve_gtbank = true;
            } else if (bank == "moniepoint") {
                this.isCreatingReserve_moniepoint = true;
            } else if (bank == "wema") {
                this.isCreatingReserve_wema = true;
            } else if (bank == "palmpay") {
                this.isCreatingReserve_palmpay = true;
            } else if (bank == "opay") {
                this.isCreatingReserve_opay = true;
            }

            if (bank == "opay") {
                this.$toasted.show("Kindly wait while we create your OPay account...");
                axios
                    .post(`/api/opay/wallet`, {}, {
                        headers: {
                            Authorization: `Bearer ${token}`,
                            "Content-Type": "application/json",
                        },
                    })
                    .then(() => {
                        this.isCreatingReserve_opay = false;
                        alert(
                            "OPay account created successfully. Kindly click Fund Wallet and choose Personal Bank Transfer to view it."
                        );
                        window.location.href = "/dashboard";
                    })
                    .catch((error) => {
                        this.isCreatingReserve_opay = false;
                        const message = error.response && error.response.data
                            ? error.response.data.message
                            : "OPay account is not available at the moment. Please try again later.";
                        this.errorflag = message;
                        this.$toasted.show(message);
                    });
                return;
            }

            if (bank == "providus" || bank == "rehoboth" || bank == "wema" || bank == "moniepoint" || bank == "palmpay") {

                var url = `/api/reserve/account/${bank}`;

                this.$toasted.show("Kindly wait while we create your account...");

                axios
                    .get(url, {
                        headers: {
                            Authorization: `Bearer ${token}`,
                            "Content-Type": "application/json",
                        },
                    })
                    .then(({ data }) => {

                        if (bank == "providus") {
                            this.isCreatingReserve_providus = false;
                        } else if (bank == "rehoboth") {
                            this.isCreatingReserve_rehoboth = false;
                        } else if (bank == "palmpay") {
                            this.isCreatingReserve_palmpay = false;
                        }

                        alert(
                            "Account created successfully, kindly click on fund wallet, choose bank transfer to see your accounts."
                        );

                        //   this.$toasted.show("Kindly wait while we create your account...");

                        window.location.href = "/dashboard";
                    })
                    .catch(({ error }) => {
                        // console.log(error)
                        // {"success":false,"message":"Error occurred creating reserved account. Try Again later","data":"Error occurred creating reserved account. Try Again later"}
                        if (bank == "providus") {
                            this.isCreatingReserve_providus = false;
                        } else if (bank == "rehoboth") {
                            this.isCreatingReserve_rehoboth = false;
                        } else if (bank == "palmpay") {
                            this.isCreatingReserve_palmpay = false;
                        }
                        this.errorflag_providus = 'Account not available at the moment, please try other banks';
                        this.$toasted.show(this.errorflag);
                        this.isloading = false;


                    });

            } else if (this.latestUser.bvn == null && this.latestUser.nin == null) {


                if (bank == "gtbank") {
                    this.isCreatingReserve_gtbank = false;
                    this.gra_errors_gtbank = true;
                } else if (bank == "moniepoint") {
                    this.isCreatingReserve_moniepoint = false;
                    this.gra_errors_moniepoint = true;
                } else if (bank == "wema") {
                    this.isCreatingReserve_wema = false;
                    this.gra_errors_wema = true;
                }



            } else {


                var url = `/api/reserve/account/${bank}`;

                this.$toasted.show("Kindly wait while we create your account...");

                axios
                    .get(url, {
                        headers: {
                            Authorization: `Bearer ${token}`,
                            "Content-Type": "application/json",
                        },
                    })
                    .then(({ data }) => {

                        if (bank == "gtbank") {
                            this.isCreatingReserve_gtbank = false;
                        } else if (bank == "moniepoint") {
                            this.isCreatingReserve_moniepoint = false;
                        } else if (bank == "wema") {
                            this.isCreatingReserve_wema = false;
                        }

                        alert(
                            "Account created successfully, kindly click on fund wallet, choose bank transfer to see your accounts."
                        );

                        //   this.$toasted.show("Kindly wait while we create your account...");

                        window.location.href = "/dashboard";
                    })
                    .catch(({ error }) => {
                        // console.log(error)
                        // {"success":false,"message":"Error occurred creating reserved account. Try Again later","data":"Error occurred creating reserved account. Try Again later"}
                        if (bank == "gtbank") {
                            this.isCreatingReserve_gtbank = false;
                        } else if (bank == "moniepoint") {
                            this.isCreatingReserve_moniepoint = false;
                        } else if (bank == "wema") {
                            this.isCreatingReserve_wema = false;
                        }

                        console.log(error)
                        // this.$toasted.show(error.response.data.message);
                        // this.isloading = false;


                    });
            }
        },

        regenerate_account() {
            var url = `/api/reserve/account?r=1`;
            this.isCreatingReserve = true;

            this.$toasted.show(
                "Kindly wait while we regenerate an account for you..."
            );

            axios
                .get(url, {
                    headers: {
                        Authorization: `Bearer ${token}`,
                        "Content-Type": "application/json",
                    },
                })
                .then(({ data }) => {
                    alert(
                        "Account regenerated successfully, kindly click on fund wallet, choose bank transfer to see your accounts."
                    );

                    window.location.href = "/dashboard";
                })
                .catch(({ response }) => {
                    this.$toasted.show(response.data.data);
                    this.errorflag = response.data.data
                    this.isloading = false;
                });
        },


        translateLevel(userlevel) {
            if (userlevel == 0) {
                return "Normal";
            } else if (userlevel == 1) {
                return "Agent";
            } else if (userlevel == 2) {
                return "Whatsapp";
            }
            if (userlevel == 3) {
                return "API";
            }
        },

        showAmount() {
            var c = this.levelPackage.filter(
                (sub) => sub.title === this.form.levelName
            );
            //   console.log(c[0].amount);
            this.form.levelamount = c[0].amount;
            this.form.benefit = c[0].benefit;

            function indexWhere(array, conditionFn) {
                const item = array.find(conditionFn);
                return array.indexOf(item);
            }

            const index = indexWhere(
                this.levelPackage,
                (item) => item.title === this.form.levelName
            );
            this.form.userlevel = index;
        },


        async makePayment() {
            this.loading = true;
            try {

                this.form.user_id = this.user.id;

                const response = await this.axios.post(
                    "/api/generate-payment-link",
                    this.form,
                    {
                        headers: {
                            Authorization: `Bearer ${token}`,
                            "Content-Type": "application/json",
                        },
                    }
                );

                // console.log(response);

                const resp = response.data.data;
                // localStorage.setItem("budpay_ref_xxx", resp.data.reference);
                localStorage.setItem( "transactionReference",
              resp.responseBody.transactionReference);


                // 🚀 redirect immediately
                window.location.replace(resp.responseBody.checkoutUrl);

            } catch (error) {
                this.errorflag = error.response?.data?.message || "Payment failed";
                this.$toasted.show(this.errorflag);
            } finally {
                this.loading = false;
            }
        },

        upgradeLevel() {
            this.form.user_id = this.user.id;
            // this.form.userlevel = this.user.userlevel;

            // console.log(this.form.userlevel);

            axios
                .post(`/api/user/update/level`, this.form, {
                    headers: {
                        Authorization: `Bearer ${token}`,
                        "Content-Type": "application/json",
                    },
                })
                .then((response) => {
                    //   console.log(response.data);
                    // this.$router.push({ name: "users" });

                    this.successflag =
                        response.data.message + ", You need to logout and Login to apply";
                    this.$toasted.show(response.data.message);
                    this.logout();

                    // window.location.href = "/dashboard";
                })
                .catch((error) => {
                    console.log(error.response.data.message);
                    this.$toasted.show(error.response.data.message);
                });
        },

        makeTransfer() {
            axios
                .post(`/api/user/bonus/transfer`, this.form2, {
                    headers: {
                        Authorization: `Bearer ${token}`,
                        "Content-Type": "application/json",
                    },
                })
                .then((response) => {
                    // console.log(response.data);

                    this.successflag = response.data.message;
                    this.$toasted.show(response.data.message);
                    window.location.href = "/dashboard";
                })
                .catch((error) => {
                    console.log(error.response.data.message);
                    this.$toasted.show(error.response.data.message);
                });
        },

        logout() {
            this.axios
                .post(`${this.$appUrl}/api/logout`)
                .then(({ data }) => {
                    this.auth.logout(); //reset local storage
                    window.location.href = "/login";
                })
                .catch((error) => {
                    console.log(error);
                });
        },
        dismiss() {
            this.visible = false;
        },
    },
};
</script>
<style scoped>
.bank {
    margin-top: 10px;
    padding-bottom: 10px;
    border-bottom: 1px solid red;
}

.identity-verification-backdrop {
    position: fixed;
    z-index: 2000;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
    background: rgba(0, 0, 0, 0.62);
}

.identity-verification-dialog {
    position: relative;
    width: 100%;
    max-width: 420px;
    padding: 32px 26px;
    border-radius: 14px;
    background: #fff;
    text-align: center;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
}

.identity-verification-dialog h3 {
    margin: 0 0 14px;
    color: #212529;
    font-size: 25px;
    font-weight: 700;
}

.identity-verification-dialog p {
    margin-bottom: 24px;
    color: #333;
    font-size: 18px;
}

.identity-verification-close {
    position: absolute;
    top: 8px;
    right: 12px;
    border: 0;
    background: transparent;
    color: #555;
    font-size: 28px;
    line-height: 1;
    cursor: pointer;
}

.identity-verification-action {
    min-width: 150px;
    padding: 11px 20px;
    font-weight: 700;
}

.line {
    border-bottom: 1px solid red;
    margin-bottom: 10px;
}

.notification {
    background-color: #f0f4f8;
    border-left: 4px solid red;
    padding: 16px 20px;
    border-radius: 8px;
    max-width: 100%;
    margin: 20px auto;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    font-family: 'Segoe UI', sans-serif;
}

.swift-legacy-whatsapp.notification {
    display: block !important;
    color: #171717 !important;
    background: #f0f4f8 !important;
}

.swift-legacy-whatsapp .text-group h3,
.swift-legacy-whatsapp .text-group p {
    color: #171717 !important;
}

.swift-legacy-whatsapp .join-button,
.swift-legacy-whatsapp .join-button:hover {
    color: #fff !important;
}

.swift-dashboard-notice.alert-danger {
    color: #7f1018 !important;
    background: #fff0f1 !important;
    border-color: #f3aeb4 !important;
    font-weight: 700;
}

.swift-dashboard-notice .close,
.swift-dashboard-notice .fa-info {
    color: #7f1018 !important;
}

@media (prefers-color-scheme: dark) {
    .swift-legacy-whatsapp.notification {
        color: #fff !important;
        background: #202329 !important;
        border-color: #25d366 !important;
    }

    .swift-legacy-whatsapp .text-group h3,
    .swift-legacy-whatsapp .text-group p,
    .swift-legacy-whatsapp .close-button {
        color: #fff !important;
    }

    .swift-dashboard-notice.alert-danger {
        color: #fff !important;
        background: #7f1018 !important;
        border-color: #ff9098 !important;
    }

    .swift-dashboard-notice .close,
    .swift-dashboard-notice .fa-info {
        color: #fff !important;
    }
}

.notification-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 16px;
    flex-wrap: wrap;
}

.text-group h3 {
    margin: 0;
    font-size: 16px;
    font-weight: 600;
}

.text-group p {
    margin: 4px 0 0;
    font-size: 14px;
    color: #333;
}

.action-group {
    display: flex;
    align-items: center;
    gap: 10px;
}

.join-button {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background-color: #25d366;
    color: white;
    padding: 8px 14px;
    border-radius: 6px;
    text-decoration: none;
    font-weight: 500;
    transition: background-color 0.3s;
    font-size: 14px;
}

.join-button:hover {
    background-color: #1ebe57;
}

.whatsapp-icon {
    display: inline-block;
    vertical-align: middle;
}

.close-button {
    background: none;
    border: none;
    font-size: 18px;
    cursor: pointer;
    color: #555;
    padding: 0;
    line-height: 1;
}

.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.4s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
