<?php
namespace BulkGeniusAi;
/**
 * GeminiService - Implementação para Google Gemini
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once _PS_MODULE_DIR_ . 'bulkgenius_ai/classes/AiServiceInterface.php';
require_once _PS_MODULE_DIR_ . 'bulkgenius_ai/classes/AbstractAiService.php';

class GeminiService extends AbstractAiService
{
    private const API_URL_TEMPLATE = 'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent?key=%s';

    public function generateProductContent(
        string $name,
        string $reference,
        string $shortDesc,
        string $lang = 'pt'
    ): array {
        $model = $this->model ?: 'gemini-1.5-flash';
        $url   = sprintf(self::API_URL_TEMPLATE, $model, $this->apiKey);
        $prompt = $this->getPromptTemplate($name, $reference, $shortDesc, $lang);

        $payload = json_encode([
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.7,
                'maxOutputTokens' => 2000,
                'responseMimeType' => 'application/json'
            ]
        ]);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json'
            ],
        ]);

        $body    = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            $errorData = json_decode($body, true);
            $errorMsg  = $errorData['error']['message'] ?? 'Erro HTTP ' . $httpCode;
            throw new \Exception('Erro Gemini: ' . $errorMsg);
        }

        $data = json_decode($body, true);
        $rawContent = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';

        return $this->parseJsonResponse($rawContent);
    }

    public function testConnection(): bool
    {
        $model = $this->model ?: 'gemini-1.5-flash';
        $url   = sprintf(self::API_URL_TEMPLATE, $model, $this->apiKey);

        $payload = json_encode([
            'contents' => [['parts' => [['text' => 'Hi']]]],
            'generationConfig' => ['maxOutputTokens' => 5]
        ]);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        ]);

        $body = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            $errorData = json_decode($body, true);
            $errorMsg  = $errorData['error']['message'] ?? 'Erro HTTP ' . $httpCode;
            throw new \Exception('Gemini: ' . $errorMsg);
        }

        return true;
    }
}
