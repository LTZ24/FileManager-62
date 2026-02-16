/**
 * Universal Table Pagination System
 * Supports: filtering, sorting, rows per page selection
 */

class TablePagination {
    constructor(tableId, options = {}) {
        this.table = document.getElementById(tableId);
        if (!this.table) {
            console.error(`Table with ID "${tableId}" not found`);
            return;
        }
        
        this.tbody = this.table.querySelector('tbody');
        this.currentPage = 1;
        this.rowsPerPage = options.rowsPerPage || 10;
        this.allRows = Array.from(this.tbody.querySelectorAll('tr'));
        this.filteredRows = [...this.allRows];
        
        // Options
        this.paginationContainerId = options.paginationContainerId || `${tableId}-pagination`;
        this.rowsSelectId = options.rowsSelectId || `${tableId}-rows-select`;
        this.showRowsSelect = options.showRowsSelect !== false;
        this.rowsPerPageOptions = options.rowsPerPageOptions || [10, 25, 50, 100];
        
        this.init();
    }
    
    init() {
        // Create pagination controls
        this.createPaginationControls();
        
        // Initial render
        this.render();
        
        // Setup rows per page selector if enabled
        if (this.showRowsSelect) {
            this.setupRowsSelect();
        }
    }
    
    createPaginationControls() {
        // Check if pagination container already exists
        let container = document.getElementById(this.paginationContainerId);
        
        if (!container) {
            // Create pagination container after table
            container = document.createElement('div');
            container.id = this.paginationContainerId;
            container.className = 'pagination-container';
            
            // Insert after table or its wrapper
            const tableWrapper = this.table.closest('.table-wrapper') || this.table.parentElement;
            tableWrapper.insertAdjacentElement('afterend', container);
        }
        
        this.paginationContainer = container;
        
        // Create rows per page selector container before table
        if (this.showRowsSelect) {
            let selectContainer = document.getElementById(`${this.paginationContainerId}-top`);
            
            if (!selectContainer) {
                selectContainer = document.createElement('div');
                selectContainer.id = `${this.paginationContainerId}-top`;
                selectContainer.className = 'pagination-top-controls';
                
                // Insert before table
                const tableWrapper = this.table.closest('.table-wrapper') || this.table.parentElement;
                tableWrapper.insertAdjacentElement('beforebegin', selectContainer);
            }
            
            this.topControlsContainer = selectContainer;
        }
    }
    
    setupRowsSelect() {
        // Rows per page will be rendered inside pagination container (bottom left)
        // Remove top controls container
        if (this.topControlsContainer) {
            this.topControlsContainer.remove();
            this.topControlsContainer = null;
        }
    }
    
    getTotalPages() {
        return Math.ceil(this.filteredRows.length / this.rowsPerPage);
    }
    
    render() {
        const totalPages = this.getTotalPages();
        const start = (this.currentPage - 1) * this.rowsPerPage;
        const end = start + this.rowsPerPage;
        
        // Hide all rows
        this.allRows.forEach(row => row.style.display = 'none');
        
        // Show only current page rows
        this.filteredRows.slice(start, end).forEach(row => {
            row.style.display = '';
        });
        
        // Update pagination controls
        this.renderPaginationButtons(totalPages);
        
        // Show empty state if no rows
        this.checkEmptyState();
    }
    
    renderPaginationButtons(totalPages) {
        const start = (this.currentPage - 1) * this.rowsPerPage;
        const end = start + this.rowsPerPage;
        const showing = Math.min(end, this.filteredRows.length);
        
        if (totalPages <= 1 && this.filteredRows.length <= this.rowsPerPage) {
            this.paginationContainer.innerHTML = '';
            return;
        }
        
        // Left side: Rows per page selector
        let leftHtml = `
            <div class="rows-per-page">
                <label for="${this.rowsSelectId}">Tampilkan:</label>
                <select id="${this.rowsSelectId}" class="rows-select">
                    ${this.rowsPerPageOptions.map(value => 
                        `<option value="${value}" ${value === this.rowsPerPage ? 'selected' : ''}>${value}</option>`
                    ).join('')}
                    <option value="all">Semua</option>
                </select>
                <span>baris per halaman (${start + 1}-${showing} dari ${this.filteredRows.length})</span>
            </div>
        `;
        
        // Right side: Page navigation buttons
        let rightHtml = '<div class="pagination-buttons">';
        
        // Previous button
        rightHtml += `
            <button class="pagination-btn ${this.currentPage === 1 ? 'disabled' : ''}" 
                    onclick="window.tablePaginationInstances['${this.table.id}'].goToPage(${this.currentPage - 1})"
                    ${this.currentPage === 1 ? 'disabled' : ''}
                    title="Halaman sebelumnya">
                <i class="fas fa-chevron-left"></i>
            </button>
        `;
        
        // Page numbers
        const maxButtons = 5;
        let startPage = Math.max(1, this.currentPage - Math.floor(maxButtons / 2));
        let endPage = Math.min(totalPages, startPage + maxButtons - 1);
        
        if (endPage - startPage < maxButtons - 1) {
            startPage = Math.max(1, endPage - maxButtons + 1);
        }
        
        // First page
        if (startPage > 1) {
            rightHtml += `
                <button class="pagination-btn" onclick="window.tablePaginationInstances['${this.table.id}'].goToPage(1)" title="Halaman 1">
                    1
                </button>
            `;
            if (startPage > 2) {
                rightHtml += '<span class="pagination-ellipsis">...</span>';
            }
        }
        
        // Page buttons
        for (let i = startPage; i <= endPage; i++) {
            rightHtml += `
                <button class="pagination-btn ${i === this.currentPage ? 'active' : ''}" 
                        onclick="window.tablePaginationInstances['${this.table.id}'].goToPage(${i})"
                        title="Halaman ${i}">
                    ${i}
                </button>
            `;
        }
        
        // Last page
        if (endPage < totalPages) {
            if (endPage < totalPages - 1) {
                rightHtml += '<span class="pagination-ellipsis">...</span>';
            }
            rightHtml += `
                <button class="pagination-btn" onclick="window.tablePaginationInstances['${this.table.id}'].goToPage(${totalPages})" title="Halaman ${totalPages}">
                    ${totalPages}
                </button>
            `;
        }
        
        // Next button
        rightHtml += `
            <button class="pagination-btn ${this.currentPage === totalPages ? 'disabled' : ''}" 
                    onclick="window.tablePaginationInstances['${this.table.id}'].goToPage(${this.currentPage + 1})"
                    ${this.currentPage === totalPages ? 'disabled' : ''}
                    title="Halaman berikutnya">
                <i class="fas fa-chevron-right"></i>
            </button>
        `;
        
        rightHtml += '</div>';
        
        // Combine left and right
        this.paginationContainer.innerHTML = leftHtml + rightHtml;
        
        // Add event listener for rows select
        const select = document.getElementById(this.rowsSelectId);
        if (select) {
            select.addEventListener('change', (e) => {
                this.rowsPerPage = e.target.value === 'all' ? this.filteredRows.length : parseInt(e.target.value);
                this.currentPage = 1;
                this.render();
            });
        }
    }
    
    goToPage(page) {
        const totalPages = this.getTotalPages();
        if (page < 1 || page > totalPages) return;
        
        this.currentPage = page;
        this.render();
        
        // Scroll to top of table
        this.table.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
    
    updateFilteredRows(filterFunction) {
        // Apply filter to all rows
        this.filteredRows = this.allRows.filter(filterFunction);
        
        // Reset to first page
        this.currentPage = 1;
        
        // Re-render
        this.render();
    }
    
    refresh() {
        // Refresh row list (useful after dynamic row additions/deletions)
        this.allRows = Array.from(this.tbody.querySelectorAll('tr'));
        this.filteredRows = [...this.allRows];
        this.currentPage = 1;
        this.render();
    }
    
    checkEmptyState() {
        // Find or create empty state element
        let emptyState = this.table.parentElement.querySelector('.empty-state');
        
        if (this.filteredRows.length === 0) {
            if (!emptyState) {
                emptyState = document.createElement('div');
                emptyState.className = 'empty-state';
                emptyState.innerHTML = `
                    <i class="fas fa-inbox"></i>
                    <p>Tidak ada data yang ditemukan</p>
                `;
                this.table.parentElement.appendChild(emptyState);
            }
            emptyState.style.display = 'block';
            this.table.style.display = 'none';
            this.paginationContainer.style.display = 'none';
        } else {
            if (emptyState) {
                emptyState.style.display = 'none';
            }
            this.table.style.display = '';
            this.paginationContainer.style.display = '';
        }
    }
}

// Global storage for pagination instances
window.tablePaginationInstances = window.tablePaginationInstances || {};

// Helper function to initialize pagination
function initTablePagination(tableId, options = {}) {
    // Check if table exists before creating instance
    if (!document.getElementById(tableId)) {
        return null;
    }
    const instance = new TablePagination(tableId, options);
    window.tablePaginationInstances[tableId] = instance;
    return instance;
}
