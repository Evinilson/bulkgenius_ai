<?php
/**
 * ProductCreator - Cria produtos no PrestaShop via API nativa
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class ProductCreator
{
    private int $defaultCategoryId;
    private int $langId;
    private int $shopId;

    public function __construct(int $categoryId, string $langIso = 'pt')
    {
        $this->defaultCategoryId = $categoryId ?: (int) \Configuration::get('PS_HOME_CATEGORY');

        // Obter ID do idioma pelo ISO
        $lang = \Language::getLanguageByIETFCode($langIso)
            ?: \Language::getLanguageByIso($langIso);

        // Correção para objeto vs array
        if (is_object($lang)) {
            $this->langId = (int) $lang->id;
        } elseif (is_array($lang)) {
            $this->langId = (int) $lang['id_lang'];
        } else {
            $this->langId = (int) \Configuration::get('PS_LANG_DEFAULT');
        }

        $this->shopId = (int) \Context::getContext()->shop->id ?: (int) \Configuration::get('PS_SHOP_DEFAULT');
    }

    /**
     * Cria um produto e retorna o ID gerado
     */
    public function create(array $data): int
    {
        $product = new \Product();

        // Campos multilíngues
        $product->name              = [$this->langId => $this->sanitize($data['name'])];
        $product->description       = [$this->langId => $data['description']];
        $product->description_short = [$this->langId => $this->sanitize($data['description_short'])];
        $product->meta_title        = [$this->langId => $this->sanitize($data['meta_title'])];
        $product->meta_description  = [$this->langId => $this->sanitize($data['meta_description'])];
        
        // Slug único
        $slug = $this->generateUniqueSlug($data['name']);
        $product->link_rewrite      = [$this->langId => $slug];

        // Campos simples
        $product->reference             = $this->sanitize($data['reference']);
        $product->price                 = (float) $data['price'];
        $product->id_category_default   = $this->defaultCategoryId;
        
        // Correção da lógica de impostos (Usar regra selecionada nas configurações)
        $product->id_tax_rules_group    = (int) \Configuration::get('BULKGENIUS_AI_TAX_RULE') ?: 1;
        
        $product->quantity              = 0;
        $product->minimal_quantity       = 1;
        $product->active                = 0; // Criar como INATIVO para revisão humana
        $product->visibility            = 'both';
        $product->show_price            = 1;
        $product->available_for_order   = 1;
        $product->condition             = 'new';
        $product->id_shop_default       = $this->shopId;
        
        // Suporte obrigatório para PrestaShop 8 (Multishop)
        $product->id_shop_list          = [$this->shopId];

        if (!$product->add()) {
            throw new \Exception('Erro ao criar produto: ' . $data['name']);
        }

        // Associar categoria
        $product->addToCategories([$this->defaultCategoryId]);

        // Atualizar stock
        \StockAvailable::setQuantity((int) $product->id, 0, 0);

        // Adicionar tags
        if (!empty($data['tags'])) {
            $this->addTags((int) $product->id, $data['tags']);
        }

        return (int) $product->id;
    }

    private function addTags(int $productId, string $tagsStr): void
    {
        $tags = array_map('trim', explode(',', $tagsStr));
        $tags = array_filter($tags);

        if (empty($tags)) {
            return;
        }

        \Tag::deleteTagsForProduct($productId);

        foreach ($tags as $tagName) {
            $tag = new \Tag();
            $tag->id_lang = $this->langId;
            $tag->name    = $this->sanitize($tagName);
            $tag->add();
            \Tag::addTags($this->langId, $productId, $tagName);
        }
    }

    private function sanitize(string $value): string
    {
        return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
    }

    private function generateUniqueSlug(string $name): string
    {
        $baseSlug = mb_strtolower($name, 'UTF-8');
        $baseSlug = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $baseSlug);
        $baseSlug = preg_replace('/[^a-z0-9\s-]/', '', $baseSlug);
        $baseSlug = preg_replace('/[\s]+/', '-', trim($baseSlug));
        $baseSlug = trim($baseSlug, '-');
        
        if (empty($baseSlug)) {
            $baseSlug = 'produto';
        }

        $slug = $baseSlug;
        $count = 1;

        // Verificar se o slug já existe nesta língua
        while (\Product::getIdByReference($slug) || $this->slugExists($slug)) {
            $slug = $baseSlug . '-' . $count;
            $count++;
            if ($count > 100) break; // Segurança
        }

        return $slug;
    }

    private function slugExists(string $slug): bool
    {
        $sql = new \DbQuery();
        $sql->select('id_product');
        $sql->from('product_lang');
        $sql->where("link_rewrite = '" . \pSQL($slug) . "'");
        $sql->where('id_lang = ' . (int) $this->langId);
        
        return (bool) \Db::getInstance()->getValue($sql);
    }
}
