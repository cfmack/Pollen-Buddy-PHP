<?php

require_once("simple_html_dom.php");

class PollenBuddy {

    // Class variables
    private $html;
    private $city;
    private $zipcode;
    private $pollenType;
    private $dates = array();
    private $levels = array();
    private $fourDayForecast;
    
    // added caching layer to prevent pooling same URL in a day
    private $cacheLifeHour = false;
    private $cacheTempFolder = './tmp';
  
    // file prefix for cache names
    const CACHE_PREFIX = 'pollen-cache-';
    const COLOR_STYLE = 'background-color';

    // Wunderground's Pollen API with zipcode GET parameter
    const WUNDERGROUND_URL = "http://www.wunderground.com/DisplayPollen.asp?Zipcode=";

    // Number of characters to strip before getting to the data
    const CITY_HTML = 20;
    const POLLEN_TYPE_HTML = 13;

    /**
     * Get the content of the Wunderground pollen site page based on the
     * user-entered zipcode
     * TODO: Check for incorrect zipcodes i.e. missing DOMs
     * @param  Integer $zipcode An US-based zipcode
     * @return mixed   $data    Content of the site
     */
    public function PollenBuddy($zipcode, $cacheLifeHour = false) {
        $this->zipcode = $zipcode;
        
        $uri = PollenBuddy::WUNDERGROUND_URL . $zipcode;
        $this->setHtml($uri, $cacheLifeHour);
    }

    /**
     * Get the site's HTML data
     * @return mixed The site HTML
     */
    public function getSiteHTML() {
        return $this->html;
    }

    /**
     * Get the name of the city
     * @return String
     */
    public function getCity() {
        $rawCity = $this->html
                        ->find("div.columns", 0)
                        ->plaintext;
        $this->city = substr(
            $rawCity,
            PollenBuddy::CITY_HTML
        );

        return $this->city;
    }

    /**
     * Get the zipcode of the city
     * @return int
     */
    public function getZipCode() {
        return $this->zipcode;
    }

    /**
     * Get today's pollen type
     * @return String
     */
    public function getPollenType() {

        $rawPollenType = $this->html
                              ->find("div.panel h3", 0)
                              ->plaintext;
        $this->pollenType = substr(
            $rawPollenType,
            PollenBuddy::POLLEN_TYPE_HTML

        );

        return $this->pollenType;
    }

    /**
     * Get the four day forecast data
     * @return mixed
     */
    public function getFourDayForecast() {

        // Iterate through the four dates [Wunderground only has four day
        // pollen prediction]
        $keys = $this->getKeys();
    	
    	$categories = array();
    	
        for($i = 0; $i < 4; $i++) {

            // Get the raw date
            $rawDate = $this->html
                ->find("td.levels p", $i)
                ->plaintext;

            // Get the raw level
            $rawLevel = $this->html
            ->find("td.even-four div", $i)
            ->plaintext;
            
            
            // Get the raw date
            $rawCategoryStyle = $this->html
            ->find("td.levels div", $i)
            ->style;
            
            $colors = $this->breakCSS($rawCategoryStyle);
            $color = '';
            if (isset($colors[PollenBuddy::COLOR_STYLE])) {
            	$color = $colors[PollenBuddy::COLOR_STYLE];
            }
            

            $categories[$rawLevel] = 'Unknown';
            if (isset($keys[$color])) {
            	$categories[$rawLevel] = $keys[$color];
            }
            
            
            // Push each date to the dates array
            array_push($this->dates, $rawDate);
            // Push each date to the levels array
            array_push($this->levels, $rawLevel);
        }

        $this->fourDayForecast = array_combine(
            $this->levels,
            $this->dates
        );

        echo print_r($categories, true);
        
        return $this->fourDayForecast;
    }

    public function getKeys() {
    
    	$keyTexts = array();
    	$keyColors = array();
    	
    	// Iterate through the four dates [Wunderground only has four day
    	// pollen prediction]
    	for($i = 0; $i < 4; $i++) {
    
    		// Get the raw date
    		$rawText = $this->html
    		->find("td.key div", $i)
    		->plaintext;
    
    		// Get the raw color
    		$rawColor =  $this->html
    		->find("td.key div", $i)
    		->style;
    
    		//
    		$colors = $this->breakCSS($rawColor);
    		$color = '';
    		if (isset($colors[PollenBuddy::COLOR_STYLE])) {
    			$color = $colors[PollenBuddy::COLOR_STYLE];
    		}
    		
    		// Push each date to the dates array
    		array_push($keyTexts, $rawText);
    		// Push each date to the levels array
    		array_push($keyColors, $color);
    	}
    
    	$keys = array_combine(
    			$keyColors,
    			$keyTexts
    	);
    
    	return $keys;
    }
    
    /**
     * Get the four forecasted dates
     * 
     * @return array Four forecasted dates
     */
    public function getFourDates() {
    	return $this->dates;
    }

    /**
     * Get four forecasted levels
     * @return array Four forecasted levels of each day's pollen levels.
     */
    public function getFourLevels() {
        return $this->levels;
    }
    
    private function setHtml($uri, $cacheLifeHour) {
    	if ($cacheLifeHour == false) {
    		$this->html = file_get_html($uri);
    	}
    	else {
    		$nowDate = new DateTime("now");
    		//$cacheDate->sub(new DateInterval("PT" . abs($cacheLifeHour) . "H")); 
    		
    		
    		$file = $this->cacheTempFolder . '/' . PollenBuddy::CACHE_PREFIX . $this->zipcode . '.tmp';
    		
    		if (!file_exists($file) || (time() - filemtime($file) >= ($cacheLifeHour * 3600))) {
    			file_put_contents($file, file_get_html($uri));
    		}
    		    		
    		$this->html = file_get_html($file);
    	}
    }
    
    /**
     * Based on http://stackoverflow.com/a/1215128
     * 
     * @param unknown $css
     * @return Ambigous <multitype:, string>
     */
    private function breakCSS($css) {
	    $results = array();
	
	    foreach(explode(';', $css) AS $attr) {
	    	if (strlen(trim($attr)) > 0) // for missing semicolon on last element, which is legal
	    	{
	    		list($name, $value) = explode(':', $attr);
	    		$results[trim($name)] = trim($value);
	    	}
	    }
	    
	    return $results;
	}
}