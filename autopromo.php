<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class Autopromo extends Module
{
    public function __construct()
    {
        $this->name = 'autopromo';
        $this->tab = 'pricing_promotion';
        $this->version = '1.0.0';
        $this->author = 'Aya Aziz';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('AutoPromo - Promotions Automatiques');
        $this->description = $this->l('Crée automatiquement des promotions et coupons personnalisés selon des règles configurables.');
    }

    public function install()
    {
        return parent::install()
            && $this->registerHook('displayBackOfficeHeader')
            && $this->registerHook('displayCustomerAccount')
            && $this->installTab();
    }

    public function uninstall()
    {
        return parent::uninstall() && $this->uninstallTab();
    }

    private function installTab()
    {
        $tab = new Tab();
        $tab->active = 1;
        $tab->class_name = 'AdminAutopromo';
        $tab->name = [];
        foreach (Language::getLanguages(true) as $lang) {
            $tab->name[$lang['id_lang']] = 'AutoPromo';
        }
        $tab->id_parent = (int)Tab::getIdFromClassName('AdminParentModulesSf');
        $tab->module = $this->name;
        return $tab->add();
    }

    private function uninstallTab()
    {
        $id_tab = (int)Tab::getIdFromClassName('AdminAutopromo');
        if ($id_tab) {
            $tab = new Tab($id_tab);
            return $tab->delete();
        }
        return true;
    }

    public function hookDisplayBackOfficeHeader()
    {
        $this->context->controller->addCSS($this->_path . 'views/css/autopromo.css');
    }

    public function getContent()
    {
        return $this->display(__FILE__, 'views/templates/admin/configure.tpl');
    }
}
