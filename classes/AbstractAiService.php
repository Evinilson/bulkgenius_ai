<?php
/**
 * Classe base para serviços de IA
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once _PS_MODULE_DIR_ . 'bulkgenius_ai/classes/AiServiceInterface.php';

abstract class AbstractAiService implements AiServiceInterface
{
    protected string $apiKey;
    protected string $model;
    protected int $timeout = 30;

    public function __construct(string $apiKey, string $model)
    {
        $this->apiKey = $apiKey;
        $this->model  = $model;
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
     * Constrói o prompt padrão para SEO de e-commerce
     */
    protected function getPromptTemplate(string $name, string $reference, string $shortDesc, string $lang): string
    {
        $langLabel = $lang === 'pt' ? 'português europeu' : 'inglês';

        return <<<PROMPT
És um especialista em copywriting e SEO para e-commerce. Com base nas informações abaixo, gera conteúdo otimizado para um produto de uma loja online.

**Produto:** {$name}
**Referência:** {$reference}
**Descrição base:** {$shortDesc}

Responde APENAS em JSON válido, sem markdown, com esta estrutura exata:
{
  "description_short": "Descrição curta SEO em {$langLabel}, máximo 160 caracteres, apelativa e com palavra-chave principal.",
  "description": "Descrição longa em {$langLabel} em HTML (usa <p>, <ul>, <li>, <strong>). Mínimo 150 palavras. Inclui: benefícios, características, usos/aplicações. Tom profissional e persuasivo.",
  "meta_title": "Meta título SEO em {$langLabel}, máximo 60 caracteres. Inclui nome do produto.",
  "meta_description": "Meta descrição em {$langLabel}, máximo 155 caracteres. Persuasiva, com chamada à ação.",
  "tags": "Lista de 5 a 8 tags/palavras-chave separadas por vírgula, em {$langLabel}, relevantes para o produto."
}
PROMPT;
    }
}
