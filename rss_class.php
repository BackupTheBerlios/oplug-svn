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

	

	function RSS() {
		print "<html><head>";
		print "<meta http-equiv=\"content-type\" content=\"application/xhtml+xml; charset=UTF-8\"/>";
		print "</head><body>";
		print "<br/>RSS feed reader v" . $this->version . " by OpLUG<br/>";
		print "Now Blogger support only<br/><br/>";
	
		$this->get_channels($this->chanfile);

		$a=0;

		while($this->channels[$a]!=null) {
			ereg('^.*xml',$this->channels[$a++],$chan);
			$this->parse($chan[0]);
		}

		$this->print_to_file($filename);
		
		print "</body></html>";		
	}

	function print_to_file($filename) {
		$a=0;
		while($a<=$this->curID) {
			print "<h2>".$this->rssData[$a]["title"]."</h2>";
			print "<p>".$this->rssData[$a]["dev"]."</p>";
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
			case "GENERATOR" : if($attrs["URL"]!="http://www.blogger.com/") die("To nie jest blogger.. Koncze..<br/>"); break;
			case "ENTRY" : $this->rssData[] = array(); $this->curID++; $this->inEntry = true; break;
			case "TITLE" : $this->isEntryTitle = true; break;
			case "DIV" : $this->isDiv = true; break;
			case "CREATED" : $this->isDate = true; break;
		}
 

	}

	function endElement($parser, $name) {
		switch($name) {
			case "ENTRY" : $this->inEntry = false; break;
			case "TITLE" : $this->isEntryTitle = false; break;
			case "DIV" : $this->isDiv = false; break;
			case "CREATED" : $this->isDate = false; break;
			case "LINK" : $this->isLink = false; break;
		}
	}

	function XMLcharacterData($parser, $data){
		if($this->inEntry) {
			$curID = $this->curID;
			if($this->isDate) $this->rssData[$curID]["date"] = $data;
			if($this->isEntryTitle) $this->rssData[$curID]["title"] = $data;
			if($this->isDiv) $this->rssData[$curID]["dev"] = $data;
		}
	}

}

?>
