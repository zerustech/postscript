<?php

/**
 * This file is part of the ZerusTech package.
 *
 * (c) Michael Lee <michael.lee@zerustech.com>
 *
 * For full copyright and license information, please view the LICENSE file that
 * was distributed with this source code.
 */

namespace ZerusTech\Component\Postscript\Tests\Font\TypeOne\Stream\Output;

use ZerusTech\Component\IO\Exception;
use ZerusTech\Component\IO\Stream\Output\StringOutputStream;
use ZerusTech\Component\IO\Stream\Input\FileInputStream;
use ZerusTech\Component\IO\Stream\Input\WashInputStream;
use ZerusTech\Component\IO\Stream\Output\FileOutputStream;
use ZerusTech\Component\Postscript\Font\TypeOne\Stream\Output\AsciiHexadecimalFormatOutputStream;

/**
 * Test case for ascii hexadecimal format output stream.
 *
 * @author Michael Lee <michael.lee@zerustech.com>
 */
class AsciiHexadecimalFormatOutputStreamTest extends \PHPUnit_Framework_TestCase
{
    public function setup()
    {
        $this->ref = new \ReflectionClass('ZerusTech\Component\Postscript\Font\TypeOne\Stream\Output\AsciiHexadecimalFormatOutputStream');

        $this->output = $this->ref->getMethod('output');
        $this->output->setAccessible(true);

        $this->out = $this->ref->getProperty('out');
        $this->out->setAccessible(true);

        $this->buffer = $this->ref->getProperty('buffer');
        $this->buffer->setAccessible(true);

        $this->column = $this->ref->getProperty('column');
        $this->column->setAccessible(true);

        $this->width = $this->ref->getProperty('width');
        $this->width->setAccessible(true);

        $this->base = __DIR__.'/../../../../Fixtures/Font/TypeOne/';
    }

    public function tearDown()
    {
        $this->width = null;
        $this->column = null;
        $this->buffer = null;
        $this->out = null;
        $this->output = null;
        $this->ref = null;

        $this->base = null;
    }

    /**
     * @dataProvider getDataForTestConstructor
     */
    public function testConstructor($column, $width)
    {
        $out = new StringOutputStream();
        $stream = new AsciiHexadecimalFormatOutputStream($out, $column, $width);

        $this->assertSame($out, $this->out->getValue($stream));
        $this->assertEquals($column, $this->column->getValue($stream));
        $this->assertEquals($width, $this->width->getValue($stream));
        $this->assertEquals('', $this->buffer->getValue($stream));
    }

    public function getDataForTestConstructor()
    {
        return [
            [0, 32],
            [1, 16],
        ];
    }

    /**
     * @dataProvider getDataForTestOutput
     */
    public function testOutput($hex, $expected, $count, $column, $width)
    {
        $out = new StringOutputStream();
        $stream = new AsciiHexadecimalFormatOutputStream($out, $column, $width);
        $this->assertEquals($count, $this->output->invoke($stream, $hex));
        $this->assertEquals($expected, $out->__toString());
    }

    public function getDataForTestOutput()
    {
        return [
            ["68656C6C6F", "68656C6C6F\n", 11, 0, 5],
            ["68656C6C6F68656C", "68656C6C6F\n68656C", 17, 0, 5],
            ["68656C6C6F68656C6C6F68656C", "68656C6C6F\n68656C6C6F\n68656C", 28, 0, 5],
            ["68656C6C6F68656C6C6F68656C", "68656C6C\n6F68656C6C\n6F68656C", 28, 1, 5],
            ["68656C6C6F68656C6C6F68656C", "68656C\n6C6F68656C\n6C6F68656C\n", 29, 2, 5],
            ["68656C6C6F68656C6C6F68656C", "6865\n6C6C6F6865\n6C6C6F6865\n6C", 29, 3, 5],
            ["68656C6C6F68656C6C6F68656C", "68\n656C6C6F68\n656C6C6F68\n656C", 29, 4, 5],
            ["68656C6C6F68656C6C6F68656C", "68656C6C6F\n68656C6C6F\n68656C", 28, 5, 5],
        ];
    }


    /**
     * @dataProvider getDataForTestOutputWithException
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessageRegex /^.* is not a valid hexadecimal string$/
     */
    public function testOutputWithException($hex)
    {
        $out = new StringOutputStream();
        $stream = new AsciiHexadecimalFormatOutputStream($out);
        $this->output->invoke($stream, $hex);
    }

    public function getDataForTestOutputWithException()
    {
        return [
            ["68656C6C6F\n"],
            ["68656C6C6F\t"],
            ["68656C6C6F\r"],
            ["68656C6C6F "],
            ["68656C\n6C6F"],
            ["68656C\r6C6F"],
            ["68656C\t6C6F"],
            ["68656C 6C6F"],
        ];
    }


    /**
     * @dataProvider getDataForTestOutputWithFile
     */
    public function testOutputWithFile($hexFile, $expectedFile, $length, $skip, $width)
    {
        $hexFile = $this->base.$hexFile;

        $expectedFile = $this->base.$expectedFile;

        $hexInput = new WashInputStream(new FileInputStream($hexFile, 'rb'));

        $out = new StringOutputStream();

        $stream = new AsciiHexadecimalFormatOutputStream($out, 0, $width);

        $column = 0;

        if ($skip > 0) {

            $hexInput->read($bytes, $skip);

            $this->output->invoke($stream, $bytes);

            $column = (strlen($bytes) / 2) % $width;
        }

        $stream = new AsciiHexadecimalFormatOutputStream($out, $column, $width);

        while (-1 !== $hexInput->read($bytes, $length)) {

            $this->output->invoke($stream, $bytes);
        }

        $this->assertEquals(file_get_contents($expectedFile), $out->__toString());
    }

    public function getDataForTestOutputWithFile()
    {
        return [
            ['eexec-block-encrypted-as-hex-without-format-001.txt', 'eexec-block-encrypted-as-hex-001.txt', 1, 0, 32],
            ['eexec-block-encrypted-as-hex-without-format-001.txt', 'eexec-block-encrypted-as-hex-001.txt', 1, 16, 32],
            ['eexec-block-encrypted-as-hex-without-format-001.txt', 'eexec-block-encrypted-as-hex-001.txt', 32, 0, 32],
            ['eexec-block-encrypted-as-hex-without-format-001.txt', 'eexec-block-encrypted-as-hex-001.txt', 32, 16, 32],
        ];
    }
}
