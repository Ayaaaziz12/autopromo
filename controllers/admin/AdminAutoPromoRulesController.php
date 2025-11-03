<?php

class AdminAutoPromoRulesController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'autopromo_rules';
        $this->identifier = 'id_rule';
        $this->className = 'AutoPromoRule';
        $this->lang = false;
        $this->deleted = false;

        parent::__construct();

        $this->fields_list = array(
            'id_rule' => array(
                'title' => $this->l('ID'),
                'align' => 'center',
                'width' => 25,
                'class' => 'fixed-width-xs'
            ),
            'name' => array(
                'title' => $this->l('Nom de la règle'),
                'width' => 'auto',
                'filter_key' => 'a!name'
            ),
            'conditions_summary' => array(
                'title' => $this->l('Conditions'),
                'width' => 300,
                'callback' => 'getConditionsSummary',
                'search' => false
            ),
            'actions_summary' => array(
                'title' => $this->l('Actions'),
                'width' => 200,
                'callback' => 'getActionsSummary',
                'search' => false
            ),
            'active' => array(
                'title' => $this->l('Actif'),
                'active' => 'status',
                'type' => 'bool',
                'align' => 'center',
                'width' => 25,
                'orderby' => false
            ),
            'date_add' => array(
                'title' => $this->l('Créée le'),
                'type' => 'datetime',
                'width' => 100
            )
        );

        $this->bulk_actions = array(
            'enable' => array(
                'text' => $this->l('Activer'),
                'icon' => 'icon-power-off text-success',
                'confirm' => $this->l('Êtes-vous sûr de vouloir activer les règles sélectionnées ?')
            ),
            'disable' => array(
                'text' => $this->l('Désactiver'),
                'icon' => 'icon-power-off text-danger',
                'confirm' => $this->l('Êtes-vous sûr de vouloir désactiver les règles sélectionnées ?')
            ),
            'delete' => array(
                'text' => $this->l('Supprimer'),
                'icon' => 'icon-trash',
                'confirm' => $this->l('Êtes-vous sûr de vouloir supprimer les règles sélectionnées ?')
            )
        );
    }

    public static function getConditionsSummary($conditions, $row)
    {
        $conditions_array = json_decode($conditions, true);
        if (!$conditions_array) {
            return '<span class="badge badge-warning">Format invalide</span>';
        }

        $summary = [];
        foreach ($conditions_array as $condition) {
            if (isset($condition['type'])) {
                $summary[] = self::formatCondition($condition);
            }
        }

        return implode('<br>', $summary);
    }

    public static function getActionsSummary($actions, $row)
    {
        $actions_array = json_decode($actions, true);
        if (!$actions_array) {
            return '<span class="badge badge-warning">Format invalide</span>';
        }

        $summary = [];
        foreach ($actions_array as $action) {
            if (isset($action['type'])) {
                $summary[] = self::formatAction($action);
            }
        }

        return implode('<br>', $summary);
    }

    private static function formatCondition($condition)
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

    private static function formatAction($action)
    {
        switch ($action['type']) {
            case 'generate_coupon':
                return "Coupon: " . $action['value'] . "%";
            case 'direct_discount':
                return "Remise: " . $action['value'] . "%";
            case 'send_email':
                return "Email promo";
            default:
                return $action['type'];
        }
    }

    public function initPageHeaderToolbar()
    {
        if (empty($this->display)) {
            $this->page_header_toolbar_btn['new_rule'] = array(
                'href' => self::$currentIndex . '&add' . $this->table . '&token=' . $this->token,
                'desc' => $this->l('Ajouter une nouvelle règle'),
                'icon' => 'process-icon-new'
            );
        }

        parent::initPageHeaderToolbar();
    }

    public function renderList()
    {
        // Ajouter des actions
        $this->addRowAction('edit');
        $this->addRowAction('delete');
        $this->addRowAction('test');

        // Ajouter un indicateur de statut
        $this->list_no_link = true;

        return parent::renderList();
    }

    /**
     * Action de test
     */
    public function displayTestLink($token, $id, $name = null)
    {
        return '<a class="btn btn-default" href="#" onclick="testRule('.$id.')">
                <i class="icon-play"></i> Tester</a>';
    }

    public function renderForm()
    {
        // Types de conditions disponibles
        $condition_types = array(
            array(
                'id' => 'total_spent',
                'name' => $this->l('Montant total dépensé')
            ),
            array(
                'id' => 'order_count', 
                'name' => $this->l('Nombre de commandes')
            ),
            array(
                'id' => 'stock_days',
                'name' => $this->l('Produit en stock depuis X jours')
            ),
            array(
                'id' => 'low_sales',
                'name' => $this->l('Produit peu vendu')
            )
        );

        // Types d'actions disponibles
        $action_types = array(
            array(
                'id' => 'generate_coupon',
                'name' => $this->l('Générer un coupon')
            ),
            array(
                'id' => 'direct_discount',
                'name' => $this->l('Appliquer une remise directe')
            ),
            array(
                'id' => 'send_email',
                'name' => $this->l('Envoyer un email')
            )
        );

        $this->fields_form = array(
            'legend' => array(
                'title' => $this->l('Gérer une règle AutoPromo'),
                'icon' => 'icon-cog'
            ),
            'input' => array(
                array(
                    'type' => 'text',
                    'label' => $this->l('Nom de la règle'),
                    'name' => 'name',
                    'required' => true,
                    'col' => 6,
                    'hint' => $this->l('Donnez un nom significatif à cette règle')
                ),
                array(
                    'type' => 'html',
                    'name' => 'conditions_help',
                    'html_content' => '
                        <div class="alert alert-info">
                            <h4>'.$this->l('Conditions').'</h4>
                            <p>'.$this->l('Définissez quand cette règle doit être déclenchée').'</p>
                        </div>
                    '
                ),
                array(
                    'type' => 'select',
                    'label' => $this->l('Type de condition'),
                    'name' => 'condition_type',
                    'options' => array(
                        'query' => $condition_types,
                        'id' => 'id',
                        'name' => 'name'
                    ),
                    'col' => 4
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Valeur'),
                    'name' => 'condition_value',
                    'col' => 2,
                    'suffix' => $this->l('€ ou jours ou commandes')
                ),
                array(
                    'type' => 'html',
                    'name' => 'actions_help',
                    'html_content' => '
                        <div class="alert alert-info">
                            <h4>'.$this->l('Actions').'</h4>
                            <p>'.$this->l('Définissez ce qui se passe quand la règle est déclenchée').'</p>
                        </div>
                    '
                ),
                array(
                    'type' => 'select',
                    'label' => $this->l('Type d\'action'),
                    'name' => 'action_type',
                    'options' => array(
                        'query' => $action_types,
                        'id' => 'id',
                        'name' => 'name'
                    ),
                    'col' => 4
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Valeur de l\'action'),
                    'name' => 'action_value',
                    'col' => 2,
                    'suffix' => $this->l('% ou montant')
                ),
                array(
                    'type' => 'textarea',
                    'label' => $this->l('Message personnalisé (pour emails)'),
                    'name' => 'custom_message',
                    'rows' => 3,
                    'cols' => 40,
                    'hint' => $this->l('Message optionnel pour les notifications emails')
                ),
                array(
                    'type' => 'switch',
                    'label' => $this->l('Activer cette règle'),
                    'name' => 'active',
                    'required' => false,
                    'is_bool' => true,
                    'values' => array(
                        array(
                            'id' => 'active_on',
                            'value' => 1,
                            'label' => $this->l('Oui')
                        ),
                        array(
                            'id' => 'active_off',
                            'value' => 0,
                            'label' => $this->l('Non')
                        )
                    )
                )
            ),
            'submit' => array(
                'title' => $this->l('Enregistrer'),
                'class' => 'btn btn-default pull-right'
            ),
            'buttons' => array(
                'save-and-stay' => array(
                    'title' => $this->l('Enregistrer et rester'),
                    'name' => 'submitAdd'.$this->table.'AndStay',
                    'type' => 'submit',
                    'class' => 'btn btn-default pull-right',
                    'icon' => 'process-icon-save'
                )
            )
        );

        return parent::renderForm();
    }

    public function postProcess()
    {
        if (Tools::isSubmit('submitAdd'.$this->table) || Tools::isSubmit('submitAdd'.$this->table.'AndStay')) {
            // Préparer les données JSON pour les conditions et actions
            $conditions = array(
                array(
                    'type' => Tools::getValue('condition_type'),
                    'value' => Tools::getValue('condition_value')
                )
            );

            $actions = array(
                array(
                    'type' => Tools::getValue('action_type'),
                    'value' => Tools::getValue('action_value'),
                    'message' => Tools::getValue('custom_message')
                )
            );

            $_POST['conditions'] = json_encode($conditions);
            $_POST['actions'] = json_encode($actions);
        }

        return parent::postProcess();
    }

    public function initToolbar()
    {
        parent::initToolbar();
        
        // Personnaliser le bouton "Ajouter"
        $this->toolbar_btn['new'] = array(
            'href' => self::$currentIndex . '&add' . $this->table . '&token=' . $this->token,
            'desc' => $this->l('Ajouter une règle')
        );
    }

    /**
     * Action pour tester une règle
     */
    public function ajaxProcessTestRule()
    {
        $id_rule = (int)Tools::getValue('id_rule');
        
        if ($id_rule) {
            $rule = new AutoPromoRule($id_rule);
            
            if (Validate::isLoadedObject($rule)) {
                // Tester avec un client spécifique (premier client de la base)
                $test_customer = Db::getInstance()->getValue(
                    "SELECT id_customer FROM " . _DB_PREFIX_ . "customer ORDER BY id_customer LIMIT 1"
                );
                
                $conditions_met = $rule->checkConditions($test_customer);
                $actions_result = $conditions_met ? $rule->executeActions($test_customer) : array();
                
                die(json_encode(array(
                    'success' => true,
                    'conditions_met' => $conditions_met,
                    'actions_executed' => $actions_result,
                    'test_customer' => $test_customer,
                    'rule_name' => $rule->name
                )));
            }
        }
        
        die(json_encode(array(
            'success' => false,
            'error' => 'Règle non trouvée'
        )));
    }
}