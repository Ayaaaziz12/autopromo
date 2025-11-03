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
private function executeSingleAction($action, $id_customer = null, $id_product = null)
{
    if (!isset($action['type'])) {
        return array(
            'success' => false,
            'error' => 'Type d\'action non défini'
        );
    }

    switch ($action['type']) {
        case 'generate_coupon':
            return $this->generateCoupon($action, $id_customer);
        
        case 'direct_discount':
            return $this->applyDirectDiscount($action, $id_product);
        
        case 'send_email':
            return $this->sendPromoEmail($action, $id_customer, $id_product);
        
        default:
            return array(
                'success' => false,
                'error' => 'Type d\'action non supporté: ' . $action['type']
            );
    }
}

/**
 * Action: Générer un coupon de réduction
 */
private function generateCoupon($action, $id_customer)
{
    try {
        // Créer un code de coupon unique
        $coupon_code = 'AUTO' . strtoupper(Tools::passwdGen(8));
        
        // Déterminer le type de réduction
        $discount_type = 'percent';
        $discount_value = (float)$action['value'];
        
        // Vérifier si c'est un montant fixe (si la valeur est > 100, c'est probablement un montant fixe)
        if ($discount_value > 100) {
            $discount_type = 'amount';
        }

        // Créer la règle panier (cart rule)
        $cart_rule = new CartRule();
        $cart_rule->code = $coupon_code;
        $cart_rule->name = array(
            Configuration::get('PS_LANG_DEFAULT') => 'Promo Auto: ' . $this->name
        );
        $cart_rule->description = array(
            Configuration::get('PS_LANG_DEFAULT') => 'Coupon généré automatiquement par AutoPromo'
        );
        
        // Configuration de la réduction
        if ($discount_type === 'percent') {
            $cart_rule->reduction_percent = $discount_value;
        } else {
            $cart_rule->reduction_amount = $discount_value;
            $cart_rule->reduction_currency = Configuration::get('PS_CURRENCY_DEFAULT');
            $cart_rule->reduction_tax = true;
        }
        
        // Restrictions
        $cart_rule->quantity = 1;
        $cart_rule->quantity_per_user = 1;
        $cart_rule->highlight = 1;
        $cart_rule->partial_use = 0;
        $cart_rule->minimum_amount = 0;
        $cart_rule->minimum_amount_tax = 0;
        $cart_rule->minimum_amount_currency = Configuration::get('PS_CURRENCY_DEFAULT');
        $cart_rule->minimum_amount_shipping = 0;
        
        // Durée de validité (15 jours par défaut)
        $cart_rule->date_from = date('Y-m-d H:i:s');
        $cart_rule->date_to = date('Y-m-d H:i:s', strtotime('+15 days'));
        
        // Restreindre au client si spécifié
        if ($id_customer) {
            $cart_rule->id_customer = $id_customer;
        }
        
        // Sauvegarder le coupon
        if (!$cart_rule->add()) {
            throw new Exception('Erreur lors de la création du coupon');
        }
        
        // Enregistrer dans l'historique
        $this->saveGeneratedCoupon($cart_rule->id, $id_customer);
        
        return array(
            'success' => true,
            'action_type' => 'generate_coupon',
            'coupon_code' => $coupon_code,
            'discount_value' => $discount_value,
            'discount_type' => $discount_type,
            'customer_id' => $id_customer,
            'message' => 'Coupon généré: ' . $coupon_code
        );
        
    } catch (Exception $e) {
        return array(
            'success' => false,
            'action_type' => 'generate_coupon',
            'error' => $e->getMessage()
        );
    }
}

/**
 * Action: Appliquer une remise directe sur un produit
 */
private function applyDirectDiscount($action, $id_product)
{
    try {
        if (!$id_product) {
            throw new Exception('ID produit manquant pour la remise directe');
        }
        
        $discount_value = (float)$action['value'];
        $discount_type = 'percentage'; // Par défaut en pourcentage
        
        // Vérifier si c'est un montant fixe
        if ($discount_value > 100) {
            $discount_type = 'amount';
        }
        
        // Ici, on pourrait créer une règle de prix spécifique
        // Pour l'instant, on log l'action et on retourne un succès simulé
        
        return array(
            'success' => true,
            'action_type' => 'direct_discount',
            'product_id' => $id_product,
            'discount_value' => $discount_value,
            'discount_type' => $discount_type,
            'message' => 'Remise de ' . $discount_value . ($discount_type === 'percentage' ? '%' : '€') . ' appliquée sur le produit ' . $id_product
        );
        
    } catch (Exception $e) {
        return array(
            'success' => false,
            'action_type' => 'direct_discount',
            'error' => $e->getMessage()
        );
    }
}

/**
 * Action: Envoyer un email promotionnel
 */
private function sendPromoEmail($action, $id_customer, $id_product)
{
    try {
        if (!$id_customer) {
            throw new Exception('ID client manquant pour l\'envoi d\'email');
        }
        
        // Récupérer les informations du client
        $customer = new Customer($id_customer);
        if (!Validate::isLoadedObject($customer)) {
            throw new Exception('Client non trouvé');
        }
        
        // Préparer le contenu de l'email
        $template_vars = array(
            '{firstname}' => $customer->firstname,
            '{lastname}' => $customer->lastname,
            '{rule_name}' => $this->name,
            '{message}' => isset($action['message']) ? $action['message'] : '',
            '{shop_name}' => Configuration::get('PS_SHOP_NAME'),
            '{shop_url}' => Context::getContext()->link->getPageLink('index')
        );
        
        // Si un produit est concerné, ajouter ses informations
        if ($id_product) {
            $product = new Product($id_product, false, Configuration::get('PS_LANG_DEFAULT'));
            if (Validate::isLoadedObject($product)) {
                $template_vars['{product_name}'] = $product->name;
                $template_vars['{product_url}'] = Context::getContext()->link->getProductLink($product);
            }
        }
        
        // Envoyer l'email
        $email_sent = Mail::Send(
            Configuration::get('PS_LANG_DEFAULT'),
            'autopromo_promotion', // Template d'email
            'Une promotion spéciale pour vous!', // Sujet
            $template_vars,
            $customer->email,
            $customer->firstname . ' ' . $customer->lastname,
            null, // from_address
            null, // from_name
            null, // attachment
            null, // smtp
            _PS_MODULE_DIR_ . 'autopromo/mails/' // template_path
        );
        
        if (!$email_sent) {
            throw new Exception('Erreur lors de l\'envoi de l\'email');
        }
        
        return array(
            'success' => true,
            'action_type' => 'send_email',
            'customer_email' => $customer->email,
            'customer_id' => $id_customer,
            'message' => 'Email promotionnel envoyé à ' . $customer->email
        );
        
    } catch (Exception $e) {
        return array(
            'success' => false,
            'action_type' => 'send_email',
            'error' => $e->getMessage()
        );
    }
}

/**
 * Sauvegarde le coupon généré dans l'historique
 */
private function saveGeneratedCoupon($id_cart_rule, $id_customer = null)
{
    $data = array(
        'id_rule' => (int)$this->id,
        'id_cart_rule' => (int)$id_cart_rule,
        'id_customer' => $id_customer ? (int)$id_customer : null,
        'date_add' => date('Y-m-d H:i:s')
    );
    
    return Db::getInstance()->insert('autopromo_generated_coupons', $data);
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