@extends('layouts.app')

@section('title', 'Notifikasi - SIMAWAR')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h4 mb-0">Notifikasi</h1>
        </div>
        @if ($notifications->total() > 0)
            <form method="POST" action="{{ route('notifications.read-all') }}">
                @csrf
                <button type="submit" class="btn btn-outline-primary btn-sm">Tandai semua sudah dibaca</button>
            </form>
        @endif
    </div>

    <div class="card border-0 shadow-sm">
        <div class="list-group list-group-flush">
            @forelse ($notifications as $notification)
                @php($data = $notification->data)
                <a href="{{ route('notifications.read', $notification) }}"
                    class="list-group-item list-group-item-action px-4 py-3 text-decoration-none text-body {{ $notification->read_at ? '' : 'notification-unread' }}">
                    <div class="d-flex gap-3">
                        <span class="notification-icon text-warning" aria-hidden="true">!</span>
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between gap-3">
                                <strong>{{ $data['title'] ?? 'Notifikasi' }}</strong>
                                <small class="text-muted text-nowrap">{{ $notification->created_at->diffForHumans() }}</small>
                            </div>
                            <div class="text-muted small mt-1">{{ $data['message'] ?? '' }}</div>
                        </div>
                    </div>
                </a>
            @empty
                <div class="text-center text-muted py-5">Belum ada notifikasi.</div>
            @endforelse
        </div>
    </div>

    <div class="mt-3">{{ $notifications->links() }}</div>
@endsection
