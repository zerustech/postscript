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

use ZerusTech\Component\Postscript\Font\TypeOne\Stream\Input\PfbToDisasmInputStream;
use ZerusTech\Component\Postscript\Font\TypeOne\Stream\Input\AsciiHexadecimalToBinaryInputStream;
use ZerusTech\Component\IO\Stream\Input\StringInputStream;
use ZerusTech\Component\IO\Stream\Input\FileInputStream;
use ZerusTech\Component\IO\Stream\Output\StringOutputStream;
use ZerusTech\Component\IO\Exception;

/**
 * Test case for pfb to disasm input stream.
 *
 * @author Michael Lee <michael.lee@zerustech.com>
 */
class PfbToDisasmInputStreamTest extends \PHPUnit_Framework_TestCase
{
    public function setup()
    {
        $this->ref = new \ReflectionClass('ZerusTech\Component\Postscript\Font\TypeOne\Stream\Input\PfbToDisasmInputStream');

        $this->convertToHex = $this->ref->getProperty('convertToHex');
        $this->convertToHex->setAccessible(true);

        $this->width = $this->ref->getProperty('width');
        $this->width->setAccessible(true);

        $this->readBufferSize = $this->ref->getProperty('readBufferSize');
        $this->readBufferSize->setAccessible(true);

        $this->pipe = $this->ref->getProperty('pipe');
        $this->pipe->setAccessible(true);

        $this->line = $this->ref->getProperty('line');
        $this->line->setAccessible(true);

        $this->base = __DIR__.'/../../../../Fixtures/Font/TypeOne/';
    }

    public function tearDown()
    {
        $this->base = null;
        $this->line = null;
        $this->pipe = null;
        $this->readBufferSize = null;
        $this->width = null;
        $this->convertToHex = null;
        $this->ref = null;
    }

    /**
     * @dataProvider getDataForTestConstructor
     */
    public function testConstructor($readBufferSize)
    {
        $in = new FileInputStream($this->base.'NimbusRomanNo9L-Regular.pfb', 'rb');

        $stream = new PfbToDisasmInputStream($in, $readBufferSize);

        $this->assertEquals(false, $this->convertToHex->getValue($stream));
        $this->assertEquals(32, $this->width->getValue($stream));
        $this->assertEquals($readBufferSize, $this->readBufferSize->getValue($stream));
    }

    public function getDataForTestConstructor()
    {
        return [
            [32],
            [64],
        ];
    }

    /**
     * @dataProvider getDataForTestReadBlock
     */
    public function testReadBlock($pfb, $readBufferSize, $expected)
    {
        $stream = new PfbToDisasmInputStream(new StringInputStream($pfb), $readBufferSize);

        for ($i = 0; $i < count($expected); $i++) {

            $block = $stream->readBlock();

            $this->assertEquals($expected[$i], $block);
        }
    }

    public function getDataForTestReadBlock()
    {

        $data = [];

        $data[] = ["\x80\x01\x05\x00\x00\x00\x68\x65\x6c\x6c\x6f", 4, ["hello"]];

        $data[] = ["\x80\x02\x09\x00\x00\x00\xe9\x8d\x09\xd7\x6c\xe6\x99\x52\xf0", 4, ["hello"]];
        $pfb = <<<EOF
\x80\x02\x22\x00\x00\x00\xd9\xd6\x6f\x63\x3b\x84\x6a\x98\x84\x1d\x1b\xe3\x20\x0c\xD9\xb5\xe6\x43\x28\xe0\x36\x61\x69\x9d\x12\x54\x22\xd2\xa2\xc9\x56\x7e\x06\xd9
EOF;
        $data[] = [$pfb, 4, ["dup 0 {\n\t3 0 callothersubr\n\tpop\n\tpop\n\tsetcurrentpoint\n\treturn\n\t}NP\n"]];

        $data[] = ["\x80\x03", 4, [null]];

        $pfb = <<<EOF
\x80\x01\x05\x00\x00\x00\x68\x65\x6c\x6c\x6f\x80\x02\x22\x00\x00\x00\xd9\xd6\x6f\x63\x3b\x84\x6a\x98\x84\x1d\x1b\xe3\x20\x0c\xD9\xb5\xe6\x43\x28\xe0\x36\x61\x69\x9d\x12\x54\x22\xd2\xa2\xc9\x56\x7e\x06\xd9\x80\x02\x09\x00\x00\x00\xe9\x8d\x09\xd7\x6c\xe6\x99\x52\xf0\x80\x03
EOF;

        $data[] = [$pfb, 4, ["hello", "dup 0 {\n\t3 0 callothersubr\n\tpop\n\tpop\n\tsetcurrentpoint\n\treturn\n\t}NP\n", "hello", null]];

        return $data;
    }

    /**
     * @dataProvider getDataForTestReadBlockWithFile
     */
    public function testReadBlockWithFile($pfbFile, $expectedFile)
    {
        $pfbFile = $this->base.$pfbFile;

        $expectedFile = $this->base.$expectedFile;

        $stream = new PfbToDisasmInputStream(new FileInputStream($pfbFile, 'rb'));

        $output = new StringOutputStream();

        while (null !== $block = $stream->readBlock()) {

            $output->write($block);
        }

        $this->assertEquals(file_get_contents($expectedFile), $output->__toString());
    }

    public function getDataForTestReadBlockWithFile()
    {
        return [
            ['NimbusRomanNo9L-Regular.pfb','NimbusRomanNo9L-Regular.disasm'],
        ];
    }
}
