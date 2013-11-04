<?php

namespace Supertag\Bundle\GearmanBundle\Command;

interface GearmanJobCommandInterface
{
    /**
     * Get a number of retries for the command
     *
     * @return integer
     */
    function getNumRetries();
}
