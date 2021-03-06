<?php
/**
 * Licensed under The GPL-3.0 License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @since    2.0.0
 * @author   Christopher Castro <chris@quickapps.es>
 * @link     http://www.quickappscms.org
 * @license  http://opensource.org/licenses/gpl-3.0.html GPL-3.0 License
 */
namespace QuickApps\View;

use Block\View\Region;
use Cake\Event\Event;
use Cake\Event\EventManager;
use Cake\Network\Request;
use Cake\Network\Response;
use Cake\Utility\Hash;
use Cake\View\View as CakeView;
use QuickApps\Event\HookAwareTrait;
use QuickApps\Event\HooktagAwareTrait;
use QuickApps\View\ViewModeAwareTrait;

/**
 * QuickApps View class.
 *
 * Extends Cake's View class to adds some QuickAppsCMS's specific functionalities
 * such as theme regions handling, objects rendering, and more.
 *
 * @property \Block\View\Helper\BlockHelper $Block
 */
class View extends CakeView
{

    use HookAwareTrait;
    use HooktagAwareTrait;
    use ViewModeAwareTrait;

    /**
     * True when the view has been rendered.
     *
     * Used to stop infinite loops when using render() method.
     *
     * @var bool
     */
    protected $_hasRendered = false;

    /**
     * Holds all region instances created for later access.
     *
     * @var array
     */
    protected $_regions = [];

    /**
     * {@inheritDoc}
     *
     * The following helpers will be automatically loaded:
     *
     * - Url
     * - Html
     * - Form
     * - Menu
     * - jQuery
     */
    public function __construct(
        Request $request = null,
        Response $response = null,
        EventManager $eventManager = null,
        array $viewOptions = []
    ) {
        $defaultOptions = [
            'helpers' => [
                'Url' => ['className' => 'QuickApps\View\Helper\UrlHelper'],
                'Html' => ['className' => 'QuickApps\View\Helper\HtmlHelper'],
                'Form' => ['className' => 'QuickApps\View\Helper\FormHelper'],
                'Menu' => ['className' => 'Menu\View\Helper\MenuHelper'],
                'jQuery' => ['className' => 'Jquery\View\Helper\JqueryHelper'],
            ]
        ];
        $viewOptions = Hash::merge($defaultOptions, $viewOptions);
        parent::__construct($request, $response, $eventManager, $viewOptions);
    }

    /**
     * Defines a new theme region to be rendered.
     *
     * ### Usage:
     *
     * Merge `left-sidebar` and `right-sidebar` regions together, the resulting
     * region limits the number of blocks it can holds to `3`:
     *
     * ```php
     * echo $this->region('left-sidebar')
     *     ->append($this->region('right-sidebar'))
     *     ->blockLimit(3);
     * ```
     *
     * ### Valid options are:
     *
     * - `fixMissing`: When creating a region that is not defined by the theme, it
     *   will try to fix it by adding it to theme's regions if this option is set to
     *   TRUE. Defaults to NULL which automatically enables when `debug` is enabled.
     *   This option will not work when using QuickAppsCMS's core themes. (NOTE:
     *   This option will alter theme's `composer.json` file).
     *
     * - `theme`: Name of the theme this regions belongs to. Defaults to auto-
     *   detect.
     *
     * @param string $name Theme's region machine-name. e.g. `left-sidebar`
     * @param array $options Additional options for region being created
     * @param bool $force Whether to skip reading from cache or not, defaults to
     *  false will get from cache if exists.
     * @return \Block\View\Region Region object
     * @see \Block\View\Region
     */
    public function region($name, $options = [], $force = false)
    {
        $this->alter('View.region', $name, $options);
        if (empty($this->_regions[$name]) || $force) {
            $this->_regions[$name] = new Region($this, $name, $options);
        }
        return $this->_regions[$name];
    }

    /**
     * {@inheritDoc}
     *
     * Overrides Cake's view rendering method. Allows to "render" objects.
     *
     * **Example:**
     *
     * ```php
     * // $node, instance of: Node\Model\Entity\Node
     * $this->render($node);
     *
     * // $block, instance of: Block\Model\Entity\Block
     * $this->render($block);
     *
     * // $field, instance of: Field\Model\Entity\Field
     * $this->render($field);
     * ```
     *
     * When rendering objects the `Render.<ClassName>` event is automatically
     * triggered. For example, when rendering a Node Entity the following event is
     * triggered, and event handlers should provide a HTML representation of the
     * given object, it basically works as the `__toString()` magic method:
     *
     * ```php
     * $someNode = TableRegistry::get('Node.Nodes')->get(1);
     * $this->render($someNode);
     * // triggers: Render.Node\Model\Entity\Node
     * ```
     *
     * It is not limited to Entity instances only, you can virtually define a
     * `Render` for any class name.
     *
     * You can pass an unlimited number of arguments to your `Render` as follow:
     *
     * ```php
     * $this->render($someObject, $arg1, $arg2, ...., $argn);
     * ```
     *
     * Your Render event-handler may look as below:
     *
     * ```php
     * public function renderMyObject(Event $event, $theObject, $arg1, $arg2, ..., $argn);
     * ```
     */
    public function render($view = null, $layout = null)
    {
        $html = '';
        if (is_object($view)) {
            $className = get_class($view);
            $args = func_get_args();
            array_shift($args);
            $args = array_merge([$view], (array)$args); // [entity, options]
            $event = new Event("Render.{$className}", $this, $args);
            EventManager::instance()->dispatch($event);
            $html = $event->result;
        } else {
            $this->alter('View.render', $view, $layout);
            if (isset($this->jQuery)) {
                $this->jQuery->load(['block' => true]);
            }

            if (!$this->_hasRendered) {
                $this->_hasRendered = true;
                $this->_setTitle();
                $this->_setDescription();
                $html = parent::render($view, $layout);
            }
        }

        return $this->hooktags($html);
    }

    /**
     * {@inheritDoc}
     *
     * Triggers the alter-event `View.element` (Alter.View.element).
     */
    public function element($name, array $data = [], array $options = [])
    {
        $this->alter('View.element', $name, $data, $options);
        return parent::element($name, $data, $options);
    }

    /**
     * {@inheritDoc}
     *
     * Adds fallback functionality, if layout is not found it uses QuickAppsCMS's
     * `default.ctp` as it will always exists.
     */
    protected function _getLayoutFileName($name = null)
    {
        try {
            $filename = parent::_getLayoutFileName($name);
        } catch (\Exception $e) {
            $filename = parent::_getLayoutFileName('default');
        }

        return $filename;
    }

    /**
     * Sets title for layout.
     *
     * It sets `title_for_layout` view variable, if no previous title was set on
     * controller. Site's title will be used if not found.
     *
     * @return void
     */
    protected function _setTitle()
    {
        if (empty($this->viewVars['title_for_layout'])) {
            $title = option('site_title');
            $this->assign('title', $title);
            $this->set('title_for_layout', $title);
        } else {
            $this->assign('title', $this->viewVars['title_for_layout']);
        }
    }

    /**
     * Sets meta-description for layout.
     *
     * It sets `description_for_layout` view-variable, and appends meta-description
     * tag to `meta` block. Site's description will be used if not found.
     *
     * @return void
     */
    protected function _setDescription()
    {
        if (empty($this->viewVars['description_for_layout'])) {
            $description = option('site_description');
            $this->assign('description', $description);
            $this->set('description_for_layout', $description);
            $this->append('meta', $this->Html->meta('description', $description));
        } else {
            $this->assign('description', $this->viewVars['description_for_layout']);
        }
    }
}
