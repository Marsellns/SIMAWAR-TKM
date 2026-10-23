@role('admin')
@php
    $definition = \App\Support\InfrastructureManualEntry::DATASETS[$dataset];
    // Keep manual validation separate from Livewire's default error bag,
    // and adapt it locally for the existing reusable edit-field partials.
    $manualErrors = session('errors', $errors)->getBag('infrastructureManual');
    $errors = (new \Illuminate\Support\ViewErrorBag)->put('default', $manualErrors);
    $reopenManual = old('_manual_dataset') === $dataset && $errors->any();
@endphp
<div class="modal fade" id="infrastructureManualModal" tabindex="-1" aria-labelledby="infrastructure-manual-title" aria-hidden="true" data-reopen="{{ $reopenManual ? 'true' : 'false' }}">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <form class="modal-content" method="POST" enctype="multipart/form-data" action="{{ route('infrastruktur.manual.store', $dataset) }}" data-infrastructure-manual-form>
            @csrf
            <input type="hidden" name="_manual_dataset" value="{{ $dataset }}">
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="infrastructure-manual-title">Tambah Manual — {{ $definition['label'] }}</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <p class="small text-body-secondary">Masukkan data satu site. Kolom bertanda * wajib diisi.</p>
                @if ($reopenManual)
                    <div class="alert alert-danger" role="alert" tabindex="-1" data-manual-errors>
                        <strong>Data belum tersimpan. Periksa isian berikut:</strong>
                        <ul class="mb-0 mt-2">@foreach ($errors->all() as $message)<li>{{ $message }}</li>@endforeach</ul>
                    </div>
                @endif
                @if ($dataset === 'sewa-lahan')
                    @include('infrastruktur.sewa-lahan.partials.form-fields', ['sewaLahan' => new \App\Models\SewaLahanRenewal])
                @elseif ($dataset === 'combat')
                    @include('infrastruktur.combat.partials.form-fields', ['combat' => new \App\Models\CombatSite])
                @elseif ($dataset === 'bapss')
                    @include('infrastruktur.bapss.partials.form-fields', ['bapss' => new \App\Models\Bapss])
                @else
                    <div class="row g-3">
                        @foreach (\App\Support\InfrastructureManualEntry::fields($dataset) as $key => $field)
                            @include('infrastruktur.partials.manual-field', ['prefix' => ''])
                        @endforeach
                    </div>
                @endif
                @if (in_array($dataset, ['sewa-lahan', 'combat'], true))
                    <details class="mt-4" @if ($reopenManual) open @endif>
                        <summary class="fw-semibold mb-3">Detail Site, Lokasi &amp; Proses</summary>
                        <p class="small text-body-secondary">Masa sewa, aging, tindak lanjut, dan prioritas dihitung otomatis dari tanggal dan status.</p>
                        <p class="small text-body-secondary">Peta lokasi muncul otomatis jika Alamat Lengkap atau pasangan Latitude dan Longitude diisi.</p>
                        <div class="row g-3">
                            @foreach (\App\Support\InfrastructureManualEntry::sourceFields($dataset) as $key => $field)
                                @include('infrastruktur.partials.manual-field', ['prefix' => 'source_details'])
                            @endforeach
                        </div>
                    </details>
                    <details class="mt-4" @if ($errors->has('performance.*') || $errors->has('performance_year')) open @endif>
                        <summary class="fw-semibold mb-3">Kinerja Bulanan (opsional)</summary>
                        <p class="small text-body-secondary">Isi revenue dan cost untuk bulan yang tersedia. P&amp;L dihitung dari revenue dikurangi cost.</p>
                        <label class="form-label" for="manual-performance-year">Tahun Kinerja</label>
                        <input type="number" class="form-control mb-3" style="max-width:180px" name="performance_year" id="manual-performance-year" value="{{ old('performance_year', now()->year) }}" min="2000" max="2100">
                        @foreach (\App\Support\InfrastructureManualEntry::MONTHS as $month => $label)
                            <div class="row g-2 mb-3">
                                @foreach (['revenue' => 'Revenue', 'cost' => 'Cost'] as $metric => $metricLabel)
                                    <div class="col-md-6">
                                        <label class="form-label small" for="manual-{{ $month }}-{{ $metric }}">{{ $metricLabel }} {{ $label }} (Rp)</label>
                                        <input type="number" class="form-control" id="manual-{{ $month }}-{{ $metric }}" name="performance[{{ $month }}][{{ $metric }}]" value="{{ old('performance.'.$month.'.'.$metric) }}" min="0" step="0.01">
                                    </div>
                                @endforeach
                            </div>
                        @endforeach
                    </details>
                @endif
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-sm btn-brand" data-manual-submit>Simpan Site</button>
            </div>
        </form>
    </div>
</div>
@push('scripts')
<script src="{{ asset('js/infrastructure-manual-entry.js') }}"></script>
@endpush
@endrole
