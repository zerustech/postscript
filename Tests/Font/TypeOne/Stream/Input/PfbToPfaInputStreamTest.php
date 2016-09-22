<?php

/**
 * This file is part of the ZerusTech package.
 *
 * (c) Michael Lee <michael.lee@zerustech.com>
 *
 * For full copyright and license information, please view the LICENSE file that
 * was distributed with this source code.
 */

namespace ZerusTech\Component\Postscript\Tests\Font\TypeOne\Stream\Input;

use ZerusTech\Component\Postscript\Font\TypeOne\Stream\Input\PfbToPfaInputStream;
use ZerusTech\Component\Postscript\Font\TypeOne\Stream\Input\AsciiHexadecimalToBinaryInputStream;
use ZerusTech\Component\IO\Stream\Input\StringInputStream;
use ZerusTech\Component\IO\Stream\Input\FileInputStream;
use ZerusTech\Component\IO\Stream\Output\StringOutputStream;
use ZerusTech\Component\IO\Exception;

/**
 * Test case for pfb to pfa input stream.
 *
 * @author Michael Lee <michael.lee@zerustech.com>
 */
class PfbToPfaInputStreamTest extends \PHPUnit_Framework_TestCase
{
    public function setup()
    {
        $this->ref = new \ReflectionClass('ZerusTech\Component\Postscript\Font\TypeOne\Stream\Input\PfbToPfaInputStream');

        $this->header = $this->ref->getProperty('header');
        $this->header->setAccessible(true);

        $this->ready = $this->ref->getProperty('ready');
        $this->ready->setAccessible(true);

        $this->offset = $this->ref->getProperty('offset');
        $this->offset->setAccessible(true);

        $this->convertToHex = $this->ref->getProperty('convertToHex');
        $this->convertToHex->setAccessible(true);

        $this->width = $this->ref->getProperty('width');
        $this->width->setAccessible(true);

        $this->column = $this->ref->getProperty('column');
        $this->column->setAccessible(true);

        $this->input = $this->ref->getMethod('input');
        $this->input->setAccessible(true);

        $this->base = __DIR__.'/../../../../Fixtures/Font/TypeOne/';
    }

    public function tearDown()
    {
        $this->base = null;

        $this->input = null;
        $this->column = null;
        $this->width = null;
        $this->convertToHex = null;
        $this->offset = null;
        $this->ready = null;
        $this->header = null;
        $this->ref = null;
    }

    /**
     * @dataProvider getDataForTestConstructor
     */
    public function testConstructor($convertToHex, $width)
    {
        $in = new FileInputStream($this->base.'NimbusRomanNo9L-Regular.pfb', 'rb');

        $stream = new PfbToPfaInputStream($in, $convertToHex, $width);

        $this->assertNull($this->header->getValue($stream));
        $this->assertFalse($this->ready->getValue($stream));
        $this->assertEquals(0, $this->offset->getValue($stream));
        $this->assertEquals($convertToHex, $this->convertToHex->getValue($stream));
        $this->assertEquals($width, $this->width->getValue($stream));
    }

    public function getDataForTestConstructor()
    {
        return [
            [true, 4],
            [false, 8],
        ];
    }

    /**
     * @dataProvider getDataForTestReadBlock
     */
    public function testReadBlock($convertToHex, $width, $pfb, $expected)
    {
        $stream = new PfbToPfaInputStream(new StringInputStream($pfb), $convertToHex, $width);

        $i = 0;

        for ($i = 0; $i < count($expected); $i++) {

            $this->assertEquals($expected[$i], $stream->readBlock());
        }
    }

    public function getDataForTestReadBlock()
    {
        return [
            [false, 3, "\x80\x01\x05\x00\x00\x00\x68\x65\x6c\x6c\x6f", ["hello"]],
            [true, 3, "\x80\x01\x05\x00\x00\x00\x68\x65\x6c\x6c\x6f", ["hello"]],
            [false,  3, "\x80\x02\x05\x00\x00\x00\x68\x65\x6c\x6c\x6f", ["hello"]],
            [true,  3, "\x80\x02\x05\x00\x00\x00\x68\x65\x6c\x6c\x6f", ["68656C\n6C6F"]],
            [false,  3, "\x80\x03", [null]],
            [true,  3, "\x80\x03", [null]],
            [false, 3, "\x80\x01\x05\x00\x00\x00\x68\x65\x6c\x6c\x6f\x80\x02\x05\x00\x00\x00\x77\x6f\x72\x6c\x64", ["hello", "world"]],
            [true, 3, "\x80\x01\x05\x00\x00\x00\x68\x65\x6c\x6c\x6f\x80\x02\x05\x00\x00\x00\x77\x6f\x72\x6c\x64", ["hello", "776F72\n6C64"]],
        ];
    }

    /**
     * @dataProvider getDataForTestReadBlockWithFile
     */
    public function testReadBlockWithFile($pfbFile, $expectedFile, $convertToHex)
    {
        $pfbFile = $this->base.$pfbFile;

        $expectedFile = $this->base.$expectedFile;

        $stream = new PfbToPfaInputStream(new FileInputStream($pfbFile, 'rb'), $convertToHex);

        $output = new StringOutputStream();

        while (null !== $block = $stream->readBlock()) {

            $output->write($block);
        }

        $this->assertEquals(substr(file_get_contents($expectedFile), 0, -532), $output->__toString());
    }

    public function getDataForTestReadBlockWithFile()
    {
        return [
            ['NimbusRomanNo9L-Regular.pfb','NimbusRomanNo9L-Regular-pfb-to-hex.pfa', true],
            ['NimbusRomanNo9L-Regular.pfb','NimbusRomanNo9L-Regular-pfb-to-bin.pfa', false],
        ];
    }
}
