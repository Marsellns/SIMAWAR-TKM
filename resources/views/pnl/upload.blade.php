@extends('layouts.app')

@section('title', 'Upload Excel Profit & Loss — SIMAWAR')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">Upload Excel Profit &amp; Loss</h1>
        <a href="{{ route('pnl.index') }}" class="btn btn-outline-secondary btn-sm">← Kembali</a>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif

    @if ($errors->has('pnl_file'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ $errors->first('pnl_file') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Upload Data PnL</h5>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('pnl.upload.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="row g-3 mb-3">
                    <div class="col-12 col-md-7">
                        <label for="pnl-file" class="form-label fw-semibold">File Excel <span class="text-danger">*</span></label>
                        <input id="pnl-file" type="file" name="pnl_file" class="form-control form-control-sm" accept=".xls,.xlsx" required>
                        <small class="text-body-secondary">Format .xls atau .xlsx, maksimal 50 MB. Header dimulai dari baris ke-2.</small>
                    </div>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <button type="submit" class="btn btn-brand btn-sm">Upload Excel</button>
                    <a href="{{ route('pnl.template') }}" class="btn btn-success btn-sm">Template Excel</a>
                    <a href="{{ route('pnl.index') }}" class="btn btn-outline-secondary btn-sm">← Kembali</a>
                </div>
            </form>

            <hr class="my-4">
            <h6 class="fw-semibold mb-2">Petunjuk Format</h6>
            <div class="alert alert-info mb-0">
                <ul class="mb-0">
                    <li>Gunakan tombol <strong>Template Excel</strong> untuk mengunduh format resmi PnL.</li>
                    <li>Baris pertama adalah judul; header kolom berada di baris ke-2.</li>
                    <li>Kolom <strong>Site ID</strong> wajib diisi.</li>
                    <li>Data dengan Site ID dan periode yang sama akan diperbarui, sedangkan data baru akan ditambahkan.</li>
                    <li>Gunakan format kolom sesuai template. Periode baru dapat ditambahkan dengan kolom Rev dan Cost dalam format seperti <strong>Rev Jul-26</strong> dan <strong>Cost Jul-26</strong>.</li>
                </ul>
            </div>
        </div>
    </div>
@endsection
