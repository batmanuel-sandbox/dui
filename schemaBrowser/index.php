<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
   "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="US-EN">
<head>
<title>LSST Database Schema Browser</title>
  <meta http-equiv="content-type" content="text/html; charset=UTF-8">
  <meta name="description" content="Large Synoptic Survey Telescope - Schema Browser">
  <meta name="keywords" content="LSST, schema, database">
  <link rel="stylesheet" type="text/css" href="includes/css/lsst.css">
  <script type="text/javascript" src="sortable.js"></script>
</head>

<body>

<div style="clear:both">
<a href="http://www.lsst.org">
  <img src="includes/images/lsst_long_banner1.jpg" border="0" title="LSST Homepage" alt="" /></a>
</div>

<div style="padding:10px">

<h1>LSST Database Schema Browser <font size="-1"><sup>alpha</sup></font></h1>

<?php
include_once("constants.php");
include_once("db.php");


// takes as argument element to be added to the arguments
function 
prepareArgList($k, $v) {

    $args = $_GET;
    $args[$k] = $v;
    $argsStr = "?";
    $argsCount = count($args);
    foreach ($args as $kA=>$vA) {
        $argsStr .= "$kA=$vA";
        if ( --$argsCount>0 ) $argsStr .= '&';
    }
    return $argsStr;
}

if ( array_key_exists('sVer', $_GET) ) {
    $csv = mysql_real_escape_string($_GET['sVer']); // current schema version
} else {
    $csv = DEFAULT_VERSION;
}

$n = count($schemaVersions);
if ( $n > 1 ) {
    print "<p>Schema versions available for browsing:&nbsp;&nbsp;";
    foreach($schemaVersions as $k=>$v) {
        $argsStr = prepareArgList('sVer', $v);
        $s = "<a href=\"index.php$argsStr\">$v</a>";
        if ( $v == $csv ) print "<b><u>$s</b></u>"; else print "$s";
        if ( $k < $n-1 ) print "&nbsp;|&nbsp;";
    }
}
print " (underlined showed)</p>

<p>User defined functions documentation: 
    <a href='sciSQL_0.1' target='_none'>version 0.1</a> (default on lsst10),
    <a href='sciSQL_0.2' target='_none'>version 0.2</a> (upcoming, on lsst10-new)
</p>
<table class='main'>
<tr>

";

global $database;

$dbDescr = $database->getDbDescr();
$dbDescrFile = $dbDescr[0]['schemaFile'];
$dbDescrRev  = $dbDescr[0]['revision'];

$tables = $database->getTableNames();

if ( array_key_exists('t', $_GET) ) {
    $tName = mysql_real_escape_string($_GET['t']);
    $title4t2d = "Details for table <i>$tName</i></td></tr>";
    $tInfo = $database->getTableInfo($tName);
    $tId     = $tInfo[0]['tableId'];
    $tEngine = $tInfo[0]['engine'];
    $tDescr  = $tInfo[0]['description'];
    
    $tColumns = $database->getTableColumns($tId);
    $tIndexes = $database->getTableIndexes($tId);

    $data4t2d ="
<p>$tDescr</p>


<table id='ttable' class='sortable' cellpadding='0' cellspacing='0'>
<tr>
  <th>name</th>
  <th>type</th>
  <th>not null</th>
  <th>default</th>
  <th>unit</th>
  <th>ucd</th>
  <th>description</th>
</tr>
";
    foreach($tColumns as $k=>$v) {
        if ( $v['notNull'] == 1 ) $notNull = 'y' ; else $notNull = '&nbsp;';

        if ( preg_match('/Not set for/i', $v['description']) ) {
            $greyOut = "style='color:grey'";
        } else {
            $greyOut = '';
        }
        $data4t2d .= "<tr>".
            "<td $greyOut valign='top' width='10%'>".$v['name']."</td>".
            "<td $greyOut valign='top' width='5%'>".$v['type']."</td>".
            "<td $greyOut valign='top' width='5%'>". $notNull."</td>".
            "<td $greyOut valign='top' width='5%'>".$v['defaultValue']."</td>".
            "<td $greyOut valign='top' width='5%'>".$v['unit']."</td>".
            "<td $greyOut valign='top' width='5%'>".$v['ucd']."</td>".
            "<td $greyOut valign='top' width='65%'>".$v['description']."</td>".
            "</tr>
";
    }
    $data4t2d .= "</table>
";

    if (count($tIndexes) > 0) {
        $data4t2d .= "

<br />
<br />
<br />
<table id='ttable' class='sortable' cellpadding='0' cellspacing='0'>
<tr>
  <th>index type</th>
  <th>columns</th>
</tr>
";
        foreach($tIndexes as $k=>$v) {
            $data4t2d .= "<tr>".
                "<td valign='top'>".$v['type']."</td>".
                "<td valign='top'>".$v['columns']."</td>".
                "</tr>
";
        }

        $data4t2d .= "
</table>

";
    }

    if ( ! is_null($tEngine) ) {
        $data4t2d .= "

<p><b>Engine:</b> $tEngine</p>

";
    }
} else {
    $title4t2d = "&nbsp;";
    $data4t2d = "To see details, select a table on the left";
}

$tableList = "";#"<span style='line-height:14px'>";
foreach ($tables as $k=>$v) {
    $argStr = prepareArgList('t', $v['name']);
    if ( isset($tName) && $tName == $v['name'] ) {
        $tableList .= "<a href='index.php$argStr' style='color:white;font-weight:bold'>" . $v['name'] . "</a><br />";
    } else {
        $tableList .= "<a href='index.php$argStr'>" . $v['name'] . "</a><br />
";
    }
}
$tableList .= "";#"</span>";

print "
<th width='20%'>
  Table List</td>
<th width='80%'>
  $title4t2d</td></tr>


<tr>
  <td valign='top'>$tableList</td>
  <td valign='top'>$data4t2d</td>
</tr>
</table>

<p class='footprint'>Based on $dbDescrFile, rev $dbDescrRev</p>

";

?>
</div>
</body>
</html>
