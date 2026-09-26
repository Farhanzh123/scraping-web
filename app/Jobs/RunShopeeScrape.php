<?php

namespace App\Jobs;

use App\Models\ProductScrape;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class RunShopeeScrape implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300;

    protected string $keyword;
    protected int $limit;

    /**
     * Create a new job instance.
     */
    public function __construct(string $keyword, int $limit = 5)
    {
        $this->keyword = $keyword;
        $this->limit = $limit;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $pythonBinary = '/usr/bin/python3';
        // Disesuaikan dengan nama file Python Anda: shopee_scrape.py
        $scriptPath = base_path('python/shopee_scraper.py');

        $process = new Process([
            $pythonBinary,
            $scriptPath,
            $this->keyword,
            (string) $this->limit
        ]);

        $process->setTimeout($this->timeout);
        $process->run();

        if (!$process->isSuccessful()) {
            Log::error('Shopee Scraping Job Failed: ' . $process->getErrorOutput());
            return;
        }

        $rawOutput = trim($process->getOutput());
        $products = json_decode($rawOutput, true);

        if (!is_array($products) || empty($products)) {
            Log::warning('Shopee Scraping: Tidak ada data produk yang ditemukan/dikembalikan.');
            return;
        }

        foreach ($products as $item) {
            $spesifikasiData = $item['spesifikasi'] ?? [];
            $spesifikasiJson = is_string($spesifikasiData) ? $spesifikasiData : json_encode($spesifikasiData);

            ProductScrape::updateOrCreate(
                [
                    'item_id' => (string) $item['item_id'],
                    'shop_id' => (string) $item['shop_id'],
                ],
                [
                    'keyword'              => $this->keyword,
                    'judul'                => $item['judul'] ?? 'Tanpa Judul',
                    'image_url'            => !empty($item['image_url']) ? $item['image_url'] : null,
                    'spesifikasi'          => $spesifikasiJson,
                    'harga'                => (int) ($item['harga'] ?? 0),
                    'terjual'              => (int) ($item['terjual'] ?? 0),
                    'rating_produk'        => (float) ($item['rating_produk'] ?? 0),
                    'total_ulasan_produk'  => (int) ($item['total_ulasan_produk'] ?? 0),
                    'toko'                 => $item['toko'] ?? '-',
                    'rating_toko'          => (float) ($item['rating_toko'] ?? 0),
                    'total_ulasan_toko'    => (int) ($item['total_ulasan_toko'] ?? 0),
                    'deskripsi'            => !empty($item['deskripsi']) ? $item['deskripsi'] : null,
                    'url_asli'             => $item['url_asli'] ?? '',
                    'url_afiliasi'         => $item['url_afiliasi'] ?? null,
                ]
            );
        }

        Log::info("Shopee Scraping Success: Berhasil menyimpan " . count($products) . " produk untuk keyword '{$this->keyword}'.");
    }
}