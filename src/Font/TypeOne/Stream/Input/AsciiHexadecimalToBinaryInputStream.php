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
     * This method reads up to ``$length`` bytes from the subordinate input
     * stream and converts the data from ascii hexadecimal format to binary
     * format
     */
    protected function input(&$bytes, $length)
    {
        $remaining = $length;

        $bytes = '';

        while ($remaining > 0) {

            if (-1 === $count = parent::input($hex, 2 * $remaining)) {

                break;
            }

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

        return (-1 === $count && $remaining === $length) ? -1 : $length - $remaining;
    }

    /**
     * {@inheritdoc}
     */
    public function available()
    {
        return (int)round((count($this->buffer) + parent::available()) / 2);
    }
}
