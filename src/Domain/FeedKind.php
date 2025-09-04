<?php
namespace App\Domain;
enum FeedKind: string {
    case All = 'all';
    case Instock = 'instock';
    case Shoptet = 'shoptet';
}