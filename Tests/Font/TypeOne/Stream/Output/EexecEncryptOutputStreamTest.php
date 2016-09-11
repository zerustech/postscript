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

use ZerusTech\Component\Postscript\Font\TypeOne\Stream\Output\EexecEncryptOutputStream;
use ZerusTech\Component\Postscript\Font\TypeOne\Stream\Output\BinaryToAsciiHexadecimalOutputStream;
use ZerusTech\Component\Postscript\Font\TypeOne\Stream\Input\AsciiHexadecimalToBinaryInputStream;
use ZerusTech\Component\IO\Stream\Input\StringInputStream;
use ZerusTech\Component\IO\Stream\Input\FileInputStream;
use ZerusTech\Component\IO\Stream\Output\OutputStreamInterface;
use ZerusTech\Component\IO\Stream\Output\StringOutputStream;

/**
 * Test case for abstract encryptor.
 *
 * @author Michael Lee <michael.lee@zerustech.com>
 */
class EexecDecryptOutputStreamTest extends \PHPUnit_Framework_TestCase
{
    public function setup()
    {
        $this->ref = new \ReflectionClass('ZerusTech\Component\Postscript\Font\TypeOne\Stream\Output\EexecEncryptOutputStream');

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
        $this->in = null;
        $this->c2 = null;
        $this->c1 = null;
        $this->R = null;
        $this->r = null;
        $this->ref = null;
    }

    public function testConstructor()
    {
        $out = new StringOutputStream();
        $stream = new EexecEncryptOutputStream($out, "aaaa");

        $this->assertEquals(55665, $this->R->getValue($stream));
        $this->assertEquals(55665, $this->r->getValue($stream));
        $this->assertEquals(52845, $this->c1->getValue($stream));
        $this->assertEquals(22719, $this->c2->getValue($stream));
        $this->assertEquals("aaaa", $this->seeds->getValue($stream));
        $this->assertSame($out, $this->out->getValue($stream));
    }

    /**
     * @dataProvider getDataForTestOutput
     */
    public function testOutput($decrypted, $expected, $seeds, $count)
    {
        $out = new StringOutputStream();
        $stream = new EexecEncryptOutputStream($out, $seeds);

        $this->assertEquals($count, $this->output->invoke($stream, $decrypted));
        $this->assertEquals($expected, $out->__toString());
    }

    public function getDataForTestOutput()
    {
        return [
            ['hello', "\xe9\x8d\x09\xd7\x6c\xe6\x99\x52\xf0", '0000', 9],
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

        $stream = new EexecEncryptOutputStream($out);

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
}
