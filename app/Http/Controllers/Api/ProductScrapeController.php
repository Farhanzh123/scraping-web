<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\RunShopeeScrape;
use App\Models\ProductScrape;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductScrapeController extends Controller
{
    public function scrape(Request $request): JsonResponse
    {
        // 1. Hilangkan batas waktu eksekusi PHP agar tidak timeout
        set_time_limit(0);
        ini_set('max_execution_time', '0');

        $validated = $request->validate([
            'keyword' => 'required|string|max:255',
            'limit' => 'nullable|integer|min:1|max:20',
        ]);

        $keyword = $validated['keyword'];
        $limit = $validated['limit'] ?? 5;

        // 2. Jalankan Job secara Synchronous (ditunggu hingga selesai)
        RunShopeeScrape::dispatchSync($keyword, $limit);

        // 3. Ambil data hasil scraping terbaru berdasarkan keyword
        $scrapedData = ProductScrape::where('keyword', $keyword)
            ->latest()
            ->take($limit)
            ->get();

        // 4. Kembalikan response 200 OK bersama data scraping
        return response()->json([
            'status' => 'success',
            'message' => 'Scraping berhasil diselesaikan.',
            'total' => $scrapedData->count(),
            'data' => $scrapedData
        ], 200);
    }

    public function index(Request $request): JsonResponse
    {
        $query = ProductScrape::query();

        if ($request->has('keyword')) {
            $query->where('keyword', 'like', '%' . $request->keyword . '%');
        }

        $products = $query->latest()->paginate($request->get('per_page', 10));

        return response()->json([
            'status' => 'success',
            'data' => $products
        ], 200);
    }

    public function show($id): JsonResponse
    {
        $product = ProductScrape::find($id);

        if (!$product) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data produk tidak ditemukan.'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $product
        ], 200);
    }
}