<?php
declare(strict_types=1);

namespace App\Service\Baagl;

use App\Entity\Baagl\BaaglImport;
use Doctrine\ORM\EntityManagerInterface;

class BaaglImportXml
{
    public function __construct(private EntityManagerInterface $em) {}

    /** TRUNCATE před načtením stránky */
    public function rebuild(): void
    {
        $conn = $this->em->getConnection();

        try {
            $platform = $conn->getDatabasePlatform();
            $sql = \method_exists($platform, 'getTruncateTableSQL')
                ? $platform->getTruncateTableSQL('baagl_import')
                : 'TRUNCATE baagl_import';
            $conn->executeStatement($sql);
        } catch (\Throwable $e) {
            // Fallback: hromadný DELETE přes DQL (pomalejší, ale portable)
            $this->em->createQuery('DELETE FROM App\Entity\Baagl\BaaglImport b')->execute();
        }
    }

    public function insertFromItems(array $items): int
    {
        if ($items === []) return 0;
       
        return $this->em->wrapInTransaction(function (EntityManagerInterface $em) use ($items)  {
            $count = 0;
            $batchSize = 300;

            foreach ($items["item"] as $item) {
                $entity = (new BaaglImport())

                    // Základ
                    ->setCode($this->str($item->registracni_cislo ?? null))
                    ->setEan($this->str($item->ean ?? null))
                    ->setNazev($this->str($item->nazev ?? null))
                    ->setPopis($this->str($item->popis ?? null))

                    // Rozměry / váhy (čárky → tečky)
                    ->setSirka(self::toDecimal($item->sirka ?? null, 1))
                    ->setVyska(self::toDecimal($item->vyska ?? null, 1))
                    ->setHloubka(self::toDecimal($item->hloubka ?? null, 1))
                    ->setHmotnost(self::toDecimal($item->hmotnost ?? null, 2))
                    ->setNosnost(self::toDecimal($item->nosnost ?? null, 1))

                    // Ostatní vlastnosti
                    ->setUom($this->str($item->merna_jednotka ?? null))
                    ->setMaterial($this->str($item->material ?? null))
                    ->setBaleni($this->str($item->baleni ?? null))
                    ->setBarva($this->str($item->barva ?? null))
                    ->setStav(isset($item->stav) ? (int)$item->stav : null)
                    ->setStavPoDoplneni(isset($item->stav_po_doplneni) ? (int)$item->stav_po_doplneni : null)
                    ->setDph($this->mapDph($this->str($item->dph ?? null)))
                    ->setMena($this->str($item->mena ?? null))
                    ->setCena(self::toDecimal($item->cena ?? null, 2))
                    ->setNakupniCena(self::toDecimal($item->nakupni_cena ?? null, 2))
                    ->setDmocCena(self::toDecimal($item->dmoc_cena ?? null, 2))
                    ->setSleva(self::toDecimal($item->sleva ?? null, 2))
                    ->setSkupinaZbozi($this->str($item->skupzbo ?? null))
                    ->setCatId($this->str($item->category_id ?? null))
                    ->setCatName($this->str($item->category_name ?? null));

                // Skupiny
                $primary = null;
                if (isset($item->skupiny_zbozi->skupina)) {
                    foreach ($item->skupiny_zbozi->skupina as $sk) {
                        if ((string)($sk->primary ?? '') === 'true') { $primary = $sk; break; }
                    }
                    if (!$primary) { $primary = $item->skupiny_zbozi->skupina[0] ?? null; }
                }
                if ($primary) {
                    $entity
                        ->setSkupinaID($this->str($primary->id ?? null))
                        ->setSkupina($this->str($primary->title ?? null));
                }

                // Obrázky max 20
                if (isset($item->obrazky->obr)) {
                    $i = 1;
                    foreach ($item->obrazky->obr as $url) {
                        if ($i > 20) break;
                        $m = 'setObrazek' . $i;
                        if (\method_exists($entity, $m)) {
                            $entity->$m(\trim((string)$url));
                        }
                        $i++;
                    }
                }

                $this->em->persist($entity);
                $count++;

                // dávkové flush/clear kvůli paměti
                if (($count % $batchSize) === 0) {
                    $this->em->flush();
                    $this->em->clear(BaaglImport::class);
                }
            }

            $this->em->flush();
            $this->em->clear(BaaglImport::class);

            return $count;
        });
    }

    private function str(mixed $v): ?string
    {
        $s = \trim((string)$v);
        return $s === '' ? null : $s;
    }

    private static function toDecimal(mixed $v, int $scale = 2): ?string
    {
        $s = \trim((string)$v);
        if ($s === '') return null;
        $s = \str_replace(',', '.', $s);
        return \number_format((float)$s, $scale, '.', '');
    }

    private function mapDph(?string $v): ?int
    {
        if ($v === null) return null;
        $v = \strtolower(\trim($v));
        return match ($v) {
            'high' => 21,
            'low'  => 12,
            'none' => 0,
            default => null,
        };
    }
}
