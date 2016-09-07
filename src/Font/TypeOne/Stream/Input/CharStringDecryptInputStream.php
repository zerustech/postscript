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

/**
 * This class performs char string decryption.
 *
 * @author Michael Lee <michael.lee@zerustech.com>
 */
class CharStringDecryptInputStream extends DecryptInputStream
{
    /**
     * This method creates a new char string decrypt input stream.
     *
     * @param InputStreamInterface $in The subordinate input stream.
     * @param int $n The number of random bytes.
     */
    public function __construct(InputStreamInterface $in, $n = 4)
    {
        parent::__construct($in, 4330, $n);
    }
}
