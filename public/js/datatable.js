class DynamicDataTable {
    constructor(elementId, config) {
        this.table = document.getElementById(elementId);
        this.tbody = this.table.querySelector('tbody');
        this.thead = this.table.querySelector('thead');
        this.config = {
            url: config.url,
            columns: config.columns, // Array of { name: 'colName', sortable: true, render: fn }
            actions: config.actions || null, // fn(row) returning html for edit/delete buttons
            onEdit: config.onEdit || null,
            onDelete: config.onDelete || null,
            perPage: config.perPage || 10,
        };

        this.state = {
            page: 1,
            search: '',
            sortCol: config.defaultSortCol || 'ID',
            sortDir: config.defaultSortDir || 'desc',
            perPage: this.config.perPage,
        };

        this.initDOM();
        this.initEvents();
        this.fetch();
    }

    initDOM() {
        // Find or create control elements
        this.wrapper = this.table.closest('.datatable-wrapper');
        this.searchInput = this.wrapper.querySelector('.datatable-search input');
        this.perPageSelect = this.wrapper.querySelector('.datatable-length select');
        this.infoText = this.wrapper.querySelector('.datatable-info');
        this.paginationContainer = this.wrapper.querySelector('.pagination-controls');

        // Setup sort indicators on headers
        this.headers = this.thead.querySelectorAll('th[data-column]');
        this.headers.forEach(th => {
            const colName = th.getAttribute('data-column');
            const colConfig = this.config.columns.find(c => c.name === colName);
            if (colConfig && colConfig.sortable) {
                th.classList.add('sortable');
                if (colName === this.state.sortCol) {
                    th.classList.add(this.state.sortDir === 'asc' ? 'sort-asc' : 'sort-desc');
                }
            }
        });
    }

    initEvents() {
        // Search Input with Debounce
        let debounceTimeout;
        if (this.searchInput) {
            this.searchInput.addEventListener('input', (e) => {
                clearTimeout(debounceTimeout);
                debounceTimeout = setTimeout(() => {
                    this.state.search = e.target.value;
                    this.state.page = 1;
                    this.fetch();
                }, 300);
            });
        }

        // Per page Select
        if (this.perPageSelect) {
            this.perPageSelect.addEventListener('change', (e) => {
                this.state.perPage = parseInt(e.target.value, 10);
                this.state.page = 1;
                this.fetch();
            });
        }

        // Column Sorting Click
        this.headers.forEach(th => {
            th.addEventListener('click', () => {
                const colName = th.getAttribute('data-column');
                const colConfig = this.config.columns.find(c => c.name === colName);
                if (!colConfig || !colConfig.sortable) return;

                if (this.state.sortCol === colName) {
                    this.state.sortDir = this.state.sortDir === 'asc' ? 'desc' : 'asc';
                } else {
                    this.state.sortCol = colName;
                    this.state.sortDir = 'asc';
                }

                // Update classes on headers
                this.headers.forEach(h => h.classList.remove('sort-asc', 'sort-desc'));
                th.classList.add(this.state.sortDir === 'asc' ? 'sort-asc' : 'sort-desc');

                this.state.page = 1;
                this.fetch();
            });
        });
    }

    fetch() {
        this.tbody.innerHTML = '<tr><td colspan="100" style="text-align: center; padding: 0.75rem 1rem;">Loading data...</td></tr>';
        
        const params = new URLSearchParams({
            page: this.state.page,
            search: this.state.search,
            sort_col: this.state.sortCol,
            sort_dir: this.state.sortDir,
            per_page: this.state.perPage
        });

        fetch(`${this.config.url}?${params.toString()}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(res => res.json())
        .then(response => {
            this.render(response);
        })
        .catch(err => {
            console.error('DataTable fetch error:', err);
            this.tbody.innerHTML = '<tr><td colspan="100" style="text-align: center; color: var(--error); padding: 0.75rem 1rem;">Failed to load data. Please refresh.</td></tr>';
        });
    }

    render(response) {
        const { data, current_page, last_page, total, from, to } = response;
        this.tbody.innerHTML = '';

        if (data.length === 0) {
            this.tbody.innerHTML = '<tr><td colspan="100" style="text-align: center; padding: 0.75rem 1rem;">No matching records found.</td></tr>';
            if (this.infoText) this.infoText.textContent = 'Showing 0 to 0 of 0 entries';
            if (this.paginationContainer) this.paginationContainer.innerHTML = '';
            return;
        }

        data.forEach(row => {
            const tr = document.createElement('tr');
            
            this.config.columns.forEach(col => {
                const td = document.createElement('td');
                if (col.render) {
                    td.innerHTML = col.render(row[col.name], row);
                } else {
                    td.textContent = row[col.name] !== null ? row[col.name] : '';
                }
                tr.appendChild(td);
            });

            // Action Buttons
            if (this.config.actions) {
                const td = document.createElement('td');
                td.innerHTML = this.config.actions(row);
                tr.appendChild(td);
            }

            this.tbody.appendChild(tr);
        });

        // Update Info Text
        if (this.infoText) {
            this.infoText.textContent = `Showing ${from} to ${to} of ${total} entries`;
        }

        // Render Pagination Controls
        this.renderPagination(current_page, last_page);
    }

    renderPagination(currentPage, lastPage) {
        if (!this.paginationContainer) return;
        this.paginationContainer.innerHTML = '';

        // Previous button
        const prevBtn = document.createElement('button');
        prevBtn.className = 'page-btn';
        prevBtn.innerHTML = '&laquo;';
        prevBtn.disabled = currentPage === 1;
        prevBtn.addEventListener('click', () => {
            if (currentPage > 1) {
                this.state.page = currentPage - 1;
                this.fetch();
            }
        });
        this.paginationContainer.appendChild(prevBtn);

        // Page numbers
        let startPage = Math.max(1, currentPage - 2);
        let endPage = Math.min(lastPage, currentPage + 2);

        if (startPage > 1) {
            const firstPageBtn = document.createElement('button');
            firstPageBtn.className = 'page-btn';
            firstPageBtn.textContent = '1';
            firstPageBtn.addEventListener('click', () => {
                this.state.page = 1;
                this.fetch();
            });
            this.paginationContainer.appendChild(firstPageBtn);

            if (startPage > 2) {
                const dots = document.createElement('span');
                dots.textContent = '...';
                dots.style.alignSelf = 'center';
                dots.style.margin = '0 0.5rem';
                this.paginationContainer.appendChild(dots);
            }
        }

        for (let i = startPage; i <= endPage; i++) {
            const pageBtn = document.createElement('button');
            pageBtn.className = `page-btn ${i === currentPage ? 'active' : ''}`;
            pageBtn.textContent = i;
            pageBtn.addEventListener('click', () => {
                this.state.page = i;
                this.fetch();
            });
            this.paginationContainer.appendChild(pageBtn);
        }

        if (endPage < lastPage) {
            if (endPage < lastPage - 1) {
                const dots = document.createElement('span');
                dots.textContent = '...';
                dots.style.alignSelf = 'center';
                dots.style.margin = '0 0.5rem';
                this.paginationContainer.appendChild(dots);
            }

            const lastPageBtn = document.createElement('button');
            lastPageBtn.className = 'page-btn';
            lastPageBtn.textContent = lastPage;
            lastPageBtn.addEventListener('click', () => {
                this.state.page = lastPage;
                this.fetch();
            });
            this.paginationContainer.appendChild(lastPageBtn);
        }

        // Next button
        const nextBtn = document.createElement('button');
        nextBtn.className = 'page-btn';
        nextBtn.innerHTML = '&raquo;';
        nextBtn.disabled = currentPage === lastPage;
        nextBtn.addEventListener('click', () => {
            if (currentPage < lastPage) {
                this.state.page = currentPage + 1;
                this.fetch();
            }
        });
        this.paginationContainer.appendChild(nextBtn);
    }
}
