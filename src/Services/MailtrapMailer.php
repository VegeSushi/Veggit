<?php

namespace Vegesushi\Veggit\Services;

class MailtrapMailer
{
    private string $apiKey;
    private string $fromEmail;
    private string $fromName;

    public function __construct()
    {
        $this->apiKey = $_ENV['MAILTRAP_API_KEY'] ?? '';
        $this->fromEmail = $_ENV['MAILTRAP_FROM_EMAIL'] ?? '';
        $this->fromName = $_ENV['MAILTRAP_FROM_NAME'] ?? '';

        if (!$this->apiKey || !$this->fromEmail || !$this->fromName) {
            throw new \RuntimeException('Mailtrap API key or From address not set in .env');
        }
    }
    
    public function send(string $toEmail, string $toName, string $subject, string $htmlBody): bool
    {
        $url = 'https://send.api.mailtrap.io/api/send';

        $payload = [
            'from' => ['email' => $this->fromEmail, 'name' => $this->fromName],
            'to' => [['email' => $toEmail, 'name' => $toName]],
            'subject' => $subject,
            'text' => strip_tags($htmlBody),
            'html' => $htmlBody,
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer {$this->apiKey}",
            "Content-Type: application/json",
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 200 && $httpCode < 300) {
            return true;
        }

        error_log("Mailtrap send failed (HTTP {$httpCode}): {$response}");
        return false;
    }
}
