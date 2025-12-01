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
                    'message' => 'Champs requis manquants : ' . implode(', ', $missingFields) .$typeEventId,
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
                    'debut' => $e->getDebut()?->format('Y-m-d H:i:s'),
                    'fin' => $e->getFin()?->format('Y-m-d H:i:s'),
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

}
