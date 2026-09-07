<?php

namespace App\Http\Controllers;

use App\Jobs\RunShopeeLogin;
use App\Jobs\RunShopeeScrape;
use App\Models\ProductScrape;
use App\Models\ShopeeSessionStatus;
use Illuminate\Http\Request;
use Symfony\Component\Process\Process;

class ShopeeController extends Controller
{
    public function index()
    {
        $sessionStatus = ShopeeSessionStatus::firstOrCreate(
            ['id' => 1],
            [
                'session_state' => 'idle',
                'status_message' => 'Klik tombol "Launch Chrome" atau "Cek Sesi Manual".',
                'chrome_connected' => false,
                'affiliate_logged_in' => false,
                'auto_check_enabled' => false,
                'interval_days' => 1,
            ],
        );

        $products = ProductScrape::latest()->paginate(10);
        return view('shopee', compact('products', 'sessionStatus'));
    }

    public function launchChrome()
    {
        RunShopeeLogin::dispatch();

        return back()->with('success', 'buka chrome');
    }

    public function triggerScrape(Request $request)
    {
        $request->validate([
            'keyword' => 'required|string|max:255',
            'limit' => 'required|integer|min:1|max:50',
        ]);

        RunShopeeScrape::dispatch($request->keyword, $request->limit);

        return back()->with('success', 'Proses scraping untuk keyword "' . $request->keyword . '" telah dijadwalkan.');
    }

    // 1. Menjalankan Python check_session.py tanpa langsung menimpa DB
    public function checkSession()
    {
        $pythonBinary = '/usr/bin/python3';
        $scriptPath = base_path('python/check_session.py');

        $process = new Process([$pythonBinary, $scriptPath]);
        $process->setTimeout(30);
        $process->run();

        if (!$process->isSuccessful()) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Gagal menjalankan script pengecekan sesi.',
                    'error' => $process->getErrorOutput(),
                ],
                500,
            );
        }

        $output = json_decode($process->getOutput(), true);

        return response()->json([
            'success' => true,
            'data' => [
                'session_state' => $output['session_state'] ?? 'error',
                'status_message' => $output['status_message'] ?? 'Pengecekan selesai.',
                'chrome_connected' => $output['chrome_connected'] ?? false,
                'affiliate_logged_in' => $output['affiliate_logged_in'] ?? false,
            ],
        ]);
    }

    // 2. Menyimpan/Meng-update data ke DB (updateOrCreate pada ID=1)
    public function saveSession(Request $request)
    {
        $request->validate([
            'session_state' => 'required|string',
            'status_message' => 'required|string',
            'chrome_connected' => 'required|boolean',
            'affiliate_logged_in' => 'required|boolean',
        ]);

        $status = ShopeeSessionStatus::updateOrCreate(
            ['id' => 1],
            [
                'session_state' => $request->session_state,
                'status_message' => $request->status_message,
                'chrome_connected' => $request->chrome_connected,
                'affiliate_logged_in' => $request->affiliate_logged_in,
                'last_checked_at' => now(),
            ],
        );

        return response()->json([
            'success' => true,
            'message' => 'Status sesi berhasil diperbarui di Database!',
            'data' => [
                'last_checked_at' => $status->last_checked_at->format('d/m/Y H:i:s'),
            ],
        ]);
    }

    public function updateSettings(Request $request)
    {
        $request->validate([
            'auto_check_enabled' => 'required|boolean',
            'interval_days' => 'required|integer|min:1',
        ]);

        $status = ShopeeSessionStatus::find(1);
        $status->update([
            'auto_check_enabled' => $request->auto_check_enabled,
            'interval_days' => $request->interval_days,
        ]);

        return response()->json(['success' => true, 'message' => 'Pengaturan disimpan.']);
    }
}
