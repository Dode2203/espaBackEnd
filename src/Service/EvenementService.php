<?php

namespace App\Service;

use App\Entity\Evenement;
use App\Repository\EvenementRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Photo;
use App\Repository\TypeEventRepository;
use Symfony\Component\Uid\Uuid;
class EvenementService
{
    private EntityManagerInterface $em;
    private EvenementRepository $evenementRepository;
    private TypeEventRepository $typeEventRepository;

    public function __construct(EntityManagerInterface $em, EvenementRepository $evenementRepository , TypeEventRepository $typeEventRepository )
    {
        $this->em = $em;
        $this->evenementRepository = $evenementRepository;
        $this->typeEventRepository = $typeEventRepository;
    }
    public function getById(string $id): ?Evenement
    {
        try {
            $uuid = Uuid::fromString($id);
            return $this->evenementRepository->getById($uuid);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Crée et enregistre un événement en base
     *
     * @param Evenement $evenement
     * @return Evenement
     */
    public function createEvenement(Evenement $evenement, ?Photo $photo = null): Evenement
    {
        $conn = $this->em->getConnection();
        $conn->beginTransaction(); 

        try {
            // 1️⃣ Vérification du type d'événement
            $this->validateAndGetTypeEvent($evenement);

            // 2️⃣ Enregistrement de la photo si fournie
            if ($photo !== null) {
                $this->em->persist($photo);
                $this->em->flush();
                $evenement->setPhoto($photo);
            }

            // 3️⃣ Sauvegarde de l'événement
            $this->em->persist($evenement);
            $this->em->flush();

            $conn->commit();
            return $evenement;

        } catch (\Exception $e) {
            $conn->rollBack();
            throw $e;
        }
    }
    private function validateAndGetTypeEvent(Evenement $evenement): void
    {
            $idTypeEvent = $evenement->getTypeEvent()->getId();

            $typeEvent = $this->getTypeEventById($idTypeEvent);

        if (!$typeEvent) {
            throw new \Exception("TypeEvent non trouvé pour l'ID : $idTypeEvent");
        }

            // 🔄 Associer le vrai TypeEvent à l'objet Evenement
            $evenement->setTypeEvent($typeEvent);
            if ($idTypeEvent === 2) {
                $evenement->setDebut(null);
                $evenement->setFin(null);
            }
    }


    public function getTypeEventById(int $id)
    {
        return $this->typeEventRepository->find($id);
    }
    public function getEvenementDateBefore(\DateTimeInterface $date,$limit): array
    {
        return $this->evenementRepository->findEvenementsBeforeDate($date,$limit);
    }


}
