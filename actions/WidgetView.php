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
        if (!empty($this->fields_values['enable_zabbix_data'])) {
            try {
                $apiUrl = $this->fields_values['zabbix_api_url'] ?? '';
                $apiToken = $this->fields_values['zabbix_api_token'] ?? '';
                
                if (!empty($apiUrl) && !empty($apiToken)) {
                    $provider = new ZabbixAPIProvider($apiUrl, $apiToken);
                    $zabbixData = $provider->formatForAI();
                } else {
                    error_log('OpenAI Widget: Zabbix API URL or Token not provided');
                }
            } catch (\Exception $e) {
                error_log('OpenAI Widget: Could not fetch Zabbix data - ' . $e->getMessage());
                $zabbixData = '';
            }
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

