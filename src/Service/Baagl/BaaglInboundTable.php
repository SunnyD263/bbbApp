<?php
namespace App\Service\Baagl;

use App\Entity\Baagl\BaaglInbound;
use Doctrine\ORM\EntityManagerInterface;

final class BaaglInboundTable
{
    public function __construct(private readonly EntityManagerInterface $em) {}

    /** TRUNCATE před načtením stránky */
    public function rebuild(): void
    {
        $conn = $this->em->getConnection();

        try {
            $platform = $conn->getDatabasePlatform();
            $sql = \method_exists($platform, 'getTruncateTableSQL')
                ? $platform->getTruncateTableSQL('baagl_inbound')
                : 'TRUNCATE baagl_inbound';
            $conn->executeStatement($sql);
        } catch (\Throwable $e) {
            // Fallback: hromadný DELETE přes DQL (pomalejší, ale portable)
            $this->em->createQuery('DELETE FROM App\Entity\Baagl\BaaglInbound b')->execute();
        }
    }

    /** Vložení po uploadu */
    public function insertFromItems(array $items): int
    {
        if ($items === []) return 0;

        return $this->em->transactional(function () use ($items) {
            $count = 0;
            $batch = 300;

            foreach ($items as $item) {
                $entity = (new BaaglInbound())
                    ->setCode($item['code'] ?? null)
                    ->setNazev($item['name'] ?? null)
                    ->setUom($item['uom'] ?? null)
                    ->setStav(isset($item['qty']) ? (int)$item['qty'] : null)
                    ->setTax(isset($item['tax']) ? (int)$item['tax'] : null)
                    ->setNakupBezDph(isset($item['priceWithoutVat']) ? self::toDecimal($item['priceWithoutVat']) : null)
                    ->setNakupDph(isset($item['priceVat']) ? self::toDecimal($item['priceVat']) : null)
                    ->setMena($item['currency'] ?? null);

                $this->em->persist($entity);
                $count++;

                // dávkové flush/clear kvůli paměti
                if (($count % $batch) === 0) {
                    $this->em->flush();
                    $this->em->clear(BaaglInbound::class);
                }
            }

            $this->em->flush();
            $this->em->clear(BaaglInbound::class);

            return $count;
        });
    }

    private static function toDecimal(mixed $v, int $scale = 2): ?string
    {
        $s = trim((string)$v);
        if ($s === '') return null;
        $s = str_replace(',', '.', $s);
        return number_format((float)$s, $scale, '.', '');
    }
}
