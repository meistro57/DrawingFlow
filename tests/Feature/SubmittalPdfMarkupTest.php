<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\DrawingRequest;
use App\Models\DrawingSubmittal;
use App\Models\PdfMarkup;
use App\Models\Project;
use App\Models\SubmittalFile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SubmittalPdfMarkupTest extends TestCase
{
    use RefreshDatabase;

    public function test_submittal_pdf_can_be_viewed_inline_and_downloaded(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        [$submittal, $submittalFile] = $this->createSubmittalWithFile($user);

        $this->actingAs($user)
            ->get(route('submittals.files.view', [$submittal, $submittalFile]))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->actingAs($user)
            ->get(route('submittals.files.download', [$submittal, $submittalFile]))
            ->assertDownload('shop-drawing.pdf');
    }

    public function test_markups_can_be_listed_for_submittal_pdf_file(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        [$submittal, $submittalFile] = $this->createSubmittalWithFile($user);

        PdfMarkup::create([
            'submittal_file_id' => $submittalFile->id,
            'user_id' => $user->id,
            'page_number' => 1,
            'markup_type' => 'circle',
            'markup_data' => [
                'x' => 10,
                'y' => 20,
                'width' => 15,
                'height' => 10,
                'color' => '#ef4444',
            ],
        ]);

        $this->actingAs($user)
            ->getJson(route('submittals.files.markups.index', [$submittal, $submittalFile]))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.markup_type', 'circle')
            ->assertJsonPath('data.0.user.id', $user->id);
    }

    public function test_markup_can_be_saved_for_submittal_pdf_file(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        [$submittal, $submittalFile] = $this->createSubmittalWithFile($user);

        $this->actingAs($user)
            ->postJson(route('submittals.files.markups.store', [$submittal, $submittalFile]), [
                'page_number' => 2,
                'markup_type' => 'highlight',
                'markup_data' => [
                    'x' => 20,
                    'y' => 10,
                    'width' => 30,
                    'height' => 15,
                    'color' => '#fde047',
                ],
            ])
            ->assertCreated()
            ->assertJsonPath('data.markup_type', 'highlight')
            ->assertJsonPath('data.page_number', 2)
            ->assertJsonPath('data.user.id', $user->id);

        $this->assertDatabaseHas('pdf_markups', [
            'submittal_file_id' => $submittalFile->id,
            'user_id' => $user->id,
            'page_number' => 2,
            'markup_type' => 'highlight',
        ]);
    }

    public function test_markup_export_returns_json_download(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        [$submittal, $submittalFile] = $this->createSubmittalWithFile($user);

        PdfMarkup::create([
            'submittal_file_id' => $submittalFile->id,
            'user_id' => $user->id,
            'page_number' => 1,
            'markup_type' => 'arrow',
            'markup_data' => [
                'x' => 10,
                'y' => 10,
                'x2' => 35,
                'y2' => 30,
            ],
        ]);

        $this->actingAs($user)
            ->get(route('submittals.files.markups.export', [$submittal, $submittalFile]))
            ->assertOk()
            ->assertHeader('content-type', 'application/json')
            ->assertHeader('content-disposition', 'attachment; filename=markups-'.$submittalFile->id.'.json');
    }

    public function test_stamp_markup_can_be_saved_for_submittal_pdf_file(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        [$submittal, $submittalFile] = $this->createSubmittalWithFile($user);

        $this->actingAs($user)
            ->postJson(route('submittals.files.markups.store', [$submittal, $submittalFile]), [
                'page_number' => 1,
                'markup_type' => 'stamp',
                'markup_data' => [
                    'x' => 55,
                    'y' => 25,
                    'label' => 'APPROVED',
                    'color' => '#b91c1c',
                ],
            ])
            ->assertCreated()
            ->assertJsonPath('data.markup_type', 'stamp');

        $this->assertDatabaseHas('pdf_markups', [
            'submittal_file_id' => $submittalFile->id,
            'user_id' => $user->id,
            'markup_type' => 'stamp',
        ]);
    }

    public function test_dimension_markup_can_be_saved_for_submittal_pdf_file(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        [$submittal, $submittalFile] = $this->createSubmittalWithFile($user);

        $this->actingAs($user)
            ->postJson(route('submittals.files.markups.store', [$submittal, $submittalFile]), [
                'page_number' => 1,
                'markup_type' => 'dimension',
                'markup_data' => [
                    'x' => 15,
                    'y' => 25,
                    'x2' => 60,
                    'y2' => 25,
                    'label' => '45\'-0"',
                    'color' => '#2563eb',
                    'stroke_width' => 1.4,
                ],
            ])
            ->assertCreated()
            ->assertJsonPath('data.markup_type', 'dimension')
            ->assertJsonPath('data.markup_data.label', '45\'-0"');
    }

    public function test_cloud_markup_can_be_saved_for_submittal_pdf_file(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        [$submittal, $submittalFile] = $this->createSubmittalWithFile($user);

        $this->actingAs($user)
            ->postJson(route('submittals.files.markups.store', [$submittal, $submittalFile]), [
                'page_number' => 3,
                'markup_type' => 'cloud',
                'markup_data' => [
                    'x' => 18,
                    'y' => 12,
                    'width' => 32,
                    'height' => 20,
                    'color' => '#9333ea',
                    'comment' => 'Coordination cloud',
                ],
            ])
            ->assertCreated()
            ->assertJsonPath('data.markup_type', 'cloud');
    }

    public function test_pen_markup_can_be_saved_for_submittal_pdf_file(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        [$submittal, $submittalFile] = $this->createSubmittalWithFile($user);

        $this->actingAs($user)
            ->postJson(route('submittals.files.markups.store', [$submittal, $submittalFile]), [
                'page_number' => 1,
                'markup_type' => 'pen',
                'markup_data' => [
                    'points' => [
                        ['x' => 10, 'y' => 12],
                        ['x' => 12, 'y' => 18],
                        ['x' => 16, 'y' => 24],
                    ],
                    'color' => '#0f766e',
                ],
            ])
            ->assertCreated()
            ->assertJsonPath('data.markup_type', 'pen')
            ->assertJsonPath('data.markup_data.points.2.x', 16);
    }

    public function test_polygon_markup_can_be_saved_for_submittal_pdf_file(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        [$submittal, $submittalFile] = $this->createSubmittalWithFile($user);

        $this->actingAs($user)
            ->postJson(route('submittals.files.markups.store', [$submittal, $submittalFile]), [
                'page_number' => 2,
                'markup_type' => 'polygon',
                'markup_data' => [
                    'points' => [
                        ['x' => 20, 'y' => 20],
                        ['x' => 45, 'y' => 24],
                        ['x' => 34, 'y' => 48],
                    ],
                    'color' => '#7c3aed',
                    'comment' => 'Slab opening',
                ],
            ])
            ->assertCreated()
            ->assertJsonPath('data.markup_type', 'polygon')
            ->assertJsonPath('data.markup_data.comment', 'Slab opening');
    }

    public function test_submittal_file_routes_require_file_to_belong_to_submittal(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        [$submittalA, $submittalFileA] = $this->createSubmittalWithFile($user);
        [$submittalB] = $this->createSubmittalWithFile($user, 'DRAW-B');

        $this->actingAs($user)
            ->get(route('submittals.files.view', [$submittalB, $submittalFileA]))
            ->assertNotFound();

        $this->actingAs($user)
            ->postJson(route('submittals.files.markups.store', [$submittalB, $submittalFileA]), [
                'page_number' => 1,
                'markup_type' => 'circle',
                'markup_data' => [
                    'x' => 10,
                    'y' => 10,
                    'width' => 10,
                    'height' => 10,
                ],
            ])
            ->assertNotFound();
    }

    public function test_markup_validation_rejects_unknown_markup_type(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        [$submittal, $submittalFile] = $this->createSubmittalWithFile($user);

        $this->actingAs($user)
            ->postJson(route('submittals.files.markups.store', [$submittal, $submittalFile]), [
                'page_number' => 1,
                'markup_type' => 'ellipse',
                'markup_data' => [
                    'x' => 10,
                    'y' => 20,
                ],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['markup_type']);
    }

    public function test_polygon_markup_validation_requires_three_points(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        [$submittal, $submittalFile] = $this->createSubmittalWithFile($user);

        $this->actingAs($user)
            ->postJson(route('submittals.files.markups.store', [$submittal, $submittalFile]), [
                'page_number' => 1,
                'markup_type' => 'polygon',
                'markup_data' => [
                    'points' => [
                        ['x' => 12, 'y' => 16],
                        ['x' => 30, 'y' => 24],
                    ],
                ],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['markup_data.points']);
    }

    public function test_markup_validation_requires_shape_specific_fields(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        [$submittal, $submittalFile] = $this->createSubmittalWithFile($user);

        $this->actingAs($user)
            ->postJson(route('submittals.files.markups.store', [$submittal, $submittalFile]), [
                'page_number' => 1,
                'markup_type' => 'text',
                'markup_data' => [
                    'x' => 10,
                    'y' => 20,
                ],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['markup_data.text']);
    }

    public function test_markup_can_be_deleted_for_submittal_pdf_file(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        [$submittal, $submittalFile] = $this->createSubmittalWithFile($user);

        $markup = PdfMarkup::create([
            'submittal_file_id' => $submittalFile->id,
            'user_id' => $user->id,
            'page_number' => 1,
            'markup_type' => 'circle',
            'markup_data' => [
                'x' => 10,
                'y' => 20,
                'width' => 15,
                'height' => 10,
                'color' => '#ef4444',
            ],
        ]);

        $this->actingAs($user)
            ->delete(route('submittals.files.markups.destroy', [$submittal, $submittalFile, $markup]))
            ->assertNoContent();

        $this->assertSoftDeleted('pdf_markups', [
            'id' => $markup->id,
        ]);
    }

    public function test_markup_delete_requires_markup_to_belong_to_submittal_file(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        [$submittalA, $submittalFileA] = $this->createSubmittalWithFile($user);
        [$submittalB, $submittalFileB] = $this->createSubmittalWithFile($user, 'DRAW-C');

        $markup = PdfMarkup::create([
            'submittal_file_id' => $submittalFileA->id,
            'user_id' => $user->id,
            'page_number' => 1,
            'markup_type' => 'circle',
            'markup_data' => [
                'x' => 10,
                'y' => 20,
                'width' => 15,
                'height' => 10,
            ],
        ]);

        $this->actingAs($user)
            ->delete(route('submittals.files.markups.destroy', [$submittalB, $submittalFileB, $markup]))
            ->assertNotFound();
    }

    public function test_markup_can_be_updated_for_submittal_pdf_file(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        [$submittal, $submittalFile] = $this->createSubmittalWithFile($user);

        $markup = PdfMarkup::create([
            'submittal_file_id' => $submittalFile->id,
            'user_id' => $user->id,
            'page_number' => 1,
            'markup_type' => 'text',
            'markup_data' => [
                'x' => 10,
                'y' => 20,
                'text' => 'Original note',
                'color' => '#ef4444',
            ],
        ]);

        $this->actingAs($user)
            ->putJson(route('submittals.files.markups.update', [$submittal, $submittalFile, $markup]), [
                'page_number' => 2,
                'markup_type' => 'text',
                'markup_data' => [
                    'x' => 15,
                    'y' => 30,
                    'text' => 'Updated note',
                    'color' => '#2563eb',
                    'comment' => 'Field coordination',
                ],
            ])
            ->assertOk()
            ->assertJsonPath('data.page_number', 2)
            ->assertJsonPath('data.markup_data.text', 'Updated note')
            ->assertJsonPath('data.markup_data.comment', 'Field coordination');
    }

    public function test_markups_can_be_imported_from_json_file(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        [$submittal, $submittalFile] = $this->createSubmittalWithFile($user);

        $tempPath = storage_path('app/testing-import-markups.json');

        file_put_contents($tempPath, json_encode([
            'markups' => [
                [
                    'page_number' => 1,
                    'markup_type' => 'stamp',
                    'markup_data' => [
                        'x' => 50,
                        'y' => 30,
                        'label' => 'APPROVED',
                        'color' => '#b91c1c',
                    ],
                ],
                [
                    'page_number' => 2,
                    'markup_type' => 'dimension',
                    'markup_data' => [
                        'x' => 10,
                        'y' => 10,
                        'x2' => 40,
                        'y2' => 10,
                        'label' => '30 u',
                    ],
                ],
                [
                    'page_number' => 3,
                    'markup_type' => 'polyline',
                    'markup_data' => [
                        'points' => [
                            ['x' => 8, 'y' => 8],
                            ['x' => 18, 'y' => 16],
                            ['x' => 22, 'y' => 30],
                        ],
                        'color' => '#f97316',
                    ],
                ],
            ],
        ], JSON_PRETTY_PRINT));

        $upload = new UploadedFile($tempPath, 'import-markups.json', 'application/json', null, true);

        $this->actingAs($user)
            ->post(route('submittals.files.markups.import', [$submittal, $submittalFile]), [
                'markups_file' => $upload,
            ])
            ->assertCreated()
            ->assertJsonPath('imported_count', 3);

        unlink($tempPath);
    }

    public function test_pdf_page_scale_can_be_saved_and_listed_per_page(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        [$submittal, $submittalFile] = $this->createSubmittalWithFile($user);

        $this->actingAs($user)
            ->putJson(route('submittals.files.page-scales.upsert', [$submittal, $submittalFile, 2]), [
                'calibration_distance' => 14.25,
                'real_length' => 24,
                'unit' => 'ft',
            ])
            ->assertOk()
            ->assertJsonPath('data.page_number', 2)
            ->assertJsonPath('data.real_length', 24);

        $this->actingAs($user)
            ->getJson(route('submittals.files.page-scales.index', [$submittal, $submittalFile]))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.page_number', 2)
            ->assertJsonPath('data.0.unit', 'ft');
    }

    public function test_pdf_page_scale_can_be_cleared(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        [$submittal, $submittalFile] = $this->createSubmittalWithFile($user);

        $this->actingAs($user)
            ->putJson(route('submittals.files.page-scales.upsert', [$submittal, $submittalFile, 1]), [
                'calibration_distance' => 10,
                'real_length' => 12,
                'unit' => 'in',
            ])
            ->assertOk();

        $this->actingAs($user)
            ->delete(route('submittals.files.page-scales.destroy', [$submittal, $submittalFile, 1]))
            ->assertNoContent();

        $this->actingAs($user)
            ->getJson(route('submittals.files.page-scales.index', [$submittal, $submittalFile]))
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    private function createSubmittalWithFile(User $user, string $suffix = 'DRAW'): array
    {
        $customer = Customer::create([
            'name' => "Customer {$suffix}",
            'code' => "CUST-{$suffix}",
            'active' => true,
        ]);

        $project = Project::create([
            'project_number' => "PRJ-{$suffix}",
            'name' => "Project {$suffix}",
            'customer_id' => $customer->id,
            'status' => 'active',
        ]);

        $drawingRequest = DrawingRequest::create([
            'request_number' => "DR-{$suffix}-0001",
            'project_id' => $project->id,
            'customer_id' => $customer->id,
            'requested_by_user_id' => $user->id,
            'assigned_to_user_id' => $user->id,
            'title' => 'Shop Drawing Package',
            'priority' => 'normal',
            'drawing_type' => 'structural',
            'requested_date' => now()->toDateString(),
            'status' => 'in_progress',
        ]);

        $submittal = DrawingSubmittal::create([
            'submittal_number' => "SUB-{$suffix}-0001",
            'drawing_request_id' => $drawingRequest->id,
            'project_id' => $project->id,
            'customer_id' => $customer->id,
            'revision' => 'A',
            'submitted_by_user_id' => $user->id,
            'purpose' => 'for_approval',
            'status' => 'draft',
        ]);

        $pdf = UploadedFile::fake()->create('shop-drawing.pdf', 250, 'application/pdf');
        $storedPath = $pdf->store("submittal-files/{$submittal->id}", 'local');

        $submittalFile = SubmittalFile::create([
            'submittal_id' => $submittal->id,
            'file_type' => 'drawing',
            'filename' => basename($storedPath),
            'original_filename' => 'shop-drawing.pdf',
            'file_path' => $storedPath,
            'file_size' => $pdf->getSize(),
            'mime_type' => 'application/pdf',
            'version' => 1,
            'is_current' => true,
            'uploaded_by_user_id' => $user->id,
            'uploaded_at' => now(),
        ]);

        return [$submittal, $submittalFile];
    }
}
