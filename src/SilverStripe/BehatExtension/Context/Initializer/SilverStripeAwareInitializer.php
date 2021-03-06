<?php

namespace SilverStripe\BehatExtension\Context\Initializer;

use Behat\Behat\Context\Initializer\InitializerInterface;
use Behat\Behat\Context\ContextInterface;
use SilverStripe\BehatExtension\Context\SilverStripeAwareContextInterface;
use SilverStripe\Core\Injector\Injector;

/*
 * This file is part of the Behat/SilverStripeExtension
 *
 * (c) Michał Ochman <ochman.d.michal@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * SilverStripe aware contexts initializer.
 * Sets SilverStripe instance to the SilverStripeAware contexts.
 *
 * @author Michał Ochman <ochman.d.michal@gmail.com>
 */
class SilverStripeAwareInitializer implements InitializerInterface
{

    private $databaseName;

    /**
     * @var array
     */
    protected $ajaxSteps;

    /**
     * @var Int Timeout in milliseconds
     */
    protected $ajaxTimeout;

    /**
     * @var String {@link see SilverStripeContext}
     */
    protected $adminUrl;

    /**
     * @var String {@link see SilverStripeContext}
     */
    protected $loginUrl;

    /**
     * @var String {@link see SilverStripeContext}
     */
    protected $screenshotPath;

    /**
     * @var object {@link TestSessionEnvironment}
     */
    protected $testSessionEnvironment;

    /**
     * @var string PHP file to included before loading Core.php
     */
    protected $bootstrapFile;

    /**
     * Initializes initializer.
     *
     * @param string $frameworkPath
     */
    public function __construct($frameworkPath, $bootstrapFile = null)
    {
        $this->bootstrap($frameworkPath, $bootstrapFile);

        file_put_contents('php://stdout', "Creating test session environment" . PHP_EOL);

        $testEnv = Injector::inst()->get('TestSessionEnvironment');
        $testEnv->startTestSession(array(
            'createDatabase' => true
        ));

        $state = $testEnv->getState();

        $this->databaseName = $state->database;
        $this->testSessionEnvironment = $testEnv;

        file_put_contents('php://stdout', "Temp Database: $this->databaseName" . PHP_EOL . PHP_EOL);

        register_shutdown_function(array($this, '__destruct'));
    }

    public function __destruct()
    {
        // Add condition here as register_shutdown_function() also calls this in __construct()
        if ($this->testSessionEnvironment) {
            file_put_contents('php://stdout', "Killing test session environment...");
            $this->testSessionEnvironment->endTestSession();
            $this->testSessionEnvironment = null;
            file_put_contents('php://stdout', " done!" . PHP_EOL);
        }
    }

    /**
     * Checks if initializer supports provided context.
     *
     * @param ContextInterface $context
     *
     * @return Boolean
     */
    public function supports(ContextInterface $context)
    {
        return $context instanceof SilverStripeAwareContextInterface;
    }

    /**
     * Initializes provided context.
     *
     * @param ContextInterface $context
     */
    public function initialize(ContextInterface $context)
    {
        $context->setDatabase($this->databaseName);
        $context->setAjaxSteps($this->ajaxSteps);
        $context->setAjaxTimeout($this->ajaxTimeout);
        $context->setScreenshotPath($this->screenshotPath);
        $context->setRegionMap($this->regionMap);
        $context->setAdminUrl($this->adminUrl);
        $context->setLoginUrl($this->loginUrl);
    }

    public function setAjaxSteps($ajaxSteps)
    {
        if ($ajaxSteps) {
            $this->ajaxSteps = $ajaxSteps;
        }
    }

    public function getAjaxSteps()
    {
        return $this->ajaxSteps;
    }

    public function setAjaxTimeout($ajaxTimeout)
    {
        $this->ajaxTimeout = $ajaxTimeout;
    }

    public function getAjaxTimeout()
    {
        return $this->ajaxTimeout;
    }

    public function setAdminUrl($adminUrl)
    {
        $this->adminUrl = $adminUrl;
    }

    public function getAdminUrl()
    {
        return $this->adminUrl;
    }

    public function setLoginUrl($loginUrl)
    {
        $this->loginUrl = $loginUrl;
    }

    public function getLoginUrl()
    {
        return $this->loginUrl;
    }

    public function setScreenshotPath($screenshotPath)
    {
        $this->screenshotPath = $screenshotPath;
    }

    public function getScreenshotPath()
    {
        return $this->screenshotPath;
    }

    public function getRegionMap()
    {
        return $this->regionMap;
    }

    public function setRegionMap($regionMap)
    {
        $this->regionMap = $regionMap;
    }

    /**
     * @param string $frameworkPath Absolute path to 'framework' module
     */
    protected function bootstrap($frameworkPath, $bootstrapFile = null)
    {
        file_put_contents('php://stdout', 'Bootstrapping' . PHP_EOL);

        // Require a bootstrap file, if provided
        if ($bootstrapFile) {
            require_once($bootstrapFile);
        }

        // Connect to database and build manifest
        $_GET['flush'] = 1;
        require_once('Core/Core.php');
        unset($_GET['flush']);

        // Remove the error handler so that PHPUnit can add its own
        restore_error_handler();
    }
}
