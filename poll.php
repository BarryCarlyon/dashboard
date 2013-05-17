<?php

$printer = array(
	7		=> '/Volumes/share/BarrySysAdmin/MyDocuments/Tenders/Tesco_Tender1/Inbox/',
	21		=> '/Volumes/share/BarrySysAdmin/MyDocuments/Tenders/Tesco_Tender2/Inbox/',
);
include('/Users/barrycarlyon/scripts/mssql/config.php');
include('/Users/barrycarlyon/scripts/mssql/common.php');

while(1) {
	$dir = __DIR__ . '/do/';

	$dir = new FilesystemIterator($dir);
foreach ($dir as $path => $fileinfo) {
    if ($fileinfo->isFile()) {
	$mag_id = $fileinfo->getFilename();

$print_path =  $printer[7];

$exec = '/usr/bin/scp -P 2020 -i /Users/barrycarlyon/.ssh/id_rsa fredaldo@fredaldous.co.uk:/home/fredaldo/www/var/tesco/remotefred/Inbox/' . $mag_id . '.csv ' . $print_path . '/.';
echo $exec . "\n";
exec($exec, $r, $return_var);

echo 'Printing' . "\n";
//	$exec = 'cd /Users/barrycarlyon/scripts/mssql/commands/ && php doatesco.php ' . $mag_id;

	unlink($path);
    }
}
sleep(1);

}
