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
 * This class reads ascii hexadecimal data from the subordinate input stream and
 * converts the data to binary format.
 *
 * @author Michael Lee <michael.lee@zerustech.com>
 */
class AsciiHexadecimalToBinaryInputStream extends FilterInputStream
{
    /**
     * @var array The internal buffer that stores hexadecimal byte that has not
     * been paired.
     */
    private $buffer = [];

    /**
     * This method creates a new ``ascii hexadecimal to binary`` input stream.
     */
    public function __construct(InputStreamInterface $input)
    {
        parent::__construct($input);

        $this->buffer = [];
    }

    /**
     * {@inheritdoc}
     *
     * Because it's impossible to predict how many space characters in the
     * subordinate stream, this method returns 1 if the subordinate stream is
     * still available, or 0 otherwise.
     *
     * @return int 1 if the subordinate stream is still available, or 0
     * otherwise.
     */
    public function available()
    {
        return parent::available() > 0 ? 1 : 0;
    }

    /**
     * {@inheritdoc}
     *
     * This method keeps reading bytes from the subordinate input stream and
     * converting the data from ascii hexadecimal format to binary format, until
     * ``$length`` binary bytes have been converted, or EOF is reached.
     *
     * NOTE: the ``$length`` argument is not the number of bytes read from the
     * subordinate stream.
     *
     * @return int The number of binary bytes converted, or -1 if EOF.
     */
    protected function input(&$bytes, $length)
    {
        $remaining = $length;

        $bytes = '';

        while ($remaining > 0 && -1 !== parent::input($hex, 2 * $remaining)) {

            for ($i = 0; $i < strlen($hex); $i++) {

                if (1 === preg_match("/^[ \t\r\n]$/", $hex[$i])) {

                    continue;
                }

                $this->buffer[] = $hex[$i];

                if (2 === count($this->buffer)) {

                    $bytes .= chr(hexdec(array_shift($this->buffer).array_shift($this->buffer)));

                    $remaining--;
                }
            }
        }

        return $remaining === $length ? -1 : $length - $remaining;
    }
}
