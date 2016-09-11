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
     * @dataProvider getDataForTestCharStringBinEncrypt
     */
    public function testOutput($decrypted, $expected, $R, $seeds, $count)
    {
        $out = new StringOutputStream();
        $stream = new EncryptOutputStream($out, $R, $seeds);
        $this->assertEquals($count, $this->output->invoke($stream, $decrypted));
        $this->assertEquals($expected, $out->__toString());
    }

    public function getDataForTestCharStringBinEncrypt()
    {
        return [
            ["hello", "\x10\xbf\x31\x70\x9a\xa9\xe3\x3d\xee", 4330, "\x00\x00\x00\x00", 9],
            ['hello', "\xe9\x8d\x09\xd7\x6c\xe6\x99\x52\xf0", 55665, "0000", 9],
        ];
    }

    /**
     * @dataProvider getDataForTestEexecBinEncryptWithFile
     */
    public function testEexecBinEncryptWithFile($plainFile, $expectedFile, $length)
    {
        $expectedFile = $this->base.$expectedFile;

        $plainFile = $this->base.$plainFile;

        $plainInput = new FileInputStream($plainFile, 'rb');

        $out = new StringOutputStream();

        $stream = new EncryptOutputStream($out, 55665);

        while (-1 !== $plainInput->read($bytes, $length)) {

            $this->output->invoke($stream, $bytes);
        }

        $this->assertEquals(file_get_contents($expectedFile), $out->__toString());
    }

    public function getDataForTestEexecBinEncryptWithFile()
    {
        return [
            ['eexec-block-decrypted-001.txt', 'eexec-block-encrypted-as-bin-001.txt', 1],
            ['eexec-block-decrypted-001.txt', 'eexec-block-encrypted-as-bin-001.txt', 32],
        ];
    }

    /**
     * @dataProvider getDataForTestCharStringBinEncryptWithFile
     */
    public function testCharStringBinEncryptWithFile($plainFile, $expectedFile, $length)
    {
        $expectedFile = $this->base.$expectedFile;

        $plainFile = $this->base.$plainFile;

        $expectedLineInput = new LineInputStream(new FileInputStream($expectedFile, 'rb'));

        $plainLineInput = new LineInputStream(new FileInputStream($plainFile, 'rb'));

        while (null !== ($expectedHex = $expectedLineInput->readLine()) && null !== ($plainHex = $plainLineInput->readLine())) {

            $out = new StringOutputStream();
            $stream = new EncryptOutputStream($out, 4330, "\x00\x00\x00\x00");
            $this->assertEquals($this->output->invoke($stream, hex2bin(trim($plainHex))), $out->size());
            $this->assertEquals(hex2bin(trim($expectedHex)), $out->__toString());
        }
    }

    public function getDataForTestCharStringBinEncryptWithFile()
    {
        return [
            [ 'charstring-decrypted-to-encoded-hex-001.txt', 'charstring-encrypted-as-hex-001.txt', 16],
        ];
    }
}
