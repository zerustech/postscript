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

/**
 * This class performs eexec encryption on the plain text and writes the
 * encrypted data to the subordinate output stream.
 *
 * @author Michael Lee <michael.lee@zerustech.com>
 */
class EexecEncryptOutputStream extends EncryptOutputStream
{
    /**
     * This method creates a new eexec encrypt output stream.
     *
     * @param OutputStreamInterface $out The subordinate output stream.
     * @param string $seeds The random bytes to be inserted, "0000"
     * by default.
     */
    public function __construct(OutputStreamInterface $out, $seeds = '0000')
    {
        parent::__construct($out, 55665, $seeds);
    }
}
