<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('estimateBuilder', (initialData) => ({
            estimate: {
                title: '',
                client_id: '',
                estimate_date: new Date().toISOString().split('T')[0],
                expiry_date: '',
                status: 'draft',
                currency: 'USD',
                type: 'standard', // 'standard' or 'room_based'
                discount_type: 'percentage', // 'percentage' or 'fixed'
                discount_value: 0,
                client_note: '',
                admin_note: '',
                terms: '',
                pdf_theme: 'modern',
                sections: [], // For room_based: [{ name: 'Room 1', items: [] }]
                items: [],    // For standard: [{ ...item }]
                ...initialData
            },
            products: @json($products),
            templates: @json($templates),
            packages: @json($packages),
            defaults: @json($defaults ?? []),
            productPicker: {
                isOpen: false,
                search: '',
                sectionIndex: null // If null, adding to standard list. If set, adding to section
            },
            totals: {
                subtotal: 0,
                totalTax: 0,
                discount: 0,
                grandTotal: 0
            },
            isSubmitting: false,

            filteredProducts() {
                if (!this.productPicker.search) return this.products;
                const q = this.productPicker.search.toLowerCase();
                return this.products.filter(p =>
                    p.name.toLowerCase().includes(q) ||
                    (p.sku && p.sku.toLowerCase().includes(q))
                );
            },

            init() {
                // Initialize sections if room_based and empty
                if (this.estimate.type === 'room_based' && this.estimate.sections.length === 0) {
                    this.estimate.sections.push({ name: 'Room 1', items: [] });
                }
                this.calculateTotals();
                this.$nextTick(() => {
                    this.initSortable();
                    this.initClientSearch();
                });
            },

            initClientSearch() {
                const el = this.$refs.clientSearch;
                // Prevent double initialization
                if (!el || el._choices) return;

                const choices = new Choices(el, {
                    searchEnabled: true,
                    searchPlaceholderValue: 'Type to search Perfex CRM...',
                    noResultsText: 'No clients found',
                    itemSelectText: '',
                });

                // Save instance
                el._choices = choices;

                // Sync initial value if editing
                if (this.estimate.client_id) {
                    choices.setChoiceByValue(this.estimate.client_id);
                }

                el.addEventListener('search', (event) => {
                    const query = event.detail.value;
                    if (query.length < 3) return;

                    fetch(`{{ route('perfex.search') }}?q=${query}`)
                        .then(response => response.json())
                        .then(data => {
                            const formattedData = data.map(client => ({
                                value: client.id,
                                label: client.name + (client.email ? ` (${client.email})` : '')
                            }));
                            choices.setChoices(formattedData, 'value', 'label', true);
                        })
                        .catch(err => console.error('Client search error', err));
                });

                el.addEventListener('change', (event) => {
                    this.estimate.client_id = event.detail.value;
                });
            },

            initSortable() {
                // Sections
                const sectionsContainer = document.querySelector('.sections-container');
                if (sectionsContainer) {
                    new Sortable(sectionsContainer, {
                        animation: 150,
                        handle: '.section-handle',
                        onEnd: (evt) => {
                            const movedItem = this.estimate.sections.splice(evt.oldIndex, 1)[0];
                            this.estimate.sections.splice(evt.newIndex, 0, movedItem);
                        }
                    });
                }

                // Section Items
                document.querySelectorAll('.section-items-sortable').forEach(el => {
                    new Sortable(el, {
                        animation: 150,
                        group: 'items',
                        handle: '.handle',
                        onEnd: (evt) => {
                            // Find parent section index
                            const sectionEl = evt.from.closest('[data-section-index]');
                            const sectionIndex = sectionEl ? parseInt(sectionEl.dataset.sectionIndex) : -1;

                            if (sectionIndex !== -1) {
                                const movedItem = this.estimate.sections[sectionIndex].items.splice(evt.oldIndex, 1)[0];
                                this.estimate.sections[sectionIndex].items.splice(evt.newIndex, 0, movedItem);
                            }
                        }
                    });
                });

                // Standard Items
                const standardList = document.querySelector('.standard-items-sortable');
                if (standardList) {
                    new Sortable(standardList, {
                        animation: 150,
                        handle: '.handle',
                        onEnd: (evt) => {
                            const movedItem = this.estimate.items.splice(evt.oldIndex, 1)[0];
                            this.estimate.items.splice(evt.newIndex, 0, movedItem);
                        }
                    });
                }
            },

            // --- Core Actions ---
            openProductPicker(sectionIndex) {
                this.productPicker.sectionIndex = sectionIndex;
                this.productPicker.search = '';
                this.productPicker.isOpen = true;
            },

            selectProduct(product) {
                const isFormula = product.calculation_method === 'formula';

                let sizeString = '';
                if (product.dimensions) {
                    const dims = product.dimensions;
                    if (dims.length && dims.width) {
                        sizeString = `${dims.length} x ${dims.width}`;
                        if (dims.unit) sizeString += ` ${dims.unit}`;
                    }
                }

                const newItem = {
                    id: null, // New item
                    name: product.name,
                    product_id: product.id,
                    unit_price: parseFloat(product.unit_price || 0),
                    quantity: 1,
                    size: sizeString,
                    unit_type: product.unit_type || 'nos',
                    description: product.description || '',
                    tax_1: parseFloat(this.defaults.tax_1_rate || 0),
                    tax_2: parseFloat(this.defaults.tax_2_rate || 0),
                    image_url: (product.images && product.images.length > 0) ? '/storage/' + product.images[0].image_path : null,
                    length: '',
                    width: '',
                    formula: isFormula ? 'area' : '',
                    showCalculator: isFormula
                };
                this.pushItem(newItem);
                this.productPicker.isOpen = false;
                this.calculateTotals();
            },

            addCustomItem() {
                const newItem = {
                    id: null,
                    name: '',
                    unit_price: 0,
                    quantity: 1,
                    unit_type: 'nos',
                    tax_1: parseFloat(this.defaults.tax_1_rate || 0),
                    tax_2: parseFloat(this.defaults.tax_2_rate || 0),
                    length: '',
                    width: '',
                    formula: '',
                    showCalculator: false
                };
                this.pushItem(newItem);
                this.productPicker.isOpen = false;
            },

            pushItem(item) {
                if (this.productPicker.sectionIndex !== null) {
                    this.estimate.sections[this.productPicker.sectionIndex].items.push(item);
                } else {
                    this.estimate.items.push(item);
                }
            },

            addSection() {
                const count = this.estimate.sections.length + 1;
                this.estimate.sections.push({ name: 'Room ' + count, items: [] });
                // Re-init sortable for new DOM elements
                this.$nextTick(() => this.initSortable());
            },

            removeSection(index) {
                if (confirm('Are you sure you want to remove this room?')) {
                    this.estimate.sections.splice(index, 1);
                    this.calculateTotals();
                }
            },

            removeItem(sIdx, iIdx) {
                if (sIdx !== null) this.estimate.sections[sIdx].items.splice(iIdx, 1);
                else this.estimate.items.splice(iIdx, 1);
                this.calculateTotals();
            },

            // --- Importers ---
            applyTemplate(template) {
                const newSection = { name: template.name, items: [] };
                if (Array.isArray(template.items)) {
                    newSection.items = template.items.map(i => ({
                        id: null,
                        name: i.item_name,
                        unit_price: parseFloat(i.unit_price || 0),
                        quantity: parseFloat(i.quantity || 1),
                        size: i.size || '',
                        unit_type: i.unit_type || 'nos',
                        description: '',
                        tax_1: 0,
                        tax_2: 0,
                        length: '', width: '', formula: '', showCalculator: false
                    }));
                }
                this.estimate.sections.push(newSection);
                this.calculateTotals();
                this.$nextTick(() => this.initSortable());
            },

            applyPackage(pkg, sIdx) {
                if (!pkg.items) return;
                const newItems = pkg.items.map(i => ({
                    id: null,
                    name: i.item_name,
                    unit_price: parseFloat(i.unit_price || 0),
                    quantity: parseFloat(i.quantity || 1),
                    size: i.size || '',
                    unit_type: i.unit_type || 'nos',
                    description: `Package: ${pkg.name}`,
                    tax_1: 0,
                    tax_2: 0,
                    length: '', width: '', formula: '', showCalculator: false
                }));
                if (sIdx !== null) this.estimate.sections[sIdx].items.push(...newItems);
                else this.estimate.items.push(...newItems);
                this.calculateTotals();
            },

            // --- Calculations ---
            calculateItemTotal(item) {
                return (parseFloat(item.unit_price) || 0) * (parseFloat(item.quantity) || 0);
            },

            calculateTotals() {
                let subtotal = 0;
                let totalTax = 0;
                const allItems = this.estimate.type === 'room_based'
                    ? this.estimate.sections.flatMap(s => s.items)
                    : this.estimate.items;

                allItems.forEach(item => {
                    const itemTotal = this.calculateItemTotal(item);
                    subtotal += itemTotal;
                    const t1 = itemTotal * ((parseFloat(item.tax_1) || 0) / 100);
                    const t2 = itemTotal * ((parseFloat(item.tax_2) || 0) / 100);
                    totalTax += (t1 + t2);
                });

                let discount = 0;
                if (this.estimate.discount_value > 0) {
                    discount = this.estimate.discount_type === 'percentage'
                        ? subtotal * (this.estimate.discount_value / 100)
                        : parseFloat(this.estimate.discount_value);
                }

                this.totals = {
                    subtotal,
                    totalTax,
                    discount,
                    grandTotal: subtotal + totalTax - discount
                };
            },

            toggleCalculator(item) {
                item.showCalculator = !item.showCalculator;
            },

            calculateQuantity(item) {
                if (item.length < 0) item.length = 0;
                if (item.width < 0) item.width = 0;

                if (item.length && item.width) {
                    const l = parseFloat(item.length) || 0;
                    const w = parseFloat(item.width) || 0;
                    if (l >= 0 && w >= 0) {
                        item.quantity = (l * w).toFixed(2);
                        item.formula = 'area';
                        this.calculateTotals();
                    }
                }
            },

            // --- Form Submission ---
            previewPdf() {
                this.submitHiddenForm('{{ route("estimates.preview") }}', true);
            },

            submitForm() {
                const actionUrl = this.estimate.id
                    ? `{{ route('estimates.update', ':id') }}`.replace(':id', this.estimate.id)
                    : `{{ route('estimates.store') }}`;

                this.submitHiddenForm(actionUrl, false, this.estimate.id ? 'PUT' : 'POST');
            },

            submitHiddenForm(url, isNewTab = false, method = 'POST') {
                if (!isNewTab) this.isSubmitting = true;

                const form = document.createElement('form');
                form.method = 'POST';
                form.action = url;
                if (isNewTab) form.target = '_blank';

                form.innerHTML = `<input type="hidden" name="_token" value="{{ csrf_token() }}">`;
                if (method !== 'POST') {
                    form.innerHTML += `<input type="hidden" name="_method" value="${method}">`;
                }

                const app = (k, v) => {
                    const i = document.createElement('input');
                    i.type = 'hidden'; i.name = k; i.value = v;
                    form.appendChild(i);
                };

                // Basic Fields
                const fields = ['title', 'client_id', 'estimate_date', 'expiry_date', 'currency', 'status', 'discount_type', 'discount_value', 'client_note', 'admin_note', 'terms', 'pdf_theme', 'type'];
                fields.forEach(f => app(f, this.estimate[f] || ''));

                // Sections/Items
                if (this.estimate.type === 'room_based') {
                    this.estimate.sections.forEach((s, sIdx) => {
                        app(`sections[${sIdx}][name]`, s.name);
                        if (s.id) app(`sections[${sIdx}][id]`, s.id); // Validating ID presence

                        s.items.forEach((i, iIdx) => {
                            for (const [k, v] of Object.entries(i)) {
                                if (v !== null && v !== undefined && k !== 'image_url') {
                                    // Include ID if it exists for updates
                                    app(`sections[${sIdx}][items][${iIdx}][${k}]`, v);
                                }
                            }
                        });
                    });
                } else {
                    this.estimate.items.forEach((i, iIdx) => {
                        for (const [k, v] of Object.entries(i)) {
                            if (v !== null && v !== undefined && k !== 'image_url') {
                                app(`items[${iIdx}][${k}]`, v);
                            }
                        }
                    });
                }

                document.body.appendChild(form);
                form.submit();
            }
        }));
    });
</script>