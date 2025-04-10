<?php

namespace App\Http\Controllers;

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


    public function contactus()
    {
        return view('web.contact-us');
    }
    




}
