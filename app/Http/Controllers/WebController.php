<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use App\Models\BlogCategory;
use Illuminate\Http\Request;

class WebController extends Controller
{
    public function aboutUs()
    {
        return view('web.about-us'); 
    }

    public function ourservices()
    {
        return view('web.our-services'); 
    }

    public function ourprojects()
    {
        return view('web.our-projects');
    }

    public function tracking()
    {
        return view('web.tracking');
    }

    public function feeback()
    {
        return view('web.feeback');
    }


    public function contactus()
    {
        return view('web.contact-us');
    }

    public function ourblog()
    {
        $blogplosts = Blog::with(['category','user'])->where('status', 1)->latest()->get();

       // dd($blogplosts);

        return view('web.our-blog',compact('blogplosts'));
    }

    public function blogDetails($slug)
    {
        $blogplost = Blog::with(['category','user'])->where('slug', $slug)
        ->first();

        $categories = BlogCategory::all();
        return view('web.blog-details', compact('blogplost','categories'));
    }

    public function privacyPolicy()
    {
        return view('web.privacy-policy');
    }
    




}
