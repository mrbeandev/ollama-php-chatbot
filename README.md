# 🤖 Ollama PHP Chatbot

> A modern PHP interface for interacting with Ollama models featuring a responsive web UI and robust API client.

## ✨ Features

| Core Features | Description |
|--------------|-------------|
| 📱 **Responsive Web Interface** | Mobile-friendly chat interface with dark/light theme support |
| 🔄 **Multiple Model Support** | Switch between different Ollama models seamlessly |
| 📝 **Template System** | Built-in prompt templates for different use cases |
| 🧠 **Context Support** | Add custom context to guide model responses |
| 📚 **Conversation Logging** | Chat history saved in markdown files by date |
| 💻 **Code Highlighting** | Automatic syntax highlighting for code blocks |
| 📱 **Mobile-First Design** | Adaptive sidebar and controls for all screen sizes |

## 🚀 Quick Start

<details>
<summary>Installation Steps</summary>

1. ✅ Ensure you have Ollama installed and running locally
2. 📂 Clone this repository:
```bash
git clone https://github.com/yourusername/ollama-php-chatbot.git
cd ollama-php-chatbot
chmod 777 conversations
```
3. 🌐 Access via web browser at `http://localhost/ollama-php-chatbot`

</details>

## 💻 Web Interface (index.php)

### 🎯 Features
- 🔄 Model selection dropdown
- 📝 Context input field
- 📋 Template selection
- 🌓 Dark/light theme toggle
- 📱 Mobile-friendly design
- ⌨️ Code syntax highlighting
- ✍️ Markdown formatting

### 📝 Example Usage
1. 🔍 Select a model from dropdown
2. 💡 (Optional) Add context 
3. 📋 (Optional) Choose a template
4. 💬 Type message and press Enter/Send

## 🛠️ Ollama PHP Client (ollama.php)

> A standalone PHP client for Ollama API integration.

### 📌 Basic Usage

```php
require_once 'ollama.php';

// Initialize with debug mode
$ollama = new Ollama(true); 

// Simple response generation
$response = $ollama->generateResponse('llama2', 'What is PHP?');

// Response with context and template
$response = $ollama->generateResponse(
    'codellama', 
    'Write a sorting function',
    'Use PHP language with types',
    'coder'
);
```

<details>
<summary>🔧 Advanced Examples</summary>

```php
// Model Management
$ollama = new Ollama();

// List all models
$models = $ollama->getModelList();
foreach ($models as $model) {
    echo "{$model['name']}: {$model['description']}\n";
}

// Get detailed model info
$modelInfo = $ollama->getModelInfo('llama2');
print_r($modelInfo);

// Unload model to free memory
$ollama->unloadModel('llama2');

// Custom Templates
$ollama->addPromptTemplate('sql', 'You are an SQL expert. Provide optimized queries.');
$response = $ollama->generateResponse(
    'llama2',
    'How to join three tables?',
    '',
    'sql'
);
```
</details>

### 📚 Built-in Templates

| Template | Purpose |
|----------|---------|
| 🤖 general | Default helpful assistant |
| 💻 coder | Programming expert |
| 📊 analyst | Data analysis specialist |
| 👨‍🏫 teacher | Educational explanations |
| 🎨 creative | Creative writing assistance |

### 🔌 API Reference

<details>
<summary>Core Methods</summary>

```php
generateResponse(string $model, string $prompt, string $context = '', string $template = 'general'): string
getModelList(): array
getModelInfo(string $modelName): ?array
unloadModel(string $modelName): bool
addPromptTemplate(string $name, string $content): void
getPromptTemplates(): array
handleAction(string $action, string $modelName): mixed
```
</details>

<details>
<summary>Debug Methods</summary>

```php
getDebugInfo(): array
checkApiStatus(): array
getRunningModels(): ?array
```
</details>

## 📁 Project Structure

```
ollama-php-chatbot/
├── 📄 index.php         # Web chat interface
├── 📄 ollama.php        # Standalone Ollama client
├── 📁 js/
│   └── 📄 chat.js      # UI interactions
└── 📁 conversations/    # Chat history storage
```

## 📋 Requirements

- ✅ PHP 7.4+
- ✅ Ollama running locally (default: http://localhost:11434)
- ✅ Web server (Apache/Nginx)
- ✅ Write permissions for conversations directory

## 🤝 Contributing

1. 🔄 Fork the repository
2. 🌿 Create a feature branch
3. 📤 Submit a pull request

## 📜 License & Support

- 📄 MIT License
- 🐛 Issues and features: Use GitHub issues tracker
- 📚 API Documentation: See inline PHP comments

> [!NOTE]
> Make sure to keep Ollama updated for the best performance.

> [!TIP]
> Check the conversations directory permissions if logging isn't working.

> [!IMPORTANT]
> Always backup your conversations before updating.

## 👨‍💻 Developer Contact

- Telegram: [@mrbeadev](https://t.me/mrbeandev)

