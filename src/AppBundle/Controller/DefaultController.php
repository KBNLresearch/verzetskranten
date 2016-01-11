<?php

namespace AppBundle\Controller;

use GuzzleHttp\Client as HttpClient;
use Pandoc\Pandoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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
        $dao = $this->get('app.dao.kb');

        return $this->render('default/index.html.twig', [
            'dao' => $dao,
        ]);
    }

    /**
     * @Route("/preview/{winkelnr}", name="preview_article")
     * @param  Request $request
     * @param  integer $winkelnr
     * @return Response
     * @throws \Pandoc\PandocException
     */
    public function previewAction(Request $request, $winkelnr)
    {
        $dao    = $this->get('app.dao.kb');
        $twig   = $this->get('twig');
        $pandoc = new Pandoc();


        $result = $dao->dopBladMetDetails('"' . $winkelnr . '"');
        $count  = $result->numRows();
        if (0 == $count) {
            $msg = sprintf('Query for WinkelID %d yielded no results', $winkelnr);
            throw new NotFoundHttpException($msg, null, 404);
        } elseif (1 < $count) {
            $msg = sprintf('Query for WinkelID %d yielded multiple results', $winkelnr);
            throw new \LogicException($msg);
        }

        $wikiText = $twig->render('default/wiki.html.twig', [
            'blad' => $result[0],
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
        $wikiText   = $request->request->get('wikitext');
        $httpClient = new HttpClient([
            'base_uri' => 'http://nl.wikipedia.org',
        ]);

        $response = $httpClient->post('w/api.php', [
            'form_params' => [
                'action'       => 'parse',
                'format'       => 'json',
                'prop'         => 'text',
                'contentmodel' => 'wikitext',
                'text'         => $wikiText
            ]
        ]);

        $htmlPreview = json_decode($response->getBody())->parse->text->{'*'};

        $response = new Response();
        $response->setContent($htmlPreview);

        return $response;
    }

    /**
     * @Route("/download-wiki", name="download_wiki")
     * @param  Request $request
     * @throws \Exception
     */
    public function downloadAction(Request $request)
    {
        $dao         = $this->get('app.dao.kb');
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
            $fileSystemSafeTitle = strtolower(preg_replace('/[^a-z-0-9\-\_]/i', '-', $result->titel));
            $filename            = sprintf('%s-%s.wiki', $result->WinkelNr, $fileSystemSafeTitle);
            $contents            = $twig->render('default/wiki.html.twig', [
                'blad' => $result,
            ]);

            $zipArchive->addFromString($filename, $contents);
        }

        if (true !== $zipArchive->close()) {
            $msg = sprintf("Failed to properly close zip archive '%s'.", $archiveName);
            throw new \Exception($msg);
        }

        Response::create(file_get_contents($archiveName), 200, [
            'Content-Type'              => 'application/zip',
            'Content-Transfer-Encoding' => 'Binary',
            'Content-Disposition'       => 'attachment; filename="' . basename($archiveName) . '"',
        ])->send();
    }
}
