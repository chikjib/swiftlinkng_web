  <!--Site Footer Start-->
  <footer class="site-footer-three">
      <div class="container">
          <div class="site-footer-three__top">
              <div class="row">
                  <div class="col-xl-4 col-lg-6 col-md-6 wow fadeInUp" data-wow-delay="100ms">
                      <div class="footer-widget__column footer-widget-three__about">
                          <div class="footer-widget-three__logo">
                              <a href="/"><img src="{{ asset('frontend/images/swiftlinklogo.png') }}" width="143px"
                                      height="27px" alt=""></a>
                          </div>
                          <div class="footer-widget-three__about-text-box">
                              <p class="footer-widget-three__about-text"> Swiftlink is a trusted and reliable platform
                                  for quality service
                                  delivery in sales of mobile data, airtime, decoder subscriptions and Electricity bills
                                  payment.</p>
                          </div>
                          <div class="footer-widget-three__btn-box">
                              <a href="contact" class="thm-btn-two footer-widget-three__btn">Contact</a>
                          </div>
                      </div>
                  </div>
                  <div class="col-xl-4 col-lg-6 col-md-6 wow fadeInUp" data-wow-delay="200ms">
                      <div class="footer-widget__column footer-widget-three__links clearfix">
                          <h3 class="footer-widget-three__title">Links</h3>
                          <ul class="footer-widget-three__links-list list-unstyled clearfix">
                              <li><a href="about">About</a></li>
                              <li><a href="pricing">Pricing</a></li>
                              <li><a href="faq">Our Faqs</a></li>
                              <li><a href="contact">Get in Touch</a></li>
                              <li><a href="terms">Terms & Conditions</a> </li>
                              <li><a href="privacy">Privacy Policy</a> </li>
                              <li>
                                  <a href="https://documenter.getpostman.com/view/20422990/2s93Y5PKhG">API
                                      Documentation</a>
                              </li>
                          </ul>
                      </div>
                  </div>

                  <div class="col-xl-4 col-lg-6 col-md-6 wow fadeInUp" data-wow-delay="400ms">
                      <div class="footer-widget__column footer-widget-three__contact clearfix">
                          <h3 class="footer-widget-three-contact__title">Contact</h3>
                          <ul class="footer-widget-three__contact-list list-unstyled clearfix">
                              <li>
                                  <div class="icon">
                                      <span class="fas fa-phone-square-alt"></span>
                                  </div>
                                  <div class="text">
                                      <p><a href="#">{{ config('app.sitePhone') }}</a></p>
                                  </div>
                              </li>
                              <li>
                                  <div class="icon">
                                      <span class="fas fa-envelope"></span>
                                  </div>
                                  <div class="text">
                                      <p><a href="#">{{ config('app.siteEmail') }}</a></p>
                                  </div>
                              </li>
                              <li>
                                  <div class="icon">
                                      <span class="fas fa-map-marker-alt"></span>
                                  </div>
                                  <div class="text">
                                      <p>{{ config('app.siteAddress') }}</p>
                                  </div>
                              </li>
                          </ul>
                      </div>
                  </div>
              </div>
               <!-- POSTIVE SSL -->
<script type="text/javascript"> //<![CDATA[
  var tlJsHost = ((window.location.protocol == "https:") ? "https://secure.trust-provider.com/" : "http://www.trustlogo.com/");
  document.write(unescape("%3Cscript src='" + tlJsHost + "trustlogo/javascript/trustlogo.js' type='text/javascript'%3E%3C/script%3E"));
//]]></script>
<script type="text/javascript">
  if (typeof window.TrustLogo === "function") {
    window.TrustLogo("https://www.positivessl.com/images/seals/positivessl_trust_seal_md_167x42.png", "POSDV", "none");
  }
</script>
<!-- End POSITIVE SSL -->
          </div>
<div style="text-align:center; color: white;">SWIFTLINK SERVICES LTD. Licensed by <a target="_blank" href="https://ncc.gov.ng" style="color: white;">NCC</a>. License No: CL/S&I/0366/2024</div>
<br/>
      </div>
      <div class="site-footer-three__bottom">
          <div class="container">
              <div class="row">
                  <div class="col-xl-12">
                      <div class="site-footer-three__bottom-inner">
                          <p class="site-footer-three__bottom-text">© All Copyright
                              2023 by <a href="#">{{ config('app.siteName') }}</a>
                          </p>
                          <div class="site-footer-three__social">
                              <a href="https://twitter.com/{{ $data['twitter'] }}"><i class="fab fa-twitter"></i></a>
                              <a href="https://facebook.com/{{ $data['facebook'] }}"><i class="fab fa-facebook"></i></a>
                              <a href="https://instagram.com/{{ $data['instagram'] }}"><i class="fab fa-instagram"></i></a>
                          </div>
                      </div>
                  </div>
              </div>
          </div>
      </div>

  </footer>


  <!--Site Footer End-->
