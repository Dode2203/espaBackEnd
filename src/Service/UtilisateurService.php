<?php

namespace App\Service;

use App\Entity\Utilisateur;
use App\Repository\RoleRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\UtilisateurRepository;
use App\Repository\StatusRepository;
use Exception;

class UtilisateurService
{
    private EntityManagerInterface $em;
     
    private UtilisateurRepository $utilisateurRepository;
    private RoleRepository $roleRepository;

    private StatusRepository $statusRepository;

    public function __construct(EntityManagerInterface $em, UtilisateurRepository $utilisateurRepository, RoleRepository $roleRepository, StatusRepository $statusRepository)
    {
        $this->em = $em;
        $this->utilisateurRepository = $utilisateurRepository;
        $this->roleRepository = $roleRepository;
        $this->statusRepository = $statusRepository;
    }

    /**
     * @param Utilisateur $user L'utilisateur à créer
     * @param string $plainPassword Le mot de passe en clair
     */
    public function createUserByRole(Utilisateur $user): Utilisateur
    {

        $plainPassword = $user->getMdp();
        $hashedPassword = password_hash($plainPassword, PASSWORD_BCRYPT);

        $user->setMdp($hashedPassword);

        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }
    public function getAllUsers(): array
    {
        return $this->utilisateurRepository->getAllParOrdre();
    }
    public function getUserById(int $id): ?Utilisateur
    {
        return $this->em->getRepository(Utilisateur::class)->find($id);
    }
    public function updateUser($idUser, array $data): Utilisateur
    {
        $user = $this->utilisateurRepository->find($idUser);
        if(!$user){
            throw new Exception('Utilisateur non trouvé pour id=' . $idUser);
        }
        if (isset($data['prenom'])) {
            $user->setPrenom($data['prenom']);
        }

        if (isset($data['nom'])) {
            $user->setNom($data['nom']);
        }

        if (isset($data['email'])) {
            $user->setEmail($data['email']);
        }

        if (isset($data['role'])) {
            $role = $this->roleRepository->findOneBy(['name' => $data['role']]);
            if (!$role) {
                throw new \InvalidArgumentException('Rôle introuvable');
            }
            $user->setRole($role);
        }

        if (isset($data['status'])) {
            $status = $this->statusRepository->findOneBy(['name' => $data['status']]);
            if (!$status) {
                throw new \InvalidArgumentException('Status introuvable');
            }
            $user->setStatus($status);
        }

        if (isset($data['mdp']) && !empty($data['mdp'])) {
            $hashedPassword = password_hash($data['mdp'], PASSWORD_BCRYPT);
            $user->setMdp($hashedPassword);
        }

        $this->em->flush();

        return $user;
    }
    public function createUser(Utilisateur $user,$role_id=2): Utilisateur
    {
        $role= $this->roleRepository->find($role_id); // 2 correspond au rôle "Utilisateur"
        if (!$role) {
            throw new Exception("Role non trouvé pour id=".$role_id);
        }
        $user->setRole($role);
        $status= $this->statusRepository->find(1); // 1 correspond au status "Actif"
        $user->setStatus($status);
        return $this->createUserByRole($user);
    }

    public function login(string $email, string $plainPassword): ?Utilisateur
    {
        $user = $this->utilisateurRepository->login($email, $plainPassword);

        return $user; 
    }

}
