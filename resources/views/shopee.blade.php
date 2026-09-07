<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopee Scraping & Affiliate Dashboard</title>
    <!-- Tailwind CSS (CDN) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Alpine.js (CDN) -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-slate-100 text-slate-800 font-sans p-6">
    <div class="max-w-5xl mx-auto space-y-6">
        
        <h1 class="text-2xl font-bold text-slate-800">Shopee Scraping & Affiliate Dashboard</h1>

        {{-- Flash Message Success --}}
        @if(session('success'))
            <div class="bg-emerald-50 text-emerald-700 border border-emerald-200 p-4 rounded-lg font-medium">
                ✓ {{ session('success') }}
            </div>
        @endif

        {{-- Flash Message Error --}}
        @if(session('error'))
            <div class="bg-rose-50 text-rose-700 border border-rose-200 p-4 rounded-lg font-medium">
                ✕ {{ session('error') }}
            </div>
        @endif

        {{-- 1. Card Health Check Sesi --}}
        <div 
            x-data="sessionManager()" 
            class="bg-white p-6 rounded-xl shadow-sm border border-slate-200 space-y-4"
        >
            <div class="border-b border-slate-100 pb-3 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2">
                <h2 class="text-lg font-semibold text-slate-700">1. Status Sesi & Health Check Browser</h2>
                
                <!-- Pengaturan Auto-Check -->
                <div class="flex items-center gap-3 text-xs text-slate-600 bg-slate-50 p-2 rounded-lg border border-slate-200">
                    <label class="flex items-center gap-1.5 cursor-pointer font-medium">
                        <input 
                            type="checkbox" 
                            x-model="autoCheckEnabled" 
                            @change="saveSettings()" 
                            class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                        >
                        Auto-Check
                    </label>

                    <div x-show="autoCheckEnabled" class="flex items-center gap-1">
                        <span>Setiap:</span>
                        <select 
                            x-model.number="intervalDays" 
                            @change="saveSettings()" 
                            class="bg-white border border-slate-300 rounded px-1.5 py-0.5 text-xs focus:ring-indigo-500 focus:border-indigo-500"
                        >
                            <option value="1">1 Hari</option>
                            <option value="3">3 Hari</option>
                            <option value="7">7 Hari</option>
                            <option value="30">30 Hari</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-slate-50 p-4 rounded-lg border border-slate-200">
                <div class="space-y-1">
                    <div class="flex items-center gap-3">
                        <span class="font-semibold text-sm text-slate-700">Status Sesi Login:</span>
                        
                        <!-- Badge Dynamic -->
                        <span 
                            x-text="statusBadge.text" 
                            :class="statusBadge.colorClass"
                            class="px-3 py-1 rounded-full text-xs font-bold transition-all duration-300"
                        ></span>
                    </div>
                    <p x-text="statusMessage" class="text-xs text-slate-500"></p>
                    <p x-show="lastCheckedAt" class="text-[11px] text-slate-400">
                        Terakhir dicatat DB: <span x-text="lastCheckedAt"></span>
                    </p>
                </div>

                {{-- WADAH 2 TOMBOL --}}
                <div class="flex items-center gap-2">
                    <!-- Tombol 1: Cek & Siapkan Data -->
                    <button 
                        @click="checkSession()" 
                        :disabled="isChecking || isSaving"
                        class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white font-medium rounded-lg text-sm transition flex items-center gap-2"
                    >
                        <span x-show="isChecking" class="animate-spin">⏳</span>
                        <span x-show="!isChecking">🩺</span>
                        <span x-text="isChecking ? 'Memeriksa...' : 'Cek Sesi Manual'"></span>
                    </button>

                    <!-- Tombol 2: Simpan ke DB (Aktif setelah berhasil cek) -->
                    <button 
                        @click="saveToDatabase()" 
                        :disabled="!hasCheckedResult || isChecking || isSaving"
                        class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 disabled:opacity-40 disabled:cursor-not-allowed text-white font-medium rounded-lg text-sm transition flex items-center gap-2"
                    >
                        <span x-show="isSaving" class="animate-spin">⏳</span>
                        <span x-show="!isSaving">💾</span>
                        <span x-text="isSaving ? 'Menyimpan...' : 'Simpan ke DB'"></span>
                    </button>
                </div>
            </div>
        </div>

        {{-- 2. Card Trigger Login / Launch Chrome --}}
        <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-200 space-y-4">
            <h2 class="text-lg font-semibold text-slate-700 border-b border-slate-100 pb-3">2. Peluncuran Browser Chrome (Port 9222)</h2>
            <form action="{{ route('shopee.login') }}" method="POST">
                @csrf
                <button type="submit" class="px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white font-medium rounded-lg text-sm transition flex items-center gap-2">
                    🌐 Launch Chrome (Port 9222)
                </button>
            </form>
        </div>

        {{-- 3. Card Form Scraping --}}
        <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-200 space-y-4">
            <h2 class="text-lg font-semibold text-slate-700 border-b border-slate-100 pb-3">3. Jalankan Scraping & Generate Link Afiliasi</h2>
            <form action="{{ route('shopee.scrape') }}" method="POST">
                @csrf
                <div class="flex flex-col sm:flex-row gap-3">
                    <input 
                        type="text" 
                        name="keyword" 
                        placeholder="Masukkan Keyword (misal: RAM DDR4 Laptop)" 
                        required 
                        class="flex-1 px-4 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                    >
                    <input 
                        type="number" 
                        name="limit" 
                        value="5" 
                        min="1" 
                        max="50" 
                        class="w-full sm:w-24 px-4 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none" 
                        title="Jumlah Produk"
                    >
                    <button type="submit" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg text-sm transition flex items-center justify-center gap-2">
                        🔍 Mulai Scraping
                    </button>
                </div>
            </form>
        </div>

        {{-- 4. Tabel Hasil Scraping --}}
        <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-200 space-y-4">
            <h2 class="text-lg font-semibold text-slate-700 border-b border-slate-100 pb-3">Hasil Scraping Terbaru</h2>
            
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm border-collapse">
                    <thead>
                        <tr class="bg-slate-50 text-slate-600 border-b border-slate-200">
                            <th class="p-3 font-semibold w-1/3">Judul Produk</th>
                            <th class="p-3 font-semibold">Harga</th>
                            <th class="p-3 font-semibold">Rating</th>
                            <th class="p-3 font-semibold">Toko</th>
                            <th class="p-3 font-semibold w-1/5">Link Afiliasi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($products as $p)
                            <tr class="hover:bg-slate-50">
                                <td class="p-3">
                                    <a href="{{ $p->url_asli ?? '#' }}" target="_blank" class="text-blue-600 hover:underline font-medium">
                                        {{ $p->judul ?? $p->title ?? 'Produk' }}
                                    </a>
                                </td>
                                <td class="p-3 whitespace-nowrap">Rp {{ number_format($p->harga ?? $p->price ?? 0, 0, ',', '.') }}</td>
                                <td class="p-3 whitespace-nowrap">
                                    ⭐ {{ $p->rating_produk ?? $p->rating ?? '-' }}
                                </td>
                                <td class="p-3 whitespace-nowrap">{{ $p->toko ?? $p->shop_name ?? '-' }}</td>
                                <td class="p-3 whitespace-nowrap">
                                    @if($p->url_afiliasi ?? $p->affiliate_url ?? false)
                                        <a href="{{ $p->url_afiliasi ?? $p->affiliate_url }}" target="_blank" class="px-2.5 py-1 bg-emerald-100 text-emerald-700 rounded text-xs font-semibold hover:bg-emerald-200 transition inline-block">
                                            🔗 Link Afiliasi
                                        </a>
                                    @else
                                        <span class="px-2.5 py-1 bg-rose-100 text-rose-700 rounded text-xs font-semibold inline-block">
                                            Belum Ada
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="p-6 text-center text-slate-400">
                                    Belum ada data produk yang di-scrape.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="pt-2">
                {{ $products->links() }}
            </div>
        </div>

    </div>

    <!-- Script Alpine.js -->
    <script>
    function sessionManager() {
        return {
            isChecking: false,
            isSaving: false,
            hasCheckedResult: false,
            
            sessionState: '{{ $sessionStatus->session_state }}',
            statusMessage: '{{ addslashes($sessionStatus->status_message) }}',
            chromeConnected: {{ $sessionStatus->chrome_connected ? 'true' : 'false' }},
            affiliateLoggedIn: {{ $sessionStatus->affiliate_logged_in ? 'true' : 'false' }},
            lastCheckedAt: '{{ $sessionStatus->last_checked_at ? $sessionStatus->last_checked_at->format("d/m/Y H:i:s") : "-" }}',
            autoCheckEnabled: {{ $sessionStatus->auto_check_enabled ? 'true' : 'false' }},
            intervalDays: {{ $sessionStatus->interval_days }},

            get statusBadge() {
                switch(this.sessionState) {
                    case 'ready':
                        return { text: 'Sesi Siap', colorClass: 'bg-emerald-100 text-emerald-700 border border-emerald-300' };
                    case 'need_login':
                        return { text: 'Perlu Login Afiliasi', colorClass: 'bg-amber-100 text-amber-700 border border-amber-300' };
                    case 'disconnected':
                        return { text: 'Chrome Terputus', colorClass: 'bg-rose-100 text-rose-700 border border-rose-300' };
                    case 'idle':
                        return { text: 'Belum Dicek', colorClass: 'bg-slate-100 text-slate-600 border border-slate-300' };
                    default:
                        return { text: 'Error', colorClass: 'bg-rose-100 text-rose-700 border border-rose-300' };
                }
            },

            // 1. Eksekusi Tombol Cek Sesi Manual (Jalankan Python)
            checkSession() {
                this.isChecking = true;
                this.hasCheckedResult = false;
                this.statusMessage = 'Sedang mengecek koneksi Python ke Chrome...';

                fetch("{{ route('shopee.check-session') }}", {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    }
                })
                .then(res => res.json())
                .then(res => {
                    this.isChecking = false;
                    if (res.success && res.data) {
                        this.sessionState = res.data.session_state;
                        this.statusMessage = res.data.status_message + ' (Belum disimpan ke DB)';
                        this.chromeConnected = res.data.chrome_connected;
                        this.affiliateLoggedIn = res.data.affiliate_logged_in;
                        
                        this.hasCheckedResult = true; // Aktifkan tombol "Simpan ke DB"
                    } else {
                        this.sessionState = 'disconnected';
                        this.statusMessage = res.message || 'Gagal mengeksekusi script Python.';
                    }
                })
                .catch(err => {
                    this.isChecking = false;
                    this.sessionState = 'disconnected';
                    this.statusMessage = 'Gagal terhubung ke server.';
                });
            },

            // 2. Eksekusi Tombol Simpan ke DB
            saveToDatabase() {
                if (!this.hasCheckedResult) return;

                this.isSaving = true;

                fetch("{{ route('shopee.save-session') }}", {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        session_state: this.sessionState,
                        status_message: this.statusMessage.replace(' (Belum disimpan ke DB)', ''),
                        chrome_connected: this.chromeConnected,
                        affiliate_logged_in: this.affiliateLoggedIn,
                    })
                })
                .then(res => res.json())
                .then(res => {
                    this.isSaving = false;
                    if (res.success) {
                        this.statusMessage = res.message;
                        this.lastCheckedAt = res.data.last_checked_at;
                        this.hasCheckedResult = false; // Nonaktifkan tombol simpan karena sudah sinkron
                    }
                })
                .catch(err => {
                    this.isSaving = false;
                    alert('Gagal menyimpan status ke database.');
                });
            },

            saveSettings() {
                fetch("{{ route('shopee.update-settings') }}", {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        auto_check_enabled: this.autoCheckEnabled,
                        interval_days: this.intervalDays
                    })
                });
            }
        }
    }
    </script>
</body>
</html>