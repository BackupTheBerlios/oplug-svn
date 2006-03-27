<?php
// Skrypt do pobieranie kanalow rss
// przeznaczony do cyklicznego uruchamiania
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="pl" lang="pl">
<head>
<meta http-equiv="content-type" content="application/xhtml+xml; charset=UTF-8"/>
<meta http-equiv="Content-Language" content="pl" />
<style type="text/css" media="all">
@import "style.css";
</style>
<title>OpLUG planet</title>

</head> 
 
<body>

<div class="baner"><a href="http://linux.opole.pl">OpLUG Planet</a></div>

<div class="main">

<?php

require_once("rss_class.php");

$rss = new RSS("channels.rss",3);

$a=0;

while($a<=$rss->curID) {
	print "<div class=\"notka\">";
	print "<h2 class=\"tytul\">".$rss->get_title($a)."</h2>";
	print "<div class=\"autor\">".$rss->get_author($a)."</div>";
	print "<div class=\"data\">".$rss->get_data_string($a)."</div>";
	print "<div class=\"tresc\">".$rss->get_content($a)."</div>";
	print "</div>";
	$a++;
}

print "<div class=\"credits\">";
$rss->print_credits();
print "</div>";

?>

</div>

</body>
</html>
