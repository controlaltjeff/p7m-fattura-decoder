<?php

declare(strict_types=1);

namespace Controlaltjeff\P7MDecoder\Tests;

use Controlaltjeff\P7MDecoder\P7MDecoder;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class P7MDecoderTest extends TestCase
{
    private string $p7mDir;

    protected function setUp(): void
    {
        $this->p7mDir = dirname(__DIR__) . '/tests/fixtures/valid';
    }

    public function testDecodeDerFileReturnsXml(): void
    {
        $xml = P7MDecoder::decodeFile($this->p7mDir . '/test-invoice.p7m');
        $this->assertNotNull($xml);
        $this->assertStringStartsWith('<?xml', trim($xml));
        $this->assertStringContainsString('FatturaElettronica', $xml);
    }

    public function testDecodeDerStringReturnsXml(): void
    {
        $content = file_get_contents($this->p7mDir . '/test-invoice.p7m');
        $this->assertNotFalse($content);
        $xml = P7MDecoder::decodeString($content);
        $this->assertNotNull($xml);
        $this->assertStringStartsWith('<?xml', trim($xml));
    }

    public function testDecodePemStringReturnsXml(): void
    {
        $content = file_get_contents($this->p7mDir . '/test-invoice.p7m.pem');
        $this->assertNotFalse($content);
        $xml = P7MDecoder::decodeString($content);
        $this->assertNotNull($xml);
        $this->assertStringStartsWith('<?xml', trim($xml));
    }

    public function testDecodeBase64StringReturnsXml(): void
    {
        $content = file_get_contents($this->p7mDir . '/test-invoice.p7m.b64');
        $this->assertNotFalse($content);
        $xml = P7MDecoder::decodeString($content);
        $this->assertNotNull($xml);
        $this->assertStringStartsWith('<?xml', trim($xml));
    }

    public function testDecodePlainXmlReturnsAsIs(): void
    {
        $xml = P7MDecoder::decodeString('<?xml version="1.0"?><root/>');
        $this->assertNotNull($xml);
        $this->assertStringContainsString('<root/>', $xml);
    }

    public function testDecodeInvalidReturnsNull(): void
    {
        $this->assertNull(P7MDecoder::decodeString('not valid content at all'));
    }

    public function testDecodeEmptyReturnsNull(): void
    {
        $this->assertNull(P7MDecoder::decodeString(''));
        $this->assertNull(P7MDecoder::decodeString('   '));
    }

    public function testDecodeNonExistentFileReturnsNull(): void
    {
        $this->assertNull(P7MDecoder::decodeFile('/nonexistent/file.p7m'));
    }

    #[DataProvider('fixtureXmlContentProvider')]
    public function testDecodedXmlMatchesOriginal(string $p7mPath): void
    {
        $original = file_get_contents($this->p7mDir . '/minimal-valid.xml');
        $this->assertNotFalse($original);

        $decoded = P7MDecoder::decodeFile($p7mPath);
        $this->assertNotNull($decoded);

        $origNormalized = $this->normalizeXml($original);
        $decodedNormalized = $this->normalizeXml($decoded);

        $this->assertSame($origNormalized, $decodedNormalized);
    }

    /** @return array<string, array{string}> */
    public static function fixtureXmlContentProvider(): array
    {
        $dir = dirname(__DIR__) . '/tests/fixtures/valid';

        return [
            'DER format' => [$dir . '/test-invoice.p7m'],
            'PEM format' => [$dir . '/test-invoice.p7m.pem'],
        ];
    }

    public function testDecodedStringMatchesOriginal(): void
    {
        $original = file_get_contents($this->p7mDir . '/minimal-valid.xml');
        $this->assertNotFalse($original);

        $p7mContent = file_get_contents($this->p7mDir . '/test-invoice.p7m');
        $this->assertNotFalse($p7mContent);

        $decoded = P7MDecoder::decodeString($p7mContent);
        $this->assertNotNull($decoded);

        $this->assertSame(
            $this->normalizeXml($original),
            $this->normalizeXml($decoded),
        );
    }

    private function normalizeXml(string $xml): string
    {
        $dom = new \DOMDocument();
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->loadXML($xml);
        return $dom->saveXML() ?: $xml;
    }
}
