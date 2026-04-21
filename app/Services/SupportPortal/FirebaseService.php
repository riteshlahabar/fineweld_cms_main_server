<?php

namespace App\Services\SupportPortal;

class FirebaseService
{
    private function base64UrlEncode($data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function getAccessToken()
    {
        $credentials = json_decode(
            file_get_contents(storage_path('app/firebase/firebase_credentials.json')),
            true
        );

        $now = time();

        $header = $this->base64UrlEncode(json_encode([
            'alg' => 'RS256',
            'typ' => 'JWT'
        ]));

        $payload = $this->base64UrlEncode(json_encode([
            'iss'   => $credentials['client_email'],
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud'   => 'https://oauth2.googleapis.com/token',
            'iat'   => $now,
            'exp'   => $now + 3600,
        ]));

        $signatureInput = $header . "." . $payload;

        openssl_sign(
            $signatureInput,
            $signature,
            $credentials['private_key'],
            'sha256'
        );

        $jwt = $signatureInput . "." . $this->base64UrlEncode($signature);

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, "https://oauth2.googleapis.com/token");
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion'  => $jwt,
        ]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = json_decode(curl_exec($ch), true);

        curl_close($ch);

        return $response['access_token'];
    }

    public function sendToTokens(array $tokens, $title, $body, $data = [])
{
    $responses = [];

    foreach ($tokens as $token) {
        $responses[] = $this->sendNotification($token, $title, $body, $data);
    }

    return $responses;
}

public function sendNotification($token, $title, $body, $data = [])
{
    $accessToken = $this->getAccessToken();

    $credentials = json_decode(
        file_get_contents(storage_path('app/firebase/firebase_credentials.json')),
        true
    );

    $projectId = $credentials['project_id'];

    $message = [
        "message" => [
            "token" => $token,
            "notification" => [
                "title" => $title,
                "body"  => $body,
            ],
            "webpush" => [
                "notification" => [
                    "title" => $title,
                    "body"  => $body,
                ]
            ]
        ]
    ];

    // ✅ Only attach data if not empty
    if (!empty($data) && is_array($data)) {
    $stringData = [];

    foreach ($data as $key => $value) {
        $stringData[$key] = (string) $value;
    }

    $message["message"]["data"] = $stringData;
}

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer {$accessToken}",
        "Content-Type: application/json"
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($message));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $result = curl_exec($ch);

    curl_close($ch);

    return json_decode($result, true);
}
}