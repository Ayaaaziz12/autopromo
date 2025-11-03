<?php

class AutoPromoRule extends ObjectModel
{
    public $id_rule;
    public $name;
    public $conditions;
    public $actions;
    public $active;
    public $date_add;
    public $date_upd;

    public static $definition = array(
        'table' => 'autopromo_rules',
        'primary' => 'id_rule',
        'fields' => array(
            'name' => array('type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 255),
            'conditions' => array('type' => self::TYPE_HTML, 'validate' => 'isString', 'required' => true),
            'actions' => array('type' => self::TYPE_HTML, 'validate' => 'isString', 'required' => true),
            'active' => array('type' => self::TYPE_BOOL, 'validate' => 'isBool'),
            'date_add' => array('type' => self::TYPE_DATE, 'validate' => 'isDate'),
            'date_upd' => array('type' => self::TYPE_DATE, 'validate' => 'isDate'),
        ),
    );

    /**
     * Vérifie si une règle doit être déclenchée pour un client/produit donné
     */
    public function checkConditions($id_customer = null, $id_product = null)
    {
        $conditions = json_decode($this->conditions, true);
        
        if (!$conditions || !is_array($conditions)) {
            return false;
        }

        foreach ($conditions as $condition) {
            if (!$this->checkSingleCondition($condition, $id_customer, $id_product)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Vérifie une condition individuelle
     */
    private function checkSingleCondition($condition, $id_customer, $id_product)
    {
        if (!isset($condition['type']) || !isset($condition['value'])) {
            return false;
        }

        $condition_type = $condition['type'];
        $condition_value = $condition['value'];

        switch ($condition_type) {
            case 'total_spent':
                return $this->checkTotalSpent($id_customer, $condition_value);
            
            case 'order_count':
                return $this->checkOrderCount($id_customer, $condition_value);
            
            case 'stock_days':
                return $this->checkStockDays($id_product, $condition_value);
            
            case 'low_sales':
                return $this->checkLowSales($id_product, $condition_value);
            
            default:
                return false;
        }
    }

    /**
     * Condition: Montant total dépensé > X €
     */
    private function checkTotalSpent($id_customer, $min_amount)
    {
        if (!$id_customer) {
            return false;
        }

        $sql = "SELECT SUM(total_paid_tax_incl) as total_spent
                FROM " . _DB_PREFIX_ . "orders 
                WHERE id_customer = " . (int)$id_customer . "
                AND valid = 1";

        $result = Db::getInstance()->getValue($sql);
        $total_spent = (float)$result;

        return $total_spent > (float)$min_amount;
    }

    /**
     * Condition: Nombre de commandes > N
     */
    private function checkOrderCount($id_customer, $min_orders)
    {
        if (!$id_customer) {
            return false;
        }

        $sql = "SELECT COUNT(*) as order_count
                FROM " . _DB_PREFIX_ . "orders 
                WHERE id_customer = " . (int)$id_customer . "
                AND valid = 1";

        $order_count = (int)Db::getInstance()->getValue($sql);

        return $order_count > (int)$min_orders;
    }

    /**
     * Condition: Produit en stock depuis X jours
     */
    private function checkStockDays($id_product, $min_days)
    {
        if (!$id_product) {
            return false;
        }

        // Récupérer la date d'ajout du produit
        $sql = "SELECT date_add 
                FROM " . _DB_PREFIX_ . "product 
                WHERE id_product = " . (int)$id_product;

        $date_add = Db::getInstance()->getValue($sql);
        
        if (!$date_add) {
            return false;
        }

        // Calculer le nombre de jours depuis l'ajout
        $date_add_obj = new DateTime($date_add);
        $now = new DateTime();
        $days_in_stock = $now->diff($date_add_obj)->days;

        return $days_in_stock > (int)$min_days;
    }

    /**
     * Condition: Produit peu vendu (ventes < X)
     */
    private function checkLowSales($id_product, $max_sales)
    {
        if (!$id_product) {
            return false;
        }

        $sql = "SELECT SUM(product_quantity) as total_sales
                FROM " . _DB_PREFIX_ . "order_detail 
                WHERE product_id = " . (int)$id_product;

        $total_sales = (int)Db::getInstance()->getValue($sql);

        return $total_sales < (int)$max_sales;
    }

    /**
     * Récupère toutes les règles actives
     */
    public static function getActiveRules()
    {
        $sql = "SELECT * FROM " . _DB_PREFIX_ . "autopromo_rules 
                WHERE active = 1 
                ORDER BY date_add ASC";

        $results = Db::getInstance()->executeS($sql);
        $rules = array();

        foreach ($results as $result) {
            $rule = new AutoPromoRule();
            $rule->hydrate($result);
            $rules[] = $rule;
        }

        return $rules;
    }

    /**
     * Exécute les actions d'une règle
     */
    public function executeActions($id_customer = null, $id_product = null)
    {
        $actions = json_decode($this->actions, true);
        
        if (!$actions) {
            return array('error' => 'Aucune action définie');
        }

        $results = array();
        foreach ($actions as $action) {
            $results[] = $this->executeSingleAction($action, $id_customer, $id_product);
        }

        // Log l'exécution
        $this->logExecution($id_customer, $id_product, $results);

        return $results;
    }

    /**
     * Exécute une action individuelle
     */
    private function executeSingleAction($action, $id_customer, $id_product)
    {
        // Pour l'instant, on retourne juste un message de simulation
        // Les vraies actions seront implémentées demain
        return array(
            'action_type' => $action['type'],
            'action_value' => $action['value'],
            'executed' => true,
            'message' => 'Action simulée - À implémenter',
            'timestamp' => date('Y-m-d H:i:s')
        );
    }

    /**
     * Log l'exécution d'une règle
     */
    private function logExecution($id_customer, $id_product, $results)
    {
        $log_data = array(
            'id_rule' => (int)$this->id,
            'id_customer' => $id_customer ? (int)$id_customer : null,
            'id_product' => $id_product ? (int)$id_product : null,
            'action_type' => 'rule_execution',
            'details' => json_encode(array(
                'rule_name' => $this->name,
                'rule_id' => $this->id,
                'results' => $results,
                'execution_time' => date('Y-m-d H:i:s')
            )),
            'date_add' => date('Y-m-d H:i:s')
        );

        return Db::getInstance()->insert('autopromo_logs', $log_data);
    }

    /**
     * Récupère un résumé des conditions pour l'affichage
     */
    public function getConditionsSummary()
    {
        $conditions = json_decode($this->conditions, true);
        if (!$conditions) {
            return 'Aucune condition';
        }

        $summary = array();
        foreach ($conditions as $condition) {
            if (isset($condition['type']) && isset($condition['value'])) {
                $summary[] = $this->formatCondition($condition);
            }
        }

        return implode(' + ', $summary);
    }

    /**
     * Récupère un résumé des actions pour l'affichage
     */
    public function getActionsSummary()
    {
        $actions = json_decode($this->actions, true);
        if (!$actions) {
            return 'Aucune action';
        }

        $summary = array();
        foreach ($actions as $action) {
            if (isset($action['type']) && isset($action['value'])) {
                $summary[] = $this->formatAction($action);
            }
        }

        return implode(' + ', $summary);
    }

    /**
     * Formate une condition pour l'affichage
     */
    private function formatCondition($condition)
    {
        switch ($condition['type']) {
            case 'total_spent':
                return "Dépenses > " . $condition['value'] . "€";
            case 'order_count':
                return "Commandes > " . $condition['value'];
            case 'stock_days':
                return "Stock > " . $condition['value'] . " jours";
            case 'low_sales':
                return "Ventes < " . $condition['value'];
            default:
                return $condition['type'];
        }
    }

    /**
     * Formate une action pour l'affichage
     */
    private function formatAction($action)
    {
        switch ($action['type']) {
            case 'generate_coupon':
                return "Coupon " . $action['value'] . "%";
            case 'direct_discount':
                return "Remise " . $action['value'] . "%";
            case 'send_email':
                return "Email promo";
            default:
                return $action['type'];
        }
    }
}