<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\SaleDetail;
use App\Models\Product;
use App\Models\Member;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

use Barryvdh\DomPDF\Facade\Pdf;

use App\Exports\SalesExport;
use Maatwebsite\Excel\Facades\Excel;


class SalesController extends Controller
{

    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $search = $request->input('search');

        $query = Sale::with(['user', 'member'])->latest();

        if ($search) {
            $query->whereHas('member', function($q) use ($search) {
                        $q->where('name', 'like', '%'.$search.'%');
                    })
                    ->orWhereHas('user', function($q) use ($search) {
                        $q->where('name', 'like', '%'.$search.'%');
                    })
                    ->orWhere('date', 'like', '%'.$search.'%')
                    ->orWhere('sub_total', 'like', '%'.$search.'%');
        }

        $sales = $query->paginate($perPage)->onEachSide(1);

        if ($request->ajax()) {
            return response()->json([
                'html' => view('sales.partials.table', compact('sales'))->render(),
                'pagination' => view('sales.partials.pagination', [
                    'paginator' => $sales,
                    'elements' => $sales->links()->elements, // Add this line
                    'entries_info' => $this->getEntriesInfo($sales)
                ])->render(),
            ]);
        }
    
        return view('sales.index', compact('sales'));
    }

    private function getEntriesInfo($paginator)
    {
        $from = $paginator->firstItem();
        $to = $paginator->lastItem();
        $total = $paginator->total();
        
        return "Showing $from to $to of $total entries";
    }
    

    public function create()
    {
        $products = Product::all();
        return view('sales.create', compact('products'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'products' => 'required|array',
            'products.*.id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|integer|min:1',
            'member_phone' => 'nullable|string',
            'amount_paid' => 'required|numeric|min:0',
            'point_used' => 'nullable|integer|min:0'
        ]);

        // Hitung subtotal
        $subTotal = 0;
        $productsData = [];
        
        foreach ($request->products as $product) {
            $productModel = Product::find($product['id']);
            $totalPrice = $productModel->price * $product['quantity'];
            $subTotal += $totalPrice;
            
            $productsData[] = [
                'product_id' => $product['id'],
                'quantity_product' => $product['quantity'],
                'total_price' => $totalPrice
            ];
        }

        // Cari member berdasarkan nomor telepon jika ada
        $member = null;
        if ($request->member_phone) {
            $member = Member::where('no_telephone', $request->member_phone)->first();
        }

        // Hitung kembalian
        $change = $request->amount_paid - ($subTotal - ($request->point_used ?? 0));

        // Buat transaksi penjualan
        $sale = Sale::create([ // Corrected to Sales
            'date' => Carbon::now(),
            'user_id' => Auth::id(),
            'member_id' => $member ? $member->id : null,
            'point_used' => $request->point_used ?? 0,
            'change' => $change,
            'amount_paid' => $request->amount_paid,
            'sub_total' => $subTotal
        ]);

        // Simpan detail produk yang dibeli
        foreach ($productsData as $productData) {
            $productData['sale_id'] = $sale->id; 
            
            // Kurangi stok produk
            $product = Product::find($productData['product_id']);
            $product->stock -= $productData['quantity_product'];
            $product->save();
        }

        // Jika member, tambahkan point
        if ($member) {
            $pointsEarned = floor($subTotal / 10000);
            $member->point += $pointsEarned;
            $member->save();
        }

        return redirect()->route('sales.index', $sale->id);
    }


    public function destroy(Sale $sale) 
    {
        // Kembalikan stok produk
        foreach ($sale->saleDetails as $detail) {
            $product = Product::find($detail->product_id);
            $product->stock += $detail->quantity_product;
            $product->save();
        }

        // Hapus transaksi
        $sale->delete();

        return redirect()->route('sales.index')->with('success', 'Sale deleted successfully');
    }

    
    public function postCreate(Request $request)
    {
        // Pastikan input 'products' bukan array kosong
        $productsInput = $request->input('products');

        // Jika produk dikirim dalam bentuk string JSON, decode ke array
        if (is_string($productsInput)) {
            $selectedProducts = json_decode($productsInput, true);
        } else {
            $selectedProducts = $productsInput; // Sudah berupa array
        }

        if (!$selectedProducts || !is_array($selectedProducts)) {
            return redirect()->back()->with('error', 'Produk tidak valid atau belum dipilih.');
        }

        session()->put('selectedProducts', $selectedProducts);

        $products = [];
        $total = 0;

        foreach ($selectedProducts as $item) {
            $productModel = Product::find($item['id']);
            if (!$productModel) {
                return redirect()->back()->with('error', 'Produk tidak ditemukan.');
            }

            // Hitung total harga untuk setiap produk
            $totalPrice = $productModel->price * $item['quantity'];
            $total += $totalPrice;

            // Menambahkan informasi produk ke array
            $products[] = [
                'id' => $productModel->id,
                'name' => $productModel->name,
                'price' => $productModel->price,
                'quantity' => $item['quantity'],
                'subtotal' => $totalPrice,
            ];
        }

        return view('sales.post-create', compact('products', 'total'));
    }


    public function processPayment(Request $request)
    {
        $request->validate([
            'products' => 'required|array',
            'products.*.id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|integer|min:1',
            'amount_paid' => 'required|numeric|min:0',
            'member_phone' => 'nullable|string'
        ]);

        // Calculate subtotal
        $subTotal = 0;
        $productsData = [];
        
        foreach ($request->products as $product) {
            $productModel = Product::find($product['id']);
            $totalPrice = $productModel->price * $product['quantity'];
            $subTotal += $totalPrice;
            
            $productsData[] = [
                'product_id' => $product['id'],
                'quantity_product' => $product['quantity'],
                'total_price' => $totalPrice
            ];
        }

        // Check if member
        $member = null;
        $isNewMember = false;
        
        if ($request->member_phone) {
            $member = Member::where('no_telephone', $request->member_phone)->first();
            
            // If member doesn't exist, create new one
            if (!$member) {
                $member = Member::create([
                    'name' => '', // Empty name to be filled later
                    'no_telephone' => $request->member_phone,
                    'point' => 0,
                    'date' => Carbon::now()
                ]);
                $isNewMember = true;
            }
        }

        // Calculate change
        $change = $request->amount_paid - $subTotal;
        if ($change < 0) {
            return redirect()->back()->withErrors(['amount_paid' => 'Jumlah pembayaran kurang dari total harga.']);
        }

        // Create sale record
        $sale = Sale::create([
            'date' => Carbon::now(),
            'user_id' => Auth::id(),
            'member_id' => $member ? $member->id : null,
            'point_used' => 0, // No points used initially
            'change' => $change,
            'amount_paid' => $request->amount_paid,
            'sub_total' => $subTotal,
            'is_new_member' => $isNewMember
        ]);

        // Save product details
        foreach ($productsData as $productData) {
            $sale->saleDetails()->create($productData);
            
            // Reduce product stock
            $product = Product::find($productData['product_id']);
            $product->stock -= $productData['quantity_product'];
            $product->save();
        }

        // Redirect based on member status
        if ($member) {
            return redirect()->route('sales.memberPayment', ['id' => $sale->id]);
        }
        
        return redirect()->route('sales.detailPrint', ['id' => $sale->id]);
    }

    public function memberPayment($id)
    {
        $sale = Sale::with(['saleDetails.product', 'member'])->findOrFail($id);
        
        // Check if this is member's first purchase
        $isFirstPurchase = $sale->member->sales()->count() === 1;
        
        return view('sales.member-payment', [
            'sale' => $sale,
            'is_new_member' => $sale->is_new_member,
            'is_first_purchase' => $isFirstPurchase,
            'can_use_points' => !$isFirstPurchase && $sale->member->point > 0
        ]);
    }

    public function updateMemberPayment(Request $request, $id)
    {
        $sale = Sale::with('member')->findOrFail($id);
        
        $request->validate([
            'member_name' => 'required|string|max:255',
            'use_points' => 'nullable|boolean',
            'point_used' => 'nullable|integer|min:0|max:'.$sale->member->point
        ]);

        // Update member name
        $sale->member->update(['name' => $request->member_name]);

        $pointUsed = 0;
        $totalAfterPoint = $sale->sub_total;
        $change = $sale->amount_paid - $totalAfterPoint;

        // Process points if used
        if ($request->use_points && $request->point_used > 0) {
            $pointUsed = min($request->point_used, $sale->member->point);
            $pointDeduction = $pointUsed * 100;
            
            $totalAfterPoint = max(0, $sale->sub_total - $pointDeduction);
            $change = $sale->amount_paid - $totalAfterPoint;
            
            $sale->member->decrement('point', $pointUsed);
        }

        // Update sale
        $sale->update([
            'point_used' => $pointUsed,
            'sub_total' => $totalAfterPoint,
            'change' => $change,
            'is_new_member' => false
        ]);

        // Add earned points
        $pointsEarned = floor($totalAfterPoint / 10000);
        $sale->member->increment('point', $pointsEarned);

        return redirect()->route('sales.detailPrint', $sale->id);
    }

    public function exportPdf($id)
    {
        $sale = Sale::with(['saleDetails.product', 'member', 'user'])->findOrFail($id);
        
        $data = [
            'sale' => $sale,
            'title' => 'Invoice #'.$sale->id
        ];
        
        $pdf = Pdf::loadView('sales.pdf-export', $data);
        return $pdf->download('invoice-'.$sale->id.'.pdf');
    }

    
    public function detail($id)
    {
        $sale = Sale::with(['saleDetails.product', 'member', 'user'])->findOrFail($id);
    
        // Calculate total
        $total = $sale->saleDetails->sum(function($detail) {
            return $detail->total_price; // Subtotal for each sale detail
        });
    
        return response()->json([
            'member' => $sale->member,
            'saleDetails' => $sale->saleDetails,
            'total' => $total,
            'sub_total' => $sale->sub_total, // Ini sudah termasuk pengurangan point
            'created_at' => $sale->created_at->format('d M Y'),
            'created_by' => $sale->user->name
        ]);
    }


    public function exportExcel()
    {
        return Excel::download(new SalesExport, 'sales_export_' . date('Ymd_His') . '.xlsx');
    }

   
    
    public function getSalesChartData()
    {
        $endDate = Carbon::today(); // Today's date
        $startDate = Carbon::today()->subDays(29); // 30 days period (including today)
        
        $salesData = Sale::selectRaw('DATE(date) as date, COUNT(*) as count')
            ->whereDate('date', '>=', $startDate)
            ->whereDate('date', '<=', $endDate)
            ->groupBy('date')
            ->orderBy('date')
            ->get();
        
        // Fill in missing dates with 0 counts
        $result = [];
        $currentDate = $startDate->copy();
        
        while ($currentDate <= $endDate) {
            $dateString = $currentDate->format('Y-m-d');
            $found = $salesData->firstWhere('date', $dateString);
            
            $result[] = [
                'date' => $currentDate->format('d M'), // Only day and month for compact display
                'count' => $found ? $found->count : 0,
                'full_date' => $currentDate->format('d M Y') // For tooltip
            ];
            
            $currentDate->addDay();
        }
        
        return response()->json($result);
    }

    public function getProductSalesData()
    {
        try {
            $productSales = SaleDetail::with('product')
                ->selectRaw('product_id, SUM(quantity_product) as total_sold')
                ->groupBy('product_id')
                ->orderBy('total_sold', 'desc')
                ->get()
                ->map(function ($item) {
                    return [
                        'product_name' => $item->product->name ?? 'Produk Tidak Dikenal',
                        'total_sold' => (int)$item->total_sold
                    ];
                });
    
            if ($productSales->isEmpty()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Tidak ada data penjualan',
                    'data' => []
                ], 200);
            }
    
            return response()->json([
                'status' => 'success',
                'data' => $productSales
            ]);
    
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal memuat data: ' . $e->getMessage()
            ], 500);
        }
    }
}


