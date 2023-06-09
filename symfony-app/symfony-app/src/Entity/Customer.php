<?php

namespace App\Entity;

use App\Repository\CustomerRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: "customer")]
#[ORM\Entity(repositoryClass: CustomerRepository::class)]
class Customer
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\Column(name: "first_name", type: "string", length: 255, nullable: true)]
    private ?string $firstName = null;

    #[ORM\Column(name: "last_name", type: "string", length: 255, nullable: true)]
    private ?string $lastName = null;

    #[ORM\OneToMany(mappedBy: 'customer', targetEntity: CustomerAddress::class, cascade: ['persist'])]
    private Collection $addresses;

    public function __construct()
    {
        $this->addresses = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(?string $firstName): self
    {
        $this->firstName = $firstName;

        return $this;
    }
    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(?string $lastName): self
    {
        $this->lastName = $lastName;

        return $this;
    }

    /**
     * @return Collection<int, CustomerAddress>
     */
    public function getAddresses(): Collection
    {
        return $this->addresses;
    }

    public function addAddress(CustomerAddress $address): self
    {
        if (!$this->addresses->contains($address)) {
            $this->addresses[] = $address;
            $address->setCustomer($this);
        }

        return $this;
    }

    public function removeAddress(CustomerAddress $address): self
    {
        $this->addresses->removeElement($address);
        $address->setCustomer(null);

        return $this;
    }
}