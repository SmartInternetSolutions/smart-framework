<?php

require_once SF::getInstance()->getBasePath() .'/lib/S3.php';

class SF_Controller_Helper extends SF_Controller_Abstract
{
    public function handleUpload()
    {
    	$filename = $_FILES['file-upload']['name'];
    	$file = $_FILES['file-upload']['tmp_name'];

    	$extension = explode(".", $filename);
    	var_dump($extension);
   		$extension = end($extension);
   		var_dump($extension);
		// AWS access info
		if (!defined('awsAccessKey')) define('awsAccessKey', '');
		if (!defined('awsSecretKey')) define('awsSecretKey', '');

		$bucketName = '';


		// Instantiate the class
		$s3 = new S3(awsAccessKey, awsSecretKey);

		$filename = substr(md5(time() . rand()), 5, 10).".".$extension;

		// Put our file (also with public read access)
		if ($s3->putObjectFile($file, $bucketName,  $filename, S3::ACL_PUBLIC_READ)) {
			$outputUrl = "https://s3-us-west-1.amazonaws.com/".$bucketName."/".$filename;
            echo '<!DOCTYPE html>' . "\n";
            echo '<html><body>';
            echo '<p>' . $outputUrl . '</p>';
            echo '</body></html>';
		}
    }

    public function handleResolvePosition()
    {
        $ll = preg_replace('/[^0-9,.]/', '', $this->getRequest()->getParam('latlng'));

        @$d = (array) json_decode(file_get_contents('http://maps.googleapis.com/maps/api/geocode/json?latlng=' . $ll . '&sensor=true'));

        if (!empty($d) && isset($d['results']) && isset($d['results'][0]) && isset($d['results'][0]->formatted_address)) {
            echo $d['results'][0]->formatted_address;
        }
    }

    public function handleLocale()
    {
//        $this->redirect($url);
    }
}
