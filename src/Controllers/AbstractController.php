<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Controllers;

/**
 * Description of AbstractController
 *
 * @author asok1
 */
use Slim\Container;
use Slim\Router;
/**
 * AbstractAction (Base class).
 */
abstract class AbstractController
{
    protected $validator;
    protected $db;
    protected $httpClient;
    protected $logger;
    protected $container;
    /**
     * Constructor.
     *
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->validator = $container->get('validator');
        $this->db = $container->get('db');
//        $this->httpClient = $container->get('httpClient');
        $this->logger = $container->get('logger');
    }
}
