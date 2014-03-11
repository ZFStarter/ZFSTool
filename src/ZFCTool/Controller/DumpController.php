<?php
/**
 * User: naxel
 * Date: 06.03.14 11:11
 */

namespace ZFCTool\Controller;

use Zend\EventManager\EventManagerInterface;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\Console\Request as ConsoleRequest;
use Zend\Console\Adapter\AdapterInterface as Console;
use Zend\Console\ColorInterface as Color;
use Zend\Console\Exception\RuntimeException;
use ZFCTool\Exception\ZFCToolException;
use ZFCTool\Service\DumpManager;

class DumpController extends AbstractActionController
{

    /** @var ConsoleRequest $request */
    protected $request;

    /** @var Console $console */
    protected $console;

    /** @var DumpManager $manager */
    protected $manager;

    /**
     * @param DumpManager $manager
     */
    public function setManager(DumpManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * @return DumpManager
     */
    public function getManager()
    {
        return $this->manager;
    }

    /**
     * @param Console $console
     */
    public function setConsole(Console $console)
    {
        $this->console = $console;
    }

    /**
     * @return Console
     */
    public function getConsole()
    {
        return $this->console;
    }

    /**
     * @param ConsoleRequest $request
     */
    public function setRequest(ConsoleRequest $request)
    {
        $this->request = $request;
    }

    /**
     * @return ConsoleRequest
     */
    public function getRequest()
    {
        return $this->request;
    }


    public function setEventManager(EventManagerInterface $events)
    {
        parent::setEventManager($events);

        $controller = $this;
        $events->attach('dispatch', function ($e) use ($controller) {

            /** @var ConsoleRequest */
            $request = $e->getRequest();

            if (!$request instanceof ConsoleRequest) {
                throw new RuntimeException('You can only use this action from a console!');
            }

            $console = $controller->getServiceLocator()->get('console');
            if (!$console instanceof Console) {
                throw new RuntimeException('Cannot obtain console adapter. Are we running in a console?');
            }

            $controller->setRequest($request);

            $controller->setConsole($console);

            /** @var DumpManager $manager */
            $manager = $controller->getServiceLocator()->get('DumpManager');
            $controller->setManager($manager);

        }, 100); // execute before executing action logic
    }


    /**
     * Method create dump of database
     */
    public function createAction()
    {
        $module = $this->request->getParam('module');
        if ($module) {
            $this->console->writeLine('Only for module "' . $module . '":');
        }

        $name = $this->request->getParam('name');
        $whitelist = $this->request->getParam('whitelist');
        $blacklist = $this->request->getParam('blacklist');

        $manager = $this->getManager();

        try {

            $result = $manager->create($module, $name, $whitelist, $blacklist);

            if ($result) {
                $this->console->writeLine('Database dump "' . $result . '" created!', Color::GREEN);
            }

        } catch (ZFCToolException $e) {
            $this->console->writeLine($e->getMessage(), Color::RED);
        } catch (\Exception $e) {
            $this->console->writeLine($e->getMessage(), Color::RED);
        }
    }


    /**
     * import dump in database
     */
    public function importAction()
    {
        $manager = $this->getManager();

        $module = $this->request->getParam('module');
        $name = $this->request->getParam('name');

        try {

            $result = $manager->import($name, $module);
            if ($result) {
                $this->console->writeLine('Database dump "' . $name . '" imported!', Color::GREEN);
            }

        } catch (ZFCToolException $e) {
            $this->console->writeLine($e->getMessage(), Color::RED);
        } catch (\Exception $e) {
            $this->console->writeLine($e->getMessage(), Color::RED);
        }
    }
}
