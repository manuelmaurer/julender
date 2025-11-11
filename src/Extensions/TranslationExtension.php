<?php

declare(strict_types=1);

namespace App\Extensions;

use Odan\Session\SessionInterface;

/**
 * Translation extension for Twig
 */
class TranslationExtension
{
    private SessionInterface $session;
    private string $translationPath;

    /**
     * @param SessionInterface $session The session is required to determine the current language
     */
    public function __construct(SessionInterface $session, string $translationPath = __DIR__ . "/../../translations")
    {
        $this->session = $session;
        $this->translationPath = rtrim($translationPath, '/');
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
        if (!file_exists("$this->translationPath/$language.php")) {
            return $text;
        }
        $translation = include "$this->translationPath/$language.php";
        if (file_exists("$this->translationPath/$language.local.php")) {
            $localTranslation = include "$this->translationPath/$language.local.php";
            $translation = array_merge($translation, $localTranslation);
        }
        if (isset($translation[$text])) {
            return $translation[$text];
        }
        return $text;
    }
}
