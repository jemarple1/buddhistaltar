<?php

namespace App\Http\Controllers;

use App\Support\DirectoryRegistry;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        return view('directory', [
            'directory' => DirectoryRegistry::config(),
            'rows' => DirectoryRegistry::rows(),
            'seo' => DirectoryRegistry::seoMeta(),
        ]);
    }
}
