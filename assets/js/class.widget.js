/*
** OpenAI Assistant Widget JavaScript
** Advanced AI chat widget with multiple model support
**/

class CWidgetOpenAIAssistant extends CWidget {
    // Model mapping: integer to OpenAI model name
    modelMap = {
        0: 'gpt-4o',
        1: 'gpt-4o-mini',
        2: 'gpt-3.5-turbo',
        3: 'o1',
        4: 'o3-mini',
        5: 'gpt-4.1',
        6: 'gpt-4.1-mini',
        7: 'gpt-5',
        8: 'gpt-5-mini'
    };

    apiToken = this._fields.token;
    apiEndpoint = this._fields.endpoint;
    selectedModel = this.modelMap[this._fields.model] || 'gpt-4o-mini';
    temperature = parseFloat(this._fields.temperature) || 0.7;
    topP = parseFloat(this._fields.top_p) || 1;
    maxTokens = parseInt(this._fields.max_tokens) || 2048;
    systemPrompt = this._fields.system_prompt || 'You are a helpful assistant.';
    stream = this._fields.stream == 1;
    enableZabbixData = this._fields.enable_zabbix_data == 1;
    zabbixData = '';
    
    conversationHistory = [];
    abort = false;

    setContents(response) {
        super.setContents(response);

        this.sendButton = this._body.querySelector('[name=send-button]');
        this.stopButton = this._body.querySelector('[name=stop-button]');
        this.clearButton = this._body.querySelector('[name=clear-button]');
        this.userInput = this._body.querySelector('.chat-form-message');
        this.chatLog = this._body.querySelector('.chat-log');

        // Store Zabbix data if available (with error handling)
        try {
            if (response && response.body && response.body.zabbix_data) {
                this.zabbixData = response.body.zabbix_data;
            }
        } catch (e) {
            console.warn('Could not load Zabbix data:', e);
            this.zabbixData = '';
        }

        this.userInput.addEventListener('keydown', e => {
            if (e.code == 'Enter' || e.code == 'NumpadEnter') {
                this.sendMessage();
            }
        });
        
        this.sendButton.addEventListener('click', this.sendMessage.bind(this));
        this.stopButton.addEventListener('click', this.stopStream.bind(this));
        this.clearButton.addEventListener('click', this.clearHistory.bind(this));
        
        // Load conversation history from localStorage
        this.loadHistory();
        
        // Show Zabbix data indicator if enabled
        try {
            if (this.enableZabbixData && this.zabbixData) {
                this.showZabbixIndicator();
            }
        } catch (e) {
            console.warn('Could not show Zabbix indicator:', e);
        }
    }

    async sendMessage() {
        const question = this.userInput.value.trim();
        this.userInput.value = '';

        if (!question) {
            return;
        }

        this.hideSendButton();
        this.showStopButton();

        this.stopController = new AbortController();

        const questionElement = this.createMessage('user');
        questionElement.innerHTML = marked.parse(question);

        const answerElement = this.createMessage('bot');

        // Add to conversation history
        this.conversationHistory.push({
            role: 'user',
            content: question
        });

        // Build messages array with system prompt and history
        const messages = [
            {
                role: 'system',
                content: this.systemPrompt
            }
        ];

        // Add Zabbix data as system context if enabled
        if (this.enableZabbixData && this.zabbixData) {
            messages.push({
                role: 'system',
                content: this.zabbixData
            });
        }

        messages.push(...this.conversationHistory);

        try {
            // Build request body
            const requestBody = {
                model: this.selectedModel,
                messages: messages,
                stream: this.stream,
            };

            // Some models (O1, O3) don't support these parameters
            const specialModels = ['o1', 'o3-mini', 'o3', 'o4-mini'];
            const isSpecialModel = specialModels.includes(this.selectedModel);

            if (!isSpecialModel) {
                requestBody.temperature = this.temperature;
                requestBody.top_p = this.topP;
                requestBody.max_tokens = this.maxTokens;
            }

            const request = await fetch(this.apiEndpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${this.apiToken}`
                },
                signal: this.stopController.signal,
                body: JSON.stringify(requestBody)
            });

            if (!request.ok) {
                // Get detailed error message
                const errorData = await request.json().catch(() => ({}));
                const errorMsg = errorData.error?.message || `${request.status} ${request.statusText}`;
                throw new Error(`API Error: ${errorMsg}`);
            }

            let assistantResponse = '';

            if (this.stream) {
                assistantResponse = await this.streamResponse(request, answerElement);
            } else {
                assistantResponse = await this.response(request, answerElement);
            }

            // Add assistant response to history
            this.conversationHistory.push({
                role: 'assistant',
                content: assistantResponse
            });

            // Save history to localStorage
            this.saveHistory();

        } catch (error) {
            if (error.name === 'AbortError') {
                answerElement.innerHTML += '<br><em>(Response stopped by user)</em>';
            } else {
                let errorMsg = error.message;
                
                // Check for specific error codes
                if (errorMsg.includes('429')) {
                    errorMsg = `
                        <strong>API Rate Limit / Quota Exceeded (Error 429)</strong><br><br>
                        This usually means:<br>
                        • Your OpenAI account is out of credits<br>
                        • You've hit the rate limit (too many requests)<br>
                        • Your API key is on free tier with limited access<br><br>
                        <strong>Solutions:</strong><br>
                        1. Check your OpenAI billing: <a href="https://platform.openai.com/account/billing" target="_blank">platform.openai.com/account/billing</a><br>
                        2. Add credits to your account (minimum $5)<br>
                        3. Wait a few minutes and try again<br>
                        4. Try using GPT-4o Mini (cheaper model)
                    `;
                } else if (errorMsg.includes('400')) {
                    errorMsg = `
                        <strong>Bad Request (Error 400)</strong><br><br>
                        This usually means:<br>
                        • The selected model doesn't support some parameters<br>
                        • System prompt is too long or invalid<br>
                        • Model name is incorrect<br><br>
                        <strong>Solutions:</strong><br>
                        1. Try switching to <strong>GPT-4o Mini</strong> model<br>
                        2. Check if your system prompt is too long<br>
                        3. O1/O3 models have special requirements<br>
                        4. See browser console (F12) for detailed error<br><br>
                        <strong>Current model:</strong> ${this.selectedModel}
                    `;
                }
                
                answerElement.innerHTML = `<div class="error-message">❌ ${errorMsg}</div>`;
                console.error('OpenAI API Error:', error);
                console.error('Model:', this.selectedModel);
                console.error('Endpoint:', this.apiEndpoint);
            }
        }

        this.showSendButton();
        this.hideStopButton();
    }

    stopStream() {
        if (this.stream) {
            this.stopController.abort();
        }

        this.showSendButton();
        this.hideStopButton();
    }

    async streamResponse(request, answerElement) {
        const reader = request.body?.pipeThrough(new TextDecoderStream()).getReader();
    
        let lastJsonLine = '';
        let rawAnswer = '';

        while (true) {
            const res = await reader?.read();
    
            if (res?.done) {
               return rawAnswer;
            }
    
            if (!res?.value) {
                continue;
            }
    
            const jsonChunks = res.value.split('\n\n').filter(line => line.trim().length > 0);
  
            for (const chunk of jsonChunks) {
                lastJsonLine += chunk;
                if (lastJsonLine.startsWith('data:')) {
                    lastJsonLine = lastJsonLine.slice('data:'.length).trim();
                }

                if (lastJsonLine === '[DONE]') {
                    return rawAnswer;
                }
    
                try {
                    const answer = JSON.parse(lastJsonLine);
                    lastJsonLine = '';
    
                    if (answer.choices && answer.choices[0].delta && answer.choices[0].delta.content) {
                        const reply = answer.choices[0].delta.content;

                        if (answerElement.querySelector('.dot-flashing')) {
                            answerElement.querySelector('.dot-flashing').remove();
                        }

                        rawAnswer += reply;
                        answerElement.innerHTML = marked.parse(rawAnswer);
                        
                        // Add copy buttons to code blocks
                        this.addCopyButtonsToCodeBlocks(answerElement);
                        
                        this.chatLog.scrollTop = this.chatLog.scrollHeight;
                    }
                } catch (e) {
                    // Incomplete JSON, continue accumulating
                }
            }
        }
    }

    async response(request, answerElement) {
        const response = await request.json();

        if (response.choices && response.choices.length > 0) {
            const content = response.choices[0].message.content;
            answerElement.innerHTML = marked.parse(content);
            this.addCopyButtonsToCodeBlocks(answerElement);
            this.chatLog.scrollTop = this.chatLog.scrollHeight;
            return content;
        }
        return '';
    }

    createMessage(sender) {
        if (!(sender === 'user' || sender === 'bot')) {
            return null;
        }

        const message = document.createElement('div');
        message.classList.add('chat-log-message', `chat-log-message-${sender}`);

        message.insertAdjacentHTML(
            'beforeend',
            `<div class="chat-log-message-author chat-log-message-author-${sender}">
                <span class="author-icon">${sender === 'user' ? '👤' : '🤖'}</span>
             </div>
             <div class="chat-log-message-text chat-log-message-text-${sender}">
                <div class="dot-flashing"></div>
             </div>`
        );

        this.chatLog.appendChild(message);
        this.chatLog.scrollTop = this.chatLog.scrollHeight;

        return message.querySelector('.chat-log-message-text');
    }

    addCopyButtonsToCodeBlocks(element) {
        const codeBlocks = element.querySelectorAll('pre code');
        codeBlocks.forEach(codeBlock => {
            if (!codeBlock.parentElement.querySelector('.copy-code-button')) {
                const copyButton = document.createElement('button');
                copyButton.className = 'copy-code-button';
                copyButton.textContent = '📋 Copy';
                copyButton.onclick = () => {
                    navigator.clipboard.writeText(codeBlock.textContent);
                    copyButton.textContent = '✓ Copied!';
                    setTimeout(() => {
                        copyButton.textContent = '📋 Copy';
                    }, 2000);
                };
                codeBlock.parentElement.style.position = 'relative';
                codeBlock.parentElement.insertBefore(copyButton, codeBlock);
            }
        });
    }

    clearHistory() {
        if (confirm('Are you sure you want to clear the conversation history?')) {
            this.conversationHistory = [];
            this.chatLog.innerHTML = '';
            this.saveHistory();
        }
    }

    saveHistory() {
        const widgetId = this._target;
        localStorage.setItem(`openai_assistant_history_${widgetId}`, JSON.stringify(this.conversationHistory));
    }

    loadHistory() {
        const widgetId = this._target;
        const saved = localStorage.getItem(`openai_assistant_history_${widgetId}`);
        
        if (saved) {
            try {
                this.conversationHistory = JSON.parse(saved);
                // Restore messages in UI
                this.conversationHistory.forEach(msg => {
                    if (msg.role === 'user' || msg.role === 'assistant') {
                        const messageElement = this.createMessage(msg.role === 'user' ? 'user' : 'bot');
                        if (messageElement.querySelector('.dot-flashing')) {
                            messageElement.querySelector('.dot-flashing').remove();
                        }
                        messageElement.innerHTML = marked.parse(msg.content);
                        this.addCopyButtonsToCodeBlocks(messageElement);
                    }
                });
            } catch (e) {
                console.error('Error loading history:', e);
                this.conversationHistory = [];
            }
        }
    }

    hideSendButton() {
        this.sendButton.classList.add('chat-form-button--hidden');
    }

    showSendButton() {
        this.sendButton.classList.remove('chat-form-button--hidden');
    }

    hideStopButton() {
        this.stopButton.classList.add('chat-form-button-stop--hidden');
    }

    showStopButton() {
        this.stopButton.classList.remove('chat-form-button-stop--hidden');
    }

    hasPadding() {
        return false;
    }

    showZabbixIndicator() {
        const indicator = document.createElement('div');
        indicator.className = 'zabbix-data-indicator';
        indicator.innerHTML = '📊 Zabbix Data Active';
        indicator.title = 'AI can see current Zabbix problems and host status';
        
        const header = this._body.querySelector('.chat-header');
        if (header) {
            header.appendChild(indicator);
        }
    }
}

