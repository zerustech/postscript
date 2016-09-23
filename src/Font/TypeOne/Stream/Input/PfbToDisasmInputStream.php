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

use ZerusTech\Component\IO\Exception\IOException;
use ZerusTech\Component\IO\Stream\Input\FilterInputStream;
use ZerusTech\Component\IO\Stream\Input\StringInputStream;
use ZerusTech\Component\IO\Stream\Input\InputStreamInterface;
use ZerusTech\Component\IO\Stream\Input\LineInputStream;
use ZerusTech\Component\IO\Stream\Input\PipedInputStream;
use ZerusTech\Component\IO\Stream\Output\StringOutputStream;
use ZerusTech\Component\IO\Stream\Output\PipedOutputStream;

/**
 * This class reads bytes in PFB format from the subordinate stream and converts
 * the bytes into disasm format.
 *
 * @author Michael Lee <michael.lee@zerustech.com>
 */
class PfbToDisasmInputStream extends PfbToPfaInputStream
{
    /**
     * @var PipedOutputStream The piped output stream that pipes the bytes read
     * from the subordinate stream to the decoder input stream.
     */
    private $pipe = null;

    /**
     * @var LineInputStream The line input stream that reads a line of decrypted
     * bytes from the subordinate stream each time. The decrypted bytes
     * must then be decoded to plain text by a decoder input stream.
     */
    private $line = null;

    /**
     * This method creates a new pfb to disassembled input stream.
     *
     * @param InputStreamInterface $input The subordinate stream, from which,
     * the PFB bytes will be read.
     */
    public function __construct(InputStreamInterface $input, $readBufferSize = 1024)
    {
        parent::__construct($input, false, 32, $readBufferSize);

        $this->pipe = null;

        $this->line = null;
    }

    /**
     * {@inheritdoc}
     */
    protected function afterParseHeader()
    {
        if (self::TYPE_BINARY === $this->header['type']) {

            $this->pipe = new PipedOutputStream();

            $this->line = new LineInputStream(new EexecDecryptInputStream(new PipedInputStream($this->pipe)));
        }
    }

    /**
     * {@inheritdoc}
     *
     * For binary block, the char string bytes will be decrypted and decoded to
     * plain text.
     */
    protected function parseBlock()
    {
        $bytes = parent::parseBlock();

        // If current block is a binary block and EOF has not been reached,
        // tries to decrypt and decode the char string bytes.
        if (null !== $bytes && self::TYPE_BINARY === $this->header['type']) {

            $this->pipe->write($bytes);

            $bytes = '';

            // Reads one line of eexec decrypted code each time.
            while (null !== $line = $this->line->readLine()) {

                // Buffers the line of code before it can be decrypted and
                // decoded.
                $this->buffer .= $line;

                // Tries to match char string encrypted code from the end of the
                // buffer, because char string bytes are appended to the buffer
                // For example: "... dup 0 15 RD<char string encrypted bytes>NP\n"
                // If no match found, continue to append more bytes to
                // $this->buffer (for example, the first few lines are not char
                // string bytes, thus no need to be decrypted and decoded).
                if (1 !== preg_match('/ ([-+0-9]+ RD) ((?:[^\n]+\n?)+)(NP|ND)\n$/m', $this->buffer, $matches, PREG_OFFSET_CAPTURE)) {

                    continue;
                }

                $start = $matches[1][1];

                $command = $matches[2][0];

                $end = $matches[3][1];

                $decoder = new CharStringDecodeInputStream(new CharStringDecryptInputStream(new StringInputStream($command)));

                $pipe = new PipedOutputStream();

                $formatter = new CharStringFormatInputStream(new PipedInputStream($pipe));

                $out = new StringOutputStream();

                // Decrypts and decodes char string bytes and writes the plain
                // text code to the piped output stream.
                while (null !== $token = $decoder->readToken()) {

                    $pipe->write($token.' ');
                }

                // Formats the plain text from the piped input stream and writes
                // the well-formatted code to a string output stream.
                while (null !== $code = $formatter->format()) {

                    $out->write($code);
                }

                // Replaces the original char string in $this->buffer with the
                // plain text and appends the revised contents of $this->buffer
                // to $bytes.
                $bytes .= substr($this->buffer, 0, $start).'{'."\n\t".trim($out->__toString(), " ").'}'.substr($this->buffer, $end);

                // Now all contents of $this->buffer have been appended to
                // $parsed, it's safe to reset it to '', so that it can be used
                // to buffer the next line.
                $this->buffer = '';
            }
        }

        if (null === $bytes && self::TYPE_BINARY === $this->header['type'] && strlen($this->buffer) > 0) {

            // At EOF, appends what ever remains in $this->buffer to $bytes and
            // resets $this->buffer to ''.
            // Because lines after char string dictionary won't match the regex
            // pattern, thus, the contents of $this->buffer will not be appended
            // to $parsed.
            $bytes .= $this->buffer;

            $this->buffer = '';
        }

        return $bytes;
    }
}
