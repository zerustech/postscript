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

/**
 * This class converts plain text into char string encoded string and writes the
 * encoded string to the subordinate stream.
 *
 * @author Michael Lee <michael.lee@zerustech.com>
 */
class CharStringEncodeOutputStream extends FilterOutputStream
{
    /**
     * @var array The list of char string commands, which maps command name to
     * the code.
     */
    private $commands = [
        'hstem' => 1,
        'vstem' => 3,
        'vmoveto' => 4,
        'rlineto' => 5,
        'hlineto' => 6,
        'vlineto' => 7,
        'rrcurveto' => 8,
        'closepath' => 9,
        'callsubr' => 10,
        'return' => 11,
        'hsbw' => 13,
        'endchar' => 14,
        'rmoveto' => 21,
        'hmoveto' => 22,
        'vhcurveto' => 30,
        'hvcurveto' => 31,
        'dotsection' => [12, 0],
        'vstem3' => [12, 1],
        'hstem3' => [12, 2],
        'seac' => [12, 6],
        'sbw' => [12, 7],
        'div' => [12, 12],
        'callothersubr' => [12, 16],
        'pop' => [12, 17],
        'setcurrentpoint' => [12, 33],
    ];

    /**
     * @var string The internal buffer that stores the next token to be encoded.
     */
    private $buffer;

    /**
     * This method creates a new char string encode output stream.
     */
    public function __construct(OutputStreamInterface $out)
    {
        parent::__construct($out);

        $this->buffer = '';
    }

    /**
     * This method encodes the given token into char string encoded string.
     * @param string $token The plain text token.
     * @return string The encoded token.
     */
    private function encode($token)
    {
        $type = $this->detect($token);

        $method = 'encode'.$type;

        return call_user_func([$this, $method], $token);
    }

    /**
     * This method detects the type of the given token.
     * @param string $token The plain text token.
     * @return string The type of the given token.
     */
    private function detect($token)
    {
        if (1 === preg_match('/^[-+]{0,1}[0-9]+$/', $token)) {

            return $this->detectNumber($token);

        } else {

            return $this->detectCommand($token);
        }
    }

    /**
     * This method detects number type of the given token.
     * @param string $token The token that represents a number.
     * @return string The number type.
     */
    private function detectNumber($token)
    {
        $token = (int)$token;

        if ($token >= -107 && $token <= 107) {

            return 'NumberTypeA';
        }

        if ($token >= 108 && $token <= 1131) {

            return 'NumberTypeB';
        }

        if ($token >= -1131 && $token <= -108) {

            return 'NumberTypeC';
        }

        return 'NumberTypeD';
    }

    /**
     * This method detects command type of the given token.
     * @param string $token The token that represents a command.
     * @return string The command type.
     */
    private function detectCommand($token)
    {
        if (is_array($this->commands[strtolower($token)])) {

            return 'CommandTypeB';
        }

        return 'CommandTypeA';
    }

    /**
     * This method encodes the given number to a char string type A number.
     * @param string $token The token represents a number.
     * @return The encoded number type A.
     */
    private function encodeNumberTypeA($token)
    {
        return chr((int)$token + 139);
    }

    /**
     * This method encodes the given number to a char string type B number.
     * @param string $token The token represents a number.
     * @return The encoded number type B.
     */
    private function encodeNumberTypeB($token)
    {
        $token = (int)$token;

        $a = $b = 0;

        for ($i = 247; $i <= 250; $i++) {

            $a = $i;

            $b = $token - 108 - ($a - 247) * 256;

            if ($b >= 0 && $b <= 255) {

                break;
            }
        }

        return chr($a).chr($b);
    }

    /**
     * This method encodes the given number to a char string type C number.
     * @param string $token The token represents a number.
     * @return The encoded number type C.
     */
    private function encodeNumberTypeC($token)
    {
        $token = (int)$token;

        $a = $b = 0;

        for ($i = 251; $i <= 254; $i++) {

            $a = $i;

            $b = -108 - $token - ($a - 251) * 256;

            if ($b >= 0 && $b <= 255) {

                break;
            }
        }

        return chr($a).chr($b);
    }

    /**
     * This method encodes the given number to a char string type D number.
     * @param string $token The token represents a number.
     * @return The encoded number type D.
     */
    private function encodeNumberTypeD($token)
    {
        $token = (int)$token;

        $encoded = chr(255).chr($token >> 24 & 0xff).chr($token >> 16 & 0xff).chr($token >> 8 & 0xff).chr($token & 0xff);

        if (abs($token) > 32000) {

            $b = $token > 0 ? (int)round($token / 32000) + 1 : (int)round($token / 32000) - 1;

            $encoded = $encoded.$this->encode((string)$b).$this->encode('div');
        }

        return $encoded;
    }

    /**
     * This method encodes the given command to a char string type A command.
     * @param string $token The token represents a command.
     * @return The encoded command type A.
     */
    private function encodeCommandTypeA($token)
    {
        return chr($this->commands[strtolower($token)]);
    }

    /**
     * This method encodes the given command to a char string type B command.
     * @param string $token The token represents a command.
     * @return The encoded command type B.
     */
    private function encodeCommandTypeB($token)
    {
        $bytes = $this->commands[strtolower($token)];

        return chr($bytes[0]).chr($bytes[1]);
    }

    /**
     * {@inheritdoc}
     */
    protected function output($bytes)
    {
        $charstring = '';

        for ($i = 0; $i < strlen($bytes); $i++) {

            if (1 === preg_match('/^[ \t\r\n]$/', $bytes[$i])) {

                if (0 !== strlen($this->buffer)) {

                    $charstring .= $this->encode($this->buffer);

                    $this->buffer = '';
                }

                continue;
            }

            $this->buffer .= $bytes[$i];
        }

        if (0 !== strlen($this->buffer)) {

            $charstring .= $this->encode($this->buffer);

            $this->buffer = '';
        }

        return parent::output($charstring);
    }
}
