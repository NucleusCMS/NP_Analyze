<?php

/*
	"NP_Analyze 0.5331" Nucleus Analyze Plugin
*/

	$strRel = '../../../';
	include($strRel . 'config.php');
	include($DIR_LIBS . 'PLUGINADMIN.php');
	if($_GET['dl']) ExportFile();

// Start
	$oPluginAdmin = new PluginAdmin('Analyze');
	global $member, $manager, $CONF, $DIR_MEDIA, $oPluginAdmin;
	if(!($member->isLoggedIn())) doError('You\'re not access.');
	if(!($oPluginAdmin->plugin->loginMember() == 1 || $member->isAdmin())) doError('You\'re not access.');
	$oPluginAdmin->start();

// Define
	$top_limit = $oPluginAdmin->plugin->getOption('alz_top_limit');
	$pastdir = $oPluginAdmin->plugin->getOption('alz_pastdir');
	$temp_judge = $oPluginAdmin->plugin->getOption('alz_temp');
	$get_id = $oPluginAdmin->plugin->getID();
	if($top_limit <= 0) $top_limit = 3;
	if($top_limit > 30) $top_limit = 30;
	$past = ($_GET['past']) ? htmlspecialchars($_GET['past']) : htmlspecialchars(postVar('past'));
	if(strlen($past) == 10) $past = substr($past, 0, 7);
	$this_month = ($past) ? date("Y-m", strtotime($past."-01")) : date("Y-m");
	$datestart = $oPluginAdmin->plugin->getOption('alz_datestart');
	$datestart2 = date("U", strtotime($datestart));
	$data_count = ceil((date("U") - $datestart2)/86400);
	$t_month = date("Y-m-d");
	$toplink = $CONF['PluginURL'].'analyze/index.php';
	$sslink = $toplink.'?select=';
	$dd = ($_GET['select'] == 'csv') ? 'csv' : 'db';
	$select = ($_GET['select']) ? htmlspecialchars($_GET['select']) : htmlspecialchars(postVar('select'));
	$p0 = (is_numeric($_GET['page'])) ? $_GET['page'] : 1 ;
	$p00 = (is_numeric($_GET['page'])) ? ($_GET['page'] - 1) * 50 : 0 ;
	if($p0 > 1) $p1 = $p0 - 1;
	$p2 = $p0 + 1;
	$sort = ($_GET['sort'] == "ASC") ? 'DESC' : 'ASC';

// General
	if($oPluginAdmin->plugin->loginMember() != 1 || $member->isAdmin()) $log_m = 1;
	$hit_range = $oPluginAdmin->plugin->getOption('alz_hit_range');
	$g_u = $oPluginAdmin->plugin->getURL();
	$g_v = $oPluginAdmin->plugin->getVersion();
	$ti_d = $oPluginAdmin->plugin->getOption('alz_time_d');
	if($log_m) $ver = ' (*<a href="'.$g_u.'version.php?v='.$g_v.'&amp;p=Analyze" title="'._NP_ANALYZE_UPGRADE.'">NP_Analyze '.$g_v.'</a>)';
	echo '<h2><a href="'.$toplink.'">'._NP_ANALYZE_TITLE.'</a> <span style="font-size: 12px;">'.date("Y-m-d H:i:s", time()+($ti_d*3600)).$ver.'</span></h2>';
	if($_GET['select'] == 'b' || postVar('search')) $sform = '<form method="post" action="'.$toplink.'">';
	$th_month = (date("d") == '01' && $data_count > 1) ? date("Y-m", strtotime("-1 day")) :$this_month ;
	$span_1 = '<span style="border: 1px solid #cccccc; padding: 0px 2px;">';
	$sform .= $span_1.'<a href="'.$sslink.'b" title="'._NP_ANALYZE_DAILY._NP_ANALYZE_ACCESSLOG.'" style="font-weight: normal;">'._NP_ANALYZE_ACCESSLOG0.'</a></span> '
.$span_1.'<a href="'.$sslink.'month&amp;sort=DESC&amp;fie=aldate&amp;past='.$th_month.'" title="'._NP_ANALYZE_LAST.'" style="font-weight: normal;">'._NP_ANALYZE_MONTH.'</a></span> ';
	$opt_s0 = '<a href="'.$CONF['AdminURL'].'index.php?action=pluginoptions&amp;plugid='.$get_id.'" title="'._NP_ANALYZE_OPTIONS.'" style="font-weight: normal;">'._NP_ANALYZE_OPTIONS0.'</a>';
	$opt_s = '['.$opt_s0.']';
	if($log_m) $sform .= $span_1.'<a href="'.$sslink.'ng" title="'._NP_ANALYZE_NG.'" style="font-weight: normal;">'._NP_ANALYZE_NG0.'</a></span> '
.$span_1.'<a href="'.$sslink.'db" title="'._NP_ANALYZE_DB0.'" style="font-weight: normal;">'._NP_ANALYZE_DB.'</a></span> '
.$span_1.'<a href="'.$sslink.'csv" title="'._NP_ANALYZE_CSV0.'" style="font-weight: normal;">'._NP_ANALYZE_CSV.'</a></span> '
.$span_1.$opt_s0.'</span> ';
	switch(TRUE) {
	case ($select == 'b' && !$_GET['group']) :
		$qtable = qTable($past);
		$post = PostQuery();
		$qu = SearchQuery($qtable);
		if($past) $sform .= '<input name="past" type="hidden" value="'.$past.'" />';
		$sform .= '<input name="serch_query" size="25" value="'.$post.'" />
<input name="select" type="hidden" value="'.$select.'" />
<input name="search" type="submit" value="'._NP_ANALYZE_SEARCH.'" tabindex="10122" />';
		if($p1) $sform .= ' <a href="'.$sslink.'b&amp;page='.$p1.'&amp;sort='.htmlspecialchars($_GET['sort']).'&amp;fie='.htmlspecialchars($_GET['fie']).'&amp;past='.$past.'&amp;query='.$post.'&amp;jd='.$past.'" title="'._NP_ANALYZE_PEPREV.'">P.'.$p1.'</a>';
		$sform .= ' [P.'.$p0.'] ';
		$sform .= '<a href="'.$sslink.'b&amp;page='.$p2.'&amp;sort='.htmlspecialchars($_GET['sort']).'&amp;fie='.htmlspecialchars($_GET['fie']).'&amp;past='.$past.'&amp;query='.$post.'&amp;jd='.$past.'" title="'._NP_ANALYZE_PENEXT.'">P.'.$p2.'</a>';
		$sform .= '</form>';
	}
	$sform .= '<p>'.GetGroupNavi($sslink, $th_month).'</p>';
	$rs_u = mysql_query("SELECT * FROM ".sql_table('plugin_analyze_hit'));
	$switch = NaviSwitch($sslink, $this_month, $pastdir, $select, $fie);

// Change monthly log
	if($temp_judge == 'yes' && $select == 'b' && $past && $_GET['past'] != $_GET['jd']) {
		$ch_log = quickQuery("SELECT LEFT(aldate, 7) as result FROM ".sql_table('plugin_analyze_temp')." LIMIT 1");
		if($ch_log != $past) $oPluginAdmin->plugin->TempChange($past);
	}
	$t_table = sql_table('plugin_analyze_templog');
// check
//	if($_GET['dc'] != 'yes') sql_query("CREATE TABLE ".$t_table." as SELECT * FROM ".sql_table('plugin_analyze_temp'));

// Switch
	switch(TRUE) {
		case ($oPluginAdmin->plugin->TableExists($t_table)):
			CheckDateChange($t_table, $toplink, $sform, $get_id, $sslink, $opt_s, $toplink);
			return;
		case ($_GET['select'] == 'month'):
			MonthlyResult($hit_range ,$past, $this_month, $top_limit, $sslink, $switch, $sform, $temp_judge);
			return;
		case ($_GET['group']):
			GetGroup($past, $p0, $p1, $p2, $p00, $sslink, $sform, $this_month, $sort, $top_limit);
			return;
		case (($_GET['select'] == 'db' || postVar('db') ) && $log_m):
			EditDatabaseTable($sform, $get_id, $sslink, $opt_s, $toplink);
			return;
		case (($_GET['select'] == 'ng' || postVar('ip') || postVar('ok')) && $log_m):
			ExclusionHost($sslink, $toplink, $sform);
			return;
		case ($_GET['select'] == 'csv' && $log_m):
			EditCsvFile($get_id, $sform, $pastdir, $opt_s, $sslink, $temp_judge);
			return;
		case ($_GET['select'] == 'b' || postVar('delete') || postVar('serch_query')):
			AccessLog($sform ,$toplink, $past, $sslink, $select, $p0, $p1, $p2, $p00, $this_month, $temp_judge, $switch, $pastdir, $sort, $log_m, $ch_log);
			return;
		case (($_GET['select'] == 'total_change' || postVar('ahvisit')) && $log_m):
			TotalCountChange($sform);
			return;
		case (!file_exists($DIR_MEDIA.$pastdir."/")):
			CsvDir($pastdir, $sslink, $opt_s);
			if($_GET['select'] == 'dir' && $_GET['re']) {
				break;
			}else {
				return;
			}
		case (mysql_num_fields($rs_u) == 8):
			UpdateCheck($opt_s);
			return;
	}

// Main page
	echo $sform;
	if($oPluginAdmin->plugin->getOption('alz_time') == 'yes' || $_GET['td']) $tdtd = 1;
	if(!$tdtd) echo ' <a href="'.$toplink.'?td=1">'._NP_ANALYZE_TIME3.'</a>';
	echo '<h3>'._NP_ANALYZE_TOTAL1.' (*'.$datestart.' ~ '.$t_month.' : '.number_format($data_count)._NP_ANALYZE_DAYS.')</h3>
<table>
<thead>
<tr class="jsbutton">
<th>'._NP_ANALYZE_DATE.'</th>
<th colspan="2">'._NP_ANALYZE_VISIT.' (*'._NP_ANALYZE_DAY_AVG.')</th>
<th colspan="2">'._NP_ANALYZE_HIT.' (*'._NP_ANALYZE_DAY_AVG.')</th>
<th colspan="2"><a href="'.$sslink.'ng" title="'._NP_ANALYZE_NG.'">'._NP_ANALYZE_NG0.'</a> (*'._NP_ANALYZE_DAY_AVG.')</th>
<th>'._NP_ANALYZE_TOTAL0.' (*'._NP_ANALYZE_HIT.'+'._NP_ANALYZE_NG0.')</th>
<th>'._NP_ANALYZE_HIT.'/'._NP_ANALYZE_VISIT.'</th>
</tr>
</thead>
<tbody>';
	$to_c = oCount('today');
	$to_c2 = oCount('today2');
	$t_c = ($to_c) ? round($to_c2/$to_c, 2) : 0;
	$today_h = mysql_num_rows(sql_query("SELECT allog FROM ".sql_table('plugin_analyze_log')));
	$robot_t1 = quickQuery("SELECT ahrobot as result FROM ".sql_table('plugin_analyze_hit')." WHERE ahdate = '2000-01-01' LIMIT 1");
	$robot_c1 = ($robot_t1) ? number_format($robot_t1/$data_count) : 0;
	echo '<tr onmouseover="focusRow(this);" onmouseout="blurRow(this);">
<td><a href="'.$sslink.'b&amp;sort=DESC&amp;past='.$past.'&amp;group=1"><img src="documentation/icon-help.gif" alt="link" height="15" width="15" title="'._NP_ANALYZE_GROUP.'"></a>'.$t_month.'</td>
<td colspan="2" style="text-align: center;">'.number_format($to_c).'</td>
<td colspan="2" style="text-align: center;">'.number_format($to_c2).'</td>
<td colspan="2" style="text-align: center;">'.number_format($today_h - $to_c2).'</td>
<td style="text-align: center;">'.number_format($today_h).'</td>
<td style="text-align: center;">'.$t_c.'</td>
</tr>';
	$tot_c = oCount();
	$tot_c2 = oCount('total2');
	$t_c1 = ($tot_c) ? round($tot_c2/$tot_c, 2): 0;
	$r_c = $robot_t1 + $today_h - $to_c2;
	if($log_m) $log_m1 = ' (*<a href="'.$sslink.'total_change" title="'._NP_ANALYZE_COUNT_CHANGE.'">'._NP_ANALYZE_CHANGE.'</a>)';
	if($data_count) {
		$tot_c_a = number_format($tot_c/$data_count);
		$tot_c2_a = number_format($tot_c2/$data_count);
	}
	echo '<tr onmouseover="focusRow(this);" onmouseout="blurRow(this);">
<td>'._NP_ANALYZE_TOTAL.$log_m1.'</td>
<td style="text-align: right;">'.number_format($tot_c).'</td>
<td style="text-align: right;">(*'.$tot_c_a.')</td>
<td style="text-align: right;">'.number_format($tot_c2).'</td>
<td style="text-align: right;">(*'.$tot_c2_a.')</td>
<td style="text-align: right;">'.number_format($r_c).'</td>
<td style="text-align: right;">(*'.$robot_c1.')</td>
<td style="text-align: center;">'.number_format($tot_c2 + $r_c).'</td>
<td style="text-align: center;">'.$t_c1.'</td>
</tr>';
	echo '</tbody>
</table>';
	if($tdtd && !$past) TimeDistinction($sslink, $t_month);
	if($data_count < 2) {
		PlugEnd();
		return;
	}
	$data_r = ($top_limit < $data_count) ? $top_limit : ($data_count - 1) ;
	$mtitle = _NP_ANALYZE_LAST1.$data_r._NP_ANALYZE_LAST2;
	echo '<h3><a href="'.$sslink.'month&amp;sort=DESC&amp;fie=aldate&amp;past='.$th_month.'"><img src="documentation/icon-help.gif" alt="link" height="15" width="15" title="'._NP_ANALYZE_LAST.'"></a>'.$mtitle .'</h3>
'.AnalyzeMainGroup($hit_range ,$past, $this_month, $top_limit, 10, $sslink, $temp_judge)
.'<p>*PHP '.phpversion().' / *MySQL '.quickQuery('SELECT VERSION() as result').'</p>
<p>'.$sform.'</p>';
	PlugEnd();

	function TimeDistinction($sslink = '' ,$t_month = '') {
		global $oPluginAdmin, $CONF;
		$t = 0;
		$result = sql_query("SELECT SUBSTRING(aldate,12,2) as time, COUNT(allog) as count FROM ".sql_table('plugin_analyze_log')." GROUP BY time ORDER BY allog ASC");
		while($row = mysql_fetch_assoc($result)) {
			for($j=$t; $j<24; $j++){
				if($j < 10) $j = '0'.$j;
				$t_th .= "<th>".$j."</th>\n";
				if($j == $row['time']) {
					$img_count = '';
					$img_c = $oPluginAdmin->plugin->getOption('alz_img_c');
					if($row['count'] > 10 && $img_c) $img_count = "<img src=\"".$CONF['PluginURL']."analyze/blue.jpg\" alt=\"point\" width=\"10\" height=\"".(round($row['count']/10, 0)*$img_c)."\"><br />\n";
					$t_t .= "<td style=\"text-align: right; vertical-align: bottom;\">".$img_count."<a href=\"".$sslink."b&amp;sort=ASC&amp;fie=aldate&amp;query=".$t_month."&#32;".$row['time']."\" title=\""._NP_ANALYZE_TIME2."\">".$row['count']."</a></td>\n";
					$t++;
					break;
				}else {
					$t_t .= "<td style=\"text-align: right; vertical-align: bottom;\">0</td>\n";
				}
				$t++;
			}
		}
		mysql_free_result($result);
		if($t < 24) {
			for($j=$t; $j<24; $j++){
				if($j < 10) $j = '0'.$j;
				$t_th .= "<th>".$j."</th>\n";
				$t_t .= "<td></td>\n";
			}
		}
		echo '<h3><a href="'.$sslink.'b"><img src="documentation/icon-help.gif" alt="link" height="15" width="15" title="'._NP_ANALYZE_DAILY._NP_ANALYZE_ACCESSLOG.'"></a>'._NP_ANALYZE_TIME1.' (*'._NP_ANALYZE_HIT.'+'._NP_ANALYZE_NG0.')</h3>
<table>
<thead>
<tr class="jsbutton">
'.$t_th.'
</tr>
</thead>
<tbody>
<tr>
'.$t_t.'
</tr>
</tbody>
</table>';
		return;
	}

	function MonthlyResult($hit_range = '' ,$past = '', $this_month = '', $top_limit = '', $sslink = '', $switch = '', $sform = '', $temp_judge = '') {
		if($temp_judge == 'yes') $mon_l = '<a href="'.$sslink.'b&amp;sort=DESC&amp;fie=aldate&amp;past='.$past.'"><img src="documentation/icon-help.gif" alt="link" title="'._NP_ANALYZE_MONTHLY._NP_ANALYZE_ACCESSLOG.'('.$past.')" height="15" width="15"></a>';
		$title = '<form method="get" action="'.$sslink.'">
<div><h3>'.$this_month.' : '.$mon_l._NP_ANALYZE_LAST.' (*'.$switch.')'
.NaviPast($sslink).
'</h3></div>
</form>';
		echo $sform.
AnalyzeMainGroup($hit_range ,$past, $this_month, $top_limit, 10, $sslink, $temp_judge, $title)
.$title.
AnalyzeMainGroup($hit_range ,$past, $this_month, $top_limit, 7, $sslink, $temp_judge)
.$sform;
		PlugEnd();
	}

	function PostQuery() {
		$search = ($_GET['query']) ? htmlspecialchars($_GET['query']) : htmlspecialchars(postVar('serch_query'));
		$post = urldecode($search);
		return $post;
	}

	function qTable($past = '') {
		$qtable = ($past) ? sql_table('plugin_analyze_temp') : sql_table('plugin_analyze_log');
		return $qtable;
	}

	function SearchQuery($qtable = '') {
		if(!(postVar('search') || $_GET['query'])) return;
		$order = (_CHARSET == 'EUC-JP') ? 'EUC-JP, UTF-8,' : 'UTF-8, EUC-JP,';
		$post = mb_convert_encoding(PostQuery(), _CHARSET, $order.' JIS, SJIS, ASCII');
		$result3 = mysql_query('SELECT * FROM '.$qtable);
		while($row = mysql_fetch_field($result3)) {
			$q3 .= $row->name.' LIKE "%'.$post.'%" or ';
		}
		$qu = ' WHERE '.$q3.' not null';
		return $qu;
	}

	function ExportFile() {
		$dl = htmlspecialchars($_GET['dl']);
		header("Content-Type: application/octet-stream");
		switch($_GET['select']) {
		case 'csv':
			$dir = htmlspecialchars($_GET['dir']);
			header("Content-Disposition: attachment; filename=".$dl."log".date(ymd).".csv");
			$lines = file($dir.$dl.".csv");
			foreach ($lines as $line) echo $line;
			exit;
		default:
			header("Content-Disposition: attachment; filename=".$dl.date(ymd).".csv");
			$rs = mysql_query("SELECT * FROM ".$dl);
			while($row = mysql_fetch_array($rs)) {
				$fields = mysql_num_fields($rs);
				for($j=0; $j<$fields; $j++) {
					echo '"'.addslashes($row[$j]).'"';
					if($j<$fields-1) echo ',';
				}
				echo "\n";
			}
			exit;
		}
	}

	function DeleteData($pastdir = '', $past = '', $toplink = '', $select = '') {
		global $DIR_MEDIA;
		$del_query = ($_GET['delete']) ? htmlspecialchars($_GET['delete']) : htmlspecialchars(postVar('delete'));
		$del_data = " FROM ".sql_table('plugin_analyze_temp')." WHERE ";
		if($del_query == 'r?') {
			$del_data .= "alid = 'r?'";
		}else {
			$del_data .= (postVar('edit') || postVar('del2')) ? "alip LIKE '%".$del_query."%'" : "alip = '".$del_query."'";
		}
		if(postVar('delete') && !postVar('edit')) {
			mysql_query("DELETE".$del_data);
			@unlink($DIR_MEDIA.$pastdir."/".$past.".csv");
			$rs = mysql_query("SELECT * FROM ".sql_table('plugin_analyze_temp'));
			$fields = mysql_num_fields($rs);
			while($row = mysql_fetch_array($rs)) {
				for($j=0; $j<$fields; $j++) {
					$data .= '"'.addslashes($row[$j]).'"';
					if($j<$fields-1) $data .= ',';
				}
				$data .= "\n";
			}
			$a = substr($data, 0, 5);
			if($a == 'Array') $data = strstr($data, 'Array');
			$fp = @fopen($DIR_MEDIA.$pastdir."/".$past.".csv", "a");
			@fputs($fp, $data);
			@fclose($fp);
			mysql_query("OPTIMIZE TABLE ".sql_table('plugin_analyze_temp'));
			$del_q = ($del_query == 'r?') ? _NP_ANALYZE_DELETE1 : _NP_ANALYZE_DELETE;
			echo '<p>'.$del_query.'</p><p>'.$del_q .htmlspecialchars(postVar('del_count')).'</p>';
		}else {
			$del_count = number_format(mysql_num_rows(mysql_query("SELECT *".$del_data)));
			$del_select = (postVar('edit')) ? '<input name="del2" type="submit" value="'._NP_ANALYZE_DELETE3.'" tabindex="10122" />' : '<input name="del" type="submit" value="'._NP_ANALYZE_DELETE3.'" tabindex="10122" />';
			$del_p = ($del_query == 'r?') ? _NP_ANALYZE_DELETE4 : _NP_ANALYZE_DELETE2;
			echo '<form method="post" action="'.$toplink.'">
<input name="delete" type="text" size="60" value="'.$del_query.'" />
<input name="select" type="hidden" value="'.$select.'" />
<p>'.$del_p.$del_count.'</p>
<input name="past" type="hidden" value="'.$past.'" />
<input name="del_count" type="hidden" value="'.$del_count.'" />'.$del_select.'
<input name="edit" type="submit" value="'._NP_ANALYZE_EDIT.'" tabindex="10123" />
<p><a href="javascript:history.go(-1);">&#62; '._NP_ANALYZE_CANCEL.' &#60;</a></p>
</form>';
		}
		PlugEnd();
	}

	function AccessLog($sform = '', $toplink = '', $past = '', $sslink = '', $select = '', $p0 = '', $p1 = '', $p2 = '', $p00 = '', $this_month = '', $temp_judge = '', $switch = '', $pastdir = '', $sort = '', $log_m = '', $ch_log = '') {
		global $oPluginAdmin, $DIR_MEDIA, $CONF;
		$past2 = ($past) ? $past : date('Y-m-d');
		if(postVar('edit') || postVar('delete')) $past2 = $ch_log;
		$qtable = qTable($past);
		$post = PostQuery();
		$qu = SearchQuery($qtable);
		$i = 1;
		$ltitle = (postVar('edit') || postVar('delete') || $past) ? '<a href="'.$sslink.'b&amp;past='.$this_month.'&amp;jd='.$past.'"><img src="documentation/icon-help.gif" alt="link" height="15" width="15"></a>'._NP_ANALYZE_MONTHLY : _NP_ANALYZE_DAILY;
		if(!(postVar('edit') || postVar('delete') || $past) && $temp_judge == 'yes') $ltitle1 = '<a href="'.$sslink.'b&amp;sort=DESC&amp;fie=aldate&amp;past='.$this_month.'&amp;jd='.$past.'"><img src="documentation/icon-help.gif" alt="link" height="15" width="15">'._NP_ANALYZE_MONTHLY._NP_ANALYZE_ACCESSLOG.'</a>';
		if($past) $sw = $switch;
		if(!$past) $sq = ' [<a href="'.$sslink.'g&amp;sort=DESC&amp;past='.$past.'&amp;group=1"><img src="documentation/icon-help.gif" alt="link" height="15" width="15">'._NP_ANALYZE_GROUP.'</a>]';
		$search_count = number_format(mysql_num_rows(mysql_query("SELECT * FROM ".$qtable.$qu)));
		if(!($_GET['delete'] || postVar('delete'))) $s_count = _NP_ANALYZE_COUNT.$search_count;
		echo $sform.'<h3>'.$ltitle._NP_ANALYZE_ACCESSLOG.' ('.$past2.')'.$s_count.$sq.'</h3>'.$ltitle1.$sw;
		if(postVar('edit') || postVar('delete') || $_GET['delete']) DeleteData($pastdir, $past, $toplink, $select);
		$alo = ($past) ? _NP_ANALYZE_DATE1 : 'ID';
		echo '<table>
<thead>
<tr class="jsbutton">
<th>No.</th>
<th>'.$alo.'</th>
<th colspan="2"><a href="'.$sslink.'b&amp;sort='.$sort.'&amp;fie=aldate&amp;page='.htmlspecialchars($_GET['page']).'&amp;past='.$past.'&amp;query='.$post.'" title="'._NP_ANALYZE_SORT.'">'._NP_ANALYZE_TIME.'</a></th>
<th><a href="'.$sslink.'b&amp;sort='.$sort.'&amp;past='.$past.'&amp;fie=alid&amp;page='.htmlspecialchars($_GET['page']).'&amp;query='.$post.'" title="'._NP_ANALYZE_SORT.'">'._NP_ANALYZE_ACCESS_PAGE.'</a></th>
<th><a href="'.$sslink.'b&amp;sort='.$sort.'&amp;past='.$past.'&amp;fie=alip&amp;page='.htmlspecialchars($_GET['page']).'&amp;query='.$post.'" title="'._NP_ANALYZE_SORT.'">'._NP_ANALYZE_ACCESS_HOST.'</a></th>
<th><a href="'.$sslink.'b&amp;sort='.$sort.'&amp;past='.$past.'&amp;fie=alreferer&amp;page='.htmlspecialchars($_GET['page']).'&amp;query='.$post.'" title="'._NP_ANALYZE_SORT.'">'._NP_ANALYZE_REFERER_PAGE.'</a></th>
</tr>
</thead>
<tbody>';
		$qu .= ($_GET['fie'] && !$_GET['group']) ? ' ORDER BY '.addslashes($_GET['fie']).' '.addslashes($_GET['sort']) : ' ORDER BY allog DESC';
		$i0 = (is_numeric($_GET['page'])) ? ($_GET['page']-1)*50 : 0;
		$result = sql_query("SELECT * FROM ".$qtable.$qu." LIMIT ".$p00.",50");
		while($row = mysql_fetch_assoc($result)) {
			$ctime0 = $ctime1;
			$ctime1 = date("U", strtotime($row['aldate']));
			$ctime2 = $ctime1-$ctime0;
			if($ctime2<0) $ctime2 = -$ctime2;
			$atime = substr($row['aldate'], 11);
			$allog = ($past) ? substr($row['aldate'], 8 ,2) : $row['allog'];
			$apid0 = explode('?', $row['alid'], 3);
			$apid = IdChange($apid0[0], $apid0[1], $apid0[2], 0, 0, 0, 1);
			if($past && $row['alid'] == 'r?' && $log_m) $apid .= '<a href="'.$sslink.'b&amp;past='.$past.'&amp;delete=r?"><img src="'.$CONF['PluginURL'].'analyze/delete.png" alt="del" height="11" width="11" title="['._NP_ANALYZE_DELETE3.']: XML-RSS"></a>';
			$apid2 = explode('?', $row['alreferer'], 3);
			$alip = htmlspecialchars(substr($row['alip'], -25));
			$alip1 = $alip.' <a href="'.$sslink.'b&amp;sort=ASC&amp;fie=aldate&amp;page=1&amp;past='.$past.'&amp;query='.$row['alip'].'&amp;jd='.$past.'"><img src="documentation/icon-help.gif" alt="link" height="15" width="15" title="['._NP_ANALYZE_EXTRACT.']: '.$row['alip'].'"></a>';
			if($past && $log_m) $alip1 .= '<a href="'.$sslink.'b&amp;past='.$past.'&amp;delete='.$row['alip'].'"><img src="'.$CONF['PluginURL'].'analyze/delete.png" alt="del" height="11" width="11" title="['._NP_ANALYZE_DELETE3.']: '.$row['alip'].'"></a>';
			$numb = strlen($apid2[0]);
			$apid1 = ($numb > 4) ? IdChange($row['alreferer']) : IdChange($apid2[0], $apid2[1], $apid2[2], 0, 0 ,$row['alword'], 1);
			echo '<tr onmouseover="focusRow(this);" onmouseout="blurRow(this);">
<td>'.($i+$i0).'</td>
<td>'.$allog.'</td>
<td>'.$atime.'</td>
<td style="font-size: 11px; color: #777777;">';
			switch(TRUE) {
			case ($ctime0 && $ctime2<3600):
				printf("%02d:%02d", floor($ctime2/60), $ctime2%60);
				break;
			case ($ctime0 && $ctime2<86400):
				echo '*1h-';
				break;
			case ($ctime0 && $ctime2>=86400):
				echo '*day-';
				break;
			}
			echo '</td>
<td>'.$apid.'</td>
<td>'.$alip1.'</td>
<td>'.$apid1.'</td>
</tr>';
			$i++;
		}
		mysql_free_result($result);
		echo '</tbody>
</table>'.$sform;
		PlugEnd();
	}

	function TotalCountChange($sform = '') {
		if(postVar('ahvisit')) {
			$ahvisit0 = intval(postVar('ahvisit'));
			$ahhit0 = intval(postVar('ahhit'));
			$ahrobo0 = intval(postVar('ahrobot'));
			sql_query("UPDATE ".sql_table('plugin_analyze_hit')." SET ahvisit = '".$ahvisit0."', ahhit = '".$ahhit0."', ahrobot = '".$ahrobo0."' WHERE ahdate = '2000-01-01' LIMIT 1");
		}
		echo $sform.'<h3>'._NP_ANALYZE_COUNT_CHANGE.'</h3>
<table>
<thead>
<tr class="jsbutton">
<th>'._NP_ANALYZE_TOTAL._NP_ANALYZE_VISIT.'</th>
<th>'._NP_ANALYZE_TOTAL._NP_ANALYZE_HIT.'</th>
<th>'._NP_ANALYZE_TOTAL._NP_ANALYZE_NG0.'</th>
</tr>
</thead>
<tbody>';
		$result = sql_query("SELECT * FROM ".sql_table('plugin_analyze_hit')." WHERE ahdate = '2000-01-01' LIMIT 1");
		while($row = mysql_fetch_assoc($result)) {
			$ahvisit = $row['ahvisit'];
			$ahhit = $row['ahhit'];
			$ahrobot = $row['ahrobot'];
			echo '<form method="post" action="'.$toplink.'">
<tr>
<td><input name="ahvisit" value="'.$ahvisit.'" size="30%" /></td>
<td><input name="ahhit" value="'.$ahhit.'" size="30%" /></td>
<td><input name="ahrobot" value="'.$ahrobot.'" size="30%" /></td>
</tr>';
		}
		echo '</tbody>
</table><input name="edit" type="submit" value="'._NP_ANALYZE_COUNT_CHANGE.'" tabindex="10122" />
</form><br /><br />';
		PlugEnd();
	}

	function ExclusionHost($sslink = '', $toplink = '', $sform = '') {
		global $oPluginAdmin;
		if($_GET['robo']) {
			if($_GET['ok']) {
				sql_query('TRUNCATE TABLE '.sql_table('plugin_analyze_ng'));
				$oPluginAdmin->plugin->InsertRobot();
				echo '<p>'._NP_ANALYZE_A._NP_ANALYZE_NG._NP_ANALYZE_INIT.'</p>';
			}else {
				echo '<p>'._NP_ANALYZE_NG._NP_ANALYZE_INIT0.'</p>
<p><a href="'.$sslink.'ng&amp;robo=1&amp;ok=1">&#62; '._NP_ANALYZE_YES.' &#60;</a></p>
<p><a href="javascript:history.go(-1);">&#62; '._NP_ANALYZE_CANCEL.' &#60;</a></p>';
				PlugEnd();
			}
		}
		$robo1 = '<table>
<thead>
<tr class="jsbutton">';
		if(!$_GET['edit']) $robo1 .= '<th>'._NP_ANALYZE_EDIT.'</th>';
		$robo1 .= '<th>'._NP_ANALYZE_NG1.'</th>
<th>'._NP_ANALYZE_NG2.'</th>
<th>'._NP_ANALYZE_NG3.'</th>
</tr>
</thead>
<tbody>';
		echo $sform.'<h3>'._NP_ANALYZE_NG.'</h3>(*<a href="'.$sslink.'ng&amp;robo=1">'._NP_ANALYZE_NG4.'</a>)'.$robo1;
		if(postVar('ip')) {
			$anid = addslashes(postVar('anid'));
			$antitle = addslashes(postVar('antitle'));
			$anip = addslashes(postVar('anip'));
			$query = "INSERT INTO ".sql_table('plugin_analyze_ng')." (anid, antitle, anip) VALUES ('$anid', '$antitle', '$anip')";
			sql_query($query);
			echo '<p>'._NP_ANALYZE_A._NP_ANALYZE_ADD0.$antitle.'</p>';
			echo '<fieldset>
<legend> '._NP_ANALYZE_SQLQUERY.' </legend>
'.$query.'
</fieldset>';
		}
		if($_GET['edit']) {
			$an = addslashes($_GET['edit']);
			$result = sql_query("SELECT * FROM ".sql_table('plugin_analyze_ng')." WHERE an = ".$an);
			while($row = mysql_fetch_assoc($result)) {
				if($row['anid'] == 1) $anid0a = ' selected="selected"';
				if($row['anid'] == 2) $anid0b = ' selected="selected"';
				echo '<form method="post" action="'.$toplink.'">
<tr onmouseover="focusRow(this);" onmouseout="blurRow(this);">
<td>
<select name="anid">
<option value="1"'.$anid0a.'>'._NP_ANALYZE_NG1A.'</option>
<option value="2"'.$anid0b.'>'._NP_ANALYZE_NG1B.'</option>
</select>
</td>
<td><input name="antitle" value="'.$row['antitle'].'" size="30%" /></td>
<td><input name="anip" value="'.$row['anip'].'" size="30%" /></td>
</tr>';
			}
			echo '</tbody>
</table>
<input name="ok" type="hidden" value="'.$an.'" />
<p><input name="ed" type="submit" value="'._NP_ANALYZE_EDIT.'" />
<input name="del" type="submit" value="'._NP_ANALYZE_DELETE3.'" /></p>
<p><a href="javascript:history.go(-1);">&#62; '._NP_ANALYZE_CANCEL.' &#60;</a></p></form>';
			mysql_free_result($result);
		}else {
			if(postVar('ok')) {
				$an = addslashes(postVar('ok'));
				$anid = addslashes(postVar('anid'));
				$antitle = addslashes(postVar('antitle'));
				$anip = addslashes(postVar('anip'));
				$gname = sql_table('plugin_analyze_ng');
				$query = (postVar('del')) ? "DELETE FROM ".$gname : "UPDATE ".$gname." SET anid = '".$anid."', antitle = '".$antitle."',anip = '".$anip."'";
				$query .= " WHERE an = '".$an."' LIMIT 1";
				sql_query($query);
				if(postVar('del')) echo '<p>'._NP_ANALYZE_A._NP_ANALYZE_DELETE0.$antitle.'</p>';
				if(postVar('ed')) echo '<p>'._NP_ANALYZE_A._NP_ANALYZE_EDIT0.$antitle.'</p>';
				echo '<fieldset>
<legend> '._NP_ANALYZE_SQLQUERY.' </legend>
'.$query.'
</fieldset>';
			}
			echo '<form method="post" action="'.$toplink.'">
<tr onmouseover="focusRow(this);" onmouseout="blurRow(this);">
<td><input name="ip" type="submit" value="'._NP_ANALYZE_ADD.'" /></td>
<td>
<select name="anid">
<option value="1" selected="selected">'._NP_ANALYZE_NG1A.'</option>
<option value="2">'._NP_ANALYZE_NG1B.'</option>
</select>
</td>
<td><input name="antitle" value="" size="30%" /></td>
<td><input name="anip" value="" size="30%" /></td>
</tr>
</tbody>
</table></form>
'.$robo1;
			$result = sql_query("SELECT * FROM ".sql_table('plugin_analyze_ng')." ORDER BY antitle ASC");
			while($row = mysql_fetch_assoc($result)) {
				$anid1 = ($row['anid'] == 1) ? _NP_ANALYZE_NG1A : _NP_ANALYZE_NG1B ;
				echo '<tr onmouseover="focusRow(this);" onmouseout="blurRow(this);">
<td><a href="'.$sslink.'ng&amp;edit='.$row['an'].'">'._NP_ANALYZE_EDIT.'</a></td>
<td>'.$anid1.'</td>
<td>'.$row['antitle'].'</td>
<td>'.$row['anip'].'</td>
</tr>';
			}
			mysql_free_result($result);
			echo '</tbody>
</table>';
		}
		echo $sform;
		PlugEnd();
	}

	function EditDatabaseTable($sform = '', $get_id = '', $sslink = '', $opt_s = '', $toplink = '', $adate = '') {
		global $oPluginAdmin, $MYSQL_PREFIX, $manager, $CONF;
		$prefix_length = strlen($MYSQL_PREFIX);
		$d_count = $oPluginAdmin->plugin->getOption('alz_d_count');
		$y1 = ($_GET['y1']) ? htmlspecialchars($_GET['y1']) : htmlspecialchars(postVar('y1'));
		$m1 = ($_GET['m1']) ? htmlspecialchars($_GET['m1']) : htmlspecialchars(postVar('m1'));
		$mtable = $oPluginAdmin->plugin->DatabaseList();
		$mtable2 = $oPluginAdmin->plugin->DatabaseLists();
		if(!$adate) echo $sform.'<h3>'._NP_ANALYZE_DB0.'</h3>';
		switch(TRUE) {
		case (postVar('db')):
			echo '<p>'.$y1.'-'.$m1._NP_ANALYZE_DELETE6.'<br />
<a href="'.$sslink.'db&amp;del=1&amp;y1='.$y1.'&amp;m1='.$m1.'">&#62; '._NP_ANALYZE_YES.' &#60;</a></p>';
			if($d_count > 0) {
				echo '<p>'.$y1.'-'.$m1._NP_ANALYZE_DELETE9.$d_count.$opt_s.'<br />
<a href="'.$sslink.'db&amp;del2=1&amp;y1='.$y1.'&amp;m1='.$m1.'">&#62; '._NP_ANALYZE_YES.' &#60;</a></p>';
			}else {
				echo '<p>'.$y1.'-'.$m1._NP_ANALYZE_DELETEB._NP_ANALYZE_OP_16._NP_ANALYZE_DELETEC.$opt_s.'</p>';
			}
			echo '[*'._NP_ANALYZE_DELETE8.']<ul>';
			foreach($mtable2 as $mt=>$mt1) {
				echo '<li>'.$mt.'</li>';
			}
			echo '</ul>
<p><a href="javascript:history.go(-1);">&#62; '._NP_ANALYZE_CANCEL.' &#60;</a></p>';
			PlugEnd();
			return;
		case ($_GET['emp']):
			$emp = htmlspecialchars($_GET['emp']);
			$emp1 = substr($emp, ($prefix_length+8));
			if($_GET['y']) {
				sql_query("TRUNCATE TABLE ".$emp);
				if($emp1 == 'plugin_analyze_hit') sql_query("INSERT INTO ".sql_table('plugin_analyze_hit')." VALUES ('2000-01-01', '0', '0', '0', '0', '0', '0', '0', '0')");
				echo $emp1.' '._NP_ANALYZE_EMP1;
			}else {
				echo '<p>'._NP_ANALYZE_EMP.'</p>
<p>'.$emp1.'</p>
<p><a href="'.$sslink.'db&amp;emp='.$emp.'&amp;y=1">&#62; '._NP_ANALYZE_YES.' &#60;</a></p>
<p><a href="javascript:history.go(-1);">&#62; '._NP_ANALYZE_CANCEL.' &#60;</a></p>';
				PlugEnd();
				return;
			}
			break;
		case ($_GET['del2']):
			$oPluginAdmin->plugin->DatabaseDelCount($y1, $m1, $d_count);
			echo $y1.'-'.$m1._NP_ANALYZE_DELETEA.$d_count;
			break;
		case ($_GET['del']):
			$pe_d = date("Y-m-d", strtotime("+1 month", strtotime($y1.'-'.$m1.'-01')));
			foreach($mtable2 as $mt=>$mt1) {
				sql_query("DELETE FROM ".sql_table($mt)." WHERE ".$mt1." < '".$pe_d."'");
				sql_query("OPTIMIZE TABLE ".sql_table($mt));
			}
			echo $y1.'-'.$m1._NP_ANALYZE_DELETE7;
			break;
		default:
			if(!$adate) echo '<form method="post" action="'.$toplink.'">'._NP_ANALYZE_DELETE5.Period($y1, $m1);
		}
		echo '<table>
<thead>
<tr class="jsbutton">
<th>'._NP_ANALYZE_DB1.'</th>
<th>'._NP_ANALYZE_DB4.'</th>
<th>'._NP_ANALYZE_DB2.'</th>
<th colspan="2">'._NP_ANALYZE_DB3.'</th>
<th>'._NP_ANALYZE_CSV3.'</th>
</tr>
</thead>
<tbody>';
		$db_a = $manager->pluginInstalled('NP_Database');
		$result = mysql_query('SHOW TABLE STATUS');
		while($row = mysql_fetch_assoc($result)) {
			$prefix = ($MYSQL_PREFIX) ? $MYSQL_PREFIX.'nucleus' : 'nucleus' ;
			if(preg_match("/^$prefix/", $row['Name'])) {
				$pr = substr($row['Name'], ($prefix_length+8));
				foreach($mtable as $mt=>$mt1){
					if($pr == $mt) {
						$cc = '';
						if(date("U", strtotime($adate." 23:59:59"))>date("U", strtotime($row['Update_time'])) && $adate) $cc = ' style="color: red;"';
						$emp1 = ($row['Rows']) ? '<a href="'.$sslink.'db&amp;emp='.$row['Name'].'" title="'._NP_ANALYZE_EMP0.'">Emp</a>' : 'Emp';
						$pr1 = ($db_a && $row['Rows']) ? '<a href="'.$CONF['PluginURL'].'database/index.php?name='.sql_table($pr).'&amp;select=b" title="'._NP_ANALYZE_TABLE.'">'.$pr.'</a>' : $pr;
						$si = number_format(($row['Data_length']+$row['Index_length'])/1000).' KB';
						$size = ($row['Rows']) ? '<a href="'.$sslink.'db&amp;dl='.sql_table($pr).'" title="'._NP_ANALYZE_EXPORT0.'">'.$si.'</a>' : $si;
						echo '<tr onmouseover="focusRow(this);" onmouseout="blurRow(this);"><td>'.$pr1.'</td>
<td>'.$mt1.'</td>
<td style="text-align: right;">'.$size.'</td>
<td style="text-align: right;">'.number_format($row['Rows']).'</td>
<td>'.$emp1.'</td>
<td'.$cc.'>'.$row['Update_time'].'</td>
</tr>';
					}
				}
			}
		}
		echo '</tbody>
</table>';
		if(!$adate) echo $sform;
		PlugEnd();
	}

	function EditCsvFile($get_id = '', $sform = '', $pastdir = '', $opt_s = '', $sslink = '', $temp_judge = '') {
		global $DIR_MEDIA;
		$dir1 = $DIR_MEDIA.$pastdir."/";
		echo $sform.'<h3>'._NP_ANALYZE_CSV0.' (*/media/'.$pastdir.'/)'.$opt_s.'</h3>';
		if($_GET['del'] && !$_GET['ok']) {
			echo '<p>'._NP_ANALYZE_CSV4.'<br />'.htmlspecialchars($_GET['del']).'</p>
<p><a href="'.$sslink.'csv&amp;del='.htmlspecialchars($_GET['del']).'&amp;ok=1">&#62; '._NP_ANALYZE_YES.' &#60;</a></p>
<p><a href="javascript:history.go(-1);">&#62; '._NP_ANALYZE_CANCEL.' &#60;</a></p>';
		}else {
			if($_GET['ok']) {
				$g_del = htmlspecialchars($_GET['del']);
				@unlink($dir1.$g_del);
				echo '<p>'._NP_ANALYZE_A._NP_ANALYZE_CSV5.'<br />
'.$g_del.'</p>';
			}
			echo '<table>
<thead>
<tr class="jsbutton">
<th>'._NP_ANALYZE_DELETE3.'</th>
<th>'._NP_ANALYZE_EXPORT.'</th>
<th>'._NP_ANALYZE_CSV1.'</th>
<th>'._NP_ANALYZE_CSV2.'</th>
<th>'._NP_ANALYZE_CSV3.'</th>
</tr>
</thead>
<tbody>';
			$dir = opendir($dir1);
			while($dir_s = readdir($dir)) {
				if(strlen($dir_s) == 11) {
					$dir_c = substr($dir_s, 0, 7);
					$ddd = ($temp_judge == 'yes' && $_GET['temp'] != 2 || $_GET['temp'] == 1) ? '<a href="'.$sslink.'b&amp;past='.$dir_c.'" title="'._NP_ANALYZE_MONTHLY._NP_ANALYZE_ACCESSLOG.'('.$dir_c.')">'.$dir_s.'</a>' : $dir_s;
					echo '<tr onmouseover="focusRow(this);" onmouseout="blurRow(this);">
<td><a href="'.$sslink.'csv&amp;del='.$dir_s.'">'._NP_ANALYZE_DELETE3.'</a></td>
<td><a href="'.$sslink.'csv&amp;dl='.$dir_c.'&amp;dir='.$dir1.'" title="'._NP_ANALYZE_EXPORT1.'">'._NP_ANALYZE_EXPORT.'</a></td>
<td>'.$ddd.'</td>
<td style="text-align: right;">'.number_format(filesize($DIR_MEDIA.$pastdir."/".$dir_s)/1000).' KB</td>
<td>'.date("Y-m-d H:i:s", filemtime($DIR_MEDIA.$pastdir."/".$dir_s)).'</td>
</tr>';
				}
			}
			closedir($dir);
			echo '</tbody>
</table>'.$sform;
		}
		PlugEnd();
	}

	function AnalyzeAll($this_month = '', $top_limit = '') {
		echo '<div style="float: left;	width: 37%;">
'.AnalyzePage($this_month, $top_limit, 0, 0, 'plugin_analyze_page').'
</div>
<div style="float: left;	width: 62%;	padding-left: 1%;">
'.AnalyzePquery($this_month, $top_limit, 0, 0, 'plugin_analyze_page_query').'
</div>
<div style="clear: left;"> </div>
'.AnalyzePattern($this_month, $top_limit, 0, 0, 'plugin_analyze_page_pattern').'
<div style="float: left;	width: 50%;">
'.AnalyzeReferer($this_month, $top_limit, 0, 0, 'plugin_analyze_referer').'
</div>
<div style="float: left;	width: 49%;	padding-left: 1%;">
'.AnalyzeQuery($this_month, $top_limit, 0, 0, 'plugin_analyze_query').'
</div>
<div style="clear: left;"> </div>
<div style="float: left;	width: 33%;">
'.AnalyzeHost($this_month, $top_limit, 0, 0, 'plugin_analyze_host').'
</div>
<div style="float: left;	width: 33%;	padding-left: 1%;">
'.AnalyzeEngine($this_month, $top_limit, 0, 0, 'plugin_analyze_engine').'
</div>
<div style="float: left;	width: 32%;	padding-left: 1%;">
'.AnalyzeRobot($this_month, $top_limit, 0, 0, 'plugin_analyze_robot').'
</div>
<div style="clear: left;"> </div>';
		return;
	}

	function CheckDateChange($t_table = '', $toplink = '', $sform = '', $get_id = '', $sslink = '', $opt_s = '', $toplink = '') {
		global $oPluginAdmin;
		$time_a = quickQuery("SELECT aldate as result FROM ".$t_table." ORDER BY allog ASC LIMIT 1");
		$t1y = date("Y", strtotime($time_a));
		$t1m = date("m", strtotime($time_a));
		$mcsv = $t1y.'-'.$t1m;
		$adate = date("Y-m-d", strtotime($time_a));
		$t0m = date("m");
		switch($_GET['dc']) {
			case 'no':
				sql_query("DROP TABLE ".$t_table);
				break;
			case 'yes':
				$g1 = getmicrotime();
				echo '<p>'.date("H:i:s")._NP_ANALYZE_DAILY_CHANGE2.'</p>';
				$oPluginAdmin->plugin->ChangeDate($t_table, $t1y, $t1m, $mcsv, $adate, $t0m);
				$g2 = getmicrotime();
				echo '<p>'.date("H:i:s")._NP_ANALYZE_DAILY_CHANGE3.'</p>
<p>Processing time ['.round($g2-$g1, 2).' sec]</p>'
.$sform;
				EditDatabaseTable($sform, $get_id, $sslink, $opt_s, $toplink, $adate);
				return;
			default:
				echo '<p>'.$adate._NP_ANALYZE_DAILY_CHANGE1.'</p>
<p><a href="'.$toplink.'?dc=yes">'._NP_ANALYZE_YES.'</a></p>
<p><a href="'.$toplink.'?dc=no">'._NP_ANALYZE_CANCEL.'</a></p>';
				EditDatabaseTable($sform, $get_id, $sslink, $opt_s, $toplink, $adate);
				return;
		}
	}

	function GetGroupNavi($sslink = '', $past = '') {
		global $oPluginAdmin, $CONF;
		$gname = htmlspecialchars($_GET['group']);
		$mtable1 = $oPluginAdmin->plugin->DatabaseListd();
		$mtable2 = $oPluginAdmin->plugin->DatabaseList();
		foreach($mtable1 as $m_a1=>$m_a2) {
			foreach($mtable2 as $m_b1=>$m_b2) {
				if($m_a1 == $m_b1) $sr .= ($gname == $m_a1) ? ' <strong style="padding: 0px 2px;">'.$m_b2.'</strong>': ' <span style="border: 1px solid #cccccc; padding: 0px 2px; background: url('.$CONF['AdminURL'].'styles/quickb.jpg) repeat-y;"><a href="'.$sslink.'g&amp;sort=ASC&amp;fie='.$m_a2.'&amp;group='.$m_a1.'&amp;past='.$past.'" style="font-weight: normal;">'.$m_b2.'</a></span>';
			}
		}
		return '<span style="border: 1px solid #cccccc; padding: 0px 2px; background: url('.$CONF['AdminURL'].'styles/quickb.jpg) repeat-y;"><a href="'.$sslink.'g&amp;group=all&amp;past='.$past.'" style="font-weight: normal;" title="'._NP_ANALYZE_ALL2.'">'._NP_ANALYZE_ALL.'</a></span>'.$sr;
	}

	function NaviPast($sslink = '') {
		$res0 = sql_query("SELECT DISTINCT LEFT(ahdate, 7) as ah FROM ".sql_table('plugin_analyze_hit')." WHERE ahdate != '2000-01-01' ORDER BY ahdate DESC");
		while($row = mysql_fetch_assoc($res0)) {
			$check = ($row['ah'] == $_GET['past']) ? ' selected="selected"' : '';
			$q1 .= '<option value="'.$row['ah'].'"'.$check.'>'.$row['ah'].'</option>';
		}
		$q = '<select name="past" onchange="return form.submit()">'
.$q1.
'</select> ';
		return '<input name="select" type="hidden" value="'.htmlspecialchars($_GET['select']).'" />
<input name="sort" type="hidden" value="'.htmlspecialchars($_GET['sort']).'" />
<input name="fie" type="hidden" value="'.htmlspecialchars($_GET['fie']).'" />
<input name="group" type="hidden" value="'.htmlspecialchars($_GET['group']).'" />
<input name="query" type="hidden" value="'.htmlspecialchars($_GET['query']).'" />
<input name="jd" type="hidden" value="'.htmlspecialchars($_GET['jd']).'" />
'.$q.'
<input name="edit" type="submit" value="'._NP_ANALYZE_SEARCH.'" tabindex="10101" />';
	}

	function NaviSwitch($sslink = '', $past = '', $pastdir = '', $select = '', $fie = '') {
		global $DIR_MEDIA;
		$prev_month = date("Y-m", strtotime("-1 month", strtotime($past."-01")));
		$next_month = date("Y-m", strtotime("+1 month", strtotime($past."-01")));
		switch(TRUE) {
		case ($_GET['select'] == 'month'):
			$nvi1 = quickQuery("SELECT ahdate as result FROM ".sql_table('plugin_analyze_hit')." WHERE LEFT(ahdate, 7) = '".$prev_month."' LIMIT 1");
			$nvi2 = quickQuery("SELECT ahdate as result FROM ".sql_table('plugin_analyze_hit')." WHERE LEFT(ahdate, 7) = '".$next_month."' LIMIT 1");
			break;
		default:
			$nvi1 = file_exists($DIR_MEDIA.$pastdir."/".$prev_month.".csv");
			$nvi2 = file_exists($DIR_MEDIA.$pastdir."/".$next_month.".csv");
		}
		if($nvi1) $switch = '<a href="'.$sslink.$select.'&amp;sort=DESC&amp;'.$fie.'=aldate&amp;past='.$prev_month.'&amp;jd='.$past.'&amp;query='.htmlspecialchars($_GET['query']).'">'.$prev_month.'</a> &laquo; ';
		$switch .= $past;
		if($nvi2) $switch .= ' &raquo; <a href="'.$sslink.$select.'&amp;sort=DESC&amp;fie='.$fie.'&amp;past='.$next_month.'&amp;jd='.$past.'&amp;query='.htmlspecialchars($_GET['query']).'">'.$next_month.'</a>';
		return $switch;
	}

	function GetGroup($past = '', $p0 = '', $p1 = '', $p2 = '', $p00 = '', $sslink = '', $sform = '', $this_month = '', $sort = '', $top_limit = '') {
		$fie = htmlspecialchars($_GET['fie']);
		$sor2 = htmlspecialchars($_GET['sort']);
		$gname = htmlspecialchars($_GET['group']);
		$past = htmlspecialchars($_GET['past']);
		$query = htmlspecialchars($_GET['query']);
		$jd = htmlspecialchars($_GET['jd']);
		if($gname == 'page' && !$past) $past = date("Y-m");
		$numb = strlen($past);
		$group_d = ($_GET['group'] == 1) ? 1 : $gname;
		$d_start = quickQuery("SELECT ahdate as result FROM ".sql_table('plugin_analyze_hit')." WHERE ahdate != '2000-01-01' ORDER BY ahdate ASC LIMIT 1");
		switch(TRUE) {
		case ($numb == 7):
			$p_d = strtotime($past.'-01');
			$prev_d = date("Y-m", strtotime("-1 month", $p_d));
			$next_d = date("Y-m", strtotime("+1 month", $p_d));
			$gg = $past.' : ';
			$gg .= ($gname == 'page') ? _NP_ANALYZE_PAGE : _NP_ANALYZE_MONTHLY;
			if($p_d > strtotime($d_start)) $p = 1;
			if(strtotime("+1 month", $p_d) < date("U")) $n = 1;
			break;
		case ($numb == 10):
			$p_d = strtotime($past);
			$prev_d = date("Y-m-d", strtotime("-1 day", $p_d));
			$next_d = date("Y-m-d", strtotime("+1 day", $p_d));
			$gg = $past.' : '._NP_ANALYZE_DAILY;
			if(strtotime($p_d) > strtotime($d_start)) $p = 1;
			if(strtotime("+2 day", $p_d) < date("U")) $n = 1;
			break;
		default:
			$gg = _NP_ANALYZE_DAILY._NP_ANALYZE_GROUP1.' (*'.date("Y-m-d").')';
		}
		if($p) $prev_s = '<a href="'.$sslink.'g&amp;sort=ASC&amp;fie='.$fie.'&amp;past='.$prev_d.'&amp;group='.$group_d.'&amp;query='.$query.'&amp;jd='.$jd.'">'.$prev_d.'</a> &laquo; ';
		if($n) $next_s = ' &raquo; <a href="'.$sslink.'g&amp;sort=ASC&amp;fie='.$fie.'&amp;past='.$next_d.'&amp;group='.$group_d.'&amp;query='.$query.'&amp;jd='.$jd.'">'.$next_d.'</a>';
		if($numb) $gg .= _NP_ANALYZE_GROUP1.' (*'.$prev_s.$past.$next_s.')';
		if($p1) $sf .= ' <a href="'.$sslink.'g&amp;page='.$p1.'&amp;sort='.$sor2.'&amp;group='.$gname.'&amp;past='.$past.'&amp;query='.$query.'&amp;jd='.$jd.'" title="'._NP_ANALYZE_PEPREV.'">P.'.$p1.'</a>';
		$sf .= ' [P.'.$p0.'] ';
		$sf .= '<a href="'.$sslink.'g&amp;page='.$p2.'&amp;sort='.$sor2.'&amp;group='.$gname.'&amp;past='.$past.'&amp;query='.$query.'&amp;jd='.$jd.'" title="'._NP_ANALYZE_PENEXT.'">P.'.$p2.'</a>';
		$sform .= ($gname != 1) ? '<form method="get" action="'.$sslink.'">
<div>
<h3>'.$gg.NaviPast($sslink).'</h3>
</div>
</form>' : '<h3>'.$gg.'</h3>';
		echo $sform;
		if(!($gname == 'all' || $gname == 'page')) echo $sf;
		switch($gname) {
		case 'plugin_analyze_page':
			echo AnalyzePage($this_month, 50, $p00, $sort, $gname, $fie, $p0);
			break;
		case 'plugin_analyze_page_query':
			echo AnalyzePquery($this_month, 50, $p00, $sort, $gname, $fie, $p0);
			break;
		case 'plugin_analyze_page_pattern':
			echo AnalyzePattern($this_month, 50, $p00, $sort, $gname, $fie, $p0);
			break;
		case 'plugin_analyze_referer':
			echo AnalyzeReferer($this_month, 50, $p00, $sort, $gname, $fie, $p0);
			break;
		case 'plugin_analyze_query':
			echo AnalyzeQuery($this_month, 50, $p00, $sort, $gname, $fie, $p0);
			break;
		case 'plugin_analyze_host':
			echo AnalyzeHost($this_month, 50, $p00, $sort, $gname, $fie, $p0);
			break;
		case 'plugin_analyze_engine':
			echo AnalyzeEngine($this_month, 50, $p00, $sort, $gname, $fie, $p0);
			break;
		case 'plugin_analyze_robot':
			echo AnalyzeRobot($this_month, 50, $p00, $sort, $gname, $fie, $p0);
			break;
		case 'all':
			echo AnalyzeAll($this_month, $top_limit);
			break;
		case 'page':
			echo PageGroup($sform, $this_month, $top_limit);
			break;
		default:
			echo GroupAnalyze($past, $p00, $numb);
		}
		if(!($gname == 'all' || $gname == 'page')) echo $sf;
		echo '<p>'.$sform.'</p>';
		PlugEnd();
	}

	function CsvDir($pastdir = '', $sslink = '', $opt_s = '') {
		global $DIR_MEDIA;
		switch(TRUE) {
		case (!$_GET['re']):
			switch(TRUE) {
			case($_GET['o_name']) :
				echo '<p>'._NP_ANALYZE_CSV1D.'</p>'
._NP_ANALYZE_CSV1A._NP_ANALYZE_CSV8.': /media/'.htmlspecialchars($_GET['o_name']).'/<br />'
._NP_ANALYZE_CSV1B._NP_ANALYZE_CSV8.': /media/'.$pastdir.'/
<p><a href="'.$sslink.'dir&amp;re='.htmlspecialchars($_GET['o_name']).'">&#62; '._NP_ANALYZE_YES.' &#60;</a></p>
<p><a href="javascript:history.go(-1);">&#62; '._NP_ANALYZE_CANCEL.' &#60;</a></p>';
				break;
			default:
				echo _NP_ANALYZE_CSV1C.'<br />'._NP_ANALYZE_CSV1B._NP_ANALYZE_CSV8.': /media/'.$pastdir.'/ '.$opt_s.'
<table>
<thead>
<tr class="jsbutton">
<th>'._NP_ANALYZE_CSV9.'</th>
<th>'._NP_ANALYZE_CSV1A._NP_ANALYZE_CSV8.'</th>
<th>'._NP_ANALYZE_CSV3.'</th>
</tr>
</thead>
<tbody>';
				$dir = opendir($DIR_MEDIA);
				while($dir_s = readdir($dir)) {
					if(is_dir($DIR_MEDIA.$dir_s) && !($dir_s == '.' || $dir_s == '..')) {
						echo '<tr onmouseover="focusRow(this);" onmouseout="blurRow(this);">
<td><a href="'.$sslink.'dir&amp;o_name='.$dir_s.'">'._NP_ANALYZE_CSV9.'</a></td>
<td>'.$pri.'/media/'.$dir_s.'/</td>
<td>'.date("Y-m-d H:i:s", filemtime($DIR_MEDIA.$dir_s)).'</td>
</tr>';
					}
				}
				closedir($dir);
				echo '</tbody>
</table>';
			}
			PlugEnd();
			break;
		default:
			rename($DIR_MEDIA.htmlspecialchars($_GET['re'])."/", $DIR_MEDIA.$pastdir."/");
		}
		return;
	}

	function PlugEnd() {
		global $oPluginAdmin;
		$oPluginAdmin->end();
		exit;
	}

	function UpdateCheck($opt_s = '') {
		echo '<p>'._NP_ANALYZE_UPDATE.'</p>'.$opt_s;
		PlugEnd();
	}

	function AnalyzeMainGroup($hit_range = '', $past = '', $this_month = '', $top_limit = '', $date_j = '', $sslink = '', $temp_judge = '', $title = '') {
		global $CONF, $oPluginAdmin;
		$hit_range = explode('/', $hit_range);
		$hit01 = ($hit_range[0] == 1) ? 1 : '1~'.$hit_range[1];
		switch(TRUE) {
		case ($date_j == 7):
			$result = sql_query("SELECT LEFT(ahdate, 7) as ahdate, SUM(ahvisit) as ahvisit, SUM(ahhit) as ahhit, SUM(ahlevel1) as ahlevel1, SUM(ahlevel2) as ahlevel2, SUM(ahlevel3) as ahlevel3, SUM(ahlevel4) as ahlevel4, SUM(ahlevel5) as ahlevel5, SUM(ahrobot) as ahrobot FROM ".sql_table('plugin_analyze_hit')." WHERE LEFT(ahdate, 4) = '".substr($this_month, 0, 4)."' GROUP BY ahdate");
			break;
		case ($past):
			$result = sql_query("SELECT * FROM ".sql_table('plugin_analyze_hit')." WHERE LEFT(ahdate, 7) = '".$this_month."' ORDER BY ahdate ASC");
			break;
		default:
			$result = sql_query("SELECT * FROM ".sql_table('plugin_analyze_hit')." WHERE ahdate != '2000-01-01' GROUP BY ahdate ORDER BY ahdate DESC LIMIT ".$top_limit);
		}
		$result_a = '<table>
<thead>';
		$result_b = '<tr class="jsbutton">
<th>'._NP_ANALYZE_DATE.'</th>
<th>'._NP_ANALYZE_VISIT.'</th>
<th>'._NP_ANALYZE_HIT.'</th>
<th><a href="'.$CONF['PluginURL'].'analyze/index.php?select=ng" title="'._NP_ANALYZE_NG.'">'._NP_ANALYZE_NG0.'</a></th>
<th>'._NP_ANALYZE_HIT.'/'._NP_ANALYZE_VISIT.'</th>
<th>'._NP_ANALYZE_DISTRIBUTION.'</th>
<th>P.'.$hit01.'</th>
<th>P.'.($hit_range[0]+1).'-'.$hit_range[1].'</th>
<th>P.'.($hit_range[1]+1).'-'.$hit_range[2].'</th>
<th>P.'.($hit_range[2]+1).'-'.$hit_range[3].'</th>
<th>P.'.($hit_range[3]+1).'-'.$hit_range[4].'</th>
</tr>';
		$result_c = '
</thead>
<tbody>';
		$ii = '';
		$result_a = $result_a.$result_b.$result_c;
		while($row = mysql_fetch_assoc($result)) {
			$ahvi = $row['ahvisit'];
			$havg = ($ahvi) ? round($row['ahhit'] / $ahvi, 2) : '-';
			$ahle1 = $row['ahlevel1'];
			$ahle2 = $row['ahlevel2'];
			$ahle3 = $row['ahlevel3'];
			$ahle4 = $row['ahlevel4'];
			$ahle5 = $row['ahlevel5'];
			$robo_c = $row['ahrobot'];
			$ahlevel1 = ($ahle1) ? round($ahle1 / $ahvi, 2)*100 : 0;
			$ahlevel2 = ($ahle2) ? round($ahle2 / $ahvi, 2)*100 : 0;
			$ahlevel3 = ($ahle3) ? round($ahle3 / $ahvi, 2)*100 : 0;
			$ahlevel4 = ($ahle4) ? round($ahle4 / $ahvi, 2)*100 : 0;
			$ahlevel5 = ($ahle5) ? round($ahle5 / $ahvi, 2)*100 : 0;
			if($date_j == 10) {
				$day_a = array(0=>_NP_ANALYZE_DAY0, 1=>_NP_ANALYZE_DAY1, 2=>_NP_ANALYZE_DAY2, 3=>_NP_ANALYZE_DAY3, 4=>_NP_ANALYZE_DAY4, 5=>_NP_ANALYZE_DAY5, 6=>_NP_ANALYZE_DAY6);
				$day_b = date("w", strtotime($row['ahdate']));
				foreach($day_a as $day_a1=>$day_a2) {
					if($day_a1 == $day_b) $day_u = '('.$day_a2.')';
				}
			}
			if($temp_judge == 'yes' && $date_j == 10) $g_each = '<a href="'.$sslink.'b&amp;sort=DESC&amp;past='.$row['ahdate'].'&amp;group=1"><img src="documentation/icon-help.gif" alt="link" height="15" width="15" title="'._NP_ANALYZE_GROUP.'"></a>';
			$s_date = '>'.$g_each.$row['ahdate'].$day_u;
			$tr = ' onmouseover="focusRow(this);" onmouseout="blurRow(this);"';
			$t_d = '<td style="text-align: right;';
			$s_d = '<span style="font-size: 11px; color: #777777">(';
			$img_c = $oPluginAdmin->plugin->getOption('alz_img_c');
			switch(TRUE) {
			case ($date_j == 10 && $past) :
				$ii++;
				$result_1 .= "<th>".substr($row['ahdate'], 8, 2)."</th>";
				$result_2 .= "<td style=\"vertical-align: bottom;\"><img src=\"".$CONF['PluginURL']."analyze/blue.jpg\" alt=\"point\" title=\"".number_format($ahvi)."\" width=\"12\" height=\"".(round($ahvi/20, 0)*$img_c)."\"></td>";
				$result_3 .= "<td style=\"vertical-align: bottom;\"><img src=\"".$CONF['PluginURL']."analyze/pink.jpg\" alt=\"point\" title=\"".$havg."\" width=\"12\" height=\"".round($havg*10, 0)."\"></td>";
				$ahvi1 = $ahvi1+$ahvi;
				$havg1 = $havg1+$havg;
				break;
			}
			$result_a .= '<tr'.$tr.'>
<td'.$s_date .'</td>
'.$t_d.'">'.number_format($ahvi).'</td>
'.$t_d.'">'.number_format($row['ahhit']).'</td>
'.$t_d.'">'.number_format($robo_c).'</td>
'.$t_d.'">'.$havg.'</td>
<td>
<img src="'.$CONF['PluginURL'].'analyze/blue.jpg" alt="point" title="P.'.$hit01.' ('.$ahlevel1.'%)" height="10" width="'.$ahlevel1.'"><img src="'.$CONF['PluginURL'].'analyze/green.jpg" alt="point" title="P.'.($hit_range[0]+1).'-'.$hit_range[1].' ('.$ahlevel2.'%)" height="10" width="'.$ahlevel2.'"><img src="'.$CONF['PluginURL'].'analyze/pink.jpg" alt="point" title="P.'.($hit_range[1]+1).'-'.$hit_range[2].' ('.$ahlevel3.'%)" height="10" width="'.$ahlevel3.'"><img src="'.$CONF['PluginURL'].'analyze/l_blue.jpg" alt="point" title="P.'.($hit_range[2]+1).'-'.$hit_range[3].' ('.$ahlevel4.'%)" height="10" width="'.$ahlevel4.'"><img src="'.$CONF['PluginURL'].'analyze/red.jpg" alt="point" title="P.'.($hit_range[3]+1).'-'.$hit_range[4].' ('.$ahlevel5.'%)" height="10" width="'.$ahlevel5.'">
</td>
'.$t_d.' background: url('.$CONF['PluginURL'].'analyze/blue.jpg) bottom no-repeat;">'.number_format($ahle1).$s_d.$ahlevel1.'%)</span></td>
'.$t_d.' background: url('.$CONF['PluginURL'].'analyze/green.jpg) bottom no-repeat;">'.number_format($ahle2).$s_d.$ahlevel2.'%)</span></td>
'.$t_d.' background: url('.$CONF['PluginURL'].'analyze/pink.jpg) bottom no-repeat;">'.number_format($ahle3).$s_d.$ahlevel3.'%)</span></td>
'.$t_d.' background: url('.$CONF['PluginURL'].'analyze/l_blue.jpg) bottom no-repeat;">'.number_format($ahle4).$s_d.$ahlevel4.'%)</span></td>
'.$t_d.' background: url('.$CONF['PluginURL'].'analyze/red.jpg) bottom no-repeat;">'.number_format($ahle5).$s_d.$ahlevel5.'%)</span></td>
</tr>';
		}
		mysql_free_result($result);
		$result_a .= '</tbody>
</table>';
		if($ii) $rselt_0 = $title;
		if($date_j == 10 && $past && $ii) $rselt_0 .= '<table style="width: '.($ii*10+30).'px; text-align: center;">
<thead>
<tr class="jsbutton">
<th>'._NP_ANALYZE_DATE.'</th>
'.$result_1.'
</tr>
</thead>
<tbody>
<tr>
<td style="vertical-align: middle;">Hit<br />
*'.number_format($ahvi1/$ii).'</td>
'.$result_2.'
</tr>
<tr>
<td style="vertical-align: middle;">PV/Hit<br />
*'.round($havg1/$ii, 2).'</td>
'.$result_3.'
</tr>
</tbody>
</table>';
		if(!$ii && $date_j == 10 && $past) return;
		return $rselt_0.$title.$result_a;
	}

	function PageGroup($sform = '', $this_month = '',$top_limit = '') {
		global $CONF;
		$id0 = explode('?', htmlspecialchars($_GET['query']), 3);
		$id = IdChange($id0[0], $id0[1], $id0[2], $this_month);
		$page = AnalyzePage($this_month, 1, 0, 0, 'plugin_analyze_page');
		$page1 = AnalyzePattern($this_month, $top_limit, 0);
		$page2 = AnalyzePattern($this_month, $top_limit, 0, 0, 0, 0, 0, 1);
		$num1 = AnalyzePattern($this_month, $top_limit, 0, 0, 0, 0, 0, 0, 1);
		$num2 = AnalyzePattern($this_month, $top_limit, 0, 0, 0, 0, 0, 1, 1);
		$mark = '<div style="float: left; width: 4%; text-align: center; padding-top: 20px;">
<img src="'.$CONF['PluginURL'].'analyze/mark.png" alt="->" height="41" width="21">
</div>';
		$p1 ='<div style="border: 1px solid #ccc; padding: 5px; margin-top: 25px">'.$id.'</div>';
		echo '
<div style="float: left;	width: 48%;">'
.$page.
'</div>
<div style="float: left; width: 48%;	padding-left: 4%;">'
.AnalyzePquery($this_month, $top_limit, 0, 0, 'plugin_analyze_page_query').
'</div>
<div style="clear: left;"> </div>';
		if($num2) echo '<h3>'._NP_ANALYZE_T6.'(*IN)</h3>
<div style="float: left; width: 64%;">'
.$page2.
'</div>
'.$mark.'
<div style="float: left; width: 32%;">'
.$p1.
'</div>
<div style="clear: left;"> </div>';
		if($num1) echo '<h3>'._NP_ANALYZE_T6.'(*OUT)</h3>
<div style="float: left; width: 32%;">'
.$p1.
'</div>
'.$mark.'
<div style="float: left; width: 64%;">'
.$page1.
'</div>
<div style="clear: left;"> </div>';
	}

	function GroupTable($this_month = '', $gname = '', $jd = '') {
		global $CONF;
		if($_GET['group'] == 'all' || ($_GET['query'] && $_GET['group'] == 'page' && $gname != plugin_analyze_page)) $j_j = '<a href="'.$CONF['PluginURL'].'analyze/index.php?select=g&amp;sort=ASC&amp;past='.$this_month.'&amp;group='.$gname.'&amp;query='.htmlspecialchars($_GET['query']).'&amp;jd='.$jd.'"><img src="documentation/icon-help.gif" alt="link" height="15" width="15" title="'._NP_ANALYZE_GROUP0.'"></a>';
		return $j_j;
	}

	function GroupSort($gname = '', $hit = '', $pv = '', $apage = '', $aorder = '', $past = '', $jd = '') {
		global $CONF;
		switch(TRUE) {
		case($_GET['query'] && $_GET['group'] == 'page' && $gname == 'plugin_analyze_page') :
			$g_sort = '<th>'._NP_ANALYZE_VISIT.'</th>
<th>'._NP_ANALYZE_HIT.'</th>';
			break;
		default:
			if(!$_GET['group']) $aorder = 'ASC';
			$t_sort = '<th><a href="'.$CONF['PluginURL'].'analyze/index.php?select=g&amp;sort='.$aorder.'&amp;past='.$past.'&amp;page='.$apage.'&amp;group='.$gname.'&amp;query='.htmlspecialchars($_GET['query']).'&amp;jd='.$jd.'&amp;fie=';
		$g_sort = $t_sort.$hit.'" title="'._NP_ANALYZE_SORT.'">'._NP_ANALYZE_VISIT.'</a></th>'.
$t_sort.$pv.'" title="'._NP_ANALYZE_SORT.'">'._NP_ANALYZE_HIT.'</a></th>';
		}
		return $g_sort;
	}

	function AnalyzePage($this_month = '', $top_limit = '', $apage = '', $aorder = '', $gname = '', $fie = '', $p0 = '') {
		global $CONF;
		if(!$aorder) $aorder = 'DESC';
		$fie = ($fie) ? $fie : 'aphit1';
		$fie2 = ($fie == 'aphit1') ? 'aphit' : 'aphit1';
		$i == '';
		if($_GET['query'] && $_GET['group'] == 'page') {
			$whe = " and apid LIKE '%".addslashes(htmlspecialchars($_GET['query']))."%'";
		}elseif($_GET['query'] || $_GET['jd']) {
			if($_GET['query']) $whe2 = " and iblog = ".addslashes(htmlspecialchars($_GET['query']));
			if($_GET['jd'] && $_GET['query']) $whe2 .= " and icat = ".addslashes($_GET['jd']);
			sql_query("CREATE TEMPORARY TABLE temp_page as SELECT SUBSTRING_INDEX(SUBSTRING(apid, 3), '?', 1) as apid0, apid, aphit, aphit1, aphit2, apdate FROM ".sql_table($gname)." WHERE apid LIKE '%i?%'");
		}
		$resu = "SELECT apid, aphit, aphit1, aphit2 FROM ";
		$resu .= ($whe2) ? "temp_page left join ".sql_table('item')." on inumber = apid0 WHERE LEFT(apdate, 7) = '".$this_month."'".$whe2 : sql_table($gname)." WHERE LEFT(apdate, 7) = '".$this_month."'".$whe;
		$result = sql_query($resu." ORDER BY ".$fie." ".$aorder.",".$fie2." ".$aorder." LIMIT ".$apage.",".$top_limit);
		$num = mysql_num_rows(sql_query($resu));
		if($_GET['group'] != 'all') ChoiceData($resu, 1);
		while($row = mysql_fetch_assoc($result)) {
			if($_GET['group'] != 'all') {
				$c_a = 100;
				$no_a = '<th>No.</th>';
			}
			$apid0 = explode('?', $row['apid'], 3);
			$hd = ($_GET['group'] == 'page') ? 0 : 1 ;
			$apid = IdChange($apid0[0], $apid0[1], $apid0[2], $this_month, $c_a, 0, $hd);
			if(!$i) $result_a = '<table>
<thead>
<tr class="jsbutton">'.$no_a.'
<th>'.GroupTable($this_month, $gname)._NP_ANALYZE_ACCESS_PAGE.'</th>
'.GroupSort($gname, 'aphit1', 'aphit', $p0, $aorder, $this_month).'
</tr>
</thead>
<tbody>';
			$n2 = $apage + $i + 1;
			if($row['aphit2'] && !$_GET['query']) $n2 .= ' <span style="color:#aaa;">('.number_format($row['aphit2']).')</span>';
			if($_GET['group'] != 'all') $no_b = '<td>'.$n2.'</td>';
			$result_a .= '<tr onmouseover="focusRow(this);" onmouseout="blurRow(this);">'.$no_b.'
<td>'.$apid.'</td>
<td style="text-align: right;">'.number_format($row['aphit1']).'</td>
<td style="text-align: right;">'.number_format($row['aphit']).'</td>
</tr>';
			$i++;
		}
		mysql_free_result($result);
		$ap1 = explode('?', htmlspecialchars($_GET['query']), 3);
		if(!$num && $_GET['query'] && $_GET['group'] == 'page') $result_a .= '<table>
<tr>
<td>'.IdChange($ap1[0], $ap1[1], 0, '+').'</td>
<td>PV</td>
<td>0</td>
</tr>';
		if($_GET['query'] && $_GET['group'] == 'page' && $this_month == date("Y-m")) {
			$res = quickQuery("SELECT COUNT(allog) as result FROM ".sql_table('plugin_analyze_log')." WHERE alid LIKE '%".addslashes(htmlspecialchars($_GET['query']))."?%' GROUP BY alid ORDER BY null LIMIT 1");
			if($res) $result_a .= '<tr>
<td style="text-align: right; color: #aaa" colspan="4">*Today\'s PageView +'.number_format($res).'<a href="'.$CONF['PluginURL'].'analyze/index.php?select=b&amp;sort=ASC&amp;fie=aldate&amp;page=1&amp;query='.htmlspecialchars($_GET['query']).'?"><img src="documentation/icon-help.gif" alt="link" height="15" width="15" title="['._NP_ANALYZE_EXTRACT.']: '.htmlspecialchars($_GET['query']).'?"></a></td>
</tr>';
		}
		$result_a .= '</tbody>
</table>';
		return $result_a;
	}

	function AnalyzePquery($this_month = '', $top_limit = '', $apage = '', $aorder = '' ,$gname = '', $fie = '', $p0 = '') {
		if(!$aorder) $aorder = 'DESC';
		$fie = ($fie) ? $fie : 'apqvisit';
		$fie2 = ($fie == 'apqvisit') ? 'apqhit' : 'apqvisit';
		$i == '';
		if($_GET['jd'] == 1) $jd = array('!(', ')');
		if($_GET['query'] && $_GET['group'] != 'page') $whe = " and ".$jd[0]."apqquery LIKE '%".addslashes(htmlspecialchars($_GET['query']))."%'".$jd[1];
		$resu = "SELECT apqid, apqquery, apqhit, apqvisit FROM ".sql_table($gname)." WHERE LEFT(apqdate, 7) = '".$this_month."'".$whe;
		if($_GET['query'] && $_GET['group'] == 'page') $resu .= " and apqid LIKE '".addslashes(htmlspecialchars($_GET['query']))."?%'";
		$result = sql_query($resu." ORDER BY ".$fie." ".$aorder.",".$fie2." ".$aorder." LIMIT ".$apage.",".$top_limit);
		$num = number_format(mysql_num_rows(sql_query($resu)));
		if($_GET['group'] != 'all') ChoiceData($resu);
		while($row = mysql_fetch_assoc($result)) {
			if($_GET['group'] != 'all') $c_a = 100;
			$apid0 = explode('?', $row['apqid'], 3);
			$apid = IdChange($apid0[0], $apid0[1], $apid0[2], $this_month, $c_a, 0 ,1);
			$apqquery = IdChange($row['apqquery'], 1, 0, $this_month);
			if($_GET['group'] != 'all') $no_a = '<th>No.</th>';
			if(!$i) $result_a .= '<table>
<thead>
<tr class="jsbutton">'.$no_a.'
<th>'.GroupTable($this_month, $gname);
			if($_GET['group'] != 'page' && !$i) $result_a .= _NP_ANALYZE_ACCESS_PAGE.'</th>
<th>';
			if(!$i) $result_a .= _NP_ANALYZE_SEARCH_WORD.'</th>
'.GroupSort($gname, 'apqvisit', 'apqhit', $p0, $aorder, $this_month).'
</tr>
</thead>
<tbody>';
			if($_GET['group'] != 'all') $no_b = '<td>'.($apage + $i + 1).'</td>';
			$result_a .= '<tr onmouseover="focusRow(this);" onmouseout="blurRow(this);">'.$no_b;
			if($_GET['group'] != 'page') $result_a .= '<td>'.$apid.'</td>';
			$result_a .= '<td>'.$apqquery.'</td>
<td style="text-align: right;">'.number_format($row['apqvisit']).'</td>
<td style="text-align: right;">'.number_format($row['apqhit']).'</td>
</tr>';
			$i++;
		}
		mysql_free_result($result);
		$result_a .= '</tbody>
</table>';
		return $result_a;
	}

	function AnalyzePattern($this_month = '', $top_limit = '', $apage = '', $aorder = '' ,$gname = '', $fie = '', $p0 = '', $jd = '', $ct = '') {
		global $CONF;
		if(!$gname) $gname = 'plugin_analyze_page_pattern';
		if(!$aorder) $aorder = 'DESC';
		$fie = ($fie) ? $fie : 'appvisit';
		$fie2 = ($fie == 'appvisit') ? 'apphit' : 'appvisit';
		$i == '';
		$resu = "SELECT appid, apppage, apphit, appvisit FROM ".sql_table($gname)." WHERE LEFT(appdate, 7) = '".$this_month."'";
		if($_GET['query'] && ($_GET['group'] == 'page' || !is_numeric($_GET['query']))) {
			$res1 = ($jd) ? " and appid" : " and apppage";
			$res2 = " LIKE '".addslashes(htmlspecialchars($_GET['query']))."?%'";
		}elseif($_GET['query'] || $_GET['jd']) {
			if($_GET['query']) $whe2 = " and iblog = ".addslashes($_GET['query']);
			if($_GET['jd'] && $_GET['query']) $whe2 .= " and icat = ".addslashes($_GET['jd']);
			sql_query("CREATE TEMPORARY TABLE temp_page as SELECT SUBSTRING_INDEX(SUBSTRING(apppage, 3), '?', 1) as apid0, appid, apppage, apphit, appvisit, appdate FROM ".sql_table($gname)." WHERE apppage LIKE '%i?%'");
			$resu = "SELECT apid0, appid, apppage, apphit, appvisit, appdate FROM temp_page left join ".sql_table('item')." on inumber = apid0 WHERE LEFT(appdate, 7) = '".$this_month."'".$whe2;
		}
		$result = sql_query($resu.$res1.$res2." ORDER BY ".$fie." ".$aorder.",".$fie2." ".$aorder." LIMIT ".$apage.",".$top_limit);
		if($ct) return mysql_num_rows(sql_query($resu.$res1.$res2));
		if($_GET['group'] != 'all') ChoiceData($resu, 1);
		while($row = mysql_fetch_assoc($result)) {
			if($_GET['group'] != 'all') $no_a = '<th>No.</th>';
			if(!$i) $result_a .= '<table>
<thead>
<tr class="jsbutton">'.$no_a.
'<th>'.GroupTable($this_month, $gname, $jd);
			$p_page = ($jd) ? _NP_ANALYZE_PEPREV : _NP_ANALYZE_PENEXT ;
			if(!$i) $result_a .= ($_GET['group'] == 'page') ? $p_page.'</th>' : _NP_ANALYZE_ACCESS_PAGE.'</th>
<th>'._NP_ANALYZE_NEXT_PAGE.'</th>';
			if(!$i) $result_a .= GroupSort($gname, 'appvisit', 'apphit', $p0, $aorder, $this_month, $jd).'
</tr>
</thead>
<tbody>';
			if($_GET['group'] != 'all') $c_a = 100;
			$apid0 = explode('?', $row['apppage'], 3);
			$apid = IdChange($apid0[0], $apid0[1], $apid0[2], $this_month, $c_a, 0 ,1);
			$apid2 = explode('?', $row['appid'], 3);
			$apid1 = IdChange($apid2[0], $apid2[1], $apid2[2], $this_month, $c_a, 0 ,1);
			if($_GET['group'] != 'all') $no_b = '<td>'.($apage + $i + 1).'</td>';
			$result_a .= '<tr onmouseover="focusRow(this);" onmouseout="blurRow(this);">'.$no_b;
			$g_page = ($jd) ? $apid : $apid1 ;
			$result_a .= ($_GET['group'] == 'page') ? '<td>'.$g_page.'</td>' : '<td>'.$apid.'</td>
<td>'.$apid1.'</td>';
			if($_GET['query'] && $_GET['group'] == 'page') {
				$num1 = quickQuery("SELECT aphit1 as result FROM ".sql_table('plugin_analyze_page')." WHERE LEFT(apdate, 7) = '".$this_month."' and apid LIKE '".addslashes(htmlspecialchars($_GET['query']))."?%'");
				$num2 = '<img src="'.$CONF['PluginURL'].'analyze/pink.jpg" alt="point" title="'.(round($row['appvisit']/$num1, 4)*100).'%" height="10" width="'.(round($row['appvisit']/$num1, 3)*130).'"> ';
			}
			$result_a .= '<td style="text-align: right;">'.$num2.number_format($row['appvisit']).'</td>
<td style="text-align: right;">'.number_format($row['apphit']).'</td>
</tr>';
			$i++;
		}
		mysql_free_result($result);
		$result_a .= '</tbody>
</table>';
		return $result_a;
	}

	function AnalyzeReferer($this_month = '', $top_limit = '', $apage = '', $aorder = '', $gname = '', $fie = '', $p0 = '') {
		if(!$aorder) $aorder = 'DESC';
		$fie = ($fie) ? $fie : 'arvisit';
		$fie2 = ($fie == 'arvisit') ? 'arhit' : 'arvisit';
		$i == '';
		if($_GET['jd'] == 1) $jd = array('!(', ')');
		if($_GET['query']) $whe = " and ".$jd[0]."arreferer LIKE '%".addslashes(htmlspecialchars($_GET['query']))."%'".$jd[1];
		$resu = "SELECT arreferer, arhit, arvisit FROM ".sql_table($gname)." WHERE LEFT(ardate, 7) = '".$this_month."'".$whe;
		$result = sql_query($resu." ORDER BY ".$fie." ".$aorder.",".$fie2." ".$aorder." LIMIT ".$apage.",".$top_limit);
		if($_GET['group'] != 'all') ChoiceData($resu);
		while($row = mysql_fetch_assoc($result)) {
			if($_GET['group'] != 'all') $no_a = '<th>No.</th>';
			if(!$i) $result_a = '<table>
<thead>
<tr class="jsbutton">'.$no_a.'
<th>'.GroupTable($this_month, $gname)._NP_ANALYZE_REFERER_PAGE.'</th>
'.GroupSort($gname, 'arvisit', 'arhit', $apage, $aorder, $this_month).'
</tr>
</thead>
<tbody>';
			$c_a = ($_GET['group'] == 'all') ? 35 : 100;
			$arreferer = IdChange($row['arreferer'], 0, 0, $this_month, $c_a);
			if($_GET['group'] != 'all') $no_b = '<td>'.($apage + $i + 1).'</td>';
			$result_a .= '<tr onmouseover="focusRow(this);" onmouseout="blurRow(this);">'.$no_b.'
<td style="font-size: 12px;">'.$arreferer.'</td>
<td style="text-align: right;">'.number_format($row['arvisit']).'</td>
<td style="text-align: right;">'.number_format($row['arhit']).'</td>
</tr>';
			$i++;
		}
		mysql_free_result($result);
		$result_a .= '</tbody>
</table>';
		return $result_a;
	}

	function AnalyzeQuery($this_month = '', $top_limit = '', $apage = '', $aorder = '', $gname = '', $fie = '', $p0 = '') {
		if(!$aorder) $aorder = 'DESC';
		$fie = ($fie) ? $fie : 'aqvisit';
		$fie2 = ($fie == 'aqvisit') ? 'aqhit' : 'aqvisit';
		$i == '';
		if($_GET['jd'] == 1) $jd = array('!(', ')');
		if($_GET['query']) $whe = " and ".$jd[0]."aqquery LIKE '%".addslashes(htmlspecialchars($_GET['query']))."%'".$jd[1];
		$resu = "SELECT aqquery, aqhit, aqvisit FROM ".sql_table($gname)." WHERE LEFT(aqdate, 7) = '".$this_month."'".$whe;
		$result = sql_query($resu." ORDER BY ".$fie." ".$aorder.",".$fie2." ".$aorder." LIMIT ".$apage.",".$top_limit);
		if($_GET['group'] != 'all') ChoiceData($resu);
		while($row = mysql_fetch_assoc($result)) {
			if($_GET['group'] != 'all') $no_a = '<th>No.</th>';
			if(!$i) $result_a = '<table>
<thead>
<tr class="jsbutton">'.$no_a.'
<th>'.GroupTable($this_month, $gname)._NP_ANALYZE_SEARCH_WORD.'</th>
'.GroupSort($gname, 'aqvisit', 'aqhit', $apage, $aorder, $this_month).'
</tr>
</thead>
<tbody>';
			$aqquery = IdChange($row['aqquery'], 1, 0, $this_month);
			if($_GET['group'] != 'all') $no_b = '<td>'.($apage + $i + 1).'</td>';
			$result_a .= '<tr onmouseover="focusRow(this);" onmouseout="blurRow(this);">'.$no_b.'
<td>'.$aqquery.'</td>
<td style="text-align: right;">'.number_format($row['aqvisit']).'</td>
<td style="text-align: right;">'.number_format($row['aqhit']).'</td>
</tr>';
			$i++;
		}
		mysql_free_result($result);
		$result_a .= '</tbody>
</table>';
		return $result_a;
	}

	function AnalyzeHost($this_month = '', $top_limit = '', $apage = '', $aorder = '', $gname = '', $fie = '', $p0 = '') {
		if(!$aorder) $aorder = 'DESC';
		$fie = ($fie) ? $fie : 'ahvisit';
		$fie2 = ($fie == 'ahvisit') ? 'ahhit' : 'ahvisit';
		$i == '';
		if($_GET['jd'] == 1) $jd = array('!(', ')');
		if($_GET['query']) $whe = " and ".$jd[0]."ahhost LIKE '%".addslashes(htmlspecialchars($_GET['query']))."%'".$jd[1];
		$resu = "SELECT ahhost, ahhit, ahvisit FROM ".sql_table($gname)." WHERE LEFT(ahdate, 7) = '".$this_month."'".$whe;
		$result2 = sql_query($resu." ORDER BY ".$fie." ".$aorder.",".$fie2." ".$aorder." LIMIT ".$apage.",".$top_limit);
		if($_GET['group'] != 'all') ChoiceData($resu, "alz_c_host");
		while($row2 = mysql_fetch_assoc($result2)) {
			if($_GET['group'] != 'all') $no_a = '<th>No.</th>';
			if(!$i) $result_a = '<table>
<thead>
<tr class="jsbutton">'.$no_a.'
<th>'.GroupTable($this_month, $gname)._NP_ANALYZE_ACCESS_HOST.'</th>
'.GroupSort($gname, 'ahvisit', 'ahhit', $apage, $aorder, $this_month).'
</tr>
</thead>
<tbody>';
			$ahhost = IdChange($row2['ahhost'], 1, 0, $this_month);
			if($_GET['group'] != 'all') $no_b = '<td>'.($apage + $i + 1).'</td>';
			$result_a .= '<tr onmouseover="focusRow(this);" onmouseout="blurRow(this);">'.$no_b.'
<td>'.$ahhost.' [<a href="http://www.google.com/search?q=%22'.$row2["ahhost"].'%22" title="Google Search for \''.$row2["ahhost"].'\'">G</a>]</td>
<td style="text-align: right;">'.number_format($row2['ahvisit']).'</td>
<td style="text-align: right;">'.number_format($row2['ahhit']).'</td>
</tr>';
			$i++;
		}
		mysql_free_result($result2);
		$result_a .= '</tbody>
</table>';
		return $result_a;
	}

	function AnalyzeEngine($this_month = '', $top_limit = '', $apage = '', $aorder = '', $gname = '', $fie = '', $p0 = '') {
		if(!$aorder) $aorder = 'DESC';
		$fie = ($fie) ? $fie : 'aevisit';
		$fie2 = ($fie == 'aevisit') ? 'aehit' : 'aevisit';
		$i == '';
		if($_GET['jd'] == 1) $jd = array('!(', ')');
		if($_GET['query']) $whe = " and ".$jd[0]."aeengine LIKE '%".addslashes(($_GET['query']))."%'".$jd[1];
		$resu = "SELECT aeengine, aehit, aevisit FROM ".sql_table($gname)." WHERE LEFT(aedate, 7) = '".$this_month."'".$whe;
		$result = sql_query($resu." ORDER BY ".$fie." ".$aorder.",".$fie2." ".$aorder." LIMIT ".$apage.",".$top_limit);
		if($_GET['group'] != 'all') ChoiceData($resu);
		while($row = mysql_fetch_assoc($result)) {
			if($_GET['group'] != 'all') $no_a = '<th>No.</th>';
			if(!$i) $result_a = '<table>
<thead>
<tr class="jsbutton">'.$no_a.'
<th>'.GroupTable($this_month, $gname)._NP_ANALYZE_SEARCH_ENGINE.'</th>
'.GroupSort($gname, 'aevisit', 'aehit', $apage, $aorder, $this_month).'
</tr>
</thead>
<tbody>';
			$aeengine0 = explode('?', $row['aeengine'], 3);
			$aeengine = IdChange($aeengine0[0], $aeengine0[1], 1, $this_month);
			if($_GET['group'] != 'all') $no_b = '<td>'.($apage + $i + 1).'</td>';
			$result_a .= '<tr onmouseover="focusRow(this);" onmouseout="blurRow(this);">'.$no_b.'
<td>'.$aeengine.'</td>
<td style="text-align: right;">'.number_format($row['aevisit']).'</td>
<td style="text-align: right;">'.number_format($row['aehit']).'</td>
</tr>';
			$i++;
		}
		mysql_free_result($result);
		$result_a .= '</tbody>
</table>';
		return $result_a;
	}

	function AnalyzeRobot($this_month = '', $top_limit = '', $apage = '', $aorder = '', $gname = '', $fie = '', $p0 = '') {
		global $CONF;
		if(!$aorder) $aorder = 'DESC';
		$fie = ($fie) ? $fie : 'arovisit';
		$fie2 = ($fie == 'arovisit') ? 'arohit' : 'arovisit';
		$i == '';
		if($_GET['jd'] == 1) $jd = array('!(', ')');
		if($_GET['query']) $whe = " and ".$jd[0]."aroengine LIKE '%".addslashes(htmlspecialchars($_GET['query']))."%'".$jd[1];
		$resu = "SELECT aroengine, arohit, arovisit FROM ".sql_table($gname)." WHERE LEFT(arodate, 7) = '".$this_month."'".$whe;
		$result = sql_query($resu." ORDER BY ".$fie." ".$aorder.",".$fie2." ".$aorder." LIMIT ".$apage.",".$top_limit);
		if($_GET['group'] != 'all') ChoiceData($resu);
		while($row = mysql_fetch_assoc($result)) {
			if($_GET['group'] != 'all') $no_a = '<th>No.</th>';
			if(!$i) $result_a = '<table>
<thead>
<tr class="jsbutton">'.$no_a.'
<th>'.GroupTable($this_month, $gname).'<a href="'.$CONF['PluginURL'].'analyze/index.php?select=ng" title="'._NP_ANALYZE_NG.'">'._NP_ANALYZE_NG0.'</a> ('._NP_ANALYZE_ROBOT.')</th>
'.GroupSort($gname, 'arovisit', 'arohit', $apage, $aorder, $this_month).'
</tr>
</thead>
<tbody>';
			if($_GET['group'] != 'all') $no_b = '<td>'.($apage + $i + 1).'</td>';
			$result_a .= '<tr onmouseover="focusRow(this);" onmouseout="blurRow(this);">'.$no_b.'
<td>'.$row['aroengine'].'</td>
<td style="text-align: right;">'.number_format($row['arovisit']).'</td>
<td style="text-align: right;">'.number_format($row['arohit']).'</td>
</tr>';
			$i++;
		}
		mysql_free_result($result);
		$result_a .= '</tbody>
</table>';
		return $result_a;
	}

	function ChoiceData($resu = '', $sel = '') {
		global $CONF, $oPluginAdmin;
		if($_GET['group'] == 'page') return;
		switch($sel) {
		case '1' :
			$res0 = sql_query("SELECT DISTINCT bnumber, bname FROM ".sql_table('blog')." ORDER BY bnumber");
			while($row = mysql_fetch_assoc($res0)) {
				$check = ($row['bnumber'] == $_GET['query']) ? ' selected="selected"' : '';
				$q1 .= '<option value="'.$row['bnumber'].'"'.$check.'>'.$row['bname'].'</option>';
			}
			if($_GET['query'] && is_numeric($_GET['query'])) $cj = ' and bnumber = '.intval($_GET['query']);
			$res0 = sql_query("SELECT DISTINCT bnumber, catid, cname FROM ".sql_table('blog').", ".sql_table('category')." WHERE bnumber = cblog".$cj." ORDER BY bnumber, catid");
			while($row = mysql_fetch_assoc($res0)) {
				$check = ($row['catid'] == $_GET['jd']) ? ' selected="selected"' : '';
				$q2 .= '<option value="'.$row['catid'].'"'.$check.'>'.$row['cname'].'</option>';
			}
			if($_GET['query'] && is_numeric($_GET['query'])) $q20 = '<select name="jd" onchange="return form.submit()">
<option value="">- Category Choice -</option>'
.$q2.
'</select>';
			$q = '<select name="query" onchange="return form.submit()">
<option value="">- Blog Choice -</option>'
.$q1.
'</select> '
.$q20;
			break;
		default :
			$s0 = explode('/', $oPluginAdmin->plugin->getOption($sel));
			$jd = ($_GET['jd'] == 1) ? ' checked="checked"' : '';
			$q = '<input name="query" size="20" value="'.htmlspecialchars($_GET['query']).'" />
<input name="jd" type="checkbox" value="1"'.$jd.' id="jd1" /><label for="jd1">NOT</label>';
		}
		$flink = $CONF['PluginURL'].'analyze/index.php';
		$f2 = '<a href="'.$flink.'?select='.htmlspecialchars($_GET['select']).'&amp;sort='.htmlspecialchars($_GET['sort']).'&amp;fie='.htmlspecialchars($_GET['fie']).'&amp;group='.htmlspecialchars($_GET['group']).'&amp;past='.htmlspecialchars($_GET['past']).'&amp;query=';
		echo '<form method="get" action="'.$flink.'">
<div>'
._NP_ANALYZE_COUNT.number_format(mysql_num_rows(sql_query($resu))).
'<input name="select" type="hidden" value="'.htmlspecialchars($_GET['select']).'" />
<input name="sort" type="hidden" value="'.htmlspecialchars($_GET['sort']).'" />
<input name="fie" type="hidden" value="'.htmlspecialchars($_GET['fie']).'" />
<input name="group" type="hidden" value="'.htmlspecialchars($_GET['group']).'" />
<input name="past" type="hidden" value="'.htmlspecialchars($_GET['past']).'" />
'.$q.'
<input name="edit" type="submit" value="'._NP_ANALYZE_SEARCH.'" tabindex="10122" />';
		if($s0[0]) foreach($s0 as $s1) echo ' ['.$f2.$s1.'">'.$s1.'</a>]';
		echo '</div></form>';
	}

	function GroupAnalyze($past = '', $p00 = '', $numb = '') {
		global $oPluginAdmin, $CONF;
		$p00 = ($p00) ? $p00 : 0;
		$gtable = qTable($past);
		if($numb == 10) $d_w = " WHERE LEFT(aldate, 10) = '".$past."' ";
		if($numb == 10) $d_w1 = " and LEFT(aldate, 10) = '".$past."' ";
		$result_a = '<div style="float: left;	width: 32%;">
<table>
<thead>
<tr class="jsbutton">
<th>'._NP_ANALYZE_ACCESS_PAGE.'</th>
<th>'._NP_ANALYZE_HIT.'</th>
</tr>
</thead>
<tbody>';
		$result = sql_query("SELECT alid , COUNT(allog) as count FROM ".$gtable.$d_w." GROUP BY alid ORDER BY count DESC LIMIT ".$p00.",50");
		while($row = mysql_fetch_assoc($result)) {
			$apid0 = explode('?', $row['alid'], 3);
			$apid = IdChange($apid0[0], $apid0[1], $apid0[2], $past, 0, 0, 1);
			$result_a .= '<tr onmouseover="focusRow(this);" onmouseout="blurRow(this);">
<td>'.$apid.'</td>
<td style="text-align: right;">'.number_format($row['count']).'</td>
</tr>';
		}
		mysql_free_result($result);
		$result_a .= '</tbody>
</table>
</div>
<div style="float: left;	width: 28%;	padding-left: 1%;">
<table>
<thead>
<tr class="jsbutton">
<th>'._NP_ANALYZE_ACCESS_HOST.'</th>
<th>'._NP_ANALYZE_HIT.'</th>
</tr>
</thead>
<tbody>';
		$result = sql_query("SELECT alip, COUNT(alip) as count FROM ".$gtable.$d_w." GROUP BY alip ORDER BY count DESC LIMIT ".$p00.",50");
		while($row = mysql_fetch_assoc($result)) {
			$apid = htmlspecialchars(substr($row['alip'], -25));
			$apid = $apid.' <a href="'.$CONF['PluginURL'].'analyze/index.php?select=b&amp;sort=ASC&amp;fie=aldate&amp;page=1&amp;past='.$past.'&amp;query='.$row['alip'].'&amp;jd='.$past.'"><img src="documentation/icon-help.gif" alt="link" height="15" width="15" title="['._NP_ANALYZE_EXTRACT.']: '.$row['alip'].'"></a>';
			$result_a .= '<tr onmouseover="focusRow(this);" onmouseout="blurRow(this);">
<td>'.$apid.'</td>
<td style="text-align: right;">'.number_format($row['count']).'</td>
</tr>';
		}
		mysql_free_result($result);
		$result_a .= '</tbody>
</table>
</div>
<div style="float: left;	width: 38%;	padding-left: 1%;">
<table>
<thead>
<tr class="jsbutton">
<th>'._NP_ANALYZE_REFERER_PAGE.'</th>
<th>'._NP_ANALYZE_HIT.'</th>
</tr>
</thead>
<tbody>';
		$result = sql_query("SELECT alreferer, COUNT(allog) as count FROM ".$gtable." WHERE LEFT(alreferer, 4) = 'http'".$d_w1." GROUP BY alreferer ORDER BY count DESC LIMIT ".$p00.",50");
		while($row = mysql_fetch_assoc($result)) {
			$apid = IdChange($row['alreferer'], 0, 0, $past, 35);
			$result_a .= '<tr onmouseover="focusRow(this);" onmouseout="blurRow(this);">
<td>'.$apid.'</td>
<td style="text-align: right;">'.number_format($row['count']).'</td>
</tr>';
		}
		mysql_free_result($result);
		$result_a .= '</tbody>
</table>
</div>
<div style="clear: left;"> </div>';
		return $result_a;
	}

	function oCount($id = '', $cat = '') {
		global $oPluginAdmin;
		return $oPluginAdmin->plugin->Countting($id, $cat);
	}

	function DatabaseDelCount($y1 = '', $m1 = '', $d_count = '') {
		global $oPluginAdmin;
		return $oPluginAdmin->plugin->DatabaseDelCount($y, $m1, $d_count);
	}

	function Period($y1 = '', $m1 = '', $db = '', $select = '') {
		$ys = ($y1) ? $y1 : date("Y");
		$ms = ($m1) ? $m1 : date("m");
		$db = ($db) ? $db : 1;
		$y = array($ys-4, $ys-3, $ys-2, $ys-1, $ys);
		$peri = '<span style="font-size: small"><select name="y1">';
		foreach($y as $s2) {
			$flag = ($s2 == $ys) ? ' selected="selected"' : '';
			$peri .= '<option value="'.$s2.'"'.$flag.'>'.$s2.'</option>';
		}
		$m = array('01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12');
		$peri .= '</select><select name="m1">';
		foreach($m as $s2) {
			$flag = ($s2 == $ms) ? ' selected="selected"' : '';
			$peri .= '<option value="'.$s2.'"'.$flag.'>'.$s2.'</option>';
		}
		$peri .= '</select>
<input name="db" type="hidden" value="'.$db.'" />
<input name="select" type="hidden" value="'.$select.'" />
<input name="period" type="submit" value="'._NP_ANALYZE_EXTRACT.'" />';
		$peri .= '</span></form>';
		return $peri;
	}

	function getmicrotime(){
		list($usec, $sec) = explode(" ",microtime());
		return ((float)$sec + (float)$usec);
	}

	function IdChange($select = '', $id = '', $other = '', $past = '', $c = '', $que = '', $hd = '') {
		global $oPluginAdmin;
		return $oPluginAdmin->plugin->IdChange($select, $id, $other, $past, $c, $que, $hd);
	}
