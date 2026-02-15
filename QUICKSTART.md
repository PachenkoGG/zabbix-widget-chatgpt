# OpenAI Assistant Widget - Quick Start Guide

## 🚀 What is this?

This is a custom Zabbix 7.0 widget that brings OpenAI's powerful language models directly into your Zabbix dashboard. Think of it as having an AI assistant that can help you with monitoring, troubleshooting, and analysis.

## ✨ Key Features

### 🎯 Multiple AI Models
Choose from 8 different OpenAI models:
- **GPT-4o** - Best overall balance
- **GPT-4o Mini** - Fast and cost-effective
- **GPT-4 Turbo** - Advanced reasoning
- **GPT-4** - Highest accuracy
- **GPT-3.5 Turbo** - Lightning fast
- **O1 / O1 Mini** - Latest models

### 💬 Smart Conversations
- Maintains conversation history
- Remembers context across messages
- Stream responses in real-time
- Clear history when needed

### 🎨 Beautiful Interface
- Modern gradient design
- Smooth animations
- Code highlighting
- Copy code with one click
- Markdown support

### ⚙️ Fully Customizable
- Custom system prompts
- Adjustable temperature & parameters
- Custom API endpoints
- Stream on/off toggle

## 📋 Requirements

- **Zabbix**: 7.0 or higher
- **PHP**: 8.0 or higher  
- **OpenAI API Key**: Get from [platform.openai.com](https://platform.openai.com/api-keys)

## 🔧 Installation

### Option 1: Automatic (Linux)

```bash
cd zabbix-widget
sudo chmod +x install.sh
sudo ./install.sh
```

### Option 2: Manual

```bash
# Copy widget to Zabbix modules directory
sudo cp -r zabbix-widget /usr/share/zabbix/modules/openai-assistant

# Set permissions
sudo chown -R www-data:www-data /usr/share/zabbix/modules/openai-assistant
sudo chmod -R 755 /usr/share/zabbix/modules/openai-assistant

# Restart services
sudo systemctl restart zabbix-server
sudo systemctl restart apache2
```

### Option 3: Windows/Development

1. Copy the `zabbix-widget` folder to your Zabbix installation
2. Typically: `C:\Program Files\Zabbix\ui\modules\`
3. Restart Zabbix services

## 🎬 Getting Started

### Step 1: Get API Key
1. Go to https://platform.openai.com/api-keys
2. Create a new API key
3. Copy it (starts with `sk-...`)

### Step 2: Add Widget
1. Open Zabbix dashboard
2. Click "Edit dashboard"
3. Add widget → Find "OpenAI Assistant"
4. Paste your API key

### Step 3: Choose Model
1. Click widget settings (gear icon)
2. Expand "Advanced Configuration"
3. Select your preferred model
4. Save

### Step 4: Start Chatting!
Just type your question and press Enter!

## 💡 Example Questions

### For Monitoring
```
What are best practices for monitoring database performance in Zabbix?
```

### For Troubleshooting
```
My web server response time increased by 300%. CPU is normal, 
but memory is at 90%. What should I check?
```

### For Configuration
```
How do I create a trigger that alerts when disk space is below 10% 
and has been decreasing for 30 minutes?
```

### For Analysis
```
I have these metrics:
- CPU: 45% average
- Memory: 85% used
- Disk I/O: 200 IOPS
- Network: 100 Mbps

Is this normal for a web server handling 10k requests/hour?
```

## 📊 Project Structure

```
zabbix-widget/
├── manifest.json              # Widget configuration
├── Widget.php                 # Main widget class
├── README.md                  # Main documentation
├── CONFIGURATION.md           # Detailed config guide
├── CHANGELOG.md               # Version history
├── VERSION.md                 # Current version
├── LICENSE                    # MIT License
├── install.sh                 # Installation script
│
├── includes/
│   └── WidgetForm.php         # Form field definitions
│
├── actions/
│   └── WidgetView.php         # View action handler
│
├── views/
│   ├── widget.view.php        # Main widget view
│   ├── widget.edit.php        # Settings form view
│   └── widget.edit.js.php     # Form JavaScript
│
├── services/
│   └── WidgetTranslator.php   # Translation service
│
├── translation/
│   ├── messages.en_US.yaml    # English translations
│   └── messages.tr_TR.yaml    # Turkish translations
│
└── assets/
    ├── css/
    │   ├── widget.css         # Main widget styles
    │   └── form.css           # Form styles
    └── js/
        ├── class.widget.js    # Main widget logic
        └── marked.min.js      # Markdown parser
```

## ⚡ Performance Tips

### For Speed
- Use **GPT-4o Mini** or **GPT-3.5 Turbo**
- Enable streaming
- Reduce max tokens to 1024

### For Quality
- Use **GPT-4 Turbo** or **GPT-4**
- Increase max tokens to 4096
- Lower temperature to 0.3

### For Cost
- Stick with **GPT-4o Mini**
- Set max tokens to 512-1024
- Use appropriate model for task

## 🔒 Security

- Never share your API key
- Don't send sensitive credentials to the AI
- Clear conversation history when handling sensitive data
- Rotate API keys regularly
- Monitor API usage on OpenAI dashboard

## 🐛 Troubleshooting

### Widget doesn't appear
```bash
# Check permissions
ls -la /usr/share/zabbix/modules/openai-assistant/

# Check Zabbix logs
tail -f /var/log/zabbix/zabbix_server.log

# Clear browser cache
Ctrl + Shift + Delete
```

### API Errors
- ✅ Verify API key is correct
- ✅ Check you have OpenAI credits
- ✅ Verify endpoint URL
- ✅ Check internet connectivity

### Slow Responses
- Switch to faster model (GPT-4o Mini)
- Enable streaming
- Reduce max_tokens
- Check network latency

## 📞 Support

For issues or questions:
1. Check `CONFIGURATION.md` for detailed setup
2. Review `README.md` for full documentation
3. Check browser console for JavaScript errors
4. Verify Zabbix and PHP versions

## 🎉 Features Comparison

| Feature | This Widget | ChatGPT Free Widget |
|---------|-------------|-------------------|
| Multiple Models | ✅ 8 models | ❌ Fixed model |
| Model Selection | ✅ Full choice | ❌ Limited |
| Conversation History | ✅ Saved | ❌ No history |
| Custom System Prompts | ✅ Yes | ❌ No |
| Stream Control | ✅ On/Off toggle | ⚠️ Stream only |
| Code Copy Buttons | ✅ Yes | ❌ No |
| Clear History | ✅ Yes | ❌ No |
| Temperature Control | ✅ Adjustable | ❌ Fixed |
| Custom Endpoints | ✅ Yes | ❌ OpenAI only |
| Turkish Language | ✅ Yes | ⚠️ Limited |

## 📈 Roadmap

Future enhancements we're considering:
- [ ] Export conversation to file
- [ ] Multiple conversation threads
- [ ] Integration with Zabbix triggers
- [ ] Custom model fine-tuning
- [ ] Voice input support
- [ ] Multi-language UI
- [ ] Custom CSS themes
- [ ] Widget presets

## 🙏 Credits

Built with inspiration from the initMAX ChatGPT widget, enhanced with:
- Multiple model support
- Conversation persistence
- Enhanced UI/UX
- Better configuration options
- Comprehensive documentation

## 📄 License

MIT License - Feel free to use, modify, and distribute!

---

**Made with ❤️ for the Zabbix community**

Enjoy your AI-powered monitoring assistant! 🤖✨

