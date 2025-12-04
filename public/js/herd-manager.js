function herdManager() {
    return {
        sites: [],
        proxies: [],
        localIpAddress: '',
        isSaving: false,
        statusMessage: '',
        statusMessageType: 'success',
        searchQuery: '',
        copiedUrl: null,
        showProxyForm: false,
        newProxy: { name: '', port: '' },
        isCreatingProxy: false,
        activeTab: 'sites',

        get filteredSites() {
            if (!this.searchQuery) {
                return this.sites;
            }

            const lowercaseQuery = this.searchQuery.toLowerCase();

            return this.sites.filter(site => {
                const nameMatches = site.name.toLowerCase().includes(lowercaseQuery);
                const urlMatches = site.url.toLowerCase().includes(lowercaseQuery);
                const pathMatches = site.path.toLowerCase().includes(lowercaseQuery);

                return nameMatches || urlMatches || pathMatches;
            });
        },

        async init() {
            await this.loadSites();
            await this.loadLocalIpAddress();
            await this.loadProxies();
            this.setupKeyboardShortcuts();
        },

        setupKeyboardShortcuts() {
            document.addEventListener('keydown', (event) => {
                const isTypingInInput = event.target.tagName === 'INPUT';

                if (isTypingInInput) {
                    return;
                }

                this.handleKeyboardShortcut(event);
            });
        },

        handleKeyboardShortcut(event) {
            if (event.key === '/') {
                event.preventDefault();
                this.activeTab = 'sites';
                setTimeout(() => this.$refs.searchInput?.focus(), 100);
                return;
            }

            if (event.key === '1') {
                this.activeTab = 'sites';
                return;
            }

            if (event.key === '2') {
                this.activeTab = 'proxies';
            }
        },

        async loadSites() {
            const response = await fetch('/api/sites');
            const data = await response.json();
            this.sites = data.sites;
        },

        async loadLocalIpAddress() {
            const response = await fetch('/api/sites/ip');
            const data = await response.json();
            this.localIpAddress = data.ip;
        },

        async loadProxies() {
            const response = await fetch('/api/proxies');
            const data = await response.json();
            this.proxies = data.proxies;
        },

        async createProxy() {
            this.isCreatingProxy = true;

            try {
                await this.sendCreateProxyRequest();
            } catch (error) {
                this.showErrorMessage('Error creating proxy: ' + error.message);
            } finally {
                this.isCreatingProxy = false;
            }
        },

        async sendCreateProxyRequest() {
            const response = await fetch('/api/proxies', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(this.newProxy)
            });

            const data = await response.json();

            if (!data.success) {
                this.showErrorMessage('Error: ' + data.error);
                return;
            }

            this.handleProxyCreated(data.proxy);
        },

        handleProxyCreated(proxy) {
            this.proxies.push(proxy);
            this.newProxy = { name: '', port: '' };
            this.showProxyForm = false;
            this.showSuccessMessage('Proxy created successfully!');
        },

        async deleteProxy(proxyName) {
            const userConfirmed = confirm(`Delete proxy "${proxyName}.test"?`);

            if (!userConfirmed) {
                return;
            }

            try {
                await this.sendDeleteProxyRequest(proxyName);
            } catch (error) {
                this.showErrorMessage('Error deleting proxy: ' + error.message);
            }
        },

        async sendDeleteProxyRequest(proxyName) {
            const response = await fetch(`/api/proxies/${proxyName}`, {
                method: 'DELETE'
            });

            const data = await response.json();

            if (!data.success) {
                this.showErrorMessage('Error: ' + data.error);
                return;
            }

            this.handleProxyDeleted(proxyName);
        },

        handleProxyDeleted(proxyName) {
            this.proxies = this.proxies.filter(proxy => proxy.name !== proxyName);
            this.showSuccessMessage('Proxy deleted successfully!');
        },

        async toggleSite(site) {
            site.exposed = !site.exposed;

            if (!site.exposed) {
                site.portInUse = false;
                return;
            }

            this.handleSiteExposed(site);
        },

        async handleSiteExposed(site) {
            site.port = this.getNextFreePort();
            await this.checkPortAvailability(site);

            setTimeout(() => {
                this.focusPortInput(site);
            }, 100);
        },

        focusPortInput(site) {
            const portInputs = document.querySelectorAll('input[type="number"]');
            const siteIndex = this.filteredSites.findIndex(filteredSite => filteredSite.name === site.name);
            const portInput = portInputs[siteIndex];

            if (!portInput) {
                return;
            }

            portInput.focus();
            portInput.select();
        },

        getNextFreePort() {
            const usedPorts = this.sites
                .filter(site => site.exposed)
                .map(site => site.port)
                .sort((portA, portB) => portA - portB);

            let nextPort = 8000;

            while (usedPorts.includes(nextPort)) {
                nextPort++;
            }

            return nextPort;
        },

        async checkPortAvailability(site) {
            if (!site.exposed || !site.port) {
                site.portInUse = false;
                return;
            }

            try {
                const response = await fetch(`/api/sites/check-port?port=${site.port}`);
                const data = await response.json();
                site.portInUse = !data.available;
            } catch (error) {
                console.error('Error checking port availability:', error);
                site.portInUse = false;
            }
        },

        copyUrl(url) {
            navigator.clipboard.writeText(url);
            this.copiedUrl = url;
            setTimeout(() => this.copiedUrl = null, 2000);
        },

        async applyChanges() {
            if (!this.validatePortsBeforeApply()) {
                return;
            }

            this.isSaving = true;
            this.showInfoMessage('Applying changes and restarting nginx...');

            try {
                await this.sendApplyChangesRequest();
            } catch (error) {
                this.showErrorMessage('Error applying settings: ' + error.message);
                this.isSaving = false;
            }
        },

        validatePortsBeforeApply() {
            const hasPortConflicts = this.hasPortConflicts();

            if (hasPortConflicts) {
                this.showErrorMessage('Some ports are in use by the system. Choose different ports.');
                return false;
            }

            const hasDuplicatePorts = this.hasDuplicatePorts();

            if (hasDuplicatePorts) {
                this.showErrorMessage('Multiple sites are using the same port. Choose different ports.');
                return false;
            }

            return true;
        },

        hasPortConflicts() {
            const sitesWithConflicts = this.sites.filter(site => site.exposed && site.portInUse);
            return sitesWithConflicts.length > 0;
        },

        hasDuplicatePorts() {
            const exposedPorts = this.sites
                .filter(site => site.exposed)
                .map(site => site.port);

            const duplicatePorts = exposedPorts.filter((port, index, allPorts) => {
                const firstOccurrence = allPorts.indexOf(port);
                return firstOccurrence !== index;
            });

            return duplicatePorts.length > 0;
        },

        async sendApplyChangesRequest() {
            const exposedSites = this.sites.filter(site => site.exposed);

            const response = await fetch('/api/sites/apply', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    sites: exposedSites
                })
            });

            const data = await response.json();

            if (!data.success) {
                this.showErrorMessage('Error: ' + data.error);
                this.isSaving = false;
                return;
            }

            this.handleApplySuccess();
        },

        handleApplySuccess() {
            this.showSuccessMessage('Settings applied successfully!');
            this.isSaving = false;
        },

        showSuccessMessage(message) {
            this.statusMessage = message;
            this.statusMessageType = 'success';
            setTimeout(() => this.statusMessage = '', 5000);
        },

        showErrorMessage(message) {
            this.statusMessage = message;
            this.statusMessageType = 'error';
            setTimeout(() => this.statusMessage = '', 5000);
        },

        showInfoMessage(message) {
            this.statusMessage = message;
            this.statusMessageType = 'info';
        }
    };
}
