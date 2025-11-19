<?php
namespace App\Domain;
enum FeedKind: string {
    case BaaglAll = 'BaaglAll';
    case BaaglInStock = 'BaaglInstock';
    case ShoptetBaagl = 'ShoptetBaagl';
    case ShoptetActiva = 'ShoptetActiva';
    case ActivaAll = 'ActivaAll';
}