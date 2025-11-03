<div class="panel">
    <div class="panel-heading">
        <i class="icon-cogs"></i> {l s='AutoPromo - Tableau de Bord' mod='autopromo'}
    </div>
    <div class="panel-body">
        <!-- Statistiques -->
        <div class="row">
            <div class="col-md-3">
                <div class="panel panel-default">
                    <div class="panel-heading" style="background-color: #3498db; color: white;">
                        <i class="icon-gears"></i> {l s='Règles Actives' mod='autopromo'}
                    </div>
                    <div class="panel-body text-center">
                        <h2 style="color: #3498db; margin: 10px 0;" id="stats-active-rules">0</h2>
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
                        <h2 style="color: #2ecc71; margin: 10px 0;" id="stats-coupons-month">0</h2>
                        <p>{l s='Générés ce mois' mod='autopromo'}</p>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="panel panel-default">
                    <div class="panel-heading" style="background-color: #e74c3c; color: white;">
                        <i class="icon-bar-chart"></i> {l s='Actions' mod='autopromo'}
                    </div>
                    <div class="panel-body text-center">
                        <h2 style="color: #e74c3c; margin: 10px 0;" id="stats-total-actions">0</h2>
                        <p>{l s='Total exécutées' mod='autopromo'}</p>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="panel panel-default">
                    <div class="panel-heading" style="background-color: #f39c12; color: white;">
                        <i class="icon-clock-o"></i> {l s='Dernière Exécution' mod='autopromo'}
                    </div>
                    <div class="panel-body text-center">
                        <h4 style="color: #f39c12; margin: 10px 0;" id="stats-last-execution">-</h4>
                        <p>{l s='Des règles' mod='autopromo'}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Moteur de Règles -->
        <div class="panel">
            <div class="panel-heading">
                <i class="icon-cogs"></i> {l s='Moteur de Règles - Exécution' mod='autopromo'}
            </div>
            <div class="panel-body">
                <div class="alert alert-warning">
                    <h4>{l s='Exécution Automatique' mod='autopromo'}</h4>
                    <p>{l s='Le moteur de règles peut s\'exécuter automatiquement via CRON ou manuellement depuis cette page.' mod='autopromo'}</p>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <i class="icon-play"></i> {l s='Exécution Manuelle' mod='autopromo'}
                            </div>
                            <div class="panel-body text-center">
                                <button type="button" class="btn btn-success btn-lg" id="run-rules-now">
                                    <i class="icon-rocket"></i> {l s='Exécuter toutes les règles maintenant' mod='autopromo'}
                                </button>
                                <p class="text-muted" style="margin-top: 10px;">
                                    {l s='Exécute immédiatement toutes les règles actives' mod='autopromo'}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <i class="icon-clock-o"></i> {l s='Statut CRON' mod='autopromo'}
                            </div>
                            <div class="panel-body text-center">
                                <div id="cron-status">
                                    <p><i class="icon-spinner icon-spin"></i> Chargement du statut...</p>
                                </div>
                                <button type="button" class="btn btn-info" id="test-cron">
                                    <i class="icon-check"></i> {l s='Tester la connexion CRON' mod='autopromo'}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Configuration CRON -->
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <i class="icon-code"></i> {l s='Configuration CRON' mod='autopromo'}
                    </div>
                    <div class="panel-body">
                        <p><strong>{l s='URL CRON sécurisée:' mod='autopromo'}</strong></p>
                        <code id="cron-url">Chargement...</code>
                        <button type="button" class="btn btn-default btn-sm" onclick="copyCronUrl()" style="margin-left: 10px;">
                            <i class="icon-copy"></i> {l s='Copier' mod='autopromo'}
                        </button>
                        
                        <p style="margin-top: 15px;">
                            {l s='Configurez cette URL dans votre planificateur CRON pour exécution automatique.' mod='autopromo'}
                        </p>
                        
                        <div class="alert alert-info">
                            <strong>{l s='Exemples de configuration CRON:' mod='autopromo'}</strong><br>
                            <code>0 2 * * * curl "{l s='URL_CRON' mod='autopromo'}"</code> - Tous les jours à 2h<br>
                            <code>0 */6 * * * curl "{l s='URL_CRON' mod='autopromo'}"</code> - Toutes les 6 heures<br>
                            <code>0 9,18 * * * curl "{l s='URL_CRON' mod='autopromo'}"</code> - À 9h et 18h
                        </div>
                    </div>
                </div>

                <!-- Logs d'exécution -->
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <i class="icon-list"></i> {l s='Dernières Exécutions' mod='autopromo'}
                    </div>
                    <div class="panel-body">
                        <div id="execution-logs">
                            <p class="text-center">{l s='Chargement des logs...' mod='autopromo'}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Actions rapides -->
        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <i class="icon-rocket"></i> {l s='Actions Rapides' mod='autopromo'}
                    </div>
                    <div class="panel-body">
                        <a href="{$link->getAdminLink('AdminAutoPromoRules')|escape:'html':'UTF-8'}" class="btn btn-primary">
                            <i class="icon-list"></i> {l s='Gérer les règles' mod='autopromo'}
                        </a>
                        <a href="{$link->getAdminLink('AdminAutoPromoRules')|escape:'html':'UTF-8'}&addautopromo_rules" class="btn btn-success">
                            <i class="icon-plus"></i> {l s='Créer une nouvelle règle' mod='autopromo'}
                        </a>
                        <button type="button" class="btn btn-warning" id="refresh-stats">
                            <i class="icon-refresh"></i> {l s='Actualiser les statistiques' mod='autopromo'}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Charger les statistiques
function loadStats() {
    $.ajax({
        url: '{$link->getAdminLink('AdminAutoPromoRules')|escape:'javascript':'UTF-8'}',
        data: {
            ajax: true,
            action: 'getDashboardStats',
            token: '{$token|escape:'javascript':'UTF-8'}'
        },
        success: function(response) {
            var stats = JSON.parse(response);
            $('#stats-active-rules').text(stats.active_rules);
            $('#stats-coupons-month').text(stats.coupons_this_month);
            $('#stats-total-actions').text(stats.total_actions);
            $('#stats-last-execution').text(stats.last_execution || '-');
        }
    });
}

// Charger le statut CRON
function loadCronStatus() {
    $.ajax({
        url: '{$link->getAdminLink('AdminAutoPromoRules')|escape:'javascript':'UTF-8'}',
        data: {
            ajax: true,
            action: 'getCronStatus',
            token: '{$token|escape:'javascript':'UTF-8'}'
        },
        success: function(response) {
            var data = JSON.parse(response);
            $('#cron-url').text(data.cron_url);
            
            var status = data.status;
            var statusHtml = '';
            var statusClass = '';
            
            if (status.status === 'active') {
                statusClass = 'success';
                statusHtml = '<div class="alert alert-success"><i class="icon-check"></i> <strong>CRON Actif</strong><br>' + status.message + '</div>';
            } else if (status.status === 'warning') {
                statusClass = 'warning';
                statusHtml = '<div class="alert alert-warning"><i class="icon-warning"></i> <strong>CRON Avertissement</strong><br>' + status.message + '</div>';
            } else if (status.status === 'inactive') {
                statusClass = 'danger';
                statusHtml = '<div class="alert alert-danger"><i class="icon-remove"></i> <strong>CRON Inactif</strong><br>' + status.message + '</div>';
            } else {
                statusHtml = '<div class="alert alert-info"><i class="icon-info"></i> <strong>Statut inconnu</strong><br>' + status.message + '</div>';
            }
            
            $('#cron-status').html(statusHtml);
        }
    });
}

// Exécuter toutes les règles
$('#run-rules-now').click(function() {
    if (confirm('{l s='Êtes-vous sûr de vouloir exécuter toutes les règles actives maintenant ?' mod='autopromo'|escape:'javascript'}')) {
        var $button = $(this);
        $button.prop('disabled', true).html('<i class="icon-spinner icon-spin"></i> {l s='Exécution en cours...' mod='autopromo'}');
        
        $.ajax({
            url: '{$link->getAdminLink('AdminAutoPromoRules')|escape:'javascript':'UTF-8'}',
            data: {
                ajax: true,
                action: 'runAllRules',
                token: '{$token|escape:'javascript':'UTF-8'}'
            },
            success: function(response) {
                try {
                    var result = JSON.parse(response);
                    if (result.success) {
                        showSuccessMessage('✅ {l s='Exécution terminée avec succès!' mod='autopromo'|escape:'javascript'}\n\n' +
                              '{l s='Règles exécutées:' mod='autopromo'|escape:'javascript'} ' + result.results.rules_executed + '/' + result.results.total_rules + '\n' +
                              '{l s='Actions réalisées:' mod='autopromo'|escape:'javascript'} ' + result.results.total_actions + '\n' +
                              '{l s='Temps d\\'exécution:' mod='autopromo'|escape:'javascript'} ' + result.results.execution_time + 's');
                        loadExecutionLogs();
                        loadStats();
                    } else {
                        showErrorMessage('❌ {l s='Erreur:' mod='autopromo'|escape:'javascript'} ' + result.error);
                    }
                } catch (e) {
                    showErrorMessage('❌ {l s='Erreur de traitement de la réponse' mod='autopromo'|escape:'javascript'}');
                }
            },
            error: function() {
                showErrorMessage('❌ {l s='Erreur lors de la requête' mod='autopromo'|escape:'javascript'}');
            },
            complete: function() {
                $button.prop('disabled', false).html('<i class="icon-rocket"></i> {l s='Exécuter toutes les règles maintenant' mod='autopromo'}');
            }
        });
    }
});

// Tester la connexion CRON
$('#test-cron').click(function() {
    var $button = $(this);
    $button.prop('disabled', true).html('<i class="icon-spinner icon-spin"></i> Test en cours...');
    
    $.ajax({
        url: '{$link->getAdminLink('AdminAutoPromoRules')|escape:'javascript':'UTF-8'}',
        data: {
            ajax: true,
            action: 'testCron',
            token: '{$token|escape:'javascript':'UTF-8'}'
        },
        success: function(response) {
            var result = JSON.parse(response);
            if (result.success) {
                showSuccessMessage('✅ ' + result.message);
            } else {
                showErrorMessage('❌ ' + result.message);
            }
        },
        error: function() {
            showErrorMessage('❌ Erreur lors du test CRON');
        },
        complete: function() {
            $button.prop('disabled', false).html('<i class="icon-check"></i> {l s='Tester la connexion CRON' mod='autopromo'}');
        }
    });
});

// Copier l'URL CRON
function copyCronUrl() {
    var cronUrl = $('#cron-url').text();
    var textArea = document.createElement("textarea");
    textArea.value = cronUrl;
    document.body.appendChild(textArea);
    textArea.select();
    document.execCommand("Copy");
    textArea.remove();
    showSuccessMessage('✅ URL CRON copiée dans le presse-papier!');
}

// Charger les logs d'exécution
function loadExecutionLogs() {
    $.ajax({
        url: '{$link->getAdminLink('AdminAutoPromoRules')|escape:'javascript':'UTF-8'}',
        data: {
            ajax: true,
            action: 'getExecutionLogs',
            token: '{$token|escape:'javascript':'UTF-8'}'
        },
        success: function(response) {
            $('#execution-logs').html(response);
        }
    });
}

// Messages
function showSuccessMessage(message) {
    // Vous pouvez remplacer par des notifications Prestashop
    alert(message);
}

function showErrorMessage(message) {
    alert(message);
}

// Actualiser les statistiques
$('#refresh-stats').click(function() {
    loadStats();
    loadCronStatus();
    loadExecutionLogs();
    showSuccessMessage('✅ Statistiques actualisées!');
});

// Chargement initial
$(document).ready(function() {
    loadStats();
    loadCronStatus();
    loadExecutionLogs();
});
</script>