<?php

namespace Pim\Bundle\UserBundle\Controller;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Pim\Component\User\Model\Group;
use Pim\Component\User\UserEvents;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\HttpFoundation\JsonResponse;

class GroupController extends Controller
{
    /**
     * Create group form
     *
     * @Template("PimUserBundle:Group:update.html.twig")
     * @AclAncestor("pim_user_group_create")
     */
    public function createAction()
    {
        $this->dispatchGroupEvent(UserEvents::PRE_CREATE_GROUP);
        return $this->update(new Group());
    }

    /**
     * Edit group form
     *
     * @Template
     * @AclAncestor("pim_user_group_edit")
     */
    public function updateAction(Group $entity)
    {
        $this->dispatchGroupEvent(UserEvents::PRE_UPDATE_GROUP, $entity);
        return $this->update($entity);
    }

    /**
     * Delete group
     *
     * @AclAncestor("pim_user_group_remove")
     */
    public function deleteAction($id)
    {
        $em = $this->get('doctrine.orm.entity_manager');
        $groupClass = $this->container->getParameter('pim_user.group.entity.class');
        $group = $em->getRepository($groupClass)->find($id);

        if (!$group) {
            throw $this->createNotFoundException(sprintf('Group with id %d could not be found.', $id));
        }

        $em->remove($group);
        $em->flush();

        return new JsonResponse('', 204);
    }

    /**
     * @param Group $entity
     *
     * @return array|JsonResponse
     */
    private function update(Group $entity)
    {
        if ($this->get('pim_user.form.handler.group')->process($entity)) {
            $this->get('session')->getFlashBag()->add(
                'success',
                $this->get('translator')->trans('oro.user.controller.group.message.saved')
            );

            return new JsonResponse(
                [
                    'route' => 'pim_user_group_update',
                    'params' => ['id' => $entity->getId()]
                ]
            );
        }

        return [
            'form' => $this->get('pim_user.form.group')->createView(),
        ];
    }

    /**
     * @param string $event
     * @param Group  $group
     */
    private function dispatchGroupEvent($event, Group $group = null)
    {
        $this->get('event_dispatcher')->dispatch($event, new GenericEvent($group));
    }
}