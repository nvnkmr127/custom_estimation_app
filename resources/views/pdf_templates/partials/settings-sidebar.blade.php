<div class="w-80 bg-gray-50 border-r border-gray-200 flex flex-col overflow-y-auto">
    <div class="p-4 space-y-4">
        <div>
            <label class="block text-sm font-medium text-gray-700">Template Name</label>
            <input type="text" name="name" value="{{ old('name', $pdfTemplate->name) }}" required
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
        </div>

        <div class="flex items-center">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', $pdfTemplate->exists ? $pdfTemplate->is_active : true) ? 'checked' : '' }}
                class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
            <label for="is_active" class="ml-2 block text-sm text-gray-900">Active</label>
        </div>

        <hr class="border-gray-200">

        <!-- PDF Settings -->
        <div>
            <label class="block text-sm font-medium text-gray-700">Paper Size</label>
            <select name="paper_size" x-model="paperSize"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                <option value="a4">A4 (210mm x 297mm)</option>
                <option value="letter">Letter (216mm x 279mm)</option>
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">Orientation</label>
            <select name="orientation" x-model="orientation"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                <option value="portrait">Portrait</option>
                <option value="landscape">Landscape</option>
            </select>
        </div>

        <hr class="border-gray-200">

        <!-- Styling Settings -->
        <div>
            <label class="block text-sm font-medium text-gray-700">Primary Color</label>
            <div class="mt-1 flex items-center space-x-2">
                <input type="color" name="primary_color" x-model="primaryColor"
                    class="h-8 w-14 p-0 border-0 rounded cursor-pointer">
                <input type="text" x-model="primaryColor"
                    class="flex-1 block w-full rounded-md border-gray-300 sm:text-sm">
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">Secondary Color</label>
            <div class="mt-1 flex items-center space-x-2">
                <input type="color" name="secondary_color" x-model="secondaryColor"
                    class="h-8 w-14 p-0 border-0 rounded cursor-pointer">
                <input type="text" x-model="secondaryColor"
                    class="flex-1 block w-full rounded-md border-gray-300 sm:text-sm">
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">Font Family</label>
            <select name="font_family" x-model="fontFamily"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                <option value="Helvetica">Helvetica (Sans-serif)</option>
                <option value="Courier">Courier (Monospace)</option>
                <option value="Times">Times New Roman (Serif)</option>
                <option value="Dejavu Sans">Dejavu Sans (UTF-8 support)</option>
            </select>
        </div>

        <hr class="border-gray-200">

        <!-- Theme Presets -->
        <div>
            <label class="block text-sm font-medium text-gray-700">Load Theme Preset</label>
            <select @change="loadSystemTemplate($event.target.value); $event.target.value=''"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                <option value="">Select a Theme...</option>
                @foreach($systemTemplates as $sysTemplate)
                    <option value="{{ $sysTemplate->id }}">{{ $sysTemplate->name }}</option>
                @endforeach
            </select>
            <p class="mt-1 text-xs text-gray-500">Choosing a preset will overwrite current content.</p>
        </div>

        <hr class="border-gray-200">

        <!-- Security & Control -->
        <div>
            <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Security & Control
            </h3>

            <div class="space-y-3">
                <div class="flex items-center">
                    <input type="checkbox" id="is_locked" name="is_locked" value="1" {{ old('is_locked', $pdfTemplate->is_locked) ? 'checked' : '' }}
                        class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                    <label for="is_locked" class="ml-2 block text-sm text-gray-900">Lock Template <span
                            class="text-xs text-gray-500">(Admins Only)</span></label>
                </div>

                <div class="flex items-center">
                    <input type="checkbox" id="is_password_protected" name="is_password_protected" value="1" {{ old('is_password_protected', $pdfTemplate->is_password_protected) ? 'checked' : '' }}
                        class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                    <label for="is_password_protected" class="ml-2 block text-sm text-gray-900">Password Protect
                        <br><span class="text-xs text-gray-500">(Uses Client Email)</span></label>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Watermark Text</label>
                    <input type="text" name="watermark_text" x-model="watermarkText"
                        value="{{ old('watermark_text', $pdfTemplate->watermark_text) }}"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                        placeholder="e.g. DRAFT">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Watermark Opacity</label>
                    <div class="flex items-center gap-2">
                        <input type="range" name="watermark_opacity" x-model="watermarkOpacity" min="0" max="1"
                            step="0.1" value="{{ old('watermark_opacity', $pdfTemplate->watermark_opacity) }}"
                            class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer">
                    </div>
                </div>
            </div>
        </div>

        <hr class="border-gray-200">

        <div>
            <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Variables Cheat
                Sheet</h3>
            <div class="text-xs text-gray-600 space-y-1 font-mono bg-gray-100 p-2 rounded max-h-96 overflow-y-auto">
                @foreach($variables as $category => $vars)
                    <div class="font-bold text-gray-800 mt-2">{{ $category }}</div>
                    @foreach($vars as $key => $desc)
                        <div class="group cursor-pointer hover:text-indigo-600" title="{{ $desc }}"
                            @click="insertAtCursor('{ {{ $key }} }')">
                            { {{ $key }} } <span class="hidden group-hover:inline text-gray-400">-
                                {{ Str::limit($desc, 20) }}</span>
                        </div>
                    @endforeach
                @endforeach
            </div>
        </div>
    </div>
</div>