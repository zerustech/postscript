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
use ZerusTech\Component\IO\Stream\Input\UncountableFilterInputStream;

/**
 * This class reads and decrypts the eexec / charstring encrypted data, in
 * binary format, from the subordinate stream.
 *
 * This class is uncountable because its subordinate input stream might be
 * uncountable. For example, a hex 2 bin input stream.
 *
 * @author Michael Lee <michael.lee@zerustech.com>
 */
class DecryptInputStream extends UncountableFilterInputStream
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
     * @var int The number of leading random numbers. This value does not change.
     */
    protected $n = 4;


    /**
     * @var int It keeps track of the number of random bytes skipped. The
     * leading random bytes should be skipped from the decrypted text.
     */
    protected $skipped;

    /**
     * This method creates a new decrypt input stream.
     *
     * @param InputStreamInterface $in The subordinate input stream.
     * @param int $R The R value.
     * @param int $n The number of leading random numbers.
     */
    public function __construct(InputStreamInterface $in, $R, $n = 4)
    {
        parent::__construct($in);

        $this->r = $this->R = $R;

        $this->n = $n;

        $this->skipped = 0;
    }

    /**
     * {@inheritdoc}
     *
     * This method keeps reading ciphertext bytes from the subordinate stream
     * and decrypting the ciphertext into ``$bytes``, untill ``$length`` bytes
     * have been decrypted.
     *
     * It returns the number of decrypted bytes, or -1 if EOF.
     *
     * @param string $bytes The buffer into which the plain text will be stored.
     * @param int $length The requested number of bytes to be decrypted.
     * @return int The actual number of decrypted bytes, or -1 if eof.
     */
    protected function input(&$bytes, $length)
    {
        $remaining = $length;

        $bytes = '';

        while ($remaining > 0) {

            if (-1 === $count = parent::input($cipherText, 1)) {

                break;
            }

            $cipherByte = ord($cipherText);

            $plainByte = ($cipherByte ^ ($this->r >> 8));

            $this->r = (($cipherByte + $this->r) * $this->c1 + $this->c2) % 65536;

            if ($this->skipped < $this->n) {

                $this->skipped++;

                continue;
            }

            $bytes .= chr($plainByte);

            $remaining--;
        }

        return (-1 === $count && $remaining === $length) ? -1 : strlen($bytes);
    }
}
