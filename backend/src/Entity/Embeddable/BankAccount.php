<?php

declare(strict_types=1);

namespace App\Entity\Embeddable;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
class BankAccount
{
    #[ORM\Column(type: Types::STRING, length: 34, nullable: true)]
    private ?string $iban = null;

    #[ORM\Column(type: Types::STRING, length: 11, nullable: true)]
    private ?string $bic = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $accountHolder = null;

    public function getIban(): ?string
    {
        return $this->iban;
    }

    public function setIban(?string $iban): static
    {
        $this->iban = $iban;

        return $this;
    }

    public function getBic(): ?string
    {
        return $this->bic;
    }

    public function setBic(?string $bic): static
    {
        $this->bic = $bic;

        return $this;
    }

    public function getAccountHolder(): ?string
    {
        return $this->accountHolder;
    }

    public function setAccountHolder(?string $accountHolder): static
    {
        $this->accountHolder = $accountHolder;

        return $this;
    }
}
