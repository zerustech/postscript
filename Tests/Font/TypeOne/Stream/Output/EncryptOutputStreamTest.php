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
    public function testEexecBinEncrypt($binSource, $plainSource, $length)
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

    public function getDataForTestEexecBinEncrypt()
    {
        return [
            ['eexec-block-encrypted-as-bin-001.txt', 'eexec-block-decrypted-001.txt', 1],
            ['eexec-block-encrypted-as-bin-001.txt', 'eexec-block-decrypted-001.txt', 32],
        ];
    }

    /**
     * @dataProvider getDataForTestEexecHexEncrypt
     */
    public function testEexecHexEncrypt($hexSource, $plainSource, $length)
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

    public function getDataForTestEexecHexEncrypt()
    {
        return [
            ['eexec-block-encrypted-as-hex-001.txt', 'eexec-block-decrypted-001.txt', 1],
            ['eexec-block-encrypted-as-hex-001.txt', 'eexec-block-decrypted-001.txt', 32],
        ];
    }

    /**
     * @dataProvider getDataForTestCharStringBinEncrypt
     */
    public function testCharStringBinEncrypt($hexSource, $plainSource, $length)
    {
        $hexSource = $this->base.$hexSource;

        $plainSource = $this->base.$plainSource;

        $hexInput = new AsciiHexadecimalToBinaryInputStream(new LineInputStream(new FileInputStream($hexSource, 'rb')));

        $plainInput = new AsciiHexadecimalToBinaryInputStream(new LineInputStream(new FileInputStream($plainSource, 'rb')));

        while (-1 !== ($hexInput->read($hex, $length)) && -1 !== ($plainInput->read($plain, $length))) {

            $out = new StringOutputStream();
            $encryptor = new EncryptOutputStream($out, 4330, "\x00\x00\x00\x00");
            $this->output->invoke($encryptor, $plain);
            $this->assertEquals($hex, $out->__toString());
        }
    }

    public function getDataForTestCharStringBinEncrypt()
    {
        return [
            ['charstring-encrypted-as-hex-001.txt', 'charstring-decrypted-to-encoded-hex-001.txt', 16],
        ];
    }
}
