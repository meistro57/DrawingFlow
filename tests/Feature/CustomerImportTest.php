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
name,code,email,active
ACME Steel,ACME,acme@example.com,1
Blue Ridge Fabrication,BLUE,blue@example.com,0
CSV);

        $response = $this->actingAs($user)->post(route('customers.import'), [
            'file' => $csv,
        ]);

        $response->assertRedirect(route('customers.index'));
        $this->assertDatabaseHas('customers', [
            'code' => 'ACME',
            'name' => 'ACME Steel',
            'email' => 'acme@example.com',
            'active' => 1,
        ]);
        $this->assertDatabaseHas('customers', [
            'code' => 'BLUE',
            'name' => 'Blue Ridge Fabrication',
            'email' => 'blue@example.com',
            'active' => 0,
        ]);
    }

    public function test_import_updates_existing_customer_by_code(): void
    {
        $user = User::factory()->create();
        Customer::create([
            'name' => 'Old Name',
            'code' => 'ACME',
            'email' => 'old@example.com',
            'active' => true,
        ]);

        $csv = $this->csvFile(<<<'CSV'
name,code,email,active
ACME Updated,ACME,new@example.com,1
CSV);

        $this->actingAs($user)->post(route('customers.import'), [
            'file' => $csv,
        ])->assertRedirect(route('customers.index'));

        $this->assertDatabaseHas('customers', [
            'code' => 'ACME',
            'name' => 'ACME Updated',
            'email' => 'new@example.com',
            'active' => 1,
        ]);
        $this->assertDatabaseCount('customers', 1);
    }

    public function test_import_skips_rows_missing_required_columns(): void
    {
        $user = User::factory()->create();
        $csv = $this->csvFile(<<<'CSV'
name,code,email
,NO_NAME,row1@example.com
No Code,,row2@example.com
Valid Customer,VALID,row3@example.com
CSV);

        $response = $this->actingAs($user)->post(route('customers.import'), [
            'file' => $csv,
        ]);

        $response->assertRedirect(route('customers.index'));
        $response->assertSessionHas('error');

        $this->assertDatabaseHas('customers', [
            'code' => 'VALID',
            'name' => 'Valid Customer',
            'email' => 'row3@example.com',
        ]);
        $this->assertDatabaseCount('customers', 1);
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
            return str_contains($message, 'Missing required header(s): name, code');
        });
        $this->assertDatabaseCount('customers', 0);
    }

    public function test_import_requires_csv_file(): void
    {
        $user = User::factory()->create();
        $invalidFile = UploadedFile::fake()->create('customers.pdf', 10, 'application/pdf');

        $this->actingAs($user)->post(route('customers.import'), [
            'file' => $invalidFile,
        ])->assertSessionHasErrors(['file']);
    }

    private function csvFile(string $contents): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('customers.csv', $contents);
    }
}
