<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class DefaultController extends Controller
{
    /**
     * @Route("/", name="homepage")
     * @param  Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request)
    {
        $dao = $this->get('app.dao.dop');

        $session = new Session();

        return $this->render('default/index.html.twig', [
            'dao'   => $dao,
            'login' => $session->get('login'),
        ]);
    }

    /**
     * @Route("/preview/{winkelnr}", name="preview_article")
     * @param  Request $request
     * @param  integer $winkelnr
     * @return Response
     */
    public function previewAction(Request $request, $winkelnr)
    {
        $dao       = $this->get('app.dao.dop');
        $mediawiki = $this->get('app.service.mediawiki');
        $twig      = $this->get('twig');

        $bladen = $dao->dopBladMetDetails('"' . $winkelnr . '"');
        $count  = $bladen->numRows();
        if (0 == $count) {
            $msg = sprintf('Query for WinkelID %d yielded no results', $winkelnr);
            throw new NotFoundHttpException($msg, null, 404);
        }

        $plaatsen = $dao->plaatsVanUitgave('"' . $winkelnr . '"');
        $personen = $dao->dopPersonenBijBlad('"' . $winkelnr . '"');
        $drukkers = $dao->dopDrukkerijVanBlad('"' . $winkelnr . '"');

        $wikiText = $twig->render('default/wiki.html.twig', [
            'blad'     => $bladen[0],
            'plaatsen' => $plaatsen,
            'personen' => $personen,
            'drukkers' => $drukkers,
        ]);

        $htmlPreview = $mediawiki->preview($wikiText);

        return $this->render('default/preview.html.twig', [
            'wiki_text'    => $wikiText,
            'html_preview' => $htmlPreview,
        ]);
    }
}
