<?php

namespace App\Http\Controllers;

use App\Models\CombatSite;
use App\Models\SewaLahanRenewal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class InfrastructureSiteImageController extends Controller
{
    private const DIRECTORY = 'infrastructure-site-images';
    private const EXTENSIONS = ['webp', 'jpg', 'png'];

    public function show(Request $request): BinaryFileResponse
    {
        $siteCode = $this->siteCode($request);
        $disk = Storage::disk('private');

        foreach (self::EXTENSIONS as $extension) {
            $path = $this->path($siteCode, $extension);
            if ($disk->exists($path)) {
                $response = response()->file($disk->path($path), [
                    'X-Content-Type-Options' => 'nosniff',
                ]);
                $response->setPrivate();
                $response->headers->addCacheControlDirective('no-store');

                return $response;
            }
        }

        abort(404);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'site_code' => ['required', 'string', 'max:100'],
            'photo' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120', 'dimensions:max_width=6000,max_height=4000'],
        ]);
        $siteCode = Str::upper(trim($validated['site_code']));
        $this->ensureSiteExists($siteCode);

        $photo = $request->file('photo');
        $extension = match ($photo->getMimeType()) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => throw ValidationException::withMessages(['photo' => 'Gunakan gambar JPG, PNG, atau WebP.']),
        };
        $disk = Storage::disk('private');
        $stored = $photo->storeAs(self::DIRECTORY, basename($this->path($siteCode, $extension)), 'private');
        abort_unless($stored, 500, 'Foto site tidak dapat disimpan.');

        foreach (self::EXTENSIONS as $otherExtension) {
            if ($otherExtension !== $extension) {
                $disk->delete($this->path($siteCode, $otherExtension));
            }
        }

        return response()->json(['photo_url' => route('infrastruktur.site-image.show', ['site_code' => $siteCode])]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $siteCode = $this->siteCode($request);
        $this->ensureSiteExists($siteCode);
        $disk = Storage::disk('private');
        foreach (self::EXTENSIONS as $extension) {
            $disk->delete($this->path($siteCode, $extension));
        }

        return response()->json(['message' => 'Foto site dihapus.']);
    }

    private function siteCode(Request $request): string
    {
        $validated = $request->validate(['site_code' => ['required', 'string', 'max:100']]);

        return Str::upper(trim($validated['site_code']));
    }

    private function ensureSiteExists(string $siteCode): void
    {
        abort_unless(
            SewaLahanRenewal::query()->where('site_code', $siteCode)->exists()
                || CombatSite::query()->where('site_code', $siteCode)->exists(),
            404,
            'Site tidak ditemukan.'
        );
    }

    private function path(string $siteCode, string $extension): string
    {
        return self::DIRECTORY.'/'.hash('sha256', $siteCode).'.'.$extension;
    }
}
