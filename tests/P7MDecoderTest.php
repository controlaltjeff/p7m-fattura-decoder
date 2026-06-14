<?php

declare(strict_types=1);

namespace Controlaltjeff\P7MDecoder\Tests;

use Controlaltjeff\P7MDecoder\P7MDecoder;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class P7MDecoderTest extends TestCase
{
    private string $p7mDir;
    private string $invalidDir;

    protected function setUp(): void
    {
        $this->p7mDir = dirname(__DIR__) . '/tests/fixtures/valid';
        $this->invalidDir = dirname(__DIR__) . '/tests/fixtures/invalid';
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

    // --- Negative / edge-case tests ---

    public function testDecodeTruncatedP7mReturnsNull(): void
    {
        $content = file_get_contents($this->invalidDir . '/truncated.p7m');
        $this->assertNotFalse($content);
        $this->assertNull(P7MDecoder::decodeString($content));
    }

    public function testDecodeGarbageBinaryReturnsNull(): void
    {
        $content = file_get_contents($this->invalidDir . '/garbage-binary.bin');
        $this->assertNotFalse($content);
        $this->assertNull(P7MDecoder::decodeString($content));
    }

    public function testDecodeInvalidBase64ReturnsNull(): void
    {
        $content = file_get_contents($this->invalidDir . '/not-base64.txt');
        $this->assertNotFalse($content);
        $this->assertNull(P7MDecoder::decodeString($content));
    }

    public function testDecodeWhitespaceOnlyReturnsNull(): void
    {
        $this->assertNull(P7MDecoder::decodeString("\n\t\r  "));
    }

    public function testDecodeBinaryNullBytesReturnsNull(): void
    {
        $this->assertNull(P7MDecoder::decodeString("\x00\x00\x00"));
    }

    public function testDecodePlainXmlWithoutDeclarationReturnsXml(): void
    {
        $xml = P7MDecoder::decodeString('<FatturaElettronica><Test/></FatturaElettronica>');
        $this->assertNotNull($xml);
        $this->assertStringContainsString('FatturaElettronica', $xml);
    }

    public function testDecodedDerFileContainsExpectedElements(): void
    {
        $xml = P7MDecoder::decodeFile($this->p7mDir . '/test-invoice.p7m');
        $this->assertNotNull($xml);
        $this->assertStringContainsString('FatturaElettronicaHeader', $xml);
        $this->assertStringContainsString('FatturaElettronicaBody', $xml);
        $this->assertStringContainsString('DatiBeniServizi', $xml);
    }

    public function testDecodedStringContainsDatiGenerali(): void
    {
        $content = file_get_contents($this->p7mDir . '/test-invoice.p7m');
        $this->assertNotFalse($content);
        $xml = P7MDecoder::decodeString($content);
        $this->assertNotNull($xml);
        $this->assertStringContainsString('DatiGenerali', $xml);
        $this->assertStringContainsString('DatiTrasmissione', $xml);
    }

    public function testDecodedPemFileContainsCedentePrestatore(): void
    {
        $xml = P7MDecoder::decodeFile($this->p7mDir . '/test-invoice.p7m.pem');
        $this->assertNotNull($xml);
        $this->assertStringContainsString('CedentePrestatore', $xml);
        $this->assertStringContainsString('CessionarioCommittente', $xml);
    }

    public function testDecodedBase64FileIsConsistentWithDer(): void
    {
        $der = P7MDecoder::decodeFile($this->p7mDir . '/test-invoice.p7m');
        $b64 = file_get_contents($this->p7mDir . '/test-invoice.p7m.b64');
        $this->assertNotFalse($b64);
        $fromB64 = P7MDecoder::decodeString($b64);

        $this->assertNotNull($der);
        $this->assertNotNull($fromB64);
        $this->assertSame($this->normalizeXml($der), $this->normalizeXml($fromB64));
    }

    // --- getLastCorrections state tests ---

    public function testGetLastCorrectionsIsEmptyByDefault(): void
    {
        P7MDecoder::decodeString('<?xml version="1.0"?><root/>');
        $corrections = P7MDecoder::getLastCorrections();
        $this->assertIsArray($corrections);
        $this->assertEmpty($corrections);
    }

    public function testGetLastCorrectionsResetsBetweenCalls(): void
    {
        P7MDecoder::decodeString('some invalid content');
        $first = P7MDecoder::getLastCorrections();

        P7MDecoder::decodeString('<?xml version="1.0"?><root/>');
        $second = P7MDecoder::getLastCorrections();

        $this->assertSame($first, $second);
        $this->assertEmpty($second);
    }

    public function testDecodeFileUnreadableReturnsNull(): void
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'p7m_test_');
        $this->assertNotFalse($tmpFile);
        chmod($tmpFile, 0000);
        $this->assertNull(P7MDecoder::decodeFile($tmpFile));
        @unlink($tmpFile);
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
