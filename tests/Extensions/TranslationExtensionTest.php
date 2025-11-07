<?php

declare(strict_types=1);

namespace App\Tests\Extensions;

use App\Extensions\TranslationExtension;
use Odan\Session\PhpSession;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(TranslationExtension::class)]
final class TranslationExtensionTest extends TestCase
{
    private string $translationPath = __DIR__ . '/../assets/translations';

    /**
     * @return array<string, array<string>>
     */
    public static function invalidLanguagesDataProvider(): array
    {
        return [
            'empty' => [''],
            'invalid' => ['invalid'],
            'en' => ['en'],
        ];
    }

    /**
     * @return array<string, array<string>>
     */
    public static function validTextDataProvider(): array
    {
        return [
            'global' => ['phpunit is great', 'PhpUnit ist super'],
            'local' => ['extended configuration', 'Erweiterte Konfiguration'],
        ];
    }

    /**
     * Helper function to generate a translator for a given language
     * @param string $language
     * @return TranslationExtension
     */
    private function getTranslator(string $language): TranslationExtension
    {
        $session = new PhpSession();
        $session->set('language', $language);

        return new TranslationExtension($session, $this->translationPath);
    }

    /**
     * Test translation returns original text if language is invalid or default
     * @param string $language
     * @return void
     */
    #[DataProvider('invalidLanguagesDataProvider')]
    public function testTranslateToDefaultLang(string $language): void
    {
        $dut = $this->getTranslator($language);
        $result = $dut('String that does not exist in translation file');
        $this->assertEquals('String that does not exist in translation file', $result);
    }

    /**
     * Test translation returns translated text for a given language both in global and in local file
     * @param string $input
     * @param string $expectedOutput
     * @return void
     */
    #[DataProvider('validTextDataProvider')]
    public function testTranslateToLanguage(string $input, string $expectedOutput): void
    {
        $dut = $this->getTranslator('de');
        $result = $dut($input);
        $this->assertEquals($expectedOutput, $result);
    }

    /**
     * Test translation returns the original text if not found in any file
     * @return void
     */
    public function testTranslateToLanguageWithFallback(): void
    {
        $dut = $this->getTranslator('de');
        $result = $dut('String that does not exist in translation file');
        $this->assertEquals('String that does not exist in translation file', $result);
    }
}
