<form method="POST" action="{{ route('infrastruktur.sewa-lahan.update', $sewaLahan) }}" data-sewa-edit-form>
    @csrf
    @method('PUT')
    <div class="alert alert-danger d-none" role="alert" data-sewa-form-errors></div>
    @include('infrastruktur.sewa-lahan.partials.form-fields')
    <div class="d-flex flex-wrap gap-2 justify-content-end mt-4">
        <button type="button" class="btn btn-outline-secondary" data-sewa-cancel-edit>Kembali ke detail</button>
        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
    </div>
</form>

