<?php
/**
 * @author @fabfuel <fabian@fabfuel.de>
 * @created 14.11.14, 08:39
 */
namespace Fabfuel\Prophiler\Plugin\Phalcon\Mvc;

use Fabfuel\Prophiler\Benchmark\BenchmarkInterface;
use Fabfuel\Prophiler\Plugin\PluginAbstract;
use Phalcon\Events\Event;
use Phalcon\Mvc\View;
use Phalcon\Mvc\ViewInterface;

/**
 * Class ViewPlugin
 */
class ViewPlugin extends PluginAbstract implements ViewPluginInterface
{

    /**
     * @var BenchmarkInterface[][]
     */
    private $benchmarks = [];

    /**
     * All render levels as descriptive strings
     * @var array
     */
    private $renderLevels = [
        View::LEVEL_ACTION_VIEW => 'action',
        View::LEVEL_AFTER_TEMPLATE => 'afterTemplate',
        View::LEVEL_BEFORE_TEMPLATE => 'beforeTemplate',
        View::LEVEL_LAYOUT => 'controller',
        View::LEVEL_MAIN_LAYOUT => 'main'
    ];

    /**
     * Start view benchmark
     *
     * @param Event $event
     * @param ViewInterface $view
     */
    public function beforeRenderView(Event $event, ViewInterface $view)
    {
        $activeRenderPath = $view->getActiveRenderPath();
        if (is_array($activeRenderPath)) {
            $activeRenderPath = current($activeRenderPath);
        }
        $name = get_class($event->getSource()) . '::render: ' . basename($activeRenderPath);
        $metadata = [
            'view' => realpath($activeRenderPath) ?: $activeRenderPath,
            'level' => $this->getRenderLevel($view->getCurrentRenderLevel()),
        ];

        $this->setBenchmark($view, $this->getProfiler()->start($name, $metadata, 'View'));
    }

    /**
     * Stop view benchmark
     *
     * @param Event $event
     * @param ViewInterface $view
     */
    public function afterRenderView(Event $event, ViewInterface $view)
    {
        $benchmark = $this->getBenchmark($view);
        $this->getProfiler()->stop($benchmark);
    }

    /**
     * Stop view benchmark
     *
     * @param Event $event
     * @param ViewInterface $view
     */
    public function afterRender(Event $event, ViewInterface $view)
    {
        foreach ($this->benchmarks as $views) {
            foreach ($views as $benchmark) {
                $this->getProfiler()->stop($benchmark);
            }
        }
    }

    /**
     * @param int $renderLevelInt
     * @return string Render level
     */
    public function getRenderLevel($renderLevelInt)
    {
        return isset($this->renderLevels[$renderLevelInt]) ? $this->renderLevels[$renderLevelInt] : '';
    }

    /**
     * @param ViewInterface $view
     * @param BenchmarkInterface $benchmark
     */
    public function setBenchmark(ViewInterface $view, BenchmarkInterface $benchmark)
    {
        $activeRenderPath = $view->getActiveRenderPath();
        if (is_array($activeRenderPath)) {
            $activeRenderPath = current($activeRenderPath);
        }
        $this->benchmarks[md5($activeRenderPath)][] = $benchmark;
    }

    /**
     * @param ViewInterface $view
     * @return BenchmarkInterface
     */
    public function getBenchmark(ViewInterface $view)
    {
        $activeRenderPath = $view->getActiveRenderPath();
        if (is_array($activeRenderPath)) {
            $activeRenderPath = current($activeRenderPath);
        }
        return array_shift($this->benchmarks[md5($activeRenderPath)]);
    }

    /**
     * @param ViewInterface $view
     * @return string
     */
    public function getIdentifier(ViewInterface $view)
    {
        $activeRenderPath = $view->getActiveRenderPath();
        if (is_array($activeRenderPath)) {
            $activeRenderPath = current($activeRenderPath);
        }
        return md5($activeRenderPath);
    }
}
