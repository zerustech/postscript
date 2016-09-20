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
     * @param bool $convertToHex Indicates whether to convert the eexec data block to ascii
     * hexadecimal format, true by default.
     * @param int $width The maximum number of columns for binary data block in
     * hexadecimal format.
     */
    public function __construct(InputStreamInterface $input, $convertToHex = true, $width = 32)
    {
        parent::__construct($input);

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
     * This method reads up to 6 bytes from current stream and parses PFB header
     * information.
     *
     * @return int The actual number of bytes read, or -1 if eof.
     * @throws \RuntimeException If failed to parse the header information.
     */
    public function parseHeader()
    {
        $parsed = false;

        if (true === $this->ready) {

            return true;
        }

        if (-1 === parent::input($header, 6)) {

            return false;
        }

        if (strlen($header) >= 1 && self::MAGIC_NUMBER !== ord($header[0])) {

            throw new \RuntimeException(sprintf("Failed to parse margic number."));
        }

        if (strlen($header) >= 2 && !in_array(ord($header[1]), [static::TYPE_ASCII, static::TYPE_BINARY, static::TYPE_EOF])) {

            throw new \RuntimeException(sprintf("Failed to parse data segment type information."));
        }

        if (strlen($header) >= 2 && ord($header[1]) === self::TYPE_EOF) {

            $this->header = [];
            $this->header['magic-number'] = self::MAGIC_NUMBER;
            $this->header['type'] = ord($header[1]);
            $this->header['length'] = 0;
            $this->offset = 0;
            $this->ready = true;
            $parsed = false;
        }

        if (6 === strlen($header) && ord($header[1]) !== self::TYPE_EOF) {

            $this->header = [];
            $this->header['magic-number'] = self::MAGIC_NUMBER;
            $this->header['type'] = ord($header[1]);
            $this->header['length'] = ord($header[2]) | ord($header[3]) << 8 | ord($header[4]) << 16 | ord($header[5]) << 24;
            $this->offset = 0;
            $this->ready = true;
            $parsed = true;
        }

        $this->afterParseHeader();

        return $parsed;
    }

    /**
     * This method returns all bytes of current data block.
     *
     * @return string The bytes of current data block, or null if EOF.
     */
    public function readBlock()
    {
        $block = null;

        if (true === $this->parseHeader()) {

            $block = '';

            while (-1 !== ($this->read($bytes, 1024))) {

                $block .= $bytes;
            }

            // skip the last block.
            if (1 === preg_match('/^([\n\r\t ]*0[\n\r\t ]*){512}cleartomark$/', $block)) {

                $block = null;
            }
        }

        return $block;
    }

    /**
     * {@inheritdoc}
     *
     * This method keeps reading pfb bytes from the subordinate stream and
     * converting the pfb bytes into pfa format, until ``$length`` number of pfa
     * bytes have been generated, EOB (end of block) or EOF is reached.
     *
     * NOTE: the ``$parseHeader()`` method must be called before calling this
     * method, otherwise, this method will just return -1.
     *
     * @return int The number of pfa bytes generated, or -1 if EOF.
     */
    protected function input(&$bytes, $length)
    {
        if (false === $this->ready) {

            return -1;
        }

        $bytes = '';

        $remaining = $length;

        while ($remaining > 0) {

            // Resets header and breaks on EOB or EOF.
            if (-1 === $count = $this->parseBlock($bytes, $remaining)) {

                $this->resetHeader();

                break;
            }

            // ``$count`` pfb bytes have been parsed.
            $remaining -= $count;
        }

        return (-1 === $count && $remaining === $length) ? -1 : $length - $remaining;
    }

    /**
     * This method reads and parses bytes from current data block.
     *
     * If $this->convertToHex is true, binary block will be converted to
     * hexadecimal format, otherwise the original binary data will be retrieved
     * (the char string bytes are still encrypted and encoded).
     *
     * @param string $bytes The buffer, into which, the bytes will be read and
     * stored.
     * @param int $length The requested number of bytes to read.
     * @return int The actual number of bytes read, or -1 if EOB (end of block) or EOF.
     */
    protected function parseBlock(&$bytes, $length)
    {
        $remaining = $length;

        $count = 0;

        // Returns -1 if EOB or EOF.
        if (0 === ($available = min($remaining, $this->header['length'] - $this->offset)) || -1 === ($count = parent::input($bytes, $available))) {

            return -1;
        }

        // Converts bytes read to ascii hexadecimal if necessary.
        if (self::TYPE_BINARY === $this->header['type'] && true === $this->convertToHex) {

            $this->column = $this->offset % $this->width;

            $bin2hex = new AsciiHexadecimalFormatInputStream(new BinaryToAsciiHexadecimalInputStream(new StringInputStream($bytes)), $this->column, $this->width);

            $bytes = '';

            while (-1 !== ($bin2hex->read($hex, 2 * $length))) {

                $bytes .= $hex;
            }
        }

        // Converts ascii data block to linux format.
        if (self::TYPE_ASCII === $this->header['type']) {

            $bytes = str_replace("\r", "\n", $bytes);
        }

        $this->offset += $count;

        $remaining -= $count;

        return $length - $remaining;
    }

    /**
     * This method is called when header has been parsed successfully.
     */
    protected function afterParseHeader()
    {
    }

    /**
     * When all bytes of current data block has been read and converted, this
     * method resets header, buffer as well as other variables to their initial
     * values.
     */
    private function resetHeader()
    {
        $this->ready = false;
        $this->header = null;
        $this->offset = 0;
        $this->column = 0;
    }
}
