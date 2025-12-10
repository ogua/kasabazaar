@extends('web.sub-template')

@section('heading','Contact Us - Rose Door To Door Shipping & Logistics')

@section('sub-heading','Contact Us')

@section('main-content')
    <!-- Contact Section -->
    <section id="contact" class="contact section">

        <div class="container" data-aos="fade-up" data-aos-delay="100">
  
          <div class="row gy-4">
  
            <div class="col-lg-6">
              <div class="info-item d-flex flex-column justify-content-center align-items-center" data-aos="fade-up" data-aos-delay="200">
                <i class="bi bi-geo-alt"></i>
                <h3>Address</h3>
                <p>Adako Jachie, Ejisu, Kumasi</p>
                <p></p>
              </div>
            </div><!-- End Info Item -->
  
            <div class="col-lg-3 col-md-6">
              <div class="info-item d-flex flex-column justify-content-center align-items-center" data-aos="fade-up" data-aos-delay="300">
                <i class="bi bi-telephone"></i>
                <h3>Call Us</h3>
                <p><b>USA</b></p>
                <p>+1 (773) 970 – 0129</p>
                <p>+1 (574) 440 – 7460</p>
                <hr>
                <p class="text-bold"><b>GHANA</b></p>
                <p>+233 50 972 5081</p>
                <p>+233 50 972 5073</p>
              </div>
            </div><!-- End Info Item -->
  
            <div class="col-lg-3 col-md-6">
              <div class="info-item d-flex flex-column justify-content-center align-items-center" data-aos="fade-up" data-aos-delay="400">
                <i class="bi bi-envelope"></i>
                <h3>Email Us</h3>
                <p>kasabazaar109@gmail.com</p>
              </div>
            </div><!-- End Info Item -->
  
          </div>
  
          <div class="row gy-4 mt-1">
            <div class="col-lg-6" data-aos="fade-up" data-aos-delay="300">
              <iframe src="https://www.google.com/maps/embed?pb=!1m14!1m8!1m3!1d3962.377613898537!2d-1.510474!3d6.723694!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0xfdbeac7c67646f1%3A0x7f50d40c195153d1!2sAdako%20Jachie%20Rd%2C%20Ejisu%2C%20Ghana!5e0!3m2!1sen!2sus!4v1737047181287!5m2!1sen!2sus" frameborder="0" style="border:0; width: 100%; height: 400px;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
            </div><!-- End Google Maps -->
  
            <div class="col-lg-6">
              <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                  <h5 class="mb-0" style="color: #fff;"><i class="bi bi-envelope me-2"></i>Send Us a Message</h5>
                </div>
                <div class="card-body p-4">
                  @livewire('contact-message-form')
                </div>
              </div>
            </div><!-- End Contact Form -->
  
          </div>
  
        </div>
  
      </section><!-- /Contact Section -->
@endsection