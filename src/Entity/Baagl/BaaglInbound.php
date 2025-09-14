<?php
namespace App\Entity\Baagl;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
#[ORM\Table(name: "baagl_inbound")]
class BaaglInbound
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type:"integer")]
    #[Groups(['debug'])]
    private ?int $id = null;

    #[ORM\Column(length:50, nullable:true)]
    #[Groups(['debug'])]
    private ?string $code = null;

    #[ORM\Column(type:"text", nullable:true)]
    #[Groups(['debug'])]
    private ?string $nazev = null;

    #[ORM\Column(length:10, nullable:true)]
    #[Groups(['debug'])]
    private ?string $uom = null;

    #[ORM\Column(type:"integer", nullable:true)]
    #[Groups(['debug'])]
    private ?int $stav = null;

    #[ORM\Column(length:10, nullable:true)]
    #[Groups(['debug'])]
    private ?string $mena = null;

    #[ORM\Column(type:"integer", nullable:true)]
    #[Groups(['debug'])]
    private ?int $tax = null;

    #[ORM\Column(type:"decimal", precision:10, scale:2, nullable:true)]
    #[Groups(['debug'])]
    private ?string $nakupBezDph = null;

    #[ORM\Column(type:"decimal", precision:10, scale:2, nullable:true)]
    #[Groups(['debug'])]
    private ?string $nakupDph = null;

    public function getId(): ?int { return $this->id; }
    public function getCode(): ?string { return $this->code; }
    public function getNazev(): ?string { return $this->nazev; }
    public function getUom(): ?string { return $this->uom; }
    public function getStav(): ?int { return $this->stav; }
    public function getTax(): ?int { return $this->tax; }
    public function getMena(): ?string { return $this->mena; }
    public function getNakupBezDph(): ?string { return $this->nakupBezDph; }
    public function getNakupDph(): ?string { return $this->nakupDph; }

}