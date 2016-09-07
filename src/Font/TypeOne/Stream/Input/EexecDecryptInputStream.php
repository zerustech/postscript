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
 * This class performs font type one eexec decryption.
 *
 * @author Michael Lee <michael.lee@zerustech.com>
 */
class EexecDecryptInputStream extends DecryptInputStream
{
    /**
     * This method creates a new eexec encryptor.
     *
     * @param InputStreamInterface $in The subordinate input stream.
     */
    public function __construct(InputStreamInterface $in)
    {
        parent::__construct($in, 55665, 4);
    }
}
