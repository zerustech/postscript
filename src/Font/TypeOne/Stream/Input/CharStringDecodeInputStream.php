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
 * This class reads char string encoded data from the subordinate stream and
 * decode the data into plain text.
 *
 * @author Michael Lee <michael.lee@zerustech.com>
 */
class CharStringDecodeInputStream extends FilterInputStream
{
    /**
     * @var array The list of char string commands, which maps command code to
     * the name.
     */
    private $commands = [
        1 => 'hstem',
        3 => 'vstem',
        4 => 'vmoveto',
        5 => 'rlineto',
        6 => 'hlineto',
        7 => 'vlineto',
        8 => 'rrcurveto',
        9 => 'closepath',
        10 => 'callsubr',
        11 => 'return',
        13 => 'hsbw',
        14 => 'endchar',
        21 => 'rmoveto',
        22 => 'hmoveto',
        30 => 'vhcurveto',
        31 => 'hvcurveto',
        12 => [
            0 => 'dotsection',
            1 => 'vstem3',
            2 => 'hstem3',
            6 => 'seac',
            7 => 'sbw',
            12 => 'div',
            16 => 'callothersubr',
            17 => 'pop',
            33 => 'setcurrentpoint',
        ],
    ];

    /**
     * @var string The internal buffer that stores bytes to be decoded.
     */
    private $buffer;

    /**
     * This method creates a new decrypt input stream.
     */
    public function __construct(InputStreamInterface $in)
    {
        parent::__construct($in);

        $this->buffer = '';
    }

    /**
     * This method tries to decode the provided byte as well as the buffered
     * bytes to plain text, if possible. The decoded plain text is returned, or
     * null if the bytes can not be decoded yet. If the byte is pushed into the
     * internal buffer, if it can not be decoded.
     *
     * @param string $byte The next byte to be decoded.
     * @return string The decoded text or null, if the byte can not be decoded
     * yet.
     */
    private function decode($byte)
    {
        $decoded = null;

        if (null !== ($type = $this->detect($byte))) {

            $method = 'decode'.$type;

            $decoded = call_user_func([$this, $method], $byte);

        } else {

            $this->buffer .= $byte;
        }

        return $decoded;
    }

    /**
     * This method detects and returns the type of the given byte. If the type
     * can not be detected yet, null is returned.
     *
     * @param string $byte The byte to be decoded.
     * @return string The type of the given byte, or null if type can not be
     * detected yet.
     */
    private function detect($byte)
    {
        $a = ord($byte);

        if (0 === strlen($this->buffer) && $a >= 32 && $a <= 246) {

            return 'NumberTypeA';
        }

        if (1 === strlen($this->buffer) && ord($this->buffer[0]) >= 247 && ord($this->buffer[0]) <= 250) {

            return 'NumberTypeB';
        }

        if (1 === strlen($this->buffer) && ord($this->buffer[0]) >= 251 && ord($this->buffer[0]) <= 254) {

            return 'NumberTypeC';
        }

        if (4 === strlen($this->buffer) && 255 === ord($this->buffer[0])) {

            return 'NumberTypeD';
        }

        if (1 === strlen($this->buffer) && 12 === ord($this->buffer[0])) {

            return "CommandTypeB";
        }

        if (0 === strlen($this->buffer) && $a >= 0 && $a <= 31 && 12 !== $a) {

            return "CommandTypeA";
        }

        return null;
    }

    /**
     * This method decodes the given byte as a char string number type A.
     * @param string $byte The byte to be decoded.
     * @return string The decoded number.
     */
    private function decodeNumberTypeA($byte)
    {
        return (string)(ord($byte) - 139);
    }

    /**
     * This method decodes the given byte as a char string number type B.
     * @param string $byte The byte to be decoded.
     * @return string The decoded number.
     */
    private function decodeNumberTypeB($byte)
    {
        $a = ord($this->buffer[0]);

        $b = ord($byte);

        $this->buffer = '';

        return (string)(($a - 247) * 256 + $b + 108);
    }

    /**
     * This method decodes the given byte as a char string number type C.
     * @param string $byte The byte to be decoded.
     * @return string The decoded number.
     */
    private function decodeNumberTypeC($byte)
    {
        $a = ord($this->buffer[0]);

        $b = ord($byte);

        $this->buffer = '';

        return (string)(-($a - 251) * 256 - $b - 108);
    }

    /**
     * This method decodes the given byte as a char string number type D.
     * @param string $byte The byte to be decoded.
     * @return string The decoded number.
     */
    private function decodeNumberTypeD($byte)
    {
        $a = ord($this->buffer[1]);

        $b = ord($this->buffer[2]);

        $c = ord($this->buffer[3]);

        $d = ord($byte);

        $e = $a << 24 | $b << 16 | $c << 8 | $d;

        $decoded = reset(unpack("l", pack("l", $e)));

        $this->buffer = '';

        return (string)$decoded;
    }

    /**
     * This method decodes the given byte as a char string command type A.
     * @param string $byte The byte to be decoded.
     * @return string The decoded command.
     */
    private function decodeCommandTypeA($byte)
    {
        return $this->commands[ord($byte)];
    }

    /**
     * This method decodes the given byte as a char string command type B.
     * @param string $byte The byte to be decoded.
     * @return string The decoded command.
     */
    private function decodeCommandTypeB($byte)
    {
        $this->buffer = '';

        return $this->commands[12][ord($byte)];
    }

    /**
     * {@inheritdoc}
     *
     * This methods reads up to ``$length`` bytes from the subordinate stream
     * and decodes the bytes into ``$bytes``.
     */
    protected function input(&$bytes, $length)
    {
        $bytes = '';

        $count = parent::input($encoded, $length);

        for ($i = 0; $i < strlen($encoded); $i++) {

            if (null !== ($decoded = $this->decode($encoded[$i]))) {

                $bytes .= $decoded.' ';
            }
        }

        return $count;
    }
}
