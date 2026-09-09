<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PI_{{ $pi->PINumber }}</title>
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
            max-width: 1000px;
            margin: 0 auto;
            background: #ffffff;
            padding: 30px 40px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            border: 1px solid #e2e8f0;
        }
        .no-print-bar {
            max-width: 1000px;
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
            margin-bottom: 20px;
            font-size: 12px;
        }
        .items-table th {
            background-color: #f1f5f9;
            color: #1e293b;
            font-weight: 700;
            text-transform: uppercase;
            padding: 8px 10px;
            text-align: left;
            border-top: 1px solid #cbd5e1;
            border-bottom: 2px solid #cbd5e1;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .items-table td {
            padding: 8px 10px;
            border-bottom: 1px solid #e2e8f0;
            color: #334155;
        }
        .items-table tr:nth-child(even) {
            background-color: #f8fafc;
        }

        .summary-table {
            width: 320px;
            margin-left: auto;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 13px;
        }
        .summary-table td {
            padding: 6px 10px;
            border-bottom: 1px solid #e2e8f0;
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
                size: A4 landscape;
                margin: 10mm;
            }
        }
    </style>
</head>
<body>

    <!-- Non-printable Header Actions -->
    <div class="no-print-bar">
        <a href="{{ route('store.pi.index') }}" class="btn btn-secondary">&larr; Back to List</a>
        <button onclick="window.print();" class="btn btn-primary">
            <span>🖨️</span> <span>Print / Save as PDF</span>
        </button>
    </div>

    <!-- Printable PI Page -->
    <div class="page-container">
        <!-- Company Header -->
        <div class="company-header">
            <div>
                <div class="company-title">PRODUCTION MASTER</div>
                <div class="company-subtitle">Store & Purchase Department | Proforma Invoice</div>
            </div>
            <div>
                <div class="doc-label">PROFORMA INVOICE</div>
                <div style="font-size: 14px; font-weight: 700; color: #0f172a; text-align: right; margin-top: 4px;">PI No: {{ $pi->PINumber }}</div>
            </div>
        </div>

        <!-- Info Cards -->
        <div class="info-grid">
            <!-- Supplier Details -->
            <div class="info-card">
                <div class="info-card-title">Supplier / Vendor Information</div>
                <div class="info-row"><span class="info-label">Supplier Name:</span><span class="info-value">{{ $pi->supplierRelation->SupplierName ?? 'N/A' }}</span></div>
                <div class="info-row"><span class="info-label">GSTIN:</span><span class="info-value">{{ $pi->supplierRelation->GSTIN ?? '-' }}</span></div>
                <div class="info-row"><span class="info-label">Contact No:</span><span class="info-value">{{ $pi->supplierRelation->ContactNo ?? '-' }}</span></div>
            </div>

            <!-- PI Reference Details -->
            <div class="info-card">
                <div class="info-card-title">Document Reference</div>
                <div class="info-row"><span class="info-label">Invoice Entry No:</span><span class="info-value">{{ $pi->InvoiceEntryNo }}</span></div>
                <div class="info-row"><span class="info-label">PI Number:</span><span class="info-value">{{ $pi->PINumber }}</span></div>
                <div class="info-row"><span class="info-label">Date:</span><span class="info-value">{{ is_string($pi->PIDate) ? date('d/m/Y', strtotime($pi->PIDate)) : $pi->PIDate->format('d/m/Y') }}</span></div>
            </div>
        </div>

        <!-- Items Table -->
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 35px; text-align: center;">#</th>
                    <th>Item Master</th>
                    <th style="width: 60px; text-align: right;">Qty</th>
                    <th style="width: 75px; text-align: right;">Basic Rate</th>
                    <th style="width: 80px; text-align: right;">Amount</th>
                    <th style="width: 55px; text-align: right;">Dis.%</th>
                    <th style="width: 80px; text-align: right;">Net V. After Dis</th>
                    <th style="width: 55px; text-align: right;">Pack.%</th>
                    <th style="width: 55px; text-align: right;">Frt.%</th>
                    <th style="width: 85px; text-align: right;">Net Amount</th>
                    <th style="width: 50px; text-align: right;">GST%</th>
                    <th style="width: 75px; text-align: right;">GST Amt</th>
                    <th style="width: 90px; text-align: right;">PI Amount</th>
                    <th style="width: 70px; text-align: right;">Net Rate</th>
                </tr>
            </thead>
            <tbody>
                @forelse($pi->children as $index => $row)
                    <tr>
                        <td style="text-align: center; font-weight: 600;">{{ $index + 1 }}</td>
                        <td style="font-weight: 600; color: #0f172a;">
                            {{ $row->itemMasterRelation->ItemName ?? 'Item' }}
                            @if($row->itemMasterRelation->PartNo) <span style="font-size:10px; color:#64748b;">({{ $row->itemMasterRelation->PartNo }})</span> @endif
                        </td>
                        <td style="text-align: right; font-weight: 600;">{{ number_format($row->Quantity, 2) }}</td>
                        <td style="text-align: right;">₹{{ number_format($row->BasicRate, 2) }}</td>
                        <td style="text-align: right;">₹{{ number_format($row->Amount, 2) }}</td>
                        <td style="text-align: right;">{{ number_format($row->TradeDisPercent, 1) }}%</td>
                        <td style="text-align: right;">₹{{ number_format($row->NetValAfterDis, 2) }}</td>
                        <td style="text-align: right;">{{ number_format($row->PackChargesPercent, 1) }}%</td>
                        <td style="text-align: right;">{{ number_format($row->FreightPercent, 1) }}%</td>
                        <td style="text-align: right; font-weight: 600;">₹{{ number_format($row->NetAmount, 2) }}</td>
                        <td style="text-align: right;">{{ number_format($row->GSTRate, 1) }}%</td>
                        <td style="text-align: right;">₹{{ number_format($row->GSTAmount, 2) }}</td>
                        <td style="text-align: right; font-weight: 700; color: #1e40af;">₹{{ number_format($row->PIAmount, 2) }}</td>
                        <td style="text-align: right; font-weight: 600; color: #92400e;">₹{{ number_format($row->NetRate, 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="14" style="text-align: center; color: #64748b;">No items added to this PI.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <!-- Summary Table -->
        <table class="summary-table">
            <tr>
                <td style="color: #64748b;">Total Ad. Pay. Dis. Amount:</td>
                <td style="text-align: right; font-weight: 600;">₹{{ number_format($pi->TotalAdPayDisAmount, 2) }}</td>
            </tr>
            <tr>
                <td style="color: #64748b;">Total Net V. After Dis.:</td>
                <td style="text-align: right; font-weight: 600;">₹{{ number_format($pi->TotalNetValAfterDis, 2) }}</td>
            </tr>
            <tr>
                <td style="color: #64748b;">Total Pack. Charges:</td>
                <td style="text-align: right; font-weight: 600;">₹{{ number_format($pi->TotalPackChargesAmount, 2) }}</td>
            </tr>
            <tr>
                <td style="color: #64748b;">Total Freight Amount:</td>
                <td style="text-align: right; font-weight: 600;">₹{{ number_format($pi->TotalFreightAmount, 2) }}</td>
            </tr>
            <tr>
                <td style="color: #64748b;">Total Net Amount:</td>
                <td style="text-align: right; font-weight: 700;">₹{{ number_format($pi->TotalNetAmount, 2) }}</td>
            </tr>
            <tr>
                <td style="color: #64748b;">Total GST Amount:</td>
                <td style="text-align: right; font-weight: 700;">₹{{ number_format($pi->TotalGSTAmount, 2) }}</td>
            </tr>
            <tr style="background-color: #f1f5f9;">
                <td style="font-weight: 800; color: #1e3a8a; font-size: 14px;">Total PI Amount:</td>
                <td style="text-align: right; font-weight: 800; color: #1e3a8a; font-size: 15px;">₹{{ number_format($pi->TotalPIAmount, 2) }}</td>
            </tr>
        </table>

        <!-- Remarks -->
        @if($pi->Remarks)
            <div class="remarks-box">
                <strong style="display: block; margin-bottom: 4px;">Remarks:</strong>
                <p style="white-space: pre-wrap; margin: 0;">{{ $pi->Remarks }}</p>
            </div>
        @endif

        <!-- Signatures Footer -->
        <div class="signatures">
            <div class="signature-box">
                <div class="signature-line"></div>
                <div class="signature-title">Prepared By</div>
            </div>
            <div class="signature-box">
                <div class="signature-line"></div>
                <div class="signature-title">Verified By</div>
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
