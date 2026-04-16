<?php
/**
 * Controlador Admin - BulkGenius AI
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once _PS_MODULE_DIR_ . 'bulkgenius_ai/classes/AiServiceInterface.php';
require_once _PS_MODULE_DIR_ . 'bulkgenius_ai/classes/ExcelReader.php';
require_once _PS_MODULE_DIR_ . 'bulkgenius_ai/classes/AiServiceFactory.php';
require_once _PS_MODULE_DIR_ . 'bulkgenius_ai/classes/ProductCreator.php';

class AdminBulkGeniusAiController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        parent::__construct();
        $this->meta_title = $this->l('BulkGenius AI');
    }

    public function initContent()
    {
        parent::initContent();

        // Guardar configurações
        if (Tools::isSubmit('submitConfig')) {
            $this->saveConfig();
        }

        // Processar ações via AJAX
        $action = Tools::getValue('action');
        if ($action) {
            switch ($action) {
                case 'preview':
                    $this->processPreview();
                    break;
                case 'import_single':
                    $this->processImportSingle();
                    break;
                case 'test_connection':
                    $this->processTestConnection();
                    break;
                case 'regenerate_product':
                    $this->processRegenerateProduct();
                    break;
            }
            exit;
        }

        $this->renderPage();
    }

    private function processRegenerateProduct()
    {
        header('Content-Type: application/json');
        $this->checkAjaxToken();

        $name = strip_tags(trim((string) Tools::getValue('name')));
        $currentDesc = Tools::getValue('current_content');
        $langCode = Tools::getValue('lang_code', 'pt');
        $type = Tools::getValue('type', 'description');

        // Resolver o langId — aceitar tanto ISO ('pt') como ID numérico ('2')
        if (is_numeric($langCode)) {
            $langId = (int) $langCode;
        } else {
            $langId = (int) Language::getIdByIso($langCode);
        }
        if (!$langId) {
            $langId = (int) Configuration::get('PS_LANG_DEFAULT');
        }

        // Fallback: se o nome não veio do DOM, buscar à BD pelo id_product
        if (empty($name)) {
            $idProduct = (int) Tools::getValue('id_product');
            if ($idProduct > 0) {
                $product = new Product($idProduct, false, $langId);
                if (Validate::isLoadedObject($product)) {
                    $name = $product->name;
                }
            }
        }

        $provider = Configuration::get('BULKGENIUS_AI_PROVIDER') ?: 'openai';
        $keyMap = [
            'openai' => ['key' => 'BULKGENIUS_AI_API_KEY', 'label' => 'OpenAI'],
            'gemini' => ['key' => 'BULKGENIUS_AI_GEMINI_KEY', 'label' => 'Google Gemini'],
            'groq' => ['key' => 'BULKGENIUS_AI_GROQ_KEY', 'label' => 'Groq'],
        ];
        $providerInfo = $keyMap[$provider] ?? $keyMap['openai'];
        $apiKey = Configuration::get($providerInfo['key']);

        if (empty($name)) {
            echo json_encode(['success' => false, 'message' => 'O nome do produto é necessário para gerar conteúdo.']);
            exit;
        }

        if (empty($apiKey)) {
            echo json_encode(['success' => false, 'message' => 'Chave da API não configurada.']);
            exit;
        }

        try {
            $aiService = AiServiceFactory::create();
            
            // Gerar conteúdo com IA
            $aiContent = $aiService->generateProductContent(
                (string)$name,
                '', // Referência opcional
                (string)$currentDesc,
                $langCode
            );

            echo json_encode([
                'success' => true,
                'content' => [
                    'description_short' => $type === 'summary' ? $aiContent['description_short'] : $aiContent['description_short'],
                    'description' => $type === 'description' ? $aiContent['description'] : $aiContent['description'],
                    'meta_title' => $aiContent['meta_title'],
                    'meta_description' => $type === 'meta_description' ? $aiContent['meta_description'] : $aiContent['meta_description'],
                    'tags' => $aiContent['tags'],
                ]
            ]);
        } catch (Throwable $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }


    private function saveConfig()
    {
        Configuration::updateValue('BULKGENIUS_AI_PROVIDER', Tools::getValue('ai_provider'));
        
        // Só atualizar chaves se não estiverem vazias (Segurança)
        $apiKey = Tools::getValue('api_key');
        if (!empty($apiKey)) {
            Configuration::updateValue('BULKGENIUS_AI_API_KEY', $apiKey);
        }
        
        $geminiKey = Tools::getValue('gemini_key');
        if (!empty($geminiKey)) {
            Configuration::updateValue('BULKGENIUS_AI_GEMINI_KEY', $geminiKey);
        }

        $groqKey = Tools::getValue('groq_key');
        if (!empty($groqKey)) {
            Configuration::updateValue('BULKGENIUS_AI_GROQ_KEY', $groqKey);
        }

        Configuration::updateValue('BULKGENIUS_AI_MODEL', Tools::getValue('ai_model'));
        Configuration::updateValue('BULKGENIUS_AI_LANG', Tools::getValue('lang'));
        Configuration::updateValue('BULKGENIUS_AI_CATEGORY', (int) Tools::getValue('id_category'));
        Configuration::updateValue('BULKGENIUS_AI_TAX_RULE', (int) Tools::getValue('id_tax_rule'));

        $this->confirmations[] = $this->l('Configurações guardadas com sucesso!');
    }

    private function processPreview()
    {
        header('Content-Type: application/json');
        $this->checkAjaxToken();

        if (!isset($_FILES['excel_file']) || $_FILES['excel_file']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['success' => false, 'message' => 'Erro ao carregar ficheiro.']);
            exit;
        }

        try {
            $reader = new ExcelReader($_FILES['excel_file']['tmp_name'], $_FILES['excel_file']['name']);
            $rows = $reader->getRows();
            echo json_encode(['success' => true, 'rows' => $rows, 'total' => count($rows)]);
        } catch (Throwable $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    private function processImportSingle()
    {
        @ini_set('display_errors', 0);
        @set_time_limit(120);
        header('Content-Type: application/json');
        $this->checkAjaxToken();

        // Liberta o lock da sessão PHP para que pedidos paralelos não fiquem à espera
        session_write_close();

        $productData = Tools::getValue('product');
        if (!$productData || !is_array($productData)) {
            echo json_encode(['success' => false, 'message' => 'Dados do produto em falta.']);
            exit;
        }

        // Sanitizar e validar campos obrigatórios
        $productData['name']              = strip_tags(trim((string) ($productData['name'] ?? '')));
        $productData['reference']         = preg_replace('/[^a-zA-Z0-9\-_\.\/]/', '', trim((string) ($productData['reference'] ?? '')));
        $productData['price']             = (float) str_replace(',', '.', preg_replace('/[^0-9,.]/', '', (string) ($productData['price'] ?? '0')));
        $productData['short_description'] = strip_tags(trim((string) ($productData['short_description'] ?? '')));

        if (empty($productData['name'])) {
            echo json_encode(['success' => false, 'message' => 'Nome do produto é obrigatório.']);
            exit;
        }

        $provider = Configuration::get('BULKGENIUS_AI_PROVIDER') ?: 'openai';
        $keyMap = [
            'openai' => ['key' => 'BULKGENIUS_AI_API_KEY',    'label' => 'OpenAI'],
            'gemini' => ['key' => 'BULKGENIUS_AI_GEMINI_KEY', 'label' => 'Google Gemini'],
            'groq'   => ['key' => 'BULKGENIUS_AI_GROQ_KEY',   'label' => 'Groq'],
        ];
        $providerInfo = $keyMap[$provider] ?? $keyMap['openai'];
        $apiKey = Configuration::get($providerInfo['key']);

        if (empty($apiKey)) {
            echo json_encode(['success' => false, 'message' => 'Chave da API não configurada.']);
            exit;
        }

        try {
            $aiService = AiServiceFactory::create();
            $creator = new ProductCreator(
                (int) Configuration::get('BULKGENIUS_AI_CATEGORY'),
                Configuration::get('BULKGENIUS_AI_LANG')
            );

            // Gerar conteúdo com IA
            $aiContent = $aiService->generateProductContent(
                $productData['name'],
                $productData['reference'],
                $productData['short_description'],
                Configuration::get('BULKGENIUS_AI_LANG')
            );

            // Criar produto no PrestaShop
            $productId = $creator->create([
                'name'              => $productData['name'],
                'reference'         => $productData['reference'],
                'price'             => $productData['price'],
                'description'       => $aiContent['description'],
                'description_short' => $aiContent['description_short'],
                'meta_title'        => $aiContent['meta_title'],
                'meta_description'  => $aiContent['meta_description'],
                'tags'              => $aiContent['tags'],
            ]);

            echo json_encode([
                'success' => true,
                'id'      => $productId,
                'name'    => $productData['name']
            ]);
        } catch (Throwable $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    private function processTestConnection()
    {
        header('Content-Type: application/json');
        $this->checkAjaxToken();
        
        $provider = strip_tags((string) Tools::getValue('ai_provider')) ?: null;
        $model    = strip_tags((string) Tools::getValue('ai_model')) ?: null;
        $apiKey   = '';

        if ($provider === 'openai') {
            $apiKey = (string) Tools::getValue('api_key');
            if (empty($apiKey)) {
                $apiKey = Configuration::get('BULKGENIUS_AI_API_KEY');
            }
        } elseif ($provider === 'gemini') {
            $apiKey = (string) Tools::getValue('gemini_key');
            if (empty($apiKey)) {
                $apiKey = Configuration::get('BULKGENIUS_AI_GEMINI_KEY');
            }
        } elseif ($provider === 'groq') {
            $apiKey = (string) Tools::getValue('groq_key');
            if (empty($apiKey)) {
                $apiKey = Configuration::get('BULKGENIUS_AI_GROQ_KEY');
            }
        }
        
        // Remover espaços e caracteres de controlo das chaves
        $apiKey = trim(preg_replace('/[\x00-\x1F\x7F]/', '', $apiKey));
        
        try {
            if (!$provider) {
                throw new Exception('Provedor não especificado.');
            }

            $aiService = AiServiceFactory::create((string)$provider, (string)$model, (string)$apiKey);
            $success = $aiService->testConnection();
            echo json_encode(['success' => $success, 'message' => 'Ligação estabelecida com sucesso!']);
        } catch (Throwable $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    public function checkAjaxToken()
    {
        $token = Tools::getValue('token');
        $validToken = Tools::getAdminTokenLite('AdminBulkGeniusAi');

        if (!$token || $token !== $validToken) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Sessão inválida ou token CSRF em falta. Por favor, recarregue a página.']);
            exit;
        }
    }

    private function renderPage()
    {
        $categories = Category::getSimpleCategories($this->context->language->id);
        $taxRules = TaxRulesGroup::getTaxRulesGroups(true);

        $this->context->smarty->assign([
            'module_dir'     => $this->module->getPathUri(),
            'action_url'     => $this->context->link->getAdminLink('AdminBulkGeniusAi'),
            'admin_token'    => Tools::getAdminTokenLite('AdminBulkGeniusAi'),
            'ai_provider'    => Configuration::get('BULKGENIUS_AI_PROVIDER'),

            // Segurança: Nunca enviar as chaves reais para o template (Inspector)
            'has_api_key'    => !empty(Configuration::get('BULKGENIUS_AI_API_KEY')),
            'has_gemini_key' => !empty(Configuration::get('BULKGENIUS_AI_GEMINI_KEY')),
            'has_groq_key'   => !empty(Configuration::get('BULKGENIUS_AI_GROQ_KEY')),

            'ai_model'       => Configuration::get('BULKGENIUS_AI_MODEL'),
            'lang'           => Configuration::get('BULKGENIUS_AI_LANG'),
            'id_category'    => (int) Configuration::get('BULKGENIUS_AI_CATEGORY'),
            'id_tax_rule'    => (int) Configuration::get('BULKGENIUS_AI_TAX_RULE'),
            'categories'     => $categories,
            'tax_rules'      => $taxRules,
            'confirmations'  => $this->confirmations,
            'errors'         => $this->errors,
        ]);

        $this->setTemplate('bulkgenius_ai/configure.tpl');
    }

    public function setMedia($isNewTheme = false)
    {
        parent::setMedia($isNewTheme);

        Media::addJsDef([
            'AI_IMPORTER' => [
                'actionUrl' => $this->context->link->getAdminLink('AdminBulkGeniusAi'),
                'token' => Tools::getAdminTokenLite('AdminBulkGeniusAi')
            ]
        ]);

        $this->addCSS($this->module->getPathUri() . 'views/css/admin.css');
        $this->addJS($this->module->getPathUri() . 'views/js/admin.js');
    }
}
