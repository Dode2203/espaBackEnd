<?php

namespace App\Entity;

use App\Repository\PhotoRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PhotoRepository::class)]
class Photo
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::BLOB)]
    private $binaire;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateInsertion = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBinaire()
    {
        return $this->binaire;
    }

    public function setBinaire($binaire): static
    {
        $this->binaire = $binaire;

        return $this;
    }

    public function getDateInsertion(): ?\DateTimeInterface
    {
        return $this->dateInsertion;
    }

    public function setDateInsertion(\DateTimeInterface $dateInsertion): static
    {
        $this->dateInsertion = $dateInsertion;

        return $this;
    }
}
