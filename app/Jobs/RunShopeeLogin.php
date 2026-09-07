<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class RunShopeeLogin implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        // Path absolut ke script python
        $pythonScriptPath = base_path('python/shopee_login.py');
        
        // Ganti '/usr/bin/python3' sesuai lokasi binary python kamu
        $process = new Process(['/usr/bin/python3', $pythonScriptPath]);
        $process->setTimeout(300); // Max timeout 5 menit
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
    }
}