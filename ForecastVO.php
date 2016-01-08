<?php
/**
 * Simple value object about the forecast
 * @author cmack
 *
 */
class ForecastVO {
	
	private $zipCode;
	private $date;
	private $pollen;
	private $scale;
	private $value;
	
	public function __construct($zipCode, $city, $date, $pollen, $scale, $value) {
		$this->zipCode = $zipCode;
		$this->city = $city;
		$this->date = $date;
		$this->pollen = $pollen;
		$this->scale = $scale;
		$this->value = $value;
	}
	
	/*
	 * Getters
	 */
	
	/**
	 * 
	 * @return string
	 */
	public function getZipCode() {
		return $this->zipCode;
	}
	
	/**
	 *
	 * @return string
	 */
	public function getCity() {
		return $this->city;
	}
	
	/**
	 * 
	 * @return string
	 */
	public function getDate() {
		return $this->date;
	}
	
	/**
	 *
	 * @return string
	 */
	public function getPollen() {
		return $this->pollen;
	}
	
	/**
	 *
	 * @return string
	 */
	public function getScale() {
		return $this->scale;
	}
	
	/**
	 *
	 * @return string
	 */
	public function getValue() {
		return $this->value;
	}
	
	public function __toString() {
		return "{$this->city} {$this->zipCode} : " . $this->date . " - {$this->pollen} {$this->scale} {$this->value}";
	}
}