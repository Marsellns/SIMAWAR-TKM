@php
    $selectedOwner = $scope === 'sewa'
        ? (request('ownership_scope') ?: (request('filter_field') === 'ownership' ? request('filter_value') : ''))
        : '';
@endphp
<div class="card mb-3" data-simaster-filter-panel="Filter {{ $scope === 'all' ? 'Infrastruktur Management' : ($scope === 'sewa' ? 'Sewa Lahan' : 'Combat') }}">
    <form id="infrastructure-scope-filter" method="get" action="{{ $scope === 'all' ? route('infrastruktur.index') : ($scope === 'sewa' ? route('infrastruktur.sewa-lahan.index') : route('infrastruktur.combat.index')) }}" class="card-body d-flex flex-wrap align-items-end gap-3">
        <div>
            <label for="filter-tahun" class="form-label small fw-semibold mb-1">{{ $scope === 'combat' ? 'Tahun Justi Combat' : ($scope === 'sewa' ? 'Tahun Renewal' : 'Tahun Renewal / Justi') }}</label>
            <select id="filter-tahun" name="tahun" class="form-select form-select-sm" style="min-width:150px">
                <option value="all">Semua Tahun</option>
                @foreach ($tahunList as $tahun)
                    <option value="{{ $tahun }}" @selected((string) request('tahun') === (string) $tahun)>{{ $tahun }}</option>
                @endforeach
            </select>
        </div>
        @if ($scope === 'sewa')
            <div>
                <label for="filter-ownership" class="form-label small fw-semibold mb-1">Kepemilikan Site</label>
                <select id="filter-ownership" name="ownership_scope" class="form-select form-select-sm" style="min-width:150px">
                    <option value="">Semua Site</option>
                    <option value="Telkomsel" @selected($selectedOwner === 'Telkomsel')>Site Telkomsel</option>
                    <option value="TP" @selected($selectedOwner === 'TP')>Site TP</option>
                </select>
            </div>
        @endif
        <button type="submit" class="btn btn-sm btn-primary">Terapkan Filter</button>
        <a href="{{ $scope === 'all' ? route('infrastruktur.index') : ($scope === 'sewa' ? route('infrastruktur.sewa-lahan.index') : route('infrastruktur.combat.index')) }}" class="btn btn-sm btn-outline-secondary">Reset</a>
        <span class="small text-body-secondary align-self-center">Grafik, ringkasan, dan tabel mengikuti pilihan ini.</span>
    </form>
</div>
