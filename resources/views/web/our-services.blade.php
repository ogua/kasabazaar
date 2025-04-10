@extends('web.sub-template')

@section('heading','Services - KasaBazaar Shipping & Logistics')

@section('sub-heading','About')


@section('main-content')

<!-- Services Section -->
@include('web.our-service')
<!-- /Services Section -->

<!-- Features Cards Section -->
<section id="features-cards" class="features-cards section">

  <div class="container">

    <div class="row gy-4">

      <div class="col-lg-3 col-md-6" data-aos="fade-up" data-aos-delay="100">
        <h3>Quasi eaque omnis</h3>
        <p>Eius non minus autem soluta ut ui labore omnis quisquam corrupti autem odit voluptas quos commodi magnam occaecati.</p>
        <ul class="list-unstyled">
          <li><i class="bi bi-check2"></i> <span>Ullamco laboris nisi ut aliquip</span></li>
          <li><i class="bi bi-check2"></i> <span>Duis aute irure dolor in reprehenderit</span></li>
          <li><i class="bi bi-check2"></i> <span>Ullamco laboris nisi ut aliquip ex ea</span></li>
        </ul>
      </div><!-- End feature item-->

      <div class="col-lg-3 col-md-6" data-aos="fade-up" data-aos-delay="200">
        <h3>Et nemo dolores consectetur</h3>
        <p>Ducimus ea quam et occaecati est. Temporibus in soluta labore voluptates aut. Et sit soluta non repellat sed quia dire plovers tradoria</p>

        <ul class="list-unstyled">
          <li><i class="bi bi-check2"></i> <span>Enim temporibus maiores eligendi</span></li>
          <li><i class="bi bi-check2"></i> <span>Ut maxime ut quibusdam quam qui</span></li>
          <li><i class="bi bi-check2"></i> <span>Officiis aspernatur in officiis</span></li>
        </ul>
      </div><!-- End feature item-->

      <div class="col-lg-3 col-md-6" data-aos="fade-up" data-aos-delay="300">
        <h3>Staque laboriosam modi</h3>
        <p>Velit eos error et dolor omnis voluptates nobis tenetur sed enim nihil vero qui suscipit ipsum at magni. Ipsa architecto consequatur aliquam</p>
        <ul class="list-unstyled">
          <li><i class="bi bi-check2"></i> <span>Quis voluptates laboriosam numquam</span></li>
          <li><i class="bi bi-check2"></i> <span>Debitis eos est est corrupti</span></li>
        </ul>
      </div><!-- End feature item-->

      <div class="col-lg-3 col-md-6" data-aos="fade-up" data-aos-delay="400">
        <h3>Dignissimos suscipit iste</h3>
        <p>Molestiae occaecati assumenda quia saepe nobis recusandae at dicta ducimus sequi numquam commodi est in consequatur ea magnam quia itaque</p>
        <ul class="list-unstyled">
          <li><i class="bi bi-check2"></i> <span>Veritatis qui reprehenderit quis</span></li>
          <li><i class="bi bi-check2"></i> <span>Accusantium vel numquam sunt minus</span></li>
          <li><i class="bi bi-check2"></i> <span>Voluptatem pariatur est sationem</span></li>
        </ul>
      </div><!-- End feature item-->

    </div>

  </div>

</section><!-- /Features Cards Section -->

<!-- Alt Services 2 Section -->
<section id="alt-services-2" class="alt-services-2 section">

  <div class="container">

    <div class="row justify-content-around gy-4">

      <div class="col-lg-6 d-flex flex-column justify-content-center order-2 order-lg-1" data-aos="fade-up" data-aos-delay="100">
        <h3>Enim quis est voluptatibus aliquid consequatur</h3>
        <p>Esse voluptas cumque vel exercitationem. Reiciendis est hic accusamus. Non ipsam et sed minima temporibus laudantium. Soluta voluptate sed facere corporis dolores excepturi</p>

        <div class="row">

          <div class="col-lg-6 icon-box d-flex">
            <i class="bi bi-easel flex-shrink-0"></i>
            <div>
              <h4>Lorem Ipsum</h4>
              <p>Voluptatum deleniti atque corrupti quos dolores et quas molestias </p>
            </div>
          </div><!-- End Icon Box -->

          <div class="col-lg-6 icon-box d-flex">
            <i class="bi bi-patch-check flex-shrink-0"></i>
            <div>
              <h4>Nemo Enim</h4>
              <p>At vero eos et accusamus et iusto odio dignissimos ducimus qui blanditiise</p>
            </div>
          </div><!-- End Icon Box -->

          <div class="col-lg-6 icon-box d-flex">
            <i class="bi bi-brightness-high flex-shrink-0"></i>
            <div>
              <h4>Dine Pad</h4>
              <p>Explicabo est voluptatum asperiores consequatur magnam. Et veritatis odit</p>
            </div>
          </div><!-- End Icon Box -->

          <div class="col-lg-6 icon-box d-flex">
            <i class="bi bi-brightness-high flex-shrink-0"></i>
            <div>
              <h4>Tride clov</h4>
              <p>Est voluptatem labore deleniti quis a delectus et. Saepe dolorem libero sit</p>
            </div>
          </div><!-- End Icon Box -->

        </div>

      </div>

      <div class="features-image col-lg-5 order-1 order-lg-2" data-aos="fade-up" data-aos-delay="200">
        <img src="assets/img/features-3-2.jpg" alt="">
      </div>

    </div>

  </div>

</section><!-- /Alt Services 2 Section -->

<!-- Testimonials Section -->
@include('web.testimonial')
<!-- /Testimonials Section -->

@endsection