<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('estimateBuilder', (initialData) => ({
            products: initialData.products || @json($products),
            templates: initialData.templates || @json($templates),
            packages: initialData.packages || @json($packages),
            unitTypes: initialData.unitTypes || @json($unitTypes ?? []),
            categories: initialData.categories || @json($categories ?? []),
            defaults: initialData.defaults || @json($defaults ?? []),

            estimate: {
                status: 'draft',
                currency: 'USD',
                type: 'room_based', // Force room_based to support mixed layouts

                client_id: '',
                client_note: '',
                admin_note: '',
                terms: '',
                pdf_theme: 'modern',
                sections: [], // For room_based: [{ name: 'Room 1', items: [] }]
                items: [],    // For standard: [{ ...item }]

                // Spread initialData but exclude system arrays to keep estimate object clean
                ...(({ products, templates, packages, defaults, ...rest }) => rest)(initialData),

                // Override dates to ensure correct format (YYYY-MM-DD) for input[type="date"]
                estimate_date: initialData.estimate_date ? initialData.estimate_date.split('T')[0] : new Date().toISOString().split('T')[0],
                expiry_date: initialData.expiry_date ? initialData.expiry_date.split('T')[0] : '',
                coupon_code_id: initialData.coupon_code_id || null
            },
            couponInput: '',
            appliedCouponCode: initialData.coupon_code ? initialData.coupon_code.code : '',
            couponValid: true,
            couponMessage: '',

            productPicker: {
                isOpen: false,
                search: '',
                categoryId: '',
                sectionIndex: null // If null, adding to standard list. If set, adding to section
            },
            internalNoteModal: {
                isOpen: false,
                activeItem: null
            },
            configModal: {
                isOpen: false,
                product: null,
                options: {}, // { option_id: value_id }
                basePrice: 0
            },
            roomModal: {
                isOpen: false,
                name: '',
                templateId: ''
            },
            totals: {
                subtotal: 0,
                totalTax: 0,
                discount: 0,
                grandTotal: 0
            },
            selectedClient: null,
            isSubmitting: false,
            validationErrors: [],

            generateUid() {
                return 'item-' + Math.random().toString(36).substr(2, 9);
            },

            hasCustomItems() {
                const allItems = this.estimate.type === 'room_based'
                    ? this.estimate.sections.flatMap(s => s.items)
                    : this.estimate.items;
                // Custom items have no product_id (or null)
                return allItems.some(i => !i.product_id);
            },

            confirmModeSwitch(event) {
                const newType = event.target.value;
                // Current type is still in this.estimate.type because x-model syncs slightly differently or we override it
                // Actually, if we use @change, x-model might have fired. 
                // Better to use @click.prevent on the inputs in the View, logic here just handles the check.
                // But the plan says: update radio buttons to use @change.

                const currentType = this.estimate.type;

                // If x-model already updated, we can't detect "change" easily from old state unless we track it.
                // However, let's assume valid event.target.value is the *new* destination.

                let hasItems = false;
                if (currentType === 'room_based') {
                    hasItems = this.estimate.sections.some(s => s.items.length > 0);
                } else {
                    hasItems = this.estimate.items.length > 0;
                }

                if (hasItems && newType !== currentType) {
                    if (!confirm('Switching modes will hide your current items and they will be lost if you save. Do you want to continue?')) {
                        // Revert
                        this.estimate.type = currentType;
                        event.target.checked = false; // Visual fix if needed, Alpine should handle it
                        // Find the old radio and check it? Alpine x-model should do it if we set privacy.
                        return; // Stop
                    }
                }
                // If confirmed or no items, allow the change (x-model will handle update, or we set it)
                this.estimate.type = newType;
            },

            filteredProducts() {
                let filtered = this.products;

                // Filter by Category
                if (this.productPicker.categoryId) {
                    filtered = filtered.filter(p => p.category_id == this.productPicker.categoryId);
                }

                // Filter by Search Query
                if (this.productPicker.search) {
                    const q = this.productPicker.search.toLowerCase();
                    filtered = filtered.filter(p =>
                        p.name.toLowerCase().includes(q) ||
                        (p.sku && p.sku.toLowerCase().includes(q))
                    );
                }

                return filtered;
            },

            init() {
                // Initialize sections if room_based and empty
                if (this.estimate.type === 'room_based' && this.estimate.sections.length === 0) {
                    this.estimate.sections.push({ name: 'Room 1', items: [] });
                }

                // Hydrate items (fix images, numbers)
                // Hydrate items (fix images, numbers)
                const hydrateItem = (item) => {
                    // 1. Resolve Product ID first
                    if (!item.product_id && item.product) {
                        item.product_id = item.product.id;
                    }
                    // Fallback: Match by name if product_id is missing
                    if (!item.product_id) {
                        const nameToMatch = (item.name || '').trim().toLowerCase();
                        if (nameToMatch && this.products) {
                            const found = this.products.find(p => (p.name || '').trim().toLowerCase() === nameToMatch);
                            if (found) {
                                item.product_id = found.id;
                            }
                        }
                    }

                    // 2. Check for forced formula from product
                    let forcedMethod = null;
                    if (item.product_id && this.products) {
                        const product = this.products.find(p => p.id == item.product_id);
                        if (product && ['formula', 'area', 'volume', 'area_lh'].includes(product.calculation_method)) {
                            forcedMethod = product.calculation_method;
                        }
                    }

                    // 3. Basic Field Hydration
                    item.unit_price = parseFloat(item.unit_price || 0);
                    item.quantity = parseFloat(item.quantity || 1);
                    item.tax_1 = parseFloat(item.tax_1 || 0);
                    item.tax_2 = parseFloat(item.tax_2 || 0);
                    item.unit_type_id = item.unit_type_id || null;
                    item._showTypePicker = false;
                    item.size = item.size || '';
                    item.length = item.length || '';
                    item.width = item.width || '';
                    item.height = item.height || '';

                    // 4. Set Formula and ShowCalculator
                    if (forcedMethod) {
                        item.formula = forcedMethod;
                    } else {
                        item.formula = item.formula || '';
                    }

                    item.showCalculator = !!(item.length || item.width || item.height) || ['formula', 'area', 'volume', 'area_lh'].includes(item.formula);

                    if (!item.image_url && item.product && item.product.images && item.product.images.length > 0) {
                        item.image_url = '/storage/' + item.product.images[0].image_path;
                    }
                    if (!item.options) item.options = [];

                    item.is_package = item.is_package || (item.description && item.description.startsWith('Package:'));
                };

                if (this.estimate.type === 'room_based') {
                    this.estimate.sections.forEach(s => {
                        s.items.forEach(i => {
                            hydrateItem(i);
                            if (!i._uid) i._uid = 'item-' + Math.random().toString(36).substr(2, 9);
                        });
                        // Sync section_type from is_package if needed
                        if (s.is_package && !s.section_type) s.section_type = 'package';
                        if (!s.section_type) s.section_type = 'room';

                        // Automatically detect if this section was added as a package (legacy backup)
                        if (s.items.length > 0 && s.items.every(i => i.is_package)) {
                            s.section_type = 'package';
                        }
                    });
                } else {
                    this.estimate.items.forEach(i => {
                        hydrateItem(i);
                        if (!i._uid) i._uid = 'item-' + Math.random().toString(36).substr(2, 9);
                    });
                }

                // If editing (has client_id), fetch full client details
                if (this.estimate.client_id) {
                    fetch(`/clients/${this.estimate.client_id}`, {
                        headers: { 'Accept': 'application/json' }
                    })
                        .then(r => r.json())
                        .then(data => { this.selectedClient = data; })
                        .catch(err => console.error('Error fetching initial client', err));
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
                            if (!Array.isArray(data)) {
                                console.error('Perfex Search Error:', data);
                                choices.setChoices([], 'value', 'label', true);
                                return;
                            }
                            const formattedData = data.map(client => ({
                                value: client.id,
                                label: client.name + (client.email ? ` (${client.email})` : '')
                            }));
                            choices.setChoices(formattedData, 'value', 'label', true);
                        })
                        .catch(err => console.error('Client search error', err));
                });

                el.addEventListener('change', (event) => {
                    const clientId = event.detail.value;
                    this.estimate.client_id = clientId;
                    this.selectedClient = null;

                    if (clientId) {
                        fetch(`/clients/${clientId}`, {
                            headers: { 'Accept': 'application/json' }
                        })
                            .then(r => r.json())
                            .then(data => {
                                this.selectedClient = data;
                                // Optionally auto-fill notes or other fields
                                if (data.property_notes && !this.estimate.admin_note) {
                                    this.estimate.admin_note = 'Property Notes: ' + data.property_notes;
                                }
                            })
                            .catch(err => console.error('Error fetching client details', err));
                    }
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
                this.productPicker.categoryId = '';
                this.productPicker.isOpen = true;
            },

            openInternalNoteModal(item) {
                this.internalNoteModal.activeItem = item;
                this.internalNoteModal.isOpen = true;
            },

            closeInternalNoteModal() {
                this.internalNoteModal.isOpen = false;
                this.internalNoteModal.activeItem = null;
            },

            getUnitsByTypeId(typeId) {
                if (!typeId) return [];
                const type = this.unitTypes.find(t => t.id == typeId);
                return type ? type.units : [];
            },

            onUnitTypeChange(item) {
                const units = this.getUnitsByTypeId(item.unit_type_id);
                if (units.length > 0) {
                    item.unit_type = units[0];
                } else {
                    item.unit_type = 'nos';
                }
            },

            getFilteredUnitTypes(sectionIndex) {
                // For standard estimates (not room-based), show all unit types
                if (this.estimate.type !== 'room_based') {
                    return this.unitTypes;
                }

                // Get the section
                const section = this.estimate.sections[sectionIndex];
                if (!section) return this.unitTypes;

                // If section has no allowed_unit_types or it's empty, show all (backward compatibility)
                if (!section.allowed_unit_types || section.allowed_unit_types.length === 0) {
                    return this.unitTypes;
                }

                // Filter unit types based on section's allowed list
                return this.unitTypes.filter(ut => section.allowed_unit_types.some(id => String(id) === String(ut.id)));
            },


            selectProduct(product) {
                // Check for options
                if (product.options && product.options.length > 0) {
                    this.productPicker.isOpen = false; // Close picker to prevent overlap
                    this.openConfigModal(product);
                    return;
                }

                const calcMethod = product.calculation_method;
                const showCalc = ['formula', 'area', 'volume', 'area_lh'].includes(calcMethod);
                let initFormula = '';
                if (calcMethod === 'formula') initFormula = 'formula';
                else if (['area', 'volume', 'area_lh'].includes(calcMethod)) initFormula = calcMethod;

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
                    unit_type_id: product.unit_type_id || null,
                    description: product.description || '',
                    tax_1: parseFloat(this.defaults.tax_1_rate || 0),
                    tax_2: parseFloat(this.defaults.tax_2_rate || 0),
                    image_url: (product.images && product.images.length > 0) ? '/storage/' + product.images[0].image_path : null,
                    length: '',
                    width: '',
                    height: '',
                    formula: initFormula,
                    showCalculator: showCalc,
                    _showTypePicker: false,
                    options: [],
                    is_package: false,
                    _uid: 'item-' + Math.random().toString(36).substr(2, 9)
                };
                this.pushItem(newItem);
                this.productPicker.isOpen = false;
                this.calculateTotals();
            },

            openConfigModal(product) {
                this.configModal.product = product;
                this.configModal.basePrice = parseFloat(product.unit_price || 0);
                this.configModal.options = {};

                // Initialize defaults (first value)
                if (product.options) {
                    product.options.forEach(opt => {
                        if (opt.values && opt.values.length > 0) {
                            this.configModal.options[opt.id] = opt.values[0].id;
                        }
                    });
                }

                this.configModal.isOpen = true;
            },
            closeConfigModal() {
                this.configModal.isOpen = false;
                this.configModal.product = null;
                this.configModal.options = {};
                // Re-open product picker on cancel so user doesn't lose context
                this.productPicker.isOpen = true;
            },

            confirmConfig() {
                const product = this.configModal.product;
                const calcMethod = product.calculation_method;
                const showCalc = ['formula', 'area', 'volume', 'area_lh'].includes(calcMethod);
                let initFormula = '';
                if (calcMethod === 'formula') initFormula = 'formula';
                else if (['area', 'volume', 'area_lh'].includes(calcMethod)) initFormula = calcMethod;

                let finalPrice = this.configModal.basePrice;
                const selectedOptionsList = [];

                if (product.options) {
                    product.options.forEach(opt => {
                        const selectedValueId = this.configModal.options[opt.id];
                        const val = opt.values.find(v => v.id == selectedValueId);
                        if (val) {
                            finalPrice += parseFloat(val.price_adjustment || 0);
                            selectedOptionsList.push({
                                name: opt.name,
                                value: val.value,
                                price_adjustment: val.price_adjustment
                            });
                        }
                    });
                }

                let sizeString = '';
                if (product.dimensions) {
                    const dims = product.dimensions;
                    if (dims.length && dims.width) {
                        sizeString = `${dims.length} x ${dims.width}`;
                        if (dims.unit) sizeString += ` ${dims.unit}`;
                    }
                }

                const newItem = {
                    id: null,
                    name: product.name,
                    product_id: product.id,
                    unit_price: finalPrice,
                    quantity: 1,
                    size: sizeString,
                    unit_type: product.unit_type || 'nos',
                    unit_type_id: product.unit_type_id || null,
                    description: product.description || '',
                    tax_1: parseFloat(this.defaults.tax_1_rate || 0),
                    tax_2: parseFloat(this.defaults.tax_2_rate || 0),
                    image_url: (product.images && product.images.length > 0) ? '/storage/' + product.images[0].image_path : null,
                    length: '',
                    width: '',
                    height: '',
                    formula: initFormula,
                    showCalculator: showCalc,
                    _showTypePicker: false,
                    options: selectedOptionsList,
                    is_package: false,
                    _uid: 'item-' + Math.random().toString(36).substr(2, 9)
                };

                this.pushItem(newItem);

                // Manually close config modal WITHOUT triggering the re-open of picker
                this.configModal.isOpen = false;
                this.configModal.product = null;
                this.configModal.options = {};
                this.productPicker.isOpen = false;

                this.calculateTotals();
            },

            addCustomItem(sectionIndex = null) {
                if (sectionIndex !== null) {
                    this.productPicker.sectionIndex = sectionIndex;
                }
                const newItem = {
                    id: null,
                    name: '',
                    unit_price: 0,
                    quantity: 1,
                    unit_type: 'nos',
                    unit_type_id: null,
                    tax_1: 0,
                    tax_2: 0,
                    length: '',
                    width: '',
                    height: '',
                    formula: '',
                    showCalculator: false,
                    options: []
                };
                this.pushItem(newItem);
                this.productPicker.isOpen = false;
            },

            pushItem(item) {
                if (typeof this.productPicker.sectionIndex !== 'undefined' && this.productPicker.sectionIndex !== null) {
                    this.estimate.sections[this.productPicker.sectionIndex].items.push(item);
                } else {
                    this.estimate.items.push(item);
                }
            },

            addSection() {
                const count = this.estimate.sections.length + 1;
                this.estimate.sections.push({ name: 'Room ' + count, items: [], section_type: 'room' });
                // Re-init sortable for new DOM elements
                this.$nextTick(() => this.initSortable());
            },

            openRoomModal() {
                this.roomModal.name = '';
                this.roomModal.templateId = '';
                this.roomModal.isOpen = true;
            },

            confirmRoom() {
                if (this.roomModal.templateId) {
                    const template = this.templates.find(t => t.id == this.roomModal.templateId);
                    if (template) {
                        this.applyTemplate(template);
                        this.roomModal.isOpen = false;
                        return;
                    }
                }

                if (this.roomModal.name.trim()) {
                    this.estimate.sections.push({
                        name: this.roomModal.name.trim(),
                        items: []
                    });
                    this.roomModal.isOpen = false;
                    this.$nextTick(() => this.initSortable());
                } else {
                    alert('Please enter a room name or select a template.');
                }
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
                const newSection = {
                    name: template.name,
                    name: template.name,
                    items: [],
                    section_type: 'room',
                    allowed_unit_types: template.allowed_unit_types || [] // Store allowed unit types from template
                };
                if (Array.isArray(template.items)) {
                    newSection.items = template.items.map(i => {
                        // Resolve fields (Priority: Template Item > Product)
                        let imageUrl = i.image_url || null;
                        let desc = i.description || '';

                        if (i.product) {
                            if (!imageUrl && i.product.images && i.product.images.length > 0) {
                                const path = i.product.images[0].image_path;
                                imageUrl = path.startsWith('http') ? path : '/storage/' + path;
                            }
                            if (!desc) {
                                desc = i.product.description || '';
                            }
                        }

                        // Get unit_type_id from template item or product, ensure it's a string or null
                        const unitTypeId = i.unit_type_id || i.product?.unit_type_id || null;
                        const unitTypeIdString = unitTypeId ? String(unitTypeId) : null;

                        // Extract product_id if available, or try to find by name
                        let productId = i.product_id || (i.product ? i.product.id : null);

                        // Fallback: Match by name if product_id is missing (handles legacy templates)
                        if (!productId) {
                            const nameToMatch = (i.item_name || i.name || '').trim().toLowerCase();
                            if (nameToMatch) {
                                const found = this.products.find(p => (p.name || '').trim().toLowerCase() === nameToMatch);
                                if (found) productId = found.id;
                            }
                        }

                        return {
                            id: null,
                            name: i.item_name || i.name || '',
                            product_id: productId,
                            unit_price: parseFloat(i.unit_price || 0),
                            quantity: parseFloat(i.quantity || 1),
                            size: i.size || '',
                            unit_type: i.unit_type || i.product?.unit_type || 'nos',
                            unit_type_id: unitTypeIdString,
                            description: desc,
                            image_url: imageUrl,
                            tax_1: 0,
                            tax_2: 0,
                            length: '', width: '', height: '', formula: '', showCalculator: false,
                            _showTypePicker: !!unitTypeIdString, // Show picker if unit type is set
                            options: [],
                            is_package: false,
                            _uid: 'item-' + Math.random().toString(36).substr(2, 9)
                        };
                    });
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
                    unit_type_id: null, // Packages might not have specific unit type linked yet
                    description: `Package: ${pkg.name}`,
                    tax_1: 0,
                    tax_2: 0,
                    length: '', width: '', height: '', formula: '', showCalculator: false,
                    options: [],
                    is_package: true,
                    _uid: this.generateUid() // Add UID for package items
                }));
                if (this.estimate.type === 'room_based') {
                    this.estimate.sections.push({
                        name: pkg.name,
                        items: newItems,
                        section_type: 'package',
                        is_package: true // Keep for legacy checks if any
                    });
                    this.$nextTick(() => this.initSortable());
                } else {
                    this.estimate.items.push(...newItems);
                }
                this.calculateTotals();
            },

            // --- Calculations ---
            calculateItemTotal(item) {
                const total = (parseFloat(item.unit_price) || 0) * (parseFloat(item.quantity) || 0);
                return parseFloat(total.toFixed(2));
            },

            calculateSectionTotal(section) {
                if (!section.items) return 0;
                return section.items.reduce((sum, item) => sum + this.calculateItemTotal(item), 0);
            },

            calculateTotals() {
                let subtotal = 0;
                let totalTax = 0;

                if (this.estimate.type === 'room_based') {
                    this.estimate.sections.forEach(section => {
                        const secTotal = this.calculateSectionTotal(section);
                        section.subtotal = secTotal; // Update section subtotal for persistence
                        subtotal += secTotal;
                    });
                } else {
                    this.estimate.items.forEach(item => {
                        subtotal += this.calculateItemTotal(item);
                    });
                }

                const t1Rate = (parseFloat(this.estimate.tax_1) || 0) / 100;
                const t2Rate = (parseFloat(this.estimate.tax_2) || 0) / 100;
                totalTax = subtotal * (t1Rate + t2Rate);

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
                const l = parseFloat(item.length) || 0;
                const w = parseFloat(item.width) || 0;
                const h = parseFloat(item.height) || 0;

                // Check if the item is linked to a product with a strict calculation method
                let forcedMethod = null;
                let customFormulaString = null;

                if (item.product_id) {
                    const product = this.products.find(p => p.id == item.product_id);
                    if (product) {
                        if (product.calculation_method === 'formula') {
                            forcedMethod = 'formula';
                            customFormulaString = product.formula;
                        } else if (['area', 'volume', 'area_lh'].includes(product.calculation_method)) {
                            forcedMethod = product.calculation_method;
                        }
                    }
                }

                if (forcedMethod) {
                    item.formula = forcedMethod;
                    if (forcedMethod === 'formula' && customFormulaString) {
                        try {
                            // Replace variable placeholders (l, w, h) with values
                            // Using word boundaries to avoid replacing parts of other words (though formula is likely simple)
                            let expression = customFormulaString.toLowerCase()
                                .replace(/\bl\b/g, l)
                                .replace(/\bw\b/g, w)
                                .replace(/\bh\b/g, h);

                            // Basic sanitization: only allow numbers, math operators, parenthesis, and spaces
                            if (/^[0-9+\-*/().\s]+$/.test(expression)) {
                                // Safe(ish) evaluation
                                const result = (new Function('return ' + expression))();
                                item.quantity = isNaN(result) ? 0 : parseFloat(result).toFixed(2);
                            } else {
                                console.warn('Invalid characters in custom formula:', expression);
                                item.quantity = 0;
                            }
                        } catch (e) {
                            console.error('Error evaluating custom formula:', e);
                            item.quantity = 0;
                        }
                    } else if (forcedMethod === 'volume') {
                        item.quantity = (l * w * h).toFixed(2);
                    } else if (forcedMethod === 'area_lh') {
                        item.quantity = (l * h).toFixed(2);
                    } else {
                        // area
                        item.quantity = (l * w).toFixed(2);
                    }
                    this.calculateTotals();
                    return;
                }

                // Default Inference Logic
                if (l > 0 && w > 0) {
                    if (h > 0) {
                        item.quantity = (l * w * h).toFixed(2);
                        item.formula = 'volume';
                    } else {
                        item.quantity = (l * w).toFixed(2);
                        item.formula = 'area';
                    }
                } else if (l > 0 && h > 0) {
                    item.quantity = (l * h).toFixed(2);
                    item.formula = 'area_lh';
                } else {
                    // Insufficient dimensions
                    item.quantity = 0;
                }
                this.calculateTotals();
            },

            // --- Coupon Logic ---
            applyCoupon() {
                if (!this.couponInput) return;

                this.calculateTotals(); // Ensure subtotal is up to date

                fetch('{{ route("coupons.validate") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        code: this.couponInput,
                        total: this.totals.subtotal
                    })
                })
                    .then(r => r.json())
                    .then(data => {
                        if (data.valid) {
                            this.estimate.coupon_code_id = data.coupon_id;
                            this.appliedCouponCode = this.couponInput.toUpperCase();
                            this.estimate.discount_type = data.type;
                            this.estimate.discount_value = data.value;
                            this.couponMessage = data.message;
                            this.couponValid = true;
                            this.calculateTotals();
                        } else {
                            this.couponMessage = data.message;
                            this.couponValid = false;
                            // Clear message after 3 seconds
                            setTimeout(() => this.couponMessage = '', 3000);
                        }
                    })
                    .catch(e => {
                        console.error(e);
                        this.couponMessage = 'Error validating coupon';
                        this.couponValid = false;
                    });
            },

            removeCoupon() {
                this.estimate.coupon_code_id = null;
                this.appliedCouponCode = '';
                this.couponInput = '';
                this.estimate.discount_value = 0;
                this.couponMessage = '';
                this.calculateTotals();
            },

            // --- Validation ---
            hasItemError(item, sectionIndex = null) {
                const location = sectionIndex !== null
                    ? `${this.estimate.sections[sectionIndex].name} - Item #${this.estimate.sections[sectionIndex].items.indexOf(item) + 1}`
                    : `Item #${this.estimate.items.indexOf(item) + 1}`;

                return this.validationErrors.some(err =>
                    err.location === location && err.itemName === (item.name || 'Unnamed Item')
                );
            },

            validateForm() {
                this.validationErrors = [];
                const errors = [];

                // Validate required fields
                if (!this.estimate.client_id || this.estimate.client_id === '') {
                    errors.push({
                        location: 'Estimate Details',
                        itemName: 'Client / Lead',
                        message: 'Please select a client or lead'
                    });
                }

                if (!this.estimate.pdf_template_id || this.estimate.pdf_template_id === '') {
                    errors.push({
                        location: 'Estimate Details',
                        itemName: 'PDF Template',
                        message: 'Please select a PDF template'
                    });
                }

                if (!this.estimate.estimate_date || this.estimate.estimate_date === '') {
                    errors.push({
                        location: 'Estimate Details',
                        itemName: 'Estimate Date',
                        message: 'Please select an estimate date'
                    });
                }

                // Validate unit configurations
                const validateItem = (item, location) => {
                    // Check if unit configuration is started but not completed
                    if (item._showTypePicker || item.unit_type_id) {
                        // If unit type picker is shown or unit_type_id is set, we need both fields
                        if (!item.unit_type_id || item.unit_type_id === '') {
                            errors.push({
                                location: location,
                                itemName: item.name || 'Unnamed Item',
                                message: 'Unit Type is required'
                            });
                        }

                        if (!item.unit_type || item.unit_type === '') {
                            errors.push({
                                location: location,
                                itemName: item.name || 'Unnamed Item',
                                message: 'Unit is required'
                            });
                        }
                    }
                };

                if (this.estimate.type === 'room_based') {
                    this.estimate.sections.forEach((section, sIdx) => {
                        section.items.forEach((item, iIdx) => {
                            validateItem(item, `${section.name} - Item #${iIdx + 1}`);
                        });
                    });
                } else {
                    this.estimate.items.forEach((item, iIdx) => {
                        validateItem(item, `Item #${iIdx + 1}`);
                    });
                }

                this.validationErrors = errors;
                return errors.length === 0;
            },

            // --- Form Submission ---
            previewPdf() {
                this.submitHiddenForm('{{ route("estimates.preview") }}', true);
            },

            submitForm() {
                // Validate all required fields before submission
                if (!this.validateForm()) {
                    // Scroll to top to show error message
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                    return;
                }

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
                const fields = ['client_id', 'estimate_date', 'expiry_date', 'currency', 'status', 'discount_type', 'discount_value', 'client_note', 'admin_note', 'terms', 'pdf_theme', 'type', 'coupon_code_id', 'pdf_template_id', 'tax_1', 'tax_2'];
                fields.forEach(f => {
                    let val = this.estimate[f];
                    if (f === 'discount_value') {
                        val = (val !== null && val !== undefined && val !== '') ? val : 0;
                    }
                    app(f, val ?? '');
                });

                // Sections/Items
                if (this.estimate.type === 'room_based') {
                    this.estimate.sections.forEach((s, sIdx) => {
                        app(`sections[${sIdx}][name]`, s.name);
                        if (s.id) app(`sections[${sIdx}][id]`, s.id); // Validating ID presence
                        app(`sections[${sIdx}][is_package]`, s.is_package ? 1 : 0);

                        s.items.forEach((i, iIdx) => {
                            for (const [k, v] of Object.entries(i)) {
                                if (v !== null && v !== undefined && k !== 'image_url' && k !== 'options') {
                                    // Explicitly cast booleans like is_package to 1/0
                                    let val = v;
                                    if (k === 'is_package') val = v ? 1 : 0;

                                    // Include ID if it exists for updates
                                    app(`sections[${sIdx}][items][${iIdx}][${k}]`, val);
                                } else if (k === 'options' && Array.isArray(v)) {
                                    v.forEach((opt, oIdx) => {
                                        app(`sections[${sIdx}][items][${iIdx}][options][${oIdx}][name]`, opt.name);
                                        app(`sections[${sIdx}][items][${iIdx}][options][${oIdx}][value]`, opt.value);
                                        app(`sections[${sIdx}][items][${iIdx}][options][${oIdx}][price_adjustment]`, opt.price_adjustment);
                                    });
                                }
                            }
                        });
                    });
                } else {
                    this.estimate.items.forEach((i, iIdx) => {
                        for (const [k, v] of Object.entries(i)) {
                            if (v !== null && v !== undefined && k !== 'image_url' && k !== 'options') {
                                // Explicitly cast booleans like is_package to 1/0
                                let val = v;
                                if (k === 'is_package') val = v ? 1 : 0;

                                app(`items[${iIdx}][${k}]`, val);
                            } else if (k === 'options' && Array.isArray(v)) {
                                v.forEach((opt, oIdx) => {
                                    app(`items[${iIdx}][options][${oIdx}][name]`, opt.name);
                                    app(`items[${iIdx}][options][${oIdx}][value]`, opt.value);
                                    app(`items[${iIdx}][options][${oIdx}][price_adjustment]`, opt.price_adjustment);
                                });
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