<script>
    window.automationPageData = function () {
        return {
            showCreateModal: false,
            showEditModal: false,
            showRuleModal: false,
            showLogsModal: false,
            showVisualizationModal: false,
            showTemplateModal: false, // Template Modal State
            templates: [],
            loadingTemplates: false,
            templateTab: 'all', // all, system, user
            visualizationView: 'flowchart', // flowchart or timeline
            loadingVisualization: false,
            isPreviewMode: false,
            timelineData: [],
            viewMode: 'list', // list, analytics
            loadingAnalytics: false,
            dashboardStats: null,
            analyticsChart: null,

            // ... existing state ...
            loadingSearch: false, // Needed for search UI if not already present
            searchQuery: '',
            filterTrigger: '',
            filterStatus: '',

            // Restored missing state
            showMetricsModal: false,
            isSubmitting: false,
            logSearch: '',
            expandedTrace: null,
            logs: {},
            metrics: {
                trigger_count: 0,
                completion_rate: 0,
                failure_rate: 0,
                conversion: {
                    opened: 0,
                    accepted: 0,
                    total_entities: 0
                }
            },
            loadingLogs: false,
            loadingMetrics: false,
            selectedRules: [],

            // ... existing state ...

            get filteredTemplates() {
                if (this.templateTab === 'system') {
                    return this.templates.filter(t => t.is_system);
                }
                if (this.templateTab === 'user') {
                    return this.templates.filter(t => !t.is_system);
                }
                return this.templates;
            },

            get filteredLogs() {
                if (!this.logSearch) return this.logs;
                const query = this.logSearch.toLowerCase();

                // Filter the object
                const filtered = {};
                for (const [eventId, trace] of Object.entries(this.logs)) {
                    // Search in event ID
                    if (eventId.toLowerCase().includes(query)) {
                        filtered[eventId] = trace;
                        continue;
                    }

                    // Search in logs content (action type, status, error)
                    const matchesContent = trace.some(log =>
                        log.action_type.toLowerCase().includes(query) ||
                        log.status.toLowerCase().includes(query) ||
                        (log.error && log.error.toLowerCase().includes(query))
                    );

                    if (matchesContent) {
                        filtered[eventId] = trace;
                    }
                }
                return filtered;
            },

            openTemplateModal() {
                this.showTemplateModal = true;
                this.fetchTemplates();
            },

            async fetchTemplates() {
                this.loadingTemplates = true;
                try {
                    const response = await fetch("{{ route('automation.templates.index') }}");
                    const data = await response.json();
                    this.templates = data.templates;
                } catch (error) {
                    console.error('Error fetching templates:', error);
                    alert('Failed to load templates.');
                } finally {
                    this.loadingTemplates = false;
                }
            },

            async installTemplate(template) {
                if (!confirm(`Create new automation from "${template.name}"?`)) return;

                try {
                    const response = await fetch(`/automation/templates/${template.id}/install`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                                'content')
                        }
                    });

                    if (response.ok) {
                        const data = await response.json();
                        window.location.href = data.redirect_url;
                    } else {
                        alert('Failed to install template.');
                    }
                } catch (error) {
                    console.error('Error installing template:', error);
                    alert('An error occurred.');
                }
            },

            fetchDashboardAnalytics() {
                this.loadingAnalytics = true;
                fetch("{{ route('automation.analytics.dashboard') }}")
                    .then(res => res.json())
                    .then(data => {
                        this.dashboardStats = data;
                        this.$nextTick(() => {
                            this.renderAnalyticsChart();
                        });
                    })
                    .catch(err => {
                        console.error('Failed to load analytics:', err);
                    })
                    .finally(() => {
                        this.loadingAnalytics = false;
                    });
            },

            renderAnalyticsChart() {
                const ctx = document.getElementById('analyticsChart');
                if (!ctx || !this.dashboardStats) return;

                if (this.analyticsChart) {
                    this.analyticsChart.destroy();
                }

                // Check if Chart is defined (loaded from CDN)
                if (typeof Chart === 'undefined') return;

                this.analyticsChart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: this.dashboardStats.chart.labels,
                        datasets: [{
                            label: 'Total Executions',
                            data: this.dashboardStats.chart.executions,
                            borderColor: 'rgb(79, 70, 229)',
                            tension: 0.1
                        },
                        {
                            label: 'Success Rate (%)',
                            data: this.dashboardStats.chart.success_rate,
                            borderColor: 'rgb(16, 185, 129)',
                            tension: 0.1,
                            yAxisID: 'y1'
                        }
                        ]
                    },
                    options: {
                        responsive: true,
                        interaction: {
                            mode: 'index',
                            intersect: false,
                        },
                        scales: {
                            y: {
                                type: 'linear',
                                display: true,
                                position: 'left',
                                title: {
                                    display: true,
                                    text: 'Executions'
                                }
                            },
                            y1: {
                                type: 'linear',
                                display: true,
                                position: 'right',
                                grid: {
                                    drawOnChartArea: false,
                                },
                                min: 0,
                                max: 100,
                                title: {
                                    display: true,
                                    text: 'Success Rate %'
                                }
                            },
                        }
                    }
                });
            },

            async confirmDeleteTemplate(template) {
                if (!confirm(`Are you sure you want to delete template "${template.name}"?`)) return;

                try {
                    const response = await fetch(`/automation/templates/${template.id}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                                'content')
                        }
                    });

                    if (response.ok) {
                        this.fetchTemplates(); // Refresh list
                    } else {
                        alert('Failed to delete template.');
                    }
                } catch (error) {
                    console.error('Error deleting template:', error);
                }
            },

            async saveAsTemplate() {
                const name = prompt("Enter a name for this template:", this.editingRule.name + " Template");
                if (!name) return;

                const category = prompt("Enter a category (optional):", "Custom");

                try {
                    const response = await fetch(`/automation/${this.editingRule.id}/save-as-template`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                                'content')
                        },
                        body: JSON.stringify({
                            name: name,
                            category: category,
                            description: this.editingRule.description
                        })
                    });

                    if (response.ok) {
                        alert('Template saved successfully!');
                    } else {
                        const data = await response.json();
                        alert('Failed to save template: ' + (data.message || 'Unknown error'));
                    }
                } catch (error) {
                    console.error('Error saving template:', error);
                    alert('An error occurred.');
                }
            },

            // Schedule state
            schedules: [],
            schedulesExpanded: false,
            safetyExpanded: true, // Safety Settings section in Rule Modal
            schedulePresets: [],
            selectedPreset: '',
            timezones: [],
            newSchedule: {
                name: '',
                cron_expression: '',
                timezone: 'UTC'
            },
            editingRule: {
                id: null,
                name: '',
                trigger_event: '',
                condition_logic: 'AND',
                conditions: [],
                actions: [{
                    type: 'email',
                    to: '',
                    field: '',
                    value: '',
                    delay: 0,
                    retry_limit: 0,
                    on_failure: 'continue',
                    is_enabled: true,
                    description: '',
                    conditions: []
                }],
                settings: {
                    is_enabled: true,
                    rate_limit_count: null,
                    rate_limit_period: 1440,
                    max_executions_per_entity: null
                },
                is_active: true
            },
            init() {
                try {
                    this.timezones = Intl.supportedValuesOf('timeZone');
                } catch (e) {
                    this.timezones = ['UTC', 'America/New_York', 'Europe/London', 'Asia/Kolkata'];
                }

                const params = new URLSearchParams(window.location.search);
                if (params.get('open_templates') === '1') {
                    this.openTemplateModal();
                }
            },
            fetchSchedules(automationId) {
                fetch(`/automation/${automationId}/schedules`)
                    .then(res => res.json())
                    .then(data => {
                        this.schedules = data.schedules;
                        this.schedulePresets = data.presets;
                    });
            },
            saveSchedule() {
                if (!this.editingRule.id || !this.newSchedule.cron_expression) return;

                fetch(`/automation/${this.editingRule.id}/schedule`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                            'content')
                    },
                    body: JSON.stringify(this.newSchedule)
                })
                    .then(res => {
                        if (!res.ok) return res.json().then(e => Promise.reject(e));
                        return res.json();
                    })
                    .then(data => {
                        this.schedules.push(data.schedule);
                        this.newSchedule.name = '';
                        this.newSchedule.cron_expression = '';
                        this.selectedPreset = '';
                    })
                    .catch(err => {
                        alert(err.message || 'Failed to create schedule');
                    });
            },
            toggleSchedule(schedule) {
                fetch(`/automation/schedule/${schedule.id}/toggle`, {
                    method: 'PATCH',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                })
                    .then(res => res.json())
                    .then(data => {
                        schedule.is_active = data.is_active;
                    });
            },
            deleteSchedule(schedule) {
                if (!confirm('Are you sure you want to delete this schedule?')) return;
                fetch(`/automation/schedule/${schedule.id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                })
                    .then(() => {
                        this.schedules = this.schedules.filter(s => s.id !== schedule.id);
                    });
            },
            applyPreset() {
                if (this.selectedPreset) {
                    this.newSchedule.cron_expression = this.selectedPreset;
                }
            },
            formatDate(dateString) {
                if (!dateString) return 'N/A';
                return new Date(dateString).toLocaleString();
            },
            openCreateModal() {
                this.editingRule = {
                    id: null,
                    name: '',
                    trigger_event: '',
                    condition_logic: 'AND',
                    conditions: [],
                    actions: [{
                        type: 'email',
                        to: '',
                        field: '',
                        value: '',
                        delay: 0,
                        retry_limit: 0,
                        on_failure: 'continue',
                        is_enabled: true,
                        description: '',
                        conditions: []
                    }],
                    settings: {
                        is_enabled: true,
                        rate_limit_count: null,
                        rate_limit_period: 1440,
                        max_executions_per_entity: null
                    },
                    is_active: true
                };
                this.showCreateModal = true;
            },
            addCondition(rule_or_action) {
                if (!rule_or_action.conditions) rule_or_action.conditions = [];
                rule_or_action.conditions.push({
                    type: 'payload',
                    field: '',
                    operator: '=',
                    value: ''
                });
            },
            removeCondition(rule_or_action, index) {
                rule_or_action.conditions.splice(index, 1);
            },
            addAction(rule) {
                if (!rule.actions) rule.actions = [];
                rule.actions.push({
                    type: 'email',
                    to: '',
                    field: '',
                    value: '',
                    delay: 0,
                    retry_limit: 0,
                    on_failure: 'continue',
                    is_enabled: true,
                    description: '',
                    conditions: []
                });
            },
            removeAction(rule, index) {
                rule.actions.splice(index, 1);
            },
            moveActionUp(rule, index) {
                if (index > 0) {
                    const temp = rule.actions[index];
                    rule.actions[index] = rule.actions[index - 1];
                    rule.actions[index - 1] = temp;
                }
            },
            moveActionDown(rule, index) {
                if (index < rule.actions.length - 1) {
                    const temp = rule.actions[index];
                    rule.actions[index] = rule.actions[index + 1];
                    rule.actions[index + 1] = temp;
                }
            },
            duplicateAction(rule, index) {
                const original = rule.actions[index];
                const duplicate = JSON.parse(JSON.stringify(original)); // Deep clone
                // Insert the duplicate right after the original
                rule.actions.splice(index + 1, 0, duplicate);
            },
            toggleSelectRule(ruleId) {
                const index = this.selectedRules.indexOf(ruleId);
                if (index > -1) {
                    this.selectedRules.splice(index, 1);
                } else {
                    this.selectedRules.push(ruleId);
                }
            },
            toggleSelectAll(checked) {
                if (checked) {
                    this.selectedRules = JSON.parse("@json($rules->pluck('id')->toArray())");
                } else {
                    this.selectedRules = [];
                }
            },
            async bulkEnable() {
                if (!confirm(`Enable ${this.selectedRules.length} automation(s)?`)) return;

                for (const ruleId of this.selectedRules) {
                    try {
                        await fetch(`/automation/${ruleId}`, {
                            method: 'PATCH',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify({
                                is_active: true
                            })
                        });
                    } catch (error) {
                        console.error('Error enabling automation:', error);
                    }
                }

                this.selectedRules = [];
                window.location.reload();
            },
            async bulkDisable() {
                if (!confirm(`Disable ${this.selectedRules.length} automation(s)?`)) return;

                for (const ruleId of this.selectedRules) {
                    try {
                        await fetch(`/automation/${ruleId}`, {
                            method: 'PATCH',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify({
                                is_active: false
                            })
                        });
                    } catch (error) {
                        console.error('Error disabling automation:', error);
                    }
                }

                this.selectedRules = [];
                window.location.reload();
            },
            async bulkDelete() {
                if (!confirm(`Delete ${this.selectedRules.length} automation(s)? This action cannot be undone.`))
                    return;

                for (const ruleId of this.selectedRules) {
                    try {
                        await fetch(`/automation/${ruleId}`, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            }
                        });
                    } catch (error) {
                        console.error('Error deleting automation:', error);
                    }
                }

                this.selectedRules = [];
                window.location.reload();
            },
            editRule(rule) {
                this.editingRule = {
                    id: rule.id,
                    name: rule.name,
                    trigger_event: rule.triggers && rule.triggers.length ? rule.triggers[0].event_name : '',
                    condition_logic: rule.condition_logic,
                    settings: rule.settings || {},
                    is_active: !!rule.is_active,
                    conditions: (rule.global_conditions || rule.globalConditions || []).map(c => ({
                        type: c.type,
                        field: c.field,
                        operator: c.operator,
                        value: c.value
                    })),
                    actions: (rule.steps || []).map(s => ({
                        type: s.action ? s.action.type : '',
                        delay: s.delay,
                        retry_limit: s.retry_limit || 0,
                        on_failure: s.on_failure || 'continue',
                        condition_logic: s.condition_logic,
                        conditions: (s.conditions || []).map(c => ({
                            type: c.type,
                            field: c.field,
                            operator: c.operator,
                            value: c.value
                        })),
                        config: s.action ? s.action.config : {}
                    }))
                };
                this.showEditModal = true;
            },
            viewLogs(ruleId) {
                this.showLogsModal = true;
                this.loadingLogs = true;
                fetch(`{{ url('automation') }}/${ruleId}/logs`)
                    .then(res => res.json())
                    .then(data => {
                        this.logs = data;
                        this.loadingLogs = false;
                    });
            },
            viewMetrics(ruleId) {
                this.showMetricsModal = true;
                this.loadingMetrics = true;
                fetch(`{{ url('automation') }}/${ruleId}/metrics`)
                    .then(res => res.json())
                    .then(data => {
                        this.metrics = data;
                        this.loadingMetrics = false;
                    });
            },
            viewVisualization(ruleId) {
                console.log('viewVisualization called for rule:', ruleId);
                this.showVisualizationModal = true;
                this.loadingVisualization = true;
                this.visualizationView = 'flowchart';
                this.isPreviewMode = false;

                // Fetch flowchart data
                console.log('Fetching flowchart...');
                fetch(`{{ url('automation') }}/${ruleId}/flowchart`)
                    .then(res => {
                        console.log('Fetch response status:', res.status);
                        return res.json();
                    })
                    .then(data => {
                        console.log('Flowchart data received:', data);
                        // Render Mermaid diagram
                        this.loadingVisualization = false;

                        // Render Mermaid diagram after DOM update
                        // Render Mermaid diagram after DOM update
                        const renderMermaid = (retries = 0) => {
                            if (retries > 10) {
                                console.error('Giving up finding mermaid div after 10 retries');
                                return;
                            }

                            const diagramDiv = document.getElementById('automation-mermaid-diagram');
                            console.log(`Attempt ${retries + 1}: diagramDiv found:`, !!diagramDiv);

                            if (diagramDiv) {
                                diagramDiv.innerHTML = data.mermaid;
                                diagramDiv.removeAttribute('data-processed');
                                if (window.mermaid) {
                                    try {
                                        mermaid.run({
                                            nodes: [diagramDiv]
                                        });
                                        console.log('Mermaid run complete.');
                                    } catch (e) {
                                        console.error('Mermaid render error:', e);
                                        diagramDiv.innerHTML = '<pre class="text-red-500 text-left text-xs">' + data.mermaid + '</pre>';
                                    }
                                }
                            } else {
                                // If not found, verify if it exists in the markup at all (debug only)
                                if (retries === 0) {
                                    const hasId = document.body.innerHTML.includes('automation-mermaid-diagram');
                                    console.log('Does ID string exist in document body?', hasId);
                                }
                                setTimeout(() => renderMermaid(retries + 1), 50);
                            }
                        };

                        this.$nextTick(() => {
                            console.log('Inside $nextTick');
                            renderMermaid();
                        });
                    })
                    .catch(err => {
                        console.error('Failed to load flowchart:', err);
                        this.loadingVisualization = false;
                    });

                // Fetch timeline data (independent)
                fetch(`{{ url('automation') }}/${ruleId}/timeline`)
                    .then(res => res.json())
                    .then(data => {
                        this.timelineData = data.timeline;
                    })
                    .catch(err => console.error('Failed to load timeline:', err));
            },

            async previewTemplate(template) {
                this.isPreviewMode = true;
                this.visualizationView = 'flowchart';
                this.showVisualizationModal = true;
                this.loadingVisualization = true;

                try {
                    const response = await fetch("{{ route('automation.preview') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                                'content')
                        },
                        body: JSON.stringify({
                            structure: template.structure
                        })
                    });

                    const data = await response.json();

                    // Render Mermaid
                    this.$nextTick(() => {
                        const diagramDiv = document.getElementById('mermaid-diagram');
                        if (diagramDiv) {
                            diagramDiv.innerHTML = data.mermaid;
                            diagramDiv.removeAttribute('data-processed');
                            if (window.mermaid) {
                                mermaid.init(undefined, diagramDiv);
                            }
                        }
                    });
                } catch (e) {
                    console.error('Preview failed:', e);
                    alert('Failed to load template preview.');
                } finally {
                    this.loadingVisualization = false;
                }
            },

            exportTemplate(template) {
                window.location.href = `/automation/templates/${template.id}/export`;
            },

            async handleImport(event) {
                const file = event.target.files[0];
                if (!file) return;

                const formData = new FormData();
                formData.append('file', file);

                this.loadingTemplates = true;
                try {
                    const response = await fetch("{{ route('automation.templates.import') }}", {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                                'content')
                        }
                    });

                    if (response.ok) {
                        alert('Template imported successfully!');
                        this.fetchTemplates();
                    } else {
                        const data = await response.json();
                        alert('Import failed: ' + (data.message || 'Unknown error'));
                    }
                } catch (e) {
                    console.error(e);
                    alert('Import error occurred.');
                } finally {
                    this.loadingTemplates = false;
                    event.target.value = '';
                }
            }
        }
    }
</script>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- Mermaid.js for flowchart rendering -->
<script src="https://cdn.jsdelivr.net/npm/mermaid@10/dist/mermaid.min.js"></script>
<script>
    mermaid.initialize({
        startOnLoad: false,
        theme: 'default'
    });
</script>
