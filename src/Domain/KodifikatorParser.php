<?php

namespace Kodifikator\Domain;

use GuzzleHttp\Client;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Parser for the Codifier of administrative-territorial units and territorial communities.
 *
 * This class fetches and parses the webpage of the Ukrainian Ministry of Development
 * for local self-government development to extract links to the latest XLSX files
 * and other related documents of the codifier.
 *
 * Official page:
 * https://mindev.gov.ua/diialnist/rozvytok-mistsevoho-samovriaduvannia/kodyfikator-administratyvno-terytorialnykh-odynyts-ta-terytorii-terytorialnykh-hromad
 * 
 * License: https://creativecommons.org/licenses/by/4.0/deed.en
 * 
 * Usage example:
 * $parser = new KodifikatorParser();
 * $xlsxUrl = $parser->getLatestLink();
 * 
 * Dependencies:
 * composer require guzzlehttp/guzzle symfony/dom-crawler symfony/css-selector
 */
class KodifikatorParser
{
    private string $url = 'https://mindev.gov.ua/diialnist/rozvytok-mistsevoho-samovriaduvannia/kodyfikator-administratyvno-terytorialnykh-odynyts-ta-terytorii-terytorialnykh-hromad';


    /**
     * Retrieves the download link to the latest XLSX file from the codifier page.
     *
     * The method fetches and parses the webpage content, extracts document sets by date/title,
     * and returns the URL of the most recent XLSX file.
     *
     * @return string|null Absolute URL to the latest XLSX file, or null if none found
     */
    public function getLatestLink(): ?string
    {
        $links = $this->process();

        // Assuming keys are sorted by insertion order (first is the latest)
        $latestSet = reset($links); 
        foreach ($latestSet as $file) {
            if (strtolower($file['extension']) === 'xlsx') {
                return $this->makeAbsoluteUrl($file['href']);
            }
        }

        return null;
    }

    /**
     * Fetches the codifier webpage and parses document links grouped by date/title.
     *
     * Parses HTML content under the 'div.editor-content' and looks for paragraphs with strong elements
     * to determine document titles and associated links with their metadata (extension, size).
     *
     * @return array Associative array where keys are document titles (usually with dates)
     *               and values are arrays of document info indexed by file extension.
     *               Example:
     *               [
     *                   "Кодифікатор 01.01.2023" => [
     *                       "xlsx" => ["text" => "...", "href" => "...", "size" => "..."],
     *                       "pdf" => [...],
     *                   ],
     *                   ...
     *               ]
     */
    public function process(): array
    {
        $html = $this->fetchHtml();
        $crawler = new Crawler($html);

        $contentDiv = $crawler->filter('div.editor-content');

        $result = [];
        $currentTitle = null;
        
        // Filter all <p> tags inside the content div
        $contentDiv->filter('p')->each(function (Crawler $node) use (&$result, &$currentTitle) {

            // Check if the paragraph contains a <strong> element (title/date)
            $strong = $node->filter('strong');
            if ($strong->count() === 1) {
                $text = trim($strong->text());

                // If the text ends with a date format dd.mm.yyyy, consider it a title
                if (preg_match('/\s\d{2}\.\d{2}\.\d{4}$/', $text)) {
                    $currentTitle = $text;
                    $result[$currentTitle] = [];
                }
            }

            if (empty($currentTitle)) {
                return;
            }

            // Find links inside the paragraph <p>
            $links = $node->filter('a');

            foreach ($links as $link) {
                $linkCrawler = new Crawler($link);
                $href = urldecode($linkCrawler->attr('href'));
                $extension = $linkCrawler->attr('data-extension') ?: strtolower(pathinfo(parse_url($href, PHP_URL_PATH), PATHINFO_EXTENSION));

                if (!empty($href) && in_array($extension, ['pdf', 'xlsx', 'docx'])) {
                    $text = $this->sanitizeText($linkCrawler->text());
                    $size = $this->sanitizeSize($linkCrawler->attr('data-size'));
                    $href = $this->makeAbsoluteUrl($href);

                    $result[$currentTitle][$extension] = [
                        'text' => $text,
                        'href' => $href,
                        'size' => $size,
                    ];
                }
            }
        });

        return $result;
    }

    /**
     * Fetches the HTML content of the codifier page.
     *
     * @return string|false HTML content on success, false on failure
     */
    private function fetchHtml(): string|false
    {
        $client = new Client();
       
        try {
            $response = $client->request('GET', $this->url);
        } catch (\Exception $e) {
            throw new \RuntimeException('Failed to fetch HTML: ' . $e->getMessage());
        }

        if ($response->getStatusCode() !== 200) {
            throw new \RuntimeException('Unexpected HTTP status: ' . $response->getStatusCode());
        }

        return $response->getBody()->getContents();
    }


    private function sanitizeText(string $text): string
    {
        $clean = strip_tags($text);
        $clean = trim(preg_replace('/\s+/', ' ', $clean));
        return $clean;
    }

    private function sanitizeSize(?string $size): ?string
    {
        if ($size === null) {
            return null;
        }

        $clean = preg_replace('/\D+/', '', $size);
        return $clean === '' ? null : $clean;
    }

    /**
     * Converts a possibly relative URL to an absolute URL based on the base site URL.
     *
     * @param string $href URL that may be relative or absolute
     * @return string Absolute URL
     */
    private function makeAbsoluteUrl(string $href): string
    {
        $href = strip_tags($href);
        if (str_starts_with($href, 'http')) {
            return $href;
        }

        return 'https://mindev.gov.ua' . $href;
    }
}
