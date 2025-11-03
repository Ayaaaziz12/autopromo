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
        $this->author = 'AyaAziz';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = [
            'min' => '1.7',
            'max' => _PS_VERSION_
        ];
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('AutoPromo - Promotions Intelligentes');
        $this->description = $this->l('Analyse le comportement clients et stocks pour générer des promotions automatiques');
        
        // Charger les modèles automatiquement
        $this->loadModels();
    }

    public function install()
    {
        return parent::install() &&
            $this->installSQL() &&
            $this->registerHook('actionCronJob') &&
            $this->installTab() &&
            $this->setupCronTask();
    }

    public function uninstall()
    {
        return parent::uninstall() && 
               $this->uninstallSQL() && 
               $this->uninstallTab() &&
               $this->removeCronTask();
    }

    /**
     * Charge automatiquement les classes de modèles
     */
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

    private function installTab()
    {
        $tab = new Tab();
        $tab->class_name = 'AdminAutoPromoRules';
        $tab->module = $this->name;
        $tab->id_parent = (int)Tab::getIdFromClassName('SELL');
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
        Tools::redirectAdmin(
            $this->context->link->getAdminLink('AdminAutoPromoRules')
        );
    }

    /**
     * =============================================
     * MOTEUR DE RÈGLES PRINCIPAL
     * =============================================
     */

    /**
     * Exécute toutes les règles actives
     * @return array Résultats de l'exécution
     */
    public function runAllRules()
    {
        $start_time = microtime(true);
        $active_rules = AutoPromoRule::getActiveRules();
        $global_results = array(
            'total_rules' => count($active_rules),
            'rules_executed' => 0,
            'total_actions' => 0,
            'execution_time' => 0,
            'rules_details' => array()
        );

        $this->logMessage("🚀 Démarrage de l'exécution des règles - " . count($active_rules) . " règles actives");

        foreach ($active_rules as $rule) {
            $rule_results = $this->processRule($rule);
            if ($rule_results) {
                $global_results['rules_executed']++;
                $global_results['total_actions'] += count($rule_results);
                $global_results['rules_details'][$rule->id] = array(
                    'rule_name' => $rule->name,
                    'results' => $rule_results
                );
            }
        }

        $global_results['execution_time'] = round(microtime(true) - $start_time, 2);

        $this->logGlobalExecution($global_results);
        
        $this->logMessage("✅ Exécution terminée - " . 
            $global_results['rules_executed'] . "/" . 
            $global_results['total_rules'] . " règles exécutées - " .
            $global_results['total_actions'] . " actions - " .
            $global_results['execution_time'] . "s"
        );

        return $global_results;
    }

    /**
     * Traite une règle spécifique
     */
    private function processRule($rule)
    {
        $conditions = json_decode($rule->conditions, true);
        if (!$conditions) {
            $this->logMessage("❌ Règle #{$rule->id} - Conditions JSON invalides");
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
                $this->logMessage("⚠️ Règle #{$rule->id} - Type de règle inconnu");
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
            "SELECT id_customer, firstname, lastname, email 
             FROM " . _DB_PREFIX_ . "customer 
             WHERE active = 1 AND deleted = 0"
        );

        $this->logMessage("👥 Traitement règle '{$rule->name}' - " . count($customers) . " clients à analyser");

        $customers_processed = 0;
        $actions_triggered = 0;

        foreach ($customers as $customer) {
            if ($rule->checkConditions($customer['id_customer'])) {
                $action_results = $rule->executeActions($customer['id_customer']);
                $results[] = array(
                    'customer_id' => $customer['id_customer'],
                    'customer_name' => $customer['firstname'] . ' ' . $customer['lastname'],
                    'actions' => $action_results
                );
                $actions_triggered++;
            }
            $customers_processed++;
        }

        $this->logMessage("✅ Règle '{$rule->name}' - {$customers_processed} clients traités, {$actions_triggered} actions déclenchées");

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
            "SELECT id_product, reference 
             FROM " . _DB_PREFIX_ . "product 
             WHERE active = 1"
        );

        $this->logMessage("📦 Traitement règle '{$rule->name}' - " . count($products) . " produits à analyser");

        $products_processed = 0;
        $actions_triggered = 0;

        foreach ($products as $product) {
            if ($rule->checkConditions(null, $product['id_product'])) {
                $action_results = $rule->executeActions(null, $product['id_product']);
                $results[] = array(
                    'product_id' => $product['id_product'],
                    'product_reference' => $product['reference'],
                    'actions' => $action_results
                );
                $actions_triggered++;
            }
            $products_processed++;
        }

        $this->logMessage("✅ Règle '{$rule->name}' - {$products_processed} produits traités, {$actions_triggered} actions déclenchées");

        return $results;
    }

    /**
     * =============================================
     * SYSTÈME CRON
     * =============================================
     */

    /**
     * Hook pour exécution CRON
     */
    public function hookActionCronJob($params)
    {
        $this->logMessage("⏰ Déclenchement CRON - Exécution automatique des règles");
        $results = $this->runAllRules();
        return true;
    }

    /**
     * Configure la tâche CRON
     */
    private function setupCronTask()
    {
        // Pour Prestashop 1.7, on utilise le système de tâches planifiées
        $cron_url = $this->context->link->getModuleLink(
            $this->name, 
            'cron',
            array('token' => $this->getCronToken()),
            true
        );

        $this->logMessage("🔧 Configuration CRON - URL: " . $cron_url);

        return true;
    }

    /**
     * Supprime la tâche CRON
     */
    private function removeCronTask()
    {
        // Nettoyage des tâches CRON
        return true;
    }

    /**
     * Génère un token sécurisé pour le CRON
     */
    private function getCronToken()
    {
        return md5(_COOKIE_KEY_ . $this->name);
    }

    /**
     * =============================================
     * LOGGING ET SUIVI
     * =============================================
     */

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
                'total_rules' => $results['total_rules'],
                'rules_executed' => $results['rules_executed'],
                'total_actions' => $results['total_actions'],
                'execution_time' => $results['execution_time'],
                'rules_details' => $results['rules_details']
            )),
            'date_add' => date('Y-m-d H:i:s')
        );

        return Db::getInstance()->insert('autopromo_logs', $log_data);
    }

    /**
     * Message de log simple
     */
    private function logMessage($message)
    {
        // Log dans la table des logs
        $log_data = array(
            'id_rule' => 0,
            'id_customer' => null,
            'id_product' => null,
            'action_type' => 'system_message',
            'details' => $message,
            'date_add' => date('Y-m-d H:i:s')
        );

        Db::getInstance()->insert('autopromo_logs', $log_data);

        // Aussi dans les logs Prestashop
        PrestaShopLogger::addLog('[AutoPromo] ' . $message, 1);
    }

    /**
     * =============================================
     * MÉTHODES UTILITAIRES POUR L'ADMIN
     * =============================================
     */

    /**
     * Exécute les règles manuellement (pour l'admin)
     */
    public function executeRulesManually()
    {
        return $this->runAllRules();
    }

    /**
     * Récupère les statistiques pour le tableau de bord
     */
    public function getDashboardStats()
    {
        $stats = array();

        // Nombre de règles actives
        $stats['active_rules'] = (int)Db::getInstance()->getValue(
            "SELECT COUNT(*) FROM " . _DB_PREFIX_ . "autopromo_rules WHERE active = 1"
        );

        // Coupons générés ce mois
        $stats['coupons_this_month'] = (int)Db::getInstance()->getValue(
            "SELECT COUNT(*) FROM " . _DB_PREFIX_ . "autopromo_generated_coupons 
             WHERE MONTH(date_add) = MONTH(NOW()) AND YEAR(date_add) = YEAR(NOW())"
        );

        // Dernière exécution
        $stats['last_execution'] = Db::getInstance()->getValue(
            "SELECT date_add FROM " . _DB_PREFIX_ . "autopromo_logs 
             WHERE action_type = 'global_execution' 
             ORDER BY date_add DESC LIMIT 1"
        );

        // Nombre total d'actions exécutées
        $stats['total_actions'] = (int)Db::getInstance()->getValue(
            "SELECT COUNT(*) FROM " . _DB_PREFIX_ . "autopromo_logs 
             WHERE action_type = 'rule_execution'"
        );

        return $stats;
    }
    /**
 * =============================================
 * SYSTÈME CRON COMPLET
 * =============================================
 */

/**
 * Génère l'URL CRON sécurisée
 */
public function getCronUrl()
{
    return $this->context->link->getModuleLink(
        $this->name,
        'cron',
        array('token' => $this->getCronToken()),
        true
    );
}

/**
 * Vérifie si le CRON est actif et fonctionnel
 */
public function checkCronStatus()
{
    $last_execution = Db::getInstance()->getValue(
        "SELECT date_add FROM " . _DB_PREFIX_ . "autopromo_logs 
         WHERE action_type = 'global_execution' 
         ORDER BY date_add DESC LIMIT 1"
    );
    
    if (!$last_execution) {
        return array(
            'status' => 'unknown',
            'message' => 'Aucune exécution enregistrée'
        );
    }
    
    $last_execution_time = strtotime($last_execution);
    $time_diff = time() - $last_execution_time;
    
    if ($time_diff < 3600) { // Moins d'une heure
        return array(
            'status' => 'active',
            'message' => 'Dernière exécution: ' . $this->timeElapsedString($last_execution),
            'last_execution' => $last_execution
        );
    } elseif ($time_diff < 86400) { // Moins d'un jour
        return array(
            'status' => 'warning', 
            'message' => 'Dernière exécution: ' . $this->timeElapsedString($last_execution),
            'last_execution' => $last_execution
        );
    } else {
        return array(
            'status' => 'inactive',
            'message' => 'Dernière exécution: ' . $this->timeElapsedString($last_execution),
            'last_execution' => $last_execution
        );
    }
}

/**
 * Formate l'intervalle de temps
 */
private function timeElapsedString($datetime, $full = false)
{
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = array(
        'y' => 'an',
        'm' => 'mois',
        'w' => 'semaine',
        'd' => 'jour',
        'h' => 'heure',
        'i' => 'minute',
        's' => 'seconde',
    );
    
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? 'il y a ' . implode(', ', $string) : 'à l\'instant';
}

/**
 * Teste la connexion CRON
 */
public function testCronConnection()
{
    $cron_url = $this->getCronUrl();
    
    // Test simple avec file_get_contents ou cURL
    $context = stream_context_create(array(
        'http' => array(
            'timeout' => 10
        )
    ));
    
    try {
        $response = @file_get_contents($cron_url, false, $context);
        if ($response !== false) {
            return array(
                'success' => true,
                'message' => 'Connexion CRON réussie'
            );
        } else {
            return array(
                'success' => false,
                'message' => 'Impossible de contacter l\'URL CRON'
            );
        }
    } catch (Exception $e) {
        return array(
            'success' => false,
            'message' => 'Erreur: ' . $e->getMessage()
        );
    }
}
}