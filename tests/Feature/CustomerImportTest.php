<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class CustomerImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_import_customers_from_csv(): void
    {
        $user = User::factory()->create();
        $csv = $this->csvFile(<<<'CSV'
name,email,active
ACME Steel,acme@example.com,1
Blue Ridge Fabrication,blue@example.com,0
CSV);

        $response = $this->actingAs($user)->post(route('customers.import'), [
            'file' => $csv,
        ]);

        $response->assertRedirect(route('customers.index'));
        $this->assertDatabaseHas('customers', [
            'name' => 'ACME Steel',
            'email' => 'acme@example.com',
            'active' => 1,
        ]);
        $this->assertDatabaseHas('customers', [
            'name' => 'Blue Ridge Fabrication',
            'email' => 'blue@example.com',
            'active' => 0,
        ]);
    }

    public function test_import_updates_existing_customer_by_name(): void
    {
        $user = User::factory()->create();
        Customer::create([
            'name' => 'ACME Steel',
            'email' => 'old@example.com',
            'active' => true,
        ]);

        $csv = $this->csvFile(<<<'CSV'
name,email,active
ACME Steel,new@example.com,1
CSV);

        $this->actingAs($user)->post(route('customers.import'), [
            'file' => $csv,
        ])->assertRedirect(route('customers.index'));

        $this->assertDatabaseHas('customers', [
            'name' => 'ACME Steel',
            'email' => 'new@example.com',
            'active' => 1,
        ]);
        $this->assertDatabaseCount('customers', 1);
    }

    public function test_import_skips_rows_missing_required_name(): void
    {
        $user = User::factory()->create();
        $csv = $this->csvFile(<<<'CSV'
name,email
,row1@example.com
No Code,row2@example.com
Valid Customer,row3@example.com
CSV);

        $response = $this->actingAs($user)->post(route('customers.import'), [
            'file' => $csv,
        ]);

        $response->assertRedirect(route('customers.index'));
        $response->assertSessionHas('error');

        $this->assertDatabaseHas('customers', [
            'name' => 'No Code',
            'email' => 'row2@example.com',
        ]);
        $this->assertDatabaseHas('customers', [
            'name' => 'Valid Customer',
            'email' => 'row3@example.com',
        ]);
        $this->assertDatabaseCount('customers', 2);
    }

    public function test_import_reports_missing_required_headers(): void
    {
        $user = User::factory()->create();
        $csv = $this->csvFile(<<<'CSV'
customer_name,customer_code,email
ACME Steel,ACME,acme@example.com
CSV);

        $response = $this->actingAs($user)->post(route('customers.import'), [
            'file' => $csv,
        ]);

        $response->assertRedirect(route('customers.index'));
        $response->assertSessionHas('error', function (string $message): bool {
            return str_contains($message, 'Missing required header(s): name');
        });
        $this->assertDatabaseCount('customers', 0);
    }

    public function test_import_does_not_create_duplicate_row_when_name_repeats_in_file(): void
    {
        $user = User::factory()->create();
        $csv = $this->csvFile(<<<'CSV'
name,email
A & A Underground,a@example.com
A & A Underground,updated@example.com
CSV);

        $this->actingAs($user)->post(route('customers.import'), [
            'file' => $csv,
        ])->assertRedirect(route('customers.index'));

        $this->assertDatabaseHas('customers', [
            'name' => 'A & A Underground',
            'email' => 'updated@example.com',
        ]);
        $this->assertDatabaseCount('customers', 1);
    }

    public function test_import_uses_first_phone_line_when_csv_cell_contains_multiple_lines(): void
    {
        $user = User::factory()->create();
        $csv = $this->csvFile(<<<'CSV'
name,phone
Bert's Welding Inc.*,Phone: 815-337-2227
Fax: (815) 337-2228
Mobile: (815) 790-4091
CSV);

        $this->actingAs($user)->post(route('customers.import'), [
            'file' => $csv,
        ])->assertRedirect(route('customers.index'));

        $this->assertDatabaseHas('customers', [
            'name' => "Bert's Welding Inc.*",
            'phone' => 'Phone: 815-337-2227',
        ]);
    }

    public function test_import_requires_csv_file(): void
    {
        $user = User::factory()->create();
        $invalidFile = UploadedFile::fake()->create('customers.pdf', 10, 'application/pdf');

        $this->actingAs($user)->post(route('customers.import'), [
            'file' => $invalidFile,
        ])->assertSessionHasErrors(['file']);
    }

    public function test_authenticated_user_can_download_customer_import_template(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('customers.import.template'));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $response->assertHeader('content-disposition', 'attachment; filename=customers-import-template.csv');

        $content = str_replace("\r\n", "\n", $response->streamedContent());

        $this->assertStringContainsString('name,email,phone,address,city,state,zip,country,notes,active', $content);
        $this->assertStringContainsString('"Acme Steel",estimating@acmesteel.com,555-0100,"123 Industry Way",Denver,CO,80202,USA,"Primary structural steel partner",1', $content);
    }

    private function csvFile(string $contents): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('customers.csv', $contents);
    }
}
