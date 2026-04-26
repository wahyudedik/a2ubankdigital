<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PushSubscription;
use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

class VapidInfo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vapid:info {--test : Test push notification functionality}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display VAPID configuration and push notification status';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔔 VAPID Push Notification Configuration');
        $this->newLine();

        // Check VAPID keys
        $publicKey = config('services.vapid.public_key');
        $privateKey = config('services.vapid.private_key');

        if (!$publicKey || !$privateKey) {
            $this->error('❌ VAPID keys not configured!');
            $this->line('Run: php artisan vapid:generate');
            return 1;
        }

        $this->info('✓ VAPID Keys Status:');
        $this->line('  Public Key: ' . substr($publicKey, 0, 20) . '...' . substr($publicKey, -10));
        $this->line('  Private Key: ' . (strlen($privateKey) > 0 ? '✓ Configured' : '❌ Missing'));
        $this->newLine();

        // Check push subscriptions
        $subscriptionCount = PushSubscription::count();
        $activeSubscriptions = PushSubscription::where('created_at', '>=', now()->subDays(30))->count();

        $this->info('📊 Push Subscription Statistics:');
        $this->line('  Total Subscriptions: ' . $subscriptionCount);
        $this->line('  Active (last 30 days): ' . $activeSubscriptions);
        $this->newLine();

        // Check environment configuration
        $this->info('⚙️  Environment Configuration:');
        $this->line('  App URL: ' . config('app.url'));
        $this->line('  Environment: ' . config('app.env'));
        $this->line('  Debug Mode: ' . (config('app.debug') ? 'Enabled' : 'Disabled'));
        $this->newLine();

        // Frontend configuration
        $frontendPublicKey = config('app.vapid_public_key') ?: env('VITE_VAPID_PUBLIC_KEY');
        $this->info('🌐 Frontend Configuration:');
        $this->line('  VITE_VAPID_PUBLIC_KEY: ' . ($frontendPublicKey ? '✓ Set' : '❌ Missing'));
        
        if ($frontendPublicKey && $frontendPublicKey !== $publicKey) {
            $this->warn('⚠️  Frontend public key differs from backend key!');
        }
        $this->newLine();

        // Test functionality if requested
        if ($this->option('test')) {
            $this->testPushNotification();
        }

        // Production recommendations
        if (config('app.env') === 'production') {
            $this->info('🚀 Production Recommendations:');
            $this->line('  ✓ Use HTTPS for your domain');
            $this->line('  ✓ Keep private keys secure');
            $this->line('  ✓ Monitor subscription cleanup');
            $this->line('  ✓ Implement rate limiting');
        }

        return 0;
    }

    /**
     * Test push notification functionality
     */
    private function testPushNotification(): void
    {
        $this->info('🧪 Testing Push Notification...');

        try {
            $publicKey = config('services.vapid.public_key');
            $privateKey = config('services.vapid.private_key');

            if (!$publicKey || !$privateKey) {
                $this->error('❌ VAPID keys not configured');
                return;
            }

            // Get a test subscription
            $testSubscription = PushSubscription::latest()->first();

            if (!$testSubscription) {
                $this->warn('⚠️  No push subscriptions found for testing');
                $this->line('Subscribe to notifications in the app first');
                return;
            }

            $webPush = new WebPush([
                'VAPID' => [
                    'subject' => config('app.url'),
                    'publicKey' => $publicKey,
                    'privateKey' => $privateKey,
                ],
            ]);

            $subscription = Subscription::create([
                'endpoint' => $testSubscription->endpoint,
                'publicKey' => $testSubscription->p256dh,
                'authToken' => $testSubscription->auth,
            ]);

            $payload = json_encode([
                'title' => 'Test Notification',
                'body' => 'VAPID configuration is working correctly!',
                'icon' => '/favicon.ico',
                'badge' => '/favicon.ico'
            ]);

            $webPush->queueNotification($subscription, $payload);

            foreach ($webPush->flush() as $report) {
                if ($report->isSuccess()) {
                    $this->info('✓ Test notification sent successfully!');
                } else {
                    $this->error('❌ Test notification failed: ' . $report->getReason());
                    
                    if ($report->isSubscriptionExpired()) {
                        $this->line('Subscription expired, cleaning up...');
                        $testSubscription->delete();
                    }
                }
            }

        } catch (\Exception $e) {
            $this->error('❌ Test failed: ' . $e->getMessage());
        }
    }
}