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
use ZerusTech\Component\IO\Stream\Output\FileOutputStream;
use ZerusTech\Component\Postscript\Font\TypeOne\Stream\Output\AsciiHexadecimalWashOutputStream;

/**
 * Test case for ascii hexadecimal wash output stream.
 *
 * @author Michael Lee <michael.lee@zerustech.com>
 */
class AsciiHexadecimalWashOutputStreamTest extends \PHPUnit_Framework_TestCase
{
    public function setup()
    {
        $this->ref = new \ReflectionClass('ZerusTech\Component\Postscript\Font\TypeOne\Stream\Output\AsciiHexadecimalWashOutputStream');

        $this->output = $this->ref->getMethod('output');
        $this->output->setAccessible(true);

        $this->out = $this->ref->getProperty('out');
        $this->out->setAccessible(true);

        $this->base = __DIR__.'/../../../../Fixtures/Font/TypeOne/';
    }

    public function tearDown()
    {
        $this->out = null;
        $this->output = null;
        $this->ref = null;

        $this->base = null;
    }

    public function testConstructor()
    {
        $out = new StringOutputStream();
        $stream = new AsciiHexadecimalWashOutputStream($out);
        $this->assertSame($out, $this->out->getValue($stream));
    }

    /**
     * @dataProvider getDataForTestOutput
     */
    public function testOutput($hex, $expected, $count)
    {
        $out = new StringOutputStream();
        $stream = new AsciiHexadecimalWashOutputStream($out);
        $this->assertEquals($count, $this->output->invoke($stream, $hex));
        $this->assertEquals($expected, $out->__toString());
    }

    public function getDataForTestOutput()
    {
        return [
            ["6\n8\t6\r5 6C6C6F\n","68656C6C6F", 10],
            ["68656C6C6F\n68656C", "68656C6C6F68656C", 16],
            ["68656C6C6F\n68656C6C6F\n68656C", "68656C6C6F68656C6C6F68656C", 26],
            ["68656C6C\n6F68656C6C\n6F68656C", "68656C6C6F68656C6C6F68656C", 26],
            ["68656C\n6C6F68656C\n6C6F68656C\n", "68656C6C6F68656C6C6F68656C", 26],
            ["6865\n6C6C6F6865\n6C6C6F6865\n6C", "68656C6C6F68656C6C6F68656C", 26],
            ["68\n656C6C6F68\n656C6C6F68\n656C", "68656C6C6F68656C6C6F68656C", 26],
            ["68656C6C6F\n68656C6C6F\n68656C", "68656C6C6F68656C6C6F68656C", 26],
        ];
    }

    /**
     * @dataProvider getDataForTestOutputWithFile
     */
    public function testOutputWithFile($hexFile, $expectedFile, $length)
    {
        $hexFile = $this->base.$hexFile;

        $expectedFile = $this->base.$expectedFile;

        $hexInput = new FileInputStream($hexFile, 'rb');

        $out = new StringOutputStream();

        $stream = new AsciiHexadecimalWashOutputStream($out);

        while (-1 !== $hexInput->read($bytes, $length)) {

            $this->output->invoke($stream, $bytes);
        }

        $this->assertEquals(trim(file_get_contents($expectedFile)), $out->__toString());
    }

    public function getDataForTestOutputWithFile()
    {
        return [
            ['eexec-block-encrypted-as-hex-001.txt', 'eexec-block-encrypted-as-hex-without-format-001.txt', 1],
            ['eexec-block-encrypted-as-hex-001.txt', 'eexec-block-encrypted-as-hex-without-format-001.txt', 32],
        ];
    }
}
