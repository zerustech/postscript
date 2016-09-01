<?php

/**
 * This file is part of the ZerusTech package.
 *
 * (c) Michael Lee <michael.lee@zerustech.com>
 *
 * For full copyright and license information, please view the LICENSE file that
 * was distributed with this source code.
 */

namespace ZerusTech\Component\Postscript\Font\TypeOne\Stream\Input;

use ZerusTech\Component\IO\Stream\Input\InputStreamInterface;
use ZerusTech\Component\IO\Stream\Input\FilterInputStream;

/**
 * This class reads binary data from the subordinate input stream and converts
 * the data to ascii hexadecimal format.
 *
 * Linefeed ("\n") characters will be inserted into the generated hexadecimal
 * text to get a fixed number of columns for all lines.
 *
 * Each pair of hexadecimal bytes represents a column, and there are up to 32
 * columns, which is 64 bytes, in a line, by default.
 *
 * @author Michael Lee <michael.lee@zerustech.com>
 */
class BinaryToAsciiHexadecimalInputStream extends FilterInputStream
{
    /**
     * @var int The column index of the next hexadecimal pair.
     */
    private $column;

    /**
     * @var bool A boolean indicates whether to format the ascii hexadecimal
     * data.
     */
    private $format;

    /**
     * The maximum number of columns (hexadecimal pairs) in each line.
     */
    private $width = 32;

    /**
     * This method creates a new binary to ascii hexadecimal input stream.
     *
     * @param InputStreamInterface $in The subordinate input stream.
     * @param int $column The initial column index.
     * @param bool $format The boolean that indicates whether to format the
     * ascii hexadecimal data.
     * @param int $width The maximum number of columns in each line.
     */
    public function __construct(InputStreamInterface $in, $column = 0, $format = true, $width = 32)
    {
        parent::__construct($in);

        $this->column = $column;

        $this->format = $format;

        $this->width = $width;
    }

    /**
     * {@inheritdoc}
     *
     * This method reads up to ``$length`` bytes from the subordinate input
     * stream and converts the data from binary format to ascii hexadecimal
     * format.
     */
    protected function input(&$bytes, $length)
    {
        $bytes = '';

        $count = parent::input($bin, $length);

        for ($i = 0; $i < strlen($bin); $i++) {

            $bytes .= strtoupper(bin2hex($bin[$i]));

            $this->column = ($this->column + 1) % $this->width;

            if (0 === $this->column && true === $this->format) {

                $bytes .= "\n";
            }
        }

        return $count;
    }
}
