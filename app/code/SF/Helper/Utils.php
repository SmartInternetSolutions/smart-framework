<?php

class SF_Helper_Utils
{
	static public function outputCSV($data)
	{
		$outstream = fopen("php://output", 'w');

		function __outputCSV(&$vals, $key, $filehandler)
		{
			fputcsv($filehandler, $vals, ';', '"');
		}

		array_walk($data, '__outputCSV', $outstream);

		fclose($outstream);
	}
}