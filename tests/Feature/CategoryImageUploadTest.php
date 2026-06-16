<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Brand;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\File\UploadedFile as SymfonyUploadedFile;
use Tests\TestCase;

class CategoryImageUploadTest extends TestCase
{
    use RefreshDatabase;

    private function superAdmin(): User
    {
        return User::factory()->create([
            'role' => UserRole::SuperAdmin,
            'is_active' => true,
        ]);
    }

    private function sampleJpg(): UploadedFile
    {
        $path = base_path('storage/app/public/category-images/emvqUDGV61ONPunNpfiQ8M4QmRTHBVIBx2luyhGU.jpg');

        if (! file_exists($path)) {
            $this->markTestSkipped('Sample JPG not available.');
        }

        return UploadedFile::createFromBase(
            new SymfonyUploadedFile($path, 'category.jpg', 'image/jpeg', null, true),
            true,
        );
    }

    public function test_category_can_be_created_with_image(): void
    {
        Storage::fake('public');

        $response = $this->actingAs($this->superAdmin())->post(route('categories.store'), [
            'name' => 'Vêtements',
            'description' => 'Test',
            'is_active' => '1',
            'category_image' => $this->sampleJpg(),
        ]);

        $response->assertRedirect(route('categories.index'));
        $response->assertSessionHasNoErrors();

        $category = Category::first();
        $this->assertNotNull($category);
        $this->assertNotNull($category->image);
        Storage::disk('public')->assertExists($category->image);
    }

    public function test_brand_can_be_created_with_image(): void
    {
        Storage::fake('public');

        $response = $this->actingAs($this->superAdmin())->post(route('brands.store'), [
            'name' => 'Nike',
            'description' => 'Test',
            'is_active' => '1',
            'brand_image' => $this->sampleJpg(),
        ]);

        $response->assertRedirect(route('categories.index'));
        $response->assertSessionHasNoErrors();

        $brand = Brand::first();
        $this->assertNotNull($brand);
        $this->assertNotNull($brand->image);
        Storage::disk('public')->assertExists($brand->image);
    }
}
