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

use ZerusTech\Component\Postscript\Font\TypeOne\Stream\Input\DecryptInputStream;
use ZerusTech\Component\Postscript\Font\TypeOne\Stream\Input\AsciiHexadecimalToBinaryInputStream;
use ZerusTech\Component\Postscript\Font\TypeOne\Stream\Output\AsciiHexadecimalToBinaryOutputStream;
use ZerusTech\Component\IO\Stream\Input\StringInputStream;
use ZerusTech\Component\IO\Stream\Input\FileInputStream;
use ZerusTech\Component\IO\Stream\Input\InputStreamInterface;
use ZerusTech\Component\IO\Stream\Input\LineInputStream;
use ZerusTech\Component\IO\Stream\Output\StringOutputStream;

/**
 * Test case for abstract encryptor.
 *
 * @author Michael Lee <michael.lee@zerustech.com>
 */
class DecryptInputStreamTest extends \PHPUnit_Framework_TestCase
{
    public function setup()
    {
        $this->ref = new \ReflectionClass('ZerusTech\Component\Postscript\Font\TypeOne\Stream\Input\DecryptInputStream');

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
        $stream = new DecryptInputStream($in, 4330, 4);

        $this->assertEquals(4330, $this->R->getValue($stream));
        $this->assertEquals(4330, $this->r->getValue($stream));
        $this->assertEquals(4, $this->n->getValue($stream));
        $this->assertEquals(52845, $this->c1->getValue($stream));
        $this->assertEquals(22719, $this->c2->getValue($stream));
        $this->assertSame($in, $this->in->getValue($stream));
    }

    /**
     * @dataProvider getDataForTestInput
     */
    public function testInput($R, $n, $encrypted, $offset, $length, $expected, $count, $skipped, $available)
    {
        $encryptedInput = new AsciiHexadecimalToBinaryInputStream(new StringInputStream($encrypted));

        $expectedInput = new AsciiHexadecimalToBinaryInputStream(new StringInputStream($expected));

        $expectedInput->read($expectedBin, strlen($expected));

        $stream = new DecryptInputStream($encryptedInput, $R, $n);

        $this->assertEquals($skipped, $stream->skip($offset));

        $this->assertEquals($count, $this->input->invokeArgs($stream, [&$bytes, $length]));

        $this->assertEquals($expectedBin, $bytes);

        $this->assertEquals($available, $stream->available());
    }

    public function getDataForTestInput()
    {
        return [
            [4330, 4, "10 BF 31 70 9A A9 E3 3D EE", 0, 5, "68 65 6c 6c 6F", 5, 0, 0],
            [4330, 4, "10 BF 31 70 9A A9 E3 3D EE", 0, 1, "68", 1, 0, 1],
            [4330, 4, "10 BF 31 70 9A A9 E3 3D EE", 0, 4, "68 65 6c 6c", 4, 0, 1],
            [55665, 4, 'E9 8D 09 D7 6C E6 99 52 F0', 0, 5, '68 65 6C 6C 6F', 5, 0, 0],
        ];
    }

    /**
     * @dataProvider getDataForTestInputWithEexecBinFile
     */
    public function testInputWithEexecBinFile($cipherFile, $plainFile, $length)
    {
        $cipherFile = $this->base.$cipherFile;

        $plainFile = $this->base.$plainFile;

        $input = new DecryptInputStream(new FileInputStream($cipherFile, 'rb'), 55665, 4);

        $output = new StringOutputStream();

        while (-1 !== $this->input->invokeArgs($input, [&$bytes, $length])) {

            $output->write($bytes);
        }

        $this->assertEquals(file_get_contents($plainFile), $output->__toString());
    }

    public function getDataForTestInputWithEexecBinFile()
    {
        return [
            ['eexec-block-encrypted-as-bin-001.txt', 'eexec-block-decrypted-001.txt', 1],
            ['eexec-block-encrypted-as-bin-001.txt', 'eexec-block-decrypted-001.txt', 2],
            ['eexec-block-encrypted-as-bin-001.txt', 'eexec-block-decrypted-001.txt', 32],
        ];
    }

    /**
     * @dataProvider getDataForTestInputWithEexecHexFile
     */
    public function testInputWithEexecHexFile($cipherFile, $plainFile, $length)
    {
        $cipherFile = $this->base.$cipherFile;

        $plainFile = $this->base.$plainFile;

        $input = new DecryptInputStream(new AsciiHexadecimalToBinaryInputStream(new FileInputStream($cipherFile, 'rb')), 55665, 4);

        $output = new StringOutputStream();

        while (-1 !== ($this->input->invokeArgs($input, [&$bytes, $length]))) {

            $output->write($bytes);
        }

        $this->assertEquals(file_get_contents($plainFile), $output->__toString());
    }

    public function getDataForTestInputWithEexecHexFile()
    {
        return [
            ['eexec-block-encrypted-as-hex-001.txt', 'eexec-block-decrypted-001.txt', 1],
            ['eexec-block-encrypted-as-hex-001.txt', 'eexec-block-decrypted-001.txt', 2],
            ['eexec-block-encrypted-as-hex-001.txt', 'eexec-block-decrypted-001.txt', 32],
        ];
    }

    /**
     * @dataProvider getDataForTestInputWithCharStringHexFile
     */
    public function testInputWithCharStringHexFile($cipherFile, $plainFile, $length)
    {
        $cipherFile = $this->base.$cipherFile;

        $plainFile = $this->base.$plainFile;

        $cipherLineInput = new LineInputStream(new FileInputStream($cipherFile, 'rb'));

        $plainLineInput = new LineInputStream(new FileInputStream($plainFile, 'rb'));

        while (-1 !== ($cipherLineInput->read($cipherHex, $length)) && -1 !== ($plainLineInput->read($plainHex, $length))) {

            $stream = new DecryptInputStream(new AsciiHexadecimalToBinaryInputStream(new StringInputStream($cipherHex)), 4330, 4);

            $plainInput = new AsciiHexadecimalToBinaryInputStream(new StringInputStream($plainHex));

            $plainInput->read($plainBin, strlen($plainHex) / 2);

            $this->input->invokeArgs($stream, [&$bytes, strlen($cipherHex) / 2]);

            $this->assertEquals($plainBin, $bytes);
        }
    }

    public function getDataForTestInputWithCharStringHexFile()
    {
        return [
            ['charstring-encrypted-as-hex-001.txt', 'charstring-decrypted-to-encoded-hex-001.txt', 4, 1],
            ['charstring-encrypted-as-hex-001.txt', 'charstring-decrypted-to-encoded-hex-001.txt', 4, 32],
        ];
    }
}
