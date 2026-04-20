<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('estimateBuilder', (initialData) => ({
            products: (initialData.products && initialData.products.length > 0) ? initialData.products : @json($products),
            templates: (initialData.templates && initialData.templates.length > 0) ? initialData.templates : @json($templates),
            packages: (initialData.packages && initialData.packages.length > 0) ? initialData.packages : @json($packages),
            unitTypes: (initialData.unitTypes && initialData.unitTypes.length > 0) ? initialData.unitTypes : @json($unitTypes ?? []),
            categories: (initialData.categories && initialData.categories.length > 0) ? initialData.categories : @json($categories ?? []),
            defaults: initialData.defaults || @json($defaults ?? []),


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

            estimate: {
                // Defaults
                client_id: '',
                client_note: '',
                admin_note: '',
                terms: '',
                pdf_theme: 'modern',
                sections: [],
                items: [],
                status: 'draft', // Default

                // Spread DB Data (overwrites above)
                ...initialData,

                // Explicit Type Casting & Fallbacks
                transportation_charges: parseFloat(initialData.transportation_charges || 0),
                tax_1: initialData.tax_1 !== undefined ? parseFloat(initialData.tax_1) : (parseFloat(@json($defaults['tax_1_rate'] ?? 0))),
                tax_2: initialData.tax_2 !== undefined ? parseFloat(initialData.tax_2) : (parseFloat(@json($defaults['tax_2_rate'] ?? 0))),

                // Date Handling - Use Local Timezone
                estimate_date: initialData.estimate_date ? initialData.estimate_date.split('T')[0] : new Date(new Date().getTime() - (new Date().getTimezoneOffset() * 60000)).toISOString().split('T')[0],
                expiry_date: initialData.expiry_date ? initialData.expiry_date.split('T')[0] : '',

                // Ensure Arrays exist. Filter out items that belong to sections because the backend joins them all together otherwise!
                items: initialData.items && Array.isArray(initialData.items) ? initialData.items.filter(i => !i.estimate_section_id) : [],
                sections: initialData.sections || [],

                // Nullable Foreign Keys
                coupon_code_id: initialData.coupon_code_id || null,
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
            isCalculating: false,
            _calcTimeout: null,
            validationErrors: [],
            deletedItems: [],
            deletedSections: [],
            historyModal: {
                isOpen: false,
                isLoading: false,
                data: []
            },
            showDeletedModal: false,
            isDirty: false,

            init() {
                // Track changes to trigger unsaved changes warning
                this.$watch('estimate', (value) => {
                    this.isDirty = true;
                }, { deep: true });

                // Prevent accidentally navigating away with unsaved changes
                window.addEventListener('beforeunload', (e) => {
                    if (this.isDirty && !this.isSubmitting) {
                        e.preventDefault();
                        e.returnValue = '';
                    }
                });

                this.calculateTotals();
            },

            generateUid() {
                return 'item-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9);
            },

            // Safe math evaluator for formulas (prevents RCE/arbitrary execution)
            safeEvaluate(expression) {
                try {
                    const tokens = expression.match(/\d+\.\d+|\d+|[+\-*/()]/g);
                    if (!tokens) return 0;
                    let pos = 0;
                    const consume = () => tokens[pos++];
                    const peek = () => tokens[pos];
                    const parsePrimary = () => {
                        let t = consume();
                        if (t === '(') {
                            let res = parseExpr();
                            consume(); // )
                            return res;
                        }
                        return parseFloat(t);
                    };
                    const parseMul = () => {
                        let left = parsePrimary();
                        while (peek() === '*' || peek() === '/') {
                            let op = consume();
                            let right = parsePrimary();
                            left = op === '*' ? left * right : left / right;
                        }
                        return left;
                    };
                    const parseExpr = () => {
                        let left = parseMul();
                        while (peek() === '+' || peek() === '-') {
                            let op = consume();
                            let right = parseMul();
                            left = op === '+' ? left + right : left - right;
                        }
                        return left;
                    };
                    return parseExpr();
                } catch (e) {
                    console.error('Math evaluation failed:', e);
                    return 0;
                }
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

            get filteredProducts() {
                let filtered = Array.isArray(this.products) ? this.products : [];

                // Filter by Category
                if (this.productPicker.categoryId) {
                    filtered = filtered.filter(p => p && p.category_id == this.productPicker.categoryId);
                }

                // Filter by Search Query
                if (this.productPicker.search) {
                    const q = this.productPicker.search.toLowerCase();
                    filtered = filtered.filter(p =>
                        (p.name && p.name.toLowerCase().includes(q)) ||
                        (p.sku && String(p.sku).toLowerCase().includes(q)) ||
                        (p.description && p.description.toLowerCase().includes(q))
                    );
                }

                return filtered;
            },

            get currentConfigPrice() {
                if (!this.configModal.product) return 0;
                let price = parseFloat(this.configModal.basePrice || 0);

                if (this.configModal.product.options) {
                    this.configModal.product.options.forEach(opt => {
                        const valId = this.configModal.options[opt.id];
                        if (valId && opt.values) {
                            const val = opt.values.find(v => v.id == valId);
                            if (val) {
                                price += parseFloat(val.price_adjustment || 0);
                            }
                        }
                    });
                }
                return price;
            },

            getProduct(productId) {
                if (!productId) return null;
                return this.products.find(p => p.id == productId);
            },

            updateItemOption(item, optionId, valueId) {
                const product = this.getProduct(item.product_id);
                if (!product) return;

                if (!item.selected_options) item.selected_options = {};
                item.selected_options[optionId] = valueId;

                // Recalculate price and update display options
                let newPrice = parseFloat(product.unit_price || 0);
                const newOptionsDetails = [];

                if (product.options) {
                    product.options.forEach(opt => {
                        const valId = item.selected_options[opt.id];
                        if (valId) {
                            const val = opt.values.find(v => v.id == valId);
                            if (val) {
                                newPrice += parseFloat(val.price_adjustment || 0);
                                newOptionsDetails.push({
                                    name: opt.name,
                                    value: val.value,
                                    price_adjustment: val.price_adjustment
                                });
                            }
                        }
                    });
                }

                item.unit_price = newPrice;
                item.options = newOptionsDetails;
                this.calculateTotals();
            },

            init() {
                console.log('EstimateBuilder Init');
                
                // Initialize default section if room_based and empty
                if (this.estimate.type === 'room_based' && this.estimate.sections.length === 0) {
                    this.estimate.sections.push({ name: 'Room 1', items: [], section_type: 'room' });
                }

                // Hydrate items (fix images, numbers)
                if (this.estimate.type === 'room_based') {
                    this.estimate.sections.forEach(s => {
                        // Robust Section Type Initialization
                        if (!s.section_type) {
                            s.section_type = s.is_package ? 'package' : 'room';
                            if (!s.is_package && s.items && s.items.length > 0 && s.items.every(i => i.is_package)) {
                                s.section_type = 'package';
                            }
                        }
                        if (s.items) {
                            s.items.forEach(i => this.hydrateItem(i));
                        }
                    });
                } else {
                    if (this.estimate.items) {
                        this.estimate.items.forEach(i => this.hydrateItem(i));
                    }
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

            hydrateItem(item) {
                // 1. Resolve Product ID
                if (!item.product_id && item.product) {
                    item.product_id = item.product.id;
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
                item.name = item.name || '';
                item.unit_price = parseFloat(item.unit_price || 0);
                item.quantity = parseFloat(item.quantity || 1);
                item.tax_1 = parseFloat(item.tax_1 || 0);
                item.tax_2 = 0; // Use one tax only (GST)
                
                // Fallback to product defaults for Unit Type if missing on the item
                if ((item.unit_type_id === null || item.unit_type_id === undefined || item.unit_type_id === 'null' || item.unit_type_id === '') && item.product && item.product.unit_type_id) {
                    item.unit_type_id = item.product.unit_type_id;
                }
                item.unit_type_id = (item.unit_type_id && item.unit_type_id !== 'null') ? String(item.unit_type_id) : '';

                // Ensure unit_type is synchronized with unit_type_id if missing
                if (item.unit_type_id && (!item.unit_type || item.unit_type === 'nos')) {
                    const units = this.getUnitsByTypeId(item.unit_type_id);
                    if (units && units.length > 0) {
                        const currentUnit = (item.unit_type || '').toLowerCase();
                        const isValid = units.some(u => u.toLowerCase() === currentUnit);
                        if (!isValid) {
                            item.unit_type = units[0];
                        }
                    }
                }

                item._showTypePicker = !!item.unit_type_id;
                item.size = item.size || '';
                item.length = item.length || '';
                item.width = item.width || '';
                item.height = item.height || '';

                if (forcedMethod) {
                    item.formula = forcedMethod;
                }

                item.showCalculator = !!(item.length || item.width || item.height) || ['formula', 'area', 'volume', 'area_lh'].includes(item.formula);

                if (!item.image_url && item.product && item.product.images && item.product.images.length > 0) {
                    item.image_url = '/storage/' + item.product.images[0].image_path;
                }
                
                if (!item.options) item.options = [];
                if (!item.selected_options) item.selected_options = {};

                // Generate UID if missing
                if (!item._uid) item._uid = item.id ? 'item-' + item.id : 'item-' + Math.random().toString(36).substr(2, 9);

                item.is_package = item.is_package || (item.description && item.description.startsWith('Package:'));
            },

            isItemLocked(item) {
                // An item is considered locked if it came from a product or is explicitly a package item
                return !!item.product_id || !!item.is_package || !!item.is_locked;
            },

            initClientSearch() {
                const el = this.$refs.clientSearch;
                // Prevent double initialization
                if (!el || el._choices) return;

                const choices = new Choices(el, {
                    searchEnabled: true,
                    searchPlaceholderValue: 'Search clients...',
                    noResultsText: 'No clients found',
                    itemSelectText: '',
                    shouldSort: true,
                });

                // Save instance
                el._choices = choices;

                // Sync initial value if editing
                if (this.estimate.client_id) {
                    choices.setChoiceByValue(String(this.estimate.client_id));
                }

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
                if (sectionsContainer && !sectionsContainer._sortable) {
                    sectionsContainer._sortable = new Sortable(sectionsContainer, {
                        animation: 150,
                        handle: '.section-handle',
                        draggable: '.section-card',
                        onEnd: (evt) => {
                            const movedItem = this.estimate.sections.splice(evt.oldDraggableIndex, 1)[0];
                            this.estimate.sections.splice(evt.newDraggableIndex, 0, movedItem);
                            this.calculateTotals();
                        }
                    });
                }

                // Section Items
                document.querySelectorAll('.section-items-sortable').forEach(el => {
                    if (el._sortable) return;
                    el._sortable = new Sortable(el, {
                        animation: 150,
                        group: 'items',
                        handle: '.handle',
                        draggable: '.item-row',
                        onEnd: (evt) => {
                            const fromSectionIdx = parseInt(evt.from.dataset.sectionIndex);
                            const toSectionIdx = parseInt(evt.to.dataset.sectionIndex);

                            // Ensure section indices are valid
                            if (isNaN(fromSectionIdx)) return;

                            if (evt.from === evt.to) {
                                // Same section move
                                const items = this.estimate.sections[fromSectionIdx].items;
                                const movedItem = items.splice(evt.oldDraggableIndex, 1)[0];
                                items.splice(evt.newDraggableIndex, 0, movedItem);
                            } else {
                                // Cross section move
                                if (isNaN(toSectionIdx)) return;
                                const fromItems = this.estimate.sections[fromSectionIdx].items;
                                const toItems = this.estimate.sections[toSectionIdx].items;
                                const movedItem = fromItems.splice(evt.oldDraggableIndex, 1)[0];
                                toItems.splice(evt.newDraggableIndex, 0, movedItem);
                            }
                            this.calculateTotals();
                        }
                    });
                });

                // Standard Items
                const standardList = document.querySelector('.standard-items-sortable');
                if (standardList && !standardList._sortable) {
                    standardList._sortable = new Sortable(standardList, {
                        animation: 150,
                        handle: '.handle',
                        draggable: '.item-row',
                        onEnd: (evt) => {
                            const movedItem = this.estimate.items.splice(evt.oldDraggableIndex, 1)[0];
                            this.estimate.items.splice(evt.newDraggableIndex, 0, movedItem);
                            this.calculateTotals();
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
                console.log('getUnitsByTypeId called for ID:', typeId);
                const type = this.unitTypes.find(t => t.id == typeId);
                if (!type) {
                    console.warn('Unit Type not found for ID:', typeId, 'Available:', this.unitTypes);
                    return [];
                }
                console.log('Found Unit Type:', type.name, 'Units:', type.units);
                return type ? type.units : [];
            },

            onUnitTypeChange(item) {
                console.log('onUnitTypeChange called for item:', item.name || item.item_name, 'Selected ID:', item.unit_type_id);
                const units = this.getUnitsByTypeId(item.unit_type_id);
                console.log('Units for selected ID:', units);
                if (units.length > 0) {
                    item.unit_type = units[0];
                } else {
                    item.unit_type = '';
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
                if (!product) return;
                // Check for options
                if (product.options && Array.isArray(product.options) && product.options.length > 0) {
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
                    unit_type_id: product.unit_type_id ? String(product.unit_type_id) : '',
                    description: product.description || '',
                    tax_1: parseFloat(this.defaults.tax_1_rate || 0),
                    tax_2: parseFloat(this.defaults.tax_2_rate || 0),
                    image_url: (product.images && product.images.length > 0) ? '/storage/' + product.images[0].image_path : null,
                    length: product.dimensions?.length || '',
                    width: product.dimensions?.width || '',
                    height: product.dimensions?.height || '',
                    formula: initFormula,
                    showCalculator: showCalc,
                    _showTypePicker: false,
                    options: [],
                    selected_options: {},
                    is_package: false,
                    _uid: 'item-' + Math.random().toString(36).substr(2, 9)
                };
                if (showCalc) {
                    this.calculateSize(newItem);
                }
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
                    unit_type_id: product.unit_type_id ? String(product.unit_type_id) : null,
                    description: product.description || '',
                    tax_1: parseFloat(this.defaults.tax_1_rate || 0),
                    tax_2: parseFloat(this.defaults.tax_2_rate || 0),
                    image_url: (product.images && product.images.length > 0) ? '/storage/' + product.images[0].image_path : null,
                    length: product.dimensions?.length || '',
                    width: product.dimensions?.width || '',
                    height: product.dimensions?.height || '',
                    formula: initFormula,
                    showCalculator: showCalc,
                    _showTypePicker: false,
                    options: selectedOptionsList,
                    selected_options: { ...this.configModal.options },
                    is_package: false,
                    _uid: 'item-' + Math.random().toString(36).substr(2, 9)
                };

                if (showCalc) {
                    this.calculateSize(newItem);
                }
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
                    size: '',
                    unit_type: 'nos',
                    unit_type_id: null,
                    tax_1: 0,
                    tax_2: 0,
                    length: '',
                    width: '',
                    height: '',
                    formula: '',
                    showCalculator: false,
                    is_package: false,
                    options: [],
                    product_id: null,
                    is_locked: false,
                    _uid: 'item-' + Math.random().toString(36).substr(2, 9)
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
                    const roomName = this.roomModal.name.trim();
                    // Check for duplicate room name (including checking against existing templates/packages masquerading as sections)
                    const isDuplicate = this.estimate.sections.some(section =>
                        section.name.trim().toLowerCase() === roomName.toLowerCase()
                    );

                    if (isDuplicate) {
                        alert('A room with this name already exists.');
                        return;
                    }

                    this.estimate.sections.push({
                        name: roomName,
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
                    const section = this.estimate.sections[index];
                    // Save to history before removal
                    this.deletedSections.push({
                        ...JSON.parse(JSON.stringify(section)),
                        _originalIndex: index
                    });
                    this.estimate.sections.splice(index, 1);
                    this.calculateTotals();
                }
            },

            removeItem(sIdx, iIdx) {
                let item;
                if (sIdx !== null) {
                    item = this.estimate.sections[sIdx].items[iIdx];
                    this.deletedItems.push({
                        ...JSON.parse(JSON.stringify(item)),
                        _sectionIndex: sIdx,
                        _originalIndex: iIdx,
                        _sectionName: this.estimate.sections[sIdx].name
                    });
                    this.estimate.sections[sIdx].items.splice(iIdx, 1);
                } else {
                    item = this.estimate.items[iIdx];
                    this.deletedItems.push({
                        ...JSON.parse(JSON.stringify(item)),
                        _sectionIndex: null,
                        _originalIndex: iIdx
                    });
                    this.estimate.items.splice(iIdx, 1);
                }
                this.calculateTotals();
            },

            restoreItem(index) {
                const deletedItem = this.deletedItems[index];
                if (deletedItem._sectionIndex !== null) {
                    // Restore to section - check if section still exists by index or name
                    let section = this.estimate.sections[deletedItem._sectionIndex];
                    if (!section || section.name !== deletedItem._sectionName) {
                        // Try to find by name instead
                        section = this.estimate.sections.find(s => s.name === deletedItem._sectionName);
                    }

                    if (section) {
                        section.items.push(deletedItem);
                    } else {
                        // Default to standard items if section gone
                        this.estimate.items.push(deletedItem);
                    }
                } else {
                    this.estimate.items.push(deletedItem);
                }
                this.deletedItems.splice(index, 1);
                this.calculateTotals();
            },

            restoreSection(index) {
                const deletedSection = this.deletedSections[index];
                this.estimate.sections.push(deletedSection);
                this.deletedSections.splice(index, 1);
                this.calculateTotals();
            },

            // --- Importers ---
            applyTemplate(template) {
                console.log('Apply Template [DEBUG]:', template.name, 'Items Count:', template.items?.length);
                console.log('Template Data:', JSON.parse(JSON.stringify(template)));
                // Check if room with template name exists
                const isDuplicate = this.estimate.sections.some(section =>
                    section.name.trim().toLowerCase() === template.name.trim().toLowerCase()
                );

                if (isDuplicate) {
                    alert('A room with this name already exists.');
                    return;
                }

                const newSection = {
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
                        const unitTypeIdString = unitTypeId ? String(unitTypeId) : '';

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

                        const newItem = {
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
                            product: i.product || null,
                            tax_1: 0,
                            tax_2: 0,
                            length: i.length || '', width: i.width || '', height: i.height || '', formula: '', showCalculator: false,
                            options: (() => {
                                if (i.options && i.options.length > 0) return JSON.parse(JSON.stringify(i.options));
                                // Fallback: If no options in template but product has them, use defaults (first value)
                                if (i.product && i.product.options && i.product.options.length > 0) {
                                    return i.product.options.map(opt => {
                                        if (opt.values && opt.values.length > 0) {
                                            const val = opt.values[0];
                                            return {
                                                name: opt.name,
                                                value: val.value,
                                                price_adjustment: val.price_adjustment
                                            };
                                        }
                                        return null;
                                    }).filter(Boolean); // Filter out nulls
                                }
                                return [];
                            })(),
                            is_package: false,
                            is_locked: true,
                            _uid: 'item-' + Math.random().toString(36).substr(2, 9)
                        };

                        this.hydrateItem(newItem);
                        return newItem;
                    });
                }
                this.estimate.sections.push(newSection);
                this.calculateTotals();
                this.$nextTick(() => this.initSortable());
            },

            applyPackage(pkg, sIdx) {
                if (!pkg.items) return;
                const newItems = pkg.items.map(i => {
                    const newItem = {
                        id: null,
                        name: i.item_name || i.name || '',
                        unit_price: parseFloat(i.unit_price || 0),
                        quantity: parseFloat(i.quantity || 1),
                        size: i.size || '',
                        unit_type: i.unit_type || 'nos',
                        unit_type_id: null,
                        description: `Package: ${pkg.name}`,
                        tax_1: 0,
                        tax_2: 0,
                        length: '', width: '', height: '', formula: '', showCalculator: false,
                        options: [],
                        is_package: true,
                        _uid: this.generateUid()
                    };
                    this.hydrateItem(newItem);
                    return newItem;
                });

                if (this.estimate.type === 'room_based') {
                    this.estimate.sections.push({
                        name: pkg.name,
                        items: newItems,
                        section_type: 'package',
                        is_package: true
                    });
                } else {
                    newItems.forEach(item => this.estimate.items.push(item));
                }
                this.calculateTotals();
                this.$nextTick(() => this.initSortable());
            },

            // --- Calculations ---
            calculateItemTotal(item) {
                const price = parseFloat(item.unit_price) || 0;
                const qty = parseFloat(item.quantity) || 0;
                let size = 1;

                // If item has a specific calculation formula (area/volume/custom), we multiply by size.
                if (item.formula && ['area', 'volume', 'area_lh', 'formula'].includes(item.formula)) {
                    const s = parseFloat(item.size);
                    if (!isNaN(s) && s > 0) size = s;
                }

                const total = price * qty * size;
                return parseFloat(total.toFixed(2));
            },

            calculateSectionTotal(section) {
                if (!section.items) return 0;
                return section.items.reduce((sum, item) => sum + this.calculateItemTotal(item), 0);
            },

            calculateTotals() {
                let subtotal = 0;
                let totalTax = 0;

                // 1. Calculate Items in Sections (if any)
                if (this.estimate.sections && this.estimate.sections.length > 0) {
                    this.estimate.sections.forEach(section => {
                        const secTotal = this.calculateSectionTotal(section);
                        section.subtotal = secTotal; 
                        subtotal += secTotal;
                    });
                } 

                // 2. Calculate Standalone Items (if any)
                // We ALWAYS include these because the backend PriceCalculator 
                // iterates over all estimate items, regardless of section_id.
                if (this.estimate.items && this.estimate.items.length > 0) {
                    this.estimate.items.forEach(item => {
                        subtotal += this.calculateItemTotal(item);
                    });
                }

                // Standardize subtotal rounding
                subtotal = parseFloat(subtotal.toFixed(2));

                let discount = 0;
                if (this.estimate.discount_value > 0) {
                    discount = this.estimate.discount_type === 'percentage'
                        ? subtotal * (this.estimate.discount_value / 100)
                        : parseFloat(this.estimate.discount_value);
                    discount = parseFloat(discount.toFixed(2));
                }

                // Coupon Discount
                const couponDiscount = parseFloat(parseFloat(this.estimate.coupon_discount || 0).toFixed(2));

                // Tax base is normally Subtotal - Discount
                const taxBase = Math.max(0, subtotal - discount);
                const t1Rate = (parseFloat(this.estimate.tax_1) || 0) / 100;
                totalTax = parseFloat((taxBase * t1Rate).toFixed(2));

                const transportation = parseFloat(parseFloat(this.estimate.transportation_charges || 0).toFixed(2));

                this.totals = {
                    subtotal,
                    totalTax,
                    discount: parseFloat((discount + couponDiscount).toFixed(2)),
                    grandTotal: parseFloat(((subtotal - discount - couponDiscount) + totalTax + transportation).toFixed(2))
                };

                // Trigger Remote AJAX Sync (Debounced for authoritative backend check)
                clearTimeout(this._calcTimeout);
                this._calcTimeout = setTimeout(() => {
                    this.calculateTotalsRemote();
                }, 800);
            },

            calculateTotalsRemote() {
                this.isCalculating = true;

                // Prepare payload
                const payload = {
                    type: this.estimate.type,
                    tax_1: this.estimate.tax_1,
                    tax_2: this.estimate.tax_2,
                    discount_type: this.estimate.discount_type,
                    discount_value: this.estimate.discount_value,
                    coupon_discount: this.estimate.coupon_discount,
                    transportation_charges: this.estimate.transportation_charges,
                    coupon_code_id: this.estimate.coupon_code_id,
                    sections: this.estimate.sections,
                    items: this.estimate.items
                };

                fetch('{{ route("estimates.calculate") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(payload)
                })
                    .then(r => r.json())
                    .then(data => {
                        // Update totals with authoritative server-side results (Keep as Numbers!)
                        this.totals.subtotal = parseFloat(data.subtotal);
                        this.totals.totalTax = parseFloat(data.total_tax);
                        this.estimate.coupon_discount = parseFloat(data.coupon_discount || 0);
                        this.totals.discount = parseFloat(
                            ((data.discount_total || data.discount || 0) + this.estimate.coupon_discount).toFixed(2)
                        );
                        this.totals.grandTotal = parseFloat(data.grand_total);

                        // Sync approval chain if relevant
                        if (data.approval_chain_id) {
                            this.estimate.approval_chain_id = data.approval_chain_id;
                        }
                    })
                    .catch(e => console.error('Remote calculation error:', e))
                    .finally(() => {
                        this.isCalculating = false;
                    });
            },

            toggleCalculator(item) {
                item.showCalculator = !item.showCalculator;
            },

            calculateSize(item) {
                const l = parseFloat(item.length) || 0;
                const w = parseFloat(item.width) || 0;
                const h = parseFloat(item.height) || 0;

                // Check if the item is linked to a product with a strict calculation method
                let forcedMethod = null;
                let customFormulaString = null;
                const isProductLinked = item.product_id !== null && item.product_id !== undefined && item.product_id !== '' && item.product_id !== 0 && item.product_id !== '0';

                if (isProductLinked) {
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
                            let expression = customFormulaString.toLowerCase()
                                .replace(/\bl\b/g, l)
                                .replace(/\bw\b/g, w)
                                .replace(/\bh\b/g, h);

                            if (/^[0-9+\-*/().\s]+$/.test(expression)) {
                                const result = this.safeEvaluate(expression);
                                item.size = isNaN(result) ? 0 : parseFloat(result).toFixed(2);
                            } else {
                                console.warn('Invalid characters in custom formula:', expression);
                                item.size = 0;
                            }
                        } catch (e) {
                            console.error('Error evaluating custom formula:', e);
                            item.size = 0;
                        }
                    } else if (forcedMethod === 'volume') {
                        item.size = (l * w * h).toFixed(2);
                    } else if (forcedMethod === 'area_lh') {
                        item.size = (l * h).toFixed(2);
                    } else {
                        // area
                        item.size = (l * w).toFixed(2);
                    }
                } else {
                    // If it's a Product Item (isProductLinked is true) but has NO forced method,
                    // we MUST stop here. We should not infer 'area'/'volume' just because dimensions exist.
                    // This allows "Fixed" products to have descriptive dimensions without affecting Price/Quantity.
                    if (isProductLinked) {
                        return;
                    }

                    // Default Inference Logic (Only for Custom/Ad-hoc Items)
                    if (l > 0 && w > 0) {
                        if (h > 0) {
                            item.size = (l * w * h).toFixed(2);
                            item.formula = 'volume';
                        } else {
                            item.size = (l * w).toFixed(2);
                            item.formula = 'area';
                        }
                    } else if (l > 0 && h > 0) {
                        item.size = (l * h).toFixed(2);
                        item.formula = 'area_lh';
                    } else {
                        // Insufficient dimensions
                        item.size = 0;
                    }
                }

                // Recalculate Totals (Quantity is essentially 'Count' now, so we don't auto-update it)
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
                            this.estimate.coupon_discount = parseFloat(data.discount || 0);
                            this.appliedCouponCode = this.couponInput.toUpperCase();
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
                this.estimate.coupon_discount = 0;
                this.appliedCouponCode = '';
                this.couponInput = '';
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

                // Ensure at least one item exists
                const totalItems = this.estimate.type === 'room_based'
                    ? this.estimate.sections.reduce((sum, s) => sum + (s.items?.length || 0), 0)
                    : this.estimate.items.length;

                if (totalItems === 0) {
                    errors.push({
                        location: 'Estimate Items',
                        itemName: 'Content',
                        message: 'The estimate must contain at least one room or item before saving.'
                    });
                }

                // Validate unit configurations
                const validateItem = (item, location) => {
                    // Only validate if a unit type has been selected or the picker was explicitly opened
                    // This allows "pure" custom items (Manual unit type) to skip this check
                    if (item.unit_type_id) {
                        if (!item.unit_type || item.unit_type === '') {
                            errors.push({
                                location: location,
                                itemName: item.name || 'Unnamed Item',
                                message: 'Specific Unit is required for the selected Unit Type'
                            });
                        }
                    } else if (item._showTypePicker) {
                        // If they clicked "Unit" button but didn't pick anything
                        errors.push({
                            location: location,
                            itemName: item.name || 'Unnamed Item',
                            message: 'Please select a Unit Type or leave as Manual'
                        });
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

            // --- Form Submission & Autosave ---


            previewPdf() {
                this.submitHiddenForm('{{ route("estimates.preview") }}', true);
            },

            submitForm(forceVersion = false) {
                this.saveEstimate(true, forceVersion);
            },

            saveAjax() {
                this.saveEstimate(false, false);
            },

            async saveEstimate(redirectAfterSuccess = false, forceVersion = false) {
                if (this.isSubmitting) return;

                if (!this.validateForm()) {
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                    return;
                }

                this.isSubmitting = true;

                try {
                    const method = this.estimate.id ? 'PUT' : 'POST';
                    const url = this.estimate.id
                        ? `{{ route('estimates.update', ':id') }}`.replace(':id', this.estimate.id)
                        : `{{ route('estimates.store') }}`;

                    const data = {
                        _token: '{{ csrf_token() }}',
                        _method: method,
                        ...this.estimate,
                        sections: this.estimate.sections,
                        items: this.estimate.items,
                        deleted_sections: this.deletedSections.map(s => s.id).filter(id => id),
                        deleted_items: this.deletedItems.map(i => i.id).filter(id => id),
                        force_version: forceVersion,
                        last_update_timestamp: this.estimate.updated_at
                    };

                    const response = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify(data)
                    });

                    if (!response.ok) {
                        const status = response.status;
                        const text = await response.text();
                        let errorMessage = `Server error (${status})`;
                        
                        try {
                            const errorJson = JSON.parse(text);
                            errorMessage = errorJson.message || errorMessage;
                            if (errorJson.errors) {
                                errorMessage += "\n\n" + Object.values(errorJson.errors).flat().join("\n");
                            }
                        } catch (e) {
                            // Not JSON, show snippet of HTML if it looks like an error page
                            if (text.includes('Page Expired') || status === 419) {
                                errorMessage = "Your session has expired. Please refresh the page and try again.";
                            } else if (text.includes('<!DOCTYPE html>') || text.includes('<html>')) {
                                // Extract title or h1 if possible
                                const match = text.match(/<title>(.*?)<\/title>/i) || text.match(/<h1>(.*?)<\/h1>/i);
                                if (match) errorMessage += ": " + match[1];
                            }
                        }
                        
                        throw new Error(errorMessage);
                    }

                    const result = await response.json();

                    if (result.success) {
                        if (redirectAfterSuccess) {
                            window.location.href = result.redirect_url;
                            return; 
                        }

                        if (result.estimate) {
                            // Sync IDs and metadata back to Alpine state to prevent duplicates on next save
                            this.estimate.id = result.estimate.id;
                            if (result.estimate.sections && this.estimate.type === 'room_based') {
                                result.estimate.sections.forEach((serverSection, sIdx) => {
                                    if (this.estimate.sections[sIdx]) {
                                        this.estimate.sections[sIdx].id = serverSection.id;
                                        if (serverSection.items) {
                                            serverSection.items.forEach((serverItem, iIdx) => {
                                                if (this.estimate.sections[sIdx].items[iIdx]) {
                                                    this.estimate.sections[sIdx].items[iIdx].id = serverItem.id;
                                                }
                                            });
                                        }
                                    }
                                });
                            } else if (result.estimate.items && this.estimate.type === 'standard') {
                                result.estimate.items.forEach((serverItem, iIdx) => {
                                    if (this.estimate.items[iIdx]) {
                                        this.estimate.items[iIdx].id = serverItem.id;
                                    }
                                });
                            }
                        }

                        if (result.last_update_timestamp) {
                            this.estimate.updated_at = result.last_update_timestamp;
                        }

                        this.isSubmitting = false;
                        alert(result.message || 'Saved successfully!');
                    } else {
                        const errorMsg = result.message || 'Submission failed.';
                        this.isSubmitting = false;
                        alert(errorMsg + (result.errors ? "\n\n" + Object.values(result.errors).flat().join("\n") : ""));
                    }
                } catch (e) {
                    this.isSubmitting = false;
                    console.error('Save failed:', e);
                    alert(e.message.startsWith('Server error') || e.message.startsWith('Your session') ? e.message : 'Submission failed: ' + e.message);
                }
            },

            submitHiddenForm(url, isNewTab = false, method = 'POST', forceVersion = false) {
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
                const fields = ['client_id', 'estimate_date', 'expiry_date', 'currency', 'status', 'discount_type', 'discount_value', 'client_note', 'admin_note', 'terms', 'pdf_theme', 'type', 'coupon_code_id', 'pdf_template_id', 'tax_1', 'tax_2', 'transportation_charges'];
                fields.forEach(f => {
                    let val = this.estimate[f];
                    if (f === 'discount_value') {
                        val = (val !== null && val !== undefined && val !== '') ? val : 0;
                    }
                    app(f, val ?? '');
                });

                if (forceVersion) {
                    app('force_version', 1);
                }

                // Sections/Items
                const processItem = (i, iIdx, prefix) => {
                    for (const [k, v] of Object.entries(i)) {
                        if (v !== null && v !== undefined && k !== 'image_url' && k !== 'options' && !k.startsWith('_')) {
                            let val = v;
                            if (k === 'is_package') val = v ? 1 : 0;
                            app(`${prefix}[${iIdx}][${k}]`, val);
                        } else if (k === 'options' && Array.isArray(v)) {
                            v.forEach((opt, oIdx) => {
                                app(`${prefix}[${iIdx}][options][${oIdx}][name]`, opt.name);
                                app(`${prefix}[${iIdx}][options][${oIdx}][value]`, opt.value);
                                app(`${prefix}[${iIdx}][options][${oIdx}][price_adjustment]`, opt.price_adjustment);
                            });
                        }
                    }
                };

                if (this.estimate.type === 'room_based') {
                    // Include all rooms (even empty ones) so they don't get deleted automatically
                    const activeSections = this.estimate.sections;

                    activeSections.forEach((s, sIdx) => {
                        app(`sections[${sIdx}][name]`, s.name);
                        if (s.id) app(`sections[${sIdx}][id]`, s.id);
                        app(`sections[${sIdx}][section_type]`, s.section_type || 'room');
                        app(`sections[${sIdx}][is_package]`, s.is_package ? 1 : 0);

                        s.items.forEach((i, iIdx) => {
                            processItem(i, iIdx, `sections[${sIdx}][items]`);
                        });
                    });

                    // Also include standalone items if any
                    this.estimate.items.forEach((i, iIdx) => {
                        processItem(i, iIdx, 'items');
                    });
                } else {
                    this.estimate.items.forEach((i, iIdx) => {
                        processItem(i, iIdx, 'items');
                    });
                }

                document.body.appendChild(form);
                form.submit();
            },
            async fetchHistory() {
                this.historyModal.isLoading = true;
                this.historyModal.isOpen = true;
                try {
                    const response = await fetch(`/estimates/${this.estimate.id}/restore-history`);
                    this.historyModal.data = await response.json();
                } catch (err) {
                    console.error('Error fetching history', err);
                    alert('Could not load deletion history.');
                } finally {
                    this.historyModal.isLoading = false;
                }
            },

            async restoreSession(timestamp) {
                if (!confirm('This will restore all rooms and items deleted at this time. The page will reload to show the changes. Continue?')) {
                    return;
                }

                try {
                    const response = await fetch(`/estimates/${this.estimate.id}/restore-session`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({ timestamp })
                    });
                    const result = await response.json();
                    if (result.success) {
                        window.location.reload();
                    } else {
                        alert('Error: ' + (result.error || 'Unknown error'));
                    }
                } catch (err) {
                    console.error('Error restoring session', err);
                    alert('Restoration failed.');
                }
            }
        }));
    });
</script>
