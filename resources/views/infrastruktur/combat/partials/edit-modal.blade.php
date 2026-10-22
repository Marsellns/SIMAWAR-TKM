<form method="POST" action="{{ route('infrastruktur.combat.update', $combat) }}" data-combat-edit-form>
    @csrf
    @method('PUT')
    <div class="alert alert-danger d-none" role="alert" data-combat-form-errors></div>
    @include('infrastruktur.combat.partials.form-fields')
    <div class="d-flex justify-content-end gap-2 mt-4">
        <button type="button" class="btn btn-outline-secondary" data-combat-cancel-edit>Batal</button>
        <button type="submit" class="btn btn-brand">Simpan Perubahan</button>
    </div>
</form>
