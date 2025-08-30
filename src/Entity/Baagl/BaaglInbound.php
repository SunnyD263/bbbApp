<?php
namespace App\Entity\Baagl;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: "baagl_inbound")]
class BaaglInbound
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type:"integer")]
    private ?int $id = null;

    #[ORM\Column(length:50, nullable:true)]
    private ?string $code = null;

    #[ORM\Column(type:"text", nullable:true)]
    private ?string $nazev = null;

    #[ORM\Column(length:10, nullable:true)]
    private ?string $uom = null;

    // v původním skriptu „stav“ = množství
    #[ORM\Column(type:"integer", nullable:true)]
    private ?int $stav = null;

    #[ORM\Column(length:10, nullable:true)]
    private ?string $mena = null;

    #[ORM\Column(type:"decimal", precision:10, scale:2, nullable:true)]
    private ?string $nakupBezDph = null;

    #[ORM\Column(type:"decimal", precision:10, scale:2, nullable:true)]
    private ?string $nakupDph = null;

    public function getId(): ?int { return $this->id; }
    public function getCode(): ?string { return $this->code; }
    public function getNazev(): ?string { return $this->nazev; }
    public function getUom(): ?string { return $this->uom; }
    public function getStav(): ?int { return $this->stav; }
    public function getMena(): ?string { return $this->mena; }
    public function getNakupBezDph(): ?string { return $this->nakupBezDph; }
    public function getNakupDph(): ?string { return $this->nakupDph; }

}