<?php namespace Betasyntax\Core\Services;

use Betasyntax\Core\Application;
use League\Container\ServiceProvider\AbstractServiceProvider;
use League\Container\ServiceProvider\BootableServiceProviderInterface;

class ServiceProvider extends AbstractServiceProvider implements BootableServiceProviderInterface
{
    /**
     * [$app The main app instance. Gets populated during app creation.]
     * @var [type]
     */
    protected $app;

    /**
     * The provides array is a way to let the container
     * know that a service is provided by this service
     * provider. Every service that is registered via
     * this service provider must have an alias added
     * to this array or it will be ignored.
     *
     * @var array
     */
    protected $provides = [];
    
    /**
     * [__construct Include the main app object so we can Inject the app instance into our classes as needed.]
     * @param Application $app [Application Class]
     */
    public function __construct(Application $app) 
    {
        $this->app = $app;
    }

    /**
     * Set the appProviders in the main application object and set the provides array for registering the sevice providers
     * @param [type] $providers [description]
     */
    private function setProviders($providers) {
        foreach ($providers as $key => $value) {
            $this->provides[] = $value;
            $this->app->appProviders[] = [$key=>$value];
        }
    }

    private function getProviders()
    {
        return $serviceProviders = config('app','providers');
    }
    /**
     * In much the same way, this method has access to the container
     * itself and can interact with it however you wish, the difference
     * is that the boot method is invoked as soon as you register
     * the service provider with the container meaning that everything
     * in this method is eagerly loaded.
     *
     * If you wish to apply inflectors or register further service providers
     * from this one, it must be from a bootable service provider like
     * this one, otherwise they will be ignored.
     */
    public function boot()
    {
        //this will automatically make containers for all of our classes.
        $this->getContainer()->delegate(
          new \League\Container\ReflectionContainer
        );
        // these are the core of the system. 
        // Only load debug bar in production mode
        if(!$this->app->isProd()) {
            $this->app->debugbar = $this->container->get('Betasyntax\DebugBar\DebugBar');
            debugStack('Application');
        }
        $this->app->config = $this->container->get('Betasyntax\Config');
        $this->app->mountManager = $this->container->get('Betasyntax\Core\MountManager\Mounts');
        $this->app->logger = $this->container->get('Betasyntax\Logger\Logger');
        $this->app->helpers = $this->container->get('App\Functions');  
        $this->app->router = $this->container->get('Betasyntax\Router\Router');  
        // debug the application object
        // register any user provided middlewares
        $this->register();
    }

    /**
     * This is where the magic happens, within the method you can
     * access the container and register or retrieve anything
     * that you need to, but remember, every alias registered
     * within this method must be declared in the `$provides` array.
     */
    public function register()
    {   

        //register the rest of the service providers
        $this->setProviders($this->getProviders());
        for($i=0;$i<count($this->provides);$i++) {
            $this->getContainer()->add($this->provides[$i]);
        }
    }
}