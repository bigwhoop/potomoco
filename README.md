# potomoco (.po to .mo compiler)

potomoco is a gettext msgfmt implementation in PHP.
It compiles .po files to binary .mo files.

## Requirements

- PHP 5.3+
- PSR-0 autoloading

## Use

    <?php
    use BigWhoop\Potomoco\Compiler.php
    
    $compiler = new Compiler();
    $compiler->compile('/path/to/your/file.po'); // Generates '/path/to/your/file.mo'
    $compiler->compile('/path/to/your/file.po', '/path/to/other/compiled.mo');

You can also use your own parser (default parser is `BigWhoop\Potomoco\Parser`).

    $compiler->setParser(new YourParser());

Custom parsers need to implement the `BigWhoop\Potomoco\ParserInterface` and return an
array with `BigWhoop\Potomoco\Message` objects.