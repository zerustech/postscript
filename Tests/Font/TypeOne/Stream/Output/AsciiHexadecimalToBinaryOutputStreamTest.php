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
use ZerusTech\Component\Postscript\Font\TypeOne\Stream\Output\AsciiHexadecimalToBinaryOutputStream;

/**
 * Test case for ascii hexadecimal to binary output stream.
 *
 * @author Michael Lee <michael.lee@zerustech.com>
 */
class AsciiHexadecimalToBinaryOutputStreamTest extends \PHPUnit_Framework_TestCase
{
    public function setup()
    {
        $this->ref = new \ReflectionClass('ZerusTech\Component\Postscript\Font\TypeOne\Stream\Output\AsciiHexadecimalToBinaryOutputStream');

        $this->buffer = $this->ref->getProperty('buffer');
        $this->buffer->setAccessible(true);

        $this->out = $this->ref->getProperty('out');
        $this->out->setAccessible(true);

        $this->output = $this->ref->getMethod('output');
        $this->output->setAccessible(true);

        $this->base = __DIR__.'/../../../../Fixtures/Font/TypeOne/';
    }

    public function tearDown()
    {
        $this->output = null;
        $this->out = null;
        $this->buffer = null;
        $this->ref = null;

        $this->base = null;
    }

    public function testConstructor()
    {
        $out = new StringOutputStream();
        $stream = new AsciiHexadecimalToBinaryOutputStream($out);

        $this->assertEquals([], $this->buffer->getValue($stream));
        $this->assertSame($out, $this->out->getValue($stream));
    }

    /**
     * @dataProvider getDataForTestOutput
     */
    public function testOutput($hex, $expected, $count)
    {
        $out = new StringOutputStream();
        $stream = new AsciiHexadecimalToBinaryOutputStream($out);
        $this->assertEquals($count, $this->output->invoke($stream, $hex));
        $this->assertEquals($expected, $out->__toString());
    }

    public function getDataForTestOutput()
    {
        return [
            ['68656C6C6F', 'hello', 5],
            ['', '', 0],
            ['6', '', 0],
            ['68', 'h', 1],
            ['686', 'h', 1],
        ];
    }


    /**
     * @dataProvider getDataForTestOutputWithException
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessageRegex /^.* is not a valid hexadecimal string.$/
     */
    public function testOutputWithException($hex)
    {
        $out = new StringOutputStream();
        $stream = new AsciiHexadecimalToBinaryOutputStream($out);
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
    public function testOutputWithFile($hexFile, $expectedFile, $length)
    {
        $expectedFile = $this->base.$expectedFile;

        $hexFile = $this->base.$hexFile;

        $hexInput = new FileInputStream($hexFile, 'rb');

        $out = new StringOutputStream();

        $stream = new AsciiHexadecimalToBinaryOutputStream($out);

        while (-1 !== $hexInput->read($bytes, $length)) {

            $this->output->invoke($stream, trim($bytes));
        }

        $this->assertEquals(file_get_contents($expectedFile), $out->__toString());
    }

    public function getDataForTestOutputWithFile()
    {
        return [
            ['eexec-block-encrypted-as-hex-without-format-001.txt', 'eexec-block-encrypted-as-bin-001.txt', 1],
            ['eexec-block-encrypted-as-hex-without-format-001.txt', 'eexec-block-encrypted-as-bin-001.txt', 32],
        ];
    }
}
