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
                        2 => 'GPT-4 Turbo',
                        3 => 'GPT-4',
                        4 => 'GPT-3.5 Turbo',
                        5 => 'GPT-3.5 Turbo 16K',
                        6 => 'O1',
                        7 => 'O1 Mini',
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
                    ->setDefault('You are a helpful AI assistant for Zabbix monitoring system. Provide clear, concise, and accurate information.')
            )
            ->addField(
                (new CWidgetFieldSelect('stream', WidgetTranslator::translate('form.stream'), [
                        1 => WidgetTranslator::translate('form.stream.option.yes'),
                        0 => WidgetTranslator::translate('form.stream.option.no'),
                    ]))
                    ->setDefault(1)
            )
        ;
    }
}

