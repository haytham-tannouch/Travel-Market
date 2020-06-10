<?php

namespace App\DataFixtures;

use App\Entity\Utilisateur;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoder;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class UtilisateurFixtures extends Fixture
{
    private $encoder;
    public function __construct(UserPasswordEncoderInterface $encoder)
    {
        $this->encoder=$encoder;
    }

    public function load(ObjectManager $manager)
    {
        $user =new Utilisateur();
        $user->setUsername('wail');
        $user->setPassword(
            $this->encoder->encodePassword($user,'0000')
        );

        $user->setEmail('wail@gmail.com');
        $user->setRoles(['ROLE_USER']);

        $manager->persist($user);

        $manager->flush();

        $user1 =new Utilisateur();
        $user1->setUsername('admin');
        $user1->setPassword(
            $this->encoder->encodePassword($user1,'0000')
        );

        $user1->setEmail('admin@gmail.com');
        $user1->setRoles(['ROLE_ADMIN']);

        $manager->persist($user1);

        $manager->flush();
    }
}
