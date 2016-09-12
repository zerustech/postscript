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
 * Test case for file ascii hexadecimal to binary input stream.
 *
 * @author Michael Lee <michael.lee@zerustech.com>
 */
class PfbToPfaInputStreamTest extends \PHPUnit_Framework_TestCase
{
    public function setup()
    {
        $this->ref = new \ReflectionClass('ZerusTech\Component\Postscript\Font\TypeOne\Stream\Input\PfbToPfaInputStream');

        $this->buffer = $this->ref->getProperty('buffer');
        $this->buffer->setAccessible(true);

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
        $this->buffer = null;
        $this->ref = null;
    }

    /**
     * @dataProvider getDataForTestConstructor
     */
    public function testConstructor($convertToHex, $width)
    {
        $in = new FileInputStream($this->base.'NimbusRomanNo9L-Regular.pfb', 'rb');

        $stream = new PfbToPfaInputStream($in, $convertToHex, $width);

        $this->assertEquals('', $this->buffer->getValue($stream));
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
     * @dataProvider getDataForTestInput
     */
    public function testInput($convertToHex, $width, $pfb, $offset, $length, $expected, $count, $skipped, $available)
    {
        $stream = new PfbToPfaInputStream(new StringInputStream($pfb), $convertToHex, $width);

        $this->assertEquals($skipped, $stream->skip($offset));

        $this->assertEquals($count, $this->input->invokeArgs($stream, [&$bytes, $length]));

        $this->assertEquals($available, $stream->available());

        $this->assertEquals($expected, $bytes);
    }

    public function getDataForTestInput()
    {
        return [
            [false, 3, "\x80\x01\x05\x00\x00\x00\x68\x65\x6c\x6c\x6f", 0, 5, "hello", 5, 0, 0],
            [false, 3, "\x80\x01\x05\x00\x00\x00\x68\x65\x6c\x6c\x6f", 0, 3, "hel", 3, 0, 1],
            [false, 3, "\x80\x01\x05\x00\x00\x00\x68\x65\x6c\x6c\x6f", 1, 2, "el", 2, 1, 1],
            [false, 3, "\x80\x01\x05\x00\x00\x00\x68\x65\x6c\x6c\x6f", 5, 1, "", -1, 5, 0],
            [true,  3, "\x80\x02\x05\x00\x00\x00\x68\x65\x6c\x6c\x6f", 0, 5, "68656C\n6C6F", 5, 0, 0],
            [true, 3, "\x80\x02\x0A\x00\x00\x00\x68\x65\x6c\x6c\x6f\x68\x65\x6c\x6c\x6f", 0, 10, "68656C\n6C6F68\n656C6C\n6F", 10, 0, 0],
            [true, 3, "\x80\x02\x0A\x00\x00\x00\x68\x65\x6c\x6c\x6f\x68\x65\x6c\x6c\x6f", 1, 5, "656C\n6C6F68\n", 5, 1, 1],
            [true, 3, "\x80\x02\x05\x00\x00\x00\x68\x65\x6c\x6c\x6f", 1, 4, "656C\n6C6F", 4, 1, 0],
            [true, 3, "\x80\x02\x05\x00\x00\x00\x68\x65\x6c\x6c\x6f", 1, 1, "65", 1, 1, 1],
            [true, 3, "\x80\x02\x05\x00\x00\x00\x68\x65\x6c\x6c\x6f", 2, 2, "6C\n6C", 2, 2, 1],
        ];
    }

    /**
     * @dataProvider getDataForTestInputWithFile
     */
    public function testInputWithFile($pfbFile, $expectedFile, $convertToHex, $length)
    {
        $pfbFile = $this->base.$pfbFile;

        $expectedFile = $this->base.$expectedFile;

        $stream = new PfbToPfaInputStream(new FileInputStream($pfbFile, 'rb'), $convertToHex);

        $output = new StringOutputStream();

        while (-1 !== $this->input->invokeArgs($stream, [&$bytes, $length])) {

            $output->write($bytes);
        }

        $this->assertEquals(file_get_contents($expectedFile), $output->__toString());
    }

    public function getDataForTestInputWithFile()
    {
        return [
            ['NimbusRomanNo9L-Regular.pfb','NimbusRomanNo9L-Regular-pfb-to-hex.pfa', true, 1],
            ['NimbusRomanNo9L-Regular.pfb','NimbusRomanNo9L-Regular-pfb-to-hex.pfa', true, 2],
            ['NimbusRomanNo9L-Regular.pfb','NimbusRomanNo9L-Regular-pfb-to-hex.pfa', true, 6],
            ['NimbusRomanNo9L-Regular.pfb','NimbusRomanNo9L-Regular-pfb-to-hex.pfa', true, 32],
            ['NimbusRomanNo9L-Regular.pfb','NimbusRomanNo9L-Regular-pfb-to-bin.pfa', false, 1],
            ['NimbusRomanNo9L-Regular.pfb','NimbusRomanNo9L-Regular-pfb-to-bin.pfa', false, 2],
            ['NimbusRomanNo9L-Regular.pfb','NimbusRomanNo9L-Regular-pfb-to-bin.pfa', false, 6],
            ['NimbusRomanNo9L-Regular.pfb','NimbusRomanNo9L-Regular-pfb-to-bin.pfa', false, 32],
        ];
    }
}
