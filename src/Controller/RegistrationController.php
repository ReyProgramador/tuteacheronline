<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Security\EmailVerifier;
use App\Security\UserAuthenticator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;

class RegistrationController extends AbstractController
{
    private EmailVerifier $emailVerifier;

    public const TOKEN = '$2y$10$MGMtPzWXFOFosK4gwGio1e.l6OP2gvA72sCfKKQ76u.6WC5gVQUJ6';
    public const COUNTRY_ID = 1;
    public const STATU_ID = 0;
    public const IS_BUYER = 0;
    public const IS_VERIFIED = 0;

    public function __construct(EmailVerifier $emailVerifier)
    {
        $this->emailVerifier = $emailVerifier;
    }

    #[Route('/register', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, UserAuthenticatorInterface $userAuthenticator, UserAuthenticator $authenticator, EntityManagerInterface $entityManager)
    {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

             // return new Response($form);

            // print_r($form->isValid());

            // exit;

        // return $this->json('data');


            // encode the plain password
            $user->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );


            // extraemos campos del form, getData lo usamos para que sea request y no form
            $first_name = $form->get('first_name')->getData();
            $last_name = $form->get('last_name')->getData();
            // $token = '$2y$10$MGMtPzWXFOFosK4gwGio1e.l6OP2gvA72sCfKKQ76u.6WC5gVQUJ6';
            // $country_id = 1;
            // $statu_id = 0;
            // $is_buyer = 0;
            // $is_verified = 0;

            $user->setFirstName($first_name);
            $user->setLastName($last_name);
            // $user->setToken($token);
            $user->setToken(self::TOKEN);
            // $user->setCountryId($country_id);
            $user->setCountryId(self::COUNTRY_ID);
            // $user->setStatuId($statu_id);
            $user->setStatuId(self::STATU_ID);
            // $user->setIsBuyer($is_buyer);
            $user->setIsBuyer(self::IS_BUYER);
            // $user->setIsVerified($is_verified);
            $user->setIsVerified(self::IS_VERIFIED);              


            $entityManager->persist($user);
            $entityManager->flush();

            // generate a signed url and email it to the user
            $this->emailVerifier->sendEmailConfirmation('app_verify_email', $user,
                (new TemplatedEmail())
                ->from(new Address('contacto@jonathan-castro.com', 'Academia Online Mail Bot'))
                ->to($user->getEmail())
                ->subject('Please Confirm your Email')
                ->htmlTemplate('registration/confirmation_email.html.twig')
            );
            // do anything else you need here, like send an email

            return $userAuthenticator->authenticateUser(
                $user,
                $authenticator,
                $request
            );
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    #[Route('/verify/email', name: 'app_verify_email')]
    public function verifyUserEmail(Request $request, TranslatorInterface $translator): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        // validate email confirmation link, sets User::isVerified=true and persists
        try {
            $this->emailVerifier->handleEmailConfirmation($request, $this->getUser());
        } catch (VerifyEmailExceptionInterface $exception) {
            $this->addFlash('verify_email_error', $translator->trans($exception->getReason(), [], 'VerifyEmailBundle'));

            return $this->redirectToRoute('app_register');
        }

        // @TODO Change the redirect on success and handle or remove the flash message in your templates
        $this->addFlash('success', 'Your email address has been verified.');

        return $this->redirectToRoute('app_register');
    }
}
