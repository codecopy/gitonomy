<?php

namespace Gitonomy\Bundle\FrontendBundle\Controller;

use Gitonomy\Bundle\CoreBundle\Entity\Email;
use Gitonomy\Bundle\CoreBundle\Entity\User;

class EmailController extends BaseController
{
    /**
     * Action to create an email from admin user
     */
    public function adminUserListAction($userId)
    {
        $this->assertPermission('USER_EDIT');

        if (!$user = $this->getDoctrine()->getRepository('GitonomyCoreBundle:User')->find($userId)) {
            throw new HttpException(404, sprintf('No %s found with id "%d".', $className, $id));
        }

        $email   = new Email();
        $request = $this->getRequest();
        $form    = $this->createForm('useremail', $email, array(
            'validation_groups' => 'admin',
        ));

        if ('POST' == $request->getMethod()) {
            $form->bindRequest($request);
            if ($form->isValid()) {
                $this->saveEmail($user, $email);

                return $this->successAndRedirect($user, 'gitonomyfrontend_adminuser_edit', sprintf('Email "%s" added.', $email->__toString()));
            } else {
                return $this->failAndRedirect($user, 'gitonomyfrontend_adminuser_edit', 'Email you filled is not valid.');
            }
        }

        return $this->render('GitonomyFrontendBundle:Email:AdminUser/list.html.twig', array(
            'user' => $user,
            'form' => $form->createView(),
        ));
    }

    /**
     * Action to create an email from profile
     */
    public function profileListAction()
    {
        $this->assertPermission('AUTHENTICATED');

        $user    = $this->getUser();
        $email   = new Email();
        $request = $this->getRequest();

        $form = $this->createForm('useremail', $email, array(
            'validation_groups' => 'profile',
        ));

        if ('POST' == $request->getMethod()) {
            $form->bindRequest($request);
            if ($form->isValid()) {
                $this->saveEmail($user, $email);
                $this->sendActivationMail($email);

                return $this->successAndRedirect($user, 'gitonomyfrontend_profile_index', sprintf('Email "%s" added.', $email->__toString()));
            } else {
                return $this->failAndRedirect($user, 'gitonomyfrontend_profile_index', 'Email you filled is not valid.');
            }
        }

        return $this->render('GitonomyFrontendBundle:Email:Profile/list.html.twig', array(
            'user' => $user,
            'form' => $form->createView(),
        ));
    }

    /**
     * Action to make as default an email from admin user
     */
    public function adminUserDefaultAction($id)
    {
        $this->assertPermission('USER_EDIT');

        $email = $this->getEmail($id);

        $this->setDefaultEmail($email);

        return $this->successAndRedirect($user, 'gitonomyfrontend_adminuser_edit', sprintf('Email "%s" now as default.', $email->__toString()));
    }

    /**
     * Action to make as default an email from profile
     */
    public function profileDefaultAction($id)
    {
        $this->assertPermission('AUTHENTICATED');

        $user  = $this->getUser();
        $email = $this->getEmail($id, $user);

        $this->setDefaultEmail($email);

        return $this->successAndRedirect($user, 'gitonomyfrontend_profile_index', sprintf('Email "%s" now as default.', $email->__toString()));
    }

    /**
     * Action to delete an email for a user from admin user
     */
    public function adminUserDeleteAction($id)
    {
        $this->assertPermission('USER_EDIT');

        $email   = $this->getEmail($id);
        $form    = $this->createFormBuilder()->getForm();
        $request = $this->getRequest();

        if ('POST' == $request->getMethod()) {
            $form->bindRequest($request);
            if ($form->isValid()) {
                $this->deleteEmail($email);

                return $this->successAndRedirect($email->getUser(), 'gitonomyfrontend_adminuser_edit', sprintf('Email "%s" deleted.', $email->__toString()));
            }
        }

        return $this->render('GitonomyFrontendBundle:Email:AdminUser/delete.html.twig', array(
            'object' => $email,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Action to delete an email for a user from profile
     */
    public function profileDeleteAction($id)
    {
        $this->assertPermission('AUTHENTICATED');

        $user  = $this->getUser();
        $email = $this->getEmail($id, $user);

        $form    = $this->createFormBuilder()->getForm();
        $request = $this->getRequest();

        if ('POST' == $request->getMethod()) {
            $form->bindRequest($request);
            if ($form->isValid()) {
                $this->deleteEmail($email);

                return $this->successAndRedirect($email->getUser(), 'gitonomyfrontend_profile_index', sprintf('Email "%s" deleted.', $email->__toString()));
            }
        }

        return $this->render('GitonomyFrontendBundle:Email:Profile/delete.html.twig', array(
            'object' => $email,
            'form'   => $form->createView(),
        ));
    }

    public function profileSendActivationAction($id)
    {
        $this->assertPermission('AUTHENTICATED');

        $user  = $this->getUser();
        $email = $this->getEmail($id, $user);

        $this->sendActivationMail($email);

        return $this->successAndRedirect($email->getUser(), 'gitonomyfrontend_profile_index', sprintf('Activation mail for "%s" sent.', $email->__toString()));
    }

    public function adminUserSendActivationAction($id)
    {
        $this->assertPermission('USER_EDIT');

        $user  = $this->getUser();
        $email = $this->getEmail($id);

        $this->sendActivationMail($email);

        return $this->successAndRedirect($email->getUser(), 'gitonomyfrontend_adminuser_edit', sprintf('Activation mail for "%s" sent.', $email->__toString()));
    }

    public function activateAction($username, $hash)
    {
        $em   = $this->getDoctrine();
        $repo = $em->getRepository('GitonomyCoreBundle:Email');
        if (!$email = $repo->getEmailFromActivation($username, $hash)) {
            throw $this->createNotFoundException('There is no mail to activate with this link. Have you already activate it?');
        }

        $email->setActivation(null);
        $em->getEntityManager()->flush();

        return $this->successAndRedirect($email->getUser(), 'gitonomyfrontend_profile_index', sprintf('Email "%s" actived.', $email->__toString()));
    }

    protected function getEmail($id, User $user = null)
    {
        $em         = $this->getDoctrine();
        $repository = $em->getRepository('GitonomyCoreBundle:Email');

        if (null === $user) {
            $email = $repository->find($id);
        } else {
            $email = $repository->findOneBy(array('id' => $id, 'user' => $user));
        }

        if (!$email) {
            throw $this->createNotFoundException(sprintf('No Email found with id "%d".', $id));
        }
    }

    protected function saveEmail(User $user, Email $email)
    {
        $em = $this->getDoctrine()->getEntityManager();
        try {
            $em->getConnection()->beginTransaction();
            $email->setUser($user);
            $email->generateActivationHash();
            $em->persist($email);
            $em->flush();
            $em->commit();
        } catch (\Exception $e) {
            throw $e;
        }
    }

    protected function sendActivationMail(Email $email)
    {
        $this->get('gitonomy_frontend.mailer')->sendMessage('GitonomyFrontendBundle:Email:activateEmail.mail.twig',
            array('email' => $email),
            $email->getEmail()
        );
    }

    protected function failAndRedirect(User $user, $route, $message)
    {
        $this->get('session')->setFlash('warning', $message);

        return $this->redirect($this->generateUrl($route, array(
            'id' => $user->getId()
        )));
    }

    protected function successAndRedirect(User $user, $route, $message)
    {
        $this->get('session')->setFlash('success', $message);

        return $this->redirect($this->generateUrl($route, array(
            'id' => $user->getId()
        )));
    }

    protected function setDefaultEmail(Email $defaultEmail)
    {
        if (!$defaultEmail->isActived()) {
            throw new \LogicException(sprintf('Email "%s" cannot be set as default : email is not validated yet!', $email->__toString()));
        }

        $em   = $this->getDoctrine()->getEntityManager();
        $user = $defaultEmail->getUser();

        foreach ($user->getEmails() as $email) {
            if ($email->isDefault()) {
                $email->setIsDefault(false);
            }
        }

        $defaultEmail->setIsDefault(true);
        $em->flush();
    }

    protected function deleteEmail(Email $email)
    {
        if ($email->isDefault()) {
            throw new \LogicException(sprintf('Email "%s" cannot be deleted : email is default email!', $email->__toString()));
        }

        $em = $this->getDoctrine()->getEntityManager();
        $em->remove($email);
        $em->flush();
    }
}