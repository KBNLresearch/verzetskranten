<?php

namespace AppBundle\Controller;

use Pandoc\Pandoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

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
        $dao    = $this->get('app.dao.kb');
        $twig   = $this->get('twig');
        $pandoc = new Pandoc();

        $wikiText = $twig->render('default/wiki.html.twig', [
//            'blad' => $dao->dopWinkel,
        ]);

        $htmlPreview = $pandoc->convert($wikiText, 'mediawiki', 'html');

        return $this->render('default/preview.html.twig', [
            'wiki_text'    => $wikiText,
            'html_preview' => $htmlPreview,
        ]);
    }

    /**
     * @Route("/convert-wiki", name="convert_wiki")
     * @param  Request $request
     * @return string
     */
    public function convertAction(Request $request)
    {
        $pandoc = new Pandoc();

        $wikiText    = $request->request->get('wikitext');
        $htmlPreview = $pandoc->convert($wikiText, 'mediawiki', 'html');

        $response = new Response();
        $response->setContent($htmlPreview);

        return $response;
    }
}
