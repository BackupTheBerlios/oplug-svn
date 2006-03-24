<?php
// Klasa RSS umozliwia odczytywanie kanalow RSS i przetwarzanie interesujacych nas informacji na HTML.
// Na razie nie jest to duzo, ale od czegos trzeba zaczac...

//Metody publiczne:
//	RSS(); - konstruktor;

class RSS {
	var $debug = true; // flaga zarezerwowana

	var $url_base = "http://linux.opole.pl/";
	var $filename = "index.html"; // Plik do wygenerowania
	var $filename_short = "planetka.php"; // plik ze skrotami..
	var $chanfile = "channels.rss"; // plik z kanalami w postaci URL
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

	var $isOnet = false;

	

	function RSS() {
		print "<html><head>";
		print "<meta http-equiv=\"content-type\" content=\"application/xhtml+xml; charset=UTF-8\"/>";
		print "</head><body>";
		print "<br/>RSS feed reader v" . $this->version . " by OpLUG<br/>";
		print "RSS feed and Blogger Atom support<br/><br/>";
	
		$this->get_channels($this->chanfile);

		$a=0;

		while($this->channels[$a]!=null) {
			ereg('^[A-Za-z0-9:\/\.\-]*',$this->channels[$a++],$chan);
			$this->parse($chan[0]);
		}

		$this->print_to_file($filename);
		
		print "</body></html>";		
	}

	function print_to_file($filename) {
		$a=0;
		while($a<=$this->curID) {
			print "<hr/>";
			print "<b>".$this->rssData[$a]["title"]."</b> - ";
			print $this->rssData[$a]["date"]."<br/>";
			print $this->rssData[$a]["div"]."<br/><br/>";
			$a++;
		}

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
			case "ITEM" :
			case "ENTRY" : $this->rssData[] = array(); $this->curID++; $this->inEntry = true; break;
			case "TITLE" : $this->isEntryTitle = true; break;
			case "DESCRIPTION" :
			case "DIV" : $this->isDiv = true; break;
			case "PUBDATE" :
			case "CREATED" : $this->isDate = true; break;
		}
 

	}

	function endElement($parser, $name) {
		switch($name) {
			case "ITEM" :
			case "ENTRY" : $this->inEntry = false; break;
			case "TITLE" : $this->isEntryTitle = false; break;
			case "DESCRIPTION" :
			case "DIV" : $this->isDiv = false; break;
			case "PUBDATE" :
			case "CREATED" : $this->isDate = false; break;
		}
	}

	function XMLcharacterData($parser, $data){
		if($this->inEntry) {
			$curID = $this->curID;
			if($this->isDate) {
				$data = str_replace("T"," ",$data);
				$data = str_replace("Z"," ",$data);
				$this->rssData[$curID]["date"] = $data;
			}
			if($this->isEntryTitle) $this->rssData[$curID]["title"] = $data;
			if($this->isDiv) $this->rssData[$curID]["div"] .= "<br/>".$data;
		}
	}

}

?>
