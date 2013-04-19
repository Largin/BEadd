<?php
class Database{
	private $db = array();
	private $mysqli;

	public function __construct($init = array()) {
		if(empty($init)) {
			$this->db = array(
			'server'	=> '127.0.0.1',
			'user'		=> 'root',
			'passw'		=> 'rootmysql',
			'base'		=> 'BEAdd');
		} else
			$this->db = $init;
	}

	public function open(){
		// połącz z serwerem
		$this->mysqli = new mysqli($this->db['server'], $this->db['user'], $this->db['passw']);

		if ($this->mysqli->connect_errno) {
			throw new Exception('Nie można się połaczyć: ' . $this->mysqli->connect_errno);
		} else {
			//$GLOBALS['err'] .= "<div>".$this->mysqli->host_info."<div>";
		}
		// otwórz bazę
		$this->openDB();
	}

	private function openDB(){
		$i = $this->mysqli->select_db($this->db['base']);
		if (!$i) {
			// nie ma bazy - stwórz
			$sql = "CREATE DATABASE ".$this->db['base'];
			$j = $this->mysqli->query($sql);
			if(!$j) {
				throw new Exception('Nie można utworzyć bazy: ' . $this->mysqli->error);
			}
			$j = $this->mysqli->select_db($this->db['base']);
			if(!$j) {
				throw new Exception('Nie można otworzyć bazy: ' . $this->mysqli->error);
			}
			$this->createDB();
		}
	}

	public function createDB(){
		$sql = "CREATE TABLE IF NOT EXISTS `Element` (
							`ID` int(11) NOT NULL AUTO_INCREMENT,
							`Name` varchar(255) DEFAULT NULL,
							`Title` varchar(255) DEFAULT NULL,
							`ParentID` int(11) DEFAULT NULL,
							`Type` smallint(6) NOT NULL DEFAULT '0',
							`Mind` tinyint(1) NOT NULL DEFAULT '0',
							`Open` tinyint(1) NOT NULL DEFAULT '0',
							`Count` int(11) NOT NULL DEFAULT '0',
							`Breadcrumbs` varchar(255) NOT NULL,
							`Link` varchar(255) NOT NULL,
								PRIMARY KEY (`ID`),
								UNIQUE INDEX `Breadcrumbs` (`Breadcrumbs`),
								INDEX `ParentID` (`ParentID`),
								INDEX `Link` (`Link`)
						) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;"; // create table and trigger
		$i = $this->mysqli->query($sql);
		if($i === FALSE){
			throw new Exception('Nie można stworzyć tabeli 1: ' . $this->mysqli->error.'<br/>Zapytanie: '.$sql);
		}
	}

	public function insert(&$epi) {
		/* trigger start */
		$sql = "SELECT ID, Breadcrumbs FROM  `Element` WHERE Link = '{$epi->Link}' ORDER BY 1";
		try{
			$i = $this->mysqli->query($sql);
			if($i === FALSE){
				throw new Exception('Nie można wpisać epizodu: ' . $this->mysqli->error.'<br/>Zapytanie: '.$sql);
			}
		} catch(Exception $exc) {
			$GLOBALS['err'] .= $exc->getMessage();
			$GLOBALS['err'] .= vd::dump($exc->getTrace());
			//$GLOBALS['err'] .= vd::dump(debug_backtrace());
			return false;
		}
		/*
select e.ID, e.`Type`, e.Breadcrumbs, f.ID, f.Breadcrumbs, Locate(f.Breadcrumbs, e.Breadcrumbs) from Element e
left join Element f ON (e.Link = f.Link and e.ID <> f.ID and f.`Type` <> 9)
where e.`Type` in (9,8)
order by e.`Type`, e.ID
		 */
		$count = $i->num_rows;
		if($count > 0) {
			// cofka
			while(($row = $i->fetch_assoc())) {
				if(strpos($row['Breadcrumbs'],$epi->Breadcrumbs)!== false)
					$epi->Type = 9;
			}
			// Ghost
			if($epi->Type != 0)
				$epi->Type = 8;
		}
		/* trigger end */

		$serialized = $epi->Serial();
		$fields = "`".implode(array_keys($serialized), '`,`')."`";
		$values = "'".implode(array_values($serialized), "','")."'";

		$sql = "INSERT INTO `Element`($fields) VALUES ($values)";
		try{
			$i = $this->mysqli->query($sql);
			if($i === FALSE){
				throw new Exception('Nie można wpisać epizodu: ' . $this->mysqli->error.'<br/>Zapytanie: '.$sql);
			}
		} catch(Exception $exc) {
			$GLOBALS['err'] .= $exc->getMessage();
			$GLOBALS['err'] .= vd::dump($exc->getTrace());
			//$GLOBALS['err'] .= vd::dump(debug_backtrace());
			return false;
		}
		return true;
	}

	public function update(&$epi, $id = -1) {
		$serialized = $epi->Serial();
		$fields = array_keys($serialized);
		$values = array_values($serialized);

		if($id == -1)
			$id = $epi->ID;

    $i=0;
    $sql="UPDATE `Element` SET ";
    while($fields[$i]) {
			if($i>0) {
				$sql .= ", ";
			}
			$sql .= "`".$fields[$i]."` = '".$values[$i]."'";
			$i++;
    }
    $sql.=" WHERE ID = ".$id.";";
		try{
			$i = $this->mysqli->query($sql);
			if($i === FALSE){
				throw new Exception('Nie można zapisać epizodu: ' . $this->mysqli->error.'<br/>Zapytanie: '.$sql);
			}
		} catch(Exception $exc) {
			$GLOBALS['err'] .= $exc->getMessage();
			$GLOBALS['err'] .= vd::dump($exc->getTrace());
			return false;
		}
    return true;
	}

	public function renew(){
		$sql = "UPDATE
							Element e,
							(SELECT
								b.ID, b.Breadcrumbs, IF(group_concat(c.Breadcrumbs) IS NOT NULL, count(*), 0) AS cnt
								FROM Element b
								LEFT JOIN Element c
									ON (c.Breadcrumbs LIKE CONCAT(b.Breadcrumbs,'-%') AND c.`Type`=0)
								WHERE b.`Type`=5
								GROUP BY b.ID) f
						SET e.Count = f.cnt
						WHERE e.ID = f.ID";
		try{
			$i = $this->mysqli->query($sql);
			if($i === FALSE){
				throw new Exception('Nie można odnowić epizodów: ' . $this->mysqli->error.'<br/>Zapytanie: '.$sql);
			}
		} catch(Exception $exc) {
			$GLOBALS['err'] .= $exc->getMessage();
			$GLOBALS['err'] .= vd::dump($exc->getTrace());
			return false;
		}
		$sql = "UPDATE
							Element s,
							(
								SELECT E.ID, E.Breadcrumbs, CAST((MAX(LENGTH(F.Breadcrumbs)) - LENGTH(E.Breadcrumbs))/2 as UNSIGNED) as 'Depth'
									FROM Element E
									LEFT JOIN Element F
										ON (F.Breadcrumbs LIKE CONCAT(E.Breadcrumbs,'-%'))
									WHERE
										E.`Type` = 5
									GROUP BY E.ID
							) n
							SET
								s.Depth = n.Depth
							WHERE
								s.ID = n.ID";
		try{
			$i = $this->mysqli->query($sql);
			if($i === FALSE){
				throw new Exception('Nie można odnowić epizodów: ' . $this->mysqli->error.'<br/>Zapytanie: '.$sql);
			}
		} catch(Exception $exc) {
			$GLOBALS['err'] .= $exc->getMessage();
			$GLOBALS['err'] .= vd::dump($exc->getTrace());
			return false;
		}
	}

	public function select($breadcrumbs) {
		$sql = "SELECT `ID`, `Name`, `Title`, `ParentID`, `Type`, `Mind`, `Open`, `Count`, `Link`, `Breadcrumbs`, `Depth` FROM `Element` WHERE `Breadcrumbs` LIKE '".$breadcrumbs."'";
		try{
			$i = $this->mysqli->query($sql);
			if($i === FALSE){
				throw new Exception('Nie można wczytać epizodu: ' . $this->mysqli->error.'<br/>Zapytanie: '.$sql);
			}
		} catch(Exception $exc) {
			$GLOBALS['err'] .= $exc->getMessage();
			$GLOBALS['err'] .= vd::dump($exc->getTrace());
			return false;
		}
		if ($i->num_rows == 0) {
			return false;
		} elseif ($i->num_rows == 1) {
			$row = $i->fetch_assoc();
			$epi = new Episode($row['Link'], $row['Breadcrumbs'], (int)$row['ParentID'], $row['Name'], (int)$row['Mind'], (int)$row['Count'], (int)$row['Type'], (int)$row['ID'], $row['Title'], (int)$row['Open'], (int)$row['Depth']);
			$i->free();
			return $epi;
		}	else {
			while(($row = $i->fetch_assoc())) {
				$epi[] = new Episode($row['Link'], $row['Breadcrumbs'], (int)$row['ParentID'], $row['Name'], (int)$row['Mind'], (int)$row['Count'], (int)$row['Type'], (int)$row['ID'], $row['Title'], (int)$row['Open'], (int)$row['Depth']);
			}
			$i->free();
			return $epi;
		}
	}

		public function selectID($id) {
		$sql = "SELECT `ID`, `Name`, `Title`, `ParentID`, `Type`, `Mind`, `Open`, `Count`, `Link`, `Breadcrumbs`, `Depth` FROM `Element` WHERE `ID` = '".$id."'";
		try{
			$i = $this->mysqli->query($sql);
			if($i === FALSE){
				throw new Exception('Nie można wczytać epizodu: ' . $this->mysqli->error.'<br/>Zapytanie: '.$sql);
			}
		} catch(Exception $exc) {
			$GLOBALS['err'] .= $exc->getMessage();
			$GLOBALS['err'] .= vd::dump($exc->getTrace());
			return false;
		}
		if ($i->num_rows == 0) {
			return false;
		} elseif ($i->num_rows == 1) {
			$row = $i->fetch_assoc();
			$epi = new Episode($row['Link'], $row['Breadcrumbs'], (int)$row['ParentID'], $row['Name'], (int)$row['Mind'], (int)$row['Count'], (int)$row['Type'], (int)$row['ID'], $row['Title'], (int)$row['Open'], (int)$row['Depth']);
			$i->free();
			return $epi;
		}	else {
			while(($row = $i->fetch_assoc())) {
				$epi[] = new Episode($row['Link'], $row['Breadcrumbs'], (int)$row['ParentID'], $row['Name'], (int)$row['Mind'], (int)$row['Count'], (int)$row['Type'], (int)$row['ID'], $row['Title'], (int)$row['Open'], (int)$row['Depth']);
			}
			$i->free();
			return $epi;
		}
	}

		public function memory() {
		$sql = "SELECT `ID`, `Name`, `Title`, `ParentID`, `Type`, `Mind`, `Open`, `Count`, `Link`, `Breadcrumbs`, `Depth` FROM `Element` WHERE `Mind` = '1' ORDER BY `Breadcrumbs`";
		try{
			$i = $this->mysqli->query($sql);
			if($i === FALSE){
				throw new Exception('Nie można wczytać epizodu: ' . $this->mysqli->error.'<br/>Zapytanie: '.$sql);
			}
		} catch(Exception $exc) {
			$GLOBALS['err'] .= $exc->getMessage();
			$GLOBALS['err'] .= vd::dump($exc->getTrace());
			return false;
		}
		if ($i->num_rows == 0) {
			return false;
		} else {
			while(($row = $i->fetch_assoc())) {
				$epi[] = new Episode($row['Link'], $row['Breadcrumbs'], (int)$row['ParentID'], $row['Name'], (int)$row['Mind'], (int)$row['Count'], (int)$row['Type'], (int)$row['ID'], $row['Title'], (int)$row['Open'], (int)$row['Depth']);
			}
			$i->free();
			return $epi;
		}
	}

		public function ghosts() {
		$sql = "SELECT `ID`, `Name`, `Title`, `ParentID`, `Type`, `Mind`, `Open`, `Count`, `Link`, `Breadcrumbs`, `Depth` FROM `Element` WHERE `Type` = '8'";
		try{
			$i = $this->mysqli->query($sql);
			if($i === FALSE){
				throw new Exception('Nie można wczytać epizodu: ' . $this->mysqli->error.'<br/>Zapytanie: '.$sql);
			}
		} catch(Exception $exc) {
			$GLOBALS['err'] .= $exc->getMessage();
			$GLOBALS['err'] .= vd::dump($exc->getTrace());
			return false;
		}
		if ($i->num_rows == 0) {
			return false;
		} else {
			while(($row = $i->fetch_assoc())) {
				$epi[] = new Episode($row['Link'], $row['Breadcrumbs'], (int)$row['ParentID'], $row['Name'], (int)$row['Mind'], (int)$row['Count'], (int)$row['Type'], (int)$row['ID'], $row['Title'], (int)$row['Open'], (int)$row['Depth']);
			}
			$i->free();
			return $epi;
		}
	}

	public function reset() {
		$sql = "TRUNCATE `Element`";
		$i = $this->mysqli->query($sql);
		if($i === FALSE){
			throw new Exception('Nie można usunąć epizodów: ' . $this->mysqli->error.'<br/>Zapytanie: '.$sql);
		}
	}

	public function get($target) {
		$sql = "UPDATE `Element` SET Open = 0";
		$i = $this->mysqli->query($sql);
		if($i === FALSE){
			throw new Exception('Nie można zamknąć epizodów: ' . $this->mysqli->error.'<br/>Zapytanie: '.$sql);
		}
		$str = '(';
		while(($l = strlen($target)) > 1) {
			$str .= "'".$target."',";
			$target = substr($target, 0, $l-2);
		}
		$str .= "'0')";
		$sql = "UPDATE `Element` SET Open = 1 WHERE Breadcrumbs IN ".$str;
		$i = $this->mysqli->query($sql);
		if($i === FALSE){
			throw new Exception('Nie można otworzyć epizodów: ' . $this->mysqli->error.'<br/>Zapytanie: '.$sql);
		}
	}

	//exec('mysqldump --user=... --password=... --host=... DB_NAME > /path/to/output/file.sql');

	public function podmiana(&$epi) {
		try{
			$sql = "SELECT `ID`, `Name`, `Title`, `ParentID`, `Type`, `Mind`, `Open`, `Count`, `Link`, `Breadcrumbs`, `Depth` FROM `Element` WHERE `Link` = '".$epi->Link."' AND `Type` < 8 LIMIT 1";
			$i = $this->mysqli->query($sql);
			if($i === FALSE){
				throw new Exception('Nie można wczytać epizodu: ' . $this->mysqli->error.'<br/>Zapytanie: '.$sql);
			}

			if ($i->num_rows == 0) {
				return false;
			}
			$row = $i->fetch_assoc();
			$par = new Episode($row['Link'], $row['Breadcrumbs'], (int)$row['ParentID'], $row['Name'], (int)$row['Mind'], (int)$row['Count'], (int)$row['Type'], (int)$row['ID'], $row['Title'], (int)$row['Open'], (int)$row['Depth']);
			$i->free();

			$sql = "UPDATE `Element` SET ParentID = '".$epi->ID."' WHERE ParentID = '".$par->ID."'";
			$i = $this->mysqli->query($sql);
			if($i === FALSE){
				throw new Exception('Nie można podmienić epizodu A: ' . $this->mysqli->error.'<br/>Zapytanie: '.$sql);
			}

			$sql = "UPDATE `Element` SET Breadcrumbs = REPLACE(Breadcrumbs, '".$par->Breadcrumbs."', '".$epi->Breadcrumbs."') WHERE Breadcrumbs LIKE '".$par->Breadcrumbs."-%'";
			$i = $this->mysqli->query($sql);
			if($i === FALSE){
				throw new Exception('Nie można podmienić epizodu B: ' . $this->mysqli->error.'<br/>Zapytanie: '.$sql);
			}

			$epi->Type = $par->Type;
			$epi->Title = $par->Title;
			$epi->Count = $par->Count;
			$epi->Open = $par->Open;
			$epi->Mind = $par->Mind;
			$par->Mind = 0;
			$par->Type = 8;
			if($this->update($epi) == FALSE) throw new Exception('Nie można podmienić epizodu C. ');
			if($this->update($par) == FALSE) throw new Exception('Nie można podmienić epizodu D. ');
		} catch(Exception $exc) {
			$GLOBALS['err'] .= $exc->getMessage();
			$GLOBALS['err'] .= vd::dump($exc->getTrace());
			return false;
		}
		return true;
	}

	public function close() {
		$this->mysqli->close();
	}
}
?>
