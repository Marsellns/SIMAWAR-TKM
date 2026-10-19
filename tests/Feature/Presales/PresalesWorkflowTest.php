<?php

namespace Tests\Feature\Presales;

use App\Models\DocumentCirculation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesUsersWithRoles;
use Tests\TestCase;

class PresalesWorkflowTest extends TestCase
{
    use CreatesUsersWithRoles;
    use RefreshDatabase;

    public function test_presales_upload_and_manager_approval_flow(): void
    {
        Storage::fake('private');
        $admin = $this->userWithRole('admin');
        $this->actingAs($admin)->get('/po-monitoring/document-circulation')
            ->assertRedirect(route('presales.index'));

        $this->actingAs($admin)->post(route('presales.store'), [
            'document_title' => 'Dokumen Presales Uji',
            'document_number' => 'PRE-001',
            'document_file' => UploadedFile::fake()->create('presales.pdf', 10, 'application/pdf'),
        ])->assertRedirect();

        $document = DocumentCirculation::firstOrFail();
        $this->assertSame('Pending', $document->status);
        $this->assertSame(2, $document->current_step);
        Storage::disk('private')->assertExists($document->file_path);
        $this->assertDatabaseHas('document_approvals', [
            'document_id' => $document->id,
            'step' => 1,
            'approver_id' => $admin->id,
            'approver_name' => $admin->name,
            'action' => 'uploaded',
        ]);
        $this->actingAs($admin)->get(route('presales.index'))
            ->assertOk()->assertSee('Dokumen Presales Uji');
        $this->actingAs($admin)->get(route('presales.show', $document))
            ->assertOk()->assertSee('Riwayat waktu')->assertSee('WIB');

        foreach (['manager_nop', 'manager_sq', 'manager_nos', 'manager_nbae'] as $role) {
            $manager = $this->userWithRole($role);
            $this->actingAs($manager)->post(route('presales.status', $document), [
                'action' => 'approve',
            ])->assertRedirect();
        }

        $document->refresh();
        $this->assertSame('Completed', $document->status);
        $this->assertSame(5, $document->approvals()->count());
        $this->actingAs($admin)->get(route('presales.file', $document))->assertOk();
    }

    public function test_summary_and_stage_links_show_the_documents_behind_their_counts(): void
    {
        $admin = $this->userWithRole('admin');
        foreach ([
            ['Menunggu NOP', 'Pending', 2],
            ['Menunggu SQ', 'In Progress', 3],
            ['Ditolak NOP', 'Rejected', 2],
            ['Selesai NBAE', 'Completed', 5],
            ['Belum masuk tahap manager', 'Pending', 1],
        ] as [$title, $status, $step]) {
            DocumentCirculation::create([
                'document_title' => $title,
                'document_number' => 'PRE-'.$step.'-'.$status,
                'file_name' => 'presales.pdf',
                'file_path' => 'document-circulation/presales.pdf',
                'status' => $status,
                'current_step' => $step,
                'uploaded_by' => $admin->id,
                'uploaded_by_name' => $admin->name,
            ]);
        }

        $overview = $this->actingAs($admin)->get(route('presales.index'))->assertOk();
        $this->assertSame(5, $overview->viewData('stats')['total']);
        $this->assertSame(2, $overview->viewData('stats')['waiting_approval']);
        $this->assertSame(1, (int) $overview->viewData('byStep')[2]);
        $this->assertSame(1, (int) $overview->viewData('byStep')[3]);
        $overview->assertSee(route('presales.index', ['status' => 'waiting_approval']).'#presales-documents', false)
            ->assertSee('data-presales-detail-url=', false);

        $waiting = $this->get(route('presales.index', ['status' => 'waiting_approval']))->assertOk();
        $this->assertSame(2, $waiting->viewData('documents')->total());
        $waiting->assertSee('Menunggu NOP')->assertSee('Menunggu SQ')
            ->assertDontSee('Ditolak NOP')->assertDontSee('Belum masuk tahap manager');

        $nop = $this->get(route('presales.index', ['status' => 'waiting_approval', 'step' => 2]))->assertOk();
        $this->assertSame(1, $nop->viewData('documents')->total());
        $nop->assertSee('Menunggu NOP')->assertDontSee('Ditolak NOP')->assertDontSee('Menunggu SQ');

        $this->assertSame(1, $this->get(route('presales.index', ['status' => 'Completed']))
            ->assertOk()->viewData('documents')->total());
        $this->assertSame(1, $this->get(route('presales.index', ['status' => 'Rejected']))
            ->assertOk()->viewData('documents')->total());
    }

    public function test_presales_rejection_requires_reason_and_correct_manager(): void
    {
        $admin = $this->userWithRole('admin');
        $nop = $this->userWithRole('manager_nop');
        $sq = $this->userWithRole('manager_sq');
        $nos = $this->userWithRole('manager_nos');
        $nbae = $this->userWithRole('manager_nbae');
        $viewer = $this->userWithRole('viewer');
        $document = DocumentCirculation::create([
            'document_title' => 'Dokumen untuk penolakan',
            'document_number' => 'PRE-002',
            'file_name' => 'presales.pdf',
            'file_path' => 'document-circulation/presales.pdf',
            'status' => 'Pending',
            'current_step' => 2,
            'uploaded_by' => $admin->id,
            'uploaded_by_name' => $admin->name,
        ]);

        $this->actingAs($sq)->post(route('presales.status', $document), [
            'action' => 'approve',
        ])->assertForbidden();
        $this->actingAs($nop)->post(route('presales.status', $document), [
            'action' => 'reject',
        ])->assertSessionHasErrors('comments');
        $this->actingAs($nop)->post(route('presales.status', $document), [
            'action' => 'reject',
            'comments' => 'Nomor dokumen perlu diperbaiki.',
        ])->assertSessionHasErrors('confirm_rejection');
        $this->actingAs($nop)->post(route('presales.status', $document), [
            'action' => 'reject',
            'comments' => 'Nomor dokumen perlu diperbaiki.',
            'confirm_rejection' => '1',
        ])->assertRedirect();

        $document->refresh();
        $this->assertSame('Rejected', $document->status);
        $this->assertSame('Nomor dokumen perlu diperbaiki.', $document->rejected_reason);
        $this->assertSame(1, $admin->fresh()->unreadNotifications()->count());
        $this->assertSame(1, $sq->fresh()->unreadNotifications()->count());
        $this->assertSame(1, $nos->fresh()->unreadNotifications()->count());
        $this->assertSame(1, $nbae->fresh()->unreadNotifications()->count());
        $this->assertSame(0, $nop->fresh()->notifications()->count());
        $this->assertSame(0, $viewer->fresh()->notifications()->count());
        $notification = $admin->fresh()->unreadNotifications()->firstOrFail();
        $this->assertSame('presales_document_rejected', $notification->data['type']);
        $this->assertSame($document->id, $notification->data['document_id']);
        $this->assertSame('Nomor dokumen perlu diperbaiki.', $notification->data['rejection_reason']);
        $this->actingAs($nop)->post(route('presales.status', $document), [
            'action' => 'approve',
        ])->assertForbidden();
        $this->assertSame(1, $document->approvals()->count());
    }
}
