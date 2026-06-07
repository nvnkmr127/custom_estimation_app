<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class ApiPortalController extends Controller
{
    /**
     * Display the API Developer Portal.
     */
    public function index(Request $request)
    {
        $token = session()->getId();
        $apiUrl = url('/');

        $markdownPath = base_path('docs/api_documentation.md');
        $markdown = File::exists($markdownPath)
            ? File::get($markdownPath)
            : "# API Documentation\nDocumentation file is missing.";

        return view('admin.api_portal.index', compact('token', 'apiUrl', 'markdown'));
    }
}
