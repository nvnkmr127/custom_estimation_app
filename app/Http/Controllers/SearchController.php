<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Estimate;
use App\Models\Product;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    /**
     * Perform a global search across multiple entities.
     */
    public function __invoke(Request $request)
    {
        $query = $request->get('q');

        if (strlen($query) < 2) {
            if ($request->expectsJson()) {
                return response()->json([]);
            }
            return view('search.index', ['results' => [], 'query' => $query]);
        }

        $results = [];

        // Security: Escape wildcards to prevent SQL Wildcard Injection
        $escapedQuery = str_replace(['%', '_'], ['\%', '\_'], $query);

        // Search Estimates (non-admins only see their own, matching the dashboard/tasks scoping)
        $estimates = Estimate::query()
            ->where(function ($q) use ($escapedQuery) {
                $q->where('estimate_number', 'like', "%{$escapedQuery}%")
                    ->orWhere('title', 'like', "%{$escapedQuery}%");
            })
            ->when(!auth()->user()->isAdmin(), fn ($q) => $q->where('created_by', auth()->id()))
            ->limit(5)
            ->get();

        foreach ($estimates as $estimate) {
            $results[] = [
                'type' => 'Estimate',
                'title' => $estimate->estimate_number . ': ' . $estimate->title,
                'url' => route('estimates.show', $estimate),
                'icon' => 'document-text',
            ];
        }

        // Search Clients
        $clients = Client::where('name', 'like', "%{$escapedQuery}%")
            ->orWhere('company', 'like', "%{$escapedQuery}%")
            ->limit(5)
            ->get();

        foreach ($clients as $client) {
            $results[] = [
                'type' => 'Client',
                'title' => $client->name . ($client->company ? " ({$client->company})" : ''),
                'url' => route('clients.show', $client),
                'icon' => 'user',
            ];
        }

        // Search Products
        $products = Product::active()
            ->where(function($q) use ($escapedQuery) {
                $q->where('name', 'like', "%{$escapedQuery}%")
                  ->orWhere('sku', 'like', "%{$escapedQuery}%");
            })
            ->limit(5)
            ->get();

        foreach ($products as $product) {
            $url = auth()->user()->can('update', $product)
                ? route('products.edit', $product)
                : route('products.index', ['search' => $product->name]);

            $results[] = [
                'type' => 'Product',
                'title' => $product->name,
                'url' => $url,
                'icon' => 'cube',
            ];
        }

        if ($request->expectsJson()) {
            return response()->json($results);
        }

        return view('search.index', compact('results', 'query'));
    }
}
