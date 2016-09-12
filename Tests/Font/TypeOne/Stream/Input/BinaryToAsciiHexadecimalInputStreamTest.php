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

        $this->base = __DIR__.'/../../../../Fixtures/Font/TypeOne/';
    }

    public function  tearDown()
    {
        $this->base = null;
        $this->input = null;
        $this->ref = null;
    }

    public function testConstructor()
    {
        $in = new StringInputStream('');

        $stream = new BinaryToAsciiHexadecimalInputStream($in);

        $this->assertSame($in, $this->in->getValue($stream));
    }

    /**
     * @dataProvider getDataForTestInput
     */
    public function testInput($bin, $offset, $length, $expected, $count, $skipped, $available)
    {
        $stream = new BinaryToAsciiHexadecimalInputStream(new StringInputStream($bin));

        $this->assertEquals($skipped, $stream->skip($offset));

        $this->assertEquals($count, $this->input->invokeArgs($stream, [&$bytes, $length]));

        $this->assertEquals($expected, $bytes);

        $this->assertEquals($available, $stream->available());
    }

    public function getDataForTestInput()
    {
        return [
            ['hello', 0, 1, '68', 2, 0, 8],
            ['hello', 0, 2, '68', 2, 0, 8],
            ['hello', 0, 3, '6865', 4, 0, 6],
            ['hello', 0, 10, '68656C6C6F', 10, 0, 0],
            ['hello', 0, 11, '68656C6C6F', 10, 0, 0],
            ['hello', 1, 1, '65', 2, 2, 6],
            ['hello', 2, 1, '65', 2, 2, 6],
            ['hello', 2, 2, '65', 2, 2, 6],
            ['hello', 8, 2, '6F', 2, 8, 0],
            ['hello', 10, 1, '', -1, 10, 0],
            ['hello', 10, 2, '', -1, 10, 0],
        ];
    }

    /**
     * @dataProvider getDataForTestInputWithFile
     */
    public function testInputWithFile($binFile, $expectedFile, $length)
    {
        $expectedFile = $this->base.$expectedFile;

        $binFile = $this->base.$binFile;

        $stream = new BinaryToAsciiHexadecimalInputStream(new FileInputStream($binFile, 'rb'));

        $out = new StringOutputStream();

        while (-1 !== $this->input->invokeArgs($stream, [&$bin, $length])) {

            $out->write($bin);
        }

        $this->assertEquals(trim(file_get_contents($expectedFile)), $out->__toString());
    }

    public function getDataForTestInputWithFile()
    {
        return [
            ['eexec-block-encrypted-as-bin-001.txt', 'eexec-block-encrypted-as-hex-without-format-001.txt', 1],
        ];
    }
}
