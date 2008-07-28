<?php
Class PHPJS_Function extends SplFileInfo {
    public $PHPJS;
    public $DocBlock = false;
    public $functionLookup = array();
    
    private $_category = "";
    private $_function = "";
    
    private $_source = "";
    private $_docBlock = "";
    private $_code = "";

    private $_tokWrapHead = array();
    private $_tokWrapTail = array();
    private $_tokDocBlock = array();
    private $_tokRealCode = array();
    
    
    private function _setSource($source) {
        $this->_source = trim($source);
    }
    
    private function _setCategory($category) {
        $this->_category = $category;
    }
    
    private function _setFunction($function) {
        $this->_function = $function;
    }


    private function _array_trim($array) {
        while (strlen(reset($array)) === 0) {
            array_shift($array);
        }
        while (strlen(end($array)) === 0) {
            array_pop($array);
        }
        return $array;
    }     
    
    private function _file2function($file) {
        return basename($file, ".js");    
    }
    
    private function _file2category($file) {
        $parts = explode("/", $file);
        array_pop($parts);
        return array_pop($parts);
    }

    private function _isLineComment($line) {
        return preg_match('/^[\s]*(\/\/|\#|\*)/', $line);
    }
    
    private function _tokenizeSource() {
        
        $recDocBlock = -1;
        
        $this->_tokDocBlock = array();
        $this->_tokWrappers = array();
        $this->_tokRealCode = array();
        
        $src_lines = explode("\n", $this->getSource());
        $src_count = count($src_lines);
        foreach ($src_lines as $i=>$src_line) {
            // Flag begin docBlock 
            if ($this->_isLineComment($src_line) && $recDocBlock == -1) {
                $recDocBlock = 1;
            }
            // Flag end docBlock
            if (!$this->_isLineComment($src_line) && $recDocBlock == 1) {
                $recDocBlock = 2;
            }
            
            if ($recDocBlock == 1) {
                // Record docBlock
                $this->_tokDocBlock[] = $src_line;
            } elseif (!$this->_isLineComment($src_line) && $recDocBlock == -1) {
                // Record begin Wrapper 
                $this->_tokWrapHead[] = $src_line;
            } elseif ($src_count == ($i+1)) {
                // Record end Wrapper
                $this->_tokWrapTail[] = $src_line;
            } else {
                // Record real code
                $this->_tokRealCode[] = $src_line;
            }
        }
        
        $this->_tokRealCode = $this->_array_trim($this->_tokRealCode);
        
        return true;
    }    
    
    
    
    public function PHPJS_Function($file, &$PHPJS){
        // Call Parent SplFileInfo constructor
        parent::__construct($file);
        
        // Reference to mother object
        $this->PHPJS = &$PHPJS;
        
        // Initialize object
        $this->reload();
    }
    
    
    public function reload() {
        $this->_setSource(file_get_contents($this->getRealPath()));
        $this->_setCategory($this->_file2category($this->getRealPath()));
        $this->_setFunction($this->_file2function($this->getRealPath()));
        
        $this->_tokenizeSource();
        
        $this->DocBlock = new PHPJS_DocBlock($this->_tokDocBlock);
    }
    
    /**
     * Recursively get dependencies
     *
     * @param boolean $recurse
     * 
     * @return array
     */
    public function getDependencies($recurse=true) {
        // Own deps
        $list = $this->DocBlock->dependencies;
        
        // Recurse
        if ($recurse) {
            if (count($this->DocBlock->dependencies)) {
                foreach($this->DocBlock->dependencies as $function) {
                    $func = &$this->PHPJS->getFunction($function);
                    $list = array_merge($list, $func->getDependencies());
                }
            }
        }
        
        return $list;
    }
    
    public function getDocBlock() {
        return $this->_tokDocBlock;
    }

    public function getRealCode() {
        return $this->_tokRealCode;
    }

    public function getWrapHead() {
        return $this->_tokWrapHead;
    }
    
    public function getWrapTail() {
        return $this->_tokWrapTail;
    }
    
    public function getSource() {
        return $this->_source;
    }

    public function getCategory() {
        return $this->_category;
    }
    
    public function getFunction() {
        return $this->_function;
    }
}

?>