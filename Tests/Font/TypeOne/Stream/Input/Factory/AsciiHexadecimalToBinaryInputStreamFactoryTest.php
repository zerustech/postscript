<?php

/**
 * This file is part of the ZerusTech package.
 *
 * (c) Michael Lee <michael.lee@zerustech.com>
 *
 * For full copyright and license information, please view the LICENSE file that
 * wsa distributed with this source code.
 */

namespace ZerusTech\Component\Postscript\Tests\Font\TypeOne\Stream\Input\Factory;

use ZerusTech\Component\IO\Exception\IOException;
use ZerusTech\Component\IO\Stream\Input\BufferedInputStream;
use ZerusTech\Component\IO\Stream\Input\StringInputStream;
use ZerusTech\Component\IO\Stream\Input\FileInputStream;
use ZerusTech\Component\IO\Stream\Input\AsciiHexadecimalToBinaryInputStream;
use ZerusTech\Component\Postscript\Font\TypeOne\Stream\Input\Factory\AsciiHexadecimalToBinaryInputStreamFactory;

/**
 * Test case for ascii hexadecimal to binary input stream factory.
 *
 * @author Michael Lee <michael.lee@zerustech.com>
 */
class AsciiHexadecimalToBinaryInputStreamFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function setup()
    {
        $this->ref = new \ReflectionClass('ZerusTech\Component\Postscript\Font\TypeOne\Stream\Input\Factory\AsciiHexadecimalToBinaryInputStreamFactory');
        $this->eexecTest = $this->ref->getMethod('eexecTest');
        $this->eexecTest->setAccessible(true);
        $this->factory = new AsciiHexadecimalToBinaryInputStreamFactory();

        $this->base = __DIR__.'/../../../../../Fixtures/Font/TypeOne/';
    }

    public function tearDown()
    {
        $this->factory = null;
        $this->eexecTest = null;
        $this->ref = null;

        $this->base = null;
    }

    public function testCreate()
    {
        $stream = $this->factory->create(new StringInputStream('hello'));

        $this->assertInstanceOf('ZerusTech\Component\Postscript\Font\TypeOne\Stream\Input\AsciiHexadecimalToBinaryInputStream', $stream);
    }

    /**
     * @dataProvider getDataForTestSupport
     */
    public function testSupport($binary, $expected)
    {
        $input = new BufferedInputStream(new StringInputStream(bin2hex($binary)));

        $this->assertEquals($this->factory->support($input), $expected);
    }

    public function getDataForTestSupport()
    {
        return [
            ["GHIJ", true],
            [" GHI", false],
            ["0123", false],
        ];
    }

    /**
     * @dataProvider getDataForTestSupportWithFile
     */
    public function testSupportWithFile($file, $expected)
    {
        $file = $this->base.$file;

        $input = new BufferedInputStream(new FileInputStream($file, 'rb'));

        $this->assertEquals($this->factory->support($input), $expected);
    }

    public function getDataForTestSupportWithFile()
    {
        return [
            ["eexec-block-encrypted-as-hex-001.txt", true],
            ["eexec-block-encrypted-as-bin-001.txt", false],
        ];
    }

    /**
     * @expectedException ZerusTech\Component\IO\Exception\IOException
     * @expectedExceptionMessageRegex /Class [^ ]+ does not support mark()./
     */
    public function testSupportWithException()
    {
        $this->factory->support(new StringInputStream(bin2hex("GHIJ")));
    }

    /**
     * @dataProvider getDataForTestSupport
     */
    public function testEexecTest($binary, $expected)
    {
        $this->assertEquals($expected, $this->eexecTest->invoke($this->factory, $binary));
    }
}
