# API Usage Examples

## Basic Request Structure

All requests to OpenAI API follow this structure:

```javascript
{
  "model": "gpt-4o-mini",
  "messages": [
    {
      "role": "system",
      "content": "You are a helpful assistant."
    },
    {
      "role": "user", 
      "content": "Your question here"
    }
  ],
  "temperature": 0.7,
  "top_p": 1,
  "max_tokens": 2048,
  "stream": true
}
```

## Message Roles

### System Message
Defines AI behavior and personality:

```javascript
{
  "role": "system",
  "content": "You are a Zabbix expert specializing in infrastructure monitoring."
}
```

### User Message
Your questions and inputs:

```javascript
{
  "role": "user",
  "content": "How do I configure SNMP monitoring in Zabbix?"
}
```

### Assistant Message
AI responses (automatically added to conversation history):

```javascript
{
  "role": "assistant",
  "content": "To configure SNMP monitoring in Zabbix..."
}
```

## Parameters Explained

### model
Which AI model to use:
- `gpt-4o` - Latest GPT-4 optimized
- `gpt-4o-mini` - Fast and economical
- `gpt-4-turbo` - Most capable GPT-4
- `gpt-3.5-turbo` - Fastest, cheapest

### temperature
Controls randomness (0.0 to 2.0):
```javascript
"temperature": 0.7  // Balanced
"temperature": 0.2  // More focused
"temperature": 1.5  // More creative
```

### top_p
Nucleus sampling (0.0 to 1.0):
```javascript
"top_p": 1    // Consider all tokens (default)
"top_p": 0.1  // Only top 10% probability
```

### max_tokens
Maximum response length:
```javascript
"max_tokens": 512   // Short responses
"max_tokens": 2048  // Medium (recommended)
"max_tokens": 4096  // Long responses
```

### stream
Enable streaming responses:
```javascript
"stream": true   // Stream response chunks
"stream": false  // Return complete response
```

## Example API Calls

### Simple Question

```bash
curl https://api.openai.com/v1/chat/completions \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_API_KEY" \
  -d '{
    "model": "gpt-4o-mini",
    "messages": [
      {"role": "user", "content": "What is Zabbix?"}
    ]
  }'
```

### With System Prompt

```bash
curl https://api.openai.com/v1/chat/completions \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_API_KEY" \
  -d '{
    "model": "gpt-4o",
    "messages": [
      {
        "role": "system", 
        "content": "You are a network monitoring expert."
      },
      {
        "role": "user",
        "content": "How do I monitor bandwidth usage?"
      }
    ],
    "temperature": 0.5
  }'
```

### Streaming Response

```bash
curl https://api.openai.com/v1/chat/completions \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_API_KEY" \
  -d '{
    "model": "gpt-4o-mini",
    "messages": [
      {"role": "user", "content": "Explain triggers in Zabbix"}
    ],
    "stream": true
  }'
```

### With Conversation History

```bash
curl https://api.openai.com/v1/chat/completions \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_API_KEY" \
  -d '{
    "model": "gpt-4o",
    "messages": [
      {"role": "system", "content": "You are a Zabbix expert."},
      {"role": "user", "content": "How do I create a trigger?"},
      {"role": "assistant", "content": "To create a trigger in Zabbix..."},
      {"role": "user", "content": "Can you give me an example?"}
    ]
  }'
```

## Response Format

### Standard Response

```json
{
  "id": "chatcmpl-123",
  "object": "chat.completion",
  "created": 1677652288,
  "model": "gpt-4o-mini",
  "choices": [{
    "index": 0,
    "message": {
      "role": "assistant",
      "content": "Your answer here..."
    },
    "finish_reason": "stop"
  }],
  "usage": {
    "prompt_tokens": 10,
    "completion_tokens": 50,
    "total_tokens": 60
  }
}
```

### Streaming Response

```
data: {"choices":[{"delta":{"content":"Hello"}}]}

data: {"choices":[{"delta":{"content":" there"}}]}

data: {"choices":[{"delta":{"content":"!"}}]}

data: [DONE]
```

## Error Handling

### Rate Limit Error

```json
{
  "error": {
    "message": "Rate limit reached",
    "type": "rate_limit_error",
    "code": "rate_limit_exceeded"
  }
}
```

### Invalid API Key

```json
{
  "error": {
    "message": "Invalid API key",
    "type": "invalid_request_error",
    "code": "invalid_api_key"
  }
}
```

### Insufficient Credits

```json
{
  "error": {
    "message": "You exceeded your current quota",
    "type": "insufficient_quota",
    "code": "insufficient_quota"
  }
}
```

## Widget Implementation

The widget handles all API communication automatically:

1. Builds message array with system prompt + history
2. Sends request to OpenAI API
3. Handles streaming or complete responses
4. Parses and displays markdown
5. Saves to conversation history
6. Handles errors gracefully

## Testing API Key

Quick test with curl:

```bash
curl https://api.openai.com/v1/chat/completions \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_API_KEY" \
  -d '{
    "model": "gpt-4o-mini",
    "messages": [{"role": "user", "content": "Say hello"}],
    "max_tokens": 10
  }'
```

Expected response:
```json
{
  "choices": [{
    "message": {
      "content": "Hello! How can I assist you today?"
    }
  }]
}
```

## Rate Limits

### Free Tier (if applicable)
- 3 requests per minute
- 200 requests per day

### Pay-as-you-go
- 3,500 requests per minute (RPM)
- 90,000 tokens per minute (TPM)

### Tips to Avoid Rate Limits
- Use GPT-4o Mini for high-volume requests
- Implement exponential backoff
- Monitor usage on OpenAI dashboard
- Cache common responses

## Cost Estimation

### Input Tokens (Prompt)
Your questions + system prompt + conversation history

### Output Tokens (Completion)
AI's response

### Example Calculation

**GPT-4o Mini:**
- Input: $0.150 / 1M tokens
- Output: $0.600 / 1M tokens

**Typical conversation:**
- System prompt: 50 tokens
- User message: 100 tokens
- AI response: 300 tokens
- Total: 450 tokens

**Cost per message:**
- Input: (150 tokens × $0.150) / 1M = $0.0000225
- Output: (300 tokens × $0.600) / 1M = $0.0001800
- **Total: ~$0.0002 (0.02 cents)**

**100 messages = ~$0.02**
**1000 messages = ~$0.20**

Very affordable for daily use!

## Best Practices

1. **Start with GPT-4o Mini**
   - Test functionality
   - Understand costs
   - Upgrade to GPT-4 if needed

2. **Use System Prompts Effectively**
   - Define role clearly
   - Set context
   - Specify output format

3. **Manage Conversation History**
   - Keep relevant context
   - Clear when switching topics
   - Monitor token usage

4. **Handle Errors Gracefully**
   - Implement retry logic
   - Show user-friendly messages
   - Log errors for debugging

5. **Optimize Token Usage**
   - Be concise in prompts
   - Clear unnecessary history
   - Use appropriate max_tokens

## Additional Resources

- [OpenAI API Documentation](https://platform.openai.com/docs)
- [OpenAI Pricing](https://openai.com/pricing)
- [OpenAI Playground](https://platform.openai.com/playground)
- [API Usage Dashboard](https://platform.openai.com/usage)

