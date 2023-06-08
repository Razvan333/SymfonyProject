<?php

namespace App\Entity;

use App\Repository\CustomerAddressRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="customer_address")
 * @ORM\Entity
 */
#[ORM\Entity(repositoryClass: CustomerAddressRepository::class)]
class CustomerAddress
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;


    #[ORM\Column(length: 255, nullable: true)]
    private ?string $address = null;

    #[ORM\ManyToOne(inversedBy: 'customerAddresses')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Customer $customerId = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): self
    {
        $this->address = $address;

        return $this;
    }

    public function getCustomerId(): ?Customer
    {
        return $this->customerId;
    }

    public function setCustomerId(?Customer $customerId): self
    {
        $this->customerId = $customerId;

        return $this;
    }
}
