<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GRN_{{ $grn->GRNNumber }}</title>
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
            max-width: 900px;
            margin: 0 auto;
            background: #ffffff;
            padding: 30px 40px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            border: 1px solid #e2e8f0;
        }
        .no-print-bar {
            max-width: 900px;
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
            background-color: #10b981;
            color: #ffffff;
        }
        .btn-primary:hover {
            background-color: #059669;
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
            color: #059669;
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
            width: 120px;
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
            margin-bottom: 25px;
            font-size: 13px;
        }
        .items-table th {
            background-color: #f1f5f9;
            color: #1e293b;
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
            border-bottom: 1px solid #e2e8f0;
            color: #334155;
        }
        .items-table tr:nth-child(even) {
            background-color: #f8fafc;
        }

        /* Remarks & Signatures */
        .remarks-box {
            background-color: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 6px;
            padding: 12px 16px;
            margin-bottom: 30px;
            font-size: 13px;
            color: #166534;
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
        <a href="{{ route('store.grn.index') }}" class="btn btn-secondary">&larr; Back to GRN List</a>
        <button onclick="window.print();" class="btn btn-primary">
            <span>🖨️</span> <span>Print GRN</span>
        </button>
    </div>

    <!-- Printable GRN Page -->
    <div class="page-container">
        <!-- Company Header -->
        <div class="company-header">
            <div>
                <div class="company-title">PRODUCTION MASTER</div>
                <div class="company-subtitle">Inventory &amp; Store Department | Goods Receipt Note</div>
            </div>
            <div>
                <div class="doc-label">GOODS RECEIPT NOTE</div>
                <div style="font-size: 14px; font-weight: 700; color: #0f172a; text-align: right; margin-top: 4px;">GRN No: {{ $grn->GRNNumber }}</div>
            </div>
        </div>

        <!-- Info Cards -->
        <div class="info-grid">
            <!-- Supplier Details -->
            <div class="info-card">
                <div class="info-card-title">Supplier Information</div>
                <div class="info-row"><span class="info-label">Supplier Name:</span><span class="info-value">{{ $grn->supplierRelation->SupplierName ?? 'N/A' }}</span></div>
                <div class="info-row"><span class="info-label">GSTIN:</span><span class="info-value">{{ $grn->supplierRelation->GSTIN ?? '-' }}</span></div>
                <div class="info-row"><span class="info-label">Contact No:</span><span class="info-value">{{ $grn->supplierRelation->ContactNo ?? '-' }}</span></div>
            </div>

            <!-- GRN Details -->
            <div class="info-card">
                <div class="info-card-title">GRN Reference</div>
                <div class="info-row"><span class="info-label">GRN No:</span><span class="info-value">{{ $grn->GRNNumber }}</span></div>
                <div class="info-row"><span class="info-label">Invoice Date:</span><span class="info-value">{{ is_string($grn->GRNDate) ? date('d/m/Y', strtotime($grn->GRNDate)) : $grn->GRNDate->format('d/m/Y') }}</span></div>
                <div class="info-row"><span class="info-label">PI Reference(s):</span><span class="info-value" style="color:#2563eb;">{{ $piNumbersString ?: 'N/A' }}</span></div>
            </div>
        </div>

        <!-- Items Table -->
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 40px; text-align: center;">#</th>
                    <th>Item Name</th>
                    <th style="width: 120px; text-align: right;">Qty Received</th>
                    <th style="width: 110px; text-align: right;">Rate (₹)</th>
                    <th style="width: 130px; text-align: right;">Amount (₹)</th>
                    <th style="width: 110px; text-align: center;">Rack / Bin</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $totalQty = 0;
                    $totalAmount = 0;
                @endphp
                @forelse($grn->children as $index => $row)
                    @php
                        $rate = (float)($row->piChildRelation->BasicRate ?? 0);
                        $amount = $row->Quantity * $rate;
                        $totalQty += $row->Quantity;
                        $totalAmount += $amount;
                    @endphp
                    <tr>
                        <td style="text-align: center; font-weight: 600;">{{ $index + 1 }}</td>
                        <td style="font-weight: 600; color: #0f172a;">
                            {{ $row->itemMasterRelation->ItemName ?? 'Unknown Item' }}
                        </td>
                        <td style="text-align: right; font-weight: 700; color: #059669;">
                            {{ number_format($row->Quantity, 2) }}
                        </td>
                        <td style="text-align: right; color: #475569;">
                            ₹ {{ number_format($rate, 2) }}
                        </td>
                        <td style="text-align: right; font-weight: 600; color: #1e293b;">
                            ₹ {{ number_format($amount, 2) }}
                        </td>
                        <td style="text-align: center; font-weight: 600; color: #475569;">
                            {{ $row->itemMasterRelation->RackNo ?? 'N/A' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align: center; color: #64748b;">No items included in this GRN.</td>
                    </tr>
                @endforelse
            </tbody>
            @if(count($grn->children) > 0)
                <tfoot>
                    <tr style="background-color: #f1f5f9; font-weight: 700;">
                        <td colspan="2" style="text-align: right; color: #1e293b;">Total:</td>
                        <td style="text-align: right; color: #059669;">{{ number_format($totalQty, 2) }}</td>
                        <td></td>
                        <td style="text-align: right; color: #1e3a8a;">₹ {{ number_format($totalAmount, 2) }}</td>
                        <td></td>
                    </tr>
                </tfoot>
            @endif
        </table>

        <!-- Remarks -->
        @if($grn->Remarks)
            <div class="remarks-box">
                <strong style="display: block; margin-bottom: 4px;">Remarks / Notes:</strong>
                <p style="white-space: pre-wrap; margin: 0;">{{ $grn->Remarks }}</p>
            </div>
        @endif

        <!-- Signatures Footer -->
        <div class="signatures">
            <div class="signature-box">
                <div class="signature-line"></div>
                <div class="signature-title">Received By (Store)</div>
            </div>
            <div class="signature-box">
                <div class="signature-line"></div>
                <div class="signature-title">Inspected / Verified By</div>
            </div>
            <div class="signature-box">
                <div class="signature-line"></div>
                <div class="signature-title">Authorized Signatory</div>
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
