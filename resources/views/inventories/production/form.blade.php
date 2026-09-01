@extends('layouts.app')

@section('title', $production->exists ? 'Edit Production' : 'Add New Production')

@section('content')
<div class="content-header">
    <div class="content-title">
        <h1>{{ $production->exists ? 'Edit Production' : 'Add New Production' }}</h1>
    </div>
    <a href="{{ route('inventories.production.index') }}" class="btn-action-secondary" style="border-radius: 50%; width: 36px; height: 36px; padding: 0; display: inline-flex; align-items: center; justify-content: center; font-size: 1.2rem;" title="Close">
        ✕
    </a>
</div>

<div class="card" style="margin-top: 1rem;">
    <form action="{{ $production->exists ? route('inventories.production.update', $production->ID) : route('inventories.production.store') }}" method="POST">
        @csrf
        @if($production->exists)
            @method('PUT')
        @endif

        <!-- Header Controls Row 1 -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1.25rem;">
            <div class="form-group">
                <label for="ID">Id</label>
                <input type="text" id="ID" value="{{ $production->ID ?? '' }}" disabled style="background: rgba(0,0,0,0.03);">
            </div>

            <div class="form-group">
                <label for="EntryDate">Entry Date</label>
                <input type="date" name="EntryDate" id="EntryDate" value="{{ old('EntryDate', $production->EntryDate) }}" class="@error('EntryDate') is-invalid @enderror" required>
                @error('EntryDate')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <!-- Header Controls Row 2 -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
            <div class="form-group">
                <label for="RollNumber">Roll Number</label>
                <input type="text" name="RollNumber" id="RollNumber" value="{{ old('RollNumber', $production->RollNumber) }}" class="@error('RollNumber') is-invalid @enderror" placeholder="Enter roll number" required>
                @error('RollNumber')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="RollSize">Roll Size</label>
                <select name="RollSize" id="RollSize" class="@error('RollSize') is-invalid @enderror" required>
                    <option value="">Select</option>
                    @foreach($rollSizes as $rs)
                        <option value="{{ $rs->ID }}" {{ old('RollSize', $production->RollSize) == $rs->ID ? 'selected' : '' }}>
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
                        <option value="{{ $fc->ID }}" {{ old('FabricColor', $production->FabricColor) == $fc->ID ? 'selected' : '' }}>
                            {{ $fc->FabricColor }}
                        </option>
                    @endforeach
                </select>
                @error('FabricColor')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="LoomNumber">Loom Number</label>
                <select name="LoomNumber" id="LoomNumber" class="@error('LoomNumber') is-invalid @enderror" required>
                    <option value="">Select</option>
                    @foreach($loomNumbers as $ln)
                        <option value="{{ $ln->ID }}" {{ old('LoomNumber', $production->LoomNumber) == $ln->ID ? 'selected' : '' }}>
                            {{ $ln->LoomNumber }}
                        </option>
                    @endforeach
                </select>
                @error('LoomNumber')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <!-- Inner Sub-Card: Production Section -->
        <div style="border: 1px solid var(--card-border); border-radius: 12px; padding: 1.25rem; background: rgba(248, 250, 252, 0.5); margin-bottom: 1.5rem;">
            <div style="text-align: center; margin-bottom: 1.25rem;">
                <h3 style="font-size: 1.1rem; font-weight: 700; color: var(--text-primary);">Production</h3>
            </div>

            <!-- Row 1: Gram / Opening / Closing / Gross Weight -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1rem;">
                <div class="form-group">
                    <label for="RequiredGramMeter">Required Gram Meter</label>
                    <div class="input-group">
                        <input type="text" name="RequiredGramMeter" id="RequiredGramMeter" value="{{ old('RequiredGramMeter', $production->RequiredGramMeter) }}" required>
                        <span class="input-group-addon">Gram</span>
                    </div>
                </div>

                <div class="form-group">
                    <label for="OpeningMeter">Opening Meter</label>
                    <div class="input-group">
                        <input type="text" name="OpeningMeter" id="OpeningMeter" value="{{ old('OpeningMeter', $production->OpeningMeter) }}">
                        <span class="input-group-addon">Meter</span>
                    </div>
                </div>

                <div class="form-group">
                    <label for="ClosingMeter">Closing Meter</label>
                    <div class="input-group">
                        <input type="text" name="ClosingMeter" id="ClosingMeter" value="{{ old('ClosingMeter', $production->ClosingMeter) }}">
                        <span class="input-group-addon">Meter</span>
                    </div>
                </div>

                <div class="form-group">
                    <label for="GrossWeight">Gross Weight</label>
                    <div class="input-group">
                        <input type="text" name="GrossWeight" id="GrossWeight" value="{{ old('GrossWeight', $production->GrossWeight) }}" required>
                        <span class="input-group-addon">KG</span>
                    </div>
                </div>
            </div>

            <!-- Row 2: Core Weight -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1rem;">
                <div class="form-group">
                    <label for="CoreWeight">Core Weight</label>
                    <div class="input-group">
                        <input type="text" name="CoreWeight" id="CoreWeight" value="{{ old('CoreWeight', $production->CoreWeight) }}" required>
                        <span class="input-group-addon">KG</span>
                    </div>
                </div>
            </div>

            <!-- Row 3: Calculations (Actual Meter, Net Weight, Actual Meter Weight, Variation) -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                <div class="form-group">
                    <label for="ActualMeter">Actual Meter</label>
                    <div class="input-group">
                        <input type="text" name="ActualMeter" id="ActualMeter" value="{{ old('ActualMeter', $production->ActualMeter) }}" required>
                        <span class="input-group-addon">Meter</span>
                    </div>
                </div>

                <div class="form-group">
                    <label for="NetWeight">Net Weight</label>
                    <div class="input-group">
                        <input type="text" name="NetWeight" id="NetWeight" value="{{ old('NetWeight', $production->NetWeight) }}" required>
                        <span class="input-group-addon">KG</span>
                    </div>
                </div>

                <div class="form-group">
                    <label for="ActualMeterWeight">Actual Meter Weight</label>
                    <div class="input-group">
                        <input type="text" name="ActualMeterWeight" id="ActualMeterWeight" value="{{ old('ActualMeterWeight', $production->ActualMeterWeight) }}" required>
                        <span class="input-group-addon">KG</span>
                    </div>
                </div>

                <div class="form-group">
                    <label for="Variation">Variation</label>
                    <div class="input-group">
                        <input type="text" name="Variation" id="Variation" value="{{ old('Variation', $production->Variation) }}" required>
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
        const openingInput = document.getElementById('OpeningMeter');
        const closingInput = document.getElementById('ClosingMeter');
        const actualMeterInput = document.getElementById('ActualMeter');
        const grossInput = document.getElementById('GrossWeight');
        const coreInput = document.getElementById('CoreWeight');
        const netWeightInput = document.getElementById('NetWeight');
        const actualMeterWeightInput = document.getElementById('ActualMeterWeight');
        const variationInput = document.getElementById('Variation');

        function calculateAll() {
            // 1. Actual Meter = Closing - Opening
            const op = parseFloat(openingInput?.value);
            const cl = parseFloat(closingInput?.value);
            if (!isNaN(op) && !isNaN(cl)) {
                const meterVal = cl - op;
                actualMeterInput.value = isNaN(meterVal) ? '' : meterVal.toFixed(2).replace(/\.00$/, '');
            }

            // 2. Net Weight = Gross Weight - Core Weight
            const gr = parseFloat(grossInput?.value);
            const cr = parseFloat(coreInput?.value);
            if (!isNaN(gr) && !isNaN(cr)) {
                const netVal = gr - cr;
                netWeightInput.value = isNaN(netVal) ? '' : netVal.toFixed(2).replace(/\.00$/, '');
            }

            // 3. Actual Meter Weight = (Net Weight / Actual Meter) * 1000
            const net = parseFloat(netWeightInput?.value);
            const am = parseFloat(actualMeterInput?.value);
            if (!isNaN(net) && !isNaN(am) && am !== 0) {
                const amwVal = (net / am) * 1000;
                actualMeterWeightInput.value = isNaN(amwVal) ? '' : amwVal.toFixed(2).replace(/\.00$/, '');
            }

            // 4. Variation = Required Gram Meter Weight - Actual Meter Weight
            const rgm = parseFloat(reqGramInput?.value);
            const amw = parseFloat(actualMeterWeightInput?.value);
            if (!isNaN(rgm) && !isNaN(amw)) {
                const varVal = rgm - amw;
                variationInput.value = isNaN(varVal) ? '' : varVal.toFixed(2).replace(/\.00$/, '');
            }
        }

        const allInputs = [
            reqGramInput, openingInput, closingInput, actualMeterInput,
            grossInput, coreInput, netWeightInput, actualMeterWeightInput
        ];

        allInputs.forEach(input => {
            if (input) {
                input.addEventListener('input', calculateAll);
            }
        });
    });
</script>
@endsection

@section('scripts')
<script>
    $(document).ready(function () {
        $('#LoomNumber').select2({
            placeholder: 'Type or select loom number...',
            allowClear: true,
            width: '100%',
            minimumResultsForSearch: 0
        });
    });
</script>
@endsection
