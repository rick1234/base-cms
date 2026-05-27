<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\Cms\Download;
use App\Models\Cms\DownloadAccessToken;
use App\Models\Cms\DownloadCategory;
use Database\Seeders\DownloadModuleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class DownloadModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_download_module_seeder_creates_private_demo_downloads_without_duplicates(): void
    {
        Storage::fake('local');

        $this->seed(DownloadModuleSeeder::class);
        $this->seed(DownloadModuleSeeder::class);

        $this->assertSame(2, DownloadCategory::query()->count());
        $this->assertSame(2, Download::query()->count());

        $this->assertDatabaseHas('downloads', [
            'slug' => 'seeded-public-download',
            'file_disk' => 'local',
            'file_path' => 'admin/downloads/seeded/public-download.txt',
            'is_password_protected' => false,
            'link_expires_after_minutes' => 60,
        ]);
        $this->assertDatabaseHas('downloads', [
            'slug' => 'seeded-protected-download',
            'file_disk' => 'local',
            'file_path' => 'admin/downloads/seeded/protected-download.txt',
            'is_password_protected' => true,
            'link_expires_after_minutes' => null,
        ]);

        Storage::disk('local')->assertExists('admin/downloads/seeded/public-download.txt');
        Storage::disk('local')->assertExists('admin/downloads/seeded/protected-download.txt');
    }

    public function test_admin_can_create_password_protected_download_with_legacy_fields_and_private_file(): void
    {
        Storage::fake('local');

        $admin = User::factory()->admin()->create();
        $category = DownloadCategory::query()->create([
            'name' => 'Manuals',
            'slug' => 'manuals',
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->post('/admin/download/edit', [
                'naam' => 'Protected manual',
                'slug' => 'Protected Manual',
                'omschrijving' => 'Only visible after a password check.',
                'status' => '1',
                'startdatum' => '01-05-2026',
                'einddatum' => '01-06-2026',
                'categorie' => [$category->id],
                'is_password_protected' => '1',
                'password' => 'download-secret',
                'unlimited_link' => '1',
                'bestand' => UploadedFile::fake()->create('manual.pdf', 12, 'application/pdf'),
            ])
            ->assertRedirect('/admin/download/1/edit');

        $download = Download::query()->firstOrFail();

        $this->assertSame('Protected manual', $download->name);
        $this->assertSame('protected-manual', $download->slug);
        $this->assertSame('active', $download->status);
        $this->assertTrue($download->is_password_protected);
        $this->assertTrue(Hash::check('download-secret', $download->password_hash));
        $this->assertNull($download->link_expires_after_minutes);
        $this->assertSame('local', $download->file_disk);
        $this->assertStringStartsWith('admin/downloads/', $download->file_path);
        $this->assertFalse(file_exists(public_path($download->file_path)));

        Storage::disk('local')->assertExists($download->file_path);
        $this->assertDatabaseHas('download_category_download', [
            'download_category_id' => $category->id,
            'download_id' => $download->id,
        ]);
    }

    public function test_public_download_issues_hashed_expiring_token_and_streams_private_file(): void
    {
        Storage::fake('local');

        $download = $this->download([
            'slug' => 'public-report',
            'link_expires_after_minutes' => 5,
            'file_path' => 'admin/downloads/tests/public-report.txt',
            'original_filename' => 'public-report.txt',
        ]);

        Storage::disk('local')->put($download->file_path, 'Public report body.');

        $response = $this->get('/downloads/public-report');
        $response->assertRedirect();

        $redirect = $response->headers->get('Location');

        $token = basename((string) parse_url((string) $redirect, PHP_URL_PATH));

        $this->assertNotSame('', $token);
        $this->assertDatabaseHas('download_access_tokens', [
            'download_id' => $download->id,
            'token_hash' => hash('sha256', $token),
        ]);
        $this->assertDatabaseMissing('download_access_tokens', [
            'token_hash' => $token,
        ]);

        $this->get((string) parse_url((string) $redirect, PHP_URL_PATH))
            ->assertOk()
            ->assertHeader('x-content-type-options', 'nosniff');

        $download->refresh();
        $this->assertSame(1, $download->download_count);
        $this->assertNotNull($download->last_downloaded_at);
    }

    public function test_expired_download_tokens_return_gone(): void
    {
        Storage::fake('local');

        $download = $this->download([
            'file_path' => 'admin/downloads/tests/expired.txt',
        ]);
        Storage::disk('local')->put($download->file_path, 'Expired report body.');

        DownloadAccessToken::query()->create([
            'download_id' => $download->id,
            'token_hash' => hash('sha256', 'expiredtoken123'),
            'expires_at' => now()->subMinute(),
        ]);

        $this->get('/downloads/file/expiredtoken123')
            ->assertStatus(410);
    }

    public function test_password_protected_download_requires_password_before_token_is_issued(): void
    {
        Storage::fake('local');

        $download = $this->download([
            'slug' => 'protected-report',
            'file_path' => 'admin/downloads/tests/protected-report.txt',
            'is_password_protected' => true,
            'password_hash' => Hash::make('download-secret'),
            'link_expires_after_minutes' => null,
        ]);
        Storage::disk('local')->put($download->file_path, 'Protected report body.');

        $this->get('/downloads/protected-report')
            ->assertOk()
            ->assertSee('Password')
            ->assertDontSee($download->file_path);

        $this->post('/downloads/protected-report', [
            'password' => 'wrong-password',
        ])
            ->assertSessionHasErrors('password');

        $response = $this->post('/downloads/protected-report', [
            'password' => 'download-secret',
        ]);
        $response->assertRedirect();

        $redirect = $response->headers->get('Location');

        $token = basename((string) parse_url((string) $redirect, PHP_URL_PATH));

        $this->assertDatabaseHas('download_access_tokens', [
            'download_id' => $download->id,
            'token_hash' => hash('sha256', $token),
            'expires_at' => null,
        ]);

        $this->get((string) parse_url((string) $redirect, PHP_URL_PATH))
            ->assertOk();
    }

    public function test_admin_can_generate_unique_urls_and_delete_private_files(): void
    {
        Storage::fake('local');

        $admin = User::factory()->admin()->create();
        $download = $this->download([
            'file_path' => 'admin/downloads/tests/admin-link.txt',
            'link_expires_after_minutes' => null,
        ]);
        Storage::disk('local')->put($download->file_path, 'Admin link body.');

        $firstUrl = $this->actingAs($admin)
            ->withHeader('Accept', 'application/json')
            ->post('/admin/download/ajax/generateLink', ['id' => $download->id])
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->json('url');

        $secondUrl = $this->actingAs($admin)
            ->withHeader('Accept', 'application/json')
            ->post('/admin/download/ajax/generateLink', ['id' => $download->id])
            ->assertOk()
            ->json('url');

        $this->assertNotSame($firstUrl, $secondUrl);
        $this->assertSame(2, DownloadAccessToken::query()->where('download_id', $download->id)->count());
        $this->assertSame(2, DownloadAccessToken::query()->whereNull('expires_at')->count());

        $this->actingAs($admin)
            ->withHeader('Accept', 'application/json')
            ->post('/admin/download/ajax/deleteBestand', ['id' => $download->id])
            ->assertOk()
            ->assertJsonPath('status', 'success');

        Storage::disk('local')->assertMissing('admin/downloads/tests/admin-link.txt');
        $this->assertNull($download->refresh()->file_path);
    }

    public function test_download_categories_support_legacy_fields_sorting_and_delete_routes(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post('/admin/download/categorieen/edit', [
                'naam' => 'Legacy download category',
                'slug' => 'Legacy Download Category',
                'omschrijving' => 'Category body',
                'status' => 1,
                'navigatieHidden' => 1,
            ])
            ->assertRedirect('/admin/download/categorieen/1/edit');

        $category = DownloadCategory::query()->firstOrFail();

        $this->assertSame('legacy-download-category', $category->slug);
        $this->assertTrue($category->is_hidden_from_navigation);

        $this->actingAs($admin)
            ->delete('/admin/download/categorieen/'.$category->id)
            ->assertRedirect('/admin/download/categorieen');

        $this->assertSoftDeleted('download_categories', [
            'id' => $category->id,
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function download(array $attributes = []): Download
    {
        $path = $attributes['file_path'] ?? 'admin/downloads/tests/'.Str::uuid().'.txt';

        return Download::query()->create([
            'name' => $attributes['name'] ?? 'Test download',
            'slug' => $attributes['slug'] ?? 'test-download',
            'description' => $attributes['description'] ?? 'Test private download.',
            'type' => 'txt',
            'url' => $path,
            'file_disk' => 'local',
            'file_path' => $path,
            'original_filename' => $attributes['original_filename'] ?? 'download.txt',
            'mime_type' => 'text/plain',
            'file_size' => 32,
            'status' => $attributes['status'] ?? 'active',
            'active_from' => $attributes['active_from'] ?? now()->subDay()->toDateString(),
            'active_until' => $attributes['active_until'] ?? now()->addDay()->toDateString(),
            'is_password_protected' => $attributes['is_password_protected'] ?? false,
            'password_hash' => $attributes['password_hash'] ?? null,
            'link_expires_after_minutes' => array_key_exists('link_expires_after_minutes', $attributes)
                ? $attributes['link_expires_after_minutes']
                : 60,
            'download_count' => $attributes['download_count'] ?? 0,
            'sort_order' => $attributes['sort_order'] ?? 1,
        ]);
    }
}
