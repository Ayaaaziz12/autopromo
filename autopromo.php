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
        $this->loadModels();
    }
    private function loadModels()
{
    $models_path = $this->getLocalPath() . 'models/';
    
    if (is_dir($models_path)) {
        $models = scandir($models_path);
        
        foreach ($models as $model) {
            if (pathinfo($model, PATHINFO_EXTENSION) === 'php') {
                require_once $models_path . $model;
            }
        }
    }
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
    $tab->id_parent = (int)Tab::getIdFromClassName('IMPROVE'); 
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
/**
 * Exécute toutes les règles actives
 */
public function runAllRules()
{
    $active_rules = AutoPromoRule::getActiveRules();
    $results = array();

    foreach ($active_rules as $rule) {
        $rule_results = $this->processRule($rule);
        if ($rule_results) {
            $results[$rule->id] = array(
                'rule_name' => $rule->name,
                'results' => $rule_results
            );
        }
    }

    $this->logGlobalExecution($results);
    return $results;
}

/**
 * Traite une règle spécifique
 */
private function processRule($rule)
{
    $conditions = json_decode($rule->conditions, true);
    if (!$conditions) {
        return null;
    }

    // Déterminer le type de règle
    $rule_type = $this->getRuleType($conditions);

    switch ($rule_type) {
        case 'customer':
            return $this->processCustomerRule($rule);
        case 'product':
            return $this->processProductRule($rule);
        default:
            return null;
    }
}

/**
 * Détermine le type de règle
 */
private function getRuleType($conditions)
{
    foreach ($conditions as $condition) {
        if (in_array($condition['type'], array('total_spent', 'order_count'))) {
            return 'customer';
        }
        if (in_array($condition['type'], array('stock_days', 'low_sales'))) {
            return 'product';
        }
    }
    return 'unknown';
}

/**
 * Traite une règle basée sur les clients
 */
private function processCustomerRule($rule)
{
    $results = array();
    
    // Récupérer tous les clients actifs
    $customers = Db::getInstance()->executeS(
        "SELECT id_customer FROM " . _DB_PREFIX_ . "customer WHERE active = 1"
    );

    foreach ($customers as $customer) {
        if ($rule->checkConditions($customer['id_customer'])) {
            $action_results = $rule->executeActions($customer['id_customer']);
            $results[] = array(
                'customer_id' => $customer['id_customer'],
                'actions' => $action_results
            );
        }
    }

    return $results;
}

/**
 * Traite une règle basée sur les produits
 */
private function processProductRule($rule)
{
    $results = array();
    
    // Récupérer tous les produits actifs
    $products = Db::getInstance()->executeS(
        "SELECT id_product FROM " . _DB_PREFIX_ . "product WHERE active = 1"
    );

    foreach ($products as $product) {
        if ($rule->checkConditions(null, $product['id_product'])) {
            $action_results = $rule->executeActions(null, $product['id_product']);
            $results[] = array(
                'product_id' => $product['id_product'],
                'actions' => $action_results
            );
        }
    }

    return $results;
}

/**
 * Log l'exécution globale
 */
private function logGlobalExecution($results)
{
    $log_data = array(
        'id_rule' => 0,
        'id_customer' => null,
        'id_product' => null,
        'action_type' => 'global_execution',
        'details' => json_encode(array(
            'timestamp' => date('Y-m-d H:i:s'),
            'total_rules_executed' => count($results),
            'results' => $results
        )),
        'date_add' => date('Y-m-d H:i:s')
    );

    return Db::getInstance()->insert('autopromo_logs', $log_data);
}
}