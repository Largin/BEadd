<?php
class Episode {
	public $ID;
	public $ParentID;
	public $Type;		// 0 - child / 1 - pustychild / 5 - episode / 9 - ghost
	public $Link;
	public $Title;
	public $Name;
	public $Mind;
	public $Open;
	public $Count;
	public $Breadcrumbs;

	public function __construct($link, $breadcrumbs, $parentID, $name, $mind = 0, $count = 1, $type = 0, $id = NULL, $title = NULL, $open = 0 ) {
		// must be
		$this->Link = $link;
		$this->Breadcrumbs = $breadcrumbs;
		if($parentID === 0)
			$this->ParentID = NULL;
		else
			$this->ParentID = $parentID;
		$this->Name = $name;
		// may be
		$this->Mind = $mind;
		$this->Open = $open;
		$this->Count = $count;
		$this->Type = $type;
		$this->ID = $id;
		$this->Title = $title;
	}

	public function Serial(){
		$serialized = array();
		if($this->ID != NULL)
			$serialized['ID'] = $this->ID;
		if($this->ParentID != NULL)
			$serialized['ParentID'] = $this->ParentID;
		if($this->Title != NULL)
			$serialized['Title'] = addslashes($this->Title);
		if($this->Name != NULL)
			$serialized['Name'] = addslashes($this->Name);
		$serialized['Mind'] = $this->Mind;
		$serialized['Open'] = $this->Open;
		$serialized['Count'] = $this->Count;
		$serialized['Type'] = $this->Type;
		$serialized['Link'] = $this->Link;
		$serialized['Breadcrumbs'] = $this->Breadcrumbs;

		return $serialized;
	}

	public function rozwin($apen = 1){
		if($this->Type == 0) {
			$base = "http://www.bearchive.com/~addventure/game1/";

			$dom = new DOMDocument();
			$dom->strictErrorChecking = false;

			if(!$dom->loadHTMLFile($this->Link)) {
				// create curl resource
				$ch = curl_init();
				// set url
				curl_setopt($ch, CURLOPT_URL, $this->Link);
				//return the transfer as a string
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				// $output contains the output string
				$output = curl_exec($ch);
				// close curl resource to free up system resources
				curl_close($ch);
				$dom->loadHTML($output);
			}

			$xpath = new DOMXPath($dom);
			$as = $xpath->query("//a");

			if ($as->item(0)->nodeValue == "Cancel") {
				$this->Title = "PUSTY";
				$this->Type = 1;
				$this->Count = 0;
				$GLOBALS['db']->update($this, $this->ID);
			} else {
				$this->Title = $xpath->query("//title")->item(0)->nodeValue;
				$n = 0;
				$bread = $this->Breadcrumbs.'-';
				foreach ($as as $a) {
					if (($a->nodeValue != "Go back") && ($a->nodeValue != "go back") && ($a->nodeValue != "Go Back") && ($a->nodeValue != "Add comment")
									&& ((strpos($a->getAttribute('href'), "../")!== FALSE) || (strpos($a->getAttribute('href'), $base)!== FALSE) )) {
						// scalamy lokalny i globalny adres
						$i = strrpos($this->Link, "/");
						;
						$adrbase = substr($this->Link, 0, $i);
						$hr = $a->getAttribute('href');
						while (strpos($hr, "../") !== false) {
							$j = strpos($hr, "../");
							$hr = substr($hr, 3);
							$i = strrpos($adrbase, "/");
							$adrbase = substr($adrbase, 0, $i);
						}
						$adr = $adrbase."/".$hr;
						$tmp = new Episode($adr, $bread.$n, $this->ID, $a->nodeValue);
						$GLOBALS['db']->insert($tmp);
						$n++;
					}
					$this->Open = $apen;
					$this->Count= $n;
				}
				$this->Type = 5;
				$GLOBALS['db']->update($this, $this->ID);
			}
		} elseif ($this->Type == 5) {
			$this->Count = 0;
			$dieti = $GLOBALS['db']->select($this->Breadcrumbs.'-_');
			if(is_array($dieti)) {
				foreach ($dieti as $epi) {
					if($epi->Count > 0) {
						$epi->rozwin(1);
						$this->Count += $epi->Count;
					}
				}
			}	else {
				if($dieti->Count > 0) {
					$dieti->rozwin(1);
					$this->Count += $dieti->Count;
				}
			}
			if($this->Count > 0) {
				$this->Open = 1;
			} else {
				$this->Open = 0;
			}
			$GLOBALS['db']->update($this, $this->ID);
		}
	}

	public function open($open = 1){
		$this->Open = $open;
		$GLOBALS['db']->update($this, $this->ID);
	}

	public function Remember(){
		$this->Mind = (($this->Mind + 1) % 2);
		$GLOBALS['db']->update($this, $this->ID);
	}

	public function paste($li = 0, $ghost = 0) {
		$odp = '';
		switch($this->Type) {
			case 0	:	// show child
				if($li == 0) $odp .= "<li id='li-".$this->Breadcrumbs."'>";
				$odp .= "<div class='text child'>";
				$odp .= "<div class='icon-ui icon-plus' onclick='get_ajax(\"rozwin\",\"".$this->Breadcrumbs."\");return false;'></div>";
				if($this->Mind == 0)	$odp .= "<div class='icon-ui icon-mind' onclick='get_ajax(\"mind\",\"".$this->Breadcrumbs."\");return false;'></div>";
				else $odp .= "<div class='icon-ui2 icon-mind' onclick='get_ajax(\"mind\",\"".$this->Breadcrumbs."\");return false;'></div>";
				$odp .= "<div class='icon-ui icon-info' onclick='get_ajax(\"info\",\"".$this->Breadcrumbs."\");return false;'></div>";
				$odp .= "<a target='_blank' href='".$this->Link."'><div class='icon-ui icon-out'></div></a>";
				if($this->Name == NULL)	$odp .= " - "."<a onclick='log(\"".$this->Link."\")' href='".$this->Link."' target='page'>".$this->Link."</a>";
				else $odp .= " - "."<a onclick='log(\"".$this->Link."\")' href='".$this->Link."' target='page' title='".htmlentities ($this->Name, ENT_COMPAT | ENT_HTML401 | ENT_QUOTES)."'>".$this->Name."</a>";
				$odp .= "</div>";
				if($li == 0) $odp .= "</li>";
				break;
			case 1	:	// show empty child
				if($li == 0) $odp .= "<li id='li-".$this->Breadcrumbs."'>";
				$odp .= "<div class='text childem'>";
				$odp .= "<div class='icon-ui icon-plus' onclick='get_ajax(\"rozwin\",\"".$this->Breadcrumbs."\");return false;'></div>";
				if($this->Mind == 0)	$odp .= "<div class='icon-ui icon-mind' onclick='get_ajax(\"mind\",\"".$this->Breadcrumbs."\");return false;'></div>";
				else $odp .= "<div class='icon-ui2 icon-mind' onclick='get_ajax(\"mind\",\"".$this->Breadcrumbs."\");return false;'></div>";
				$odp .= "<div class='icon-ui icon-info' onclick='get_ajax(\"info\",\"".$this->Breadcrumbs."\");return false;'></div>";
				$odp .= "<a target='_blank' href='".$this->Link."'><div class='icon-ui icon-out'></div></a>";
				if($this->Name == NULL)	$odp .= " - ".$this->Link;
				else $odp .= " - ".$this->Name;
				$odp .= "</div>";
				if($li == 0) $odp .= "</li>";
				break;
			case 5	:	// show episode
				if($li == 0) {$odp .= "<li id='li-".$this->Breadcrumbs."'"; if($this->Open == 1) $odp .= " class='epi'"; $odp .= ">";}
				$odp .= "<div class='text episode'>";
				if($this->Open == 0) $odp .= "<div class='icon-ui icon-plus' onclick='get_ajax(\"open\",\"".$this->Breadcrumbs."\");return false;'></div>";
				else $odp .= "<div class='icon-ui icon-minus' onclick='get_ajax(\"close\",\"".$this->Breadcrumbs."\");return false;'></div>";
				$odp .= "<div class='icon-ui icon-burst' onclick='get_ajax(\"burst\",\"".$this->Breadcrumbs."\");return false;'></div>";
				if($this->Count > 0) $odp .= "<span id='ctli-".$this->Breadcrumbs."' class='count'>[{$this->Count}]</span>";
				else $odp .= "<span id='ctli-".$this->Breadcrumbs."' class='done'>[Done]</span>";
				$odp .= "<div class='icon-ui icon-cut' onclick='get_ajax(\"cut\",\"".$this->Breadcrumbs."\");return false;'></div>";
				if($this->Mind == 0)	$odp .= "<div class='icon-ui icon-mind' onclick='get_ajax(\"mind\",\"".$this->Breadcrumbs."\");return false;'></div>";
				else $odp .= "<div class='icon-ui2 icon-mind' onclick='get_ajax(\"mind\",\"".$this->Breadcrumbs."\");return false;'></div>";
				$odp .= "<div class='icon-ui icon-info' onclick='get_ajax(\"info\",\"".$this->Breadcrumbs."\");return false;'></div>";
				$odp .= "<a target='_blank' href='".$this->Link."'><div class='icon-ui icon-out'></div></a>";
				if($this->Name == NULL)	$odp .= " - "."<a onclick='log(\"".$this->Link."\")' href='".$this->Link."' target='page'>".$this->Link."</a>";
				else $odp .= " - "."<a onclick='log(\"".$this->Link."\")' href='".$this->Link."' target='page' title='".htmlentities ($this->Name, ENT_COMPAT | ENT_HTML401 | ENT_QUOTES)."'>".$this->Name."</a>";
				if($this->Title != NULL) $odp .= "<br/>".$this->Title;
				$odp .= "</div>";
				if($this->Open == 1) {
					$odp .= "<ul>";
					$dieti = $GLOBALS['db']->select($this->Breadcrumbs.'-_');
					if(is_array($dieti)) {
						foreach ($dieti as $epi) {
							$odp .= $epi->paste();
						}
					}	else {
						$odp .= $dieti->paste();
					}
					$odp .= "</ul>";
				}
				if($li == 0) $odp .= "</li>";
				break;
			case 8 :	//show ghost
				if($li == 0) $odp .= "<li id='li-".$this->Breadcrumbs."'>";
				$odp .= "<div class='text sghost'>";
				$odp .= "<div class='icon-ui icon-repl' onclick='get_ajax(\"rep\",\"".$this->Breadcrumbs."\");return false;'></div>";
				if($this->Mind == 0)	$odp .= "<div class='icon-ui icon-mind' onclick='get_ajax(\"mind\",\"".$this->Breadcrumbs."\");return false;'></div>";
				else $odp .= "<div class='icon-ui2 icon-mind' onclick='get_ajax(\"mind\",\"".$this->Breadcrumbs."\");return false;'></div>";
				$odp .= "<div class='icon-ui icon-info' onclick='get_ajax(\"info\",\"".$this->Breadcrumbs."\");return false;'></div>";
				$odp .= "<a target='_blank' href='".$this->Link."'><div class='icon-ui icon-out'></div></a>";
				if($this->Name == NULL) $odp .= " - "."<a onclick='log(\"".$this->Link."\")' href='".$this->Link."' target='page'>".$this->Link."</a>";
				else $odp .= " - "."<a onclick='log(\"".$this->Link."\")' href='".$this->Link."' target='page' title='".htmlentities ($this->Name, ENT_COMPAT | ENT_HTML401 | ENT_QUOTES)."'>".$this->Name."</a>";
				$odp .= "</div>";
				if($li == 0) $odp .= "</li>";
				break;
			case 9 :	//show backtrace
				if($li == 0) $odp .= "<li id='li-".$this->Breadcrumbs."'>";
				$odp .= "<div class='text ghost'>";
				if($this->Mind == 0)	$odp .= "<div class='icon-ui icon-mind' onclick='get_ajax(\"mind\",\"".$this->Breadcrumbs."\");return false;'></div>";
				else $odp .= "<div class='icon-ui2 icon-mind' onclick='get_ajax(\"mind\",\"".$this->Breadcrumbs."\");return false;'></div>";
				$odp .= "<div class='icon-ui icon-info' onclick='get_ajax(\"info\",\"".$this->Breadcrumbs."\");return false;'></div>";
				$odp .= "<a target='_blank' href='".$this->Link."'><div class='icon-ui icon-out'></div></a>";
				if($this->Name == NULL) $odp .= " - "."<a onclick='log(\"".$this->Link."\")' href='".$this->Link."' target='page'>".$this->Link."</a>";
				else $odp .= " - "."<a onclick='log(\"".$this->Link."\")' href='".$this->Link."' target='page' title='".htmlentities ($this->Name, ENT_COMPAT | ENT_HTML401 | ENT_QUOTES)."'>".$this->Name."</a>";
				$odp .= "</div>";
				if($li == 0) $odp .= "</li>";
				break;
			default :
				if($li == 0) $odp .= "<li id='li-".$this->Breadcrumbs."'>";
				$odp .= "<div class='text ghost'>";
				if($this->Mind == 0)	$odp .= "<div class='icon-ui icon-mind' onclick='get_ajax(\"mind\",\"".$this->Breadcrumbs."\");return false;'></div>";
				else $odp .= "<div class='icon-ui2 icon-mind' onclick='get_ajax(\"mind\",\"".$this->Breadcrumbs."\");return false;'></div>";
				$odp .= "<div class='icon-ui icon-info' onclick='get_ajax(\"info\",\"".$this->Breadcrumbs."\");return false;'></div>";
				$odp .= "<a target='_blank' href='".$this->Link."'><div class='icon-ui icon-out'></div></a>";
				if($this->Name == NULL) $odp .= " - "."<a onclick='log(\"".$this->Link."\")' href='".$this->Link."' target='page'>".$this->Link."</a>";
				else $odp .= " - "."<a onclick='log(\"".$this->Link."\")' href='".$this->Link."' target='page' title='".htmlentities ($this->Name, ENT_COMPAT | ENT_HTML401 | ENT_QUOTES)."'>".$this->Name."</a>";
				$odp .= "</div>";
				if($li == 0) $odp .= "</li>";
				break;
		}
		return $odp;
	}

	public function Memory() {
		$odp = '<li';
		switch($this->Type) {
			case 0: $odp .= " class='child'"; break;
			case 1: $odp .= " class='childem'"; break;
			case 5: $odp .= " class='episode'"; break;
			case 8: $odp .= " class='sghost'"; break;
			case 9: $odp .= " class='ghost'"; break;
		}
		$odp .= '>';
		if($this->Type == 8)
				$odp .= "<div class='icon-ui icon-repl' onclick='get_ajax(\"rep\",\"".$this->Breadcrumbs."\");return false;'></div>";
		$odp .= "<div class='icon-ui icon-get'onclick='get_ajax(\"get\",\"".$this->Breadcrumbs."\");return false;'></div>";
		$odp .= "<div class='icon-ui icon-cut' onclick='get_ajax(\"cut\",\"".$this->Breadcrumbs."\");return false;'></div>";
		$odp .= "<div class='icon-ui2 icon-mind' onclick='get_ajax(\"mind\",\"".$this->Breadcrumbs."\");return false;'></div>";
		$odp .= "<div class='icon-ui icon-info' onclick='get_ajax(\"info\",\"".$this->Breadcrumbs."\");return false;'></div>";
		$odp .= "<a target='_blank' href='".$this->Link."'><div class='icon-ui icon-out'></div></a>";
		if($this->Name == NULL && $this->Title == NULL) $odp .= "<a onclick='log(\"".$this->Link."\")' href='".$this->Link."' target='page'>".$this->Link."</a>";
		else
			if($this->Name != NULL) {
				$odp .= "<a onclick='log(\"".$this->Link."\")' href='".$this->Link."' target='page'>".$this->Name."</a>";
				if($this->Title != NULL) $odp .= "<br/>".$this->Title;
			}	else {
				$odp .= "<a onclick='log(\"".$this->Link."\")' href='".$this->Link."' target='page'>".$this->Title."</a>";
			}
		$odp .= '</li>';
		return $odp;
	}

	public function Replace() {
		$GLOBALS['db']->podmiana($this);
	}
}
?>
