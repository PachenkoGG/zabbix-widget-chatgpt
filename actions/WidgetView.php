<?php
/*
** OpenAI Assistant Widget View Action
**/

namespace Modules\OpenAIAssistant\Actions;

use CControllerDashboardWidgetView;
use CControllerResponseData;
use Modules\OpenAIAssistant\Services\ZabbixAPIProvider;

class WidgetView extends CControllerDashboardWidgetView {

    protected function doAction(): void {
        
        // Get Zabbix data if enabled
        $zabbixData = '';
        
        // DEBUG: Log field values
        error_log('=== OpenAI Widget Debug ===');
        error_log('enable_zabbix_data: ' . var_export($this->fields_values['enable_zabbix_data'] ?? 'NOT SET', true));
        error_log('zabbix_api_url: ' . var_export($this->fields_values['zabbix_api_url'] ?? 'NOT SET', true));
        error_log('zabbix_api_token: ' . (isset($this->fields_values['zabbix_api_token']) ? 'SET (length=' . strlen($this->fields_values['zabbix_api_token']) . ')' : 'NOT SET'));
        
        if (!empty($this->fields_values['enable_zabbix_data'])) {
            try {
                $apiUrl = $this->fields_values['zabbix_api_url'] ?? '';
                $apiToken = $this->fields_values['zabbix_api_token'] ?? '';
                
                error_log('Attempting to fetch Zabbix data...');
                error_log('API URL: ' . $apiUrl);
                
                if (!empty($apiUrl) && !empty($apiToken)) {
                    $provider = new ZabbixAPIProvider($apiUrl, $apiToken);
                    $zabbixData = $provider->formatForAI();
                    error_log('Zabbix data fetched successfully! Length: ' . strlen($zabbixData));
                } else {
                    error_log('ERROR: Zabbix API URL or Token not provided');
                }
            } catch (\Exception $e) {
                error_log('ERROR: Could not fetch Zabbix data - ' . $e->getMessage());
                error_log('Stack trace: ' . $e->getTraceAsString());
                $zabbixData = '';
            }
        } else {
            error_log('Zabbix data disabled in widget config');
        }

        $this->setResponse(new CControllerResponseData([
            'name' => $this->getInput('name', $this->widget->getName()),
            'fields_values' => $this->fields_values,
            'zabbix_data' => $zabbixData,
            'user' => [
                'debug_mode' => $this->getDebugMode()
            ]
        ]));
    }
}

