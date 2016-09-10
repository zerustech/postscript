<?php

/**
 * This file is part of the ZerusTech package.
 *
 * (c) Michael Lee <michael.lee@zerustech.com>
 *
 * For full copyright and license information, please view the LICENSE file that
 * was distributed with this source code.
 */

namespace ZerusTech\Component\Postscript\Font\TypeOne\Stream\Output;

use ZerusTech\Component\IO\Stream\Output\OutputStreamInterface;
use ZerusTech\Component\IO\Stream\Output\FilterOutputStream;
use ZerusTech\Component\Postscript\Font\TypeOne\Stream\Input\AsciiHexadecimalToBinaryInputStream;

/**
 * This class converts the data provided from ascii hexadecimal format to binary
 * format and writes the converted data to the subordinate stream.
 *
 * @author Michael Lee <michael.lee@zerustech.com>
 */
class AsciiHexadecimalToBinaryOutputStream extends FilterOutputStream
{
    /**
     * @var array The internal buffer that stores unpaired hexadecimal byte,
     * when two hexadecimal bytes are pushed in the buffer, it's ready to be
     * converted to a binary byte.
     */
    private $buffer = [];

    /**
     * This method creates a new ascii hexadecimal to binary output stream.
     */
    public function __construct(OutputStreamInterface $out)
    {
        parent::__construct($out);

        $this->buffer = [];
    }

    /**
     * {@inheritdoc}
     *
     * This method converts `$bytes`` to binary format and writes the converted
     * data to the subordinate stream.
     */
    protected function output($bytes)
    {
        $bin = '';

        for ($i = 0; $i < strlen($bytes); $i++) {

            $this->buffer[] = $bytes[$i];

            if (2 === count($this->buffer)) {

                $bin .= chr(hexdec(array_shift($this->buffer).array_shift($this->buffer)));
            }
        }

        return parent::output($bin);
    }
}
