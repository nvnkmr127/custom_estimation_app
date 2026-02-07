<script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.14.0/Sortable.min.js"></script>
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('templateEditor', () => ({
            activeTab: 'visual', // Default to Visual
            html: @json(old('html_content', $pdfTemplate->html_content ?? "<html>\n<body>\n    <div style=\"padding: 40px;\">\n        <h1 style=\"color: var(--primary-color);\">{estimate_title}</h1>\n        <p><strong>Estimate #:</strong> {estimate_number}</p>\n        \n        <table style=\"width: 100%; border-collapse: collapse; margin-top: 20px;\">\n            <thead>\n                <tr style=\"background-color: var(--primary-color); color: white;\">\n                    <th style=\"padding: 10px; text-align: left;\">Item</th>\n                    <th style=\"padding: 10px; text-align: right;\">Total</th>\n                </tr>\n            </thead>\n            <tbody>\n                {LOOP_ITEMS}\n                <tr style=\"border-bottom: 1px solid #eee;\">\n                    <td style=\"padding: 10px;\">{item_name}</td>\n                    <td style=\"padding: 10px; text-align: right;\">{item_total}</td>\n                </tr>\n                {END_LOOP}\n            </tbody>\n            <tfoot>\n                <tr style=\"font-weight: bold;\">\n                    <td style=\"padding: 10px; text-align: right;\">Grand Total:</td>\n                    <td style=\"padding: 10px; text-align: right;\">{grand_total}</td>\n                </tr>\n            </tfoot>\n        </table>\n    </div>\n\n    <footer style=\"position: fixed; bottom: 0; left: 0; right: 0; text-align: center; font-size: 12px; color: #777;\">\n        &copy; {company_name}. All rights reserved.\n    </footer>\n</body>\n</html>")),
            css: `{{ old('css_content', $pdfTemplate->css_content ?? '') }}`,
            paperSize: '{{ old('paper_size', $pdfTemplate->paper_size ?? 'a4') }}',
            orientation: '{{ old('orientation', $pdfTemplate->orientation ?? 'portrait') }}',
            primaryColor: '{{ old('primary_color', $pdfTemplate->primary_color ?? '#333333') }}',
            secondaryColor: '{{ old('secondary_color', $pdfTemplate->secondary_color ?? '#555555') }}',
            fontFamily: '{{ old('font_family', $pdfTemplate->font_family ?? 'Helvetica') }}',
            watermarkText: '{{ old('watermark_text', $pdfTemplate->watermark_text ?? '') }}',
            watermarkOpacity: '{{ old('watermark_opacity', $pdfTemplate->watermark_opacity ?? '0.1') }}',

            // Preview State Controls
            previewState: {
                roomBased: true,
                hasTax: true,
                hasDiscount: true
            },

            // Visual Builder Data
            blocks: @json($pdfTemplate->content_structure ?? []),
            selectedBlockIndex: null,
            availableBlocks: {
                header: {
                    label: 'Company Header',
                    desc: 'Logo, Name, Address',
                    fields: {
                        showLogo: { label: 'Show Logo', type: 'checkbox' },
                        align: { label: 'Alignment', type: 'select', options: ['left', 'center', 'right'] },
                        bgColor: { label: 'Background', type: 'color' },
                        // Advanced Styling
                        paddingTop: { label: 'Padding Top (px)', type: 'text' },
                        paddingBottom: { label: 'Padding Bottom (px)', type: 'text' }
                    },
                    render: (data) => `<div style="padding: ${data.paddingTop || '20'}px 20px ${data.paddingBottom || '20'}px; background-color: ${data.bgColor || 'transparent'}; text-align: ${data.align || 'left'};">
                        ${data.showLogo ? '<img src="https://placehold.co/100x50?text=LOGO" style="margin-bottom:10px;">' : ''}
                        <h1 style="margin:0; font-size: 24px; color: var(--primary-color);">{company_name}</h1>
                        <p style="margin: 5px 0; font-size: 12px; color: #555;">{company_address} | {company_email}</p>
                    </div>`
                },
                columns_2: {
                    label: '2 Columns',
                    desc: 'Split content 50/50',
                    fields: {
                        col1_title: { label: 'Left Title', type: 'text' },
                        col1_content: { label: 'Left Content', type: 'textarea' },
                        col2_title: { label: 'Right Title', type: 'text' },
                        col2_content: { label: 'Right Content', type: 'textarea' }
                    },
                    render: (data) => `<table style="width: 100%; border-collapse: collapse;">
                        <tr>
                            <td style="width: 50%; padding: 20px; vertical-align: top;">
                                ${data.col1_title ? `<h3 style="border-bottom: 2px solid var(--secondary-color); font-size: 14px; margin-bottom: 10px;">${data.col1_title}</h3>` : ''}
                                <div>${data.col1_content || 'Left column content...'}</div>
                            </td>
                            <td style="width: 50%; padding: 20px; vertical-align: top;">
                                ${data.col2_title ? `<h3 style="border-bottom: 2px solid var(--secondary-color); font-size: 14px; margin-bottom: 10px;">${data.col2_title}</h3>` : ''}
                                <div>${data.col2_content || 'Right column content...'}</div>
                            </td>
                        </tr>
                    </table>`
                },
                image: {
                    label: 'Image',
                    desc: 'Insert via URL',
                    fields: {
                        src: { label: 'Image URL', type: 'text', placeholder: 'https://...' },
                        width: { label: 'Width (px or %)', type: 'text' },
                        align: { label: 'Alignment', type: 'select', options: ['left', 'center', 'right'] }
                    },
                    render: (data) => `<div style="padding: 20px; text-align: ${data.align || 'center'};">
                        <img src="${data.src || 'https://placehold.co/400x200?text=Placeholder+Image'}" style="max-width: 100%; width: ${data.width || 'auto'};">
                    </div>`
                },
                chart: {
                    label: 'Chart',
                    desc: 'Dynamic graph',
                    fields: {
                        type: { label: 'Chart Type', type: 'select', options: ['Pie', 'Doughnut', 'Bar'] },
                        source: { label: 'Data Source', type: 'select', options: ['Sections', 'Items'] },
                        align: { label: 'Alignment', type: 'select', options: ['left', 'center', 'right'] }
                    },
                    render: (data) => {
                        const type = (data.type || 'Pie').toUpperCase();
                        const source = (data.source || 'Sections').toUpperCase();
                        const variable = `{CHART_${source}_${type}}`;

                        return `<div style="padding: 20px; text-align: ${data.align || 'center'};">
                                    <h3 style="margin-bottom: 10px; font-size: 14px; text-transform: uppercase; letter-spacing: 0.05em; color: var(--secondary-color);">${data.source || 'Sections'} ${data.type || 'Chart'}</h3>
                                    <img src="${variable}" style="max-width: 100%; width: 500px; height: auto;">
                                    <p style="font-size: 11px; color: #888; font-style: italic; margin-top: 5px;">* Chart data rendered dynamically in PDF</p>
                                </div>`;
                    }
                },
                client_info: {
                    label: 'Client Info',
                    desc: 'Bill To / Ship To columns',
                    fields: {
                        title: { label: 'Section Title', type: 'text' }
                    },
                    render: (data) => `<table style="width: 100%; border-collapse: collapse;">
                        <tr>
                            <td style="width: 50%; padding: 20px; vertical-align: top;">
                                <h3 style="border-bottom: 2px solid var(--secondary-color); padding-bottom: 5px; margin-bottom: 10px; font-size: 14px;">${data.title || 'Bill To'}</h3>
                                <p><strong>{client_name}</strong><br>{client_address}<br>{client_email}</p>
                            </td>
                            <td style="width: 50%; padding: 20px; vertical-align: top;">
                                <h3 style="border-bottom: 2px solid var(--secondary-color); padding-bottom: 5px; margin-bottom: 10px; font-size: 14px;">Estimate Details</h3>
                                <p><strong>#:</strong> {estimate_number}<br><strong>Date:</strong> {estimate_date}<br><strong>Expires:</strong> {expiry_date}</p>
                            </td>
                        </tr>
                    </table>`
                },
                items_table: {
                    label: 'Items Table (Flat)',
                    desc: 'Dynamic list of items (all together)',
                    fields: {
                        showImages: { label: 'Show Images', type: 'checkbox' },
                        showConfig: { label: 'Show Config', type: 'checkbox' },
                        showSize: { label: 'Show Size', type: 'checkbox' },
                        showComments: { label: 'Show Comments', type: 'checkbox' }
                    },
                    render: (data) => `<div style="padding: 0 20px;">
                        <table style="width: 100%; border-collapse: collapse; margin-top: 20px;">
                            <thead>
                                <tr style="background-color: var(--primary-color); color: white;">
                                    ${data.showImages ? '<th style="padding: 10px; text-align: left;">Image</th>' : ''}
                                    ${data.showConfig ? '<th style="padding: 10px; text-align: left;">Config</th>' : ''}
                                    <th style="padding: 10px; text-align: left;">Item</th>
                                    ${data.showSize ? '<th style="padding: 10px; text-align: center;">Size</th>' : ''}
                                    <th style="padding: 10px; text-align: right;">Price</th>
                                    <th style="padding: 10px; text-align: right;">Qty</th>
                                    <th style="padding: 10px; text-align: right;">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                {LOOP_ITEMS}
                                <tr style="border-bottom: 1px solid #eee;">
                                    ${data.showImages ? '<td style="padding: 10px; text-align: center;">{item_image}</td>' : ''}
                                    ${data.showConfig ? '<td style="padding: 10px; font-size: 10px; color: #666;">{item_unit_configuration}</td>' : ''}
                                    <td style="padding: 10px;"><strong>{item_name}</strong><br><span style="font-size: 12px; color: #777;">{item_description}</span>${data.showComments ? '{item_comments}' : ''}</td>
                                    ${data.showSize ? '<td style="padding: 10px; text-align: center;">{item_size}</td>' : ''}
                                    <td style="padding: 10px; text-align: right;">{item_price}</td>
                                    <td style="padding: 10px; text-align: right;">{item_quantity} {item_unit}</td>
                                    <td style="padding: 10px; text-align: right;">{item_total}</td>
                                </tr>
                                {END_LOOP}
                            </tbody>
                            <tfoot>
                                <tr style="font-weight: bold; font-size: 1.1em;">
                                    <td colspan="${4 + (data.showImages ? 1 : 0) + (data.showConfig ? 1 : 0) + (data.showSize ? 1 : 0) - 1}" style="padding: 15px 10px 10px; text-align: right;">Subtotal:</td>
                                    <td style="padding: 15px 10px 10px; text-align: right;">{subtotal}</td>
                                </tr>
                                <tr style="font-weight: bold; color: var(--primary-color); font-size: 1.3em;">
                                    <td colspan="${4 + (data.showImages ? 1 : 0) + (data.showConfig ? 1 : 0) + (data.showSize ? 1 : 0) - 1}" style="padding: 10px; text-align: right;">Grand Total:</td>
                                    <td style="padding: 10px; text-align: right;">{grand_total}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>`
                },
                room_items_table: {
                    label: 'Items Table (By Room)',
                    desc: 'Dynamic list grouped by Section/Room',
                    fields: {
                        showHeader: { label: 'Show Room Name', type: 'checkbox' },
                        showImages: { label: 'Show Images', type: 'checkbox' },
                        showConfig: { label: 'Show Config', type: 'checkbox' },
                        showComments: { label: 'Show Comments', type: 'checkbox' },
                        showSectionTotal: { label: 'Show Room Total', type: 'checkbox' }
                    },
                    render: (data) => `<div style="padding: 0 20px;">
                        {LOOP_SECTIONS}
                        ${data.showHeader ? '<h3 style="background-color: #f8fafc; padding: 8px 12px; margin-top: 20px; border-left: 4px solid var(--primary-color); font-size: 14px;">{section_name}</h3>' : ''}
                        <table style="width: 100%; border-collapse: collapse; margin-bottom: 15px;">
                            <thead>
                                <tr style="border-bottom: 2px solid #eee; color: #64748b; font-size: 11px; text-transform: uppercase;">
                                    ${data.showImages ? '<th style="padding: 8px; text-align: left;">Image</th>' : ''}
                                    ${data.showConfig ? '<th style="padding: 8px; text-align: left;">Config</th>' : ''}
                                    <th style="padding: 8px; text-align: left;">Item</th>
                                    <th style="padding: 8px; text-align: right;">Price</th>
                                    <th style="padding: 8px; text-align: right;">Qty</th>
                                    <th style="padding: 8px; text-align: right;">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                {LOOP_ITEMS}
                                <tr style="border-bottom: 1px solid #f1f5f9;">
                                    ${data.showImages ? '<td style="padding: 8px; text-align: center;">{item_image}</td>' : ''}
                                    ${data.showConfig ? '<td style="padding: 8px; font-size: 10px; color: #666;">{item_unit_configuration}</td>' : ''}
                                    <td style="padding: 8px;">
                                        <strong>{item_name}</strong><br>
                                        <span style="font-size: 11px; color: #64748b;">{item_description}</span>
                                        ${data.showComments ? '{item_comments}' : ''}
                                    </td>
                                    <td style="padding: 8px; text-align: right; font-size: 12px;">{item_price}</td>
                                    <td style="padding: 8px; text-align: right; font-size: 12px;">{item_quantity} {item_unit}</td>
                                    <td style="padding: 8px; text-align: right; font-weight: 500;">{item_total}</td>
                                </tr>
                                {END_LOOP}
                            </tbody>
                            ${data.showSectionTotal ? `<tfoot>
                                <tr>
                                    <td colspan="${3 + (data.showImages ? 1 : 0) + (data.showConfig ? 1 : 0)}" style="padding: 10px; text-align: right; font-size: 12px; color: #64748b;">{section_name} Total:</td>
                                    <td style="padding: 10px; text-align: right; font-weight: bold; border-top: 1px solid #e2e8f0;">{section_total}</td>
                                </tr>
                            </tfoot>` : ''}
                        </table>
                        {END_LOOP_SECTIONS}
                        
                        <!-- Overall Totals -->
                        <table style="width: 100%; border-collapse: collapse; border-top: 2px solid var(--primary-color); margin-top: 10px;">
                            <tr style="font-weight: bold; font-size: 1.1em;">
                                <td style="padding: 15px 10px 10px; text-align: right;">Estimate Subtotal:</td>
                                <td style="padding: 15px 10px 10px; text-align: right; width: 120px;">{subtotal}</td>
                            </tr>
                            <tr style="font-weight: bold; color: var(--primary-color); font-size: 1.3em;">
                                <td style="padding: 10px; text-align: right;">Grand Total:</td>
                                <td style="padding: 10px; text-align: right;">{grand_total}</td>
                            </tr>
                        </table>
                    </div>`
                },
                text_block: {
                    label: 'Text Block',
                    desc: 'Rich text area',
                    fields: {
                        content: { label: 'Content', type: 'richtext' },
                        // Typography
                        fontSize: { label: 'Font Size (px)', type: 'text' },
                        fontWeight: { label: 'Weight', type: 'select', options: ['normal', 'bold', '300', '500', '700'] },
                        textColor: { label: 'Text Color', type: 'color' },
                        // Spacing & Border
                        paddingTop: { label: 'Pad Top', type: 'text' },
                        paddingBottom: { label: 'Pad Bot', type: 'text' },
                        border: { label: 'Border (e.g. 1px solid #ccc)', type: 'text' },
                        borderRadius: { label: 'Radius (px)', type: 'text' }
                    },
                    render: (data) => `<div style="
                                padding: ${data.paddingTop || '20'}px 20px ${data.paddingBottom || '20'}px; 
                                font-size: ${data.fontSize || '14'}px; 
                                font-weight: ${data.fontWeight || 'normal'}; 
                                color: ${data.textColor || 'inherit'};
                                border: ${data.border || 'none'};
                                border-radius: ${data.borderRadius || '0'}px;
                            ">${data.content || 'Enter text here...'}</div>`
                },
                spacer: {
                    label: 'Spacer',
                    desc: 'Vertical gap',
                    fields: {
                        height: { label: 'Height (px)', type: 'text' }
                    },
                    render: (data) => `<div style="height: ${data.height || '20'}px;"></div>`
                },
                divider: {
                    label: 'Divider',
                    desc: 'Horizontal Line',
                    fields: {
                        color: { label: 'Color', type: 'color' },
                        width: { label: 'Thickness (px)', type: 'text' },
                        style: { label: 'Style', type: 'select', options: ['solid', 'dashed', 'dotted'] },
                        margin: { label: 'Margin Y (px)', type: 'text' }
                    },
                    render: (data) => `<div style="padding: ${data.margin || '20'}px 0;">
                                <hr style="border: 0; border-top: ${data.width || '1'}px ${data.style || 'solid'} ${data.color || '#e5e7eb'};">
                            </div>`
                },
                footer: {
                    label: 'Footer',
                    desc: 'Terms & Notes',
                    fields: {
                        showTerms: { label: 'Show Terms', type: 'checkbox' },
                        centerText: { label: 'Center Text', type: 'text' }
                    },
                    render: (data) => `<div style="padding: 20px; margin-top: 30px; border-top: 1px solid #ddd; font-size: 12px; color: #666;">
                                ${data.showTerms ? '<h4>Terms & Conditions</h4><p>{terms}</p>' : ''}
                                <div style="text-align: center; margin-top: 20px;">${data.centerText || ''}</div>
                            </div>`
                }
            },

            debounceTimer: null,
            systemTemplates: @json($systemTemplates),

            lastFocusedElement: null,

            init() {
                // Decide text or visual tab initially
                if (this.blocks && this.blocks.length > 0) {
                    this.activeTab = 'visual';
                } else {
                    if (this.html && this.html.length > 50) {
                        this.activeTab = 'html';
                    } else {
                        this.activeTab = 'visual';
                    }
                }

                this.$watch('blocks', () => this.compileBlocks(), { deep: true });

                this.$watch('html', () => this.updatePreview());
                this.$watch('css', () => this.updatePreview());
                this.$watch('primaryColor', () => this.updatePreview());
                this.$watch('secondaryColor', () => this.updatePreview());
                this.$watch('fontFamily', () => this.updatePreview());
                this.$watch('watermarkText', () => this.updatePreview());
                this.$watch('watermarkOpacity', () => this.updatePreview());

                this.$watch('activeTab', (val) => {
                    if (val === 'preview') this.updatePreview();
                });

                // Track focus for Variable Picker
                this.$el.addEventListener('focusin', (e) => {
                    // Only track if it's an input/textarea/contenteditable
                    if (['INPUT', 'TEXTAREA'].includes(e.target.tagName) || e.target.isContentEditable) {
                        this.lastFocusedElement = e.target;
                    }
                });

                // Initialize SortableJS
                this.$nextTick(() => {
                    if (document.getElementById('sortable-blocks')) {
                        new Sortable(document.getElementById('sortable-blocks'), {
                            animation: 150,
                            handle: '.drag-handle',
                            onEnd: (evt) => {
                                // Re-order active blocks based on DOM order
                                // This is a bit tricky with Alpine reactivity, so we'll do a simple swap or move
                                // Actually, simpler: let's just grab the indices
                                const itemEl = evt.item;
                                const newIndex = evt.newIndex;
                                const oldIndex = evt.oldIndex;

                                if (newIndex === oldIndex) return;

                                // Move in array
                                const movedItem = this.blocks.splice(oldIndex, 1)[0];
                                this.blocks.splice(newIndex, 0, movedItem);

                                // Fix selected index if needed
                                if (this.selectedBlockIndex === oldIndex) {
                                    this.selectedBlockIndex = newIndex;
                                } else if (oldIndex < this.selectedBlockIndex && newIndex >= this.selectedBlockIndex) {
                                    this.selectedBlockIndex--;
                                } else if (oldIndex > this.selectedBlockIndex && newIndex <= this.selectedBlockIndex) {
                                    this.selectedBlockIndex++;
                                }

                                this.compileBlocks();
                            }
                        });
                    }
                });

                // Default blocks for new template
                if (!this.html && (!this.blocks || this.blocks.length === 0)) {
                    this.addBlock('header');
                    this.addBlock('client_info');
                    this.addBlock('items_table');
                    this.addBlock('footer');
                }

                setTimeout(() => this.updatePreview(), 500);
            },

            addBlock(type) {
                this.blocks.push({
                    id: Date.now(),
                    type: type,
                    data: { bgColor: '#ffffff', align: 'left' } // Defaults
                });
                this.selectedBlockIndex = this.blocks.length - 1;
                this.compileBlocks();
            },

            removeBlock(index) {
                // LOCK: Prevent deleting critical blocks
                const block = this.blocks[index];
                if (block.type === 'items_table') {
                    alert('The Items Table is required and cannot be deleted.');
                    return;
                }

                this.blocks.splice(index, 1);
                this.selectedBlockIndex = null;
                this.compileBlocks();
            },
            duplicateBlock(index) {
                const blockToClone = this.blocks[index];
                const newBlock = {
                    id: Date.now(), // Unique ID
                    type: blockToClone.type,
                    data: JSON.parse(JSON.stringify(blockToClone.data)) // Deep copy
                };
                this.blocks.splice(index + 1, 0, newBlock); // Insert after
                this.selectedBlockIndex = index + 1;
                this.compileBlocks();
            },
            moveBlock(index, direction) {
                if (index + direction < 0 || index + direction >= this.blocks.length) return;
                const temp = this.blocks[index];
                this.blocks[index] = this.blocks[index + direction];
                this.blocks[index + direction] = temp;
                this.selectedBlockIndex = index + direction;
                this.blocks = [...this.blocks];
                this.compileBlocks();
            },

            compileBlocks() {
                // Simple guard: If blocks are empty, don't wipe HTML (unless we just deleted the last block?)
                if (this.blocks.length === 0 && this.activeTab !== 'visual') return;

                let compiledHtml = `<html><body>`;

                this.blocks.forEach(block => {
                    const def = this.availableBlocks[block.type];
                    if (def) {
                        compiledHtml += `<!-- BLOCK: ${block.type} -->\n`;
                        compiledHtml += def.render(block.data) + '\n';
                    }
                });

                compiledHtml += `</body></html>`;

                this.html = compiledHtml;
            },

            loadSystemTemplate(id) {
                if (!id) return;
                const t = this.systemTemplates.find(x => x.id == id);
                if (t) {
                    this.html = t.html_content;
                    this.css = t.css_content;
                    this.primaryColor = t.primary_color;
                    this.secondaryColor = t.secondary_color;
                    this.fontFamily = t.font_family;
                }
            },

            getPreviewStyle() {
                // Base dimensions in mm
                let width, height;
                if (this.paperSize === 'a4') {
                    width = 210; height = 297;
                } else { // Letter
                    width = 215.9; height = 279.4;
                }

                if (this.orientation === 'landscape') {
                    [width, height] = [height, width];
                }

                return `width: ${width}mm; height: ${height}mm; min-height: ${height}mm;`;
            },

            updatePreview() {
                clearTimeout(this.debounceTimer);
                this.debounceTimer = setTimeout(() => {
                    this.fetchPreview();
                }, 800);
            },

            fetchPreview() {
                const iframe = document.getElementById('previewFrame');
                if (!iframe) return;

                fetch('{{ route("pdf-templates.preview") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        html_content: this.html,
                        css_content: this.css,
                        watermark_text: this.watermarkText,
                        watermark_opacity: this.watermarkOpacity,
                        primary_color: this.primaryColor,
                        secondary_color: this.secondaryColor,
                        font_family: this.fontFamily,
                        preview_state: this.previewState
                    })
                })
                    .then(async res => {
                        if (!res.ok) {
                            const data = await res.json().catch(() => ({}));
                            throw new Error(data.error || 'Server Error: ' + res.status);
                        }
                        return res.text();
                    })
                    .then(html => {
                        const iframe = document.getElementById('previewFrame');
                        if (!iframe) return;

                        const doc = iframe.contentWindow.document;
                        doc.open();
                        doc.write(html);
                        const style = doc.createElement('style');
                        style.innerHTML = `:root { 
                                                                                        --primary-color: ${this.primaryColor}; 
                                                                                        --secondary-color: ${this.secondaryColor}; 
                                                                                        --font-body: ${this.fontFamily}; 
                                                                                    } 
                                                                                    body { font-family: var(--font-body) !important; }`;
                        doc.head.appendChild(style);

                        doc.close();
                    })
                    .catch(err => {
                        console.error(err);
                        const iframe = document.getElementById('previewFrame');
                        if (iframe) {
                            const doc = iframe.contentWindow.document;
                            doc.open();
                            doc.write(`<div style="color:red; padding:20px; font-family:sans-serif;"><strong>Preview Failed:</strong><br>${err.message}</div>`);
                            doc.close();
                        }
                    });
            },

            insertAtCursor(text) {
                if (this.activeTab === 'visual') {
                    // Attempt to insert into last focused element inside visual builder
                    const el = this.lastFocusedElement;
                    if (el && document.contains(el) && (['INPUT', 'TEXTAREA'].includes(el.tagName) || el.isContentEditable)) {
                        if (el.isContentEditable) {
                            // Focus back to restore range? 
                            el.focus();
                            // This replaces the entire selection or inserts at cursor
                            // However, focus might have been lost. Simple append might be safer if selection lost?
                            // execCommand is deprecated but still the only way for reliable richtext insertion without heavy libs
                            document.execCommand('insertText', false, text);
                            el.dispatchEvent(new Event('input', { bubbles: true }));
                        } else {
                            const start = el.selectionStart || 0;
                            const end = el.selectionEnd || 0;
                            const val = el.value || '';
                            el.value = val.substring(0, start) + text + val.substring(end);
                            el.selectionStart = el.selectionEnd = start + text.length;
                            el.focus();
                            el.dispatchEvent(new Event('input', { bubbles: true }));
                            el.dispatchEvent(new Event('change', { bubbles: true }));
                        }
                        return;
                    } else {
                        alert('Please click inside a text field in the Visual Editor settings where you want to insert the variable.');
                        return;
                    }
                }

                // Fallback for HTML/CSS tabs
                const textarea = this.activeTab === 'html' ? document.getElementById('htmlEditor') : document.getElementById('cssEditor');
                if (!textarea) return;

                const start = textarea.selectionStart;
                const end = textarea.selectionEnd;
                const val = textarea.value;

                const newVal = val.substring(0, start) + text + val.substring(end);

                // Update Alpine model
                if (this.activeTab === 'html') this.html = newVal;
                else this.css = newVal;

                // Restore cursor
                this.$nextTick(() => {
                    textarea.focus();
                    textarea.selectionStart = textarea.selectionEnd = start + text.length;
                });
            },

            insertSnippet(type) {
                let snippet = '';
                if (type === 'table') {
                    snippet = `
                                                                                <table class="w-full border-collapse">
                                                                                <thead>
                                                                                    <tr class="bg-gray-100 text-gray-700">
                                                                                        <th class="p-2 text-left">Image</th>
                                                                                        <th class="p-2 text-left">Config</th>
                                                                                        <th class="p-2 text-left">Item Details</th>
                                                                                        <th class="p-2 text-center">Size</th>
                                                                                        <th class="p-2 text-right">Price</th>
                                                                                        <th class="p-2 text-right">Total</th>
                                                                                    </tr>
                                                                                </thead>
                                                                                <tbody>
                                                                                    {LOOP_ITEMS}
                                                                                    <tr>
                                                                                        <td class="border-b p-2 text-center">{item_image}</td>
                                                                                        <td class="border-b p-2 text-xs text-gray-500">{item_unit_configuration}</td>
                                                                                        <td class="border-b p-2"><strong>{item_name}</strong>{item_comments}</td>
                                                                                        <td class="border-b p-2 text-center">{item_size}</td>
                                                                                        <td class="border-b p-2 text-right">{item_price}</td>
                                                                                        <td class="border-b p-2 text-right">{item_total}</td>
                                                                                    </tr>
                                                                                    {END_LOOP}
                                                                                </tbody>
                                                                            </table>
            `;
                } else if (type === 'room_table') {
                    snippet = `
                                                                                {LOOP_SECTIONS}
                                                                                <h3 style="background-color: #f8fafc; padding: 10px; margin-top: 20px; border-left: 4px solid var(--primary-color);">{section_name}</h3>
                                                                                <table class="w-full border-collapse">
                                                                                    <thead>
                                                                                        <tr style="border-bottom: 2px solid #eee;">
                                                                                            <th class="p-2 text-left">Item</th>
                                                                                            <th class="p-2 text-right">Price</th>
                                                                                            <th class="p-2 text-right">Qty</th>
                                                                                            <th class="p-2 text-right">Total</th>
                                                                                        </tr>
                                                                                    </thead>
                                                                                    <tbody>
                                                                                        {LOOP_ITEMS}
                                                                                        <tr style="border-bottom: 1px solid #f1f5f9;">
                                                                                            <td class="p-2"><strong>{item_name}</strong></td>
                                                                                            <td class="p-2 text-right">{item_price}</td>
                                                                                            <td class="p-2 text-right">{item_quantity} {item_unit}</td>
                                                                                            <td class="p-2 text-right">{item_total}</td>
                                                                                        </tr>
                                                                                        {END_LOOP}
                                                                                    </tbody>
                                                                                    <tfoot>
                                                                                        <tr>
                                                                                            <td colspan="3" class="p-2 text-right">Section Total:</td>
                                                                                            <td class="p-2 text-right font-bold">{section_total}</td>
                                                                                        </tr>
                                                                                    </tfoot>
                                                                                </table>
                                                                                {END_LOOP_SECTIONS}
                                                                            `;
                } else if (type === '2col') {
                    snippet = `
                                                                                <div style="display: flex; gap: 20px;">
                                                                                    <div style="flex: 1;">Column 1</div>
                                                                                    <div style="flex: 1;">Column 2</div>
                                                                                </div>`;
                } else if (type === 'img') {
                    snippet = `<img src="https://placehold.co/150" alt="Placeholder" style="max-width: 100%;">`;
                } else if (type === 'header_footer') {
                    // Switch to CSS tab if not already
                    if (this.activeTab !== 'css') {
                        alert('Switching to CSS tab to insert @page styles.');
                        this.activeTab = 'css';
                        // Defer slightly
                        setTimeout(() => {
                            this.insertAtCursor(`@page { margin: 100px 25px; }
                                                                                header { position: fixed; top: -60px; left: 0px; right: 0px; height: 50px; }
                                                                                footer { position: fixed; bottom: -60px; left: 0px; right: 0px; height: 50px; }`);
                        }, 100);
                        return;
                    }
                    snippet = `@page { margin: 100px 25px; }
                                                                            header { position: fixed; top: -60px; left: 0px; right: 0px; height: 50px; }
                                                                            footer { position: fixed; bottom: -60px; left: 0px; right: 0px; height: 50px; }`;
                } else if (type === 'watermark') {
                    snippet = `<div style="position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%) rotate(-45deg); font-size: 100px; color: #000; opacity: 0.1; white-space: nowrap; z-index: -1;">
                                                                              DRAFT
                                                                            </div>`;
                } else if (type === 'pagenumber') {
                    if (this.activeTab !== 'css') {
                        alert('Switching to CSS for page numbers.');
                        this.activeTab = 'css';
                        setTimeout(() => {
                            this.insertAtCursor(`.page-number:after { content: counter(page); }`);
                        }, 100);
                        return;
                    }
                    snippet = `.page-number:after { content: counter(page); }`;
                } else if (type === 'logic_if') {
                    snippet = `{IF_has_discount}\n  <div class="alert">Has Discount!</div>\n{END_IF}`;
                } else if (type === 'page_break') {
                    snippet = `<div class="page-break-after"></div>`;
                }

                this.insertAtCursor(snippet);
            }
        }));
    });
</script>