<?php

namespace App\Console\Commands;

use App\Domain\Orders\Actions\ExpirePendingOrders as ExpirePendingOrdersAction;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('orders:expire-pending')]
#[Description('Expire les commandes en attente de paiement arrivées à échéance.')]
class ExpirePendingOrders extends Command
{
    public function handle(ExpirePendingOrdersAction $expirePendingOrders): int
    {
        $count = $expirePendingOrders->execute();

        $this->info("{$count} commande(s) expirée(s).");

        return self::SUCCESS;
    }
}
