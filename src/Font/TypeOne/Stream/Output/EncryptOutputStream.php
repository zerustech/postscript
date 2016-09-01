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
 * This class performs eexec or char string encryption on the plain text and
 * writes the encrypted data to the subordinate output stream.
 *
 * @author Michael Lee <michael.lee@zerustech.com>
 */
class EncryptOutputStream extends FilterOutputStream
{
    /**
     * @var int The ``R`` value of a specific type of encryptor. This value does
     * not change during the encryption / decryption process.
     */
    protected $R = 0;

    /**
     * @var int The next r value of current encryptor. This value changes during
     * the encryption / decryption process.
     */
    protected $r = 0;

    /**
     * @var int The c1 value. This value does not change.
     */
    protected $c1 = 52845;

    /**
     * @var int The c2 value. This value does not change.
     */
    protected $c2 = 22719;

    /**
     * @var string The random bytes to be inserted at the front of the plain
     * text.
     */
    protected $seeds;

    /**
     * This method creates a new encrypt output stream.
     *
     * @param OutputStreamInterface The subordinate output stream.
     * @param int $R The R value.
     * @param int $n The number of leading random numbers.
     * @param string $seeds The random bytes to be inserted.
     */
    public function __construct(OutputStreamInterface $out, $R, $seeds = '0000')
    {
        parent::__construct($out);

        $this->r = $this->R = $R;

        $this->seeds = $seeds;
    }

    /**
     * {@inheritdoc}
     *
     * This method encrypts plain text into ciphertext and writes the ciphertext
     * into the output stream.
     */
    protected function output($bytes)
    {
        $bytes = ($this->r === $this->R) ? $this->seeds.$bytes : $bytes;

        $cipherText = '';

        for ($i = 0; $i < strlen($bytes); $i++) {

            $plainByte = ord($bytes[$i]);

            $cipherByte = ($plainByte ^ ($this->r >> 8));

            $this->r = (($cipherByte + $this->r) * $this->c1 + $this->c2) % 65536;

            $cipherText .= chr($cipherByte);
        }

        return parent::output($cipherText);
    }
}
