<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;

class FcmService
{
    protected $messaging;

    public function __construct()
    {
        $factory = (new Factory())->withServiceAccount(base_path(env('FIREBASE_CREDENTIALS')));
        $this->messaging = $factory->createMessaging();
    }

    /**
     * Kirim notifikasi ke single token
     */
    public function sendToToken(string $token, string $title, string $body, array $data = []): bool
    {
        if (empty($token)) {
            return false;
        }

        try {
            // Memastikan data payload bertipe String murni
            $formattedData = array_map('strval', $data);
            $formattedData['click_action'] = 'FLUTTER_NOTIFICATION_CLICK';
            $formattedData['sound_url'] = $this->getSupabaseSoundUrl();

            // Menggunakan CloudMessage::fromArray() -> Sangat aman & didukung versi lawas maupun baru
            $message = CloudMessage::fromArray([
                'token' => $token,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                    'sound' => 'notif.mpeg',
                ],
                'data' => $formattedData,
            ]);

            $this->messaging->send($message);
            return true;
        } catch (\Exception $e) {
            Log::error('FCM Error (sendToToken): ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Kirim notifikasi ke multiple tokens (broadcast)
     */
    public function sendToMultipleTokens(array $tokens, string $title, string $body, array $data = []): bool
    {
        $validTokens = array_filter($tokens, fn($token) => !empty($token));
        
        if (empty($validTokens)) {
            return false;
        }

        try {
            $formattedData = array_map('strval', $data);
            $formattedData['click_action'] = 'FLUTTER_NOTIFICATION_CLICK';
            $formattedData['sound_url'] = $this->getSupabaseSoundUrl();

            // Format multicast menggunakan template array kosong di awal tanpa parameter token tunggal
            $message = CloudMessage::fromArray([
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                    'sound' => 'notif.mp3',
                ],
                'data' => $formattedData,
            ]);

            $this->messaging->sendMulticast($message, array_values($validTokens));
            return true;
        } catch (\Exception $e) {
            Log::error('FCM Error (sendToMultipleTokens): ' . $e->getMessage());
            return false;
        }
    }

    private function getSupabaseSoundUrl(): string
    {
        $supabaseUrl = rtrim(env('SUPABASE_URL', ''), '/');
        $bucket = env('SUPABASE_STORAGE_BUCKET', 'motocare');
        return $supabaseUrl . '/storage/v1/object/public/' . $bucket . '/sounds/notif.mp3';
    }

    /**
     * Helper: Kirim notifikasi ke single user berdasarkan ID
     */
    public function sendToUser($userId, $title, $body, $data = []): bool
    {
        $user = User::find($userId);
        if ($user && $user->fcm_token) {
            return $this->sendToToken($user->fcm_token, $title, $body, $data);
        }
        return false;
    }

    /**
     * Helper: Kirim notifikasi ke semua user yang memiliki role tertentu
     */
    public function sendToRole($role, $title, $body, $data = []): bool
    {
        $tokens = User::whereRaw('LOWER(role) = ?', [strtolower($role)])
            ->whereNotNull('fcm_token')
            ->pluck('fcm_token')
            ->toArray();

        return $this->sendToMultipleTokens($tokens, $title, $body, $data);
    }
}