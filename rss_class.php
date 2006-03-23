<?php
// Klasa RSS umozliwia odczytywanie kanalow RSS i przetwarzanie interesujacych nas informacji na HTML.
// Na razie nie jest to duzo, ale od czegos trzeba zaczac...

class RSS {
	var $debug = true;

	var $url_base = "http://linux.opole.pl/";
	var $example_rss_chan = "http://www.linux.pl/rss.php";
	var $version = "1.0";

	var $RSSparser;

	var $rssVersion = 2.0;
	var $rssInfo = array(); // Info na tema feeda
	var $rssData = array(); // A tutaj wlasnie beda wszystkie informacje wyciagniete z RSS'a

	var $file="data.xml";

	var $curID = -1;
	var $inChannel = false;
	var $inItem = false;
	var $isTitle = false;
	var $isLink = false;
	var $isDesc = false;


	function RSS() {
		print "<br/>RSS feed reader v" . $this->version . " by OpLUG<br/>";
		$this->parse($this->example_rss_chan);

		//przyladowy post
		print "<br/>".$this->rssInfo["title"]."<br/>";
		print "<br/><b>".$this->rssData[0]["title"]."</b><br/><br/>";
		print $this->rssData[0]["description"]."<br/>";
	}

	function parse($channel) {
		$this->RSSparser = xml_parser_create();
		xml_set_element_handler($this->RSSparser,array(&$this,"startElement"),array(&$this,"endElement"));
		xml_set_character_data_handler($this->RSSparser, array(&$this, "XMLcharacterData"));
		if (!($fp = fopen($channel, "r"))) {
  		 die("could not open XML input");
		}

		while ($data = fread($fp, 4096)) {
   		if (!xml_parse($this->RSSparser, $data, feof($fp))) {
       	die(sprintf("XML error: %s at line %d",
                   xml_error_string(xml_get_error_code($this->RSSparser)),
                   xml_get_current_line_number($this->RSSparser)));
   		}
		}
		xml_parser_free($this->RSSparser);
	}

	function startElement($parser, $name, $attrs)
	{
		if( $name == "rss" || $name == "RSS" ) $this->isRSS = true;
 
		if(! $this->isRSS ) $this->errError(16);
 
		switch( $name ){
			case "rss" : $this->rssVersion = $attrs["version"]; break;
			case "RSS" : $this->rssVersion = $attrs["VERSION"]; break;
			case "channel" :
			case "CHANNEL" : $this->inChannel = true; break;
			case "item" :
			case "ITEM" : $this->rssData[] = array(); $this->curID++; $this->inItem = true; break;
			case "title" :
			case "TITLE" : $this->isTitle = true; break;
			case "link" :
			case "LINK" : $this->isLink = true; break;
			case "description" :
			case "DESCRIPTION" : $this->isDesc = true; break;
		}
	}

	function endElement($parser, $name)
	{
		switch( $name ){
			case "channel" :
			case "CHANNEL" : $this->inChannel = false; break;
			case "item" :
			case "ITEM" : $this->inItem = false; break;
			case "title" :
			case "TITLE" : $this->isTitle = false; break;
			case "link" :
			case "LINK" : $this->isLink = false; break;
			case "description" :
			case "DESCRIPTION" : $this->isDesc = false; break;
		}
	}

function XMLcharacterData($parser, $data){
	if( $this->inChannel )
		if(! $this->inItem ){
			if( $this->isTitle ) $this->rssInfo["title"] = $data;
			if( $this->isLink ) $this->rssInfo["link"] = $data;
		}else
			if( $this->inItem ){
				$curID = $this->curID;
			if( $this->isTitle )
				$this->rssData[$curID]["title"] = $data;
			if( $this->isLink )
				$this->rssData[$curID]["link"] = $data;
			if( $this->isDesc )
				$this->rssData[$curID]["description"] .= $data;
		}
	}

}

?>