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

use ZerusTech\Component\Postscript\Font\TypeOne\Stream\Input\CharStringDecodeInputStream;
use ZerusTech\Component\Postscript\Font\TypeOne\Stream\Input\AsciiHexadecimalToBinaryInputStream;
use ZerusTech\Component\IO\Stream\Input\StringInputStream;
use ZerusTech\Component\IO\Stream\Input\FileInputStream;
use ZerusTech\Component\IO\Stream\Input\InputStreamInterface;
use ZerusTech\Component\IO\Stream\Input\LineInputStream;
use ZerusTech\Component\IO\Stream\Output\StringOutputStream;
use ZerusTech\Component\IO\Stream\Output\FileOutputStream;

/**
 * Test case for char string decode input stream.
 *
 * @author Michael Lee <michael.lee@zerustech.com>
 */
class CharStringDecodeInputStreamTest extends \PHPUnit_Framework_TestCase
{
    public function setup()
    {
        $this->ref = new \ReflectionClass('ZerusTech\Component\Postscript\Font\TypeOne\Stream\Input\CharStringDecodeInputStream');

        $this->buffer = $this->ref->getProperty('buffer');
        $this->buffer->setAccessible(true);

        $this->commands = $this->ref->getProperty('commands');
        $this->commands->setAccessible(true);

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
        $this->commands = null;
        $this->buffer = null;
        $this->ref = null;
    }

    public function testConstructor()
    {
        $in = new StringInputStream('hello');
        $stream = new CharStringDecodeInputStream($in);

        $commands = [
            1 => 'hstem',
            3 => 'vstem',
            4 => 'vmoveto',
            5 => 'rlineto',
            6 => 'hlineto',
            7 => 'vlineto',
            8 => 'rrcurveto',
            9 => 'closepath',
            10 => 'callsubr',
            11 => 'return',
            13 => 'hsbw',
            14 => 'endchar',
            21 => 'rmoveto',
            22 => 'hmoveto',
            30 => 'vhcurveto',
            31 => 'hvcurveto',
            12 => [
            0 => 'dotsection',
            1 => 'vstem3',
            2 => 'hstem3',
            6 => 'seac',
            7 => 'sbw',
            12 => 'div',
            16 => 'callothersubr',
            17 => 'pop',
            33 => 'setcurrentpoint',
            ],
        ];

        $this->assertEquals('', $this->buffer->getValue($stream));
        $this->assertSame($in, $this->in->getValue($stream));
        $this->assertEquals($commands, $this->commands->getValue($stream));
    }

    /**
     * @dataProvider getDataForTestInput
     */
    public function testInput($encoded, $offset, $length, $expected, $count, $skipped, $available)
    {
        $in = new AsciiHexadecimalToBinaryInputStream(new StringInputStream($encoded));

        $stream = new CharStringDecodeInputStream($in);

        $this->assertEquals($skipped, $stream->skip($offset));

        $this->assertEquals($count, $this->input->invokeArgs($stream, [&$bytes, $length]));

        $this->assertEquals($expected, trim($bytes));

        $this->assertEquals($available, $stream->available());
    }

    public function getDataForTestInput()
    {
        return [
            ['20 27 8B EF F6', 0, 15, '-107 -100 0 100 107', 15, 0, 0],
            ['F700 F888 FAFF', 0, 10, '108 500 1131', 10, 0, 0],
            ['FEFF FC88 FB00', 0, 13, '-1131 -500 -108', 13, 0, 0],
            ['FFFFFF63C0 FFFFFF82FF FFFFFF8300 FFFFFFFB94 FF0000046C FF00007D00 FF00007D01 FF00009C40', 0, 42, '-40000 -32001 -32000 -1132 1132 32000 32001 40000', 42, 0, 0],
            ['01 03 04 05 06 07 08 09 0A 0B 0D 0E 15 16 1E 1F 0C00 0C01 0C02 0C06 0C07 0C0C 0C10 0C11 0C21', 0, 176,
             'hstem vstem vmoveto rlineto hlineto vlineto rrcurveto closepath callsubr return hsbw endchar rmoveto hmoveto vhcurveto hvcurveto dotsection vstem3 hstem3 seac sbw div callothersubr pop setcurrentpoint', 176, 0, 0],
            ['20 27 8B EF F6', 0, 1, '-107', 4, 0, 1],
            ['20 27 8B EF F6', 0, 4, '-107', 4, 0, 1],
            ['20 27 8B EF F6', 0, 5, '-107 -100', 8, 0, 1],
            ['20 27 8B EF F6', 1, 11, '-100 0 100 107', 11, 4, 0],
            ['20 27 8B EF F6', 1, 12, '-100 0 100 107', 11, 4, 0],
            ['20 27 8B EF F6', 15, 1, '', -1, 15, 0],
        ];
    }

    /**
     * @dataProvider getDataForTestInputWithFile
     */
    public function testInputWithFile($decodedFile, $encodedFile, $length)
    {
        $decodedFile = $this->base.$decodedFile;

        $encodedFile = $this->base.$encodedFile;

        $encodedLineInput = new LineInputStream(new FileInputStream($encodedFile, 'rb'));

        $decodedLineInput = new LineInputStream(new FileInputStream($decodedFile, 'rb'));

        while (-1 !== ($encodedLineInput->read($encoded, $length)) && -1 !== ($decodedLineInput->read($expected, $length))) {

            $stream = new CharStringDecodeInputStream(new AsciiHexadecimalToBinaryInputStream(new StringInputStream($encoded)));

            $decoded = '';

            while (-1 !== $this->input->invokeArgs($stream, [&$bytes, $length])) {

                $decoded .= $bytes;
            }

            $this->assertEquals(trim($expected), trim($decoded));
        }
    }

    public function getDataForTestInputWithFile()
    {
        return [
            ['charstring-decrypted-to-decoded-001.txt', 'charstring-decrypted-to-encoded-hex-001.txt', 1],
            ['charstring-decrypted-to-decoded-001.txt', 'charstring-decrypted-to-encoded-hex-001.txt', 16],
        ];
    }
}
