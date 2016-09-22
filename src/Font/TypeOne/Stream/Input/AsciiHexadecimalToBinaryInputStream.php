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
use ZerusTech\Component\IO\Stream\Input\UncountableBufferableFilterInputStream;

/**
 * This class reads ascii hexadecimal data from the subordinate input stream and
 * converts the data to binary format.
 *
 * This class is a subclass of uncountable bufferable filter input stream,
 * because it's subordinate input stream might be uncountable.
 *
 * @author Michael Lee <michael.lee@zerustech.com>
 */
class AsciiHexadecimalToBinaryInputStream extends UncountableBufferableFilterInputStream
{
    /**
     * {@inheritdoc}
     *
     * This method keeps reading bytes from the subordinate input stream and
     * converting the data from ascii hexadecimal format to binary format, until
     * ``$length`` binary bytes have been converted, or EOF is reached.
     *
     * @return int The number of binary bytes converted, or -1 if EOF.
     *
     * @throw \RuntimeException If the bytes read from subordinate is not valid
     * hexadecimal string.
     */
    protected function input(&$bytes, $length)
    {
        $remaining = $length;

        $bytes = '';

        while ($remaining > 0 && -1 !== parent::input($hex, 2 * $remaining)) {

            if (1 ===  preg_match('/[^0-9a-fA-F]/', $hex)) {

                throw new \RuntimeException(sprintf("%s is not a valid hexadecimal string.", $hex));
            }

            $this->buffer .= $hex;

            $len = 0 === strlen($this->buffer) % 2 ? strlen($this->buffer) : strlen($this->buffer) - 1;

            if ($len > 0) {

                $bin = hex2bin(substr($this->buffer, 0, $len));

                $bytes .= $bin;

                $this->buffer = substr($this->buffer, $len);

                $remaining -= strlen($bin);
            }
        }

        return $remaining === $length ? -1 : $length - $remaining;
    }
}
