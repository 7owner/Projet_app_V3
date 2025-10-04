<?php

namespace App\Entity;

use App\Repository\RendezvousImageRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RendezvousImageRepository::class)]
#[ORM\Table(name: 'rendez_vous_image')]
#[ORM\UniqueConstraint(name: 'rendez_vous_image_unique', columns: ['rendez_vous_id', 'image_id'])]
class RendezvousImage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Rendezvous::class)]
    #[ORM\JoinColumn(name: 'rendez_vous_id', referencedColumnName: 'id', nullable: false)]
    private ?Rendezvous $rendezvous = null;

    #[ORM\ManyToOne(targetEntity: Images::class)]
    #[ORM\JoinColumn(name: 'image_id', referencedColumnName: 'id', nullable: false)]
    private ?Images $image = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRendezvous(): ?Rendezvous
    {
        return $this->rendezvous;
    }

    public function setRendezvous(?Rendezvous $rendezvous): static
    {
        $this->rendezvous = $rendezvous;

        return $this;
    }

    public function getImage(): ?Images
    {
        return $this->image;
    }

    public function setImage(?Images $image): static
    {
        $this->image = $image;

        return $this;
    }
}
