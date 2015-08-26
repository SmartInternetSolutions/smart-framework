<?php

class SF_Helper_XMLElement extends SimpleXMLElement
{   
	public function searchDescendant($name)
	{
		foreach ($this->children() as $child) {
			if ($child->getName() === $name) {
				return $child;
			}

			$found = $child->searchDescendant($name);
			if ($found !== null) {
				return $found;
			}
		}

		return null;
	}
	
	public function setAttribute($name, $value, $ns = null)
	{
		$attrs = $this->attributes();
		
		if (isset($attrs->$name)) {
			$attrs->$name = $value;
		} else {
			$this->addAttribute($name, $value, $ns);
		}
		
		return $this;
	}
    
    public function addCData($cdata)
    {
        $node = dom_import_simplexml($this);
        
        $node->appendChild($node->ownerDocument->createCDATASection($cdata)); 
    }
}
