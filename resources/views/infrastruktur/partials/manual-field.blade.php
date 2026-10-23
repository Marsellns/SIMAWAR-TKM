@php
    [$label, $type, $length] = $field;
    $path = $prefix ?? '';
    $inputName = $path === '' ? $key : $path.'['.$key.']';
    $oldKey = $path === '' ? $key : $path.'.'.$key;
    $inputId = 'manual-'.str_replace('.', '-', $oldKey);
    $default = $key === 'ownership' && request('filter_field') === 'ownership' && in_array(request('filter_value'), ['Telkomsel', 'TP'], true) ? request('filter_value') : null;
@endphp
<div class="{{ $type === 'textarea' ? 'col-12' : 'col-md-6' }}">
    <label for="{{ $inputId }}" class="form-label">{{ $label }} @if ($key === 'site_code')<span class="text-danger">*</span>@endif</label>
    @if ($type === 'textarea')
        <textarea id="{{ $inputId }}" name="{{ $inputName }}" class="form-control @error($oldKey) is-invalid @enderror" rows="2" maxlength="{{ $length }}">{{ old($oldKey) }}</textarea>
    @elseif ($type === 'ownership')
        <select id="{{ $inputId }}" name="{{ $inputName }}" class="form-select @error($oldKey) is-invalid @enderror">
            <option value="">— pilih —</option>
            @foreach (['Telkomsel', 'TP'] as $ownership)
                <option value="{{ $ownership }}" @selected(old($oldKey, $default) === $ownership)>{{ $ownership }}</option>
            @endforeach
        </select>
    @elseif ($type === 'checkbox')
        <select id="{{ $inputId }}" name="{{ $inputName }}" class="form-select"><option value="0">Tidak</option><option value="1" @selected(old($oldKey) == 1)>Ya</option></select>
    @else
        <input type="{{ in_array($type, ['year', 'integer', 'coordinate'], true) ? 'number' : $type }}" id="{{ $inputId }}" name="{{ $inputName }}"
               value="{{ old($oldKey) }}" class="form-control @error($oldKey) is-invalid @enderror"
               @required($key === 'site_code') @if ($length) maxlength="{{ $length }}" @endif
               @if ($type === 'number') step="0.01" min="0" @elseif ($type === 'coordinate') step="any" min="{{ $key === 'latitude' ? -90 : -180 }}" max="{{ $key === 'latitude' ? 90 : 180 }}" @elseif ($type === 'year') min="2000" max="2100" step="1" @elseif ($type === 'integer') min="0" step="1" @endif>
    @endif
    @error($oldKey)<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
