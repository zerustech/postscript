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
 * @author Michael Lee <michael.lee@zerustech.com>
 */
class BinaryToAsciiHexadecimalInputStream extends FilterInputStream
{
    /**
     * {@inheritdoc}
     */
    public function available()
    {
        return parent::available() * 2;
    }

    /**
     * {@inheritdoc}
     *
     * This method keeps reading bytes from the subordinate input
     * stream and converting the data from binary format to ascii hexadecimal
     * format, untill ``$length`` hexadecimal bytes have been converted.
     *
     * Since one binary byte is always converted into two hexadecimal bytes, the
     * ``$length`` argument should be an even number. If the ``$length``
     * argument is an odd number, the next lowest even number (``$length + 1``)
     * will be used.
     *
     * @return int The number of hexadecimal bytes converted, or -1 if EOF.
     */
    protected function input(&$bytes, $length)
    {
        $remaining = $length = 0 === $length % 2 ? $length : $length + 1;

        $bytes = '';

        while ($remaining > 0 && -1 !== parent::input($bin, $remaining / 2)) {

            $hex = strtoupper(bin2hex($bin));

            $bytes .= $hex;

            $remaining -= strlen($hex);
        }

        return $remaining === $length ? -1 : $length - $remaining;
    }
}
