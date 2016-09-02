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

use ZerusTech\Component\Postscript\Font\TypeOne\Stream\Output\CharStringEncryptOutputStream;
use ZerusTech\Component\Postscript\Font\TypeOne\Stream\Input\AsciiHexadecimalToBinaryInputStream;
use ZerusTech\Component\IO\Stream\Input\StringInputStream;
use ZerusTech\Component\IO\Stream\Input\FileInputStream;
use ZerusTech\Component\IO\Stream\Input\LineInputStream;
use ZerusTech\Component\IO\Stream\Output\OutputStreamInterface;
use ZerusTech\Component\IO\Stream\Output\StringOutputStream;

/**
 * Test case for char string encrypt output stream.
 *
 * @author Michael Lee <michael.lee@zerustech.com>
 */
class CharStringEncryptOutputStreamTest extends \PHPUnit_Framework_TestCase
{
    public function setup()
    {
        $this->ref = new \ReflectionClass('ZerusTech\Component\Postscript\Font\TypeOne\Stream\Output\CharStringEncryptOutputStream');

        $this->r = $this->ref->getProperty('r');
        $this->r->setAccessible(true);

        $this->R = $this->ref->getProperty('R');
        $this->R->setAccessible(true);

        $this->c1 = $this->ref->getProperty('c1');
        $this->c1->setAccessible(true);

        $this->c2 = $this->ref->getProperty('c2');
        $this->c2->setAccessible(true);

        $this->out = $this->ref->getProperty('out');
        $this->out->setAccessible(true);

        $this->seeds = $this->ref->getProperty('seeds');
        $this->seeds->setAccessible(true);

        $this->output = $this->ref->getMethod('output');
        $this->output->setAccessible(true);

        $this->base = __DIR__.'/../../../../Fixtures/Font/TypeOne/';
    }

    public function tearDown()
    {
        $this->output = null;
        $this->seeds = null;
        $this->out = null;
        $this->c2 = null;
        $this->c1 = null;
        $this->R = null;
        $this->r = null;
        $this->ref = null;
    }

    public function testConstructor()
    {
        $out = new StringOutputStream();
        $stream = new CharStringEncryptOutputStream($out, "aaaa");

        $this->assertEquals(4330, $this->R->getValue($stream));
        $this->assertEquals(4330, $this->r->getValue($stream));
        $this->assertEquals(52845, $this->c1->getValue($stream));
        $this->assertEquals(22719, $this->c2->getValue($stream));
        $this->assertEquals("aaaa", $this->seeds->getValue($stream));
        $this->assertSame($out, $this->out->getValue($stream));
    }

    /**
     * @dataProvider getDataForTestOutput
     */
    public function testOutput($cipherFile, $plainFile, $seeds, $length)
    {
        $cipherFile = $this->base.$cipherFile;

        $plainFile = $this->base.$plainFile;

        $cipherInput = new LineInputStream(new FileInputStream($cipherFile, 'rb'));

        $plainInput = new AsciiHexadecimalToBinaryInputStream(new LineInputStream(new FileInputStream($plainFile, 'rb')));

        while (-1 !== ($cipherInput->read($cipherHex, $length)) && -1 !== ($plainInput->read($plainBin, $length))) {

            $out = new StringOutputStream();
            $encryptor = new CharStringEncryptOutputStream($out, $seeds);
            $this->output->invoke($encryptor, $plainBin);
            $this->assertEquals(trim($cipherHex), strtoupper(bin2hex($out->__toString())));
        }
    }

    public function getDataForTestOutput()
    {
        return [
            ['charstring-encrypted-as-hex-001.txt', 'charstring-decrypted-to-encoded-hex-001.txt', "\x00\x00\x00\x00", 1],
            ['charstring-encrypted-as-hex-001.txt', 'charstring-decrypted-to-encoded-hex-001.txt', "\x00\x00\x00\x00", 32],
        ];
    }
}
