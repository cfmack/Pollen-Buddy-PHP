<?php

require_once("simple_html_dom.php");

class PollenBuddy {

    // Class variables
    private $html;
    private $city;
    private $zipcode;
    private $pollenType;
    private $fourDayForecast;

    // Wunderground's Pollen API with zipcode GET parameter
    const WUNDERGROUND_URL = "http://www.wunderground.com/DisplayPollen.asp?Zipcode=";

    // Number of characters to strip before getting to the data
    const CITY_HTML = 20;
    const POLLEN_TYPE_HTML = 13;

    /**
     * Get the content of the Wunderground pollen site page based on the
     * user-entered zipcode
     * @param  Integer $zipcode An US-based zipcode
     * @return mixed   $data    Content of the site
     */
    public function PollenBuddy($zipcode) {
        $this->zipcode = $zipcode;
        $this->html = file_get_html(PollenBuddy::WUNDERGROUND_URL . $zipcode);
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
        $this->city = substr($rawCity, PollenBuddy::CITY_HTML);

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
        return $this->fourDayForecast;
    }
}