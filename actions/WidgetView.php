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
        
        // Get Zabbix data if enabled
        $zabbixData = '';
        if (!empty($this->fields_values['enable_zabbix_data'])) {
            $zabbixData = ZabbixDataProvider::formatForAI();
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

