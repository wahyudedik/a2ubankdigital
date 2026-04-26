<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Minishlink\WebPush\VAPID;

class GenerateVapidKeys extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vapid:generate {--force : Force overwrite existing keys}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate new VAPID keys for push notifications';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            // Check if keys already exist
            if (!$this->option('force') && (config('services.vapid.public_key') || config('services.vapid.private_key'))) {
                if (!$this->confirm('VAPID keys already exist. Do you want to generate new ones?')) {
                    $this->info('Operation cancelled.');
                    return 0;
                }
            }

            // Generate VAPID keys using the proper web-push library
            $vapidKeys = VAPID::createVapidKeys();

            $this->info('✓ VAPID Keys Generated Successfully!');
            $this->newLine();
            
            $this->info('🔑 Your new VAPID keys:');
            $this->newLine();
            
            // Display keys in a nice format
            $this->line('📋 Copy these to your .env file:');
            $this->newLine();
            
            $this->line('# VAPID Keys for Push Notifications');
            $this->line('VITE_VAPID_PUBLIC_KEY=' . $vapidKeys['publicKey']);
            $this->line('VAPID_PUBLIC_KEY=' . $vapidKeys['publicKey']);
            $this->line('VAPID_PRIVATE_KEY=' . $vapidKeys['privateKey']);
            
            $this->newLine();
            $this->info('📝 Next steps:');
            $this->line('1. Add the keys above to your .env file');
            $this->line('2. Run: php artisan config:cache');
            $this->line('3. Update your frontend with the new public key');
            
            $this->newLine();
            $this->warn('⚠️  Important: Keep your private key secure and never expose it publicly!');
            
            // Optionally write to .env file directly
            if ($this->confirm('Do you want to automatically update your .env file?')) {
                $this->updateEnvFile($vapidKeys);
            }
            
        } catch (\Exception $e) {
            $this->error('❌ Error generating VAPID keys: ' . $e->getMessage());
            $this->error('Make sure the minishlink/web-push package is installed.');
            return 1;
        }

        return 0;
    }

    /**
     * Update .env file with new VAPID keys
     */
    private function updateEnvFile(array $vapidKeys): void
    {
        $envPath = base_path('.env');
        
        if (!file_exists($envPath)) {
            $this->error('.env file not found!');
            return;
        }

        $envContent = file_get_contents($envPath);
        
        // Update or add VAPID keys
        $keys = [
            'VITE_VAPID_PUBLIC_KEY' => $vapidKeys['publicKey'],
            'VAPID_PUBLIC_KEY' => $vapidKeys['publicKey'],
            'VAPID_PRIVATE_KEY' => $vapidKeys['privateKey']
        ];

        foreach ($keys as $key => $value) {
            if (preg_match("/^{$key}=.*$/m", $envContent)) {
                // Update existing key
                $envContent = preg_replace("/^{$key}=.*$/m", "{$key}={$value}", $envContent);
            } else {
                // Add new key
                $envContent .= "\n{$key}={$value}";
            }
        }

        file_put_contents($envPath, $envContent);
        
        $this->info('✓ .env file updated successfully!');
        $this->line('Run: php artisan config:cache to apply changes');
    }
}
