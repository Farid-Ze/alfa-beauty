<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Handle failed queue jobs.
 *
 * This listener logs failed jobs and optionally sends notifications
 * to administrators for critical failures.
 */
class LogFailedJobs
{
    /**
     * Critical job types that require immediate notification.
     */
    protected array $criticalJobs = [
        'App\\Notifications\\OrderConfirmation',
        'App\\Notifications\\PaymentReceived',
        'App\\Jobs\\ProcessOrder',
        'App\\Jobs\\SyncInventory',
    ];

    /**
     * Handle the event.
     */
    public function handle(JobFailed $event): void
    {
        $jobName = $event->job->resolveName();
        $exception = $event->exception;

        // Log the failure with full context
        Log::error('Queue job failed', [
            'job' => $jobName,
            'connection' => $event->connectionName,
            'queue' => $event->job->getQueue(),
            'attempts' => $event->job->attempts(),
            'exception' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
            'payload' => $event->job->payload(),
        ]);

        // Send Slack notification for critical jobs in production
        if ($this->isCriticalJob($jobName) && app()->environment('production')) {
            $this->notifyAdmins($jobName, $exception);
        }
    }

    /**
     * Check if job is critical and requires immediate notification.
     */
    protected function isCriticalJob(string $jobName): bool
    {
        foreach ($this->criticalJobs as $critical) {
            if (str_contains($jobName, $critical)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Send notification to administrators.
     */
    protected function notifyAdmins(string $jobName, \Throwable $exception): void
    {
        // Log to Slack channel (configured in logging.php)
        Log::channel('slack')->critical('Critical queue job failed!', [
            'job' => $jobName,
            'error' => $exception->getMessage(),
            'time' => now()->toDateTimeString(),
        ]);
    }
}
