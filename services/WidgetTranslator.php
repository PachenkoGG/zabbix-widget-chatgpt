<?php
/*
** OpenAI Assistant Widget Translator Service
**/

namespace Modules\OpenAIAssistant\Services;

class WidgetTranslator
{
    public static function translate(string $key): string {
        $translations = [
            'widget.name' => _('OpenAI Assistant'),
            'form.token' => _('OpenAI API Token'),
            'form.service.label' => _('Service Provider'),
            'form.service.option.openai' => _('OpenAI'),
            'form.service.option.custom' => _('Custom Provider'),
            'form.endpoint' => _('API Endpoint'),
            'form.model' => _('AI Model'),
            'form.temperature' => _('Temperature'),
            'form.top_p' => _('Top P'),
            'form.max_tokens' => _('Max Tokens'),
            'form.system_prompt' => _('System Prompt'),
            'form.stream' => _('Stream Response'),
            'form.stream.option.yes' => _('Yes'),
            'form.stream.option.no' => _('No'),
            'form.enable_zabbix_data' => _('Include Zabbix Data'),
            'form.enable_zabbix_data.option.yes' => _('Yes'),
            'form.enable_zabbix_data.option.no' => _('No'),
            'form.zabbix_api_url' => _('Zabbix API URL'),
            'form.zabbix_api_token' => _('Zabbix API Token'),
            'form.advanced-configuration' => _('Advanced Configuration'),
            'view.message.placeholder' => _('Type your message here...'),
            'view.clear.button' => _('Clear History'),
            'view.copy.button' => _('Copy'),
            'view.error.api' => _('API Error'),
            'view.error.network' => _('Network Error'),
        ];

        return $translations[$key] ?? $key;
    }
}

