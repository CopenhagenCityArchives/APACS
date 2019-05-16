<?php
    include dirname(__DIR__) . "/vendor/autoload.php";
    
    #$dotenv = \Dotenv\Dotenv::create('/etc/', '.env');
    #$dotenv->load();

    $host = getenv('KBHKILDER_FTP_HOST');
    $user = getenv('KBHKILDER_FTP_USER');
    $password = getenv('KBHKILDER_FTP_PASSWORD');
    $ftp_path = getenv('KBHKILDER_FTP_PATH');

    echo 'connecting using ' . $host . PHP_EOL;

    $path = $ftp_path;
    $srcPath = dirname(__DIR__);

    $ftp = new \FtpClient\FtpClient();
    echo 'connecting' . PHP_EOL;
    $ftp->connect($host, false, 21,15);
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
    try
    {
      $ftp->rmdir($path, true);
    }
    catch(Exception $e) {
    
    } 
    
    echo 'creating directory ' . $path . PHP_EOL;
    // Create the directory    
    $ftp->mkdir($path);
    
    echo 'uploading files from ' . $srcPath . ' to ' . $path . PHP_EOL;
    //Put all files from parent folder
    $ftp->putAll($srcPath, $path, FTP_BINARY);

    echo 'all done' . PHP_EOL;
