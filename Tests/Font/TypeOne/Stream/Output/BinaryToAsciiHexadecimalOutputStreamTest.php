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

        $this->column = $this->ref->getProperty('column');
        $this->column->setAccessible(true);

        $this->format = $this->ref->getProperty('format');
        $this->format->setAccessible(true);

        $this->width = $this->ref->getProperty('width');
        $this->width->setAccessible(true);

        $this->base = __DIR__.'/../../../../Fixtures/Font/TypeOne/';
    }

    public function tearDown()
    {
        $this->width = null;
        $this->format = null;
        $this->column = null;
        $this->out = null;
        $this->output = null;
        $this->ref = null;

        $this->base = null;
    }

    /**
     * @dataProvider getDataForTestConstructor
     */
    public function testConstructor($column, $format, $width)
    {
        $out = new StringOutputStream();
        $stream = new BinaryToAsciiHexadecimalOutputStream($out, $column, $format, $width);

        $this->assertSame($out, $this->out->getValue($stream));
        $this->assertEquals($column, $this->column->getValue($stream));
        $this->assertEquals($format, $this->format->getValue($stream));
        $this->assertEquals($width, $this->width->getValue($stream));
    }

    public function getDataForTestConstructor()
    {
        return [
            [0, true, 32],
            [1, false, 16],
        ];
    }

    /**
     * @dataProvider getDataForTestOutput
     */
    public function testOutput($binFile, $hexFile, $length, $skip, $width)
    {
        $binFile = $this->base.$binFile;

        $hexFile = $this->base.$hexFile;

        $in = new FileInputStream($binFile, 'rb');

        $out = new StringOutputStream();

        $stream = new BinaryToAsciiHexadecimalOutputStream($out);

        $column = 0;

        if ($skip > 0) {

            $in->read($bytes, $skip);

            $this->output->invoke($stream, $bytes);

            $column = strlen($bytes) % $width;
        }

        $stream = new BinaryToAsciiHexadecimalOutputStream($out, $column, true, $width);

        while (-1 !== $in->read($bytes, $length)) {

            $this->output->invoke($stream, $bytes);
        }

        $this->assertEquals(file_get_contents($hexFile), $out->__toString());
    }

    public function getDataForTestOutput()
    {
        return [
            ['eexec-block-encrypted-as-bin-001.txt', 'eexec-block-encrypted-as-hex-001.txt', 1, 0, 32],
            ['eexec-block-encrypted-as-bin-001.txt', 'eexec-block-encrypted-as-hex-001.txt', 1, 16, 32],
            ['eexec-block-encrypted-as-bin-001.txt', 'eexec-block-encrypted-as-hex-001.txt', 32, 0, 32],
            ['eexec-block-encrypted-as-bin-001.txt', 'eexec-block-encrypted-as-hex-001.txt', 32, 16, 32],
        ];
    }

    /**
     * @dataProvider getDataForTestOutputWithoutFormat
     */
    public function testOutputWithoutFormat($binFile, $hexFile, $length)
    {
        $binFile = $this->base.$binFile;

        $hexFile = $this->base.$hexFile;

        $in = new FileInputStream($binFile, 'rb');

        $out = new StringOutputStream();

        $stream = new BinaryToAsciiHexadecimalOutputStream($out, 0, false);

        while (-1 !== $in->read($bytes, $length)) {

            $this->output->invoke($stream, $bytes);
        }

        $out->write("\n");

        $this->assertEquals(file_get_contents($hexFile), $out->__toString());
    }

    public function getDataForTestOutputWithoutFormat()
    {
        return [
            ['eexec-block-encrypted-as-bin-001.txt', 'eexec-block-encrypted-as-hex-without-format-001.txt', 1],
            ['eexec-block-encrypted-as-bin-001.txt', 'eexec-block-encrypted-as-hex-without-format-001.txt', 16],
        ];
    }
}
