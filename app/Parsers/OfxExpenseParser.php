<?php namespace LBudget\Parsers;

class OfxExpenseParser {
	/**
	 * @var OfxParser
	 */
	private $parser;
	private $lastTagNode = null;
	private $lastTrans = null;
	private $lastAccount = null;
	private $currentAccountId = null;
	
	
	function __construct() {
		//do nothing
	}

	function open($filename)
	{
		$this->parser = new OfxParser(fopen($filename, 'r'));
		$this->parser->parse();
	}
	
	function loadFromString($ofx) {
		$this->parser = new OfxParser(fopen('data://text/plain,' . $ofx, 'r'));
		$this->parser->parse();
	}
		
	function findNextTransaction() {
		do {
			//find the next account or transation.
			$newTagNode = $this->parser->findTag(['CCACCTFROM', 'BANKACCTFROM', 'INVSTMTRS', 'STMTTRN', 'INVBUY', 'INVSELL'], $this->lastTagNode);
			if(!$newTagNode) {
				if($this->lastAccount && $this->lastAccount->name == 'INVSTMTRS') {
					//create a balance adjustment for any price fluctuation.
					$lastTrans = $this->parser->findTag(['INVPOSLIST'], null, $this->lastAccount);
					break;
				}
				$lastTrans = null;
				break;
			}
			$this->lastTagNode = $newTagNode;
			//if it's an account, record the account id.
			if($newTagNode->name != 'STMTTRN' && $newTagNode->name != 'INVBUY' && $newTagNode->name != 'INVSELL') {
				$break = false;
				//first record balance adjustments for the previous investment account...
				if($this->lastAccount && $this->lastAccount->name == 'INVSTMTRS') {
					//create a balance adjustment for any price fluctuation.
					$lastTrans = $this->parser->find(['INVPOSLIST'], null, $this->lastAccount);
					$break = true;
				}
				$this->lastAccount = $newTagNode;
				$acctIdTag = $this->parser->findTag(['ACCTID'], null, $newTagNode);
				$this->currentAccountId = $acctIdTag->content;
				if($break)
					break;
			} else {
				$lastTrans = $newTagNode;
				break;
			}
		} while (true);
		//we found a transaction.
		return $lastTrans;
	}

	function getTransaction() {
		$lastTrans = $this->findNextTransaction();
		if ($lastTrans === null) {
			return $lastTrans;
		}
		$answer = array();
		if (!is_object($lastTrans)) {
			var_dump($lastTrans);
		}
		switch ($lastTrans->name) {
			case 'STMTTRN':
				return $this->loadStatementTransaction($lastTrans);
			case 'INVBUY':
			case 'INVSELL':
				return $this->loadInvestmentTransaction($lastTrans);
			case 'INVPOSLIST':
				return $this->getBalanceAdjustmentTransaction($lastTrans);
		}
	}
	
	function loadStatementTransaction($lastTrans) {
	
		$answer = ['description' => null];
		$type = $this->parser->findTag(['TRNTYPE'], $lastTrans);
		if($type && $type->content == 'CHECK') {
			$answer['description'] = 'CHECK';
		}
		$checknum = $this->parser->findTag(['CHECKNUM'], $lastTrans);

		if($checknum && $answer['description'] == 'CHECK') {
			$answer['description'] .= ' NUM ' . $checknum->content;
		}
		$ymdt = $this->parser->findTag(['DTPOSTED'], $lastTrans);
		if($ymdt) {
			$ymdt = $ymdt->content;
			$answer['ymdt'] = substr($ymdt, 0, 4) . '-' . substr($ymdt, 4, 2) . '-' . substr($ymdt, 6, 2);
		}
		$amount = $this->parser->findTag(['TRNAMT'], $lastTrans);
		if($amount) {
			$answer['amount'] = $amount->content;
		}
		$name = $this->parser->findTag(['NAME'], $lastTrans);
		if($name) {
			$answer['description'] = $name->content;
		}
		$memo = $this->parser->findTag(['MEMO'], $lastTrans);
		if($memo) {
			if(!isset($answer['description']) || (strlen($memo->content) > strlen($answer['description']) && strpos($memo->content,'DBT') !== 0 && strpos($memo->content, 'POS') !== 0))
				$answer['description'] = $memo->content;
		}
		if($answer['amount'][0] == '-')
		{
			$answer['credit'] = 0;
			$answer['amount'] = substr($answer['amount'], 1);
		}
		else
		{
			$answer['credit'] = 1;
		}
		$answer['description'] = $this->stripNumbersFromDescription($answer['description']);
		$answer['raw_account_id'] = $this->currentAccountId;
		return $answer;
	}

	protected function stripNumbersFromDescription($description) {
		$description = explode(' ' , $description);
		//when you have meaningless strings of numbers as the first item.
		if(!empty($description[0]) && count($description) > 1 && is_numeric($description[0][0])) {
			array_shift($description);
		}			
		if($description[0] == 'P.O.S.')
		{
			array_shift($description);
			array_shift($description);
			array_shift($description);
		}
		$description = trim(implode(' ' , $description));
		return $description;
	}
	
	function loadInvestmentTransaction($lastTrans) {
		$cusip = $this->parser->findTag(['UNIQUEID'], null, $lastTrans)->content;
		$securities = $this->parser->findTag(['SECLIST']);
		$secInfo = null;
		do {
			$secInfo = $this->parser->findTag(['SECINFO'], $secInfo, $securities);
			if(!$secInfo)
				break;
			if($this->parser->findTag(['UNIQUEID'],null, $secInfo)->content == $cusip) {
				$secName = $this->parser->findTag(['SECNAME'], null, $secInfo)->content;
				break;
			}
		} while(true);
		$type = $lastTrans->parent->name;
		if($secName) {
			$name = "$type $secName";
		} else {
			switch ($type) {
				case 'BUYMF':
				case 'SELLMF':
					$lookupType = 'fund';
					break;
				case 'BUYSTOCK':
				case 'SELLSTOCK':
					$lookupType = 'stock';
					break;
				default:
					$lookupType = null;
					break;
			}
			$name = "$type CUSIP: ";
			if($lookupType != null) {
				$lookup = "http://activequote.fidelity.com/mmnet/SymLookup.phtml?reqforlookup=REQUESTFORLOOKUP&for=$lookupType&by=cusip&criteria=$cusip&submit=Search";
				$name .= "<a target=\"_blank\" href=\"$lookup\">$cusip</a>";
			} else {
				$name .= $cusip;
			}
		}
		$answer['description'] = $name;
		$ymdt = $this->parser->findTag(['DTTRADE'], null, $lastTrans);
		if($ymdt) {
			$ymdt = $ymdt->content;
			$answer['ymdt'] = substr($ymdt, 0, 4) . '-' . substr($ymdt, 4, 2) . '-' . substr($ymdt, 6, 2);
		}
		$amount = $this->parser->findTag(['TOTAL'], null, $lastTrans);
		if($amount) {
			$answer['amount'] = $amount->content;
		}
		//we treat investments backwards from how they treat them... maybe we're wrong, but it'll do.
		if($answer['amount'][0] == '-')
		{
			$answer['credit'] = 1;
			$answer['amount'] = substr($answer['amount'], 1);
		}
		else
		{
			$answer['credit'] = 0;
		}
		$answer['description'] = explode(' ' , $answer['description']);
		if(is_numeric($answer['description'][0]))//when you have meaningless strings of numbers as the first item.
			array_shift($answer['description']);
		if($answer['description'][0] == 'P.O.S.')
		{
			array_shift($answer['description']);
			array_shift($answer['description']);
			array_shift($answer['description']);
		}
		$answer['description'] = trim(implode(' ' , $answer['description']));
		$answer['raw_account_id'] = $this->currentAccountId;
		
		return $answer;
	}

	function getBalanceAdjustmentTransaction($lastTrans) {
		$accountNumber = $this->parser->findTag(['ACCTID'], null, $this->lastAccount)->content;
		$account = new Account(Account::getIdByNumber(getLoggedInUser()->id, $accountNumber));
		$currBalance = $account->getBalance();
		$val = null;
		$newBalance = 0;
		do {
			$val = $this->parser->findTag(['MKTVAL'], $val, $lastTrans);
			if(!$val) {
				break;
			}
			$newBalance += $val->content;
		} while (true);
		$answer['amount'] = $newBalance - $currBalance;
		$answer['description'] = 'Changes in Market Price';
		$ymdt = $this->parser->findTag(['DTPRICEASOF'], null, $lastTrans);
		if($ymdt) {
			$ymdt = $ymdt->content;
			$answer['ymdt'] = substr($ymdt, 0, 4) . '-' . substr($ymdt, 4, 2) . '-' . substr($ymdt, 6, 2);
		}
		if($answer['amount'][0] == '-')
		{
			$answer['credit'] = 0;
			$answer['amount'] = substr($answer['amount'], 1);
		}
		else
		{
			$answer['credit'] = 1;
		}
		$answer['description'] = explode(' ' , $answer['description']);
		if(is_numeric($answer['description'][0]))//when you have meaningless strings of numbers as the first item.
			array_shift($answer['description']);
		if($answer['description'][0] == 'P.O.S.')
		{
			array_shift($answer['description']);
			array_shift($answer['description']);
			array_shift($answer['description']);
		}
		$answer['description'] = trim(implode(' ' , $answer['description']));
		$answer['raw_account_id'] = $this->currentAccountId;
		$this->lastAccount = null;
		return $answer;
	}
}
