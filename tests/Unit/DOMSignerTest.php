<?php

declare(strict_types=1);

namespace PhpCfdi\XmlCancelacion\Tests\Unit;

use DOMDocument;
use DOMElement;
use LogicException;
use PhpCfdi\XmlCancelacion\Credentials;
use PhpCfdi\XmlCancelacion\DOMSigner;
use PhpCfdi\XmlCancelacion\Tests\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class DOMSignerTest extends TestCase
{
    public function testThrowExceptionWhenPassingAnEmptyDomDocument(): void
    {
        /** @var Credentials&MockObject $credentials */
        $credentials = $this->createMock(Credentials::class);
        $signer = new DOMSigner(new DOMDocument());

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Document does not have a root element');
        $signer->sign($credentials);
    }

    public function testCreateKeyInfoWithIssuerNameWithAmpersand(): void
    {
        $document = new DOMDocument();
        $signer = new class($document) extends DOMSigner {
            public function expose(string $name, string $serial, string $contents, array $pubKeyData): DOMElement
            {
                return $this->createKeyInfoElement($name, $serial, $contents, $pubKeyData);
            }
        };

        $issuerName = 'John & Co';
        $serialNumber = '&0001';
        $pemContents = '&';
        $pubKeyData = [
            'type' => OPENSSL_KEYTYPE_RSA,
            'rsa' => ['n' => '1', 'e' => '2'],
        ];
        /** @var DOMElement $keyInfo */
        $keyInfo = $signer->expose($issuerName, $serialNumber, $pemContents, $pubKeyData);

        $this->assertXmlStringEqualsXmlString(
            sprintf('<X509IssuerName>%s</X509IssuerName>', htmlspecialchars($issuerName, ENT_XML1)),
            $document->saveXML($keyInfo->getElementsByTagName('X509IssuerName')[0]),
            'Ampersand was not correctly parsed on X509IssuerName'
        );
        $this->assertXmlStringEqualsXmlString(
            sprintf('<X509SerialNumber>%s</X509SerialNumber>', htmlspecialchars($serialNumber, ENT_XML1)),
            $document->saveXML($keyInfo->getElementsByTagName('X509SerialNumber')[0]),
            'Ampersand was not correctly parsed on X509SerialNumber'
        );
        $this->assertXmlStringEqualsXmlString(
            sprintf('<X509Certificate>%s</X509Certificate>', htmlspecialchars($pemContents, ENT_XML1)),
            $document->saveXML($keyInfo->getElementsByTagName('X509Certificate')[0]),
            'Ampersand was not correctly parsed on X509Certificate'
        );
    }
}
