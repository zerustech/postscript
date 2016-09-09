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
            'callothersubr' => [12, 16],
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
    public function testOutput($decoded, $encoded, $count)
    {
        $out = new StringOutputStream();
        $stream = new CharStringEncodeOutputStream($out);
        $this->assertEquals($count, $this->output->invoke($stream, $decoded));
        $this->assertEquals(strtoupper(bin2hex($out->__toString())), str_replace(' ', '', trim($encoded)));
    }

    public function getDataForTestOutput()
    {
        return [
            ["-107 -100 0 100 107", "20 27 8B EF F6", 5],
            ["108 500 1131", "F700 F888 FAFF", 6],
            ["-1131 -500 -108", "FEFF FC88 FB00", 6],
            ["-40000 -32001 -32000 -1132 1132 32000 32001 40000", "FFFFFF63C08D0C0C FFFFFF82FF8D0C0C FFFFFF8300 FFFFFFFB94 FF0000046C FF00007D00 FF00007D018D0C0C FF00009C408D0C0C", 52],
            ["hstem vstem vmoveto rlineto hlineto vlineto rrcurveto closepath callsubr return hsbw endchar rmoveto hmoveto vhcurveto hvcurveto dotsection vstem3 hstem3 seac sbw div callothersubr pop setcurrentpoint",
            "01 03 04 05 06 07 08 09 0A 0B 0D 0E 15 16 1E 1F 0C00 0C01 0C02 0C06 0C07 0C0C 0C10 0C11 0C21", 34],
        ];
    }

    /**
     * @dataProvider getDataForTestOutputWithFile
     */
    public function testOutputWithFile($decodedFile, $encodedFile, $length)
    {
        $decodedFile = $this->base.$decodedFile;

        $encodedFile = $this->base.$encodedFile;

        $decodedLineInput = new LineInputStream(new FileInputStream($decodedFile, 'rb'));

        $encodedLineInput = new LineInputStream(new FileInputStream($encodedFile, 'rb'));

        while (-1 !== ($decodedLineInput->read($decoded, $length)) && -1 !== ($encodedLineInput->read($expected, $length))) {

            $out = new StringOutputStream();
            $stream = new CharStringEncodeOutputStream($out);
            $this->assertEquals($this->output->invoke($stream, $decoded), $out->size());
            $this->assertEquals(trim($expected), strtoupper(bin2hex($out->__toString())));
        }
    }

    public function getDataForTestOutputWithFile()
    {
        return [
            ['charstring-decrypted-to-decoded-001.txt', 'charstring-decrypted-to-encoded-hex-001.txt', 1],
            ['charstring-decrypted-to-decoded-001.txt', 'charstring-decrypted-to-encoded-hex-001.txt', 32]
        ];
    }
}
