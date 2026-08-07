<footer class="footer">
    <div class="container">
        <div class="footer-top">
            <div class="row">
                <div class="col-lg-4 col-sm-6">
                    <div class="widget widget-about">
                        <a href="{{ route('storefront.home') }}" class="logo-footer">
                            <img src="{{ asset('storefront/assets/images/demos/demo2/footer-logo.png') }}" alt="{{ config('app.name') }}" width="144" height="45" />
                        </a>
                        <div class="widget-body">
                            <p class="widget-about-title">Got a question?</p>
                            <a href="mailto:{{ config('mail.from.address') }}" class="widget-about-call">{{ config('mail.from.address') }}</a>
                            <p class="widget-about-desc">A multi-vendor marketplace connecting local sellers with shoppers across Ghana.</p>
                            <div class="social-icons social-icons-colored">
                                <a href="#" class="social-icon social-facebook w-icon-facebook"></a>
                                <a href="#" class="social-icon social-twitter w-icon-twitter"></a>
                                <a href="#" class="social-icon social-instagram w-icon-instagram"></a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-sm-6">
                    <div class="widget">
                        <h3 class="widget-title">Company</h3>
                        <ul class="widget-body">
                            <li><a href="{{ route('storefront.about') }}">About Us</a></li>
                            <li><a href="{{ route('storefront.contact') }}">Contact Us</a></li>
                            <li><a href="{{ route('storefront.become-vendor') }}">Sell on {{ config('app.name') }}</a></li>
                            <li><a href="{{ route('storefront.faq') }}">FAQs</a></li>
                        </ul>
                    </div>
                </div>
                <div class="col-lg-3 col-sm-6">
                    <div class="widget">
                        <h4 class="widget-title">My Account</h4>
                        <ul class="widget-body">
                            <li><a href="{{ route('storefront.account.orders') }}">Track My Order</a></li>
                            <li><a href="{{ route('storefront.cart') }}">View Cart</a></li>
                            <li><a href="{{ route('storefront.login') }}">Sign In</a></li>
                            <li><a href="{{ route('storefront.wishlist') }}">My Wishlist</a></li>
                        </ul>
                    </div>
                </div>
                <div class="col-lg-2 col-sm-6">
                    <div class="widget">
                        <h4 class="widget-title">Vendors</h4>
                        <ul class="widget-body">
                            <li><a href="{{ route('storefront.vendors') }}">All Vendors</a></li>
                            <li><a href="{{ route('storefront.become-vendor') }}">Become a Vendor</a></li>
                            <li><a href="{{ route('vendor.login') }}">Vendor Sign In</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <div class="footer-left">
                <p class="copyright">Copyright © {{ now()->year }} {{ config('app.name') }}. All Rights Reserved.</p>
            </div>
            <div class="footer-right">
                <span class="payment-label mr-lg-8">We're using safe payment for</span>
                <figure class="payment">
                    <img src="{{ asset('storefront/assets/images/payment.png') }}" alt="payment" width="159" height="25" />
                </figure>
            </div>
        </div>
    </div>
</footer>
