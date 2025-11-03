<?php
// test_complete.php - Test complet du module
include_once('../../config/config.inc.php');
include_once('../../init.php');

echo "<h1>🧪 TEST COMPLET AUTOPROMO</h1>";

// 1. Test du module
$module = Module::getInstanceByName('autopromo');
if (!$module) {
    die("❌ Module non trouvé");
}
echo "✅ Module chargé<br>";

// 2. Test des règles
$rules = Db::getInstance()->executeS("SELECT * FROM "._DB_PREFIX_."autopromo_rules");
echo "✅ Règles trouvées: " . count($rules) . "<br>";

// 3. Test d'exécution
try {
    $results = $module->runAllRules();
    echo "✅ Exécution réussie: " . $results['rules_executed'] . "/" . $results['total_rules'] . " règles<br>";
    echo "✅ Actions: " . $results['total_actions'] . " en " . $results['execution_time'] . "s<br>";
} catch (Exception $e) {
    echo "❌ Erreur exécution: " . $e->getMessage() . "<br>";
}

// 4. Test CRON
$cron_url = $module->getCronUrl();
echo "✅ URL CRON: " . $cron_url . "<br>";

// 5. Test statut
$status = $module->checkCronStatus();
echo "✅ Statut CRON: " . $status['status'] . " - " . $status['message'] . "<br>";

echo "<h2>✅ TEST TERMINÉ</h2>";