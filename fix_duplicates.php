<?php

use App\Entity\Client;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Dotenv\Dotenv;

require __DIR__ . '/vendor/autoload.php';

// Charger les variables d'environnement
if (file_exists(__DIR__ . '/.env')) {
    (new Dotenv())->load(__DIR__ . '/.env');
}

// Boot Symfony Kernel
$kernel = new \App\Kernel($_ENV['APP_ENV'], (bool) $_ENV['APP_DEBUG']);
$kernel->boot();

/** @var EntityManagerInterface $em */
$em = $kernel->getContainer()->get('doctrine')->getManager();

$em->getConnection()->beginTransaction();

try {
    // ----------------------------
    // Supprimer les doublons dans Client
    // ----------------------------
    echo "Traitement des doublons dans Client...\n";

    $clientRepo = $em->getRepository(Client::class);
    $duplicateClients = $em->createQueryBuilder()
        ->select('c.email')
        ->from(Client::class, 'c')
        ->groupBy('c.email')
        ->having('COUNT(c.id) > 1')
        ->getQuery()
        ->getResult();

    $totalDeletedClients = 0;
    foreach ($duplicateClients as $dup) {
        $email = $dup['email'];
        $clients = $clientRepo->findBy(['email' => $email], ['id' => 'ASC']);
        array_shift($clients); // garder le premier

        foreach ($clients as $c) {
            $em->remove($c);
            $totalDeletedClients++;
            echo "Suppression Client ID {$c->getId()} (email: $email)\n";
        }
    }

    // ----------------------------
    // Supprimer les doublons dans User
    // ----------------------------
    echo "Traitement des doublons dans User...\n";

    $userRepo = $em->getRepository(User::class);
    $duplicateUsers = $em->createQueryBuilder()
        ->select('u.email')
        ->from(User::class, 'u')
        ->groupBy('u.email')
        ->having('COUNT(u.id) > 1')
        ->getQuery()
        ->getResult();

    $totalDeletedUsers = 0;
    foreach ($duplicateUsers as $dup) {
        $email = $dup['email'];
        $users = $userRepo->findBy(['email' => $email], ['id' => 'ASC']);
        array_shift($users); // garder le premier

        foreach ($users as $u) {
            $em->remove($u);
            $totalDeletedUsers++;
            echo "Suppression User ID {$u->getId()} (email: $email)\n";
        }
    }

    $em->flush();
    $em->getConnection()->commit();

    echo "\nSuppression terminée !\n";
    echo "Total Clients supprimés : $totalDeletedClients\n";
    echo "Total Users supprimés   : $totalDeletedUsers\n";

    // ----------------------------
    // Vérification finale
    // ----------------------------
    $remainingClients = $em->createQueryBuilder()
        ->select('c.email, COUNT(c.id) as count')
        ->from(Client::class, 'c')
        ->groupBy('c.email')
        ->having('COUNT(c.id) > 1')
        ->getQuery()
        ->getResult();

    $remainingUsers = $em->createQueryBuilder()
        ->select('u.email, COUNT(u.id) as count')
        ->from(User::class, 'u')
        ->groupBy('u.email')
        ->having('COUNT(u.id) > 1')
        ->getQuery()
        ->getResult();

    if (!$remainingClients && !$remainingUsers) {
        echo "✅ Vérification finale OK : aucun doublon restant.\n";
    } else {
        echo "⚠️ Attention : certains doublons restent dans la base !\n";
        if ($remainingClients) {
            echo "Clients restant en doublon :\n";
            print_r($remainingClients);
        }
        if ($remainingUsers) {
            echo "Users restant en doublon :\n";
            print_r($remainingUsers);
        }
    }
} catch (\Exception $e) {
    $em->getConnection()->rollBack();
    echo "❌ Erreur : " . $e->getMessage() . "\n";
    exit(1);
}
