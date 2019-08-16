<?php

namespace SimpleSAML\Test\XML;

require_once(__DIR__.'/../../../SigningTestCase.php');

use PHPUnit\Framework\TestCase;
use \SimpleSAML\Test\SigningTestCase;
use \SimpleSAML\XML\Signer;
use \SimpleSAML\XML\Validator;

use \org\bovigo\vfs\vfsStream;

/**
 * Tests for SimpleSAML\XML\Validator.
 */
class ValidatorTest extends SigningTestCase
{
    /**
     * @return void
     */
    public function testValidatorMissingSignature()
    {
        $doc = new \DOMDocument();
        $doc->loadXML('<?xml version="1.0"?><node>value</node>');

        $this->expectException(\Exception::class);
        new Validator($doc);
    }


    /**
     * @return void
     */
    public function testGetX509Certificate()
    {
        $doc = new \DOMDocument();
        $doc->loadXML('<?xml version="1.0"?><node>value</node>');

        $node = $doc->getElementsByTagName('node')->item(0);

        $signature_parent = $doc->appendChild(new \DOMElement('signature_parent'));

        $signer = new Signer([]);
        $signer->loadPrivateKey($this->good_private_key_file, null, true);
        $signer->loadCertificate($this->good_certificate_file, true);
        $signer->sign($node, $signature_parent);

        $validator = new Validator($doc, 'node');

        $result = $validator->getX509Certificate();

        // getX509Certificate returns a certificate with a newline
        $expected = $this->good_certificate."\n";

        $this->assertEquals($result, $expected);
    }


    /**
     * @return void
     */
    public function testIsNodeValidatedSuccess()
    {
        $doc = new \DOMDocument();
        $doc->loadXML('<?xml version="1.0"?><node>value</node>');

        $node = $doc->getElementsByTagName('node')->item(0);

        $signature_parent = $doc->appendChild(new \DOMElement('signature_parent'));

        $signer = new Signer([]);
        $signer->loadPrivateKey($this->good_private_key_file, null, true);
        $signer->sign($node, $signature_parent);

        $validator = new Validator(
            $doc,
            'node',
            ['PEM' => $this->good_certificate]
        );

        $result = $validator->isNodeValidated($node);

        $this->assertTrue($result);
    }


    /**
     * @return void
     */
    public function testIsNodeValidatedFailure()
    {
        $doc = new \DOMDocument();
        $doc->loadXML('<?xml version="1.0"?><parent><node1>value1</node1><node2>value2</node2></parent>');

        $node1 = $doc->getElementsByTagName('node1')->item(0);
        $node2 = $doc->getElementsByTagName('node2')->item(0);

        $signature_parent = $doc->appendChild(new \DOMElement('signature_parent'));

        $signer = new Signer([]);
        $signer->loadPrivateKey($this->good_private_key_file, null, true);
        $signer->sign($node1, $signature_parent);

        $validator = new Validator(
            $doc,
            'node1',
            ['PEM' => $this->good_certificate]
        );

        $result = $validator->isNodeValidated($node2);

        $this->assertFalse($result);
    }


    /**
     * @return void
     */
    public function testValidateCertificateMissingCAFile()
    {
        $ca_file = $this->ca_certificate_file.'NOT';

        $this->expectException(\Exception::class);
        Validator::validateCertificate($this->good_certificate, $ca_file);
    }
}
