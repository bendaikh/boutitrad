<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\City;
use App\Models\Product;
use App\Support\ImageUpload;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $products = Product::with(['category', 'brand'])
            ->latest()
            ->paginate(25)
            ->withQueryString();

        $editingProduct = $request->filled('edit')
            ? Product::query()->with(['category', 'brand'])->find($request->edit)
            : null;

        $formActive = $editingProduct !== null || $request->boolean('new');

        $initialCityId = old('city_id');
        $initialCityName = old('city', $editingProduct?->city);

        if (! $initialCityId && $initialCityName) {
            $initialCityId = City::findByName($initialCityName)?->id;
        }

        return view('products.index', [
            'products' => $products,
            'categories' => Category::orderBy('name')->get(),
            'brands' => Brand::where('is_active', true)->orderBy('name')->get(),
            'editingProduct' => $editingProduct,
            'formActive' => $formActive,
            'previewSku' => Product::generateSku(),
            'citiesData' => $this->citiesData(),
            'initialCityId' => $initialCityId,
            'initialCityName' => $initialCityName,
        ]);
    }

    public function create(): RedirectResponse
    {
        return redirect()->route('products.index', ['new' => 1]);
    }

    public function store(Request $request): RedirectResponse
    {
        ImageUpload::assertValidUpload($request, 'product_image');

        $request->merge([
            'category_id' => $request->input('category_id') ?: null,
            'brand_id' => $request->input('brand_id') ?: null,
        ]);

        $validated = $request->validate([
            'category_id' => 'nullable|exists:categories,id',
            'brand_id' => 'nullable|exists:brands,id',
            'name' => 'required|string|max:255',
            'sku' => 'nullable|string|max:100|unique:products,sku',
            'barcode' => 'nullable|string|max:255',
            'supplier' => 'nullable|string|max:255',
            'city_id' => 'nullable|exists:cities,id',
            'city' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'product_image' => ImageUpload::RULE,
            'purchase_price' => 'required|numeric|min:0',
            'sale_price' => 'required|numeric|min:0',
            'quantity' => 'required|integer|min:0',
            'min_quantity' => 'nullable|integer|min:0',
            'unit' => 'nullable|string|max:50',
            'is_active' => 'boolean',
        ]);

        $validated['min_quantity'] = $validated['min_quantity'] ?? 5;
        $validated['unit'] = $validated['unit'] ?? 'unité';
        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['city'] = $this->resolveCityName($request);

        if ($path = ImageUpload::storeFromRequest($request, 'product_image', 'product-images')) {
            $validated['image'] = $path;
        }

        unset($validated['product_image']);

        $validated['sku'] = Product::generateSku();

        Product::create($validated);

        return redirect()->route('products.index')->with('success', 'Produit créé.');
    }

    public function show(Product $product): View
    {
        $product->load(['category', 'brand', 'variants', 'stockMovements' => fn ($q) => $q->latest()->limit(20)]);

        return view('products.show', compact('product'));
    }

    public function print(Product $product): View
    {
        $product->load(['category', 'brand']);

        return view('products.print', compact('product'));
    }

    public function edit(Product $product): RedirectResponse
    {
        return redirect()->route('products.index', ['edit' => $product->id]);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        ImageUpload::assertValidUpload($request, 'product_image');

        $request->merge([
            'category_id' => $request->input('category_id') ?: null,
            'brand_id' => $request->input('brand_id') ?: null,
        ]);

        $validated = $request->validate([
            'category_id' => 'nullable|exists:categories,id',
            'brand_id' => 'nullable|exists:brands,id',
            'name' => 'required|string|max:255',
            'sku' => 'required|string|max:100|unique:products,sku,'.$product->id.'|regex:/^PR\d{5}$/',
            'barcode' => 'nullable|string|max:255',
            'supplier' => 'nullable|string|max:255',
            'city_id' => 'nullable|exists:cities,id',
            'city' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'product_image' => ImageUpload::RULE,
            'remove_image' => 'nullable|boolean',
            'purchase_price' => 'required|numeric|min:0',
            'sale_price' => 'required|numeric|min:0',
            'quantity' => 'required|integer|min:0',
            'min_quantity' => 'nullable|integer|min:0',
            'unit' => 'nullable|string|max:50',
            'is_active' => 'boolean',
        ]);

        $validated['min_quantity'] = $validated['min_quantity'] ?? $product->min_quantity;
        $validated['unit'] = $validated['unit'] ?? $product->unit;
        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['city'] = $this->resolveCityName($request);

        if ($request->boolean('remove_image') && $product->image) {
            Storage::disk('public')->delete($product->image);
            $validated['image'] = null;
        }

        if ($request->hasFile('product_image')) {
            if ($product->image) {
                Storage::disk('public')->delete($product->image);
            }
            $validated['image'] = ImageUpload::storeFromRequest($request, 'product_image', 'product-images');
        }

        unset($validated['product_image'], $validated['remove_image']);

        $product->update($validated);

        return redirect()->route('products.index')->with('success', 'Produit mis à jour.');
    }

    public function destroy(Product $product): JsonResponse|RedirectResponse
    {
        if ($product->image) {
            Storage::disk('public')->delete($product->image);
        }

        $product->delete();

        if (request()->wantsJson()) {
            return response()->json(['message' => 'Produit supprimé.']);
        }

        return redirect()->route('products.index')->with('success', 'Produit supprimé.');
    }

    private function cities()
    {
        return City::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    /**
     * @return \Illuminate\Support\Collection<int, array{id: int, name: string}>
     */
    private function citiesData()
    {
        return $this->cities()->map(fn (City $city) => [
            'id' => $city->id,
            'name' => $city->name,
        ])->values();
    }

    private function resolveCityName(Request $request): ?string
    {
        if ($request->filled('city_id')) {
            return City::query()->find($request->integer('city_id'))?->name;
        }

        $city = trim((string) $request->input('city', ''));

        return $city !== '' ? $city : null;
    }
}
