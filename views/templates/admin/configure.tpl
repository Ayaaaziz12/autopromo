<div class="panel">
    <div class="panel-heading">
        <i class="icon-cogs"></i> {l s='AutoPromo - Promotions Intelligentes' mod='autopromo'}
    </div>
    <div class="panel-body">
        <div class="alert alert-info">
            <h4>{l s='Bienvenue dans AutoPromo!' mod='autopromo'}</h4>
            <p>{l s='Ce module vous permet de créer des règles automatiques pour générer des promotions basées sur le comportement des clients et l\'état des stocks.' mod='autopromo'}</p>
        </div>

        <div class="row">
            <div class="col-md-4">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <i class="icon-gears"></i> {l s='Règles' mod='autopromo'}
                    </div>
                    <div class="panel-body text-center">
                        <h3>0</h3>
                        <p>{l s='Règles actives' mod='autopromo'}</p>
                        <a href="{$link->getAdminLink('AdminAutoPromoRules')|escape:'html':'UTF-8'}" class="btn btn-primary">
                            {l s='Gérer les règles' mod='autopromo'}
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <i class="icon-tags"></i> {l s='Coupons générés' mod='autopromo'}
                    </div>
                    <div class="panel-body text-center">
                        <h3>0</h3>
                        <p>{l s='Ce mois' mod='autopromo'}</p>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <i class="icon-bar-chart"></i> {l s='Performance' mod='autopromo'}
                    </div>
                    <div class="panel-body text-center">
                        <h3>0%</h3>
                        <p>{l s='Taux de conversion' mod='autopromo'}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <i class="icon-rocket"></i> {l s='Actions rapides' mod='autopromo'}
                    </div>
                    <div class="panel-body">
                        <a href="{$link->getAdminLink('AdminAutoPromoRules')|escape:'html':'UTF-8'}&addautopromo_rules" class="btn btn-success">
                            <i class="icon-plus"></i> {l s='Créer une nouvelle règle' mod='autopromo'}
                        </a>
                        <button type="button" class="btn btn-default" id="test-rules">
                            <i class="icon-play"></i> {l s='Tester les règles maintenant' mod='autopromo'}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        $('#test-rules').click(function() {
            // À implémenter plus tard - test manuel des règles
            alert('Fonctionnalité à venir!');
        });
    });
</script>