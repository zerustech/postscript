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
 * This class formats the provided hexadecimal bytes to fixed-width lines by
 * inserting "\n" at the end of each line and writes the bytes to the
 * subordinate output stream.
 *
 * In a hexadecimal line, each pair of hexadecimal bytes represents a column
 * and by default, there are up to 32 columns in a line.
 *
 * @author Michael Lee <michael.lee@zerustech.com>
 */
class AsciiHexadecimalFormatOutputStream extends FilterOutputStream
{
    /**
     * @var int The column index of the next hexadecimal pair (0 - 31).
     */
    private $column;

    /**
     * @var int The number of columns (hexadecimal pairs) in each line.
     */
    private $width = 32;

    /**
     * @var string The internal buffer that stores unpaired hexadecimal bytes.
     */
    private $buffer;

    /**
     * This method creates a new ascii hexadecimal format output stream.
     *
     * @param OutputStreamInterface $out The subordinate output stream.
     * @param int $column The initial column index.
     * @param int $width The maximum number of columns in a line.
     */
    public function __construct(OutputStreamInterface $out, $column = 0, $width = 32)
    {
        parent::__construct($out);

        $this->column = $column;

        $this->width = $width;

        $this->buffer = '';
    }

    /**
     * {@inheritdoc}
     *
     * This method formats ``$bytes`` and writes the formatted string to the
     * subordinate output stream.
     *
     * @throws \InvalidArgumentException If ``$bytes`` is not a valid
     * hexadecimal string.
     */
    protected function output($bytes)
    {
        $hex = '';

        if (1 === preg_match("/[^0-9a-fA-F]/", $bytes)) {

            throw new \InvalidArgumentException(sprintf("%s is not a valid hexadecimal string", $bytes));
        }

        for ($i = 0; $i < strlen($bytes); $i++) {

            $this->buffer .= $bytes[$i];

            if (2 === strlen($this->buffer)) {

                $hex .= $this->buffer;

                $this->buffer = '';

                $this->column = ($this->column + 1) % $this->width;

                if (0 === $this->column) {

                    $hex .= "\n";
                }
            }
        }

        return parent::output($hex);
    }
}
