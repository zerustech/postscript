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
use ZerusTech\Component\IO\Stream\Input\WashInputStream;
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

    /**
     * @dataProvider getDataForTestReadToken
     */
    public function testReadToken($encoded, $expected, $available)
    {
        $in = new AsciiHexadecimalToBinaryInputStream(new WashInputStream(new StringInputStream($encoded)));

        $stream = new CharStringDecodeInputStream($in);

        foreach (explode(' ', $expected) as $token) {

            $this->assertEquals($token, $stream->readToken());
        }
    }

    public function getDataForTestReadToken()
    {
        return [
            ['20 27 8B EF F6', '-107 -100 0 100 107', 0],
            ['F700 F888 FAFF', '108 500 1131', 0],
            ['FEFF FC88 FB00', '-1131 -500 -108', 0],
            ['FFFFFF63C0 FFFFFF82FF FFFFFF8300 FFFFFFFB94 FF0000046C FF00007D00 FF00007D01 FF00009C40', '-40000 -32001 -32000 -1132 1132 32000 32001 40000', 0],
            ['01 03 04 05 06 07 08 09 0A 0B 0D 0E 15 16 1E 1F 0C00 0C01 0C02 0C06 0C07 0C0C 0C10 0C11 0C21',
             'hstem vstem vmoveto rlineto hlineto vlineto rrcurveto closepath callsubr return hsbw endchar rmoveto hmoveto vhcurveto hvcurveto dotsection vstem3 hstem3 seac sbw div callothersubr pop setcurrentpoint ', 0],
            ['20 27 8B EF F6', '-107', 1],
            ['20 27 8B EF F6', '-107 -100', 1],
        ];
    }

    /**
     * @dataProvider getDataForTestReadTokenWithFile
     */
    public function testInputWithFile($encodedFile, $expectedFile, $length)
    {
        $expectedFile = $this->base.$expectedFile;

        $encodedFile = $this->base.$encodedFile;

        $encodedLineInput = new LineInputStream(new FileInputStream($encodedFile, 'rb'));

        $expectedLineInput = new LineInputStream(new FileInputStream($expectedFile, 'rb'));

        while (null !== ($encoded = $encodedLineInput->readLine()) && null !== ($expected = $expectedLineInput->readLine())) {

            $stream = new CharStringDecodeInputStream(new AsciiHexadecimalToBinaryInputStream(new WashInputStream(new StringInputStream($encoded))));

            foreach (explode(' ', trim($expected)) as $token) {

                $this->assertEquals($token, $stream->readToken());
            }
        }
    }

    public function getDataForTestReadTokenWithFile()
    {
        return [
            ['charstring-decrypted-to-encoded-hex-001.txt', 'charstring-decrypted-to-decoded-001.txt', 1],
            ['charstring-decrypted-to-encoded-hex-001.txt', 'charstring-decrypted-to-decoded-001.txt', 16],
        ];
    }
}
