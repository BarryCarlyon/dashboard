<?php

$data = '{ "configuration": { "copy_config_files": true, "email_notify": false, "notification_addresses": [ "barry@barrycarlyon.co.uk" ] }, "end_revision": { "author": "Barry Carlyon", "email": "barry@barrycarlyon.co.uk", "message": "Update WordPress properly", "ref": "52b14186da5b7b1fb47cdf44679d5894e0c9b867", "timestamp": "2012-10-27T00:19:58+01:00" }, "files": { "cc951d25-1c62-1ca7-2523-87b72731e472": { "changed": [], "removed": [] } }, "identifier": "178aeb71-acd1-dd4f-6d89-261547f73f6e", "project": { "name": "Trains", "permalink": "trains", "public_key": "ssh-rsa AAAAB3NzaC1yc2EAAAABIwAAAQEA2ArhKEPIydKd2dUPi9Mwz3KavKznwlA1F8b7XuFgDXoA3JkTNsJ+BSbiKD/r97ljzt3dRWE9bf0vMubv8NLR43kf+5+wXjhXuUTEbS2ME7f8KivIR0P6rbA69bLLvma/r+DIH0wJBw9qI/v0bmj9hBEYNvpAhNWSKjzrWQ3xaVPepEjsUFysntGPmr89i9nA8pYyS0iM7xWos3zzwDvUni7t1mxkeMbknJ/cyKgotkrukXy52Fkr7p0ez4r08FGskOVmFMpS29ZggRTDtURa5EYF2h/Lw5HKukdOCBRhWis61IjIlY9ojv2/jS3Iy1gV7791QijCGiBmmWwH0ZBHOw== DeployHQ.com Key for trains", "repository": { "branch": "dev", "cached": true, "hosting_service": { "commits_url": "http://barrycarlyon.codebasehq.com/projects/personalprojects/repositories/trains/commits/dev", "name": "Codebase", "tree_url": "http://barrycarlyon.codebasehq.com/projects/personalprojects/repositories/trains/tree/dev", "url": "http://www.codebasehq.com" }, "port": null, "scm_type": "git", "url": "git@codebasehq.com:barrycarlyon/personalprojects/trains.git", "username": "" } }, "servers": [ { "auto_deploy_url": "https://barrycarlyon.deployhq.com/deploy/trains/to/dev-server/3hid5yae4j1t", "hostname": "barrycarlyon.co.uk", "identifier": "cc951d25-1c62-1ca7-2523-87b72731e472", "last_revision": "52b14186da5b7b1fb47cdf44679d5894e0c9b867", "name": "Dev Server", "notify_email": false, "port": 22, "preferred_branch": "dev", "protocol_type": "ssh", "server_path": "/var/www/trains/dev", "use_ssh_keys": true, "username": "deploybot" } ], "start_revision": { "author": "Barry Carlyon", "email": "barry@barrycarlyon.co.uk", "message": "Update WordPress properly", "ref": "52b14186da5b7b1fb47cdf44679d5894e0c9b867", "timestamp": "2012-10-27T00:19:58+01:00" }, "status": "completed", "timestamps": { "completed_at": "2012-11-20T00:32:32Z", "duration": null, "queued_at": "2012-11-20T00:32:27Z", "started_at": "2012-11-20T00:32:28Z" } }';
$signature = 'b+lvBBGD9b9g/kp4DIRohCEo4YD5YLo774rC94uqsdweFmybiRCydV3VJgPY 6rTJGuNi3hWci7FFj9WDImjKhq/ZMV1w5hR7CtqDP2InSB9Qduz54PY4lQGe 1Cym+cg46HtVnO8yEWz4cmdFO9h5Fazf7hQ0O+3J9ayemVAIdnk=';

$data = $_GET['payload'];
$signature = $_GET['signature'];

//echo base64_decode($signature);
echo "\n";
//$data = $_POST['payload'];
//$signature = $_POST['signature'];

/*
$fp = fopen("./public.key", "r");
$cert = fread($fp, 8192);
fclose($fp);
$pubkeyid = openssl_get_publickey($cert);
*/
$pubkeyid = file_get_contents('./public.key');
/*
$pubkeyid = '-----BEGIN PUBLIC KEY-----
MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDXJcP2N6NtcN26Q8nVaidXOA0w
RxWK2HQTblIaQdGRDjqTvhrSlFuV5N4jz7w/w8uskP20G7ZQ+CkHwIXrWk76KZJn
pdoOHPO6AqRmEFgV5Q6Y1CR77mvnT9O21hTnfzfyyiAdQC2oO8M9/jeLRPTAqmkG
xdQa8iepUz4BwrrHmwIDAQAB
-----END PUBLIC KEY-----
';
*/

echo 'a';
echo "\n";

// state whether signature is okay or not
$ok = openssl_verify($data, $signature, $pubkeyid);
if ($ok == 1) {
    echo "good";
} elseif ($ok == 0) {
    echo "bad";
} else {
    echo "ugly, error checking signature";
}

echo "\n";
echo 'b';
echo "\n";

// state whether signature is okay or not
$ok = openssl_verify($data, base64_decode($signature), $pubkeyid);
if ($ok == 1) {
    echo "good";
} elseif ($ok == 0) {
    echo "bad";
} else {
    echo "ugly, error checking signature";
}

/*
echo "\n";
echo 'c';
echo "\n";

// state whether signature is okay or not
$ok = openssl_verify($data, $signature, base64_decode($pubkeyid));
if ($ok == 1) {
    echo "good";
} elseif ($ok == 0) {
    echo "bad";
} else {
    echo "ugly, error checking signature";
}

echo "\n";
echo 'd';
echo "\n";

// state whether signature is okay or not
$ok = openssl_verify($data, base64_decode($signature), base64_decode($pubkeyid));
if ($ok == 1) {
    echo "good";
} elseif ($ok == 0) {
    echo "bad";
} else {
    echo "ugly, error checking signature";
}
*/
// free the key from memory
//openssl_free_key($pubkeyid);
echo "\n";
