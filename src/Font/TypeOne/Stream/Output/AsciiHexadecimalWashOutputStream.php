<?php

/**
 * This file is part of the ZerusTech package.
 *
 * (c) Michael Lee <michael.lee@zerustech.com>
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with the source code.
 */

namespace ZerusTech\Component\Postscript\Font\TypeOne\Stream\Output;

use ZerusTech\Component\IO\Stream\Output\FilterOutputStream;
use ZerusTech\Component\IO\Stream\Output\OutputStreamInterface;

/**
 * This class removes space characters, "\n", "\r", "\t" and " " from the
 * provided hexadecimal bytes and writes the bytes to the subordinate output
 * stream.
 *
 * @author Michael Lee <michael.lee@zerustech.com>
 */
class AsciiHexadecimalWashOutputStream extends FilterOutputStream
{
    /**
     * This method creates a new ascii hexadecimal wash output stream.
     *
     * @param OutputStreamInterface $out The subordinate output stream.
     */
    public function __construct(OutputStreamInterface $out)
    {
        parent::__construct($out);
    }

    /**
     * {@inheritdoc}
     *
     * This method removes space characters from ``$bytes`` and writes the
     * washed string to the subordinate stream.
     */
    protected function output($bytes)
    {
        return parent::output(preg_replace("/([^\n\t\r ]*)([\n\t\r ]*)([^\n\r\t ]*)/", "$1$3", $bytes));
    }
}
