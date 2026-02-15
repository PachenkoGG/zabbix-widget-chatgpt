<?php
/*
** OpenAI Assistant widget view.
**/

use Modules\OpenAIAssistant\Services\WidgetTranslator;

/**
 * @var CView $this
 * @var array $data
 */

(new CWidgetView($data))
    ->addItem(
        (new CDiv([
            (new CDiv([
                (new CTag('h3', true, 'OpenAI Assistant'))
                    ->addStyle('margin: 0; color: #fff; font-size: 1.2rem;')
                ,
                (new CButton('clear-button', WidgetTranslator::translate('view.clear.button')))
                    ->addClass('chat-clear-button')
                    ->setAttribute('type', 'button')
                ,    
            ]))
                ->addClass('chat-header')
            ,
            (new CDiv())
                ->setId('chat-log')
                ->addClass('chat-log')
            ,
            (new CDiv([
                (new CInput('text'))
                    ->addClass('chat-form-message')
                    ->setAttribute('placeholder', WidgetTranslator::translate('view.message.placeholder'))
                ,
                (new CButton('stop-button', '◼'))
                    ->addClass('chat-form-button-stop')
                    ->addClass('chat-form-button-stop--hidden')
                    ->setAttribute('type', 'button')
                ,
                (new CButton('send-button', '➜'))
                    ->addClass('chat-form-button')
                    ->setAttribute('type', 'submit')
                ,
                ]))
                ->addClass('chat-form')
            ,
        ]))
        ->setId('chat-container')
        ->addClass('chat-container')
    )
->show();

