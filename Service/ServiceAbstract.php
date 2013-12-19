<?php

namespace Pois\ServiceBundle\Service;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Doctrine\ORM\EntityManager;
use Knp\Component\Pager\Paginator;
use Pois\ServiceBundle\Entity\EntityInterface;
use Pois\CommentBundle\Entity\ICommentable;
use Pois\AttachmentBundle\Entity\IUploadable;

/**
 * Abstract service
 *
 * @author
 */
abstract class ServiceAbstract
{
    /**
     * Entity class
     */
    protected $entityClass;

    /**
     * Service container.
     *
     * @var ContainerInterface
     */
    protected $container;

    /**
     * Doctrine entity manager.
     *
     * @var Doctrine\ORM\EntityManager
     */
    protected $em;

    /**
     * KPN Paginator
     *
     * @var Knp\Component\Pager\Paginator
     */
    protected $paginator;

    /**
     * constructor to setup and handle our dependency injection.
     *
     * @author peterg
     * @param  ContainerInterface $container
     * @param  EntityManager      $entityManager
     * @param  Paginator          $paginator
     * @return void
     */
    public function __construct(ContainerInterface $container, EntityManager $entityManager, Paginator $paginator, $entityClass)
    {
        $this->container   = $container;
        $this->em          = $entityManager;
        $this->paginator   = $paginator;
        $this->entityClass = $entityClass;
    }

    /**
     * method to delete an entity by id
     *
     * @param  integer $id
     * @return void
     */
    public function delete($id)
    {
        $entity = $this->get($id);
        $this->em->remove($entity);
        $this->em->flush();
    }

    /**
     * method to save or update an address entity
     *
     */
    public function save(EntityInterface $entity, $force = false, $flush = true)
    {
        $this->em->persist($entity);
        if ($flush) {
            $this->em->flush();
        }
    }

    /**
     * get new object
     *
     * @return new object
     */
    public function createNew()
    {
        return new $this->entityClass();
    }

    /**
     * method to retrieve all items
     *
     * @return ArrayCollection all entities
     */
    public function getAll()
    {
        $entities = $this->em
                    ->getRepository($this->entityClass)
                    ->findAll();

        return $entities;
    }

    /**
     * method to retreive an entity based on its id
     *
     * @param  integer $addressId
     * @return Entity
     */
    public function get($id)
    {
        return $this->em
                    ->getRepository($this->entityClass)
                    ->find($id);
    }

    /**
     * Add a comment if connected class implements ICommentable
     * @param [type] $id      [description]
     * @param [type] $userid  [description]
     * @param [type] $message [description]
     */
    public function addComment($id, $userid, $message, $isSystem = false, $persist = true)
    {
        $implements = class_implements($this->entityClass, true);

        if (!isset($implements['Pois\CommentBundle\Entity\ICommentable'])) {
            throw new \Exception("This service is not implementing ICommentable interface", 1);
        }

        //get object of our ICommentable class
        $entity = $this->get($id);

        //add new comment
        $comment = $this->container->get('g_service.message')->createNew();

        $comment->setMessage($message);
        $comment->setIsSystem($isSystem);
        if ($userid) {
            $user = $this->container->get('g_service.user')->get($userid);
        } else {
            $user = $this->container->get('security.context')->getToken()?$this->container->get('security.context')->getToken()->getUser():null;
        }
        //$userid
        $comment->setCreatedBy($user);

        //attach comment to this object
        $entity->addMessage($comment);

        if ($persist) {
            $this->save($entity, true, $persist);
            $this->container->get('g_service.message')->save($comment, null, $persist);
        }
    }

    /**
     * Add a attachment if connected class implements IUploadable
     * @param [type] $id      [description]
     * @param [type] $userid  [description]
     * @param [type] $message [description]
     */
    public function addAttachment($id, $userid, $attachment)
    {
        $implements = class_implements($this->entityClass, true);

        if (!isset($implements['Pois\AttachmentBundle\Entity\IUploadable'])) {
            throw new \Exception("This service is not implementing IUploadable interface", 1);
        }
        //get object of our IUploadable class
        $entity = $this->get($id);

        if ($userid) {
            $user = $this->container->get('g_service.user')->get($userid);
        } else {
            $user = $this->container->get('security.context')->getToken()?$this->container->get('security.context')->getToken()->getUser():null;
        }
        //$userid
        $attachment->setCreatedBy($user);

        //attach comment to this object
        $entity->addAttachment($attachment);

        $this->save($entity, true);
        $this->container->get('g_service.attachment')->save($attachment);
    }

    /**
     * Add a notification if connected class implements INotifiable
     * @param [type] $id               [description]
     * @param [type] $userid           [description]
     * @param [type] $notificationType object
     * @param array  $params           [stanMinimalny, expiryInDays]
     */
    public function addNotification($id, $userid, $notificationType, $params)
    {
        $implements = class_implements($this->entityClass, true);

        if (!isset($implements['Pois\NotificationBundle\Entity\INotifiable'])) {
            throw new \Exception("This service is not implementing INotifiable interface", 1);
        }
        //get object of our INotifiable class
        $entity = $this->get($id);

        if ($userid) {
            $user = $this->container->get('g_service.user')->get($userid);
        } else {
            $user = $this->container->get('security.context')->getToken()?$this->container->get('security.context')->getToken()->getUser():null;
        }

        $notification = $this->container->get('g_service.notification')->createNew();
        $notification->setNotificationType($notificationType);
        $notification->setParameters($params);
        // $notification->setCreatedBy($user);

        //attach comment to this object
        $entity->addNotification($notification);

        $this->save($entity, true);
        $this->container->get('g_service.notification')->save($notification);
    }

    /**
     * get object for given notification
     * @param  [type] $notificationId [description]
     * @return [type] [description]
     */
    public function getForNotification($notificationId)
    {
        $implements = class_implements($this->entityClass, true);

        if (!isset($implements['Pois\NotificationBundle\Entity\INotifiable'])) {
            throw new \Exception("This service is not implementing INotifiable interface", 1);
        }

        return $this->em
            ->getRepository($this->entityClass)
            ->findByNotification($notificationId);
    }
}
