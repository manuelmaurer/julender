<?php

declare(strict_types=1);

namespace App\Extensions;

use Odan\Session\PhpSession;

/**
 * Translation extension for Twig
 */
class TranslationExtension
{
    private PhpSession $session;

    /**
     * @param PhpSession $session The session is required to determine the current language
     */
    public function __construct(PhpSession $session)
    {
        $this->session = $session;
    }

    /**
     * Translation function
     * @param string $text The text to translate
     * @return string The translated text or the original text if no translation is found
     */
    public function __invoke(string $text): string
    {
        $language = $this->session->get('language');
        if (empty($language)) {
            $language = 'en';
        }
        if (!file_exists(__DIR__ . "/../../translations/$language.php")) {
            return $text;
        }
        $translation = include __DIR__ . "/../../translations/$language.php";
        if (file_exists(__DIR__ . "/../../translations/$language.local.php")) {
            $localTranslation = include __DIR__ . "/../../translations/$language.local.php";
            $translation = array_merge($translation, $localTranslation);
        }
        if (isset($translation[$text])) {
            return $translation[$text];
        }
        return $text;
    }
}
