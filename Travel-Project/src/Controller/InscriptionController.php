<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Form\RegistrationFormType;
use App\Security\UsersAuthenticator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Cache\Adapter\AbstractAdapter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Guard\GuardAuthenticatorHandler;

class InscriptionController extends AbstractController
{
    /**
     * @Route("/inscription", name="inscription")
     */
    public function inscription(Request $request, UserPasswordEncoderInterface $passwordEncoder, GuardAuthenticatorHandler $guardHandler, UsersAuthenticator $authenticator,\Swift_Mailer $mailer):Response
    {
        $user = new Utilisateur();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // encode the plain password
            $user->setPassword(
                $passwordEncoder->encodePassword(
                    $user,
                    $form->get('password')->getData()
                )
            );

            $user->setRoles(['ROLE_USER']);
            // On génère un token et on l'enregistre
            $user->setActivationToken(md5(uniqid()));

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($user);
            $entityManager->flush();

            // do anything else you need here, like send an email



        }

        // On crée le message
        $message = (new \Swift_Message('Nouveau compte'))
            // On attribue l'expéditeur
            ->setFrom('votre@adresse.fr')
            // On attribue le destinataire
            ->setTo($user->getEmail())
            // On crée le texte avec la vue
            ->setBody(
                $this->renderView(
                    'emails/activation.html.twig', ['token' => $user->getActivationToken()]
                ),
                'text/html'
            )
        ;
        $mailer->send($message);

        return $this->render('/registration/register.html.twig', [
            'registrationForm' => $form->createView()
        ]);
    }

    /**
     * @Route("/activation", name="activation")
     */
    public function activation(Request $request):Response
    {
        $entityManager = $this->getDoctrine()->getManager();

        // On recherche si un utilisateur avec ce token existe dans la base de données
        $users = $this->getDoctrine()->getRepository(Utilisateur::class);
        $user = $users->findOneBy(['activation_token' => $request->query->get('token')]);

        // Si aucun utilisateur n'est associé à ce token
        if(!$user){
            // On renvoie une erreur 404
            throw $this->createNotFoundException('Cet utilisateur n\'existe pas');
        }

        // On supprime le token
        $user->setActivationToken(null);
        $entityManager->persist($user);
        $entityManager->flush();

        // On génère un message
        $this->addFlash('message', 'Utilisateur activé avec succès');

        // On retourne à l'accueil
        return $this->redirectToRoute('login');
    }

}
