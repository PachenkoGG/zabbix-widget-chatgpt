# OpenAI Assistant Widget for Zabbix 7.0

🤖 Advanced AI-powered chat widget with **Zabbix monitoring integration**! Ask questions about your infrastructure and get intelligent insights.

## Features

✅ **Multiple OpenAI Model Selection**
- GPT-4o
- GPT-4o Mini  
- GPT-3.5 Turbo
- O1
- O3 Mini
- GPT-4.1
- GPT-4.1 Mini
- GPT-5
- GPT-5 Mini

✅ **🆕 Zabbix Integration**
- Real-time problem monitoring
- Host status information
- Severity level breakdown
- AI can analyze your infrastructure
- Token-based secure API access

✅ **Advanced Features**
- Conversation history with localStorage
- Stream response support
- Custom system prompts
- Adjustable temperature and parameters
- Code syntax highlighting with copy buttons
- Markdown rendering support
- Custom API endpoint configuration
- Clear conversation history

✅ **Modern UI**
- Beautiful gradient design
- Smooth animations
- Responsive layout
- Dark mode code blocks
- Copy code snippets

## Requirements

- Zabbix 7.0+
- PHP 8.0+
- OpenAI API Key (get from https://platform.openai.com/api-keys)

## Installation

1. Copy the `zabbix-widget-chatgpt` folder to your Zabbix modules directory:
   ```bash
   cp -r zabbix-widget-chatgpt /usr/share/zabbix/modules/
   ```

2. Set proper permissions:
   ```bash
   chown -R www-data:www-data /usr/share/zabbix/modules/zabbix-widget-chatgpt
   chmod -R 755 /usr/share/zabbix/modules/zabbix-widget-chatgpt
   ```

3. Restart Zabbix frontend service:
   ```bash
   systemctl restart zabbix-server
   systemctl restart apache2  # or nginx/php-fpm
   ```

4. Go to Zabbix dashboard and add the "OpenAI Assistant" widget

## Configuration

### Required Settings

- **OpenAI API Token**: Your OpenAI API key (required)

### Advanced Configuration

- **Include Zabbix Data**: Enable AI access to Zabbix monitoring data
- **Zabbix API URL**: Zabbix API endpoint (e.g., `http://localhost/zabbix/api_jsonrpc.php`)
- **Zabbix API Token**: API token from Administration → API tokens
- **Service Provider**: Choose between OpenAI or custom provider
- **API Endpoint**: API endpoint URL (default: https://api.openai.com/v1/chat/completions)
- **AI Model**: Select from available OpenAI models
- **Stream Response**: Enable/disable streaming responses
- **Temperature**: Control randomness (0-2, default: 0.7)
- **Top P**: Nucleus sampling parameter (0-1, default: 1)
- **Max Tokens**: Maximum tokens in response (default: 2048)
- **System Prompt**: Define AI assistant behavior and personality

## Zabbix Integration Setup

### 1. Create Zabbix API Token

1. Go to **Administration → API tokens**
2. Click **Create API token**
3. Enter a name (e.g., "OpenAI Widget API")
4. Select a user with appropriate permissions
5. Leave "Expires at" empty for no expiration
6. Click **Add** and copy the generated token

### 2. Configure Widget

1. Edit the widget
2. Expand **Advanced Configuration**
3. Set **Include Zabbix Data** to **Yes**
4. Enter **Zabbix API URL**: `http://localhost/zabbix/api_jsonrpc.php`
5. Paste your **Zabbix API Token**
6. Click **Apply** and **Update**

### 3. Test Integration

Ask the AI:
```
"How many problems are in the system?"
"Show me high severity issues"
"Which hosts have problems?"
```

The AI will now have access to:
- Total hosts and problems
- Problem severity breakdown
- Recent problems with details
- Host information

## Usage

1. Add widget to your Zabbix dashboard
2. Configure with your OpenAI API token
3. (Optional) Configure Zabbix API integration
4. Select preferred AI model
5. Start chatting!

### Example Questions

**Without Zabbix Integration:**
- "Explain how to configure Zabbix triggers"
- "Write a Python script to query Zabbix API"
- "What are best practices for monitoring?"

**With Zabbix Integration:**
- "What's the current status of my infrastructure?"
- "Show me the most critical problems"
- "Which hosts are experiencing issues?"
- "Analyze the severity distribution of current problems"

### Tips

- Use **Clear History** button to start fresh conversations
- Conversation history is saved per widget in browser localStorage
- Click **Copy** button on code blocks to easily copy snippets
- Press **Enter** to send messages
- Use **Stop** button to interrupt long responses
- Enable Zabbix integration for infrastructure-aware AI assistance

## Model Comparison

| Model | Speed | Cost | Best For |
|-------|-------|------|----------|
| GPT-4o | Fast | Medium | General purpose, balanced |
| GPT-4o Mini | Very Fast | Low | Quick queries, simple tasks |
| GPT-4 Turbo | Medium | High | Complex analysis, detailed responses |
| GPT-4 | Slower | Highest | Most accurate, complex reasoning |
| GPT-3.5 Turbo | Very Fast | Lowest | Simple queries, high volume |
| O1 | Medium | High | Advanced reasoning tasks |

## Customization

### Custom System Prompts

You can customize the AI assistant behavior by setting custom system prompts:

```
You are a Zabbix monitoring expert. Help users with monitoring configuration, 
trigger expressions, and problem analysis. Always provide clear examples.
```

### Custom API Endpoints

For using custom OpenAI-compatible APIs (like Azure OpenAI, LocalAI, etc.):

1. Select "Custom Provider" 
2. Enter your custom endpoint URL
3. Ensure your API key format matches the provider

## Troubleshooting

### Widget doesn't appear
- Check file permissions
- Verify Zabbix version compatibility (7.0+)
- Check browser console for errors

### API Errors
- Verify API key is valid
- Check API endpoint URL
- Ensure you have OpenAI credits
- Verify network connectivity

### Conversation not saving
- Check browser localStorage is enabled
- Clear browser cache and try again

## License

This widget is provided as-is for use with Zabbix monitoring system.

## Support

For issues and feature requests, please contact your administrator.

## Changelog

### Version 7.0-1
- Initial release
- Multiple OpenAI model support
- Conversation history
- Streaming responses
- Code highlighting
- Markdown support
- Custom system prompts

