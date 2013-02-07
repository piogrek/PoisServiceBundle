<?php

namespace Pois\ServiceBundle\Entity;

/**
 * Interface for Entities
 *
 */
interface EntityInterface
{
    /**
     * return object converted to array
     * @return Array entity values
     */
    public function toArray();
   
}