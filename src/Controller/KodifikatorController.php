<?php

namespace Kodifikator\Controller;


use Kodifikator\Service\KodifikatorParser;
use Kodifikator\Service\KodifikatorUploader;
use Kodifikator\Service\KodifikatorImport;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controller for handling Kodifikator-related operations via HTTP.
 *
 * Supports parsing Kodifikator metadata, fetching XLSX files,
 * and importing data into the system.
 */
class KodifikatorController extends AbstractController
{
    
    public function __construct(
        private KodifikatorUploader $uploader,
        private KodifikatorParser $parser,
        private KodifikatorImport $import
    ) {}
    
    /**
     * Parses the Kodifikator page and returns the extracted document links as JSON.
     *
     * This endpoint fetches the Kodifikator page, parses available document URLs (XLSX, PDF, DOCX),
     * grouped by release date/title, and returns the structured data.
     *
     * @param KodifikatorParser $parser Service to fetch and parse Kodifikator data
     * @return JsonResponse JSON response with parsed Kodifikator document metadata
     */
    #[Route('/kodifikator/parse', name: 'kodifikator_parse')]
    public function parse(KodifikatorParser $parser): JsonResponse
    {
        $data = $parser->fetchAndParse();

        return $this->json($data);
    }


    /**
     * Fetches latest XLSX files and imports Kodifikator data.
     *
     * Runs the full update process (download + import).
     *
     * @return JsonResponse Status and messages about the operation.
     */
    #[Route('/kodifikator/update', name: 'kodifikator_update', methods: ['POST'])]
    public function update(): JsonResponse
    {
        try {
            $this->uploader->fetch();
            $this->import->import();

            return $this->json([
                'status' => 'success',
                'message' => 'Kodifikator updated and imported successfully.'
            ]);
        } catch (\Throwable $e) {
            return $this->json([
                'status' => 'error',
                'message' => 'Failed to update Kodifikator: ' . $e->getMessage()
            ], 500);
        }
    }

}
