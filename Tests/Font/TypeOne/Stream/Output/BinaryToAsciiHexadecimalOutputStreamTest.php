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
use ZerusTech\Component\Postscript\Font\TypeOne\Stream\Output\BinaryToAsciiHexadecimalOutputStream;

/**
 * Test case for binary to ascii hexadecimal output stream.
 *
 * @author Michael Lee <michael.lee@zerustech.com>
 */
class BinaryToAsciiHexadecimalOutputStreamTest extends \PHPUnit_Framework_TestCase
{
    public function setup()
    {
        $this->ref = new \ReflectionClass('ZerusTech\Component\Postscript\Font\TypeOne\Stream\Output\BinaryToAsciiHexadecimalOutputStream');

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
        $stream = new BinaryToAsciiHexadecimalOutputStream($out);

        $this->assertSame($out, $this->out->getValue($stream));
    }

    /**
     * @dataProvider getDataForTestOutput
     */
    public function testOutput($bin, $expected, $count)
    {
        $out = new StringOutputStream();
        $stream = new BinaryToAsciiHexadecimalOutputStream($out);
        $this->assertEquals($count, $this->output->invoke($stream, $bin));
        $this->assertEquals($expected, $out->__toString());
    }

    public function getDataForTestOutput()
    {
        return [
            ["hello", "68656C6C6F", 10],
            ["hellohel", "68656C6C6F68656C", 16],
        ];
    }

    /**
     * @dataProvider getDataForTestOutputWithFile
     */
    public function testOutputWithFile($binFile, $hexFile, $length, $skip, $width)
    {
        $binFile = $this->base.$binFile;

        $hexFile = $this->base.$hexFile;

        $binInput = new FileInputStream($binFile, 'rb');

        $out = new StringOutputStream();

        $stream = new BinaryToAsciiHexadecimalOutputStream($out);

        $column = 0;

        if ($skip > 0) {

            $binInput->read($bytes, $skip);

            $this->output->invoke($stream, $bytes);

            $column = strlen($bytes) % $width;
        }

        $stream = new BinaryToAsciiHexadecimalOutputStream($out, $column, true, $width);

        while (-1 !== $binInput->read($bytes, $length)) {

            $this->output->invoke($stream, $bytes);
        }

        $this->assertEquals(trim(file_get_contents($hexFile)), $out->__toString());
    }

    public function getDataForTestOutputWithFile()
    {
        return [
            ['eexec-block-encrypted-as-bin-001.txt', 'eexec-block-encrypted-as-hex-without-format-001.txt', 1, 0, 32],
            ['eexec-block-encrypted-as-bin-001.txt', 'eexec-block-encrypted-as-hex-without-format-001.txt', 1, 16, 32],
            ['eexec-block-encrypted-as-bin-001.txt', 'eexec-block-encrypted-as-hex-without-format-001.txt', 32, 0, 32],
            ['eexec-block-encrypted-as-bin-001.txt', 'eexec-block-encrypted-as-hex-without-format-001.txt', 32, 16, 32],
        ];
    }
}
