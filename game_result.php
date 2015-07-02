<?
header("Content-type: text/html; charset=utf-8"); 
$mv7root = dirname(__FILE__).'/../../web_work/mv7';
require($mv7root.'/lib/class.Db_mysql.php');
require($mv7root.'/lib/class.MvUtil.php');
//----

$game = isset($_REQUEST['game'][0])?$_REQUEST['game']:'';
$level = isset($_REQUEST['level'][0])?$_REQUEST['level']:'';
$score = isset($_REQUEST['score'][0])?$_REQUEST['score']:'';
$scoreTds = isset($_REQUEST['scoreTds'][0])?$_REQUEST['scoreTds']:'';
$mode = isset($_REQUEST['mode'][0])?$_REQUEST['mode']:'step1';
$retUrl = isset($_REQUEST['retUrl'][0])?$_REQUEST['retUrl']:(isset($_SERVER['HTTP_REFERER'][0])?$_SERVER['HTTP_REFERER']:'game_hexa.html');
$nick = isset($_REQUEST['nick'][0])?$_REQUEST['nick']:'';
$date = isset($_REQUEST['date'][0])?$_REQUEST['date']:'';
$cookie_nick = isset($_COOKIE['c_nick'][0])?$_COOKIE['c_nick']:'';

$setting = isset($_REQUEST['setting'][0])?$_REQUEST['setting']:'';
$etc = isset($_REQUEST['etc'][0])?$_REQUEST['etc']:'';
$error = '';
//====================================
if($mode=='step1'){
	if($game==''
	|| $level==''
	|| $score==''
	|| $scoreTds==''
	|| $date==''
	){
		exit('Not Allow step1');
	}
	if(strtotime($date) < 100){
		exit('Not Allow step1 2');
	}
	if(!isset($nick[0])){ $nick = $cookie_nick; }
}
//====================================
if($mode=='save'){
	if($game==''
	|| $level==''
	|| $score==''
	|| $scoreTds==''
	|| $nick==''
	|| $date==''
	){
		exit('Not Allow save');
	}
	if(strtotime($date) < 100){
		exit('Not Allow save 2');
	}
	if($scoreTds*3*100 < $score){
		$error = '잘못된 접근입니다.(1)';
	}
	if($scoreTds - $level*30  > 30 ){
		$error = '잘못된 접근입니다.(2)';
	}
	if(!isset($error[0])){
		$etc .= (isset($etc[0])?'&':'').'&scoreTds='.$scoreTds;

		$dbObj =& new Db_mysql;
		$cfgDbObj = parse_ini_file($mv7root.'/ini/ini.Db_mysql.php',true);
		//$dbObj->debugLevel = 1;	//에러레벨
		$dbObj->connect($cfgDbObj['dsn'],$cfgDbObj['dsnOptions']);
		$sql = "INSERT INTO game_hexa (gr_nick, gr_ip, gr_game, gr_date, gr_setting, gr_level, gr_score, gr_etc)
		VALUES (:gr_nick, :gr_ip, :gr_game, :gr_date, :gr_setting, :gr_level, :gr_score, :gr_etc)
		ON DUPLICATE KEY UPDATE
		gr_game = :gr_game
		";
		$dbObj->parse($sql);

		$dbObj->setBind(':gr_nick',$nick);
		$dbObj->setBind(':gr_ip',$_SERVER['REMOTE_ADDR']);
		$dbObj->setBind(':gr_game',$game);
		//$date = date('YmdHis');
		$dbObj->setBind(':gr_date',$date);
		$dbObj->setBind(':gr_setting',$setting);
		$dbObj->setBind(':gr_level',$level);
		$dbObj->setBind(':gr_score',$score);
		$dbObj->setBind(':gr_etc',$etc);
		$R = $dbObj->execute();

		$dbObj->disconnect();

		if($R){
			setCookie('c_nick',$nick,time()+60*60*24*30,dirname($_SERVER['REQUEST_URI']));
		}
		$qstrs = array();
		$qstrs['mode']='result';
		$qstrs['game']=$game;
		$qstrs['nick']=$nick;
		$qstrs['date']=$date;
		$qstrs['retUrl']=$retUrl;
		$t = array();
		foreach($qstrs as $k=>$v){
			$t[] = urlencode($k).'='.urlencode($v);
		}
		$url = '?'.implode('&',$t);
		header('Location: '.$url);
		exit('Location: '.$url);
	}
}
//====================================
if($mode=='result'){
	$date = isset($_REQUEST['date'][0])?$_REQUEST['date']:'';
	if(!isset($nick[0])){ $nick = $cookie_nick; }
	if($game==''
//	|| $nick==''
//	|| $date==''
	){
		exit('Not Allow result');
	}

	$dbObj =& new Db_mysql;
	$cfgDbObj = parse_ini_file($mv7root.'/ini/ini.Db_mysql.php',true);
	//$dbObj->debugLevel = 1;	//에러레벨
	$dbObj->connect($cfgDbObj['dsn'],$cfgDbObj['dsnOptions']);
	//=== 랭커(이번주)
		$tm = time()-(date('w')*60*60*24); //이번주 시작일
		$d0 = date('Ymd000000',$tm);
		$d1 = date('Ymd999999',$tm+(7*60*60*24));
		$sql = "SELECT *
				, (SELECT COUNT(*)+1 FROM game_hexa gr1 WHERE gr1.gr_score > gr0.gr_score) rank_all
				FROM game_hexa gr0
				WHERE gr_game = :gr_game
				AND gr_date BETWEEN '{$d0}' AND '{$d1}'
				ORDER BY gr_score DESC, gr_date ASC
				LIMIT 10
				";
		$dbObj->parse($sql);
		$dbObj->setBind(':gr_game',$game);
		$R = $dbObj->execute();
		$rowsA = $dbObj->fetchArray(1);
	//=== 랭커(전번주)
		//$tm = time()-(date('w')*60*60*24); //이번주 시작일
		$tm -= (7*60*60*24);
		$d2 = date('Ymd000000',$tm);
		$d3 = date('Ymd999999',$tm+(7*60*60*24));
		$sql = "SELECT *
				, (SELECT COUNT(*)+1 FROM game_hexa gr1 WHERE gr1.gr_score > gr0.gr_score) rank_all
				FROM game_hexa gr0
				WHERE gr_game = :gr_game
				AND gr_date BETWEEN '{$d2}' AND '{$d3}'
				ORDER BY gr_score DESC, gr_date ASC
				LIMIT 10
				";
		$dbObj->parse($sql);
		$dbObj->setBind(':gr_game',$game);
		$R = $dbObj->execute();
		$rowsA2 = $dbObj->fetchArray(1);

	
	//=== 현 게임 기준 성적
	if($nick!='' && $date!=''){

		$sql = "SELECT *
				, (SELECT COUNT(*)+1 FROM game_hexa gr1 WHERE gr_nick = :gr_nick AND gr1.gr_score > gr0.gr_score) rank_self
				, (SELECT COUNT(*)+1 FROM game_hexa gr1 WHERE gr1.gr_score > gr0.gr_score AND gr_date BETWEEN '{$d0}' AND '{$d1}') rank_all
				, (SELECT COUNT(*) FROM game_hexa gr1 WHERE gr_nick = :gr_nick ) count_self
				FROM game_hexa gr0
				WHERE gr_game = :gr_game AND gr_nick = :gr_nick AND gr_date = :gr_date";
		$dbObj->parse($sql);

		$dbObj->setBind(':gr_nick',$nick);
		$dbObj->setBind(':gr_game',$game);
		$dbObj->setBind(':gr_date',$date);
		$R = $dbObj->execute();
		$rows = $dbObj->fetchArray(1);
		$rowR = isset($rows[0])?$rows[0]:null;
	}else{
		$rowR = null;
	}
	/*
gr_nick  gr_ip         gr_game  gr_date         gr_setting  gr_level  gr_score  gr_etc  rank_self  rank_all
-------  ------------  -------  --------------  ----------  --------  --------  ------  ---------  --------
xxx      210.92.76.20  hexa     20130425165709                     1         0                  3         3
	*/
	//=== 닉네임 기준 성적
	if($nick!=''){
		$sql = "SELECT A.* 
				, (SELECT COUNT(*)+1 FROM game_hexa gr1 WHERE gr1.gr_score > A.max_gr_score AND gr_date BETWEEN '{$d0}' AND '{$d1}') rank_all
				FROM (
					SELECT 
						MAX(gr_score) max_gr_score
						, MAX(gr_level) max_gr_level
					FROM game_hexa gr0
					WHERE gr_game = :gr_game AND gr_nick = :gr_nick 
					AND gr_date BETWEEN '{$d0}' AND '{$d1}'
				)A";
		$dbObj->parse($sql);

		$dbObj->setBind(':gr_nick',$nick);
		$dbObj->setBind(':gr_game',$game);
		$R = $dbObj->execute();
		$rows = $dbObj->fetchArray(1);
		$rowN = isset($rows[0]['max_gr_score'][0])?$rows[0]:null;
	}else{
		$rowN = NULL;
	}
/*
max_gr_score  rank_all
------------  --------
		 300         1
*/
	//---
	unset($rows);
	$dbObj->disconnect();
	
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>게임 결과</title>
<meta name="viewport" content="width=400, initial-scale=1.0, maximum-scale=1.0" />

<link type="text/css" rel="stylesheet" href="../../js/_M.all/_M.all.css" />
<script type="text/javascript" src="../../js/_M.all/_M.all.js"></script>
<link rel="stylesheet" type="text/css" href="game_hexa.css"/>
<style type="text/css">
.rank_1{ font-size:1.2em; color:#FF0; background-color:#aaa; font-weight:bold;}
.rank_2{font-size:1.1em;; color:#cee; background-color:#999; font-weight:bold;}
.rank_3{font-size:1.0em;; color:#9B2134; background-color:#888; font-weight:bold;}
.rank_4{background-color:#777;color:#aaa; }
.rank_5{background-color:#666;color:#999; }
.rank_6{background-color:#555;color:#888; }
.rank_7{background-color:#444;color:#777; }
.rank_8{background-color:#333;color:#666; }
.rank_9{background-color:#222;color:#555; }
.rank_10{background-color:#111;color:#444; }
</style>
</head>
<body>
<div style="width:320px; margin:0 auto;">
<? if($mode=='step1' && $error==''){ ?>
<script type="text/javascript">
//<!--
	function check_form(f){
		try{
			var ta;
			ta = f.nick;
			if(ta.value.length<2){
				alert("닉네임을 필수입니다.");
				ta.focus();
				return false;
			}
			ta = f.game;
			if(ta.game.length<1){alert("게임명은 필수입니다.");ta.focus();return false;}
			ta = f.level;
			if(ta.value.length<1){alert("레벨은 필수입니다.");ta.focus();return false;}
			ta = f.score;
			if(ta.value.length<1){alert("점수는 필수입니다.");ta.focus();return false;}
			ta = f.scoreTds;
			if(ta.value.length<1){alert("처리블록은 필수입니다.");ta.focus();return false;}
			return true;
		}catch(e){
			alert("알 수 없는 에러가 발생되었습니다. 개발자에게 연락해주세요");
			return false;
		}
		return false;
	}
//-->
</script>
<form action="" method="post">
	<input type="hidden" name="retUrl" value="<?=htmlspecialchars($retUrl)?>" />
	<input type="hidden" name="mode" value="save" />
	<input type="hidden" name="game" value="<?=htmlspecialchars($game)?>" />
	<input type="hidden" name="setting" value="<?=htmlspecialchars($setting)?>" />
	<input type="hidden" name="etc" value="<?=htmlspecialchars($etc)?>" />
	<input type="hidden" name="date" value="<?=htmlspecialchars($date)?>" />
	<fieldset>
		<legend>기록을 남길까요?</legend>
		<table  class="table_result"  border="0" cellpadding="0" cellspacing="0" style=" margin:0 auto;">
			<col width="100" /><col width="100" />
			<tr><th>닉네임</th><td  align="center"><input type="text" name="nick" class="txtbox" size="8" maxlength="8" value="<?=htmlspecialchars($nick)?>" /></td></tr>
			<tr><th>레벨</th><td align="center">Lv.<input readonly="readonly" class="txtbox nobox" type="text" size="4" name="level" value="<?=htmlspecialchars($level)?>" /></td></tr>
			<tr><th>점수</th><td align="center"><input readonly="readonly" class="txtbox nobox" type="text" size="4" name="score" value="<?=htmlspecialchars($score)?>"  />점</td></tr>
			<tr><th>처리블록</th><td align="center"><input readonly="readonly" class="txtbox nobox" type="text" size="4" name="scoreTds" value="<?=htmlspecialchars($scoreTds)?>"  />개</td></tr>


			<tr>
				<td colspan="2" align="center"><input class="btnbox" type="submit" value="점수 입력"  style="font-size:20px; display:block; width:80%; font-weight:bold"/></td>
			</tr>
			<tr>
				<td colspan="2" align="center">
				<button onkeyup="return false;" class="btnbox"  style="font-size:20px; display:block; width:80%; font-weight:bold"><a href="<?=htmlspecialchars($retUrl)?>" >게임 계속하기</a></button></td>
			</tr>
		</table>
	</fieldset>
</form>
<? } ?>
<? if($mode=='result' && $error==''){ ?>
<? /*
gr_nick  gr_ip         gr_game  gr_date         gr_setting  gr_level  gr_score  gr_etc  rank_self  rank_all  count_self

max_gr_score  max_gr_level  rank_all
*/ ?>
	<fieldset>
		<legend>
		<strong><?=isset($nick[0])?htmlspecialchars($nick):'-'?></strong>
		님의 <strong><?=htmlspecialchars($game)?></strong> 기록</legend>
		<table class="table_result"  border="0" cellpadding="0" cellspacing="0" style=" margin:0 auto;">
			<col width="100" /><col width="200" />
			<? if(isset($rowN)){ ?>
			<tr>
				<th colspan="2" style="border-top:6px double #369; border-bottom:1px solid #abc;">이번주 본인 최고점수</th></tr>
			<tr><th>최고 점수</th>
			<td align="center">【<?=$rowN['rank_all']?>등】 [Lv.<?=$rowN['max_gr_level']?>] <?=$rowN['max_gr_score']?>점 </td>
			</tr>
			<? }else{ ?>
			<tr><th colspan="2" style="border-top:6px double #369; border-bottom:1px solid #abc; color:red;">닉네임이 등록되지 않았습니다.</th></tr>
			<? } ?>
			<? if(isset($rowR)){ ?>
			<tr><th colspan="2">&nbsp;</th></tr>
			<tr><th colspan="2" style="border-top:6px double #369; border-bottom:1px solid #abc;">이번 게임</th></tr>
			<tr><th>이번주 랭킹</th>
			<td align="center">【<?=$rowR['rank_all']?>등】 [Lv.<?=$rowR['gr_level']?>] <?=$rowR['gr_score']?>점 </td>
			</tr>
			<tr><th>자체랭킹</th><td align="center"><?=$rowR['rank_self']?>등 / <?=$rowR['count_self']?>게임</td>
			</tr>
			<? if($rowR['rank_self'] == '1'){ ?>
				<tr class="rank_1"><th>기록갱신!</th><td align="center">New Record!</td></tr>
				<? }else if($rowR['rank_self'] < 10){ ?>
				<tr class="rank_2"><th>기록갱신!</th><td align="center">High Record!</td></tr>
				<? } ?>
			<? } ?>
			<tr><th colspan="2">&nbsp;</th></tr>
			<tr>
				<td colspan="2" align="center">
				<button onkeyup="return false;" class="btnbox"  style="font-size:20px; width:80%; font-weight:bold"><a href="<?=htmlspecialchars($retUrl)?>" >게임 계속하기</a></button></td>
			</tr>
			<tr><th colspan="2">&nbsp;</th></tr>
		</table>
		<table class="table_result"  border="0" cellpadding="0" cellspacing="0" style=" margin:0 auto;">
			<col width="40" /><col width="120" /><col width="140" />
			<tr><th colspan="3" style="border-top:6px double #369; border-bottom:1px solid #abc;">이번주 랭킹</th></tr>
			<? 
			$inum = 0;
			foreach($rowsA as $k=>$row){ 
			$inum++;
			?>
			<tr class="rank_<?=$inum?>"><th><?=$inum?></th><td  align="center"><strong><?=htmlspecialchars($row['gr_nick'])?></strong></td>
				<td  align="center">[Lv.<?=$row['gr_level']?>] <?=$row['gr_score']?>점</td>
			</tr>
			<? 
			} 
			?>
			<tr><th colspan="3">&nbsp;</th></tr>
			<tr><th colspan="3" style="border-top:6px double #369; border-bottom:1px solid #abc;">저번주 랭킹</th></tr>
			<? 
			$inum = 0;
			foreach($rowsA2 as $k=>$row){ 
			$inum++;
			?>
			<tr class="rank_<?=$inum?>"><th><?=$inum?></th><td  align="center"><strong><?=htmlspecialchars($row['gr_nick'])?></strong></td>
				<td  align="center">[Lv.<?=$row['gr_level']?>] <?=$row['gr_score']?>점</td>
			</tr>
			<? 
			} 
			?>
			<tr><th colspan="3">&nbsp;</th></tr>
		</table>


	</fieldset>
<? } ?>
<? if($error!=''){ ?>
	<fieldset>
		<legend><strong>에러</legend>
		<div style="color:red; font-weight:bold; text-align:center; line-height:2em; font-size:18px;"><?=$error?></div>
		<div style="text-align:center">
		<button onkeyup="return false;" class="btnbox"  style="font-size:20px; width:80%; font-weight:bold"><a href="<?=htmlspecialchars($retUrl)?>" >게임 계속하기</a></button>
		</div>
	</fieldset>
<? } ?>


</div>
<script type="text/javascript">

  var _gaq = _gaq || [];
  _gaq.push(['_setAccount', 'UA-28167507-1']);
  _gaq.push(['_trackPageview']);

  (function() {
    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
  })();

</script>

</body>
</html>
