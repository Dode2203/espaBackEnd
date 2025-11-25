<?php

namespace App\Controller\Api;

use App\Service\PhotoService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/photo')]
class PhotoController extends AbstractController
{
    private PhotoService $photoService;

    public function __construct(PhotoService $photoService)
    {
        $this->photoService = $photoService;
    }

    #[Route('/{id}', name: 'api_photo_get', methods: ['GET'])]
    public function getPhoto(int $id): Response
    {
        $photo = $this->photoService->getPhotoById($id);

        if (!$photo) {
            return $this->json([
                'status' => 'error',
                'message' => 'Photo non trouvée'
            ], 404);
        }

        // Lire le contenu binaire
        $binaire = stream_get_contents($photo->getBinaire());

        // Détecter le type MIME automatiquement
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->buffer($binaire);

        return new Response(
            $binaire,
            200,
            [
                'Content-Type' => $mimeType,
                'Content-Disposition' => 'inline; filename="photo_'.$id.'"'
            ]
        );
    }
}
