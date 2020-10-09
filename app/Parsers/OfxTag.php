<?php namespace LBudget\Parsers;

class OfxTag {
	public $name;
	public $content;
	public $parent;
	public $children = [];
	
	function toOFX() {
		$answer = "<{$this->name}>";
		if(isset($this->content)) {
			$answer .= $this->content;
			return $answer;
		}
		foreach($this->children as $child) {
			$answer .= $child->toOFX();
		}
		return $answer . "</{$this->name}>";
		//<INVACCTFROM><BROKERID>{$account->brokerId}<ACCTID>{$accountInfo->acctId}</INVACCTFROM>
	}
}
