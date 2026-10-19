@extends('layouts.app')

@section('title', 'Upload '.$definition['label'].' — SIMAWAR')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0">Upload {{ $definition['label'] }}</h1>
    <a href="{{ route('infrastruktur.'.$dataset.'.index') }}" class="btn btn-outline-secondary btn-sm">← Kembali</a>
</div>
@if ($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif
<div class="card">
 <div class="card-body">
  <form method="POST" enctype="multipart/form-data" action="{{ route('infrastruktur.upload.store', $dataset) }}">
   @csrf
   <label class="form-label fw-semibold" for="dataset-file">File Excel/CSV <span class="text-danger">*</span></label>
   <input id="dataset-file" type="file" name="dataset_file" class="form-control" accept="{{ in_array($dataset, ['sewa-lahan', 'combat'], true) ? '.xlsx,.xls,.xlsm,.csv' : '.xlsx,.xls,.csv' }}" required>
   <small class="text-body-secondary d-block mt-2">Maksimal 50 MB. Gunakan format kolom dataset yang tersedia.</small>
   <div class="mt-3 d-flex gap-2">
    <button class="btn btn-brand" type="submit">Upload dan simpan</button>
    <a class="btn btn-outline-primary" href="{{ route('infrastruktur.upload.template', $dataset) }}">Template Excel</a>
    <a class="btn btn-outline-secondary" href="{{ route('infrastruktur.'.$dataset.'.index') }}">Batal</a>
   </div>
  </form>
  <div class="alert alert-info mt-4 mb-0">@if ($dataset === 'bapss') Upload BAPSS mengganti data modul ini. @elseif (in_array($dataset, ['sewa-lahan', 'combat'], true)) Workbook Rebuild mengganti seluruh data {{ $definition['label'] }} dari sheet aktifnya dan mencadangkan data sebelumnya. File template biasa menambahkan atau memperbarui entri. @elseif ($dataset === 'recurring-tagihan-ipas') Setiap baris Excel adalah satu rincian termin yang disimpan di tabel rincian dan dirangkum otomatis per Site ID. Isi kolom Recurring bila tersedia agar kontrak paralel dapat dibedakan saat upload ulang. Gunakan Rincian CSV untuk menyimpan metadata sumber; file Excel ringkasan per site tidak dapat diunggah sebagai rincian. @else Upload menambahkan data baru dan memperbarui entri dengan kunci yang sama. Data lain tetap tersimpan. @endif Baris tanpa Site ID dilewati.</div>
 </div>
</div>
@endsection
