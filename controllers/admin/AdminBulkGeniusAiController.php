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
                case 'import':
                    $this->processImport();
                    break;
                case 'preview':
                    $this->processPreview();
                    break;
                case 'test_connection':
                    $this->processTestConnection();
                    break;
            }
            exit;
        }

        $this->renderPage();
    }

    private function saveConfig()
    {
        Configuration::updateValue('BULKGENIUS_AI_PROVIDER', Tools::getValue('ai_provider'));
        Configuration::updateValue('BULKGENIUS_AI_API_KEY', Tools::getValue('api_key'));
        Configuration::updateValue('BULKGENIUS_AI_GEMINI_KEY', Tools::getValue('gemini_key'));
        Configuration::updateValue('BULKGENIUS_AI_GROQ_KEY', Tools::getValue('groq_key'));
        Configuration::updateValue('BULKGENIUS_AI_MODEL', Tools::getValue('ai_model'));
        Configuration::updateValue('BULKGENIUS_AI_LANG', Tools::getValue('lang'));
        Configuration::updateValue('BULKGENIUS_AI_CATEGORY', (int) Tools::getValue('id_category'));

        $this->confirmations[] = $this->l('Configurações guardadas com sucesso!');
    }

    private function processPreview()
    {
        header('Content-Type: application/json');

        if (!isset($_FILES['excel_file']) || $_FILES['excel_file']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['success' => false, 'message' => 'Erro ao carregar ficheiro.']);
            exit;
        }

        try {
            $reader = new \BulkGeniusAi\ExcelReader($_FILES['excel_file']['tmp_name'], $_FILES['excel_file']['name']);
            $rows = $reader->getRows();
            echo json_encode(['success' => true, 'rows' => array_slice($rows, 0, 5), 'total' => count($rows)]);
        } catch (Throwable $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    private function processImport()
    {
        @ini_set('display_errors', 1);
        @error_reporting(E_ALL);
        @set_time_limit(600); // 10 minutos
        @ini_set('max_execution_time', 600);
        @ini_set('memory_limit', '512M');

        header('Content-Type: application/json');

        $provider = Configuration::get('BULKGENIUS_AI_PROVIDER') ?: 'openai';
        $keyMap = [
            'openai' => ['key' => 'BULKGENIUS_AI_API_KEY',    'label' => 'OpenAI'],
            'gemini' => ['key' => 'BULKGENIUS_AI_GEMINI_KEY', 'label' => 'Google Gemini'],
            'groq'   => ['key' => 'BULKGENIUS_AI_GROQ_KEY',   'label' => 'Groq'],
        ];
        $providerInfo = $keyMap[$provider] ?? $keyMap['openai'];
        $apiKey = Configuration::get($providerInfo['key']);

        if (empty($apiKey)) {
            echo json_encode(['success' => false, 'message' => 'Chave API ' . $providerInfo['label'] . ' não configurada.']);
            exit;
        }

        if (!isset($_FILES['excel_file']) || $_FILES['excel_file']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['success' => false, 'message' => 'Erro ao carregar ficheiro Excel.']);
            exit;
        }

        try {
            $reader      = new \BulkGeniusAi\ExcelReader($_FILES['excel_file']['tmp_name'], $_FILES['excel_file']['name']);
            $rows        = $reader->getRows();
            $aiService   = \BulkGeniusAi\AiServiceFactory::create();
            $creator     = new \BulkGeniusAi\ProductCreator(
                (int) Configuration::get('BULKGENIUS_AI_CATEGORY'),
                Configuration::get('BULKGENIUS_AI_LANG')
            );

            $results = [];
            $errors  = [];

            foreach ($rows as $index => $row) {
                try {
                    // Gerar conteúdo com IA
                    $aiContent = $aiService->generateProductContent(
                        $row['name'],
                        $row['reference'],
                        $row['short_description'],
                        Configuration::get('BULKGENIUS_AI_LANG')
                    );

                    // Criar produto no PrestaShop
                    $productId = $creator->create([
                        'name'             => $row['name'],
                        'reference'        => $row['reference'],
                        'price'            => $row['price'],
                        'description'      => $aiContent['description'],
                        'description_short'=> $aiContent['description_short'],
                        'meta_title'       => $aiContent['meta_title'],
                        'meta_description' => $aiContent['meta_description'],
                        'tags'             => $aiContent['tags'],
                    ]);

                    $results[] = [
                        'row'  => $index + 1,
                        'name' => $row['name'],
                        'id'   => $productId,
                    ];

                    // Pequena pausa para não sobrecarregar a API
                    usleep(500000); // 0.5s
                } catch (Throwable $e) {
                    $errors[] = ['row' => $index + 1, 'name' => $row['name'] ?? '?', 'error' => $e->getMessage()];
                }
            }

            echo json_encode([
                'success' => true,
                'created' => count($results),
                'errors'  => count($errors),
                'results' => $results,
                'error_details' => $errors,
            ]);
        } catch (Throwable $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    private function processTestConnection()
    {
        header('Content-Type: application/json');
        
        $provider = Tools::getValue('ai_provider') ?: null;
        $model    = Tools::getValue('ai_model') ?: null;
        $apiKey   = '';

        if ($provider === 'openai') {
            $apiKey = Tools::getValue('api_key') ?: '';
        } elseif ($provider === 'gemini') {
            $apiKey = Tools::getValue('gemini_key') ?: '';
        } elseif ($provider === 'groq') {
            $apiKey = Tools::getValue('groq_key') ?: '';
        }
        
        try {
            if (!$provider) {
                throw new Exception('Provedor não especificado.');
            }

            $aiService = \BulkGeniusAi\AiServiceFactory::create((string)$provider, (string)$model, (string)$apiKey);
            $success = $aiService->testConnection();
            echo json_encode(['success' => $success, 'message' => 'Ligação estabelecida com sucesso!']);
        } catch (Throwable $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    private function renderPage()
    {
        $categories = Category::getSimpleCategories($this->context->language->id);

        $this->context->smarty->assign([
            'module_dir'     => $this->module->getPathUri(),
            'action_url'     => $this->context->link->getAdminLink('AdminBulkGeniusAi'),
            'admin_token'    => Tools::getAdminTokenLite('AdminBulkGeniusAi'),
            'ai_provider'    => Configuration::get('BULKGENIUS_AI_PROVIDER'),
            'api_key'        => Configuration::get('BULKGENIUS_AI_API_KEY'),
            'gemini_key'     => Configuration::get('BULKGENIUS_AI_GEMINI_KEY'),
            'groq_key'       => Configuration::get('BULKGENIUS_AI_GROQ_KEY'),
            'ai_model'       => Configuration::get('BULKGENIUS_AI_MODEL'),
            'lang'           => Configuration::get('BULKGENIUS_AI_LANG'),
            'id_category'    => (int) Configuration::get('BULKGENIUS_AI_CATEGORY'),
            'categories'     => $categories,
            'confirmations'  => $this->confirmations,
            'errors'         => $this->errors,
        ]);

        $this->setTemplate('main.tpl');
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
