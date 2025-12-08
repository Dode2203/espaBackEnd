<?php

namespace App\Controller\Api;

use App\Entity\Evenement;
use App\Entity\Photo;
use App\Entity\TypeEvent;
use App\Service\EvenementService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Annotation\TokenRequired;
use App\Service\JwtTokenManager;
#[Route('/evenement')]
class EvenementController extends AbstractController
{
    private EvenementService $evenementService;
    private EntityManagerInterface $em;

    private JwtTokenManager $jwtTokenManager;

    public function __construct(
        EvenementService $evenementService,
        JwtTokenManager $jwtTokenManager
    ) {
        $this->evenementService = $evenementService;
        $this->jwtTokenManager = $jwtTokenManager;
    }

    #[Route('', name: 'api_evenement_create', methods: ['POST'])]
    #[TokenRequired(['Admin','Utilisateur'])]
    public function create(Request $request): JsonResponse
    {
        try {
            $token = $this->jwtTokenManager->extractTokenFromRequest($request);
            $arrayToken = $this->jwtTokenManager->extractClaimsFromToken($token);
            $idUser = $arrayToken['id']; // Récupérer l'id de l'utilisateur à partir du token
            $data = json_decode($request->getContent(), true);

            // Champs requis
            $requiredFields = ['titre', 'description', 'typeEventId'];
            $missingFields = [];

            $typeEventId = (int)($data['typeEventId'] ?? 2);
            
            if ($typeEventId === 1) {
                $requiredFields[] = 'debut';
                $requiredFields[] = 'fin';
            }

            foreach ($requiredFields as $field) {
                if (!isset($data[$field])) {
                    $missingFields[] = $field;
                }
            }

            if (!empty($missingFields)) {
                return new JsonResponse([
                    'status' => 'error',
                    'message' => 'Champs requis manquants : ' . implode(', ', $missingFields),
                    'missingFields' => $missingFields
                ], 400);
            }


            // Créer la photo si fournie
            $photo = null;
            if (!empty($data['photoBinaire'])) {
                $photo = new Photo();
                $photo->setBinaire(base64_decode($data['photoBinaire']));
                $photo->setDateInsertion(new \DateTime());
            }

            $evenement = new Evenement();
            $typeEvent = new TypeEvent();
            $typeEvent->setId($typeEventId);

            $evenement->setTitre($data['titre'])
                    ->setDescription($data['description'])
                    ->setDebut(isset($data['debut']) ? new \DateTime($data['debut']) : null)
                    ->setFin(isset($data['fin']) ? new \DateTime($data['fin']) : null)
                    ->setTypeEvent($typeEvent);
            $debut = $evenement->getDebut();
            $fin = $evenement->getFin();
            if ($debut && $fin && $debut > $fin) {
                return new JsonResponse([
                    'status' => 'error',
                    'message' => 'La date de début doit être antérieure à la date de fin.'
                ], 400);
            }
            $evenement = $this->evenementService->createEvenement($evenement, $photo,$idUser);

            return new JsonResponse([
                'status' => 'success',
                'evenement' => [
                    'id' => $evenement->getId(),
                    'titre' => $evenement->getTitre(),
                    'description' => $evenement->getDescription(),
                    'debut' => $evenement->getDebut()?->format('Y-m-d'),
                    'fin' => $evenement->getFin()?->format('Y-m-d'),
                    'typeEvent' => $evenement->getTypeEvent()->getName(),
                    'photoId' => $evenement->getPhoto()?->getId()
                ]
            ], 201);

        } catch (\Exception $e) {
            // Retourne l'erreur en JSON
            error_log($e->getMessage());
            error_log($e->getTraceAsString());
            if ($e->getMessage() === 'Inactif') {
                    return new JsonResponse([
                        'status' => 'error',
                        'message' => 'Utilisateur inactif'
                    ], 401); // ← renvoie bien 401
            }
            
            return new JsonResponse([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
    }

    #[Route('', name: 'evenements_avant', methods: ['GET'])]
    #[TokenRequired(['Admin','Utilisateur'])]
    public function getEvenementsAvant(Request $request): JsonResponse
    {
        try {
            $token = $this->jwtTokenManager->extractTokenFromRequest($request);
            $arrayToken = $this->jwtTokenManager->extractClaimsFromToken($token);
            $idUser = $arrayToken['id']; 
            $dateParam = $request->query->get('date');
            $limitParam = $request->query->get('limit');

            $date = $dateParam ? new \DateTime($dateParam) : new \DateTime();
            $limit = $limitParam ? (int)$limitParam : 10;


            $evenements = $this->evenementService->getEvenementDateBeforeId($date, $limit, $idUser);

            $evenementsArray = array_map(function ($e) {
                return [
                    'id' => $e->getId(),
                    'titre' => $e->getTitre(),
                    'description' => $e->getDescription(),
                    'debut' => $e->getDebut()?->format('Y-m-d'),
                    'fin' => $e->getFin()?->format('Y-m-d'),
                    'type' => $e->getTypeEvent()?->getName(),
                    'photoId' => $e->getPhoto()?->getId(),
                    'datePublication' => $e->getDatePublication()?->format('Y-m-d H:i:s'),
                ];
            }, $evenements);

            return new JsonResponse([
                'status' => 'success',
                'data' => $evenementsArray
            ], 200);

        } catch (\Exception $e) {
                if ($e->getMessage() === 'Inactif') {
                    return new JsonResponse([
                        'status' => 'error',
                        'message' => 'Utilisateur inactif'
                    ], 401); // ← renvoie bien 401
                }

                return new JsonResponse([
                    'status' => 'error',
                    'message' => $e->getMessage()
                ], 400);
            }

    }
    #[Route('/news', name: 'news_avant', methods: ['GET'])]
    public function getNewsAvant(Request $request): JsonResponse
    {
        try {
            $dateParam = $request->query->get('date');
            $limitParam = $request->query->get('limit');

            $date = $dateParam ? new \DateTime($dateParam) : new \DateTime();
            $limit = $limitParam ? (int)$limitParam : 10;


            $evenements = $this->evenementService->getNewsBefore($date, $limit);

            $evenementsArray = array_map(function ($e) {
                return [
                    'id' => $e->getId(),
                    'titre' => $e->getTitre(),
                    'description' => $e->getDescription(),
                    'debut' => $e->getDebut()?->format('Y-m-d'),
                    'fin' => $e->getFin()?->format('Y-m-d'),
                    'type' => $e->getTypeEvent()?->getName(),
                    'photoId' => $e->getPhoto()?->getId(),
                    'datePublication' => $e->getDatePublication()?->format('Y-m-d H:i:s'),
                ];
            }, $evenements);

            return new JsonResponse([
                'status' => 'success',
                'data' => $evenementsArray
            ], 200);

        } catch (\Exception $e) {
                if ($e->getMessage() === 'Inactif') {
                    return new JsonResponse([
                        'status' => 'error',
                        'message' => 'Utilisateur inactif'
                    ], 401); // ← renvoie bien 401
                }

                return new JsonResponse([
                    'status' => 'error',
                    'message' => $e->getMessage()
                ], 400);
            }

    }
    #[Route('/all', name: 'all', methods: ['GET'])]
    public function getAllAvant(Request $request): JsonResponse
    {
        try {
            $dateParam = $request->query->get('date');
            $limitParam = $request->query->get('limit');

            $date = $dateParam ? new \DateTime($dateParam) : new \DateTime();
            $limit = $limitParam ? (int)$limitParam : 10;


            $evenements = $this->evenementService-> getEvenementDateBefore($date, $limit);

            $evenementsArray = array_map(function ($e) {
                return [
                    'id' => $e->getId(),
                    'titre' => $e->getTitre(),
                    'description' => $e->getDescription(),
                    'debut' => $e->getDebut()?->format('Y-m-d'),
                    'fin' => $e->getFin()?->format('Y-m-d'),
                    'type' => $e->getTypeEvent()?->getName(),
                    'photoId' => $e->getPhoto()?->getId(),
                    'datePublication' => $e->getDatePublication()?->format('Y-m-d H:i:s'),
                ];
            }, $evenements);

            return new JsonResponse([
                'status' => 'success',
                'data' => $evenementsArray
            ], 200);

        } catch (\Exception $e) {
                if ($e->getMessage() === 'Inactif') {
                    return new JsonResponse([
                        'status' => 'error',
                        'message' => 'Utilisateur inactif'
                    ], 401); // ← renvoie bien 401
                }

                return new JsonResponse([
                    'status' => 'error',
                    'message' => $e->getMessage()
                ], 400);
            }

    }
    #[Route('/events', name: 'event_avant', methods: ['GET'])]
    public function getEventAvant(Request $request): JsonResponse
    {
        try {
            $dateParam = $request->query->get('date');
            $limitParam = $request->query->get('limit');

            $date = $dateParam ? new \DateTime($dateParam) : new \DateTime();
            $limit = $limitParam ? (int)$limitParam : 10;


            $evenements = $this->evenementService->getEventBefore($date, $limit);

            $evenementsArray = array_map(function ($e) {
                return [
                    'id' => $e->getId(),
                    'titre' => $e->getTitre(),
                    'description' => $e->getDescription(),
                    'debut' => $e->getDebut()?->format('Y-m-d'),
                    'fin' => $e->getFin()?->format('Y-m-d'),
                    'type' => $e->getTypeEvent()?->getName(),
                    'photoId' => $e->getPhoto()?->getId(),
                    'datePublication' => $e->getDatePublication()?->format('Y-m-d H:i:s'),
                ];
            }, $evenements);

            return new JsonResponse([
                'status' => 'success',
                'data' => $evenementsArray
            ], 200);

        } catch (\Exception $e) {
                if ($e->getMessage() === 'Inactif') {
                    return new JsonResponse([
                        'status' => 'error',
                        'message' => 'Utilisateur inactif'
                    ], 401); // ← renvoie bien 401
                }

                return new JsonResponse([
                    'status' => 'error',
                    'message' => $e->getMessage()
                ], 400);
            }

    }
    #[Route('/{id}', name: 'api_evenement_update', methods: ['PUT'])]
    #[TokenRequired(['Admin','Utilisateur'])]
    public function update(Request $request, string $id): JsonResponse
    {
        try {
           
            $data = json_decode($request->getContent(), true);

            $photo = null;
            if (isset($data['photoBinaire']) && !empty($data['photoBinaire'])) {

                $photo = new Photo();
                $photo->setBinaire(base64_decode($data['photoBinaire']));
                $photo->setDateInsertion(new \DateTime());
               
            }

            

            $evenement = $this->evenementService->updateEvenement($id, $data, $photo);


            

            return new JsonResponse([
                'status' => 'success',
                'message' => "Événement mis à jour avec succès.",
                'data' => [
                    'id' => $evenement->getId(),
                    'titre' => $evenement->getTitre(),
                    'description' => $evenement->getDescription(),
                    'debut' => $evenement->getDebut()?->format('Y-m-d'),
                    'fin' => $evenement->getFin()?->format('Y-m-d'),
                    'type' => $evenement->getTypeEvent()->getName(),
                    'photoId' => $evenement->getPhoto()?->getId()
                ]
            ], 200);

        } catch (\Exception $e) {
            error_log($e->getMessage());
            error_log($e->getTraceAsString());

            if ($e->getMessage() === 'Inactif') {
                return new JsonResponse([
                    'status' => 'error',
                    'message' => 'Utilisateur inactif'
                ], 401);
            }
            
            // Gestion spécifique pour "Événement introuvable" définie dans votre service
            if ($e->getMessage() === 'Événement introuvable') {
                return new JsonResponse([
                    'status' => 'error',
                    'message' => $e->getMessage()
                ], 404);
            }

            return new JsonResponse([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
    }

}
