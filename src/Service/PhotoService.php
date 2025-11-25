<?php

namespace App\Service;

use App\Repository\PhotoRepository;
use App\Entity\Photo;
use Doctrine\ORM\EntityManagerInterface;

class PhotoService
{
    private EntityManagerInterface $em;
    private PhotoRepository $photoRepository;

    public function __construct(EntityManagerInterface $em, PhotoRepository $photoRepository)
    {
        $this->em = $em;
        $this->photoRepository = $photoRepository;
    }

    /**
     * Récupérer une photo par son ID
     *
     * @param int $id
     * @return Photo|null
     */
    public function getPhotoById(int $id): ?Photo
    {
        return $this->photoRepository->find($id);
    }
}
