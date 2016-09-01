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
 * Test case for ``ascii hexadecimal to binary`` input stream.
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

    /**
     * @dataProvider getDataForTestInput
     */
    public function testInput($hex, $offset, $length, $bin, $count, $skipped, $available)
    {
        $in = new StringInputStream($hex);

        $stream = new AsciiHexadecimalToBinaryInputStream($in);

        $this->assertEquals($skipped, $stream->skip($offset));

        $this->assertEquals($count, $this->input->invokeArgs($stream, [&$bytes, $length]));

        $this->assertEquals($bin, $bytes);

        $this->assertEquals($available, $stream->available());
    }

    public function getDataForTestInput()
    {
        return [
            ['68656C6C6F', 0, 1, '', 0, 0, 10],
            ['68656C6C6F', 0, 2, 'h', 2, 0, 8],
            ['68656C6C6F', 0, 5, 'he', 4, 0, 6],
            ['68656C6C6F', 0, 10, 'hello', 10, 0, 0],
            ['68656C6C6F', 0, 12, 'hello', 10, 0, 0],
            ['68656C6C6F', 9, 1, 'o', 2, 8, 0],
            ['68656C6C6F', 9, 2, 'o', 2, 8, 0],
            ['68656C6C6F', 10, 1, '', -1, 10, 0],
            ['68656C6C6F', 10, 2, '', -1, 10, 0],
            ["\n\t\r 68", 0, 5, '', 4, 0, 2],
            ["\n\t\r 68", 0, 6, 'h', 6, 0, 0],
            ["\n\t\r 68\n\t\r ", 0, 6, 'h', 6, 0, 4],
            ["\n\t\r 68\n\t\r ", 0, 10, 'h', 10, 0, 0],
            ["\n\t\r 68\n\t\r ", 0, 11, 'h', 10, 0, 0],
            ["\n\t\r 68\n\t\r ", 0, 12, 'h', 10, 0, 0],
            ["\n\t\r 68\n\t\r ", 5, 1, 'h', 2, 4, 4],
            ["\n\t\r 68\n\t\r ", 5, 2, 'h', 3, 4, 3],
            ["\n\t\r 68\n\t\r ", 5, 3, 'h', 4, 4, 2],
            ["\n\t\r 68\n\t\r ", 5, 4, 'h', 5, 4, 1],
            ["\n\t\r 68\n\t\r ", 5, 5, 'h', 6, 4, 0],
            ["\n\t\r 6\n\t\r 8\n\t\r ", 0, 5, '', 4, 0, 10],
            ["\n\t\r 6\n\t\r 8\n\t\r ", 0, 10, 'h', 10, 0, 4],
            ["\n\t\r 6\n\t\r 8\n\t\r ", 0, 14, 'h', 14, 0, 0],
            ["\n\t\r 6\n\t\r 8\n\t\r ", 0, 15, 'h', 14, 0, 0],
            ["\n\t\r 6\n\t\r 8\n\t\r ", 0, 16, 'h', 14, 0, 0],
            ["\n\t\r 6\n\t\r 8\n\t\r ", 14, 1, '', -1, 14, 0],
            ["\n\t\r 6\n\t\r 8\n\t\r ", 14, 2, '', -1, 14, 0],
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
