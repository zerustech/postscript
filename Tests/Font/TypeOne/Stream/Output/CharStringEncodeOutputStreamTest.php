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

use ZerusTech\Component\IO\Stream\Input\StringInputStream;
use ZerusTech\Component\IO\Stream\Input\FileInputStream;
use ZerusTech\Component\IO\Stream\Input\LineInputStream;
use ZerusTech\Component\IO\Stream\Output\StringOutputStream;
use ZerusTech\Component\Postscript\Font\TypeOne\Stream\Output\CharStringEncodeOutputStream;

/**
 * Test case for char string encode output stream.
 *
 * @author Michael Lee <michael.lee@zerustech.com>
 */
class CharStringEncodeOutputStreamTest extends \PHPUnit_Framework_TestCase
{
    public function setup()
    {
        $this->ref = new \ReflectionClass('ZerusTech\Component\Postscript\Font\TypeOne\Stream\Output\CharStringEncodeOutputStream');

        $this->commands = $this->ref->getProperty('commands');
        $this->commands->setAccessible(true);

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
        $this->commands = null;

        $this->ref = null;
    }

    public function testConstructor()
    {
        $out = new StringOutputStream();
        $stream = new CharStringEncodeOutputStream($out);

        $commands = [
            'hstem' => 1,
            'vstem' => 3,
            'vmoveto' => 4,
            'rlineto' => 5,
            'hlineto' => 6,
            'vlineto' => 7,
            'rrcurveto' => 8,
            'closepath' => 9,
            'callsubr' => 10,
            'return' => 11,
            'hsbw' => 13,
            'endchar' => 14,
            'rmoveto' => 21,
            'hmoveto' => 22,
            'vhcurveto' => 30,
            'hvcurveto' => 31,
            'dotsection' => [12, 0],
            'vstem3' => [12, 1],
            'hstem3' => [12, 2],
            'seac' => [12, 6],
            'sbw' => [12, 7],
            'div' => [12, 12],
            'calltothersubr' => [12, 16],
            'pop' => [12, 17],
            'setcurrentpoint' => [12, 33],
        ];

        $this->assertEquals('', $this->buffer->getValue($stream));
        $this->assertEquals($commands, $this->commands->getValue($stream));
        $this->assertSame($out, $this->out->getValue($stream));
    }

    /**
     * @dataProvider getDataForTestOutput
     */
    public function testOutput($decodedFile, $encodedFile, $length)
    {
        $decodedFile = $this->base.$decodedFile;

        $encodedFile = $this->base.$encodedFile;

        $decodedInput = new LineInputStream(new FileInputStream($decodedFile, 'rb'));

        $encodedInput = new LineInputStream(new FileInputStream($encodedFile, 'rb'));

        while (-1 !== ($decodedInput->read($decoded, $length)) && -1 !== ($encodedInput->read($encoded, $length))) {

            $out = new StringOutputStream();
            $encoder = new CharStringEncodeOutputStream($out);
            $encoder->write($decoded);
            $this->assertEquals(strtoupper(bin2hex($out->__toString())), trim($encoded));
        }
    }

    public function getDataForTestOutput()
    {
        return [
            ['charstring-decrypted-to-decoded-001.txt', 'charstring-decrypted-to-encoded-hex-001.txt', 32]
        ];
    }
}
