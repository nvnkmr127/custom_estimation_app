@extends('guide.layout')

@section('content')
    <div class="mb-10">
        <h1 class="text-3xl font-extrabold text-slate-900">Developer API</h1>
        <p class="mt-4 text-lg text-slate-600">Integrate external tools with the estimation engine.</p>
    </div>

    <div class="bg-slate-900 rounded-xl overflow-hidden shadow-lg mb-12">
        <div class="bg-slate-800 px-6 py-4 flex items-center justify-between">
            <span class="text-slate-300 font-mono text-sm">GET /api/v1/estimates</span>
            <span class="bg-green-500 text-white text-xs px-2 py-1 rounded">PUBLIC</span>
        </div>
        <div class="p-6 text-slate-300 font-mono text-sm leading-relaxed">
            <span class="text-purple-400">curl</span> -X GET "https://app.estimation.com/api/v1/estimates" \<br>
            &nbsp;&nbsp;-H "Authorization: Bearer <span class="text-yellow-400">{YOUR_API_KEY}</span>" \<br>
            &nbsp;&nbsp;-H "Content-Type: application/json"
        </div>
    </div>

    <div class="mb-16">
        <h2 class="text-2xl font-bold text-slate-900 mb-6">Authentication</h2>
        <p class="text-slate-600 mb-4">All API requests must include a Bearer Token in the header.</p>
        <ul class="list-disc list-inside text-slate-600 space-y-2">
            <li>Generate tokens in <strong>Profile > API Keys</strong>.</li>
            <li>Tokens expire after 90 days.</li>
        </ul>
    </div>

    <div class="mb-16">
        <h2 class="text-2xl font-bold text-slate-900 mb-6">Webhooks</h2>
        <p class="text-slate-600 mb-4">We support outgoing webhooks for the following events:</p>
        <table class="min-w-full divide-y divide-slate-200 border border-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Event</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Payload</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-slate-200">
                <tr>
                    <td class="px-6 py-4 text-sm font-medium text-slate-900">estimate.signed</td>
                    <td class="px-6 py-4 text-sm text-slate-500">JSON object containing the signed PDF URL and Client ID.
                    </td>
                </tr>
                <tr>
                    <td class="px-6 py-4 text-sm font-medium text-slate-900">product.low_stock</td>
                    <td class="px-6 py-4 text-sm text-slate-500">Triggered when inventory count drops below threshold.</td>
                </tr>
            </tbody>
        </table>
    </div>
@endsection