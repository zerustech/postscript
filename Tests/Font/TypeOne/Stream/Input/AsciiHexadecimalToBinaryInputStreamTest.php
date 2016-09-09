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

use ZerusTech\Component\Postscript\Font\TypeOne\Stream\Input\AsciiHexadecimalToBinaryInputStream;
use ZerusTech\Component\IO\Stream\Input\StringInputStream;
use ZerusTech\Component\IO\Stream\Input\FileInputStream;
use ZerusTech\Component\IO\Stream\Output\StringOutputStream;
use ZerusTech\Component\IO\Exception;

/**
 * Test case for ascii hexadecimal to binary input stream.
 *
 * @author Michael Lee <michael.lee@zerustech.com>
 */
class AsciiHexadecimalToBinaryInputStreamTest extends \PHPUnit_Framework_TestCase
{
    public function setup()
    {
        $this->ref = new \ReflectionClass('ZerusTech\Component\Postscript\Font\TypeOne\Stream\Input\AsciiHexadecimalToBinaryInputStream');

        $this->buffer = $this->ref->getProperty('buffer');
        $this->buffer->setAccessible(true);

        $this->input = $this->ref->getMethod('input');
        $this->input->setAccessible(true);

        $this->base = __DIR__.'/../../../../Fixtures/Font/TypeOne/';
    }

    public function tearDown()
    {
        $this->input = null;
        $this->buffer = null;

        $this->ref = null;

        $this->base = null;
    }

    public function testConstruct()
    {
        $in = new StringInputStream('hello');
        $stream = new AsciiHexadecimalToBinaryInputStream($in);
        $this->assertEquals([], $this->buffer->getValue($stream));
    }

    /**
     * @dataProvider getDataForTestInput
     */
    public function testInput($hex, $offset, $length, $expected, $count, $skipped, $available)
    {
        $in = new StringInputStream($hex);

        $stream = new AsciiHexadecimalToBinaryInputStream($in);

        $this->assertEquals($skipped, $stream->skip($offset));

        $this->assertEquals($count, $this->input->invokeArgs($stream, [&$bytes, $length]));

        $this->assertEquals($expected, $bytes);

        $this->assertEquals($available, $stream->available());
    }

    public function getDataForTestInput()
    {
        return [
            ['68656C6C6F', 0, 1, 'h', 1, 0, 1],
            ['68656C6C6F', 0, 2, 'he', 2, 0, 1],
            ['68656C6C6F', 0, 5, 'hello', 5, 0, 0],
            ['68656C6C6F', 0, 6, 'hello', 5, 0, 0],
            ['68656C6C6F', 4, 1, 'o', 1, 4, 0],
            ['68656C6C6F', 4, 2, 'o', 1, 4, 0],
            ['68656C6C6F', 5, 1, '', -1, 5, 0],
            ['68656C6C6F', 5, 2, '', -1, 5, 0],
            ["\n\t\r 68", 0, 1, 'h', 1, 0, 0],
            ["\n\t\r 68\n\t\r ", 0, 1, 'h', 1, 0, 1],
            ["\n\t\r 68\n\t\r ", 0, 2, 'h', 1, 0, 0],
            ["\n\t\r 68\n\t\r ", 1, 1, '', -1, 1, 0],
            ["\n\t\r 6\n\t\r 8\n\t\r ", 0, 1, 'h', 1, 0, 1],
            ["\n\t\r 6\n\t\r 8\n\t\r ", 0, 2, 'h', 1, 0, 0],
            ["\n\t\r 6\n\t\r 8\n\t\r ", 1, 1, '', -1, 1, 0],
        ];
    }

    /**
     * @dataProvider getDataForTestInputWithFile
     */
    public function testInputWithFile($hexFile, $binFile, $length)
    {
        $hexFile = $this->base.$hexFile;

        $binFile = $this->base.$binFile;

        $in = new FileInputStream($hexFile, 'rb');

        $stream = new AsciiHexadecimalToBinaryInputStream($in);

        $out = new StringOutputStream();

        while (-1 !== $this->input->invokeArgs($stream, [&$hex, $length])) {

            $out->write($hex);
        }

        $this->assertEquals(file_get_contents($binFile), $out->__toString());
    }

    public function getDataForTestInputWithFile()
    {
        return [
            ['eexec-block-encrypted-as-hex-001.txt', 'eexec-block-encrypted-as-bin-001.txt', 1],
            ['eexec-block-encrypted-as-hex-001.txt', 'eexec-block-encrypted-as-bin-001.txt', 2],
            ['eexec-block-encrypted-as-hex-001.txt', 'eexec-block-encrypted-as-bin-001.txt', 32],
        ];
    }
}
