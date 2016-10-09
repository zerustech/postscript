[![Build Status](https://api.travis-ci.org/zerustech/postscript.svg)](https://travis-ci.org/zerustech/postscript)

ZerusTech Postscript Component
================================================
The *ZerusTech Postscript Component* is a library that provides classes and
utilities to work with variant postscript files, including font type 1 and etc.

Installation
-------------

You can install this component in 2 different ways:

* Install it via Composer
```bash
$ cd <project-root-directory>
$ composer require zerustech/postscript
```

* Use the official Git repository [zerustech/postscript][2]

Examples
-------------

### AsciiHexadecimalToBinaryInputStream ###

This class reads ascii hexadecimal bytes from the subordinate input stream and
converts them to binary bytes. Two hexadecimal bytes are converted to one binary
byte.

```php
<?php

require_once __DIR__.'/vendor/autoload.php';

use ZerusTech\Component\IO\Stream\Input\StringInputStream;
use ZerusTech\Component\Postscript\Font\TypeOne\Stream\Input;

$in = new StringInputStream('68656c6c6f');

$input = new Input\AsciiHexadecimalToBinaryInputStream($in);

$count = $input->read($string, 5); // reads upto 5 binary bytes from the stream. 

printf("%d bytes read: %s\n", $count, $string); // 'hello'

```

### BinaryToAsciiHexadecimalInputStream ###

This class reads binary bytes from the subordinate input stream and converts
them to ascii hexadecimal bytes. One binary byte is converted to two ascii
hexadecimal bytes.

```php
<?php

require_once __DIR__.'/vendor/autoload.php';

use ZerusTech\Component\IO\Stream\Input\StringInputStream;
use ZerusTech\Component\Postscript\Font\TypeOne\Stream\Input;

$in = new StringInputStream('hello');

$input = new Input\BinaryToAsciiHexadecimalInputStream($in);

$count = $input->read($string, 10); // reads upto 10 hex bytes from the stream. 

printf("%d bytes read: %s\n", $count, $string); // '68656C6C6F'

```

### AsciiHexadecimalFormatInputStream ###

This class reads ascii hexadecimal bytes from the subordinate input stream and
formats them by inserting line feeds ``"\n"``.

```php
<?php

require_once __DIR__.'/vendor/autoload.php';

use ZerusTech\Component\IO\Stream\Input\StringInputStream;
use ZerusTech\Component\Postscript\Font\TypeOne\Stream\Input;

$in = new StringInputStream('68656c6c6f68656c6c6f68656c6c6f');

// Formats hexadecimal string as follows:
// Current column: 0
// Columns per line: 5
$input = new Input\AsciiHexadecimalFormatInputStream($in, 0, 5);

// reads upto 33 hex bytes, including line feeds from the stream. 
$count = $input->read($string, 33);

// "68656c6c6f\n68656c6c6f\n68656c6c6f\n"
printf("%d bytes read: %s\n", $count, $string);
```
### CharStringDecodeInputStream ###

This class reads char string encoded bytes from the subordinate input stream and
decodes them to plain text.

```php
<?php

require_once __DIR__.'/vendor/autoload.php';

use ZerusTech\Component\IO\Stream\Input\StringInputStream;
use ZerusTech\Component\Postscript\Font\TypeOne\Stream\Input;

$in = new StringInputStream("\x8e\x8b\x0c\x10\x0c\x11\x0c\x11\x0c\x21\x0b");

$input = new Input\CharStringDecodeInputStream($in);

$string = '';

while (null != $token = $input->readToken()) {

    $string .= $token.' ';
}

// "3 0 callothersubr pop pop setcurrentpoint return "
printf("%s\n", $string);

```

### CharStringDecryptInputStream ###

This class reads char string encrypted bytes from the subordinate input stream
and decrypt them to char string encoded bytes.

```php
<?php

require_once __DIR__.'/vendor/autoload.php';

use ZerusTech\Component\IO\Stream\Input\StringInputStream;
use ZerusTech\Component\Postscript\Font\TypeOne\Stream\Input;

$in = new StringInputStream("\x10\xbf\x31\x70\x9a\xa9\xe3\x3d\xee");

$input = new Input\CharStringDecryptInputStream($in);

$string = '';

$count = $input->read($string, 5);

printf("%d bytes read: %s\n", $count, $string); // 'hello'
```

### CharStringFormatInputStream ###

This class reads char string plain text (decrypted and decoded), from the
subordinate input stream and formats them by inserting line feeds ``"\n"`` and
tabs ``"\t"``.

```php
<?php

require_once __DIR__.'/vendor/autoload.php';

use ZerusTech\Component\IO\Stream\Input\StringInputStream;
use ZerusTech\Component\Postscript\Font\TypeOne\Stream\Input;

$in = new StringInputStream("-45 3 -10 10 -35 74 rrcurveto -249 568 rlineto ");

$input = new Input\CharStringFormatInputStream($in);

//  "-45 3 -10 10 -35 74 rrcurveto\n\t"
printf("1st formated string: %s\n", $input->format());

// "-249 568 rlineto\n\t"
printf("2nd formatted string: %s\n", $input->format());
```

### DecryptInputStream ###

This class reads encrypted bytes from the subordinate input stream and performs
eexec or char string decryption.

```php
<?php

require_once __DIR__.'/vendor/autoload.php';

use ZerusTech\Component\IO\Stream\Input\StringInputStream;
use ZerusTech\Component\Postscript\Font\TypeOne\Stream\Input;

$in = new StringInputStream("\xe9\x8d\x09\xd7\x6c\xe6\x99\x52\xf0");
// R: 55665
// n: 4
$input = new Input\DecryptInputStream($in, 55665, 4);

// Performs eexec decryption
$count = $input->read($string, 5);
// hello
printf("%d bytes read: %s\n", $count, $string);

$in = new StringInputStream("\x10\xbf\x31\x70\x9a\xa9\xe3\x3d\xee");
// R: 4330
// n: 4
$input = new Input\DecryptInputStream($in, 4330, 4);

// Performs char string decryption
$count = $input->read($string, 5);
// hello
printf("%d bytes read: %s\n", $count, $string);
```

### EexecDecryptInputStream ###

This class reads eexec encrypted bytes from the subordinate input stream and
performs eexec decryption.

```php
<?php

require_once __DIR__.'/vendor/autoload.php';

use ZerusTech\Component\IO\Stream\Input\StringInputStream;
use ZerusTech\Component\Postscript\Font\TypeOne\Stream\Input;

$in = new StringInputStream("\xe9\x8d\x09\xd7\x6c\xe6\x99\x52\xf0");
$input = new Input\EexecDecryptInputStream($in);

// Performs eexec decryption
$count = $input->read($string, 5);
// hello
printf("%d bytes read: %s\n", $count, $string);
```
### PfbToPfaInputStream ###

This class reads pfb bytes from the subordinate input stream and converts the
pfb bytes to pfa bytes.

```php
<?php

require_once __DIR__.'/vendor/autoload.php';

use ZerusTech\Component\IO\Stream\Input\StringInputStream;
use ZerusTech\Component\IO\Stream\Input\FileInputStream;
use ZerusTech\Component\IO\Stream\Output\FileOutputStream;

use ZerusTech\Component\Postscript\Font\TypeOne\Stream\Input;

$base = __DIR__.'/Tests/Fixtures/Font/TypeOne/';

$in = new FileInputStream($base.'NimbusRomanNo9L-Regular.pfb', 'rb');

$input = new Input\PfbToPfaInputStream($in);

$out = new FileOutputStream(__DIR__.'/test.pfa', 'wb');

// Reads pfb blocks from pfb file and converts them to pfa blocks.
while (null !== $block = $input->readBlock()) {

    $out->write($block);
}
```

### PfbToDisasmInputStream ###

This class reads pfb bytes from the subordinate input stream and converts the
pfb bytes to disasm bytes.

```php
<?php

require_once __DIR__.'/vendor/autoload.php';

use ZerusTech\Component\IO\Stream\Input\StringInputStream;
use ZerusTech\Component\IO\Stream\Input\FileInputStream;
use ZerusTech\Component\IO\Stream\Output\FileOutputStream;

use ZerusTech\Component\Postscript\Font\TypeOne\Stream\Input;

$base = __DIR__.'/Tests/Fixtures/Font/TypeOne/';

$in = new FileInputStream($base.'NimbusRomanNo9L-Regular.pfb', 'rb');

$input = new Input\PfbToDisasmInputStream($in);

$out = new FileOutputStream(__DIR__.'/test.disasm', 'wb');

// Reads pfb blocks from pfb file and converts them to disasm format.
while (null !== $block = $input->readBlock()) {

    $out->write($block);
}
```

### AsciiHexadecimalFormatOutputStream ###

This class formats the provided ascii hexadecimal bytes by inserting line feeds
``"\n"`` and writes them to the subordinate output stream.

```php
<?php

require_once __DIR__.'/vendor/autoload.php';

use ZerusTech\Component\IO\Stream\Output\StringOutputStream;
use ZerusTech\Component\Postscript\Font\TypeOne\Stream\Output;

$string = '68656c6c6f68656c6c6f68656c6c6f';

$out = new StringOutputStream();

// Format hexadecimal string as follows:
// Current column: 0
// Columsn per line: 5
$output = new Output\AsciiHexadecimalFormatOutputStream($out, 0, 5);

$output->write($string);

// "68656c6c6f\n68656c6c6f\n68656c6c6f\n"
printf("Bytes written: %s\n", $out->__toString());
```

### AsciiHexadecimalToBinaryOutputStream ###

This class converts the provided ascii hexadecimal bytes to binary bytes and
writes them to the subordinate output stream.

```php
<?php

require_once __DIR__.'/vendor/autoload.php';

use ZerusTech\Component\IO\Stream\Output\StringOutputStream;
use ZerusTech\Component\Postscript\Font\TypeOne\Stream\Output;

$string = '68656c6c6f';

$out = new StringOutputStream();

$output = new Output\AsciiHexadecimalToBinaryOutputStream($out);

$output->write($string);

// "hello"
printf("Bytes written: %s\n", $out->__toString());
```

### BinaryToAsciiHexadecimalOutputStream ###

This class converts the provided binary bytes to ascii hexadecimal bytes and
writes them to the subordinate output stream.

```php
<?php

require_once __DIR__.'/vendor/autoload.php';

use ZerusTech\Component\IO\Stream\Output\StringOutputStream;
use ZerusTech\Component\Postscript\Font\TypeOne\Stream\Output;

$string = 'hello';

$out = new StringOutputStream();

$output = new Output\BinaryToAsciiHexadecimalOutputStream($out);

$output->write($string);

// "68656C6C6F"
printf("Bytes written: %s\n", $out->__toString());
```

### CharStringEncodeOutputStream ###

This class performs char string encoding on the provided bytes and writes them
to the subordinate output stream.

```php
<?php

require_once __DIR__.'/vendor/autoload.php';

use ZerusTech\Component\IO\Stream\Output\StringOutputStream;
use ZerusTech\Component\Postscript\Font\TypeOne\Stream\Output;

$string = '3 0 callothersubr pop pop setcurrentpoint return ';

$out = new StringOutputStream();

$output = new Output\CharStringEncodeOutputStream($out);

$output->write($string);

// "\x8e\x8b\x0c\x10\x0c\x11\x0c\x11\x0c\x21\x0b"
printf("Bytes written: %s\n", $out->__toString());
```
### CharStringEncryptOutputStream ###

This class performs char string encryption on the provided char string encoded
bytes and writes them to the subordinate output stream.

```php
<?php

require_once __DIR__.'/vendor/autoload.php';

use ZerusTech\Component\IO\Stream\Output\StringOutputStream;
use ZerusTech\Component\Postscript\Font\TypeOne\Stream\Output;

$string = 'hello';

$out = new StringOutputStream();

$output = new Output\CharStringEncryptOutputStream($out);

$output->write($string);

// "\x10\xbf\x31\x70\x9a\xa9\xe3\x3d\xee"
printf("Bytes written: %s\n", $out->__toString());
```

### EexecEncryptOutputStream ###

This class performs eexec encryption on the provided eexec bytes and writes them
to the subordinate output stream.

```php
<?php

require_once __DIR__.'/vendor/autoload.php';

use ZerusTech\Component\IO\Stream\Output\StringOutputStream;
use ZerusTech\Component\Postscript\Font\TypeOne\Stream\Output;

$string = 'hello';

$out = new StringOutputStream();

$output = new Output\EexecEncryptOutputStream($out);

$output->write($string);

// "\xe9\x8d\x09\xd7\x6c\xe6\x99\x52\xf0"
printf("Bytes written: %s\n", $out->__toString());
```

### EncryptOutputStream ###

This class performs eexec or char string encryption on the provided bytes and
writes them to the subordinate output stream.

```php
<?php

require_once __DIR__.'/vendor/autoload.php';

use ZerusTech\Component\IO\Stream\Output\StringOutputStream;
use ZerusTech\Component\Postscript\Font\TypeOne\Stream\Output;

// Performs eexec encryption and uses "0000" as the first 4 random bytes.
$string = 'hello';

$out = new StringOutputStream();

$output = new Output\EncryptOutputStream($out, 55665, "0000");

$output->write($string);

// "\xe9\x8d\x09\xd7\x6c\xe6\x99\x52\xf0"
printf("Bytes written: %s\n", $out->__toString());


// Performs char string encryption and uses "\x00\x00\x00\x00" as the first 4 
// random bytes.
$out = new StringOutputStream();

$output = new Output\EncryptOutputStream($out, 4330, "\x00\x00\x00\x00");

$output->write($string);

// "\x10\xbf\x31\x70\x9a\xa9\xe3\x3d\xee"
printf("Bytes written: %s\n", $out->__toString());
```

References
----------
* [The zerustech/io project][5]
* [Adobe Type 1 Font Format][3]
* [Variant File Formats for Font Type 1][4]

[1]:  https://opensource.org/licenses/MIT "The MIT License (MIT)"
[2]:  https://github.com/zerustech/postscript "The zerustech/postscript Project"
[3]:  https://partners.adobe.com/public/developer/en/font/T1_SPEC.PDF "Adobe Type 1 Font Format"
[4]:  https://www.math.utah.edu/~beebe/fonts/postscript-type-1-fonts.html#postscript-font-background "PostScript Type 1 fonts"
[5]:  https://github.com/zerustech/io "The zerustech/io Project"

License
-------
The *ZerusTech Postscript Component* is published under the [MIT License][1].
