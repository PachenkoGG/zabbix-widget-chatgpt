# Configuration Guide

## Basic Configuration

### 1. API Token (Required)
Get your OpenAI API key from [OpenAI Platform](https://platform.openai.com/api-keys)

```
sk-xxxxxxxxxxxxxxxxxxxxxxxxxx
```

### 2. Select AI Model
Choose the model that best fits your needs:

#### Recommended Models:
- **GPT-4o Mini**: Fast, cost-effective, good for most tasks
- **GPT-4o**: Balanced performance and cost
- **GPT-4 Turbo**: Best for complex analysis

## Advanced Configuration

### Temperature
Controls randomness in responses (0.0 to 2.0):
- **0.0-0.3**: More focused, deterministic
- **0.7-0.9**: Balanced (recommended)
- **1.5-2.0**: More creative, varied

### Top P (Nucleus Sampling)
Alternative to temperature (0.0 to 1.0):
- **0.1**: Only considers top 10% probable tokens
- **1.0**: Considers all tokens (default)

### Max Tokens
Maximum length of response:
- **512**: Short responses
- **2048**: Medium responses (recommended)
- **4096**: Long, detailed responses

### System Prompt Examples

#### Zabbix Expert
```
You are a Zabbix monitoring expert with deep knowledge of metrics, triggers, 
and problem analysis. Provide clear, actionable advice with examples.
```

#### Security Analyst
```
You are a cybersecurity expert focusing on threat detection and incident 
response. Analyze security events and provide mitigation strategies.
```

#### DevOps Assistant
```
You are a DevOps engineer specializing in infrastructure monitoring, 
automation, and best practices. Help optimize monitoring configurations.
```

#### Network Engineer
```
You are a network infrastructure expert. Help troubleshoot network issues, 
analyze traffic patterns, and optimize performance.
```

## Custom API Endpoints

### Azure OpenAI
```
https://YOUR-RESOURCE-NAME.openai.azure.com/openai/deployments/YOUR-DEPLOYMENT-NAME/chat/completions?api-version=2024-02-15-preview
```

### LocalAI (Self-hosted)
```
http://localhost:8080/v1/chat/completions
```

### OpenRouter
```
https://openrouter.ai/api/v1/chat/completions
```

## Model Comparison

### Speed vs Quality

| Model | Speed | Quality | Cost/1K tokens |
|-------|-------|---------|----------------|
| GPT-4o Mini | ⚡⚡⚡⚡ | ⭐⭐⭐ | $0.00015 |
| GPT-4o | ⚡⚡⚡ | ⭐⭐⭐⭐ | $0.005 |
| GPT-4 Turbo | ⚡⚡ | ⭐⭐⭐⭐⭐ | $0.01 |
| GPT-4 | ⚡ | ⭐⭐⭐⭐⭐ | $0.03 |

### Use Cases

**GPT-4o Mini**: 
- Quick monitoring queries
- Log analysis
- Simple troubleshooting
- High-volume requests

**GPT-4o**:
- Incident investigation
- Performance analysis
- Configuration help
- General purpose

**GPT-4 Turbo**:
- Complex problem solving
- Root cause analysis
- Strategic planning
- Detailed reporting

**GPT-4**:
- Critical incident analysis
- Security investigations
- Complex integrations
- Highest accuracy needs

## Security Best Practices

1. **API Key Security**
   - Never share your API key
   - Use environment variables for production
   - Rotate keys regularly
   - Monitor API usage

2. **Data Privacy**
   - Don't send sensitive credentials to AI
   - Review prompts before sending
   - Be aware conversation history is stored locally
   - Clear history when handling sensitive data

3. **Access Control**
   - Limit widget access to authorized users
   - Use Zabbix user permissions
   - Monitor widget usage
   - Audit API calls

## Performance Tuning

### Reduce Latency
- Use GPT-4o Mini or GPT-3.5 Turbo
- Enable streaming responses
- Reduce max_tokens
- Lower temperature

### Improve Quality
- Use GPT-4 Turbo or GPT-4
- Increase max_tokens
- Craft detailed system prompts
- Provide context in questions

### Optimize Costs
- Use GPT-4o Mini for most queries
- Set appropriate max_tokens
- Cache common responses
- Monitor token usage

## Troubleshooting

### Widget not loading
```bash
# Check Zabbix logs
tail -f /var/log/zabbix/zabbix_server.log

# Verify file permissions
ls -la /usr/share/zabbix/modules/openai-assistant/

# Restart services
systemctl restart zabbix-server
systemctl restart apache2
```

### API errors
- Verify API key is correct
- Check OpenAI account has credits
- Verify endpoint URL
- Check network connectivity
- Review rate limits

### Slow responses
- Switch to faster model (GPT-4o Mini)
- Enable streaming
- Reduce max_tokens
- Check API status

## Examples

### Monitoring Query
```
What are the best Zabbix triggers for detecting high CPU usage?
```

### Troubleshooting
```
Server response time increased by 200%. 
Memory usage is at 85%, CPU at 40%, disk I/O normal.
What could be the issue?
```

### Configuration Help
```
How do I configure a trigger to alert when disk usage exceeds 80% 
for more than 5 minutes?
```

### Log Analysis
```
I see errors in my logs:
"Connection timeout after 30s"
"Failed to connect to database"
What should I check?
```

