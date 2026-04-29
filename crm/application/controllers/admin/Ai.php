<?php

use app\services\ai\AiProviderRegistry;
use app\services\ai\AudioTranscriptionService;
use app\services\ai\Contracts\AiProviderInterface;

defined('BASEPATH') or exit('No direct script access allowed');

class Ai extends AdminController
{
    private AiProviderInterface $provider;

    public function __construct()
    {
        parent::__construct();

        $this->provider = AiProviderRegistry::getProvider(get_option('ai_provider'));
    }

    public function text_enhancement($enhancementType)
    {
        if (! in_array($enhancementType, ['polite', 'formal', 'friendly'])) {
            show_404('Invalid enhancement type');
        }

        try {
            $enhancedText = $this->provider->enhanceText($this->input->post('text'), $enhancementType);

            echo json_encode([
                'success' => true,
                'message' => $enhancedText,
            ]);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error'   => $e->getMessage(),
            ]);
        }
    }

    public function transcribe_audio()
    {
        try {
            if (! isset($_FILES['audio'])) {
                throw new Exception('No audio file was uploaded.');
            }

            $audio = $_FILES['audio'];

            if (($audio['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                throw new Exception('Audio upload failed.');
            }

            $service = new AudioTranscriptionService();

            if (! $service->isConfigured()) {
                throw new Exception('Server transcription is not configured.');
            }

            $transcript = $service->transcribe(
                $audio['tmp_name'],
                $audio['name'] ?? 'dictation.webm',
                $audio['type'] ?? 'audio/webm'
            );

            echo json_encode([
                'success'    => true,
                'transcript' => $transcript,
            ]);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error'   => $e->getMessage(),
            ]);
        }
    }
}
