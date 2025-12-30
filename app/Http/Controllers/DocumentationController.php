<?php

namespace App\Http\Controllers;

class DocumentationController extends Controller
{
    /**
     * Display the getting started page.
     */
    public function index()
    {
        return view('guide.getting-started');
    }

    /**
     * Display a specific documentation page.
     */
    public function show($page)
    {
        // Whitelist allowed pages to prevent LFI
        $allowedPages = [
            'getting-started',
            'estimates',
            'approvals',
            'reports',
            'inventory',
            'workflow',
            'admin',
            'api',
            'faq',
        ];

        if (! in_array($page, $allowedPages)) {
            abort(404);
        }

        return view("guide.{$page}");
    }
}
