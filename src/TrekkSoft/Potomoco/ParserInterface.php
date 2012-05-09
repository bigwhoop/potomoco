<?php
/**
 * This file is part of potomoco.
 *
 * (c) TrekkSoft AG (www.trekksoft.com)
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * @author Philippe Gerber <philippe@bigwhoop.ch>
 */
namespace TrekkSoft\Potomoco;

interface ParserInterface
{
    /**
     * @param string $poPath
     * @return array   Array of Message objects
     */
    public function parse($poPath);
}
