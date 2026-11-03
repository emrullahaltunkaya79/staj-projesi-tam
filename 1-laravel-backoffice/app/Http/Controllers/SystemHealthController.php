<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\WebhookDelivery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

/**
 * Sistem sağlığı paneli: kuyruk, scheduler, yedek, veritabanı durumu.
 * Yalnızca yönetici erişebilir.
 */
class SystemHealthController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->can('manage', \App\Models\User::class), 403);

        // Kuyruk: bekleyen job sayısı
        $queuePending = DB::table('jobs')->count();

        // Son scheduler işaretleri (cache'de scheduler içinde set edilebilir; burada DB göstergeleri kullanılır)
        $lastCasperCheck = \App\Models\CasperPayment::query()->max('updated_at');
        $lastBackupFile = glob(storage_path('app/backups/*/*.zip'));
        $lastBackup = $lastBackupFile ? date('d.m.Y H:i', filemtime(end($lastBackupFile))) : null;

        // Veritabanı boyutu
        $dbSize = null;
        if (config('database.default') === 'mysql') {
            $dbSize = DB::selectOne(
                'SELECT ROUND(SUM(data_length+index_length)/1024/1024, 1) AS mb
                 FROM information_schema.tables WHERE table_schema = ?',
                [config('database.connections.mysql.database')]
            )->mb.' MB';
        } else {
            $dbFile = database_path('database.sqlite');
            $dbSize = file_exists($dbFile) ? round(filesize($dbFile) / 1024 / 1024, 1).' MB' : '—';
        }

        return view('system.health', [
            'queuePending' => $queuePending,
            'lastCasperCheck' => $lastCasperCheck,
            'lastBackup' => $lastBackup,
            'dbSize' => $dbSize,
            'dbDriver' => config('database.default'),
            'phpVersion' => PHP_VERSION,
            'laravelVersion' => app()->version(),
        ]);
    }
}
