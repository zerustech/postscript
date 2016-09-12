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
 * This class reads hexadecimal bytes from the subordinate input stream and
 * formats them to fixed width lines by inserting line feeds ("\n") at the end
 * of each line.
 *
 * Each pair of hexadecimal bytes represents a column, and by default there are
 * up to 32 columns, which is 64 bytes, in a line.
 *
 * @author Michael Lee <michael.lee@zerustech.com>
 */
class AsciiHexadecimalFormatInputStream extends FilterInputStream
{
    /**
     * @var int The column index of the next hexadecimal pair.
     */
    private $column;

    /**
     * The maximum number of columns (hexadecimal pairs) in each line.
     */
    private $width = 32;

    /**
     * This method creates a new ascii hexadecimal format input stream.
     *
     * @param InputStreamInterface $in The subordinate input stream.
     * @param int $column The initial column index.
     * @param int $width The maximum number of columns in each line.
     */
    public function __construct(InputStreamInterface $in, $column = 0, $width = 32)
    {
        parent::__construct($in);

        $this->column = $column;

        $this->width = $width;
    }

    /**
     * {@inheritdoc}
     */
    public function available()
    {
        return (parent::available() / 2 < $this->width - $this->column) ? parent::available() : parent::available() + 1 + (int)((parent::available() / 2 - $this->width + $this->column) / $this->width);
    }

    /**
     * {@inheritdoc}
     *
     * This method keeps reading bytes from the subordinate input
     * stream and formatting them to fixed-width lines, untill at least
     * ``$length`` bytes (including the linefeeds) have been formated.
     *
     * NOTE: the ``$length`` argument is the number of formatted bytes and the
     * return value of this method may be greater than ``$length``.
     *
     * @return int The number of formatted bytes, or -1 if EOF.
     *
     * @throw \RuntimeException If the bytes read from subordinate is not valid
     * hexadecimal string.
     */
    protected function input(&$bytes, $length)
    {
        $remaining = $length;

        $bytes = '';

        while ($remaining > 0 && -1 !== parent::input($hex, 2)) {

            if (1 ===  preg_match('/[^0-9a-fA-F]/', $hex)) {

                throw new \RuntimeException(sprintf("%s is not a valid hexadecimal string.", $hex));
            }

            $bytes .= $hex;

            $remaining -= 2;

            $this->column = ($this->column + 1) % $this->width;

            if (0 === $this->column) {

                $bytes .= "\n";

                $remaining--;
            }
        }

        return $remaining === $length ? -1 : $length - $remaining;
    }
}
