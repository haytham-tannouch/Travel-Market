<?php

namespace App\Controller;

use App\Form\ResetPassType;
use App\Entity\Utilisateur;
use App\Repository\UtilisateurRepository;
use http\Client\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Csrf\TokenGenerator\TokenGeneratorInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
USE App\Controller\UsersAuthenticator;


class SecurityController extends Controller
{
    /**
     * @Route("/login", name="login")
     */
    public function login(Request $request , AuthenticationUtils $utils)
    {
        $error=$utils->getLastAuthenticationError();

        $lastusername=$utils->getLastUsername();
        return $this->render('security/login.html.twig', [
            'error'         => $error,
            'last_username' => $lastusername
        ]);
    }

    /**
     * @Route("afterlogin", name="after_login")
     */
    public function afterlogin()
    {
        if($this->isGranted('ROLE_ADMIN'))
        {
            return $this->redirectToRoute('dashboard');
        }
        else if($this->isGranted('ROLE_USER'))
        {
            return $this->redirectToRoute('user');
        }
    }

    /**
     * @Route("/logout" ,name="logout")
     */
    public function logout()
    {

    }

    /**
     * @Route("/oubli-pass", name="app_forgotten_password")
     */
    public function oubliPass(Request $request, UtilisateurRepository $users, \Swift_Mailer $mailer, TokenGeneratorInterface $tokenGenerator)
    {
        // On initialise le formulaire
        $form = $this->createForm(ResetPassType::class);

        // On traite le formulaire
        $form->handleRequest($request);

        // Si le formulaire est valide
        if ($form->isSubmitted() && $form->isValid()) {
            // On récupère les données
            $donnees = $form->getData();

            // On cherche un utilisateur ayant cet e-mail
            $user = $users->findOneByEmail($donnees['email']);

            // Si l'utilisateur n'existe pas
            if ($user === null) {
                // On envoie une alerte disant que l'adresse e-mail est inconnue
                $this->addFlash('danger', 'Cette adresse e-mail est inconnue');

                // On retourne sur la page de connexion
                return $this->redirectToRoute('login');
            }

            // On génère un token
            $token = $tokenGenerator->generateToken();

            // On essaie d'écrire le token en base de données
            try{
                $user->setResetToken($token);
                $entityManager = $this->getDoctrine()->getManager();
                $entityManager->persist($user);
                $entityManager->flush();
            } catch (\Exception $e) {
                $this->addFlash('warning', $e->getMessage());
                return $this->redirectToRoute('login');
            }

            // On génère l'URL de réinitialisation de mot de passe
            $url = $this->generateUrl('app_reset_password', ['token' => $token], UrlGeneratorInterface::ABSOLUTE_URL);

            // On génère l'e-mail
            $message = (new \Swift_Message('Mot de passe oublié'))
                ->setFrom('votre@adresse.fr')
                ->setTo($user->getEmail())
                ->setBody(
                    "Bonjour,<br><br>Une demande de réinitialisation de mot de passe a été effectuée pour le site Nouvelle-Techno.fr. Veuillez cliquer sur le lien suivant : " . $url .'</p>',
                    'text/html'
                )
            ;

            // On envoie l'e-mail
            $mailer->send($message);

            // On crée le message flash de confirmation
            $this->addFlash('message', 'E-mail de réinitialisation du mot de passe envoyé !');

            // On redirige vers la page de login
            //return $this->redirectToRoute('app_forgotten_password');
        }

        // On envoie le formulaire à la vue
        return $this->render('security/forgotten_password.html.twig',['emailForm' => $form->createView()]);
    }

    /**
     * @Route("/reset_pass/{token}", name="app_reset_password")
     */
    public function resetPassword(Request $request, string $token, UserPasswordEncoderInterface $passwordEncoder)
    {
        // On cherche un utilisateur avec le token donné
        $user = $this->getDoctrine()->getRepository(Utilisateur::class)->findOneBy(['reset_token' => $token]);

        // Si l'utilisateur n'existe pas
        if ($user === null) {
            // On affiche une erreur
            $this->addFlash('danger', 'Token Inconnu');
            return $this->redirectToRoute('login');
        }

        // Si le formulaire est envoyé en méthode post
        if ($request->isMethod('POST')) {
            // On supprime le token
            $user->setResetToken(null);

            // On chiffre le mot de passe
            $user->setPassword($passwordEncoder->encodePassword($user, $request->request->get('password')));

            // On stocke
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($user);
            $entityManager->flush();

            // On crée le message flash
            $this->addFlash('message', 'Mot de passe mis à jour');

            // On redirige vers la page de connexion
            return $this->redirectToRoute('login');
        }else {
            // Si on n'a pas reçu les données, on affiche le formulaire
            return $this->render('security/reset_password.html.twig', ['token' => $token]);
        }

    }
}
