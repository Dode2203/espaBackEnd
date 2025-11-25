<?php

namespace App\Service;

use App\Entity\Role;
use App\Entity\Utilisateur;
use App\Repository\RoleRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\UtilisateurRepository;

class UtilisateurService
{
    private EntityManagerInterface $em;
     
    private UtilisateurRepository $utilisateurRepository;
    private RoleRepository $roleRepository;

    public function __construct(EntityManagerInterface $em, UtilisateurRepository $utilisateurRepository, RoleRepository $roleRepository)
    {
        $this->em = $em;
        $this->utilisateurRepository = $utilisateurRepository;
        $this->roleRepository = $roleRepository;
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
    public function createUser(Utilisateur $user): Utilisateur
    {
        $role= $this->roleRepository->find(2); // 2 correspond au rôle "Utilisateur"
        $user->setRole($role);
        return $this->createUserByRole($user);
    }

    public function login(string $email, string $plainPassword): ?Utilisateur
    {
        $user = $this->utilisateurRepository->login($email, $plainPassword);

        return $user; 
    }

}
