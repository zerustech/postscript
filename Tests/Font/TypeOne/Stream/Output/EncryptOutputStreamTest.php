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

use ZerusTech\Component\Postscript\Font\TypeOne\Stream\Output\EncryptOutputStream;
use ZerusTech\Component\Postscript\Font\TypeOne\Stream\Output\BinaryToAsciiHexadecimalOutputStream;
use ZerusTech\Component\Postscript\Font\TypeOne\Stream\Input\AsciiHexadecimalToBinaryInputStream;
use ZerusTech\Component\IO\Stream\Input\StringInputStream;
use ZerusTech\Component\IO\Stream\Input\FileInputStream;
use ZerusTech\Component\IO\Stream\Input\LineInputStream;
use ZerusTech\Component\IO\Stream\Output\OutputStreamInterface;
use ZerusTech\Component\IO\Stream\Output\StringOutputStream;

/**
 * Test case for abstract encryptor.
 *
 * @author Michael Lee <michael.lee@zerustech.com>
 */
class EncryptOutputStreamTest extends \PHPUnit_Framework_TestCase
{
    public function setup()
    {
        $this->ref = new \ReflectionClass('ZerusTech\Component\Postscript\Font\TypeOne\Stream\Output\EncryptOutputStream');

        $this->r = $this->ref->getProperty('r');
        $this->r->setAccessible(true);

        $this->R = $this->ref->getProperty('R');
        $this->R->setAccessible(true);

        $this->c1 = $this->ref->getProperty('c1');
        $this->c1->setAccessible(true);

        $this->c2 = $this->ref->getProperty('c2');
        $this->c2->setAccessible(true);

        $this->seeds = $this->ref->getProperty('seeds');
        $this->seeds->setAccessible(true);

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
        $this->seeds = null;
        $this->c2 = null;
        $this->c1 = null;
        $this->R = null;
        $this->r = null;
        $this->ref = null;
    }

    public function testConstructor()
    {
        $out = new StringOutputStream();
        $stream = new EncryptOutputStream($out, 4330, 'aaaa');

        $this->assertEquals(4330, $this->R->getValue($stream));
        $this->assertEquals(4330, $this->r->getValue($stream));
        $this->assertEquals(52845, $this->c1->getValue($stream));
        $this->assertEquals(22719, $this->c2->getValue($stream));
        $this->assertEquals('aaaa', $this->seeds->getValue($stream));
        $this->assertSame($out, $this->out->getValue($stream));
    }

    /**
     * @dataProvider getDataForTestEexecBinEncrypt
     */
    public function testEexecBinEncrypt($decrypted, $expected, $count)
    {
        $decryptedInput = new AsciiHexadecimalToBinaryInputStream(new StringInputStream($decrypted));
        $expectedInput = new AsciiHexadecimalToBinaryInputStream(new StringInputStream($expected));
        $decryptedInput->read($decryptedBin, strlen($decrypted));
        $expectedInput->read($expectedBin, strlen($expected));

        $out = new StringOutputStream();
        $stream = new EncryptOutputStream($out, 55665, '0000');

        $this->assertEquals($count, $this->output->invoke($stream, $decryptedBin));
        $this->assertEquals($expectedBin, $out->__toString());
    }

    public function getDataForTestEexecBinEncrypt()
    {
        return [
            ['68 65 6C 6C 6F', 'E9 8D 09 D7 6C E6 99 52 F0', 9],
        ];
    }

    /**
     * @dataProvider getDataForTestCharStringBinEncrypt
     */
    public function testOutput($decrypted, $expected, $count)
    {
        $decryptedInput = new AsciiHexadecimalToBinaryInputStream(new StringInputStream($decrypted));
        $expectedInput = new AsciiHexadecimalToBinaryInputStream(new StringInputStream($expected));

        $decryptedInput->read($decryptedBin, strlen($decrypted));
        $expectedInput->read($expectedBin, strlen($expected));

        $out = new StringOutputStream();
        $stream = new EncryptOutputStream($out, 4330, "\x00\x00\x00\x00");
        $this->assertEquals($count, $this->output->invoke($stream, $decryptedBin));
        $this->assertEquals($expectedBin, $out->__toString());
    }

    public function getDataForTestCharStringBinEncrypt()
    {
        return [
            ["68 65 6c 6c 6F", "10 BF 31 70 9A A9 E3 3D EE", 9]
        ];
    }

    /**
     * @dataProvider getDataForTestEexecBinEncryptWithFile
     */
    public function testEexecBinEncryptWithFile($binSource, $plainSource, $length)
    {
        $binSource = $this->base.$binSource;

        $plainSource = $this->base.$plainSource;

        $in = new FileInputStream($plainSource, 'rb');

        $out = new StringOutputStream();

        $stream = new EncryptOutputStream($out, 55665);

        while (-1 !== $in->read($bytes, $length)) {

            $this->output->invoke($stream, $bytes);
        }

        $this->assertEquals($out->__toString(), file_get_contents($binSource));
    }

    public function getDataForTestEexecBinEncryptWithFile()
    {
        return [
            ['eexec-block-encrypted-as-bin-001.txt', 'eexec-block-decrypted-001.txt', 1],
            ['eexec-block-encrypted-as-bin-001.txt', 'eexec-block-decrypted-001.txt', 32],
        ];
    }

    /**
     * @dataProvider getDataForTestEexecHexEncryptWithFile
     */
    public function testEexecHexEncryptWithFile($hexSource, $plainSource, $length)
    {
        $hexSource = $this->base.$hexSource;

        $plainSource = $this->base.$plainSource;

        $in = new FileInputStream($plainSource, 'rb');

        $out = new StringOutputStream();

        $stream = new EncryptOutputStream(new BinaryToAsciiHexadecimalOutputStream($out), 55665);

        while (-1 !== $in->read($bytes, $length)) {

            $this->output->invoke($stream, $bytes);
        }

        $this->assertEquals($out->__toString(), file_get_contents($hexSource));
    }

    public function getDataForTestEexecHexEncryptWithFile()
    {
        return [
            ['eexec-block-encrypted-as-hex-001.txt', 'eexec-block-decrypted-001.txt', 1],
            ['eexec-block-encrypted-as-hex-001.txt', 'eexec-block-decrypted-001.txt', 32],
        ];
    }

    /**
     * @dataProvider getDataForTestCharStringBinEncryptWithFile
     */
    public function testCharStringBinEncryptWithFile($encryptedFile, $decryptedFile, $length)
    {
        $encryptedFile = $this->base.$encryptedFile;

        $decryptedFile = $this->base.$decryptedFile;

        $encryptedLineInput = new LineInputStream(new FileInputStream($encryptedFile, 'rb'));

        $decryptedLineInput = new LineInputStream(new FileInputStream($decryptedFile, 'rb'));

        while (-1 !== ($encryptedLineInput->read($expectedHex, $length)) && -1 !== ($decryptedLineInput->read($decryptedHex, $length))) {

            $out = new StringOutputStream();
            $encryptor = new EncryptOutputStream($out, 4330, "\x00\x00\x00\x00");

            $hex2bin = new AsciiHexadecimalToBinaryInputStream(new StringInputStream($decryptedHex));
            $hex2bin->read($decryptedBin, strlen($decryptedHex) / 2);

            $hex2bin = new AsciiHexadecimalToBinaryInputStream(new StringInputStream($expectedHex));
            $hex2bin->read($expectedBin, strlen($expectedHex) / 2);

            $this->assertEquals($this->output->invoke($encryptor, $decryptedBin), $out->size());
            $this->assertEquals($expectedBin, $out->__toString());
        }
    }

    public function getDataForTestCharStringBinEncryptWithFile()
    {
        return [
            ['charstring-encrypted-as-hex-001.txt', 'charstring-decrypted-to-encoded-hex-001.txt', 16],
        ];
    }
}
