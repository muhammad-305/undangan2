// Admin-specific JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Modal management
    window.openModal = function(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
    };

    window.closeModal = function(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('active');
            document.body.style.overflow = 'auto';
        }
    };

    // Close modal on outside click
    document.querySelectorAll('.modal').forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeModal(modal.id);
            }
        });
    });

    // Close modal on escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal.active').forEach(modal => {
                closeModal(modal.id);
            });
        }
    });

    // Image preview
    window.previewImage = function(input, previewId) {
        const file = input.files[0];
        const preview = document.getElementById(previewId);
        
        if (file && preview) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.style.display = 'block';
            };
            reader.readAsDataURL(file);
        }
    };

    // Auto-generate slug
    window.generateSlug = function(text, targetId) {
        const slug = text.toLowerCase()
            .replace(/[^a-z0-9\s-]/g, '')
            .replace(/\s+/g, '-')
            .replace(/-+/g, '-')
            .trim();
        
        const target = document.getElementById(targetId);
        if (target) {
            target.value = slug;
        }
        
        return slug;
    };

    // Auto-generate slug from nama inputs
    const namaPriaInput = document.getElementById('nama_pria');
    const namaWanitaInput = document.getElementById('nama_wanita');
    const slugInput = document.getElementById('slug');

    if (namaPriaInput && namaWanitaInput && slugInput) {
        function updateSlug() {
            const pria = namaPriaInput.value.trim();
            const wanita = namaWanitaInput.value.trim();
            if (pria && wanita) {
                generateSlug(`${wanita}-${pria}`, 'slug');
            }
        }

        namaPriaInput.addEventListener('blur', updateSlug);
        namaWanitaInput.addEventListener('blur', updateSlug);
    }

    // Table search
    window.searchTable = function(inputId, tableId) {
        const input = document.getElementById(inputId);
        const table = document.getElementById(tableId);
        
        if (!input || !table) return;

        const filter = input.value.toLowerCase();
        const rows = table.querySelectorAll('tbody tr');

        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(filter) ? '' : 'none';
        });
    };

    // Sortable table headers
    document.querySelectorAll('th[data-sort]').forEach(header => {
        header.style.cursor = 'pointer';
        header.addEventListener('click', function() {
            const table = this.closest('table');
            const tbody = table.querySelector('tbody');
            const rows = Array.from(tbody.querySelectorAll('tr'));
            const column = Array.from(this.parentElement.children).indexOf(this);
            const currentOrder = this.dataset.order || 'asc';
            const newOrder = currentOrder === 'asc' ? 'desc' : 'asc';

            rows.sort((a, b) => {
                const aVal = a.children[column].textContent.trim();
                const bVal = b.children[column].textContent.trim();
                
                if (newOrder === 'asc') {
                    return aVal.localeCompare(bVal, undefined, { numeric: true });
                } else {
                    return bVal.localeCompare(aVal, undefined, { numeric: true });
                }
            });

            rows.forEach(row => tbody.appendChild(row));
            this.dataset.order = newOrder;
            
            // Update sort indicator
            table.querySelectorAll('th').forEach(th => {
                th.classList.remove('sort-asc', 'sort-desc');
            });
            this.classList.add(`sort-${newOrder}`);
        });
    });

    // Bulk actions
    window.toggleSelectAll = function(checkbox, className) {
        const checkboxes = document.querySelectorAll(`.${className}`);
        checkboxes.forEach(cb => {
            cb.checked = checkbox.checked;
        });
    };

    window.getSelectedIds = function(className) {
        const checkboxes = document.querySelectorAll(`.${className}:checked`);
        return Array.from(checkboxes).map(cb => cb.value);
    };

    // Character counter
    document.querySelectorAll('[data-maxlength]').forEach(input => {
        const maxLength = input.getAttribute('data-maxlength');
        const counter = document.createElement('div');
        counter.className = 'char-counter';
        counter.style.cssText = 'text-align: right; font-size: 12px; color: #666; margin-top: 5px;';
        input.parentElement.appendChild(counter);

        function updateCounter() {
            const length = input.value.length;
            counter.textContent = `${length} / ${maxLength}`;
            
            if (length > maxLength * 0.9) {
                counter.style.color = '#dc3545';
            } else {
                counter.style.color = '#666';
            }
        }

        input.addEventListener('input', updateCounter);
        updateCounter();
    });

    // Drag and drop file upload
    document.querySelectorAll('[data-dropzone]').forEach(dropzone => {
        const input = dropzone.querySelector('input[type="file"]');
        
        if (!input) return;

        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropzone.addEventListener(eventName, function(e) {
                e.preventDefault();
                e.stopPropagation();
            });
        });

        ['dragenter', 'dragover'].forEach(eventName => {
            dropzone.addEventListener(eventName, function() {
                dropzone.classList.add('drag-over');
            });
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropzone.addEventListener(eventName, function() {
                dropzone.classList.remove('drag-over');
            });
        });

        dropzone.addEventListener('drop', function(e) {
            const files = e.dataTransfer.files;
            input.files = files;
            
            if (input.getAttribute('data-preview')) {
                previewImage(input, input.getAttribute('data-preview'));
            }
        });
    });

    // Wizard navigation
    window.nextWizardStep = function(currentStep) {
        const current = document.querySelector(`.wizard-step[data-step="${currentStep}"]`);
        const next = document.querySelector(`.wizard-step[data-step="${currentStep + 1}"]`);
        
        if (current && next) {
            current.classList.remove('active');
            current.classList.add('completed');
            next.classList.add('active');
            
            document.querySelector(`.wizard-content[data-step="${currentStep}"]`).style.display = 'none';
            document.querySelector(`.wizard-content[data-step="${currentStep + 1}"]`).style.display = 'block';
        }
    };

    window.prevWizardStep = function(currentStep) {
        const current = document.querySelector(`.wizard-step[data-step="${currentStep}"]`);
        const prev = document.querySelector(`.wizard-step[data-step="${currentStep - 1}"]`);
        
        if (current && prev) {
            current.classList.remove('active');
            prev.classList.remove('completed');
            prev.classList.add('active');
            
            document.querySelector(`.wizard-content[data-step="${currentStep}"]`).style.display = 'none';
            document.querySelector(`.wizard-content[data-step="${currentStep - 1}"]`).style.display = 'block';
        }
    };
});

// Add admin-specific styles
if (!document.querySelector('style[data-admin-js-styles]')) {
    const style = document.createElement('style');
    style.setAttribute('data-admin-js-styles', 'true');
    style.textContent = `
        .drag-over {
            border-color: #D4AF37 !important;
            background: rgba(212, 175, 55, 0.1) !important;
        }
        th.sort-asc::after {
            content: ' ↑';
            color: #D4AF37;
        }
        th.sort-desc::after {
            content: ' ↓';
            color: #D4AF37;
        }
    `;
    document.head.appendChild(style);
}
