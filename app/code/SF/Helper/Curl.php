<?php

class SF_Helper_Curl
{
	private $uri;
	private $method = "GET";
	private $entity;
	public function __construct($uri)
	{
		$this->uri = $uri;
	}

	public function setMethod($method)
	{
		$this->method = $method;
	}

	public function setEntity($entity)
	{
		$this->entity = json_encode($entity);
	}

	public function execute()
	{
		$ch = curl_init($this->uri);
		// curl_setopt($ch, CURLOPT_FILE, $fp);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		switch ($this->method)
		{
			case 'POST':
				curl_setopt($ch, CURLOPT_POST, 1);
			break;
			case 'PUT':
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
			break;
		}
		if(isset($this->entity)) {
			curl_setopt($ch, CURLOPT_POSTFIELDS, $this->entity);
		}
//		echo('$this->entity: ' . $this->entity);
//		echo '<br>';
		//curl_setopt($ch, CURLOPT_VERBOSE, 1);
		$json = curl_exec($ch);
//		echo ('$json: ' . $json);
		curl_close($ch);
		return $json;
	}
}