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
     * @return Response
     */
    public function homeAction(Request $request)
    {
        $dao = $this->get('app.dao.dop');

        $session = new Session();

        return $this->render('default/home.html.twig', [
            'dao'   => $dao,
            'login' => $session->get('login'),
        ]);
    }

    /**
     * @Route("/index", name="index")
     * @param  Request $request
     * @return Response
     */
    public function indexAction(Request $request)
    {
        $dao       = $this->get('app.dao.dop');
        $mediawiki = $this->get('app.service.mediawiki');
        $twig      = $this->get('twig');

        $list       = $dao->bladenlijst;
        $partitions = array_reduce($list->getArrayCopy(), function (array $carry, $item) {
            $idx = (isset($item->Titel) && !empty($item->Titel) && preg_match('/([a-z0-9]{1})/i', $item->Titel, $matches))
                ? $matches[1] : '?';

            if (!array_key_exists($idx, $carry)) {
                $carry[$idx] = [];
            }

            $carry[$idx][] = $item;
            return $carry;
        }, []);


        ksort($partitions, SORT_NATURAL);
        foreach ($partitions as &$partition) {
            usort($partition, function ($a, $b) {
                return strnatcasecmp($a->WinkelNr, $b->WinkelNr);
            });
        }

        $wikiText = $twig->render('default/wiki-index.html.twig', [
            'partitions' => $partitions,
        ]);

        $htmlPreview = $mediawiki->preview($wikiText);

        return $this->render('default/preview.html.twig', [
            'wiki_text'    => $wikiText,
            'html_preview' => $htmlPreview,
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
        $relaties = $dao->dopGerelateerdeBladen('"' . $winkelnr . '"');
        $reproductieMethoden = $dao->dopReproductiemethodeVanBlad('"' . $winkelnr . '"');
        $inhoudsVormen       = $dao->dopInhoudsvormVanBlad('"' . $winkelnr . '"');

        $wikiText = $twig->render('default/wiki.html.twig', [
            'blad'     => $bladen[0],
            'plaatsen' => $plaatsen,
            'personen' => $personen,
            'drukkers' => $drukkers,
            'relaties' => $relaties,
            'reproductieMethoden' => $reproductieMethoden,
            'inhoudsVormen'       => $inhoudsVormen,
        ]);

        $htmlPreview = $mediawiki->preview($wikiText);

        return $this->render('default/preview.html.twig', [
            'wiki_title'   => $bladen[0]->titelWP,
            'wiki_text'    => $wikiText,
            'html_preview' => $htmlPreview,
        ]);
    }

    /**
     * @Route("/nuke-cache", name="nuke_cache")
     * @return Response
     */
    public function nukeCacheAction()
    {
        $cache = $this->get('zend_cache');

        try {
            $success = $cache->flush();
        } catch (\Exception $e) {
            $success = false;
        }

        $body = ['success' => $success];
        return Response::create(json_encode($body), 200, [
            'Content-Type' => 'application/json',
        ]);
    }
}
