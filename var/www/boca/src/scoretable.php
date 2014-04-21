<?php
////////////////////////////////////////////////////////////////////////////////
//BOCA Online Contest Administrator
//    Copyright (C) 2003-2012 by BOCA System (bocasystem@gmail.com)
//
//    This program is free software: you can redistribute it and/or modify
//    it under the terms of the GNU General Public License as published by
//    the Free Software Foundation, either version 3 of the License, or
//    (at your option) any later version.
//
//    This program is distributed in the hope that it will be useful,
//    but WITHOUT ANY WARRANTY; without even the implied warranty of
//    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//    GNU General Public License for more details.
//    You should have received a copy of the GNU General Public License
//    along with this program.  If not, see <http://www.gnu.org/licenses/>.
////////////////////////////////////////////////////////////////////////////////
//Last updated 02/nov/2012 by cassio@ime.usp.br
require_once("db.php");
//sam 
$nuevoscore=" sam ";
$encabezado="<tr>\n  <td><b>#</b></td>\n  <td><b>User</b></td>\n  <td><b>Name</b></td>\n";
if(isset($_SESSION["locr"]))
	$locr=$_SESSION["locr"];
else
	$locr='.';

if(isset($_GET["clock"]) && $_GET["clock"]==1) {
	ob_start();
	header ("Expires: " . gmdate("D, d M Y H:i:s") . " GMT");
	header ("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header ("Cache-Control: no-cache, must-revalidate");
	header ("Pragma: no-cache");
	header ("Content-Type: text/html; charset=utf-8");
	session_start();
	ob_end_flush();

	if(!isset($contest) || !isset($localsite)) {
		$ct=DBGetActiveContest();
		$contest=$ct['contestnumber'];
		$localsite=$ct['contestlocalsite'];
	}
	if (($blocal = DBSiteInfo($contest, $localsite)) == null) {
		echo "0";
		exit;
	}
	if(isset($blocal['currenttime']))
		echo $blocal["currenttime"];
	else echo "0";
	exit;
}
if(isset($_GET['remote']) && is_numeric($_GET['remote'])) {
	ob_start();
	header ("Expires: " . gmdate("D, d M Y H:i:s") . " GMT");
	header ("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header ("Cache-Control: no-cache, must-revalidate");
	header ("Pragma: no-cache");
	header ("Content-Type: text/html; charset=utf-8");
	session_start();
	ob_end_flush();

	if (isset($_SESSION["usertable"])) {
		$_SESSION["usertable"] = DBUserInfo($_SESSION["usertable"]["contestnumber"],
			$_SESSION["usertable"]["usersitenumber"], $_SESSION["usertable"]["usernumber"]);
	} else {
		IntrusionNotify("scoretable1");
		ForceLoad("index.php");
	}
	if(!isset($_SESSION['usertable']['usertype']) || $_SESSION["usertable"]["usertype"] != "score") {
		IntrusionNotify("scoretable2");
		ForceLoad("index.php");
	}
}

if(!ValidSession()) {
	InvalidSession("scoretable.php");
	ForceLoad("index.php");
}
$loc = $_SESSION["loc"];
if(!isset($detail)) $detail=true;
if(!isset($final)) $final=false;
$scoredelay["admin"] = 2;
$scoredelay["score"] = 30;
$scoredelay["team"] = 30;
$scoredelay["judge"] = 10;
$scoredelay["staff"] = 60;
$actualdelay = 60;
if(isset($scoredelay[$_SESSION["usertable"]["usertype"]])) $actualdelay = $scoredelay[$_SESSION["usertable"]["usertype"]];
$ds = DIRECTORY_SEPARATOR;
if($ds=="") $ds = "/";

$scoretmp = $_SESSION["locr"] . $ds . "private" . $ds . "scoretmp" . $ds . $_SESSION["usertable"]["usertype"] . ".php";
$redo = TRUE;
if(file_exists($scoretmp)) {
	if(($strtmp = file_get_contents($scoretmp,FALSE,NULL,-1,100000)) !== FALSE) {
		$nuevoscore = file_get_contents($nuevoscore,FALSE,NULL,-1,100000);
		list($d) = sscanf($strtmp,"%*s %d");
		list($d) = sscanf($nuevoscore,"%*s %d");
		if($d > time() - $actualdelay) {
			$redo = FALSE;
		}
	}
}

if($_SESSION["usertable"]["usertype"]=='score' || $_SESSION["usertable"]["usertype"]=='admin' || (isset($_GET["remote"]) && is_numeric($_GET["remote"]))) {
	$privatedir = $_SESSION['locr'] . $ds . "private";
	$remotedir = $_SESSION['locr'] . $ds . "private" . $ds . "remotescores";
	$destination = $remotedir . $ds ."scores.zip";
	if(is_writable($remotedir)) {
		if($redo || !is_readable($destination)) {
			if (($s = DBSiteInfo($_SESSION["usertable"]["contestnumber"],$_SESSION["usertable"]["usersitenumber"])) == null)
				ForceLoad("index.php");

			$level=$s["sitescorelevel"];
			$data0 = array();
			if($level>0) {
				list($score,$data0) = DBScoreSite($_SESSION["usertable"]["contestnumber"], 
					$_SESSION["usertable"]["usersitenumber"], 0, -1);
			}
			$ct=DBGetActiveContest();
			$localsite=$ct['contestlocalsite'];
			$fname = $privatedir . $ds . "score_localsite_" . $localsite . "_" . md5($_SERVER['HTTP_HOST']);
			@file_put_contents($fname . ".tmp",base64_encode(serialize($data0)));
			@rename($fname . ".tmp",$fname . ".dat");

			$data0 = array();
			if($level>0) {
				list($score,$data0) = DBScoreSite($_SESSION["usertable"]["contestnumber"], 
					$_SESSION["usertable"]["usersitenumber"], 1, -1);
			}
			$ct=DBGetActiveContest();
			$localsite=$ct['contestlocalsite'];
			$fname = $remotedir . $ds . "score_site" . $localsite . "_" . $localsite . "_" . md5($_SERVER['HTTP_HOST']);
			@file_put_contents($fname . ".tmp",base64_encode(serialize($data0)));
			@rename($fname . ".tmp",$fname . ".dat");

			if(@create_zip($remotedir,glob($remotedir . '/*.dat'),$fname . ".tmp") != 1) {
				LOGError("Cannot create score zip file");
				if(@create_zip($remotedir,array(),$fname . ".tmp") == 1)
					@rename($fname . ".tmp",$destination);
			} else {
				@rename($fname . ".tmp",$destination);
			}
		}
	}
}

if(isset($_GET["remote"])) {
	if(is_numeric($_GET["remote"])) {
		if($_GET["remote"]==-42) {
			echo file_get_contents($destination);
		} else {
			if (($s = DBSiteInfo($_SESSION["usertable"]["contestnumber"],$_SESSION["usertable"]["usersitenumber"])) == null)
				ForceLoad("index.php");
			
			$level=$s["sitescorelevel"];
			$score = array();
			if($level>0) {
				list($score,$data0) = DBScoreSite($_SESSION["usertable"]["contestnumber"], 
					$_SESSION["usertable"]["usersitenumber"], 1, -1, $_GET["remote"]);
			}
			echo base64_encode(serialize($score));
		}
	} else {
		echo base64_encode(serialize(array()));
	}
	exit;
}

if(!$redo) {
	//encode
	$conf=globalconf();
	$strtmp = decryptData(substr($strtmp,strpos($strtmp,"\n")),$conf["key"],'score');
	$nuevoscore=decryptData(substr($nuevoscore,strpos($nuevoscore,"\n")),$conf["key"],'score');
	if($strtmp=="") $redo=TRUE;
}
if($redo) {
	$strtmp = "<script language=\"JavaScript\" src=\"" . $loc . "/hide.js\"></script>\n";

	$pr = DBGetProblems($_SESSION["usertable"]["contestnumber"]);

	$ct=DBGetActiveContest();
	$contest=$ct['contestnumber'];
	$duration=$ct['contestduration'];

	if(!isset($hor)) $hor = -1;
	if($hor>$duration) $hor=$duration;

	$level=$s["sitescorelevel"];
	if($level<=0) $level=-$level;
	else {
		$des=true;
	}

	if (($s = DBSiteInfo($_SESSION["usertable"]["contestnumber"],$_SESSION["usertable"]["usersitenumber"])) == null)
		ForceLoad("index.php");
	$score = DBScore($_SESSION["usertable"]["contestnumber"], $ver, $hor*60, $s["siteglobalscore"]);
	
	if ($_SESSION["usertable"]["usertype"]!="score" && $_SESSION["usertable"]["usertype"]!="admin" && $level>3) $level=3;

	$minu = 3;
	$rn = DBRecentNews($_SESSION["usertable"]["contestnumber"],
		$_SESSION["usertable"]["usersitenumber"], $ver, $minu);
	if(count($rn)>0 && $level>3) {
		$strtmp .= "<table border=0><tr>";
		$strtmp .= "<td>News (last ${minu}'): &nbsp;</td>\n";
		for($i=0; $i<count($rn); $i++) {
			$strtmp .= "<td width=200>";
			if($rn[$i]["yes"]=='t') {
				$strtmp .= "<img alt=\"".$rn[$i]["colorname"].":\" width=\"28\" ".
				"src=\"" . balloonurl($rn[$i]["color"]) ."\" />";
			}
			else
				$strtmp .= "<img alt=\"\" width=\"22\" ".
			"src=\"$loc/images/bigballoontransp-blink.gif\" />\n";
			$strtmp .= $rn[$i]["problemname"] . ": " . $rn[$i]["userfullname"] . " (" . ((int) ($rn[$i]["time"]/60)) . "')";
			$strtmp .= "</td>\n";
		}
		$strtmp .= "</tr></table>";
	}
	if($hor>=0) {
		$strtmp .= "<center>As of $hor minutes. Next: ";
		for($h=-30; $h<40; $h+=10) {
			if($hor+$h>=0 && $h!=0) {
				$strtmp .= "<a href=\"$loc/admin/report/score.php?p=0&hor=" . ($hor+$h) . "\">";
				if($h>0) $strtmp .= "+";
				$strtmp .= "$h</a>&nbsp;";
			}
		}
		$strtmp .= "</center><br>";
	}
	if(is_readable($_SESSION["locr"] . $ds . 'private' . $ds . 'score.sep')) {
		$rf=file($_SESSION["locr"] . $ds . 'private' . $ds . 'score.sep');
		$strtmp .= "<br><img src=\"$loc/images/smallballoontransp.png\" alt=\"\" onload=\"javascript:toggleGroup(1)\"> <b>Available scores:</b> \n";
		for($rfi=1;$rfi<=count($rf);$rfi++) {
			$lin = explode('#',trim($rf[$rfi-1]));
			if(isset($lin[1]) && $_SESSION["usertable"]["usertype"]!='admin') {
				$arr=explode(' ',trim($lin[1]));
				for($arri=0;$arri<count($arr);$arri++)
					if(preg_match($arr[$arri],$_SESSION["usertable"]["username"])) break;
				if($arri>=count($arr)) continue;
			}
			$lin = trim($lin[0]);
			if($lin=='') continue;
			$grname=explode(' ',$lin);
			$class=1;
			reset($score);
			while(list($e,$c) = each($score)) {
				if(!isset($score[$e]['classingroup'])) $score[$e]['classingroup']=array();
				for($k=1;$k<count($grname);$k++) {
					if($score[$e]['site']==$grname[$k]) {
						$score[$e]['classingroup'][$rfi]=$class;
						$class++;
					}
					else if(strpos($grname[$k],'/') >= 1) {
						$u1 = explode('/',$grname[$k]);
						if(isset($u1[1]) && $score[$e]['user'] >= $u1[0] && $score[$e]['user'] <= $u1[1]) {
							if(!isset($u1[2]) || $u1[2]==$score[$e]['site']) {
								$score[$e]['classingroup'][$rfi]=$class;
								$class++;
							}
						}
					}

				}
			}
			if($class>1)
				$strtmp .= "<a href=\"#\" onclick=\"javascript:toggleGroup($rfi)\">" . $grname[0] . "</a> ";
		}
		$strtmp .= "<br>\n";
	} else {
		reset($score);
		$class = 1;
		while(list($e,$c) = each($score)) {
			$score[$e]['classingroup'][1]=$class;
			$class++;
		}
	}
	
	$strtmp .= "<br>\n<table id=\"myscoretable\" width=\"100%\" border=1>\n <tr>\n  <td><b>#</b></td>\n  <td><b>User</b></td>\n  <td><b>Name</b></td>\n";
	if(!$des) {
		if($level>0)
			$strtmp .= "<td><b>Problems</b></td>";
	} else if($detail) {
		for($i=0;$i<count($pr);$i++){
			$strtmp .= "<td nowrap><b>" . $pr[$i]["problem"] . " &nbsp;</b></td>";
			$encabezado .="<td nowrap><b>" . $pr[$i]["problem"] . " &nbsp;</b></td>";
		}
	} 
	$strtmp .= "<td><b>Total</b></td>\n";
	$strtmp .= "</tr>\n";

	$encabezado .= "<td><b>Total</b></td>\n";
	$encabezado .= "</tr>\n";
	$n=0;
	reset($score);
	//$nuevoscore .= "<table id=\"myscoretable\" width=\"100%\" border=1>";
	while(list($e, $c) = each($score)) {
		reset($score[$e]['classingroup']);
		while(list($cg1,$cg2) = each($score[$e]['classingroup'])) {
			$strtmp .= " <tr class=\"";
			$strtmp .= "sitegroup" . $cg1 . "\">";
			$strtmp .= "<td>" . $cg2 . "</td>\n";
			//sam
			$nuevoscore .= " <tr class=\"";
			$nuevoscore .= "sitegroup" . $cg1 . "\">";
			$nuevoscore .= "<td>" . $cg2 . "</td>\n";
			//eof sam
/*	
		if($level>3 && !$final && $score[$e]["site"]==$ct['contestlocalsite'] &&
		   ((isset($_SESSION["scorepos"][$score[$e]["username"]."-".$score[$e]["site"]]) &&
			 $_SESSION["scorepos"][$score[$e]["username"]."-".$score[$e]["site"]] > $cg2) || 
			(isset($_SESSION["scoreblink"][$score[$e]["username"]."-".$score[$e]["site"]]) &&
			 $_SESSION["scoreblink"][$score[$e]["username"]."-".$score[$e]["site"]]>time()))) {
			$strtmp .= "  <td nowrap bgcolor=\"#b0b0a0\">" . $score[$e]["username"]."/".$score[$e]["site"];
			$strtmp .= "<td bgcolor=\"#b0b0a0\">" . $score[$e]["userfullname"];
			if(!isset($_SESSION["scoreblink"][$score[$e]["username"]."-".$score[$e]["site"]]) ||
				$_SESSION["scoreblink"][$score[$e]["username"]."-".$score[$e]["site"]]==0) {
				$_SESSION["scoreblink"][$score[$e]["username"]."-".$score[$e]["site"]] = time()+1;
			}
		}
		else {
*/
			$_SESSION["scoreblink"][$score[$e]["username"]."-".$score[$e]["site"]]=0;
			$strtmp .= "  <td nowrap>" . $score[$e]["username"]."/".$score[$e]["site"] . " ";
			$strtmp .= "<td>" . $score[$e]["userfullname"];
//		}
			$_SESSION["scorepos"][$score[$e]["username"]."-".$score[$e]["site"]] = $cg2;

			//sam
			$nuevoscore .= "  <td nowrap>" . $score[$e]["username"]."/".$score[$e]["site"] . " ";
			$nuevoscore .= "<td>" . $score[$e]["userfullname"];
			//eof sam

//    $strtmp .= "(" . $score[$e]["site"] . ")";
//    $strtmp .= "</td>\n";
//    if(!$detail && $score[$e]["userdesc"]!="")
//        $strtmp .= "(" . $score[$e]["userdesc"] . ")";
			$strtmp .= "</td>";
			//sam
			$nuevoscore .="</td>";
			//eof sam

			if($level > 0) {
				if(!$des) {
					$strtmp .= "<td>";
					$nuevoscore.="<td>";
				}
				for($h=0;$h<count($pr);$h++) {
					$ee = $pr[$h]["number"];
					if($detail) {
						if($des) {
							$strtmp 	.= "<td nowrap>";
							$nuevoscore .= "<td nowrap>";
//					$name=$score[$e]["problem"][$ee]["name"];
							if(isset($score[$e]["problem"][$ee]["solved"]) && $score[$e]["problem"][$ee]["solved"]) {
								$strtmp .= "<img alt=\"".$score[$e]["problem"][$ee]["colorname"].":\" width=\"18\" "."src=\"" . balloonurl($score[$e]["problem"][$ee]["color"]) ."\" />";

								$nuevoscore.="<img alt=\"".$score[$e]["problem"][$ee]["colorname"].":\" width=\"18\" "."src=\"" . balloonurl($score[$e]["problem"][$ee]["color"]) ."\" />";
							}
							else {
								if($level>3 && isset($score[$e]["problem"][$ee]["judging"]) && $score[$e]["problem"][$ee]["judging"]){
									$strtmp .= "<img alt=\"\" width=\"18\" "."src=\"$loc/images/bigballoontransp-blink.gif\" />\n";
									$nuevoscore.="<img alt=\"\" width=\"18\" "."src=\"$loc/images/bigballoontransp-blink.gif\" />\n";
								}
								else{
									$strtmp .= "&nbsp;";
									$nuevoscore.="&nbsp;";
								}
							}
						}
						if ($ver && $level<3) {
							if(isset($score[$e]["problem"][$ee]["solved"]) && $score[$e]["problem"][$ee]["solved"]) {
								if ($level==1) {
									$strtmp .= "/". $score[$e]["problem"][$ee]["time"] . "\n";
									$nuevoscore.="/". $score[$e]["problem"][$ee]["time"] . "\n";
								}
								else{
									$strtmp .= $score[$e]["problem"][$ee]["count"] . "/" .$score[$e]["problem"][$ee]["time"] . "\n";
									$nuevoscore .= $score[$e]["problem"][$ee]["count"] . "/" .$score[$e]["problem"][$ee]["time"] . "\n";					
								}
							} else if($des) {
								$strtmp .= "&nbsp;";
								$nuevoscore .= "&nbsp;";
							}
						}
						else {
							if (isset($score[$e]["problem"][$ee]['count']) && $score[$e]["problem"][$ee]["count"]!=0) {
								$tn = $score[$e]["problem"][$ee]["count"];
								if (isset($score[$e]["problem"][$ee]["solved"]) && $score[$e]["problem"][$ee]["solved"]) $t = $score[$e]["problem"][$ee]["time"];
								else $t = "-";
								$strtmp .= "<font size=\"-2\">" . $tn . "/${t}" . "</font>\n";
								$nuevoscore .= "<font size=\"-2\">" . $tn . "/${t}" . "</font>\n";
							} else if($des) {
								$strtmp .= "&nbsp;";
								$nuevoscore .= "&nbsp;";
							}
						}
						if($des){
							$strtmp .= "</td>";
							$nuevoscore .= "</td>";
						}
					}
				}
				if(!$des){ 
					$strtmp .= "&nbsp;</td>\n";
					$nuevoscore .= "&nbsp;</td>\n";
				}
			}
			$strtmp .= "  <td nowrap>" . 
			$score[$e]["totalcount"] . " (" . $score[$e]["totaltime"] . ")</td>\n";
			$strtmp .= " </tr>\n";

			$nuevoscore .= " <td nowrap>".$score[$e]["totalcount"] . " (" . $score[$e]["totaltime"] . ")</td>\n";
			$nuevoscore .= " </tr>\n";


			$n++;
		}
	}
	$strtmp .= "</table>";
	$nuevoscore .= "</table>";
	if ($n == 0) $strtmp .= "<br><center><b><font color=\"#ff0000\">SCOREBOARD IS EMPTY</font></b></center>";
	else {
		if(!$des) 
			if($level>0) $strtmp .= "<br><font color=\"#ff0000\">P.S. Problem names are hidden.</font>";
		else  $strtmp .= "<br><font color=\"#ff0000\">P.S. Problem data are hidden.</font>";
	}

	$conf=globalconf();
	
	$strtmp = "<!-- " . time() . " --> <?php exit; ?>\n" . encryptData($strtmp,$conf["key"],false);
	
	$nuevoscore = "<!-- " . time() . " --> <?php exit; ?>\n" . encryptData($nuevoscore,$conf["key"],false);

	if(file_put_contents($scoretmp, $strtmp,LOCK_EX)===FALSE) {
		if($_SESSION["usertable"]["usertype"] == 'admin') {
			MSGError("Cannot write to the score cache file -- performance might be compromised");
		}
		LOGError("Cannot write to the ".$_SESSION["usertable"]["usertype"]."-score cache file -- performance might be compromised");
	}
	$conf=globalconf();
	
	$strtmp = decryptData(substr($strtmp,strpos($strtmp,"\n")),$conf["key"]);
	$nuevoscore = decryptData(substr($nuevoscore,strpos($nuevoscore,"\n")),$conf["key"]);
}
/*
	sam
*/
	//solo cuenta de score cambia
	//con cuenta de admin no cambia nada 
	//para q tenga efecto el frozen	
	if($_SESSION["usertable"]["usertype"]=='score'){
		if(file_exists("/var/www/scoreori.html")&&file_exists("/var/www/scorenew.html")){
			$fp = fopen("/var/www/scorenew.html", "w");
			fputs($fp, "<table id=\"nuevo\" width=\"100%\" border=1>".$nuevoscore);
			fclose($fp);
		}else{
			$fp = fopen("/var/www/scoreori.html", "w");
			fputs($fp, "<table id=\"ori\" width=\"100%\" border=1>".$encabezado.$nuevoscore);
			fclose($fp);

			$fp = fopen("/var/www/scorenew.html", "w");
			fputs($fp, "<table id=\"nuevo\" width=\"100%\" border=1>".$nuevoscore);
			fclose($fp);
		}
	}
/*
End sam
*/
	echo $strtmp;
	//echo $nuevoscore;
?>