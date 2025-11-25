<?php

namespace App\Controller\Api;


use App\Entity\Utilisateur;
use App\Service\JwtTokenManager;
use App\Service\UtilisateurService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Annotation\TokenRequired;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[Route('/utilisateur')]
class UtilisateurController extends AbstractController
{
    private ParameterBagInterface $params;
    private EntityManagerInterface $em;
    private UtilisateurService $utilisateurService;

    private JwtTokenManager $jwtTokenManager;

    public function __construct(EntityManagerInterface $em, UtilisateurService $utilisateurService,JwtTokenManager $jwtTokenManager, ParameterBagInterface $params)
    {
        $this->em = $em;
        $this->utilisateurService = $utilisateurService;
        $this->jwtTokenManager = $jwtTokenManager;
        $this->params = $params;
    }

    #[Route('/create', name: 'api_utilisateur_create', methods: ['POST'])]
    #[TokenRequired(['Admin','Utilisateur'])]
    public function createUser(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $requiredFields = ['email', 'nom', 'prenom', 'mdp'];
        $missingFields = [];

        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                $missingFields[] = $field;
            }
        }

        if (!empty($missingFields)) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Champs requis manquants',
                'missingFields' => $missingFields
            ], 400);
        }

        $user = new Utilisateur();
        $user->setEmail($data['email'])
             ->setNom($data['nom'])
             ->setPrenom($data['prenom']);

        // 🔐 Hashage simple du mot de passe
        $plainPassword = $data['mdp'];
        $user->setMdp($plainPassword);
        try {
            $user = $this->utilisateurService->createUser($user);
            return new JsonResponse([
            'status' => 'success',
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'nom' => $user->getNom(),
                'prenom' => $user->getPrenom()
            ]
        ], 201);

        } catch (\Exception $e) {
			return new JsonResponse([
				'status' => 'error',
				'message' => $e->getMessage()
			], 400);
		}
        
        

        
    }

    #[Route('/login', name: 'api_utilisateur_login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $requiredFields = ['email', 'mdp'];
        $missingFields = [];

        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                $missingFields[] = $field;
            }
        }

        if (!empty($missingFields)) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Champs requis manquants',
                'missingFields' => $missingFields
            ], 400);
        }

        $email = $data['email'];
        $plainPassword = $data['mdp'];

        // 🔑 Vérification du login via le repository
        $user = $this->utilisateurService->login($email, $plainPassword);

        if (!$user) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Identifiants invalides'
            ], 404);
        }
        $claims = [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'role' => $user->getRole()->getName()
        ];

        $tokenDuration = $this->params->get('jwt_token_duration');

        $token = $this->jwtTokenManager->createToken($claims, $tokenDuration);
        $tokenString = $token->toString();
        $data = [
            'membre' => [
                // 'id' => $user->getId(),
                'email' => $user->getEmail(),
                'role' => $user->getRole()->getName(), // ajouter role ici
                'nom' => $user->getNom(),
                'prenom' => $user->getPrenom()
            ],
            'token' => $tokenString
        ];
        return new JsonResponse([
            'status' => 'success',
            'data' => $data
        ], 200);
    }
}
