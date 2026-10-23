@extends('layouts.app')
@section('title', 'Tambah Data '.$definition[1].' — SIMAWAR')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3"><h1 class="h4">Tambah Data {{ $definition[1] }}</h1><a href="{{ route($definition[2]) }}" class="btn btn-outline-secondary">Kembali</a></div>
@if ($errors->any())<div class="alert alert-danger" role="alert"><strong>Data belum tersimpan.</strong><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<div class="card mb-3"><div class="card-body">
    <h2 class="h5">Upload Excel / CSV</h2><p class="text-muted">Gunakan template ini. Identitas yang sama diperbarui; data lain tetap tersedia. Unggahan ditolak seluruhnya jika ada baris yang tidak valid.</p>
    @if ($dataset === 'pnl')<p class="small text-muted">Template ini berisi satu baris per site, bulan, dan tahun. Format Excel P&amp;L dengan banyak kolom bulan tetap dapat diunggah melalui menu Upload Excel pada halaman Profit &amp; Loss.</p>@endif
    <form method="POST" action="{{ route('operations.upload', $dataset) }}" enctype="multipart/form-data" class="d-flex flex-wrap gap-2 align-items-center">@csrf
        <label class="visually-hidden" for="dataset-file">File Excel atau CSV</label><input id="dataset-file" type="file" name="dataset_file" accept=".xlsx,.xls,.csv" class="form-control w-auto" required>
        <button class="btn btn-brand">Upload Excel</button><a class="btn btn-success" href="{{ route('operations.template', $dataset) }}">Template Excel</a>
    </form>
</div></div>
<div class="card"><div class="card-body"><h2 class="h5 mb-3">Tambah Manual</h2>
@if ($dataset === 'input-ibc')<p class="alert alert-info">Isi nomor invoice untuk menambah atau mengoreksi invoice tertentu. Tanpa nomor invoice, input menjadi total tagihan site pada periode tersebut. Tanggal bayar menentukan status Done; tanpa tanggal bayar berstatus Pending.</p>@endif
<form method="POST" action="{{ route('operations.store', $dataset) }}">@csrf<div class="row g-3">
@foreach ($fields as $name => $field)
<div class="col-md-6"><label class="form-label" for="entry-{{ $name }}">{{ $field['label'] }} @if ($field['required'])<span class="text-danger">*</span>@endif</label>
@if ($field['choices'])<select id="entry-{{ $name }}" name="{{ $name }}" class="form-select" @required($field['required'])><option value="">Pilih {{ $field['label'] }}</option>@foreach ($field['choices'] as $choice)<option value="{{ $choice }}" @selected(old($name) === $choice)>{{ $choice }}</option>@endforeach</select>
@else<input id="entry-{{ $name }}" name="{{ $name }}" class="form-control" type="{{ $name === 'periode' ? 'month' : $field['type'] }}" value="{{ old($name) }}" @if ($field['type'] === 'number') @if ($dataset !== 'pnl' || in_array($name, ['bulan', 'tahun'])) min="0" @endif step="any" @endif @required($field['required'])>@endif
</div>
@endforeach
</div><button class="btn btn-brand mt-3">Simpan Data</button></form>
</div></div>
@endsection
@push('scripts')
<script>
    document.querySelectorAll('form[action*="/operations/"]').forEach(form => {
        form.addEventListener('submit', event => {
            if (form.dataset.submitting) { event.preventDefault(); return; }
            form.dataset.submitting = 'true';
            form.querySelectorAll('button[type="submit"], button:not([type])').forEach(button => { button.disabled = true; });
        });
    });
    window.addEventListener('pageshow', () => {
        document.querySelectorAll('form[data-submitting]').forEach(form => {
            delete form.dataset.submitting;
            form.querySelectorAll('button[type="submit"], button:not([type])').forEach(button => { button.disabled = false; });
        });
    });
</script>
@endpush
