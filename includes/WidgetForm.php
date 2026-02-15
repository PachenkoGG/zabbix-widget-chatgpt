<?php
/*
** OpenAI Assistant Widget Form
**/

namespace Modules\OpenAIAssistant\Includes;

use Modules\OpenAIAssistant\Services\WidgetTranslator;
use Zabbix\Widgets\CWidgetField;
use Zabbix\Widgets\CWidgetForm;
use Zabbix\Widgets\Fields\CWidgetFieldSelect;
use Zabbix\Widgets\Fields\CWidgetFieldTextBox;

/**
 * OpenAI Assistant widget form.
 */

class WidgetForm extends CWidgetForm
{
    public function addFields(): self {
        return $this
            ->addField(
                (new CWidgetFieldTextBox('token', WidgetTranslator::translate('form.token')))
                    ->setFlags(CWidgetField::FLAG_NOT_EMPTY | CWidgetField::FLAG_LABEL_ASTERISK)
            )
            ->addField(
                (new CWidgetFieldSelect('service', WidgetTranslator::translate('form.service.label'), [
                        0 => WidgetTranslator::translate('form.service.option.openai'),
                        1 => WidgetTranslator::translate('form.service.option.custom'),
                    ]))
                    ->setDefault(0)
                    ->setFlags(CWidgetField::FLAG_LABEL_ASTERISK)
            )
            ->addField(
                (new CWidgetFieldTextBox('endpoint', WidgetTranslator::translate('form.endpoint')))
                    ->setDefault('https://api.openai.com/v1/chat/completions')
                    ->setFlags(CWidgetField::FLAG_NOT_EMPTY | CWidgetField::FLAG_LABEL_ASTERISK)
            )
            ->addField(
                (new CWidgetFieldSelect('model', WidgetTranslator::translate('form.model'), [
                        0 => 'GPT-4o',
                        1 => 'GPT-4o Mini',
                        2 => 'GPT-3.5 Turbo',
                        3 => 'O1',
                        4 => 'O3 Mini',
                        5 => 'GPT-4.1',
                        6 => 'GPT-4.1 Mini',
                        7 => 'GPT-5',
                        8 => 'GPT-5 Mini',
                    ]))
                    ->setDefault(1)
                    ->setFlags(CWidgetField::FLAG_LABEL_ASTERISK)
            )
            ->addField(
                (new CWidgetFieldTextBox('temperature', WidgetTranslator::translate('form.temperature')))
                    ->setDefault('0.7')
            )
            ->addField(
                (new CWidgetFieldTextBox('top_p', WidgetTranslator::translate('form.top_p')))
                    ->setDefault('1')
            )
            ->addField(
                (new CWidgetFieldTextBox('max_tokens', WidgetTranslator::translate('form.max_tokens')))
                    ->setDefault('2048')
            )
            ->addField(
                (new CWidgetFieldTextBox('system_prompt', WidgetTranslator::translate('form.system_prompt')))
                    ->setDefault('You are a helpful AI assistant for Zabbix monitoring system. Provide clear, concise, and accurate information. 

IMPORTANT FORMATTING RULES:
- Use simple text, NO LaTeX or mathematical formulas
- For calculations, use plain text: "Memory Usage = (Used / Total) * 100"
- Use bullet points and numbered lists for clarity
- Keep explanations simple and practical
- When showing Zabbix data, format it clearly with proper units
- Respond in Turkish when user writes in Turkish

ACCESSING HOST INFORMATION:
When user asks about a specific host (e.g., "zabbix cpu kaç core", "zabbix ram kaç gb"), you have access to ALL metrics for that host in the Zabbix context above. Look for metrics like:
- CPU cores: Search for "Number of CPUs", "Processor count", "CPU count" in the metric names
- Total memory: Search for "Total memory", "Memory total", "Available memory" 
- Disk capacity: Search for "Total space", "Disk size", "Filesystem total"
- Operating system: Search for "Operating system", "System information", "OS name"
- Uptime: Search for "System uptime", "Uptime"

IMPORTANT: Read ALL available metrics for the requested host before saying "information not available". The data is likely in the Zabbix context provided above.')
            )
            ->addField(
                (new CWidgetFieldSelect('stream', WidgetTranslator::translate('form.stream'), [
                        1 => WidgetTranslator::translate('form.stream.option.yes'),
                        0 => WidgetTranslator::translate('form.stream.option.no'),
                    ]))
                    ->setDefault(1)
            )
            ->addField(
                (new CWidgetFieldSelect('enable_zabbix_data', WidgetTranslator::translate('form.enable_zabbix_data'), [
                        1 => WidgetTranslator::translate('form.enable_zabbix_data.option.yes'),
                        0 => WidgetTranslator::translate('form.enable_zabbix_data.option.no'),
                    ]))
                    ->setDefault(0)
            )
            ->addField(
                (new CWidgetFieldTextBox('zabbix_api_url', WidgetTranslator::translate('form.zabbix_api_url')))
                    ->setDefault('http://localhost/zabbix/api_jsonrpc.php')
            )
            ->addField(
                (new CWidgetFieldTextBox('zabbix_api_token', WidgetTranslator::translate('form.zabbix_api_token')))
            )
        ;
    }
}

