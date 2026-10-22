@extends('layouts.app')

@section('title', ($showForm ? 'Input Data Tagihan IBC' : 'Daftar Tagihan Listrik IBC Tersimpan').' — SIMAWAR')

@section('content')
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <div>
            <h1 class="h4 {{ $showForm ? 'mb-1' : 'mb-0' }}">{{ $showForm ? 'Input Data Tagihan IBC' : 'Daftar Tagihan Listrik IBC Tersimpan' }}</h1>
            @if ($showForm)
                <p class="text-body-secondary mb-0 small">Total Tagihan dan Total Payment dihitung otomatis dari isian form.</p>
            @endif
        </div>
        <div class="d-flex flex-wrap gap-2">
            @include('operations.actions', ['dataset' => 'input-ibc', 'entryLabel' => 'Upload Excel'])
            <a href="{{ route('electricity.inbuilding.listrik-inbuilding.index') }}" class="btn btn-outline-secondary btn-sm">
                Master Listrik Inbuilding
            </a>
            @if ($showForm)
                <a href="{{ route('electricity.inbuilding.input-tagihan-ibc.index') }}" class="btn btn-outline-secondary btn-sm">Daftar Tagihan Tersimpan</a>
            @else
                @role('admin')
                <a href="{{ route('electricity.inbuilding.input-tagihan-ibc.create') }}" class="btn btn-brand btn-sm">Input Data Tagihan IBC</a>
                @endrole
            @endif
        </div>
    </div>

    {{-- Alert Feedback Container --}}
    <div id="alert-container"></div>

    @if ($showForm)
    {{-- Form Input Card --}}
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span class="fw-semibold">{{ $editRecord ? 'Edit Data Tagihan Listrik IBC' : 'Form Input Manual Tagihan Listrik IBC' }}</span>
            <span class="small text-secondary">* Wajib diisi</span>
        </div>
        <div class="card-body">
            <form id="formInputTagihanIbc">
                @csrf

                {{-- 1. IDENTITAS TIPE, SITE & PERIODE --}}
                <div class="detail-block mb-3">
                    <div class="section-title mb-2">1. Identitas Tipe, Site & Periode Tagihan</div>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="type" class="form-label small fw-semibold">TYPE (13 Pilihan) <span class="text-danger">*</span></label>
                            <select name="type" id="type" class="form-select form-select-sm" required>
                                <option value="" selected disabled>-- Pilih Type --</option>
                                @foreach ($types as $t)
                                    <option value="{{ $t }}">{{ $t }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="site_id" class="form-label small fw-semibold">Site ID <span class="text-danger">*</span></label>
                            <input type="text" name="site_id" id="site_id" list="siteIdList" class="form-control form-control-sm" required placeholder="Contoh: BKS008">
                            <datalist id="siteIdList">
                                @foreach ($sites as $sId => $sName)
                                    <option value="{{ $sId }}">{{ $sName }}</option>
                                @endforeach
                            </datalist>
                        </div>
                        <div class="col-md-4">
                            <label for="periode" class="form-label small fw-semibold">Periode Tagihan <span class="text-danger">*</span></label>
                            <select name="periode" id="periode" class="form-select form-select-sm" required>
                                <option value="" selected disabled>-- Pilih Periode --</option>
                                @foreach ($periods as $p)
                                    <option value="{{ $p['code'] }}">{{ $p['label'] }} ({{ $p['code'] }})</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                {{-- 2. FAKTUR PAJAK, BAST & METERAN --}}
                <div class="detail-block mb-3">
                    <div class="section-title mb-2">2. Faktur Pajak, BAST & Meteran Listrik</div>
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label for="no_faktur_pajak" class="form-label small fw-semibold">No. Faktur Pajak</label>
                            <input type="text" name="no_faktur_pajak" id="no_faktur_pajak" class="form-control form-control-sm" placeholder="Contoh: 010.000-24.00000001">
                        </div>
                        <div class="col-md-3">
                            <label for="tgl_faktur_pajak" class="form-label small fw-semibold">Tgl. Faktur Pajak</label>
                            <input type="date" name="tgl_faktur_pajak" id="tgl_faktur_pajak" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-3">
                            <label for="no_bast" class="form-label small fw-semibold">No. BAST</label>
                            <input type="text" name="no_bast" id="no_bast" class="form-control form-control-sm" placeholder="Nomor BAST">
                        </div>
                        <div class="col-md-3">
                            <label for="tgl_bast" class="form-label small fw-semibold">Tgl. BAST</label>
                            <input type="date" name="tgl_bast" id="tgl_bast" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-3">
                            <label for="meter_awal" class="form-label small fw-semibold">Meter Awal (kWh)</label>
                            <input type="number" step="0.01" name="meter_awal" id="meter_awal" class="form-control form-control-sm" placeholder="0.00">
                        </div>
                        <div class="col-md-3">
                            <label for="meter_akhir" class="form-label small fw-semibold">Meter Akhir (kWh)</label>
                            <input type="number" step="0.01" name="meter_akhir" id="meter_akhir" class="form-control form-control-sm" placeholder="0.00">
                        </div>
                        <div class="col-md-3">
                            <label for="no_invoice_bm" class="form-label small fw-semibold">No. Invoice BM</label>
                            <input type="text" name="no_invoice_bm" id="no_invoice_bm" class="form-control form-control-sm" placeholder="Nomor Invoice BM">
                            <small class="text-muted">Isi invoice untuk menambah/koreksi invoice tertentu. Kosong berarti total tagihan site pada periode ini.</small>
                        </div>
                        <div class="col-md-3">
                            <label for="tgl_bayar_to_bm" class="form-label small fw-semibold">Tgl. Bayar to BM</label>
                            <input type="date" name="tgl_bayar_to_bm" id="tgl_bayar_to_bm" class="form-control form-control-sm">
                        </div>
                    </div>
                </div>

                {{-- 3. KOMPONEN TAGIHAN & KALKULASI TOTAL TAGIHAN (F) --}}
                <div class="detail-block mb-3">
                    <div class="section-title mb-2">3. Komponen Biaya Tagihan (Kalkulasi Otomatis Total Tagihan F)</div>
                    <div class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label for="dpp_ppn_b" class="form-label small fw-semibold">DPP PPN (B)</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text">Rp</span>
                                <input type="number" step="0.01" name="dpp_ppn_b" id="dpp_ppn_b" class="form-control calc-input" placeholder="0">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label for="ppj_pju_c" class="form-label small fw-semibold">PPJ / PJU (C)</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text">Rp</span>
                                <input type="number" step="0.01" name="ppj_pju_c" id="ppj_pju_c" class="form-control calc-input" placeholder="0">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label for="materai_d" class="form-label small fw-semibold">Materai (D)</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text">Rp</span>
                                <input type="number" step="0.01" name="materai_d" id="materai_d" class="form-control calc-input" placeholder="0">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label for="others_e" class="form-label small fw-semibold">Others (E)</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text">Rp</span>
                                <input type="number" step="0.01" name="others_e" id="others_e" class="form-control calc-input" placeholder="0">
                            </div>
                        </div>
                        {{-- Calculated Total Tagihan (F) --}}
                        <div class="col-12 mt-2">
                            <div class="p-2 px-3 rounded border d-flex justify-content-between align-items-center bg-body-tertiary">
                                <div>
                                    <span class="small fw-semibold text-secondary">Total Tagihan (F)</span>
                                    <div class="small text-muted">[Otomatis: B + C + D + E]</div>
                                </div>
                                <div class="fs-5 fw-bold text-primary" id="preview_total_tagihan_f">Rp 0</div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 4. PAJAK & KALKULASI TOTAL PAYMENT (K) --}}
                <div class="detail-block mb-4">
                    <div class="section-title mb-2">4. Pajak, Pemotongan & Total Payment (K)</div>
                    <div class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label for="gross_up_g" class="form-label small fw-semibold">Gross Up (G)</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text">Rp</span>
                                <input type="number" step="0.01" name="gross_up_g" id="gross_up_g" class="form-control calc-input" placeholder="0">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label for="ppn_h" class="form-label small fw-semibold">PPN (H)</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text">Rp</span>
                                <input type="number" step="0.01" name="ppn_h" id="ppn_h" class="form-control calc-input" placeholder="0">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label for="dpp_wht_i" class="form-label small fw-semibold">DPP WHT (I)</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text">Rp</span>
                                <input type="number" step="0.01" name="dpp_wht_i" id="dpp_wht_i" class="form-control calc-input" placeholder="0">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label for="wht_j" class="form-label small fw-semibold">WHT (J)</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text">Rp</span>
                                <input type="number" step="0.01" name="wht_j" id="wht_j" class="form-control calc-input" placeholder="0">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="no_invoice_rpj" class="form-label small fw-semibold">No. Invoice RPJ</label>
                            <input type="text" name="no_invoice_rpj" id="no_invoice_rpj" class="form-control form-control-sm" placeholder="Nomor Invoice RPJ">
                        </div>
                        {{-- Calculated Total Payment (K) --}}
                        <div class="col-12 mt-2">
                            <div class="p-2 px-3 rounded border d-flex justify-content-between align-items-center bg-body-tertiary">
                                <div>
                                    <span class="small fw-semibold text-secondary">Total Payment (K)</span>
                                    <div class="small text-muted">[Otomatis: F + G + H - WHT]</div>
                                </div>
                                <div class="fs-4 fw-bold text-success" id="preview_total_payment_k">Rp 0</div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Action Buttons --}}
                <div class="d-flex justify-content-end gap-2">
                    <button type="reset" class="btn btn-outline-secondary btn-sm" id="btnResetForm">Reset Form</button>
                    <button type="submit" class="btn btn-brand btn-sm d-flex align-items-center gap-1" id="btnSubmitForm">
                        <span class="spinner-border spinner-border-sm d-none" id="submitSpinner"></span>
                        <svg id="submitIcon" xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path d="M15.854.146a.5.5 0 0 1 .11.54l-5.8 14.5a.5.5 0 0 1-.928.026L6.5 10.5 1.787 7.764a.5.5 0 0 1 .026-.928L16.313.036a.5.5 0 0 1 .541.11M6.71 9.79l2.84 4.025 4.606-11.516zm-1.04-1.04L1.87 6.45l11.516-4.606z"/></svg>
                        {{ $editRecord ? 'Perbarui Tagihan IBC' : 'Simpan Tagihan IBC' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

    {{-- Tabel Data Tagihan IBC Tersimpan --}}
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span class="fw-semibold">Daftar Tagihan Listrik IBC Tersimpan</span>
            <span class="small text-secondary">Data ter-update secara otomatis</span>
        </div>
        <div class="card-body">
            <table id="tagihan-ibc-table" class="display align-middle text-nowrap" style="width:100%">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>TYPE</th>
                        <th>Site ID</th>
                        <th>Periode</th>
                        <th>No. Faktur</th>
                        <th>DPP PPN (B)</th>
                        <th>Total Tagihan (F)</th>
                        <th>Total Payment (K)</th>
                        <th>Invoice BM</th>
                        <th>Tgl Bayar</th>
                        <th class="text-center" style="min-width: 140px;">Aksi</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>

    {{-- MODAL LIHAT DETAIL TAGIHAN --}}
    <div class="modal fade" id="modalDetailTagihan" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Rincian Tagihan Listrik IBC</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body" id="modalDetailBody">
                    <div class="text-center py-4 text-secondary">Memuat data rincian...</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
$(function () {
    const editRecord = @json($editRecord);
    const formUrl = @json($editRecord ? route('electricity.inbuilding.input-tagihan-ibc.update', $editRecord) : route('electricity.inbuilding.input-tagihan-ibc.store'));
    const formMethod = @json($editRecord ? 'PUT' : 'POST');
    const dataUrl = @json(route('electricity.inbuilding.input-tagihan-ibc.data'));
    const baseShowUrl = @json(url('electricity/inbuilding/input-tagihan-ibc'));

    // Format IDR Helper
    function fmtRupiah(num) {
        return 'Rp ' + new Intl.NumberFormat('id-ID').format(Math.round(num || 0));
    }

    // Auto Calculation Logic
    function recalculateTotals() {
        const b = parseFloat($('#dpp_ppn_b').val()) || 0;
        const c = parseFloat($('#ppj_pju_c').val()) || 0;
        const d = parseFloat($('#materai_d').val()) || 0;
        const e = parseFloat($('#others_e').val()) || 0;

        // F = B + C + D + E
        const totalF = b + c + d + e;
        $('#preview_total_tagihan_f').text(fmtRupiah(totalF));

        const g = parseFloat($('#gross_up_g').val()) || 0;
        const h = parseFloat($('#ppn_h').val()) || 0;
        const i = parseFloat($('#dpp_wht_i').val()) || 0;
        const j = parseFloat($('#wht_j').val()) || 0;

        // K = F + G + H - J (fallback: - I jika WHT 0)
        let totalK = (totalF + g + h) - j;
        if (i > 0 && j === 0) {
            totalK = (totalF + g + h) - i;
        }

        $('#preview_total_payment_k').text(fmtRupiah(Math.max(0, totalK)));
    }

    $('.calc-input').on('input change keyup', function () {
        recalculateTotals();
    });

    $('#btnResetForm').on('click', function () {
        setTimeout(recalculateTotals, 50);
    });

    // DataTable Initialization
    const table = new DataTable('#tagihan-ibc-table', {
        processing: true,
        serverSide: true,
        scrollX: true,
        ajax: dataUrl,
        columns: [
            { data: 'DT_RowIndex', name: 'no', orderable: false, searchable: false },
            { data: 'type', name: 'type' },
            { data: 'site_id', name: 'site_id' },
            { data: 'periode', name: 'periode' },
            { data: 'no_faktur_pajak', name: 'no_faktur_pajak' },
            { data: 'dpp_ppn_b', name: 'dpp_ppn_b', className: 'text-end' },
            { data: 'total_tagihan_f', name: 'total_tagihan_f', className: 'text-end' },
            { data: 'total_payment_k', name: 'total_payment_k', className: 'text-end' },
            { data: 'no_invoice_bm', name: 'no_invoice_bm' },
            { data: 'tgl_bayar_to_bm', name: 'tgl_bayar_to_bm', className: 'text-center' },
            { data: 'aksi', name: 'aksi', orderable: false, searchable: false }
        ],
        rowCallback: function (row, data) {
            $(row).find('td').each(function (index) {
                if (index > 0 && index < 10) {
                    $(this).addClass('clickable-detail').css('cursor', 'pointer');
                }
            });
        },
        order: [],
        pageLength: 10,
    });

    // AJAX Form Submission
    $('#formInputTagihanIbc').on('submit', function (e) {
        e.preventDefault();

        const $btn = $('#btnSubmitForm');
        const $spinner = $('#submitSpinner');
        const $icon = $('#submitIcon');

        $btn.prop('disabled', true);
        $spinner.removeClass('d-none');
        $icon.addClass('d-none');

        $.ajax({
            url: formUrl,
            method: formMethod,
            data: $(this).serialize() + (formMethod === 'PUT' ? '&_method=PUT' : ''),
            success: function (res) {
                $('#alert-container').html(`
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <strong>Berhasil!</strong> ${res.message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                `);

                if (editRecord) {
                    window.location.href = @json(route('electricity.inbuilding.input-tagihan-ibc.index'));
                    return;
                }
                $('#formInputTagihanIbc')[0].reset();
                recalculateTotals();
                table.ajax.reload(null, false);
                window.scrollTo({ top: 0, behavior: 'smooth' });
            },
            error: function (xhr) {
                let msg = 'Terjadi kesalahan saat menyimpan data.';
                if (xhr.responseJSON && xhr.responseJSON.errors) {
                    msg = Object.values(xhr.responseJSON.errors).map(e => e.join(', ')).join('<br>');
                } else if (xhr.responseJSON && xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                }

                $('#alert-container').html(`
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <strong>Gagal Menyimpan:</strong><br>${msg}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                `);
            },
            complete: function () {
                $btn.prop('disabled', false);
                $spinner.addClass('d-none');
                $icon.removeClass('d-none');
            }
        });
    });

    function esc(v) { return $('<div>').text(v == null || v === '' ? '-' : String(v)).html(); }

    if (editRecord) {
        Object.keys(editRecord).forEach(function (key) {
            const field = $('[name="' + key + '"]');
            if (!field.length || editRecord[key] === null) return;
            let value = editRecord[key];
            if (['tgl_faktur_pajak', 'tgl_bast', 'tgl_bayar_to_bm'].includes(key)) {
                value = String(value).substring(0, 10);
            }
            field.val(value);
        });
        recalculateTotals();
    }

    // Modal detail dibuka dari tombol Edit lama atau klik sel data.
    const modalDetail = new bootstrap.Modal('#modalDetailTagihan');

    $(document).on('click', '.btn-detail-tagihan, #tagihan-ibc-table tbody td.clickable-detail', function () {
        const id = $(this).data('id') || table.row($(this).closest('tr')).data().id;
        $('#modalDetailBody').html('<div class="text-center py-4 text-secondary">Memuat data rincian...</div>');
        modalDetail.show();

        $.ajax({
            url: baseShowUrl + '/' + id,
            method: 'GET',
            success: function (d) {
                const html = `
                <div class="detail-block">
                    <table class="table table-bordered table-sm mb-3">
                        <tr><td colspan="4" class="section-title">Identitas & Periode Tagihan</td></tr>
                        <tr>
                            <th>TYPE</th><td><span class="badge text-bg-secondary">${esc(d.type)}</span></td>
                            <th>Site ID</th><td><span class="badge text-bg-light border">${esc(d.site_id)}</span></td>
                        </tr>
                        <tr>
                            <th>Periode</th><td><strong>${esc(d.periode)}</strong></td>
                            <th>Tgl Input</th><td>${esc(d.created_at)}</td>
                        </tr>
                    </table>

                    <table class="table table-bordered table-sm mb-3">
                        <tr><td colspan="4" class="section-title">Dokumen Faktur, BAST & Meteran</td></tr>
                        <tr>
                            <th>No. Faktur Pajak</th><td>${esc(d.no_faktur_pajak)}</td>
                            <th>Tgl. Faktur Pajak</th><td>${esc(d.tgl_faktur_pajak)}</td>
                        </tr>
                        <tr>
                            <th>No. BAST</th><td>${esc(d.no_bast)}</td>
                            <th>Tgl. BAST</th><td>${esc(d.tgl_bast)}</td>
                        </tr>
                        <tr>
                            <th>Meter Awal</th><td>${esc(d.meter_awal)}</td>
                            <th>Meter Akhir</th><td>${esc(d.meter_akhir)}</td>
                        </tr>
                        <tr>
                            <th>No. Invoice BM</th><td>${esc(d.no_invoice_bm)}</td>
                            <th>Tgl. Bayar to BM</th><td>${esc(d.tgl_bayar_to_bm)}</td>
                        </tr>
                    </table>

                    <table class="table table-bordered table-sm mb-0">
                        <tr><td colspan="4" class="section-title">Komponen Biaya & Hasil Kalkulasi</td></tr>
                        <tr>
                            <th>DPP PPN (B)</th><td class="text-end">${esc(d.dpp_ppn_b)}</td>
                            <th>PPJ/PJU (C)</th><td class="text-end">${esc(d.ppj_pju_c)}</td>
                        </tr>
                        <tr>
                            <th>Materai (D)</th><td class="text-end">${esc(d.materai_d)}</td>
                            <th>Others (E)</th><td class="text-end">${esc(d.others_e)}</td>
                        </tr>
                        <tr>
                            <th>Total Tagihan (F)</th><td colspan="3" class="text-end fw-bold text-primary fs-6">${esc(d.total_tagihan_f)}</td>
                        </tr>
                        <tr>
                            <th>Gross Up (G)</th><td class="text-end">${esc(d.gross_up_g)}</td>
                            <th>PPN (H)</th><td class="text-end">${esc(d.ppn_h)}</td>
                        </tr>
                        <tr>
                            <th>DPP WHT (I)</th><td class="text-end">${esc(d.dpp_wht_i)}</td>
                            <th>WHT (J)</th><td class="text-end">${esc(d.wht_j)}</td>
                        </tr>
                        <tr>
                            <th>Total Payment (K)</th><td colspan="3" class="text-end fw-bold text-success fs-5">${esc(d.total_payment_k)}</td>
                        </tr>
                    </table>
                </div>`;
                $('#modalDetailBody').html(html);
            },
            error: function () {
                $('#modalDetailBody').html('<div class="alert alert-danger mb-0">Gagal memuat rincian data.</div>');
            }
        });
    });

    // Delete Tagihan
    $(document).on('click', '.btn-delete-tagihan', function () {
        const id = $(this).data('id');
        const site = $(this).data('site');

        if (!confirm(`Apakah Anda yakin ingin menghapus data tagihan IBC site ${site}?`)) {
            return;
        }

        $.ajax({
            url: baseShowUrl + '/' + id,
            method: 'DELETE',
            data: { _token: '{{ csrf_token() }}' },
            success: function (res) {
                $('#alert-container').html(`
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        ${res.message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                `);
                table.ajax.reload(null, false);
            },
            error: function () {
                alert('Gagal menghapus data.');
            }
        });
    });
});
</script>
@endpush
