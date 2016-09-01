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
    public function testOutput($binFile, $hexFile, $length)
    {
        $binFile = $this->base.$binFile;

        $hexFile = $this->base.$hexFile;

        $in = new FileInputStream($hexFile, 'rb');

        $out = new StringOutputStream();

        $stream = new AsciiHexadecimalToBinaryOutputStream($out);

        while (-1 !== $in->read($bytes, $length)) {

            $this->output->invoke($stream, $bytes);
        }

        $this->assertEquals(file_get_contents($binFile), $out->__toString());
    }

    public function getDataForTestOutput()
    {
        return [
            ['eexec-block-encrypted-as-bin-001.txt', 'eexec-block-encrypted-as-hex-001.txt', 1],
            ['eexec-block-encrypted-as-bin-001.txt', 'eexec-block-encrypted-as-hex-001.txt', 32],
        ];
    }
}
