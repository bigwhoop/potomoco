# potomoco (.po to .mo compiler)

potomoco is a gettext msgfmt implementation in PHP.
It compiles .po files to binary .mo files.

## Requirements

- PHP 5.3+
- PSR-0 autoloading

## Use

    <?php
    use TrekkSoft\Potomoco\Compiler;
    
    $compiler = new Compiler();
    
    // Generates '/path/to/your/file.mo'
    $compiler->compile('/path/to/your/file.po');
    
    // Generates '/path/to/other/compiled.mo'
    $compiler->compile('/path/to/your/file.po', '/path/to/other/compiled.mo');

You can also use your own parser (default parser is `TrekkSoft\Potomoco\Parser`).

    $compiler->setParser(new YourParser());

Custom parsers need to implement the `TrekkSoft\Potomoco\ParserInterface` and return an
array with `TrekkSoft\Potomoco\Message` objects.