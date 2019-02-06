<?php
    include dirname(__DIR__) . "/vendor/autoload.php";
    putenv('FTP_HOST=phhw-140602.cust.powerhosting.dk');
    putenv('FTP_USER=kbharkiv');
    putenv('FTP_PASSWORD=***REMOVED***');
    $host = getenv('FTP_HOST');
    $user = getenv('FTP_USER');
    $password = getenv('FTP_PASSWORD');

    $ftp = new \FtpClient\FtpClient();
    $ftp->connect($host, false, 21);
    $ftp->login($user, $password);
    $ftp->pasv(true);
    var_dump($ftp->chdir('public_html'));
    var_dump($ftp->nlist('.'));
  /*  var_dump($ftp->pwd());
    var_dump($ftp->chdir('public_html'));
    var_dump($ftp->pwd());
    var_dump($ftp->chdir('1508'));
    var_dump($ftp->nlist('.'));
*/
    // Creates a directory
   // $ftp->mkdir('public_html/1508/test_bo');
   // $ftp->putAll(dirname(__DIR__), 'public_html/1508/test_bo', FTP_BINARY);