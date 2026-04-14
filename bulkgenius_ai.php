<?php
/**
 * BulkGenius AI - Módulo PrestaShop 8.x
 * Importa produtos em massa via Excel com geração de conteúdo por IA
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class BulkGenius_Ai extends Module
{
    public function __construct()
    {
        $this->name = 'bulkgenius_ai';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'BulkGenius';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        if (file_exists(_PS_MODULE_DIR_ . $this->name . '/vendor/autoload.php')) {
            require_once _PS_MODULE_DIR_ . $this->name . '/vendor/autoload.php';
        }

        $this->displayName = $this->l('BulkGenius AI');
        $this->description = $this->l('Importa produtos em massa via Excel com descrições geradas por IA (OpenAI, Gemini, Groq).');
        $this->confirmUninstall = $this->l('Tem a certeza que quer desinstalar este módulo?');
        $this->ps_versions_compliancy = ['min' => '8.0.0', 'max' => _PS_VERSION ];
    }

    public function install()
    {
        return parent::install()
            && $this->installTab()
            && $this->installConfig();
    }

    public function uninstall()
    {
        return parent::uninstall()
            && $this->uninstallTab()
            && $this->uninstallConfig();
    }

    private function installTab()
    {
        $tab = new Tab();
        $tab->active = 1;
        $tab->class_name = 'AdminBulkGeniusAi';
        $tab->name = [];
        foreach (Language::getLanguages(true) as $lang) {
            $tab->name[$lang['id_lang']] = 'BulkGenius AI';
        }
        $tab->id_parent = (int) Tab::getIdFromClassName('AdminCatalog');
        $tab->module = $this->name;

        return $tab->add();
    }

    private function uninstallTab()
    {
        $idTab = (int) Tab::getIdFromClassName('AdminBulkGeniusAi');
        if ($idTab) {
            $tab = new Tab($idTab);
            return $tab->delete();
        }
        return true;
    }

    private function installConfig()
    {
        Configuration::updateValue('BULKGENIUS_AI_PROVIDER', 'openai');
        Configuration::updateValue('BULKGENIUS_AI_API_KEY', '');
        Configuration::updateValue('BULKGENIUS_AI_GEMINI_KEY', '');
        Configuration::updateValue('BULKGENIUS_AI_GROQ_KEY', '');
        Configuration::updateValue('BULKGENIUS_AI_MODEL', 'gpt-4o-mini');
        Configuration::updateValue('BULKGENIUS_AI_LANG', 'pt');
        Configuration::updateValue('BULKGENIUS_AI_CATEGORY', (int) Configuration::get('PS_HOME_CATEGORY'));
        return true;
    }

    private function uninstallConfig()
    {
        Configuration::deleteByName('BULKGENIUS_AI_PROVIDER');
        Configuration::deleteByName('BULKGENIUS_AI_API_KEY');
        Configuration::deleteByName('BULKGENIUS_AI_GEMINI_KEY');
        Configuration::deleteByName('BULKGENIUS_AI_GROQ_KEY');
        Configuration::deleteByName('BULKGENIUS_AI_MODEL');
        Configuration::deleteByName('BULKGENIUS_AI_LANG');
        Configuration::deleteByName('BULKGENIUS_AI_CATEGORY');
        return true;
    }

    public function getContent()
    {
        Tools::redirectAdmin(
            $this->context->link->getAdminLink('AdminBulkGeniusAi')
        );
    }
}
