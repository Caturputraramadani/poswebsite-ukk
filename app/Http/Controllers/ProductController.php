<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    // public function index()
    // {
    //     $products = Product::all();
    //     return view('pages.product', compact('products'));
    // }

    

    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $search = $request->input('search');

        $query = Product::query(); // atau User::query()

        if ($search) {
            $query->where('name', 'like', '%'.$search.'%')
                ->orWhere('price', 'like', '%'.$search.'%')
                ->orWhere('stock', 'like', '%'.$search.'%');
        }

        $data = $query->paginate($perPage)->onEachSide(1); // Batasi hanya 1 halaman di setiap sisi

        if ($request->ajax()) {
            return response()->json([
                'html' => view('products.partials.table', ['products' => $data])->render(),
                'pagination' => view('products.partials.pagination', [
                    'paginator' => $data,
                    'elements' => $data->links()->elements,
                    'entries_info' => "Showing {$data->firstItem()} to {$data->lastItem()} of {$data->total()} entries"
                ])->render(),
            ]);
        }

        return view('pages.product', ['products' => $data]);
    }

    public function save(Request $request, $id = null)
    {
        // Jika hanya update stock
        if ($id && !$request->has('name')) {
            $request->validate([
                'stock' => 'required|integer',
            ]);
    
            $product = Product::findOrFail($id);
            $product->stock = $request->stock;
            $product->save();
    
            return redirect()->route('products.index')->with('success', 'Stock updated successfully');
        }
    
        // Validasi biasa untuk create/update
        $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric',
            'stock' => 'required|integer',
            'images' => 'nullable|image',
        ]);
    
        if ($id) {
            $product = Product::findOrFail($id);
            $message = "Product updated successfully";
        } else {
            $product = new Product();
            $message = "Product created successfully";
        }
    
        $product->name = $request->name;
        $product->price = $request->price;
        $product->stock = $request->stock;
        
    
        if ($request->hasFile('images')) {
            if ($id && $product->images && Storage::disk('public')->exists($product->images)) {
                Storage::disk('public')->delete($product->images);
            }
            $product->images = $request->file('images')->store('images', 'public');
        }
    
        $product->save();
    
        return redirect()->route('products.index')->with('success', $message);
    }
    

    public function destroy(Product $product)
    {  
        if (!$product) {
            return response()->json([
                'error' => 'Product not found.'
            ], 404);
        }

        // Cek apakah produk terkait dengan sale_details (penjualan)
        if ($product->saleDetails()->exists()) {
            return response()->json([
                'error' => 'Cannot delete product because it is associated with sales records.'
            ], 422);
        }

        try {
            // Hapus gambar jika ada
            if ($product->images && Storage::disk('public')->exists($product->images)) {
                Storage::disk('public')->delete($product->images);
            }

            $product->delete();

            return response()->json([
                'success' => 'Product deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to delete product: ' . $e->getMessage()
            ], 500);
        }
    }
    
}
