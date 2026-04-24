<?php
/**
 * PromptManager - Centraliza e organiza os templates de prompts para IA
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class PromptManager
{
    /**
     * Gera o prompt completo baseado no tipo solicitado
     */
    public function getPrompt(string $type, array $data, string $lang): string
    {
        $langLabel = $lang === 'pt' ? 'European Portuguese (PT-PT)' : 'English';
        
        $productContext = sprintf(
            "Product Name: %s\nReference: %s\nBase Description: %s",
            $data['name'] ?? '',
            $data['reference'] ?? '',
            $data['shortDesc'] ?? ''
        );

        // Instruções extra apenas para Português de Portugal
        $ptSpecific = '';
        if ($lang === 'pt') {
            $ptSpecific = "\n   - IMPORTANT: Use STRICTLY European Portuguese (PT-PT) vocabulary and grammar.\n" .
                          "   - AVOID Brazilian Portuguese (PT-BR) terms (e.g., use 'ecrã' instead of 'tela', 'telemóvel' instead of 'celular').\n" .
                          "   - Use PT-PT formal or informal standards ('tu/vós' logic), never Brazilian usage of 'você'.";
        }

        switch ($type) {
            case 'description_short':
                return $this->buildSpecificPrompt("short description (summary)", $this->getShortDescRules($langLabel), $productContext, $langLabel, $ptSpecific);
            
            case 'description':
                return $this->buildSpecificPrompt("long description", $this->getLongDescRules($langLabel), $productContext, $langLabel, $ptSpecific);
            
            case 'meta_description':
                return $this->buildSpecificPrompt("SEO meta description", $this->getMetaDescRules($langLabel), $productContext, $langLabel, $ptSpecific);
            
            case 'tags':
                return $this->buildSpecificPrompt("product tags", $this->getTagRules($langLabel), $productContext, $langLabel, $ptSpecific);
            
            case 'full':
            default:
                return $this->getFullPrompt($productContext, $langLabel, $ptSpecific);
        }
    }

    private function getFullPrompt(string $context, string $langLabel, string $ptSpecific): string
    {
        return <<<PROMPT
Act as a professional e-commerce SEO and Copywriting expert.
Based on the product information provided below, generate optimized content.

CONTEXT:
{$context}

RULES:
1. Output MUST be a valid JSON object.
2. Language: ALL content must be written in {$langLabel}.{$ptSpecific}
3. JSON Structure:
{
  "description_short": "{$this->getShortDescRules($langLabel)}",
  "description": "{$this->getLongDescRules($langLabel)}",
  "meta_description": "{$this->getMetaDescRules($langLabel)}",
  "tags": "{$this->getTagRules($langLabel)}"
}
{$this->getAntiHallucinationRules()}
PROMPT;
    }

    private function buildSpecificPrompt(string $fieldName, string $rules, string $context, string $langLabel, string $ptSpecific): string
    {
        // Determinamos a chave do JSON baseada no tipo para manter compatibilidade com o parser
        $jsonKey = $this->getJsonKeyFromFieldName($fieldName);

        return <<<PROMPT
Act as a professional e-commerce SEO and Copywriting expert.
Your task is to generate ONLY the {$fieldName} for the product below.

CONTEXT:
{$context}

RULES:
1. Target Language: {$langLabel}.{$ptSpecific}
2. Specific Rules: {$rules}
3. Output Format: Return a JSON object with a single key "{$jsonKey}". Do NOT include any other fields.
{$this->getAntiHallucinationRules()}

Example:
{
  "{$jsonKey}": "your generated content here"
}
PROMPT;
    }

    private function getShortDescRules(string $lang): string
    {
        return "Professional HTML summary. Must start with an <h2> containing the product name and a creative benefit (70-110 chars). Followed by max 5 paragraphs <p>. Use <strong> for key specs. DO NOT invent specifications not present in context.";
    }

    private function getLongDescRules(string $lang): string
    {
        return "Extensive professional description in HTML (h2, p, ul, li, strong). Must start with an <h2> containing the product name and a powerful emotional headline (max 110 chars). Min 150 words. Include benefits, technical features, and applications based ONLY on provided data or general common sense for standard items.";
    }


    private function getMetaDescRules(string $lang): string
    {
        return "SEO Meta Description, max 155 chars. Persuasive with Call to Action.";
    }

    private function getTagRules(string $lang): string
    {
        return "Comma-separated list of 5 to 8 relevant keywords.";
    }

    private function getJsonKeyFromFieldName(string $name): string
    {
        if (strpos($name, 'short') !== false) return 'description_short';
        if (strpos($name, 'long') !== false) return 'description';
        if (strpos($name, 'meta description') !== false) return 'meta_description';
        if (strpos($name, 'tags') !== false) return 'tags';
        return 'content';
    }

    /**
     * Regras centrais para evitar alucinações de dados
     */
    private function getAntiHallucinationRules(): string
    {
        return "\n4. DATA INTEGRITY & ANTI-HALLUCINATION:\n" .
               "   - STRICT FACTUALITY: Do NOT invent technical specifications (e.g., battery life, dimensions, material) if not provided.\n" .
               "   - INTELLIGENT KNOWLEDGE: If the product is well-known (e.g., 'iPhone 15'), you may use general accurate info. Otherwise, stay strictly within the provided context.\n" .
               "   - NO GUESSING: If data is scarce, keep descriptions concise and professional rather than filling with fake details.\n" .
               "   - GROUNDING: Use the provided Name and Base Description as your primary source of truth.";
    }
}
