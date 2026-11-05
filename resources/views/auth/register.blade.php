<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#143c34">
    <title>Buat Akun — SIMAWAR</title>
    <link href="{{ asset('css/auth.css') }}" rel="stylesheet">
</head>
<body class="simawar-auth-page">
    <main class="simawar-auth-shell">
        @include('auth.partials.login-hero')

        <section class="simawar-auth-form-side" aria-labelledby="register-title">
            <div class="simawar-auth-form-wrap">
                <div class="simawar-auth-index"><span>AKSES SISTEM</span><span>SIMAWAR / 02</span></div>
                <div class="simawar-auth-intro simawar-auth-intro-register">
                    <p class="simawar-auth-kicker">PENDAFTARAN AKUN</p>
                    <h1 id="register-title">Mulai dari sini.</h1>
                    <p>Isi data diri untuk mengajukan akses ke SIMAWAR. Permintaan Anda akan ditinjau oleh admin.</p>
                </div>

                @if ($errors->any())
                    <div class="simawar-auth-alert simawar-auth-alert-error" role="alert">{{ $errors->first() }}</div>
                @endif

                <form method="POST" action="{{ route('register') }}" class="simawar-auth-form">
                    @csrf
                    <div class="simawar-auth-field">
                        <label for="name">Nama lengkap</label>
                        <input type="text" id="name" name="name" value="{{ old('name') }}"
                               autocomplete="name" placeholder="Masukkan nama lengkap"
                               maxlength="255" @if ($errors->has('name')) aria-invalid="true" @endif required autofocus>
                    </div>
                    <div class="simawar-auth-field">
                        <label for="email">Email akun</label>
                        <input type="email" id="email" name="email" value="{{ old('email') }}"
                               autocomplete="email" inputmode="email" placeholder="nama@email.com"
                               maxlength="255" @if ($errors->has('email')) aria-invalid="true" @endif required>
                    </div>
                    <div class="simawar-auth-field">
                        <label for="requested_role">Role yang diminta</label>
                        <select id="requested_role" name="requested_role" @if ($errors->has('requested_role')) aria-invalid="true" @endif required>
                            @foreach ($roles as $role => $label)
                                <option value="{{ $role }}" @selected(old('requested_role', 'viewer') === $role)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="simawar-auth-field">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password"
                               autocomplete="new-password" placeholder="Buat password"
                               minlength="12" aria-describedby="password-hint"
                               @if ($errors->has('password')) aria-invalid="true" @endif required>
                        <p class="simawar-auth-field-hint" id="password-hint">Minimal 12 karakter, termasuk huruf besar, angka, dan simbol.</p>
                    </div>
                    <div class="simawar-auth-field">
                        <label for="password_confirmation">Konfirmasi password</label>
                        <input type="password" id="password_confirmation" name="password_confirmation"
                               autocomplete="new-password" placeholder="Ulangi password" minlength="12" required>
                    </div>
                    <button type="submit" class="simawar-auth-submit">
                        <span>Ajukan akses SIMAWAR</span><span aria-hidden="true">↗</span>
                    </button>
                </form>

                <p class="simawar-auth-register">Sudah memiliki akun? <a href="{{ route('login') }}">Kembali ke login</a></p>
                <p class="simawar-auth-note">Akun dapat digunakan setelah permintaan akses disetujui admin.</p>
            </div>
        </section>
    </main>
</body>
</html>
