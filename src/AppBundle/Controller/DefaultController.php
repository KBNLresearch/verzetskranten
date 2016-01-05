<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends Controller
{
    /**
     * @Route("/", name="homepage")
     * @param  Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request)
    {
        $dao = $this->get('app.dao.kb');

        return $this->render('default/index.html.twig', [
            'dao' => $dao,
        ]);
    }

    /**
     * @Route("/preview/{winkelnr}", name="preview_article")
     * @param  Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function previewAction(Request $request)
    {
        $dao = $this->get('app.dao.kb');



        return $this->render('default/wiki.html.twig', [
            'blad' => $dao->dopWinkel
        ]);
    }
}
