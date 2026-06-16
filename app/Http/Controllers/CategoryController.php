<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Support\ImageUpload;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(Request $request): View
    {
        $editingCategory = $request->filled('edit_category')
            ? Category::find($request->edit_category)
            : null;

        $editingBrand = $request->filled('edit_brand')
            ? Brand::find($request->edit_brand)
            : null;

        return view('categories.index', [
            'categories' => Category::orderBy('name')->get(),
            'brands' => Brand::orderBy('name')->get(),
            'editingCategory' => $editingCategory,
            'editingBrand' => $editingBrand,
        ]);
    }

    public function storeCategory(Request $request): RedirectResponse
    {
        ImageUpload::assertValidUpload($request, 'category_image');
        $validated = $this->validateCategory($request);

        if ($path = ImageUpload::storeFromRequest($request, 'category_image', 'category-images')) {
            $validated['image'] = $path;
        }

        $validated['is_active'] = $request->boolean('is_active', true);

        Category::create($validated);

        return redirect()->route('categories.index')->with('success', 'Catégorie créée.');
    }

    public function updateCategory(Request $request, Category $category): RedirectResponse
    {
        ImageUpload::assertValidUpload($request, 'category_image');
        $validated = $this->validateCategory($request, $category);

        if ($request->hasFile('category_image')) {
            if ($category->image) {
                Storage::disk('public')->delete($category->image);
            }
            $validated['image'] = ImageUpload::storeFromRequest($request, 'category_image', 'category-images');
        }

        $validated['is_active'] = $request->boolean('is_active', true);

        $category->update($validated);

        return redirect()->route('categories.index')->with('success', 'Catégorie mise à jour.');
    }

    public function destroyCategory(Category $category): RedirectResponse
    {
        if ($category->products()->exists()) {
            return back()->with('error', 'Impossible de supprimer : des produits utilisent cette catégorie.');
        }

        if ($category->image) {
            Storage::disk('public')->delete($category->image);
        }

        $category->delete();

        return redirect()->route('categories.index')->with('success', 'Catégorie supprimée.');
    }

    public function storeBrand(Request $request): RedirectResponse
    {
        ImageUpload::assertValidUpload($request, 'brand_image');
        $validated = $this->validateBrand($request);

        if ($path = ImageUpload::storeFromRequest($request, 'brand_image', 'brand-images')) {
            $validated['image'] = $path;
        }

        $validated['is_active'] = $request->boolean('is_active', true);

        Brand::create($validated);

        return redirect()->route('categories.index')->with('success', 'Marque créée.');
    }

    public function updateBrand(Request $request, Brand $brand): RedirectResponse
    {
        ImageUpload::assertValidUpload($request, 'brand_image');
        $validated = $this->validateBrand($request, $brand);

        if ($request->hasFile('brand_image')) {
            if ($brand->image) {
                Storage::disk('public')->delete($brand->image);
            }
            $validated['image'] = ImageUpload::storeFromRequest($request, 'brand_image', 'brand-images');
        }

        $validated['is_active'] = $request->boolean('is_active', true);

        $brand->update($validated);

        return redirect()->route('categories.index')->with('success', 'Marque mise à jour.');
    }

    public function destroyBrand(Brand $brand): RedirectResponse
    {
        if ($brand->products()->exists()) {
            return back()->with('error', 'Impossible de supprimer : des produits utilisent cette marque.');
        }

        if ($brand->image) {
            Storage::disk('public')->delete($brand->image);
        }

        $brand->delete();

        return redirect()->route('categories.index')->with('success', 'Marque supprimée.');
    }

    private function validateCategory(Request $request, ?Category $category = null): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category_image' => ImageUpload::RULE,
        ]);
    }

    private function validateBrand(Request $request, ?Brand $brand = null): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'brand_image' => ImageUpload::RULE,
        ]);
    }
}
