<?php
/**
 * Ollama PHP Client
 *
 * This class provides a PHP interface for interacting with the Ollama API.
 * It allows for model management, chat interactions, and various utility functions.
 *
 * @package OllamaPHPClient
 * @version 1.0.0
 * @author Mrbeandev
 * @license MIT
 */
class Ollama {
    private $debug = false;
    private $models = [];
    private $baseUrl = 'http://localhost:11434/api';
    private $modelCache = [];
    private $promptTemplates = [
        'general' => ['role' => 'system', 'content' => 'You are a helpful assistant.'],
        'coder' => ['role' => 'system', 'content' => 'You are an expert programmer. Provide clear, secure, and efficient code.'],
        'analyst' => ['role' => 'system', 'content' => 'You are a data analyst. Provide detailed analysis and insights.'],
        'teacher' => ['role' => 'system', 'content' => 'You are a patient teacher. Explain concepts clearly and thoroughly.'],
        'creative' => ['role' => 'system', 'content' => 'You are a creative writer. Think outside the box and be imaginative.']
    ];

    /**
     * Constructor for the Ollama class.
     *
     * @param bool $debug Enable debug mode for additional logging
     */
    public function __construct($debug = false) {
        $this->debug = $debug;
        $this->loadModels();
    }

    /**
     * Load available models from the Ollama API.
     *
     * @throws Exception if unable to fetch or parse model data
     */
    private function loadModels() {
        try {
            $ch = curl_init($this->baseUrl . '/tags');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($ch);

            if (curl_errno($ch)) {
                throw new Exception('Failed to get models: ' . curl_error($ch));
            }

            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode !== 200) {
                throw new Exception("HTTP Error: $httpCode");
            }

            $data = json_decode($response, true);
            if (!isset($data['models'])) {
                throw new Exception('Invalid response format from Ollama API');
            }

            $this->models = array_map(function($model) {
                return [
                    'name' => $model['name'],
                    'description' => "Size: {$model['size']}, Modified: {$model['modified_at']}"
                ];
            }, $data['models']);

            if ($this->debug) {
                error_log("Models loaded: " . print_r($this->models, true));
            }

        } catch (Exception $e) {
            error_log("Error loading models: " . $e->getMessage());
            $this->models = [];
            throw $e;
        }
    }

    /**
     * Unload a model from memory.
     *
     * @param string $modelName Name of the model to unload
     * @return bool True if successful, throws exception otherwise
     * @throws Exception if unloading fails
     */
    public function unloadModel($modelName) {
        try {
            $ch = curl_init($this->baseUrl . '/generate');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode([
                    'model' => $modelName,
                    'prompt' => '',
                    'keep_alive' => '0s'
                ]),
                CURLOPT_HTTPHEADER => ['Content-Type: application/json']
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode !== 200) {
                throw new Exception("Failed to unload model: HTTP $httpCode");
            }

            // Clear model from cache
            unset($this->modelCache[$modelName]);
            return true;

        } catch (Exception $e) {
            error_log("Error unloading model: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get detailed information about a specific model.
     *
     * @param string $modelName Name of the model
     * @return array|null Model information or null if not found
     */
    public function getModelInfo($modelName) {
        if (isset($this->modelCache[$modelName])) {
            return $this->modelCache[$modelName];
        }

        try {
            $ch = curl_init($this->baseUrl . '/show');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode(['model' => $modelName]),
                CURLOPT_HTTPHEADER => ['Content-Type: application/json']
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode !== 200) {
                throw new Exception("Failed to get model info: HTTP $httpCode");
            }

            $modelInfo = json_decode($response, true);
            $this->modelCache[$modelName] = $modelInfo;
            return $modelInfo;

        } catch (Exception $e) {
            error_log("Error getting model info: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get a list of all available models.
     *
     * @return array List of available models
     */
    public function getModelList() {
        return $this->models;
    }

    /**
     * Generate a response using the specified model and prompt.
     *
     * @param string $modelName Name of the model to use
     * @param string $prompt User's input prompt
     * @param string $context Optional context for the conversation
     * @param string $template Optional template to use (default: 'general')
     * @return string Generated response
     * @throws Exception if response generation fails
     */
    public function generateResponse($modelName, $prompt, $context = '', $template = 'general') {
        try {
            // Prepare messages array with template
            $messages = [];
            
            // Add template if specified
            if (isset($this->promptTemplates[$template])) {
                $messages[] = $this->promptTemplates[$template];
            }

            // Add custom context if provided
            if (!empty($context)) {
                $messages[] = [
                    'role' => 'system',
                    'content' => $context
                ];
            }

            // Add user message
            $messages[] = [
                'role' => 'user',
                'content' => $prompt
            ];

            $data = [
                'model' => $modelName,
                'messages' => $messages,
                'stream' => false,
                'options' => [
                    'temperature' => 0.7,
                    'top_p' => 0.9,
                    'frequency_penalty' => 0.1
                ]
            ];

            $ch = curl_init($this->baseUrl . '/chat');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($data),
                CURLOPT_HTTPHEADER => ['Content-Type: application/json']
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            if (curl_errno($ch)) {
                throw new Exception('Curl error: ' . curl_error($ch));
            }
            curl_close($ch);

            if ($httpCode !== 200) {
                throw new Exception("HTTP Error: $httpCode");
            }

            $responseData = json_decode($response, true);
            if (!isset($responseData['message']['content'])) {
                throw new Exception('Invalid API response format');
            }

            // Return only the content since that's what the frontend expects
            return $responseData['message']['content'];

        } catch (Exception $e) {
            error_log("Error generating response: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Handle various actions on models (e.g., unloading).
     *
     * @param string $action Action to perform
     * @param string $modelName Name of the model
     * @return mixed Result of the action
     * @throws Exception for unknown actions
     */
    public function handleAction($action, $modelName) {
        switch ($action) {
            case 'unload':
                return $this->unloadModel($modelName);
            default:
                throw new Exception("Unknown action: $action");
        }
    }

    /**
     * Get available prompt templates.
     *
     * @return array List of available prompt template names
     */
    public function getPromptTemplates() {
        return array_keys($this->promptTemplates);
    }

    /**
     * Add a custom prompt template.
     *
     * @param string $name Name of the new template
     * @param string $content Content of the template
     */
    public function addPromptTemplate($name, $content) {
        $this->promptTemplates[$name] = [
            'role' => 'system',
            'content' => $content
        ];
    }

    /**
     * Get debug information about the current state.
     *
     * @return array Debug information
     */
    public function getDebugInfo() {
        try {
            return [
                'models' => $this->models,
                'running_models' => $this->getRunningModels(),
                'api_status' => $this->checkApiStatus(),
                'cache_status' => [
                    'models_cached' => count($this->modelCache),
                    'memory_usage' => memory_get_usage(true)
                ]
            ];
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    // Get list of running models using /api/ps endpoint
    private function getRunningModels() {
        $ch = curl_init($this->baseUrl . '/ps');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);

        if ($response) {
            return json_decode($response, true);
        }
        return null;
    }

    // Check if API is accessible
    private function checkApiStatus() {
        $ch = curl_init($this->baseUrl . '/tags');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [
            'code' => $httpCode,
            'accessible' => ($httpCode === 200)
        ];
    }
}

?>
