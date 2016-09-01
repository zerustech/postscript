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
 * (0-9, a-f, or A-F). White-space characters are ignored.
 *
 * For example:
 *
 * In binary format, string 'abc' consists of three bytes (hexadecimal value):
 * 0x61, 0x62 and 0x63
 *
 * While in ascii hexadecimal format, it consists of six bytes:
 * '616263'
 *
 * When converting data into ascii hexadecimal format, the "\n" will be inserted
 * to get fixed-width lines, each pair of hexadecimal bytes represents a column
 * and there are up to 32 columns in a line, by default.
 *
 * @author Michael Lee <michael.lee@zerustech.com>
 */
class BinaryToAsciiHexadecimalOutputStream extends FilterOutputStream
{

    /**
     * @var int The column index of the next hexadecimal pair (0 - 31).
     */
    private $column;

    /**
     * @var bool This boolean indicates whether to format the hexadecimal bytes
     * as fixed-width sequences, true by default.
     */
    private $format;

    /**
     * @var int The number of columns (hexadecimal pairs) in each line.
     */
    private $width = 32;

    /**
     * This method creates a new binary to ascii hexadecimal input stream.
     *
     * @param OutputStreamInterface $out The subordinate output stream.
     * @param int $column The initial column index.
     * @param bool $format A boolean indicates whether to format the hexadecimal
     * string to fixed-width lines.
     * @param int $width The maximum number of columns in a line.
     */
    public function __construct(OutputStreamInterface $out, $column = 0, $format = true, $width = 32)
    {
        parent::__construct($out);

        $this->column = $column;

        $this->format = $format;

        $this->width = $width;
    }

    /**
     * {@inheritdoc}
     *
     * This method converts ``$bytes`` from binary format to ascii hexadecimal
     * format and writes the converted data to its subordinate output stream.
     */
    protected function output($bytes)
    {
        $hex = '';

        for ($i = 0; $i < strlen($bytes); $i++) {

            $hex .= strtoupper((bin2hex($bytes[$i])));

            $this->column = ($this->column + 1) % $this->width;

            if (0 === $this->column && true === $this->format) {

                $hex .= "\n";
            }
        }

        return parent::output($hex);
    }
}
