@extends('layouts.app')

@section('title', $purchase->exists ? 'Edit Purchase' : 'Add New Purchase')

@section('content')
<div class="content-header">
    <div class="content-title">
        <h1>{{ $purchase->exists ? 'Edit Purchase' : 'Add New Purchase' }}</h1>
    </div>
    <a href="{{ route('inventories.purchase.index') }}" class="btn-action-secondary" style="border-radius: 50%; width: 36px; height: 36px; padding: 0; display: inline-flex; align-items: center; justify-content: center; font-size: 1.2rem;" title="Close">
        ✕
    </a>
</div>

<div class="card" style="margin-top: 1rem;">
    <form action="{{ $purchase->exists ? route('inventories.purchase.update', $purchase->ID) : route('inventories.purchase.store') }}" method="POST">
        @csrf
        @if($purchase->exists)
            @method('PUT')
        @endif

        <!-- Header Controls Row 1 -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1.25rem;">
            <div class="form-group">
                <label for="ID">Id</label>
                <input type="text" id="ID" value="{{ $purchase->ID ?? '' }}" disabled style="background: rgba(0,0,0,0.03);">
            </div>

            <div class="form-group">
                <label for="InvoiceNo">Invoice No</label>
                <input type="text" name="InvoiceNo" id="InvoiceNo" value="{{ old('InvoiceNo', $purchase->InvoiceNo) }}" class="@error('InvoiceNo') is-invalid @enderror" placeholder="Enter invoice number">
                @error('InvoiceNo')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="EntryDate">Entry Date</label>
                <input type="date" name="EntryDate" id="EntryDate" value="{{ old('EntryDate', $purchase->EntryDate) }}" class="@error('EntryDate') is-invalid @enderror" required>
                @error('EntryDate')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <!-- Header Controls Row 2 -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
            <div class="form-group">
                <label for="RollNumber">Roll Number</label>
                <input type="text" name="RollNumber" id="RollNumber" value="{{ old('RollNumber', $purchase->RollNumber) }}" class="@error('RollNumber') is-invalid @enderror" placeholder="Enter roll number" required>
                @error('RollNumber')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="RollSize">Roll Size</label>
                <select name="RollSize" id="RollSize" class="@error('RollSize') is-invalid @enderror" required>
                    <option value="">Select</option>
                    @foreach($rollSizes as $rs)
                        <option value="{{ $rs->ID }}" {{ old('RollSize', $purchase->RollSize) == $rs->ID ? 'selected' : '' }}>
                            {{ $rs->RollSize }}
                        </option>
                    @endforeach
                </select>
                @error('RollSize')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="FabricColor">Fabric Color</label>
                <select name="FabricColor" id="FabricColor" class="@error('FabricColor') is-invalid @enderror" required>
                    <option value="">Select</option>
                    @foreach($fabricColors as $fc)
                        <option value="{{ $fc->ID }}" {{ old('FabricColor', $purchase->FabricColor) == $fc->ID ? 'selected' : '' }}>
                            {{ $fc->FabricColor }}
                        </option>
                    @endforeach
                </select>
                @error('FabricColor')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="Lamination">Lamination</label>
                <select name="Lamination" id="Lamination" class="@error('Lamination') is-invalid @enderror">
                    <option value="">- Select -</option>
                    <option value="1" {{ old('Lamination', $purchase->Lamination) == '1' ? 'selected' : '' }}>Laminate</option>
                    <option value="0" {{ old('Lamination', $purchase->Lamination) === 0 || old('Lamination', $purchase->Lamination) === '0' ? 'selected' : '' }}>Unlaminate</option>
                </select>
                @error('Lamination')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <!-- Inner Sub-Card: Purchase Section -->
        <div style="border: 1px solid var(--card-border); border-radius: 12px; padding: 1.25rem; background: rgba(248, 250, 252, 0.5); margin-bottom: 1.5rem;">
            <div style="text-align: center; margin-bottom: 1.25rem;">
                <h3 style="font-size: 1.1rem; font-weight: 700; color: var(--text-primary);">Purchase</h3>
            </div>

            <!-- Row 1: Gram / Actual Meter / Gross Weight / Core Weight -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1rem;">
                <div class="form-group">
                    <label for="RequiredGramMeter">Required Gram Meter</label>
                    <div class="input-group">
                        <input type="text" name="RequiredGramMeter" id="RequiredGramMeter" value="{{ old('RequiredGramMeter', $purchase->RequiredGramMeter) }}" required>
                        <span class="input-group-addon">Gram</span>
                    </div>
                </div>

                <div class="form-group">
                    <label for="ActualMeter">Actual Meter</label>
                    <div class="input-group">
                        <input type="text" name="ActualMeter" id="ActualMeter" value="{{ old('ActualMeter', $purchase->ActualMeter) }}" required>
                        <span class="input-group-addon">Meter</span>
                    </div>
                </div>

                <div class="form-group">
                    <label for="GrossWeight">Gross Weight</label>
                    <div class="input-group">
                        <input type="text" name="GrossWeight" id="GrossWeight" value="{{ old('GrossWeight', $purchase->GrossWeight) }}" required>
                        <span class="input-group-addon">KG</span>
                    </div>
                </div>

                <div class="form-group">
                    <label for="CoreWeight">Core Weight</label>
                    <div class="input-group">
                        <input type="text" name="CoreWeight" id="CoreWeight" value="{{ old('CoreWeight', $purchase->CoreWeight) }}" required>
                        <span class="input-group-addon">KG</span>
                    </div>
                </div>
            </div>

            <!-- Row 2: Calculations (Net Weight, Actual Meter Weight, Variation) -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                <div class="form-group" style="grid-column: span 1;">
                    <!-- Spacer or offset matching mockup -->
                </div>

                <div class="form-group">
                    <label for="NetWeight">Net Weight</label>
                    <div class="input-group">
                        <input type="text" name="NetWeight" id="NetWeight" value="{{ old('NetWeight', $purchase->NetWeight) }}" required>
                        <span class="input-group-addon">KG</span>
                    </div>
                </div>

                <div class="form-group">
                    <label for="ActualMeterWeight">Actual Meter Weight</label>
                    <div class="input-group">
                        <input type="text" name="ActualMeterWeight" id="ActualMeterWeight" value="{{ old('ActualMeterWeight', $purchase->ActualMeterWeight) }}" required>
                        <span class="input-group-addon">KG</span>
                    </div>
                </div>

                <div class="form-group">
                    <label for="Variation">Variation</label>
                    <div class="input-group">
                        <input type="text" name="Variation" id="Variation" value="{{ old('Variation', $purchase->Variation) }}" required>
                        <span class="input-group-addon">KG</span>
                    </div>
                </div>
            </div>
        </div>

        <div style="margin-top: 1.5rem;">
            <button type="submit" class="btn-success">
                Save
            </button>
        </div>
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const reqGramInput = document.getElementById('RequiredGramMeter');
        const actualMeterInput = document.getElementById('ActualMeter');
        const grossInput = document.getElementById('GrossWeight');
        const coreInput = document.getElementById('CoreWeight');
        const netWeightInput = document.getElementById('NetWeight');
        const actualMeterWeightInput = document.getElementById('ActualMeterWeight');
        const variationInput = document.getElementById('Variation');

        function calculateAll() {
            // Net Weight = Gross Weight - Core Weight
            const gr = parseFloat(grossInput?.value);
            const cr = parseFloat(coreInput?.value);
            if (!isNaN(gr) && !isNaN(cr)) {
                const netVal = gr - cr;
                netWeightInput.value = isNaN(netVal) ? '' : netVal.toFixed(2).replace(/\.00$/, '');
            }

            // Actual Meter Weight = (Net Weight / Actual Meter) * 1000
            const net = parseFloat(netWeightInput?.value);
            const am = parseFloat(actualMeterInput?.value);
            if (!isNaN(net) && !isNaN(am) && am !== 0) {
                const amwVal = (net / am) * 1000;
                actualMeterWeightInput.value = isNaN(amwVal) ? '' : amwVal.toFixed(2).replace(/\.00$/, '');
            }

            // Variation = Required Gram Meter Weight - Actual Meter Weight
            const rgm = parseFloat(reqGramInput?.value);
            const amw = parseFloat(actualMeterWeightInput?.value);
            if (!isNaN(rgm) && !isNaN(amw)) {
                const varVal = rgm - amw;
                variationInput.value = isNaN(varVal) ? '' : varVal.toFixed(2).replace(/\.00$/, '');
            }
        }

        const allInputs = [
            reqGramInput, actualMeterInput, grossInput, coreInput, netWeightInput, actualMeterWeightInput
        ];

        allInputs.forEach(input => {
            if (input) {
                input.addEventListener('input', calculateAll);
            }
        });
    });
</script>
@endsection
