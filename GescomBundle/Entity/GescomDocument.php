<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @author      Nicolas Marchand <contact@nicolasmarchand.dev>
 * @copyright   Copyright 2018 Nicolas Marchand
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
namespace Nmdev\Bundle\GescomBundle\Entity;

use Oro\Bundle\OrderBundle\Entity\Order;
use Nmdev\Bundle\GescomBundle\Model\ExtendGescomDocument;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareInterface;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareTrait;

/**
 * GescomDocument
 *
 * @ORM\Table(name="gescom_document")
 * @ORM\Entity(repositoryClass="Nmdev\Bundle\GescomBundle\Repository\GescomDocumentRepository")
 * @Config(
 *     defaultValues={
 *         "entity"={
 *             "label"="Document gestion commerciale",
 *             "plural_label"="Documents gestion commerciale"
 *         },
 *          "dataaudit"={
 *              "auditable"=true
 *          },
 *          "security"={
 *              "type"="ACL",
 *              "group_name"=""
 *          }
 *     }
 * )
 */
class GescomDocument extends ExtendGescomDocument implements DatesAwareInterface
{

    use DatesAwareTrait;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;


    /**
     * @var string
     *
     * @ORM\Column(name="identifier", type="string", nullable=true, length=50)
     */
    private $identifier;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", nullable=true, length=50)
     */
    private $type;

    /**
     * @var bool
     *
     * @ORM\Column(name="draft", type="boolean", options={"default"=false})
     */
    private $draft;

    /**
     * @var bool
     *
     * @ORM\Column(name="sent", type="boolean", options={"default"=false})
     */
    private $sent;


    /**
     * @var string
     *
     * @ORM\Column(name="path", type="string", nullable=true, length=255)
     */
    private $path;


    /**
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\OrderBundle\Entity\Order")
     * @ORM\JoinColumn(name="order_id", referencedColumnName="id")
     */
    private $order;



    /**
     * Get id
     *
     * @return int
     */
    public function getId() {
        return $this->id;
    }


    /**
     * Get identifier
     *
     * @return string
     */
    public function getIdentifier() {
        return $this->identifier;
    }

    /**
     * Set identifier
     *
     * @param string $identifier
     *
     * @return GescomDocument
     */
    public function setIdentifier($identifier) {
        $this->identifier = $identifier;

        return $this;
    }


    /**
     * Get type
     *
     * @return string
     */
    public function getType() {
        return $this->type;
    }

    /**
     * Set type
     *
     * @param string $type
     *
     * @return GescomDocument
     */
    public function setType($type) {
        $this->type = $type;

        return $this;
    }


    /**
     * Get sent
     *
     * @return string
     */
    public function getSent() {
        return $this->sent;
    }

    /**
     * Set sent
     *
     * @param boolean $sent
     *
     * @return GescomDocument
     */
    public function setSent($sent) {
        $this->sent= $sent;

        return $this;
    }


    /**
     * Get draft
     *
     * @return string
     */
    public function getDraft() {
        return $this->draft;
    }

    /**
     * Set identifier
     *
     * @param boolean $draft
     *
     * @return GescomDocument
     */
    public function setDraft($draft) {
        $this->draft= $draft;

        return $this;
    }

    /**
     * Get path
     *
     * @return string
     */
    public function getPath() {
        return $this->path;
    }

    /**
     * Set path
     *
     * @param string $path
     *
     * @return GescomDocument
     */
    public function setPath($path) {
        $this->path= $path;

        return $this;
    }


    /**
     * Set order
     *
     * @return GescomDocument
     */
    public function setOrder(Order $order) {
        $this->order = $order;
        return $this;
    }

    /**
     * Get order
     *
     * @return Order
     */
    public function getOrder() {
        return $this->order;
    }





    /**
     * Pre persist event listener
     *
     * @ORM\PrePersist
     */
    public function prePersist()
    {
        $this->createdAt = new \DateTime('now', new \DateTimeZone('UTC'));
        $this->updatedAt = new \DateTime('now', new \DateTimeZone('UTC'));
    }

    /**
     * Pre update event handler
     *
     * @ORM\PreUpdate
     */
    public function preUpdate()
    {
        $this->updatedAt = new \DateTime('now', new \DateTimeZone('UTC'));
    }


}
