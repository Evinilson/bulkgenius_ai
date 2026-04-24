<?php
/**
 * Classe base para serviços de IA
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once _PS_MODULE_DIR_ . 'bulkgenius_ai/classes/AiServiceInterface.php';
require_once _PS_MODULE_DIR_ . 'bulkgenius_ai/classes/PromptManager.php';

abstract class AbstractAiService implements AiServiceInterface
{
    protected string $apiKey;
    protected string $model;
    protected int $timeout = 30;
    protected PromptManager $promptManager;

    public function __construct(string $apiKey, string $model)
    {
        $this->apiKey = $apiKey;
        $this->model  = $model;
        $this->promptManager = new PromptManager();
    }

    /**
     * Extrai e valida o JSON da resposta da IA
     */
    protected function parseJsonResponse(string $raw): array
    {
        // Limpar possível markdown (```json ... ```)
        $clean = preg_replace('/^```(?:json)?\s*/m', '', $raw);
        $clean = preg_replace('/\s*```$/m', '', $clean);
        $clean = trim($clean);

        $parsed = json_decode($clean, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Resposta inválida da IA (JSON corrompido).');
        }

        // Garantir campos obrigatórios com valores padrão
        return [
            'description_short' => $parsed['description_short'] ?? '',
            'description'       => $parsed['description'] ?? '',
            'meta_title'        => $parsed['meta_title'] ?? '',
            'meta_description'  => $parsed['meta_description'] ?? '',
            'tags'              => $parsed['tags'] ?? '',
        ];
    }

    /**
     * Constrói o prompt usando o PromptManager
     */
    protected function getPromptTemplate(string $name, string $reference, string $shortDesc, string $lang, string $type = 'full'): string
    {
        return $this->promptManager->getPrompt($type, [
            'name' => $name,
            'reference' => $reference,
            'shortDesc' => $shortDesc
        ], $lang);
    }
}
