<?php

/**
 * This file is part of the ZerusTech package.
 *
 * (c) Michael Lee <michael.lee@zerustech.com>
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with the source code.
 */

namespace ZerusTech\Component\Postscript\Font\TypeOne\Stream\Output;

use ZerusTech\Component\IO\Stream\Output\FilterOutputStream;
use ZerusTech\Component\IO\Stream\Output\OutputStreamInterface;

/**
 * This class converts binary data to ascii hexadecimal prior to writing the
 * data to the subordinate output stream.
 *
 * In binary format, each byte represents a binary byte. In ascii hexadecimal
 * format, each binary byte is presented by a pair of hexadecimal characters
 * (0-9, a-f, or A-F).
 *
 * For example:
 *
 * In binary format, string 'abc' consists of three bytes (hexadecimal value):
 * 0x61, 0x62 and 0x63
 *
 * While in ascii hexadecimal format, it consists of six bytes:
 * '616263'
 *
 * @author Michael Lee <michael.lee@zerustech.com>
 */
class BinaryToAsciiHexadecimalOutputStream extends FilterOutputStream
{
    /**
     * This method creates a new binary to ascii hexadecimal output stream.
     *
     * @param OutputStreamInterface $out The subordinate output stream.
     */
    public function __construct(OutputStreamInterface $out)
    {
        parent::__construct($out);
    }

    /**
     * {@inheritdoc}
     *
     * This method converts ``$bytes`` from binary format to ascii hexadecimal
     * format and writes the converted data to its subordinate output stream.
     */
    protected function output($bytes)
    {
        return parent::output(strtoupper(bin2hex($bytes)));
    }
}
