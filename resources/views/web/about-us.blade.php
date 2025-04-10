@extends('web.sub-template')

@section('heading','About - KasaBazaar Shipping & Logistics')

@section('sub-heading','About')


@section('main-content')

<!-- About Section -->
<section id="about" class="about section">
    <div class="container">
        <div class="row position-relative">
            <div class="col-lg-5 about-img" data-aos="zoom-out" data-aos-delay="200">
                <img src="assets/shipping-images/shipping (5).JPG" alt="Rose Door to Door Shipping Image">
            </div>

            <div class="col-lg-7" data-aos="fade-up" data-aos-delay="100">
                <h2 class="inner-title">About Us</h2>
                <div class="our-story">
                    <h4>Rose Door to Door Shipping & Delivery</h4>
                    <h3>Our Story</h3>
                    <p>This most trusted and reliable freight forwarding company, Rose Door to Door Shipping and Delivery from KasaBazaar Limited, has become a household name in Ghana and the African communities in the United States of America, especially in Michigan, Indiana, New York, Kentucky, and more. It was registered and incorporated in [year of incorporation].</p>
                    
                    <p>Our dedicated and professional staff are experienced in freight forwarding and home delivery services. Our shipping experts are dedicated to anticipating and meeting your shipping needs whenever they arise. Our customers enjoy peace of mind and happiness whenever their goods are with us because of the excellent services we have been providing over the years.</p>
                    
                    <p>We are ideally positioned to successfully navigate the global shipping market while remaining committed to delivering the highest level of customer service.</p>

                    <p>The global supply chain in the shipping industry is becoming highly complex and vulnerable. With a dedicated team of professionals, Rose Door to Door Shipping and Delivery is well positioned to provide exceptional logistics solutions. Over the years, we have been simplifying our customers' supply chain processes.</p>

                    <p>From the sender’s residence or point of contact to the recipient’s residence or point of contact, Rose Door to Door Shipping and Delivery ships and delivers goods to and from the USA and Ghana.</p>

                    <div class="watch-video d-flex align-items-center position-relative">
                        <i class="bi bi-play-circle"></i>
                        <a href="#" class="glightbox stretched-link">Watch Video</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- /About Section -->

    <!-- Stats Counter Section -->
    <section id="stats-counter" class="stats-counter section">

      <!-- Section Title -->
      <div class="container section-title" data-aos="fade-up">
        <h2>Stats</h2>
        <p>Necessitatibus eius consequatur ex aliquid fuga eum quidem sint consectetur velit</p>
      </div><!-- End Section Title -->

      <div class="container" data-aos="fade-up" data-aos-delay="100">

        <div class="row gy-4">

          <div class="col-lg-3 col-md-6">
            <div class="stats-item d-flex align-items-center w-100 h-100">
              <i class="bi bi-emoji-smile color-blue flex-shrink-0"></i>
              <div>
                <span data-purecounter-start="0" data-purecounter-end="232" data-purecounter-duration="1" class="purecounter"></span>
                <p>Happy Clients</p>
              </div>
            </div>
          </div><!-- End Stats Item -->

          <div class="col-lg-3 col-md-6">
            <div class="stats-item d-flex align-items-center w-100 h-100">
              <i class="bi bi-journal-richtext color-orange flex-shrink-0"></i>
              <div>
                <span data-purecounter-start="0" data-purecounter-end="521" data-purecounter-duration="1" class="purecounter"></span>
                <p>Projects</p>
              </div>
            </div>
          </div><!-- End Stats Item -->

          <div class="col-lg-3 col-md-6">
            <div class="stats-item d-flex align-items-center w-100 h-100">
              <i class="bi bi-headset color-green flex-shrink-0"></i>
              <div>
                <span data-purecounter-start="0" data-purecounter-end="1463" data-purecounter-duration="1" class="purecounter"></span>
                <p>Hours Of Support</p>
              </div>
            </div>
          </div><!-- End Stats Item -->

          <div class="col-lg-3 col-md-6">
            <div class="stats-item d-flex align-items-center w-100 h-100">
              <i class="bi bi-people color-pink flex-shrink-0"></i>
              <div>
                <span data-purecounter-start="0" data-purecounter-end="15" data-purecounter-duration="1" class="purecounter"></span>
                <p>Hard Workers</p>
              </div>
            </div>
          </div><!-- End Stats Item -->

        </div>

      </div>

    </section><!-- /Stats Counter Section -->

    <!-- Alt Services Section -->
    @include('web.why-choose-us')
    <!-- /Alt Services Section -->

    <!-- Alt Services 2 Section -->
    <section id="alt-services-2" class="alt-services-2 section">

    <div class="container">

        <div class="row justify-content-around gy-4">

            <div class="col-lg-6 d-flex flex-column justify-content-center order-2 order-lg-1" data-aos="fade-up" data-aos-delay="100">
                <h3>Operational States in the USA & Current Destinations</h3>
                <p>We proudly operate in several states across the United States, offering reliable shipping services to various destinations, including Ghana. Our operations ensure that goods are shipped efficiently and securely.</p>

                <div class="row">

                    <div class="col-lg-6 icon-box d-flex">
                        <i class="bi bi-map flex-shrink-0"></i>
                        <div>
                            <h4>Operational States in the USA</h4>
                            <ul>
                                <li>Michigan</li>
                                <li>Illinois</li>
                                <li>Indiana</li>
                                <li>New York</li>
                                <li>New Jersey</li>
                                <li>Kentucky</li>
                                <li>Ohio</li>
                            </ul>
                        </div>
                    </div><!-- End Icon Box -->

                    <div class="col-lg-6 icon-box d-flex">
                        <i class="bi bi-globe flex-shrink-0"></i>
                        <div>
                            <h4>Current Destinations</h4>
                            <ul>
                                <li>Any state in the USA</li>
                                <li>Ghana</li>
                            </ul>
                        </div>
                    </div><!-- End Icon Box -->

                </div>

            </div>

            <div class="features-image col-lg-5 order-1 order-lg-2" data-aos="fade-up" data-aos-delay="200">
                <img src="assets/img/shipping (11).jpg" alt="Shipping Services">
            </div>

        </div>

    </div>

</section>
<!-- /Alt Services 2 Section -->

    <!-- Team Section -->
    <section id="team" class="team section">

      <!-- Section Title -->
      <div class="container section-title" data-aos="fade-up">
        <h2>Team</h2>
        <p>Meet Our Team</p>
      </div><!-- End Section Title -->

      <div class="container">

        <div class="row gy-5">

          <div class="col-lg-4 col-md-6 member" data-aos="fade-up" data-aos-delay="100">
            <div class="member-img">
              <img src="assets/team/team-1.jpg" class="img-fluid" alt="">
              <div class="social">
                <a href="#"><i class="bi bi-twitter-x"></i></a>
                <a href="#"><i class="bi bi-facebook"></i></a>
                <a href="#"><i class="bi bi-instagram"></i></a>
                <a href="#"><i class="bi bi-linkedin"></i></a>
              </div>
            </div>
            <div class="member-info text-center">
              <h4>J.W. Abel</h4>
              <span>Director</span>
            </div>
          </div><!-- End Team Member -->

          <div class="col-lg-4 col-md-6 member" data-aos="fade-up" data-aos-delay="200">
            <div class="member-img">
              <img src="assets/team/team-2.jpg" class="img-fluid" alt="">
              <div class="social">
                <a href="#"><i class="bi bi-twitter-x"></i></a>
                <a href="#"><i class="bi bi-facebook"></i></a>
                <a href="#"><i class="bi bi-instagram"></i></a>
                <a href="#"><i class="bi bi-linkedin"></i></a>
              </div>
            </div>
            <div class="member-info text-center">
              <h4>Rose N. Adel</h4>
              <span>CEO</span>
            </div>
          </div><!-- End Team Member -->

          <div class="col-lg-4 col-md-6 member" data-aos="fade-up" data-aos-delay="300">
            <div class="member-img">
              <img src="assets/team/team-3.jpg" class="img-fluid" alt="">
              <div class="social">
                <a href="#"><i class="bi bi-twitter-x"></i></a>
                <a href="#"><i class="bi bi-facebook"></i></a>
                <a href="#"><i class="bi bi-instagram"></i></a>
                <a href="#"><i class="bi bi-linkedin"></i></a>
              </div>
            </div>
            <div class="member-info text-center">
              <h4>Alfred Oduro</h4>
              <span>Site Engineer</span>
            </div>
          </div><!-- End Team Member -->

          <div class="col-lg-4 col-md-6 member" data-aos="fade-up" data-aos-delay="400">
            <div class="member-img">
              <img src="assets/team/team-4.png" class="img-fluid" alt="">
              <div class="social">
                <a href="#"><i class="bi bi-twitter-x"></i></a>
                <a href="#"><i class="bi bi-facebook"></i></a>
                <a href="#"><i class="bi bi-instagram"></i></a>
                <a href="#"><i class="bi bi-linkedin"></i></a>
              </div>
            </div>
            <div class="member-info text-center">
              <h4>Tina Arhin</h4>
              <span>Admin Secretary</span>
            </div>
          </div><!-- End Team Member -->

          <div class="col-lg-4 col-md-6 member" data-aos="fade-up" data-aos-delay="500">
            <div class="member-img">
              <img src="assets/team/team-5.jpg" class="img-fluid" alt="">
              <div class="social">
                <a href="#"><i class="bi bi-twitter-x"></i></a>
                <a href="#"><i class="bi bi-facebook"></i></a>
                <a href="#"><i class="bi bi-instagram"></i></a>
                <a href="#"><i class="bi bi-linkedin"></i></a>
              </div>
            </div>
            <div class="member-info text-center">
              <h4>Paul Adel Owusu</h4>
              <span>Architect</span>
            </div>
          </div><!-- End Team Member -->

          <div class="col-lg-4 col-md-6 member" data-aos="fade-up" data-aos-delay="600">
            <div class="member-img">
              <img src="assets/team/team-6.jpg" class="img-fluid" alt="">
              <div class="social">
                <a href="#"><i class="bi bi-twitter-x"></i></a>
                <a href="#"><i class="bi bi-facebook"></i></a>
                <a href="#"><i class="bi bi-instagram"></i></a>
                <a href="#"><i class="bi bi-linkedin"></i></a>
              </div>
            </div>
            <div class="member-info text-center">
              <h4>Abigail Nkansa</h4>
              <span>Property Management Head</span>
            </div>
          </div><!-- End Team Member -->


          <div class="col-lg-4 col-md-6 member" data-aos="fade-up" data-aos-delay="600">
            <div class="member-img">
              <img src="assets/team/team-7.jpg" class="img-fluid" alt="">
              <div class="social">
                <a href="#"><i class="bi bi-twitter-x"></i></a>
                <a href="#"><i class="bi bi-facebook"></i></a>
                <a href="#"><i class="bi bi-instagram"></i></a>
                <a href="#"><i class="bi bi-linkedin"></i></a>
              </div>
            </div>
            <div class="member-info text-center">
              <h4>Pastor Joe Quao</h4>
              <span>Chaplain</span>
            </div>
          </div><!-- End Team Member -->


          <div class="col-lg-4 col-md-6 member" data-aos="fade-up" data-aos-delay="600">
            <div class="member-img">
              <img src="assets/team/team-8.png" class="img-fluid" alt="">
              <div class="social">
                <a href="#"><i class="bi bi-twitter-x"></i></a>
                <a href="#"><i class="bi bi-facebook"></i></a>
                <a href="#"><i class="bi bi-instagram"></i></a>
                <a href="#"><i class="bi bi-linkedin"></i></a>
              </div>
            </div>
            <div class="member-info text-center">
              <h4>Abdul-Latif Ana’ambono, ISSAH​</h4>
              <span>Quantity Surveyor</span>
            </div>
          </div><!-- End Team Member -->


          <div class="col-lg-4 col-md-6 member" data-aos="fade-up" data-aos-delay="600">
            <div class="member-img">
              <img src="assets/team/team-9.png" class="img-fluid" alt="">
              <div class="social">
                <a href="#"><i class="bi bi-twitter-x"></i></a>
                <a href="#"><i class="bi bi-facebook"></i></a>
                <a href="#"><i class="bi bi-instagram"></i></a>
                <a href="#"><i class="bi bi-linkedin"></i></a>
              </div>
            </div>
            <div class="member-info text-center">
              <h4>Reindorf Nkansah Dapaah</h4>
              <span>Property Management Head</span>
            </div>
          </div><!-- End Team Member -->

          <div class="col-lg-4 col-md-6 member" data-aos="fade-up" data-aos-delay="600">
            <div class="member-img">
              <img src="assets/team/team-10.jpg" class="img-fluid" alt="">
              <div class="social">
                <a href="#"><i class="bi bi-twitter-x"></i></a>
                <a href="#"><i class="bi bi-facebook"></i></a>
                <a href="#"><i class="bi bi-instagram"></i></a>
                <a href="#"><i class="bi bi-linkedin"></i></a>
              </div>
            </div>
            <div class="member-info text-center">
              <h4>Esther Gyebour Fosu</h4>
              <span>Property Management Head</span>
            </div>
          </div><!-- End Team Member -->


          <div class="col-lg-4 col-md-6 member" data-aos="fade-up" data-aos-delay="600">
            <div class="member-img">
              <img src="assets/team/team-11.png" class="img-fluid" alt="">
              <div class="social">
                <a href="#"><i class="bi bi-twitter-x"></i></a>
                <a href="#"><i class="bi bi-facebook"></i></a>
                <a href="#"><i class="bi bi-instagram"></i></a>
                <a href="#"><i class="bi bi-linkedin"></i></a>
              </div>
            </div>
            <div class="member-info text-center">
              <h4>Mr. Solomon Kutiame</h4>
              <span>I.T Manager</span>
            </div>
          </div><!-- End Team Member -->


          <div class="col-lg-4 col-md-6 member" data-aos="fade-up" data-aos-delay="600">
            <div class="member-img">
              <img src="assets/team/team-12.jpg" class="img-fluid" alt="">
              <div class="social">
                <a href="#"><i class="bi bi-twitter-x"></i></a>
                <a href="#"><i class="bi bi-facebook"></i></a>
                <a href="#"><i class="bi bi-instagram"></i></a>
                <a href="#"><i class="bi bi-linkedin"></i></a>
              </div>
            </div>
            <div class="member-info text-center">
              <h4>Osei Kwaku​</h4>
              <span>Architect</span>
            </div>
          </div><!-- End Team Member -->

        </div>

      </div>

    </section><!-- /Team Section -->

    <!-- Testimonials Section -->
    @include('web.testimonial')
    <!-- /Testimonials Section -->

@endsection