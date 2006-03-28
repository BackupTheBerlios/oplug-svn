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


$rss = new RSS("channels.rss",3);

$a=0;

while($a<=$rss->curID) {
	print "<div class=\"notka\">";
	print "<h2 class=\"tytul\"><a href=\"".$rss->get_link($a)."\">".$rss->get_title($a)."</a></h2>";
	print "<div class=\"autor\">".$rss->get_author($a)."</div>";
	print "<div class=\"data\">".$rss->get_data_string($a)."</div>";
	print "<div class=\"tresc\">".$rss->get_content($a)."</div>";
	print "</div>";
	$a++;
}

print "<div class=\"credits\">";
$rss->print_credits();
print "</div>";

// Klasa RSS umozliwia odczytywanie kanalow RSS i przetwarzanie interesujacych nas informacji na HTML.
// Na razie nie jest to duzo, ale od czegos trzeba zaczac...

//Metody publiczne:
//	RSS($chanfile,$limit); - konstruktor; Jako parametr pobiera nazwe pliku z kanalami i liczbe postow z kanalu do pobrania
//	print_credits() - wyswietla info..
//	get_data_string($id) - wyswietla date posta o numerze $id..
//	get_title($id)
//	get_content($id)
//	get_short_content($id)

class RSS {
	var $debug = true; // flaga zarezerwowana

	var $url_base = "http://linux.opole.pl/";
	var $filename = "index.html"; // Plik do wygenerowania
	var $filename_short = "planetka.php"; // plik ze skrotami..
	var $channels = array(); // tablica kanalow do przegladniecia
	var $version = "1.0";

	var $RSSparser; // Maszynka do wyciagania informacji z RSS :]

	var $rssVersion = 2.0;
	var $rssInfo = array(); // Info na temat feeda
	var $rssData = array(); // A tutaj wlasnie beda wszystkie informacje wyciagniete z RSS'a

	var $curID = -1;
	var $inEntry = false;
	var $isDate = false;
	var $isEntryTitle = false;
	var $isDiv = false;
	var $isID = false;
	var $isAuthor = false;
	var $isLink = false;

	var $isRSS = false;
	var $isAtom = false;

	var $counter = 0;
	var $post_limit;


	

	function RSS($chanfile,$limit) {
		$this->post_limit = $limit;
		$this->get_channels($chanfile);

		$a=0;

		while($this->channels[$a]!=null) {
			$this->counter = 0;
			ereg('^[A-Za-z0-9:\/\.\-]*',$this->channels[$a++],$chan);
			$this->parse($chan[0]);
		}
		
		$this->sort_posts();

	}

	function sort_posts() { // Sortowanie babelkowe :]
		$a = 0; $b = 0;
		while($a <= $this->curID-1) {
			$b=0;
			while($b <= $this->curID-1) {
				if($this->rssData[$b]["date"]["num"] < $this->rssData[$b+1]["date"]["num"]) {
					$temp = $this->rssData[$b];
					$this->rssData[$b] = $this->rssData[$b+1];
					$this->rssData[$b+1] = $temp;
				}
			$b++;
			}
		$a++;
		}
	}

	function print_credits() {
		print "RSS feed reader v" . $this->version . " by OpLUG<br/>";
		print "RSS feed and Blogger Atom support<br/>";

	}


	function get_title($id) {
		return $this->rssData[$id]["title"];
	}

	function get_author($id) {
		return $this->rssData[$id]["author"];
	}

	function get_link($id) {
		return $this->rssData[$id]["link"];
	}

	function get_data_string($id) {
		//print $this->rssData[$id]["date"]["num"]."<br/>";
		return $this->rssData[$id]["date"]["day"]."/".$this->rssData[$id]["date"]["month"]."/".$this->rssData[$id]["date"]["year"];
	}

	function get_content($id) {
		return $this->rssData[$id]["div"];
	}

	function get_channels($chanfile) {
		
		$this->channels = file($chanfile);
		if(!($this->channels[0])) die("Brak kanalow.. Sprawdź plik ".$chanfile."<br/>");
		
	}

	function get_short_content($id) {
// Czeka na implementacje..
	}

	function parse($channel) {
		$this->RSSparser = xml_parser_create();
		xml_set_element_handler($this->RSSparser,array(&$this,"startElement"),array(&$this,"endElement"));
		xml_set_character_data_handler($this->RSSparser, array(&$this, "XMLcharacterData"));
		if (!($fp = fopen($channel, "r"))) {
  		 die("Nie moge otworzyc kanalu ".$channel.". Zly adres???");
		}

		while ($data = fread($fp, 4096)) {
   			if (!xml_parse($this->RSSparser, $data, feof($fp))) {
       			die(sprintf("Blad XML: %s at line %d",
                   xml_error_string(xml_get_error_code($this->RSSparser)),
                   xml_get_current_line_number($this->RSSparser)));
   			}
		}
		xml_parser_free($this->RSSparser);
	}

	function startElement($parser, $name, $attrs) {

		switch($name) {
			case "RSS" : $this->isRSS = true; break;
			case "FEED" : $this->isAtom = true; break;
			case "ITEM" :
			case "ENTRY" :
				{
					if($this->counter >= $this->post_limit) break;
					$this->rssData[] = array(); $this->curID++; $this->inEntry = true; $this->counter++; break;
				}
			case "TITLE" : $this->isEntryTitle = true; break;
			case "DESCRIPTION" :
			case "DIV" : $this->isDiv = true; break;
			case "PUBDATE" :
			case "CREATED" : $this->isDate = true; break;
			case "NAME" :
			case "AUTHOR" : $this->isAuthor = true; break;
			case "LINK" : $this->isLink = true; break;
			case "IMG" : if($this->isAtom) $this->rssData[$this->curID]["div"] .= "<img src=\"".$attrs["SRC"]."\" alt=\"\">"; break;
			case "A" : if($this->isAtom) $this->rssData[$this->curID]["div"] .= "<a href=\"".$attrs["HREF"]."\">"; break;
		}
 

	}

	function endElement($parser, $name) {
		switch($name) {
			case "BR" : if($this->isAtom) $this->rssData[$this->curID]["div"] .= "<br/>"; break;
			case "RSS" : $this->isRSS = false; break;
			case "FEED" : $this->isAtom = false; break;
			case "ITEM" :
			case "ENTRY" : $this->inEntry = false; break;
			case "TITLE" : $this->isEntryTitle = false; break;
			case "DESCRIPTION" :
			case "DIV" : $this->isDiv = false; break;
			case "PUBDATE" :
			case "CREATED" : $this->isDate = false; break;
			case "NAME" :
			case "AUTHOR" : $this->isAuthor = false; break;
			case "LINK" : $this->isLink = false; break;
			case "A" : if($this->isAtom) $this->rssData[$this->curID]["div"] .= "</a>"; break;
		}
	}

	function XMLcharacterData($parser, $data){
		if($this->isRSS||$this->isAtom) {
			if($this->inEntry) {
				$curID = $this->curID;
				if($this->isDate) {
					$this->rssData[$curID]["date"] = array();
					if($this->isRSS) {
						$data = explode(" ",$data);
						$this->rssData[$curID]["date"]["year"] = $data[3];
						switch($data[2]) {
							case "Jan" : $this->rssData[$curID]["date"]["month"] = "01"; break;
							case "Feb" : $this->rssData[$curID]["date"]["month"] = "02"; break;
							case "Mar" : $this->rssData[$curID]["date"]["month"] = "03"; break;
							case "Apr" : $this->rssData[$curID]["date"]["month"] = "04"; break;
							case "May" : $this->rssData[$curID]["date"]["month"] = "05"; break;
							case "Jun" : $this->rssData[$curID]["date"]["month"] = "06"; break;
							case "Jul" : $this->rssData[$curID]["date"]["month"] = "07"; break;
							case "Aug" : $this->rssData[$curID]["date"]["month"] = "08"; break;
							case "Sep" : $this->rssData[$curID]["date"]["month"] = "09"; break;
							case "Oct" : $this->rssData[$curID]["date"]["month"] = "10"; break;
							case "Nov" : $this->rssData[$curID]["date"]["month"] = "11"; break;
							case "Dec" : $this->rssData[$curID]["date"]["month"] = "12"; break;
						}
						
						$this->rssData[$curID]["date"]["day"] = $data[1];
						$this->rssData[$curID]["date"]["num"] = $data[3].$this->rssData[$curID]["date"]["month"].$data[1];
					}else if($this->isAtom) {
						ereg('^[0-9\-]*',$data,$data);
						$data = explode("-",$data[0]);
						$this->rssData[$curID]["date"]["year"] = $data[0];
						$this->rssData[$curID]["date"]["month"] = $data[1];
						$this->rssData[$curID]["date"]["day"] = $data[2];
						$this->rssData[$curID]["date"]["num"] = $data[0].$data[1].$data[2];

					}
					
				}
				if($this->isEntryTitle) $this->rssData[$curID]["title"] = $data;
				if($this->isDiv) $this->rssData[$curID]["div"] .= $data;

				if($this->isAuthor) $this->rssData[$curID]["author"] = $data;
				if($this->isLink) $this->rssData[$curID]["link"] = $data;
			}
		}

	}

}

?>

</div>

</body>
</html>
