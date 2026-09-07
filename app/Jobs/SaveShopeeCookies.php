<?php

namespace App\Jobs;

use App\Models\ShopeeAccount;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Exception;

class SaveShopeeCookies implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 60;

    public function handle(): void
    {
        $pythonScriptPath = base_path('python/shopee_export_cookies.py');
        $pythonBinary = '/usr/bin/python3';

        $process = new Process([$pythonBinary, $pythonScriptPath]);
        $process->setTimeout(30);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        $output = trim($process->getOutput());
        $jsonCheck = json_decode($output, true);

        if (isset($jsonCheck['error'])) {
            throw new Exception($jsonCheck['error']);
        }

        ShopeeAccount::updateOrCreate(
            ['username' => 'default_account'],
            [
                'cookies_json' => $output,
                'last_synced_at' => now(),
            ]
        );
    }
}