<?php

/*
 * This file is part of the FOSRestBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\RestBundle\Routing\Loader\Reader;

use Doctrine\Common\Annotations\Reader;
use FOS\RestBundle\Controller\Annotations;
use FOS\RestBundle\Routing\ClassResourceInterface;
use FOS\RestBundle\Routing\RestRouteCollection;
use Symfony\Component\Config\Resource\FileResource;

/**
 * REST controller reader.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * @internal
 */
class RestControllerReader
{
    private $actionReader;
    private $annotationReader;

    public function __construct(RestActionReader $actionReader, Reader $annotationReader)
    {
        $this->actionReader = $actionReader;
        $this->annotationReader = $annotationReader;
    }

    public function getActionReader(): RestActionReader
    {
        return $this->actionReader;
    }

    public function read(\ReflectionClass $reflectionClass): RestRouteCollection
    {
        $collection = new RestRouteCollection();
        $collection->addResource(new FileResource($reflectionClass->getFileName()));

        // read prefix annotation
        if ($annotation = $this->annotationReader->getClassAnnotation($reflectionClass, Annotations\Prefix::class)) {
            $this->actionReader->setRoutePrefix($annotation->value);
        }

        // read name-prefix annotation
        if ($annotation = $this->annotationReader->getClassAnnotation($reflectionClass, Annotations\NamePrefix::class)) {
            $this->actionReader->setNamePrefix($annotation->value);
        }

        // read version annotation
        if ($annotation = $this->annotationReader->getClassAnnotation($reflectionClass, Annotations\Version::class)) {
            $this->actionReader->setVersions($annotation->value);
        }

        $resource = [];
        // read route-resource annotation
        if ($annotation = $this->annotationReader->getClassAnnotation($reflectionClass, Annotations\RouteResource::class)) {
            $resource = explode('_', $annotation->resource);
            $this->actionReader->setPluralize($annotation->pluralize);
        } elseif ($reflectionClass->implementsInterface(ClassResourceInterface::class)) {
            $resource = preg_split(
                '/([A-Z][^A-Z]*)Controller/',
                $reflectionClass->getShortName(),
                -1,
                PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE
            );
            if (empty($resource)) {
                throw new \InvalidArgumentException("Controller '{$reflectionClass->name}' does not identify a resource");
            }
        }

        // trim '/' at the start of the prefix
        if ('/' === substr($prefix = $this->actionReader->getRoutePrefix(), 0, 1)) {
            $this->actionReader->setRoutePrefix(substr($prefix, 1));
        }

        // read action routes into collection
        foreach ($reflectionClass->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            $this->actionReader->read($collection, $method, $resource);
        }

        $this->actionReader->setRoutePrefix(null);
        $this->actionReader->setNamePrefix(null);
        $this->actionReader->setVersions(null);
        $this->actionReader->setPluralize(null);
        $this->actionReader->setParents([]);

        return $collection;
    }
}
