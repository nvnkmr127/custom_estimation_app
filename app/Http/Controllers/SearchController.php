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

        // Search Estimates
        $estimates = Estimate::where('estimate_number', 'like', "%{$query}%")
            ->orWhere('title', 'like', "%{$query}%")
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
        $clients = Client::where('name', 'like', "%{$query}%")
            ->orWhere('company', 'like', "%{$query}%")
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
        $products = Product::where('name', 'like', "%{$query}%")
            ->limit(5)
            ->get();

        foreach ($products as $product) {
            $results[] = [
                'type' => 'Product',
                'title' => $product->name,
                'url' => route('products.edit', $product),
                'icon' => 'cube',
            ];
        }

        if ($request->expectsJson()) {
            return response()->json($results);
        }

        return view('search.index', compact('results', 'query'));
    }
}
