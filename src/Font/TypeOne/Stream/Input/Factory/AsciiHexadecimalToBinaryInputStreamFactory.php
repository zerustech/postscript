<?php
/**
 * This file is part of the ZerusTech package.
 *
 * (c) Michael Lee <michael.lee@zerustech.com>
 *
 * For full copyright and license information, please view the LICENSE file that
 * was distributed with this source code.
 */

namespace ZerusTech\Component\Postscript\Font\TypeOne\Stream\Input\Factory;

use ZerusTech\Component\IO\Exception\IOException;
use ZerusTech\Component\IO\Stream\Input\InputStreamInterface;
use ZerusTech\Component\IO\Stream\Input\StringInputStream;
use ZerusTech\Component\IO\Stream\Input\Factory\FilterInputStreamFactoryInterface;
use ZerusTech\Component\Postscript\Font\TypeOne\Stream\Input\AsciiHexadecimalToBinaryInputStream;

/**
 * This class creates input stream that converts data read from hexadecimal
 * format to binary format.
 *
 * It detects data format of the input stream by peeking a few leading bytes.
 *
 * @author Michael Lee <michael.lee@zerustech.com>
 */
class AsciiHexadecimalToBinaryInputStreamFactory implements FilterInputStreamFactoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function create(InputStreamInterface $input)
    {
        return new AsciiHexadecimalToBinaryInputStream($input);
    }

    /**
     * {@inheritdoc}
     */
    public function support(InputStreamInterface $input)
    {
        if (false === $input->markSupported()) {

            throw new IOException(sprintf("Class %s does not support mark().", get_class($input)));
        }

        $input->mark(9);

        $input->read($bytes, 8);

        $input->reset();

        $supported = false;

        if (false === $this->eexecTest(substr($bytes, 0, 4))) {

            $buffer = new StringInputStream($bytes);
            $filter = $this->create($buffer);
            $filter->read($bytes, 8);
            $supported = $this->eexecTest($bytes);
        }

        return $supported;
    }

    /**
     * This method returns a boolean that indicates whether the given 4 bytes
     * string complies with the font type one eexec encryption specification.
     *
     * Refer to section 7.2 eexec Encryption of type one font specification for
     * details: {@link https://partners.adobe.com/public/developer/en/font/T1_SPEC.PDF T1_SPEC.PDF}.
     *
     * @param string $bytes The 4 bytes string to be tested against the eexec
     * encryption specification.
     *
     * @return bool True if the given string complies with the spec, or false
     * otherwise.
     */
    private function eexecTest($bytes)
    {
        return 1 !== preg_match("/^[ \t\r\n]$/", $bytes[0]) && 1 === preg_match("/[^0-9a-fA-F]/", $bytes);
    }
}
