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

use ZerusTech\Component\Postscript\Font\TypeOne\Stream\Input\CharStringFormatInputStream;
use ZerusTech\Component\IO\Stream\Input\StringInputStream;
use ZerusTech\Component\IO\Stream\Input\FileInputStream;
use ZerusTech\Component\IO\Stream\Output\StringOutputStream;
use ZerusTech\Component\IO\Exception;

/**
 * Test case for char string format input stream.
 *
 * @author Michael Lee <michael.lee@zerustech.com>
 */
class CharStringFormatInputStreamTest extends \PHPUnit_Framework_TestCase
{
    public function setup()
    {
        $this->ref = new \ReflectionClass('ZerusTech\Component\Postscript\Font\TypeOne\Stream\Input\CharStringFormatInputStream');

        $this->input = $this->ref->getMethod('input');
        $this->input->setAccessible(true);

        $this->in = $this->ref->getProperty('in');
        $this->in->setAccessible(true);

        $this->buffer = $this->ref->getProperty('buffer');
        $this->buffer->setAccessible(true);

        $this->base = __DIR__.'/../../../../Fixtures/Font/TypeOne/';
    }

    public function  tearDown()
    {
        $this->base = null;
        $this->buffer = null;
        $this->input = null;
        $this->ref = null;
    }

    /**
     * @dataProvider getDataForTestFormat
     */
    public function testFormat($source, $expected)
    {
        $stream = new CharStringFormatInputStream(new StringInputStream($source));

        for ($i = 0; $i < count($expected); $i++) {

            $this->assertEquals($expected[$i], $stream->format());
        }
    }

    public function getDataForTestFormat()
    {
        return [
            ['0 2 callothersubr ', ["0 2 callothersubr\n\t"]],
            ['-45 3 -10 10 -35 74 rrcurveto -249 568 rlineto ', ["-45 3 -10 10 -35 74 rrcurveto\n\t", "-249 568 rlineto\n\t"]],
            ['-45 3 -10 10 -35 74 rrcurveto -249 568 rlineto 100 ', ["-45 3 -10 10 -35 74 rrcurveto\n\t", "-249 568 rlineto\n\t", "100"]],
            ['', [null]]
        ];
    }
}
