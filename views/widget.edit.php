<?php declare(strict_types = 0);

use Modules\OpenAIAssistant\Services\WidgetTranslator;
use Modules\OpenAIAssistant\Widget;

/**
 * OpenAI Assistant widget form view.
 *
 * @var CView $this
 * @var array $data
 */

(new CWidgetFormView($data))
    ->addField(
        new CWidgetFieldTextBoxView($data['fields']['token'])
    )
    ->addFieldset((new CWidgetFormFieldsetCollapsibleView(WidgetTranslator::translate('form.advanced-configuration')))
        ->addField(
            new CWidgetFieldSelectView($data['fields']['enable_zabbix_data'])
        )
        ->addField(
            new CWidgetFieldSelectView($data['fields']['service'])
        )
        ->addField(
            new CWidgetFieldTextBoxView($data['fields']['endpoint'])
        )
        ->addField(
            new CWidgetFieldSelectView($data['fields']['model'])
        )
        ->addField(
            new CWidgetFieldSelectView($data['fields']['stream'])
        )
        ->addField(
            (new CWidgetFieldTextBoxView($data['fields']['temperature']))
                ->setFieldHint(
                    makeHelpIcon(_('Sampling temperature between 0 and 2. Higher values (0.8) make output more random, lower values (0.2) make it more focused and deterministic.'), 'icon-help')
                )
        )
        ->addField(
            (new CWidgetFieldTextBoxView($data['fields']['top_p']))
                ->setFieldHint(
                    makeHelpIcon(_('Nucleus sampling: model considers tokens with top_p probability mass. 0.1 means only tokens comprising top 10% probability mass.'), 'icon-help')
                )
        )
        ->addField(
            (new CWidgetFieldTextBoxView($data['fields']['max_tokens']))
                ->setFieldHint(
                    makeHelpIcon(_('Maximum number of tokens to generate in the completion.'), 'icon-help')
                )
        )
        ->addField(
            (new CWidgetFieldTextBoxView($data['fields']['system_prompt']))
                ->setFieldHint(
                    makeHelpIcon(_('System prompt defines the AI assistant behavior and personality.'), 'icon-help')
                )
        )
    )
    ->includeJsFile('widget.edit.js.php')
    ->addJavaScript('widget_openai_assistant_form.init();')
    ->show();

