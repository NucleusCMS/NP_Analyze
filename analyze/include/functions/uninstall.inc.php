<?php
if ($this->getOption('alz_deltable') == 'yes') {
    mysql_query("DROP table " . sql_table('plugin_analyze_log'));
    mysql_query("DROP table " . sql_table('plugin_analyze_hit'));
    mysql_query("DROP table " . sql_table('plugin_analyze_page'));
    mysql_query("DROP table " . sql_table('plugin_analyze_page_pattern'));
    mysql_query("DROP table " . sql_table('plugin_analyze_page_query'));
    mysql_query("DROP table " . sql_table('plugin_analyze_query'));
    mysql_query("DROP table " . sql_table('plugin_analyze_engine'));
    mysql_query("DROP table " . sql_table('plugin_analyze_robot'));
    mysql_query("DROP table " . sql_table('plugin_analyze_referer'));
    mysql_query("DROP table " . sql_table('plugin_analyze_temp'));
    mysql_query("DROP table " . sql_table('plugin_analyze_host'));
    mysql_query("DROP table " . sql_table('plugin_analyze_ng'));
}
$this->deleteOption('alz_loggedin');
$this->deleteOption('alz_deltable');
$this->deleteOption('alz_report');
$this->deleteOption('alz_datestart');
$this->deleteOption('alz_top_limit');
$this->deleteOption('alz_hit_range');
$this->deleteOption('alz_quickmenu');
$this->deleteOption('alz_member');
$this->deleteOption('alz_copyright');
$this->deleteOption('alz_temp');
$this->deleteOption('alz_rss');
$this->deleteOption('alz_pastdir');
$this->deleteOption('alz_time');
$this->deleteOption('alz_count');
$this->deleteOption('alz_oname');
$this->deleteOption('alz_d_count');
$this->deleteOption('alz_img_c');
$this->deleteOption('alz_temp_c');
$this->deleteOption('alz_time_d');
$this->deleteOption('alz_report_m');
$this->deleteOption('alz_c_host');
