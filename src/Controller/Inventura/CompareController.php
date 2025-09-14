<?php
namespace App\Controller\Inventura;

use App\Service\Db;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class CompareController extends AbstractController
{
    #[Route('/inventura/porovnani', name: 'inventory_compare', methods: ['GET'])]
    public function __invoke(Db $db): Response
    {
        // vytvoř/vyčisti tabulku compare
        $db->execute('DROP TABLE IF EXISTS compare');
        $db->execute(
            'CREATE TABLE compare(
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255),
                ean VARCHAR(18),
                productNumber VARCHAR(50),
                barva VARCHAR(50),
                velikost INT,
                datum DATETIME,
                scanstock INT,
                invstock INT,
                finalstock INT
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        );

        // inventura + agregované skeny
        $db->execute(
            'INSERT INTO compare (id, name, ean, productNumber, barva, velikost, datum, scanstock, invstock, finalstock)
             SELECT NULL,
                    inv.name, inv.ean, inv.productNumber, inv.barva, inv.velikost,
                    COALESCE(s.maxdatum, NOW()),
                    COALESCE(s.stock, 0),
                    inv.stock,
                    COALESCE(s.stock, 0) - inv.stock
             FROM inventura inv
             LEFT JOIN (
                SELECT productNumber, velikost, SUM(stock) stock, MAX(datum) maxdatum
                FROM scan
                GROUP BY productNumber, velikost
             ) s ON s.productNumber = inv.productNumber AND s.velikost = inv.velikost'
        );

        // "Unknown" skeny
        $db->execute(
            'INSERT INTO compare (id, name, ean, productNumber, barva, velikost, datum, scanstock, invstock, finalstock)
             SELECT NULL, name, ean, productNumber, barva, velikost, datum, stock, 0, stock
             FROM scan WHERE productNumber = "Unknown"'
        );

        $rows = $db->select('SELECT * FROM compare ORDER BY productNumber, velikost');
        return $this->render('inventura/compare.html.twig', ['rows'=>$rows]);
    }
}
