<?php
require_once(__DIR__ . '/lib/TranslateController.php');
require_once(__DIR__ . '/lib/TranslationService.php');

class TranslateExtension extends Minz_Extension {
    private const ApiUrl = 'http://localhost:1188/translate';

    public function init() {
        error_log('Translate: Plugin initializing...');
        
        if (!extension_loaded('mbstring')) {
            error_log('Translate plugin requires PHP mbstring extension');
        }
        
        if (php_sapi_name() == 'cli') {
            if (!FreshRSS_Context::$user_conf) {
                error_log('Translate: No user context in CLI mode');
                $username = 'default';
                FreshRSS_Context::$user_conf = new FreshRSS_UserConfiguration($username);
                FreshRSS_Context::$user_conf->load();
            }
        }
        
        $this->registerHook('feed_before_insert', array($this, 'addTranslationOption'));
        $this->registerHook('entry_before_insert', array($this, 'translateEntry'));

        if (is_null(FreshRSS_Context::$user_conf->TranslateService)) {
            FreshRSS_Context::$user_conf->TranslateService = 'google';
        }
        
        if (is_null(FreshRSS_Context::$user_conf->TargetLanguage)) {
            FreshRSS_Context::$user_conf->TargetLanguage = 'en';
        }

        if (is_null(FreshRSS_Context::$user_conf->DeeplxApiUrl)) {
            FreshRSS_Context::$user_conf->DeeplxApiUrl = self::ApiUrl;
        }

        if (is_null(FreshRSS_Context::$user_conf->LibreApiUrl)) {
            FreshRSS_Context::$user_conf->LibreApiUrl = 'http://localhost:5000';
        }

        if (is_null(FreshRSS_Context::$user_conf->LibreApiKey)) {
            FreshRSS_Context::$user_conf->LibreApiKey = '';
        }

        FreshRSS_Context::$user_conf->save();

        error_log('Translate: Hooks registered');
    }

    public function handleConfigureAction() {
        if (Minz_Request::isPost()) {
            $translateService = Minz_Request::param('TranslateService', 'google');
            FreshRSS_Context::$user_conf->TranslateService = $translateService;
            
            $targetLanguage = Minz_Request::param('TargetLanguageCode', 'en');
            FreshRSS_Context::$user_conf->TargetLanguage = $targetLanguage;
            
            $translateTitles = Minz_Request::param('TranslateTitles', array());
            error_log("Translate: Saving translation config: " . json_encode($translateTitles));
            
            if (!is_array($translateTitles)) {
                $translateTitles = array();
            }
            
            FreshRSS_Context::$user_conf->TranslateTitles = $translateTitles;
            
            $deeplxApiUrl = Minz_Request::param('DeeplxApiUrl', self::ApiUrl);
            FreshRSS_Context::$user_conf->DeeplxApiUrl = $deeplxApiUrl;

            $libreApiUrl = Minz_Request::param('LibreApiUrl', 'http://localhost:5000');
            FreshRSS_Context::$user_conf->LibreApiUrl = $libreApiUrl;

            $libreApiKey = Minz_Request::param('LibreApiKey', '');
            FreshRSS_Context::$user_conf->LibreApiKey = $libreApiKey;

            $saveResult = FreshRSS_Context::$user_conf->save();
            error_log("Translate: Config save result: " . ($saveResult ? 'success' : 'failed'));
            
            error_log("Translate: Saved config verification: " . 
                json_encode(FreshRSS_Context::$user_conf->TranslateTitles));
        }
    }

    public function handleUninstallAction() {
        if (isset(FreshRSS_Context::$user_conf->TranslateService)) {
            unset(FreshRSS_Context::$user_conf->TranslateService);
        }
        if (isset(FreshRSS_Context::$user_conf->TargetLanguage)) {
            unset(FreshRSS_Context::$user_conf->TargetLanguage);
        }
        if (isset(FreshRSS_Context::$user_conf->TranslateTitles)) {
            unset(FreshRSS_Context::$user_conf->TranslateTitles);
        }
        if (isset(FreshRSS_Context::$user_conf->DeeplxApiUrl)) {
            unset(FreshRSS_Context::$user_conf->DeeplxApiUrl);
        }
        if (isset(FreshRSS_Context::$user_conf->LibreApiUrl)) {
            unset(FreshRSS_Context::$user_conf->LibreApiUrl);
        }
        if (isset(FreshRSS_Context::$user_conf->LibreApiKey)) {
            unset(FreshRSS_Context::$user_conf->LibreApiKey);
        }
        FreshRSS_Context::$user_conf->save();
    }

    public function translateEntry($entry) {
        if (php_sapi_name() == 'cli') {
            if (!FreshRSS_Context::$user_conf) {
                $usernames = $this->listUsers();
                foreach ($usernames as $username) {
                    FreshRSS_Context::$user_conf = new FreshRSS_UserConfiguration($username);
                    FreshRSS_Context::$user_conf->load();
                    break;
                }
            }
        }
        
        error_log("Translate: processing entry");
        $feedId = $entry->feed()->id();
        $targetLang = FreshRSS_Context::$user_conf->TargetLanguage ?? 'en';
        
        if (isset(FreshRSS_Context::$user_conf->TranslateTitles[$feedId]) && 
            FreshRSS_Context::$user_conf->TranslateTitles[$feedId] == '1') {
            $title = $entry->title();
            error_log("Original title: " . $title);
            
            $translateController = new TranslateExtensionController();
            $translatedTitle = $translateController->translate($title, $targetLang);
            
            error_log("Translated title: " . ($translatedTitle ?: 'translation failed'));
            
            if (!empty($translatedTitle)) {
                $entry->_title($translatedTitle);
            }
        }
        
        // Also translate content for enabled feeds
        if (isset(FreshRSS_Context::$user_conf->TranslateTitles[$feedId]) && 
            FreshRSS_Context::$user_conf->TranslateTitles[$feedId] == '1') {
            $content = $entry->content();
            if (!empty($content)) {
                error_log("Translating content...");
                $translateController = new TranslateExtensionController();
                $translatedContent = $translateController->translate($content, $targetLang);
                
                if (!empty($translatedContent)) {
                    $entry->_content($translatedContent);
                }
            }
        }
        
        return $entry;
    }

    private function listUsers() {
        $path = DATA_PATH . '/users';
        $users = array();
        if ($handle = opendir($path)) {
            while (false !== ($entry = readdir($handle))) {
                if ($entry != "." && $entry != ".." && is_dir($path . '/' . $entry)) {
                    $users[] = $entry;
                }
            }
            closedir($handle);
        }
        return $users;
    }

    public function addTranslationOption($feed) {
        $feed->TranslateTitles = '0';
        return $feed;
    }
}