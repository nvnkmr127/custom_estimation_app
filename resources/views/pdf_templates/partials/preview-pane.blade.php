<div x-show="activeTab === 'preview'" class="absolute inset-0 bg-gray-200 flex flex-col">
    <!-- Preview Controls -->
    <div class="bg-white border-b border-gray-200 px-4 py-2 flex items-center justify-between shadow-sm z-10">
        <div class="flex items-center gap-4 text-sm">
            <span class="font-bold text-gray-500 uppercase text-xs">Preview Context:</span>

            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" x-model="previewState.roomBased" @change="updatePreview()"
                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                <span>Room Based</span>
            </label>

            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" x-model="previewState.hasTax" @change="updatePreview()"
                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                <span>Has Tax</span>
            </label>

            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" x-model="previewState.hasDiscount" @change="updatePreview()"
                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                <span>Has Discount</span>
            </label>
        </div>
        <div class="text-xs text-gray-400">
            Updates automatically
        </div>
    </div>

    <div class="flex-1 overflow-auto p-4 flex justify-center">
        <div class="bg-white shadow-lg transition-all duration-300 transform origin-top" :style="getPreviewStyle()">
            <iframe id="previewFrame" sandbox="allow-same-origin" class="w-full h-full border-0"></iframe>
        </div>
    </div>