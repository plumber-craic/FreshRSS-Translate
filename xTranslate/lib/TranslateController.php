<?php
require_once(__DIR__ . '/TranslationService.php');
class TranslateExtensionController {
    public function translate($text, $targetLang = 'zh') {
        if (empty($text)) {
            error_log("Translate: Empty text provided");
            return '';
        }
        $serviceType = FreshRSS_Context::$user_conf->TranslateService ?? 'google';
        $translationService = new TranslateExtensionService($serviceType);
        $translatedText = '';
        $attempts = 0;
        $sleepTime = 1;
        error_log("Translate: Service: " . $serviceType . ", Text: " . $text);
        while ($attempts < 2) {
            try {
                $translatedText = $translationService->translate($text, $targetLang);
                if (!empty($translatedText)) {
                    error_log("Translate: Translation successful: " . $translatedText);
                    break;
                }
                error_log("Translate: Empty translation result on attempt " . ($attempts + 1));
            } catch (Exception $e) {
                error_log("Translate: Translation error on attempt " . ($attempts + 1) . " - " . $e->getMessage());
                $attempts++;
                sleep($sleepTime);
                $sleepTime *= 2;
            }
        }
        if (empty($translatedText) && $serviceType == 'deeplx') {
            error_log("Translate: DeeplX failed, falling back to Google Translate");
            $translationService = new TranslateExtensionService('google');
            try {
                $translatedText = $translationService->translate($text, $targetLang);
                if (!empty($translatedText)) {
                    error_log("Translate: Google Translate fallback successful: " . $translatedText);
                }
            } catch (Exception $e) {
                error_log("Translate: Google Translate fallback failed - " . $e->getMessage());
            }
        }
        if (empty($translatedText)) {
            error_log("Translate: All translation attempts failed, returning original text");
            return $text;
        }
        return $translatedText;
    }
}