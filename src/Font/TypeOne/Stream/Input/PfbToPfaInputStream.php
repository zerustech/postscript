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

/**
 * This class reads bytes in PFB format from the subordinate stream and converts
 * the bytes into PFA format. The converted eexec block is stored in binary
 * format, which is the default eexec format in PFB format.
 *
 * PFB is a binary format for PC fonts. The PFB file is conceptually divided
 * into segments, each of which has a small header containing a "type" field and
 * length information. There are three types of segments:
 *
 * - Type 1: ASCII text. It can be directly used without any decompression.
 * - Type 2: Binary data that should be converted to hexadecimal format.
 * - Type 3: EOF. This is a flag that indicates that the end of the data
 * segement has been reached.
 *
 * {@link https://partners.adobe.com/public/developer/en/font/5040.Download_Fonts.pdf downloadable postscript fonts}
 *
 * @author Michael Lee <michael.lee@zerustech.com>
 */
class PfbToPfaInputStream extends FilterInputStream
{
    /**
     * This constant represents data segment type 1.
     */
    const TYPE_ASCII = 1;

    /**
     * This constant represents data segment type 2.
     */
    const TYPE_BINARY = 2;

    /**
     * This constant represents data segment type 3.
     */
    const TYPE_EOF = 3;

    /**
     * This constant represents the magic number in PFB header.
     */
    const MAGIC_NUMBER = 0x80;

    /**
     * The internal buffer for storing bytes before converted.
     *
     * @var string The internal buffer.
     */
    protected $buffer;

    /**
     * The PFB header information of the next PFB block. The structure of a PFB
     * header is as follows:
     *
     * The data structure of the parsed header is as follows:
     *     array(
     *         'magic-number' => 0x80,
     *         'type' => (1|2|3),
     *         'length' => ...
     *     );
     *
     * @var array The PFB header information.
     */
    protected $header;

    /**
     * @var bool This is a boolean flag that indicates if the header of the next
     * segment has been parsed successfully and it's ready to read the data now.
     */
    protected $ready = false;

    /**
     * @var int This is the relative offset from the beginning of current data
     * segment.
     */
    protected $offset = 0;

    /**
     * @var bool This is a boolean that indicates whether to convert the eexec
     * block into hexadecimal format.
     */
    protected $convertToHex = true;

    /**
     * @var int The corresponding column of current offset in current binary
     * data block.
     */
    protected $column = 0;

    /**
     * The maximum number of columns (hexadecimal pairs) in each line.
     */
    protected $width = 32;

    /**
     * This method creates a new pfb to pfa input stream.
     *
     * @param InputStreamInterface $input The subordinate stream, from which,
     * the PFB bytes will be read.
     * @param bool Indicates whether to convert the eexec data block to ascii
     * hexadecimal format, true by default.
     */
    public function __construct(InputStreamInterface $input, $convertToHex = true, $width = 32)
    {
        parent::__construct($input);

        $this->buffer = '';

        $this->header = null;

        $this->ready = false;

        $this->offset = 0;

        $this->convertToHex = $convertToHex;

        $this->width = $width;
    }

    /**
     * {@inheritdoc}
     *
     * Because it's impossible to predict the length of pfa bytes, this
     * method returns 1 if the subordinate stream is still available, or 0
     * otherwise.
     */
    public function available()
    {
        return parent::available() > 0 ? 1 : 0;
    }

    /**
     * {@inheritdoc}
     *
     * This method keeps reading pfb bytes from the subordinate stream and
     * converting the pfb bytes into pfa format, until ``$length`` number of pfa
     * bytes have been generated, or EOF is reached.
     *
     * @return int The number of pfa bytes generated, or -1 if EOF.
     */
    protected function input(&$bytes, $length)
    {
        $bytes = '';

        $remaining = $length;

        while ($remaining > 0) {

            if (false === $this->ready && -1 === ($count = $this->parseHeader($length))) {

                // If ready is false, try to parse header.
                // But if EOF has been reached, the EOF header, break current
                // loop.
                break;
            }

            if (false === $this->ready) {

                // If header has not been parsed, continue to parse header.
                continue;
            }

            if (-1 === ($count = $this->parseBlock($bytes, $remaining))) {

                // If EOF has been reached, break current loop.
                break;
            }

            // ``$count`` pfb bytes have been parsed.
            $remaining -= $count;

            if ($this->offset === $this->header['length']) {

                $this->resetHeader();

                break;
            }
        }

        return (-1 === $count && $remaining === $length) ? -1 : $length - $remaining;
    }

    /**
     * This method reads up to 6 bytes from current stream and parses PFB header
     * information.
     *
     * @param int $length The requested number of bytes to read.
     * @return int The actual number of bytes read, or -1 if eof.
     * @throws IOException If failed to parse the header information.
     */
    protected function parseHeader($length)
    {
        $count = 0;

        // Header has already been parsed, returns 0
        if (true === $this->ready) {

            return $count;
        }

        // Reads, which is the least, either the number of missing bytes for
        // passing a header, or the specified numbrer of bytes, from the
        // subordinate stream.
        if (min(6 - strlen($this->buffer), $length) > 0) {

            $count = parent::input($bytes, min(6 - strlen($this->buffer), $length));
        }

        $buffer = ($this->buffer .= $bytes);

        // If EOF has been reached, and there was no byte in the buffer, the
        // buffer could still be empty now and count MUST be -1 in this case.
        if (0 === strlen($buffer)) {

            return $count;
        }

        if (strlen($buffer) >= 1 && self::MAGIC_NUMBER !== ord($buffer[0])) {

            throw new IOException(sprintf("Failed to parse margic number."));
        }

        if (strlen($buffer) >= 2 && !in_array(ord($buffer[1]), [static::TYPE_ASCII, static::TYPE_BINARY, static::TYPE_EOF])) {

            throw new IOException(sprintf("Failed to parse data segment type information."));
        }

        if (strlen($buffer) >= 2 && ord($buffer[1]) === self::TYPE_EOF) {

            $this->header = [];
            $this->header['magic-number'] = self::MAGIC_NUMBER;
            $this->header['type'] = ord($buffer[1]);
            $this->header['length'] = 0;
            $this->offset = 0;
            $this->ready = true;
            $this->buffer = '';

            $count = -1;

        } else if (6 === strlen($buffer)) {

            $this->header = [];
            $this->header['magic-number'] = self::MAGIC_NUMBER;
            $this->header['type'] = ord($buffer[1]);
            $this->header['length'] = ord($buffer[2]) | ord($buffer[3]) << 8 | ord($buffer[4]) << 16 | ord($buffer[5]) << 24;
            $this->offset = 0;
            $this->ready = true;
            $this->buffer = '';
        }

        $this->postParseHeader();

        return $count;
    }

    /**
     * When all bytes of current data block has been read and converted, this
     * method resets header, buffer as well as other variables to their initial
     * values.
     */
    protected function resetHeader()
    {
        $this->ready = false;
        $this->header = null;
        $this->buffer = '';
        $this->offset = 0;
        $this->column = 0;

        $this->postResetHeader();
    }

    /**
     * This method reads and parses the byte data from the upcoming data block.
     *
     * @param string $bytes The buffer, into which, the bytes will be read and
     * stored.
     * @param int $length The requested number of bytes to read.
     * @return int The actual number of bytes read, or -1 if EOF.
     */
    protected function parseBlock(&$bytes, $length)
    {
        $remaining = $length;

        if (0 === ($available = min($remaining, $this->header['length'] - $this->offset))) {

            return 0;
        }

        if (-1 === ($count = parent::input($bytes, $available))) {

            return -1;
        }

        $this->column = $this->offset % $this->width;

        $this->offset += $count;

        if (self::TYPE_ASCII === $this->header['type']) {

            $bytes = str_replace("\r", "\n", $bytes);
        }

        if (self::TYPE_BINARY === $this->header['type'] && true === $this->convertToHex) {

            $bin2hex = new AsciiHexadecimalFormatInputStream(new BinaryToAsciiHexadecimalInputStream(new StringInputStream($bytes)), $this->column, $this->width);

            $bytes = '';

            while (-1 !== ($bin2hex->read($hex, 2 * $length))) {

                $bytes .= $hex;
            }
        }

        $remaining -= $count;

        return $length - $remaining;
    }

    /**
     * This method is called when header has been parsed successfully.
     */
    protected function postParseHeader()
    {
    }

    /**
     * This method is called after the header is reset.
     */
    protected function postResetHeader()
    {
    }
}
