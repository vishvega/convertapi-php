<?php

namespace ConvertApi\Test;

use \ConvertApi\ConvertApi;

class ConvertApiTest extends \PHPUnit_Framework_TestCase
{
    protected $origApiSecret;

    protected function setUp()
    {
        // Save original values so that we can restore them after running tests
        $this->origApiSecret = ConvertApi::getApiSecret();

        ConvertApi::setApiSecret(getenv('CONVERT_API_SECRET'));
    }

    protected function tearDown()
    {
        // Restore original values
        ConvertApi::setApiSecret($this->origApiSecret);
    }

    public function testConfigurationAccessors()
    {
        ConvertApi::setApiSecret('test-secret');
        $this->assertEquals('test-secret', ConvertApi::getApiSecret());
    }

    public function testClient()
    {
        $this->assertInstanceOf('\ConvertApi\Client', ConvertApi::client());
    }

    public function testGetUser()
    {
        $user_info = ConvertApi::getUser();

        $this->assertInternalType('array', $user_info);
        $this->assertArrayHasKey('SecondsLeft', $user_info);
    }

    public function testConvertWithFileUrl()
    {
        $params = ['File' => 'https://www.w3.org/TR/PNG/iso_8859-1.txt'];

        $result = ConvertApi::convert('pdf', $params);

        $this->assertInstanceOf('\ConvertApi\Result', $result);

        $this->assertInternalType('int', $result->getConversionCost());

        $files = $result->saveFiles(sys_get_temp_dir());

        $this->assertFileExists($files[0]);

        foreach ($files as $file)
            unlink($file);
    }

    public function testConvertWithFilePath()
    {
        $params = ['File' => 'examples/files/test.docx'];

        $result = ConvertApi::convert('pdf', $params);

        $this->assertEquals('test.pdf', $result->getFile()->getFileName());
    }

    public function testConvertWithFileUpload()
    {
        $fileUpload = new \ConvertApi\FileUpload('examples/files/test.docx', 'custom.docx');
        $params = ['File' => $fileUpload];

        $result = ConvertApi::convert('pdf', $params);

        $this->assertEquals('custom.pdf', $result->getFile()->getFileName());
    }

    public function testConvertWithMultipleFiles()
    {
        $params = [
            'Files' => ['examples/files/test.pdf', 'examples/files/test.pdf']
        ];

        $result = ConvertApi::convert('zip', $params);

        $this->assertEquals('test.zip', $result->getFile()->getFileName());
    }

    public function testConvertWithUrl()
    {
        $params = ['Url' => 'https://www.convertapi.com'];

        $result = ConvertApi::convert('pdf', $params);

        $this->assertInternalType('int', $result->getFile()->getFileSize());
    }

    public function testChainedConversion()
    {
        $params = ['File' => 'examples/files/test.docx'];

        $result = ConvertApi::convert('pdf', $params);

        $params = ['Files' => $result->getFiles()];

        $result = ConvertApi::convert('zip', $params);

        $this->assertEquals('test.zip', $result->getFile()->getFileName());
    }
}