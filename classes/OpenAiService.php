<?php
namespace BulkGeniusAi;
/**
 * OpenAiService - Implementação para OpenAI
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once _PS_MODULE_DIR_ . 'bulkgenius_ai/classes/AiServiceInterface.php';
require_once _PS_MODULE_DIR_ . 'bulkgenius_ai/classes/AbstractAiService.php';

class OpenAiService extends AbstractAiService
{
    private const API_URL = 'https://api.openai.com/v1/chat/completions';

    public function generateProductContent(
        string $name,
        string $reference,
        string $shortDesc,
        string $lang = 'pt'
    ): array {
        $prompt = $this->getPromptTemplate($name, $reference, $shortDesc, $lang);

        $payload = json_encode([
            'model'       => $this->model ?: 'gpt-4o-mini',
            'messages'    => [
                ['role' => 'system', 'content' => 'És um especialista em SEO e copywriting para e-commerce. Respondes sempre em JSON válido.'],
                ['role' => 'user',   'content' => $prompt],
            ],
            'temperature' => 0.7,
            'max_tokens'  => 1500,
        ]);

        $ch = curl_init(self::API_URL);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->apiKey,
            ],
        ]);

        $body    = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            $errorData = json_decode($body, true);
            $errorMsg  = $errorData['error']['message'] ?? 'Erro HTTP ' . $httpCode;
            throw new \Exception('Erro OpenAI: ' . $errorMsg);
        }

        $data = json_decode($body, true);
        $rawContent = $data['choices'][0]['message']['content'] ?? '';

        return $this->parseJsonResponse($rawContent);
    }

    public function testConnection(): bool
    {
        $payload = json_encode([
            'model'    => $this->model ?: 'gpt-4o-mini',
            'messages' => [['role' => 'user', 'content' => 'Hi']],
            'max_tokens' => 5,
        ]);

        $ch = curl_init(self::API_URL);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->apiKey,
            ],
        ]);

        $body = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            $errorData = json_decode($body, true);
            $errorMsg = $errorData['error']['message'] ?? 'Erro HTTP ' . $httpCode;
            throw new \Exception('OpenAI: ' . $errorMsg);
        }

        return true;
    }
}
