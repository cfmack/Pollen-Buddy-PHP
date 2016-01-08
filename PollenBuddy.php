<?php

require_once("simple_html_dom.php");
require_once("ForecastVO.php");

class PollenBuddy {

    // added caching layer to prevent pooling same URL in a day
    private $cacheLifeHour = false;
    private $cacheTempFolder = './tmp';
    private $daysForecast = 1;
  
    // file prefix for cache names
    const CACHE_PREFIX = 'pollen-cache-';
    const COLOR_STYLE = 'background-color';

    // Wunderground's Pollen API with zipcode GET parameter
    const WUNDERGROUND_URL = "http://www.wunderground.com/DisplayPollen.asp?Zipcode=";

    // max days in the forecast for Wunderground
    const MAX_FORECAST = 4;
    
    // Number of characters to strip before getting to the data
    const CITY_HTML = 17;
    const POLLEN_TYPE_HTML = 13;

    

    /**
     * Entry point
     * 
     *  Only set configuration data
     * 
     * @param int $daysForecast number of days to forecast out
     * @param int|false $cacheLifeHour hour the cache is good for.  false for no cache
     * @param string $cacheTempFolder location of cache folder
     */
    public function __construct($daysForecast = PollenBuddy::MAX_FORECAST, $cacheLifeHour = false, $cacheTempFolder = './tmp') {
    	$this->daysForecast = $daysForecast;
    	$this->cacheLifeHour = $cacheLifeHour;
    	$this->cacheTempFolder = $cacheTempFolder;
    }
    
    /**
     * Run multiple zip codes
     * @param unknown $zipcode
     */
    public function run($zipCode) {
    	$uri = PollenBuddy::WUNDERGROUND_URL . $zipCode;
    	
    	$html 		= $this->retrieveHtml($uri, $zipCode);
    	$city 		= $this->parseCity($html);
    	$pollen 	= $this->parsePollenType($html);
    	$keys 		= $this->parseKeys($html);
    	$forecasts 	= $this->parseForecast($html, $pollen, $city, $zipCode, $keys);
    	
    	return $forecasts;
    	 
    }
    
    
    /**
     * Function to retrieve the html from cache or web
     * 
     * @param string $uri
     * @return simple_html_dom|string
     */
    private function retrieveHtml($uri, $zipCode) {
    	$html = 'No page found';
    	
    	if ($this->cacheLifeHour == false) {
    		$html = file_get_html($uri);
    	}
    	else {
    		$nowDate = new DateTime("now");
    		
    		$file = $this->cacheTempFolder . '/' . PollenBuddy::CACHE_PREFIX . $zipCode . '.tmp';
    		
    		// if file does not exist or the cache is expired, create file
    		if (!file_exists($file) || (time() - filemtime($file) >= ($this->cacheLifeHour * 3600))) {
    			file_put_contents($file, file_get_html($uri));
    		}
    
    		$html = file_get_html($file);
    	}
    	
    	return $html;
    }

    /**
     * Get the name of the city
     * 
     * @return String
     */
    public function parseCity($html) {
        $rawCity = $html->find("div.pollen-data div.columns h1", 0)
                        ->plaintext;
        $city = substr(
            $rawCity,
            PollenBuddy::CITY_HTML
        );

        return $city;
    }


    /**
     * Get today's pollen type
     * @return String
     */
    public function parsePollenType($html) {
    
    	$rawPollenType = $html->find("div.panel h3", 0)->plaintext;
    	$pollenType = substr(
    			$rawPollenType,
    			PollenBuddy::POLLEN_TYPE_HTML
    
    	);
    
    	return $pollenType;
    }
    
    /**
     * Get the legend keys to the level's like high, medium, low, keyed by the background css style
     * 
     * @return Array
     */
    public function parseKeys($html) {
    
    	$keyTexts = array();
    	$keyColors = array();
    	
    	
    	for($i = 0; $i < 5; $i++) {
    
    		$rawText = $html
    			->find("td.key div", $i)
    			->plaintext;

    		$rawColor =  $html
    			->find("td.key div", $i)
    			->style;
    
    		$colors = $this->breakCSS($rawColor);
    		$color = '';
    		if (isset($colors[PollenBuddy::COLOR_STYLE])) {
    			$color = $colors[PollenBuddy::COLOR_STYLE];
    		}
    		
    		array_push($keyTexts, $rawText);
    		array_push($keyColors, $color);
    	}
    
    	$keys = array_combine(
    			$keyColors,
    			$keyTexts
    	);
    
    	return $keys;
    }
    
    /**
     * 
     * @param unknown $html
     * @param unknown $pollen
     * @param unknown $city
     * @param unknown $zipCode
     * @return multitype:
     */
    private function parseForecast(simple_html_dom $html, $pollen, $city, $zipCode, $keys) {
    
    	// Iterate through the four dates [Wunderground only has four day
    	// pollen prediction]
    
    	$forecasts = array();
    		
    	for($i = 0; $i < $this->daysForecast; $i++) {
    
    		// Get the raw date
    		$rawDate = $html
    			->find("td.even-four div", $i)
    			->plaintext;
    		
    		$value = $html
    			->find("td.levels p", $i)
    			->plaintext;
    		
    
    		$scale = $this->parseScale($html, $keys, $i);
    		
    		$forecast = new ForecastVO($zipCode, $city, $rawDate, $pollen, $scale, $value);
    		$forecasts[] = $forecast;
    	}
    
    	
    	return $forecasts;
    
    }
    
    /**
     * Function to scale of pollen by matching keys
     * 
     * @param simple_html_dom $html
     * @param array $keys - legend of the scale
     * @param int $domCount - exact dom to extract
     * 
     * @return string
     */
    private function parseScale($html, $keys, $domCount) {
    	// Get the raw date
    	$rawCategoryStyle = $html
    		->find("td.levels div", $domCount)
    		->style;
    	
    	$colors = $this->breakCSS($rawCategoryStyle);
    	
    	$color = '';
    	if (isset($colors[PollenBuddy::COLOR_STYLE])) {
    		$color = $colors[PollenBuddy::COLOR_STYLE];
    	}
    	
    	// if there is a key with the same background color, extract it
    	$scale = 'Unknown';
    	if (isset($keys[$color])) {
    		$scale = $keys[$color];
    	}
    	
    	return $scale;
    }
    
    

    /**********************************************************************
     *
     * Helper functions - could be moved to another class(es) 
     *
     **********************************************************************/
    
    
        
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