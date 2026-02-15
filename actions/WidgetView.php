<?php
/*
** OpenAI Assistant Widget View Action
**/

namespace Modules\OpenAIAssistant\Actions;

use CControllerDashboardWidgetView;
use CControllerResponseData;
use Modules\OpenAIAssistant\Services\ZabbixDataProvider;

class WidgetView extends CControllerDashboardWidgetView {

    protected function doAction(): void {
        
        // Get Zabbix data if enabled (with error handling)
        $zabbixData = '';
        if (!empty($this->fields_values['enable_zabbix_data'])) {
            try {
                $zabbixData = ZabbixDataProvider::formatForAI();
            } catch (\Exception $e) {
                // Silently fail - widget should still work without Zabbix data
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

