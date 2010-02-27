<?php

	class Plist {
		
		/**
		 * Default constructor
		 * TODO Determine if this is necessary
		 */
		public function __construct() {
		}
		
		/**
		 * Loads a file from the system and parses it as though it is a plist
		 * 
		 * @param string $path Path to the file that should be loaded and parsed
		 * @return array The plist parsed into a native PHP array, or FALSE if the file could not be loaded / parsed.
		 */
		public function load_file($path = NULL) {
			$str_plist = file_get_contents($path);
			
			if ($str_plist !== FALSE) {
				return $this->load_string($str_plist);
			}
			
			return $str_plist;
		}
		
		/**
		 * Parses a string containing XML plist data
		 * 
		 * @param string $str_plist String containing XML plist data
		 * @return array The string parsed into a native PHP array, or FALSE if the string could not be parsed.
		 */
		public function load_string($str_plist = NULL) {
			$pobject = new stdClass();
			$pdoc = DOMDocument::loadXML($str_plist);
			
			// Load the root plist element
			$roots = $pdoc->getElementsByTagName('plist');
			
			// There can not be more than one plist element in the document
			if ($roots->length == 1) {
				$root = $roots->item(0);
				
				// Walk over the child nodes until we hit the root dict element
				for ($i = 0; $i < $root->childNodes->length; $i++) {
					if ($root->childNodes->item($i)->nodeType == XML_ELEMENT_NODE) {
						return $this->process_value_element($root->childNodes->item($i));
					}
				}
			}
			
			return FALSE;
		}
		
		public function save_file($obj_data, $path) {
			file_put_contents($path, $this->save_string(obj_data));
		}
		
		public function save_string($obj_data = NULL) {
			$imp = new DOMImplementation();
			$dtd = $imp->createDocumentType('plist', '-//Apple//DTD PLIST 1.0//EN', 'http://www.apple.com/DTDs/PropertyList-1.0.dtd');
			$odoc = $imp->createDocument('', '', $dtd);
			
			$p_root = $odoc->createElement('plist');
			$odoc->appendChild($p_root);
			
			if (is_array($obj_data)) {
				
			}
			else {
				
			}
			
			return $odoc->saveXML();
		}
		
		/**
		 * Processes a dict[tionary] DOMElement into a PHPObject, will recursively call other processing functions as required
		 * until the entire dictionary had been converted into a PHPObject.
		 * 
		 * @param DOMElement $dict_node DOMElement representing the dict element to be processed
		 * @return stdClass The dictionary as a native PHP object
		 */
		public function process_dict_element(DOMElement $dict_node) {
			/* Every dict element is key-value coded. First there is a <key> element which representing the key for the next element which,
			is that key's value. Parsing will occur sequentially traversing the XML structure of the plist until the entire object is parsed
			at which point the element which is being built will be returned. */
			
			$arr_dict = array();
			
			$key = NULL;
			for ($i = 0; $i < $dict_node->childNodes->length; $i++) {
				$child_node = $dict_node->childNodes->item($i);
				
				if ($child_node->nodeType == XML_ELEMENT_NODE) {
					if ($child_node->localName == 'key') {
						$key = $child_node->textContent;
					}
					else {
						$arr_dict[$key] = $this->process_value_element($child_node);
						$key = NULL;
					}
				}
			}
			
			return $arr_dict;
		}
		
		/**
		 * Processes an array element returning it as a native PHP array
		 * 
		 * @param DOMElement $array_node DOMElement containing an array node requiring processing
		 * @return array The node in a native PHP format
		 */
		public function process_array_element(DOMElement $array_node) {
			$arr_node = array();
			
			for ($i = 0; $i < $array_node->childNodes->length; $i++) {
				$child_node = $array_node->childNodes->item($i);
				
				if ($child_node->nodeType == XML_ELEMENT_NODE) {
					$arr_node[] = $this->process_value_element($child_node);
				}
			}
			
			return $arr_node;
		}
		
		/**
		 * Processes the given element into its appropriate type
		 * 
		 * @param DOMElement $ele Element containing the value to be processed
		 * @return mixed The value in its native PHP type
		 */
		public function process_value_element(DOMElement $ele) {
			switch ($ele->localName) {
				case 'string':
					return (string)$ele->textContent;
					break;
				
				case 'integer':
					return (int)$ele->textContent;
					break;
				
				case 'real':
					return (real)$ele->textContent;
					break;
					
				case 'true':
					return TRUE;
					break;
					
				case 'false':
					return FALSE;
					break;
				
				case 'data':
					return bin2hex($ele->textContent);
					break;
				
				case 'date':
					return date_parse($ele->textContent);
					break;
				
				case 'dict':
					return $this->process_dict_element($ele);
					break;
				
				case 'array':
					return $this->process_array_element($ele);
					break;
			}
		}
	}
	
?>