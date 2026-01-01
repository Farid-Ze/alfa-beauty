<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Custom maintenance mode with enhanced features.
 *
 * This command provides a user-friendly maintenance mode with:
 * - Estimated downtime display
 * - Custom maintenance message
 * - Secret bypass token
 * - Allowed IP addresses
 */
class MaintenanceMode extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'app:maintenance 
                            {action : The action to perform (on/off/status)}
                            {--message= : Custom maintenance message}
                            {--duration= : Estimated downtime in minutes}
                            {--secret= : Secret token to bypass maintenance}
                            {--allow=* : IP addresses to allow access}';

    /**
     * The console command description.
     */
    protected $description = 'Manage application maintenance mode with enhanced features';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $action = $this->argument('action');

        return match ($action) {
            'on' => $this->enableMaintenance(),
            'off' => $this->disableMaintenance(),
            'status' => $this->showStatus(),
            default => $this->invalidAction(),
        };
    }

    /**
     * Enable maintenance mode.
     */
    protected function enableMaintenance(): int
    {
        $message = $this->option('message') ?? 'Sistem sedang dalam pemeliharaan. Kami akan segera kembali.';
        $duration = $this->option('duration') ?? 30;
        $secret = $this->option('secret') ?? bin2hex(random_bytes(16));
        $allow = $this->option('allow');

        $params = [
            '--refresh=15',
            '--retry=60',
            "--secret={$secret}",
        ];

        // Add allowed IPs
        foreach ($allow as $ip) {
            $params[] = "--allow={$ip}";
        }

        // Call Laravel's down command
        $this->call('down', $params);

        // Store maintenance info for the view
        $maintenanceInfo = [
            'message' => $message,
            'duration' => (int) $duration,
            'started_at' => now()->toDateTimeString(),
            'estimated_end' => now()->addMinutes((int) $duration)->toDateTimeString(),
            'secret' => $secret,
        ];

        file_put_contents(
            storage_path('framework/maintenance_info.json'),
            json_encode($maintenanceInfo, JSON_PRETTY_PRINT)
        );

        $this->info('✅ Maintenance mode enabled');
        $this->table(
            ['Setting', 'Value'],
            [
                ['Message', $message],
                ['Duration', "{$duration} minutes"],
                ['Estimated End', $maintenanceInfo['estimated_end']],
                ['Secret Bypass', $secret],
                ['Bypass URL', url("/{$secret}")],
            ]
        );

        return Command::SUCCESS;
    }

    /**
     * Disable maintenance mode.
     */
    protected function disableMaintenance(): int
    {
        $this->call('up');

        // Clean up maintenance info
        $infoFile = storage_path('framework/maintenance_info.json');
        if (file_exists($infoFile)) {
            unlink($infoFile);
        }

        $this->info('✅ Maintenance mode disabled - Application is live!');

        return Command::SUCCESS;
    }

    /**
     * Show current maintenance status.
     */
    protected function showStatus(): int
    {
        $isDown = app()->isDownForMaintenance();
        $infoFile = storage_path('framework/maintenance_info.json');

        if (!$isDown) {
            $this->info('🟢 Application is UP and running');
            return Command::SUCCESS;
        }

        $this->warn('🔴 Application is DOWN for maintenance');

        if (file_exists($infoFile)) {
            $info = json_decode(file_get_contents($infoFile), true);
            $this->table(
                ['Setting', 'Value'],
                [
                    ['Message', $info['message'] ?? 'N/A'],
                    ['Started At', $info['started_at'] ?? 'N/A'],
                    ['Estimated End', $info['estimated_end'] ?? 'N/A'],
                    ['Secret Bypass', $info['secret'] ?? 'N/A'],
                ]
            );
        }

        return Command::SUCCESS;
    }

    /**
     * Handle invalid action.
     */
    protected function invalidAction(): int
    {
        $this->error('Invalid action. Use: on, off, or status');
        return Command::FAILURE;
    }
}
