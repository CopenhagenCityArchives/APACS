<?php
    include dirname(__DIR__) . "/vendor/autoload.php";
    
    $host = getenv('FTP_HOST');
    $user = getenv('FTP_USER');
    $password = getenv('FTP_PASSWORD');
    $ftp_path = getenv('FTP_PATH');

    echo 'connecting using ' . $host . PHP_EOL;

    $path = $ftp_path;
    $srcPath = dirname(__DIR__);

    $ftp = new \FtpClient\FtpClient();
    echo 'connecting' . PHP_EOL;
    $ftp->connect($host, false, 21);
    $ftp->login($user, $password);
    $ftp->pasv(true);
  //  var_dump($ftp->chdir('public_html'));
  //  var_dump($ftp->nlist('.'));
  /*  var_dump($ftp->pwd());
    var_dump($ftp->chdir('public_html'));
    var_dump($ftp->pwd());
    var_dump($ftp->chdir('1508'));
    var_dump($ftp->nlist('.'));
*/
    
// Remove the directory and its contents
    echo 'removing directory ' . $path . PHP_EOL;
    $ftp->rmdir($path, true);
    
    echo 'creating directory ' . $path . PHP_EOL;
    // Create the directory    
    $ftp->mkdir($path);
    
    echo 'uploading files from ' . $srcPath . ' to ' . $path . PHP_EOL;
    //Put all files from parent folder
    $ftp->putAll($srcPath, $path, FTP_BINARY);

    echo 'all done' . PHP_EOL;