<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;

class WikiController extends Controller
{
    /**
     * @Route("/wiki/convert", name="wiki_convert")
     * @param  Request $request
     * @return string
     */
    public function convertAction(Request $request)
    {
        $mediawiki   = $this->get('app.service.mediawiki');
        $wikiText    = $request->request->get('wikitext');
        $htmlPreview = $mediawiki->preview($wikiText);

        return Response::create($htmlPreview, 200, [
            'Content-Type' => 'text/html',
        ]);
    }

    /**
     * @Route("/wiki/download", name="wiki_download")
     * @param  Request $request
     * @return Response
     * @throws \Exception
     */
    public function downloadAction(Request $request)
    {
        $dao         = $this->get('app.dao.dop');
        $twig        = $this->get('twig');

        $winkelNrs   = json_decode($request->get('ids', '[]'));
        $zipArchive  = new \ZipArchive();
        $zipErrorMap = [
            \ZipArchive::ER_MULTIDISK   => 'Multi-disk zip archives not supported',
            \ZipArchive::ER_RENAME      => 'Renaming temporary file failed',
            \ZipArchive::ER_CLOSE       => 'Closing zip archive failed',
            \ZipArchive::ER_SEEK        => 'Seek error',
            \ZipArchive::ER_READ        => 'Read error',
            \ZipArchive::ER_WRITE       => 'Write error',
            \ZipArchIve::ER_CRC         => 'CRC error',
            \ZipArchive::ER_ZIPCLOSED   => 'Containing zip archive was closed',
            \ZipArchive::ER_NOENT       => 'No such file.',
            \ZipArchive::ER_EXISTS      => 'File already exists.',
            \ZipArchive::ER_OPEN        => 'Cannot open file.',
            \ZipArchive::ER_TMPOPEN     => 'Failure to create temporary file',
            \ZipArchive::ER_ZLIB        => 'Zlib error',
            \ZipArchive::ER_MEMORY      => 'Malloc failure.',
            \ZipArchive::ER_CHANGED     => 'Entry has been changed',
            \ZipArchive::ER_COMPNOTSUPP => 'Compression method not supported',
            \ZipArchive::ER_EOF         => 'Premature EOF',
            \ZipArchive::ER_INVAL       => 'Invalid argument.',
            \ZipArchive::ER_NOZIP       => 'Not a zip archive.',
            \ZipArchive::ER_INTERNAL    => 'Internal error',
            \ZipArchive::ER_INCONS      => 'Zip archive inconsistent.',
            \ZipArchive::ER_REMOVE      => "Can't remove file",
            \ZipArchive::ER_DELETED     => 'Entry has been deleted',
        ];

        $archiveName = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'wikis-' . time() . '.zip';
        $opened      = $zipArchive->open($archiveName, \ZipArchive::CREATE | \ZipArchive::OVERWRITE | \ZipArchive::CHECKCONS);
        if (true !== $opened) {
            $msg = sprintf("Failed to create zip archive. %s", $zipErrorMap[$opened]);
            throw new \Exception($msg);
        }

        $arg = implode(',', array_map(function($winkelNr) {
            return sprintf('"%s"', $winkelNr);
        }, $winkelNrs));

        $results = $dao->dopBladMetDetails($arg);
        foreach($results as $result) {
            $plaatsen = $dao->plaatsVanUitgave('"' . $result->WinkelNr . '"');
            $personen = $dao->dopPersonenBijBlad('"' . $result->WinkelNr . '"');
            $drukkers = $dao->dopDrukkerijVanBlad('"' . $result->WinkelNr . '"');
            $contents = $twig->render('default/wiki.html.twig', [
                'blad'     => $result,
                'plaatsen' => $plaatsen,
                'personen' => $personen,
                'drukkers' => $drukkers,
            ]);

            $fileSystemSafeTitle = strtolower(preg_replace('/[^a-z-0-9\-\_]/i', '-', $result->titel));
            $filename            = sprintf('%s-%s.wiki', $result->WinkelNr, $fileSystemSafeTitle);

            $zipArchive->addFromString($filename, $contents);
        }

        if (true !== $zipArchive->close()) {
            $msg = sprintf("Failed to properly close zip archive '%s'.", $archiveName);
            throw new \Exception($msg);
        }

        return Response::create(file_get_contents($archiveName), 200, [
            'Content-Type'              => 'application/zip',
            'Content-Transfer-Encoding' => 'Binary',
            'Content-Disposition'       => 'attachment; filename="' . basename($archiveName) . '"',
        ]);
    }

    /**
     * @Route("/wiki/login", name="wiki_login")
     * @param  Request $request
     * @return Response
     */
    public function loginAction(Request $request)
    {
        $mediawiki = $this->get('app.service.mediawiki');

        $session = new Session();

        $result = $mediawiki->login($request->request->get('username'), $request->request->get('password'));
        $body   = (null != $result)
            ? ['success' => true, 'userid' => $result->lguserid, 'username' => $result->lgusername]
            : ['success' => false];

        $session->set('login', $body);

        return Response::create(json_encode($body), 200, [
            'Content-Type' => 'application/json',
        ]);
    }

    /**
     * @Route("/wiki/logout", name="wiki_logout")
     * @param  Request $request
     * @return Response
     */
    public function logoutAction(Request $request)
    {
        $mediawiki = $this->get('app.service.mediawiki');

        $session = new Session();

        $success = $mediawiki->logout();
        if (true == $success) {
            $session->remove('login');
        }

        $body = ['success' => $success];

        return Response::create(json_encode($body), 200, [
            'Content-Type' => 'application/json',
        ]);
    }

    /**
     * @Route("/wiki/edit", name="wiki_edit")
     * @param  Request $request
     * @return Response
     */
    public function editAction(Request $request)
    {
        $dao       = $this->get('app.dao.dop');
        $twig      = $this->get('twig');
        $winkelNrs = json_decode($request->get('ids', '[]'));
        $mediawiki = $this->get('app.service.mediawiki');
        $session   = new Session();

        $success = false;
        $message = null;

        try {
            // check login
            $login = $session->get('login');
            if (null == $login || !property_exists($login, 'userid')) {
                throw new \Exception('Not logged in');
            }

            // get edit token if not already present
            if (null == ($token = $session->get('edittoken', null))) {
                $token = $mediawiki->token('edittoken');
            }

            // query all the selected bladen
            $arg = implode(',', array_map(function($winkelNr) {
                return sprintf('"%s"', $winkelNr);
            }, $winkelNrs));

            $results = $dao->dopBladMetDetails($arg);
            foreach($results as $result) {
                $plaatsen = $dao->plaatsVanUitgave('"' . $result->WinkelNr . '"');
                $personen = $dao->dopPersonenBijBlad('"' . $result->WinkelNr . '"');
                $drukkers = $dao->dopDrukkerijVanBlad('"' . $result->WinkelNr . '"');
                $wikitext = $twig->render('default/wiki.html.twig', [
                    'blad'     => $result,
                    'plaatsen' => $plaatsen,
                    'personen' => $personen,
                    'drukkers' => $drukkers,
                ]);

                // post to wikipedia
                $mediawiki->edit($token, $result->titelWP, $wikitext);
            }

            $success = true;
            $message = 'All selected lemmas exported successfully';
        } catch (\Exception $e) {
            $message = $e->getMessage();
        }

        $body = ['success' => $success, 'message' => $message];
        return Response::create(json_encode($body), 200, [
            'Content-Type' => 'application/json',
        ]);
    }
}
