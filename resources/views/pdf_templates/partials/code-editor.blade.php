<div x-show="activeTab === 'html'" class="absolute inset-0 flex flex-col pt-3 bg-white">
    <div class="px-4 pb-2">
        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-3">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd"
                            d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                            clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-yellow-700">
                        <strong>Warning:</strong> Changes made here will be lost if you modify blocks in the Visual
                        Builder.
                    </p>
                </div>
            </div>
        </div>
    </div>
    <textarea id="htmlEditor" name="html_content" x-model="html"
        class="w-full h-full p-4 font-mono text-sm bg-gray-900 text-gray-100 resize-none focus:ring-0 border-0"
        spellcheck="false"></textarea>
</div>
<div x-show="activeTab === 'css'" class="absolute inset-0 flex flex-col pt-3 bg-white">
    <div class="px-4 pb-2">
        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-3">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd"
                            d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                            clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-yellow-700">
                        <strong>Warning:</strong> Changes made here will be lost if you modify blocks in the Visual
                        Builder.
                    </p>
                </div>
            </div>
        </div>
    </div>
    <textarea id="cssEditor" name="css_content" x-model="css"
        class="w-full h-full p-4 font-mono text-sm bg-gray-900 text-gray-100 resize-none focus:ring-0 border-0"
        spellcheck="false"></textarea>
</div>