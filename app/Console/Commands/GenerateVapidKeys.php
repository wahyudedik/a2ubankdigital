<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class GenerateVapidKeys extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vapid:generate';

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
            // Generate VAPID keys using web-push library approach
            $vapidKeys = $this->generateVapidKeyPair();

            $this->info('✓ VAPID Keys Generated Successfully!');
            $this->newLine();
            
            $this->info('Add these to your .env file:');
            $this->newLine();
            
            $this->line('VITE_VAPID_PUBLIC_KEY=' . $vapidKeys['publicKey']);
            $this->line('VAPID_PUBLIC_KEY=' . $vapidKeys['publicKey']);
            $this->line('VAPID_PRIVATE_KEY=' . $vapidKeys['privateKey']);
            
            $this->newLine();
            $this->info('Then run: php artisan config:cache');
            
        } catch (\Exception $e) {
            $this->error('Error generating VAPID keys: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }

    /**
     * Generate VAPID key pair
     */
    private function generateVapidKeyPair(): array
    {
        // Generate EC key pair (P-256 curve)
        $config = [
            'private_key_type' => OPENSSL_KEYTYPE_EC,
            'curve_name' => 'prime256v1'
        ];

        $res = openssl_pkey_new($config);
        if (!$res) {
            throw new \Exception('Failed to generate key pair');
        }

        openssl_pkey_export($res, $privateKeyPem);
        $publicKeyDetails = openssl_pkey_get_details($res);
        $publicKeyPem = $publicKeyDetails['key'];

        // Extract raw key bytes
        $privateKey = $this->extractPrivateKey($privateKeyPem);
        $publicKey = $this->extractPublicKey($publicKeyPem);

        // Encode to base64url format
        $publicKeyBase64Url = rtrim(strtr(base64_encode($publicKey), '+/', '-_'), '=');
        $privateKeyBase64Url = rtrim(strtr(base64_encode($privateKey), '+/', '-_'), '=');

        return [
            'publicKey' => $publicKeyBase64Url,
            'privateKey' => $privateKeyBase64Url
        ];
    }

    /**
     * Extract private key bytes from PEM format
     */
    private function extractPrivateKey(string $pem): string
    {
        // Parse PEM to get DER
        $lines = explode("\n", $pem);
        $der = '';
        foreach ($lines as $line) {
            if (strpos($line, '-----') === false) {
                $der .= $line;
            }
        }
        $der = base64_decode($der);

        // Extract the private key value (last 32 bytes for P-256)
        // This is a simplified extraction - the actual structure is more complex
        // For production, consider using a proper library like web-push
        return substr($der, -32);
    }

    /**
     * Extract public key bytes from PEM format
     */
    private function extractPublicKey(string $pem): string
    {
        // Parse PEM to get DER
        $lines = explode("\n", $pem);
        $der = '';
        foreach ($lines as $line) {
            if (strpos($line, '-----') === false) {
                $der .= $line;
            }
        }
        $der = base64_decode($der);

        // Extract the public key value (65 bytes for P-256 uncompressed)
        // This is a simplified extraction - the actual structure is more complex
        // For production, consider using a proper library like web-push
        $pos = strpos($der, "\x04");
        if ($pos !== false) {
            return substr($der, $pos, 65);
        }

        return substr($der, -65);
    }
}
