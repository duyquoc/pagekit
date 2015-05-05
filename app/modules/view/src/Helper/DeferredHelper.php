<?php

namespace Pagekit\View\Helper;

use Pagekit\Application;
use Pagekit\View\View;

class DeferredHelper implements HelperInterface
{
    /**
     * @var array
     */
    protected $deferred = [];

    /**
     * @var array
     */
    protected $placeholder = [];

    /**
     * Constructor.
     *
     * @param View        $view
     * @param Application $app
     */
    public function __construct(View $view, Application $app)
    {
        $view->on('render', function ($event) use ($app) {

            $name = $event->getTemplate();

            if (isset($this->placeholder[$name])) {

                $this->deferred[$name] = clone $event;

                $event->setResult($this->placeholder[$name]);
                $event->stopPropagation();
            }

        }, 15);

        $app->on('app.response', function ($e, $request, $response) use ($view) {

            $dispatcher = $e->getDispatcher();

            foreach ($this->deferred as $name => $event) {

                // TODO fix prefix
                $dispatcher->trigger($event->setName("view.$name"), [$view]);
                $response->setContent(str_replace($this->placeholder[$name], $event->getResult(), $response->getContent()));
            }

        }, 10);
    }

    /**
     * Defers a template render call.
     *
     * @return string
     */
    public function __invoke($name)
    {
        $this->placeholder[$name] = sprintf('<!-- %s -->', uniqid());
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'defer';
    }
}
