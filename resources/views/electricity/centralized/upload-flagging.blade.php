@extends('layouts.app')

@section('title', 'Upload Excel Tagihan Listrik Centralized — SIMAWAR')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">Upload Excel Tagihan Listrik Centralized</h1>
        <a href="{{ url()->previous() }}" class="btn btn-outline-secondary btn-sm">← Kembali</a>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Upload Data Flagging (Listrik PLN)</h5>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('electricity.centralized.upload-flagging.store') }}" enctype="multipart/form-data">
                @csrf

                <div class="row g-3 mb-3">
                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold">File Excel <span class="text-danger">*</span></label>
                        <input type="file" name="file" class="form-control form-control-sm" accept=".xls,.xlsx" required>
                        <small class="text-body-secondary">Format: .xls atau .xlsx, maks 20 MB. Header kolom harus sesuai template.</small>
                    </div>
                </div>

                <div class="d-flex flex-wrap gap-2">
                    <button type="submit" class="btn btn-brand btn-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16" class="me-1"><path d="M.5 9.9a.5.5 0 0 1 .5.1v2.5A1.5 1.5 0 0 0 2.5 14h11a1.5 1.5 0 0 0 1.5-1.5V10a.5.5 0 0 1 1 0v2.5a2.5 2.5 0 0 1-2.5 2.5h-11A2.5 2.5 0 0 1 0 12.5V10a.5.5 0 0 1 .5-.1"/><path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708z"/></svg>
                        Upload
                    </button>
                    <a href="{{ route('electricity.centralized.template-flagging') }}" class="btn btn-success btn-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16" class="me-1"><path d="M12 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2zM4 1h8a1 1 0 0 1 1 1v10a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1z"/><path d="M8 4a.5.5 0 0 1 .5.5v3.793l1.146-1.147a.5.5 0 0 1 .708.708l-2 2a.5.5 0 0 1-.708 0l-2-2a.5.5 0 1 1 .708-.708L7.5 8.293V4.5A.5.5 0 0 1 8 4z"/></svg>
                        Template Excel
                    </a>
                    <a href="{{ url()->previous() }}" class="btn btn-outline-secondary btn-sm">← Kembali</a>
                </div>
            </form>

            <hr class="my-4">

            <h6 class="fw-semibold mb-2">Format Template (Header Kolom)</h6>
            <div class="table-responsive">
                <table class="table table-sm table-bordered table-striped">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>Nama Kolom</th>
                            <th>Tipe Data</th>
                            <th>Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td>1</td><td>ID Pelanggan</td><td>String</td><td>Wajib diisi, unik per baris</td></tr>
                        <tr><td>2</td><td>Site ID</td><td>String</td><td></td></tr>
                        <tr><td>3</td><td>Site Name</td><td>String</td><td></td></tr>
                        <tr><td>4</td><td>Alamat</td><td>Text</td><td></td></tr>
                        <tr><td>5</td><td>Status Aktif Site</td><td>Enum</td><td>Aktif / Tidak Aktif</td></tr>
                        <tr><td>6</td><td>Nama Pelanggan</td><td>String</td><td></td></tr>
                        <tr><td>7</td><td>Daya (VA)</td><td>Integer</td><td>Contoh: 1300, 2200, 5500</td></tr>
                        <tr><td>8</td><td>Phasa</td><td>Enum</td><td>1 Phasa / 3 Phasa</td></tr>
                        <tr><td>9</td><td>Status AMR</td><td>Enum</td><td>Ada / Tidak Ada</td></tr>
                        <tr><td>10</td><td>Gol Tarif</td><td>String</td><td></td></tr>
                        <tr><td>11</td><td>Unit Layanan PLN</td><td>String</td><td></td></tr>
                        <tr><td>12</td><td>Jenis Bayar</td><td>Enum</td><td>Prabayar / Pascabayar</td></tr>
                        <tr><td>13</td><td>TP Owner</td><td>String</td><td></td></tr>
                        <tr><td>14</td><td>NOP</td><td>String</td><td>Nomor Objek Pajak</td></tr>
                        <tr><td>15</td><td>Update By</td><td>String</td><td>Nama user yang update</td></tr>
                        <tr><td>16</td><td>Tanggal</td><td>Date</td><td>Format: YYYY-MM-DD atau DD-MM-YYYY</td></tr>
                    </tbody>
                </table>
            </div>

            <div class="alert alert-info mt-3">
                <strong>Catatan:</strong>
                <ul class="mb-0 mt-2">
                    <li>Baris pertama file Excel harus berisi header kolom (persis seperti tabel di atas).</li>
                    <li>Data dimulai dari baris ke-2.</li>
                    <li>Kolom <strong>ID Pelanggan</strong> wajib diisi — baris tanpa ID Pelanggan akan dilewati.</li>
                    <li>Upload bersifat <strong>upsert</strong>: data dengan ID Pelanggan yang sama akan diupdate, yang baru akan diinsert.</li>
                    <li>Enum tidak case-sensitive; nilai tidak valid akan diabaikan (dibuat null).</li>
                    <li>Tanggal: format yang didukung YYYY-MM-DD, DD-MM-YYYY, DD/MM/YYYY, serial Excel.</li>
                </ul>
            </div>
        </div>
    </div>
@endsection