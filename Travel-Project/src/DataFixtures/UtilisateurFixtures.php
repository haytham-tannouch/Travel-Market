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
        $user->setUsername('admin');
        $user->setPassword(
            $this->encoder->encodePassword($user,'0000')
        );


        $user->setEmail('admin@gmail.com');

        $user->setDescription('admin');

        $manager->persist($user);

        $manager->flush();
    }
}
