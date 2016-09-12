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

use ZerusTech\Component\Postscript\Font\TypeOne\Stream\Input\AsciiHexadecimalFormatInputStream;
use ZerusTech\Component\Postscript\Font\TypeOne\Stream\Input\AsciiHexadecimalWashInputStream;
use ZerusTech\Component\IO\Stream\Input\StringInputStream;
use ZerusTech\Component\IO\Stream\Input\FileInputStream;
use ZerusTech\Component\IO\Stream\Input\WashInputStream;
use ZerusTech\Component\IO\Stream\Output\StringOutputStream;
use ZerusTech\Component\IO\Exception;

/**
 * Test case for file ascii hexadecimal format input stream.
 *
 * @author Michael Lee <michael.lee@zerustech.com>
 */
class AsciiHexadecimalFormatInputStreamTest extends \PHPUnit_Framework_TestCase
{
    public function setup()
    {
        $this->ref = new \ReflectionClass('ZerusTech\Component\Postscript\Font\TypeOne\Stream\Input\AsciiHexadecimalFormatInputStream');

        $this->input = $this->ref->getMethod('input');
        $this->input->setAccessible(true);

        $this->in = $this->ref->getProperty('in');
        $this->in->setAccessible(true);

        $this->column = $this->ref->getProperty('column');
        $this->column->setAccessible(true);

        $this->width = $this->ref->getProperty('width');
        $this->width->setAccessible(true);

        $this->base = __DIR__.'/../../../../Fixtures/Font/TypeOne/';
    }

    public function  tearDown()
    {
        $this->base = null;
        $this->width = null;
        $this->column = null;
        $this->input = null;
        $this->ref = null;
    }

    /**
     * @dataProvider getDataForTestConstructor
     */
    public function testConstructor($column, $width)
    {
        $in = new StringInputStream('');

        $stream = new AsciiHexadecimalFormatInputStream($in, $column, $width);

        $this->assertSame($in, $this->in->getValue($stream));

        $this->assertEquals($column, $this->column->getValue($stream));

        $this->assertEquals($width, $this->width->getValue($stream));
    }

    public function getDataForTestConstructor()
    {
        return [
            [0, 16],
            [16, 32],
        ];
    }

    /**
     * @dataProvider getDataForTestInput
     */
    public function testInput($column, $width, $hex, $offset, $length, $expected, $count, $skipped, $available)
    {
        $stream = new AsciiHexadecimalFormatInputStream(new StringInputStream($hex), $column, $width);

        $this->assertEquals($skipped, $stream->skip($offset));

        $this->assertEquals($count, $this->input->invokeArgs($stream, [&$bytes, $length]));

        $this->assertEquals($expected, $bytes);

        $this->assertEquals($available, $stream->available());
    }

    public function getDataForTestInput()
    {
        return [
            [0, 4, '68656C6C6F', 0, 2, '68', 2, 0, 1],
            [0, 4, '68656C6C6F', 0, 3, '6865', 4, 0, 1],
            [0, 4, '68656C6C6F', 0, 4, '6865', 4, 0, 1],
            [0, 4, '68656C6C6F', 0, 7, "68656C6C\n", 9, 0, 1],
            [0, 4, '68656C6C6F', 0, 8, "68656C6C\n", 9, 0, 1],
            [0, 4, '68656C6C6F', 0, 9, "68656C6C\n", 9, 0, 1],
            [0, 4, '68656C6C6F', 0, 10, "68656C6C\n6F", 11, 0, 0],
            [0, 4, '68656C6C6F', 0, 11, "68656C6C\n6F", 11, 0, 0],
            [0, 4, '68656C6C6F', 0, 12, "68656C6C\n6F", 11, 0, 0],
            [0, 4, '68656C6C6F', 1, 1, '65', 2, 2, 1],
            [0, 4, '68656C6C6F', 1, 2, '65', 2, 2, 1],
            [0, 4, '68656C6C6F', 2, 1, '65', 2, 2, 1],
            [0, 4, '68656C6C6F', 2, 2, '65', 2, 2, 1],
            [0, 4, '68656C6C6F', 5, 1, "6C\n", 3, 6, 1],
            [0, 4, '68656C6C6F', 5, 2, "6C\n", 3, 6, 1],
            [0, 4, '68656C6C6F', 6, 1, "6C\n", 3, 6, 1],
            [0, 4, '68656C6C6F', 6, 2, "6C\n", 3, 6, 1],
            [0, 4, '68656C6C6F', 7, 1, "6F", 2, 9, 0],
            [0, 4, '68656C6C6F', 7, 2, "6F", 2, 9, 0],
            [0, 4, '68656C6C6F', 8, 1, "6F", 2, 9, 0],
            [0, 4, '68656C6C6F', 8, 2, "6F", 2, 9, 0],
            [0, 4, '68656C6C6F', 10, 1, '', -1, 11, 0],
            [0, 4, '68656C6C6F', 11, 1, '', -1, 11, 0],
            [0, 4, '30313233303132333031323330313233', 0, 36, "30313233\n30313233\n30313233\n30313233\n", 36, 0, 0],
            [1, 4, '30313233303132333031323330313233', 0, 36, "303132\n33303132\n33303132\n33303132\n33", 36, 0, 0],
            [2, 4, '30313233303132333031323330313233', 0, 36, "3031\n32333031\n32333031\n32333031\n3233", 36, 0, 0],
            [3, 4, '30313233303132333031323330313233', 0, 36, "30\n31323330\n31323330\n31323330\n313233", 36, 0, 0],
            [4, 4, '30313233303132333031323330313233', 0, 36, "30313233\n30313233\n30313233\n30313233\n", 36, 0, 0],
        ];
    }

    /**
     * @dataProvider getDataForTestInputWithException
     * @expectedException \RuntimeException
     * @expectedExceptionMessageRegex /.* is not a valid hexadecimal string./
     */
    public function testInputWithException($hex, $length)
    {
        $stream = new AsciiHexadecimalFormatInputStream(new StringInputStream($hex));

        $this->input->invokeArgs($stream, [&$bytes, $length]);
    }

    public function getDataForTestInputWithException()
    {
        return [
            ["68656C6C6F\n", 11],
            ["68656C6C6F\t", 11],
            ["68656C6C6F\r", 11],
            ["68656C6C6F ", 11],
            ["68656C\n6C6F", 11],
            ["68656C\r6C6F", 11],
            ["68656C\t6C6F", 11],
            ["68656C 6C6F", 11],
        ];
    }

    /**
     * @dataProvider getDataForTestInputWithFile
     */
    public function testInputWithFile($hexFile, $expectedFile, $length)
    {
        $hexFile = $this->base.$hexFile;

        $expectedFile = $this->base.$expectedFile;

        $stream = new AsciiHexadecimalFormatInputStream(new WashInputStream(new FileInputStream($hexFile, 'rb')));

        $out = new StringOutputStream();

        while (-1 !== $this->input->invokeArgs($stream, [&$hex, $length])) {

            $out->write($hex);
        }

        $this->assertEquals(file_get_contents($expectedFile), $out->__toString());
    }

    public function getDataForTestInputWithFile()
    {
        return [
            ['eexec-block-encrypted-as-hex-without-format-001.txt', 'eexec-block-encrypted-as-hex-001.txt', 1],
            ['eexec-block-encrypted-as-hex-without-format-001.txt', 'eexec-block-encrypted-as-hex-001.txt', 2],
            ['eexec-block-encrypted-as-hex-without-format-001.txt', 'eexec-block-encrypted-as-hex-001.txt', 32],
        ];
    }
}
