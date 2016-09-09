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

use ZerusTech\Component\Postscript\Font\TypeOne\Stream\Input\EexecDecryptInputStream;
use ZerusTech\Component\Postscript\Font\TypeOne\Stream\Input\DecryptInputStream;
use ZerusTech\Component\Postscript\Font\TypeOne\Stream\Input\AsciiHexadecimalToBinaryInputStream;
use ZerusTech\Component\IO\Stream\Input\StringInputStream;
use ZerusTech\Component\IO\Stream\Input\FileInputStream;
use ZerusTech\Component\IO\Stream\Input\InputStreamInterface;
use ZerusTech\Component\IO\Stream\Output\StringOutputStream;

/**
 * Test case for abstract encryptor.
 *
 * @author Michael Lee <michael.lee@zerustech.com>
 */
class EexecDecryptInputStreamTest extends \PHPUnit_Framework_TestCase
{
    public function setup()
    {
        $this->ref = new \ReflectionClass('ZerusTech\Component\Postscript\Font\TypeOne\Stream\Input\EexecDecryptInputStream');

        $this->r = $this->ref->getProperty('r');
        $this->r->setAccessible(true);

        $this->R = $this->ref->getProperty('R');
        $this->R->setAccessible(true);

        $this->c1 = $this->ref->getProperty('c1');
        $this->c1->setAccessible(true);

        $this->c2 = $this->ref->getProperty('c2');
        $this->c2->setAccessible(true);

        $this->n = $this->ref->getProperty('n');
        $this->n->setAccessible(true);

        $this->in = $this->ref->getProperty('in');
        $this->in->setAccessible(true);

        $this->input = $this->ref->getMethod('input');
        $this->input->setAccessible(true);

        $this->base = __DIR__.'/../../../../Fixtures/Font/TypeOne/';
    }

    public function tearDown()
    {
        $this->input = null;
        $this->in = null;
        $this->n = null;
        $this->c2 = null;
        $this->c1 = null;
        $this->R = null;
        $this->r = null;
        $this->ref = null;
    }

    public function testConstructor()
    {
        $in = new StringInputStream('hello');
        $stream = new EexecDecryptInputStream($in);

        $this->assertEquals(55665, $this->R->getValue($stream));
        $this->assertEquals(55665, $this->r->getValue($stream));
        $this->assertEquals(4, $this->n->getValue($stream));
        $this->assertEquals(52845, $this->c1->getValue($stream));
        $this->assertEquals(22719, $this->c2->getValue($stream));
        $this->assertSame($in, $this->in->getValue($stream));
    }

    /**
     * @dataProvider getDataForTestInput
     */
    public function testInput($encrypted, $offset, $length, $expected, $count, $skipped, $available)
    {
        $encryptedInput = new AsciiHexadecimalToBinaryInputStream(new StringInputStream($encrypted));

        $expectedInput = new AsciiHexadecimalToBinaryInputStream(new StringInputStream($expected));

        $expectedInput->read($expectedBin, strlen($expected));

        $stream = new EexecDecryptInputStream($encryptedInput);

        $this->assertEquals($skipped, $stream->skip($offset));

        $this->assertEquals($count, $this->input->invokeArgs($stream, [&$bytes, $length]));

        $this->assertEquals($expectedBin, $bytes);

        $this->assertEquals($available, $stream->available());
    }

    public function getDataForTestInput()
    {
        return [
            ['E9 8D 09 D7 6C E6 99 52 F0', 0, 5, '68 65 6C 6C 6F', 5, 0, 0],
        ];
    }


    /**
     * @dataProvider getDataForTestInputWithBinFile
     */
    public function testInputWithBinFile($binFile, $plainFile, $length)
    {
        $binFile = $this->base.$binFile;

        $plainFile = $this->base.$plainFile;

        $input = new EexecDecryptInputStream(new FileInputStream($binFile, 'rb'));

        $output = new StringOutputStream();

        while (-1 !== $this->input->invokeArgs($input, [&$bytes, $length])) {

            $output->write($bytes);
        }

        $this->assertEquals(file_get_contents($plainFile), $output->__toString());
    }

    public function getDataForTestInputWithBinFile()
    {
        return [
            ['eexec-block-encrypted-as-bin-001.txt', 'eexec-block-decrypted-001.txt', 1],
            ['eexec-block-encrypted-as-bin-001.txt', 'eexec-block-decrypted-001.txt', 2],
            ['eexec-block-encrypted-as-bin-001.txt', 'eexec-block-decrypted-001.txt', 32],
        ];
    }

    /**
     * @dataProvider getDataForTestInputWithHexFile
     */
    public function testInputWithHexFile($hexFile, $plainFile, $length)
    {
        $hexFile = $this->base.$hexFile;

        $plainFile = $this->base.$plainFile;

        $stream = new EexecDecryptInputStream(new AsciiHexadecimalToBinaryInputStream(new FileInputStream($hexFile, 'rb')));

        $output = new StringOutputStream();

        while (-1 !== ($this->input->invokeArgs($stream, [&$bytes, $length]))) {

            $output->write($bytes);
        }

        $this->assertEquals(file_get_contents($plainFile), $output->__toString());
    }

    public function getDataForTestInputWithHexFile()
    {
        return [
            ['eexec-block-encrypted-as-hex-001.txt', 'eexec-block-decrypted-001.txt', 1],
            ['eexec-block-encrypted-as-hex-001.txt', 'eexec-block-decrypted-001.txt', 2],
            ['eexec-block-encrypted-as-hex-001.txt', 'eexec-block-decrypted-001.txt', 32],
        ];
    }
}
