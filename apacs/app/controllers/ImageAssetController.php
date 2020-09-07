<?php

class ImageAssetController extends MainController {
    
    public function GetImageAssetByIdentifier(){
        // Time used for stats
        $starttime = microtime(true);
           
        $fileId = NULL;
        if (isset($_GET["fileId"])) {
            $fileId = $_GET["fileId"];
        }
        
        if (is_numeric($fileId)) {
            $page = Pages::findFirst('id = ' . $fileId);
            
            if(!$page){
                $this->addStat('error_no_result', null, $starttime, $fileId);
                $this->returnError(404, "not found");
                return;
            }

            $file = $_SERVER['DOCUMENT_ROOT'] . '/../collections/' . $page->relative_filename_converted;
        
            if (!file_exists($file)) {
                $this->addStat('error_file_not_found', $file, $starttime, $fileId);
                $this->returnError(404, "not found");
                return;
            }
        
            $this->outputFileAndHeader($file, null);
        
            $this->addStat(null, '/' . $page->relative_filename_converted, $starttime, $fileId);
        } else {
            // Getting and parsing the actual page URL
            $parsedUrl = parse_url('http://' . $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"]);
     
            //Getting fragments of the path, used to validate the file type
            $pathFragments = explode('/', $parsedUrl['path']);
        
            $fileNameParts = explode('.', $pathFragments[count($pathFragments) - 1]);
        
            $length = count($fileNameParts);
        
            $fileExtension = $fileNameParts[$length - 1];
        
            //Extension found and of accepted type
            if ($length > 1 && ($fileExtension == 'jpg' || $fileExtension == 'jpeg' || $fileExtension == 'png' || $fileExtension == 'pdf' || $fileExtension == "zip")) {
                $file = '';
        
                //Reads and outputs file
                $file = $_SERVER['DOCUMENT_ROOT'] . $parsedUrl['path'];
        
                $file = implode('/', explode('/', $_SERVER['DOCUMENT_ROOT'], -1)) . $parsedUrl['path'];
        
              
                /*
                * Solution 1: Check if file exists, then read the file
                */

                if (!file_exists($file)) {
                    $this->response->setHeader("HTTP/1.0 404 Not Found");
                    //echo 'File not found: ' . $file;
                    
                    $fileLoaded = '';
                    $length = count($pathFragments);
                    for ($i = 2; $i < $length; $i++) {
                        $fileLoaded = $fileLoaded . '\\\\' . $pathFragments[$i];
                    }
        
                    $this->addStat('error_file_not_found', $fileLoaded, $starttime);
                } else {
                    $this->outputFileAndHeader($file, $fileExtension);
        
                    $fileLoaded = '';
                    $length = count($pathFragments);
                    for ($i = 2; $i < $length; $i++) {
                        $fileLoaded = $fileLoaded . '\\\\' . $pathFragments[$i];
                    }
        
                    $this->addStat($pathFragments[2], $fileLoaded, $starttime);
                }
        
                /*
                * Solution 2: Read file without checking if it exists
                */        
            }
        
        }
        
    }

    private function outputFileAndHeaderS3($fileId) {
        //https://s3-eu-west-1.amazonaws.com/kbhkilder/10
        $url = 'https://s3-eu-west-1.amazonaws.com/kbhkilder/' . $fileId;
        $this->response->setHeader('Content-type: image/jpeg');
        //$this->response->setHeader('Expires: ' . date(DATE_RFC1123, strtotime('+1 year')));
        //$this->response->setHeader('Location:' . $url);
        //file_get_contents($url);
        readfile($url);
    }

    private function outputFileAndHeader($file, $extension = null) {
        if ($extension == 'pdf') {
            $this->response->setHeader('Content-type: application/pdf');
            $this->response->setHeader('Access-Control-Allow-Origin: *');
            $this->response->setHeader('Access-Control-Allow-Headers: range');

            readfile($file);
            
            return;
        }
        
        $this->response->setHeader('Content-type: image/jpeg');
        $this->response->setHeader('Last-Modified: ' . date(DATE_RFC1123, filemtime($file)));
        $this->response->setHeader('Expires: ' . date(DATE_RFC1123, strtotime('+1 year')));
        $this->response->setHeader('Content-Disposition: attachment; filename="' . basename($file) . '"');
        
        readfile($file);
        
        return;
    }

    private function addStat($collection, $file, $starttime, $fileId = 'NULL') {
        
        // Skip stats from ksa
        if ($_SERVER['REMOTE_ADDR'] == '85.129.89.86') {
            return;
        }

        $stats = new Stats();
        $stats->collection = $collection;
        $stats->file = $file;
        $stats->loadTime = number_format(microtime(true) - $starttime, 6, '.', '');
        $stats->fileId = $fileId;
        $stats->ip = $_SERVER['REMOTE_ADDR'];

        $stats->save();
    }
}

