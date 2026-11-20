{{-- Rendered inside the authenticated Filament OperationalReport page. --}}
@include('reports.styles')
@stack('styles')
@if (session('success'))
    <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
    </div>
@endif
@yield('content')
@include('reports.scripts')
@stack('scripts')
