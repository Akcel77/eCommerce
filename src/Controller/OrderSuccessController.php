<?php

namespace App\Controller;

use App\Classe\Cart;
use App\Entity\Order;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class OrderSuccessController extends AbstractController
{
    private $entityManager;

    /**
     * @param $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @Route("/commande/merci/{stripeSessionId}", name="order_validate")
     */
    public function index($stripeSessionId, Cart $cart): Response
    {
        $order = $this->entityManager->getRepository(Order::class)->findOneBy(['stripeSessionId' => $stripeSessionId]);

        if(!$order || $order->getUser() != $this->getUser()){
            return $this->redirectToRoute('home');
        }

        //Modifier le statut isPaid de notre commande en passant a 1

        if(!$order->getIsPaid()){

            $cart->remove();
            $order->setIsPaid(1);
            $this->entityManager->flush();
            //Envoyer un email a notre client pour lui confirmer la commande
        }

        //Afficher les quelques informations de la commande de l'utilisateur

        return $this->render('order_success/index.html.twig', [
            'order' => $order,
        ]);
    }
}
