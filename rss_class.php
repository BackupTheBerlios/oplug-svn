<?php
// Klasa RSS umozliwia odczytywanie kanalow RSS i przetwarzanie interesujacych nas informacji na HTML.
// Na razie nie jest to duzo, ale od czegos trzeba zaczac...

//Metody publiczne:
//	RSS($chanfile); - konstruktor; Jako parametr pobiera nazwe pliku z kanalami.
//  print_credits() - wyswietla info..
//  get_data_string($id) - wyswietla date posta o numerze $id..

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

	var $isRSS = false;
	var $isAtom = false;

	

	function RSS($chanfile) {
		$this->get_channels($chanfile);

		$a=0;

		while($this->channels[$a]!=null) {
			ereg('^[A-Za-z0-9:\/\.\-]*',$this->channels[$a++],$chan);
			$this->parse($chan[0]);
		}

	}

	function print_credits() {
		print "RSS feed reader v" . $this->version . " by OpLUG<br/>";
		print "RSS feed and Blogger Atom support<br/>";

	}


	function get_title($id) {
		return $this->rssData[$id]["title"];
	}

	function get_data_string($id) {
		return $this->rssData[$id]["date"]["day"]."/".$this->rssData[$id]["date"]["month"]."/".$this->rssData[$id]["date"]["year"];
	}

	function get_content($id) {
		return $this->rssData[$id]["div"];
	}

	function get_channels($chanfile) {
		
		$this->channels = file($chanfile);
		if(!($this->channels[0])) die("Brak kanalow.. Sprawdź plik ".$chanfile."<br/>");
		
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
			case "ENTRY" : $this->rssData[] = array(); $this->curID++; $this->inEntry = true; break;
			case "TITLE" : $this->isEntryTitle = true; break;
			case "DESCRIPTION" :
			case "DIV" : $this->isDiv = true; break;
			case "PUBDATE" :
			case "CREATED" : $this->isDate = true; break;
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
					}else if($this->isAtom) {
						ereg('^[0-9\-]*',$data,$data);
						$data = explode("-",$data[0]);
						$this->rssData[$curID]["date"]["year"] = $data[0];
						$this->rssData[$curID]["date"]["month"] = $data[1];
						$this->rssData[$curID]["date"]["day"] = $data[2];

					}
					
				}
				if($this->isEntryTitle) $this->rssData[$curID]["title"] = $data;
				if($this->isDiv) $this->rssData[$curID]["div"] .= $data;
			}
		}

	}

}

?>
