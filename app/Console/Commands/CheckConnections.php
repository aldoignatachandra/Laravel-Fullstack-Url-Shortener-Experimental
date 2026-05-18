<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class CheckConnections extends Command
{
    protected $signature = 'app:check-connections';

    protected $description = 'Check database and Redis connections';

    public function handle(): int
    {
        $this->newLine();
        $this->line('  <fg=yellow>Checking connections...</>');
        $this->newLine();

        // Check PostgreSQL
        $this->checkPostgreSQL();

        // Check Redis
        $this->checkRedis();

        $this->newLine();

        return self::SUCCESS;
    }

    private function checkPostgreSQL(): void
    {
        try {
            DB::connection()->getPdo();
            $version = DB::select('SELECT version()')[0]->version ?? 'unknown';
            $dbName = DB::connection()->getDatabaseName();

            $this->line("  <fg=green>✓</> PostgreSQL  <fg=gray>│</>  <fg=white>{$dbName}</>  <fg=gray>│</>  <fg=gray>{$version}</>");
        } catch (\Exception $e) {
            $this->line("  <fg=red>✗</> PostgreSQL  <fg=gray>│</>  <fg=red>Connection failed: {$e->getMessage()}</>");
        }
    }

    private function checkRedis(): void
    {
        try {
            $redis = Redis::connection();
            $redis->ping();
            $info = $redis->info();
            $version = $info['Server']['redis_version'] ?? 'unknown';

            $this->line("  <fg=green>✓</> Redis       <fg=gray>│</>  <fg=white>default</>  <fg=gray>│</>  <fg=gray>v{$version}</>");
        } catch (\Exception $e) {
            $this->line("  <fg=red>✗</> Redis       <fg=gray>│</>  <fg=red>Connection failed: {$e->getMessage()}</>");
        }
    }
}
