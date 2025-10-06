<?php
class TranslateExtensionService {
    private $serviceType;
    private $deeplxBaseUrl;
    private $googleBaseUrl;
    private $libreBaseUrl;
    private $libreApiKey;

    public function __construct($serviceType) {
        $this->serviceType = $serviceType;
        $this->deeplxBaseUrl = FreshRSS_Context::$user_conf->DeeplxApiUrl;
        $this->googleBaseUrl = 'https://translate.googleapis.com/translate_a/single';
        $this->libreBaseUrl = FreshRSS_Context::$user_conf->LibreApiUrl;
        $this->libreApiKey = FreshRSS_Context::$user_conf->LibreApiKey;
    }

    public function translate($text, $targetLang = 'zh') {
        switch ($this->serviceType) {
            case 'deeplx':
                return $this->translateWithDeeplx($text, $targetLang);
            case 'libre':
                return $this->translateWithLibre($text, $targetLang);
            default:
                return $this->translateWithGoogle($text, $targetLang);
        }
    }

    private function translateWithLibre($text, $targetLang) {
        if (empty($text)) {
            return '';
        }

        $apiUrl = rtrim($this->libreBaseUrl, '/') . '/translate';
        
        $postData = array(
            'q' => $text,
            'source' => 'auto',
            'target' => $targetLang,
            'format' => 'text'
        );
        
        if (!empty($this->libreApiKey)) {
            $postData['api_key'] = $this->libreApiKey;
        }

        $jsonData = json_encode($postData);
        
        error_log("LibreTranslate Request URL: " . $apiUrl);
        error_log("LibreTranslate Request Data: " . $jsonData);

        $options = array(
            'http' => array(
                'header' => array(
                    'Content-Type: application/json',
                    'Content-Length: ' . strlen($jsonData)
                ),
                'method' => 'POST',
                'content' => $jsonData,
                'timeout' => 10,
                'ignore_errors' => true
            ),
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );

        $context = stream_context_create($options);

        try {
            $result = @file_get_contents($apiUrl, false, $context);
            
            $responseHeaders = $http_response_header ?? array();
            $statusLine = $responseHeaders[0] ?? '';
            error_log("LibreTranslate Response Status: " . $statusLine);
            
            if ($result === FALSE) {
                error_log("LibreTranslate API request failed - No Response");
                return $text;
            }

            error_log("LibreTranslate Raw Response: " . $result);
            
            $response = json_decode($result, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log("LibreTranslate JSON decode error: " . json_last_error_msg());
                return $text;
            }

            if (isset($response['translatedText'])) {
                return mb_convert_encoding($response['translatedText'], 'UTF-8', 'UTF-8');
            } else if (isset($response['error'])) {
                error_log("LibreTranslate API error: " . $response['error']);
                return $text;
            } else {
                error_log("LibreTranslate API unexpected response structure: " . print_r($response, true));
                return $text;
            }
        } catch (Exception $e) {
            error_log("LibreTranslate exception: " . $e->getMessage());
            return $text;
        }
    }

    private function translateWithGoogle($text, $targetLang) {
        $translatedText = '';

        $queryParams = http_build_query([
            'client' => 'gtx',
            'sl' => 'auto',
            'tl' => $targetLang,
            'dt' => 't',
            'q' => $text,
        ]);

        $url = $this->googleBaseUrl . '?' . $queryParams;

        $options = [
            'http' => [
                'method' => 'GET',
                'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
                'timeout' => 3,
            ],
        ];

        $context = stream_context_create($options);

        try {
            $result = @file_get_contents($url, false, $context);
            if ($result === FALSE) {
                throw new Exception("Failed to get content from Google Translate API.");
            }

            $response = json_decode($result, true);
            if (!empty($response[0][0][0])) {
                $translatedText = $response[0][0][0];
            } else {
                throw new Exception("Google Translate API returned an empty translation.");
            }
        } catch (Exception $e) {
            error_log("Error in translation: " . $e->getMessage());
        }

        return $translatedText;
    }

    private function translateWithDeeplx($text, $targetLang) {
        $translatedText = '';

        sleep(rand(1, 3));

        // Convert language codes to DeepL format
        $deeplTargetLang = strtoupper($targetLang);
        // Special cases for DeepL language codes
        $langMap = [
            'pt' => 'PT-BR',  // Portuguese (Brazil)
            'en' => 'EN-US',  // English (US)
            'zh' => 'ZH'      // Chinese
        ];
        if (isset($langMap[strtolower($targetLang)])) {
            $deeplTargetLang = $langMap[strtolower($targetLang)];
        }

        $postData = json_encode([
            'text' => $text,
            'source_lang' => 'auto',
            'target_lang' => $deeplTargetLang
        ]);

        $options = [
            'http' => [
                'header' => "Content-Type: application/json\r\n",
                'method' => 'POST',
                'content' => $postData,
                'timeout' => 3,
            ]
        ];

        $context = stream_context_create($options);

        try {
            $result = file_get_contents($this->deeplxBaseUrl, false, $context);
            if ($result === FALSE) {
                throw new Exception("Failed to get content from DeeplX API.");
            }

            $response = json_decode($result, true);
            if (isset($response['data']) && !empty($response['data'])) {
                $translatedText = $response['data'];
            } else {
                throw new Exception("DeeplX API returned an empty translation. Response code: " . $response['code']);
            }
        } catch (Exception $e) {
            error_log("Error in DeeplX translation: " . $e->getMessage());
        }

        return $translatedText;
    }
}