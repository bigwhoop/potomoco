<?php
/**
 * This file is part of potomoco.
 *
 * (c) Philippe Gerber <philippe@bigwhoop.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace BigWhoop\Potomoco;

interface ParserInterface
{
    /**
     * @param string $poPath
     * @return array   Array of Message objects
     */
    public function parse($poPath);
}
