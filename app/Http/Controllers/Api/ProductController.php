<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\Request;
use Str;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Product::with ('category');

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        $products = $query->paginate(10)->withQueryString();

        return ProductResource::collection($products);

    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:150',
            'description' => 'nullable|string',
            'category_id' => 'required|exists:categories,category_id',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'img' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        $path = "";

        if ($request->hasFile('img')) {
            $path = $request->file('img')->store('products', 'public');
        }

        $product = Product::create([
            'name' => $request->name,
            'description' => $request->description,
            'category_id' => $request->category_id,
            'price' => $request->price,
            'stock' => $request->stock,
            'img' => $path,
            'slug' => Str::slug($request->name),
        ]);

        return new ProductResource($product);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $product = Product::with('category')->findOrFail($id);
        return new ProductResource($product);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $product = Product::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:150',
            'description' => 'nullable|string',
            'category_id' => 'required|exists:categories,category_id',
            'price' => 'required|numeric',
            'stock' => 'required|integer',
            'img' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        // Pertahankan gambar lama jika tidak ada gambar baru
        $path = $product->img;

        // Upload gambar baru jika disertakan
        if ($request->hasFile('img')) {
            $path = $request->file('img')->store('products', 'public');
        }

        $product->update([
            'name' => $request->name,
            'description' => $request->description,
            'category_id' => $request->category_id,
            'price' => $request->price,
            'stock' => $request->stock,
            'img' => $path,
            'slug' => Str::slug($request->name),
        ]);

        return new ProductResource($product);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $product = Product::find($id);

        if ($product != null) {
            $product->delete();

            return response()->json([
                'message' => 'Product deleted successfully'
            ]);
        }

        return response()->json([
            'message' => 'Product not found'
        ], 404);
    }
}
