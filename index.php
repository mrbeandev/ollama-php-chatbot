<?php
/**
 * Ollama PHP Chat Interface
 *
 * This script serves as the main entry point for the Ollama PHP Chat interface.
 * It handles API requests, manages the chat UI, and interacts with the Ollama class.
 *
 * @package OllamaPHPChat
 * @version 1.0.0
 * @author Your Name
 * @license MIT
 */
ini_set('display_errors', 1);
error_reporting(E_ALL);
$my_default_model = 'llama3.2:latest'; // Default model in the select box

// Load the OLLAMA library
require_once 'ollama.php';

// Check if debug mode is enabled
$debug_mode = isset($_GET['debug']) && $_GET['debug'] === 'true';

// Initialize the OLLAMA engine with debug mode
$ollama = new Ollama($debug_mode);

// Define the path to the markdown files
$markdown_dir = 'conversations';
if (!file_exists($markdown_dir)) {
    mkdir($markdown_dir, 0777, true);
}

// Get the current date for the conversation file
$current_date = date('Y-m-d');
$conversation_file = "$markdown_dir/$current_date.txt";

// Handle incoming messages
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    // Handle actions (like unload)
    if (isset($data['action'])) {
        try {
            $result = $ollama->handleAction($data['action'], $data['model']);
            echo json_encode(['success' => true, 'message' => 'Action completed successfully']);
        } catch (Exception $e) {
            error_log('Error handling action: ' . $e->getMessage());
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }

    // Handle chat messages
    if (isset($data['model']) && isset($data['message'])) {
        $selected_model = htmlspecialchars($data['model']);
        $message = htmlspecialchars($data['message']);
        $context = isset($data['context']) ? htmlspecialchars($data['context']) : '';
        $template = isset($data['template']) ? htmlspecialchars($data['template']) : 'general';

        try {
            $response = $ollama->generateResponse($selected_model, $message, $context, $template);

            // Append the conversation to the markdown file without HTML color tags
            $conversation = "\n----\n\n### $message\n\n" . strtoupper($selected_model) . ":\n\n$response\n\n\n";
            file_put_contents($conversation_file, $conversation, FILE_APPEND);

            echo json_encode(['success' => true, 'response' => $response]);
        } catch (Exception $e) {
            error_log('Error in index.php: ' . $e->getMessage());
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }
}

// List all the models
$model_list = $ollama->getModelList();

// Move Llama3.1 to the beginning of the list
$default_model_key = array_search($my_default_model, array_column($model_list, 'name'));
if ($default_model_key !== false) {
    $default_model = $model_list[$default_model_key];
    unset($model_list[$default_model_key]);
    array_unshift($model_list, $default_model);
}

// Get debug information if in debug mode
$debug_info = $debug_mode ? $ollama->getDebugInfo() : null;
?>
<!DOCTYPE html>
<html lang="en" class="h-full">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ollama Chat Interface</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/@mdi/font@7.2.96/css/materialdesignicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.8.0/styles/github-dark.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.8.0/highlight.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <script src="js/chat.js" defer></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        primary: '#2563eb',
                        secondary: '#4f46e5',
                    }
                }
            }
        }
    </script>
    <style type="text/tailwindcss">
        @layer utilities {
            .scrollbar-custom {
                scrollbar-width: thin;
                scrollbar-color: rgba(156, 163, 175, 0.5) transparent;
            }
            .scrollbar-custom::-webkit-scrollbar {
                width: 6px;
            }
            .scrollbar-custom::-webkit-scrollbar-track {
                background: transparent;
            }
            .scrollbar-custom::-webkit-scrollbar-thumb {
                background-color: rgba(156, 163, 175, 0.5);
                border-radius: 3px;
            }
        }

        .mobile-menu-btn {
            @apply fixed top-4 left-4 z-50 py-1 px-2 bg-gray-800 dark:bg-gray-700 
                   text-white rounded-md lg:hidden shadow-lg 
                   hover:bg-gray-700 dark:hover:bg-gray-600 
                   transition-all duration-200 ease-in-out;
        }

        .sidebar-mobile {
            @apply fixed inset-y-0 left-0 z-40 w-64 transform -translate-x-full 
                   transition-transform duration-300 ease-in-out lg:static 
                   lg:transform-none bg-white dark:bg-gray-800 border-r 
                   border-gray-200 dark:border-gray-700 shadow-lg;
        }

        .sidebar-mobile.open {
            @apply translate-x-0 shadow-2xl;
        }

        /* Add overlay for mobile menu */
        .mobile-menu-overlay {
            @apply fixed inset-0 bg-black/50 lg:hidden;
            display: none;
        }

        .mobile-menu-overlay.active {
            display: block;
        }
    </style>
</head>

<body class="h-full bg-gray-50 dark:bg-gray-900">
    <!-- Mobile Menu Overlay -->
    <div id="mobile-menu-overlay" class="mobile-menu-overlay"></div>

    <!-- Mobile Menu Button -->
    <button id="mobile-menu-btn" class="mobile-menu-btn">
        <span class="mdi mdi-menu text-xl"></span>
    </button>

    <div class="flex h-full">
        <!-- Sidebar with mobile classes -->
        <aside class="sidebar-mobile bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 flex flex-col">
            <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                <h1 class="text-xl font-semibold text-gray-800 dark:text-white">Ollama Chat</h1>
            </div>

            <!-- Model Selection -->
            <div class="p-4 space-y-4">
                <div class="space-y-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Model</label>
                    <select id="model-select" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 px-3 py-2 text-sm">
                        <?php foreach ($model_list as $model):
                            $modelInfo = $ollama->getModelInfo($model['name']);
                            $details = isset($modelInfo['details']) ?
                                " ({$modelInfo['details']['parameter_size']}, {$modelInfo['details']['quantization_level']})" : '';
                        ?>
                            <option value="<?= htmlspecialchars($model['name']) ?>">
                                <?= htmlspecialchars($model['name']) . $details ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Model Controls -->
                <div class="space-y-2">
                    <button id="unload-model" class="w-full px-3 py-2 text-sm text-white bg-red-600 hover:bg-red-700 rounded-md">
                        Unload Model
                    </button>
                    <button id="clear-chat" class="w-full px-3 py-2 text-sm text-gray-700 dark:text-gray-200 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-md">
                        Clear Chat
                    </button>
                </div>

                <!-- Prompt Templates -->
                <div class="space-y-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Template</label>
                    <select id="prompt-template" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 px-3 py-2 text-sm">
                        <option value="">No Template</option>
                        <?php foreach ($ollama->getPromptTemplates() as $template): ?>
                            <option value="<?= htmlspecialchars($template) ?>"><?= ucfirst($template) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Theme Toggle -->
            <div class="mt-auto p-4 border-t border-gray-200 dark:border-gray-700">
                <button id="theme-toggle" class="w-full flex items-center justify-center px-3 py-2 text-sm text-gray-700 dark:text-gray-200 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-md">
                    <span class="mdi mdi-theme-light-dark mr-2"></span>
                    <span id="theme-label">Toggle Theme</span>
                </button>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 flex flex-col h-full">
            <!-- Chat Window -->
            <div id="chat-window" class="flex-1 p-4 overflow-y-auto scrollbar-custom space-y-4">
                <!-- Messages will be inserted here -->
            </div>

            <!-- Input Area -->
            <div class="border-t border-gray-200 dark:border-gray-700 p-4 space-y-4">
                <textarea id="context-input"
                    class="w-full px-3 py-2 text-sm text-gray-900 dark:text-gray-100 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md"
                    rows="2"
                    placeholder="Add context here (optional)"></textarea>

                <div class="flex space-x-2">
                    <textarea id="chat-input"
                        class="flex-1 px-3 py-2 text-sm text-gray-900 dark:text-gray-100 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md"
                        rows="3"
                        placeholder="Type your message..."></textarea>
                    <button id="send-chat" class="px-4 py-2 bg-primary hover:bg-primary/90 text-white rounded-md flex items-center justify-center">
                        <span class="mdi mdi-send"></span>
                    </button>
                </div>
            </div>
        </main>
    </div>

    <!-- Loading Indicator -->
    <div id="loading" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center">
        <div class="bg-white dark:bg-gray-800 rounded-lg p-4 flex items-center space-x-3">
            <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-primary"></div>
            <span class="text-gray-900 dark:text-gray-100">Processing...</span>
        </div>
    </div>
</body>

</html>