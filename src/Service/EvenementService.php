<?php

namespace App\Service;

use App\Entity\Evenement;
use App\Repository\EvenementRepository;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Photo;
use App\Repository\TypeEventRepository;
use App\Entity\TypeEvent;
use Symfony\Component\Uid\Uuid;
use App\Entity\Utilisateur;
class EvenementService
{
    private EntityManagerInterface $em;
    private EvenementRepository $evenementRepository;
    private TypeEventRepository $typeEventRepository;
    
    private UtilisateurRepository $utilisateurRepository;

    public function __construct(EntityManagerInterface $em, EvenementRepository $evenementRepository , TypeEventRepository $typeEventRepository ,UtilisateurRepository $utilisateurRepository)
    {
        $this->em = $em;
        $this->evenementRepository = $evenementRepository;
        $this->typeEventRepository = $typeEventRepository;
        $this->utilisateurRepository = $utilisateurRepository;
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
    public function createEvenement(Evenement $evenement, ?Photo $photo = null,int $userId=1): Evenement
    {
        $conn = $this->em->getConnection();
        $conn->beginTransaction(); 

        try {
            // 1️⃣ Vérification du type d'événement
            $user = $this->utilisateurRepository->find($userId);
            $status= $user->getStatus();
            if($status && $status->getName() !=='Actif'){
                throw new \Exception("Inactif");
            }

            if (!$user) {
                throw new \Exception("Utilisateur introuvable avec l'ID : $userId");
            }

            $evenement->setUtilisateur($user);
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
    public function getEvenementDateBefore(\DateTimeInterface $date,$limit,Utilisateur $user = null,TypeEvent $typeEvent = null): array
    {
        return $this->evenementRepository->findEvenementsBeforeDate($date,$limit,$user, $typeEvent);
    }
    public function getEvenementDateBeforeIdEvent(\DateTimeInterface $date,$limit, $idTypeEvent): array
    {
            $typeEvent = $this->typeEventRepository->find($idTypeEvent);
            if (!$typeEvent) {
                throw new \Exception("TypeEvent non trouvé pour l'ID : " . $idTypeEvent);
            }
            

        return $this->getEvenementDateBefore($date,$limit,null,$typeEvent);
    }
    public function getEventBefore(\DateTimeInterface $date,$limit): array
    {
        $idTypeEvent=1;
        return $this->getEvenementDateBeforeIdEvent($date,$limit, $idTypeEvent);
    }
    public function getNewsBefore(\DateTimeInterface $date,$limit): array
    {
        $idTypeEvent=2;
        return $this->getEvenementDateBeforeIdEvent($date,$limit, $idTypeEvent);
    }
    public function getEvenementDateBeforeId(\DateTimeInterface $date,$limit, $idUser): array
    {
            $user = $this->utilisateurRepository->find($idUser);
            $status= $user->getStatus();
            if($status && $status->getName() !=='Actif'){
                throw new \Exception("Inactif");
            }

        return $this->getEvenementDateBefore($date,$limit,$user);
    }
    public function updateEvenement(string $id, array $data, ?Photo $photo = null ): Evenement
    {
        $evenement = $this->evenementRepository->getById($id) ;

        if (!$evenement) {
            throw new \Exception("Événement introuvable");
        }

        if (isset($data['titre'])) {
            $evenement->setTitre($data['titre']);
        }

        if (isset($data['description'])) {
            $evenement->setDescription($data['description']);
        }

        if(isset($data['typeEventId'])){ 
            $typeEvent = $this->typeEventRepository->find($data['typeEventId']);
            if (!$typeEvent) {
                throw new \Exception("TypeEvent non trouvé pour l'ID : " . $data['typeEventId']);
            }

            $evenement->setTypeEvent($typeEvent);
            
            if ($data['typeEventId'] === 1) {
                
                $dateDebut = null;
                $dateFin = null;

                // 1. Création des objets DateTime
                if (isset($data['debut'])) {
                    $dateDebut = new \DateTime($data['debut']);
                }

                if (isset($data['fin'])) {
                    $dateFin = new \DateTime($data['fin']);
                }

                // 2. VALIDATION CLÉ : La date de début doit être antérieure à la date de fin
                if ($dateDebut !== null && $dateFin !== null) {
                    // La méthode compare les deux objets DateTime.
                    // Si $dateDebut est supérieur ou égal à $dateFin, on lance une exception.
                    if ($dateDebut >$dateFin) {
                        throw new \Exception("La date de début doit être antérieure à la date de fin de l'événement.");
                    }
                }

                // 3. Application des setters si les validations sont passées
                if ($dateDebut !== null) {
                    $evenement->setDebut($dateDebut);
                }
                if ($dateFin !== null) {
                    $evenement->setFin($dateFin);
                }
                
            }
            else if ($data['typeEventId'] === 2) {
                $evenement->setDebut(null);
                $evenement->setFin(null);
            }
        }
        
        if($photo !== null){
            $this->em->persist($photo);
            $this->em->flush();
            $evenement->setPhoto($photo);
        }

        $this->em->persist($evenement);
        $this->em->flush();

        return $evenement;
    }
    



}
