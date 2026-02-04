<div x-show="activeTab === 'visual'" class="absolute inset-0 bg-gray-50 flex overflow-hidden">
    <!-- Block Palette -->
    <div class="w-64 bg-white border-r border-gray-200 overflow-y-auto flex flex-col">
        <div class="p-3 bg-blue-50 text-blue-700 text-xs border-b border-blue-100">
            <strong>Note:</strong> This PDF generator uses DomPDF. Avoid <code>flex</code> or <code>grid</code> layouts.
            Use <code>&lt;table&gt;</code> for reliable columns.
        </div>
        <div class="p-3 bg-gray-100 border-b border-gray-200 font-semibold text-xs text-gray-500 uppercase">Available
            Blocks</div>
        <div class="p-2 space-y-2">
            <template x-for="(block, type) in availableBlocks" :key="type">
                <div @click="addBlock(type)"
                    class="cursor-pointer border border-gray-200 rounded p-3 hover:bg-indigo-50 hover:border-indigo-300 transition-colors shadow-sm bg-white">
                    <div class="font-medium text-sm text-gray-800" x-text="block.label"></div>
                    <div class="text-xs text-gray-500" x-text="block.desc"></div>
                </div>
            </template>
        </div>
    </div>

    <!-- Canvas / Stack -->
    <div class="flex-1 overflow-y-auto p-8 bg-gray-100 pb-32">
        <!-- Validation Warning -->
        <div x-show="!blocks.some(b => ['items_table', 'room_items_table'].includes(b.type))"
            class="max-w-3xl mx-auto mb-4 bg-amber-50 border-l-4 border-amber-400 p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-amber-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd"
                            d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                            clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-amber-700">
                        <strong>Critical Missing Element:</strong> Your template must include at least one <strong>Items
                            Table</strong> block to display estimate data.
                    </p>
                </div>
            </div>
        </div>

        <div class="max-w-3xl mx-auto space-y-4" id="sortable-blocks">
            <template x-for="(block, index) in blocks" :key="block.id">
                <div class="bg-white border rounded shadow-sm relative group"
                    :class="{'border-indigo-500 ring-2 ring-indigo-200': selectedBlockIndex === index, 'border-gray-200 hover:border-gray-300': selectedBlockIndex !== index}"
                    @click="selectedBlockIndex = index">

                    <!-- Block Header / Actions -->
                    <div
                        class="flex items-center justify-between p-2 bg-gray-50 border-b border-gray-100 rounded-t cursor-move drag-handle">
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 8h16M4 16h16"></path>
                            </svg>
                            <span class="text-xs font-bold uppercase text-gray-500"
                                x-text="availableBlocks[block.type].label"></span>
                        </div>
                        <div class="flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                            <button type="button" @click.stop="duplicateBlock(index)"
                                class="p-1 hover:bg-blue-100 rounded text-blue-600" title="Duplicate">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z">
                                    </path>
                                </svg>
                            </button>
                            <button type="button" @click.stop="removeBlock(index)"
                                class="p-1 hover:bg-red-100 rounded text-red-600" title="Delete">✕</button>
                        </div>
                    </div>

                    <!-- Block Properties Form -->
                    <div class="p-4" x-show="selectedBlockIndex === index">
                        <div class="grid grid-cols-1 gap-3">
                            <template x-for="(fieldDef, fieldName) in availableBlocks[block.type].fields"
                                :key="fieldName">
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1"
                                        x-text="fieldDef.label"></label>

                                    <template x-if="fieldDef.type === 'text'">
                                        <input type="text" x-model="block.data[fieldName]"
                                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-xs">
                                    </template>

                                    <template x-if="fieldDef.type === 'textarea'">
                                        <textarea x-model="block.data[fieldName]" rows="3"
                                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-xs"></textarea>
                                    </template>

                                    <template x-if="fieldDef.type === 'richtext'">
                                        <div class="border border-gray-300 rounded-md overflow-hidden bg-white">
                                            <!-- Toolbar -->
                                            <div
                                                class="flex items-center gap-1 p-1 bg-gray-50 border-b border-gray-200">
                                                <button type="button" @click="document.execCommand('bold', false, null)"
                                                    class="p-1 hover:bg-gray-200 rounded text-gray-700 font-bold"
                                                    title="Bold">B</button>
                                                <button type="button"
                                                    @click="document.execCommand('italic', false, null)"
                                                    class="p-1 hover:bg-gray-200 rounded text-gray-700 italic"
                                                    title="Italic">I</button>
                                                <button type="button"
                                                    @click="document.execCommand('underline', false, null)"
                                                    class="p-1 hover:bg-gray-200 rounded text-gray-700 underline"
                                                    title="Underline">U</button>
                                                <div class="w-px h-4 bg-gray-300 mx-1"></div>
                                                <button type="button"
                                                    @click="document.execCommand('insertUnorderedList', false, null)"
                                                    class="p-1 hover:bg-gray-200 rounded text-gray-700"
                                                    title="Bulleted List">• List</button>
                                                <button type="button"
                                                    @click="document.execCommand('justifyLeft', false, null)"
                                                    class="p-1 hover:bg-gray-200 rounded text-gray-700"
                                                    title="Align Left">←</button>
                                                <button type="button"
                                                    @click="document.execCommand('justifyCenter', false, null)"
                                                    class="p-1 hover:bg-gray-200 rounded text-gray-700"
                                                    title="Align Center">↔</button>
                                            </div>
                                            <!-- Editor -->
                                            <div contenteditable="true" class="p-3 min-h-[100px] outline-none text-sm"
                                                @input="block.data[fieldName] = $el.innerHTML"
                                                x-html="block.data[fieldName] || ''">
                                            </div>
                                        </div>
                                    </template>

                                    <template x-if="fieldDef.type === 'color'">
                                        <div class="flex items-center gap-2">
                                            <input type="color" x-model="block.data[fieldName]"
                                                class="h-6 w-8 p-0 border rounded">
                                            <input type="text" x-model="block.data[fieldName]"
                                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-xs">
                                        </div>
                                    </template>

                                    <template x-if="fieldDef.type === 'select'">
                                        <select x-model="block.data[fieldName]"
                                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-xs">
                                            <template x-for="opt in fieldDef.options" :key="opt">
                                                <option :value="opt" x-text="opt"></option>
                                            </template>
                                        </select>
                                    </template>

                                    <template x-if="fieldDef.type === 'checkbox'">
                                        <div class="flex items-center">
                                            <input type="checkbox" x-model="block.data[fieldName]"
                                                class="h-4 w-4 text-indigo-600 border-gray-300 rounded">
                                            <span class="ml-2 text-sm text-gray-600" x-text="fieldDef.label"></span>
                                        </div>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Minimized Preview (when not selected) -->
                    <div class="p-2 text-sm text-gray-400 italic" x-show="selectedBlockIndex !== index">
                        Click to edit settings...
                    </div>
                </div>
            </template>

            <div x-show="blocks.length === 0"
                class="text-center py-10 text-gray-400 border-2 border-dashed border-gray-300 rounded-lg">
                <p>No blocks detected.</p>
                <p class="text-xs">You can add blocks from the left sidebar.</p>
            </div>
        </div>
    </div>

    <!-- Hidden input to store structure -->
    <input type="hidden" name="content_structure" :value="JSON.stringify(blocks)">
</div>