<?php

namespace App\Library\SalesOrder;

use GuzzleHttp\Client;


class Fulfillment
{
	private $baseUrl;
	
	public function __construct()
	{
        $this->client = new Client();
		$this->baseUrl = 'https://api.acommercedev.com/';
	}

    /**
     * Generate new auth key
     */
	public function genNewAuth()
	{
		$url = $this->baseUrl . 'identity/token';
	}

    /**
     * Create new fulfillment
     */
    public function create()
    {

    }

    /**
     * Sales status retrieval
     */
    public function get()
    {

    }

	/**
	 * Update data
     * @param string $status
	 */
	public function update($status)
	{

	}
}