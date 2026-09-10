 <header class="main-header-five">
     <div class="main-header-five__top">
         <div class="container">
             <div class="main-header-five__top-inner">
                 <div class="main-header-five__top-address">
                     <ul class="list-unstyled main-header-five__top-address-list">
                         <li>
                             <i class="icon">
                                 <span class="fas fa-envelope"></span>
                             </i>
                             <div class="text">
                                 <p><a href="#">{{ config('app.siteEmail') }}</a></p>
                             </div>
                         </li>
                         <li>
                             <i class="icon">
                                 <span class="fas fa-map-marker"></span>
                             </i>
                             <div class="text">
                                 <p>{{ config('app.siteAddress') }}</p>
                             </div>
                         </li>
                         <li>
                             <i class="icon">
                                 <span class="fas fa-phone-alt"></span>
                             </i>
                             <div class="text">
                                 <p><a href="#">{{ config('app.sitePhone') }}</a></p>
                             </div>
                         </li>
                     </ul>
                 </div>
                 <div class="main-header-five__top-right">
                     <a href="https://twitter.com/{{ $data['twitter'] }}"><i class="fab fa-twitter"></i></a>
                         <a href="https://facebook.com/{{ $data['facebook'] }}"><i class="fab fa-facebook"></i></a>
                         <a href="https://instagram.com/{{ $data['instagram'] }}"><i class="fab fa-instagram"></i></a>
                 </div>
             </div>
         </div>
     </div>
     <nav class="main-menu main-menu-five">
         <div class="main-menu-five__wrapper"
             style="background-image: url('{{ asset('frontend/images/backgrounds/page-header-bg.jpg') }}') !important;">
             <div class="container">
                 <div class="main-menu-five__wrapper-inner">
                     <div class="main-menu-five__left">
                         <div class="main-menu-five__logo">
                             <a href="/"><img src="{{ asset('frontend/images/swiftlinklogo.png') }}"
                                     alt="Swiftlinkng" width="176" height="31"></a>
                         </div>
                         <div class="main-menu-five__main-menu-box">
                             <a href="#" class="mobile-nav__toggler"><i class="fa fa-bars"></i></a>
                             <ul class="main-menu__list">
                                 <li><a href="/">Home</a></li>
                                 <li><a href="/about">About</a></li>
                                 <li><a href="/contact">Contact</a></li>
                                 <li><a href="/faq">Faq</a></li>

                                 <li><a href="/pricing">Pricing</a></li>

                                 <li><a href="/login">Login</a></li>
                                 <li><a href="/register">Register</a></li>

                             </ul>
                         </div>
                     </div>
                     <div class="main-menu-five__right">
                         <div class="main-menu-five__search-get-quote-btn">

                             <div class="main-menu-five__get-quote-btn-box">
                                 <a href="/login" class="thm-btn-two main-menu-five__get-quote-btn">Login
                                 </a>

                             </div>
                         </div>
                     </div>
                 </div>
             </div>
         </div>
     </nav>
 </header>
 <div class="stricky-header stricked-menu main-menu main-menu-five">
     <div class="sticky-header__cntent"></div><!-- /.sticky-header__content -->
 </div><!-- /.stricky-header -->
