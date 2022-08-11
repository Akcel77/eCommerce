<?php

namespace App\Controller;

use App\Classe\Mail;
use App\Form\ContactType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ContactController extends AbstractController
{
    /**
     * @Route("/nous-contacter", name="contact")
     */
    public function index(Request $request): Response
    {
        $form = $this->createForm(ContactType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->addFlash('notice', 'Votre message a bien été envoyé. Notre équipe vous répondra dans les plus brefs délais.');

            $mail = new Mail();
            $content = "Bonjour </br>Vous avez reçus un message de <strong>".$form->getData()['prenom']." ".$form->getData()['nom']."</strong></br><br>Adresse email : <strong>".$form->getData()['email']."</strong> </br><br><strong>Message : </strong><br>".$form->getData()['content']."</br></br>";

            $mail->send('Akeucel@gmail.com', 'Nom du site contact', 'Vous avez recu une nouvelle demande de contact', $content);
        }

        return $this->render('contact/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
