<?php
namespace App\Domain;
enum FeedKind: string {
    case BaaglAll = 'BaaglAll';
    case BaaglInStock = 'BaaglInstock';
    case Shoptet = 'Shoptet';
    case ActivaAll = 'ActivaAll';
}