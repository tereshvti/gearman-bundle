<?php

namespace Supertag\Bundle\GearmanBundle;

final class Workload
{
    private $parameters;

    /**
     * Construct with $parameters for job command
     *
     * @param array $parameters - list of parameters for job command
     *  example:
     *      array(
     *          "--force" => null,
     *          "an argument",
     *          "-s" => "short option",
     *          "--long" => "long option"
     *      )
     */
    public function __construct(array $parameters = array())
    {
        $this->parameters = $parameters;
    }

    public function __toString()
    {
        return serialize($this->parameters);
    }
}
