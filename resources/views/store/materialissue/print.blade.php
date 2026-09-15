<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Material Issue Voucher - {{ $materialIssue->IssueNo }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            color: #333;
            background: #fff;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            text-transform: uppercase;
        }
        .header p {
            margin: 5px 0 0 0;
            font-size: 14px;
            color: #666;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-bottom: 20px;
            border: 1px solid #ddd;
            padding: 15px;
            border-radius: 6px;
        }
        .info-item label {
            font-weight: bold;
            display: block;
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
        }
        .info-item span {
            font-size: 16px;
            font-weight: bold;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
            font-size: 14px;
        }
        th {
            background-color: #f5f5f5;
            font-weight: bold;
        }
        .text-right {
            text-align: right;
        }
        .footer {
            margin-top: 40px;
            display: flex;
            justify-content: space-between;
        }
        .signature-box {
            text-align: center;
            width: 200px;
            border-top: 1px solid #333;
            padding-top: 5px;
            font-size: 13px;
        }
        @media print {
            .no-print {
                display: none;
            }
            body {
                margin: 0;
            }
        }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 20px; text-align: right;">
        <button onclick="window.print()" style="padding: 8px 16px; background: #4f46e5; color: white; border: none; border-radius: 4px; cursor: pointer;">🖨️ Print Voucher</button>
    </div>

    <div class="header">
        <h1>STORE MATERIAL ISSUE VOUCHER</h1>
        <p>Production Management System</p>
    </div>

    <div class="info-grid">
        <div class="info-item">
            <label>Issue Number</label>
            <span>{{ $materialIssue->IssueNo }}</span>
        </div>
        <div class="info-item">
            <label>Issue Date</label>
            <span>{{ $materialIssue->IssueDate ? date('d/m/Y', strtotime($materialIssue->IssueDate)) : '-' }}</span>
        </div>
        <div class="info-item">
            <label>Technician / Person</label>
            <span>{{ $materialIssue->technicianRelation ? $materialIssue->technicianRelation->Name : '-' }}</span>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 50px;">#</th>
                <th>Loom No. / Machine Name</th>
                <th>To Department</th>
                <th>Item Details</th>
                <th class="text-right" style="width: 120px;">Quantity Issued</th>
            </tr>
        </thead>
        <tbody>
            @foreach($materialIssue->children as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    @php
                        $loom = $item->loomRelation;
                        $loomDisplay = '-';
                        if ($loom) {
                            $nameParts = [];
                            if ($loom->LoomNumber) $nameParts[] = $loom->LoomNumber;
                            if ($loom->MachineName) $nameParts[] = $loom->MachineName;
                            $nameStr = implode(' / ', $nameParts) ?: 'Loom #' . $loom->ID;
                            $typeStr = $loom->LoomTypeName ? ' - ( ' . $loom->LoomTypeName . ' )' : '';
                            $loomDisplay = $nameStr . $typeStr;
                        }
                    @endphp
                    <td>{{ $loomDisplay }}</td>
                    <td>{{ $item->departmentRelation ? $item->departmentRelation->DepartmentName . ($item->departmentRelation->Code ? ' ('.$item->departmentRelation->Code.')' : '') : '-' }}</td>
                    <td>{{ $item->itemMasterRelation ? $item->itemMasterRelation->ItemName . ($item->itemMasterRelation->PartNo ? ' | Part No: '.$item->itemMasterRelation->PartNo : '') . ($item->itemMasterRelation->CatalogueNo ? ' | Cat No: '.$item->itemMasterRelation->CatalogueNo : '') : '-' }}</td>
                    <td class="text-right">{{ number_format($item->Quantity, 0) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <th colspan="4" class="text-right">Total Quantity Issued</th>
                <th class="text-right">{{ number_format($materialIssue->TotalQuantity, 0) }}</th>
            </tr>
        </tfoot>
    </table>

    @if($materialIssue->Remarks)
        <div style="margin-bottom: 30px;">
            <label style="font-weight: bold; font-size: 13px; color: #666; display: block;">REMARKS:</label>
            <div style="padding: 10px; border: 1px dashed #ccc; border-radius: 4px; background: #fafafa;">
                {{ $materialIssue->Remarks }}
            </div>
        </div>
    @endif

    <div class="footer">
        <div class="signature-box">
            Issued By (Store)
        </div>
        <div class="signature-box">
            Received By (Technician / Person)
        </div>
    </div>
</body>
</html>
