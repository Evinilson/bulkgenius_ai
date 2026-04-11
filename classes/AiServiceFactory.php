<?php
namespace BulkGeniusAi;
/**
 * AiServiceFactory - Fábrica para instanciar o serviço de IA correto
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once _PS_MODULE_DIR_ . 'bulkgenius_ai/classes/AiServiceInterface.php';
require_once _PS_MODULE_DIR_ . 'bulkgenius_ai/classes/OpenAiService.php';
require_once _PS_MODULE_DIR_ . 'bulkgenius_ai/classes/GeminiService.php';
require_once _PS_MODULE_DIR_ . 'bulkgenius_ai/classes/GroqService.php';

class AiServiceFactory
{
    /**
     * Retorna uma instância do serviço de IA configurado ou usa parâmetros manuais
     */
    public static function create(string $provider = null, string $model = null, string $apiKey = null): AiServiceInterface
    {
        $provider = $provider ?: \Configuration::get('BULKGENIUS_AI_PROVIDER') ?: 'openai';
        $model    = $model ?: \Configuration::get('BULKGENIUS_AI_MODEL');

        switch ($provider) {
            case 'gemini':
                $apiKey = $apiKey ?: \Configuration::get('BULKGENIUS_AI_GEMINI_KEY');
                return new GeminiService($apiKey, $model ?: 'gemini-1.5-flash');

            case 'groq':
                $apiKey = $apiKey ?: \Configuration::get('BULKGENIUS_AI_GROQ_KEY');
                return new GroqService($apiKey, $model ?: 'llama-3.1-8b-instant');

            case 'openai':
            default:
                $apiKey = $apiKey ?: \Configuration::get('BULKGENIUS_AI_API_KEY');
                return new OpenAiService($apiKey, $model ?: 'gpt-4o-mini');
        }
    }
}
