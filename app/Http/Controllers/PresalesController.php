<?php

namespace App\Http\Controllers;

use App\Models\DocumentApproval;
use App\Models\DocumentCirculation;
use App\Services\PresalesDocumentNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\View\View;

class PresalesController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'in:waiting_approval,Pending,In Progress,Completed,Rejected'],
            'step' => ['nullable', 'integer', 'between:2,5'],
        ]);
        $search = trim($filters['search'] ?? '');
        $documents = DocumentCirculation::query()
            ->with('uploader')
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search) {
                $query->where('document_title', 'like', '%'.$search.'%')
                    ->orWhere('document_number', 'like', '%'.$search.'%')
                    ->orWhere('file_name', 'like', '%'.$search.'%');
            }))
            ->when(($filters['status'] ?? null) === 'waiting_approval', fn ($query) => $query
                ->whereIn('status', ['Pending', 'In Progress'])
                ->whereBetween('current_step', [2, 5]))
            ->when(isset($filters['status']) && $filters['status'] !== 'waiting_approval',
                fn ($query) => $query->where('status', $filters['status']))
            ->when(isset($filters['step']), fn ($query) => $query->where('current_step', $filters['step']))
            ->latest('id')
            ->paginate(15)->withQueryString();

        $roleStep = $this->roleStep();

        $stats = [
            'total' => DocumentCirculation::count(),
            'in_progress' => DocumentCirculation::whereIn('status', ['Pending', 'In Progress'])->count(),
            'completed' => DocumentCirculation::where('status', 'Completed')->count(),
            'rejected' => DocumentCirculation::where('status', 'Rejected')->count(),
            'waiting_approval' => DocumentCirculation::whereIn('status', ['Pending', 'In Progress'])->whereBetween('current_step', [2, 5])->count(),
            'action_required' => $roleStep === null ? 0 : DocumentCirculation::whereIn('status', ['Pending', 'In Progress'])->where('current_step', $roleStep)->count(),
        ];

        $byStep = DocumentCirculation::query()
            ->whereIn('status', ['Pending', 'In Progress'])
            ->whereBetween('current_step', [2, 5])
            ->selectRaw('current_step, count(*) as total')
            ->groupBy('current_step')
            ->pluck('total', 'current_step');

        return view('presales.index', [
            'documents' => $documents,
            'stats' => $stats,
            'byStep' => $byStep,
            'filters' => $filters,
            'roleStep' => $roleStep,
            'canUpload' => $this->canUpload(),
        ]);
    }

    public function create(): View
    {
        abort_unless($this->canUpload(), 403, 'Role Anda belum diberi akses upload Presales.');

        return view('presales.create');
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($this->canUpload(), 403, 'Role Anda belum diberi akses upload Presales.');

        $validated = $request->validate([
            'document_title' => ['required', 'string', 'max:255'],
            'document_number' => ['required', 'string', 'max:255'],
            'document_file' => ['required', 'file', 'mimes:pdf', 'max:10240'],
        ]);

        $file = $request->file('document_file');
        // Dokumen Presales bersifat internal. Simpan di disk privat dan
        // akseskan hanya melalui endpoint terautentikasi di bawah ini.
        $path = $file->store('document-circulation', 'private');

        $document = DB::transaction(function () use ($validated, $file, $path, $request): DocumentCirculation {
            $document = DocumentCirculation::create([
                'document_title' => $validated['document_title'],
                'document_number' => $validated['document_number'] ?? null,
                'file_name' => $file->getClientOriginalName(),
                'file_path' => $path,
                'status' => 'Pending',
                'current_step' => 2,
                'uploaded_by' => $request->user()->id,
                'uploaded_by_name' => $request->user()->name,
            ]);

            // Step 1 is an audit event as well: it makes upload time and actor
            // persist alongside every later manager decision.
            DocumentApproval::create([
                'document_id' => $document->id,
                'step' => 1,
                'approver_id' => $request->user()->id,
                'approver_name' => $request->user()->name,
                'action' => 'uploaded',
                'acted_at' => now(),
            ]);

            return $document;
        });

        return redirect()->route('presales.show', $document)
            ->with('success', 'Dokumen berhasil diupload dan menunggu Manager NOP.');
    }

    public function show(DocumentCirculation $document): View
    {
        $document->load(['uploader', 'approvals.approver']);

        return view('presales.show', [
            'document' => $document,
            'steps' => DocumentCirculation::STEPS,
            'canAct' => $this->canActOn($document),
            'roleStep' => $this->roleStep(),
        ]);
    }

    public function file(DocumentCirculation $document): StreamedResponse
    {
        abort_unless(Storage::disk('private')->exists($document->file_path), 404);

        return Storage::disk('private')->response($document->file_path, $document->file_name, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    public function updateStatus(
        Request $request,
        DocumentCirculation $document,
        PresalesDocumentNotificationService $notificationService,
    ): RedirectResponse
    {
        $validated = $request->validate([
            'action' => ['required', 'in:approve,reject'],
            'comments' => ['nullable', 'required_if:action,reject', 'string', 'max:2000'],
            'confirm_rejection' => ['exclude_unless:action,reject', 'required', 'accepted'],
        ]);

        $message = DB::transaction(function () use ($document, $request, $validated, $notificationService): string {
            $locked = DocumentCirculation::query()->lockForUpdate()->findOrFail($document->id);
            abort_unless($this->canActOn($locked), 403, 'Dokumen ini bukan pada tahap approval role Anda.');

            $step = (int) $locked->current_step;
            $action = $validated['action'] === 'approve' ? 'approved' : 'rejected';

            $approval = DocumentApproval::create([
                'document_id' => $locked->id,
                'step' => $step,
                'approver_id' => $request->user()->id,
                'approver_name' => $request->user()->name,
                'action' => $action,
                'comments' => $validated['comments'] ?? null,
                'acted_at' => now(),
            ]);

            if ($action === 'rejected') {
                $locked->update([
                    'status' => 'Rejected',
                    'rejected_reason' => $validated['comments'],
                ]);

                $notificationService->sendRejectionReminder($locked, $approval, $request->user());

                return 'Dokumen ditolak. Pihak terkait telah menerima notifikasi.';
            }

            $nextStep = min(5, $step + 1);
            $locked->update([
                'current_step' => $nextStep,
                'status' => $step >= 5 ? 'Completed' : 'In Progress',
                'rejected_reason' => null,
            ]);

            return $step >= 5
                ? 'Dokumen selesai disetujui seluruh manager.'
                : 'Approval tersimpan. Dokumen diteruskan ke '.DocumentCirculation::STEPS[$nextStep].'.';
        });

        return back()->with('success', $message);
    }

    private function canUpload(): bool
    {
        $user = auth()->user();
        if (!$user) return false;
        if ($user->hasRole('admin')) return true;

        return collect($user->getRoleNames())
            ->map(fn ($role) => strtolower((string) $role))
            ->contains(fn (string $role) => str_starts_with($role, 'manager_') || str_starts_with($role, 'manager '));
    }

    private function roleStep(): ?int
    {
        $user = auth()->user();
        if (!$user) return null;
        if ($user->hasRole('admin')) return null;

        foreach ($user->getRoleNames() as $role) {
            $normalized = strtolower(trim((string) $role));
            if (isset(DocumentCirculation::ROLE_TO_STEP[$normalized])) {
                return DocumentCirculation::ROLE_TO_STEP[$normalized];
            }
        }

        return null;
    }

    private function canActOn(DocumentCirculation $document): bool
    {
        $user = auth()->user();
        if (!$user || in_array($document->status, ['Completed', 'Rejected'], true)) return false;
        if ($user->hasRole('admin')) return true;

        return $this->roleStep() === (int) $document->current_step;
    }
}
