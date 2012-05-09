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
    /**
     * @var ParserInterface
     */
    private $parser;
    
    
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
        
        if (null == $moPath) {
            $moPath = str_replace('.po', '.mo', $poPath);
        }
        
        file_put_contents($moPath, $data);
        
        return $this;
    }
    
    
    /**
     * @param array $messages
     * @return bool
     */
    public function searchMetaMessage(array $messages)
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
    private function getMetaMessage()
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
        if (!$this->searchMetaMessage($messages)) {
            $messages[] = $this->getMetaMessage();
        }
        
        $numHeaderItems                = 7;
        $sizeHeaderItem                = 4;
        
        $numStrings                    = count($messages);
        $sizeStringIndex               = 8;
        $sizeStringsTable              = $numStrings * $sizeStringIndex;
        $offsetOriginalStringsTable    = $numHeaderItems * $sizeHeaderItem;
        $offsetTranslationStringsTable = $offsetOriginalStringsTable + $sizeStringsTable;
        
        $numHashTableItems             = 0;
        $sizeHashTableItem             = 4;
        $sizeHashTable                 = $numHashTableItems * $sizeHashTableItem;
        $offsetHashTable               = $offsetTranslationStringsTable + $sizeStringsTable;
        
        $offsetStrings                 = $offsetHashTable + $sizeHashTable;
        
        // Binary data
        $data = pack(
            str_repeat('V', $numHeaderItems),
            0x950412de,                             // magic number = 0x950412de
            0,                                      // file format revision = 0
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
            
            // Append "length" and "offset" to the "strings table"
            $data .= pack('VV', strlen($originalString), $offsetHashTable + strlen($originalStrings));
            
            $originalStrings .= $originalString . "\x00";
        }
        
        // Translation strings
        $translationStrings       = '';
        $offsetTranslationStrings = $offsetStrings + strlen($originalStrings);
        foreach ($messages as $message) {
            $translationString = $message->isPlural()
                               ? join("\x00", $message->msgStrPlural)
                               : $message->msgStr;
            
            // Append "length" and "offset" to the "strings table"
            $data .= pack('VV', strlen($translationString), $offsetTranslationStrings + strlen($translationStrings));
            
            $translationStrings .= $translationString . "\x00";
        }
        
        $data .= $originalStrings . $translationStrings;
        
        return $data;
    }
}
