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

use ZerusTech\Component\Postscript\Font\TypeOne\Stream\Input\BinaryToAsciiHexadecimalInputStream;
use ZerusTech\Component\IO\Stream\Input\StringInputStream;
use ZerusTech\Component\IO\Stream\Input\FileInputStream;
use ZerusTech\Component\IO\Stream\Output\StringOutputStream;
use ZerusTech\Component\IO\Exception;

/**
 * Test case for file binary to ascii hexadecimal input stream.
 *
 * @author Michael Lee <michael.lee@zerustech.com>
 */
class BinaryToAsciiHexadecimalInputStreamTest extends \PHPUnit_Framework_TestCase
{
    public function setup()
    {
        $this->ref = new \ReflectionClass('ZerusTech\Component\Postscript\Font\TypeOne\Stream\Input\BinaryToAsciiHexadecimalInputStream');

        $this->input = $this->ref->getMethod('input');
        $this->input->setAccessible(true);

        $this->in = $this->ref->getProperty('in');
        $this->in->setAccessible(true);

        $this->column = $this->ref->getProperty('column');
        $this->column->setAccessible(true);

        $this->format = $this->ref->getProperty('format');
        $this->format->setAccessible(true);

        $this->width = $this->ref->getProperty('width');
        $this->width->setAccessible(true);

        $this->base = __DIR__.'/../../../../Fixtures/Font/TypeOne/';
    }

    public function  tearDown()
    {
        $this->base = null;
        $this->width = null;
        $this->format = null;
        $this->column = null;
        $this->input = null;
        $this->ref = null;
    }

    /**
     * @dataProvider getDataForTestConstructor
     */
    public function testConstructor($column, $format, $width)
    {
        $in = new StringInputStream('');

        $stream = new BinaryToAsciiHexadecimalInputStream($in, $column, $format, $width);

        $this->assertSame($in, $this->in->getValue($stream));

        $this->assertEquals($column, $this->column->getValue($stream));

        $this->assertEquals($format, $this->format->getValue($stream));

        $this->assertEquals($width, $this->width->getValue($stream));
    }

    public function getDataForTestConstructor()
    {
        return [
            [0, true, 32],
            [0, false, 16],
            [16, true, 32],
        ];
    }

    /**
     * @dataProvider getDataForTestInput
     */
    public function testInput($column, $format, $width, $bin, $offset, $length, $expected, $count, $skipped, $available)
    {
        $stream = new BinaryToAsciiHexadecimalInputStream(new StringInputStream($bin), $column, $format, $width);

        $this->assertEquals($skipped, $stream->skip($offset));

        $this->assertEquals($count, $this->input->invokeArgs($stream, [&$bytes, $length]));

        $this->assertEquals($expected, $bytes);

        $this->assertEquals($available, $stream->available());
    }

    public function getDataForTestInput()
    {
        return [
            [0, false, 4, 'hello', 0, 1, '68', 2, 0, 8],
            [0, false, 4, 'hello', 0, 2, '68', 2, 0, 8],
            [0, false, 4, 'hello', 0, 3, '6865', 4, 0, 6],
            [0, false, 4, 'hello', 0, 10, '68656C6C6F', 10, 0, 0],
            [0, false, 4, 'hello', 0, 11, '68656C6C6F', 10, 0, 0],
            [0, false, 4, 'hello', 1, 1, '65', 2, 2, 6],
            [0, false, 4, 'hello', 2, 1, '65', 2, 2, 6],
            [0, false, 4, 'hello', 2, 2, '65', 2, 2, 6],
            [0, false, 4, 'hello', 8, 2, '6F', 2, 8, 0],
            [0, false, 4, 'hello', 10, 1, '', -1, 10, 0],
            [0, false, 4, 'hello', 10, 2, '', -1, 10, 0],
            [0, true, 4, '0123012301230123', 0, 32, "30313233\n30313233\n30313233\n30313233\n", 32, 0, 0],
            [0, true, 4, '0123012301230123', 0, 33, "30313233\n30313233\n30313233\n30313233\n", 32, 0, 0],
            [0, true, 4, '0123012301230123', 0, 12, "30313233\n3031", 12, 0, 20],
            [2, true, 4, '0123012301230123', 0, 12, "3031\n32333031\n", 12, 0, 20],
            [0, true, 4, '0123012301230123', 4, 28, "3233\n30313233\n30313233\n30313233\n", 28, 4, 0],
            [1, true, 4, '0123012301230123', 4, 28, "32\n33303132\n33303132\n33303132\n33", 28, 4, 0],
            [1, true, 4, '0123012301230123', 3, 28, "32\n33303132\n33303132\n33303132\n33", 28, 4, 0],
            [0, true, 4, '0123012301230123', 32, 2, '', -1, 32, 0],
            [0, true, 4, '0123012301230123', 32, 1, '', -1, 32, 0],
        ];
    }

    /**
     * @dataProvider getDataForTestInputWithFile
     */
    public function testInputWithFile($hexFile, $binFile, $length)
    {
        $hexFile = $this->base.$hexFile;

        $binFile = $this->base.$binFile;

        $stream = new BinaryToAsciiHexadecimalInputStream(new FileInputStream($binFile, 'rb'));

        $out = new StringOutputStream();

        while (-1 !== $this->input->invokeArgs($stream, [&$bin, $length])) {

            $out->write($bin);
        }

        $this->assertEquals(file_get_contents($hexFile), $out->__toString());
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
