<?php

namespace App\Controller;

use App\Classe\Mail;
use App\Entity\ResetPassword;
use App\Entity\User;
use App\Form\ResetPasswordType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class ResetPasswordController extends AbstractController
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
     * @Route("/mot-de-passe-oublie", name="reset_password")
     */
    public function index(Request $request): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('home');
        }

        if ($request->get('email')) {
            $user = $this->entityManager->getRepository(User::class)->findOneByEmail($request->get('email'));

            if ($user) {
                $reset_password = new ResetPassword();
                $reset_password->setUser($user);
                $reset_password->setToken(uniqid());
                $reset_password->setCreatedAt(new \DateTimeImmutable());
                $this->entityManager->persist($reset_password);
                $this->entityManager->flush();

                $url = $this->generateUrl('update_password', ['token' => $reset_password->getToken()]);
                $content = "Bonjour" . $user->getFirstname() . "<br> Vous avez demandé un nouveau mot de passe, <br><br> veuillez cliquer sur le lien ci-dessous pour le réinitialiser : <br> <a href='$url'>Réinitialiser le mot de passe</a>";

                $mail = new Mail();
                $mail->send($user->getEmail(), $user->getFirstname(), 'Réinitialisation de votre mot de passe sur le site NomDuSite', $content);

                $this->addFlash('notice', 'Un email vous a été envoyé pour réinitialiser votre mot de passe');

            }else{
                $this->addFlash('notice', 'Cette adresse email n\'existe pas');
            }

        }

        return $this->render('reset_password/index.html.twig', [

        ]);
    }

    /**
     * @Route("/modifier-mon-mot-de-passe/{token}", name="update_password")
     */
    public function update(Request $request, UserPasswordHasherInterface $encoder ,$token)
    {
        $reset_password = $this->entityManager->getRepository(ResetPassword::class)->findOneByToken($token);
        if (!$reset_password) {
            return $this->redirectToRoute('reset_password');
        }

        $now = new \DateTimeImmutable();

        if ( $now > $reset_password->getCreatedAt()->modify('+3 hour') ) {
            $this->addFlash('notice', 'Ce lien n\'est plus valide, merci de renouveler votre demande');
            return $this->redirectToRoute('reset_password');
        }

        $form = $this->createForm(ResetPasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $new_psw = $form->get('new_password')->getData();

            $password = $encoder->hashPassword($reset_password->getUser(), $new_psw);
            $reset_password->getUser()->setPassword($password);

            $this->entityManager->flush();
            $this->addFlash('notice', 'Votre mot de passe a bien été modifié');

            return $this->redirectToRoute('app_login');
        }

        return $this->render('reset_password/update.html.twig', [
            'form' => $form->createView()
        ]);
    }

}
