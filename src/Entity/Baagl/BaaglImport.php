<?php

namespace App\Entity\Baagl;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'baagl_import')]
class BaaglImport
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $skupinaID = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $skupina = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $skupinaZbozi = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $code = null;

    #[ORM\Column(name: 'catId', length: 50, nullable: true)]
    private ?string $catId = null;

    #[ORM\Column(name: 'catName', length: 255, nullable: true)]
    private ?string $catName = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $ean = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $nazev = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $popis = null;

    // decimály držím jako stringy (bez ztráty přesnosti)
    #[ORM\Column(type: 'decimal', precision: 10, scale: 1, nullable: true)]
    private ?string $sirka = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 1, nullable: true)]
    private ?string $vyska = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 1, nullable: true)]
    private ?string $hloubka = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $barva = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $baleni = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $material = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?string $hmotnost = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 1, nullable: true)]
    private ?string $nosnost = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $uom = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $stav = null;

    #[ORM\Column(name: 'stav_po_doplneni', type: 'integer', nullable: true)]
    private ?int $stavPoDoplneni = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $dph = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $mena = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?string $cena = null;

    #[ORM\Column(name: 'nakupni_cena', type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?string $nakupniCena = null;

    // „doporučená maloobchodní cena“ – v tabulce je sloupec dmoc_cena
    #[ORM\Column(name: 'dmoc_cena', type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?string $dmocCena = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?string $sleva = null;

    // obrázky 1–20
    #[ORM\Column(type: 'text', nullable: true)] private ?string $obrazek1 = null;
    #[ORM\Column(type: 'text', nullable: true)] private ?string $obrazek2 = null;
    #[ORM\Column(type: 'text', nullable: true)] private ?string $obrazek3 = null;
    #[ORM\Column(type: 'text', nullable: true)] private ?string $obrazek4 = null;
    #[ORM\Column(type: 'text', nullable: true)] private ?string $obrazek5 = null;
    #[ORM\Column(type: 'text', nullable: true)] private ?string $obrazek6 = null;
    #[ORM\Column(type: 'text', nullable: true)] private ?string $obrazek7 = null;
    #[ORM\Column(type: 'text', nullable: true)] private ?string $obrazek8 = null;
    #[ORM\Column(type: 'text', nullable: true)] private ?string $obrazek9 = null;
    #[ORM_COLUMN(type: 'text', nullable: true)] private ?string $obrazek10 = null;
    #[ORM\Column(type: 'text', nullable: true)] private ?string $obrazek11 = null;
    #[ORM\Column(type: 'text', nullable: true)] private ?string $obrazek12 = null;
    #[ORM\Column(type: 'text', nullable: true)] private ?string $obrazek13 = null;
    #[ORM\Column(type: 'text', nullable: true)] private ?string $obrazek14 = null;
    #[ORM\Column(type: 'text', nullable: true)] private ?string $obrazek15 = null;
    #[ORM\Column(type: 'text', nullable: true)] private ?string $obrazek16 = null;
    #[ORM\Column(type: 'text', nullable: true)] private ?string $obrazek17 = null;
    #[ORM\Column(type: 'text', nullable: true)] private ?string $obrazek18 = null;
    #[ORM\Column(type: 'text', nullable: true)] private ?string $obrazek19 = null;
    #[ORM\Column(type: 'text', nullable: true)] private ?string $obrazek20 = null;

    // ===== Gettery/Settery =====

    public function getId(): ?int { return $this->id; }

    public function getSkupinaID(): ?string { return $this->skupinaID; }
    public function setSkupinaID(?string $v): self { $this->skupinaID = $v; return $this; }

    public function getSkupina(): ?string { return $this->skupina; }
    public function setSkupina(?string $v): self { $this->skupina = $v; return $this; }

    public function getSkupinaZbozi(): ?string { return $this->skupinaZbozi; }
    public function setSkupinaZbozi(?string $v): self { $this->skupinaZbozi = $v; return $this; }

    public function getCode(): ?string { return $this->code; }
    public function setCode(?string $v): self { $this->code = $v; return $this; }

    public function getCatId(): ?string { return $this->catId; }
    public function setCatId(?string $v): self { $this->catId = $v; return $this; }

    public function getCatName(): ?string { return $this->catName; }
    public function setCatName(?string $v): self { $this->catName = $v; return $this; }

    public function getEan(): ?string { return $this->ean; }
    public function setEan(?string $v): self { $this->ean = $v; return $this; }

    public function getNazev(): ?string { return $this->nazev; }
    public function setNazev(?string $v): self { $this->nazev = $v; return $this; }

    public function getPopis(): ?string { return $this->popis; }
    public function setPopis(?string $v): self { $this->popis = $v; return $this; }

    public function getSirka(): ?string { return $this->sirka; }
    public function setSirka(?string $v): self { $this->sirka = $v; return $this; }

    public function getVyska(): ?string { return $this->vyska; }
    public function setVyska(?string $v): self { $this->vyska = $v; return $this; }

    public function getHloubka(): ?string { return $this->hloubka; }
    public function setHloubka(?string $v): self { $this->hloubka = $v; return $this; }

    public function getBarva(): ?string { return $this->barva; }
    public function setBarva(?string $v): self { $this->barva = $v; return $this; }

    public function getBaleni(): ?string { return $this->baleni; }
    public function setBaleni(?string $v): self { $this->baleni = $v; return $this; }

    public function getMaterial(): ?string { return $this->material; }
    public function setMaterial(?string $v): self { $this->material = $v; return $this; }

    public function getHmotnost(): ?string { return $this->hmotnost; }
    public function setHmotnost(?string $v): self { $this->hmotnost = $v; return $this; }

    public function getNosnost(): ?string { return $this->nosnost; }
    public function setNosnost(?string $v): self { $this->nosnost = $v; return $this; }

    public function getUom(): ?string { return $this->uom; }
    public function setUom(?string $v): self { $this->uom = $v; return $this; }

    public function getStav(): ?int { return $this->stav; }
    public function setStav(?int $v): self { $this->stav = $v; return $this; }

    public function getStavPoDoplneni(): ?int { return $this->stavPoDoplneni; }
    public function setStavPoDoplneni(?int $v): self { $this->stavPoDoplneni = $v; return $this; }

    public function getDph(): ?int { return $this->dph; }
    public function setDph(?int $v): self { $this->dph = $v; return $this; }

    public function getMena(): ?string { return $this->mena; }
    public function setMena(?string $v): self { $this->mena = $v; return $this; }

    public function getCena(): ?string { return $this->cena; }
    public function setCena(?string $v): self { $this->cena = $v; return $this; }

    public function getNakupniCena(): ?string { return $this->nakupniCena; }
    public function setNakupniCena(?string $v): self { $this->nakupniCena = $v; return $this; }

    public function getDmocCena(): ?string { return $this->dmocCena; }
    public function setDmocCena(?string $v): self { $this->dmocCena = $v; return $this; }

    public function getSleva(): ?string { return $this->sleva; }
    public function setSleva(?string $v): self { $this->sleva = $v; return $this; }

    public function getObrazek1(): ?string { return $this->obrazek1; }   public function setObrazek1(?string $v): self { $this->obrazek1 = $v; return $this; }
    public function getObrazek2(): ?string { return $this->obrazek2; }   public function setObrazek2(?string $v): self { $this->obrazek2 = $v; return $this; }
    public function getObrazek3(): ?string { return $this->obrazek3; }   public function setObrazek3(?string $v): self { $this->obrazek3 = $v; return $this; }
    public function getObrazek4(): ?string { return $this->obrazek4; }   public function setObrazek4(?string $v): self { $this->obrazek4 = $v; return $this; }
    public function getObrazek5(): ?string { return $this->obrazek5; }   public function setObrazek5(?string $v): self { $this->obrazek5 = $v; return $this; }
    public function getObrazek6(): ?string { return $this->obrazek6; }   public function setObrazek6(?string $v): self { $this->obrazek6 = $v; return $this; }
    public function getObrazek7(): ?string { return $this->obrazek7; }   public function setObrazek7(?string $v): self { $this->obrazek7 = $v; return $this; }
    public function getObrazek8(): ?string { return $this->obrazek8; }   public function setObrazek8(?string $v): self { $this->obrazek8 = $v; return $this; }
    public function getObrazek9(): ?string { return $this->obrazek9; }   public function setObrazek9(?string $v): self { $this->obrazek9 = $v; return $this; }
    public function getObrazek10(): ?string { return $this->obrazek10; } public function setObrazek10(?string $v): self { $this->obrazek10 = $v; return $this; }
    public function getObrazek11(): ?string { return $this->obrazek11; } public function setObrazek11(?string $v): self { $this->obrazek11 = $v; return $this; }
    public function getObrazek12(): ?string { return $this->obrazek12; } public function setObrazek12(?string $v): self { $this->obrazek12 = $v; return $this; }
    public function getObrazek13(): ?string { return $this->obrazek13; } public function setObrazek13(?string $v): self { $this->obrazek13 = $v; return $this; }
    public function getObrazek14(): ?string { return $this->obrazek14; } public function setObrazek14(?string $v): self { $this->obrazek14 = $v; return $this; }
    public function getObrazek15(): ?string { return $this->obrazek15; } public function setObrazek15(?string $v): self { $this->obrazek15 = $v; return $this; }
    public function getObrazek16(): ?string { return $this->obrazek16; } public function setObrazek16(?string $v): self { $this->obrazek16 = $v; return $this; }
    public function getObrazek17(): ?string { return $this->obrazek17; } public function setObrazek17(?string $v): self { $this->obrazek17 = $v; return $this; }
    public function getObrazek18(): ?string { return $this->obrazek18; } public function setObrazek18(?string $v): self { $this->obrazek18 = $v; return $this; }
    public function getObrazek19(): ?string { return $this->obrazek19; } public function setObrazek19(?string $v): self { $this->obrazek19 = $v; return $this; }
    public function getObrazek20(): ?string { return $this->obrazek20; } public function setObrazek20(?string $v): self { $this->obrazek20 = $v; return $this; }
}
