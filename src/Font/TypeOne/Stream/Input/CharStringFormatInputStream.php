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
use ZerusTech\Component\IO\Stream\Input\UncountableBufferableFilterInputStream;

/**
 * This class reads decoded char string source code from the subordinate input
 * stream and formats the source code with tabs and line feeds.
 *
 * @author Michael Lee <michael.lee@zerustech.com>
 */
class CharStringFormatInputStream extends UncountableBufferableFilterInputStream
{
    /**
     * This method reads decoded char string source code from the subordinate
     * stream and formats the source with tab and line feeds.
     *
     * It tries to find the closest call to any command, appends a line feed
     * ("\n") and a tab ("\t") to the end of the command and returns the
     * formatted source code.
     *
     * @return string The formatted code, or null if EOF.
     */
    public function format()
    {
        $formatted = null;

        while (1 !== $matched = preg_match('/^[^a-zA-Z_]*([a-zA-Z][a-zA-Z_0-9]+)[ ]/', $this->buffer, $matches, PREG_OFFSET_CAPTURE)) {

            if (-1 === $this->in->read($bytes, $this->readBufferSize)) {

                break;
            }

            $this->buffer .= $bytes;
        }

        if (1 === $matched) {

            $command = $matches[1][0];

            $offset = $matches[1][1];

            $formatted = trim(substr($this->buffer, 0, $offset + strlen($command)))."\n\t";

            $this->buffer = substr($this->buffer, $offset + strlen($command));

        } else if (strlen($this->buffer) > 0) {

            // Trims line feed, carriage return and spaces, and appends a space
            // to the end of the buffer.
            $formatted = trim($this->buffer).' ';

            $this->buffer = '';
        }

        return $formatted;
    }
}
