<div>
    <div class="mb-6 flex flex-col md:flex-row gap-4">
        <div class="flex-1">
            <label class="block text-xs font-medium text-gray-700 mb-1">Search (Trace ID, Event ID, Error)</label>
            <input type="text" wire:model.live.debounce.300ms="search" class="w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Search logs...">
        </div>
        <div class="w-full md:w-48">
             <label class="block text-xs font-medium text-gray-700 mb-1">Status</label>
             <select wire:model.live="status" class="w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                 <option value="">All Statuses</option>
                 <option value="success">Success</option>
                 <option value="failed">Failed</option>
                 <option value="processing">Processing</option>
                 <option value="retrying">Retrying</option>
             </select>
        </div>
        <div class="w-full md:w-48">
             <label class="block text-xs font-medium text-gray-700 mb-1">Endpoint</label>
             <select wire:model.live="webhookId" class="w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                 <option value="">All Endpoints</option>
                 @foreach($webhooks as $wh)
                     <option value="{{ $wh->id }}">{{ $wh->name }}</option>
                 @endforeach
             </select>
        </div>
        <div class="w-full md:w-32">
             <label class="block text-xs font-medium text-gray-700 mb-1">Time Range</label>
             <select wire:model.live="dateRange" class="w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                 <option value="24h">Last 24h</option>
                 <option value="7d">Last 7 Days</option>
                 <option value="30d">Last 30 Days</option>
             </select>
        </div>
    </div>

    <div class="bg-white shadow overflow-hidden rounded-md">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Timestamp</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Endpoint</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Event</th>
                         <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-24">Resp</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Latency</th>
                        <th scope="col" class="relative px-6 py-3">
                            <span class="sr-only">Expand</span>
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($logs as $log)
                        <!-- Main Row -->
                        <tr class="hover:bg-gray-50 {{ in_array($log->id, $expandedRows) ? 'bg-gray-50' : '' }}">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $log->created_at->format('M d H:i:s') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    {{ $log->status === 'success' ? 'bg-green-100 text-green-800' : '' }}
                                    {{ $log->status === 'failed' ? 'bg-red-100 text-red-800' : '' }}
                                    {{ $log->status === 'processing' ? 'bg-blue-100 text-blue-800' : '' }}
                                    {{ $log->status === 'retrying' ? 'bg-yellow-100 text-yellow-800' : '' }}">
                                    {{ ucfirst($log->status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $log->webhook->name ?? 'Deleted' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $log->event->event_type ?? 'Unknown' }}
                                <div class="text-xs text-gray-400 mt-1">{{ Str::limit($log->webhook_event_id, 8) }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                @if($log->response_status)
                                    <span class="font-mono {{ $log->response_status >= 400 ? 'text-red-600 font-bold' : '' }}">
                                        {{ $log->response_status }}
                                    </span>
                                @else
                                    <span class="text-gray-300">-</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $log->duration_ms ? $log->duration_ms . 'ms' : '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <button wire:click="toggleRow('{{ $log->id }}')" class="text-indigo-600 hover:text-indigo-900 focus:outline-none">
                                    {{ in_array($log->id, $expandedRows) ? 'Collapse' : 'Details' }}
                                </button>
                            </td>
                        </tr>

                        <!-- Details Row -->
                         @if(in_array($log->id, $expandedRows))
                            <tr>
                                <td colspan="7" class="px-6 py-4 bg-gray-50 border-t border-gray-200">
                                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 text-sm">
                                        <!-- Left: Basics & Context -->
                                        <div class="space-y-4">
                                            @if($log->error_message)
                                                <div class="p-3 bg-red-50 border border-red-200 rounded text-red-700 font-mono text-xs whitespace-pre-wrap">
                                                    <strong>Error:</strong> {{ $log->error_message }}
                                                </div>
                                            @endif
                                            
                                            <div>
                                                 <h4 class="font-medium text-gray-900 mb-2">Delivery Context</h4>
                                                 <dl class="grid grid-cols-2 gap-x-4 gap-y-2 text-xs">
                                                     <dt class="text-gray-500">Delivery ID:</dt> <dd class="font-mono">{{ $log->id }}</dd>
                                                     <dt class="text-gray-500">Attempt:</dt> <dd>{{ $log->attempt }}</dd>
                                                     <dt class="text-gray-500">Next Retry:</dt> <dd>{{ $log->next_retry_at ?? 'None' }}</dd>
                                                     <dt class="text-gray-500">Trace ID:</dt> <dd class="font-mono">{{ $log->event->payload['meta']['trace_id'] ?? 'N/A' }}</dd>
                                                 </dl>
                                            </div>

                                            <div>
                                                <h4 class="font-medium text-gray-900 mb-1">Response Body</h4>
                                                <div class="bg-gray-100 p-2 rounded max-h-40 overflow-y-auto font-mono text-xs text-gray-600">
                                                    {{ $log->response_body ?: '(No Response Body)' }}
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Right: Request/Response Headers -->
                                        <div class="space-y-4">
                                            <div>
                                                <h4 class="font-medium text-gray-900 mb-1">Request Headers</h4>
                                                @if(is_array($log->request_headers))
                                                     <div class="bg-gray-100 p-2 rounded max-h-40 overflow-y-auto font-mono text-xs">
                                                        @foreach($log->request_headers as $k => $v)
                                                            <div class="flex"><span class="text-gray-500 w-32 shrink-0">{{ $k }}:</span> <span class="text-gray-800 break-all">{{ is_array($v) ? json_encode($v) : $v }}</span></div>
                                                        @endforeach
                                                     </div>
                                                @else
                                                    <span class="text-gray-400 italic">Not recorded</span>
                                                @endif
                                            </div>

                                            <div>
                                                <h4 class="font-medium text-gray-900 mb-1">Response Headers</h4>
                                                @if(isset($log->response_headers) && is_array($log->response_headers))
                                                     <div class="bg-gray-100 p-2 rounded max-h-40 overflow-y-auto font-mono text-xs">
                                                        @foreach($log->response_headers as $k => $v)
                                                            <div class="flex"><span class="text-gray-500 w-32 shrink-0">{{ $k }}:</span> <span class="text-gray-800 break-all">{{ is_array($v) ? json_encode($v) : $v }}</span></div>
                                                        @endforeach
                                                     </div>
                                                @else
                                                    <span class="text-gray-400 italic">Not recorded</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mt-4 flex justify-end">
                                        <a href="{{ route('admin.webhooks.events.show', $log->webhook_event_id) }}" class="text-indigo-600 hover:text-indigo-900 text-xs font-medium">
                                            View Full Event Timeline &rarr;
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr>
                             <td colspan="7" class="px-6 py-4 text-center text-gray-500 text-sm">
                                 No logs found matching your criteria.
                             </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t border-gray-200">
             {{ $logs->links() }}
        </div>
    </div>
</div>
