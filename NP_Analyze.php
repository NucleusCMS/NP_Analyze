<?php
if (!function_exists('sql_table')) {
    function sql_table($name)
    {
        return 'nucleus_' . $name;
    }
}
class NP_Analyze extends NucleusPlugin
{
    function getName()
    {
        return "Analyze";
    }

    function getAuthor()
    {
        return "jun";
    }

    function getURL()
    {
        return "https://github.com/NucleusCMS/NP_Analyze";
    }

    function getVersion()
    {
        return "0.5331";
    }

    function getMinNucleusVersion()
    {
        return "250";
    }

    function getDescription()
    {
        return _NP_ANALYZE_TITLE;
    }

    function supportsFeature($what)
    {
        switch ($what) {
            case 'SqlTablePrefix':
                return 1;
            default:
                return 0;
        }
    }

    function getEventList()
    {
        return array('QuickMenu', 'PreSkinParse', 'PostAddComment', 'PostPluginOptionsUpdate');
    }

    function hasAdminArea()
    {
        return 1;
    }

    function install()
    {
        include __DIR__ . '/include/functions/install.inc.php';
    }

    function InsertRobot()
    {
        include __DIR__ . '/include/functions/insert-robot.inc.php';
    }

    function uninstall()
    {
        include __DIR__ . '/include/functions/uninstall.inc.php';
    }

    function getTableList()
    {
        return array(
            sql_table('plugin_analyze_log'),
            sql_table('plugin_analyze_hit'),
            sql_table('plugin_analyze_page'),
            sql_table('plugin_analyze_page_pattern'),
            sql_table('plugin_analyze_page_query'),
            sql_table('plugin_analyze_query'),
            sql_table('plugin_analyze_engine'),
            sql_table('plugin_analyze_robot'),
            sql_table('plugin_analyze_referer'),
            sql_table('plugin_analyze_host'),
            sql_table('plugin_analyze_temp'),
            sql_table('plugin_analyze_ng')
        );
    }

    function event_QuickMenu(&$data)
    {
        global $member;
        if ($this->getOption('alz_quickmenu') !== 'yes') {
            return;
        }
        if (!$member->isLoggedIn()) {
            return;
        }
        if ($this->loginMember() != 1 && !$member->isAdmin()) {
            return;
        }
        $data['options'][] = array(
            'title' => _NP_ANALYZE_DATA,
            'url' => $this->getAdminURL(),
            'tooltip' => _NP_ANALYZE_TITLE
        );
    }

    function event_PostAddComment(&$data)
    {
        global $member;
        if ($member->isLoggedIn() && $this->getOption('alz_loggedin') === 'no') {
            return;
        }
        $comment = $data['comment'];
        $commentid = $data['commentid'];
        $alid = 'co?' . (int)$commentid . '?' . addslashes($comment['user']);
        $aldate = date('Y-m-d H:i:s', $comment['timestamp']);
        $alip = addslashes($comment['host']);
        $alreferer = 'i?' . (int)$comment['itemid'] . '?';
        sql_query(
            sprintf(
                "INSERT INTO %s (alid, aldate, alip, alreferer) VALUES ('%s', '%s', '%s', '%s')",
                sql_table('plugin_analyze_log'),
                $alid,
                $aldate,
                $alip,
                $alreferer
            )
        );
        return;
    }

    function event_PostPluginOptionsUpdate(&$data)
    {
        if ($data['plugid'] == $this->GetID()) {
            if ($this->getOption(alz_rss) === 'yes') {
                sql_query(
                    sprintf(
                        "DELETE FROM %s WHERE antitle = 'rss'",
                        sql_table('plugin_analyze_ng')
                    )
                );
            } else {
                $rss1 = quickQuery(
                    sprintf(
                        "SELECT anid as result FROM %s WHERE antitle = 'rss'",
                        sql_table('plugin_analyze_ng')
                    )
                );
                if (!$rss1) {
                    sql_query(
                        sprintf(
                            "INSERT INTO %s (anid, antitle, anip) VALUES ('1', 'rss', '')",
                            sql_table('plugin_analyze_ng')
                        )
                    );
                }
            }
            if ($this->getOption(alz_temp) === 'no') {
                sql_query('TRUNCATE TABLE ' . sql_table('plugin_analyze_temp'));
            }
        }
    }

    function loginMember()
    {
        global $member;
        $memberid = $member->getID();
        $logmem = explode('/', $this->getOption('alz_member'));
        $logmem1 = 0;
        foreach ($logmem as $logm) {
            if ($memberid == $logm) {
                $logmem1++;
            }
        }
        return $logmem1;
    }

    function init()
    {
        $language = ereg_replace('[\\|/]', '', getLanguageName());
        if (file_exists($this->getDirectory() . $language . '.php')) {
            include_once($this->getDirectory() . $language . '.php');
        } else {
            include_once($this->getDirectory() . 'english.php');
        }
    }

    function TableExists($tbl_name)
    {
        global $MYSQL_DATABASE;
        $rs = mysql_list_tables($MYSQL_DATABASE);
        while ($arr_row = mysql_fetch_row($rs)) {
            if (in_array($tbl_name, $arr_row)) {
                return true;
            }
        }
        return false;
    }

    function UpPage($t_table = '', $t1y = '', $t1m = '', $adate = '')
    {
        sql_query(
            sprintf(
                "CREATE TEMPORARY TABLE p_group as SELECT alid, COUNT(allog) as count"
                . " FROM %s GROUP BY alip, alid ORDER BY null",
                $t_table
            )
        );
        $page_groups = sql_query(
            "SELECT alid, COUNT(count) as count FROM p_group GROUP BY alid ORDER BY count DESC"
        );
        $i = 1;
        while ($row2 = mysql_fetch_assoc($page_groups)) {
            $apid = $row2['alid'];
            $aphit = quickQuery(
                sprintf(
                    "SELECT COUNT(allog) as result FROM %s WHERE alid='%s' GROUP BY alid ORDER BY null LIMIT 1",
                    $t_table,
                    $row2['alid']
                )
            );
            $aphit1 = $row2['count'];
            $aphit2 = $i;
            $tp = sql_query(
                sprintf(
                    "SELECT * FROM %s WHERE LEFT(apdate, 7) = '%s-%s' and apid = '%s' LIMIT 1",
                    sql_table('plugin_analyze_page'),
                    $t1y,
                    $t1m,
                    $apid
                )
            );
            if (!mysql_num_rows($tp)) {
                $i_page .= sprintf(
                    "('%s', '%s', '%s', '%s', '%s'),",
                    $apid,
                    $adate,
                    $aphit,
                    $aphit1,
                    $aphit2
                );
                $i++;
                continue;
            }
            while ($resp = mysql_fetch_assoc($tp)) {
                $aphits = $resp['aphit'] + $aphit;
                $aphits1 = $resp['aphit1'] + $aphit1;
                sql_query(
                    sprintf(
                        "UPDATE %s SET aphit=%s, aphit1=%s, aphit2=%s, apdate='%s'"
                        . " WHERE LEFT(apdate, 7)='%s-%s' AND apid='%s'",
                        sql_table('plugin_analyze_page'),
                        $aphits,
                        $aphits1,
                        $aphit2,
                        $adate,
                        $t1y,
                        $t1m,
                        $apid
                    )
                );
            }
            $i++;
        }
        if ($i_page) {
            $i_page = substr($i_page, 0, -1);
            sql_query(
                sprintf(
                    "INSERT INTO %s VALUES %s",
                    sql_table('plugin_analyze_page'),
                    $i_page
                )
            );
        }
        return;
    }

    function UpPagePattern($t_table = '', $t1y = '', $t1m = '', $adate = '')
    {
        sql_query(
            sprintf(
                "CREATE TEMPORARY TABLE pagep as SELECT alid, alip, alreferer, COUNT(allog) as count"
                . " FROM %s"
                . " WHERE NOT(LEFT(alreferer, 3) = 'en?' or alreferer LIKE 'http%%' or alreferer = '')"
                . " GROUP BY alid, alreferer, alip ORDER BY null",
                $t_table
            )
        );
        $pagez = "SELECT alid, SUM(count) as count, COUNT(alip) as count1, alreferer"
            . " FROM pagep GROUP BY alid, alreferer ORDER BY null";
        $pagep = sql_query($pagez);
        while ($row3 = mysql_fetch_assoc($pagep)) {
            $appid = $row3['alid'];
            $apppage = $row3['alreferer'];
            $apphit = $row3['count'];
            $appvisit = $row3['count1'];
            $tp1 = sql_query(
                sprintf(
                    "SELECT * FROM %s WHERE LEFT(appdate, 7)='%s-%s' and appid='%s' and apppage='%s' LIMIT 1",
                    sql_table('plugin_analyze_page_pattern'),
                    $t1y,
                    $t1m,
                    $appid,
                    $apppage
                )
            );
            if (!mysql_num_rows($tp1)) {
                $i_pagep .= sprintf("('%s', '%s', '%s', '%s', '%s'),", $appid, $adate, $apppage, $apphit, $appvisit);
                continue;
            }
            while ($respp = mysql_fetch_assoc($tp1)) {
                sql_query(
                    sprintf(
                        "UPDATE %s SET apphit=%s, appvisit=%s, appdate='%s'"
                        . " WHERE LEFT(appdate, 7)='%s-%s' and appid='%s' and apppage='%s'",
                        sql_table('plugin_analyze_page_pattern'),
                        $respp['apphit'] + $apphit,
                        $respp['appvisit'] + $appvisit,
                        $adate,
                        $t1y,
                        $t1m,
                        $appid,
                        $apppage
                    )
                );
            }
        }
        if ($i_pagep) {
            $i_pagep = substr($i_pagep, 0, -1);
            sql_query(
                sprintf(
                    "INSERT INTO %s VALUES %s",
                    sql_table('plugin_analyze_page_pattern'),
                    $i_pagep
                )
            );
        }
        return;
    }

    function UpPageQuery($t_table = '', $t1y = '', $t1m = '', $adate = '')
    {
        sql_query(
            sprintf(
                "CREATE TEMPORARY TABLE pq_group as SELECT alid, alword, COUNT(alip) as count"
                . " FROM %s WHERE LEFT(alreferer, 3) = 'en?' GROUP BY alip, alid, alword ORDER BY null",
                $t_table
            )
        );
        $page_que = sql_query(
            "SELECT alid, alword, COUNT(alid) as count FROM pq_group GROUP BY alid, alword ORDER BY null"
        );
        while ($row4 = mysql_fetch_assoc($page_que)) {
            $apqid = $row4['alid'];
            $apqquery = $row4['alword'];
            $apqhit = quickQuery(
                sprintf(
                    "SELECT COUNT(alip) as result FROM %s"
                    . " WHERE LEFT(alreferer, 3)='en?' and alid='%s' and alword='%s' GROUP BY alid, alword",
                    $t_table,
                    $apqid,
                    $apqquery
                )
            );
            $apqvisit = $row4['count'];
            $tp4 = sql_query(
                sprintf(
                    "SELECT * FROM %s WHERE LEFT(apqdate, 7)='%s-%s' and apqid='%s' and apqquery='%s' LIMIT 1",
                    sql_table('plugin_analyze_page_query'),
                    $t1y,
                    $t1m,
                    $apqid,
                    $apqquery
                )
            );
            if (!mysql_num_rows($tp4)) {
                $i_pageq .= sprintf("('%s', '%s', '%s', '%s', '%s'),", $apqid, $adate, $apqquery, $apqhit, $apqvisit);
                continue;
            }
            while ($resp4 = mysql_fetch_assoc($tp4)) {
                $apqhit2 = $resp4['apqhit'] + $apqhit;
                $apqvisit2 = $resp4['apqvisit'] + $apqvisit;
                sql_query(
                    sprintf(
                        "UPDATE %s SET apqhit=%s, apqvisit=%s, apqdate='%s'"
                        . " WHERE LEFT(apqdate, 7) = '%s-%s' and apqid = '%s' and apqquery = '%s'",
                        sql_table('plugin_analyze_page_query'),
                        $apqhit2,
                        $apqvisit2,
                        $adate,
                        $t1y,
                        $t1m,
                        $apqid,
                        $apqquery
                    )
                );
            }
        }
        if ($i_pageq) {
            $i_pageq = substr($i_pageq, 0, -1);
            sql_query(
                sprintf(
                    "INSERT INTO %s VALUES %s", sql_table('plugin_analyze_page_query')
                    , $i_pageq
                )
            );
        }
        return;
    }

    function UpQuery($t_table = '', $t1y = '', $t1m = '', $adate = '')
    {
        sql_query(
            sprintf(
                "CREATE TEMPORARY TABLE q_group as SELECT alword, COUNT(alip) as count"
                . " FROM %s WHERE LEFT(alreferer, 3)='en?' GROUP BY alip, alword ORDER BY null",
                $t_table
            )
        );
        $page_que2 = sql_query(
            "SELECT alword, COUNT(count) as count FROM q_group GROUP BY alword ORDER BY null"
        );
        while ($row5 = mysql_fetch_assoc($page_que2)) {
            $aqhit = quickQuery(
                sprintf(
                    "SELECT COUNT(alip) as result FROM %s"
                    . " WHERE LEFT(alreferer, 3)='en?' and alword='%s' GROUP BY alword",
                    $t_table,
                    $row5['alword']
                )
            );
            $aqvisit = $row5['count'];
            $aqquery = $row5['alword'];
            $tp5 = sql_query(
                sprintf(
                    "SELECT * FROM %s WHERE LEFT(aqdate, 7)='%s-%s' and aqquery='%s' LIMIT 1",
                    sql_table('plugin_analyze_query'),
                    $t1y,
                    $t1m,
                    $aqquery
                )
            );
            if (!mysql_num_rows($tp5)) {
                $i_query .= sprintf("('%s', '%s', '%s', '%s'),", $aqquery, $adate, $aqhit, $aqvisit);
                continue;
            }
            while ($resp5 = mysql_fetch_assoc($tp5)) {
                $aqhit1 = $resp5['aqhit'] + $aqhit;
                $aqvisit1 = $resp5['aqvisit'] + $aqvisit;
                sql_query(
                    sprintf(
                        "UPDATE %s SET aqhit=%s, aqvisit=%s, aqdate='%s'"
                        . " WHERE LEFT(aqdate, 7)='%s-%s' and aqquery='%s'",
                        sql_table('plugin_analyze_query'),
                        $aqhit1,
                        $aqvisit1,
                        $adate,
                        $t1y,
                        $t1m,
                        $aqquery
                    )
                );
            }
        }
        if ($i_query) {
            sql_query(
                sprintf(
                    "INSERT INTO %s VALUES %s",
                    sql_table('plugin_analyze_query'),
                    substr($i_query, 0, -1)
                )
            );
        }
        return;
    }

    function UpEngine($t_table = '', $t1y = '', $t1m = '', $adate = '')
    {
        sql_query(
            sprintf(
                "CREATE TEMPORARY TABLE e_group as SELECT alreferer, COUNT(alip) as count"
                . " FROM %s"
                . " WHERE LEFT(alreferer, 3)='en?' GROUP BY alip, alreferer ORDER BY null",
                $t_table
            )
        );
        $page_engine = sql_query(
            "SELECT alreferer, COUNT(count) as count FROM e_group GROUP BY alreferer ORDER BY null"
        );
        while ($row6 = mysql_fetch_assoc($page_engine)) {
            $aevisit = $row6['count'];
            $aehit = quickQuery(
                sprintf(
                    "SELECT COUNT(alip) as result FROM %s"
                    . " WHERE LEFT(alreferer, 3)='en?' and alreferer='%s' GROUP BY alreferer ORDER BY null",
                    $t_table,
                    $row6['alreferer']
                )
            );
            $aeengine = $row6['alreferer'];
            $tp6 = sql_query(
                sprintf(
                    "SELECT * FROM %s WHERE LEFT(aedate, 7)='%s-%s' and aeengine='%s' LIMIT 1",
                    sql_table('plugin_analyze_engine'),
                    $t1y,
                    $t1m,
                    $aeengine
                )
            );
            if (!mysql_num_rows($tp6)) {
                $i_engine .= sprintf("('%s', '%s', '%s', '%s'),", $aeengine, $adate, $aehit, $aevisit);
                continue;
            }
            while ($resp6 = mysql_fetch_assoc($tp6)) {
                $aehit1 = $resp6['aehit'] + $aehit;
                $aevisit1 = $resp6['aevisit'] + $aevisit;
                sql_query(
                    sprintf(
                        "UPDATE %s SET aehit=%s, aevisit=%s, aedate='%s'"
                        . " WHERE LEFT(aedate, 7)='%s-%s' and aeengine='%s'",
                        sql_table('plugin_analyze_engine'),
                        $aehit1,
                        $aevisit1,
                        $adate,
                        $t1y,
                        $t1m,
                        $aeengine
                    )
                );
            }
        }
        if ($i_engine) {
            $i_engine = substr($i_engine, 0, -1);
            sql_query(
                sprintf(
                    "INSERT INTO %s VALUES %s",
                    sql_table('plugin_analyze_engine'),
                    $i_engine
                )
            );
        }
        return;
    }

    function UpReferer($t_table = '', $t1y = '', $t1m = '', $adate = '')
    {
        sql_query(
            sprintf(
                "CREATE TEMPORARY TABLE r_group as SELECT alreferer, COUNT(alip) as count"
                . " FROM %s WHERE LEFT(alreferer, 4)='http' GROUP BY alip, alreferer ORDER BY null",
                $t_table
            )
        );
        $p_referer = sql_query(
            "SELECT alreferer, COUNT(count) as count FROM r_group GROUP BY alreferer ORDER BY null"
        );
        while ($row7 = mysql_fetch_assoc($p_referer)) {
            $arhit = quickQuery(
                sprintf(
                    "SELECT COUNT(alip) as result FROM %s"
                    . " WHERE LEFT(alreferer, 4)='http' and alreferer='%s' GROUP BY alreferer ORDER BY null",
                    $t_table,
                    $row7['alreferer']
                )
            );
            $arvisit = $row7['count'];
            $arreferer = $row7['alreferer'];
            $tp7 = sql_query(
                sprintf(
                    "SELECT * FROM %s WHERE LEFT(ardate, 7) = '%s-%s' and arreferer = '%s' LIMIT 1",
                    sql_table('plugin_analyze_referer'),
                    $t1y,
                    $t1m,
                    $arreferer
                )
            );
            if (!mysql_num_rows($tp7)) {
                $i_referer .= sprintf("('%s', '%s', '%s', '%s'),", $arreferer, $adate, $arhit, $arvisit);
                continue;
            }
            while ($resp7 = mysql_fetch_assoc($tp7)) {
                $arhit1 = $resp7['arhit'] + $arhit;
                $arvisit1 = $resp7['arvisit'] + $arvisit;
                sql_query(
                    sprintf(
                        "UPDATE %s SET arhit = %s, arvisit = %s, ardate = '%s'"
                        . " WHERE LEFT(ardate, 7) = '%s-%s' and arreferer = '%s'",
                        sql_table('plugin_analyze_referer'),
                        $arhit1,
                        $arvisit1,
                        $adate,
                        $t1y,
                        $t1m,
                        $arreferer
                    )
                );
            }
        }
        if ($i_referer) {
            $i_referer = substr($i_referer, 0, -1);
            sql_query(
                sprintf(
                    "INSERT INTO %s VALUES %s", sql_table('plugin_analyze_referer'),
                    $i_referer
                )
            );
        }
        return;
    }

    function UpHost($t_table = '', $t1y = '', $t1m = '', $adate = '')
    {
        sql_query(
            sprintf(
                "CREATE TEMPORARY TABLE h_group as SELECT"
                . " CASE SUBSTRING_INDEX(alip, '.', -1)"
                . " WHEN 'com' THEN SUBSTRING_INDEX(alip, '.', -2)"
                . " WHEN 'net' THEN SUBSTRING_INDEX(alip, '.', -2)"
                . " ELSE SUBSTRING_INDEX(alip, '.', -3)"
                . " END as alip1,"
                . " COUNT(allog) as count"
                . " FROM %s GROUP BY alip ORDER BY null",
                $t_table
            )
        );
        $p_hos = "SELECT alip1, COUNT(count) as count, SUM(count) as count1"
            . " FROM h_group GROUP BY alip1 ORDER BY null";
        $p_host = sql_query($p_hos);
        while ($row8 = mysql_fetch_assoc($p_host)) {
            $ahhhit = $row8['count1'];
            $ahvisit = $row8['count'];
            $ahhost = $row8['alip1'];
            $tp8 = sql_query(
                sprintf(
                    "SELECT * FROM %s WHERE LEFT(ahdate, 7) = '%s-%s' and ahhost = '%s' LIMIT 1",
                    sql_table('plugin_analyze_host'),
                    $t1y,
                    $t1m,
                    $ahhost
                )
            );
            if (!mysql_num_rows($tp8)) {
                $i_host .= sprintf("('%s', '%s', '%s', '%s'),", $ahhost, $adate, $ahhhit, $ahvisit);
                continue;
            }
            while ($resp8 = mysql_fetch_assoc($tp8)) {
                $ahhhit1 = $resp8['ahhit'] + $ahhhit;
                $ahvisit1 = $resp8['ahvisit'] + $ahvisit;
                sql_query(
                    sprintf(
                        "UPDATE %s SET ahhit = %s, ahvisit = %s, ahdate = '%s'"
                        . " WHERE LEFT(ahdate, 7) = '%s-%s' and ahhost = '%s'",
                        sql_table('plugin_analyze_host'),
                        $ahhhit1,
                        $ahvisit1,
                        $adate,
                        $t1y,
                        $t1m,
                        $ahhost
                    )
                );
            }
        }
        if ($i_host) {
            $i_host = substr($i_host, 0, -1);
            sql_query(
                sprintf(
                    "INSERT INTO %s VALUES %s",
                    sql_table('plugin_analyze_host'),
                    $i_host
                )
            );
        }
        return;
    }

    function SendMailMonth($t1y = '', $t1m = '')
    {
        $result = sql_query(
            sprintf(
                "SELECT"
                . " LEFT(ahdate, 7) as ahdate,"
                . " SUM(ahvisit) as ahvisit,"
                . " SUM(ahhit) as ahhit,"
                . " SUM(ahlevel1) as ahlevel1,"
                . " SUM(ahlevel2) as ahlevel2,"
                . " SUM(ahlevel3) as ahlevel3,"
                . " SUM(ahlevel4) as ahlevel4,"
                . " SUM(ahlevel5) as ahlevel5,"
                . " SUM(ahrobot) as ahrobot"
                . " FROM %s"
                . " WHERE LEFT(ahdate, 7)='%s-%s' GROUP BY ahdate",
                sql_table('plugin_analyze_hit'),
                $t1y,
                $t1m
            )
        );
        while ($row = mysql_fetch_assoc($result)) {
            $me1 = "[Monthly Access]\n\n";
            $me1 .= "Hit : " . number_format($row['ahvisit']) . "\n";
            $me1 .= "PV : " . number_format($row['$ahhit']) . "\n\n";
            $me1 .= "lev.1 : " . number_format($row['$ahlevel1']) . "\n";
            $me1 .= "lev.2 : " . number_format($row['$ahlevel2']) . "\n";
            $me1 .= "lev.3 : " . number_format($row['$ahlevel3']) . "\n";
            $me1 .= "lev.4 : " . number_format($row['$ahlevel4']) . "\n";
            $me1 .= "lev.5 : " . number_format($row['$ahlevel5']) . "\n\n\n";
        }
        return $me1;
    }

    function SendMail($adate = '', $message = '')
    {
        global $CONF;
        @mb_language('Ja');
        @mb_internal_encoding('EUC-JP');
        $subject = 'Access Analyze ' . $adate;
        $m = $CONF['SiteName'] . "\n";
        $m .= $CONF['IndexURL'] . "\n\n";
        $m .= $adate . " (since " . $this->getOption('alz_datestart') . ")\n\n\n";
        $m .= $message;
        $m .= "Read more.\n";
        $m .= $CONF['PluginURL'] . "analyze/\n\n\n";
        $m .= "-----------------------------\n";
        $m .= "Powered by NP_Analyze\n";
        $m .= $this->getURL() . "\n";
        $mailfrom = 'From:' . $CONF['AdminEmail'];
        @mb_send_mail($CONF['AdminEmail'], $subject, $m, $mailfrom);
    }

    function ChangeDate($t_table = '', $t1y = '', $t1m = '', $mcsv = '', $adate = '', $t0m = '', $me2 = '')
    {
        $this->UpPage($t_table, $t1y, $t1m, $adate);
        $this->UpQuery($t_table, $t1y, $t1m, $adate);
        $this->UpEngine($t_table, $t1y, $t1m, $adate);
        $this->UpReferer($t_table, $t1y, $t1m, $adate);
        $this->UpHost($t_table, $t1y, $t1m, $adate);
        $this->UpPageQuery($t_table, $t1y, $t1m, $adate);
        $this->UpPagePattern($t_table, $t1y, $t1m, $adate);
        // Monthly
        if ($t0m != $t1m) {
            $d_count = $this->getOption('alz_d_count');
            if ($d_count > 0) {
                $this->DatabaseDelCount($t1y, $t1m, $d_count);
            }
        }
        $this->AccessLog($mcsv, $t_table);
        $this->TempChange($mcsv);
        $t_1s = quickQuery(
            sprintf(
                "SELECT DATE_FORMAT(aldate,'%%Y-%%m-%%d') as result FROM %s ORDER BY allog ASC LIMIT 1",
                $t_table
            )
        );
        $h_time = quickQuery(
            sprintf(
                "SELECT ahdate as result FROM %s ORDER BY ahdate DESC LIMIT 1",
                sql_table('plugin_analyze_host')
            )
        );
        if ($h_time == $t_1s) {
            sql_query("DROP TABLE " . $t_table);
        }
        return;
    }

    function event_PreSkinParse(&$skindata)
    {
        global $CONF, $blogid, $catid, $itemid, $member, $memberid, $archive, $manager, $archivelist, $query;
        $blogid = (int)$blogid;
        $catid = (int)$catid;
        $itemid = (int)$itemid;
        $archivelist = (int)$archivelist;
        if ($member->isLoggedIn() && $this->getOption('alz_loggedin') === 'no') {
            return;
        }
        $mact = $manager->pluginInstalled('NP_MultipleCategories');
        if ($mact) {
            global $subcatid;
            $subcatid = (int)$subcatid;
        }
        $icon = substr($_SERVER['REQUEST_URI'], -4, 4);
        switch (TRUE) {
            case ($itemid):
                $alid = 'i?' . $itemid . '?';
                break;
            case ($skindata['type'] === 'error'):
                $alid = 'e?' . $itemid . '?';
                break;
            case ($skindata['type'] === 'imagepopup'):
                $alid = 'im?';
                break;
            case ($memberid):
                $alid = 'm?' . $memberid . '?';
                break;
            case ($skindata['type'] === 'search'):
                $order = (_CHARSET === 'EUC-JP') ? 'EUC-JP, UTF-8' : 'UTF-8, EUC-JP';
                $post = mb_convert_encoding($query, _CHARSET, $order . ', JIS, SJIS, ASCII');
                $alid = 's?' . $blogid . '?' . $post;
                break;
            case ($archive):
                $alid = 'a?' . $archive . '/' . $blogid . '?';
                break;
            case ($archivelist):
                $alid = 'l?' . $archivelist . '?';
                break;
            case ($subcatid):
                $alid = 'sb?' . $subcatid . '?';
                break;
            case ($catid):
                $alid = 'c?' . $catid . '?';
                break;
            case ($icon === '.ico'):
                return;
            default:
                foreach ($skindata['skin'] as $skins => $id) {
                    if ($skins === 'contentType') {
                        $skin = $id;
                    }
                }
                switch ($skin) {
                    case 'text/html':
                        if ($manager->pluginInstalled('NP_MultiTags')) {
                            $que =  quickQuery(
                                sprintf(
                                    "SELECT odef as result FROM %s, %s"
                                    . " WHERE opid=pid and oname='tag_query' and pfile='NP_MultiTags'",
                                    sql_table('plugin'),
                                    sql_table('plugin_option_desc')
                                )
                            );
                            switch ($CONF['URLMode']) {
                                case 'normal':
                                    $tag = $_GET[$que];
                                    break;
                                default:
                                    $lq = explode('/' . $que . '/', $_SERVER['REQUEST_URI']);
                                    $lr = explode('/', $lq[1]);
                                    $ls = explode('?', $lr[0]);
                                    $tag = $ls[0];
                            }
                        }
                        switch ($tag) {
                            case '':
                                $alid = 'b?' . $blogid . '?' . $_SERVER['REQUEST_URI'];
                                break;
                            default:
                                $alid = 'mt?' . $blogid . '?' . $tag;
                        }
                        break;
                    default:
                        $alid = 'r?';
                        break;
                }
        }
        $alip = @gethostbyaddr($_SERVER['REMOTE_ADDR']);
        $alip = addslashes($alip);
        $aldate = date("Y-m-d H:i:s", time() + ($this->getOption('alz_time_d') * 3600));
        $t0y = date("Y", strtotime($aldate));
        $t0m = date("m", strtotime($aldate));
        $t0d = date("d", strtotime($aldate));
        // Referer
        if ($_SERVER['HTTP_REFERER']) {
            $alref1 = quickQuery(
                sprintf(
                    "SELECT alid as result FROM %s WHERE alip='%s' ORDER BY allog DESC LIMIT 1",
                    sql_table('plugin_analyze_log'),
                    $alip
                )
            );
        }
        switch (TRUE) {
            case (!$_SERVER['HTTP_REFERER']):
                $alreferer = '';
                break;
            case ($alref1):
                if ($alid == $alref1) {
                    return;
                }
                $alreferer = $alref1;
                break;
            default:
                // Search engines (thanks for Osamu Higuchi. http://www.higuchi.com/item/150 )
                $ref_a = explode('//', $_SERVER['HTTP_REFERER'], 2);
                $ref_b = explode('/', $ref_a[1], 2);
                $search_q = explode('?', $ref_b[1]);
                $search_que = explode('&', $search_q[1]);
                $s_que = array();
                foreach ($search_que as $search_q) {
                    list($col, $val) = split('=', $search_q);
                    $s_que[$col] = urldecode($val);
                }
                $engines = array(
                    "216\.239\.[0-9]+\.10[0-9]" => 'Google',
                    "images\.google\." => 'GoogleIMG',
                    "\.google\." => 'Google',
                    "search-kids\.yahoo\." => 'Yahoo!kids',
                    "search\.yahoo\." => 'Yahoo!',
                    "search\.[a-zA-Z0-9_]+\.msn\." => 'msn',
                    "search\.msn\." => 'msn',
                    "goo\.ne\.jp$" => 'goo',
                    "infobee\.ne\.jp$" => 'goo',
                    "infoseek\.co\.jp$" => 'infoseek',
                    "\.excite\.co\.jp$" => 'Excite',
                    "search\.fresheye\.com$" => 'freshEYE',
                    "search\.nifty\.com" => '@nifty',
                    "search\.biglobe\.ne\.jp$" => 'BIGLOBE',
                    "ask\.jp$" => 'ask',
                    "ask\.com$" => 'ask',
                    "\.alltheweb\.com$" => 'AlltheWeb',
                    "\.alexa.com$" => 'Alexa',
                    "\.attens.net$" => 'AT&T',
                    "search\.naver\." => 'NAVER'
                );
                foreach ($engines as $engine => $engin) {
                    if (preg_match("/$engine/", $ref_b[0])) {
                        $alref = $engin;
                    }
                }
                $enco = _CHARSET;
                $s_encode = 'EUC-JP, SJIS, UTF-8, JIS, ASCII';
                switch ($alref) {
                    case "GoogleIMG":
                        $al_f = "Google";
                        break;
                    case "Yahoo!kids":
                        $al_f = "Yahoo!";
                        break;
                    default:
                        $al_f = $alref;
                }
                switch ($al_f) {
                    case "Google":
                        $alword = mb_convert_encoding($s_que['q'] . $s_que['as_q'] . $s_que['as_epq'], $enco, 'UTF-8, SJIS, EUC-JP, JIS, ASCII');
                        break;
                    case "Yahoo!":
                        $s_encode = ($s_que['ei']) ? $s_que['ei'] : $s_encode;
                        $alword = mb_convert_encoding($s_que['p'] . $s_que['va'], $enco, $s_encode);
                        break;
                    case "msn":
                        $alword = mb_convert_encoding($s_que['q'], $enco, 'UTF-8, SJIS, EUC-JP, JIS, ASCII');
                        break;
                    case "goo":
                        $alword = mb_convert_encoding($s_que['MT'], $enco, $s_encode);
                        break;
                    case "infoseek":
                        $s_encode = ($s_que['enc']) ? $s_que['enc'] : $s_encode;
                        $alword = mb_convert_encoding($s_que['qt'], $enco, $s_encode);
                        break;
                    case "Excite":
                        $alword = mb_convert_encoding($s_que['search'] . $s_que['s'], $enco, 'SJIS, UTF-8, EUC-JP, JIS, ASCII');
                        break;
                    case "freshEYE":
                        $alword = mb_convert_encoding($s_que['kw'], $enco, $s_encode);
                        break;
                    case "@nifty":
                        $alword = mb_convert_encoding($s_que['Text'], $enco, 'EUC-JP, JIS, SJIS, UTF-8, ASCII');
                        break;
                    case "BIGLOBE":
                        $alword = mb_convert_encoding($s_que['q'], $enco, 'UTF-8, SJIS, EUC-JP, JIS, ASCII');
                        break;
                    case "ask":
                        $alword = mb_convert_encoding($s_que['q'] . $s_que['query'], $enco, 'UTF-8');
                        break;
                    case "AlltheWeb":
                        $s_encode = ($s_que['cs']) ? $s_que['cs'] : $s_encode;
                        $alword = mb_convert_encoding($s_que['q'], $enco, $s_encode);
                        break;
                    case "NAVER":
                        $alword = mb_convert_encoding($s_que['query'], $enco, $s_encode);
                        break;
                }
                $alreferer = ($alword) ? 'en?' . $alref . '?' : $_SERVER['HTTP_REFERER'];
        }

        // Count
        $time1 = quickQuery(
            sprintf(
                "SELECT aldate as result FROM %s ORDER BY allog ASC LIMIT 1",
                sql_table('plugin_analyze_log')
            )
        );
        $t1y = date("Y", strtotime($time1));
        $t1m = date("m", strtotime($time1));
        $t1d = date("d", strtotime($time1));
        $mcsv = $t1y . '-' . $t1m;
        switch (TRUE) {
            case ($t0y == $t1y && $t0m == $t1m && $t0d == $t1d):
                $this->orDie($alid, $aldate, $alip, $alreferer, $alword);
                return;
            default:
                $adate = $t1y . '-' . $t1m . '-' . $t1d;

                // plugin_analyze_hit
                $ip_gri = " FROM " . sql_table('plugin_analyze_log') . " WHERE NOT(" . $this->ExRobo('alip') . "alip = '') ";
                $ip_grou = "SELECT alip, COUNT(allog) as count" . $ip_gri . "GROUP BY alip ORDER BY null";
                sql_query(
                    sprintf(
                        "CREATE TEMPORARY TABLE ipgroup as SELECT alid, COUNT(allog) as count %s"
                        . " GROUP BY alip, alid ORDER BY null",
                        $ip_gri
                    )
                );
                $ip_gri = "SELECT alip" . $ip_gri;
                $ahhit = mysql_num_rows(sql_query($ip_gri));
                $hit_range = explode('/', $this->getOption('alz_hit_range'));
                $today_h = mysql_num_rows(
                    sql_query(
                        "SELECT * FROM " . sql_table('plugin_analyze_log')
                    )
                );
                $robot_t = $today_h - $this->Countting('today2');
                $ip_group = sql_query($ip_grou);
                $ahvisit = mysql_num_rows($ip_group);
                while ($row = mysql_fetch_assoc($ip_group)) {
                    switch (TRUE) {
                        case ($row['count'] > $hit_range[3]):
                            $ahlevel5++;
                            break;
                        case ($row['count'] > $hit_range[2]):
                            $ahlevel4++;
                            break;
                        case ($row['count'] > $hit_range[1]):
                            $ahlevel3++;
                            break;
                        case ($row['count'] > $hit_range[0]):
                            $ahlevel2++;
                            break;
                        default:
                            $ahlevel1++;
                    }
                }
                $c_time = quickQuery(
                    sprintf(
                        "SELECT ahdate as result FROM %s ORDER BY ahdate DESC LIMIT 1",
                        sql_table('plugin_analyze_hit')
                    )
                );
                if ($c_time == $adate) {
                    $this->orDie($alid, $aldate, $alip, $alreferer, $alword);
                    return;
                }
                sql_query(
                    sprintf(
                        "INSERT INTO %s"
                        . " (ahdate, ahvisit, ahhit, ahlevel1, ahlevel2, ahlevel3, ahlevel4, ahlevel5, ahrobot)"
                        . " VALUES ('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')",
                        sql_table('plugin_analyze_hit'),
                        $adate,
                        $ahvisit,
                        $ahhit,
                        $ahlevel1,
                        $ahlevel2,
                        $ahlevel3,
                        $ahlevel4,
                        $ahlevel5,
                        $robot_t
                    )
                );
                mysql_free_result($ip_group);
                $result = sql_query(
                    sprintf(
                        "SELECT * FROM %s WHERE ahdate = '2000-01-01' LIMIT 1",
                        sql_table('plugin_analyze_hit')
                    )
                );
                while ($res = mysql_fetch_assoc($result)) {
                    $ahvisit0 = $res['ahvisit'] + $ahvisit;
                    $ahhit0 = $res['ahhit'] + $ahhit;
                    $ahlevel10 = $res['ahlevel1'] + $ahlevel1;
                    $ahlevel20 = $res['ahlevel2'] + $ahlevel2;
                    $ahlevel30 = $res['ahlevel3'] + $ahlevel3;
                    $ahlevel40 = $res['ahlevel4'] + $ahlevel4;
                    $ahlevel50 = $res['ahlevel5'] + $ahlevel5;
                    $ahrobot0 = $res['ahrobot'] + $robot_t;
                }
                sql_query(
                    sprintf(
                        "UPDATE %s"
                        . " SET ahvisit=%s,ahhit=%s,ahlevel1=%s,ahlevel2=%s,ahlevel3=%s,ahlevel4=%s,ahlevel5=%s,ahrobot=%s"
                        . " WHERE ahdate = '2000-01-01' LIMIT 1",
                        sql_table('plugin_analyze_hit'),
                        $ahvisit0,
                        $ahhit0,
                        $ahlevel10,
                        $ahlevel20,
                        $ahlevel30,
                        $ahlevel40,
                        $ahlevel50,
                        $ahrobot0
                    )
                );
                mysql_free_result($result);

                // plugin_analyze_robot Main
                $ng_gr = sql_query(
                    sprintf(
                        "SELECT anip, antitle FROM %s WHERE antitle != 'rss'",
                        sql_table('plugin_analyze_ng')
                    )
                );
                $robo1 = array();
                while (list($anip, $antitle) = mysql_fetch_row($ng_gr)) {
                    $robo1[$anip] = $antitle;
                }
                mysql_free_result($ng_gr);
                foreach ($robo1 as $robo1[0] => $robo1[1]) {
                    $arohit = 0;
                    $arovisit = 0;
                    $aroengine = $robo1[1];
                    $ip_gr = sql_query(
                        sprintf(
                            "SELECT alip, COUNT(allog) as count FROM %s WHERE alip LIKE '%%%s%%' GROUP BY alip ORDER BY null",
                            sql_table('plugin_analyze_log'),
                            $robo1[0]
                        )
                    );
                    while ($row = mysql_fetch_assoc($ip_gr)) {
                        $arohit = $arohit + $row['count'];
                        $arovisit++;
                    }
                    mysql_free_result($ip_gr);
                    $ro = sql_query(
                        sprintf(
                            "SELECT arohit, arovisit FROM %s WHERE LEFT(arodate, 7)='%s-%s' and aroengine='%s' LIMIT 1",
                            sql_table('plugin_analyze_robot'),
                            $t1y,
                            $t1m,
                            $aroengine
                        )
                    );
                    $ro1 = mysql_num_rows($ro);
                    switch (TRUE) {
                        case (!$arovisit):
                            break;
                        case ($ro1):
                            while ($resr = mysql_fetch_assoc($ro)) {
                                $arohit0 = $resr['arohit'] + $arohit;
                                $arovisit0 = $resr['arovisit'] + $arovisit;
                                sql_query(
                                    sprintf(
                                        "UPDATE %s SET arohit=%s, arovisit=%s, arodate='%s'"
                                        . " WHERE LEFT(arodate, 7) = '%s-%s' and aroengine = '%s' LIMIT 1",
                                        sql_table('plugin_analyze_robot'),
                                        $arohit0,
                                        $arovisit0,
                                        $adate,
                                        $t1y,
                                        $t1m,
                                        $aroengine
                                    )
                                );
                            }
                            mysql_free_result($ro);
                            break;
                        default:
                            sql_query(
                                sprintf(
                                    "INSERT INTO %s VALUES ('%s', '%s', '%s', '%s')",
                                    sql_table('plugin_analyze_robot'),
                                    $aroengine,
                                    $adate,
                                    $arohit,
                                    $arovisit
                                )
                            );
                    }
                }

                // plugin_analyze_robot RSS
                $rss_g = quickQuery(
                    sprintf(
                        "SELECT antitle as result FROM %s WHERE antitle = 'rss' LIMIT 1",
                        sql_table('plugin_analyze_ng')
                    )
                );
                if ($rss_g) {
                    $rss_gr = quickQuery(
                        sprintf(
                            "SELECT COUNT(allog) as result FROM %s WHERE alid = 'r?' GROUP BY alid ORDER BY null",
                            sql_table('plugin_analyze_log')
                        )
                    );
                    sql_query(
                        sprintf(
                            "CREATE TEMPORARY TABLE rss_group as SELECT alip, COUNT(allog) as count"
                            . " FROM %s WHERE alid = 'r?' GROUP BY alip, alid ORDER BY null",
                            sql_table('plugin_analyze_log')
                        )
                    );
                    $arovisit = mysql_num_rows(
                        sql_query(
                            "SELECT COUNT(count) as count FROM rss_group GROUP BY alip ORDER BY null"
                        )
                    );
                    $roa = sql_query(
                        sprintf(
                            "SELECT arohit, arovisit FROM %s WHERE LEFT(arodate, 7)='%s-%s' and aroengine='XML-RSS' LIMIT 1",
                            sql_table('plugin_analyze_robot'),
                            $t1y,
                            $t1m
                        )
                    );
                    $roa1 = mysql_num_rows($roa);
                    switch (TRUE) {
                        case (!$arovisit):
                            break;
                        case ($roa1):
                            while ($resr = mysql_fetch_assoc($roa)) {
                                $arohit0 = $resr['arohit'] + $rss_gr;
                                $arovisit0 = $resr['arovisit'] + $arovisit;
                                sql_query(
                                    sprintf(
                                        "UPDATE %s SET arohit = %s, arovisit = %s, arodate = '%s'"
                                        . " WHERE LEFT(arodate, 7)='%s-%s' and aroengine='XML-RSS' LIMIT 1",
                                        sql_table('plugin_analyze_robot'),
                                        $arohit0,
                                        $arovisit0,
                                        $adate,
                                        $t1y,
                                        $t1m
                                    )
                                );
                            }
                            mysql_free_result($roa);
                            break;
                        default:
                            sql_query(
                                sprintf(
                                    "INSERT INTO %s VALUES ('XML-RSS', '%s', '%s', '%s')",
                                    sql_table('plugin_analyze_robot'),
                                    $adate,
                                    $rss_gr,
                                    $arovisit
                                )
                            );
                    }
                }

                // LogTable change
                $t_table = sql_table('plugin_analyze_templog');
                if ($this->TableExists($t_table)) {
                    sql_query("DROP TABLE " . $t_table);
                }
                sql_query(
                    sprintf(
                        "CREATE TABLE %s as SELECT * FROM %s",
                        $t_table,
                        sql_table('plugin_analyze_log')
                    )
                );
                sql_query(
                    sprintf(
                        "DELETE FROM %s WHERE %salip = ''",
                        $t_table,
                        $this->ExRobo('alip', '2')
                    )
                );
                sql_query("OPTIMIZE TABLE " . $t_table);
                sql_query("TRUNCATE TABLE " . sql_table('plugin_analyze_log'));

                // E-mail dairy report
                if ($this->getOption('alz_report') === 'yes' || $this->getOption('alz_report_m') === 'yes') {
                    $me1 = "[Today's Access]\n\n";
                    $me1 .= "Hit : " . number_format($ahvisit) . "\n";
                    $me1 .= "PV : " . number_format($ahhit) . "\n\n";
                    $me1 .= "lev.1 : " . number_format($ahlevel1) . "\n";
                    $me1 .= "lev.2 : " . number_format($ahlevel2) . "\n";
                    $me1 .= "lev.3 : " . number_format($ahlevel3) . "\n";
                    $me1 .= "lev.4 : " . number_format($ahlevel4) . "\n";
                    $me1 .= "lev.5 : " . number_format($ahlevel5) . "\n\n\n";
                    $me2 = "[Total Access]\n\n";
                    $me2 .= "Hit : " . number_format($ahvisit0) . "\n";
                    $me2 .= "PV : " . number_format($ahhit0) . "\n\n";
                    $me2 .= "lev.1 : " . number_format($ahlevel10) . "\n";
                    $me2 .= "lev.2 : " . number_format($ahlevel20) . "\n";
                    $me2 .= "lev.3 : " . number_format($ahlevel30) . "\n";
                    $me2 .= "lev.4 : " . number_format($ahlevel40) . "\n";
                    $me2 .= "lev.5 : " . number_format($ahlevel50) . "\n\n\n";
                    if ($t0m != $t1m && $this->getOption('alz_report_m') === 'yes') {
                        $me0 = SendMailMonth($t1y, $t1m);
                    }
                    $this->SendMail($adate, $me1 . $me0 . $me2);
                }

                $this->ChangeDate($t_table, $t1y, $t1m, $mcsv, $adate, $t0m, $me2);
                $this->orDie($alid, $aldate, $alip, $alreferer, $alword, $copyright);
        }
        return;
    }

    function doTemplateVar(&$item)
    {
        echo $this->DirectLink($item->itemid);
    }

    function doSkinVar($skinType, $type = '', $id = '', $cat = '', $m1 = '', $m2 = '', $m3 = '', $m4 = '', $m5 = '')
    {
        global $itemid, $blogid, $catid;
        $blogid = (int)$blogid;
        $catid = (int)$catid;
        $itemid = (int)$itemid;
        $m5 = (int)$m5;
        if ($m2 == 1 || $m2 == 3) {
            $qdate = date("Y-m", strtotime("-1 month", strtotime(date("Y-m") . '-01')));
        } else {
            $qdate = date("Y-m");
        }
        switch (TRUE) {
            case ($m1 === 'tr'):
                $m1a = 'td';
                break;
            case (!$m1):
                $m1 = 'div';
                $m1a = 'span';
                break;
            default:
                $m1a = 'span';
        }
        if ($id > 100) {
            $id = 100;
        }
        $i = 1;
        switch (TRUE) {
            case ($type === 'count' && $id === 'all'):
                echo $this->Countting($id, $cat);
                break;
            case ($type === 'count'):
                echo $this->ShowCountting($id, $cat);
                //			echo $this->Countting($id, $cat);
                break;
            case ($type === 'hit'):
                switch (TRUE) {
                    case ($m4):
                        sql_query(
                            sprintf(
                                "CREATE TEMPORARY TABLE %s as SELECT SUBSTRING_INDEX(SUBSTRING(apid, 3), '?', 1) as apid0, apid, aphit1, apdate"
                                . " FROM %s WHERE apid LIKE '%%i?%%'",
                                sql_table('t_table'),
                                sql_table('plugin_analyze_page')
                            )
                        );
                        $q1 = " left join " . sql_table('item') . " on inumber = apid0";
                    case ($m4 === 'b'):
                        $q2 = "' and iblog = '" . $blogid;
                        break;
                    case ($m4 === 'c'):
                        $q2 = "' and icat = '" . $catid;
                }
                $table = ($m4) ? 't_table' : 'plugin_analyze_page';
                $query = sprintf(
                    "SELECT apid, aphit1 FROM %s%s WHERE LEFT(apdate, 7) = '%s",
                    sql_table($table),
                    $q1,
                    $qdate
                );
                if ($m3 && !$m4) {
                    $query .= "' and apid LIKE '%" . $m3 . "?%";
                }
                $query .= $q2 . "' ORDER BY aphit1 DESC LIMIT 0, " . $id;
                $tp1 = sql_query($query);
                while ($row = mysql_fetch_assoc($tp1)) {
                    $apid = explode('?', $row['apid'], 3);
                    $apid1 = $this->IdChange($apid[0], $apid[1], '', '+', $m5);
                    echo '
<' . $m1 . ' class="analyze_top">';
                    if ($m1 !== 'li' && $cat != 2 && $cat != 3) {
                        echo '
<' . $m1a . ' class="analyze_num">' . $i . '.</' . $m1a . '>';
                    }
                    echo '<' . $m1a . ' class="analyze_body">' . $apid1 . '</' . $m1a . '>';
                    switch (TRUE) {
                        case ($m2 > 1):
                            if ($apid[0] === 'i') {
                                $dr = $this->DirectLink($apid[1]);
                            }
                            echo '<' . $m1a . '>' . $dr . '</' . $m1a . '>';
                    }
                    if ($cat != 1 && $cat != 3) {
                        echo '<' . $m1a . ' class="analyze_count" style="text-align: right;"> ' . number_format($row['aphit1']) . '</' . $m1a . '>';
                    }
                    echo '
</' . $m1 . '>';
                    $i++;
                }
                mysql_free_result($tp1);
                break;
            case ($type === 'query'):
                $que = ($m3 === 'item') ? 'i?' . $itemid : $m3;
                $query = sprintf(
                    "SELECT apqid, apqquery, apqvisit FROM %s WHERE LEFT(apqdate, 7) = '%s",
                    sql_table('plugin_analyze_page_query'),
                    $qdate
                );
                if ($m3) {
                    $query .= "' and apqid LIKE '%" . $que . "?%";
                }
                $query .= "' ORDER BY apqvisit DESC LIMIT 0, " . $id;
                $tp1 = sql_query($query);
                while ($row = mysql_fetch_assoc($tp1)) {
                    $apid = explode('?', $row['apqid'], 3);
                    $apid1 = $this->IdChange($apid[0], $apid[1], '', '+', $m5);
                    echo '
<' . $m1 . ' class="analyze_top">';
                    if ($m1 !== 'li' && $cat != 2 && $cat != 3) {
                        echo '
<' . $m1a . ' class="analyze_num">' . $i . '.</' . $m1a . '>';
                    }
                    if ($m3 !== 'item') {
                        echo '<' . $m1a . ' class="analyze_body">' . $apid1 . '</' . $m1a . '>';
                    }
                    switch (TRUE) {
                        case ($m3 !== 'item' && $m2 > 1):
                            if ($apid[0] === 'i') {
                                $dr = $this->DirectLink($apid[1]);
                            }
                            echo '<' . $m1a . '>' . $dr . '</' . $m1a . '>';
                    }
                    echo '<' . $m1a . ' class="analyze_body"> ' . htmlspecialchars($row['apqquery']) . '</' . $m1a . '>';
                    if ($cat != 1 && $cat != 3) {
                        echo '<' . $m1a . ' class="analyze_count" style="text-align: right;"> ' . number_format($row['apqvisit']) . '</' . $m1a . '>';
                    }
                    echo '
</' . $m1 . '>';
                    $i++;
                }
                mysql_free_result($tp1);
                break;
            case ($type === 'pattern' && $itemid):
                $data = ($m4) ? 'appid' : 'apppage';
                $data0 = ($m4) ? 'apppage' : 'appid';
                $query = sprintf(
                    "SELECT appid, apppage, appvisit FROM %s WHERE LEFT(appdate, 7) = '%s",
                    sql_table('plugin_analyze_page_pattern'),
                    $qdate
                );
                $query .= "' and " . $data . " LIKE '%i?" . $itemid . "?%";
                if ($m3) {
                    $query .= "' and " . $data0 . " LIKE '%" . $m3 . "?%";
                }
                $query .= "' ORDER BY appvisit DESC LIMIT 0, " . $id;
                $tp1 = sql_query($query);
                while ($row = mysql_fetch_assoc($tp1)) {
                    $apid = explode('?', $row['appid'], 3);
                    $apid1 = $this->IdChange($apid[0], $apid[1], $apid[2], '+', $m5);
                    $apid2 = explode('?', $row['apppage'], 3);
                    $apid3 = $this->IdChange($apid2[0], $apid2[1], $apid2[2], '+', $m5);
                    $data1 = ($m4) ? $apid3 : $apid1;
                    $data2 = ($m4) ? $apid2[0] : $apid[0];
                    echo '
<' . $m1 . ' class="analyze_top">';
                    if ($m1 !== 'li' && $cat != 2 && $cat != 3) {
                        echo '
<' . $m1a . ' class="analyze_num">' . $i . '.</' . $m1a . '>';
                    }
                    echo '
<' . $m1a . ' class="analyze_body">' . $data1 . '</' . $m1a . '>';
                    switch (TRUE) {
                        case ($m2 > 1):
                            if ($data2 === 'i') {
                                $dr = $this->DirectLink($apid[1]);
                            }
                            echo '<' . $m1a . '>' . $dr . '</' . $m1a . '>';
                    }
                    if ($cat != 1 && $cat != 3) {
                        echo '<' . $m1a . ' class="analyze_count" style="text-align: right;"> ' . number_format($row['appvisit']) . '</' . $m1a . '>';
                    }
                    echo '
</' . $m1 . '>';
                    $i++;
                }
                mysql_free_result($tp1);
                break;
            case ($type === 'link'):
                $query = sprintf(
                    "SELECT arreferer, arvisit FROM %s WHERE LEFT(ardate, 7)='%s' ORDER BY arvisit DESC LIMIT 0, %s",
                    sql_table('plugin_analyze_referer'),
                    $m2 ? date('Y-m', strtotime("-1 month", strtotime(date('Y-m') . '-01'))) : date("Y-m"),
                    $id
                );
                $tp1 = sql_query($query);
                while ($row = mysql_fetch_assoc($tp1)) {
                    $ref_l = htmlspecialchars($row['arreferer']);
                    if (!$m5) {
                        $m5 = '50';
                    }
                    $link = substr($ref_l, 0, $m5);
                    if (strlen($ref_l) > $m5) {
                        $link .= '...';
                    }
                    echo '
<' . $m1 . ' class="analyze_top">';
                    if ($m1 !== 'li' && $cat != 2 && $cat != 3) {
                        echo '
<' . $m1a . ' class="analyze_num">' . $i . '.</' . $m1a . '>';
                    }
                    echo '
<' . $m1a . ' class="analyze_body"><a href="' . $ref_l . '">' . $link . '</a></' . $m1a . '>';
                    if ($cat != 1 && $cat != 3) {
                        echo '<' . $m1a . ' class="analyze_count" style="text-align: right;"> ' . number_format($row['arvisit']) . '</' . $m1a . '>';
                    }
                    echo '
</' . $m1 . '>';
                    $i++;
                }
                mysql_free_result($tp1);
                break;
            case ($this->getOption('alz_copyright') === 'yes'):
                echo $copyright = '<a href="' . htmlspecialchars($this->getURL()) . '" title="' . $this->getDescription() . ' NP_' . $this->getName() . $this->getVersion() . '">' . $this->getName() . '</a>';
        }
    }

    function DirectLink($itemid = '')
    {
        global $CONF, $member;
        if (!$member->isLoggedIn()) {
            return;
        }
        if ($this->loginMember() != 1 && !$member->isAdmin()) {
            return;
        }
        $tp0 = '<a href="' . $CONF['PluginURL'] . 'analyze/index.php?select=g&amp;group=page&amp;query=i?' . $itemid . '&amp;past=' . date("Y-m") . '"><img src="' . $CONF['AdminURL'] . 'documentation/icon-up.gif" alt="link" title="' . _NP_ANALYZE_PAGE . _NP_ANALYZE_GROUP1 . '" height="15" width="15"></a>';
        $tp1 = quickQuery(
            sprintf(
                "SELECT aphit1 as result FROM %s WHERE LEFT(apdate, 7) = '%s' and apid LIKE '%%i?%s?%%' LIMIT 1",
                sql_table('plugin_analyze_page'),
                date("Y-m"),
                $itemid
            )
        );
        $tp2 = quickQuery(
            sprintf(
                "SELECT aphit1 as result FROM %s WHERE LEFT(apdate, 7)='%s' and apid LIKE '%%i?%s?%%' LIMIT 1",
                sql_table('plugin_analyze_page'),
                date("Y-m", strtotime("-1 month", strtotime(date("Y-m") . '-01'))),
                $itemid
            )
        );
        $chan = $tp0 . number_format($tp1);
        if ($tp2) {
            $chan .= '(*' . number_format($tp2) . ')';
        }
        return '<span style="font-size: 10px; color: #aaa;">' . $chan . '<a href="' . $CONF['PluginURL'] . 'analyze/index.php?select=b&amp;sort=ASC&amp;fie=aldate&amp;page=1&amp;query=i?' . $itemid . '?"><img src="' . $CONF['AdminURL'] . 'documentation/icon-help.gif" alt="link" height="15" width="15" title="' . _NP_ANALYZE_EXTRACT . '"></a></span>';
    }

    function orDie($alid = '', $aldate = '', $alip = '', $alreferer = '', $alword = '', $copyright = '')
    {
        $alid = addslashes($alid);
        $alip = addslashes($alip);
        $alreferer = addslashes($alreferer);
        $alword = addslashes($alword);
        sql_query(
            sprintf(
                "INSERT INTO %s (alid, aldate, alip, alreferer, alword) VALUES ('%s', '%s', '%s', '%s', '%s')",
                sql_table('plugin_analyze_log'),
                $alid,
                $aldate,
                $alip,
                $alreferer,
                $alword
            )
        );
        return;
    }

    function ExRobo($alip = '', $anid = '')
    {
        $anid = ($anid) ? $anid : '1,2';
        $result = sql_query(
            sprintf(
                "SELECT anip, antitle FROM %s WHERE anid in (%s)",
                sql_table('plugin_analyze_ng'),
                $anid
            )
        );
        while ($res = mysql_fetch_assoc($result)) {
            if ($res['antitle'] === 'rss') {
                $robo .= " alid = 'r?' or ";
            }
            if ($res['anip']) {
                $robo .= $alip . " LIKE '%" . $res['anip'] . "%' or ";
            }
        }
        mysql_free_result($result);
        return $robo;
    }

    function DatabaseList()
    {
        $mtable = array(
            plugin_analyze_page => _NP_ANALYZE_T5,
            plugin_analyze_page_query => _NP_ANALYZE_T7,
            plugin_analyze_page_pattern => _NP_ANALYZE_T6,
            plugin_analyze_referer => _NP_ANALYZE_T9,
            plugin_analyze_query => _NP_ANALYZE_T8,
            plugin_analyze_host => _NP_ANALYZE_T2,
            plugin_analyze_engine => _NP_ANALYZE_T0,
            plugin_analyze_robot => _NP_ANALYZE_TA,
            plugin_analyze_hit => _NP_ANALYZE_T1,
            plugin_analyze_ng => _NP_ANALYZE_T4,
            plugin_analyze_log => _NP_ANALYZE_T3,
            plugin_analyze_temp => _NP_ANALYZE_TB
        );
        return $mtable;
    }

    function DatabaseLists()
    {
        $mtable2 = array(
            plugin_analyze_page => apdate,
            plugin_analyze_page_query => apqdate,
            plugin_analyze_page_pattern => appdate,
            plugin_analyze_referer => ardate,
            plugin_analyze_query => aqdate,
            plugin_analyze_host => ahdate,
            plugin_analyze_engine => aedate,
            plugin_analyze_robot => arodate
        );
        return $mtable2;
    }

    function DatabaseListd()
    {
        $mtable3 = array(
            plugin_analyze_page => aphit1,
            plugin_analyze_page_query => apqvisit,
            plugin_analyze_page_pattern => appvisit,
            plugin_analyze_referer => arvisit,
            plugin_analyze_query => aqvisit,
            plugin_analyze_host => ahvisit,
            plugin_analyze_engine => aevisit,
            plugin_analyze_robot => arovisit
        );
        return $mtable3;
    }

    function DatabaseDelCount($y1 = '', $m1 = '', $d_count = '')
    {
        $mtable = $this->DatabaseList();
        $mtable2 = $this->DatabaseLists();
        $mtable3 = $this->DatabaseListd();
        $pe_d = date("Y-m-d", strtotime($y1 . '-' . $m1 . '-01'));
        $pe_d2 = date("Y-m-d", strtotime("+1 month", strtotime($y1 . '-' . $m1 . '-01')));
        foreach ($mtable2 as $mt => $mt1) {
            $rs = mysql_query("SELECT " . $mt1 . " FROM " . sql_table($mt) . " WHERE " . $mt1 . " >= '" . $pe_d . "' and " . $mt1 . " < '" . $pe_d2 . "'");
            $rows = mysql_num_rows($rs);
            $c_2 = $rows - $d_count;
            foreach ($mtable3 as $md => $md1) {
                if ($mt == $md && $c_2 > 0) {
                    sql_query(
                        sprintf(
                            "DELETE FROM %s WHERE %s >= '%s' and %s < '%s' ORDER BY %s ASC LIMIT %s",
                            sql_table($mt),
                            $mt1,
                            $pe_d,
                            $mt1,
                            $pe_d2,
                            $md1,
                            $c_2
                        )
                    );
                }
            }
            sql_query("OPTIMIZE TABLE " . sql_table($mt));
        }
        return;
    }

    function AccessLog($mcsv = '', $t_table = '')
    {
        global $DIR_MEDIA;
        $rs = mysql_query("SELECT * FROM " . $t_table);
        $fields = mysql_num_fields($rs);
        while ($row = mysql_fetch_array($rs)) {
            for ($j = 0; $j < $fields; $j++) {
                $data0 .= '"' . addslashes($row[$j]) . '"';
                if ($j < $fields - 1) {
                    $data0 .= ',';
                }
            }
            $data0 .= "\n";
        }
        $fp = @fopen($DIR_MEDIA . $this->getOption('alz_pastdir') . "/" . $mcsv . ".csv", "a");
        @fputs($fp, $data0);
        @fclose($fp);
        return;
    }

    function TempChange($past = '')
    {
        global $DIR_MEDIA;
        $lines = @file($DIR_MEDIA . $this->getOption('alz_pastdir') . "/" . $past . ".csv");
        if ($this->getOption('alz_temp') !== 'yes' || !$lines) {
            return;
        }
        $ct = $this->getOption('alz_temp_c');
        if (!$ct) {
            $ct = 5000;
        }
        $tem = sql_table('plugin_analyze_temp');
        sql_query("TRUNCATE TABLE " . $tem);
        $i = 0;
        $k = 0;
        foreach ($lines as $line) {
            switch (TRUE) {
                case ($i < $ct):
                    $c_temp[$k] .= "(" . $line . "),";
                    $i++;
                    break;
                default:
                    $i = 0;
                    $k++;
            }
        }
        for ($j = 0; $j <= $k; $j++) {
            $c_temp[$j] = substr($c_temp[$j], 0, -1);
            sql_query("INSERT INTO " . $tem . " VALUES " . $c_temp[$j]);
        }
        return;
    }

    function ShowCountting($id = '', $cat = '')
    {
        return number_format($this->Countting($id, $cat));
    }

    function Countting($id = '', $cat = '')
    {
        $aldate = date("U", time() + ($this->getOption('alz_time_d') * 3600));
        if (!$id) {
            $id = 'total';
        }
        $today_c = " as result FROM " . sql_table('plugin_analyze_log') . " WHERE NOT(" . $this->ExRobo('alip') . "alip = '')";
        switch ($id) {
            case 'all':
                $today_v = mysql_num_rows(
                    sql_query(
                        sprintf(
                            "SELECT COUNT(allog)%s GROUP BY alip ORDER BY null",
                            $today_c
                        )
                    )
                );
                $total_v = quickQuery(
                    sprintf(
                        "SELECT ahvisit as result FROM %s WHERE ahdate = '2000-01-01' ORDER BY null LIMIT 1",
                        sql_table('plugin_analyze_hit')
                    )
                );
                $yday = quickQuery(
                    sprintf(
                        "SELECT ahvisit as result FROM %s WHERE ahdate != '2000-01-01' ORDER BY ahdate DESC LIMIT 1",
                        sql_table('plugin_analyze_hit')
                    )
                );
                $s0 = explode('/', $this->getOption('alz_counter'));
                return $s0[0] . number_format($total_v + $today_v) . $s0[1] . number_format($today_v) . $s0[2] . number_format($yday);
                break;
            case 'total':
                $today_v = mysql_num_rows(
                    sql_query(
                        sprintf(
                            "SELECT COUNT(allog)%s GROUP BY alip ORDER BY null",
                            $today_c)
                    )
                );
                $total_v = quickQuery(
                    sprintf(
                        "SELECT ahvisit as result FROM %s WHERE ahdate = '2000-01-01' ORDER BY null LIMIT 1",
                        sql_table('plugin_analyze_hit')
                    )
                );
                return ($total_v + $today_v);
            case 'total2':
                $today_v1 = mysql_num_rows(sql_query("SELECT allog" . $today_c));
                $total_v1 = quickQuery(
                    sprintf(
                        "SELECT ahhit as result FROM %s WHERE ahdate = '2000-01-01' ORDER BY null LIMIT 1",
                        sql_table('plugin_analyze_hit')
                    )
                );
                return ($total_v1 + $today_v1);
            case 'today':
                $today_v = mysql_num_rows(
                    sql_query(
                        sprintf(
                            "SELECT COUNT(allog)%s GROUP BY alip ORDER BY null",
                            $today_c)
                    )
                );
                return ($today_v);
            case 'today2':
                $today_v1 = mysql_num_rows(sql_query("SELECT allog" . $today_c));
                return $today_v1;
            case 'yesterday':
                return quickQuery(
                    sprintf(
                        "SELECT ahvisit as result FROM %s WHERE ahdate != '2000-01-01' ORDER BY ahdate DESC LIMIT 1",
                        sql_table('plugin_analyze_hit')
                    )
                );
            case 'yesterday2':
                return quickQuery(
                    sprintf(
                        "SELECT ahhit as result FROM %s WHERE ahdate != '2000-01-01' ORDER BY ahdate DESC LIMIT 1",
                        sql_table('plugin_analyze_hit')
                    )
                );
            default:
                switch ($cat) {
                    case '2':
                        $today_v1 = mysql_num_rows(sql_query("SELECT allog" . $today_c));
                        $week_v1 = quickQuery(
                            sprintf(
                                "SELECT SUM(ahhit) as result FROM %s WHERE ahdate > %s",
                                sql_table('plugin_analyze_hit'),
                                mysqldate($aldate - 86400 * $id)
                            )
                        );
                        return ($week_v1 + $today_v1);
                    default:
                        $today_v = mysql_num_rows(sql_query("SELECT COUNT(allog)" . $today_c . " GROUP BY alip"));
                        $week_v = quickQuery(
                            sprintf(
                                "SELECT SUM(ahvisit) as result FROM %s WHERE ahdate > %s",
                                sql_table('plugin_analyze_hit'),
                                mysqldate($aldate - 86400 * $id)
                            )
                        );
                        return ($week_v + $today_v);
                }
        }
    }

    function oName($apname = '', $acount = '')
    {
        $ac = $this->getOption('alz_count');
        $ref_a = explode('//', $apname, 2);
        if ($ref_a[0] === 'http:') {
            $apname = $ref_a[1];
        }
        if (!$acount) {
            $acount = ($ac) ? $ac : 18;
        }
        switch ($ref_a[0]) {
            case 'http:':
                $apname1 = mb_strimwidth($apname, 0, $acount, '..');
                break;
            default:
                $apname1 = (_CHARSET === 'EUC-JP') ? mb_strimwidth($apname, 0, $acount, '..', euc) : mb_strimwidth($apname, 0, $acount, '..', utf8);
        }
        //			$apname1 = htmlspecialchars(substr($apname, 0, $acount));
        //htmlspecialchars(mb_substr($apname, 0, $acount));
        //		if(mb_strlen($apname) > $acount) $apname1 .= '..';
        return $apname1;
    }

    function ChangeData($past = '', $que1 = '', $jd = '', $que = '', $hd = '', $bd = '')
    {
        global $CONF;
        if ($past === '+') {
            return;
        }
        if ($que == 1) {
            $jd = '';
        }
        switch (TRUE) {
            case ($hd):
                if ($bd) {
                    $que1 = $bd;
                }
                $chan = '<a href="' . $CONF['PluginURL'] . 'analyze/index.php?select=g&amp;group=page&amp;query=' . $que1 . '&amp;past=' . $past . '"><img src="documentation/icon-up.gif" alt="link" title="' . _NP_ANALYZE_PAGE . _NP_ANALYZE_GROUP1 . '" height="15" width="15"></a>';
        }
        switch (TRUE) {
            case ($this->getOption('alz_temp') === 'no' && ($past || ($_GET['group']) === 'page')):
                break;
            default:
                if (!$past && ($_GET['group']) === 'page') {
                    $past = date("Y-m");
                }
                $chan .= ' <a href="' . $CONF['PluginURL'] . 'analyze/index.php?select=b&amp;sort=ASC&amp;fie=aldate&amp;page=1&amp;past=' . $past . '&amp;query=' . $que1 . $jd . '&amp;jd=' . $past . '"><img src="documentation/icon-help.gif" alt="link" height="15" width="15" title="' . _NP_ANALYZE_EXTRACT . '"></a> ' . $other;
                if ($que && $que != 1) {
                    $chan .= $que . ' <a href="' . $CONF['PluginURL'] . 'analyze/index.php?select=b&amp;sort=ASC&amp;fie=aldate&amp;page=1&amp;past=' . $past . '&amp;query=' . $que . '"><img src="documentation/icon-help.gif" alt="link" height="15" width="15" title="' . _NP_ANALYZE_EXTRACT . ' : ' . $que . '"></a>';
                }
        }
        return $chan;
    }

    function IdChange($select = '', $id = '', $other = '', $past = '', $c = '', $que = '', $hd = '')
    {
        global $CONF;
        if (!$past) {
            $past = ($_GET['past']) ? addslashes($_GET['past']) : addslashes(postVar('past'));
        }
        $numb = strlen($select);
        $other = htmlspecialchars($other);
        switch (TRUE) {
            case ($past === '+'):
                $url = '';
                break;
            case ($CONF['URLMode'] === 'normal'):
                $url = $CONF['IndexURL'] . 'index.php';
                break;
            default:
                $url = substr($CONF['IndexURL'], 0, -1);
        }
        $que1 = ($numb > 3) ? substr($select, 0, 60) : $select . '?' . $id;
        if ($other && $other != 1 && $numb < 10 && $select !== 'mt') {
            $que1 .= '?' . $other;
        }
        $sort = ($_GET['sort'] === 'ASC') ? 'DESC' : 'ASC';
        if ($select === 'a') {
            $asa = explode('/', $id);
            $id = $asa[1];
        }
        if ($select !== 'en' && $select !== 'mt' && is_numeric($id)) {
            $apname1 = quickQuery(
                sprintf(
                    "SELECT bname as result FROM %s WHERE bnumber=%s",
                    sql_table('blog'),
                    $id
                )
            );
        }
        if ($this->getOption('alz_oname') !== 'no') {
            $oname_j = $this->oName($other, 10);
        }
        switch ($select) {
            case 'i':
                if (is_numeric($id)) {
                    $apname = quickQuery(
                        sprintf(
                            "SELECT ititle as result FROM %s WHERE inumber = %s",
                            sql_table('item'),
                            $id
                        )
                    );
                }
                if ($past !== '+') {
                    $change = '<strong>I.</strong>';
                }
                $change .= ($apname) ? '<a href="' . $url . createItemLink((int)$id) . '" title="' . $apname . '">' . $this->oName($apname, $c) . '</a>' : $id . ' ' . _NP_ANALYZE_DEL;
                return $change . $this->ChangeData($past, $que1, '?', 0, $hd);
            case 'b':
                if ($past !== '+') {
                    $change = '<strong>B.</strong>';
                }
                if (is_numeric($id)) {
                    $change .= ($apname1) ? '<a href="' . $url . createBlogidLink($id) . '" title="' . $apname1 . ' ' . $other . '">' . $this->oName($apname1, $c) . '</a>' . $oname_j : $id . ' ' . _NP_ANALYZE_DEL;
                }
                return $change . $this->ChangeData($past, $que1, '?', 1, $hd, 'b?' . $id);
            case 'c':
                if (is_numeric($id)) {
                    $apname = quickQuery(
                        sprintf(
                            "SELECT cname as result FROM %s WHERE catid = %s",
                            sql_table('category'),
                            $id
                        )
                    );
                }
                if (is_numeric($id)) {
                    $bid = quickQuery(
                        sprintf(
                            "SELECT cblog as result FROM %s WHERE catid = %s",
                            sql_table('category'),
                            $id
                        )
                    );
                }
                if ($past !== '+') {
                    $change = '<strong>C.</strong>';
                }
                $change .= ($apname) ? '<a href="' . $url . createBlogidLink($bid, array('catid' => $id)) . '" title="' . $apname . '">' . $this->oName($apname, $c) . '</a>' : $id . ' ' . _NP_ANALYZE_DEL;
                return $change . $this->ChangeData($past, $que1, '?', 0, $hd);
            case 'l':
                if ($past !== '+') {
                    $change = '<strong>AL.</strong>';
                }
                if ($id) {
                    $change .= ($apname1) ? '<a href="' . $url . createArchiveListLink((int)$id) . '" title="' . $apname1 . '">' . $this->oName($apname1, $c) . '</a>' : $id . ' ' . _NP_ANALYZE_DEL;
                }
                return $change . $this->ChangeData($past, $que1, '?', 0, $hd);
            case 'a':
                if ($past !== '+') {
                    $change = '<strong>Ar.</strong>';
                }
                if ($id || $asa[0]) {
                    $change .= ($apname1) ? '<a href="' . $url . createArchiveLink((int)$id, $asa[0]) . '" title="' . $asa[0] . ' ' . $apname1 . '">' . $this->oName($asa[0] . ' ' . $apname1, $c) . '</a>' : $asa[0] . $id . ' ' . _NP_ANALYZE_DEL;
                }
                return $change . $this->ChangeData($past, $que1, '?', 0, $hd);
            case 'r':
                if ($past !== '+') {
                    $change = '<strong>R.</strong>';
                }
                return $change . 'XML-RSS ' . $id . $this->ChangeData($past, $que1);
            case 'm':
                if (is_numeric($id)) {
                    $apname = quickQuery(
                        sprintf(
                            "SELECT mname as result FROM %s WHERE mnumber = %s",
                            sql_table('member'),
                            $id
                        )
                    );
                }
                if ($past !== '+') {
                    $change = '<strong>M.</strong>';
                }
                $change .= ($apname) ? '<a href="' . $url . createMemberLink((int)$id) . '" title="Member Page : ' . $apname . '">' . $apname . '</a>' : $id . ' ' . _NP_ANALYZE_DEL;
                return $change . $this->ChangeData($past, $que1, '?', 0, $hd);
            case 'im':
                return '<strong>IMG.</strong>[Popup window]' . $this->ChangeData($past, $que1);
            case 's':
                $change = '<strong>S.';
                $change .= (is_numeric($id)) ? '<a href="' . $url . '?query=' . $other . '&amp;blogid=' . $id . '" title="' . _NP_ANALYZE_SEARCH_PAGE . ' : ' . $apname1 . '">' . _NP_ANALYZE_SEARCH . '</a>' : _NP_ANALYZE_SEARCH_PAGE;
                return $change . '</strong>' . $this->ChangeData($past, $que1, '?') . $other;
            case 'en':
                if ($other == 1) {
                    return $id . $this->ChangeData($past, $que1);
                }
                if ($other != 1) {
                    return '<strong>[' . $id . ']</strong> ' . $this->ChangeData($past, $que1, '', $que);
                }
            case 'co':
                if (is_numeric($id)) {
                    $apname = quickQuery(
                        sprintf(
                            "SELECT ititle as result FROM %s, %s WHERE inumber = citem and cnumber = %s",
                            sql_table('item'),
                            sql_table('comment'),
                            $id
                        )
                    );
                }
                if (is_numeric($id)) {
                    $aid = quickQuery(
                        sprintf(
                            "SELECT inumber as result FROM %s, %s WHERE inumber = citem and cnumber = %s",
                            sql_table('item'),
                            sql_table('comment'),
                            $id
                        )
                    );
                }
                $change = '<strong>Co.</strong>';
                $change .= ($aid) ? '<a href="' . $url . createItemLink($aid) . '" title="' . $apname . '">' . $this->oName($apname) . '</a> ' . $this->oName($other, 10) : $id . ' ' . _NP_ANALYZE_DEL;
                return $change . $this->ChangeData($past, $que1, '?');
            case 'sb':
                if (is_numeric($id)) {
                    $apname = quickQuery(
                        sprintf(
                            "SELECT sname as result FROM %s WHERE scatid = %s",
                            sql_table('plug_multiple_categories_sub'),
                            $id
                        )
                    );
                }
                if (is_numeric($id)) {
                    $bid = quickQuery(
                        sprintf(
                            "SELECT bnumber as result FROM %s as sb, %s as c, %s as b"
                            . " WHERE c.cblog=b.bnumber and c.catid=sb.catid and sb.scatid = %s",
                            sql_table('plug_multiple_categories_sub'),
                            sql_table('category'),
                            sql_table('blog'),
                            $id
                        )
                    );
                }
                if ($past !== '+') {
                    $change = '<strong>Sb.</strong>';
                }
                $change .= ($bid) ? '<a href="' . $url . createBlogidLink($bid, array('subcatid' => $id)) . '" title="' . $apname . '">' . $this->oName($apname, $c) . '</a>' : $id . ' ' . _NP_ANALYZE_DEL;
                return $change . $this->ChangeData($past, $que1, '?', 0, $hd);
            case 'mt':
                if ($other) {
                    $other = str_replace(' ', '+', $other);
                    $ot = explode('+', $other);
                    foreach ($ot as $q1) $q2 .= preg_replace('/^([0-9]*).*/', '\\1', $q1) . ',';
                    $ids = substr($q2, 0, -1);
                    $ot = explode(',', $ids);
                    $que1 .= '?' . $ot[0];
                    $re = sql_query(
                        sprintf(
                            "SELECT tagname FROM %s WHERE tagid in (%s)",
                            sql_table('plugin_multitags'),
                            $ids
                        )
                    );
                    while ($row = mysql_fetch_assoc($re)) $apname .= $row['tagname'] . '+';
                    $apname = substr($apname, 0, -1);
                    if ($apname) {
                        $que = quickQuery(
                            sprintf(
                                "SELECT odef as result FROM %s, %s"
                                . " WHERE opid=pid and oname='tag_query' and pfile='NP_MultiTags'",
                                sql_table('plugin'),
                                sql_table('plugin_option_desc')
                            )
                        );
                    }
                    switch ($CONF['URLMode']) {
                        case 'normal':
                            $id2 = '&amp;' . $que . '=' . $other;
                            break;
                        default:
                            $id2 = '/' . $que . '/' . $other;
                    }
                }
                if ($past !== '+') {
                    $change = '<strong>MT.</strong>';
                }
                $change .= ($apname) ? '<a href="' . $url . createBlogidLink($id) . $id2 . '" title="' . $apname . '">' . $this->oName($apname) . '</a> ' . $this->ChangeData($past, $que1) : $other;
                return $change;
            case 'e':
                return '<strong>E.</strong>' . _NP_ANALYZE_ERROR_PAGE . ' ' . $id . $this->ChangeData($past, $que1);
            default:
                if (!$select) {
                    return;
                }
                $select = htmlspecialchars($select);
                switch (TRUE) {
                    case ($id == 1):
                        return $select . $this->ChangeData($past, $que1);
                        break;
                    case (!$id):
                        return '<a href="' . $select . '" title="' . $select . '">' . $this->oName($select, $c) . '</a>' . $this->ChangeData($past, $que1);
                        break;
                    default:
                        return '<a href="' . $select . '" title="' . $select . '">' . $this->oName($select . '?' . $id, $c) . '</a>' . $this->ChangeData($past, $que1);
                }
        }
    }
}
