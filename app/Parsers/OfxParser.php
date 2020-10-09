<?php namespace LBudget\Parsers;

class OfxParser {
	private $file;
	/**
	 * 
	 * @param resource $file
	 */
	function __construct($file) {
		$this->file = $file;
	}


	/**
	 * 
	 * handles when response is all one line, when tag is on different lines from content, etc.
	 * Most ofx parsers use bad regular expressions to close tags. We instead parse
	 * the actual content, and make the assumption that a tag with content is never a parent.
	 * This seems to work for most possible responses.
	 */
	function parse() {
		//skip to ofx
		
		while(($string = trim(fgets($this->file))) != 'OFXHEADER:100') {
			continue;
		}
		while($string = trim(fgets($this->file))) {
			if ($string[0] === '<') {
				break;
			}
		}
		$this->tags = [];
		$this->curTag = new ofxTag();
		$this->tags[] = $this->curTag;
		$this->breakIntoTags($string);
		while(!feof($this->file)) {
			$string = trim(fgets($this->file));
			$this->breakIntoTags($string);
		}
		$this->curTag = &$this->tags[0];
		return $this->tags;
	}
	
	function parseTag($line) {
		if($this->curIndex >= $this->lineLength) {
			return;
		}
		if($line[$this->curIndex] == '<') {
			
			$nextIndex = strpos($line, '>', $this->curIndex);
			$tagName = substr($line, $this->curIndex+1, $nextIndex - ($this->curIndex+1));
			$this->curIndex = $nextIndex+1;
			//for ofx, if it's a new tag, and we have seen any data, it's a sibling.
			if(isset($this->curTag->content)) {
				$this->curTag = &$this->curTag->parent;
			}
			if($tagName != '/' . $this->curTag->name) {
				$this->curTag->children[] = new ofxTag();
				end($this->curTag->children)->parent = $this->curTag;
				$this->curTag = &$this->curTag->children[count($this->curTag->children)-1];
				$this->curTag->name = $tagName;
				$this->parseTag($line);
			} elseif($tagName == '/' . $this->curTag->name) {
				$this->curTag = &$this->curTag->parent;
			}
		} else {
			$nextIndex = strpos($line, '<', $this->curIndex);
			if($nextIndex) {
				$this->curTag->content .= substr($line, $this->curIndex, $nextIndex - $this->curIndex);
				$this->curIndex = $nextIndex;
			} else {
				$this->curTag->content = substr($line, $this->curIndex);
				$this->curIndex = $this->lineLength;
			}
		}
	}
	
	function breakIntoTags($line) {
		$this->curIndex = 0;
		$this->lineLength = strlen($line);
		while($this->curIndex < $this->lineLength) {
			$this->parseTag($line);
		}
	}
	
	private function _recursiveFindTag($names, $startFrom = null, $tag = null, $foundStart = false) {
		if($tag == null) {
			$tag = &$this->tags[0];	
		}
		if($startFrom == null) {
			$foundStart = true;
		}
		if($foundStart && in_array($tag->name, $names)) {
			return [$tag, $foundStart];
		} else {
			if(!$foundStart && $tag === $startFrom) {
				$foundStart = true;
			}
			foreach($tag->children as $index => &$childTag) {
				list($answer, $foundStart) = $this->_recursiveFindTag($names, $startFrom, $childTag, $foundStart);
				if($answer) {
					return array($answer, $foundStart);
				}
			}
		}
		$answer = null;
		return array($answer, $foundStart);
	}
	
	function findTag($names, $startFrom = null, $tag = null, $foundStart = false) {
		list($tag, $tmp) = $this->_recursiveFindTag($names, $startFrom, $tag, $foundStart);
		return $tag;
	}
}