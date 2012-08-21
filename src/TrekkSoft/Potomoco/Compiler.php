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
 * Compiles a .PO file (gettext) into its binary representation (.MO)
 * 
 * @link http://www.gnu.org/software/gettext/manual/gettext.html#MO-Files
 */
class Compiler
{
    const MAGIC_NUMBER         = 0x950412de;
    const REVISION             = 0;
    const NUM_HEADER_ITEMS     = 7;
    const NUM_HASH_TABLE_ITEMS = 0;
    const SIZE_HEADER_ITEM     = 4; // 32 Bits
    const SIZE_STRING_INDEX    = 8; // 64 Bits
    const SIZE_HASH_TABLE_ITEM = 4; // 32 Bits
    
    /**
     * @var ParserInterface|null
     */
    private $parser = null;
    
    
    /**
     * @param ParserInterface $v
     * @return Compiler
     */
    public function setParser(ParserInterface $v)
    {
        $this->parser = $v;
        return $this;
    }
    
    
    /**
     * @return ParserInterface
     */
    public function getParser()
    {
        if (!$this->parser) {
            $this->parser = new Parser();
        }
        
        return $this->parser;
    }
    
    
    /**
     * @param string $poPath
     * @param string|null $moPath
     * @throws \InvalidArgumentException
     * @return Compiler
     */
    public function compile($poPath, $moPath = null)
    {
        if (!file_exists($poPath)) {
            throw new \InvalidArgumentException("No .po file at '$poPath'.");
        }
        
        $parser   = $this->getParser();
        $messages = $parser->parse($poPath);
        $data     = $this->compileMessages($messages);
        
        if (null === $moPath) {
            $moPath = str_replace('.po', '.mo', $poPath);
        }
        
        file_put_contents($moPath, $data);
        
        return $this;
    }
    
    
    /**
     * @param array $messages
     * @return bool
     */
    public function hasMetaMessage(array $messages)
    {
        foreach ($messages as $message) {
            if ($message->msgStr === '') {
                return true;
            }
        }
        
        return false;
    }
    
    
    /**
     * @return Message
     */
    private function getDefaultMetaMessage()
    {
        $metaMessage = new Message();
        $metaMessage->msgStr = join('\n', array(
            "MIME-Version: 1.0",
            "Content-Type: text/plain; charset=UTF-8",
            "Content-Transfer-Encoding: 8bit",
        ));
        
        return $metaMessage;
    }
    
    
    /**
     * @param array $messages       Array of Message objects
     * @return string
     */
    private function compileMessages(array $messages)
    {
        // Meta message is the one with an empty string message id.
        // If none was set we define our own. So at least the encoding
        // will be present.
        if (!$this->hasMetaMessage($messages)) {
            array_unshift($messages, $this->getDefaultMetaMessage());
        }
        
        $numStrings                    = count($messages);
        $sizeStringsTable              = $numStrings * self::SIZE_STRING_INDEX;
        
        $offsetOriginalStringsTable    = self::NUM_HEADER_ITEMS * self::SIZE_HEADER_ITEM;
        $offsetTranslationStringsTable = $offsetOriginalStringsTable + $sizeStringsTable;
        
        $sizeHashTable                 = self::NUM_HASH_TABLE_ITEMS * self::SIZE_HASH_TABLE_ITEM;
        $offsetHashTable               = $offsetTranslationStringsTable + $sizeStringsTable;
        
        $offsetStrings                 = $offsetHashTable + $sizeHashTable;
        
        // Binary data
        $data = pack(
            str_repeat('V', self::NUM_HEADER_ITEMS),
            self::MAGIC_NUMBER,                     // magic number = 0x950412de
            self::REVISION,                         // file format revision = 0
            $numStrings,                            // number of strings
            $offsetOriginalStringsTable,            // offset of table with original strings
            $offsetTranslationStringsTable,         // offset of table with translation strings
            $sizeHashTable,                         // size of hashing table
            $offsetTranslationStringsTable          // offset of hashing table
        );
        
        // Original strings
        $originalStrings = '';
        foreach ($messages as $message) {
            $originalString = '';
            
            // Add context
            if (is_string($message->msgCtxt)) {
                $originalString .= $message->msgCtxt . "\x04";
            }
            
            // Add message id
            $originalString .= $message->msgId;
            
            // Add plural message id
            if ($message->isPlural()) {
                $originalString .= "\x00" . $message->msgIdPlural;
            }
            
            // Append "length" and "offset" to the index table
            $data .= pack('VV', strlen($originalString), $offsetHashTable + strlen($originalStrings));
            
            // Append the string
            $originalStrings .= $originalString . "\x00";
        }
        
        // Translation strings
        $translationStrings       = '';
        $offsetTranslationStrings = $offsetStrings + strlen($originalStrings);
        foreach ($messages as $message) {
            $translationString = $message->isPlural()
                               ? join("\x00", $message->msgStrPlural)
                               : $message->msgStr;
            
            // Append "length" and "offset" to the index table
            $data .= pack('VV', strlen($translationString), $offsetTranslationStrings + strlen($translationStrings));
            
            // Append the string
            $translationStrings .= $translationString . "\x00";
        }
        
        $data .= $originalStrings . $translationStrings;
        
        return $data;
    }
}
