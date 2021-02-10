<?php

namespace App\Controllers;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use \App\Models\User;
use \App\Helpers\Search;
defined('BASEPATH') OR exit('No direct script access allowed');

class AjaxController extends Controller {
    


    public function search($request,$response) {
        $post     = clean($request->getParams());
        $route    = $request->getAttribute('route')->getName();
        $search   = new Search($post,$route);
        $view     = '/admin/elements/instant-search.twig';
        $lists    = $search->search();
        $number   = $search->number();
        return $this->view->render($response, $view, compact('lists'));
    }

        


    public function delete($request,$response,$args) {
        $list = \App\Models\Lists::find($args['id']);
        $list->deleted_at = \Carbon\Carbon::now();
        $list->save();
        header('Location: '.$_SERVER['PHP_SELF']);
die;
    }

    


}
