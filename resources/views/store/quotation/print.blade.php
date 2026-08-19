<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quotation_{{ $quotation->QuotationNumber }}</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
        }
        body {
            background-color: #f8fafc;
            color: #1e293b;
            padding: 20px;
        }
        .page-container {
            max-width: 800px;
            margin: 0 auto;
            background: #ffffff;
            padding: 30px 40px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            border: 1px solid #e2e8f0;
        }
        .no-print-bar {
            max-width: 800px;
            margin: 0 auto 20px auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 18px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            border: none;
            transition: opacity 0.2s;
        }
        .btn-primary {
            background-color: #1e40af;
            color: #ffffff;
        }
        .btn-primary:hover {
            background-color: #1e3a8a;
        }
        .btn-secondary {
            background-color: #64748b;
            color: #ffffff;
        }

        /* Document Header */
        .company-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .company-title {
            font-size: 24px;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        .company-subtitle {
            font-size: 12px;
            color: #64748b;
            font-weight: 600;
            margin-top: 2px;
        }
        .doc-label {
            font-size: 20px;
            font-weight: 700;
            color: #2563eb;
            text-align: right;
        }

        /* Info Grids */
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 25px;
        }
        .info-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 12px 16px;
        }
        .info-card-title {
            font-size: 11px;
            text-transform: uppercase;
            font-weight: 700;
            color: #64748b;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
        }
        .info-row {
            font-size: 13px;
            margin-bottom: 4px;
            display: flex;
        }
        .info-label {
            width: 110px;
            color: #64748b;
            font-weight: 500;
        }
        .info-value {
            font-weight: 600;
            color: #0f172a;
            flex: 1;
        }

        /* Table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .items-table th {
            background-color: #f1f5f9;
            color: #1e293b;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            padding: 10px 12px;
            text-align: left;
            border-top: 1px solid #cbd5e1;
            border-bottom: 2px solid #cbd5e1;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .items-table td {
            padding: 10px 12px;
            font-size: 13px;
            border-bottom: 1px solid #e2e8f0;
            color: #334155;
        }
        .items-table tr:nth-child(even) {
            background-color: #f8fafc;
        }

        .totals-row td {
            font-weight: 700;
            background-color: #f1f5f9;
            border-top: 2px solid #cbd5e1;
            font-size: 14px;
            color: #0f172a;
        }

        /* Terms & Signatures */
        .remarks-box {
            background-color: #fffbebf5;
            border: 1px solid #fde68a;
            border-radius: 6px;
            padding: 12px 16px;
            margin-bottom: 30px;
            font-size: 12px;
            color: #92400e;
        }
        .signatures {
            display: flex;
            justify-content: space-between;
            margin-top: 50px;
            padding-top: 10px;
        }
        .signature-box {
            text-align: center;
            width: 200px;
        }
        .signature-line {
            border-top: 1px solid #94a3b8;
            margin-bottom: 6px;
        }
        .signature-title {
            font-size: 12px;
            font-weight: 600;
            color: #475569;
        }

        /* Print Media Styles */
        @media print {
            body {
                background: #ffffff;
                padding: 0;
            }
            .no-print-bar {
                display: none !important;
            }
            .page-container {
                box-shadow: none;
                border: none;
                padding: 0;
                max-width: 100%;
            }
            @page {
                size: A4 portrait;
                margin: 15mm;
            }
        }
    </style>
</head>
<body>

    <!-- Non-printable Header Actions -->
    <div class="no-print-bar">
        <a href="{{ route('store.quotation.index') }}" class="btn btn-secondary">&larr; Back to List</a>
        <button onclick="window.print();" class="btn btn-primary">
            <span>🖨️</span> <span>Print / Save as PDF</span>
        </button>
    </div>

    <!-- Printable Quotation Page -->
    <div class="page-container">
        <!-- Company Header -->
        <div class="company-header">
            <div>
                <div class="company-title">PRODUCTION MASTER</div>
                <div class="company-subtitle">Store & Procurement Department | Request for Quotation</div>
            </div>
            <div>
                <div class="doc-label">VENDOR QUOTATION</div>
                <div style="font-size: 13px; font-weight: 700; color: #0f172a; text-align: right; margin-top: 4px;">{{ $quotation->QuotationNumber }}</div>
            </div>
        </div>

        <!-- Info Cards -->
        <div class="info-grid">
            <!-- Supplier Details -->
            <div class="info-card">
                <div class="info-card-title">Vendor / Supplier Details</div>
                <div class="info-row"><span class="info-label">Supplier Name:</span><span class="info-value">{{ $quotation->supplierRelation->SupplierName ?? 'N/A' }}</span></div>
                <div class="info-row"><span class="info-label">GSTIN:</span><span class="info-value">{{ $quotation->supplierRelation->GSTIN ?? '-' }}</span></div>
                <div class="info-row"><span class="info-label">Contact No:</span><span class="info-value">{{ $quotation->supplierRelation->ContactNo ?? '-' }}</span></div>
                <div class="info-row"><span class="info-label">Address:</span><span class="info-value">{{ $quotation->supplierRelation->Address ?? '' }} {{ $quotation->supplierRelation->City ?? '' }}</span></div>
            </div>

            <!-- Quotation Reference Details -->
            <div class="info-card">
                <div class="info-card-title">Quotation Details</div>
                <div class="info-row"><span class="info-label">Quotation No:</span><span class="info-value">{{ $quotation->QuotationNumber }}</span></div>
                <div class="info-row"><span class="info-label">Date:</span><span class="info-value">{{ is_string($quotation->QuotationDate) ? date('d/m/Y', strtotime($quotation->QuotationDate)) : $quotation->QuotationDate->format('d/m/Y') }}</span></div>
                @if($quotation->FromDate && $quotation->ToDate)
                    <div class="info-row">
                        <span class="info-label">MRL Range:</span>
                        <span class="info-value">
                            {{ date('d/m/Y', strtotime($quotation->FromDate)) }} to {{ date('d/m/Y', strtotime($quotation->ToDate)) }}
                        </span>
                    </div>
                @endif
                <div class="info-row"><span class="info-label">Total Items:</span><span class="info-value">{{ $quotation->TotalItems }} item(s)</span></div>
            </div>
        </div>

        <!-- Items Table -->
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 50px; text-align: center;">Sr No</th>
                    <th>Item Name</th>
                    <th style="width: 130px;">Part No</th>
                    <th style="width: 130px;">Catalogue No</th>
                    <th style="width: 90px;">HSN Code</th>
                    <th style="width: 110px; text-align: right;">Quantity</th>
                </tr>
            </thead>
            <tbody>
                @forelse($quotation->children as $index => $row)
                    <tr>
                        <td style="text-align: center; font-weight: 600;">{{ $index + 1 }}</td>
                        <td style="font-weight: 600; color: #0f172a;">{{ $row->itemMasterRelation->ItemName ?? 'Item' }}</td>
                        <td>{{ $row->itemMasterRelation->PartNo ?? '-' }}</td>
                        <td>{{ $row->itemMasterRelation->CatalogueNo ?? '-' }}</td>
                        <td>{{ $row->itemMasterRelation->HSNNo ?? '-' }}</td>
                        <td style="text-align: right; font-weight: 700; color: #0f172a;">{{ number_format($row->Quantity, 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align: center; color: #64748b;">No items attached to this quotation.</td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr class="totals-row">
                    <td colspan="5" style="text-align: right; padding: 12px;">Total Requisition Quantity:</td>
                    <td style="text-align: right; padding: 12px;">{{ number_format($quotation->TotalQuantity, 2) }}</td>
                </tr>
            </tfoot>
        </table>

        <!-- Remarks -->
        @if($quotation->Remarks)
            <div class="remarks-box">
                <strong style="display: block; margin-bottom: 4px;">Terms & Remarks:</strong>
                <p style="white-space: pre-wrap; margin: 0;">{{ $quotation->Remarks }}</p>
            </div>
        @endif

        <!-- Signatures Footer -->
        <div class="signatures">
            <div class="signature-box">
                <div class="signature-line"></div>
                <div class="signature-title">Prepared By (Store Dept)</div>
            </div>
            <div class="signature-box">
                <div class="signature-line"></div>
                <div class="signature-title">Authorised Signatory</div>
            </div>
            <div class="signature-box">
                <div class="signature-line"></div>
                <div class="signature-title">Vendor Acceptance & Stamp</div>
            </div>
        </div>
    </div>

    <script>
        window.addEventListener('load', () => {
            setTimeout(() => {
                window.print();
            }, 300);
        });
    </script>
</body>
</html>
