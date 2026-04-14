<?php
/**
 * Interface para os serviços de IA
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

interface AiServiceInterface
{
    /**
     * Gera conteúdo SEO completo para um produto
     * 
     * @param string $name Nome do produto
     * @param string $reference Referência
     * @param string $shortDesc Descrição original/curta
     * @param string $lang Idioma (pt ou en)
     * @return array [description_short, description, meta_title, meta_description, tags]
     */
    public function generateProductContent(
        string $name,
        string $reference,
        string $shortDesc,
        string $lang = 'pt'
    ): array;

    /**
     * Testa a ligação com a API enviando um pedido mínimo
     * 
     * @return bool True se sucesso, lança Exception se erro
     */
    public function testConnection(): bool;
}
