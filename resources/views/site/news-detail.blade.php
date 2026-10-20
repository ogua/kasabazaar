@extends('site.layouts.app')

@section('title', $post['title'])
@section('meta_description', $metaDescription)
@section('og_type', 'article')
@if ($post['image_is_upload'])
@section('og_image', $post['image'])
@endif

@section('content')
   <!-- Promo Section Start -->
   <section class="promo-sec bg-cover jarallax" data-jarallax data-speed=".4">
      <img src="{{ asset('site/images/promo.jpg') }}" alt="" class="jarallax-img">
      <div class="parallax-overly"></div>
      <div class="container">
         <div class="row">
            <div class="col-lg-12">
               <div class="promo-wrap">
                  <h1 class="display-2 text-info">News</h1>
                  <nav aria-label="breadcrumb">
                     <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('home-page') }}">Home</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('our-blog') }}">News</a></li>
                        <li class="breadcrumb-item active" aria-current="page">
                           {{ $post['title'] }}
                        </li>
                     </ol>
                  </nav>
               </div>
            </div>
         </div>
      </div>
   </section>
   <!-- Promo Section End -->

   <!-- Post Section Start -->
   <section class="post-single sec-padding">
      <div class="container">
         <div class="row">
            <div class="col-lg-9 mx-auto">

                  <article>
                     <div class="post-header mb-4">
                        <div class="entry-meta d-flex gap-4 mb-3">
                           @if (!empty($post['category']))
                              <span><i class="fa fa-folder"></i> {{ $post['category'] }}</span>
                           @endif
                           @if ($post['posted_at'])
                              <span><i class="fa fa-calendar"></i> {{ $post['posted_at']->format('M j, Y') }}</span>
                           @endif
                           @if (!empty($post['postedby']))
                              <span><i class="fa fa-user"></i> {{ $post['postedby'] }}</span>
                           @endif
                        </div>
                        <h2 class="sec-title">{{ $post['title'] }}</h2>
                     </div>

                     @if ($post['image_is_upload'])
                        <div class="entry-thumb mb-5 round overflow-hidden">
                           <img src="{{ $post['image'] }}"
                              alt="{{ $post['title'] }}" class="img-fluid w-100">
                        </div>
                     @endif

                     @if (!empty($post['description']))
                        <p class="lead">{{ $post['description'] }}</p>
                     @endif

                     <div class="post-body">
                        {!! \App\Services\PublicWebsite::richText($post['content']) !!}
                     </div>

                     @if (!empty($post['references']))
                        <div class="article-footer mt-5 pt-4 border-top">
                           <h3 class="display-6">References</h3>
                           <p class="mb-0">{{ $post['references'] }}</p>
                        </div>
                     @endif

                     <div class="mt-5">
                        <a href="{{ route('our-blog') }}" class="btn btn-primary">
                           <i class="fa fa-arrow-left"></i> All News<span></span>
                        </a>
                     </div>
                  </article>

                  @php
                  // NewsArticle JSON-LD. Mirrors what's actually rendered above rather than
                  // inventing anything: same title/description/image, the same "postedby"
                  // byline shown in entry-meta (only present when the field is filled in —
                  // see the fa-user line above), and $post['keywords'], which the Filament
                  // admin lets staff fill in but which nothing on this site surfaced until now.
                  $article_data = [
                      '@context'      => 'https://schema.org',
                      '@type'         => 'NewsArticle',
                      'headline'      => $post['title'],
                      'description'  => html_entity_decode($metaDescription, ENT_QUOTES),
                      'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => url()->current()],
                      'publisher'    => [
                          '@type' => 'Organization',
                          'name'  => config('group.parent.name'),
                          'logo'  => ['@type' => 'ImageObject', 'url' => asset('site/images/kasarose-logistics-logo.png')],
                      ],
                  ];
                  if ($post['image_is_upload']) {
                      $article_data['image'] = [$post['image']];
                  }
                  $published = $post['posted_at']?->toIso8601String();
                  if ($published) {
                      $article_data['datePublished'] = $published;
                  }
                  $article_data['dateModified'] = $post['updated_at']?->toIso8601String() ?: $published;
                  if ($article_data['dateModified'] === null) {
                      unset($article_data['dateModified']);
                  }
                  if (!empty($post['postedby'])) {
                      $article_data['author'] = ['@type' => 'Person', 'name' => $post['postedby']];
                  } else {
                      $article_data['author'] = ['@type' => 'Organization', 'name' => config('group.parent.name')];
                  }
                  if (!empty($post['keywords'])) {
                      $article_data['keywords'] = $post['keywords'];
                  }
                  @endphp
                  <script type="application/ld+json">{!! json_encode($article_data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>

            </div>
         </div>
      </div>
   </section>
   <!-- Post Section End -->

@endsection
