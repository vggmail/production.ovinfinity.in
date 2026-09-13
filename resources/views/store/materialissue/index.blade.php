@extends('layouts.app')

@section('title', 'Material Issue')

@section('content')
<div class="content-header">
    <div class="content-title">
        <h1>Material Issue List</h1>
        <p>Manage store material issues and item distribution to departments & machines</p>
    </div>
    <a href="{{ route('store.materialissue.create') }}" class="btn-circle-add" title="Add New Material Issue">
        +
    </a>
</div>

<div class="card datatable-wrapper">
    <div class="datatable-controls">
        <div class="datatable-length">
            <span>Show</span>
            <select id="dt-length">
                <option value="50">50</option>
                <option value="100">100</option>
                <option value="250">250</option>
                <option value="500">500</option>
            </select>
            <span>entries</span>
        </div>
        <div class="datatable-search">
            <input type="text" id="dt-search" placeholder="Search by issue no, date, technician or remarks...">
        </div>
    </div>

    <div class="table-container">
        <table class="datatable" id="materialissue-table">
            <thead>
                <tr>
                    <th data-column="IssueNo" style="width: 140px;">Issue No</th>
                    <th data-column="IssueDate" style="width: 110px;">Issue Date</th>
                    <th style="min-width: 160px;">Technician / Person</th>
                    <th data-column="TotalItems" style="width: 100px;">Total Items</th>
                    <th data-column="TotalQuantity" style="width: 120px;">Total Qty</th>
                    <th style="min-width: 180px;">Remarks</th>
                    <th style="width: 140px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <!-- Rows loaded via AJAX -->
            </tbody>
        </table>
    </div>

    <div class="datatable-footer">
        <div class="datatable-info">Showing 0 to 0 of 0 entries</div>
        <div class="pagination-controls"></div>
    </div>
</div>
@endsection

@section('scripts')
<script src="{{ asset('js/datatable.js') }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        function formatDate(val) {
            if (!val) return '';
            const d = new Date(val);
            if (isNaN(d.getTime())) return val;
            const day = String(d.getDate()).padStart(2, '0');
            const month = String(d.getMonth() + 1).padStart(2, '0');
            const year = d.getFullYear();
            return `${day}/${month}/${year}`;
        }

        const table = new DynamicDataTable('materialissue-table', {
            url: "{{ route('store.materialissue.data') }}",
            defaultSortCol: 'ID',
            defaultSortDir: 'desc',
            columns: [
                { 
                    name: 'IssueNo', 
                    sortable: true,
                    render: (val, row) => {
                        const editUrl = "{{ route('store.materialissue.edit', ':id') }}".replace(':id', row.ID);
                        return `<a href="${editUrl}" class="table-id-link" title="Click to edit">${val || row.ID}</a>`;
                    }
                },
                { 
                    name: 'IssueDate', 
                    sortable: true,
                    render: (val) => formatDate(val)
                },
                { 
                    name: 'Technician', 
                    sortable: false,
                    render: (val, row) => row.technician_relation ? row.technician_relation.Name : '<span class="text-muted">-</span>'
                },
                { name: 'TotalItems', sortable: true },
                { name: 'TotalQuantity', sortable: true },
                { 
                    name: 'Remarks', 
                    sortable: false,
                    render: (val) => val ? val : '<span class="text-muted">-</span>'
                }
            ],
            actions: (row) => {
                const editUrl = "{{ route('store.materialissue.edit', ':id') }}".replace(':id', row.ID);
                const printUrl = "{{ route('store.materialissue.print', ':id') }}".replace(':id', row.ID);
                return `
                    <a href="${editUrl}" class="datatable-action-btn btn-edit" title="Edit">✏️</a>
                    <!--<a href="${printUrl}" target="_blank" class="datatable-action-btn" title="Print" style="margin-left: 4px;">🖨️</a>-->
                    <span style="opacity: 0.3; margin: 0 0.25rem;">|</span>
                    <button class="datatable-action-btn btn-delete" onclick="deleteRecord(${row.ID})" title="Delete">🗑️</button>
                `;
            }
        });

        window.deleteRecord = (id) => {
            if (confirm('Are you sure you want to delete this Material Issue record?')) {
                fetch("{{ route('store.materialissue.destroy', ':id') }}".replace(':id', id), {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(res => res.json())
                .then(response => {
                    if (response.success) {
                        table.fetch();
                    } else {
                        alert(response.message || 'Failed to delete the record.');
                    }
                })
                .catch(err => {
                    console.error('Delete error:', err);
                    alert('An error occurred while deleting.');
                });
            }
        };
    });
</script>
@endsection
