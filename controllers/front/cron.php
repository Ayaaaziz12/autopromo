<?php

class AutoPromoCronModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        // Vérifier le token de sécurité
        $token = Tools::getValue('token');
        $expected_token = md5(_COOKIE_KEY_ . 'autopromo');
        
        if ($token !== $expected_token) {
            $this->logAccess('Token invalide: ' . $token);
            header('HTTP/1.0 403 Forbidden');
            die('Token invalide');
        }

        // Désactiver l'affichage HTML
        $this->display_header = false;
        $this->display_footer = false;

        $this->logAccess('Démarrage exécution CRON');

        try {
            // Exécuter les règles
            $start_time = microtime(true);
            $results = $this->module->runAllRules();
            $execution_time = round(microtime(true) - $start_time, 2);

            // Préparer la réponse
            $response = array(
                'success' => true,
                'message' => 'Règles exécutées avec succès',
                'execution_time' => $execution_time,
                'results' => $results,
                'timestamp' => date('Y-m-d H:i:s')
            );

            $this->logAccess('Exécution CRON réussie - ' . 
                $results['rules_executed'] . '/' . $results['total_rules'] . 
                ' règles - ' . $execution_time . 's');

        } catch (Exception $e) {
            $response = array(
                'success' => false,
                'message' => 'Erreur lors de l\'exécution',
                'error' => $e->getMessage(),
                'timestamp' => date('Y-m-d H:i:s')
            );

            $this->logAccess('Erreur CRON: ' . $e->getMessage());
        }

        // Retourner les résultats en JSON
        header('Content-Type: application/json');
        die(json_encode($response));
    }

    /**
     * Log les accès CRON
     */
    private function logAccess($message)
    {
        $log_data = array(
            'id_rule' => 0,
            'id_customer' => null,
            'id_product' => null,
            'action_type' => 'cron_access',
            'details' => $message . ' - IP: ' . $_SERVER['REMOTE_ADDR'],
            'date_add' => date('Y-m-d H:i:s')
        );

        Db::getInstance()->insert('autopromo_logs', $log_data);
    }
}