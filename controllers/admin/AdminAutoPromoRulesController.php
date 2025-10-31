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
                'width' => 25
            ),
            'name' => array(
                'title' => $this->l('Nom de la règle'),
                'width' => 'auto'
            ),
            'active' => array(
                'title' => $this->l('Actif'),
                'active' => 'status',
                'type' => 'bool',
                'align' => 'center',
                'width' => 25
            ),
            'date_add' => array(
                'title' => $this->l('Date de création'),
                'type' => 'datetime',
                'width' => 100
            )
        );

        $this->bulk_actions = array(
            'delete' => array(
                'text' => $this->l('Supprimer la sélection'),
                'icon' => 'icon-trash',
                'confirm' => $this->l('Êtes-vous sûr de vouloir supprimer les éléments sélectionnés ?')
            )
        );
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
        $this->addRowAction('edit');
        $this->addRowAction('delete');

        return parent::renderList();
    }

    public function renderForm()
    {
        $this->fields_form = array(
            'legend' => array(
                'title' => $this->l('Règle de promotion AutoPromo'),
                'icon' => 'icon-cog'
            ),
            'input' => array(
                array(
                    'type' => 'text',
                    'label' => $this->l('Nom de la règle'),
                    'name' => 'name',
                    'required' => true,
                    'col' => 6
                ),
                array(
                    'type' => 'textarea',
                    'label' => $this->l('Conditions (JSON)'),
                    'name' => 'conditions',
                    'required' => true,
                    'rows' => 5,
                    'cols' => 40,
                    'hint' => $this->l('Définir les conditions au format JSON')
                ),
                array(
                    'type' => 'textarea',
                    'label' => $this->l('Actions (JSON)'),
                    'name' => 'actions',
                    'required' => true,
                    'rows' => 5,
                    'cols' => 40,
                    'hint' => $this->l('Définir les actions au format JSON')
                ),
                array(
                    'type' => 'switch',
                    'label' => $this->l('Actif'),
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
            )
        );

        return parent::renderForm();
    }
}