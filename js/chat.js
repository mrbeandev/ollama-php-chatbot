/**
 * OllamaChat Class
 * 
 * This class manages the chat interface for the Ollama PHP Chat application.
 * It handles user interactions, API calls, and UI updates.
 *
 * @class
 */
class OllamaChat {
    /**
     * Initialize the OllamaChat instance.
     * Sets up event listeners and initializes the UI.
     */
    constructor() {
        this.chatWindow = document.getElementById('chat-window');
        this.chatInput = document.getElementById('chat-input');
        this.sendButton = document.getElementById('send-chat');
        this.modelSelect = document.getElementById('model-select');
        this.themeToggle = document.getElementById('theme-toggle');
        this.themeLabel = document.getElementById('theme-label');
        this.contextInput = document.getElementById('context-input');
        this.promptTemplate = document.getElementById('prompt-template');
        this.loadingIndicator = document.getElementById('loading');
        this.mobileMenuBtn = document.getElementById('mobile-menu-btn');
        this.sidebar = document.querySelector('.sidebar-mobile');
        this.mainContent = document.querySelector('main');
        this.overlay = document.getElementById('mobile-menu-overlay');
        
        this.initializeEventListeners();
        this.initializeTheme();
        this.initializeMobileMenu();
        this.welcomeMessage();
    }

    /**
     * Set up event listeners for various UI elements.
     */
    initializeEventListeners() {
        // Chat handlers
        this.sendButton.addEventListener('click', () => this.sendMessage());
        this.chatInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                this.sendMessage();
            }
        });

        // Model management
        this.modelSelect.addEventListener('change', () => {
            this.appendMessage('System', `Switched to model: ${this.modelSelect.value}`);
        });

        document.getElementById('unload-model').addEventListener('click', () => this.unloadModel());
        document.getElementById('clear-chat').addEventListener('click', () => this.clearChat());

        // Template handler
        this.promptTemplate.addEventListener('change', () => {
            const template = this.promptTemplate.value;
            if (template) {
                this.appendMessage('System', `Using ${template} template`);
            }
        });

        // Theme handler
        this.themeToggle.addEventListener('click', () => this.toggleTheme());
    }

    /**
     * Send a chat message to the server and handle the response.
     */
    async sendMessage() {
        const message = this.chatInput.value.trim();
        const context = this.contextInput.value.trim();
        const template = this.promptTemplate.value;
        const model = this.modelSelect.value;
        
        if (!message) return;

        try {
            this.showLoading(true);
            this.chatInput.value = '';
            
            this.appendMessage('User', message, false, true);

            const response = await fetch('index.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ 
                    model: model,
                    message: message,
                    context: context,
                    template: template
                })
            });

            const data = await response.json();
            if (data.success) {
                this.appendMessage(model, data.response);
            } else {
                throw new Error(data.error || 'Failed to get response');
            }
        } catch (error) {
            this.appendMessage('Error', error.message, true);
        } finally {
            this.showLoading(false);
        }
    }

    /**
     * Unload the currently selected model.
     */
    async unloadModel() {
        const model = this.modelSelect.value;
        try {
            this.showLoading(true);
            const response = await fetch('index.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ 
                    action: 'unload',
                    model: model 
                })
            });
            const data = await response.json();
            if (data.success) {
                this.appendMessage('System', `Unloaded model: ${model}`);
            } else {
                throw new Error(data.error);
            }
        } catch (error) {
            this.appendMessage('Error', error.message, true);
        } finally {
            this.showLoading(false);
        }
    }

    /**
     * Clear the chat window and display a system message.
     */
    clearChat() {
        this.chatWindow.innerHTML = '';
        this.appendMessage('System', 'Chat cleared');
    }

    /**
     * Create a DOM element for a chat message.
     * 
     * @param {string} sender - The sender of the message
     * @param {string} content - The content of the message
     * @param {boolean} isError - Whether the message is an error
     * @param {boolean} isUser - Whether the message is from the user
     * @returns {HTMLElement} The created message element
     */
    createMessageElement(sender, content, isError = false, isUser = false) {
        const div = document.createElement('div');
        div.className = `p-4 rounded-lg ${
            isUser ? 'bg-primary/10 ml-12' : 'bg-white dark:bg-gray-800 mr-12'
        }`;

        const header = document.createElement('div');
        header.className = 'flex items-center space-x-2 mb-2';
        
        const icon = document.createElement('span');
        icon.className = `mdi ${isUser ? 'mdi-account' : 'mdi-robot'} text-xl ${
            isUser ? 'text-primary' : 'text-gray-600 dark:text-gray-400'
        }`;
        
        const name = document.createElement('span');
        name.className = 'font-medium text-gray-900 dark:text-gray-100';
        name.textContent = sender.toUpperCase();

        header.appendChild(icon);
        header.appendChild(name);
        
        const body = document.createElement('div');
        body.className = 'prose dark:prose-invert max-w-none';
        body.innerHTML = isUser ? content.replace(/\n/g, '<br>') : marked.parse(content);

        div.appendChild(header);
        div.appendChild(body);

        if (isError) {
            div.classList.add('bg-red-50', 'dark:bg-red-900/50', 'border', 'border-red-200', 'dark:border-red-800');
        }

        return div;
    }

    /**
     * Append a new message to the chat window.
     * 
     * @param {string} sender - The sender of the message
     * @param {string} content - The content of the message
     * @param {boolean} isError - Whether the message is an error
     * @param {boolean} isUser - Whether the message is from the user
     */
    appendMessage(sender, content, isError = false, isUser = false) {
        const messageEl = this.createMessageElement(sender, content, isError, isUser);
        this.chatWindow.appendChild(messageEl);
        this.chatWindow.scrollTop = this.chatWindow.scrollHeight;
        hljs.highlightAll();
    }

    /**
     * Initialize the theme based on user preference or system settings.
     */
    initializeTheme() {
        if (localStorage.theme === 'dark' || 
            (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            this.setTheme(true);
        } else {
            this.setTheme(false);
        }

        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', e => {
            if (!localStorage.theme) {
                this.setTheme(e.matches);
            }
        });
    }

    /**
     * Set the theme (light or dark).
     * 
     * @param {boolean} isDark - Whether to set dark mode
     */
    setTheme(isDark) {
        const html = document.documentElement;
        if (isDark) {
            html.classList.add('dark');
            this.themeLabel.textContent = 'Light Mode';
            localStorage.setItem('theme', 'dark');
        } else {
            html.classList.remove('dark');
            this.themeLabel.textContent = 'Dark Mode';
            localStorage.setItem('theme', 'light');
        }
    }

    /**
     * Toggle between light and dark themes.
     */
    toggleTheme() {
        const isDark = document.documentElement.classList.contains('dark');
        this.setTheme(!isDark);
    }

    /**
     * Initialize the mobile menu functionality.
     */
    initializeMobileMenu() {
        if (!this.mobileMenuBtn) return; // Guard clause

        // Toggle menu on button click
        this.mobileMenuBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            this.toggleMobileMenu();
        });

        // Close menu when clicking overlay
        this.overlay.addEventListener('click', () => {
            this.toggleMobileMenu(false);
        });

        // Close menu when clicking outside
        document.addEventListener('click', (e) => {
            if (this.sidebar.classList.contains('open') && 
                !this.sidebar.contains(e.target) && 
                !this.mobileMenuBtn.contains(e.target)) {
                this.toggleMobileMenu(false);
            }
        });

        // Handle resize events
        window.addEventListener('resize', () => {
            if (window.innerWidth >= 1024) {
                this.toggleMobileMenu(false);
            }
        });
    }

    /**
     * Toggle the mobile menu open or closed.
     * 
     * @param {boolean|null} force - Force open (true), closed (false), or toggle (null)
     */
    toggleMobileMenu(force = null) {
        const willOpen = force !== null ? force : !this.sidebar.classList.contains('open');
        
        // Toggle sidebar
        this.sidebar.classList.toggle('open', willOpen);
        
        // Toggle overlay
        this.overlay.classList.toggle('active', willOpen);
        
        // Toggle menu icon
        const icon = this.mobileMenuBtn.querySelector('.mdi');
        icon.classList.toggle('mdi-menu', !willOpen);
        icon.classList.toggle('mdi-close', willOpen);
        
        // Toggle body scroll
        document.body.style.overflow = willOpen ? 'hidden' : '';
    }

    /**
     * Show or hide the loading indicator.
     * 
     * @param {boolean} show - Whether to show the loading indicator
     */
    showLoading(show) {
        if (show) {
            this.loadingIndicator.classList.remove('hidden');
        } else {
            this.loadingIndicator.classList.add('hidden');
        }
    }

    /**
     * Display the welcome message in the chat window.
     */
    welcomeMessage() {
        this.appendMessage('System', 'Welcome to Ollama Chat! Select a model and start chatting.');
    }
}

// Initialize the chat interface when the DOM is fully loaded
document.addEventListener('DOMContentLoaded', () => {
    window.ollamaChat = new OllamaChat();
});