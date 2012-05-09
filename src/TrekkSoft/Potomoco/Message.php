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

class Message
{
    /**
     * @var string
     */
    public $msgId = '';
    
    /**
     * @var string
     */
    public $msgIdPlural = '';
    
    /**
     * @var string
     */
    public $msgStr = '';
    
    /**
     * @var array
     */
    public $msgStrPlural = array();
    
    /**
     * @var null|string
     */
    public $msgCtxt = null;
    
    
    /**
     * @return bool
     */
    public function hasTranslation()
    {
        return !empty($this->msgStr) || !empty($this->msgStrPlural);
    }
    
    
    /**
     * @return bool
     */
    public function isPlural()
    {
        return !empty($this->msgIdPlural);
    }
}
