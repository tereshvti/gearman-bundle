<?php

namespace Supertag\Bundle\GearmanBundle\Annotation;

use Doctrine\Common\Annotations\Annotation;

/**
 * @Annotation
 * @Target("METHOD")
 */
final class Job extends Annotation
{
    /**
     * A name for gearman job
     *
     * @var string
     * @required
     */
    public $name;

    /**
     * Number of max retries for this job
     *
     * @var integer
     */
    public $retries = 3;

    /**
     * A job description
     *
     * @var string
     */
    public $description;
}
