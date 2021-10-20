<?php
global $DIR_MEDIA;
$oldmask = umask(0);
@mkdir($DIR_MEDIA . "analyze", 0777);
umask($oldmask);
sql_query('CREATE TABLE IF NOT EXISTS ' . sql_table('plugin_analyze_log') . ' (
    allog int(11) not null auto_increment,
    alid varchar(30) not null,
    aldate datetime not null,
    alip varchar(60) not null,
    alreferer varchar(255) not null,
    alword varchar(200) not null,
    PRIMARY KEY (allog)
)');
sql_query('CREATE TABLE IF NOT EXISTS ' . sql_table('plugin_analyze_hit') . ' (
    ahdate date not null,
    ahvisit int(11) unsigned not null,
    ahhit int(11) unsigned not null,
    ahlevel1 int(11) unsigned,
    ahlevel2 int(11) unsigned,
    ahlevel3 int(11) unsigned,
    ahlevel4 int(11) unsigned,
    ahlevel5 int(11) unsigned,
    ahrobot int(11) unsigned,
    PRIMARY KEY (ahdate)
)');
$rs = mysql_query("SELECT * FROM " . sql_table('plugin_analyze_hit'));
if (mysql_num_fields($rs) == 8) sql_query("ALTER TABLE " . sql_table('plugin_analyze_hit') . " ADD ahrobot int(11) unsigned");
sql_query("INSERT IGNORE INTO " . sql_table('plugin_analyze_hit') . " (ahdate, ahvisit, ahhit, ahlevel1, ahlevel2, ahlevel3, ahlevel4, ahlevel5) VALUES ('2000-01-01', '0', '0', '0', '0', '0', '0', '0')");
sql_query('CREATE TABLE IF NOT EXISTS ' . sql_table('plugin_analyze_page') . ' (
    apid varchar(30) not null,
    apdate date not null,
    aphit int(11) unsigned not null,
    aphit1 int(11) unsigned,
    aphit2 int(11) unsigned,
    KEY apdate (apdate)
)');
sql_query('CREATE TABLE IF NOT EXISTS ' . sql_table('plugin_analyze_page_pattern') . ' (
    appid varchar(30) not null,
    appdate date not null,
    apppage varchar(255),
    apphit int(11) unsigned not null,
    appvisit int(11) unsigned not null,
    KEY appdate (appdate)
)');
sql_query('CREATE TABLE IF NOT EXISTS ' . sql_table('plugin_analyze_page_query') . ' (
    apqid varchar(30) not null,
    apqdate date not null,
    apqquery varchar(255) not null,
    apqhit int(11) unsigned not null,
    apqvisit int(11) unsigned not null,
    KEY apqdate (apqdate)
)');
sql_query('CREATE TABLE IF NOT EXISTS ' . sql_table('plugin_analyze_query') . ' (
    aqquery varchar(255) not null,
    aqdate date not null,
    aqhit int(11) unsigned not null,
    aqvisit int(11) unsigned not null,
    KEY aqdate (aqdate)
)');
sql_query('CREATE TABLE IF NOT EXISTS ' . sql_table('plugin_analyze_engine') . ' (
    aeengine varchar(30) not null,
    aedate date not null,
    aehit int(11) unsigned not null,
    aevisit int(11) unsigned not null,
    KEY aedate (aedate)
)');
sql_query('CREATE TABLE IF NOT EXISTS ' . sql_table('plugin_analyze_robot') . ' (
    aroengine varchar(30) not null,
    arodate date not null,
    arohit int(11) unsigned not null,
    arovisit int(11) unsigned not null,
    KEY arodate (arodate)
)');
sql_query('CREATE TABLE IF NOT EXISTS ' . sql_table('plugin_analyze_referer') . ' (
    arreferer varchar(255) not null,
    ardate date not null,
    arhit int(11) unsigned not null,
    arvisit int(11) unsigned not null,
    KEY ardate (ardate)
)');
sql_query('CREATE TABLE IF NOT EXISTS ' . sql_table('plugin_analyze_host') . ' (
    ahhost varchar(50) not null,
    ahdate date not null,
    ahhit int(11) unsigned not null,
    ahvisit int(11) unsigned not null,
    KEY ahdate (ahdate)
)');
sql_query('CREATE TABLE IF NOT EXISTS ' . sql_table('plugin_analyze_temp') . ' (
    allog int(11) not null,
    alid varchar(30) not null,
    aldate datetime not null,
    alip varchar(60) not null,
    alreferer varchar(255) not null,
    alword varchar(200) not null,
    KEY allog (allog)
)');
sql_query('CREATE TABLE IF NOT EXISTS ' . sql_table('plugin_analyze_ng') . ' (
    an int(11) not null auto_increment,
    anid tinyint(4) not null,
    antitle varchar(30) not null,
    anip varchar(255) not null,
    PRIMARY KEY (an)
)');
$in = quickQuery("SELECT COUNT(anid) as result FROM " . sql_table('plugin_analyze_ng') . " LIMIT 1");
if ($in < 1) $this->InsertRobot();
$datestart = quickQuery("SELECT ahdate as result FROM " . sql_table('plugin_analyze_hit') . " WHERE ahdate != '2000-01-01' ORDER BY ahdate ASC LIMIT 1");
$datestart = ($datestart) ? $datestart : date("Y-m-d");
$this->createOption('alz_loggedin', _NP_ANALYZE_OP_1, 'yesno', 'no');
$this->createOption('alz_deltable', _NP_ANALYZE_OP_2, 'yesno', 'no');
$this->createOption('alz_report', _NP_ANALYZE_OP_3, 'yesno', 'no');
$this->createOption('alz_datestart', _NP_ANALYZE_OP_4 . $datestart . ' )', 'text', $datestart);
$this->createOption('alz_top_limit', _NP_ANALYZE_OP_5, 'text', 5);
$this->createOption('alz_hit_range', _NP_ANALYZE_OP_6, 'text', '1/5/9/49');
$this->createOption('alz_copyright', _NP_ANALYZE_OP_7, 'yesno', 'yes');
$this->createOption('alz_quickmenu', _NP_ANALYZE_OP_8, 'yesno', 'yes');
$this->createOption('alz_member', _NP_ANALYZE_OP_9, 'text', '');
$this->createOption('alz_temp', _NP_ANALYZE_OP_10, 'yesno', 'no');
$this->createOption('alz_rss', _NP_ANALYZE_OP_11, 'yesno', 'yes');
$this->createOption('alz_pastdir', _NP_ANALYZE_OP_12, 'text', 'analyze');
$this->createOption('alz_time', _NP_ANALYZE_OP_13, 'yesno', 'yes');
$this->createOption('alz_count', _NP_ANALYZE_OP_14, 'text', '18');
$this->createOption('alz_oname', _NP_ANALYZE_OP_15, 'yesno', 'yes');
$this->createOption('alz_d_count', _NP_ANALYZE_OP_16, 'text', '200');
$this->createOption('alz_img_c', _NP_ANALYZE_OP_17, 'text', '1');
$this->createOption('alz_temp_c', _NP_ANALYZE_OP_18, 'text', '5000');
$this->createOption('alz_time_d', _NP_ANALYZE_OP_19, 'text', '');
$this->createOption('alz_report_m', _NP_ANALYZE_OP_20, 'yesno', 'no');
$this->createOption('alz_c_host', _NP_ANALYZE_OP_21 . _NP_ANALYZE_OP_210 . ' )', 'text', _NP_ANALYZE_OP_210);
$this->createOption('alz_counter', _NP_ANALYZE_OP_22 . _NP_ANALYZE_OP_220 . ' )', 'text', _NP_ANALYZE_OP_220);
