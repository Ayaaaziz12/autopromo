<div class="panel">
    <div class="panel-heading">
        <i class="icon-cogs"></i> {l s='AutoPromo - Tableau de bord' mod='autopromo'}
    </div>
    <div class="panel-body">
        <div class="alert alert-info">
            <h4>{l s='Bienvenue dans AutoPromo!' mod='autopromo'}</h4>
            <p>{l s='Gérez vos règles de promotion automatiques pour booster vos ventes et fidéliser vos clients.' mod='autopromo'}</p>
        </div>

        <div class="row">
            <div class="col-md-3">
                <div class="panel panel-default">
                    <div class="panel-heading" style="background-color: #3498db; color: white;">
                        <i class="icon-gears"></i> {l s='Règles Actives' mod='autopromo'}
                    </div>
                    <div class="panel-body text-center">
                        <h2 style="color: #3498db; margin: 10px 0;">{$rules_count|intval}</h2>
                        <p>{l s='Règles configurées' mod='autopromo'}</p>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="panel panel-default">
                    <div class="panel-heading" style="background-color: #2ecc71; color: white;">
                        <i class="icon-tags"></i> {l s='Coupons' mod='autopromo'}
                    </div>
                    <div class="panel-body text-center">
                        <h2 style="color: #2ecc71; margin: 10px 0;">{$coupons_count|intval}</h2>
                        <p>{l s='Générés ce mois' mod='autopromo'}</p>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="panel panel-default">
                    <div class="panel-heading" style="background-color: #e74c3c; color: white;">
                        <i class="icon-bar-chart"></i> {l s='Performance' mod='autopromo'}
                    </div>
                    <div class="panel-body text-center">
                        <h2 style="color: #e74c3c; margin: 10px 0;">{$conversion_rate|floatval}%</h2>
                        <p>{l s='Taux de conversion' mod='autopromo'}</p>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="panel panel-default">
                    <div class="panel-heading" style="background-color: #f39c12; color: white;">
                        <i class="icon-clock-o"></i> {l s='Dernière Exécution' mod='autopromo'}
                    </div>
                    <div class="panel-body text-center">
                        <h4 style="color: #f39c12; margin: 10px 0;">{$last_execution|escape:'html':'UTF-8'}</h4>
                        <p>{l s='Des règles' mod='autopromo'}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <i class="icon-rocket"></i> {l s='Actions Rapides' mod='autopromo'}
                    </div>
                    <div class="panel-body">
                        <a href="{$link->getAdminLink('AdminAutoPromoRules')|escape:'html':'UTF-8'}" class="btn btn-primary btn-block" style="margin-bottom: 10px;">
                            <i class="icon-list"></i> {l s='Voir toutes les règles' mod='autopromo'}
                        </a>
                        <a href="{$link->getAdminLink('AdminAutoPromoRules')|escape:'html':'UTF-8'}&addautopromo_rules" class="btn btn-success btn-block" style="margin-bottom: 10px;">
                            <i class="icon-plus"></i> {l s='Créer une nouvelle règle' mod='autopromo'}
                        </a>
                        <button type="button" class="btn btn-warning btn-block" id="test-rules">
                            <i class="icon-play"></i> {l s='Tester les règles maintenant' mod='autopromo'}
                        </button>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <i class="icon-lightbulb"></i> {l s='Exemples de Règles' mod='autopromo'}
                    </div>
                    <div class="panel-body">
                        <div class="alert alert-success" style="margin-bottom: 10px;">
                            <strong>{l s='Client fidèle:' mod='autopromo'}</strong><br>
                            {l s='Si commandes > 5 ET dépenses > 500€ → Coupon 10%' mod='autopromo'}
                        </div>
                        <div class="alert alert-info" style="margin-bottom: 10px;">
                            <strong>{l s='Stock ancien:' mod='autopromo'}</strong><br>
                            {l s='Si produit en stock > 30 jours → Remise 20%' mod='autopromo'}
                        </div>
                        <div class="alert alert-warning">
                            <strong>{l s='Abandon panier:' mod='autopromo'}</strong><br>
                            {l s='Si panier abandonné > 2 jours → Coupon 5%' mod='autopromo'}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        $('#test-rules').click(function() {
            if (confirm('{$l s='Êtes-vous sûr de vouloir exécuter toutes les règles actives maintenant ?' mod='autopromo'|escape:'javascript'}')) {
                $.ajax({
                    url: '{$link->getAdminLink('AdminAutoPromoRules')|escape:'javascript':'UTF-8'}',
                    data: {
                        ajax: true,
                        action: 'testRules',
                        token: '{$token|escape:'javascript':'UTF-8'}'
                    },
                    success: function(response) {
                        alert('{$l s='Règles exécutées avec succès !' mod='autopromo'|escape:'javascript'}');
                    }
                });
            }
        });
    });
</script>
<script>
function testRule(id_rule) {
    if (confirm('Tester cette règle ?')) {
        $.ajax({
            url: '{$link->getAdminLink('AdminAutoPromoRules')|escape:'javascript':'UTF-8'}',
            data: {
                ajax: true,
                action: 'testRule',
                id_rule: id_rule,
                token: '{$token|escape:'javascript':'UTF-8'}'
            },
            success: function(response) {
                var result = JSON.parse(response);
                if (result.success) {
                    if (result.conditions_met) {
                        alert('✅ Conditions remplies ! Actions exécutées avec succès.');
                    } else {
                        alert('❌ Conditions non remplies pour le client test.');
                    }
                } else {
                    alert('❌ Erreur: ' + result.error);
                }
            }
        });
    }
}

// Tester toutes les règles
$('#test-rules').click(function() {
    if (confirm('Tester toutes les règles actives ?')) {
        $.ajax({
            url: '{$link->getAdminLink('AdminAutoPromoRules')|escape:'javascript':'UTF-8'}',
            data: {
                ajax: true,
                action: 'testAllRules',
                token: '{$token|escape:'javascript':'UTF-8'}'
            },
            success: function(response) {
                alert('✅ Toutes les règles ont été testées ! Consultez les logs pour les détails.');
            }
        });
    }
});
</script>