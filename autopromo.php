<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class AutoPromo extends Module
{
    public function __construct()
    {
        $this->name = 'autopromo';
        $this->tab = 'advertising_marketing';
        $this->version = '1.0.0';
        $this->author = 'Aya Aziz';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = [
            'min' => '1.7',
            'max' => _PS_VERSION_
        ];
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('AutoPromo - Promotions Intelligentes');
        $this->description = $this->l('Analyse le comportement clients et stocks pour générer des promotions automatiques');
    }

    public function install()
    {
        return parent::install() &&
            $this->installSQL() &&
            $this->registerHook('actionCronJob') &&
            $this->installTab();
    }

    public function uninstall()
    {
        return parent::uninstall() && 
               $this->uninstallSQL() && 
               $this->uninstallTab();
    }

    private function installTab()
{
    $tab = new Tab();
    $tab->class_name = 'AdminAutoPromoRules';
    $tab->module = $this->name;
    $tab->id_parent = (int)Tab::getIdFromClassName('IMPROVE'); // Ou 'SELL' selon ta version
    $tab->position = 1;
    
    $languages = Language::getLanguages();
    foreach ($languages as $lang) {
        $tab->name[$lang['id_lang']] = 'AutoPromo';
    }
    
    return $tab->add();
}

    private function uninstallTab()
    {
        $id_tab = (int)Tab::getIdFromClassName('AdminAutoPromoRules');
        if ($id_tab) {
            $tab = new Tab($id_tab);
            return $tab->delete();
        }
        return true;
    }

    private function installSQL()
    {
        $sql_files = array('install');
        foreach ($sql_files as $file) {
            $sql_file = $this->getLocalPath() . 'sql/' . $file . '.sql';
            if (!file_exists($sql_file)) {
                continue;
            }
            
            $sql_content = file_get_contents($sql_file);
            $sql_content = str_replace(
                array('PREFIX_', 'ENGINE_TYPE'),
                array(_DB_PREFIX_, _MYSQL_ENGINE_),
                $sql_content
            );
            
            $sql_requests = preg_split("/;\s*[\r\n]+/", $sql_content);
            
            foreach ($sql_requests as $request) {
                if (!empty($request)) {
                    if (!Db::getInstance()->execute(trim($request))) {
                        return false;
                    }
                }
            }
        }
        return true;
    }

    private function uninstallSQL()
    {
        $tables = array(
            'autopromo_rules',
            'autopromo_logs',
            'autopromo_generated_coupons'
        );
        
        foreach ($tables as $table) {
            $sql = "DROP TABLE IF EXISTS `" . _DB_PREFIX_ . $table . "`";
            if (!Db::getInstance()->execute($sql)) {
                return false;
            }
        }
        return true;
    }

    public function getContent()
{
    // Redirection vers le controller des règles
    Tools::redirectAdmin(
        $this->context->link->getAdminLink('AdminAutoPromoRules')
    );
}
}