<?php

/*

    Controller for redirecting permalinks to concrete assets
    Supported endpoints:

    Sitepages:  /permalink/sitepages/[politforum|search]
    Posts:      /permalink/post/17-275010 (https://kbharkiv.dk/permalink/post/17-275010 -> https://www.kbharkiv.dk/sog-i-arkivet/sog-i-indtastede-kilder#/post/17-275010)
    Sources:    /permalink/source/118/1118713 -> https://kildeviser.kbharkiv.dk/#!?collection=118&item=1118713
    
*/

class PermalinkController extends MainController {
    private function redirect($newUrl){
        $this->response->setHeader("Location", $newUrl);
    }
    
    public function redirectToPage($pageName){
        switch ($pageName) {
            case 'search':
                $newUrl = 'https://kbharkiv.dk/brug-samlingerne/soeg-i-indtastede-kilder/';
                break;
            case 'politforum':
                $newUrl = 'https://static.kbharkiv.dk/forumarkiv/';
                break;
            default:
                $newUrl = 'https://kbharkiv.dk';
        }
        $this->redirect($newUrl);
    }

    public function redirectToPost($postId){
        $newUrl = 'https://kbharkiv.dk/sog-i-arkivet/sog-i-indtastede-kilder#/post/' . $postId;
        $this->redirect($newUrl);
    }

    public function redirectToSource($collectionId, $itemId){
        $newUrl = 'https://kildeviser.kbharkiv.dk/#!?collection='. $collectionId . '&item=' . $itemId;
        $this->redirect($newUrl);
    }
}