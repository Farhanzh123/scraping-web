<?php

namespace App\Jobs;

use App\Models\ProductScrape;
use App\Models\ShopeeAccount;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Illuminate\Support\Facades\Log;

class RunShopeeScrape implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;
    protected string $keyword;
    protected int $limit;

    public function __construct(string $keyword, int $limit = 5)
    {
        $this->keyword = $keyword;
        $this->limit = $limit;
    }

    public function handle(): void
    {
        $account = ShopeeAccount::where('username', 'default_account')->first();
        $cookiesJson = $account ? $account->cookies_json : '[]';

        $pythonScriptPath = base_path('python/shopee_scraper.py');
        $pythonBinary = '/usr/bin/python3'; // Pastikan path python ini sesuai

        $process = new Process([$pythonBinary, $pythonScriptPath, $this->keyword, $this->limit, $cookiesJson]);
        $process->setTimeout(240);
        $process->run();

        if (!$process->isSuccessful()) {
            Log::error('ShopeeScrape Error: ' . $process->getErrorOutput());
            throw new ProcessFailedException($process);
        }

        $output = trim($process->getOutput());
        
        // Cek mentahan output python di storage/logs/laravel.log
        Log::info('ShopeeScrape Raw Output: ' . $output);

        $products = json_decode($output, true);

        if (is_array($products) && count($products) > 0) {
            foreach ($products as $item) {
                ProductScrape::create([
                    'keyword'              => $this->keyword,
                    'item_id'              => $item['item_id'] ?? null,
                    'shop_id'              => $item['shop_id'] ?? null,
                    'judul'                => $item['judul'] ?? 'Tanpa Judul',
                    'harga'                => $item['harga'] ?? 0,
                    'rating_produk'        => $item['rating_produk'] ?? 0,
                    'total_ulasan_produk'  => $item['total_ulasan_produk'] ?? 0,
                    'toko'                 => $item['toko'] ?? '-',
                    'rating_toko'          => $item['rating_toko'] ?? 0,
                    'total_ulasan_toko'    => $item['total_ulasan_toko'] ?? 0,
                    'url_asli'             => $item['url_asli'] ?? '',
                    'url_afiliasi'         => $item['url_afiliasi'] ?? null,
                ]);
            }
            Log::info('Berhasil menyimpan ' . count($products) . ' produk ke database.');
        } else {
            Log::warning('ShopeeScrape: Output JSON kosong atau bukan array valid.');
        }
    }
}