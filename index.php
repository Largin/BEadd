<?php

error_reporting(E_ERROR | E_PARSE);

require 'C_Episode.php';
require 'C_Database.php';
require 'C_HTML.php';

// Errors
$GLOBALS['err'] = '';
// pierwszy epizod
$root = "http://www.bearchive.com/~addventure/game1/docs/000/2.html";
// init
//todo zamknć w $SESSION
$init = array(
			'server'	=> 'sql205.byethost14.com',
			'user'		=> 'b14_12562639',
			'passw'		=> 'makaki69',
			'base'		=> 'b14_12562639_BEAdd');

$db = new Database();
// database open
try {
	$db->open();
} catch (Exception $exc) {
	$GLOBALS['err'] .= $exc->getMessage();
	$GLOBALS['err'] .= '<pre>'.var_export($exc->getTrace(), true).'</pre>';
}

// sesja
session_start();
	// ustawienie cut
	if(isset($_POST['cut'])) {
		if($_POST['cut'] != 'A' && $_POST['cut'] != 'B'){
			$_SESSION['cut'] = $_POST['cut'];
		}
		if($_POST['cut'] == 'B') {
			$cel = $db->select($_SESSION['cut']);
			if($cel->ParentID != null) {
				$cel = $db->selectID($cel->ParentID);
				$_SESSION['cut'] = $cel->Breadcrumbs;
			}
		}
	}

	// reset cut
	if(isset($_POST['reset'])) {
		unset($_SESSION['cut']);
	}
	// sprawdzenie cut
	if(isset($_SESSION['cut'])) {
		$cel = $db->select($_SESSION['cut']);
		if($cel === FALSE) {
			$_SESSION['cut'] = '0';
		}
		$CUT = $_SESSION['cut'];
	} else {
		$_SESSION['cut'] = '0';
		$CUT = $_SESSION['cut'];
	}
	if(isset($_POST['rozwin']) || isset($_POST['cut'])){
		$ren = 0;
		if(!isset($_SESSION['renew'])) {
			$_SESSION = time();
			$ren = 1;
		} else {
			if($_SESSION['renew'] + 30 < time()) {
				$_SESSION['renew'] = time();
				$ren = 1;
			}
		}
	}
session_write_close();
// koniec sesji

if(isset($_POST['AJAX'])) {

	// rozwinięcie
	if(isset($_POST['rozwin'])) {
		$cel = $db->select($_POST['rozwin']);
		if($cel !== FALSE) {
			try {
				$cel->rozwin(1);
				if($ren == 1)
					$db->renew();
				if($cel->Open == 1 && $cel->Type = 5)
					$ep = "yes";
				else
					$ep = "no";
				$json = array(
					"target"	=> "li-".$cel->Breadcrumbs,
					"data"		=> $cel->paste(1),
//					"link"		=> $cel->Link,
					"err"			=> $GLOBALS['err'],
					"time"		=> $_POST['time'],
					"delay"		=> "yes",
					"episode" => $ep
					);
				echo json_encode($json, JSON_FORCE_OBJECT);
			} catch (Exception $exc) {
				$GLOBALS['err'] .= $exc->getMessage();
				$GLOBALS['err'] .= '<pre>'.var_export($exc->getTrace(), true).'</pre>';
				$json = array(
//					"target"	=> "li-".$cel->Breadcrumbs,
//					"data"		=> $cel->paste(1),
//					"link"		=> $cel->Link,
					"err"			=> $GLOBALS['err'],
					"time"		=> $_POST['time']);
				echo json_encode($json, JSON_FORCE_OBJECT);
			}
		}
	} elseif (isset($_POST['open'])) {
		$cel = $db->select($_POST['open']);
		if($cel !== FALSE) {
			try {
				$cel->open();
				$json = array(
					"target"	=> "li-".$cel->Breadcrumbs,
					"data"		=> $cel->paste(1),
//					"link"		=> $cel->Link,
					"err"			=> $GLOBALS['err'],
					"time"		=> $_POST['time'],
					"delay"		=> "yes",
					"episode" => "yes");
				echo json_encode($json, JSON_FORCE_OBJECT);
			} catch (Exception $exc) {
				$GLOBALS['err'] .= $exc->getMessage();
				$GLOBALS['err'] .= '<pre>'.var_export($exc->getTrace(), true).'</pre>';
				$json = array(
//					"target"	=> "li-".$cel->Breadcrumbs,
//					"data"		=> $cel->paste(1),
//					"link"		=> $cel->Link,
					"err"			=> $GLOBALS['err'],
					"time"		=> $_POST['time']);
				echo json_encode($json, JSON_FORCE_OBJECT);
			}
		}
	} elseif (isset($_POST['close'])) {
		$cel = $db->select($_POST['close']);
		if($cel !== FALSE) {
			try {
				$cel->open(0);
				$json = array(
					"target"	=> "li-".$cel->Breadcrumbs,
					"data"		=> $cel->paste(1),
//					"link"		=> $cel->Link,
					"err"			=> $GLOBALS['err'],
					"time"		=> $_POST['time'],
					"delay"		=> "yes",
					"episode" => "no");
				echo json_encode($json, JSON_FORCE_OBJECT);
			} catch (Exception $exc) {
				$GLOBALS['err'] .= $exc->getMessage();
				$GLOBALS['err'] .= '<pre>'.var_export($exc->getTrace(), true).'</pre>';
				$json = array(
//					"target"	=> "li-".$cel->Breadcrumbs,
//					"data"		=> $cel->paste(1),
//					"link"		=> $cel->Link,
					"err"			=> $GLOBALS['err'],
					"time"		=> $_POST['time']);
				echo json_encode($json, JSON_FORCE_OBJECT);
			}
		}
	} elseif (isset($_POST['cut'])) {
		$ref = 0;
		if($_POST['cut'] == 'A') {
			$_POST['cut'] = $CUT;
			$ref = 1;
		}
		if($_POST['cut'] == 'B') {
			$_POST['cut'] = $CUT;
			$ref = 1;
		}
		if($ren == 1)
			$db->renew();
		$cel = $db->select($_POST['cut']);
		if($cel !== FALSE) {
			try {
				if($cel->Open == 1 && $cel->Type = 5)
					$ep = "yes";
				else
					$ep = "no";
				$json = array(
					"target"	=> "tree",
					"data"		=> $cel->paste(),
					"link"		=> $cel->Link,
					"err"			=> $GLOBALS['err'],
					"time"		=> $_POST['time'],
					"delay"		=> "no",
					"episode" => $ep
					);
				if($ref == 1)
					unset($json['link']);
				echo json_encode($json, JSON_FORCE_OBJECT);
			} catch (Exception $exc) {
				$GLOBALS['err'] .= $exc->getMessage();
				$GLOBALS['err'] .= '<pre>'.var_export($exc->getTrace(), true).'</pre>';
				$json = array(
//					"target"	=> "li-".$cel->Breadcrumbs,
//					"data"		=> $cel->paste(1),
//					"link"		=> $cel->Link,
					"err"			=> $GLOBALS['err'],
					"time"		=> $_POST['time']);
				echo json_encode($json, JSON_FORCE_OBJECT);
			}

		}
	} elseif (isset($_POST['rep'])) {
		$cel = $db->select($_POST['rep']);
		$cel->Replace();
		$cel = $db->select($CUT);
		if($cel !== FALSE) {
			try {
				if($cel->Open == 1 && $cel->Type = 5)
					$ep = "yes";
				else
					$ep = "no";
				$json = array(
					"target"	=> "tree",
					"data"		=> $cel->paste(),
					//"link"		=> $cel->Link,
					"err"			=> $GLOBALS['err'],
					"time"		=> $_POST['time'],
					"delay"		=> "no",
					"episode" => $ep);
				echo json_encode($json, JSON_FORCE_OBJECT);
			} catch (Exception $exc) {
				$GLOBALS['err'] .= $exc->getMessage();
				$GLOBALS['err'] .= '<pre>'.var_export($exc->getTrace(), true).'</pre>';
				$json = array(
//					"target"	=> "li-".$cel->Breadcrumbs,
//					"data"		=> $cel->paste(1),
//					"link"		=> $cel->Link,
					"err"			=> $GLOBALS['err'],
					"time"		=> $_POST['time']);
				echo json_encode($json, JSON_FORCE_OBJECT);
			}

		}
	} elseif (isset($_POST['mind'])) {
		$cel = $db->select($_POST['mind']);
		if($cel !== FALSE) {
			try {
				$cel->Remember();
				$json = array(
					"target"	=> "li-".$cel->Breadcrumbs,
//					"data"		=> $cel->paste(1),
//					"link"		=> $cel->Link,
					"err"			=> $GLOBALS['err'],
					"time"		=> $_POST['time']);
				echo json_encode($json, JSON_FORCE_OBJECT);
			} catch (Exception $exc) {
				$GLOBALS['err'] .= $exc->getMessage();
				$GLOBALS['err'] .= '<pre>'.var_export($exc->getTrace(), true).'</pre>';
				$json = array(
//					"target"	=> "li-".$cel->Breadcrumbs,
//					"data"		=> $cel->paste(1),
//					"link"		=> $cel->Link,
					"err"			=> $GLOBALS['err'],
					"time"		=> $_POST['time']);
				echo json_encode($json, JSON_FORCE_OBJECT);
			}
		}
	}	elseif (isset($_POST['get'])) {
		$db->get($_POST['get']);
		$cel2 = $db->select($_POST['get']);
		$cel = $db->select('0');
		if($cel !== FALSE) {
			try {
				if($cel->Open == 1 && $cel->Type = 5)
					$ep = "yes";
				else
					$ep = "no";
				$json = array(
					"target"	=> "tree",
					"data"		=> $cel->paste(),
					"link"		=> $cel2->Link,
					"err"			=> $GLOBALS['err'],
					"time"		=> $_POST['time'],
					"delay"		=> "no",
					"episode" => $ep);
				echo json_encode($json, JSON_FORCE_OBJECT);
			} catch (Exception $exc) {
				$GLOBALS['err'] .= $exc->getMessage();
				$GLOBALS['err'] .= '<pre>'.var_export($exc->getTrace(), true).'</pre>';
				$json = array(
//					"target"	=> "li-".$cel->Breadcrumbs,
//					"data"		=> $cel->paste(1),
//					"link"		=> $cel->Link,
					"err"			=> $GLOBALS['err'],
					"time"		=> $_POST['time']);
				echo json_encode($json, JSON_FORCE_OBJECT);
			}

		}
	} elseif (isset($_POST['info'])) {
		$cel = $db->select($_POST['info']);
		if($cel !== FALSE) {
			try {
				$json = array(
//					"target"	=> "li-".$cel->Breadcrumbs,
//					"data"		=> $cel->paste(1),
//					"link"		=> $cel->Link,
					"err"			=> vd::dump($cel),
					"time"		=> $_POST['time'],
//					"delay"		=> "yes"
						);
				echo json_encode($json, JSON_FORCE_OBJECT);
			} catch (Exception $exc) {
				$GLOBALS['err'] .= $exc->getMessage();
				$GLOBALS['err'] .= '<pre>'.var_export($exc->getTrace(), true).'</pre>';
				$json = array(
//					"target"	=> "li-".$cel->Breadcrumbs,
//					"data"		=> $cel->paste(1),
//					"link"		=> $cel->Link,
					"err"			=> $GLOBALS['err'],
					"time"		=> $_POST['time']);
				echo json_encode($json, JSON_FORCE_OBJECT);
			}
		}
	}
	if(isset($_POST['memory'])) {
		$cel = $db->memory();
		if($cel !== FALSE) {
			try {
				$odp = "Memory:<ul>";
				foreach($cel as $epi)
					$odp .= $epi->Memory();
				$odp .= "</ul>";
				// Ghosts by móc ustaic je w odpowiedniej kolejności
				$cel = $db->Ghosts();
				$odp .= "Ghosts:<ul>";
				foreach($cel as $epi)
					$odp .= $epi->Memory();
				$odp .= "</ul>";
				$json = array(
//					"target"	=> "li-".$cel->Breadcrumbs,
					"data"		=> $odp,
//					"link"		=> $cel->Link,
					"err"			=> $GLOBALS['err'],
					"time"		=> $_POST['time']);
				echo json_encode($json, JSON_FORCE_OBJECT);
			} catch (Exception $exc) {
				$GLOBALS['err'] .= $exc->getMessage();
				$GLOBALS['err'] .= '<pre>'.var_export($exc->getTrace(), true).'</pre>';
				$json = array(
//					"target"	=> "li-".$cel->Breadcrumbs,
//					"data"		=> $cel->paste(1),
//					"link"		=> $cel->Link,
					"err"			=> $GLOBALS['err'],
					"time"		=> $_POST['time']);
				echo json_encode($json, JSON_FORCE_OBJECT);
			}
		} else {
			$json = array(
//					"target"	=> "li-".$cel->Breadcrumbs,
				"data"		=> '',
//					"link"		=> $cel->Link,
				"err"			=> $GLOBALS['err'],
				"time"		=> $_POST['time']);
			echo json_encode($json, JSON_FORCE_OBJECT);
		}
	}
	if(isset($_POST['reset'])) {
		$db->reset();
		try {
			$tree = $db->select('0');
			if($tree === FALSE) {
				$GLOBALS['err'] .= "Brak korzenia w bazie. Stworzenie nowego.<br/>";
				$tree = new Episode($root, '0', NULL, NULL);
				$db->insert($tree);
				$tree = $db->select('0');
			$json = array(
				"target"	=> "tree",
				"data"		=> $tree->paste(),
				"link"		=> $tree->Link,
				"err"			=> $GLOBALS['err'],
				"time"		=> $_POST['time']);
			}
		} catch (Exception $exc) {
			$GLOBALS['err'] .= $exc->getMessage();
			$GLOBALS['err'] .= '<pre>'.var_export($exc->getTrace(), true).'</pre>';
			$json = array(
//				"target"	=> "tree",
//				"data"		=> $tree->paste(),
//				"link"		=> $tree->Link,
				"err"				=> $GLOBALS['err'],
				"time"			=> $_POST['time']);
		}
		echo json_encode($json, JSON_FORCE_OBJECT);
//		echo "0||tree||".$tree->paste();
	}
} else {
	// nagłówek
	echo HTML::header();
	// nagłówek
	echo "<div class='menu'><span id='menul'>";
	echo "<button id='ball'>ALL</button>";
	echo "<button id='bres'>RESET</button>";
	echo "<button id='bref'>REFRESH</button>";
	echo "<button id='bbck'>BACK</button>";
	echo "<input type='checkbox' id='bmem'/><label for='bmem'>MEMORY</label>";
	echo "</span><span id='menur' class='right'>";
	echo "<input type='checkbox' id='btas' checked/><label for='btas'>TASKS</label>";
	echo "<input type='checkbox' id='berr'/><label for='berr'>LOGS</label>";
	echo "<button id='berrclr'>CLEAR LOGS</button>";
	echo "<input type='checkbox' id='bdat'/><label for='bdat'>DATABASE</label>";
	echo "</span></div>";

	echo HTML::starttable();
	// korzeń
	try {
		$tree = $db->select('0');
		if($tree === FALSE) {
			$GLOBALS['err'] .= "Brak korzenia w bazie. Stworzenie nowego.<br/>";
			$tree = new Episode($root, '0', NULL, NULL);
			$db->insert($tree);
			$tree = $db->select('0');
		}
	} catch (Exception $exc) {
		$GLOBALS['err'] .= $exc->getMessage();
		$GLOBALS['err'] .= '<pre>'.var_export($exc->getTrace(), true).'</pre>';
	}

	// pokaz
	$cel = $db->select($CUT);


	// wyświetlenie
	echo "<ul id='tree'>".$cel->paste()."</ul>";

	echo HTML::endtable();
	echo "<div id='task'>"."</div>";
	echo "<div id='err'>".$GLOBALS['err']."</div>";
	echo "<div id='dbs'><div id='menudb'><button id='dbex'>EXPORT DB</button><button id='dbim'>IMPORT DB</button></div>"."</div>";
	echo "<div id='mem'>"."</div>";

	echo '
	<div id="reset-dialog" title="Reset reading?">
		<p><span class="ui-icon ui-icon-alert" style="float: left; margin: 0 7px 20px 0;"></span>Do you want to reset reading and delete all Episodes?</p>
	</div>';


	echo HTML::footer();
}

// database close
try {
	$db->close();
} catch (Exception $exc) {
	$GLOBALS['err'] .= $exc->getMessage();
	$GLOBALS['err'] .= '<pre>'.var_export($exc->getTrace(), true).'</pre>';
}

?>
