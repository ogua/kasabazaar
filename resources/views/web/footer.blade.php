<footer id="footer" class="footer dark-background">

    <div class="container footer-top">
      <div class="row gy-4">
        <div class="col-lg-3 col-md-3 footer-about">
          <a href="/" class="logo d-flex align-items-center">
            {{-- <span class="sitename">KasaBazaar</span> --}}
            <img src="{{ URL::to('images/Kasabazaar-logo.jpg') }}" alt="">
          </a>
          <div class="pt-3 footer-contact">
            <p>USA</p>
            <p><strong>Phone:</strong> <span>+1 (773) 970 – 0129</span></p>
            <p class=""><strong>Phone:</strong> <span>+1 (574) 440 – 7460</span></p>

            <p class="mt-3">GHANA</p>
            <p><strong>Phone:</strong> <span>+233 50 972 5081</span></p>
            <p><strong>Phone:</strong> <span>+233 50 972 5073</span></p>

            <p class="mt-3"><strong>Email:</strong> <span>kasabazaar109@gmail.com</span></p>
          </div>
          <div class="mt-4 social-links d-flex">
            <a href="#" aria-label="Twitter/X"><i class="bi bi-twitter-x"></i></a>
            <a href="#" aria-label="Facebook"><i class="bi bi-facebook"></i></a>
            <a href="#" aria-label="Instagram"><i class="bi bi-instagram"></i></a>
            <a href="#" aria-label="LinkedIn"><i class="bi bi-linkedin"></i></a>
          </div>
        </div>

        <div class="col-lg-3 col-md-3 footer-links">
          <h4>Useful Links</h4>
          <ul>
            <li><a href="{{ route('home-page') }}">Home</a></li>
            <li><a href="{{ route('about-us') }}">About us</a></li>
            <li><a href="{{ route('our-services') }}">Services</a></li>
            <li><a href="{{ route('privacy-policy') }}">Privacy policy</a></li>
            <li><a href="{{ route('terms') }}">Terms &amp; conditions</a></li>
          </ul>
        </div>

        <div class="col-lg-3 col-md-3 footer-links">
          <h4>Our Services</h4>
          <ul>
            <li><a href="#">Air Freight Service</a></li>
            <li><a href="#">Sea Freight Service</a></li>
            <li><a href="#">E-commerce shipments</a></li>
            <li><a href="#">Unbonded warehouses</a></li>
          </ul>
        </div>

        {{-- Group cross-links. Driven by config/group.php, which is mirrored in
             the group site, KASAROSE and Neoride repos — see CLAUDE.md. --}}
        <div class="col-lg-3 col-md-3 footer-links">
          <h4>Our Group</h4>
          <ul>
            @foreach (config('group.companies') as $company)
              <li>
                <a href="{{ $company['url'] }}" target="_blank" rel="noopener" title="{{ $company['role'] }}">
                  {{ $company['name'] }}
                </a>
              </li>
            @endforeach
            <li>
              <a href="{{ config('group.parent.companies_url') }}" target="_blank" rel="noopener">
                {{ config('group.parent.name') }}
              </a>
            </li>
          </ul>
        </div>

      </div>
    </div>

    <div class="container mt-4 text-center copyright">
      <p>© {{ date('Y') }} <strong class="px-1 sitename">{{ config('group.company.full_name') }}</strong> — All Rights Reserved</p>
      <p class="small mb-0">
        A company of the
        <a href="{{ config('group.parent.url') }}" target="_blank" rel="noopener">{{ config('group.parent.name') }}</a>
      </p>
      <div class="credits">
        <!-- All the links in the footer should remain intact. -->
        <!-- You can delete the links only if you've purchased the pro version. -->
        <!-- Licensing information: https://bootstrapmade.com/license/ -->
        <!-- Purchase the pro version with working PHP/AJAX contact form: [buy-url] -->
        <!-- Designed by <a href="https://bootstrapmade.com/">Oguses IT Solutions</a> -->
      </div>
    </div>

  </footer>