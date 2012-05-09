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

/**
 * Parses a .PO file into an array of messages
 * 
 * @link http://www.gnu.org/software/gettext/manual/gettext.html#PO-Files
 */
class Parser implements ParserInterface
{
    const TOKEN_MSGID         = 'msgid';
    const TOKEN_MSGID_PLURAL  = 'msgid_plural';
    const TOKEN_MSGSTR        = 'msgstr';
    const TOKEN_MSGSTR_PLURAL = 'msgstr[';
    const TOKEN_MULTILINE_STR = '"';
    const TOKEN_CONTEXT       = 'msgctxt';
    const TOKEN_FLAG          = '#,';
    const TOKEN_REFERENCE     = '#:';
    
    /**
     * @var array
     */
    static private $tokenParsingMap = array(
        self::TOKEN_MSGID_PLURAL  => 'parseTranslatedStringPlural',
        self::TOKEN_MSGID         => 'parseTranslatedString',
        self::TOKEN_MSGSTR_PLURAL => 'parseUntranslatedStringPlural',
        self::TOKEN_MSGSTR        => 'parseUntranslatedString',
        self::TOKEN_MULTILINE_STR => 'parseMultiLineString',
        //self::TOKEN_CONTEXT       => 'parseContext',
        //self::TOKEN_FLAG          => 'parseFlag',
        //self::TOKEN_REFERENCE     => 'parseReference',
    );
    
    /**
     * @var Message
     */
    private $currentMessage;
    
    /**
     * @var array
     */
    private $messages = array();
    
    /**
     * @var string
     */
    private $multiLineState = '';
    
    
    /**
     * @param string $poPath
     * @return array        Array of Message objects
     * @throws \InvalidArgumentException
     */
    public function parse($poPath)
    {
        if (!file_exists($poPath)) {
            throw new \InvalidArgumentException("No .po file at '$poPath'.");
        }
        
        $contents = file_get_contents($poPath);
        
        // Normalize newlines
        $contents = str_replace(array("\r\n", "\r"), array("\n", "\n"), $contents);
        
        // Make sure the kitchen is clean :)
        $this->reset();
        
        // Parse each line
        foreach (explode("\n", $contents) as $line) {
            $this->parseLine($line);
        }
        
        // Make sure we add the last message (if any is available)
        $this->addCurrentMessage();
        
        return $this->messages;
    }
    
    
    /**
     * @return Parser
     */
    private function reset()
    {
        $this->messages = array();
        $this->currentMessage = new Message();
        
        return $this;
    }
    
    
    /**
     * @param string $line
     */
    private function parseLine($line)
    {
        $line = trim($line);
        
        foreach (self::$tokenParsingMap as $token => $methodName) {
            if (0 === strpos($line, $token)) {
                $this->$methodName($line);
                break;
            }
        }
    }
    
    
    /**
     * Checks whether the current message can be added to the message stack
     */
    private function addCurrentMessage()
    {
        if ($this->currentMessage->hasTranslation()) {
            $this->messages[] = $this->currentMessage;
            $this->currentMessage = new Message();
        }
    }
    
    
    /**
     * @param string $line
     */
    private function parseTranslatedString($line)
    {
        $this->addCurrentMessage();
        
        $chunks = explode(' ', $line, 2);
        
        if (isset($chunks[1])) {
            $this->currentMessage->msgId = $this->normalizeString($chunks[1]);
            $this->multiLineState = self::TOKEN_MSGID;
        }
    }
    
    
    /**
     * @param string $line
     */
    private function parseTranslatedStringPlural($line)
    {
        $chunks = explode(' ', $line, 2);
        
        if (isset($chunks[1])) {
            $this->currentMessage->msgIdPlural = $this->normalizeString($chunks[1]);
        }
    }
    
    
    /**
     * @param string $line
     */
    private function parseUntranslatedString($line)
    {
        $chunks = explode(' ', $line, 2);
        
        if (isset($chunks[1])) {
            $this->currentMessage->msgStr = $this->normalizeString($chunks[1]);
            $this->multiLineState = self::TOKEN_MSGSTR;
        }
    }
    
    
    /**
     * @param string $line
     */
    private function parseUntranslatedStringPlural($line)
    {
        $chunks = explode(' ', $line, 2);
        
        if (count($chunks) != 2) {
            return;
        }
        
        list($token, $data) = $chunks;
        
        $matches = array();
        if (preg_match('|msgstr\[([0-9]+)\]|i', $token, $matches)) {
            $idx = $matches[1];
            $this->currentMessage->msgStrPlural[$idx] = $this->normalizeString($data);
        }
    }
    
    
    /**
     * @param string $line
     */
    private function parseMultiLineString($line)
    {
        $line = $this->normalizeString($line);
        
        switch ($this->multiLineState) {
            case self::TOKEN_MSGID  :   $this->currentMessage->msgId  .= $line;   break;
            case self::TOKEN_MSGSTR :   $this->currentMessage->msgStr .= $line;   break;
        }
    }
    
    
    /**
     * @param string $string
     * @return string
     */
    private function normalizeString($string)
    {
        if (empty($string)) {
            return '';
        }
        
        // Strip leading "
        if ($string[0] === '"') {
            $string = mb_substr($string, 1);
        }
        
        // Strip trailing "
        if ('"' === mb_substr($string, -1, 1)) {
            $string = mb_substr($string, 0, -1);
        }
        
        return $string;
    }
}
