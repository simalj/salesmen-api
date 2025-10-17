<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DatabaseHealthCheck extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'db:health-check 
                            {--detailed : Show detailed database statistics}
                            {--slow-queries : Show slow query analysis}
                            {--connections : Show connection statistics}';

    /**
     * The console command description.
     */
    protected $description = 'Check database health and performance metrics';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔍 Database Health Check Started');
        $this->newLine();

        try {
            // Basic connection test
            $this->checkConnection();
            
            if ($this->option('detailed')) {
                $this->showDetailedStats();
            }
            
            if ($this->option('slow-queries')) {
                $this->analyzeSlowQueries();
            }
            
            if ($this->option('connections')) {
                $this->showConnectionStats();
            }
            
            if (!$this->option('detailed') && !$this->option('slow-queries') && !$this->option('connections')) {
                $this->showBasicStats();
            }

            $this->newLine();
            $this->info('✅ Database health check completed successfully');
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error('❌ Database health check failed: ' . $e->getMessage());
            Log::error('Database health check failed', ['error' => $e->getMessage()]);
            
            return 1;
        }
    }

    /**
     * Check basic database connection.
     */
    private function checkConnection(): void
    {
        $start = microtime(true);
        $result = DB::select('SELECT version() as version, current_database() as database');
        $connectionTime = round((microtime(true) - $start) * 1000, 2);
        
        if (!empty($result)) {
            $this->info("🟢 Database Connection: OK ({$connectionTime}ms)");
            $this->line("   Database: {$result[0]->database}");
            $this->line("   Version: {$result[0]->version}");
        } else {
            throw new \Exception('Connection test failed');
        }
    }

    /**
     * Show basic database statistics.
     */
    private function showBasicStats(): void
    {
        $this->info('📊 Basic Statistics:');
        
        try {
            // Database size
            $sizeResult = DB::select("SELECT pg_size_pretty(pg_database_size(current_database())) as size");
            $this->line("   Database Size: {$sizeResult[0]->size}");
            
            // Table count
            $tableCount = DB::select("SELECT count(*) as count FROM information_schema.tables WHERE table_schema = 'public'");
            $this->line("   Tables: {$tableCount[0]->count}");
            
            // Active connections
            $connections = DB::select("SELECT count(*) as count FROM pg_stat_activity WHERE state = 'active'");
            $this->line("   Active Connections: {$connections[0]->count}");
            
        } catch (\Exception $e) {
            $this->warn("   Could not retrieve basic stats: {$e->getMessage()}");
        }
    }

    /**
     * Show detailed database statistics.
     */
    private function showDetailedStats(): void
    {
        $this->info('📈 Detailed Statistics:');
        
        try {
            // Table statistics
            $tables = DB::select("
                SELECT 
                    schemaname,
                    relname as tablename,
                    n_tup_ins as inserts,
                    n_tup_upd as updates,
                    n_tup_del as deletes,
                    n_tup_ins + n_tup_upd + n_tup_del as total_changes,
                    pg_size_pretty(pg_total_relation_size(schemaname||'.'||relname)) as size
                FROM pg_stat_user_tables
                ORDER BY total_changes DESC
                LIMIT 10
            ");
            
            if (!empty($tables)) {
                $this->table(
                    ['Table', 'Inserts', 'Updates', 'Deletes', 'Total Changes', 'Size'],
                    array_map(function ($table) {
                        return [
                            $table->tablename,
                            number_format($table->inserts),
                            number_format($table->updates),
                            number_format($table->deletes),
                            number_format($table->total_changes),
                            $table->size
                        ];
                    }, $tables)
                );
            }
            
        } catch (\Exception $e) {
            $this->warn("   Could not retrieve detailed stats: {$e->getMessage()}");
        }
    }

    /**
     * Analyze slow queries (requires pg_stat_statements extension).
     */
    private function analyzeSlowQueries(): void
    {
        $this->info('🐌 Slow Query Analysis:');
        
        try {
            // Check if pg_stat_statements is available
            $extensionCheck = DB::select("
                SELECT 1 FROM pg_extension WHERE extname = 'pg_stat_statements'
            ");
            
            if (empty($extensionCheck)) {
                $this->warn('   pg_stat_statements extension not available');
                return;
            }
            
            $slowQueries = DB::select("
                SELECT 
                    substring(query, 1, 50) || '...' as query_snippet,
                    calls,
                    round(total_exec_time::numeric, 2) as total_time_ms,
                    round(mean_exec_time::numeric, 2) as avg_time_ms,
                    round((100 * total_exec_time / sum(total_exec_time) OVER())::numeric, 2) as pct_total_time
                FROM pg_stat_statements
                WHERE calls > 10
                ORDER BY mean_exec_time DESC
                LIMIT 10
            ");
            
            if (!empty($slowQueries)) {
                $this->table(
                    ['Query', 'Calls', 'Total Time (ms)', 'Avg Time (ms)', '% Total Time'],
                    array_map(function ($query) {
                        return [
                            $query->query_snippet,
                            number_format($query->calls),
                            number_format($query->total_time_ms, 2),
                            number_format($query->avg_time_ms, 2),
                            $query->pct_total_time . '%'
                        ];
                    }, $slowQueries)
                );
            } else {
                $this->line('   No slow queries found');
            }
            
        } catch (\Exception $e) {
            $this->warn("   Could not analyze slow queries: {$e->getMessage()}");
        }
    }

    /**
     * Show connection statistics.
     */
    private function showConnectionStats(): void
    {
        $this->info('🔗 Connection Statistics:');
        
        try {
            $connections = DB::select("
                SELECT 
                    state,
                    count(*) as count,
                    round(avg(extract(epoch from (now() - state_change)))::numeric, 2) as avg_duration_sec
                FROM pg_stat_activity 
                WHERE pid != pg_backend_pid()
                GROUP BY state
                ORDER BY count DESC
            ");
            
            if (!empty($connections)) {
                $this->table(
                    ['State', 'Count', 'Avg Duration (sec)'],
                    array_map(function ($conn) {
                        return [
                            $conn->state ?: 'unknown',
                            $conn->count,
                            $conn->avg_duration_sec ?: '0'
                        ];
                    }, $connections)
                );
            }
            
            // Max connections setting
            $maxConnections = DB::select("SHOW max_connections");
            $this->line("   Max Connections: {$maxConnections[0]->max_connections}");
            
        } catch (\Exception $e) {
            $this->warn("   Could not retrieve connection stats: {$e->getMessage()}");
        }
    }
}