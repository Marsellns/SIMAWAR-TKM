@extends('layouts.app')

@section('title', $title.' — Electricity Centralized — SIMAWAR')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">Upload Excel {{ $title }}</h1>
        <a href="{{ url()->previous() }}" class="btn btn-outline-secondary btn-sm">Kembali</a>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">{{ $errors->first() }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    <div class="card">
        <div class="card-header"><h5 class="mb-0">Upload Data {{ $title }}</h5></div>
        <div class="card-body">
            <form method="POST" action="{{ $uploadRoute }}" enctype="multipart/form-data">
                @csrf
                @if ($type === 'payment')
                    <div class="row g-3 mb-3">
                        <div class="col-md-4"><label class="form-label fw-semibold">Status</label><select name="status" class="form-select form-select-sm" required><option value="Done">Done</option><option value="Pending">Pending</option></select></div>
                        <div class="col-md-4"><label class="form-label fw-semibold">Bulan</label><select name="bulan" class="form-select form-select-sm" required>@foreach($months as $number => $month)<option value="{{ $number }}">{{ $month }}</option>@endforeach</select></div>
                        <div class="col-md-4"><label class="form-label fw-semibold">Tahun</label><input type="number" name="tahun" class="form-control form-control-sm" value="{{ now()->year }}" min="2000" max="2100" required></div>
                    </div>
                @endif
                <div class="mb-3">
                    <label class="form-label fw-semibold">File Excel <span class="text-danger">*</span></label>
                    <input type="file" name="{{ $fileField }}" class="form-control form-control-sm" accept=".xls,.xlsx" required>
                    <small class="text-body-secondary">Format .xls atau .xlsx, maksimal 50 MB. Gunakan kolom sesuai template.</small>
                    @if ($type === 'listrik-all')
                        <small class="text-body-secondary d-block">Untuk tahun lain, ubah angka tahun pada semua header Flagging sebelum mengisi data.</small>
                    @endif
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <button type="submit" class="btn btn-brand btn-sm">Upload dan Perbarui</button>
                    <a href="{{ $templateRoute }}" class="btn btn-success btn-sm">Template Excel</a>
                    <a href="{{ url()->previous() }}" class="btn btn-outline-secondary btn-sm">Kembali</a>
                </div>
            </form>

            <hr class="my-4">
            <h6 class="fw-semibold mb-2">Format Template (Header Kolom)</h6>
            <div class="table-responsive">
                <table class="table table-sm table-bordered table-striped">
                    <thead class="table-light"><tr><th>No</th><th>Nama Kolom</th><th>Keterangan</th></tr></thead>
                    <tbody>
                    @foreach ($headers as $index => $header)
                        <tr><td>{{ $index + 1 }}</td><td>{{ $header }}</td><td>{{ in_array($header, ['ID Pelanggan', 'Site ID'], true) ? 'Isi minimal salah satu ID' : '' }}</td></tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="alert alert-info mt-3 mb-0"><strong>Catatan:</strong> Baris baru akan ditambahkan, dan entri dengan kunci yang sama akan diperbarui. Data lain tetap tersimpan. Pastikan struktur kolom mengikuti template.</div>
        </div>
    </div>
@endsection
